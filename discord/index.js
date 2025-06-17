require('dotenv').config();
const { Client, Collection, GatewayIntentBits } = require('discord.js');
const fs = require('fs');
const path = require('path');
const config = require('./config.js');
const sqlite3 = require('sqlite3');

// Always use the main database path
const DB_PATH = process.env.RENDER ? '/var/www/html/db/narrrf_world.sqlite' : path.join(__dirname, '..', 'db', 'narrrf_world.sqlite');
console.log('Using database at:', DB_PATH);

// Create client instance
const client = new Client({ 
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMessages,
        GatewayIntentBits.GuildMembers
    ]
});

// Create commands collection
client.commands = new Collection();

// Load commands
const commandsPath = path.join(__dirname, 'commands');
const commandFiles = fs.readdirSync(commandsPath).filter(file => file.endsWith('.js'));

for (const file of commandFiles) {
    const filePath = path.join(commandsPath, file);
    const command = require(filePath);
    
    if ('data' in command && 'execute' in command) {
        client.commands.set(command.data.name, command);
    }
}

// Handle interactions
client.on('interactionCreate', async interaction => {
    if (!interaction.isChatInputCommand()) return;

    const command = client.commands.get(interaction.commandName);

    if (!command) return;

    try {
        await command.execute(interaction);
    } catch (error) {
        console.error(error);
        if (interaction.replied || interaction.deferred) {
            await interaction.followUp({ 
                content: 'There was an error executing this command!', 
                ephemeral: true 
            });
        } else {
            await interaction.reply({ 
                content: 'There was an error executing this command!', 
                ephemeral: true 
            });
        }
    }
});

// Add these event handlers after the ready event
client.on('guildMemberAdd', async member => {
    try {
        // Add user to database
        const db = new sqlite3.Database(DB_PATH);
        
        // Check if user exists
        const user = await new Promise((resolve, reject) => {
            db.get('SELECT discord_id FROM tbl_users WHERE discord_id = ?', [member.id], (err, row) => {
                if (err) reject(err);
                resolve(row);
            });
        });
        
        if (!user) {
            // Add new user
            await new Promise((resolve, reject) => {
                db.run('INSERT INTO tbl_users (discord_id, username) VALUES (?, ?)', 
                    [member.id, member.user.username], 
                    (err) => {
                        if (err) reject(err);
                        resolve();
                    });
            });
            console.log(`Added new user ${member.user.username} (${member.id}) to database`);
        }
        
        db.close();
    } catch (error) {
        console.error('Error syncing new member:', error);
    }
});

client.on('userUpdate', async (oldUser, newUser) => {
    if (oldUser.username !== newUser.username) {
        try {
            const db = new sqlite3.Database(DB_PATH);
            
            // Update username
            await new Promise((resolve, reject) => {
                db.run('UPDATE tbl_users SET username = ? WHERE discord_id = ?', 
                    [newUser.username, newUser.id], 
                    (err) => {
                        if (err) reject(err);
                        resolve();
                    });
            });
            
            console.log(`Updated username for ${newUser.id} from ${oldUser.username} to ${newUser.username}`);
            db.close();
        } catch (error) {
            console.error('Error updating username:', error);
        }
    }
});

// Add this function after the event handlers
async function syncAllUsers() {
    try {
        const guild = client.guilds.cache.get(config.guildId);
        const members = await guild.members.fetch();
        const db = new sqlite3.Database(DB_PATH);
        
        for (const [id, member] of members) {
            // Check if user exists
            const user = await new Promise((resolve, reject) => {
                db.get('SELECT discord_id FROM tbl_users WHERE discord_id = ?', [id], (err, row) => {
                    if (err) reject(err);
                    resolve(row);
                });
            });
            
            if (!user) {
                // Add new user
                await new Promise((resolve, reject) => {
                    db.run('INSERT INTO tbl_users (discord_id, username) VALUES (?, ?)', 
                        [id, member.user.username], 
                        (err) => {
                            if (err) reject(err);
                            resolve();
                        });
                });
                console.log(`Added user ${member.user.username} (${id}) during sync`);
            }
        }
        
        db.close();
        console.log('Completed user sync');
    } catch (error) {
        console.error('Error during user sync:', error);
    }
}

// Add this to the ready event
client.once('ready', async () => {
    console.log(`🚀 Narrrf's World Bot is ready!`);
    console.log(`👋 Logged in as ${client.user.tag}`);
    
    // Check for specific user
    try {
        const guild = client.guilds.cache.get(config.guildId);
        const member = await guild.members.fetch('734200554322001922');
        
        if (member) {
            console.log('Found cryptodaniel:', {
                id: member.id,
                username: member.user.username,
                nickname: member.nickname,
                roles: member.roles.cache.map(r => r.name)
            });
            
            // Add to database if not exists
            const db = new sqlite3.Database(DB_PATH);
            
            // Check if user exists
            const user = await new Promise((resolve, reject) => {
                db.get('SELECT discord_id FROM tbl_users WHERE discord_id = ?', [member.id], (err, row) => {
                    if (err) reject(err);
                    resolve(row);
                });
            });
            
            if (!user) {
                // Add new user
                await new Promise((resolve, reject) => {
                    db.run('INSERT INTO tbl_users (discord_id, username) VALUES (?, ?)', 
                        [member.id, member.user.username], 
                        (err) => {
                            if (err) reject(err);
                            resolve();
                        });
                });
                console.log(`Added cryptodaniel to database`);
            } else {
                console.log('cryptodaniel already in database');
            }
            
            db.close();
        } else {
            console.log('cryptodaniel not found in server');
        }
    } catch (error) {
        console.error('Error checking for cryptodaniel:', error);
    }
    
    // Sync all users when bot starts
    syncAllUsers();
});

// Login to Discord using the bot token from config
client.login(config.botToken); 