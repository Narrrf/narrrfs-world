require('dotenv').config();
const { Client, GatewayIntentBits } = require('discord.js');
const config = require('./config.js');

const client = new Client({ 
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMembers
    ]
});

client.once('ready', async () => {
    console.log(`Logged in as ${client.user.tag}`);
    
    try {
        const guild = client.guilds.cache.get(config.guildId);
        const member = await guild.members.fetch('734200554322001922');
        
        if (member) {
            console.log('Found member:', {
                id: member.id,
                username: member.user.username,
                nickname: member.nickname,
                roles: member.roles.cache.map(r => r.name)
            });
        } else {
            console.log('Member not found');
        }
    } catch (error) {
        console.error('Error:', error);
    }
    
    client.destroy();
});

client.login(config.botToken); 