const { SlashCommandBuilder } = require('@discordjs/builders');
const { EmbedBuilder } = require('discord.js');
const fetch = require('node-fetch');
const config = require('../config.js');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('inventory')
        .setDescription('View your inventory of items')
        .addUserOption(option =>
            option.setName('user')
                .setDescription('User to check inventory for (optional)')
                .setRequired(false)),

    async execute(interaction) {
        try {
            await interaction.deferReply();
            
            // Get target user (mentioned user or command user)
            const targetUser = interaction.options.getUser('user') || interaction.user;
            
            // Fetch inventory
            const response = await fetch(`${config.apiUrl}/api/store/inventory.php?user_id=${targetUser.id}`, {
                headers: {
                    'Authorization': `Bot ${config.botToken}`
                }
            });

            const data = await response.json();
            
            if (!data.success) {
                if (data.error === 'No items found') {
                    const embed = new EmbedBuilder()
                        .setColor(config.colors.primary)
                        .setTitle(`${targetUser.username}'s Inventory`)
                        .setDescription('No items found. Visit the store to buy some!')
                        .setThumbnail(targetUser.displayAvatarURL());
                    
                    await interaction.editReply({ embeds: [embed] });
                    return;
                }
                throw new Error(data.error || 'Failed to fetch inventory');
            }

            // Create inventory display embed
            const embed = new EmbedBuilder()
                .setColor(config.colors.primary)
                .setTitle(`${targetUser.username}'s Inventory`)
                .setThumbnail(targetUser.displayAvatarURL());

            // Group items by type and add to embed
            data.items.forEach(item => {
                embed.addFields({
                    name: item.name,
                    value: `Quantity: ${item.quantity}\n${item.description || ''}`,
                    inline: true
                });
            });

            if (data.items.length === 0) {
                embed.setDescription('No items found. Visit the store to buy some!');
            }

            await interaction.editReply({ embeds: [embed] });

        } catch (error) {
            console.error('Inventory command error:', error);
            await interaction.editReply({
                content: 'There was an error fetching the inventory. Please try again later.',
                ephemeral: true
            });
        }
    },
}; 