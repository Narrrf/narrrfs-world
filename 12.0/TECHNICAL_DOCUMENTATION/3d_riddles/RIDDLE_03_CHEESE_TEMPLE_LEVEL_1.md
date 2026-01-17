# 🧩 RIDDLE #3 - CHEESE TEMPLE LEVEL 1

**Document Created:** November 13, 2025  
**Riddle ID:** `CHEESE_TEMPLE_RIDDLE_03`  
**Level:** Cheese Temple - Level 1 (Same Map)  
**Status:** ✅ **IMPLEMENTED & TESTED (LIVE)**  
**Trait Unlocked:** `CHEESE_TEMPLE_RIDDLE_03_SOLVED`  
**Last Updated:** November 15, 2025

---

## 📋 **RIDDLE OVERVIEW**

### **Objective:**
🧀 **RIDDLE #3 - FIND THE LEVER, MOVE THE BLOCK, ACTIVATE THE PORTAL** 🧀

This riddle is implemented in the same Cheese Temple map (level1.json) as Riddle #1 and Riddle #2, providing players with a third and final challenge that unlocks the portal to Level 2.

### **Riddle Description:**
After completing Riddle #2, players must:
1. **Step 1:** Find the lever on a wall far away and press it to unlock a movable block
2. **Step 2:** Move the unlocked block to an oak block (similar to Riddle #2 Step 1)
3. **Step 3:** When the block reaches the oak block, a huge and bright portal appears on the wall
4. **Completion:** Player succeeds and enters Level 2

### **Lever Image Assets:**
- **File (Off State):** `slever1.png`
- **File (On State):** `slever2.png`
- **Location:** `/textures/blocks/slever1.png`, `/textures/blocks/slever2.png`
- **Description:** Dark metallic toggle switch/lever with industrial appearance
- **Usage:** Lever appears on wall after Riddle #2 completion, player clicks to switch between off/on states

### **Portal Image Asset:**
- **File:** `Portal1.png`
- **Location:** `/textures/blocks/Portal1.png`
- **Description:** Stone archway portal with glowing yellow-orange light and energy effects
- **Usage:** Portal appears very huge and bright on the wall after Step 2 completion (block moved to oak block)
- **Size:** Very large (scale 5.0×) portal for dramatic effect
- **Brightness:** Very bright (brightness 2.0×) to indicate Level 2 entrance
- **Visual:** Bright and glowing stone archway portal

### **Reward:**
- **Trait Unlocked:** `CHEESE_TEMPLE_RIDDLE_03_SOLVED` (`true`)
- **DSPOINC Reward:** 750 base DSPOINC (VIP ×2.0, Holder ×1.5, Champion ×1.4, Tester ×1.3, Early ×1.2, Cheese Hunter ×1.1, WL ×1.3)
- **Visual Feedback:** Dedicated completion toast (“🧩 RIDDLE #3 SOLVED! 🧀 PORTAL ACTIVATED!”) + DSPOINC reward pop-up
- **Progress Tracking:** Existing HUD panel updates instantly; pause menu reflects new total
- **APIs:** `POST /api/user/unlock-trait.php`, `POST /api/dev/riddle-reward.php` (payload `riddle_id: CHEESE_TEMPLE_RIDDLE_03`)

---

## 🎮 **HOW TO SOLVE**

### **Step 1: Find and Press the Lever**
1. Complete Riddle #2 first (all steps).
2. After Riddle #2 completion, a lever will appear on a wall far away.
3. Find the lever by exploring the map (it will be visible on a wall).
4. Walk up to the lever and press/click it to activate it.
5. The lever will switch from `slever1.png` (off) to `slever2.png` (on) state.
6. This unlocks a movable block for Step 2.
7. **Audio Cue:** `slever.ogg` plays when the lever flips so players get immediate confirmation.

### **Step 2: Move the Block to the Oak Block**
1. After pressing the lever, a movable block will appear (or become movable).
2. Walk up to the block and push it by moving into it while pressing W/A/S/D keys.
3. Guide the block to an oak block (similar to Riddle #2 Step 1).
4. When the block is within 1.5 units of the oak block, Step 2 completes automatically and `block_moved_correct.ogg` fires so you hear the success even if you’re staring at the portal spawn point.
5. The block will snap to the oak block position and lock in place.

### **Step 3: Portal Appears & Jump-In Requirement**
1. After Step 2 completes, a huge glowing portal spawns on the north wall (x:60, y:5, z:10).
2. The portal exerts a subtle suction when you’re within ~5 units horizontally (assistive pull).
3. To finish the level you must jump directly into the portal: you need to be **< 2.5 units horizontally and < 3 units vertically**.
4. Once inside the threshold, Riddle #3 locks in, awards the trait + DSPOINC, and triggers the Level 1 completion screen.
5. If you hover near the edge, the suction keeps nudging you toward the doorway until you cross the finish line.

### **Debug Shortcut for Testing:**
- Press **Shift+K** or **Ctrl+K** to skip Riddles #1 and #2 and test Riddle #3 directly.
- This makes the lever, movable block, oak block, and portal visible immediately.

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
    step1Complete: false,
    step2Complete: false,
    oakStone: null,
    oakStoneBlinkTimer: 0,
    cheeseAimTimer: 0,
    unlockableBlockVelocity: new THREE.Vector3(0, 0, 0),
    unlockableBlockOriginalPosition: null
  },
  // Riddle #3 state
  riddle3: {
    step1Complete: false,  // Lever pressed (unlocks movable block)
    step2Complete: false,  // Block moved to oak block (unlocks portal)
    step3Complete: false,  // Portal appeared (riddle complete)
    lever: null,           // Lever block/mesh on wall
    leverPressed: false,   // Lever state (off/on)
    movableBlock: null,    // Movable block for Step 2
    oakBlock: null,        // Oak block target for Step 2
    portal: null,          // Portal block/mesh for Level 2 entrance
    movableBlockVelocity: new THREE.Vector3(0, 0, 0), // Velocity for movable block
    movableBlockOriginalPosition: null // Original position of movable block
  }
};
```

### **Constants:**
```javascript
const RIDDLE3_PROXIMITY_THRESHOLD = 1.5; // Distance threshold for block on oak block (1.5 units)
const RIDDLE3_LEVER_CLICK_DISTANCE = 2.0; // Distance threshold for clicking lever (2.0 units)
const RIDDLE3_PORTAL_SCALE = 5.0; // Portal scale multiplier (very huge)
const RIDDLE3_PORTAL_BRIGHTNESS = 2.0; // Portal brightness multiplier (very bright)
const RIDDLE3_PORTAL_ENTER_DISTANCE = 2.5; // Player must be within 2.5 units horizontally
const RIDDLE3_PORTAL_VERTICAL_THRESHOLD = 3.0; // Player must be within 3 units vertically
const RIDDLE3_PORTAL_SUCTION_RADIUS = 5.0; // Distance where suction starts
const RIDDLE3_PORTAL_SUCTION_STRENGTH = 18.0; // Force multiplier applied each frame
```

### **Key Functions (Planned):**

#### **1. `createRiddle3Lever(spawnData, blockSize)`**
- **Purpose:** Creates the lever block/mesh on a wall far away.
- **Texture:** Lever images (`/textures/blocks/slever1.png` for off, `slever2.png` for on)
- **Position:** On a wall far away from spawn (to be determined based on map layout)
- **Visual:** Dark metallic toggle switch/lever with industrial appearance
- **Initial State:** Hidden until Riddle #2 is complete, starts in off state (`slever1.png`)
- **Interaction:** Player can click/press the lever when within 2.0 units

#### **2. `createRiddle3MovableBlock(spawnData, blockSize)` (Updated January 16, 2026)**
- **Purpose:** Creates the movable O-Block GLB model for Step 2.
- **Model:** Tetris O-Block GLB (`/textures/3d models/tetris/o-block.glb`)
- **Type:** GLB 3D model (replaced BoxGeometry on January 16, 2026)
- **Position:** (spawnX + 5, 1.5, spawnZ) - 5 blocks right of spawn (preserved from original)
- **Scale:** Auto-calculated to match blockSize (1.0 unit) for easy movement
- **Material Processing:** Uses `processWeaponMaterial()` for visibility
- **Data Persistence:** Uses `resolveAssetPath()` + `encodeURI()` + `loadModel()` pattern
- **Movement System:** All userData properties preserved (`isMovable`, `isRiddle3Movable`, etc.)
- **Initial State:** Hidden until lever is pressed (Step 1 complete)
- **Physics:** Can be pushed by player movement (same as Riddle #2 - position-based detection works with GLB)

#### **3. `createRiddle3OakBlock(spawnData, blockSize)`**
- **Purpose:** Creates the oak block target for Step 2.
- **Texture:** Oak planks (`/textures/blocks/oak-planks.png`)
- **Position:** *[To be determined based on map layout]*
- **Visual:** Oak block similar to Riddle #2's oak stone
- **Initial State:** Visible after lever is pressed (Step 1 complete)

#### **4. `createRiddle3Portal(spawnData, blockSize)`**
- **Purpose:** Creates the huge and bright portal on the wall.
- **Texture:** Portal image (`/textures/blocks/Portal1.png`)
- **Position:** On a wall (same wall as lever or different wall)
- **Visual:** Very large (scale 5.0×) and very bright (brightness 2.0×) portal
- **Initial State:** Hidden until Step 2 completes (block moved to oak block)
- **Size:** Very huge portal for dramatic effect
- **Brightness:** Very bright and glowing to indicate Level 2 entrance

#### **5. `updateRiddle3(delta, aimingAtCheese)`**
- **Purpose:** Manages Riddle #3 logic, including lever interaction, block movement, and portal appearance.
- **Lever Interaction:** Checks if player is near lever and clicks to switch state
- **Block Movement:** Applies player push force, friction, and bounds clamping (similar to Riddle #2)
- **Proximity Detection:** Checks if movable block is within threshold of oak block
- **Portal Appearance:** Shows portal when Step 2 completes (block moved to oak block) and enables suction
- **Step 3 Completion:** Requires player to enter suction radius and cross the jump-in threshold; completion triggers trait + reward

#### **3. `completeRiddle3()` (Planned)**
- **Purpose:** Handles Riddle #3 completion, trait unlocking, and DSPOINC reward.
- **Actions:**
  - Shows completion message: "🧩 RIDDLE #3 SOLVED! 🧀"
  - Calls API to unlock trait: `POST /api/user/unlock-trait.php` with `trait_name: 'CHEESE_TEMPLE_RIDDLE_03_SOLVED'`
  - Calls API to award DSPOINC reward: `POST /api/dev/riddle-reward.php` with `riddle_id: 'CHEESE_TEMPLE_RIDDLE_03'`
  - Updates HUD and shows reward notification.

#### **4. `showRiddle3CompletionMessage()` (Planned)**
- **Purpose:** Displays a specific completion message for Riddle #3.

#### **6. `handleRiddle3LeverClick()`**
- **Purpose:** Handles lever click/press interaction.
- **Trigger:** Player clicks/presses lever when within 2.0 units
- **Actions:**
  - Switches lever texture from `slever1.png` (off) to `slever2.png` (on)
  - Sets `riddle3.leverPressed = true`
  - Sets `riddle3.step1Complete = true`
  - Unlocks movable block for Step 2
  - Makes oak block visible
  - Plays `slever.ogg` through `playLeverSound()` for instant feedback

#### **7. `skipRiddles1And2ForTesting()` (Debug Function)**
- **Purpose:** Skips Riddle #1 and Riddle #2 for testing Riddle #3 directly.
- **Trigger:** Press **Shift+K** or **Ctrl+K** (extend existing debug shortcut).
- **Actions:**
  - Marks all Riddle #1 and Riddle #2 steps as complete.
  - Makes lever, movable block, oak block, and portal visible.
  - Activates Riddle #3 for testing.

---

## 🎯 **LEVER INTERACTION SYSTEM**

### **Lever Click Detection:**
- **Distance Threshold:** 2.0 units (player must be within 2.0 units of lever to click)
- **Interaction Method:** Mouse click or key press (E key or similar)
- **State Switching:** Lever texture switches from `slever1.png` (off) to `slever2.png` (on)
- **Visual Feedback:** Lever rotates or changes appearance when pressed
- **One-Time Action:** Lever can only be pressed once (prevents multiple activations)

### **Lever Placement:**
- **Location:** On a wall far away from spawn
- **Visibility:** Visible after Riddle #2 completion
- **Position:** *[To be determined based on map layout - far corner or distant wall]*
- **Orientation:** Flat against wall surface

## 🎯 **BLOCK MOVEMENT PHYSICS SYSTEM**

### **Push Mechanics (Similar to Riddle #2):**
- **Push Distance:** 2.0 units (player must be within 2.0 units of block to push it)
- **Push Force:** 25.0 × push strength (scales with distance, stronger when closer)
- **Push Direction:** Combines player movement direction with direction from player to block
- **Friction:** 0.95 (5% reduction per frame at 60fps, very low friction for responsive movement)
- **Movement Threshold:** 0.001 (allows very small movements)

### **Proximity Detection:**
- **Threshold:** 1.5 units (block must be within 1.5 units of oak block)
- **Completion:** When block is within threshold, Step 2 completes automatically
- **Block Locking:** Block velocity is zeroed and position is snapped to oak block
- **Visual Feedback:** Oak block glows permanently to indicate completion

## 🎯 **PORTAL APPEARANCE SYSTEM**

### **Portal Activation:**
- **Trigger:** Step 2 completion (block moved to oak block)
- **Appearance:** Portal appears very huge and bright on the wall
- **Size:** Scale 5.0× (very large portal for dramatic effect)
- **Brightness:** Brightness 2.0× (very bright and glowing)
- **Visual:** Stone archway with glowing yellow-orange light and energy effects
- **Position:** On a wall (same wall as lever or different wall)

### **Portal Effects:**
- **Glow:** Very bright emissive glow to indicate Level 2 entrance
- **Animation:** *[Optional - portal animation effects to be implemented]*
- **Size:** Very huge portal for dramatic entrance effect
- **Completion:** Riddle #3 completes when portal appears

---

## 📊 **PROGRESS UI SYSTEM**

### **UI Location:**
- **Position:** Fixed at bottom-center of screen (same as Riddle #1 and Riddle #2)
- **Styling:** Dark background with golden border, matching game theme
- **Differentiation:** Shows "🧩 Cheese Temple Riddle #3" title to distinguish from other riddles

### **Progress Display:**

#### **Step 1: Find and Press the Lever**
- **Title:** "🧩 Cheese Temple Riddle #3"
- **Step 1 Text:** "Step 1: Find and Press the Lever" with distance to lever
- **Distance Display:** Shows distance to lever in real-time (e.g., "15.3 units away")
- **Progress Bar:** Visual indicator showing lever search progress

#### **Step 2: Move Block to Oak Block**
- **Step 1 Status:** "✅ Step 1 Complete"
- **Step 2 Text:** "Step 2: Move Block to Oak Block" with distance to oak block
- **Distance Display:** Shows distance to oak block in real-time (e.g., "3.2 units away")
- **Progress Bar:** Visual indicator showing block movement progress

#### **Step 3: Portal Appears**
- **Step 2 Status:** "✅ Step 2 Complete"
- **Step 3 Text:** "Step 3: Portal Activated!" with completion message
- **Portal Status:** Shows "Portal Active - Enter Level 2"

#### **Completion:**
- **Progress UI:** Hidden when Riddle #3 is complete
- **Completion Message:** Shows "🧩 RIDDLE #3 SOLVED! 🧀 PORTAL ACTIVATED!"
- **Reward Notification:** Shows DSPOINC reward notification
- **Level 2 Entrance:** Portal is visible and ready for player to enter

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
  "trait_name": "CHEESE_TEMPLE_RIDDLE_03_SOLVED",
  "trait_value": "true"
}
```

### **Response:**
```json
{
  "success": true,
  "message": "✅ Trait unlocked successfully",
  "trait_name": "CHEESE_TEMPLE_RIDDLE_03_SOLVED",
  "trait_value": "true",
  "action": "unlocked"
}
```

### **Database Schema:**
```sql
-- Same table structure as Riddle #1 and Riddle #2
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
  "riddle_id": "CHEESE_TEMPLE_RIDDLE_03",
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
  "riddle_id": "CHEESE_TEMPLE_RIDDLE_03",
  "base_reward": 500,
  "multiplier": 2.0,
  "total_reward": 1000,
  "dsPoincAwarded": 1000,
  "alreadyCompleted": false
}
```

---

## ⚙️ **CONFIGURATION & ADJUSTMENT**

### **Riddle #3 Specific Constants:**
- `RIDDLE3_PROXIMITY_THRESHOLD`: Distance threshold for block on oak block (1.5 units)
- `RIDDLE3_LEVER_CLICK_DISTANCE`: Distance threshold for clicking lever (2.0 units)
- `RIDDLE3_PORTAL_SCALE`: Portal scale multiplier (5.0 = very huge)
- `RIDDLE3_PORTAL_BRIGHTNESS`: Portal brightness multiplier (2.0 = very bright)
- `pushDistance`: Distance threshold for player to push block (2.0 units)
- `pushForce`: Base push force multiplier (25.0)
- `friction`: Friction multiplier for block movement (0.95 = 5% reduction per frame)

### **Adjustable Parameters:**
- **Lever Click Distance:** Increase `RIDDLE3_LEVER_CLICK_DISTANCE` for easier clicking (default: 2.0)
- **Push Force:** Increase `pushForce` in `updateRiddle3()` for stronger push (default: 25.0)
- **Friction:** Decrease `friction` for more friction, increase for less friction (default: 0.95)
- **Push Distance:** Increase `pushDistance` for larger push range (default: 2.0)
- **Proximity Threshold:** Increase `RIDDLE3_PROXIMITY_THRESHOLD` for easier completion (default: 1.5)
- **Portal Scale:** Adjust `RIDDLE3_PORTAL_SCALE` for different portal size (default: 5.0)
- **Portal Brightness:** Adjust `RIDDLE3_PORTAL_BRIGHTNESS` for different portal brightness (default: 2.0)
- **Lever Position:** Adjust lever position on wall (far away from spawn)
- **Movable Block Position:** Adjust movable block spawn position
- **Oak Block Position:** Adjust oak block target position
- **Portal Position:** Adjust portal position on wall

---

## 🐛 **DEBUGGING**

### **Console Logs:**

#### **Lever Interaction Debug:**
- **Trigger:** When player is near lever (within click distance)
- **Frequency:** 10% chance per frame when near lever
- **Logs:**
  - Lever visibility, position, state (off/on)
  - Player position, distance to lever
  - Lever pressed status

#### **Block Movement Debug:**
- **Trigger:** When player is near block (within push distance)
- **Frequency:** 10% chance per frame when near block
- **Logs:**
  - Block visibility, position, velocity
  - Player position, distance to block
  - Movement state (forward, backward, left, right)

#### **Portal Appearance Debug:**
- **Trigger:** When portal appears (Step 2 completion)
- **Frequency:** 100% when portal appears
- **Logs:**
  - Portal visibility, position, scale, brightness
  - Step 3 completion status

#### **Debug Shortcut:**
- **Key:** Shift+K or Ctrl+K (extend existing shortcut)
- **Action:** Skips Riddles #1 and #2, activates Riddle #3 for testing
- **Logs:** "🧪 [DEBUG] Skipping Riddles #1 and #2 for testing Riddle #3..."

### **Common Issues:**

#### **Lever Not Appearing:**
- **Cause:** Riddle #2 not complete (`riddleState.riddle2.step2Complete === false`)
- **Solution:** Complete Riddle #2 first, or use debug shortcut (Shift+K)
- **Debug:** Check console logs for `riddle2.step2Complete` status

#### **Lever Not Clickable:**
- **Cause:** Player not within click distance (2.0 units)
- **Solution:** Move closer to lever
- **Debug:** Check console logs for `distanceToLever` and `RIDDLE3_LEVER_CLICK_DISTANCE`

#### **Block Not Moving:**
- **Cause:** Lever not pressed (`riddle3.step1Complete === false`) or player not within push distance (2.0 units)
- **Solution:** Press lever first, then move closer to block
- **Debug:** Check console logs for `step1Complete` and `distanceToBlock`

#### **Block Not Completing Step 2:**
- **Cause:** Block not within proximity threshold (1.5 units)
- **Solution:** Move block closer to oak block
- **Debug:** Check console logs for distance between block and oak block

#### **Portal Not Appearing:**
- **Cause:** Step 2 not complete (`riddle3.step2Complete === false`)
- **Solution:** Complete Step 2 first (move block to oak block)
- **Debug:** Check console logs for `step2Complete` status

#### **Portal Not Visible:**
- **Cause:** Portal scale or brightness too low, or portal position incorrect
- **Solution:** Adjust `RIDDLE3_PORTAL_SCALE` and `RIDDLE3_PORTAL_BRIGHTNESS` constants
- **Debug:** Check console logs for portal position, scale, and brightness

---

## 📝 **TESTING CHECKLIST**

### **Functional Testing:**
- [ ] Riddle #3 only activates after Riddle #2 is complete
- [ ] The lever appears on a wall far away after Riddle #2 completion
- [ ] The lever is clickable when player is within 2.0 units
- [ ] The lever switches from off state (`slever1.png`) to on state (`slever2.png`) when clicked
- [ ] The movable block appears or becomes movable after lever is pressed
- [ ] The oak block appears after lever is pressed
- [ ] The movable block is pushable by player movement
- [ ] The movable block stops moving and snaps to the oak block when placed within the proximity threshold
- [ ] The oak block glows permanently after Step 2 completion
- [ ] The portal appears very huge and bright on the wall after Step 2 completion
- [ ] The portal is visible and ready for player to enter Level 2
- [ ] `CHEESE_TEMPLE_RIDDLE_03_SOLVED` trait is unlocked in the database
- [ ] DSPOINC reward is awarded for Riddle #3
- [ ] Riddle #3 progress UI displays correctly
- [ ] Debug shortcut (Shift+K) works for testing

### **Edge Cases:**
- [ ] Player tries to complete Riddle #3 before Riddle #2 (should be prevented)
- [ ] Player tries to click lever multiple times (should only work once)
- [ ] Player pushes block off the map (should reset or be recoverable)
- [ ] Block gets stuck in geometry (should be prevented by bounds clamping)
- [ ] Portal doesn't appear (should be checked for Step 2 completion)
- [ ] Portal appears but is not visible (should check scale and brightness)

### **Performance Testing:**
- [ ] Lever interaction doesn't cause frame rate drops
- [ ] Block movement physics doesn't cause frame rate drops
- [ ] Portal appearance doesn't cause frame rate drops
- [ ] Debug logging doesn't spam console (limited to 5-10% chance per frame)

---

## 🎨 **VISUAL DESIGN**

### **Lever:**
- **Texture (Off):** Lever image (`/textures/blocks/slever1.png`)
- **Texture (On):** Lever image (`/textures/blocks/slever2.png`)
- **Material:** `MeshLambertMaterial` with emissive glow (optional)
- **Visual:** Dark metallic toggle switch/lever with industrial appearance
- **Position:** On a wall far away from spawn
- **Size:** Standard block size (1×1×1 units) or custom size
- **Interaction:** Rotates or changes texture when clicked (off to on)

### **Movable Block (O-Block GLB Model):**
- **Model:** Tetris O-Block GLB (`/textures/3d models/tetris/o-block.glb`)
- **Type:** GLB 3D model (replaced BoxGeometry on January 16, 2026)
- **Material:** Processed with `processWeaponMaterial()` for visibility
- **Visual:** 3D Tetris O-Block shape (same as Riddle #2's movable block)
- **Scale:** Auto-calculated to match blockSize (1.0 unit) for easy movement
- **Position:** (spawnX + 5, 1.5, spawnZ) - 5 blocks right of spawn
- **Size:** Scaled to match standard block size (1×1×1 units)
- **Physics:** Can be pushed by player movement (same as Riddle #2)
- **Data Persistence:** Uses `resolveAssetPath()` + `encodeURI()` + `loadModel()` pattern (same as Portal/Blue Cheese)
- **Visibility:** Hidden until lever pressed (Step 1 complete)
- **Movement System:** Fully compatible - uses position-based detection (works with GLB models)

### **Oak Block:**
- **Texture:** Oak planks (`/textures/blocks/oak-planks.png`)
- **Material:** `MeshLambertMaterial` with emissive glow
- **Visual:** Oak block similar to Riddle #2's oak stone
- **Position:** *[To be determined based on map layout]*
- **Size:** Standard block size (1×1×1 units)
- **Completion Glow:** Permanent glow (emissiveIntensity: 0.8) after Step 2

### **Portal:**
- **Texture:** Portal image (`/textures/blocks/Portal1.png`)
- **Material:** `MeshLambertMaterial` with very bright emissive glow
- **Visual:** Stone archway portal with glowing yellow-orange light and energy effects
- **Position:** On a wall (same wall as lever or different wall)
- **Size:** Very huge (scale 5.0× = 5×5×5 units or larger)
- **Brightness:** Very bright (brightness 2.0×, emissiveIntensity: 2.0)
- **Glow:** Very bright emissive glow to indicate Level 2 entrance
- **Gameplay Effect:** Applies suction when player is within 5u horizontally to help them jump into the portal; completion requires <2.5u horizontal & <3u vertical distance.

### **Progress UI:**
- **Step 1:** Shows "Step 1: Find and Press the Lever" with distance to lever
- **Step 2:** Shows "Step 2: Move Block to Oak Block" with distance to oak block
- **Step 3:** Shows "Step 3: Portal Activated!" with completion message
- **Styling:** Dark background with golden border, matching Riddle #1 and Riddle #2 UI
- **Title:** "🧩 Cheese Temple Riddle #3" to distinguish from other riddles

### **Completion Message:**
- **Text:** "🧩 RIDDLE #3 SOLVED! 🧀 PORTAL ACTIVATED!"
- **Style:** Centered, large font, golden color, glowing border
- **Duration:** 3 seconds
- **Animation:** Fade out after 3 seconds

---

## 🔮 **FUTURE ENHANCEMENTS**

### **Potential Improvements:**
- Sound effects for riddle progress and completion
- Particle effects for visual feedback
- Portal animation effects
- Additional achievements for perfect completion
- Leaderboard integration for fastest completion times

---

## 📚 **RELATED DOCUMENTATION**

- **Main Technical Doc:** `12.0/TECHNICAL_DOCUMENTATION/HYTOPIA_THREE_TECH_DOCUMENTATION.md`
- **Riddle #1:** `RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md`
- **Riddle #2:** `RIDDLE_02_CHEESE_TEMPLE_LEVEL_1.md`
- **Cheese Hunt System:** `12.0/TECHNICAL_DOCUMENTATION/CHEESE_HUNT_SYSTEM_SPECIFICATION.md`
- **Controls System:** See Chapter 13 in Hytopia Three Tech Documentation
- **API Reference:** `api/user/unlock-trait.php`, `api/dev/riddle-reward.php`

---

## 🧀 **RIDDLE METADATA**

| Property | Value |
|----------|-------|
| **Riddle Number** | 3 |
| **Level** | Cheese Temple - Level 1 (Same Map) |
| **Difficulty** | Hard (multi-zone search + precision finish) |
| **Estimated Time** | 6-10 minutes on first attempt |
| **Required Skill** | Exploration, block pushing control, precise portal entry |
| **Trait Name** | `CHEESE_TEMPLE_RIDDLE_03_SOLVED` |
| **Trait Value** | `true` |
| **Status** | ✅ Implemented & Tested (Nov 15, 2025) |
| **Steps** | 1) Press lever 2) Push block to oak stone 3) Jump into portal |
| **Portal Asset** | `Portal1.png` |

---

## 📝 **IMPLEMENTATION NOTES**

### **Same Map as Riddle #1 and Riddle #2:**
- Riddle #3 will be implemented in the same Cheese Temple map (level1.json)
- Players can complete all three riddles in the same level
- Riddles are independent - completing one doesn't affect the others
- Each riddle has its own state management and progress tracking

### **Portal Image Asset:**
- **File:** `Portal1.png`
- **Location:** `/textures/blocks/Portal1.png`
- **Description:** Stone archway portal with glowing yellow-orange light and energy effects
- **Usage:** *[To be determined based on riddle mechanics]*
- **Note:** Portal image available for Riddle #3 implementation

### **Integration Points:**
- **State Management:** Will extend existing `riddleState` object with `riddle3` property
- **UI System:** Will extend existing progress UI system for Riddle #3
- **Detection System:** Will reuse existing raycasting and detection systems
- **API Integration:** Will use same API endpoints (different riddle_id)
- **Debug Shortcut:** Will extend existing Shift+K / Ctrl+K shortcut to skip Riddles #1 and #2

### **Current Status & Next Steps:**
1. ✅ **Implementation Complete:** Lever → block push → portal flow live in `three.js/main.js`
2. ✅ **Assets Wired:** `slever1/2.png`, `Portal1.png`, oak/cheese textures deployed
3. ✅ **Rewards Live:** Trait + 750 DSPOINC base payout hooked to APIs
4. ✅ **Portal Suction:** Jump-in requirement + suction radius documented (see `RIDDLE_PORTAL_SUCTION_NOTE.md`)
5. 🔄 **Future Enhancements:** Add portal VFX/audio, integrate leaderboard timing, design Level 2 entry cutscene

---

**Document Version:** 2.1  
**Last Updated:** January 16, 2026  
**Maintained By:** Narrrf's Lab Tech Council  
**Status:** ✅ **LIVE IMPLEMENTATION**

**Riddle Notes:**  
- **Nov 13, 2025:** Mechanics specced (lever → block → portal).  
- **Nov 15, 2025:** Portal suction + jump-in requirement shipped. See `12.0/LAB_NOTES/2025/11_NOVEMBER/DAILY_NOTES/2025-11-15/RIDDLE_PORTAL_SUCTION_NOTE.md` for granular QA details.
- **Jan 16, 2026:** Movable block replaced with Tetris O-Block GLB model (`o-block.glb`). Block now uses data persistence file system pattern (`resolveAssetPath()` + `encodeURI()` + `loadModel()`), auto-scaled to match blockSize (1.0 unit), materials processed with `processWeaponMaterial()`, and all movement system properties preserved. Movement system fully compatible - uses position-based detection that works with GLB models.

---

