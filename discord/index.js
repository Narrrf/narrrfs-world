require('dotenv').config();
const { Client, Collection, GatewayIntentBits, Partials } = require('discord.js');
const fs = require('fs');
const path = require('path');
const config = require('./config.js');
const fetch = require('node-fetch');
const { buildDashboardEmbed } = require('./utils/dashboardEmbed');

const dashboardChannelId = '1386489250140262410'; // Change if needed!
const DB_API = 'https://narrrfs.world/api/discord/db-access.php';
const DEBUG = true;

// --- DB QUERY ---
async function queryDb(query, params = []) {
  if (DEBUG) console.log('[QUERY]', query, params);
  try {
    const response = await fetch(DB_API, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': config.botToken
      },
      body: JSON.stringify({ action: 'query', query, params })
    });
    const data = await response.json();
    if (DEBUG) console.log('[RESPONSE]', data);
    if (data.error) throw new Error(data.error);
    return data.data;
  } catch (error) {
    console.error('[DB ERROR]', error);
    throw error;
  }
}

// --- INIT CLIENT ---
const client = new Client({
  intents: [
    GatewayIntentBits.Guilds,
    GatewayIntentBits.GuildMessages,
    GatewayIntentBits.GuildMembers,
    GatewayIntentBits.MessageContent,
    GatewayIntentBits.GuildPresences
  ],
  partials: [Partials.User, Partials.GuildMember, Partials.Message]
});

// --- LOAD COMMANDS ---
client.commands = new Collection();
const commandsPath = path.join(__dirname, 'commands');
if (fs.existsSync(commandsPath)) {
  const commandFiles = fs.readdirSync(commandsPath).filter(f => f.endsWith('.js'));
  for (const file of commandFiles) {
    const command = require(path.join(commandsPath, file));
    if ('data' in command && 'execute' in command)
      client.commands.set(command.data.name, command);
  }
}

// --- DASHBOARD AUTOPOP CHANNEL HANDLER ---
client.on('messageCreate', async (message) => {
  try {
    if (DEBUG) console.log(`[EVENT] messageCreate: ${message.author.tag} in #${message.channel?.name} (${message.channelId})`);
    if (
      message.channel.id === dashboardChannelId &&
      !message.author.bot
    ) {
      // Delete user's message for privacy
      await message.delete().catch(err => {
        console.error('[MESSAGE DELETE ERROR]', err);
      });

      // --- Send doc message (public commands) ---
      const docMsg = await message.channel.send({
        content:
          "👋 **Welcome to the Narrrf's World Dashboard!**\n\n" +
          "🧀 *Your dashboard will appear below and vanish after 10 seconds.*\n" +
          "\n**Public Commands:**\n" +
          "• `/dashboard` – Get your personal dashboard (only you can see it)\n" +
          "• `/balance` – Check your $DSPOINC\n" +
          "• `/leaderboard` – View top cheese collectors\n" +
          "• `/rewards` – See what you’ve unlocked\n" +
          "\n*All dashboards are public for a few seconds, then auto-delete for privacy & less channel clutter.*"
      });
      setTimeout(() => docMsg.delete().catch(() => {}), 15000);

      // --- Build dashboard for the user and send (auto-delete) ---
      try {
        const { embed, row } = await buildDashboardEmbed(
          message.author.id,
          message.author.username,
          message.author.displayAvatarURL(),
          queryDb
        );
        const dashMsg = await message.channel.send({
          content: `Dashboard for <@${message.author.id}>:`,
          embeds: [embed],
          components: [row]
        });
        setTimeout(() => dashMsg.delete().catch(() => {}), 10000);
      } catch (err) {
        console.error('[DASHBOARD EMBED ERROR]', err);
        const errMsg = await message.channel.send({
          content: `❌ Sorry <@${message.author.id}>, your dashboard could not be loaded.`
        });
        setTimeout(() => errMsg.delete().catch(() => {}), 10000);
      }
    }
  } catch (e) {
    console.error('[MESSAGECREATE ERROR]', e);
  }
});

// --- SLASH COMMAND & BUTTON HANDLER ---
client.on('interactionCreate', async interaction => {
  // Handle SLASH COMMANDS
  if (interaction.isChatInputCommand()) {
    const command = client.commands.get(interaction.commandName);
    if (!command) return;
    try {
      await command.execute(interaction, queryDb);
    } catch (error) {
      console.error('[COMMAND ERROR]', error);
      if (interaction.replied || interaction.deferred)
        await interaction.followUp({ content: 'Error executing command!', ephemeral: true });
      else
        await interaction.reply({ content: 'Error executing command!', ephemeral: true });
    }
    return;
  }

  // --- BUTTON HANDLERS (Quest claims etc) ---
  if (interaction.isButton()) {
    if (interaction.customId.startsWith('quest_claim_')) {
      const questId = interaction.customId.replace('quest_claim_', '');
      const userId = interaction.user.id;
      try {
        // Prevent duplicate claims:
        const existing = await queryDb(
          'SELECT * FROM tbl_quest_claims WHERE quest_id = ? AND user_id = ?',
          [questId, userId]
        );
        if (existing && existing.length > 0) {
          await interaction.reply({
            content: '❌ You already claimed this quest!',
            ephemeral: true
          });
          return;
        }

        // Record the claim
        await queryDb(
          'INSERT INTO tbl_quest_claims (quest_id, user_id, claimed_at) VALUES (?, ?, ?)',
          [questId, userId, new Date().toISOString()]
        );

        await interaction.reply({
          content: `✅ Claim received for Quest ID ${questId}! Your quest completion will be verified and you will get your $DSPOINC soon.`,
          ephemeral: true
        });
      } catch (err) {
        console.error('[QUEST CLAIM ERROR]', err);
        await interaction.reply({
          content: '❌ Could not record your claim (DB error). Please try again.',
          ephemeral: true
        });
      }
      return;
    }
    // ... add more button handlers as needed
  }
});

// --- OTHER EVENTS (UNCHANGED) ---
client.once('ready', async () => {
  console.log(`🚀 Narrrf's World Bot is ready as ${client.user.tag}`);
  try {
    const result = await queryDb('SELECT COUNT(*) as count FROM tbl_users');
    console.log('✅ Connected, users:', result[0].count);
  } catch (error) {
    console.error('❌ DB connection error:', error);
  }
});

client.login(config.botToken);
