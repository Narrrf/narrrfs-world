const { SlashCommandBuilder } = require('@discordjs/builders');
const { EmbedBuilder } = require('discord.js');

const MOD_ROLE_ID = '1386472869290053662';

module.exports = {
    data: new SlashCommandBuilder()
        .setName('history')
        .setDescription('View point change history for yourself or another user')
        .addUserOption(option =>
            option.setName('user')
                .setDescription('User to view history for (mods only)')
                .setRequired(false)
        ),

    async execute(interaction, queryDb) {
        const targetUser = interaction.options.getUser('user') || interaction.user;
        const isSelf = (targetUser.id === interaction.user.id);
        const isMod = interaction.member.roles.cache.has(MOD_ROLE_ID);

        // Permission logic
        if (!isSelf && !isMod) {
            return interaction.reply({
                content: "You do not have permission to view other users' history.",
                flags: 64
            });
        }

        // Query last 20 adjustments for user
        const rows = await queryDb(
            `SELECT amount, action, reason, admin_id, timestamp
             FROM tbl_score_adjustments
             WHERE user_id = ?
             ORDER BY timestamp DESC LIMIT 20`,
            [targetUser.id]
        );

        if (!rows.length) {
            return interaction.reply({
                content: `No point changes found for ${targetUser.username}.`,
                flags: 64
            });
        }

        // Fetch usernames of admins for context
        let adminTags = {};
        if (!isSelf) {
            const adminIds = [...new Set(rows.map(r => r.admin_id))];
            for (let id of adminIds) {
                try {
                    const user = await interaction.client.users.fetch(id);
                    adminTags[id] = user.tag;
                } catch (e) {
                    adminTags[id] = id;
                }
            }
        }

        const lines = rows.map(r => {
            const adminInfo = adminTags[r.admin_id] ? ` by ${adminTags[r.admin_id]}` : '';
            return `[${r.timestamp.split('T')[0]}] ${r.amount > 0 ? '+' : ''}${r.amount} (${r.action})${adminInfo}\nReason: ${r.reason || '—'}`;
        });

        const embed = new EmbedBuilder()
            .setTitle(`${targetUser.username}'s $DSPOINC History`)
            .setDescription(lines.join('\n\n'))
            .setColor('#f0c92c')
            .setTimestamp();

        return interaction.reply({ embeds: [embed], flags: 64 });
    }
};
