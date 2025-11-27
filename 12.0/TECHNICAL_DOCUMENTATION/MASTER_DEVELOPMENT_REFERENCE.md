# 🧀 MASTER DEVELOPMENT REFERENCE - NARRRFS WORLD

**Document Created:** November 26, 2025  
**Status:** ✅ **ACTIVE - SINGLE SOURCE OF TRUTH**  
**Purpose:** Complete reference for all levels, games, riddles, traits, rewards, APIs, and database structures  
**Scope:** All development work for decades to come  

---

## 📋 **TABLE OF CONTENTS**

1. [Level Structure & Patterns](#level-structure--patterns)
2. [Riddle System Architecture](#riddle-system-architecture)
3. [Trait System & Naming Conventions](#trait-system--naming-conventions)
4. [Reward System & Calculations](#reward-system--calculations)
5. [API Endpoints Reference](#api-endpoints-reference)
6. [Database Tables & Schema](#database-tables--schema)
7. [Game Systems (Tetris, Snake, Space Invaders, Cheese Hunt, Discord Race)](#game-systems)
8. [Code Patterns & Standards](#code-patterns--standards)
9. [File Structure & Organization](#file-structure--organization)

---

## 🎮 **LEVEL STRUCTURE & PATTERNS**

### **Current Levels: 5 Total**

#### **Level 1: Cheese Temple**
- **Type:** Multi-riddle exploration level
- **Total Riddles:** 4 (3 main + 1 secret)
- **Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md`
- **Status:** ✅ **PRODUCTION VERIFIED**

#### **Level 2: The Spawn (Matrix Construct)**
- **Type:** Inspection-based riddle with gallery exploration
- **Total Steps:** 3 (Step 0, Step 1, Step 2)
- **Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_THE_SPAWN_LEVEL_2.md`
- **Status:** ✅ **PRODUCTION VERIFIED**

#### **Level 3: The Hunt**
- **Type:** Monster hunting challenge (10 monsters in 2 phases)
- **Total Steps:** 3 (Step 0, Step 1, Step 2)
- **Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_THE_HUNT_LEVEL_3.md`
- **Status:** ✅ **PRODUCTION VERIFIED**

#### **Level 4: The First Shot**
- **Type:** Shooting challenge (50 cheeses + 30 monsters in waves)
- **Total Steps:** 4 (Step 0, Step 1, Step 2, Step 3)
- **Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_THE_FIRST_SHOT_LEVEL_4.md`
- **Status:** ✅ **PRODUCTION VERIFIED**

#### **Level 5: The Walk**
- **Type:** Open-world exploration with monster hunt
- **Total Steps:** 2 (Step 0, Step 1 - 10 waves of 5 monsters each)
- **Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_THE_WALK_LEVEL_5.md`
- **Status:** ✅ **STEP 1 COMPLETE**

---

### **Universal Level Requirements**

**CRITICAL RULE:** All levels MUST have identical features:
- ✅ **GOD Mode:** Double speed + fly mode (Space/Shift)
- ✅ **Level Selector:** L key opens level menu
- ✅ **Riddle Cycling:** G key cycles riddle steps
- ✅ **Sound System:** Footsteps, jump, level-up sounds
- ✅ **Options Menu:** GOD Mode toggle, camera modes
- ✅ **Player Speed:** 1.5x base speed (12 normal, 21 sprint)
- ✅ **Controls:** WASD movement, Space jump, Shift sprint
- ✅ **Camera Modes:** First-person, third-person, joystick view

**Rule Document:** `12.0/RULES/12_UNIVERSAL_LEVEL_REQUIREMENTS_RULE.md`

---

### **Level Implementation Pattern**

Every level follows this structure:

```javascript
// 1. State Objects
const levelXState = {
  built: false,
  group: new THREE.Group(),
  // Level-specific state
};

const levelXRiddleState = {
  step0Complete: false,
  step1Active: false,
  // Riddle-specific state
};

// 2. Configuration
const levelXConfig = {
  size: 160,
  origin: new THREE.Vector3(0, 0, z),
  spawnPosition: new THREE.Vector3(0, 1, z - 50),
  portalPosition: new THREE.Vector3(0, 5, z + 60)
};

// 3. Trait Constants
const LEVELX_STEP0_TRAIT = "CHEESE_TEMPLE_LEVELX_STEP0";
const LEVELX_STEP1_TRAIT = "CHEESE_TEMPLE_LEVELX_STEP1";

// 4. Core Functions
function buildLevelX() { /* Build level geometry */ }
function warpToLevelX() { /* Warp player to level */ }
function updateLevelX(delta) { /* Update level logic */ }
function restartLevelX() { /* Reset level state */ }

// 5. Riddle Functions
function updateLevelXStep0() { /* Step 0 logic */ }
function unlockLevelXTrait(traitKey, description) { /* Unlock trait */ }
async function awardLevelXDspoincReward(stepId, baseReward, contextLabel) { /* Award DSPOINC */ }
```

---

## 🧩 **RIDDLE SYSTEM ARCHITECTURE**

### **Riddle Naming Convention**

**Pattern:** `CHEESE_TEMPLE_[LEVEL]_[STEP]` or `CHEESE_TEMPLE_RIDDLE_##`

**Examples:**
- Level 1: `CHEESE_TEMPLE_RIDDLE_01`, `CHEESE_TEMPLE_RIDDLE_02`, `CHEESE_TEMPLE_RIDDLE_03`, `CHEESE_TEMPLE_RIDDLE_04_SECRET`
- Level 2+: `CHEESE_TEMPLE_LEVEL2_STEP0`, `CHEESE_TEMPLE_LEVEL2_STEP1`, `CHEESE_TEMPLE_LEVEL2_STEP2`
- Level 3+: `CHEESE_TEMPLE_LEVEL3_STEP0`, `CHEESE_TEMPLE_LEVEL3_STEP1`, `CHEESE_TEMPLE_LEVEL3_STEP2`
- Level 4+: `CHEESE_TEMPLE_LEVEL4_STEP0`, `CHEESE_TEMPLE_LEVEL4_STEP1`, `CHEESE_TEMPLE_LEVEL4_STEP2`, `CHEESE_TEMPLE_LEVEL4_STEP3`
- Level 5+: `CHEESE_TEMPLE_LEVEL5_STEP0`, `CHEESE_TEMPLE_LEVEL5_STEP1`, `CHEESE_TEMPLE_LEVEL5_WAVE1` through `CHEESE_TEMPLE_LEVEL5_WAVE10`

### **Riddle ID Structure**

**For API Calls:**
- **Level 1:** `CHEESE_TEMPLE_RIDDLE_01`, `CHEESE_TEMPLE_RIDDLE_02`, `CHEESE_TEMPLE_RIDDLE_03`, `CHEESE_TEMPLE_RIDDLE_04_SECRET`
- **Level 2:** `CHEESE_TEMPLE_LEVEL2_STEP0`, `CHEESE_TEMPLE_LEVEL2_STEP1`, `CHEESE_TEMPLE_LEVEL2_STEP2`
- **Level 3:** `CHEESE_TEMPLE_LEVEL3_STEP0`, `CHEESE_TEMPLE_LEVEL3_MONSTER_1` through `CHEESE_TEMPLE_LEVEL3_MONSTER_10`
- **Level 4:** `CHEESE_TEMPLE_LEVEL4_STEP0`, `CHEESE_TEMPLE_LEVEL4_CHEESE_1` through `CHEESE_TEMPLE_LEVEL4_CHEESE_50`, `CHEESE_TEMPLE_LEVEL4_STEP3`
- **Level 5:** `CHEESE_TEMPLE_LEVEL5_STEP0`, `CHEESE_TEMPLE_LEVEL5_WAVE1` through `CHEESE_TEMPLE_LEVEL5_WAVE10`

### **Standard Riddle Flow**

1. **Step 0 (Hidden Trigger):** Find and activate hidden cheese stone (10 seconds standing)
2. **Step 1+ (Main Challenge):** Complete main riddle objective(s)
3. **Step N (Portal):** Portal activation after all steps complete
4. **Completion Screen:** Show completion options (restart, next level, return to other levels)

### **Trait Unlocking Pattern**

```javascript
async function unlockLevelXTrait(traitKey, description) {
  const response = await fetch(`${API_BASE_URL}/api/user/traits.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: localStorage.getItem("discord_id"),
      trait_key: traitKey,
      trait_value: "1"
    })
  });
  // Handle response
}
```

**Function Location:** Each level has its own `unlockLevelXTrait()` function in `three.js/main.js`

### **Reward Awarding Pattern**

```javascript
async function awardLevelXDspoincReward(stepId, baseReward, contextLabel = "") {
  const response = await fetch(RIDDLE_REWARD_ENDPOINT, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      discord_id: localStorage.getItem("discord_id"),
      discord_name: localStorage.getItem("discord_name") || "Player",
      riddle_id: stepId,
      level_id: `CHEESE_TEMPLE_LEVELX`,
      base_reward: baseReward,
      session_id: generateSessionId()
    })
  });
  // Handle response
}
```

**Function Location:** Each level has its own `awardLevelXDspoincReward()` function in `three.js/main.js`

---

## 🏷️ **TRAIT SYSTEM & NAMING CONVENTIONS**

### **Trait Naming Pattern**

**Format:** `CHEESE_TEMPLE_[LEVEL]_[STEP]` or `CHEESE_TEMPLE_RIDDLE_##`

**Level 1 Traits:**
- `CHEESE_TEMPLE_RIDDLE_SOLVED` (Riddle #1)
- `CHEESE_TEMPLE_RIDDLE_02_SOLVED` (Riddle #2)
- `CHEESE_TEMPLE_RIDDLE_03_SOLVED` (Riddle #3)
- No trait for Riddle #4 (secret riddle)

**Level 2 Traits:**
- `CHEESE_TEMPLE_LEVEL2_STEP0`
- `CHEESE_TEMPLE_LEVEL2_STEP1`
- `CHEESE_TEMPLE_LEVEL2_STEP2`

**Level 3 Traits:**
- `CHEESE_TEMPLE_LEVEL3_STEP0`
- `CHEESE_TEMPLE_LEVEL3_STEP1`
- `CHEESE_TEMPLE_LEVEL3_STEP2`

**Level 4 Traits:**
- `CHEESE_TEMPLE_LEVEL4_STEP0`
- `CHEESE_TEMPLE_LEVEL4_STEP1`
- `CHEESE_TEMPLE_LEVEL4_STEP2`
- `CHEESE_TEMPLE_LEVEL4_STEP3`

**Level 5 Traits:**
- `CHEESE_TEMPLE_LEVEL5_STEP0`
- `CHEESE_TEMPLE_LEVEL5_STEP1`

### **Trait Storage**

**Database Table:** `tbl_user_traits`
- **Column:** `trait_name` (stores trait key)
- **Column:** `trait_value` (stores "1" for unlocked)
- **Column:** `user_id` (Discord ID)
- **Unique Constraint:** `(user_id, trait_name)`

**API Endpoint:** `/api/user/traits.php`
- **Method:** POST
- **Payload:** `{ "user_id": "...", "trait_key": "...", "trait_value": "1" }`

---

## 💰 **REWARD SYSTEM & CALCULATIONS**

### **Reward Calculation Flow**

1. **Base Reward Defined:** In level constants (e.g., `LEVEL5_STEP1_WAVE_REWARD_DSPOINC = 250`)
2. **API Call:** Frontend calls `/api/dev/riddle-reward.php` with `base_reward`
3. **Role Multiplier Applied (Server-Side):** 
   - VIP Holder: 2.0x
   - Holder: 1.5x
   - Champion: 1.4x
   - Season Tester/WL: 1.3x
   - Early Bird: 1.2x
   - Cheese Hunter: 1.1x
   - Default: 1.0x
4. **Total Reward:** `base_reward × multiplier = total_reward`
5. **Database Records:** Three tables updated automatically:
   - `tbl_riddle_completions` - Completion record
   - `tbl_user_scores` - DSPOINC balance
   - `tbl_score_adjustments` - Audit trail

### **Reward Examples by Level**

#### **Level 1 Rewards:**
- Riddle #1: 500 base (VIP: 1,000)
- Riddle #2: 500 base (VIP: 1,000)
- Riddle #3: 750 base (VIP: 1,500)
- Riddle #4 (Secret): 1,000 fixed (no multiplier)

#### **Level 2 Rewards:**
- Step 0: 100 base (VIP: 200)
- Step 1: 100 base (VIP: 200)
- Step 2: 120 base (VIP: 240)

#### **Level 3 Rewards:**
- Step 0: 100 base (VIP: 200)
- Each Monster: 50 base (VIP: 100) × 10 monsters = 500 base (VIP: 1,000)

#### **Level 4 Rewards:**
- Step 0: 100 base (VIP: 200)
- Each Cheese: 50 base (VIP: 100) × 50 cheeses = 2,500 base (VIP: 5,000)
- Each Monster: 50 base (VIP: 100) × 30 monsters = 1,500 base (VIP: 3,000)
- Step 3: 200 base (VIP: 400)

#### **Level 5 Rewards:**
- Step 0: Currently no reward (can be added)
- Each Wave: 250 base (VIP: 500) × 10 waves = 2,500 base (VIP: 5,000)

### **API Endpoint for Rewards**

**URL:** `/api/dev/riddle-reward.php`  
**Method:** POST  
**Payload:**
```json
{
  "discord_id": "user_discord_id",
  "discord_name": "username",
  "riddle_id": "CHEESE_TEMPLE_LEVEL5_WAVE1",
  "level_id": "CHEESE_TEMPLE_LEVEL5",
  "base_reward": 250,
  "session_id": "session_id"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_reward": 500,
    "multiplier": 2.0,
    "multiplier_source": "🎴 VIP Holder"
  }
}
```

**Rule Document:** `12.0/RULES/15_RIDDLE_REWARD_DATABASE_RULE.md`

---

## 🔗 **API ENDPOINTS REFERENCE**

### **Riddle & Trait APIs**

#### **1. Riddle Reward API**
- **URL:** `/api/dev/riddle-reward.php`
- **Method:** POST
- **Purpose:** Award DSPOINC for riddle/wave completion
- **Database Updates:** `tbl_riddle_completions`, `tbl_user_scores`, `tbl_score_adjustments`
- **Role Multiplier:** Applied server-side automatically

#### **2. Trait API**
- **URL:** `/api/user/traits.php`
- **Method:** POST (save) / GET (fetch)
- **Purpose:** Save/fetch user trait unlocks
- **Database Table:** `tbl_user_traits`

### **Game Score APIs**

#### **3. Save Score API**
- **URL:** `/api/dev/save-score.php`
- **Method:** POST
- **Purpose:** Save game scores (Tetris, Snake, Space Invaders)
- **Database Table:** `tbl_tetris_scores`

#### **4. User Stats API**
- **URL:** `/api/user/user-game-missions.php`
- **Method:** POST
- **Purpose:** Fetch all game stats and mission status
- **Returns:** All 5 games data with season info

### **Achievement APIs**

#### **5. Tetris Achievements API**
- **URL:** `/api/user/get-tetris-achievements.php`
- **Method:** POST
- **Purpose:** Fetch Tetris achievements
- **Database Table:** `tbl_tetris_achievements`

#### **6. Snake Achievements API**
- **URL:** `/api/user/get-snake-achievements.php`
- **Method:** POST
- **Purpose:** Fetch Snake achievements
- **Database Table:** `tbl_snake_achievements`

#### **7. Space Invaders Achievements API**
- **URL:** `/api/user/get-space-invaders-achievements.php`
- **Method:** POST
- **Purpose:** Fetch Space Invaders achievements
- **Database Table:** `tbl_space_invaders_achievements`

#### **8. 3D Puzzles Achievements API**
- **URL:** `/api/user/get-3d-puzzles-achievements.php`
- **Method:** POST
- **Purpose:** Fetch 3D riddle achievements
- **Database Table:** `tbl_user_traits` (filters for `CHEESE_TEMPLE_*` traits)

---

## 💾 **DATABASE TABLES & SCHEMA**

### **Core Riddle & Reward Tables**

#### **1. `tbl_user_traits`**
**Purpose:** Stores user trait unlocks

**Schema:**
```sql
CREATE TABLE tbl_user_traits (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id TEXT NOT NULL,
  trait_name TEXT NOT NULL,
  trait_value TEXT NOT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(user_id, trait_name)
);
```

**Columns:**
- `user_id` - Discord ID
- `trait_name` - Trait key (e.g., `CHEESE_TEMPLE_LEVEL5_STEP1`)
- `trait_value` - Usually "1" for unlocked
- `updated_at` - Timestamp

#### **2. `tbl_riddle_completions`**
**Purpose:** Tracks riddle/wave completions with rewards

**Schema:**
```sql
CREATE TABLE tbl_riddle_completions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  discord_id TEXT NOT NULL,
  discord_name TEXT,
  riddle_id TEXT NOT NULL,
  level_id TEXT NOT NULL,
  base_reward INTEGER NOT NULL,
  multiplier REAL NOT NULL,
  total_reward INTEGER NOT NULL,
  completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  session_id TEXT,
  metadata TEXT,
  UNIQUE(discord_id, riddle_id)
);
```

**Key Columns:**
- `discord_id` - Discord ID
- `riddle_id` - Unique riddle identifier
- `base_reward` - Base DSPOINC before multiplier
- `multiplier` - Role multiplier applied
- `total_reward` - Final reward (base × multiplier)

#### **3. `tbl_user_scores`**
**Purpose:** Stores DSPOINC balance

**Schema:**
```sql
CREATE TABLE tbl_user_scores (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id TEXT NOT NULL,
  game TEXT NOT NULL,
  score INTEGER NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  source TEXT DEFAULT 'legacy',
  game_type TEXT,
  season TEXT DEFAULT 'season_2',
  created_at DATETIME
);
```

**Key Columns:**
- `user_id` - Discord ID
- `game` - Game identifier (e.g., `'cheese_temple_riddles'`)
- `score` - DSPOINC amount (after multiplier)
- `source` - Source (e.g., `'riddle_completion'`)

#### **4. `tbl_score_adjustments`**
**Purpose:** Audit trail for all DSPOINC awards

**Schema:**
```sql
CREATE TABLE tbl_score_adjustments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id TEXT NOT NULL,
  admin_id TEXT NOT NULL,
  amount INTEGER NOT NULL,
  action TEXT NOT NULL,
  reason TEXT,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Key Columns:**
- `user_id` - Discord ID
- `admin_id` - System identifier (e.g., `'system-riddle-reward'`)
- `amount` - DSPOINC amount
- `reason` - Formatted description (e.g., `"Riddle completion (CHEESE_TEMPLE_LEVEL5_WAVE1): base 250 × 2.00 = 500 DSPOINC"`)

### **Game Score Tables**

#### **5. `tbl_tetris_scores`**
**Purpose:** Stores scores for Tetris, Snake, and Space Invaders

**Key Columns:**
- `discord_id` - Discord ID (NOT `user_id`)
- `game` - `'tetris'`, `'snake'`, or `'space_invaders'`
- `score` - Game score

**CRITICAL:** Uses `discord_id` for queries (NOT `user_id`)

#### **6. `tbl_cheese_clicks`**
**Purpose:** Stores Cheese Hunt game data

**Key Columns:**
- `user_wallet` - Discord ID (NOT `discord_id` or `user_id`)
- `clicks` - Click count

**CRITICAL:** Uses `user_wallet` for queries

#### **7. `tbl_race_participants`**
**Purpose:** Stores Discord Race participation

**Key Columns:**
- `user_id` - Discord ID
- `position` - Race position (NOT `final_position`)

**CRITICAL:** Uses `user_id` and `position` (NOT `final_position`)

### **Achievement Tables**

#### **8. `tbl_tetris_achievements`**
**Purpose:** Tetris achievements (25 total)

**Key Columns:**
- `user_id` - Discord ID
- `achievement_key` - Achievement identifier
- `unlocked_at` - Timestamp

#### **9. `tbl_snake_achievements`**
**Purpose:** Snake achievements (20 total)

**Key Columns:**
- `user_id` - Discord ID
- `achievement_key` - Achievement identifier
- `unlocked_at` - Timestamp

#### **10. `tbl_space_invaders_achievements`**
**Purpose:** Space Invaders achievements (28 total)

**Key Columns:**
- `user_id` - Discord ID
- `achievement_key` - Achievement identifier
- `unlocked_at` - Timestamp

**Rule Document:** `12.0/RULES/15_RIDDLE_REWARD_DATABASE_RULE.md`  
**Master Ruleset:** See "Perfect 5-Game Score Retrieval System V2.0" section

---

## 🎮 **GAME SYSTEMS**

### **The 5 Games**

#### **1. Tetris**
- **Table:** `tbl_tetris_scores`
- **Field:** `discord_id` (NOT `user_id`)
- **Game Identifier:** `game = 'tetris'`
- **API:** Uses `save-score.php`

#### **2. Snake**
- **Table:** `tbl_tetris_scores` (SAME as Tetris)
- **Field:** `discord_id` (NOT `user_id`)
- **Game Identifier:** `game = 'snake'`
- **API:** Uses `save-score.php`

#### **3. Space Invaders**
- **Table:** `tbl_tetris_scores` (SAME as Tetris)
- **Field:** `discord_id` (NOT `user_id`)
- **Game Identifier:** `game = 'space_invaders'`
- **API:** Uses `save-score.php`

#### **4. Cheese Hunt**
- **Table:** `tbl_cheese_clicks`
- **Field:** `user_wallet` (NOT `discord_id` or `user_id`)
- **API:** Uses `cheese-hunt-capture.php`

#### **5. Discord Race**
- **Table:** `tbl_race_participants`
- **Field:** `user_id` (Discord ID)
- **Field:** `position` (NOT `final_position`)

**CRITICAL FIELD MAPPINGS:**
- ✅ Tetris, Snake, Space Invaders: Use `discord_id` in `tbl_tetris_scores`
- ✅ Cheese Hunt: Use `user_wallet` in `tbl_cheese_clicks`
- ✅ Discord Race: Use `user_id` in `tbl_race_participants`
- ✅ All Achievements: Use `user_id` in their respective achievement tables

**Rule Document:** Master Ruleset - "Perfect 5-Game Score Retrieval System V2.0"

---

## 📝 **CODE PATTERNS & STANDARDS**

### **Function Naming Conventions**

**Level Functions:**
- `buildLevelX()` - Build level geometry
- `warpToLevelX()` - Warp player to level
- `updateLevelX(delta)` - Update level logic
- `restartLevelX()` - Reset level state

**Riddle Functions:**
- `updateLevelXStepY()` - Update specific step logic
- `unlockLevelXTrait(traitKey, description)` - Unlock trait
- `awardLevelXDspoincReward(stepId, baseReward, contextLabel)` - Award DSPOINC

**Monster/Entity Functions:**
- `spawnLevelXMonster(path, position)` - Spawn monster
- `updateLevelXMonsters(delta)` - Update monster logic
- `defeatLevelXMonster(index)` - Handle monster defeat

### **State Object Pattern**

Every level uses this pattern:

```javascript
const levelXState = {
  built: false,
  group: new THREE.Group(),
  // Level-specific state arrays
  monsters: [],
  cheeses: [],
  portals: []
};

const levelXRiddleState = {
  step0Complete: false,
  step1Active: false,
  // Step-specific state
  currentWave: 1,
  totalMonsters: 0
};
```

### **Configuration Pattern**

Every level has a config object:

```javascript
const levelXConfig = {
  size: 160, // Arena size
  origin: new THREE.Vector3(0, 0, z), // Level origin
  wallHeight: 30, // Wall height
  spawnPosition: new THREE.Vector3(0, 1, z - 50), // Player spawn
  portalPosition: new THREE.Vector3(0, 5, z + 60) // Portal location
};
```

### **Constant Naming Pattern**

**Traits:**
- `LEVELX_STEPY_TRAIT = "CHEESE_TEMPLE_LEVELX_STEPY"`

**Rewards:**
- `LEVELX_STEPY_REWARD_DSPOINC = 100`

**Timers:**
- `LEVELX_STEPY_TIMER_DURATION = 600`

**Counts:**
- `LEVELX_STEPY_MONSTER_COUNT = 50`

---

## 📁 **FILE STRUCTURE & ORGANIZATION**

### **Main Game File**
- `three.js/main.js` - Complete game logic (22,391+ lines)

### **Documentation Structure**
```
12.0/TECHNICAL_DOCUMENTATION/
├── 3d_riddles/
│   ├── README.md (index of all riddles)
│   ├── RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md
│   ├── RIDDLE_01_THE_SPAWN_LEVEL_2.md
│   ├── RIDDLE_01_THE_HUNT_LEVEL_3.md
│   ├── RIDDLE_01_THE_FIRST_SHOT_LEVEL_4.md
│   └── RIDDLE_01_THE_WALK_LEVEL_5.md
└── MASTER_DEVELOPMENT_REFERENCE.md (this file)
```

### **API Structure**
```
api/
├── dev/
│   ├── riddle-reward.php (MANDATORY for all riddle rewards)
│   ├── save-score.php (Tetris, Snake, Space Invaders)
│   └── cheese-hunt-capture.php (Cheese Hunt)
└── user/
    ├── traits.php (MANDATORY for all trait unlocks)
    ├── get-3d-puzzles-achievements.php
    ├── get-tetris-achievements.php
    ├── get-snake-achievements.php
    ├── get-space-invaders-achievements.php
    └── user-game-missions.php (comprehensive game stats)
```

### **Rules Structure**
```
12.0/RULES/
├── 00_RULES_INDEX.md (index of all rules)
├── 01_MASTER_RULESET.md (single source of truth)
├── 12_UNIVERSAL_LEVEL_REQUIREMENTS_RULE.md
├── 13_3D_GAME_DSPOINC_SYNC_RULE.md
├── 14_GLTF_SKELETON_CLONING_RULE.md
└── 15_RIDDLE_REWARD_DATABASE_RULE.md
```

---

## 🚨 **CRITICAL RULES FOR FUTURE DEVELOPMENT**

### **1. Always Use Standard Patterns**
- ✅ Use existing trait/reward functions as templates
- ✅ Follow naming conventions exactly
- ✅ Use correct database tables and fields
- ✅ Use correct API endpoints

### **2. Always Update Documentation**
- ✅ Update level riddle documentation file
- ✅ Update this master reference
- ✅ Update rules if new patterns emerge

### **3. Always Test Database Integration**
- ✅ Verify traits save correctly
- ✅ Verify rewards calculate correctly
- ✅ Verify role multipliers work
- ✅ Verify all 3 database tables update

### **4. Always Follow Field Mappings**
- ✅ Games use correct field names (discord_id vs user_wallet vs user_id)
- ✅ Achievements use `user_id`
- ✅ Scores use correct tables

### **5. Always Preserve Working Code**
- ✅ Never delete working code
- ✅ Only add new features
- ✅ Document all changes

**Rule Document:** `12.0/RULES/08_CRITICAL_CODE_PRESERVATION_RULE.md`

---

## 🎯 **QUICK REFERENCE CHECKLIST**

### **When Creating a New Level:**
- [ ] Create state objects (`levelXState`, `levelXRiddleState`)
- [ ] Create config object (`levelXConfig`)
- [ ] Define trait constants (`LEVELX_STEPY_TRAIT`)
- [ ] Define reward constants (`LEVELX_STEPY_REWARD_DSPOINC`)
- [ ] Implement `buildLevelX()` function
- [ ] Implement `warpToLevelX()` function
- [ ] Implement `updateLevelX(delta)` function
- [ ] Implement `restartLevelX()` function
- [ ] Implement `unlockLevelXTrait()` function
- [ ] Implement `awardLevelXDspoincReward()` function
- [ ] Create riddle documentation file
- [ ] Update `README.md` in `3d_riddles/` folder

### **When Creating a New Riddle Step:**
- [ ] Define trait constant
- [ ] Define reward constant
- [ ] Implement step update function
- [ ] Call `unlockLevelXTrait()` on completion
- [ ] Call `awardLevelXDspoincReward()` on completion
- [ ] Test trait unlock
- [ ] Test reward calculation
- [ ] Test role multiplier
- [ ] Update level documentation

### **When Creating a New Game:**
- [ ] Determine correct database table
- [ ] Determine correct field name (discord_id vs user_wallet vs user_id)
- [ ] Create API endpoint if needed
- [ ] Update user-game-missions.php if needed
- [ ] Document field mappings
- [ ] Update master ruleset

---

## 📚 **RELATED DOCUMENTATION**

- **3D Riddles Index:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/README.md`
- **Master Ruleset:** `12.0/RULES/01_MASTER_RULESET.md`
- **Riddle Reward Rule:** `12.0/RULES/15_RIDDLE_REWARD_DATABASE_RULE.md`
- **Level Requirements Rule:** `12.0/RULES/12_UNIVERSAL_LEVEL_REQUIREMENTS_RULE.md`
- **DSPOINC Sync Rule:** `12.0/RULES/13_3D_GAME_DSPOINC_SYNC_RULE.md`

---

**Document Version:** 1.0  
**Last Updated:** November 26, 2025  
**Status:** ✅ **ACTIVE - SINGLE SOURCE OF TRUTH**  
**Maintained By:** Narrrf's Lab Tech Council  

**🧀 This document ensures decades of consistent, synchronized development! 🧀**

