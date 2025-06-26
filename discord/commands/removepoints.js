const { SlashCommandBuilder } = require('@discordjs/builders');

const MOD_ROLE_ID = '1386472869290053662';

module.exports = {
    data: new SlashCommandBuilder()
        .setName('removepoints')
        .setDescription('Remove $DSPOINC from a user (mods/admins only)')
        .addUserOption(option =>
            option.setName('user')
                .setDescription('User to remove points from')
                .setRequired(true)
        )
        .addIntegerOption(option =>
            option.setName('amount')
                .setDescription('Amount to remove')
                .setRequired(true)
        )
        .addStringOption(option =>
            option.setName('reason')
                .setDescription('Reason for removing points')
                .setRequired(false)
        ),
    async execute(interaction, queryDb) {
        // Only users with mod role can use
        if (!interaction.member.roles.cache.has(MOD_ROLE_ID)) {
            return interaction.reply({ content: 'You do not have permission to use this command.', flags: 64 });
        }
        const user = interaction.options.getUser('user');
        const amount = interaction.options.getInteger('amount');
        const reason = interaction.options.getString('reason') || 'Manual admin adjustment';

        // Decrease user score
        await queryDb(
            'UPDATE tbl_user_scores SET score = score - ? WHERE user_id = ?',
            [amount, user.id]
        );
        // Log the adjustment
        await queryDb(
            'INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason) VALUES (?, ?, ?, ?, ?)',
            [user.id, interaction.user.id, -amount, 'remove', reason]
        );
        await interaction.reply({ content: `Removed ${amount} $DSPOINC from ${user.username}.\nReason: ${reason}` });
    }
};
