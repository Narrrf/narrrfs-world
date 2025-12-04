# 🤖 DISCORD BOT MANAGEMENT RULE - LIVE DATABASE OPERATIONS

**STATUS:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**  
**CREATED:** December 3, 2025  
**PURPOSE:** Standardized Discord bot management for live database operations and server functions  
**PRIORITY:** 🚨 **CRITICAL - PRODUCTION OPERATIONS**  

---

## 🎯 **RULE OVERVIEW**

### **CORE PRINCIPLE:**
**The local Discord bot manages the live database and many critical server functions. Proper command deployment and bot management ensures all features work correctly.**

### **RULE SCOPE:**
- **Command Deployment** - Registering Discord slash commands
- **Bot Lifecycle** - Starting, stopping, restarting the bot
- **Database Interaction** - Live database operations via PHP API
- **Giveaway System** - Active giveaway management and recovery
- **Bug Tracker** - Automatic bug status monitoring
- **Command Updates** - Deploying new commands and subcommands

---

## 🚀 **COMMAND DEPLOYMENT PROTOCOL**

### **✅ WHEN TO DEPLOY COMMANDS:**

**ALWAYS deploy commands when:**
- Adding a new slash command (e.g., `/giveaway refresh`)
- Modifying command structure (new subcommands, options, descriptions)
- Updating command permissions or requirements
- After cloning/pulling new command files

**NEVER skip deployment** - Discord won't recognize changes until deployed!

---

### **✅ DEPLOYMENT PROCESS:**

#### **Step 1: Navigate to Discord Directory**
```bash
cd discord
```

#### **Step 2: Deploy Commands**
```bash
node deploy-commands.js
```

**Or using npm:**
```bash
npm run deploy
```

**Expected Output:**
```
Started refreshing 46 application (/) commands.
Successfully reloaded 46 application (/) commands.
```

**What Happens:**
- Reads all `.js` files from `discord/commands/` directory
- Extracts command definitions (`data` property from each file)
- Registers all commands with Discord API for your guild
- Takes ~5-10 seconds

---

## 🔄 **BOT LIFECYCLE MANAGEMENT**

### **✅ STARTING THE BOT:**

#### **Initial Start:**
```bash
cd discord
npm start
```

**Or:**
```bash
cd discord
node index.js
```

**Expected Startup Output:**
```
🚀 Narrrf's World Bot is ready as BotName#1234
✅ Connected, users: X
✅ Races loaded from database
✅ Giveaways loaded (manual recovery available via /recover-giveaways)
[GIVEAWAY] Loading active giveaways from database...
[GIVEAWAY] Found X active giveaways
[GIVEAWAY] ✅ Refreshed message embed for giveaway_XXX
✅ Bug tracker monitoring started
✅ Twitter mission monitoring started
```

---

### **✅ STOPPING THE BOT:**

**Press `Ctrl+C` in the terminal where bot is running**

**Safe Shutdown:**
- Bot will finish current operations
- Active timers and intervals are cleaned up
- Database connections are closed properly

**⚠️ WARNING:** Don't force-kill (kill -9) unless absolutely necessary - may leave database in inconsistent state.

---

### **✅ RESTARTING THE BOT:**

**Full Restart Process:**

1. **Stop the bot:**
   - Press `Ctrl+C` in bot terminal

2. **Wait 2-3 seconds** for clean shutdown

3. **Start the bot:**
   ```bash
   cd discord
   npm start
   ```

**Why Restart is Needed:**
- Loads active giveaways from database
- Refreshes giveaway message embeds
- Restores auto-end timers
- Initializes bug tracker monitoring
- Reconnects to Discord API

---

## 🗄️ **DATABASE INTERACTION SYSTEM**

### **✅ HOW THE BOT ACCESSES DATABASE:**

**API Endpoint:** `https://narrrfs.world/api/discord/db-access.php`

**Function:** `queryDb(query, params = [])`

**Location:** `discord/index.js` (lines 24-44)

**Example Usage:**
```javascript
const results = await queryDb(
    'SELECT * FROM tbl_giveaways WHERE status = ?',
    ['active']
);
```

**Security:**
- Uses `Authorization: config.botToken` header
- 30-second timeout for queries
- Error handling and logging included

---

### **✅ CRITICAL DATABASE TABLES:**

The bot interacts with these live database tables:

1. **`tbl_giveaways`** - Active giveaway events
2. **`tbl_giveaway_participants`** - User entries
3. **`tbl_giveaway_winners`** - Winner records
4. **`tbl_twitter_missions`** - Twitter mission data
5. **`tbl_bug_reports`** - Bug tracker data
6. **`tbl_users`** - User accounts
7. **`tbl_user_scores`** - DSPOINC balances
8. **`tbl_cheese_races`** - Race events

---

## 🎁 **GIVEAWAY SYSTEM OPERATIONS**

### **✅ GIVEAWAY PERSISTENCE:**

**On Bot Startup:**
- Loads all active giveaways from `tbl_giveaways`
- Restores auto-end timers for each giveaway
- Refreshes Discord message embeds with latest database values
- Handles overdue giveaways (ends immediately)

**Function:** `loadGiveawaysFromDatabase(queryDb, clientOverride)`

**Location:** `discord/commands/giveaway.js` (lines 23-87)

---

### **✅ GIVEAWAY COMMANDS:**

#### **Create Giveaway:**
```
/giveaway create prize:... duration:... winners:...
```

#### **Join Giveaway:**
```
/giveaway join giveaway_id:...
```

#### **List Giveaways:**
```
/giveaway list
```

#### **Refresh Giveaway Message (NEW - December 3, 2025):**
```
/giveaway refresh giveaway_id:...
```

**Purpose:** Sync Discord message embed with latest database values (end time, participants, etc.)

**When to Use:**
- After manually editing database
- If message embed shows wrong information
- After bot restart (automatic, but can manually trigger)

---

### **✅ GIVEAWAY RECOVERY:**

**Manual Recovery Command:**
```
/recover-giveaways
```

**Purpose:** Find and raffle overdue giveaways without winners

**When to Use:**
- If bot was down when giveaway should have ended
- If giveaway timer failed
- Emergency recovery situation

---

## 🐛 **BUG TRACKER MONITORING**

### **✅ AUTOMATIC BUG STATUS MONITORING:**

**Function:** `startBugResolvedMonitoring(client, queryDb)`

**Location:** `discord/index.js`

**What It Does:**
- Monitors bug tracker channel for "resolved" keywords
- Adds ✅ reaction to resolved bug messages
- Prevents duplicate reactions
- Tracks processed bugs to avoid spam

**Channel ID:** `1379193350162485351`

**Keywords Monitored:**
- "resolved"
- "fixed"
- "solved"
- "done"

---

## 📋 **COMMON COMMANDS REFERENCE**

### **✅ ADMIN COMMANDS:**

#### **Point Management:**
```
/addpoints user:... amount:...
/removepoints user:... amount:...
/setpoints user:... amount:...
/balance user:...
/history user:...
```

#### **Store Management:**
```
/createitem name:... price:... description:...
/listitems
/giftitem user:... item:... quantity:...
```

#### **Quest Management:**
```
/quest create name:... description:... reward:...
/approvequest quest_id:...
```

#### **Role Management:**
```
/check-holder user:...
/verify-holder user:...
/grant-season-tester user:...
```

#### **Dashboard:**
```
/dashboard
/cheeseboard
/leaderboard
```

---

### **✅ USER COMMANDS:**

#### **Account:**
```
/balance
/inventory
/history
```

#### **Store:**
```
/store
/useitem item:...
```

#### **Quests:**
```
/quest list
/quest claim quest_id:...
```

---

## 🔧 **TROUBLESHOOTING GUIDE**

### **❌ COMMAND NOT APPEARING IN DISCORD:**

**Problem:** New command doesn't show up after adding code

**Solution:**
1. **Deploy commands:**
   ```bash
   cd discord
   node deploy-commands.js
   ```

2. **Wait 1-2 minutes** for Discord to update

3. **Refresh Discord client** (reload or restart app)

4. **Check deployment output** - Should show "Successfully reloaded X commands"

**Common Mistakes:**
- ❌ Forgot to deploy commands
- ❌ Command file missing `data` property
- ❌ Syntax error in command definition
- ❌ Discord cache not refreshed

---

### **❌ GIVEAWAY MESSAGE NOT UPDATING:**

**Problem:** Discord message shows old end time, but database is correct

**Solution 1 - Automatic (On Bot Restart):**
```bash
# Restart bot - automatic refresh happens on startup
cd discord
npm start
```

**Solution 2 - Manual Refresh:**
```
/giveaway refresh giveaway_id:giveaway_XXX
```

**Solution 3 - Direct Database Edit:**
1. Edit `tbl_giveaways.ends_at` in database
2. Restart bot OR use `/giveaway refresh`

**Function:** `updateGiveawayMessage(giveawayId, channel, queryDb)`

---

### **❌ BOT NOT RESPONDING TO COMMANDS:**

**Problem:** Bot is online but commands don't work

**Checklist:**
1. **Bot is online** - Check Discord member list
2. **Bot has permissions** - View Channels, Send Messages, Read Message History
3. **Commands are deployed** - Run `node deploy-commands.js`
4. **Bot is in correct guild** - Verify `DISCORD_GUILD` in `.env`
5. **Check bot console** - Look for error messages

---

### **❌ DATABASE QUERY FAILURES:**

**Problem:** `queryDb` function returns errors

**Checklist:**
1. **API endpoint accessible:**
   - Test: `https://narrrfs.world/api/discord/db-access.php`
   - Should return JSON response

2. **Bot token correct:**
   - Check `config.botToken` matches `.env` file
   - Verify token hasn't expired

3. **Database connection:**
   - Check if production database is accessible
   - Verify PHP API is working

4. **Query syntax:**
   - Use parameterized queries (`?` placeholders)
   - Check SQL syntax is valid

---

### **❌ GIVEAWAY TIMER NOT WORKING:**

**Problem:** Giveaway doesn't end automatically

**Checklist:**
1. **Bot is running** - Timer only works when bot is active
2. **Giveaway loaded on startup** - Check console logs
3. **End time is valid** - Verify `ends_at` in database is future date
4. **Use recovery command:**
   ```
   /recover-giveaways
   ```

---

## 📁 **FILE STRUCTURE**

### **✅ KEY FILES:**

```
discord/
├── index.js                      # Main bot file (startup, event handlers)
├── deploy-commands.js            # Command deployment script
├── package.json                  # Dependencies and npm scripts
├── config.js                     # Configuration (bot token, etc.)
├── .env                          # Environment variables (DISCORD_BOT_SECRET, etc.)
├── commands/                     # All slash commands
│   ├── giveaway.js              # Giveaway system (1021 lines)
│   ├── balance.js               # User balance
│   ├── store.js                 # Store management
│   ├── quest.js                 # Quest system
│   ├── admin.js                 # Admin commands
│   └── ...                      # 40+ other commands
└── utils/                        # Utility functions
    └── dashboardEmbed.js        # Dashboard embed builder
```

---

## 🚨 **CRITICAL WORKFLOW: ADDING NEW COMMAND**

### **✅ STEP-BY-STEP PROCESS:**

#### **Step 1: Create Command File**
Create new file in `discord/commands/` directory:
```javascript
const { SlashCommandBuilder } = require('discord.js');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('newcommand')
        .setDescription('New command description'),
    async execute(interaction) {
        await interaction.reply('Command response!');
    }
};
```

#### **Step 2: Deploy Commands**
```bash
cd discord
node deploy-commands.js
```

#### **Step 3: Restart Bot (Optional)**
Only needed if command uses startup initialization:
```bash
cd discord
npm start
```

#### **Step 4: Test in Discord**
- Type `/` in Discord channel
- Find your new command
- Test functionality

---

## 🎯 **QUICK REFERENCE COMMANDS**

### **✅ DEPLOYMENT:**
```bash
cd discord && node deploy-commands.js
```

### **✅ START BOT:**
```bash
cd discord && npm start
```

### **✅ RESTART BOT:**
1. Stop: `Ctrl+C`
2. Start: `cd discord && npm start`

### **✅ CHECK BOT STATUS:**
- Look in Discord member list
- Check bot console for errors
- Test a simple command like `/help`

---

## 📊 **MONITORING & LOGS**

### **✅ IMPORTANT CONSOLE LOGS:**

**Bot Startup:**
```
🚀 Narrrf's World Bot is ready as BotName#1234
✅ Connected, users: X
```

**Giveaway System:**
```
[GIVEAWAY] Loading active giveaways from database...
[GIVEAWAY] Found X active giveaways
[GIVEAWAY] ✅ Refreshed message embed for giveaway_XXX
```

**Database Queries:**
```
[QUERY] SELECT * FROM tbl_giveaways WHERE status = ? ['active']
[RESPONSE] { data: [...] }
```

**Errors:**
```
[DB ERROR] Error message here
[GIVEAWAY] Error message here
```

---

## 🚨 **CRITICAL RULES TO NEVER VIOLATE**

### **❌ NEVER DO:**
- **Skip command deployment** - Commands won't work
- **Force-kill bot** - May corrupt database state
- **Edit database manually** without refreshing Discord messages
- **Remove active giveaway timers** without proper cleanup
- **Delete command files** without updating deployment

### **✅ ALWAYS DO:**
- **Deploy commands** after any command changes
- **Restart bot cleanly** (Ctrl+C, then restart)
- **Check console logs** for errors after changes
- **Test commands** in Discord after deployment
- **Refresh giveaway messages** after database edits
- **Backup database** before major operations

---

## 🔄 **COMMON OPERATIONS CHECKLIST**

### **✅ ADDING NEW SUBCOMMAND:**

1. [ ] Add subcommand to command builder in `data` property
2. [ ] Add case handler in `execute` function
3. [ ] Deploy commands: `node deploy-commands.js`
4. [ ] Test in Discord
5. [ ] Document in this rule file

---

### **✅ FIXING GIVEAWAY MESSAGE OUT OF SYNC:**

1. [ ] Check database: `SELECT * FROM tbl_giveaways WHERE giveaway_id = '...'`
2. [ ] Verify `ends_at` is correct
3. [ ] Restart bot OR use `/giveaway refresh`
4. [ ] Verify message embed updated

---

### **✅ UPDATING BOT AFTER PULL:**

1. [ ] Pull latest code from repository
2. [ ] Check for new commands or command changes
3. [ ] Deploy commands: `node deploy-commands.js`
4. [ ] Restart bot: `npm start`
5. [ ] Verify all systems working (giveaways, bug tracker, etc.)

---

## 🧀 **FINAL MANDATE**

### **THIS RULE IS NON-NEGOTIABLE:**
- **Every command change** MUST be deployed
- **Every bot restart** MUST follow clean shutdown process
- **Every database edit** MUST sync with Discord messages
- **Every new feature** MUST be tested and documented

### **THE ULTIMATE GOAL:**
**Ensure the Discord bot operates reliably with the live database, maintaining all server functions, giveaways, and user interactions without data loss or synchronization issues.**

---

## 📚 **RELATED DOCUMENTATION**

### **Command Files:**
- `discord/commands/giveaway.js` - Complete giveaway system (1021 lines)
- `discord/commands/BOT_COMMANDS_REVIEW.md` - Command review documentation
- `discord/commands/COMPLETE_COMMANDS_LIST.md` - Full command list

### **Deployment:**
- `discord/deploy-commands.js` - Command deployment script
- `discord/package.json` - npm scripts and dependencies

### **Lab Notes:**
- `12.0/LAB_NOTES/2025/12_DECEMBER/DAILY_NOTES/2025-12-03/GIVEAWAY_REFRESH_DEPLOYMENT_INSTRUCTIONS.md`
- `12.0/LAB_NOTES/2025/12_DECEMBER/DAILY_NOTES/2025-12-03/GIVEAWAY_MESSAGE_REFRESH_SOLUTION.md`

---

**RULE CREATED:** December 3, 2025  
**STATUS:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**  
**PURPOSE:** Discord Bot Management for Live Database Operations  
**SCOPE:** All bot operations, command deployment, database interactions, server functions  

**🤖 THIS RULE ENSURES DECADES OF RELIABLE DISCORD BOT OPERATIONS! 🤖**

