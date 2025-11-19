# 🧩 3D RIDDLES DOCUMENTATION

**Folder Created:** November 12, 2025  
**Purpose:** Centralized documentation for all 3D adventure riddles  
**Status:** ✅ **ACTIVE**

---

## 📋 **OVERVIEW**

This folder contains comprehensive documentation for all riddles implemented in the 3D Cheese Temple adventure game. Each riddle document includes:

- **Riddle Description:** How to solve the riddle
- **Technical Implementation:** Code details and functions
- **Configuration:** How to adjust difficulty, timing, and rewards
- **Testing Guidelines:** Debugging and testing procedures
- **Visual Design:** UI/UX specifications

---

## 📚 **RIDDLE INDEX**

### **Level 1: Cheese Temple (3 Riddles)**
- **File:** `RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md`
- **Status:** ✅ **PRODUCTION VERIFIED** (November 19, 2025)
- **Total Riddles:** 3 separate riddles with individual rewards
- **Total Rewards:** 1,750 DSPOINC base (VIP: 3,500 DSPOINC with 2.0x multiplier)
- **Version:** 2.4 (Updated Nov 19, 2025 - Production testing complete)

#### **Riddle #1: The Discovery**
- **Trait:** `CHEESE_TEMPLE_RIDDLE_SOLVED`
- **Reward:** 500 DSPOINC base (VIP: 1,000 with 2.0x)
- **Description:** Three-step challenge (hidden discovery → aim cheese → aim unlockable block)

#### **Riddle #2: The Push**
- **Trait:** `CHEESE_TEMPLE_RIDDLE_02_SOLVED`
- **Reward:** 500 DSPOINC base (VIP: 1,000 with 2.0x)
- **Description:** Push cheese stone to oak stone → aim at cheese

#### **Riddle #3: The Portal**
- **Trait:** `CHEESE_TEMPLE_RIDDLE_03_SOLVED`
- **Reward:** 750 DSPOINC base (VIP: 1,500 with 2.0x)
- **Description:** Press lever → push block → enter portal

**Last Updated:** November 19, 2025 - Production testing verified: All 3 riddles tested with fresh database, role multipliers working correctly, all systems verified


### **Riddle #1: The Spawn (Level 2)**
- **File:** `RIDDLE_01_THE_SPAWN_LEVEL_2.md`
- **Status:** ✅ **FULLY IMPLEMENTED & TESTED (Nov 17, 2025)**
- **Difficulty:** Medium
- **Traits:** `CHEESE_TEMPLE_LEVEL2_STEP0`, `CHEESE_TEMPLE_LEVEL2_STEP1`, `CHEESE_TEMPLE_LEVEL2_STEP2`
- **Description:** Matrix-style white room with weapon galleries, accessory corridors, and monster displays. Three-step challenge: find hidden cheese stone → activate lever → inspect all displays to unlock portal.
- **Rewards:** +100 DSPOINC per step (300 total)
- **Version:** 2.0 (Updated Nov 17, 2025 - Complete 3-step system with traits and DSPOINC rewards)
- **Last Updated:** November 17, 2025 - Complete implementation with all steps, traits, and rewards verified

### **Riddle #1: The Hunt (Level 3)**
- **File:** `RIDDLE_01_THE_HUNT_LEVEL_3.md`
- **Status:** ✅ **FULLY IMPLEMENTED & TESTED (Nov 17, 2025)**
- **Difficulty:** Hard (10 monsters to catch across 2 phases)
- **Traits:** `CHEESE_TEMPLE_LEVEL3_STEP0`, `CHEESE_TEMPLE_LEVEL3_STEP1`, `CHEESE_TEMPLE_LEVEL3_STEP2`
- **Description:** Massive 160x160 cheese stone arena. Three-step challenge: find hidden cheese stone → hunt 5 monsters (Step 1) → hunt 5 more monsters (Step 2) → portal opens. Each monster rewards +50 DSPOINC with progressive scaling.
- **Rewards:** +100 DSPOINC (Step 0) + +250 DSPOINC (Step 1) + +250 DSPOINC (Step 2) = 600 total
- **Version:** 3.0 (Updated Nov 17, 2025 - Complete 3-step system with 10 monsters, traits, and portal)
- **Last Updated:** November 17, 2025 - Complete implementation verified: all 3 traits and 10 monster rewards confirmed in database

### **Riddle #1: The First Shot (Level 4)**
- **File:** `RIDDLE_01_THE_FIRST_SHOT_LEVEL_4.md`
- **Status:** ✅ **FULLY IMPLEMENTED (Nov 17, 2025)** — 50-cheese shooting challenge with progressive difficulty
- **Difficulty:** Hard (50 cheeses to shoot with increasing difficulty)
- **Traits:** `CHEESE_TEMPLE_LEVEL4_STEP0`, `CHEESE_TEMPLE_LEVEL4_STEP1`, `CHEESE_TEMPLE_LEVEL4_STEP2`
- **Description:** Massive 160x160 cheese stone arena. Step 0: find hidden cheese stone → stand for 10 seconds → unlock Step 1. Step 1: shoot 50 floating cheese entities with first-person weapon. Cheeses spawn continuously (1-5 per batch) with progressive difficulty - get smaller, faster, and smarter as you progress. Step 2: enter portal for completion screen.
- **Rewards:** +100 DSPOINC (Step 0) + +2,500 DSPOINC (Step 1: 50 × 50) + +200 DSPOINC (Step 2) = 2,800 total
- **Version:** 3.0 (Updated Nov 17, 2025 - Complete 50-cheese system with shooting mechanics, progressive difficulty, portal, and completion screen)
- **Last Updated:** November 17, 2025 - Fully implemented: 50-cheese shooting challenge with first-person weapon viewmodel, raycasting hit detection, progressive difficulty system, explosion effects, progress HUD, portal system, and completion screen. God Mode G and L keys supported.

### **Future Riddles:**
- Riddle #2: Cheese Temple Level 2 (Planned)
- Riddle #2: Cheese Temple Level 3 (Planned)
- Additional riddles will be added as they are implemented

---

## ✅ **PRODUCTION TESTING VERIFICATION (November 19, 2025)**

### **Level 1 Complete Testing:**
- **Test Method:** Fresh database test with Narrrf (VIP Holder) account, normal user mode (no God Mode)
- **Results:** ✅ **ALL TESTS PASSED**
  - All 3 riddles completed successfully
  - All 3 traits unlocked correctly
  - All 3 DSPOINC rewards awarded with correct 2.0x multiplier
  - All database records verified (traits, score adjustments, riddle completions, user scores)
  - All frontend displays working perfectly (Recent Score Changes, 3D Puzzles Achievements)
- **Total Rewards:** 3,500 DSPOINC correctly awarded (matches VIP Holder expected total)
- **Status:** ✅ **PRODUCTION VERIFIED** - All systems working correctly

### **Role-Based Multiplier Fix:**
- **Issue:** VIP Holder users receiving 1.0x multiplier instead of 2.0x
- **Fix:** Updated `getRoleMultiplier()` function to query `role_name` column (not non-existent `role_id`)
- **Result:** Role multipliers now correctly applied (VIP: 2.0x, Holder: 1.5x, Champion: 1.4x, etc.)
- **Status:** ✅ **PRODUCTION VERIFIED** - Working correctly for all role types

---

## 🎯 **RIDDLE SYSTEM ARCHITECTURE**

### **Core Components:**
1. **State Management:** `riddleState` object tracks progress
2. **Aiming Detection:** `THREE.Raycaster` for crosshair-based detection
3. **Timer System:** Frame-based delta timing with decay mechanism
4. **Progress UI:** Real-time visual feedback
5. **Trait Unlocking:** API integration for reward system

### **File Structure:**
```
3d_riddles/
├── README.md (this file)
├── RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md (✅ Implemented)
├── RIDDLE_02_CHEESE_TEMPLE_LEVEL_1.md (✅ Implemented)
├── RIDDLE_03_CHEESE_TEMPLE_LEVEL_1.md (✅ Implemented)
├── RIDDLE_01_THE_SPAWN_LEVEL_2.md (✅ Implemented)
├── RIDDLE_01_THE_HUNT_LEVEL_3.md (✅ Implemented)
├── RIDDLE_01_THE_FIRST_SHOT_LEVEL_4.md (✅ Step 1 Complete)
└── [Future riddles will be added here]
```

---

## 🔧 **COMMON PATTERNS**

### **Naming Convention:**
- **Riddle Files:** `RIDDLE_##_[LEVEL_NAME].md`
- **Trait Names:** `CHEESE_TEMPLE_RIDDLE_##` or `CHEESE_TEMPLE_[SPECIFIC_NAME]`
- **Function Prefixes:** `updateRiddle...`, `completeRiddle...`, `unlockRiddle...`

### **Standard Sections:**
1. Riddle Overview
2. How to Solve
3. Technical Implementation
4. Configuration & Adjustment
5. Debugging
6. Testing Checklist
7. Visual Design
8. Future Enhancements

---

## 📝 **DOCUMENTATION STANDARDS**

### **Required Information:**
- ✅ Riddle ID and level
- ✅ Step-by-step solution guide
- ✅ Technical implementation details
- ✅ Configuration options (timing, difficulty, etc.)
- ✅ API endpoints and database schema
- ✅ Visual design specifications
- ✅ Testing procedures

### **Code Examples:**
- Include actual code snippets from implementation
- Show configuration variables and their default values
- Provide examples for common adjustments

### **Visual References:**
- Describe UI elements and their styling
- Include color codes and positioning
- Document animation and transition effects

---

## 🚀 **QUICK REFERENCE**

### **Adjusting Riddle Difficulty:**
1. **Aim Time:** Change `RIDDLE_AIM_TIME` constant
2. **Timer Decay:** Modify decay multiplier in `updateRiddleAiming()`
3. **Detection Range:** Adjust distance threshold in `updateCrosshairAim()`

### **Adding New Riddles:**
1. Create new riddle document in this folder
2. Implement riddle logic in `three.js/main.js`
3. Add riddle state to `riddleState` object
4. Create trait unlock API call
5. Update this README with new riddle entry

---

## 🔗 **RELATED DOCUMENTATION**

- **Main Technical Doc:** `../HYTOPIA_THREE_TECH_DOCUMENTATION.md`
- **Cheese Hunt System:** `../CHEESE_HUNT_SYSTEM_SPECIFICATION.md`
- **Controls System:** See Chapter 13 in Hytopia Three Tech Documentation
- **Trait API:** `api/user/unlock-trait.php`

---

## 🏆 **3D PUZZLES ACHIEVEMENTS SYSTEM**

**Status:** ✅ **IMPLEMENTED** (November 18, 2025)

All riddle completions are automatically displayed as achievements on the player's profile page via the 3D Puzzles Achievements section.

### **Features:**
- **Auto-Detection:** Automatically finds all `CHEESE_TEMPLE_*` traits from database
- **Level Grouping:** Achievements organized by level (Level 1, 2, 3, 4...)
- **Dynamic Generation:** Achievement definitions generated from trait names
- **Scalable:** Works for unlimited levels and steps
- **Profile Integration:** Beautiful UI on profile page with expandable sections

### **API Endpoint:**
- **URL:** `/api/user/get-3d-puzzles-achievements.php`
- **Method:** POST
- **Parameters:** `user_id` (Discord ID or `LOCAL_TEST_DISCORD` for local testing)
- **Response:** All achievements with unlock status, grouped by level

### **Trait Patterns:**
- **Level 1 Riddles:** `CHEESE_TEMPLE_RIDDLE_SOLVED`, `CHEESE_TEMPLE_RIDDLE_02_SOLVED`, `CHEESE_TEMPLE_RIDDLE_03_SOLVED`
- **Level 2+ Steps:** `CHEESE_TEMPLE_LEVEL2_STEP0`, `CHEESE_TEMPLE_LEVEL2_STEP1`, `CHEESE_TEMPLE_LEVEL2_STEP2`, etc.

### **Documentation:**
- **Implementation Plan:** `12.0/TECHNICAL_DOCUMENTATION/3D_PUZZLES_ACHIEVEMENTS_IMPLEMENTATION_PLAN.md`
- **Tech Docs:** `12.0/TECHNICAL_DOCUMENTATION/HYTOPIA_THREE_TECH_DOCUMENTATION.md` (Section 21)

---

## 🎯 **DSPOINC REWARDS SYNC RULE (CRITICAL)**

**MANDATORY FOR ALL FUTURE RIDDLE REWARDS:** All DSPOINC rewards from the 3D game MUST sync to the player's DSPOINC database adjustments and appear in "Recent Score Changes" on the profile page.

### **Required API Endpoint:**
- **ALWAYS use:** `/api/dev/riddle-reward.php` for all riddle step completions
- **Database Tables:** 
  - `tbl_riddle_completions` - Completion tracking
  - `tbl_user_scores` - DSPOINC balance (`game: "cheese_temple_riddles"`, `source: "riddle_completion"`)
  - `tbl_score_adjustments` - **CRITICAL** for "Recent Score Changes" display

### **Standard Pattern:**
```javascript
// 1. Unlock trait FIRST
await unlockLevelXTrait(TRAIT_KEY, "Description");

// 2. Award DSPOINC reward (writes to tbl_score_adjustments automatically)
await awardLevelXDspoincReward(stepId, baseReward, contextLabel);
```

### **Rule Document:**
- **Full Rule:** `12.0/RULES/13_3D_GAME_DSPOINC_SYNC_RULE.md`
- **Master Ruleset:** `12.0/RULES/01_MASTER_RULESET.md` (Section: "3D GAME DSPOINC REWARDS SYNC RULE")

### **Current Status:**
- ✅ Level 1: Using `RIDDLE_REWARD_ENDPOINT` (3 riddles)
- ✅ Level 2: Using standardized helper function
- ✅ Level 3: Using standardized helper function
- ✅ Level 4: Using standardized helper function
- ✅ All rewards appear in "Recent Score Changes" on profile page

---

## ⚙️ **UNIVERSAL LEVEL REQUIREMENTS**

**CRITICAL RULE:** All levels MUST have identical GOD Mode features, sound systems, and controls. These features are implemented at the global level and automatically extend to all levels.

### **Required Features (Same in ALL Levels):**
- ✅ **GOD Mode:** Double speed + fly mode (Space/Shift) - Works in all levels
- ✅ **Level Selector:** L key opens level menu - Works in all levels
- ✅ **Riddle Cycling:** G key cycles riddle steps - Works in all levels
- ✅ **Sound System:** 
  - Footstep sounds (when moving on ground)
  - Jump sound (Space key)
  - Level-up sound (portal completion)
  - All sounds work identically in all levels
- ✅ **Options Menu:** GOD Mode toggle, camera modes - Same in all levels
- ✅ **Player Speed:** 1.5x base speed (12 normal, 21 sprint) - Same in all levels
- ✅ **Controls:** WASD movement, Space jump, Shift sprint - Same in all levels
- ✅ **Camera Modes:** First-person, third-person, joystick view - Same in all levels

### **Enforcement:**
- These features are **global** and not level-specific
- No level should override or disable these features
- All levels inherit these features automatically
- If a feature works in one level, it must work in all levels

### **Implementation:**
- Event handlers are at the **document level** (not level-specific)
- Sound system is **global** (not level-specific)
- GOD Mode is **global** (not level-specific)
- Level selector is **global** (not level-specific)

---

**Folder Version:** 2.2  
**Last Updated:** November 18, 2025  
**Maintained By:** Narrrf's Lab Tech Council

**Latest Update:** November 18, 2025 - Local Test User System updated to use Narrrf's actual Discord ID (`328601656659017732`) for local testing. Old `LOCAL_TEST_DISCORD` string automatically converted. Balance always fetched from database (no stale cache). All traits and DSPOINC rewards sync to Narrrf's account. Production uses logged-in user's Discord ID from session.

---

## 🧪 **LOCAL TEST USER SYSTEM (Updated: November 18, 2025)**

### **CRITICAL:** Local Development Uses Narrrf's Actual Account

**Local Development (localhost):**
- **Discord ID:** `328601656659017732` (Narrrf's actual Discord ID)
- **Display Name:** `Narrrf`
- **Balance:** Fetches real balance from database (1,611,333+ DSPOINC)
- **Traits & Rewards:** All traits and DSPOINC rewards sync to Narrrf's account
- **API Behavior:** All APIs receive Narrrf's Discord ID and query real database data
- **Auto-Replacement:** Old `LOCAL_TEST_DISCORD` string automatically converted to Narrrf's ID
- **Balance Fetching:** Always fetches from API (no cached balance for test users)

**Production (narrrfs.world):**
- **Discord ID:** Logged-in user's actual Discord ID from session
- **Display Name:** User's actual Discord username
- **Balance:** Fetches user's real balance from database
- **Traits & Rewards:** All traits and DSPOINC rewards sync to logged-in user's account

**Implementation:**
- **Code Location:** `three.js/main.js` (lines 137-179)
- **API Support:** `api/user/details.php` handles legacy string conversion
- **Testing:** Play levels locally → traits/rewards sync to Narrrf's account → verify on profile page

**This ensures:**
- ✅ Local testing uses real account data (not fake test data)
- ✅ Traits and rewards persist in database
- ✅ Profile page shows correct achievements and balance
- ✅ Production uses actual logged-in users automatically

