const { SlashCommandBuilder } = require('@discordjs/builders');
const { EmbedBuilder, ActionRowBuilder, ButtonBuilder, ButtonStyle } = require('discord.js');
const fetch = require('node-fetch');
const config = require('../config.js');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('store')
        .setDescription('View and purchase items from the store')
        .addSubcommand(subcommand =>
            subcommand
                .setName('view')
                .setDescription('View available items in the store'))
        .addSubcommand(subcommand =>
            subcommand
                .setName('buy')
                .setDescription('Purchase an item from the store')
                .addStringOption(option =>
                    option.setName('item_id')
                        .setDescription('The ID of the item to purchase')
                        .setRequired(true))
                .addIntegerOption(option =>
                    option.setName('quantity')
                        .setDescription('How many to buy (default: 1)')
                        .setRequired(false))),

    async execute(interaction) {
        try {
            await interaction.deferReply();
            const subcommand = interaction.options.getSubcommand();

            if (subcommand === 'view') {
                // Fetch store items
                const response = await fetch(`${config.apiUrl}/api/store/items.php`, {
                    headers: {
                        'Authorization': `Bot ${config.botToken}`
                    }
                });

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Failed to fetch store items');
                }

                // Create store display embed
                const embed = new EmbedBuilder()
                    .setColor(config.colors.primary)
                    .setTitle('🏪 Narrrf\'s World Store')
                    .setDescription('Use `/store buy item_id` to purchase items');

                // Add items to embed
                data.items.forEach(item => {
                    embed.addFields({
                        name: `${item.name} (ID: ${item.item_id})`,
                        value: `${item.description}\nPrice: ${item.price.toLocaleString()} $DSPOINC`,
                        inline: true
                    });
                });

                await interaction.editReply({ embeds: [embed] });

            } else if (subcommand === 'buy') {
                const itemId = interaction.options.getString('item_id');
                const quantity = interaction.options.getInteger('quantity') || 1;

                // Attempt purchase
                const response = await fetch(`${config.apiUrl}/api/store/purchase.php`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bot ${config.botToken}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: interaction.user.id,
                        item_id: itemId,
                        quantity: quantity
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    await interaction.editReply({
                        content: `❌ ${data.error || 'Failed to make purchase'}`,
                        ephemeral: true
                    });
                    return;
                }

                // Create success embed
                const embed = new EmbedBuilder()
                    .setColor(config.colors.success)
                    .setTitle('✅ Purchase Successful!')
                    .setDescription(`You bought ${quantity}x ${data.item.name} for ${data.total_price.toLocaleString()} $DSPOINC`)
                    .setFooter({ 
                        text: 'Use /inventory to view your items',
                        iconURL: interaction.user.displayAvatarURL()
                    });

                await interaction.editReply({ embeds: [embed] });
            }

        } catch (error) {
            console.error('Store command error:', error);
            await interaction.editReply({
                content: 'There was an error with the store command. Please try again later.',
                ephemeral: true
            });
        }
    },
}; 