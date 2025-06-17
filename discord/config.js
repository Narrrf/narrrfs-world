module.exports = {
    // Bot configuration
    botToken: process.env.DISCORD_BOT_TOKEN || 'your-bot-token',
    clientId: process.env.DISCORD_CLIENT_ID || 'your-client-id',
    guildId: process.env.DISCORD_GUILD_ID || 'your-guild-id',
    
    // API configuration
    apiUrl: process.env.API_URL || 'https://narrrfs.world',
    
    // Command configuration
    commandsDir: './commands',
    
    // Embed colors
    colors: {
        primary: 0xf0c92c,
        error: 0xff0000,
        success: 0x00ff00
    }
}; 