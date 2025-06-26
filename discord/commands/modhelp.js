// /commands/modhelp.js
const { SlashCommandBuilder, EmbedBuilder } = require('discord.js');

module.exports = {
  data: new SlashCommandBuilder()
    .setName('modhelp')
    .setDescription('Show detailed mod/admin command overview'),
  async execute(interaction) {
    // Only for admins/mods
    if (
      !interaction.member.permissions.has('ManageGuild') &&
      !interaction.member.permissions.has('Administrator')
    ) {
      return interaction.reply({ content: 'Only mods/admins!', ephemeral: true });
    }

    const embed = new EmbedBuilder()
      .setTitle('🧀 Mod & Admin Command Help — Narrrf\'s World')
      .setDescription(
        'A full, updated reference for **cheese admins, mods, and quest runners**.\n' +
        'Use these commands in **admin/mod channels** only. For questions, ping a super-admin! All actions are logged for transparency.\n\n' +
        '**NEW:**\n' +
        '• Custom and Tweet Quests\n' +
        '• Duration & expiration\n' +
        '• Full claims/review pipeline\n' +
        '• Inventory, store, leaderboard and more\n'
      )
      .addFields(
        {
          name: '🔢 `/addpoints`',
          value: 'Add points to any user.\n> `/addpoints @user 123 reason:<text>`\nQuickly reward for ANY action.',
        },
        {
          name: '🧮 `/setpoints`',
          value: 'Set a user\'s score directly.\n> `/setpoints @user 42000 reason:<reset/correction>`',
        },
        {
          name: '🏪 `/store`',
          value: 'Browse and buy store items:\n• `/store view` — see items\n• `/store buy item_id:X quantity:Y` — buy',
        },
        {
          name: '🚀 `/quest create`',
          value:
            '**Create ANY quest!**\n' +
            '> `/quest create mode:<tweet/custom> reward:<number> ...other fields...`\n\n' +
            '• **mode:** `tweet` for Twitter, `custom` for any custom mission\n' +
            '• **tweet_url:** The tweet to act on (for tweet quests)\n' +
            '• **type:** RT, Like, Comment, etc\n' +
            '• **description:** (Custom quest description)\n' +
            '• **reward:** $DSPOINC paid out\n' +
            '• **duration/expires_at:** Auto-calculated expiry (1h, 6h, 2d, etc or custom timestamp)\n' +
            '\n_This posts a live quest with a **"I DID IT"** claim button. All claims are saved and must be verified._',
        },
        {
          name: '📝 `/quest claims`',
          value:
            'Review **pending quest claims** for payout (mod only):\n' +
            '`/quest claims [quest_id]`\n' +
            '• Shows up to 25 pending claims (use `quest_id` to filter).\n' +
            '• Each comes with **Verify & Pay** and **Reject** buttons.\n' +
            '• All claim actions are logged for audit.',
        },
        {
          name: '📋 `/quest list`',
          value:
            'See all currently active quests (any user can run):\n' +
            '`/quest list`',
        },
        {
          name: '❌ `/quest close`',
          value:
            'Close a quest for new claims (mod only):\n' +
            '`/quest close quest_id:<id>`',
        },
        {
          name: '📊 `/dashboard`',
          value:
            'See your live user dashboard (private only to you). Includes:\n' +
            '• Balance, traits, roles, trophies, etc.',
        },
        {
          name: '💰 `/balance`',
          value: 'Check your $DSPOINC balance.',
        },
        {
          name: '🏆 `/leaderboard`',
          value:
            'Top cheese collectors, sortable, always fresh. Only **one high score per user**!',
        },
        {
          name: '🎁 `/rewards`',
          value:
            'See all unlocked rewards, badges, traits, inventory, store purchases.',
        },
        {
          name: '❓ **EXTRA TIPS**',
          value:
            '• All moderation is logged — claims, point changes, and store actions.\n' +
            '• `/modhelp` is always updated as features evolve.\n' +
            '• For manual interventions, use `/setpoints` with reason field.\n' +
            '• To view or re-check quest claims, just use `/quest claims` anytime.',
        }
      )
      .setColor(0xf0c92c)
      .setFooter({
        text: 'For more help, ping a super-admin or see narrrfs.world. Actions are always logged.',
      })
      .setTimestamp();

    await interaction.reply({ embeds: [embed], ephemeral: false }); // Visible to all mods in the channel
  },
};
