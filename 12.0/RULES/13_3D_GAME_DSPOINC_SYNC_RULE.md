# 🎯 13_3D_GAME_DSPOINC_SYNC_RULE.md

**Rule Name:** 3D Game DSPOINC Rewards and Trait Synchronization
**Description:** This rule standardizes how DSPOINC rewards and traits from the 3D game (Cheese Temple riddles) are synchronized to the database, player profiles, and Discord integration.
**Last Updated:** November 19, 2025
**Status:** ✅ **PRODUCTION VERIFIED** - All systems tested and working correctly

---

## ✅ **PRODUCTION VERIFICATION (November 19, 2025)**

### **Testing Results:**
- ✅ **Fresh Database Test:** All 3 Level 1 riddles tested with Narrrf (VIP Holder) account
- ✅ **Role Multipliers:** 2.0x multiplier correctly applied to all 3 riddles
- ✅ **Database Records:** All traits, score adjustments, and completions correctly stored
- ✅ **Frontend Display:** All rewards and achievements displaying correctly
- ✅ **Total Rewards:** 3,500 DSPOINC correctly awarded (matches VIP Holder expected total)

### **Verified Systems:**
- ✅ Role-based multiplier system working correctly
- ✅ DSPOINC rewards syncing to `tbl_score_adjustments`
- ✅ Traits unlocking correctly in `tbl_user_traits`
- ✅ Riddle completions tracking in `tbl_riddle_completions`
- ✅ User scores updating in `tbl_user_scores`
- ✅ Frontend "Recent Score Changes" displaying correctly
- ✅ Frontend "3D Puzzles Achievements" displaying correctly

---

## 📋 **OVERVIEW**

**CRITICAL RULE:** ALL future DSPOINC rewards and trait unlocks from the 3D game MUST follow this standardized pattern to ensure they appear correctly in:
- Player's total DSPOINC balance
- **"Recent Score Changes" section on the profile page** (MANDATORY)
- Discord integration (if applicable)
- Achievement tracking systems

**This rule applies to:**
- ✅ All existing levels (Level 1, 2, 3, 4)
- ✅ **ALL future levels** (Level 5, 6, 7...)
- ✅ **ALL future riddle steps** (any new steps added to existing levels)
- ✅ **ALL future reward types** (any new reward mechanisms)

---

## 🎯 **REQUIRED API ENDPOINTS**

### **1. Riddle Completion Rewards (Level 2, 3, 4 Steps)**

**Endpoint:** `/api/dev/riddle-reward.php`

**Used For:**
- Level 2: Step 0, Step 1, Step 2
- Level 3: Step 0, Step 1 (monster hunts), Step 2 (monster hunts), Step 3 (portal)
- Level 4: Step 0, Step 1 (cheese captures), Step 2 (portal)

**Payload Structure:**
```javascript
{
  discord_id: string,        // User's Discord ID
  discord_name: string|null,  // User's Discord username (optional)
  riddle_id: string,          // Trait key (e.g., "CHEESE_TEMPLE_LEVEL4_STEP0")
  level_id: string,           // Level identifier (e.g., "CHEESE_TEMPLE_LEVEL4")
  base_reward: number,        // Base DSPOINC amount (before multipliers)
  session_id: string|null      // Session identifier (optional)
}
```

**What It Does:**
1. Checks for duplicate completions (prevents double rewards)
2. Applies role-based multipliers (VIP Holder, Champion, etc.)
3. Inserts into `tbl_riddle_completions` (completion tracking)
4. Inserts into `tbl_user_scores` (DSPOINC balance)
5. **Inserts into `tbl_score_adjustments`** (Recent Score Changes) with descriptive `reason` field

**Response Structure:**
```javascript
{
  success: boolean,
  data: {
    discord_id: string,
    riddle_id: string,
    base_reward: number,
    multiplier: number,
    ds_poinc_awarded: number,    // Final amount after multipliers
    total_ds_poinc: number,       // User's new total balance
    total_riddle_completions: number,
    season: string
  }
}
```

---

## 📊 **DATABASE TABLES**

### **1. `tbl_riddle_completions`**
Tracks all riddle step completions.

**Required Fields:**
- `discord_id` (TEXT) - User's Discord ID
- `riddle_id` (TEXT) - Trait key (e.g., "CHEESE_TEMPLE_LEVEL4_STEP0")
- `level_id` (TEXT) - Level identifier
- `base_reward` (INTEGER) - Base DSPOINC amount
- `multiplier` (REAL) - Role multiplier applied
- `total_reward` (INTEGER) - Final DSPOINC amount
- `completed_at` (DATETIME) - Timestamp
- `UNIQUE(discord_id, riddle_id)` - Prevents duplicate rewards

### **2. `tbl_user_scores`**
Tracks DSPOINC balance per game/source.

**Required Fields:**
- `user_id` (TEXT) - User's Discord ID
- `score` (INTEGER) - DSPOINC amount
- `game` (TEXT) - **MUST BE:** `"cheese_temple_riddles"`
- `source` (TEXT) - **MUST BE:** `"riddle_completion"`
- `season` (TEXT) - Current season name

### **3. `tbl_score_adjustments`** ⚠️ **CRITICAL FOR RECENT SCORE CHANGES**
Tracks all DSPOINC changes for display in "Recent Score Changes".

**Required Fields:**
- `user_id` (TEXT) - User's Discord ID
- `admin_id` (TEXT) - **MUST BE:** `"system-riddle-reward"` for riddle rewards
- `amount` (INTEGER) - DSPOINC amount (positive for rewards)
- `action` (TEXT) - **MUST BE:** `"add"` for rewards
- `reason` (TEXT) - **MUST BE DESCRIPTIVE:** Format: `"Riddle completion (RIDDLE_ID): base X × Y.Z = W DSPOINC"`
- `timestamp` (DATETIME) - Auto-generated timestamp

**Example `reason` Values:**
- `"Riddle completion (CHEESE_TEMPLE_LEVEL4_STEP0): base 100 × 1.00 = 100 DSPOINC"`
- `"Riddle completion (CHEESE_TEMPLE_LEVEL3_STEP1): base 50 × 2.00 = 100 DSPOINC"` (VIP Holder multiplier)

---

## 🔧 **IMPLEMENTATION PATTERN**

### **JavaScript Function Template:**

```javascript
async function awardLevelXDspoincReward(stepId, baseReward, contextLabel = "") {
  if (!resolvedDiscordId) {
    console.warn(`🧀 [LEVEL X] Skipping DSPOINC reward (${stepId}) — no Discord ID.`);
    return;
  }
  try {
    const payload = {
      discord_id: resolvedDiscordId,
      discord_name: playerDisplayName && playerDisplayName !== "Guest" ? playerDisplayName : null,
      riddle_id: stepId,
      level_id: "CHEESE_TEMPLE_LEVELX",
      base_reward: baseReward,
      session_id: cheeseSessionId
    };
    const response = await fetch(RIDDLE_REWARD_ENDPOINT, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify(payload)
    });
    const result = await response.json().catch(() => ({ success: false, error: "Invalid JSON" }));
    if (response.ok && result.success) {
      const dsPoincAwarded = result.data?.ds_poinc_awarded || 0;
      const totalDspoinc = result.data?.total_ds_poinc;
      if (typeof totalDspoinc === "number") {
        currentTotalDspoinc = totalDspoinc;
        window.localStorage.setItem("narrrfs_last_ds_balance", String(currentTotalDspoinc));
        if (isGamePaused) updatePausePlayerInfo();
      }
      showRiddleRewardNotification(dsPoincAwarded, result.data?.multiplier || 1.0);
      console.log(`🧀 [LEVEL X] DSPOINC reward granted (${stepId})`, {
        baseReward,
        dsPoincAwarded,
        totalDspoinc,
        contextLabel
      });
    } else if (response.status === 409) {
      console.warn(`🧀 [LEVEL X] Reward already claimed for ${stepId}`);
      showRiddleRewardNotification(0, 1.0, true);
    } else {
      console.warn(`🧀 [LEVEL X] DSPOINC reward failed (${stepId}):`, result.error);
    }
  } catch (error) {
    console.error(`🧀 [LEVEL X] Error awarding DSPOINC reward (${stepId}):`, error);
  }
}
```

---

## 📋 **TRAIT UNLOCKING**

### **Trait Unlock Pattern:**

Traits should be unlocked **BEFORE** awarding DSPOINC rewards to ensure proper tracking.

**JavaScript Function Template:**

```javascript
async function unlockLevelXTrait(traitKey, description) {
  if (!resolvedDiscordId) {
    console.warn(`🧀 [LEVEL X] Skipping trait unlock (${traitKey}) — no Discord ID.`);
    return;
  }
  try {
    const response = await fetch(`${API_BASE_URL}/api/user/unlock-trait.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        user_id: resolvedDiscordId,
        trait: traitKey,
        description: description
      })
    });
    const result = await response.json();
    if (result.success) {
      console.log(`🧀 [LEVEL X] Trait unlocked: ${traitKey}`);
    } else {
      console.warn(`🧀 [LEVEL X] Trait unlock failed (${traitKey}):`, result.error);
    }
  } catch (error) {
    console.error(`🧀 [LEVEL X] Error unlocking trait (${traitKey}):`, error);
  }
}
```

**Usage:**
```javascript
// Step 0 completion
if (!levelXRiddleState.step0TraitUnlocked) {
  unlockLevelXTrait(LEVELX_STEP0_TRAIT, "Level X Step 0");
}
awardLevelXDspoincReward("CHEESE_TEMPLE_LEVELX_STEP0", 100, "Level X Step 0");
```

---

## 🎯 **STANDARD DSPOINC REWARDS**

### **Level 2: "The Spawn"**
- Step 0 (Platform): 100 DSPOINC
- Step 1 (Lever): 100 DSPOINC
- Step 2 (Inspection Zones): 120 DSPOINC

### **Level 3: "The Hunt"**
- Step 0 (Platform): 100 DSPOINC
- Step 1 (Monster Hunt - 5 monsters): 50 DSPOINC per monster (250 total)
- Step 2 (Monster Hunt - 5 monsters): 50 DSPOINC per monster (250 total)
- Step 3 (Portal): 200 DSPOINC

### **Level 4: "The First Shot"**
- Step 0 (Platform): 100 DSPOINC
- Step 1 (Cheese Captures): 50 DSPOINC per cheese (up to 50 cheeses = 2500 DSPOINC max)
- Step 2 (Portal): 200 DSPOINC

---

## 🔍 **VERIFICATION CHECKLIST**

When implementing a new level or riddle step, verify:

- [ ] Trait is unlocked **before** DSPOINC reward
- [ ] DSPOINC reward uses `RIDDLE_REWARD_ENDPOINT` (`/api/dev/riddle-reward.php`)
- [ ] Payload includes all required fields (`discord_id`, `riddle_id`, `level_id`, `base_reward`)
- [ ] Reward appears in `tbl_riddle_completions`
- [ ] Reward appears in `tbl_user_scores` with `game: "cheese_temple_riddles"` and `source: "riddle_completion"`
- [ ] **Reward appears in `tbl_score_adjustments` with descriptive `reason` field**
- [ ] Reward appears in "Recent Score Changes" on profile page
- [ ] User's total DSPOINC balance is updated correctly
- [ ] Role multipliers are applied correctly (if applicable)
- [ ] Duplicate rewards are prevented (409 Conflict response)

---

## 🚨 **CRITICAL RULES (MANDATORY FOR ALL FUTURE DEVELOPMENT)**

1. **ALWAYS use `riddle-reward.php` for riddle step completions** - Do NOT use `award-dspoinc-reward.php` for riddle rewards
2. **ALWAYS insert into `tbl_score_adjustments`** - This is **REQUIRED** for "Recent Score Changes" display on profile page
3. **ALWAYS use descriptive `reason` field** - Format: `"Riddle completion (RIDDLE_ID): base X × Y.Z = W DSPOINC"`
4. **ALWAYS unlock trait BEFORE awarding DSPOINC** - Ensures proper tracking order
5. **ALWAYS check for duplicate completions** - Use `UNIQUE(discord_id, riddle_id)` constraint
6. **ALWAYS use `game: "cheese_temple_riddles"` and `source: "riddle_completion"`** - Consistent with other 3D game rewards
7. **ALL future rewards MUST sync to database adjustments** - No exceptions - every reward must appear in "Recent Score Changes"
8. **ALL future levels MUST follow this pattern** - Level 5, 6, 7, and beyond must use the same standardized system

---

## 📝 **PROFILE PAGE INTEGRATION**

The "Recent Score Changes" section on `profile.html` fetches data from `/api/user/recent-adjustments.php`, which queries `tbl_score_adjustments` filtered by `user_id`.

**Local Development (Updated: November 18, 2025):**
- **Test User:** Uses Narrrf's actual Discord ID (`328601656659017732`) for local testing
- **Display Name:** Shows "Narrrf" in pause menu and profile page
- **Balance:** Fetches real balance from database (1,611,333+ DSPOINC)
- **Traits & Rewards:** All traits and DSPOINC rewards sync to Narrrf's account in database
- **API Behavior:** All APIs receive Narrrf's Discord ID and query real database data
- **Legacy Support:** Old `LOCAL_TEST_DISCORD` string automatically converted to Narrrf's ID

**Production:**
- Uses logged-in user's actual Discord ID from session
- Fetches user's real balance and traits from database
- All rewards sync to logged-in user's account

**Display Format:**
- Timestamp (formatted)
- Amount (green for positive, red for negative)
- Reason (descriptive text from `reason` field)

---

## 🔗 **RELATED RULES**

- **`01_MASTER_RULESET.md`** - API Creation Protocol
- **`12_UNIVERSAL_LEVEL_REQUIREMENTS_RULE.md`** - Universal level features
- **`HYTOPIA_THREE_TECH_DOCUMENTATION.md`** - Technical documentation

---

## 📅 **CREATED**

November 18, 2025

---

## ✅ **STATUS**

✅ **ACTIVE - MANDATORY FOR ALL 3D GAME RIDDLE REWARDS**

**Confirmed:** November 18, 2025 - All future rewards MUST sync to player's DSPOINC database adjustments and appear in "Recent Score Changes" on profile page. This is the standard way to show all DSPOINC rewards from the 3D game.

