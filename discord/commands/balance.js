const { SlashCommandBuilder } = require('@discordjs/builders');
const { EmbedBuilder } = require('discord.js');
const fetch = require('node-fetch');
require('dotenv').config();

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
        await interaction.deferReply();

        try {
            // Get target user (mentioned user or command user)
            const targetUser = interaction.options.getUser('user') || interaction.user;
            
            // Fetch balance from API
            const response = await fetch(
                `https://www.narrrfs.world/api/discord/get-balance.php?user_id=${targetUser.id}`,
                {
                    headers: {
                        'Authorization': `Bot ${process.env.DISCORD_BOT_SECRET}`
                    }
                }
            );

            const data = await response.json();

            if (!data.success) {
                if (data.error === 'User not found') {
                    return interaction.editReply({
                        content: `${targetUser.username} doesn't have a Narrrf's World account yet! They need to connect their wallet first at https://www.narrrfs.world`,
                        ephemeral: true
                    });
                }
                throw new Error(data.error);
            }

            // Create embed
            const embed = new EmbedBuilder()
                .setColor(0xf0c92c)
                .setTitle(`${targetUser.username}'s Balance`)
                .setThumbnail(targetUser.displayAvatarURL())
                .addFields(
                    { name: '$DSPOINC Balance', value: data.balance.toLocaleString(), inline: true }
                )
                .setFooter({ 
                    text: 'Narrrf\'s World',
                    iconURL: 'https://www.narrrfs.world/img/cheese.png'
                })
                .setTimestamp();

            await interaction.editReply({ embeds: [embed] });

        } catch (error) {
            console.error('Balance command error:', error);
            await interaction.editReply({
                content: 'Sorry, there was an error fetching the balance. Please try again later.',
                ephemeral: true
            });
        }
    },
}; 