const { SlashCommandBuilder, EmbedBuilder, ActionRowBuilder, ButtonBuilder, ButtonStyle } = require('discord.js');

module.exports = {
  data: new SlashCommandBuilder()
    .setName('dashboard')
    .setDescription('Show your Narrrf\'s World Dashboard with live balance, links, and more'),

  async execute(interaction, queryDb) {
    // 1. Get user's balance
    const rows = await queryDb('SELECT score FROM tbl_user_scores WHERE user_id = ?', [interaction.user.id]);
    const balance = rows.length && typeof rows[0].score === 'number' ? rows[0].score : 0;
	
	// 1.5 Get recent adjustments for the user
const adjustments = await queryDb(
  'SELECT amount, reason, timestamp FROM tbl_score_adjustments WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5',
  [interaction.user.id]
);
const adjText = adjustments.length
  ? adjustments.map(a =>
      `${a.timestamp.slice(0, 19)}: ${a.amount > 0 ? '+' : ''}${a.amount} (${a.reason})`
    ).join('\n')
  : 'No recent changes.';
  
const traits = await queryDb('SELECT trait FROM tbl_user_traits WHERE user_id = ?', [interaction.user.id]);
const traitText = traits.length ? traits.map(t => t.trait).join(', ') : 'None';

const roles = await queryDb('SELECT role_name FROM tbl_user_roles WHERE user_id = ?', [interaction.user.id]);
const rolesText = roles.length ? roles.map(r => r.role_name).join(', ') : 'None';


    // 2. Check if wallet is linked (replace with real check if you track it!)
    // Here, just simulating true; implement your own lookup if needed
    const walletLinked = true; // Example only

    // 3. Build embed
const embed = new EmbedBuilder()
  .setTitle("Narrrf's Dashboard")
  .setDescription(`✨ **${balance.toLocaleString()} $DSPOINC**`)
  .setThumbnail(interaction.user.displayAvatarURL())
  .addFields(
    { name: 'Wallet Linked', value: walletLinked ? '✅ Yes' : '❌ Not linked', inline: true },
    { name: 'Recent Changes', value: `\`\`\`\n${adjText}\n\`\`\``, inline: false },
    { name: 'Traits', value: traitText, inline: false },
    { name: 'Roles', value: rolesText, inline: false }
  )
  .setColor(0xf0c92c)
  .setFooter({ text: 'Narrrf\'s World', iconURL: interaction.user.displayAvatarURL() })
  .setTimestamp();


    // 4. Build button row
    const dashboardUrl = `https://narrrfs.world/profile.html?discord_id=${interaction.user.id}`;
    const leaderboardUrl = 'https://narrrfs.world/leaderboard.html';
    const shareText = encodeURIComponent(`I have collected ${balance.toLocaleString()} $DSPOINC in Narrrf's World! Powered by #dripchain 🚀`);
    const shareUrl = `https://twitter.com/intent/tweet?text=${shareText}`;

    const row = new ActionRowBuilder().addComponents(
      new ButtonBuilder()
        .setLabel('🟡 My Dashboard')
        .setStyle(ButtonStyle.Link)
        .setURL(dashboardUrl),
      new ButtonBuilder()
        .setLabel('🏆 Leaderboard')
        .setStyle(ButtonStyle.Link)
        .setURL(leaderboardUrl),
      new ButtonBuilder()
        .setLabel('Share')
        .setStyle(ButtonStyle.Link)
        .setURL(shareUrl)
    );

    // 5. Reply to user (ephemeral/private, or set ephemeral: false to post in channel)
    await interaction.reply({
      content: `Welcome to your dashboard, ${interaction.user.username}!`,
      embeds: [embed],
      components: [row],
      flags: 64 // Ephemeral (private to user)
    });
  }
};
