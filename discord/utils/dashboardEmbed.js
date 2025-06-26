// utils/dashboardEmbed.js
const { EmbedBuilder, ActionRowBuilder, ButtonBuilder, ButtonStyle } = require('discord.js');

async function buildDashboardEmbed(userId, username, avatarURL, queryDb) {
  // Query balance
  const rows = await queryDb('SELECT score FROM tbl_user_scores WHERE user_id = ?', [userId]);
  const balance = rows.length && typeof rows[0].score === 'number' ? rows[0].score : 0;

  // Recent changes
  const adjustments = await queryDb(
    'SELECT amount, reason, timestamp FROM tbl_score_adjustments WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5',
    [userId]
  );
  const adjText = adjustments.length
    ? adjustments.map(a =>
        `${a.timestamp.slice(0, 19)}: ${a.amount > 0 ? '+' : ''}${a.amount} (${a.reason})`
      ).join('\n')
    : 'No recent changes.';

  // Traits
  const traits = await queryDb('SELECT trait FROM tbl_user_traits WHERE user_id = ?', [userId]);
  const traitText = traits.length ? traits.map(t => t.trait).join(', ') : 'None';

  // Roles
  const roles = await queryDb('SELECT role_name FROM tbl_user_roles WHERE user_id = ?', [userId]);
  const rolesText = roles.length ? roles.map(r => r.role_name).join(', ') : 'None';

  const walletLinked = true; // Your logic here

  const embed = new EmbedBuilder()
    .setTitle("Narrrf's Dashboard")
    .setDescription(`✨ **${balance.toLocaleString()} $DSPOINC**`)
    .setThumbnail(avatarURL)
    .addFields(
      { name: 'Wallet Linked', value: walletLinked ? '✅ Yes' : '❌ Not linked', inline: true },
      { name: 'Recent Changes', value: `\`\`\`\n${adjText}\n\`\`\``, inline: false },
      { name: 'Traits', value: traitText, inline: false },
      { name: 'Roles', value: rolesText, inline: false }
    )
    .setColor(0xf0c92c)
    .setFooter({ text: "Narrrf's World", iconURL: avatarURL })
    .setTimestamp();

  const dashboardUrl = `https://narrrfs.world/profile.html?discord_id=${userId}`;
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

  return { embed, row };
}

module.exports = { buildDashboardEmbed };
