# 🧀 CHEESE RACE SYSTEM - COMPREHENSIVE ANALYSIS

**Date:** December 3, 2025  
**Purpose:** Understanding Cheese Race mechanics for creating Cheese Rumble  
**Status:** ✅ **ANALYSIS COMPLETE**

---

## 🎯 **CORE CONCEPT**

**Cheese Race** is a Discord-based competitive event where players:
1. Join via Discord button interaction
2. Play the Snake game to collect cheese pieces
3. Compete for the highest cheese count within a time limit
4. Winner receives DSPOINC rewards and optional role rewards

---

## 📊 **DATABASE STRUCTURE**

### **Table 1: `tbl_cheese_races`**
**Purpose:** Stores race event information

**Key Fields:**
- `race_id` - Unique race identifier
- `creator_id` - Discord ID of race creator
- `creator_name` - Username of race creator
- `channel_id` - Discord channel where race is posted
- `message_id` - Discord message ID of race embed
- `status` - Race status (`waiting`, `active`, `finished`, `cancelled`)
- `max_players` - Maximum number of participants
- `duration` - Race duration in seconds
- `dspoinc_reward` - DSPOINC prize amount
- `role_reward` - Optional role reward name
- `tag_role` - Optional role to mention on race end
- `auto_start` - Boolean for auto-start when full
- `created_at` - Race creation timestamp
- `started_at` - Race start timestamp
- `finished_at` - Race end timestamp
- `ended_at` - Race end timestamp (duplicate)
- `winner_id` - Discord ID of winner
- `winner_name` - Username of winner
- `comment` - Optional race description

### **Table 2: `tbl_race_participants`**
**Purpose:** Stores participant data and progress

**Key Fields:**
- `race_id` - Reference to race
- `user_id` - Discord ID of participant (also used as `discord_id` in some queries)
- `username` - Username of participant
- `joined_at` - Join timestamp
- `cheese_count` - Current cheese pieces collected
- `start_time` - Participant start time
- `end_time` - Participant finish time
- `finished_at` - Participant finish timestamp
- `position` - Final ranking position (1 = winner)
- `status` - Participant status (`joined`, `active`, `completed`, `winner`)
- `dspoinc_earned` - DSPOINC amount earned
- `season` - Season identifier
- `updated_at` - Last update timestamp

---

## 🔄 **RACE LIFECYCLE**

### **Phase 1: Race Creation**
1. Admin creates race via `/cheese-race create` command
2. Race embed posted in Discord channel
3. Race status: `waiting`
4. Players can join via "Join Race" button

### **Phase 2: Waiting Period**
- Players join via button interaction
- Race message updates with participant list
- Race can auto-start when full OR manually started
- Race status remains: `waiting`

### **Phase 3: Race Active**
1. Race starts (auto or manual)
2. Race status changes to: `active`
3. Participants play Snake game
4. Game tracks cheese pieces collected per participant
5. Progress updates in database via game API
6. Race message updates with live standings

### **Phase 4: Race End**
1. Duration timer expires OR all players finish
2. Winner determined by highest `cheese_count`
3. Race status changes to: `finished`
4. Winner receives:
   - DSPOINC reward (added to `tbl_user_scores`)
   - Optional role reward
   - Score adjustment logged in `tbl_score_adjustments`
5. Final leaderboard displayed
6. All-time leaderboard shown

---

## 🎮 **GAME INTEGRATION**

### **How Players Compete:**
1. Players join race in Discord
2. Players launch Snake game (web-based)
3. Game checks for active race via API
4. Game tracks cheese pieces collected
5. Game sends progress updates to API
6. API updates `tbl_race_participants.cheese_count`
7. Discord bot displays live standings

### **API Endpoints Used:**
- Game checks active race status
- Game submits cheese count updates
- Game queries leaderboard

---

## 🤖 **DISCORD BOT FUNCTIONS**

### **Command: `/cheese-race create`**
**Parameters:**
- `duration` - Race duration in seconds
- `max_players` - Maximum participants (default: 10)
- `dspoinc_reward` - DSPOINC prize amount
- `role_reward` - Optional role name
- `tag_role` - Optional role to mention
- `auto_start` - Auto-start when full (boolean)
- `comment` - Optional description

**Actions:**
1. Creates race record in `tbl_cheese_races`
2. Posts race embed in Discord channel
3. Adds race to `activeRaces` Map (in-memory)
4. Returns race ID

---

### **Button Interactions:**

#### **1. Join Race Button (`join_race_${raceId}`)**
- Adds player to `tbl_race_participants`
- Updates race message with new participant
- Disables button if max players reached

#### **2. Leave Race Button (`leave_race_${raceId}`)**
- Removes player from participants
- Updates race message
- Only works during `waiting` status

#### **3. Start Race Button (`start_race_${raceId}`)**
- Changes race status to `active`
- Sets `started_at` timestamp
- Updates race message with active status
- Only works if at least 1 player joined

#### **4. Cancel Race Button (`cancel_race_${raceId}`)**
- Changes race status to `cancelled`
- Removes race from active races
- Only works during `waiting` status

#### **5. View Racers Button (`view_racers_${raceId}`)**
- Shows detailed participant list
- Displays current standings

---

## 💾 **PERSISTENCE & RECOVERY**

### **Bot Startup:**
1. `loadRacesFromDatabase()` called on bot startup
2. Loads all races with status `waiting` or `active`
3. Loads participants for each race
4. Restores race timers if race was active
5. Adds races to `activeRaces` Map

### **Database Sync:**
- All race state changes synced to database immediately
- Player progress synced in real-time
- Race completion fully persisted

---

## 🏆 **REWARD SYSTEM**

### **Winner Determination:**
- Highest `cheese_count` wins
- If tie, earliest finish time wins

### **DSPOINC Award:**
1. Winner's balance updated in `tbl_user_scores`
2. Score adjustment logged in `tbl_score_adjustments`
3. Action: `add`, Reason: `Cheese race winner - Race ID: ${raceId}`

### **Role Reward:**
- Optional role added to winner
- Role name or ID specified in race creation

### **Leaderboard:**
- All-time leaderboard shows top 10 champions
- Displays: wins, total races, total DSPOINC, win rate

---

## 🔧 **TECHNICAL IMPLEMENTATION**

### **File Structure:**
```
discord/
├── commands/
│   └── cheese-race.js          # Main race command (4844 lines)
├── index.js                     # Button handlers, bot startup
└── ...

api/
├── dev/
│   ├── get-leaderboard.php     # Leaderboard API
│   └── save-score.php          # Score submission API
└── admin/
    ├── get-discord-race-overview.php
    ├── get-race-stats.php
    └── ...
```

### **Key Functions:**

#### **In-Memory Management:**
- `activeRaces` Map - Stores active races during runtime
- Fast lookups for button interactions
- Synced with database on startup

#### **Database Functions:**
- `loadRacesFromDatabase()` - Load races on startup
- `createRaceInDatabase()` - Create new race
- `updateRaceStatusInDatabase()` - Update race status
- `addPlayerToRaceInDatabase()` - Add participant
- `updatePlayerProgressInDatabase()` - Update cheese count
- `endRaceInDatabase()` - Complete race

#### **Message Functions:**
- `createAndSendRaceMessage()` - Create race embed
- `updateRaceMessage()` - Update race embed
- `endRaceMessage()` - Final results embed

---

## 📋 **RACE CONFIGURATION OPTIONS**

### **Duration:**
- Configurable in seconds (e.g., 300 = 5 minutes)
- Timer starts when race becomes `active`
- Race ends automatically when timer expires

### **Max Players:**
- Default: 10 players
- Join button disabled when full
- Auto-start can trigger when full

### **Auto-Start:**
- If `true`: Race starts automatically when max players reached
- If `false`: Requires manual start via button

### **Rewards:**
- DSPOINC: Fixed amount for winner
- Role: Optional Discord role reward
- Tag Role: Optional role to mention on completion

---

## 🎯 **KEY MECHANICS SUMMARY**

1. **Discord-Based Competition** - Players join via Discord buttons
2. **Game Integration** - Players play Snake game to collect cheese
3. **Real-Time Tracking** - Cheese count updates during gameplay
4. **Time-Limited** - Race has fixed duration
5. **Winner Rewards** - DSPOINC + optional role
6. **Leaderboard** - All-time statistics tracked
7. **Persistence** - All data stored in database
8. **Recovery** - Races restored on bot restart

---

## 💡 **DESIGN PATTERNS**

### **1. Database-First Architecture:**
- All state stored in database
- In-memory Map for performance
- Database is source of truth

### **2. Button-Based Interaction:**
- Discord buttons for user actions
- Real-time message updates
- Clean, intuitive UI

### **3. Real-Time Progress Tracking:**
- Game API submits progress
- Discord bot displays standings
- Live updates during race

### **4. Persistence & Recovery:**
- Races survive bot restarts
- Active races restored from database
- Timers re-established on startup

---

## 🚀 **EXTENSIBILITY POINTS**

### **Easy to Extend:**
- ✅ New race types (different games)
- ✅ New reward types
- ✅ New race parameters
- ✅ New leaderboard types
- ✅ New status types

### **Modular Components:**
- Race creation logic
- Button handlers
- Database functions
- Message builders
- Reward system

---

**Analysis Complete:** December 3, 2025  
**Status:** ✅ **READY FOR CHEESE RUMBLE DESIGN**  
**Next:** Design Cheese Rumble based on same architecture

