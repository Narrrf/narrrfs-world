const { SlashCommandBuilder } = require('@discordjs/builders');
const { EmbedBuilder } = require('discord.js');
const config = require('../config.js');

// You must pass the queryDb function to this command in your bot code!
module.exports = {
    data: new SlashCommandBuilder()
        .setName('balance')
        .setDescription('Check your $DSPOINC balance')
        .addUserOption(option =>
            option.setName('user')
                .setDescription('User to check balance for (optional)')
                .setRequired(false)
        ),

    async execute(interaction, queryDb) {
        try {
            await interaction.deferReply();
            const targetUser = interaction.options.getUser('user') || interaction.user;

            // Direct DB query for DSPOINC
            const rows = await queryDb(
                'SELECT score FROM tbl_user_scores WHERE user_id = ?',
                [targetUser.id]
            );
            let balance = 0;
            if (rows.length > 0 && typeof rows[0].score === 'number') {
                balance = rows[0].score;
            }

            // Embed reply
            const embed = new EmbedBuilder()
                .setColor(config.colors.primary)
                .setTitle(`${targetUser.username}'s Balance`)
                .setThumbnail(targetUser.displayAvatarURL())
                .addFields({ name: '$DSPOINC Balance', value: balance.toLocaleString(), inline: true })
                .setFooter({
                    text: 'Narrrf\'s World',
                    iconURL: `${config.apiUrl}/img/cheese.png`
                })
                .setTimestamp();

            await interaction.editReply({ embeds: [embed] });

        } catch (error) {
            console.error('Balance command error:', error);
            const errorMessage = 'Sorry, there was an error fetching the balance. Please try again later.\n' +
                'Error details: ' + error.message;
            try {
                if (interaction.deferred) {
                    await interaction.editReply({
                        content: errorMessage,
                        ephemeral: true
                    });
                } else {
                    await interaction.reply({
                        content: errorMessage,
                        ephemeral: true
                    });
                }
            } catch (replyError) {
                console.error('Error sending error message:', replyError);
            }
        }
    },
};
