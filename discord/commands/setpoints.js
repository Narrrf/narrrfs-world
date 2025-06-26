const { SlashCommandBuilder } = require('@discordjs/builders');

const MOD_ROLE_ID = '1386472869290053662';

module.exports = {
    data: new SlashCommandBuilder()
        .setName('setpoints')
        .setDescription('Set a user\'s $DSPOINC to a specific value (mods only)')
        .addUserOption(option =>
            option.setName('user')
                .setDescription('User to set points for')
                .setRequired(true)
        )
        .addIntegerOption(option =>
            option.setName('amount')
                .setDescription('New total $DSPOINC')
                .setRequired(true)
        )
        .addStringOption(option =>
            option.setName('reason')
                .setDescription('Reason for setting points')
                .setRequired(false)
        ),
    async execute(interaction, queryDb) {
        // 1. Permissions check: Only mod role
        if (!interaction.member.roles.cache.has(MOD_ROLE_ID)) {
            return interaction.reply({ content: 'You do not have permission to use this command.', flags: 64 });
        }

        // 2. Get target, amount, reason
        const user = interaction.options.getUser('user');
        const newAmount = interaction.options.getInteger('amount');
        const reason = interaction.options.getString('reason') || 'Manual set by admin';

        // 3. Get current amount
        const rows = await queryDb(
            'SELECT score FROM tbl_user_scores WHERE user_id = ?',
            [user.id]
        );
        const oldAmount = rows.length ? rows[0].score : 0;
        const delta = newAmount - oldAmount;

        // 4. Set new score
        await queryDb(
            'UPDATE tbl_user_scores SET score = ? WHERE user_id = ?',
            [newAmount, user.id]
        );
        // 5. Log to tbl_score_adjustments (action: 'set')
        await queryDb(
            'INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason) VALUES (?, ?, ?, ?, ?)',
            [user.id, interaction.user.id, delta, 'set', reason]
        );

        // 6. Reply
        await interaction.reply({
            content: `Set ${user.username}'s $DSPOINC to ${newAmount} (was ${oldAmount}).\nReason: ${reason}`,
            flags: 64
        });
    }
};
