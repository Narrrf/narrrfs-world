# 💥 GAME 6: CHEESE RUMBLE - COMPLETE TECHNICAL DOCUMENTATION 2025

**Created:** December 20, 2025  
**Status:** ✅ **PRODUCTION READY - COMPLETE INTEGRATION**  
**Version:** 2.0.0 - Enhanced Gameplay System  
**Purpose:** Complete technical reference for Cheese Rumble game integration in Narrrfs World

---

## 📋 **TABLE OF CONTENTS**

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Discord Bot Implementation](#discord-bot-implementation)
4. [Database Schema](#database-schema)
5. [Game System Flow](#game-system-flow)
6. [Reward System](#reward-system)
7. [Profile.html Integration](#profilehtml-integration)
8. [Admin Interface Integration](#admin-interface-integration)
9. [API Integration](#api-integration)
10. [Code Examples](#code-examples)
11. [Testing & Verification](#testing--verification)

---

## 🎯 **OVERVIEW**

### **Game Description:**
Cheese Rumble is a text-based battle royale Discord game where players join a rumble and watch as epic cheese-themed battles unfold. Players are eliminated through random events, and the last mouse standing wins the DSPOINC prize. The game features 200+ event variations, item system, revival system, and full integration with Narrrfs World ecosystem.

### **Key Features:**
- ✅ Text-based battle royale gameplay
- ✅ 200+ event variations across 7 categories
- ✅ Item system (finding and using items)
- ✅ Revival system (ghost/zombie mice)
- ✅ Balanced combat (counter-attacks, mutual eliminations)
- ✅ First out reward (1,000 DSPOINC)
- ✅ Winner prize (configurable DSPOINC)
- ✅ Full database persistence
- ✅ Profile.html integration
- ✅ Admin interface integration

### **Integration Status:**
- ✅ **Discord Bot:** `discord/commands/cheese-rumble.js` (~2,450 lines)
- ✅ **Database:** `tbl_cheese_rumbles`, `tbl_rumble_participants`
- ✅ **Profile.html:** Full integration via `/api/user/user-game-missions.php`
- ✅ **Admin Interface:** Full integration via `/api/admin/get-all-games-stats.php`

---

## 🏗️ **ARCHITECTURE**

### **System Flow:**
```
User Runs /cheese-rumble create in Discord
        ↓
Discord Bot Creates Rumble Object
        ↓
Bot Saves Rumble to Database (tbl_cheese_rumbles)
        ↓
Bot Posts Rumble Embed with Buttons
        ↓
Players Click "Join Rumble" Button
        ↓
Bot Adds Players to Database (tbl_rumble_participants)
        ↓
Rumble Starts (Auto or Manual)
        ↓
Rounds Process (5-12 events per round)
        ↓
Players Eliminated Through Events
        ↓
First Out Reward (1,000 DSPOINC)
        ↓
Last Mouse Standing Wins
        ↓
Winner Reward (Configurable DSPOINC)
        ↓
Bot Updates All Participant Records
        ↓
Rumble Status Set to 'finished'
        ↓
Profile.html Updates via API
        ↓
Admin Interface Updates via API
```

### **Technology Stack:**
- **Discord Bot:** Node.js, discord.js
- **Database:** SQLite3 (`narrrf_world.sqlite`)
- **APIs:** PHP RESTful endpoints
- **Frontend:** HTML5, JavaScript, Tailwind CSS

---

## 🤖 **DISCORD BOT IMPLEMENTATION**

### **File Structure:**
```
discord/
├── commands/
│   ├── cheese-rumble.js          # Main command (~2,450 lines)
│   ├── cheese-rumble-test.js     # Test command (~377 lines)
│   └── stop-cheese-rumble.js     # Stop command (~194 lines)
└── index.js                       # Button handler integration, bot startup loading
```

### **Key Commands:**

#### **1. Create Rumble Command:**
```javascript
// discord/commands/cheese-rumble.js
module.exports = {
    data: new SlashCommandBuilder()
        .setName('cheese-rumble')
        .setDescription('Start a cheese rumble!')
        .addSubcommand(subcommand =>
            subcommand
                .setName('create')
                .setDescription('Create a new cheese rumble')
                .addIntegerOption(option =>
                    option.setName('players')
                        .setDescription('Maximum players (2-50)')
                        .setRequired(false)
                        .setMinValue(2)
                        .setMaxValue(50))
                .addIntegerOption(option =>
                    option.setName('reward')
                        .setDescription('DSPOINC prize for winner (100-1000000)')
                        .setRequired(false)
                        .setMinValue(100)
                        .setMaxValue(1000000))),
    async execute(interaction) {
        // Create rumble object
        const rumble = {
            id: generateRumbleId(),
            creator: interaction.user.id,
            creatorName: interaction.user.username,
            channelId: interaction.channel.id,
            status: 'waiting',
            maxPlayers: players || 10,
            dspoincReward: reward || 5000,
            players: []
        };
        
        // Save to database
        await createRumbleInDatabase(rumble, queryDb);
        
        // Post rumble embed
        const message = await createAndSendRumbleMessage(rumble, interaction.channel);
        rumble.messageId = message.id;
        
        // Store in active rumbles map
        activeRumbles.set(rumble.id, rumble);
    }
};
```

#### **2. Join Rumble Button Handler:**
```javascript
// discord/index.js
client.on('interactionCreate', async interaction => {
    if (interaction.isButton() && interaction.customId.startsWith('join_rumble_')) {
        const rumbleId = interaction.customId.replace('join_rumble_', '');
        const rumble = activeRumbles.get(rumbleId);
        
        // Add player to rumble
        const player = {
            id: interaction.user.id,
            username: interaction.user.username,
            joinedAt: Date.now(),
            status: 'alive',
            kills: 0,
            inventory: []
        };
        
        rumble.players.push(player);
        
        // Save to database
        await addPlayerToRumbleInDatabase(rumbleId, player, queryDb);
        
        // Update rumble message
        await updateRumbleMessage(rumble, interaction.channel);
    }
});
```

#### **3. Round Processing:**
```javascript
// discord/commands/cheese-rumble.js
async function processRound(rumbleId) {
    const rumble = activeRumbles.get(rumbleId);
    
    // Generate 5-12 events
    const eventCount = 5 + Math.floor(Math.random() * 8);
    
    for (let i = 0; i < eventCount; i++) {
        // Get random event
        const event = getRandomEvent(rumble);
        
        // Process event (eliminate players, find items, revivals, etc.)
        await processEvent(rumble, event);
        
        // Update database
        await updatePlayerStatusInDatabase(rumbleId, event.player.id, event.player.status, event.player.kills, queryDb);
        
        // Wait 1 second between events
        await sleep(1000);
    }
    
    // Check win condition
    const alivePlayers = rumble.players.filter(p => p.status === 'alive' || p.status === 'ghost' || p.status === 'zombie');
    const trulyAlive = alivePlayers.filter(p => p.status === 'alive');
    const undead = alivePlayers.filter(p => p.status === 'ghost' || p.status === 'zombie');
    
    if (trulyAlive.length <= 1 && undead.length === 0) {
        // End rumble
        await endRumble(rumbleId);
    } else {
        // Continue to next round (5-10 second delay)
        const delay = 5000 + Math.random() * 5000;
        setTimeout(() => processRound(rumbleId), delay);
    }
}
```

#### **4. Rumble Completion:**
```javascript
// discord/commands/cheese-rumble.js
async function endRumble(rumbleId) {
    const rumble = activeRumbles.get(rumbleId);
    
    // Determine winner (last alive player)
    const winner = rumble.players.find(p => p.status === 'alive');
    
    if (winner) {
        // Award winner DSPOINC
        await awardDspoincToWinner(winner.id, rumble.dspoincReward, queryDb);
        
        // Update participant record
        await queryDb(`
            UPDATE tbl_rumble_participants 
            SET dspoinc_earned = ?, status = 'winner', final_position = 1, finished_at = ?
            WHERE rumble_id = ? AND user_id = ?
        `, [rumble.dspoincReward, new Date().toISOString(), rumbleId, winner.id]);
    }
    
    // Update rumble status
    await endRumbleInDatabase(rumbleId, winner.id, winner.username, queryDb);
}
```

---

## 🗄️ **DATABASE SCHEMA**

### **1. Rumble Table: `tbl_cheese_rumbles`**

```sql
CREATE TABLE tbl_cheese_rumbles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rumble_id TEXT PRIMARY KEY,              -- Unique rumble identifier
    creator_id TEXT NOT NULL,                -- Discord ID of creator
    creator_name TEXT NOT NULL,              -- Username of creator
    channel_id TEXT NOT NULL,                -- Discord channel ID
    message_id TEXT,                          -- Discord message ID
    status TEXT NOT NULL DEFAULT 'waiting',  -- waiting, active, finished, cancelled
    max_players INTEGER DEFAULT 10,
    duration INTEGER DEFAULT 300,            -- Max duration in seconds
    dspoinc_reward INTEGER DEFAULT 5000,     -- Winner prize
    role_reward TEXT,                        -- Optional Discord role
    tag_role TEXT,                           -- Role to tag
    auto_start INTEGER DEFAULT 0,             -- Auto-start when full
    start_in_minutes INTEGER,                -- Auto-start delay
    category TEXT DEFAULT 'all',             -- Event category filter
    comment TEXT,                            -- Optional comment
    current_round INTEGER DEFAULT 0,        -- Current round number
    players_alive INTEGER DEFAULT 0,         -- Players still alive
    events_log TEXT,                         -- Events log (JSON)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,                     -- When rumble started
    finished_at DATETIME,                    -- When rumble finished
    ended_at DATETIME,                       -- When rumble ended
    winner_id TEXT,                          -- Discord ID of winner
    winner_name TEXT,                        -- Username of winner
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_rumble_status ON tbl_cheese_rumbles(status);
CREATE INDEX idx_rumble_creator ON tbl_cheese_rumbles(creator_id);
CREATE INDEX idx_rumble_created ON tbl_cheese_rumbles(created_at);
```

### **2. Participants Table: `tbl_rumble_participants`**

**CRITICAL:** Uses `user_id` field (NOT `discord_id`) and `final_position` field!

```sql
CREATE TABLE tbl_rumble_participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rumble_id TEXT NOT NULL,                 -- References tbl_cheese_rumbles.rumble_id
    user_id TEXT NOT NULL,                   -- Discord ID (NOT discord_id!)
    username TEXT NOT NULL,                  -- Discord username
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'alive',             -- alive, eliminated, winner, ghost, zombie
    kills INTEGER DEFAULT 0,                 -- Number of eliminations
    eliminated_by TEXT,                      -- Discord ID of eliminator
    elimination_reason TEXT,                 -- How player was eliminated
    eliminated_in_round INTEGER,             -- Round number when eliminated
    final_position INTEGER,                  -- Final position (1 = winner)
    dspoinc_earned INTEGER DEFAULT 0,        -- DSPOINC earned (winner or first out)
    season TEXT,                             -- Season identifier
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rumble_id) REFERENCES tbl_cheese_rumbles(rumble_id)
);

CREATE INDEX idx_rumble_participants_rumble ON tbl_rumble_participants(rumble_id);
CREATE INDEX idx_rumble_participants_user ON tbl_rumble_participants(user_id);
CREATE INDEX idx_rumble_participants_status ON tbl_rumble_participants(status);
CREATE INDEX idx_rumble_participants_season ON tbl_rumble_participants(season);
```

**CRITICAL RULES:**
- ✅ Uses `user_id` field (NOT `discord_id`)
- ✅ Uses `final_position` field (NOT `position`)
- ✅ Season-aware data tracking

### **3. Query Examples:**

#### **Get User Rumble Stats:**
```sql
SELECT 
    COUNT(*) as total_rumbles,
    COUNT(CASE WHEN status = 'winner' OR final_position = 1 THEN 1 END) as wins,
    COUNT(CASE WHEN final_position <= 3 AND final_position IS NOT NULL THEN 1 END) as podiums,
    MIN(final_position) as best_position,
    SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned,
    MAX(COALESCE(joined_at, updated_at)) as last_played
FROM tbl_rumble_participants
WHERE user_id = ?;
```

#### **Get Rumble Participants:**
```sql
SELECT 
    user_id,
    username,
    final_position,
    status,
    kills,
    dspoinc_earned
FROM tbl_rumble_participants
WHERE rumble_id = ?
ORDER BY final_position ASC;
```

---

## 🎮 **GAME SYSTEM FLOW**

### **1. Rumble Creation:**
```javascript
// User runs /cheese-rumble create
const rumble = {
    id: generateRumbleId(),
    creator: interaction.user.id,
    creatorName: interaction.user.username,
    status: 'waiting',
    maxPlayers: 10,
    dspoincReward: 5000,
    players: []
};

// Save to database
await createRumbleInDatabase(rumble, queryDb);
```

### **2. Player Joining:**
```javascript
// Player clicks "Join Rumble" button
const player = {
    id: interaction.user.id,
    username: interaction.user.username,
    joinedAt: Date.now(),
    status: 'alive',
    kills: 0,
    inventory: []
};

// Add to rumble
rumble.players.push(player);

// Save to database
await addPlayerToRumbleInDatabase(rumble.id, player, queryDb);
```

### **3. Rumble Execution:**
```javascript
// Rumble starts (auto or manual)
rumble.status = 'active';
rumble.startTime = Date.now();

// Update database
await updateRumbleStatusInDatabase(rumble.id, 'active', queryDb);

// Process rounds
processRound(rumble.id);
```

### **4. Round Processing:**
```javascript
// Each round: 5-12 events
const eventCount = 5 + Math.floor(Math.random() * 8);

for (let i = 0; i < eventCount; i++) {
    const event = getRandomEvent(rumble);
    await processEvent(rumble, event);
    await sleep(1000); // 1 second between events
}

// Wait 5-10 seconds before next round
const delay = 5000 + Math.random() * 5000;
setTimeout(() => processRound(rumble.id), delay);
```

### **5. Rumble Completion:**
```javascript
// Last mouse standing wins
const winner = rumble.players.find(p => p.status === 'alive');

// Award winner
await awardDspoincToWinner(winner.id, rumble.dspoincReward, queryDb);

// Update participant record
await queryDb(`
    UPDATE tbl_rumble_participants 
    SET dspoinc_earned = ?, status = 'winner', final_position = 1
    WHERE rumble_id = ? AND user_id = ?
`, [rumble.dspoincReward, rumble.id, winner.id]);
```

---

## 💰 **REWARD SYSTEM**

### **Winner Reward:**
- **DSPOINC Prize:** Configurable (default: 5,000 DSPOINC)
- **Role Reward:** Optional Discord role
- **Database Logging:** Saved to `tbl_user_scores` and `tbl_score_adjustments`
- **Participant Record:** Updated in `tbl_rumble_participants` (status='winner', final_position=1)

### **First Out Reward:**
- **1,000 DSPOINC:** Fixed consolation prize
- **Awarded Immediately:** When first player is eliminated
- **Database Logging:** Saved to `tbl_user_scores` and `tbl_score_adjustments`
- **Participant Record:** Updated in `tbl_rumble_participants` (dspoinc_earned=1000)

### **Reward Distribution:**
```javascript
// discord/commands/cheese-rumble.js
async function awardDspoincToWinner(userId, amount, queryDb) {
    // Update tbl_user_scores
    await queryDb(`
        INSERT INTO tbl_user_scores (user_id, score, game, source, timestamp)
        VALUES (?, ?, 'cheese_rumble', 'rumble_winner', datetime('now'))
        ON CONFLICT(user_id) DO UPDATE SET score = score + ?
    `, [userId, amount, amount]);
    
    // Audit trail
    await queryDb(`
        INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp)
        VALUES (?, 'discord-bot', ?, 'add', 'Cheese Rumble Winner', datetime('now'))
    `, [userId, amount]);
}
```

---

## 🌐 **PROFILE.HTML INTEGRATION**

### **API Endpoint: `/api/user/user-game-missions.php`**

#### **Cheese Rumble Statistics:**
```php
// api/user/user-game-missions.php
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_rumbles,
        COUNT(CASE WHEN status = 'winner' OR final_position = 1 THEN 1 END) as wins,
        COUNT(CASE WHEN final_position <= 3 AND final_position IS NOT NULL THEN 1 END) as podiums,
        MIN(final_position) as best_position,
        SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned,
        MAX(COALESCE(joined_at, updated_at)) as last_played
    FROM tbl_rumble_participants 
    WHERE user_id = ?
");
$stmt->execute([$discordId]);
$rumbleData = $stmt->fetch(PDO::FETCH_ASSOC);

$response['cheese_rumble'] = [
    'total_rumbles' => (int)$rumbleData['total_rumbles'],
    'wins' => (int)$rumbleData['wins'],
    'podiums' => (int)$rumbleData['podiums'],
    'best_position' => $rumbleData['best_position'] ? (int)$rumbleData['best_position'] : null,
    'dspoinc_earned' => (int)$rumbleData['total_dspoinc_earned'],
    'last_played' => $rumbleData['last_played']
];
```

**CRITICAL:** Uses `user_id` field (NOT `discord_id`)!

### **Profile.html Display:**

#### **1. Game Stats Display:**
```javascript
// public/profile.html
case 'cheese_rumble':
    statsHTML = `
      <div class="text-blue-300">Total Rumbles: ${stats.total_rumbles || 0}</div>
      <div class="text-green-300">Wins: ${stats.wins || 0}</div>
      <div class="text-purple-300">Podium Finishes: ${stats.podium_finishes || 0}</div>
      <div class="text-yellow-300">Best Position: ${stats.best_position || 'N/A'}</div>
      <div class="text-yellow-300">DSPOINC Earned: ${stats.dspoinc_earned ? stats.dspoinc_earned.toLocaleString() : '0'}</div>
    `;
    break;
```

#### **2. All-Time Stats Card:**
```javascript
// public/profile.html
const rumble = stats.games.cheese_rumble;
if (rumble) {
  cardsHTML += `
    <div class="bg-gradient-to-br from-orange-900/20 to-red-900/20 rounded-lg p-3 border border-orange-500/30">
      <div class="flex items-center gap-2 mb-2">
        <span class="text-2xl">💥</span>
        <span class="text-white font-semibold">${rumble.name || 'Cheese Rumble'}</span>
      </div>
      <div class="text-xs space-y-1">
        <div class="flex justify-between">
          <span class="text-gray-400">Total Rumbles:</span>
          <span class="text-white font-semibold">${rumble.total_rumbles ? rumble.total_rumbles.toLocaleString() : 0}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">Wins:</span>
          <span class="text-orange-300">${rumble.total_wins || 0} (${rumble.total_rumbles > 0 ? Math.round(((rumble.total_wins || 0)/rumble.total_rumbles)*100) : 0}%)</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">Best Position:</span>
          <span class="text-red-300">#${rumble.best_position || 'N/A'}</span>
        </div>
      </div>
    </div>
  `;
}
```

#### **3. Game Played Check:**
```javascript
// public/profile.html
function hasPlayedGame(game) {
    if (game.total_rumbles) return game.total_rumbles > 0; // 6th game: Cheese Rumble
    // ... other games
}
```

---

## 🖥️ **ADMIN INTERFACE INTEGRATION**

### **API Endpoint: `/api/admin/get-all-games-stats.php`**

#### **Cheese Rumble Statistics:**
```php
// api/admin/get-all-games-stats.php
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT cr.rumble_id) as total_rumbles,
        COUNT(DISTINCT rp.user_id) as unique_players,
        COUNT(CASE WHEN rp.status = 'winner' OR rp.final_position = 1 THEN 1 END) as wins,
        COUNT(CASE WHEN rp.final_position <= 3 AND rp.final_position IS NOT NULL THEN 1 END) as podiums,
        COUNT(CASE WHEN cr.created_at >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
        COUNT(CASE WHEN cr.created_at >= datetime('now', '-7 days') THEN 1 END) as recent_7d
    FROM tbl_cheese_rumbles cr
    LEFT JOIN tbl_rumble_participants rp ON cr.rumble_id = rp.rumble_id
    WHERE cr.created_at >= ?
");
$stmt->execute([$seasonStart]);
$rumbleData = $stmt->fetch(PDO::FETCH_ASSOC);

$response['data']['games']['cheese_rumble'] = [
    'game_name' => 'Cheese Rumble',
    'game_icon' => '💥',
    'status' => 'active',
    'season_data' => [
        'total_rumbles' => (int)$rumbleData['total_rumbles'],
        'unique_players' => (int)$rumbleData['unique_players'],
        'wins' => (int)$rumbleData['wins'],
        'podiums' => (int)$rumbleData['podiums'],
        'recent_24h' => (int)$rumbleData['recent_24h'],
        'recent_7d' => (int)$rumbleData['recent_7d']
    ]
];
```

### **Admin Interface Display:**
```javascript
// admin-interface.html
function displayCheeseRumbleStats(data) {
    const rumbleTab = document.getElementById('cheeseRumbleTab');
    
    rumbleTab.innerHTML = `
        <div class="stats-card">
            <h3>Cheese Rumble Statistics</h3>
            <p>Total Rumbles: ${data.total_rumbles}</p>
            <p>Unique Players: ${data.unique_players}</p>
            <p>Total Wins: ${data.wins}</p>
            <p>Total Podiums: ${data.podiums}</p>
            <p>Recent 24h: ${data.recent_24h}</p>
            <p>Recent 7d: ${data.recent_7d}</p>
        </div>
    `;
}
```

---

## 🔌 **API INTEGRATION**

### **API Endpoint 1: `/api/user/user-game-missions.php`**

#### **Request Format:**
```json
{
    "user_id": "1107633105185013790"
}
```

#### **Response Format:**
```json
{
    "success": true,
    "cheese_rumble": {
        "total_rumbles": 5,
        "wins": 2,
        "podiums": 3,
        "best_position": 1,
        "dspoinc_earned": 12000,
        "last_played": "2025-12-20 10:30:00"
    }
}
```

**CRITICAL:** Uses `user_id` field in query (NOT `discord_id`)!

### **API Endpoint 2: `/api/admin/get-all-games-stats.php`**

#### **Response Format:**
```json
{
    "success": true,
    "data": {
        "games": {
            "cheese_rumble": {
                "game_name": "Cheese Rumble",
                "game_icon": "💥",
                "status": "active",
                "season_data": {
                    "total_rumbles": 50,
                    "unique_players": 25,
                    "wins": 10,
                    "podiums": 15,
                    "recent_24h": 5,
                    "recent_7d": 20
                }
            }
        }
    }
}
```

---

## 💡 **CODE EXAMPLES**

### **Complete Rumble Lifecycle:**
```javascript
// discord/commands/cheese-rumble.js

// 1. Create rumble
const rumble = await createRumble(interaction);

// 2. Players join
await joinRumble(rumbleId, player);

// 3. Rumble starts
await startRumble(rumbleId);

// 4. Process rounds
await processRound(rumbleId);

// 5. Award first out (when first elimination)
await awardFirstOut(rumbleId, firstElimination);

// 6. Rumble ends
await endRumble(rumbleId);

// 7. Award winner
await awardWinner(rumbleId, winner);
```

### **Event Processing:**
```javascript
// discord/commands/cheese-rumble.js
function getRandomEvent(rumble) {
    const random = Math.random();
    let eventType;
    
    if (random < 0.35) eventType = 'kills';           // 35%
    else if (random < 0.50) eventType = 'itemUsage';  // 15%
    else if (random < 0.70) eventType = 'selfEliminations'; // 20%
    else if (random < 0.82) eventType = 'itemFinding'; // 12%
    else if (random < 0.92) eventType = 'special';    // 10%
    else if (random < 0.97) eventType = 'environmental'; // 5%
    else eventType = 'revival';                        // 3%
    
    return selectEventFromPool(eventType, rumble);
}
```

---

## ✅ **TESTING & VERIFICATION**

### **Test Checklist:**
- [ ] Rumble creation saves to `tbl_cheese_rumbles`
- [ ] Player joining saves to `tbl_rumble_participants`
- [ ] User queries use `user_id` field (NOT `discord_id`)
- [ ] Position tracking uses `final_position` field
- [ ] First out reward (1,000 DSPOINC) awarded correctly
- [ ] Winner reward (configurable DSPOINC) awarded correctly
- [ ] Profile.html displays stats correctly
- [ ] Admin interface displays stats correctly
- [ ] Discord bot buttons work correctly

### **Verification Commands:**
```sql
-- Verify rumble created
SELECT * FROM tbl_cheese_rumbles WHERE rumble_id = 'RUMBLE_ID';

-- Verify participant joined
SELECT * FROM tbl_rumble_participants WHERE rumble_id = 'RUMBLE_ID' AND user_id = 'USER_ID';

-- Verify user stats
SELECT 
    COUNT(*) as total_rumbles,
    COUNT(CASE WHEN status = 'winner' THEN 1 END) as wins
FROM tbl_rumble_participants
WHERE user_id = 'USER_ID';
```

---

## 🚨 **CRITICAL RULES**

1. **ALWAYS use `user_id`** for Cheese Rumble queries (NOT `discord_id`)
2. **ALWAYS use `final_position`** field (NOT `position`)
3. **ALWAYS use `tbl_rumble_participants`** table for participant data
4. **ALWAYS use `tbl_cheese_rumbles`** table for rumble data
5. **CRITICAL:** Field names differ from Discord Race (uses `position`, Cheese Rumble uses `final_position`)!

---

**💥 Complete technical documentation for Cheese Rumble - Ready for decades of development! 💥**

