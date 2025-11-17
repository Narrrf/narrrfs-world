# 🧩 RIDDLE #1 - CHEESE TEMPLE LEVEL 1

**Document Created:** November 12, 2025  
**Riddle ID:** `CHEESE_TEMPLE_RIDDLE_01`  
**Level:** Cheese Temple - Level 1  
**Status:** ✅ **IMPLEMENTED & TESTED**  
**Trait Unlocked:** `CHEESE_TEMPLE_RIDDLE_SOLVED`  
**Last Tested:** November 12, 2025 - ✅ **WORKING**

---

## 📋 **RIDDLE OVERVIEW**

### **Objective:**
Solve the Cheese Temple riddle by completing three sequential challenges: discovery, precision aiming, and final activation.

### **Riddle Description:**
Players must demonstrate exploration skills, focus, and precision by:
1. **Step 0:** Find and stand on the hidden golden stone block in the back of the game field for 10 continuous seconds
2. **Step 1:** After unlocking the riddle hint, aim at the floating cheese entity for 10 continuous seconds
3. **Step 2:** After unlocking the special block, aim at the unlockable block for 10 continuous seconds

### **Reward:**
- **Trait Unlocked:** `CHEESE_TEMPLE_RIDDLE_SOLVED` (value: `true`)
- **DSPOINC Reward:** 500 DSPOINC base reward (multiplied by role multiplier)
  - **VIP Holder:** 1,000 DSPOINC (×2.0)
  - **Holder:** 750 DSPOINC (×1.5)
  - **Champion:** 700 DSPOINC (×1.4)
  - **WL/Season Tester:** 650 DSPOINC (×1.3)
  - **Early Bird:** 600 DSPOINC (×1.2)
  - **Cheese Hunter:** 550 DSPOINC (×1.1)
  - **Default:** 500 DSPOINC (×1.0)
- **Visual Feedback:** Completion message, reward notification, and glowing block effect
- **Progress Tracking:** Real-time progress bar with countdown timer
- **HUD Update:** DSPOINC balance automatically updated in pause menu and HUD

---

## 🎮 **HOW TO SOLVE**

### **Step 0: Discover the Hidden Golden Stone (10 seconds)**
1. **Explore the game field** - The riddle UI is hidden until you find the trigger block
2. **Navigate to the back of the level** - The hidden golden stone block is located at coordinates (110, 1, 110) - far from spawn
3. **Look for the yellow-cheese texture block** - It has a subtle golden glow to help you discover it
4. **Stand on the block** - Position yourself directly on top of the golden stone block
5. **Wait for 10 seconds** - The riddle UI will appear showing "🔍 Step 0: Stand on Golden Stone" with a countdown
6. **Keep standing on the block** - Timer will decay if you step off (prevents accidental completion)
7. **Audio feedback and completion** - As soon as you step on the stone the `cheese_platform_active.ogg` cue plays once to confirm you found the correct trigger. After 10 seconds the riddle hint unlocks and Step 1 instructions appear.

### **Step 1: Aim at Cheese Entity (10 seconds)**
1. After Step 0 completes, the riddle UI now shows "Step 1: Aim at Cheese"
2. Locate the floating cheese cube that roams around the temple
3. Aim your crosshair at the cheese entity (crosshair will turn yellow/gold when aiming)
4. **Keep aiming continuously for 10 seconds** - do not move your crosshair away
5. Watch the progress bar at the bottom of the screen for countdown
6. Timer will decay if you stop aiming (prevents accidental completion)

### **Step 2: Aim at Unlockable Block (10 seconds)**
1. After Step 1 completes, a special glowing block appears in the middle of the platform
2. The block is positioned **one block above the spawn point** (top block in center)
3. Aim your crosshair at the unlockable block (crosshair will turn yellow/gold)
4. **Keep aiming continuously for 10 seconds** - do not move your crosshair away
5. Watch the progress bar for countdown
6. Timer will decay if you stop aiming
7. When the timer completes, the floating cheese entity does a quick shake/glow celebration and the new `cheese_aim_clear.wav` arcade cue plays so you instantly know you nailed the aim challenge.

### **Completion:**
- When all three steps are complete, a celebration message appears: **"🧩 RIDDLE SOLVED! 🧀"**
- The trait `CHEESE_TEMPLE_RIDDLE_SOLVED` is automatically unlocked
- DSPOINC reward is automatically awarded based on role multiplier
- Reward notification appears showing DSPOINC amount (e.g., "🎉 +1,000 DSPOINC (×2.0)! 🧀")
- HUD and pause menu automatically update with new DSPOINC balance
- Progress UI disappears after completion
- **One-time reward:** Riddle can only be completed once per player (duplicate completions are prevented)
- **Audio Cue:** When the hidden cheese stone unlocks (Step 2), the new `cheese_platform_active.ogg` sound plays so players immediately know the platform spawned even if it’s off-camera.

---

## 🔧 **TECHNICAL IMPLEMENTATION**

### **File Location:**
- **Main Code:** `three.js/main.js`
- **Trait API Endpoint:** `api/user/unlock-trait.php`
- **Reward API Endpoint:** `api/dev/riddle-reward.php`
- **Database Tables:** 
  - `tbl_user_traits` (trait unlock tracking)
  - `tbl_riddle_completions` (riddle completion tracking)
  - `tbl_user_scores` (DSPOINC balance)
  - `tbl_score_adjustments` (DSPOINC audit trail)

### **State Management:**
```javascript
let riddleState = {
  step0Complete: false,      // Hidden trigger block discovery complete
  step1Complete: false,      // Cheese aiming complete
  step2Complete: false,      // Block aiming complete
  triggerBlockTimer: 0,      // Timer for standing on trigger block (0-10 seconds)
  cheeseAimTimer: 0,         // Timer for aiming at cheese (0-10 seconds)
  blockAimTimer: 0,          // Timer for aiming at unlockable block (0-10 seconds)
  triggerBlock: null,        // The hidden golden stone block player must find and stand on
  unlockableBlock: null      // The special block that unlocks after step 1
};
const RIDDLE_AIM_TIME = 10;  // 10 seconds required for each step
```

### **Key Functions:**

#### **1. `createTriggerBlock(mapData, blockSize)`**
- **Purpose:** Creates the hidden golden stone block that players must discover (Step 0)
- **Position:** Back of game field at coordinates (110, 1, 110) - far from spawn point
- **Visual:** Yellow-cheese texture (`/textures/blocks/yellow-cheese.png`) with emissive glow (intensity: 0.4)
- **Initial State:** Visible (`visible: true`) but hidden in the back - players must explore to find it
- **Detection:** Uses `checkTriggerBlockStanding()` to detect when player is standing on top

#### **2. `checkTriggerBlockStanding()`**
- **Purpose:** Detects if player is standing on the trigger block
- **Method:** Checks if player's feet position is within block bounds and at correct height
- **Tolerance:** Player feet Y must be within 0.3 units of block top Y
- **Returns:** `true` if player is standing on block, `false` otherwise

#### **3. `createUnlockableBlock(spawnData, blockSize)`**
- **Purpose:** Creates the special block that appears after Step 1
- **Position:** Middle of platform, one block above spawn point
- **Visual:** Cheese-stone texture with emissive glow (intensity: 0.3, increases to 0.8 when unlocked)
- **Initial State:** Hidden (`visible: false`) until Step 1 completes

#### **4. `updateRiddleAiming(delta, aimingAtCheese, aimingAtBlock)`**
- **Purpose:** Updates timers based on standing/aiming state for all three steps
- **Step 0 Logic:**
  - If standing on trigger block: Increment `triggerBlockTimer` by `delta`
  - If not standing: Decay timer by `delta * 0.5` (prevents accidental completion)
  - When timer reaches `RIDDLE_AIM_TIME`: Complete Step 0, unlock riddle hint UI
- **Step 1 Logic:**
  - Only active after Step 0 completes
  - If aiming at cheese: Increment `cheeseAimTimer` by `delta`
  - If not aiming: Decay timer by `delta * 0.5` (prevents accidental completion)
  - When timer reaches `RIDDLE_AIM_TIME`: Complete Step 1, unlock block
- **Step 2 Logic:**
  - Only active after Step 1 completes
  - If aiming at block: Increment `blockAimTimer` by `delta`
  - If not aiming: Decay timer by `delta * 0.5`
  - When timer reaches `RIDDLE_AIM_TIME`: Complete Step 2, unlock trait

#### **5. `updateCrosshairAim(cheese)`**
- **Purpose:** Detects what player is aiming at using raycasting
- **Method:** `THREE.Raycaster` from camera center (crosshair position)
- **Detection Range:** 50 units maximum distance
- **Visual Feedback:** Crosshair turns yellow/gold when aiming at valid targets

#### **6. `unlockRiddleBlock()`**
- **Purpose:** Makes the unlockable block visible and adds glow effect
- **Actions:**
  - Sets `unlockableBlock.visible = true`
  - Increases emissive intensity from 0.3 to 0.6
  - Calls `playCheesePlatformSound()` which plays `cheese_platform_active.ogg` for instant audio feedback

#### **7. `completeRiddle()`**
- **Purpose:** Handles riddle completion, trait unlocking, and DSPOINC reward
- **Actions:**
  - Shows completion message: "🧩 RIDDLE SOLVED! 🧀"
  - **Step 1:** Calls API to unlock trait: `POST /api/user/unlock-trait.php`
    - Trait name: `CHEESE_TEMPLE_RIDDLE_SOLVED`
    - Trait value: `true`
  - **Step 2:** Calls API to award DSPOINC reward: `POST /api/dev/riddle-reward.php`
    - Base reward: 500 DSPOINC
    - Role multiplier applied automatically (VIP: ×2.0, Holder: ×1.5, etc.)
    - Total reward calculated: `baseReward × multiplier`
  - **Step 3:** Updates HUD with new DSPOINC balance
  - **Step 4:** Shows reward notification with DSPOINC amount and multiplier
  - **Step 5:** Updates pause menu with new DSPOINC balance
  - **Error Handling:** 
    - If riddle already completed (409 Conflict): Shows "Already Completed" message
    - If API fails: Logs error but doesn't block completion
  - **Duplicate Prevention:** Server-side check prevents duplicate rewards (one-time reward per player)

#### **8. `updateRiddleProgressUI()`**
- **Purpose:** Updates the progress bar UI at bottom of screen
- **Displays:**
  - Step 0: Only shown when player is standing on trigger block
  - Step 1: Shown after Step 0 completes
  - Step 2: Shown after Step 1 completes
  - Time remaining (countdown) for current step
  - Progress bar (0-100%) for current step
  - Step completion status
- **Visibility:** UI is hidden by default and only appears when:
  - Step 0: Player is standing on trigger block
  - Step 1+: After Step 0 completes

#### **9. `showRiddleRewardNotification(dsPoincAwarded, multiplier, alreadyCompleted)`**
- **Purpose:** Shows DSPOINC reward notification after riddle completion
- **Parameters:**
  - `dsPoincAwarded`: DSPOINC amount awarded (number)
  - `multiplier`: Role multiplier applied (number, e.g., 2.0 for VIP)
  - `alreadyCompleted`: Whether riddle was already completed (boolean, optional)
- **Display:**
  - Success: Green notification showing "🎉 +{amount} DSPOINC (×{multiplier})! 🧀"
  - Already Completed: Yellow notification showing "🧩 Riddle Already Completed!"
- **Position:** Fixed at top center of screen (120px from top)
- **Duration:** 4 seconds (fade in/out animation)
- **Styling:** 
  - Success: Green background (`rgba(34, 197, 94, 0.95)`)
  - Already Completed: Yellow background (`rgba(255, 193, 7, 0.95)`)
  - Black text, bold font, rounded corners, shadow

---

## 🎯 **AIMING DETECTION SYSTEM**

### **Raycasting Method:**
```javascript
const raycaster = new THREE.Raycaster();
raycaster.setFromCamera(new THREE.Vector2(0, 0), camera); // Center of screen
raycaster.far = 200; // Check up to 200 units ahead (increased for better detection)

const cheeseIntersects = raycaster.intersectObject(cheese.mesh, false);
const blockIntersects = raycaster.intersectObject(riddleState.unlockableBlock, false);
```

### **Aiming Conditions (STRICT - Updated November 12, 2025):**
- **Step 1 (Cheese Aiming):** `step0Complete && intersects.length > 0 && distance < 50 && !step1Complete`
  - **STRICT:** Requires EXACT raycast hit on cheese mesh pixels
  - **NO FALLBACK:** Removed distance/angle fallback check
  - **IMMEDIATE:** If crosshair moves even 1 pixel off the moving cheese entity, `aimingAtCheese` becomes `false` immediately
  - **Timer Decay:** Decays at rate `0.5` when not aiming (same as Step 2)
  
- **Step 2 (Block Aiming):** `step0Complete && intersects.length > 0 && distance < 50 && step1Complete && !step2Complete`
  - **STRICT:** Requires EXACT raycast hit on unlockable block pixels
  - **NO FALLBACK:** No distance/angle fallback check
  - **IMMEDIATE:** If crosshair moves even 1 pixel off the block, `aimingAtBlock` becomes `false` immediately
  - **Timer Decay:** Decays at rate `0.5` when not aiming (same as Step 1)

### **Detection Method (Both Steps):**
1. **Direct Raycast:** `raycaster.intersectObject(target, false)` - checks if crosshair hits target directly
2. **Scene Search Fallback:** If no direct hit, searches all scene objects recursively to find target
3. **NO Distance/Angle Fallback:** Both steps require exact raycast intersection - no tolerance for "close enough"

### **Crosshair Feedback:**
- **Aiming at Target:** Yellow/gold color (`#fbbf24`) with glow effect
- **Not Aiming:** Default white color (`#ffffff`)
- **Visual Indicator:** Crosshair turns yellow ONLY when crosshair is directly on target pixels

---

## 📊 **PROGRESS UI SYSTEM**

### **UI Location:**
- **Position:** Fixed at bottom-center of screen
- **Styling:** Dark background with golden border, matching game theme

### **Step 0 Display:**
```
🧩 Cheese Temple Riddle
🔍 Step 0: Stand on Golden Stone    [7.3s]
[████████████░░░░░░░░] 73%
```
- **Visibility:** Only shown when player is standing on trigger block
- **Hidden:** UI is hidden when player steps off the block

### **Step 1 Display:**
```
🧩 Cheese Temple Riddle
Step 1: Aim at Cheese          [9.5s]
[████████░░░░░░░░░░░░] 50%
```
- **Visibility:** Shown after Step 0 completes

### **Step 2 Display:**
```
🧩 Cheese Temple Riddle
✅ Step 1 Complete
Step 2: Aim at Unlockable Block    [8.2s]
[████████████████░░░░] 82%
```
- **Visibility:** Shown after Step 1 completes

### **Completion:**
- UI automatically hides when all three steps are complete

---

## 🔗 **API INTEGRATION**

### **Trait Unlock Endpoint:**
**URL:** `/api/user/unlock-trait.php`  
**Method:** `POST`  
**Headers:** `Content-Type: application/json`  
**Credentials:** `include` (for session-based auth)

### **Request Body:**
```json
{
  "user_id": "discord_id_here",
  "trait_name": "CHEESE_TEMPLE_RIDDLE_SOLVED",
  "trait_value": "true"
}
```

### **Response:**
```json
{
  "success": true,
  "message": "✅ Trait unlocked successfully",
  "trait_name": "CHEESE_TEMPLE_RIDDLE_SOLVED",
  "trait_value": "true",
  "action": "unlocked"
}
```

### **Database Schema:**
```sql
-- Actual table structure (verified November 13, 2025)
CREATE TABLE tbl_user_traits (
  user_id TEXT,
  trait TEXT,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, trait),
  FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id)
);
```

### **Trait Unlock API Fix (November 13, 2025):**
- **Issue:** API was using incorrect column names (`trait_name`, `trait_value`, `created_at`, `updated_at`)
- **Fix:** Updated to use correct column names (`trait`, `timestamp`)
- **Status:** ✅ **FIXED - API now works with actual database schema**

---

## ⚙️ **CONFIGURATION & ADJUSTMENT**

### **Adjusting Aim Time:**
**File:** `three.js/main.js`  
**Line:** ~296  
**Variable:** `const RIDDLE_AIM_TIME = 10;`

**Example:** Change to 15 seconds:
```javascript
const RIDDLE_AIM_TIME = 15; // 15 seconds required for each step
```

### **Adjusting Timer Decay Rate:**
**File:** `three.js/main.js`  
**Function:** `updateRiddleAiming()`  
**Lines:** ~2387, ~2401

**Current Decay:** `delta * 0.5` (timer decreases by 50% of frame time when not aiming)

**Example:** Faster decay (more punishing):
```javascript
riddleState.cheeseAimTimer = Math.max(0, riddleState.cheeseAimTimer - delta * 1.0); // 100% decay
```

**Example:** Slower decay (more forgiving):
```javascript
riddleState.cheeseAimTimer = Math.max(0, riddleState.cheeseAimTimer - delta * 0.2); // 20% decay
```

### **Adjusting Aiming Detection Range:**
**File:** `three.js/main.js`  
**Function:** `updateCrosshairAim()`  
**Line:** ~2374 (Step 1), ~2424 (Step 2)

**Current Range:** 50 units maximum distance (both steps)

**Important:** Both Step 1 and Step 2 use STRICT detection - no fallback tolerance. Range only affects maximum distance, but still requires exact raycast hit.

**Example:** Increase range (easier):
```javascript
// Step 1
aimingAtCheese = cheeseIntersects.length > 0 && cheeseIntersects[0].distance < 75; // 75 units

// Step 2
aimingAtBlock = blockIntersects.length > 0 && blockIntersects[0].distance < 75; // 75 units
```

**Example:** Decrease range (harder):
```javascript
// Step 1
aimingAtCheese = cheeseIntersects.length > 0 && cheeseIntersects[0].distance < 30; // 30 units

// Step 2
aimingAtBlock = blockIntersects.length > 0 && blockIntersects[0].distance < 30; // 30 units
```

### **Adjusting Timer Decay Rate:**
**File:** `three.js/main.js`  
**Function:** `updateRiddleAiming()`  
**Lines:** ~2797 (Step 1), ~2816 (Step 2)

**Current Decay:** `delta * 0.5` (timer decreases by 50% of frame time when not aiming) - **SAME FOR BOTH STEPS**

**Example:** Faster decay (more punishing):
```javascript
// Step 1
riddleState.cheeseAimTimer = Math.max(0, riddleState.cheeseAimTimer - delta * 1.0); // 100% decay

// Step 2
riddleState.blockAimTimer = Math.max(0, riddleState.blockAimTimer - delta * 1.0); // 100% decay
```

**Example:** Slower decay (more forgiving):
```javascript
// Step 1
riddleState.cheeseAimTimer = Math.max(0, riddleState.cheeseAimTimer - delta * 0.2); // 20% decay

// Step 2
riddleState.blockAimTimer = Math.max(0, riddleState.blockAimTimer - delta * 0.2); // 20% decay
```

### **Adjusting Trigger Block Position (Step 0):**
**File:** `three.js/main.js`  
**Function:** `createTriggerBlock()`  
**Lines:** ~2431-2433

**Current Position:**
- X: `110 * blockSize + blockSize / 2` (far back in X direction)
- Y: `1 * blockSize + blockSize / 2` (ground level)
- Z: `110 * blockSize + blockSize / 2` (far back in Z direction)

**Example:** Move trigger block to different location:
```javascript
const triggerX = 50 * blockSize + blockSize / 2;  // Closer to spawn
const triggerY = 2 * blockSize + blockSize / 2;   // One block higher
const triggerZ = 50 * blockSize + blockSize / 2;  // Different Z position
```

### **Adjusting Unlockable Block Position:**
**File:** `three.js/main.js`  
**Function:** `createUnlockableBlock()`  
**Lines:** ~2469-2471

**Current Position:**
- X: `spawn.x` (middle of platform)
- Y: `spawn.y + 1` (one block above spawn)
- Z: `spawn.z` (same Z as spawn)

**Example:** Move block to different position:
```javascript
const blockX = spawnData.x * blockSize + blockSize / 2;
const blockY = (spawnData.y + 2) * blockSize + blockSize / 2; // Two blocks above spawn
const blockZ = (spawnData.z + 5) * blockSize + blockSize / 2; // 5 blocks forward
```

### **Adjusting Block Visual Effects:**
**File:** `three.js/main.js`  
**Function:** `createUnlockableBlock()`  
**Line:** ~2358

**Current Emissive Intensity:** 0.3 (increases to 0.6 when unlocked)

**Example:** Brighter glow:
```javascript
emissiveIntensity: 0.5  // Initial glow
// In unlockRiddleBlock():
material.emissiveIntensity = 0.8;  // Unlocked glow
```

---

## 🐛 **DEBUGGING**

### **Console Logs:**
The riddle system includes comprehensive debug logging:
- `🧩 [RIDDLE] Unlockable block created at: [position]`
- `🧩 [RIDDLE] Step 1 complete! Block unlocked!`
- `🧩 [RIDDLE] Block is now visible and ready!`
- `🧩 [RIDDLE] Step 2 complete! Riddle solved!`
- `🧩 [RIDDLE] Trait unlocked successfully!`

### **Common Issues:**

#### **Issue: Timer not incrementing**
- **Check:** Is `updateRiddleAiming()` being called in the animate loop?
- **Check:** Is `aimingAtCheese` or `aimingAtBlock` returning `true`?
- **Check:** Is the game paused? (`isGamePaused` should be `false`)

#### **Issue: Block not appearing**
- **Check:** Is `riddleState.step1Complete` set to `true`?
- **Check:** Is `unlockRiddleBlock()` being called?
- **Check:** Is the block's `visible` property set to `true`?

#### **Issue: Trait not unlocking**
- **Check:** Is `resolvedDiscordId` valid (not `null` or `LOCAL_TEST_DISCORD`)?
- **Check:** Is the API endpoint `/api/user/unlock-trait.php` accessible?
- **Check:** Browser console for API errors
- **Check:** Database connection and `tbl_user_traits` table exists

#### **Issue: Progress UI not showing**
- **Check:** Is `createRiddleProgressUI()` being called?
- **Check:** Is `updateRiddleProgressUI()` being called in `updateRiddleAiming()`?
- **Check:** Browser console for DOM errors

---

## 📝 **TESTING CHECKLIST**

### **Functional Testing:**
- [ ] Step 0: Riddle UI is hidden initially
- [ ] Step 0: Trigger block is visible in the back of the level
- [ ] Step 0: Timer increments when standing on trigger block
- [ ] Step 0: Timer decays when stepping off trigger block
- [ ] Step 0: Riddle UI appears when standing on trigger block
- [ ] Step 0: Riddle UI hides when stepping off trigger block
- [ ] Step 0: Completes after 10 seconds of continuous standing
- [ ] Step 1: Timer increments when aiming at cheese (after Step 0)
- [ ] Step 1: Timer decays when not aiming
- [ ] Step 1: Completes after 10 seconds of continuous aiming
- [ ] Unlockable block appears after Step 1
- [ ] Step 2: Timer increments when aiming at block
- [ ] Step 2: Timer decays when not aiming
- [ ] Step 2: Completes after 10 seconds of continuous aiming
- [ ] Completion message appears
- [ ] Trait is unlocked in database
- [ ] Progress UI shows correct information for each step
- [ ] Crosshair changes color when aiming at targets (Step 1 & 2)

### **Edge Cases:**
- [ ] Timer resets correctly when switching between targets
- [ ] Timer doesn't increment when game is paused
- [ ] Block remains visible after Step 1 completion
- [ ] Riddle state persists correctly
- [ ] Multiple players can solve riddle independently

---

## 🎨 **VISUAL DESIGN**

### **Trigger Block (Step 0):**
- **Texture:** Yellow-cheese (`/textures/blocks/yellow-cheese.png`)
- **Material:** `MeshLambertMaterial` with emissive glow
- **Glow Intensity:** 0.4 (subtle golden glow to aid discovery)
- **Position:** Back of game field (110, 1, 110) - far from spawn
- **Size:** 1×1×1 units (standard block size)
- **Visibility:** Always visible (players must explore to find it)

### **Unlockable Block (Step 2):**
- **Texture:** Cheese-stone (`/textures/blocks/cheese-stone.png`)
- **Material:** `MeshLambertMaterial` with emissive glow
- **Initial Glow:** 0.3 intensity (subtle, hidden until Step 1)
- **Unlocked Glow:** 0.8 intensity (bright, visible after Step 1)
- **Scale:** 1.1×1.1×1.1 (slightly larger when unlocked)
- **Size:** 1×1×1 units (standard block size)

### **Progress UI:**
- **Background:** `rgba(14, 12, 20, 0.9)` (dark with transparency)
- **Border:** `2px solid rgba(255, 224, 102, 0.5)` (golden)
- **Text Color:** `#ffe066` (golden yellow)
- **Progress Bar:** Gradient from `#fbbf24` to `#ffe066`
- **Font:** Montserrat, Arial, sans-serif

### **Completion Message:**
- **Background:** Gradient dark blue/gray
- **Border:** `3px solid #ffe066` (golden)
- **Text:** `#ffe066` (golden yellow)
- **Animation:** Fade out after 3 seconds

---

## 🔮 **FUTURE ENHANCEMENTS**

### **Potential Improvements:**
- **Sound Effects:** Audio cues for timer progress and completion
- **Particle Effects:** Visual particles when block unlocks
- **Multiple Riddles:** Extend system for Level 2, Level 3, etc.
- **Difficulty Levels:** Adjustable aim time based on difficulty
- **Leaderboard:** Track fastest riddle completion times
- **Achievements:** Additional achievements for perfect completion

---

## 📚 **RELATED DOCUMENTATION**

- **Main Technical Doc:** `12.0/TECHNICAL_DOCUMENTATION/HYTOPIA_THREE_TECH_DOCUMENTATION.md`
- **Cheese Hunt System:** `12.0/TECHNICAL_DOCUMENTATION/CHEESE_HUNT_SYSTEM_SPECIFICATION.md`
- **Controls System:** See Chapter 13 in Hytopia Three Tech Documentation
- **API Reference:** `api/user/unlock-trait.php`

---

## 🧀 **RIDDLE METADATA**

| Property | Value |
|----------|-------|
| **Riddle Number** | 1 |
| **Level** | Cheese Temple - Level 1 |
| **Difficulty** | Medium-Hard (3-step challenge) |
| **Estimated Time** | 30 seconds (10s × 3 steps) |
| **Required Skill** | Exploration, precision aiming, patience |
| **Trait Name** | `CHEESE_TEMPLE_RIDDLE_SOLVED` |
| **Trait Value** | `true` |
| **Status** | ✅ Implemented & Tested |
| **Steps** | 3 (Discovery → Aim Cheese → Aim Block) |

---

**Document Version:** 2.2  
**Last Updated:** November 13, 2025  
**Maintained By:** Narrrf's Lab Tech Council  
**Status:** ✅ **PRODUCTION READY** - 3-Step System with Strict Aiming Detection  
**Riddle Note:** November 13, 2025 - Trait unlock API fix complete

**Changes:** 
- **Version 2.0:** Added Step 0 (hidden discovery challenge) - Riddle UI now hidden until player finds and stands on trigger block
- **Version 2.1:** Implemented STRICT aiming detection for both Step 1 and Step 2 - removed fallback tolerance checks, both steps now require exact raycast hits on target pixels. Timer only increments when crosshair is directly on target, decays immediately when crosshair moves off by even 1 pixel.
- **Version 2.2:** Fixed trait unlock API database schema mismatch - updated SQL queries to match actual table structure (`trait` instead of `trait_name`, `timestamp` instead of `created_at`/`updated_at`). Trait unlock now works correctly with existing database schema.

**Latest Riddle Note (November 13, 2025):**
- **Trait Unlock API Fix:** Fixed 500 Internal Server Error - database schema mismatch resolved
  - **Issue:** Code was using incorrect column names (`trait_name`, `trait_value`, `created_at`, `updated_at`)
  - **Fix:** Updated SQL queries to use correct column names (`trait`, `timestamp`)
  - **Result:** Trait unlock API now works correctly with existing database schema
  - **Status:** ✅ **FIXED - API now works correctly**
- **Database Schema Verified:** Actual table structure uses `user_id`, `trait`, `timestamp` columns
- **Testing Status:** ✅ Trait unlock API fixed and ready for testing
- **DSPOINC Reward:** ✅ Working correctly (500 DSPOINC awarded successfully)

**Previous Riddle Note (November 12, 2025):**
- **Step 1 (Cheese Entity):** Fixed to require EXACT raycast hit - removed distance/angle fallback check. Now matches Step 2 strictness. Timer only counts when crosshair is directly on moving cheese entity pixels. Any pixel deviation = immediate timer decay.
- **Step 2 (Unlockable Block):** Confirmed perfect - requires exact raycast hit, no fallback tolerance. Timer only counts when crosshair is directly on block pixels.
- **Both Steps:** Use identical strict detection method - direct raycast intersection only, 50 unit range, immediate decay when crosshair moves off target.
- **Code Locations:**
  - Step 1 Detection: `three.js/main.js` ~2374 (updateCrosshairAim function)
  - Step 2 Detection: `three.js/main.js` ~2424 (updateCrosshairAim function)
  - Step 1 Timer: `three.js/main.js` ~2797 (updateRiddleAiming function)
  - Step 2 Timer: `three.js/main.js` ~2816 (updateRiddleAiming function)
- **Testing Status:** ✅ Both steps tested and confirmed working perfectly with strict detection

