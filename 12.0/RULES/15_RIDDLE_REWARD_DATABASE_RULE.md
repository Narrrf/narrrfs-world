# 🎯 CRITICAL RIDDLE REWARD & DATABASE RULE - LEVEL 5 SYSTEM

**STATUS:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**  
**CREATED:** November 26, 2025  
**PURPOSE:** Document database tables and reward system for riddle completions  
**PRIORITY:** 🚨 **CRITICAL - PRODUCTION SYSTEM**  

---

## 🎯 **RULE OVERVIEW**

### **CORE PRINCIPLE:**
**All riddle rewards and traits MUST use the correct database tables and API endpoints as documented. Never hardcode rewards or bypass the reward API system.**

### **RULE SCOPE:**
- **Database Tables** - Trait storage, reward tracking, DSPOINC balance
- **API Endpoints** - Reward API, Trait API
- **Reward Calculation** - Base reward × role multiplier = total reward
- **Trait Unlocking** - Trait storage and verification
- **Level 5 Riddle System** - Monster hunt rewards and traits

---

## 💾 **DATABASE TABLES FOR RIDDLE REWARDS**

### **1. Trait Storage: `tbl_user_traits`**

**Purpose:** Stores user trait unlocks for riddle completions

**Schema:**
```sql
CREATE TABLE tbl_user_traits (
  user_id TEXT NOT NULL,
  trait TEXT NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, trait)
);
```

**Columns:**
- `user_id` - Discord ID of the user
- `trait` - Trait key (e.g., `CHEESE_TEMPLE_LEVEL5_STEP1`)
- `timestamp` - When the trait was unlocked

**Level 5 Traits:**
- **Trait Key:** `CHEESE_TEMPLE_LEVEL5_STEP1`
- **Description:** "Level 5 Step 1 - All Waves Complete"
- **Unlocked:** When player completes all 10 waves of Step 1

**Query Examples:**
```sql
-- Check if user has Level 5 Step 1 trait
SELECT * FROM tbl_user_traits 
WHERE user_id = ? AND trait = 'CHEESE_TEMPLE_LEVEL5_STEP1';

-- Get all traits for a user
SELECT * FROM tbl_user_traits WHERE user_id = ? ORDER BY timestamp DESC;
```

**API Endpoint:** `/api/user/traits.php` (POST method)

---

### **2. Reward Completion Tracking: `tbl_riddle_completions`**

**Purpose:** Records each riddle/wave completion with base reward, multiplier, and total reward

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

**Columns:**
- `discord_id` - Discord ID of the user
- `discord_name` - Username (optional)
- `riddle_id` - Unique riddle identifier (e.g., `CHEESE_TEMPLE_LEVEL5_WAVE1`)
- `level_id` - Level identifier (e.g., `CHEESE_TEMPLE_LEVEL5`)
- `base_reward` - Base DSPOINC reward before multiplier
- `multiplier` - Role multiplier applied (e.g., 2.0 for VIP Holder)
- `total_reward` - Final reward amount (base_reward × multiplier)
- `completed_at` - Completion timestamp
- `session_id` - Game session ID (optional)
- `metadata` - JSON metadata (multiplier_source, etc.)

**Level 5 Wave Riddle IDs:**
- `CHEESE_TEMPLE_LEVEL5_WAVE1` through `CHEESE_TEMPLE_LEVEL5_WAVE10`
- Each wave: `base_reward: 250`, `multiplier: 2.0` (if VIP Holder), `total_reward: 500`
- Total for all 10 waves: **5,000 DSPOINC** (with 2x multiplier)

**Query Examples:**
```sql
-- Check all Level 5 wave completions for a user
SELECT riddle_id, base_reward, multiplier, total_reward, completed_at 
FROM tbl_riddle_completions 
WHERE discord_id = ? AND riddle_id LIKE 'CHEESE_TEMPLE_LEVEL5_WAVE%'
ORDER BY completed_at DESC;

-- Get total DSPOINC from Level 5 waves
SELECT COUNT(*) as wave_count, SUM(total_reward) as total_dspoinc 
FROM tbl_riddle_completions 
WHERE discord_id = ? AND riddle_id LIKE 'CHEESE_TEMPLE_LEVEL5_WAVE%';

-- Check if specific wave already completed (prevent duplicates)
SELECT * FROM tbl_riddle_completions 
WHERE discord_id = ? AND riddle_id = 'CHEESE_TEMPLE_LEVEL5_WAVE1';
```

**Duplicate Prevention:**
- `UNIQUE(discord_id, riddle_id)` constraint prevents duplicate rewards
- API returns 409 Conflict if already completed

**API Endpoint:** `/api/dev/riddle-reward.php` (POST method)

---

### **3. DSPOINC Balance: `tbl_user_scores`**

**Purpose:** Stores actual DSPOINC points added to user balance

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

**Columns:**
- `user_id` - Discord ID of the user
- `game` - Game identifier (e.g., `'cheese_temple_riddles'`)
- `score` - DSPOINC amount (total reward after multiplier)
- `timestamp` - When the score was added
- `source` - Source of score (e.g., `'riddle_completion'`)
- `game_type` - Game type (optional)
- `season` - Season identifier (e.g., `'Season 5'`)
- `created_at` - Creation timestamp

**Level 5 Score Records:**
- **game:** `'cheese_temple_riddles'`
- **source:** `'riddle_completion'`
- **score:** Total reward amount (e.g., 500 per wave with multiplier)
- Each wave completion adds a separate record with the multiplied reward

**Query Examples:**
```sql
-- Get total DSPOINC from Level 5 rewards
SELECT SUM(score) as total_dspoinc 
FROM tbl_user_scores 
WHERE user_id = ? 
  AND source = 'riddle_completion' 
  AND game = 'cheese_temple_riddles';

-- Get all riddle completion scores for a user
SELECT * FROM tbl_user_scores 
WHERE user_id = ? 
  AND source = 'riddle_completion' 
ORDER BY timestamp DESC;
```

**API Endpoint:** `/api/dev/riddle-reward.php` (automatically adds to this table)

---

### **4. Score Adjustment History: `tbl_score_adjustments`**

**Purpose:** Audit trail for all DSPOINC awards (admin tracking)

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

**Columns:**
- `user_id` - Discord ID of the user
- `admin_id` - Admin/system identifier (e.g., `'system-riddle-reward'`)
- `amount` - DSPOINC amount
- `action` - Action type (e.g., `'add'` for rewards)
- `reason` - Description of the adjustment
- `timestamp` - When the adjustment was made

**Level 5 Adjustment Records:**
- **admin_id:** `'system-riddle-reward'`
- **action:** `'add'`
- **amount:** Total reward (e.g., 500 per wave)
- **reason:** `'Riddle completion (CHEESE_TEMPLE_LEVEL5_WAVE1): base 250 × 2.00 = 500 DSPOINC'`

**Query Examples:**
```sql
-- Get all Level 5 reward adjustments for a user
SELECT * FROM tbl_score_adjustments 
WHERE user_id = ? 
  AND admin_id = 'system-riddle-reward'
  AND reason LIKE '%LEVEL5%'
ORDER BY timestamp DESC;
```

**API Endpoint:** `/api/dev/riddle-reward.php` (automatically logs to this table)

---

## 🔄 **REWARD CALCULATION FLOW**

### **Step-by-Step Process:**

1. **Base Reward Defined:**
   - Level 5: `LEVEL5_STEP1_WAVE_REWARD_DSPOINC = 250` (per wave)
   - Defined in `three.js/main.js`

2. **API Call:**
   - Frontend calls `/api/dev/riddle-reward.php` with `base_reward: 250`
   - Payload includes `discord_id`, `riddle_id`, `level_id`, etc.

3. **Role Multiplier Applied (Server-Side):**
   - API fetches user roles from `tbl_user_roles`
   - Applies highest priority multiplier:
     - VIP Holder: 2.0x
     - Holder: 1.5x
     - Champion: 1.4x
     - Season Tester/WL: 1.3x
     - Early Bird: 1.2x
     - Cheese Hunter: 1.1x
     - Default: 1.0x

4. **Total Reward Calculated:**
   - `total_reward = base_reward × multiplier`
   - Example: `250 × 2.0 = 500 DSPOINC`

5. **Database Records Created:**
   - `tbl_riddle_completions` - Completion record with all details
   - `tbl_user_scores` - DSPOINC added to balance
   - `tbl_score_adjustments` - Audit trail entry

6. **Response Sent:**
   - Returns `success`, `total_reward`, `multiplier`, `multiplier_source`

### **Example Calculation:**

**Level 5 Wave 1 Completion:**
- Base Reward: 250 DSPOINC
- User Role: VIP Holder (2.0x multiplier)
- Total Reward: 250 × 2.0 = **500 DSPOINC**

**Level 5 All 10 Waves:**
- Base Total: 250 × 10 = 2,500 DSPOINC
- With 2x Multiplier: 500 × 10 = **5,000 DSPOINC**

---

## 🚨 **CRITICAL NOTES**

### **⚠️ Display vs Actual Reward:**
- **Game UI displays base reward** (e.g., "2500 DSPOINC" = 250 × 10 waves)
- **API applies role multipliers server-side**
- **Actual DSPOINC awarded** = base reward × multiplier (e.g., 5000 DSPOINC with 2x multiplier)

### **✅ Duplicate Prevention:**
- `tbl_riddle_completions` has `UNIQUE(discord_id, riddle_id)` constraint
- Prevents duplicate rewards if player completes same wave multiple times
- API returns 409 Conflict if already completed
- Frontend should handle 409 responses gracefully

### **✅ Trait Unlocking:**
- Traits are unlocked via `/api/user/traits.php` (POST method)
- Trait stored in `tbl_user_traits` with timestamp
- Can be queried to check completion status
- Traits should be unlocked once when condition is met (not per wave)

### **✅ Reward Verification:**
- Always check `tbl_riddle_completions` for completion records
- Always check `tbl_user_scores` for actual DSPOINC balance
- Always check `tbl_user_traits` for trait unlocks
- Use these tables to verify rewards were correctly awarded

---

## 📋 **API ENDPOINTS**

### **Reward API: `/api/dev/riddle-reward.php`**

**Method:** POST  
**Purpose:** Award DSPOINC for riddle/wave completion

**Request Payload:**
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

**Success Response:**
```json
{
  "success": true,
  "total_reward": 500,
  "multiplier": 2.0,
  "multiplier_source": "🎴 VIP Holder",
  "message": "Reward awarded successfully"
}
```

**Error Response (409 Conflict - Already Completed):**
```json
{
  "success": false,
  "error": "Riddle already completed",
  "already_completed": true,
  "previous_reward": 500
}
```

---

### **Trait API: `/api/user/traits.php`**

**Method:** POST  
**Purpose:** Save user trait unlock

**Request Payload:**
```json
{
  "user_id": "user_discord_id",
  "trait_key": "CHEESE_TEMPLE_LEVEL5_STEP1",
  "trait_value": "1"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Trait saved successfully"
}
```

---

## 🧀 **FINAL MANDATE**

### **THIS RULE IS NON-NEGOTIABLE:**
- **Every riddle reward** MUST use `/api/dev/riddle-reward.php`
- **Every trait unlock** MUST use `/api/user/traits.php`
- **Never hardcode rewards** - always use API system
- **Always verify** rewards in database tables
- **Complete compliance** required for all team members

### **THE ULTIMATE GOAL:**
**Ensure every riddle completion is properly tracked, rewarded, and verified through the database system for accurate DSPOINC balances and trait unlocks.**

---

**RULE CREATED:** November 26, 2025  
**STATUS:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**  
**PURPOSE:** Database & Reward System Documentation  
**SCOPE:** All riddle rewards, all trait unlocks, all DSPOINC awards  

**🎯 THIS RULE ENSURES ACCURATE REWARD TRACKING AND DATABASE INTEGRITY! 🎯**
