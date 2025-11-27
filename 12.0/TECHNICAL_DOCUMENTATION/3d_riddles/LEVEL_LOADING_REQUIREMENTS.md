# 📋 LEVEL LOADING REQUIREMENTS - COMPREHENSIVE OVERVIEW

**Created:** November 27, 2025  
**Last Updated:** November 27, 2025 (Level 3 & 4 Initialization Fixes)  
**Purpose:** Document what must be loaded/initialized for each level to work properly  
**Status:** ✅ **LEVEL 3 & 4 VERIFIED WORKING**

---

## 🎯 **CORE PRINCIPLE**

**Every level must be completely reset and initialized when switching via GOD mode or restart.**

---

## 📊 **LEVEL-BY-LEVEL REQUIREMENTS**

### **LEVEL 1: CHEESE TEMPLE**

#### **What Must Be Loaded:**
1. ✅ **Environment**
   - Cheese temple map/scene
   - Floating cheese (if exists)
   - Trigger blocks for riddles
   - Bear trap system

2. ✅ **State Reset**
   - Riddle state (all steps reset)
   - Bear trap state
   - Trigger block timers
   - Completion flags

3. ✅ **Player State**
   - Spawn position
   - Camera mode reset

#### **Critical Initialization:**
- Trigger blocks must be visible
- Riddle state must start from Step 0
- Bear trap must be recreated

---

### **LEVEL 2: THE SPAWN (MATRIX CONSTRUCT)**

#### **What Must Be Loaded:**
1. ✅ **Environment**
   - White room arena
   - Weapon gallery
   - Trigger blocks
   - Portal system

2. ✅ **State Reset**
   - Step 0/1/2 completion flags
   - Weapon gallery unlock state
   - Trigger block timers

3. ✅ **Weapon System**
   - Weapon cache cleared
   - Gallery weapons reset

#### **Critical Initialization:**
- Trigger blocks visible
- Gallery locked initially
- Portal hidden until unlocked

---

### **LEVEL 3: THE HUNT (MONSTER ARENA)**

#### **What Must Be Loaded:**
1. ✅ **Environment**
   - 160x160 cheese stone arena
   - Moving walls (4 walls)
   - Hidden cheese stone trigger block
   - Portal (hidden initially)

2. ✅ **State Reset**
   - `level3RiddleState.step0Complete = false`
   - `level3RiddleState.currentStep = 0`
   - `level3RiddleState.monstersCaught = 0`
   - `level3RiddleState.currentMonsterIndex = 0`
   - `level3RiddleState.firstMonsterSpawned = false`
   - `level3RiddleState.triggerBlockTimer = 0`
   - `level3State.monsters = []` (clear all)
   - `level3State.portalActive = false`
   - `level3State.crushActive = false`
   - `level3State.introShown = false` ⚠️ **CRITICAL**

3. ✅ **Trigger Block**
   - `triggerBlockVisual.visible = true` ⚠️ **CRITICAL**
   - `triggerBlockVisual.position.y = restY` (reset to rest position)
   - `triggerBlock.visible = false` (collider hidden)

4. ✅ **Moving Walls**
   - Reset to initial positions
   - Reset movement configuration

5. ✅ **Monster System**
   - All monsters cleared from scene
   - All mixers stopped
   - Monster array cleared

#### **Critical Initialization:**
- ⚠️ **Trigger block visual MUST be visible** - Player needs to find it
- ⚠️ **Intro toast must show** - Requires `introShown = false`
- ⚠️ **Moving walls must reset** - Call `resetLevel3MovingWalls()`
- ⚠️ **All monsters must be cleared** - Reset function does this

#### **Monster Spawning Flow:**
1. Player finds hidden cheese stone (trigger block visual)
2. Player stands on it for 10 seconds
3. Step 0 completes → `step0Complete = true`
4. First monster spawns → `spawnLevel3Monster()` called
5. Player hunts monsters in Step 1 (5 monsters)
6. Then Step 2 (5 more monsters)
7. Portal appears after all 10 monsters caught

---

### **LEVEL 4: THE FIRST SHOT**

#### **What Must Be Loaded:**
1. ✅ **Environment**
   - 160x160 cheese stone arena
   - Hidden cheese stone trigger block
   - Portal (hidden initially)

2. ✅ **State Reset**
   - `level4RiddleState.step0Complete = false`
   - `level4RiddleState.step1Active = false` ⚠️ **CRITICAL**
   - `level4RiddleState.step2Active = false` ⚠️ **CRITICAL**
   - `level4RiddleState.currentWave = 1`
   - `level4RiddleState.cheesesCaught = 0`
   - `level4RiddleState.monstersDefeated = 0`
   - `level4RiddleState.currentMonsterWave = 1`
   - `level4RiddleState.triggerBlockTimer = 0`
   - `level4State.cheeses = []` (clear all)
   - `level4State.monsters = []` (clear all)
   - `level4State.monsterProjectiles = []` (clear all)
   - `level4State.introShown = false` ⚠️ **CRITICAL**

3. ✅ **Weapon System**
   - `level4State.currentWeaponSlot = 1` (reset to default)
   - `level4State.weaponSlots = {}` (clear weapon cache)
   - `level4State.weaponHeat = 0`
   - `level4State.isOverheated = false`
   - `removeLevel4WeaponViewmodel()` (remove any active weapon)
   - `weaponBobPhase = 0`
   - `weaponRecoilOffset = 0`

4. ✅ **Trigger Block**
   - `triggerBlockVisual.visible = true` ⚠️ **CRITICAL**
   - `triggerBlockVisual.position.y = restY` (reset to rest position)
   - `triggerBlock.visible = false` (collider hidden)

5. ✅ **UI Elements**
   - HUD removed/reset
   - Wave countdown elements removed
   - Progress HUD cleared

#### **Critical Initialization:**
- ⚠️ **Trigger block visual MUST be visible** - Player needs to find it
- ⚠️ **Weapon must be removed** - Only loads when Step 1 becomes active
- ⚠️ **Step flags must be false** - `step1Active` and `step2Active` start as false
- ⚠️ **Intro toast must show** - Requires `introShown = false`

#### **Weapon Activation Flow:**
1. Player finds hidden cheese stone (trigger block visual)
2. Player stands on it for 10 seconds
3. Step 0 completes → `step0Complete = true`
4. Step 1 activates → `step1Active = true` ⚠️ **CRITICAL**
5. Weapon loads → `loadLevel4WeaponViewmodel()` called
6. HUD appears → `createLevel4ProgressHUD()` called
7. First wave countdown → `showLevel4WaveCountdown()` called
8. First wave spawns → `spawnLevel4Cheeses()` called

#### **Monster Wave Activation Flow:**
1. All 50 cheeses shot → `cheesesCaught >= 50`
2. Step 1 deactivates → `step1Active = false`
3. Step 2 activates → `step2Active = true` ⚠️ **CRITICAL**
4. Monster wave countdown → `showLevel4MonsterWaveCountdown()` called
5. First monster wave spawns → `spawnMonsterWave()` called

---

### **LEVEL 5: THE WALK**

#### **What Must Be Loaded:**
1. ✅ **Environment**
   - Klagenfurt city map
   - Collision mesh
   - Border walls
   - Portal system

2. ✅ **State Reset**
   - Step 0/1 completion flags
   - Monster spawn state
   - Timer state
   - Trait unlock state

#### **Critical Initialization:**
- Collision mesh must be ready
- Map must be loaded
- Spawn position set

---

## 🔍 **COMMON ISSUES IDENTIFIED**

### **Issue 1: introShown Flag Not Reset**
- **Problem:** `showLevel3IntroToast()` and `showLevel4IntroToast()` check `if (introShown) return;`
- **Impact:** Intro toast won't show on subsequent level switches
- **Fix:** Reset `introShown = false` in reset functions ✅ **FIXED**

### **Issue 2: Trigger Block Visibility**
- **Problem:** Trigger block visual might not be visible after reset
- **Impact:** Player can't start the level (can't find trigger block)
- **Fix:** Ensure `triggerBlockVisual.visible = true` in reset functions ✅ **VERIFIED**

### **Issue 3: Step Flags Not Reset**
- **Problem:** `step1Active` and `step2Active` might remain true from previous run
- **Impact:** Weapons/monsters activate immediately without completing Step 0
- **Fix:** Reset all step flags in reset functions ✅ **VERIFIED**

### **Issue 4: Weapon Cache Not Cleared**
- **Problem:** Weapon cache might contain stale references
- **Impact:** Weapons don't load properly or wrong weapon shows
- **Fix:** Clear `weaponSlots = {}` in reset functions ✅ **VERIFIED**

### **Issue 5: Warp vs Restart Inconsistency**
- **Problem:** `warpToLevelX()` functions don't match `restartLevelX()` functions
- **Impact:** Different initialization paths cause issues
- **Fix:** Make warp functions identical to restart functions ✅ **IN PROGRESS**

---

## 🔧 **FIXES APPLIED**

### **Fix 1: Reset introShown Flags**
- ✅ Added to `resetLevel3Progress()` (line 8643)
- ✅ Added to `resetLevel4Progress()` (line 10523)

### **Fix 2: Align Warp with Restart**
- ✅ Added `hideLevel3GameOverScreen()` to `warpToLevel3()` (line 12818)
- ✅ Added weapon heat reset to `warpToLevel4()` (line 12213-12217)

### **Fix 3: Comprehensive Cleanup**
- ✅ `cleanupAllLevels()` function created and called in all warp/restart functions

---

## ✅ **VERIFICATION COMPLETE**

1. ✅ **Verify all level state objects are properly initialized** (Level 3 & 4 verified)
2. ✅ **Test GOD mode level switching** (Level 3 & 4 verified working)
3. ✅ **Verify monsters spawn correctly** (Level 3 & 4 verified)
4. ✅ **Verify weapons activate correctly** (Level 4 verified)
5. ✅ **Create initialization checklist for each level** (documented)

---

## 🎯 **VERIFIED WORKING PATTERN**

**Critical Fix Applied to Level 3 & 4:**
- Group scene verification in warp/restart functions
- Auto-add group to scene if missing
- Comprehensive spawn function checks
- Result: Both levels work perfectly on first attempt via GOD mode

---

**Last Updated:** November 27, 2025  
**Status:** ✅ **LEVEL 3 & 4 VERIFIED WORKING - PRODUCTION READY**

