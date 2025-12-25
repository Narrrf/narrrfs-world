# 🏁 GAME 5: DISCORD RACE - COMPLETE TECHNICAL DOCUMENTATION 2025

**Created:** December 20, 2025  
**Status:** ✅ **PRODUCTION READY - COMPLETE INTEGRATION**  
**Version:** 3.0 - Database-Integrated System  
**Purpose:** Complete technical reference for Discord Race game integration in Narrrfs World

---

## 📋 **TABLE OF CONTENTS**

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Discord Bot Implementation](#discord-bot-implementation)
4. [Database Schema](#database-schema)
5. [Race System Flow](#race-system-flow)
6. [DSPOINC Rewards](#dspoinc-rewards)
7. [API Integration](#api-integration)
8. [Admin Interface Integration](#admin-interface-integration)
9. [Code Examples](#code-examples)
10. [Testing & Verification](#testing--verification)

---

## 🎯 **OVERVIEW**

### **Game Description:**
Discord Race is a Discord-native competitive game where players participate in real-time cheese collection races directly within Discord channels. Players join races via Discord bot commands, compete to collect the most cheese pieces, and winners receive DSPOINC rewards. This is a unique social gaming experience native to Discord.

### **Key Features:**
- ✅ Discord bot integration (`/cheese-race start`)
- ✅ Real-time race participation
- ✅ Button-based interaction (Join Race, Leave Race, Start Race)
- ✅ DSPOINC rewards for winners
- ✅ Position tracking (1st, 2nd, 3rd place)
- ✅ Season-aware data tracking
- ✅ Full database persistence

### **Integration Status:**
- ✅ **Discord Bot:** `discord/commands/cheese-race.js`
- ✅ **Database:** `tbl_cheese_races`, `tbl_race_participants`
- ✅ **Admin Interface:** Full integration via `/api/admin/get-all-games-stats.php`
- ✅ **User Missions:** Full integration via `/api/user/user-game-missions.php`

---

## 🏗️ **ARCHITECTURE**

### **System Flow:**
```
User Runs /cheese-race start in Discord
        ↓
Discord Bot Creates Race Object
        ↓
Bot Saves Race to Database (tbl_cheese_races)
        ↓
Bot Posts Race Embed with Buttons
        ↓
Players Click "Join Race" Button
        ↓
Bot Adds Players to Database (tbl_race_participants)
        ↓
Race Starts (Auto or Manual)
        ↓
Players Compete (Cheese Collection)
        ↓
Bot Tracks Progress in Real-Time
        ↓
Race Ends (Timer or Manual)
        ↓
Bot Determines Winner (Position 1)
        ↓
Bot Awards DSPOINC to Winner
        ↓
Bot Updates All Participant Records
        ↓
Race Status Set to 'finished'
```

### **Technology Stack:**
- **Discord Bot:** Node.js, discord.js
- **Database:** SQLite3 (`narrrf_world.sqlite`)
- **APIs:** PHP RESTful endpoints
- **Integration:** Discord OAuth 2.0

---

## 🤖 **DISCORD BOT IMPLEMENTATION**

### **File Structure:**
```
discord/
├── commands/
│   └── cheese-race.js          # Main race command (4,800+ lines)
└── index.js                     # Bot initialization & button handlers
```

### **Key Commands:**

#### **1. Start Race Command:**
```javascript
// discord/commands/cheese-race.js
module.exports = {
    data: new SlashCommandBuilder()
        .setName('cheese-race')
        .setDescription('Start a cheese race!')
        .addSubcommand(subcommand =>
            subcommand
                .setName('start')
                .setDescription('Start a new cheese race')
                .addIntegerOption(option =>
                    option.setName('duration')
                        .setDescription('Race duration in seconds')
                        .setRequired(false)
                        .setMinValue(60)
                        .setMaxValue(3600))
                .addIntegerOption(option =>
                    option.setName('max-players')
                        .setDescription('Maximum players')
                        .setRequired(false)
                        .setMinValue(2)
                        .setMaxValue(50))),
    async execute(interaction) {
        // Create race object
        const race = {
            id: generateRaceId(),
            creator: interaction.user.id,
            creatorName: interaction.user.username,
            channelId: interaction.channel.id,
            status: 'waiting',
            maxPlayers: maxPlayers || 10,
            duration: duration || 300,
            dspoincReward: 1000,
            players: []
        };
        
        // Save to database
        await createRaceInDatabase(race, queryDb);
        
        // Post race embed
        const message = await createAndSendRaceMessage(race, interaction.channel);
        race.messageId = message.id;
        
        // Store in active races map
        activeRaces.set(race.id, race);
    }
};
```

#### **2. Join Race Button Handler:**
```javascript
// discord/index.js
client.on('interactionCreate', async interaction => {
    if (interaction.isButton() && interaction.customId.startsWith('join_race_')) {
        const raceId = interaction.customId.replace('join_race_', '');
        const race = activeRaces.get(raceId);
        
        // Add player to race
        const player = {
            id: interaction.user.id,
            username: interaction.user.username,
            joinedAt: Date.now(),
            cheeseCount: 0,
            position: 0
        };
        
        race.players.push(player);
        
        // Save to database
        await addPlayerToRaceInDatabase(raceId, player, queryDb);
        
        // Update race message
        await updateRaceMessage(race, interaction.channel);
    }
});
```

#### **3. Race Completion:**
```javascript
// discord/commands/cheese-race.js
async function endRace(raceId, queryDb) {
    const race = activeRaces.get(raceId);
    
    // Sort players by cheese count (descending)
    race.players.sort((a, b) => b.cheeseCount - a.cheeseCount);
    
    // Assign positions
    race.players.forEach((player, index) => {
        player.position = index + 1;
        
        // Update database
        updatePlayerProgressInDatabase(
            raceId,
            player.id,
            player.position,
            player.cheeseCount,
            'completed',
            queryDb
        );
    });
    
    // Award DSPOINC to winner (position 1)
    const winner = race.players[0];
    if (winner) {
        await awardDspoincToWinner(winner.id, race.dspoincReward, queryDb);
        
        // Update participant record with DSPOINC earned
        await queryDb(`
            UPDATE tbl_race_participants 
            SET dspoinc_earned = ?, finished_at = ?
            WHERE race_id = ? AND user_id = ?
        `, [race.dspoincReward, new Date().toISOString(), raceId, winner.id]);
    }
    
    // Update race status
    await updateRaceStatusInDatabase(raceId, 'finished', queryDb);
}
```

---

## 🗄️ **DATABASE SCHEMA**

### **1. Race Table: `tbl_cheese_races`**

```sql
CREATE TABLE tbl_cheese_races (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    race_id TEXT UNIQUE NOT NULL,           -- Unique race identifier
    creator_id TEXT NOT NULL,               -- Discord ID of race creator
    creator_name TEXT NOT NULL,             -- Username of race creator
    status TEXT NOT NULL DEFAULT 'waiting', -- waiting, active, finished, cancelled
    max_players INTEGER NOT NULL DEFAULT 10,
    duration INTEGER NOT NULL DEFAULT 300,  -- Race duration in seconds
    dspoinc_reward INTEGER NOT NULL DEFAULT 1000,
    role_reward TEXT,                       -- Optional Discord role to award
    tag_role TEXT,                          -- Role to tag for race announcements
    channel_id TEXT,                        -- Discord channel where race was created
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,                    -- When race actually started
    finished_at DATETIME,                   -- When race finished
    winner_id TEXT,                         -- Discord ID of winner
    winner_name TEXT,                       -- Username of winner
    comment TEXT                            -- Optional race comment
);

CREATE INDEX idx_cheese_races_status ON tbl_cheese_races(status);
CREATE INDEX idx_cheese_races_creator ON tbl_cheese_races(creator_id);
CREATE INDEX idx_cheese_races_created ON tbl_cheese_races(created_at);
```

### **2. Participants Table: `tbl_race_participants`**

**CRITICAL:** Uses `user_id` field (NOT `discord_id`) and `position` field (NOT `final_position`)!

```sql
CREATE TABLE tbl_race_participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    race_id TEXT NOT NULL,                  -- References tbl_cheese_races.race_id
    user_id TEXT NOT NULL,                  -- Discord ID (NOT discord_id!)
    username TEXT NOT NULL,                 -- Discord username
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    cheese_count INTEGER DEFAULT 0,        -- Cheese pieces collected
    start_time DATETIME,                    -- When race started for this player
    end_time DATETIME,                      -- When race ended for this player
    finished_at DATETIME,                   -- When player finished
    position INTEGER DEFAULT 0,             -- Final position (NOT final_position!)
    status TEXT DEFAULT 'joined',           -- joined, active, completed, left
    dspoinc_earned INTEGER DEFAULT 0,       -- DSPOINC earned (winner only)
    season TEXT,                            -- Season identifier
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (race_id) REFERENCES tbl_cheese_races(race_id)
);

CREATE INDEX idx_race_participants_race ON tbl_race_participants(race_id);
CREATE INDEX idx_race_participants_user ON tbl_race_participants(user_id);
CREATE INDEX idx_race_participants_position ON tbl_race_participants(position);
CREATE INDEX idx_race_participants_season ON tbl_race_participants(season);
```

**CRITICAL RULES:**
- ✅ Uses `user_id` field (NOT `discord_id`)
- ✅ Uses `position` field (NOT `final_position`)
- ✅ Season-aware data tracking

### **3. Query Examples:**

#### **Get User Race Stats:**
```sql
SELECT 
    COUNT(*) as total_races,
    COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
    COUNT(CASE WHEN position <= 3 THEN 1 END) as podiums,
    MIN(position) as best_position,
    SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned
FROM tbl_race_participants
WHERE user_id = ?;
```

#### **Get Race Participants:**
```sql
SELECT 
    user_id,
    username,
    position,
    cheese_count,
    dspoinc_earned,
    status
FROM tbl_race_participants
WHERE race_id = ?
ORDER BY position ASC;
```

---

## 🏁 **RACE SYSTEM FLOW**

### **1. Race Creation:**
```javascript
// User runs /cheese-race start
const race = {
    id: generateRaceId(),
    creator: interaction.user.id,
    creatorName: interaction.user.username,
    status: 'waiting',
    maxPlayers: 10,
    duration: 300, // 5 minutes
    dspoincReward: 1000,
    players: []
};

// Save to database
await createRaceInDatabase(race, queryDb);
```

### **2. Player Joining:**
```javascript
// Player clicks "Join Race" button
const player = {
    id: interaction.user.id,
    username: interaction.user.username,
    joinedAt: Date.now(),
    cheeseCount: 0,
    position: 0
};

// Add to race
race.players.push(player);

// Save to database
await addPlayerToRaceInDatabase(race.id, player, queryDb);
```

### **3. Race Execution:**
```javascript
// Race starts (auto or manual)
race.status = 'active';
race.startTime = Date.now();

// Update database
await updateRaceStatusInDatabase(race.id, 'active', queryDb);

// Track progress (periodic updates)
setInterval(() => {
    race.players.forEach(player => {
        // Update cheese count from game
        updatePlayerProgressInDatabase(
            race.id,
            player.id,
            player.position,
            player.cheeseCount,
            'active',
            queryDb
        );
    });
}, 5000); // Every 5 seconds
```

### **4. Race Completion:**
```javascript
// Race ends (timer or manual)
race.status = 'finished';
race.endTime = Date.now();

// Sort players by cheese count
race.players.sort((a, b) => b.cheeseCount - a.cheeseCount);

// Assign positions
race.players.forEach((player, index) => {
    player.position = index + 1;
    
    // Update database
    updatePlayerProgressInDatabase(
        race.id,
        player.id,
        player.position,
        player.cheeseCount,
        'completed',
        queryDb
    );
});

// Award winner
const winner = race.players[0];
if (winner) {
    await awardDspoincToWinner(winner.id, race.dspoincReward, queryDb);
}
```

---

## 💰 **DSPOINC REWARDS**

### **Reward System:**
- **Winner (Position 1):** Full DSPOINC reward (default: 1,000 DSPOINC)
- **Other Positions:** 0 DSPOINC (no rewards for non-winners)
- **Lucky Loser Feature:** First player out gets 1,000 DSPOINC (optional)

### **Reward Distribution:**
```javascript
// discord/commands/cheese-race.js
async function awardDspoincToWinner(userId, amount, queryDb) {
    // Update tbl_user_scores
    await queryDb(`
        INSERT INTO tbl_user_scores (user_id, score, game, source, timestamp)
        VALUES (?, ?, 'discord_race', 'race_winner', datetime('now'))
    `, [userId, amount]);
    
    // Update tbl_race_participants
    await queryDb(`
        UPDATE tbl_race_participants 
        SET dspoinc_earned = ?
        WHERE race_id = ? AND user_id = ? AND position = 1
    `, [amount, raceId, userId]);
    
    // Audit trail
    await queryDb(`
        INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp)
        VALUES (?, 'discord-bot', ?, 'add', 'Discord Race Winner', datetime('now'))
    `, [userId, amount]);
}
```

---

## 🔌 **API INTEGRATION**

### **API Endpoint: `/api/user/user-game-missions.php`**

#### **Discord Race Statistics:**
```php
// api/user/user-game-missions.php
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_races,
        COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
        COUNT(CASE WHEN position <= 3 THEN 1 END) as podiums,
        MIN(position) as best_position,
        SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned
    FROM tbl_race_participants 
    WHERE user_id = ?
");
$stmt->execute([$discordId]);
$raceData = $stmt->fetch(PDO::FETCH_ASSOC);

$response['discord_race'] = [
    'total_races' => (int)$raceData['total_races'],
    'wins' => (int)$raceData['wins'],
    'podiums' => (int)$raceData['podiums'],
    'best_position' => $raceData['best_position'] ? (int)$raceData['best_position'] : null,
    'dspoinc_earned' => (int)$raceData['total_dspoinc_earned']
];
```

**CRITICAL:** Uses `user_id` field (NOT `discord_id`)!

### **API Endpoint: `/api/admin/get-all-games-stats.php`**

#### **Admin Statistics:**
```php
// api/admin/get-all-games-stats.php
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_races,
        COUNT(DISTINCT user_id) as unique_players,
        COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
        COUNT(CASE WHEN position <= 3 THEN 1 END) as podiums,
        COUNT(CASE WHEN joined_at >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
        COUNT(CASE WHEN joined_at >= datetime('now', '-7 days') THEN 1 END) as recent_7d
    FROM tbl_race_participants
");
$stmt->execute();
$raceData = $stmt->fetch(PDO::FETCH_ASSOC);

$response['data']['games']['discord_race'] = [
    'game_name' => 'Discord Race',
    'game_icon' => '🏁',
    'status' => 'active',
    'season_data' => [
        'total_races' => (int)$raceData['total_races'],
        'unique_players' => (int)$raceData['unique_players'],
        'wins' => (int)$raceData['wins'],
        'podiums' => (int)$raceData['podiums'],
        'recent_24h' => (int)$raceData['recent_24h'],
        'recent_7d' => (int)$raceData['recent_7d']
    ]
];
```

---

## 🖥️ **ADMIN INTERFACE INTEGRATION**

### **Admin Interface Display:**
```javascript
// admin-interface.html
function displayDiscordRaceStats(data) {
    const raceTab = document.getElementById('discordRaceTab');
    
    raceTab.innerHTML = `
        <div class="stats-card">
            <h3>Discord Race Statistics</h3>
            <p>Total Races: ${data.total_races}</p>
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

## 💡 **CODE EXAMPLES**

### **Complete Race Lifecycle:**
```javascript
// discord/commands/cheese-race.js

// 1. Create race
const race = await createRace(interaction);

// 2. Players join
await joinRace(raceId, player);

// 3. Race starts
await startRace(raceId);

// 4. Track progress
await updateProgress(raceId, playerId, cheeseCount);

// 5. Race ends
await endRace(raceId);

// 6. Award winner
await awardWinner(raceId, winnerId);
```

---

## ✅ **TESTING & VERIFICATION**

### **Test Checklist:**
- [ ] Race creation saves to `tbl_cheese_races`
- [ ] Player joining saves to `tbl_race_participants`
- [ ] Position tracking uses `position` field (NOT `final_position`)
- [ ] User queries use `user_id` field (NOT `discord_id`)
- [ ] DSPOINC awarded to winner correctly
- [ ] Admin interface displays stats
- [ ] User missions show race data

### **Verification Commands:**
```sql
-- Verify race created
SELECT * FROM tbl_cheese_races WHERE race_id = 'RACE_ID';

-- Verify participant joined
SELECT * FROM tbl_race_participants WHERE race_id = 'RACE_ID' AND user_id = 'USER_ID';

-- Verify user stats
SELECT 
    COUNT(*) as total_races,
    COUNT(CASE WHEN position = 1 THEN 1 END) as wins
FROM tbl_race_participants
WHERE user_id = 'USER_ID';
```

---

## 🚨 **CRITICAL RULES**

1. **ALWAYS use `user_id`** for Discord Race queries (NOT `discord_id`)
2. **ALWAYS use `position`** field (NOT `final_position`)
3. **ALWAYS use `tbl_race_participants`** table for participant data
4. **ALWAYS use `tbl_cheese_races`** table for race data
5. **CRITICAL:** Field names differ from other games!

---

**🏁 Complete technical documentation for Discord Race - Ready for decades of development! 🏁**

