const { SlashCommandBuilder } = require('@discordjs/builders');

// Replace with your Mod role ID
const MOD_ROLE_ID = '1386472869290053662';

module.exports = {
    data: new SlashCommandBuilder()
        .setName('addpoints')
        .setDescription('Add $DSPOINC to a user (mods/admins only)')
        .addUserOption(option =>
            option.setName('user')
                .setDescription('User to add points to')
                .setRequired(true)
        )
        .addIntegerOption(option =>
            option.setName('amount')
                .setDescription('Amount to add')
                .setRequired(true)
        )
        .addStringOption(option =>
            option.setName('reason')
                .setDescription('Reason for adding points')
                .setRequired(false)
        ),
    async execute(interaction, queryDb) {
        // 1. Permissions check: Only allow users with the mod role
        if (!interaction.member.roles.cache.has(MOD_ROLE_ID)) {
            return interaction.reply({ content: 'You do not have permission to use this command.', flags: 64 });
        }

        // 2. Get target, amount, reason
        const user = interaction.options.getUser('user');
        const amount = interaction.options.getInteger('amount');
        const reason = interaction.options.getString('reason') || 'Manual admin adjustment';

        // 3. Update tbl_user_scores
        await queryDb(
            'UPDATE tbl_user_scores SET score = score + ? WHERE user_id = ?',
            [amount, user.id]
        );
        // 4. Log to tbl_score_adjustments
        await queryDb(
            'INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason) VALUES (?, ?, ?, ?, ?)',
            [user.id, interaction.user.id, amount, 'add', reason]
        );
        // 5. Reply
        await interaction.reply({ content: `Added ${amount} $DSPOINC to ${user.username}.\nReason: ${reason}` });
    }
};
