# 🔍 LEVEL 1 & LEVEL 5 RIDDLE INVESTIGATION

**Date:** January 11, 2026  
**Status:** 🔍 **INVESTIGATION COMPLETE**  
**Purpose:** Investigate Level 1 plate/trigger block system and Level 5 riddle step implementation

---

## 🎯 **INVESTIGATION REQUEST**

User reported:
- **Level 1:** Cannot find the plate that should work and make monsters spawn
  - **Note:** Level 1 does NOT spawn monsters - it's a riddle system with trigger blocks, cheese aiming, and block aiming
  - User may be confusing Level 1 with Level 3/4/5 which have monster systems
- **Level 5:** Want to extend riddle steps but first need to understand what's currently in the code

---

## 🧩 **LEVEL 1 RIDDLE SYSTEM ANALYSIS**

### **Level 1 Riddle State Structure:**

Level 1 uses a **global `riddleState` object** (not `level1RiddleState`):

```javascript
let riddleState = {
  step0Complete: false,  // Hidden trigger block discovery complete
  step0StandingSoundPlayed: false, // Audio cue when stepping onto cheese stone
  step1Complete: false,  // Cheese aiming complete
  step2Complete: false,  // Block aiming complete
  triggerBlockTimer: 0, // Timer for standing on trigger block (0-10 seconds)
  cheeseAimTimer: 0,    // Timer for aiming at cheese (0-10 seconds)
  blockAimTimer: 0,     // Timer for aiming at unlockable block (0-10 seconds)
  triggerBlock: null,   // The hidden golden stone block player must find and stand on (collider)
  triggerBlockVisual: null, // Visual block for animation (sinks when player stands on it)
  triggerBlockTargetY: 0, // Target Y position for visual block animation
  unlockableBlock: null,  // The special block that unlocks after step 1
  // ... riddle2, riddle3, riddle4 states
};
```

### **Level 1 Riddle #1 Structure:**

Level 1 has **3 separate riddles** (Riddle #1, #2, #3) plus 1 hidden secret riddle (#4):

- **RIDDLE #1:** "The Discovery" - 3 steps (Step 0: trigger block, Step 1: cheese aim, Step 2: block aim)
- **RIDDLE #2:** "The Push" - 2 steps (Step 1: push cheese stone, Step 2: aim at cheese)
- **RIDDLE #3:** "The Portal" - 3 steps (Step 1: lever press, Step 2: push block, Step 3: portal appears)
- **RIDDLE #4:** "The Hidden Secret" - Secret lever combination puzzle

### **Level 1 Trigger Block (Plate) System:**

**Function:** `createTriggerBlock(mapData, blockSize)` (line ~31240)

**What It Creates:**
- **Collider Block:** Hidden golden stone block (invisible, used for collision detection)
- **Visual Block:** Visible block with animation (sinks when player stands on it)
- **Position:** Hidden in the back of the game field (relative to spawn)
- **Texture:** `yellow-cheese.png` (golden/yellow stone appearance)
- **Detection:** Uses `isPlayerStandingOnBlock()` function to detect when player stands on it

**Key Properties:**
- `riddleState.triggerBlock` - The collider (invisible)
- `riddleState.triggerBlockVisual` - The visual block (visible, animated)
- `block.visible = false` - Collider is hidden
- `visualBlock.visible = true` - Visual block is visible
- `block.userData.riddleId = 'CHEESE_TEMPLE_RIDDLE_01'` - Identifies which riddle
- `block.userData.triggerStep = 0` - Step 0 trigger

**Detection System:**
- Uses `isPlayerStandingOnBlock(riddleState.triggerBlock)` function
- Checks if player capsule collides with trigger block
- Timer counts up when player is standing on it (10 seconds required)
- Visual block sinks when player stands on it (animation feedback)

**Issue Investigation:**
- User says they cannot find the plate
- The trigger block is **hidden in the back of the game field**
- It should be visible (visual block with yellow-cheese texture)
- Possible issues:
  1. Block not being created (function not called)
  2. Block created but not visible (material/texture issue)
  3. Block created but positioned incorrectly (hard to find)
  4. Block created but not added to scene properly

---

## 🎮 **LEVEL 5 RIDDLE SYSTEM ANALYSIS**

### **Level 5 Riddle State:**

**Current Status:** ❌ **NO RIDDLE STATE FOUND IN CODE**

Level 5 does **NOT** have a `level5RiddleState` object defined in the code.

**Level 5 State Structure (Current):**
```javascript
const level5State = {
  built: false,
  group: new THREE.Group(),
  mapMesh: null,
  mapScale: 5.0,
  spawnPosition: new THREE.Vector3(0, 0, 0),
  borderWalls: null,
  glyphs: [], // NEW (January 11, 2026)
  glyphPositions: [] // NEW (January 11, 2026)
  // NO riddle state properties
};
```

### **Level 5 Planned Riddle System (From Documentation):**

According to `LEVEL_5_STEP1_MONSTER_HUNT_PLAN.md`, Level 5 should have:

**Step 0 (Trigger Plate):**
- Player stands on cheese-stone plate
- Weapons activate (slots 1 & 2)
- Step 1 starts (monsters spawn)

**Step 1 (Monster Hunt):**
- All monsters spawn across entire Level 5 map
- 10-minute countdown timer starts
- Monster counter shows remaining monsters
- Player hunts monsters using weapons
- Flying monsters shoot thunder bullets (instant Game Over if hit)
- Completion: All monsters defeated → Award 2,500 DSPOINC

**Planned State Structure:**
```javascript
const level5RiddleState = {
  // Step 0 (existing)
  step0Complete: false,
  weaponsEnabled: false,
  triggerPlate: null,
  
  // Step 1 (new)
  step1Active: false,
  step1Complete: false,
  step1Timer: 600, // 10 minutes in seconds
  step1StartTime: null,
  step1TimerActive: false,
  monstersDefeated: 0,
  totalMonsters: 0
};
```

### **Level 5 Current Implementation:**

**❌ NOT IMPLEMENTED:**
- No `level5RiddleState` object
- No trigger plate/block system
- No Step 0 implementation
- No Step 1 monster spawning
- No timer system
- No monster counter
- No completion system
- No riddle update function (`updateLevel5Step0`, `updateLevel5Step1`)

**✅ CURRENTLY IMPLEMENTED:**
- Level 5 map loading (`buildLevel5TheWalk()`)
- Border walls creation
- Glyph monuments (5 glyphs, circle pattern) - NEW (January 11, 2026)
- Collision mesh system
- Weapon system (slots 1 & 2) - shared with Level 4
- `updateLevel5(delta)` function exists but only handles weapons (line ~21680)

---

## 📋 **COMPARISON: LEVEL 1 vs LEVEL 2/3/4 vs LEVEL 5**

### **Level 1 (Riddle System):**
- **State:** `riddleState` (global object)
- **Riddles:** 4 separate riddles (Riddle #1, #2, #3, #4)
- **Riddle #1 Steps:** Step 0 (trigger block), Step 1 (cheese aim), Step 2 (block aim)
- **Trigger System:** `createTriggerBlock()` function creates hidden golden stone block
- **Detection:** `isPlayerStandingOnBlock()` function
- **Status:** ✅ **IMPLEMENTED**

### **Level 2 (Riddle System):**
- **State:** `level2RiddleState` (level-specific object)
- **Steps:** Step 0 (trigger block), Step 1 (lever), Step 2 (inspection zones)
- **Trigger System:** `level2RiddleState.triggerBlock` (cheese-stone block)
- **Detection:** `checkLevel2TriggerBlockStanding()` function
- **Status:** ✅ **IMPLEMENTED**

### **Level 3 (Riddle System):**
- **State:** `level3RiddleState` (level-specific object)
- **Steps:** Step 0 (trigger block), Step 1 (monster hunt), Step 2 (monster hunt), Step 3 (portal)
- **Trigger System:** `level3RiddleState.triggerBlock` (cheese-stone block)
- **Detection:** Similar pattern to Level 2
- **Status:** ✅ **IMPLEMENTED**

### **Level 4 (Riddle System):**
- **State:** `level4RiddleState` (level-specific object)
- **Steps:** Step 0 (trigger block), Step 1 (cheese captures), Step 2 (portal)
- **Trigger System:** `level4RiddleState.triggerBlock` (cheese-stone block)
- **Detection:** Similar pattern to Level 2/3
- **Status:** ✅ **IMPLEMENTED**

### **Level 5 (Riddle System):**
- **State:** ❌ **NOT DEFINED** (should be `level5RiddleState`)
- **Steps:** ❌ **NOT IMPLEMENTED**
- **Trigger System:** ❌ **NOT IMPLEMENTED**
- **Detection:** ❌ **NOT IMPLEMENTED**
- **Status:** ❌ **NOT IMPLEMENTED** (only planned in documentation)

---

## 🔍 **LEVEL 1 PLATE/TRIGGER BLOCK LOCATION**

### **Position Calculation:**

From `createTriggerBlock()` function (line ~31240):
```javascript
// Position: Hidden in the back of the game field
const triggerX = spawnX + 20; // 20 units east of spawn
const triggerZ = spawnZ + 100; // 100 units north of spawn (back of field)
const triggerY = floorTopY; // Ground level (1.0)
```

**Relative to Spawn:**
- **X:** +20 units (east of spawn)
- **Z:** +100 units (north of spawn - **back of the field**)
- **Y:** Ground level (1.0)

**Block Properties:**
- **Size:** 1x1x1 unit block
- **Texture:** `yellow-cheese.png` (golden/yellow stone)
- **Visual:** Should be visible (yellow stone block)
- **Animation:** Sinks 0.3 units when player stands on it

**Possible Issues:**
1. **Block too far from spawn** (Z = +100) - might be hard to find
2. **Block not visible** - material/texture issue
3. **Block not created** - `createTriggerBlock()` not called in `buildLevel1()`
4. **Block created but wrong position** - calculation issue

---

## 🔍 **LEVEL 1 TRIGGER BLOCK DETAILED ANALYSIS**

### **Position Details:**
- **Function:** `createTriggerBlock(mapData, blockSize)` (line ~31228)
- **Relative Position:** 
  - **X:** `spawnX + (20 * blockSize + blockSize/2)` = `spawnX + 20.5`
  - **Z:** `spawnZ + (100 * blockSize + blockSize/2)` = `spawnZ + 100.5`
  - **Y:** `floorTopY - (blockHeight / 2) + 0.1` = Ground level + 0.1 (slightly above floor)
- **Absolute Position:** If spawn is at (60, groundY, 15), block is at approximately (80.5, groundY+0.1, 115.5)
- **Distance from Spawn:** ~103 units (far corner, requires exploration)

### **Block Properties:**
- **Scale:** 1.5x (larger than normal blocks)
- **Texture:** `yellow-cheese.png` (golden/yellow stone)
- **Visibility:** 
  - **Collider Block:** `visible = false` (invisible, used for detection)
  - **Visual Block:** `visible = true` (should be visible)
- **Material:** `MeshStandardMaterial` with yellow-cheese texture
- **Pulsing Glow:** Emissive intensity pulses between 0.3-0.5 (subtle glow)
- **Animation:** Sinks 0.3 units when player stands on it

### **Detection System:**
- **Function:** `checkTriggerBlockStanding()` (line ~31859)
- **Uses:** `isPlayerStandingOnBlock(riddleState.triggerBlock)`
- **Update Loop:** Called in `updateRiddleAiming(delta, aimingAtCheese, aimingAtBlock)` (line ~31902)
- **Timer:** `riddleState.triggerBlockTimer` increments when standing, decays when not standing
- **Completion:** Timer reaches `RIDDLE_AIM_TIME` (10 seconds) → Step 0 complete

### **Possible Issues:**
1. **Block Position Too Far:** Z = +100 from spawn (far corner) - hard to find
2. **Visual Block Not Visible:** Material/texture issue, or block not added to scene
3. **Block Created But Wrong Position:** Calculation error in `createTriggerBlock()`
4. **User Confusion:** Level 1 doesn't spawn monsters - user may be looking for wrong thing

---

## 🎯 **RECOMMENDATIONS**

### **For Level 1:**
1. **Verify `createTriggerBlock()` is called** in `buildLevel1()` function (line ~16280)
2. **Check block visibility** - ensure visual block is visible and textured correctly
3. **Check block position** - verify it's at (spawnX + 20.5, groundY+0.1, spawnZ + 100.5)
4. **Add debug logging** - log block position and visibility status (already exists in code)
5. **Make block more visible** - Consider:
   - Making it larger (currently 1.5x)
   - Adding stronger glow effect
   - Moving it closer to spawn (currently 100 units away)
   - Adding visual markers or particles
6. **Clarify to User:** Level 1 doesn't spawn monsters - it's a riddle system (aiming challenges)

### **For Level 5:**
1. **Create `level5RiddleState` object** - following Level 2/3/4 pattern
   - Location: After `level5State` definition (line ~2784)
   - Structure: Similar to `level2RiddleState`, `level3RiddleState`, `level4RiddleState`
2. **Implement Step 0 (Trigger Plate)** - create cheese-stone plate at spawn area
   - Function: `createLevel5TriggerPlate()` (similar to Level 2/3/4)
   - Position: Near spawn (easier to find than Level 1)
   - Detection: `checkLevel5TriggerPlateStanding()` (similar to Level 2/3/4)
3. **Implement Step 1 (Monster Hunt)** - following `LEVEL_5_STEP1_MONSTER_HUNT_PLAN.md`
   - Function: `spawnLevel5Step1Monsters()` - grid-based spawning
   - Timer System: 10-minute countdown
   - Counter System: Monster counter display
   - Completion System: Award 2,500 DSPOINC
4. **Add update loop** - Extend `updateLevel5(delta)` function to handle riddle steps
   - Add `updateLevel5Step0(delta)` - trigger plate detection
   - Add `updateLevel5Step1(delta)` - timer and monster counter updates
5. **Follow Level 2/3/4 patterns** - reuse proven trigger block system
   - Use `isPlayerStandingOnBlock()` function
   - Use timer decay system (prevents accidental completion)
   - Use visual block animation (sinks when pressed)

---

## 📝 **NEXT STEPS**

1. **Investigate Level 1 trigger block** - Check if it's being created and positioned correctly
2. **Verify Level 1 plate visibility** - Ensure visual block is visible and findable
3. **Plan Level 5 riddle implementation** - Based on Level 2/3/4 patterns and documentation
4. **Implement Level 5 Step 0** - Create trigger plate system
5. **Implement Level 5 Step 1** - Create monster hunt system

---

**Status:** ✅ **INVESTIGATION COMPLETE**  
**Date:** January 11, 2026  
**Next:** User review and decision on implementation approach
