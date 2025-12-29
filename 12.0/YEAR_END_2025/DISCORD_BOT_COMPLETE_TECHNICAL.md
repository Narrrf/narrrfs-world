# 🤖 DISCORD BOT - COMPLETE TECHNICAL DOCUMENTATION

**Created:** December 20, 2025  
**Status:** ✅ **COMPLETE**  
**Version:** 1.0.0  
**Purpose:** Complete technical documentation for Narrrf's World Discord Bot  
**Scope:** All commands, features, integrations, APIs, and architecture

---

## 📋 **TABLE OF CONTENTS**

1. [Overview](#overview)
2. [Bot Architecture](#bot-architecture)
3. [Command System](#command-system)
4. [Database Integration](#database-integration)
5. [API Integration](#api-integration)
6. [Game Integrations](#game-integrations)
7. [Admin Features](#admin-features)
8. [User Features](#user-features)
9. [Event System](#event-system)
10. [Button Interactions](#button-interactions)
11. [Permission System](#permission-system)
12. [Deployment & Configuration](#deployment--configuration)
13. [Code Examples](#code-examples)
14. [Future Implementation Plans](#future-implementation-plans)

---

## 🎯 **OVERVIEW**

### **Bot Purpose**
The Narrrf's World Discord Bot is a comprehensive community management system that provides:
- **User Management** - Balance tracking, profile dashboards, inventory management
- **Game Integration** - Discord Race, Cheese Rumble, leaderboards, game stats
- **Store System** - Item purchases, inventory management, gifting
- **Quest System** - Quest creation, claim verification, reward distribution
- **NFT Verification** - Holder verification, automatic role granting
- **Admin Tools** - Point management, user search, system statistics
- **Giveaway System** - Epic cheese-themed giveaways with persistence
- **Twitter Missions** - Social media mission management
- **Bug Tracker** - Automated bug report processing

### **Technical Stack**
- **Runtime:** Node.js
- **Framework:** Discord.js v14.11.0
- **Database:** SQLite3 (via PHP API)
- **HTTP Client:** node-fetch v2.6.9
- **Environment:** dotenv v16.5.0

### **File Structure**
```
discord/
├── index.js                    # Main bot entry point (2,407 lines)
├── config.js                   # Configuration file
├── package.json                # Dependencies
├── deploy-commands.js          # Command deployment script
├── commands/                   # All bot commands (50+ files)
│   ├── admin.js               # Admin interface commands
│   ├── balance.js             # Balance checking
│   ├── dashboard.js           # User dashboard
│   ├── store.js               # Store system
│   ├── cheese-race.js         # Discord Race game
│   ├── cheese-rumble.js       # Cheese Rumble game
│   ├── giveaway.js            # Giveaway system
│   ├── quest.js               # Quest management
│   ├── verify-holder.js       # NFT verification
│   ├── twitter-missions.js    # Twitter missions
│   └── ... (40+ more commands)
├── utils/                      # Utility functions
│   └── dashboardEmbed.js      # Dashboard embed builder
└── [Documentation files]       # Various MD guides
```

---

## 🏗️ **BOT ARCHITECTURE**

### **Core Components**

#### **1. Bot Client (`index.js`)**
- **Gateway Intent Bits:** `Guilds`, `GuildMessages`, `GuildMembers`, `MessageContent`
- **Partials:** `Message`, `Channel`, `Reaction`
- **Command Collection:** Dynamic loading from `commands/` directory
- **Event Handlers:** `ready`, `interactionCreate`, `messageCreate`, `error`

#### **2. Configuration (`config.js`)**
```javascript
module.exports = {
    botToken: process.env.DISCORD_BOT_SECRET,
    apiSecret: process.env.DISCORD_SECRET,
    clientId: process.env.DISCORD_CLIENT_ID,
    guildId: process.env.DISCORD_GUILD_ID || '1332015322546311218',
    apiUrl: process.env.API_URL || (process.platform === 'win32' ? 'http://localhost' : 'https://narrrfs.world'),
    colors: {
        primary: 0xf0c92c,
        error: 0xff0000,
        success: 0x00ff00
    }
};
```

#### **3. Database Query Function**
```javascript
async function queryDb(query, params = []) {
    const response = await fetch(DB_API, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': config.botToken
        },
        body: JSON.stringify({ action: 'query', query, params }),
        timeout: 30000
    });
    const data = await response.json();
    if (data.error) throw new Error(data.error);
    return data.data;
}
```

**Database API Endpoint:** `https://narrrfs.world/api/discord/db-access.php`

---

## 📝 **COMMAND SYSTEM**

### **Total Commands: 50+ Commands**

### **Command Categories**

#### **1. User Commands (13 Commands)**
- `/balance` - Check $DSPOINC balance, staking status, and rank
- `/dashboard` - View complete profile with stats
- `/history` - View transaction history
- `/inventory` - View purchased items
- `/store` - Browse and purchase items
- `/listitems` - See all available items
- `/help [category]` - Comprehensive help system
- `/cheeseboard` - Gateway to NFT verification
- `/leaderboard` - View top players
- `/check-holder` - Check NFT holder status and staking info
- `/verify-holder` - Verify NFT ownership and get roles
- `/stake-status` - Check DSPOINC staking status and active stakes
- `/set-twitter` - Link Twitter account

#### **2. Admin Commands (19 Commands)**
- `/admin search` - Search users by name/ID
- `/admin stats` - Get system statistics
- `/admin topusers` - Get top users by balance
- `/admin recent` - Get recent score adjustments
- `/admin userhistory` - Get user adjustment history
- `/admin questclaims` - Review pending quest claims
- `/admin season-settings` - Manage season settings
- `/managepoints` - Complete point management (add/set/remove)
- `/addpoints` - Add points to user
- `/setpoints` - Set user's total points
- `/removepoints` - Remove points from user
- `/quest` - Quest management (10 subcommands)
- `/approvequest` - Approve/reject quest claims
- `/sync-users` - Sync Discord users to database
- `/sync-adjustments` - Sync pending adjustments
- `/fix-adjustments` - Fix unapplied adjustments
- `/synch-scores` - Clean up duplicate scores
- `/admininventory` - Inventory management (view/remove/clear/history/compare)
- `/cleanup-messages` - Clean up old messages

#### **3. Game Commands (3 Commands)**
- `/cheese-race` - Start/join cheese races
- `/cheese-rumble` - Create/manage cheese rumbles
- `/stop-cheese-rumble` - Stop active rumble

#### **4. Store Commands (3 Commands)**
- `/store` - Store interface (view/preview/buy)
- `/listitems` - List all store items
- `/giftitem` - Gift items to users
- `/createitem` - Create new store items (admin)

#### **5. Giveaway Commands (1 Command with 8 Subcommands)**
- `/giveaway create` - Create new giveaway
- `/giveaway join` - Join giveaway
- `/giveaway list` - List active giveaways
- `/giveaway participants` - View participants (admin)
- `/giveaway reroll` - Reroll winners (admin)
- `/giveaway end` - End giveaway (admin)
- `/giveaway cancel` - Cancel giveaway (admin)
- `/giveaway refresh` - Refresh giveaway message (admin)

#### **6. Twitter Mission Commands (2 Commands)**
- `/twitter-missions` - View all Twitter missions
- `/verify-twitter` - Verify Twitter mission completion

#### **7. Utility Commands (2 Commands)**
- `/help` - Comprehensive help system
- `/modhelp` - Moderator help guide

### **Command Structure Pattern**
```javascript
module.exports = {
    data: new SlashCommandBuilder()
        .setName('command-name')
        .setDescription('Command description')
        .addSubcommand(sub => ...)
        .addStringOption(opt => ...)
        .setDefaultMemberPermissions(PermissionFlagsBits.ManageMessages), // Optional
    
    async execute(interaction, queryDb) {
        // Command logic
    },
    
    async autocomplete(interaction, queryDb) {
        // Autocomplete logic (optional)
    }
};
```

---

## 🗄️ **DATABASE INTEGRATION**

### **Database API Endpoint**
**URL:** `https://narrrfs.world/api/discord/db-access.php`  
**Method:** POST  
**Authentication:** Bearer token (DISCORD_BOT_SECRET or DISCORD_SECRET)

### **Database Tables Used**

#### **User Management Tables**
- `tbl_users` - User accounts
- `tbl_user_scores` - DSPOINC balances
- `tbl_score_adjustments` - Score adjustment audit trail
- `tbl_user_roles` - Discord role assignments
- `tbl_user_traits` - User traits

#### **Game Tables**
- `tbl_tetris_scores` - Tetris, Snake, Space Invaders scores
- `tbl_cheese_clicks` - Cheese Hunt click data
- `tbl_cheese_races` - Discord Race events
- `tbl_race_participants` - Race participants
- `tbl_cheese_rumbles` - Cheese Rumble events
- `tbl_rumble_participants` - Rumble participants

#### **Store Tables**
- `tbl_store_items` - Store item definitions
- `tbl_user_inventory` - User inventory
- `tbl_purchase_history` - Purchase records

#### **Quest Tables**
- `tbl_quests` - Quest definitions
- `tbl_quest_claims` - Quest claim submissions

#### **Giveaway Tables**
- `tbl_giveaways` - Giveaway events
- `tbl_giveaway_participants` - Giveaway participants
- `tbl_giveaway_winners` - Giveaway winners

#### **Twitter Mission Tables**
- `tbl_twitter_missions` - Twitter mission definitions
- `tbl_twitter_mission_participants` - Mission participants

#### **NFT Tables**
- `tbl_nft_ownership` - NFT ownership records
- `tbl_holder_verifications` - Holder verification records
- `tbl_role_grants` - Role grant audit trail

#### **Other Tables**
- `tbl_seasons` - Season management
- `tbl_game_settings` - Game configuration
- `tbl_bug_reports` - Bug tracker
- `tbl_bug_status_history` - Bug status changes

### **Database Query Examples**
```javascript
// Get user balance
const balance = await queryDb(
    'SELECT SUM(score) as total FROM tbl_user_scores WHERE user_id = ?',
    [userId]
);

// Get active giveaways
const giveaways = await queryDb(
    'SELECT * FROM tbl_giveaways WHERE status = ? ORDER BY created_at DESC',
    ['active']
);

// Insert score adjustment
await queryDb(
    'INSERT INTO tbl_score_adjustments (user_id, amount, reason, timestamp) VALUES (?, ?, ?, ?)',
    [userId, amount, reason, new Date().toISOString()]
);
```

---

## 🔌 **API INTEGRATION**

### **Backend API Endpoints**

#### **Discord Bot APIs (`/api/discord/`)**
- `db-access.php` - Database query endpoint (primary)
- `grant-role.php` - Discord role granting
- `get-balance.php` - Balance retrieval
- `score-history.php` - Score history

#### **Admin APIs (`/api/admin/`)**
- `discord-events.php` - Discord event logging
- `store-management.php` - Store item management
- `get-season-tester-eligible-players.php` - Season tester eligibility

#### **Dev APIs (`/api/dev/`)**
- `get-leaderboard.php` - Leaderboard data

#### **Store APIs (`/api/store/`)**
- `items.php` - Store items list
- `purchase.php` - Item purchase processing

### **API Usage Pattern**
```javascript
const response = await fetch(`${config.apiUrl}/api/store/items.php`, {
    headers: {
        'Authorization': `Bot ${config.botToken}`
    }
});
const data = await response.json();
```

---

## 🎮 **GAME INTEGRATIONS**

### **1. Discord Race (`/cheese-race`)**

**Features:**
- Race creation with custom settings
- Player joining via buttons
- Race state management (waiting, active, ended)
- Database persistence
- Reward distribution
- Message embed updates

**Database Tables:**
- `tbl_cheese_races` - Race definitions
- `tbl_race_participants` - Participants and positions

**Key Functions:**
- `createRace()` - Create new race
- `joinRace()` - Join race via button
- `startRace()` - Start active race
- `endRace()` - End race and distribute rewards
- `updateRaceMessage()` - Update race embed

**Code Location:** `discord/commands/cheese-race.js` (4,794 lines)

### **2. Cheese Rumble (`/cheese-rumble`)**

**Features:**
- Battle royale event system
- 200+ unique event variations (kills, eliminations, drama)
- Round-based gameplay
- Winner + First Out rewards
- Database persistence
- Event pool system

**Database Tables:**
- `tbl_cheese_rumbles` - Rumble definitions
- `tbl_rumble_participants` - Participants and final positions

**Key Functions:**
- `createRumble()` - Create new rumble
- `joinRumble()` - Join rumble
- `processRumbleRound()` - Process round events
- `endRumble()` - End rumble and distribute rewards
- `getRandomEvent()` - Get random event from pool

**Code Location:** `discord/commands/cheese-rumble.js` (2,723 lines)

### **3. Leaderboard (`/leaderboard`)**

**Features:**
- Multi-game leaderboards
- Season-aware data
- Interactive navigation buttons
- Real-time statistics

**Data Source:** `/api/dev/get-leaderboard.php`

---

## 🛠️ **ADMIN FEATURES**

### **Point Management**

#### **`/managepoints` Command**
- **Subcommands:** `add`, `set`, `remove`, `balance`
- **Features:**
  - Single-record logic (prevents duplicates)
  - Balance verification
  - Audit trail logging
  - Negative balance prevention

#### **Individual Point Commands**
- `/addpoints` - Add points to user
- `/setpoints` - Set user's total points
- `/removepoints` - Remove points from user

### **User Management**

#### **`/admin` Command**
- **Subcommands:**
  - `search` - Search users by name/ID
  - `stats` - System statistics
  - `topusers` - Top users by balance
  - `recent` - Recent score adjustments
  - `userhistory` - User adjustment history
  - `questclaims` - Review quest claims
  - `season-settings` - Manage season settings

#### **User Synchronization**
- `/sync-users` - Sync Discord users to database
- `/sync-adjustments` - Sync pending adjustments
- `/fix-adjustments` - Fix unapplied adjustments
- `/synch-scores` - Clean up duplicate scores

### **Inventory Management**

#### **`/admininventory` Command**
- **Subcommands:**
  - `view` - View user inventory
  - `remove` - Remove items from user
  - `clear` - Clear user inventory
  - `history` - View inventory history
  - `compare` - Compare two users' inventories

---

## 👤 **USER FEATURES**

### **Balance & Profile**

#### **`/balance` Command**
- Current DSPOINC balance
- Total games played
- Recent activity (last 5 scores)
- Shopping power indicator

#### **`/dashboard` Command**
- Complete user profile
- Balance display
- Recent changes
- Traits and roles
- Links to web dashboard and leaderboard

#### **`/history` Command**
- Transaction history
- Score adjustments
- Timestamps and reasons

### **Store System**

#### **`/store` Command**
- **Subcommands:**
  - `view` - Browse available items
  - `preview` - Beautiful preview format
  - `buy` - Purchase items (with autocomplete)

**Features:**
- Item browsing with pagination
- Autocomplete item selection
- Quantity support
- Purchase confirmation
- Inventory updates

### **Inventory**

#### **`/inventory` Command**
- View purchased items
- Item descriptions
- Purchase dates
- Item counts

---

## 🎁 **GIVEAWAY SYSTEM**

### **Features**
- Epic cheese-themed giveaways
- Full persistence (survives bot restarts)
- Button integration (Join, Participants)
- Admin controls (End, Cancel, Reroll)
- Weighted random selection
- Role requirements
- Auto-end timers
- Message embed updates

### **Database Tables**
- `tbl_giveaways` - Giveaway definitions
- `tbl_giveaway_participants` - Participants
- `tbl_giveaway_winners` - Winner records

### **Key Functions**
- `createGiveaway()` - Create new giveaway
- `joinGiveaway()` - Join via button
- `endGiveaway()` - End and select winners
- `rerollGiveaway()` - Reroll winners
- `loadGiveawaysFromDatabase()` - Restore on startup
- `updateGiveawayMessage()` - Update embed

**Code Location:** `discord/commands/giveaway.js` (1,039 lines)

---

## 🎯 **QUEST SYSTEM**

### **Features**
- Quest creation (10 quest types)
- Quest listing
- Claim submission
- Approval/rejection workflow
- Automatic role granting
- DSPOINC rewards
- Duration management

### **Quest Types**
1. Twitter Quest - Like, retweet, comment
2. Discord Quest - Join server, verify, etc.
3. Game Quest - Achieve score/level
4. Custom Quest - Free-form quests

### **`/quest` Command Subcommands**
- `create` - Create new quest
- `list` - List active quests
- `claims` - View quest claims
- `close` - Close quest

### **`/approvequest` Command**
- Approve/reject quest claims
- Automatic role granting
- DSPOINC reward distribution
- Audit logging

**Database Tables:**
- `tbl_quests` - Quest definitions
- `tbl_quest_claims` - Claim submissions

---

## 🎴 **NFT VERIFICATION SYSTEM**

### **Features**
- **Centralized API Integration** - Uses `verify-nft-holder.php` API for consistency with website (Updated: December 29, 2025)
- **Bot Token Authentication** - Secure bot-to-API communication bypassing signature verification
- Wallet address validation
- NFT ownership verification via Helius API (`get-nfts.php`)
- Automatic Discord role granting via centralized API
- Holder status checking with staking information
- Collection support (Genesis, VIP) with proper address mapping
- Staking integration in holder and balance commands
- Audit logging

### **Commands**
- `/check-holder wallet:<address>` - Check holder status, current roles, and staking info
- `/verify-holder wallet:<address> [collection:<type>]` - Verify NFT ownership and get Discord roles
- `/stake-status` - View detailed DSPOINC staking status, active stakes, and ready-to-claim rewards (New: December 29, 2025)
- `/balance` - Check $DSPOINC balance with staking breakdown (available vs staked) (Updated: December 29, 2025)

### **API Integration (Updated December 29, 2025)**
The bot now uses the centralized `verify-nft-holder.php` API endpoint for all verification operations:

**Endpoint:** `POST /api/user/verify-nft-holder.php`

**Bot Authentication:**
- Uses `bot_token` parameter with `DISCORD_SECRET` from config
- Bypasses signature verification for trusted bot requests
- Maintains security while allowing automated role granting

**Request Format:**
```javascript
{
    user_id: "discord_user_id",
    wallet_address: "solana_wallet_address",
    collection: "collection_address_or_empty_for_all",
    bot_token: "discord_secret_from_config"
}
```

**Response Format:**
```javascript
{
    success: true,
    wallet: "wallet_address",
    verified_collections: [
        {
            collection_address: "AtJCkW4...",
            collection: "Narrrfs World: Genesis Genetic",
            role: "🏆 Holder",
            role_id: "1402668301414563971",
            count: 5,
            granted: true
        }
    ]
}
```

### **Role Mapping**
- **Genesis Collection** (`AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML`) → `🏆 Holder` role (ID: 1402668301414563971)
- **VIP Collection** (`CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg`) → `🎴 VIP Holder` role (ID: 1332016526848692345)

### **Staking Integration**
- **Balance Command** - Shows available vs staked DSPOINC, active stakes count, ready-to-claim rewards
- **Check-Holder Command** - Displays staking summary when available
- **Stake-Status Command** - Complete staking overview with active stakes list and reward details

### **Database Tables**
- `tbl_nft_ownership` - NFT ownership records
- `tbl_holder_verifications` - Verification records
- `tbl_role_grants` - Role grant audit trail
- `tbl_dspoinc_stakes` - Staking records (for staking features)

### **Technical Implementation**
**Files:**
- `discord/commands/verify-holder.js` - Uses centralized API, no direct role granting
- `discord/commands/check-holder.js` - Enhanced with staking information display
- `discord/commands/stake-status.js` - New command for detailed staking status (December 29, 2025)
- `discord/commands/balance.js` - Enhanced with staking breakdown (December 29, 2025)

**Key Changes (December 29, 2025):**
1. Replaced direct NFT fetching with centralized `verify-nft-holder.php` API
2. Removed direct role granting - now handled by API
3. Added bot token authentication support
4. Integrated staking information into holder and balance commands
5. Created dedicated `/stake-status` command for comprehensive staking overview

---

## 🐦 **TWITTER MISSION SYSTEM**

### **Features**
- Mission creation via Admin Interface
- Automatic Discord posting
- Button-based participation
- Verification workflow
- Reward distribution
- Expiration handling

### **Mission Types**
- `like` - Like the tweet
- `retweet` - Retweet the tweet
- `comment` - Comment on the tweet
- `like_retweet_comment` - All actions
- And more combinations

### **Commands**
- `/twitter-missions` - View all missions (admin)
- `/verify-twitter` - Verify mission completion (admin)

### **Database Tables**
- `tbl_twitter_missions` - Mission definitions
- `tbl_twitter_mission_participants` - Participants and verification status

### **Integration**
- Monitors `tbl_twitter_missions` for new missions
- Posts to configured Discord channel
- Updates mission embeds with participant counts
- Handles expiration and status updates

---

## 🐛 **BUG TRACKER INTEGRATION**

### **Features**
- Automatic bug report processing
- AI-based categorization
- Priority determination
- Attachment handling
- Status monitoring
- Resolved message notifications

### **Bug Processing**
1. Message posted in bug tracker channel
2. Bot extracts title and description
3. AI categorizes bug (achievement, game, mobile, UI, etc.)
4. AI determines priority (critical, high, medium, low, enhancement)
5. Bug saved to database with status "Reported"
6. Status changes trigger notifications

### **Database Tables**
- `tbl_bug_reports` - Bug report records
- `tbl_bug_status_history` - Status change history
- `tbl_bug_categories` - Category definitions
- `tbl_bug_priorities` - Priority definitions

---

## 🎨 **BUTTON INTERACTIONS**

### **Button Types**

#### **Cheese Race Buttons**
- `join_race_<raceId>` - Join race
- `leave_race_<raceId>` - Leave race
- `start_race_<raceId>` - Start race (creator only)
- `view_racers_<raceId>` - View participants
- `cancel_race_<raceId>` - Cancel race (creator only)

#### **Cheese Rumble Buttons**
- `join_rumble_<rumbleId>` - Join rumble
- `leave_rumble_<rumbleId>` - Leave rumble
- `start_rumble_<rumbleId>` - Start rumble (creator only)
- `view_rumble_<rumbleId>` - View participants
- `cancel_rumble_<rumbleId>` - Cancel rumble (creator only)

#### **Giveaway Buttons**
- `join_giveaway_<giveawayId>` - Join giveaway
- `participants_giveaway_<giveawayId>` - View participants

#### **Quest Buttons**
- `quest_claim_<questId>` - Claim quest

#### **Store Buttons**
- `buy_item_<itemId>` - Purchase item
- `view_item_<itemId>` - View item details

#### **Cheeseboard Buttons**
- `cheese_check` - NFT status check info
- `cheese_verify` - NFT verification info
- `cheese_balance` - Balance info
- `cheese_store` - Store info
- `cheese_dashboard` - Dashboard info
- `cheese_leaderboard` - Leaderboard
- `cheese_help` - Help info

#### **Leaderboard Buttons**
- `leaderboard_tetris` - Tetris leaderboard
- `leaderboard_snake` - Snake leaderboard
- `leaderboard_all` - All games leaderboard

---

## 🔐 **PERMISSION SYSTEM**

### **Permission Levels**

#### **1. Public Commands**
- Available to all users
- No permission requirements
- Examples: `/balance`, `/help`, `/store`, `/dashboard`

#### **2. ManageMessages Permission**
- Requires Discord "Manage Messages" permission
- Typically mods/admins
- Examples: `/admin`, `/managepoints`, `/quest`, `/giveaway`

#### **3. Administrator Permission**
- Requires Discord "Administrator" permission
- Server admins only
- Examples: Some advanced admin commands

#### **4. Role-Based Checks**
- Additional runtime permission checks
- Uses `MOD_ROLE_ID` (1386472869290053662)
- Checks role names: "Founder", "Moderator", "Admin"

### **Permission Implementation**
```javascript
// Command-level permission
.setDefaultMemberPermissions(PermissionFlagsBits.ManageMessages)

// Runtime permission check
if (!interaction.member.roles.cache.has(MOD_ROLE_ID)) {
    return interaction.reply({ 
        content: '❌ Permission denied', 
        ephemeral: true 
    });
}
```

---

## 🚀 **DEPLOYMENT & CONFIGURATION**

### **Environment Variables**
```env
DISCORD_BOT_SECRET=your-bot-token
DISCORD_SECRET=your-api-secret
DISCORD_CLIENT_ID=your-client-id
DISCORD_GUILD_ID=1332015322546311218
API_URL=https://narrrfs.world
```

### **Command Deployment**
```bash
cd discord
node deploy-commands.js
# or
npm run deploy
```

**Process:**
1. Reads all `.js` files from `commands/` directory
2. Extracts command definitions (`data` property)
3. Registers all commands with Discord API
4. Takes ~5-10 seconds

### **Bot Startup**
```bash
cd discord
npm start
# or
node index.js
```

**Startup Process:**
1. Loads all commands from `commands/` directory
2. Connects to Discord gateway
3. Loads active giveaways from database
4. Restores giveaway timers
5. Starts bug tracker monitoring
6. Starts Twitter mission monitoring

### **Bot Shutdown**
- Press `Ctrl+C` in terminal
- Bot finishes current operations
- Timers and intervals cleaned up
- Database connections closed

---

## 💻 **CODE EXAMPLES**

### **Example 1: Simple Command**
```javascript
const { SlashCommandBuilder, EmbedBuilder } = require('discord.js');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('balance')
        .setDescription('💰 Check your $DSPOINC balance'),
    
    async execute(interaction, queryDb) {
        await interaction.deferReply();
        
        const userId = interaction.user.id;
        const result = await queryDb(
            'SELECT SUM(score) as total FROM tbl_user_scores WHERE user_id = ?',
            [userId]
        );
        const balance = result[0]?.total || 0;
        
        const embed = new EmbedBuilder()
            .setTitle('💰 Your Balance')
            .setDescription(`**Balance:** ${balance.toLocaleString()} $DSPOINC`)
            .setColor(0xf0c92c);
        
        await interaction.editReply({ embeds: [embed] });
    }
};
```

### **Example 2: Command with Subcommands**
```javascript
const { SlashCommandBuilder } = require('discord.js');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('store')
        .setDescription('🏪 Store system')
        .addSubcommand(sub =>
            sub.setName('view')
                .setDescription('Browse items'))
        .addSubcommand(sub =>
            sub.setName('buy')
                .setDescription('Purchase item')
                .addStringOption(opt =>
                    opt.setName('item_id')
                        .setDescription('Item ID')
                        .setRequired(true))),
    
    async execute(interaction, queryDb) {
        const subcommand = interaction.options.getSubcommand();
        
        if (subcommand === 'view') {
            // View logic
        } else if (subcommand === 'buy') {
            // Purchase logic
        }
    }
};
```

### **Example 3: Button Handler**
```javascript
if (interaction.isButton()) {
    if (interaction.customId.startsWith('join_race_')) {
        const raceId = interaction.customId.replace('join_race_', '');
        const race = activeRaces.get(raceId);
        
        if (!race) {
            return interaction.reply({ 
                content: '❌ Race not found', 
                ephemeral: true 
            });
        }
        
        // Join race logic
        race.players.push({
            id: interaction.user.id,
            username: interaction.user.username
        });
        
        await interaction.reply({ 
            content: '✅ Joined race!', 
            ephemeral: true 
        });
    }
}
```

### **Example 4: Database Query**
```javascript
// Get user data
const user = await queryDb(
    'SELECT * FROM tbl_users WHERE discord_id = ?',
    [userId]
);

// Insert record
await queryDb(
    'INSERT INTO tbl_giveaways (giveaway_id, prize, winners, ends_at, status) VALUES (?, ?, ?, ?, ?)',
    [giveawayId, prize, winners, endsAt, 'active']
);

// Update record
await queryDb(
    'UPDATE tbl_user_scores SET score = score + ? WHERE user_id = ?',
    [amount, userId]
);
```

### **Example 5: External API Call**
```javascript
const response = await fetch(`${config.apiUrl}/api/store/items.php`, {
    headers: {
        'Authorization': `Bot ${config.botToken}`
    }
});
const data = await response.json();

if (data.success) {
    // Process items
    data.items.forEach(item => {
        // Item processing logic
    });
}
```

---

## 🔮 **FUTURE IMPLEMENTATION PLANS**

### **Planned Features**

#### **1. Enhanced Game Integration**
- Real-time game stats display
- Achievement notifications
- Season progress tracking
- Cross-game leaderboards

#### **2. Advanced Quest System**
- Automated quest verification
- Multi-step quest chains
- Quest templates
- Scheduled quest releases

#### **3. Enhanced Giveaway System**
- Multi-prize giveaways
- Tiered rewards
- Custom entry requirements
- Giveaway analytics

#### **4. Social Features**
- User profiles in Discord
- Achievement showcases
- Social leaderboards
- Community challenges

#### **5. Admin Enhancements**
- Bulk operations
- Scheduled tasks
- Advanced analytics
- Export functionality

#### **6. Integration Improvements**
- Real-time web dashboard updates
- Webhook notifications
- API rate limiting
- Caching system

---

## 📊 **STATISTICS**

### **Command Statistics**
- **Total Commands:** 50+
- **User Commands:** 12
- **Admin Commands:** 19
- **Game Commands:** 3
- **Store Commands:** 4
- **Giveaway Commands:** 1 (8 subcommands)
- **Twitter Mission Commands:** 2
- **Utility Commands:** 2

### **Database Tables Used:** 30+
### **API Endpoints Used:** 10+
### **Button Interactions:** 20+
### **Lines of Code:** ~15,000+

---

## 📚 **DOCUMENTATION REFERENCES**

### **Bot Documentation Files**
- `COMPLETE_COMMANDS_LIST.md` - Complete command list
- `BOT_COMMANDS_REVIEW.md` - Command review and assessment
- `COMMAND_PERMISSIONS_SUMMARY.md` - Permission system
- `CHEESE_RACES_FIX_GUIDE.md` - Race system documentation
- `DEPLOYMENT_GUIDE.md` - Deployment instructions
- `TROUBLESHOOTING_GUIDE.md` - Troubleshooting guide

### **Integration Documentation**
- `LEADERBOARD_IMPLEMENTATION.md` - Leaderboard system
- `STORE_MANAGEMENT_DEPLOYMENT.md` - Store system
- `ITEM_USAGE_TICKET_SYSTEM.md` - Item usage system
- `ADMIN_INVENTORY_MANAGEMENT.md` - Inventory management

---

## ✅ **SUMMARY**

The Narrrf's World Discord Bot is a **comprehensive community management system** with:
- ✅ **50+ commands** covering all aspects of community management
- ✅ **Full game integration** (Discord Race, Cheese Rumble, leaderboards)
- ✅ **Complete store system** with inventory management
- ✅ **Advanced quest system** with approval workflow
- ✅ **Epic giveaway system** with persistence
- ✅ **NFT verification** with automatic role granting
- ✅ **Twitter mission management** with verification
- ✅ **Bug tracker integration** with automated processing
- ✅ **Admin tools** for complete system management
- ✅ **User features** for engagement and tracking

**Status:** ✅ **Production Ready** - All systems operational and tested

---

**Document Created:** December 20, 2025  
**Last Updated:** December 28, 2025  
**Version:** 1.0.0  
**Maintainer:** Narrrf's World Development Team

