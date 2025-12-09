const { REST, Routes } = require('discord.js');
const fs = require('fs');
const path = require('path');
require('dotenv').config();
// Optionally: const config = require('./config.js');

const commands = [];
const commandsPath = path.join(__dirname, 'commands');
const commandFiles = fs.readdirSync(commandsPath).filter(file => file.endsWith('.js'));

for (const file of commandFiles) {
    const filePath = path.join(commandsPath, file);
    const command = require(filePath);
    if ('data' in command) {
        commands.push(command.data.toJSON());
    }
}

// Use correct API version, always set a version string!
// 🚨 CRITICAL FIX: Increase timeout and add retry logic for network issues
const rest = new REST({ 
    version: '10',
    timeout: 30000, // 30 seconds timeout (default is 10 seconds)
    retries: 3, // Retry up to 3 times on failure
    retryDelay: 2000 // Wait 2 seconds between retries
}).setToken(process.env.DISCORD_BOT_SECRET);

// Retry function for command deployment
async function deployCommandsWithRetry(maxRetries = 3, retryDelay = 2000) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            console.log(`[DEPLOY] Attempt ${attempt}/${maxRetries}: Refreshing ${commands.length} application (/) commands...`);

            // Register all commands for a single guild (dev mode, fast)
            const data = await rest.put(
                Routes.applicationGuildCommands(process.env.DISCORD_CLIENT_ID, process.env.DISCORD_GUILD),
                { body: commands },
            );

            console.log(`✅ Successfully reloaded ${data.length} application (/) commands.`);
            return data;
        } catch (error) {
            console.error(`[DEPLOY] Attempt ${attempt}/${maxRetries} failed:`, error.message);
            
            // If it's a network timeout error and we have retries left, try again
            if (error.code === 'UND_ERR_CONNECT_TIMEOUT' && attempt < maxRetries) {
                console.log(`[DEPLOY] Network timeout detected. Retrying in ${retryDelay}ms...`);
                await new Promise(resolve => setTimeout(resolve, retryDelay));
                continue;
            }
            
            // If it's the last attempt or a different error, throw
            if (attempt === maxRetries) {
                console.error('❌ Command deployment failed after all retries:', error);
                throw error;
            }
        }
    }
}

(async () => {
    try {
        await deployCommandsWithRetry(3, 2000);
    } catch (error) {
        console.error('❌ Command deployment failed:', error);
        console.error('Error details:', {
            code: error.code,
            message: error.message,
            stack: error.stack
        });
        process.exit(1); // Exit with error code
    }
})();
