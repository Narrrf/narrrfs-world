// /commands/quest.js
const { SlashCommandBuilder, EmbedBuilder, ActionRowBuilder, ButtonBuilder, ButtonStyle } = require('discord.js');

// Predefined durations for quests
const DURATION_CHOICES = [
  { name: '1 Hour', value: '1h' },
  { name: '6 Hours', value: '6h' },
  { name: '1 Day', value: '1d' },
  { name: '2 Days', value: '2d' },
  { name: '3 Days', value: '3d' },
  { name: 'Custom', value: 'custom' }
];

module.exports = {
  data: new SlashCommandBuilder()
    .setName('quest')
    .setDescription('Post, join, review, or manage a Twitter/custom quest')
    .addSubcommand(sub =>
      sub.setName('create')
        .setDescription('Create a new quest (admin)')
        // --- ALL REQUIRED FIRST! ---
        .addStringOption(opt =>
          opt.setName('mode')
            .setDescription('Quest type')
            .setRequired(true)
            .addChoices(
              { name: 'Tweet Quest', value: 'tweet' },
              { name: 'Custom Quest', value: 'custom' }
            )
        )
        .addIntegerOption(opt =>
          opt.setName('reward').setDescription('$DSPOINC reward').setRequired(true)
        )
        // --- THEN OPTIONAL ---
        .addStringOption(opt =>
          opt.setName('tweet_url').setDescription('Tweet URL (if Tweet Quest)')
        )
        .addStringOption(opt =>
          opt.setName('type').setDescription('Required action (RT, Like, Comment, etc)')
        )
        .addStringOption(opt =>
          opt.setName('description').setDescription('Quest description (required for Custom)')
        )
        .addStringOption(opt =>
          opt.setName('duration')
            .setDescription('Quest duration')
            .addChoices(...DURATION_CHOICES)
        )
        .addStringOption(opt =>
          opt.setName('custom_duration').setDescription('Custom duration (e.g. 2d 12h)')
        )
        .addStringOption(opt =>
          opt.setName('expires_at').setDescription('Expiration time (YYYY-MM-DD HH:MM:SS)')
        )
    )
    .addSubcommand(sub =>
      sub.setName('claims')
        .setDescription('Review pending quest claims (mod/admin)')
        .addStringOption(opt => opt.setName('quest_id').setDescription('Quest ID (optional)'))
    )
    .addSubcommand(sub =>
      sub.setName('list').setDescription('List all active quests')
    )
    .addSubcommand(sub =>
      sub.setName('close')
        .setDescription('Close an active quest (admin)')
        .addStringOption(opt => opt.setName('quest_id').setDescription('Quest ID to close').setRequired(true))
    ),

  async execute(interaction, queryDb) {
    const sub = interaction.options.getSubcommand();

    // --- CREATE QUEST ---
    if (sub === 'create') {
      if (
        !interaction.member.permissions.has('Administrator') &&
        !interaction.member.permissions.has('ManageGuild')
      ) {
        return interaction.reply({ content: 'Admins only!', ephemeral: true });
      }
      const mode = interaction.options.getString('mode');
      const reward = interaction.options.getInteger('reward');
      const tweetUrl = interaction.options.getString('tweet_url');
      const type = interaction.options.getString('type');
      const description = interaction.options.getString('description');
      const duration = interaction.options.getString('duration');
      const customDuration = interaction.options.getString('custom_duration');
      const expiresAt = interaction.options.getString('expires_at');
      const createdBy = interaction.user.id;

      // --- Duration logic ---
      let expiresTimestamp = null;
      if (duration && duration !== 'custom') {
        const now = Date.now();
        let ms = 0;
        if (duration.endsWith('h')) ms = parseInt(duration) * 60 * 60 * 1000;
        else if (duration.endsWith('d')) ms = parseInt(duration) * 24 * 60 * 60 * 1000;
        if (ms > 0) expiresTimestamp = new Date(now + ms).toISOString();
      } else if (duration === 'custom' && customDuration) {
        expiresTimestamp = customDuration;
      } else if (expiresAt) {
        expiresTimestamp = expiresAt;
      }

      // --- Validation ---
      let questType, questDesc, questLink;
      if (mode === 'tweet') {
        questType = type;
        questLink = tweetUrl;
        if (!questType || !questLink) return interaction.reply({ content: 'Tweet quests need both a type and a tweet URL!', ephemeral: true });
        questDesc = `Do this: **${questType}**\n[View Tweet](${questLink})`;
      } else if (mode === 'custom') {
        questType = 'Custom';
        questDesc = description;
        questLink = null;
        if (!questDesc) return interaction.reply({ content: 'Custom quest requires a description.', ephemeral: true });
      } else {
        return interaction.reply({ content: 'Invalid quest mode.', ephemeral: true });
      }

      // --- Insert into DB ---
      let questId = null;
      try {
        const result = await queryDb(
          'INSERT INTO tbl_quests (type, description, link, reward, created_by, is_active, expires_at) VALUES (?, ?, ?, ?, ?, 1, ?)',
          [questType, questDesc, questLink, reward, createdBy, expiresTimestamp]
        );
        questId = (result && result.insertId) || (result && result.quest_id) || (result && result.id) || 'N/A';
      } catch (err) {
        console.error('[QUEST CREATE DB ERROR]', err);
        return interaction.reply({ content: 'Failed to create quest (DB error).', ephemeral: true });
      }

      // --- Embed ---
      const embed = new EmbedBuilder()
        .setTitle(mode === 'tweet' ? '🚀 New Tweet Quest' : '🚀 New Custom Quest')
        .setDescription(
          `${questDesc}\n\n**Reward:** ${reward.toLocaleString()} $DSPOINC\n${expiresTimestamp ? `**Expires:** ${expiresTimestamp}` : ''}\n**Quest ID:** ${questId}`
        )
        .setFooter({ text: `Posted by ${interaction.user.username}` })
        .setColor(0x4fd1c5);

      const row = new ActionRowBuilder().addComponents(
        new ButtonBuilder()
          .setLabel('✅ I DID IT!')
          .setStyle(ButtonStyle.Success)
          .setCustomId(`quest_claim_${questId}`)
      );
      await interaction.reply({ embeds: [embed], components: [row] });
      return;
    }

    // --- CLAIMS REVIEW ---
    if (sub === 'claims') {
      if (!interaction.member.permissions.has('ManageGuild')) {
        return interaction.reply({ content: 'Only mods/admins!', ephemeral: true });
      }
      const questId = interaction.options.getString('quest_id');
      let claims;
      if (questId) {
        claims = await queryDb(
          'SELECT qc.*, u.username FROM tbl_quest_claims qc LEFT JOIN tbl_users u ON qc.user_id = u.discord_id WHERE quest_id = ? AND (qc.status IS NULL OR qc.status = "pending") ORDER BY claimed_at ASC LIMIT 25',
          [questId]
        );
      } else {
        claims = await queryDb(
          'SELECT qc.*, u.username, q.description FROM tbl_quest_claims qc LEFT JOIN tbl_users u ON qc.user_id = u.discord_id LEFT JOIN tbl_quests q ON qc.quest_id = q.quest_id WHERE (qc.status IS NULL OR qc.status = "pending") ORDER BY claimed_at ASC LIMIT 25'
        );
      }

      if (!claims || !claims.length) {
        return interaction.reply({ content: 'No pending claims found!', ephemeral: true });
      }
      for (const claim of claims) {
        const embed = new EmbedBuilder()
          .setTitle('🚩 Pending Quest Claim')
          .addFields(
            { name: 'Quest', value: claim.description || `ID: ${claim.quest_id}` },
            { name: 'User', value: `<@${claim.user_id}> (${claim.username || 'Unknown'})`, inline: true },
            { name: 'Claimed At', value: claim.claimed_at, inline: true },
            { name: 'Quest ID', value: String(claim.quest_id), inline: true }
          )
          .setColor(0xfbbf24)
          .setFooter({ text: `Claim ID: ${claim.id || claim.claim_id || 'N/A'}` });

        const row = new ActionRowBuilder().addComponents(
          new ButtonBuilder()
            .setCustomId(`verify_quest_${claim.claim_id || claim.id || `${claim.quest_id}_${claim.user_id}`}`)
            .setLabel('✅ Verify & Pay')
            .setStyle(ButtonStyle.Success),
          new ButtonBuilder()
            .setCustomId(`reject_quest_${claim.claim_id || claim.id || `${claim.quest_id}_${claim.user_id}`}`)
            .setLabel('❌ Reject')
            .setStyle(ButtonStyle.Danger)
        );
        await interaction.channel.send({ embeds: [embed], components: [row] });
      }
      return interaction.reply({ content: 'Pending claims shown above.', ephemeral: true });
    }

    // --- LIST ACTIVE QUESTS ---
    if (sub === 'list') {
      const quests = await queryDb(
        'SELECT quest_id, type, link, description, reward, is_active, expires_at FROM tbl_quests WHERE is_active = 1 ORDER BY created_at DESC LIMIT 20'
      );
      if (!quests || !quests.length) {
        return interaction.reply({ content: 'No active quests found.', ephemeral: true });
      }
      const embed = new EmbedBuilder()
        .setTitle('Active Quests')
        .setDescription(
          quests.map(q =>
            `**#${q.quest_id}**: ${q.link ? `[Link](${q.link})` : ''} (${q.type})\n${q.description}\nReward: ${q.reward} $DSPOINC\n${q.expires_at ? `Expires: ${q.expires_at}` : ''}`
          ).join('\n\n')
        )
        .setColor(0x4fd1c5);
      return interaction.reply({ embeds: [embed], ephemeral: true });
    }

    // --- CLOSE QUEST ---
    if (sub === 'close') {
      if (!interaction.member.permissions.has('ManageGuild')) {
        return interaction.reply({ content: 'Only mods/admins!', ephemeral: true });
      }
      const questId = interaction.options.getString('quest_id');
      const updated = await queryDb(
        'UPDATE tbl_quests SET is_active = 0 WHERE quest_id = ?',
        [questId]
      );
      if (updated && updated.affectedRows > 0) {
        return interaction.reply({ content: `Quest #${questId} closed successfully.`, ephemeral: true });
      } else {
        return interaction.reply({ content: `Quest #${questId} not found or already closed.`, ephemeral: true });
      }
    }
  }
};
