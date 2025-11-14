# 🧩 RIDDLE #2 - CHEESE TEMPLE LEVEL 1

**Document Created:** November 13, 2025  
**Riddle ID:** `CHEESE_TEMPLE_RIDDLE_02`  
**Level:** Cheese Temple - Level 1 (Same Map)  
**Status:** 🟡 **IMPLEMENTATION IN PROGRESS** - Block Movement Physics Implemented  
**Trait Unlocked:** `CHEESE_TEMPLE_RIDDLE_02_SOLVED`  
**Last Updated:** November 13, 2025

---

## 📋 **RIDDLE OVERVIEW**

### **Objective:**
🧀 **RIDDLE #2 - MOVE THE CHEESE STONE TO THE OAK STONE** 🧀

This riddle is implemented in the same Cheese Temple map (level1.json) as Riddle #1, providing players with a second challenge that requires moving the unlockable block (Cheese Stone) from Riddle #1 to a special blinking oak stone.

### **Riddle Description:**
After completing Riddle #1, players must:
1. **Step 1:** Move the Cheese Stone (the unlockable block from Riddle #1) to a special blinking oak stone located on the middle platform.
2. **Step 2:** Aim at the floating cheese entity for 10 seconds to complete the riddle.

### **Reward:**
- **Trait Unlocked:** `CHEESE_TEMPLE_RIDDLE_02_SOLVED` (value: `true`)
- **DSPOINC Reward:** 500 DSPOINC base reward
  - Role multipliers are applied automatically (VIP: ×2.0, Holder: ×1.5, etc.)
- **Visual Feedback:** Completion message, reward notification, and visual effects
- **Progress Tracking:** Real-time progress UI with distance and blinking status for Step 1, timer for Step 2
- **HUD Update:** DSPOINC balance automatically updated in pause menu and HUD

---

## 🎮 **HOW TO SOLVE**

### **Step 1: Move the Cheese Stone to the Oak Stone**
1. Complete Riddle #1 first (all 3 steps).
2. After Riddle #1 completion, the oak stone will appear on the middle platform (near spawn, offset by 3 blocks in X direction).
3. The oak stone blinks every 15 seconds for 2 seconds (glowing effect).
4. Walk up to the Cheese Stone (unlockable block from Riddle #1) - it should be visible at its original position.
5. Push the Cheese Stone by moving into it while pressing W/A/S/D keys.
6. Guide the Cheese Stone to the oak stone location.
7. When the Cheese Stone is within 1.5 units of the oak stone, Step 1 completes automatically.
8. The Cheese Stone will snap to the oak stone position and lock in place.
9. The oak stone will glow permanently to indicate completion.

### **Step 2: Hunt the Cheese**
1. After Step 1 completes, aim at the floating cheese entity with your crosshair.
2. Keep the crosshair on the cheese for 10 seconds continuously.
3. If you move the crosshair away, the timer decays at 0.5× speed.
4. Once the timer reaches 10 seconds, Riddle #2 completes.
5. You will receive the trait unlock and DSPOINC reward.

### **Debug Shortcut for Testing:**
- Press **Shift+K** or **Ctrl+K** to skip Riddle #1 and test Riddle #2 directly.
- This makes the unlockable block and oak stone visible immediately.

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
  // Riddle #1 state
  step0Complete: false,
  step1Complete: false,
  step2Complete: false,
  triggerBlock: null,
  unlockableBlock: null,
  // Riddle #2 state
  riddle2: {
    step1Complete: false,  // Cheese stone moved to oak stone complete
    step2Complete: false,  // Cheese aiming complete
    oakStone: null,        // The blinking oak stone block on middle platform
    oakStoneBlinkTimer: 0, // Timer for oak stone blinking (0-15 seconds)
    cheeseAimTimer: 0,     // Timer for aiming at cheese (0-10 seconds)
    unlockableBlockVelocity: new THREE.Vector3(0, 0, 0), // Velocity for movable cheese stone
    unlockableBlockOriginalPosition: null // Original position of unlockable block
  }
};

// Constants
const RIDDLE_AIM_TIME = 10; // 10 seconds required for each step
const RIDDLE2_OAK_STONE_BLINK_INTERVAL = 15; // Oak stone blinks every 15 seconds
const RIDDLE2_OAK_STONE_BLINK_DURATION = 2; // Blink duration: 2 seconds
const RIDDLE2_PROXIMITY_THRESHOLD = 1.5; // Distance threshold for cheese stone on oak stone (1.5 units)
```

### **Key Functions:**

#### **1. `createRiddle2OakStone(spawnData, blockSize)`**
- **Purpose:** Creates the special blinking oak stone block for Riddle #2.
- **Position:** Middle platform, offset by 3 blocks in X direction from spawn.
- **Visual:** Oak planks texture (`/textures/blocks/oak-planks.png`) with emissive glow that blinks.
- **Initial State:** Hidden until Riddle #1 is complete (`riddleState.step2Complete === true`).

#### **2. `updateRiddle2(delta, aimingAtCheese)`**
- **Purpose:** Manages Riddle #2 logic, including oak stone blinking, movable block physics, and proximity detection.
- **Oak Stone Blinking:** Controls `emissiveIntensity` of `riddle2.oakStone` to create a blinking effect every `RIDDLE2_OAK_STONE_BLINK_INTERVAL` seconds for `RIDDLE2_OAK_STONE_BLINK_DURATION` seconds.
- **Movable Block Physics:** Applies player-induced push force, friction, and clamps Y position for `riddleState.unlockableBlock`.
- **Proximity Detection:** Checks if `riddleState.unlockableBlock` is within `RIDDLE2_PROXIMITY_THRESHOLD` of `riddle2.oakStone`.
- **Step 1 Completion:** If block is on oak stone, `riddle2.step1Complete` is set to `true`, block is locked in place, and oak stone glows permanently.
- **Step 2 Logic:** If `riddle2.step1Complete` is true, it checks `aimingAtCheese` to increment `riddle2.cheeseAimTimer`.

#### **3. `completeRiddle2()`**
- **Purpose:** Handles Riddle #2 completion, trait unlocking, and DSPOINC reward.
- **Actions:**
  - Shows completion message: "🧩 RIDDLE #2 SOLVED! 🧀"
  - Calls API to unlock trait: `POST /api/user/unlock-trait.php` with `trait_name: 'CHEESE_TEMPLE_RIDDLE_02_SOLVED'`
  - Calls API to award DSPOINC reward: `POST /api/dev/riddle-reward.php` with `riddle_id: 'CHEESE_TEMPLE_RIDDLE_02'`
  - Updates HUD and shows reward notification.

#### **4. `showRiddle2CompletionMessage()`**
- **Purpose:** Displays a specific completion message for Riddle #2.

#### **5. `skipRiddle1ForTesting()` (Debug Function)**
- **Purpose:** Skips Riddle #1 for testing Riddle #2 directly.
- **Trigger:** Press **Shift+K** or **Ctrl+K**.
- **Actions:**
  - Marks all Riddle #1 steps as complete.
  - Makes unlockable block and oak stone visible.
  - Activates Riddle #2 for testing.

---

## 🎯 **BLOCK MOVEMENT PHYSICS SYSTEM**

### **Push Mechanics:**
- **Push Distance:** 2.0 units (player must be within 2.0 units of block to push it)
- **Push Force:** 25.0 × push strength (scales with distance, stronger when closer)
- **Push Direction:** Combines player movement direction with direction from player to block
- **Friction:** 0.95 (5% reduction per frame at 60fps, very low friction for responsive movement)
- **Movement Threshold:** 0.001 (allows very small movements)

### **Push Physics Implementation:**
1. **Player Movement Detection:** Checks if player is pressing W/A/S/D keys.
2. **World Space Calculation:** Converts player input to world space movement direction using camera forward/side vectors.
3. **Push Force Application:** Applies push force to block velocity based on:
   - Player movement direction (primary)
   - Direction from player to block (secondary, 30% influence)
   - Distance-based strength (1.0 when touching, 0.0 at max distance)
4. **Friction Application:** Applies frame-rate independent friction to slow block down.
5. **Position Update:** Updates block position based on velocity, clamped to level bounds.

### **Collision Detection:**
- **Y Position Clamping:** Block Y position clamped to ground level (1.5) to prevent falling.
- **Level Bounds:** Block clamped to level bounds (-10 to 130 in X and Z) to prevent going outside map.
- **Velocity Zeroing:** Horizontal velocity zeroed when block hits bounds.

### **Proximity Detection:**
- **Threshold:** 1.5 units (block must be within 1.5 units of oak stone)
- **Completion:** When block is within threshold, Step 1 completes automatically.
- **Block Locking:** Block velocity is zeroed and position is snapped to oak stone.
- **Visual Feedback:** Oak stone glows permanently (emissiveIntensity: 0.8) to indicate completion.

## 🎯 **AIMING DETECTION SYSTEM**

### **Step 2: Cheese Aiming**
- **Method:** Reuses Riddle #1 Step 1 cheese aiming system.
- **Raycasting:** Uses Three.js raycaster to detect if crosshair is aiming at cheese entity.
- **Timer:** 10 seconds required for completion.
- **Decay:** Timer decays at 0.5× speed when not aiming at cheese.
- **Detection:** Only active when Riddle #1 is complete and Riddle #2 Step 1 is complete.

### **Raycasting Method:**
- **Primary:** Direct raycast to cheese mesh.
- **Fallback:** Raycast to all scene objects, then check if hit object is cheese.
- **Distance Check:** Cheese must be within 50 units for detection.
- **Strict Detection:** Same strict detection as Riddle #1 Step 1 (no tolerance).

---

## 📊 **PROGRESS UI SYSTEM**

### **UI Location:**
- **Position:** Fixed at bottom-center of screen (same as Riddle #1)
- **Styling:** Dark background with golden border, matching game theme
- **Differentiation:** Shows "🧩 Cheese Temple Riddle #2" title to distinguish from Riddle #1

### **Progress Display:**

#### **Step 1: Move Cheese Stone to Oak Stone**
- **Title:** "🧩 Cheese Temple Riddle #2"
- **Step 1 Text:** "Step 1: Move Cheese Stone to Oak Stone" with blinking status ("⚡ BLINKING!" when oak stone is blinking)
- **Distance Display:** Shows distance to oak stone in real-time (e.g., "3.2 units away")
- **Progress Bar:** Visual indicator showing blinking status (opacity changes based on blink state)

#### **Step 2: Aim at Cheese**
- **Step 1 Status:** "✅ Step 1 Complete"
- **Step 2 Text:** "Step 2: Aim at Cheese" with countdown timer (e.g., "8.5s")
- **Progress Bar:** Visual progress bar showing timer progress (0% to 100%)

#### **Completion:**
- **Progress UI:** Hidden when Riddle #2 is complete
- **Completion Message:** Shows "🧩 RIDDLE #2 SOLVED! 🧀" message for 3 seconds
- **Reward Notification:** Shows DSPOINC reward notification

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
  "trait_name": "CHEESE_TEMPLE_RIDDLE_02_SOLVED",
  "trait_value": "true"
}
```

### **Response:**
```json
{
  "success": true,
  "message": "✅ Trait unlocked successfully",
  "trait_name": "CHEESE_TEMPLE_RIDDLE_02_SOLVED",
  "trait_value": "true",
  "action": "unlocked"
}
```

### **Database Schema:**
```sql
-- Same table structure as Riddle #1
CREATE TABLE tbl_user_traits (
  user_id TEXT,
  trait TEXT,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, trait),
  FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id)
);
```

### **Reward API Endpoint:**
**URL:** `/api/dev/riddle-reward.php`  
**Method:** `POST`  
**Headers:** `Content-Type: application/json`  
**Credentials:** `include` (for session-based auth)

### **Request Body:**
```json
{
  "discord_id": "discord_id_here",
  "discord_name": "player_name_here",
  "riddle_id": "CHEESE_TEMPLE_RIDDLE_02",
  "level_id": "CHEESE_TEMPLE_LVL1",
  "base_reward": 500,
  "session_id": "session_id_here"
}
```

### **Response:**
```json
{
  "success": true,
  "message": "✅ Riddle completed successfully",
  "riddle_id": "CHEESE_TEMPLE_RIDDLE_02",
  "base_reward": 500,
  "multiplier": 2.0,
  "total_reward": 1000,
  "dsPoincAwarded": 1000,
  "alreadyCompleted": false
}
```

---

## ⚙️ **CONFIGURATION & ADJUSTMENT**

### **Riddle #2 Specific Constants:**
- `RIDDLE2_OAK_STONE_BLINK_INTERVAL`: Time in seconds between blinks (15 seconds)
- `RIDDLE2_OAK_STONE_BLINK_DURATION`: Duration of the blink in seconds (2 seconds)
- `RIDDLE2_PROXIMITY_THRESHOLD`: Distance in units for the cheese stone to be considered "on" the oak stone (1.5 units)
- `pushDistance`: Distance threshold for player to push block (2.0 units)
- `pushForce`: Base push force multiplier (25.0)
- `friction`: Friction multiplier for block movement (0.95 = 5% reduction per frame)

### **Adjustable Parameters:**
- **Push Force:** Increase `pushForce` in `updateRiddle2()` for stronger push (default: 25.0)
- **Friction:** Decrease `friction` for more friction, increase for less friction (default: 0.95)
- **Push Distance:** Increase `pushDistance` for larger push range (default: 2.0)
- **Proximity Threshold:** Increase `RIDDLE2_PROXIMITY_THRESHOLD` for easier completion (default: 1.5)
- **Blink Interval:** Adjust `RIDDLE2_OAK_STONE_BLINK_INTERVAL` for different blink timing (default: 15)
- **Blink Duration:** Adjust `RIDDLE2_OAK_STONE_BLINK_DURATION` for different blink length (default: 2)

---

## 🐛 **DEBUGGING**

### **Console Logs:**

#### **Block Physics Debug:**
- **Trigger:** When player is near block (within push distance)
- **Frequency:** 10% chance per frame when near block
- **Logs:**
  - Block visibility, position, velocity
  - Player position, distance to block
  - Movement state (forward, backward, left, right)
  - Step completion status

#### **Push Application Debug:**
- **Trigger:** When significant push is applied (> 0.1 units)
- **Frequency:** 10% chance when pushing significantly
- **Logs:**
  - Push force, push strength, push vector
  - Push direction, world movement direction
  - Velocity after push, distance to block

#### **Block Movement Debug:**
- **Trigger:** When block is moving (> 0.01 units per frame)
- **Frequency:** 5% chance when moving
- **Logs:**
  - Block velocity, velocity length
  - Move delta, old position, new position

#### **Debug Shortcut:**
- **Key:** Shift+K or Ctrl+K
- **Action:** Skips Riddle #1 and activates Riddle #2 for testing
- **Logs:** "🧪 [DEBUG] Skipping Riddle #1 for testing Riddle #2..."

### **Common Issues:**

#### **Block Not Moving:**
- **Cause:** Player not within push distance (2.0 units)
- **Solution:** Move closer to block
- **Debug:** Check console logs for `distanceToBlock` and `pushDistance`

#### **Block Moving Too Slowly:**
- **Cause:** Friction too high or push force too low
- **Solution:** Increase `pushForce` or decrease `friction` in `updateRiddle2()`
- **Debug:** Check console logs for `pushForce` and `velocityLength`

#### **Block Not Completing Step 1:**
- **Cause:** Block not within proximity threshold (1.5 units)
- **Solution:** Move block closer to oak stone
- **Debug:** Check console logs for distance between block and oak stone

#### **Oak Stone Not Blinking:**
- **Cause:** Riddle #1 not complete (`riddleState.step2Complete === false`)
- **Solution:** Complete Riddle #1 first, or use debug shortcut (Shift+K)
- **Debug:** Check console logs for `step2Complete` status

#### **Block Falling Through Floor:**
- **Cause:** Y position not clamped correctly
- **Solution:** Block Y position should be clamped to 1.5 (ground level)
- **Debug:** Check console logs for block Y position

#### **Block Going Outside Map:**
- **Cause:** Level bounds not enforced
- **Solution:** Block position should be clamped to -10 to 130 in X and Z
- **Debug:** Check console logs for block position and bounds

---

## 📝 **TESTING CHECKLIST**

### **Functional Testing:**
- [x] Riddle #2 only activates after Riddle #1 is complete
- [x] The oak stone appears and is visible after Riddle #1 completion
- [x] The oak stone blinks every 15 seconds for 2 seconds
- [x] The cheese stone (unlockable block from Riddle #1) is movable by player physics push
- [x] The cheese stone stops moving and snaps to the oak stone when placed within the proximity threshold
- [x] The oak stone glows permanently after Step 1 completion
- [x] Step 2: Aiming at the cheese entity increments the timer
- [x] Step 2: Timer decays when not aiming at the cheese
- [x] Riddle #2 completes after 10 seconds of continuous aiming at cheese
- [x] `CHEESE_TEMPLE_RIDDLE_02_SOLVED` trait is unlocked in the database
- [x] DSPOINC reward is awarded for Riddle #2
- [x] Riddle #2 progress UI displays correctly
- [x] Debug shortcut (Shift+K) works for testing

### **Edge Cases:**
- [ ] Player pushes block off the map (should reset or be recoverable)
- [ ] Player tries to complete Riddle #2 before Riddle #1 (should be prevented)
- [ ] Multiple players interacting with the movable block (if multiplayer is considered)
- [ ] Block gets stuck in geometry (should be prevented by bounds clamping)
- [ ] Block moves too fast (should be limited by friction)
- [ ] Block doesn't respond to push (should be fixed by increased push force)

### **Performance Testing:**
- [x] Block physics doesn't cause frame rate drops
- [x] Debug logging doesn't spam console (limited to 5-10% chance per frame)
- [x] Oak stone blinking doesn't cause performance issues
- [x] Proximity detection doesn't cause performance issues

---

## 🎨 **VISUAL DESIGN**

### **Oak Stone:**
- **Texture:** Oak planks (`/textures/blocks/oak-planks.png`)
- **Material:** `MeshLambertMaterial` with emissive glow
- **Blinking Glow:** `emissiveIntensity` pulses from 0.0 to 1.0 during blink (pulsing effect with 4 pulses per blink), 0.0 otherwise
- **Completion Glow:** `emissiveIntensity` set to 0.8 permanently after Step 1
- **Position:** Middle platform, offset by 3 blocks in X direction from spawn
- **Size:** Standard block size (1×1×1 units)

### **Movable Cheese Stone:**
- **Texture:** Cheese stone (`/textures/blocks/cheese-stone.png`)
- **Material:** `MeshLambertMaterial` with emissive glow (from Riddle #1)
- **Visual:** Retains its glow and appearance from Riddle #1
- **Physics:** Can be pushed by player movement
- **Position:** Original position from Riddle #1 (unlocks after Riddle #1 Step 1)

### **Progress UI:**
- **Step 1:** Shows "Step 1: Move Cheese Stone to Oak Stone" with distance and blinking status
- **Step 2:** Shows "Step 2: Aim at Cheese" with countdown timer
- **Styling:** Dark background with golden border, matching Riddle #1 UI
- **Title:** "🧩 Cheese Temple Riddle #2" to distinguish from Riddle #1

### **Completion Message:**
- **Text:** "🧩 RIDDLE #2 SOLVED! 🧀"
- **Style:** Centered, large font, golden color, glowing border
- **Duration:** 3 seconds
- **Animation:** Fade out after 3 seconds

---

## 🔮 **FUTURE ENHANCEMENTS**

### **Potential Improvements:**
- Sound effects for riddle progress and completion
- Particle effects for visual feedback
- Additional achievements for perfect completion
- Leaderboard integration for fastest completion times

---

## 📚 **RELATED DOCUMENTATION**

- **Main Technical Doc:** `12.0/TECHNICAL_DOCUMENTATION/HYTOPIA_THREE_TECH_DOCUMENTATION.md`
- **Riddle #1:** `RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md`
- **Cheese Hunt System:** `12.0/TECHNICAL_DOCUMENTATION/CHEESE_HUNT_SYSTEM_SPECIFICATION.md`
- **Controls System:** See Chapter 13 in Hytopia Three Tech Documentation
- **API Reference:** `api/user/unlock-trait.php`, `api/dev/riddle-reward.php`

---

## 🧀 **RIDDLE METADATA**

| Property | Value |
|----------|-------|
| **Riddle Number** | 2 |
| **Level** | Cheese Temple - Level 1 (Same Map) |
| **Difficulty** | *[To be determined]* |
| **Estimated Time** | *[To be determined]* |
| **Required Skill** | *[To be determined]* |
| **Trait Name** | `CHEESE_TEMPLE_RIDDLE_02_SOLVED` |
| **Trait Value** | `true` |
| **Status** | 🔄 **PLANNING** - Awaiting Implementation Details |
| **Steps** | *[To be determined]* |

---

## 📝 **IMPLEMENTATION NOTES**

### **Same Map as Riddle #1:**
- Riddle #2 will be implemented in the same Cheese Temple map (level1.json)
- Players can complete both Riddle #1 and Riddle #2 in the same level
- Riddles are independent - completing one doesn't affect the other
- Each riddle has its own state management and progress tracking

### **Integration Points:**
- **State Management:** Will need separate state object or extend existing `riddleState`
- **UI System:** Will need separate progress UI or extend existing UI system
- **Detection System:** Will reuse existing raycasting and detection systems
- **API Integration:** Will use same API endpoints (different riddle_id)

### **Implementation Status:**
1. ✅ **Documentation Created:** Riddle #2 documentation structure created
2. ✅ **Implementation:** Block movement physics, oak stone blinking, proximity detection implemented
3. ✅ **Testing:** Debug shortcut (Shift+K) added for testing
4. 🟡 **Testing In Progress:** Block movement physics being tested and refined
5. ⏳ **Final Testing:** Full riddle completion flow testing pending
6. ⏳ **Production Ready:** Awaiting final testing and approval

### **Recent Changes (November 13, 2025):**
- **Block Movement Physics:** Implemented push mechanics with player movement detection
- **Push Force:** Increased from 8.0 to 25.0 for stronger push
- **Friction:** Reduced from 0.9 to 0.95 for more responsive movement
- **Push Distance:** Increased from 1.5 to 2.0 units for easier pushing
- **Debug Logging:** Added comprehensive debug logging for block physics
- **Debug Shortcut:** Added Shift+K / Ctrl+K to skip Riddle #1 for testing
- **Movement Threshold:** Lowered from 0.01 to 0.001 to allow smaller movements
- **Bounds Clamping:** Added level bounds clamping (-10 to 130 in X and Z)
- **Y Position Clamping:** Added ground level clamping (Y: 1.5) to prevent falling

---

**Document Version:** 2.1  
**Last Updated:** November 13, 2025  
**Maintained By:** Narrrf's Lab Tech Council  
**Status:** ✅ **SUCCESSFULLY IMPLEMENTED AND TESTED**

**Riddle Note:** November 13, 2025 - Riddle #2 successfully implemented and tested! Block movement physics, oak stone blinking, proximity detection, and API integration all working correctly. Database verification confirms trait unlock (`CHEESE_TEMPLE_RIDDLE_02_SOLVED`) and DSPOINC reward (500 DSPOINC) recorded successfully. Completion recorded at 2025-11-13 06:53:39. Ready for production testing with real Discord users.

---

