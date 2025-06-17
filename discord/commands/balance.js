const { SlashCommandBuilder } = require('@discordjs/builders');
const { EmbedBuilder } = require('discord.js');
const fetch = require('node-fetch');
const config = require('../config.js');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('balance')
        .setDescription('Check your $DSPOINC balance')
        .addUserOption(option => 
            option.setName('user')
                .setDescription('User to check balance for (optional)')
                .setRequired(false)
        ),
    
    async execute(interaction) {
        try {
            await interaction.deferReply();
            console.log('Balance command started for user:', interaction.user.tag);

            // Get target user (mentioned user or command user)
            const targetUser = interaction.options.getUser('user') || interaction.user;
            console.log('Target user:', targetUser.tag);
            
            // Construct API URL using config
            const apiUrl = `${config.apiUrl}/api/discord/get-balance.php?user_id=${targetUser.id}`;
            console.log('Fetching balance from:', apiUrl);

            // Fetch balance from API
            const response = await fetch(apiUrl, {
                headers: {
                    'Authorization': `Bot ${config.botToken}`
                }
            });

            console.log('API Response status:', response.status);
            const data = await response.json();
            console.log('API Response data:', data);

            if (!data.success) {
                if (data.error === 'User not found') {
                    await interaction.editReply({
                        content: `${targetUser.username} doesn't have a Narrrf's World account yet! They need to connect their wallet first at ${config.apiUrl}`,
                        ephemeral: true
                    });
                    return;
                }
                throw new Error(data.error || 'Unknown API error');
            }

            // Create embed
            const embed = new EmbedBuilder()
                .setColor(config.colors.primary)
                .setTitle(`${targetUser.username}'s Balance`)
                .setThumbnail(targetUser.displayAvatarURL())
                .addFields(
                    { name: '$DSPOINC Balance', value: data.balance.toLocaleString(), inline: true }
                )
                .setFooter({ 
                    text: 'Narrrf\'s World',
                    iconURL: `${config.apiUrl}/img/cheese.png`
                })
                .setTimestamp();

            await interaction.editReply({ embeds: [embed] });

        } catch (error) {
            console.error('Balance command error:', error);
            // Log full error details
            console.error('Full error:', {
                message: error.message,
                stack: error.stack,
                cause: error.cause
            });

            // Send error message to user
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