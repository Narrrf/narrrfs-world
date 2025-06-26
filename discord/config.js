module.exports = {
    // Bot configuration
    botToken: process.env.DISCORD_BOT_SECRET || 'your-bot-token',     // Discord bot login ONLY
    apiSecret: process.env.DISCORD_SECRET || 'your-api-secret',       // For API requests to backend (PHP)
    clientId: process.env.DISCORD_CLIENT_ID || 'your-client-id',
    guildId: process.env.DISCORD_GUILD_ID || process.env.DISCORD_GUILD || '1332015322546311218', // supports both

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
