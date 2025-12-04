# 💥 CHEESE RUMBLE - IMPLEMENTATION PLAN

**Date:** December 3, 2025  
**Status:** ✅ **READY TO BUILD**

---

## 🎯 **PHASE 1: DATABASE TABLES**

### **Table 1: `tbl_cheese_rumbles`**
```sql
CREATE TABLE IF NOT EXISTS tbl_cheese_rumbles (
    rumble_id TEXT PRIMARY KEY,
    creator_id TEXT NOT NULL,
    creator_name TEXT NOT NULL,
    channel_id TEXT NOT NULL,
    message_id TEXT,
    status TEXT NOT NULL DEFAULT 'waiting', -- waiting, active, finished, cancelled
    max_players INTEGER DEFAULT 10,
    duration INTEGER DEFAULT 300, -- seconds
    dspoinc_reward INTEGER DEFAULT 5000,
    role_reward TEXT,
    tag_role TEXT,
    auto_start INTEGER DEFAULT 0, -- 0 = false, 1 = true
    comment TEXT,
    current_round INTEGER DEFAULT 0,
    players_alive INTEGER DEFAULT 0,
    events_log TEXT, -- JSON array of events
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,
    finished_at DATETIME,
    ended_at DATETIME,
    winner_id TEXT,
    winner_name TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### **Table 2: `tbl_rumble_participants`**
```sql
CREATE TABLE IF NOT EXISTS tbl_rumble_participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rumble_id TEXT NOT NULL,
    user_id TEXT NOT NULL, -- Discord ID
    username TEXT NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'alive', -- alive, eliminated, winner
    kills INTEGER DEFAULT 0,
    eliminated_by TEXT, -- user_id who eliminated them
    elimination_reason TEXT, -- story text of how they died
    eliminated_in_round INTEGER,
    final_position INTEGER, -- 1 = winner, 2 = 2nd place, etc.
    dspoinc_earned INTEGER DEFAULT 0,
    season TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rumble_id) REFERENCES tbl_cheese_rumbles(rumble_id)
);
```

---

## 🎯 **PHASE 2: DISCORD COMMAND STRUCTURE**

### **Command: `/cheese-rumble create`**

**Parameters:**
- `duration` - Race duration in seconds (default: 300)
- `max_players` - Maximum participants (default: 10)
- `dspoinc_reward` - DSPOINC prize amount (default: 5000)
- `role_reward` - Optional role name
- `tag_role` - Optional role to mention
- `auto_start` - Auto-start when full (boolean)
- `comment` - Optional description

**Embed (Start/Waiting):**
- Title: "🧀 **CHEESE RUMBLE CREATED!** 🧀"
- Thumbnail: `https://narrrfs.world/img/rumble/cheese_rumble.png`
- Description: Battle royale explanation
- Fields: Duration, Max Players, Prize, etc.
- Buttons: Join, Leave, Start, Cancel, View Participants

---

## 🎯 **PHASE 3: ROUND SYSTEM**

### **Round Generation Algorithm:**

```javascript
async function processRound(rumbleId, channel, queryDb) {
    const rumble = activeRumbles.get(rumbleId);
    if (!rumble || rumble.status !== 'active') return;
    
    // Get alive players
    const alivePlayers = rumble.players.filter(p => p.status === 'alive');
    
    // Check win condition
    if (alivePlayers.length <= 1) {
        endRumble(rumbleId, channel, queryDb);
        return;
    }
    
    // Increment round
    rumble.currentRound++;
    
    // Generate 3-8 random events
    const eventCount = Math.floor(Math.random() * 6) + 3; // 3-8 events
    
    // Process events
    for (let i = 0; i < eventCount && alivePlayers.length > 1; i++) {
        await processRandomEvent(rumbleId, channel, queryDb);
    }
    
    // Update embed with running image
    await updateRumbleMessage(rumble, channel);
    
    // Wait 5-10 seconds before next round
    setTimeout(() => {
        processRound(rumbleId, channel, queryDb);
    }, Math.floor(Math.random() * 5000) + 5000); // 5-10 seconds
}
```

---

## 🎯 **PHASE 4: EVENT SYSTEM**

### **Event Pool Structure:**

```javascript
const EVENT_POOL = {
    kills: [
        "🧀 **{killer}** threw a giant wheel of cheese at **{victim}**'s head! **{victim}** is now in cheese heaven! 🧀",
        "🧪 **{killer}** used a cheese experiment gone wrong and melted **{victim}**! Lab accident or murder? 🧀",
        // ... 30+ variations
    ],
    selfEliminations: [
        "🧀 **{player}** ate too much cheese and exploded! Too much of a good thing! 💥",
        "🧪 **{player}** drank experimental cheese formula and turned into a cheese puddle! Lab accident! 🧀",
        // ... 20+ variations
    ],
    special: [
        "🎁 **{player}** found a golden cheese wheel! Extra protection for next round! 🧀",
        "✨ **{player}** gained the power of cheese teleportation! Zoom zoom! ✨",
        // ... 30+ variations
    ],
    environmental: [
        "🌊 **{player}** got swept away by a wave of cheese fondue! Drowned in deliciousness! 🧀",
        "🏔️ **{player}** fell off a cheese mountain! Too high to survive! 🧀",
        // ... 15+ variations
    ]
};
```

### **Event Selection:**
- 50% probability: Kill events
- 30% probability: Self-elimination events
- 15% probability: Special events
- 5% probability: Environmental events

---

## 🎯 **PHASE 5: IMAGE INTEGRATION**

### **Embed Image Logic:**

```javascript
function getRumbleImage(status) {
    switch(status) {
        case 'waiting':
            return 'https://narrrfs.world/img/rumble/cheese_rumble.png';
        case 'active':
            return 'https://narrrfs.world/img/rumble/cheese_rumble_progress.png';
        case 'finished':
            return 'https://narrrfs.world/img/race/cheese-race-finish-banner.png';
        default:
            return 'https://narrrfs.world/img/rumble/cheese_rumble.png';
    }
}
```

---

## 🎯 **PHASE 6: REWARD SYSTEM**

### **Winner Reward:**
- Full DSPOINC reward (e.g., 5000 DSPOINC)
- Optional role reward
- Logged in `tbl_score_adjustments`

### **First Out Reward:**
- 1,000 DSPOINC consolation prize
- Logged in `tbl_score_adjustments`
- Awarded when first player is eliminated

---

## 📋 **IMPLEMENTATION CHECKLIST**

### **Phase 1: Database** ⏳
- [ ] Create `tbl_cheese_rumbles` table
- [ ] Create `tbl_rumble_participants` table
- [ ] Test database structure

### **Phase 2: Command** ⏳
- [ ] Create `/cheese-rumble create` command
- [ ] Add all parameters (same as Cheese Race)
- [ ] Create start embed with `cheese_rumble.png`
- [ ] Add join/leave/start/cancel buttons

### **Phase 3: Round System** ⏳
- [ ] Implement round generation
- [ ] Add 3-8 events per round logic
- [ ] Add 5-10 second delay between rounds
- [ ] Update embed with `cheese_rumble_progress.png` during active state

### **Phase 4: Event System** ⏳
- [ ] Create event pool (150+ variations)
- [ ] Implement random event selection
- [ ] Add event message posting
- [ ] Tag members in events

### **Phase 5: Elimination** ⏳
- [ ] Track player status (alive/eliminated)
- [ ] Count kills per player
- [ ] Determine winner (last alive)
- [ ] Award first out (1,000 DSPOINC)

### **Phase 6: Rewards** ⏳
- [ ] Award winner DSPOINC
- [ ] Award first out DSPOINC
- [ ] Log score adjustments
- [ ] Optional role rewards

### **Phase 7: End Screen** ⏳
- [ ] Create end embed with Cheese Race finish banner
- [ ] Show final leaderboard
- [ ] Display stats (most kills, etc.)
- [ ] Announce winner

---

## 🚀 **READY TO START BUILDING!**

**Status:** ✅ **ALL CONFIRMED - READY FOR IMPLEMENTATION**

**Next:** Start with Phase 1 (Database tables) and work through each phase!

