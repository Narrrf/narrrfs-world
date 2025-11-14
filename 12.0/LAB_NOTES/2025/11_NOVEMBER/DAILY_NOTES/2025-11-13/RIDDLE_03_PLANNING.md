# 📝 RIDDLE #3 PLANNING - CHEESE TEMPLE LEVEL 1

**Date:** November 13, 2025  
**Purpose:** Document the planning and initial implementation details for Riddle #3 in the Cheese Temple Level 1.

---

## 📋 **USER SPECIFICATIONS**

### **Riddle #3 Mechanics:**
1. **After Riddle #2 is solved:** A lever appears on a wall far away
2. **Step 1:** Player finds the lever and presses/clicks it to unlock a movable block
   - Lever switches from `slever1.png` (off) to `slever2.png` (on) state
   - Lever can be rotated or have visual feedback when pressed
3. **Step 2:** After lever is activated, a movable block becomes available
   - Player moves the block to an oak block (similar to Riddle #2 Step 1)
   - Block movement physics similar to Riddle #2 (push force, friction, bounds clamping)
4. **Step 3:** When block is moved to oak block, a huge and bright portal appears on the wall
   - Portal is very large (scale 5.0×) and very bright (brightness 2.0×)
   - Portal indicates Level 2 entrance
5. **Completion:** Player succeeds and enters Level 2 through the portal

### **Lever Image Assets:**
- **File (Off State):** `slever1.png`
- **File (On State):** `slever2.png`
- **Location:** `/textures/blocks/slever1.png`, `/textures/blocks/slever2.png`
- **Description:** Dark metallic toggle switch/lever with industrial appearance
- **Usage:** Lever appears on wall after Riddle #2 completion, player clicks to switch between off/on states
- **Interaction:** Lever can be rotated or have visual feedback when pressed

### **Portal Image Asset:**
- **File:** `Portal1.png`
- **Location:** `/textures/blocks/Portal1.png`
- **Description:** Stone archway portal with glowing yellow-orange light and energy effects
- **Usage:** Portal appears very huge and bright on the wall after Step 2 completion (block moved to oak block)
- **Size:** Very large (scale 5.0×) portal for dramatic effect
- **Brightness:** Very bright (brightness 2.0×) to indicate Level 2 entrance
- **Visual:** Bright and glowing stone archway portal

### **Context:**
- This riddle is for the *same Cheese Temple map* (level1.json) as Riddle #1 and Riddle #2.
- Players can complete all three riddles in the same level.
- Riddles are independent - completing one doesn't affect the others.

---

## 🔧 **TECHNICAL APPROACH (Planned)**

### **1. Riddle State Extension:**
- Extend the existing `riddleState` object in `three.js/main.js` to include specific state variables for Riddle #3.
- Example: `riddleState.riddle3 = { step1Complete: false, step2Complete: false, step3Complete: false, lever: null, leverPressed: false, movableBlock: null, oakBlock: null, portal: null, movableBlockVelocity: new THREE.Vector3(0, 0, 0), movableBlockOriginalPosition: null }`

### **2. Create Lever Block/Mesh:**
- A new function `createRiddle3Lever(spawnData, blockSize)` will be added to `three.js/main.js`.
- This function will create a `THREE.Mesh` with the `slever1.png` texture (off state).
- Lever position will be on a wall far away from spawn.
- Lever will be hidden until Riddle #2 is complete.
- Lever interaction will switch texture from `slever1.png` (off) to `slever2.png` (on).

### **3. Create Movable Block:**
- A new function `createRiddle3MovableBlock(spawnData, blockSize)` will be added to `three.js/main.js`.
- This function will create a `THREE.Mesh` with a texture (can reuse cheese stone or use different texture).
- Block will be hidden or locked until lever is pressed (Step 1 complete).
- Block will be movable by player push (similar to Riddle #2).

### **4. Create Oak Block:**
- A new function `createRiddle3OakBlock(spawnData, blockSize)` will be added to `three.js/main.js`.
- This function will create a `THREE.Mesh` with the `oak-planks.png` texture.
- Oak block will be hidden until lever is pressed (Step 1 complete).
- Oak block will glow permanently after Step 2 completion.

### **5. Create Portal Block/Mesh:**
- A new function `createRiddle3Portal(spawnData, blockSize)` will be added to `three.js/main.js`.
- This function will create a `THREE.Mesh` with the `Portal1.png` texture.
- Portal will be very huge (scale 5.0×) and very bright (brightness 2.0×).
- Portal will be hidden until Step 2 completes (block moved to oak block).
- Portal visual effects (glowing, animation, etc.) will be implemented as needed.

### **6. Riddle #3 Logic:**
- A new `updateRiddle3(delta, aimingAtCheese)` function will be created.
- This function will manage Riddle #3 logic:
  - **Lever Interaction:** Checks if player is near lever and clicks to switch state
  - **Block Movement:** Applies player push force, friction, and bounds clamping (similar to Riddle #2)
  - **Proximity Detection:** Checks if movable block is within threshold of oak block
  - **Portal Appearance:** Shows portal when Step 2 completes (block moved to oak block)
  - **Step 3 Completion:** Completes riddle when portal appears

### **7. Lever Click Handler:**
- A new `handleRiddle3LeverClick()` function will be created.
- This function will handle lever click/press interaction.
- It will switch lever texture from `slever1.png` (off) to `slever2.png` (on).
- It will set `riddle3.leverPressed = true` and `riddle3.step1Complete = true`.
- It will unlock movable block for Step 2 and make oak block visible.

### **4. Riddle #3 Completion:**
- A new `completeRiddle3()` function will be created.
- It will trigger a new trait unlock (`CHEESE_TEMPLE_RIDDLE_03_SOLVED`) via `api/user/unlock-trait.php`.
- It will award DSPOINC via `api/dev/riddle-reward.php` with `riddle_id: 'CHEESE_TEMPLE_RIDDLE_03'`.
- A specific completion message (`showRiddle3CompletionMessage()`) will be displayed.

### **5. Progress UI Integration:**
- The `updateRiddleProgressUI()` function will be extended to display progress for Riddle #3 when it is active.
- Progress UI will show "🧩 Cheese Temple Riddle #3" title and step progress.

### **6. Debug Shortcut Extension:**
- Extend the existing `skipRiddle1ForTesting()` function to skip both Riddle #1 and Riddle #2.
- Rename to `skipRiddles1And2ForTesting()` or extend to handle all previous riddles.
- This will allow direct testing of Riddle #3 without completing previous riddles.

---

## ❓ **CLARIFYING QUESTIONS / DECISIONS**

1. **Lever Placement:**
   - **Question:** Where should the lever be placed on the wall? (Far corner, distant wall, etc.)
   - **Decision:** On a wall far away from spawn (to be determined based on map layout)

2. **Lever Interaction:**
   - **Question:** How should the player interact with the lever? (Mouse click, key press, proximity, etc.)
   - **Decision:** Mouse click or key press (E key) when within 2.0 units of lever

3. **Lever Visual Feedback:**
   - **Question:** Should the lever rotate or have visual feedback when pressed?
   - **Decision:** Lever texture switches from `slever1.png` (off) to `slever2.png` (on), optional rotation

4. **Movable Block Placement:**
   - **Question:** Where should the movable block be placed?
   - **Decision:** *[To be determined based on map layout]*

5. **Oak Block Placement:**
   - **Question:** Where should the oak block be placed?
   - **Decision:** *[To be determined based on map layout]*

6. **Portal Placement:**
   - **Question:** Where should the portal be placed on the wall? (Same wall as lever or different wall?)
   - **Decision:** On a wall (same wall as lever or different wall, to be determined)

7. **Portal Size and Brightness:**
   - **Question:** How huge and bright should the portal be?
   - **Decision:** Very huge (scale 5.0×) and very bright (brightness 2.0×) for dramatic effect

8. **Riddle Dependency:**
   - **Question:** Should Riddle #3 require completion of Riddle #1 and/or Riddle #2?
   - **Decision:** Riddle #3 requires completion of Riddle #2 (lever appears after Riddle #2 completion)

---

## 🎯 **NEXT STEPS**

1. ✅ **User Specifications Received:** Riddle mechanics and implementation details provided
2. ⏳ **Implement Lever Creation:** Create `createRiddle3Lever()` function
3. ⏳ **Implement Movable Block Creation:** Create `createRiddle3MovableBlock()` function
4. ⏳ **Implement Oak Block Creation:** Create `createRiddle3OakBlock()` function
5. ⏳ **Implement Portal Creation:** Create `createRiddle3Portal()` function
6. ⏳ **Implement Lever Click Handler:** Create `handleRiddle3LeverClick()` function
7. ⏳ **Implement Riddle Logic:** Create `updateRiddle3()` function
8. ⏳ **Implement Block Movement Physics:** Implement push mechanics (similar to Riddle #2)
9. ⏳ **Implement Proximity Detection:** Check if block is within threshold of oak block
10. ⏳ **Implement Portal Appearance:** Show portal when Step 2 completes
11. ⏳ **Implement Completion:** Create `completeRiddle3()` and `showRiddle3CompletionMessage()` functions
12. ⏳ **Extend Progress UI:** Update `updateRiddleProgressUI()` for Riddle #3
13. ⏳ **Extend Debug Shortcut:** Update `skipRiddle1ForTesting()` to skip Riddles #1 and #2
14. ⏳ **Integrate into Main Loop:** Add Riddle #3 updates to main `animate()` loop
15. ⏳ **Testing:** Test Riddle #3 completion flow
16. ⏳ **Documentation Update:** Update documentation with final implementation details

---

## 📝 **NOTES**

### **Lever Image Assets:**
- **File (Off State):** `slever1.png`
- **File (On State):** `slever2.png`
- **Location:** `/textures/blocks/slever1.png`, `/textures/blocks/slever2.png`
- **Description:** Dark metallic toggle switch/lever with industrial appearance
- **Visual:** Dark metallic lever with industrial texture, switch between off/on states
- **Usage:** Lever appears on wall after Riddle #2 completion, player clicks to switch between off/on states
- **Note:** Lever images should be loaded and used in `createRiddle3Lever()` function

### **Portal Image Asset:**
- **File:** `Portal1.png`
- **Location:** `/textures/blocks/Portal1.png`
- **Description:** Stone archway portal with glowing yellow-orange light and energy effects
- **Visual:** Stone archway with glowing light inside, energy effects emanating from center
- **Usage:** Portal appears very huge and bright on the wall after Step 2 completion
- **Size:** Very large (scale 5.0×) portal for dramatic effect
- **Brightness:** Very bright (brightness 2.0×) to indicate Level 2 entrance
- **Note:** Portal image should be loaded and used in `createRiddle3Portal()` function

### **Debug Shortcut:**
- **Current:** Shift+K / Ctrl+K skips Riddle #1 for testing Riddle #2
- **Extended:** Will skip Riddle #1 and Riddle #2 for testing Riddle #3
- **Implementation:** Extend `skipRiddle1ForTesting()` function or create new function

### **State Management:**
- **Riddle #1:** `riddleState.step0Complete`, `riddleState.step1Complete`, `riddleState.step2Complete`
- **Riddle #2:** `riddleState.riddle2.step1Complete`, `riddleState.riddle2.step2Complete`
- **Riddle #3:** `riddleState.riddle3.step1Complete`, `riddleState.riddle3.step2Complete` (to be implemented)

---

## 🎨 **VISUAL DESIGN (Planned)**

### **Lever:**
- **Texture (Off):** Lever image (`/textures/blocks/slever1.png`)
- **Texture (On):** Lever image (`/textures/blocks/slever2.png`)
- **Material:** `MeshLambertMaterial` with emissive glow (optional)
- **Visual:** Dark metallic toggle switch/lever with industrial appearance
- **Position:** On a wall far away from spawn
- **Size:** Standard block size (1×1×1 units) or custom size
- **Interaction:** Rotates or changes texture when clicked (off to on)

### **Movable Block:**
- **Texture:** *[To be determined - can reuse cheese stone or use different texture]*
- **Material:** `MeshLambertMaterial` with emissive glow (optional)
- **Visual:** Movable block similar to Riddle #2's cheese stone
- **Position:** *[To be determined based on map layout]*
- **Size:** Standard block size (1×1×1 units)
- **Physics:** Can be pushed by player movement

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

### **Progress UI:**
- **Title:** "🧩 Cheese Temple Riddle #3"
- **Styling:** Dark background with golden border, matching Riddle #1 and Riddle #2 UI
- **Progress Display:** *[To be determined based on riddle mechanics]*

### **Completion Message:**
- **Text:** "🧩 RIDDLE #3 SOLVED! 🧀"
- **Style:** Centered, large font, golden color, glowing border
- **Duration:** 3 seconds
- **Animation:** Fade out after 3 seconds

---

## 📚 **RELATED DOCUMENTATION**

- **Riddle #3 Technical Doc:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_03_CHEESE_TEMPLE_LEVEL_1.md`
- **Riddle #2 Technical Doc:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_02_CHEESE_TEMPLE_LEVEL_1.md`
- **Riddle #1 Technical Doc:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md`
- **Main Technical Doc:** `12.0/TECHNICAL_DOCUMENTATION/HYTOPIA_THREE_TECH_DOCUMENTATION.md`

---

**Status:** 🔄 **PLANNING & READY FOR IMPLEMENTATION**

**Riddle Note:** November 13, 2025 - Riddle #3 mechanics specified! Lever interaction, block movement, and portal appearance mechanics provided. Lever images (`slever1.png`, `slever2.png`) and portal image (`Portal1.png`) documented. Ready for implementation.

---

