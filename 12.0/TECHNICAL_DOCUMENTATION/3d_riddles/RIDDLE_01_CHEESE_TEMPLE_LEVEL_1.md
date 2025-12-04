# 🧩 LEVEL 1 - CHEESE TEMPLE (4 RIDDLES)

**Document Created:** November 12, 2025  
**Level:** Cheese Temple - Level 1  
**Status:** ✅ **IMPLEMENTED & TESTED**  
**Total Riddles:** 4 separate riddles with individual rewards (3 main + 1 hidden secret)  
**Last Tested:** November 21, 2025 - ✅ **WORKING**

---

## 📋 **LEVEL 1 OVERVIEW**

### **Level 1 Contains 3 Separate Riddles:**

#### **🧩 RIDDLE #1: The Discovery**
- **Riddle ID:** `CHEESE_TEMPLE_RIDDLE_01`
- **Trait Unlocked:** `CHEESE_TEMPLE_RIDDLE_SOLVED`
- **Base Reward:** 500 DSPOINC
- **VIP Holder (×2.0):** 1,000 DSPOINC
- **Holder (×1.5):** 750 DSPOINC
- **Champion (×1.4):** 700 DSPOINC
- **WL/Season Tester (×1.3):** 650 DSPOINC
- **Early Bird (×1.2):** 600 DSPOINC
- **Cheese Hunter (×1.1):** 550 DSPOINC
- **Default (×1.0):** 500 DSPOINC

#### **🧩 RIDDLE #2: The Push**
- **Riddle ID:** `CHEESE_TEMPLE_RIDDLE_02`
- **Trait Unlocked:** `CHEESE_TEMPLE_RIDDLE_02_SOLVED`
- **Base Reward:** 500 DSPOINC
- **VIP Holder (×2.0):** 1,000 DSPOINC
- **Holder (×1.5):** 750 DSPOINC
- **Champion (×1.4):** 700 DSPOINC
- **WL/Season Tester (×1.3):** 650 DSPOINC
- **Early Bird (×1.2):** 600 DSPOINC
- **Cheese Hunter (×1.1):** 550 DSPOINC
- **Default (×1.0):** 500 DSPOINC

#### **🧩 RIDDLE #3: The Portal**
- **Riddle ID:** `CHEESE_TEMPLE_RIDDLE_03`
- **Trait Unlocked:** `CHEESE_TEMPLE_RIDDLE_03_SOLVED`
- **Base Reward:** 750 DSPOINC
- **VIP Holder (×2.0):** 1,500 DSPOINC
- **Holder (×1.5):** 1,125 DSPOINC
- **Champion (×1.4):** 1,050 DSPOINC
- **WL/Season Tester (×1.3):** 975 DSPOINC
- **Early Bird (×1.2):** 900 DSPOINC
- **Cheese Hunter (×1.1):** 825 DSPOINC
- **Default (×1.0):** 750 DSPOINC

#### **🧩 RIDDLE #4: The Hidden Secret (Secret Riddle)**
- **Riddle ID:** `CHEESE_TEMPLE_RIDDLE_04_SECRET`
- **Trait Unlocked:** None (hidden secret riddle)
- **Base Reward:** 1,000 DSPOINC (fixed, no role multiplier)
- **Reward:** Always 1,000 DSPOINC regardless of role
- **Location:** Wall at x: 10.5, z: 95.7, 100.7, 105.7 (3 levers horizontally aligned)
- **Type:** Sequence-based combination puzzle

### **🎯 TOTAL LEVEL 1 REWARDS (All 4 Riddles):**

**VIP Holder (×2.0):**
- Riddle #1: 1,000 DSPOINC
- Riddle #2: 1,000 DSPOINC
- Riddle #3: 1,500 DSPOINC
- Riddle #4 (Secret): 1,000 DSPOINC
- **Total: 4,500 DSPOINC** 🧀

**Holder (×1.5):**
- Riddle #1: 750 DSPOINC
- Riddle #2: 750 DSPOINC
- Riddle #3: 1,125 DSPOINC
- Riddle #4 (Secret): 1,000 DSPOINC
- **Total: 3,625 DSPOINC** 🧀

**Champion (×1.4):**
- Riddle #1: 700 DSPOINC
- Riddle #2: 700 DSPOINC
- Riddle #3: 1,050 DSPOINC
- Riddle #4 (Secret): 1,000 DSPOINC
- **Total: 3,450 DSPOINC** 🧀

**Default (×1.0):**
- Riddle #1: 500 DSPOINC
- Riddle #2: 500 DSPOINC
- Riddle #3: 750 DSPOINC
- Riddle #4 (Secret): 1,000 DSPOINC
- **Total: 2,750 DSPOINC** 🧀

---

## 🧩 **RIDDLE #1: THE DISCOVERY**

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
- **Recent Score Changes:** Appears in profile page with format: `Riddle completion (CHEESE_TEMPLE_RIDDLE_01): base 500 × [multiplier] = [total] DSPOINC`

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
- DSPOINC reward is automatically awarded based on role multiplier:
  - **VIP Holder:** 1,000 DSPOINC (base 500 × 2.0)
  - **Holder:** 750 DSPOINC (base 500 × 1.5)
  - **Champion:** 700 DSPOINC (base 500 × 1.4)
  - **WL/Season Tester:** 650 DSPOINC (base 500 × 1.3)
  - **Early Bird:** 600 DSPOINC (base 500 × 1.2)
  - **Cheese Hunter:** 550 DSPOINC (base 500 × 1.1)
  - **Default:** 500 DSPOINC (base 500 × 1.0)
- Reward notification appears showing DSPOINC amount (e.g., "🎉 +1,000 DSPOINC (×2.0)! 🧀")
- HUD and pause menu automatically update with new DSPOINC balance
- Progress UI disappears after completion
- **One-time reward:** Riddle can only be completed once per player (duplicate completions are prevented)
- **Audio Cue:** When the hidden cheese stone unlocks (Step 2), the new `cheese_platform_active.ogg` sound plays so players immediately know the platform spawned even if it's off-camera.
- **Recent Score Changes:** Reward appears in profile page "Recent Score Changes" section with full details

---

## 🎮 **COMPLETE LEVEL 1 WALKTHROUGH**

### **Starting Level 1:**
1. **Spawn Location:** Player spawns at center of level (approximately x: 60, z: 15, y: 2)
2. **Initial State:** Riddle UI is hidden - no hints visible
3. **Objective:** Complete all 3 riddles to unlock Level 2 portal
4. **Total Rewards:** 1,750 DSPOINC base (VIP: 3,500 DSPOINC with 2.0x multiplier)

---

## 🧩 **RIDDLE #1: THE DISCOVERY - COMPLETE WALKTHROUGH**

### **Step 0: Discovery (10 seconds)**
1. **Explore the Level:**
   - Level is 120×120 blocks
   - Hidden block is at coordinates (110, 1, 110) - far back corner
   - Use WASD to move, mouse to look around
   - Look for a block with yellow-cheese texture and golden glow

2. **Finding the Block:**
   - Navigate to the back of the level (opposite direction from spawn)
   - The block is on the ground level (y: 1)
   - It has a subtle golden glow to help discovery
   - Audio cue plays when you step on it: `cheese_platform_active.ogg`

3. **Completing Step 0:**
   - Stand directly on top of the block
   - Riddle UI appears at bottom of screen: "🔍 Step 0: Stand on Golden Stone"
   - Progress bar shows countdown from 10 seconds
   - **Important:** Timer decays if you step off (must stay on block)
   - After 10 seconds, Step 1 unlocks

### **Step 1: Aim at Cheese (10 seconds)**
1. **After Step 0 Completes:**
   - Riddle UI updates: "Step 1: Aim at Cheese"
   - Floating cheese entity is already in the level (roaming around)
   - Locate the cheese cube that moves around the temple

2. **Aiming at Cheese:**
   - Use mouse to aim crosshair at the cheese entity
   - Crosshair turns yellow/gold when aiming at cheese
   - **STRICT:** Must keep crosshair directly on cheese pixels
   - Timer increments only when crosshair is on cheese
   - Timer decays immediately if crosshair moves off (even 1 pixel)

3. **Completing Step 1:**
   - Keep crosshair on cheese for 10 continuous seconds
   - Progress bar shows countdown
   - When complete, unlockable block appears in center of platform
   - Audio cue: `cheese_platform_active.ogg` plays

### **Step 2: Aim at Block (10 seconds)**
1. **After Step 1 Completes:**
   - Special glowing block appears in middle of platform
   - Block is positioned one block above spawn point
   - Block has cheese-stone texture with bright glow

2. **Aiming at Block:**
   - Use mouse to aim crosshair at the unlockable block
   - Crosshair turns yellow/gold when aiming at block
   - **STRICT:** Must keep crosshair directly on block pixels
   - Timer increments only when crosshair is on block
   - Timer decays immediately if crosshair moves off

3. **Completing Step 2:**
   - Keep crosshair on block for 10 continuous seconds
   - Progress bar shows countdown
   - When complete, celebration message appears: "🧩 RIDDLE SOLVED! 🧀"
   - Trait unlocked: `CHEESE_TEMPLE_RIDDLE_SOLVED`
   - DSPOINC reward awarded (VIP: 1,000 DSPOINC with 2.0x multiplier)
   - Reward notification shows: "🎉 +1,000 DSPOINC (×2.0)! 🧀"
   - **Riddle #2 unlocks** - The unlockable block becomes movable

---

## 🧩 **RIDDLE #2: THE PUSH - COMPLETE WALKTHROUGH**

### **Step 1: Push Cheese Stone to Oak Stone**
1. **After Riddle #1 Completes:**
   - The unlockable block from Riddle #1 becomes the movable cheese stone
   - A blinking oak stone appears on the middle platform
   - Oak stone blinks every 15 seconds (2-second blink duration)
   - Riddle UI shows: "Step 1: Move Cheese Stone to Oak Stone"

2. **Pushing the Block:**
   - Walk to the cheese stone block (it's in the center of the platform)
   - Press movement keys (W/A/S/D) while near the block (within 2.0 units)
   - Block moves in the direction you're pressing
   - **Works in both normal mode and God Mode**
   - Push force: 30.0 (strong enough to move block easily)
   - Block has physics - it will slide and slow down naturally

3. **Completing Step 1:**
   - Push block to within 1.5 units of the oak stone
   - Block automatically snaps to oak stone position
   - Oak stone stops blinking and glows permanently (golden glow)
   - Audio cue: `block_moved_correct.ogg` plays
   - Step 2 unlocks

### **Step 2: Aim at Cheese (10 seconds)**
1. **After Step 1 Completes:**
   - Riddle UI updates: "Step 2: Aim at Cheese"
   - Floating cheese entity is already in the level (same as Riddle #1)

2. **Aiming at Cheese:**
   - Use mouse to aim crosshair at the cheese entity
   - Crosshair turns yellow/gold when aiming at cheese
   - **STRICT:** Must keep crosshair directly on cheese pixels
   - Timer increments only when crosshair is on cheese
   - Timer decays immediately if crosshair moves off

3. **Completing Step 2:**
   - Keep crosshair on cheese for 10 continuous seconds
   - Progress bar shows countdown
   - When complete, celebration message appears: "🧩 RIDDLE #2 SOLVED! 🧀"
   - Trait unlocked: `CHEESE_TEMPLE_RIDDLE_02_SOLVED`
   - DSPOINC reward awarded (VIP: 1,000 DSPOINC with 2.0x multiplier)
   - Reward notification shows: "🎉 +1,000 DSPOINC (×2.0)! 🧀"
   - **Lever for Riddle #3 becomes visible**

---

## 🧩 **RIDDLE #3: THE PORTAL - COMPLETE WALKTHROUGH**

### **Step 1: Press the Lever**
1. **After Riddle #2 Completes:**
   - Lever appears on the wall (near the end of the room)
   - Lever position: approximately (20.5, 2.5, 100.5)
   - Lever is visible and ready to interact
   - Riddle UI shows: "Step 1: Press the Lever"

2. **Pressing the Lever:**
   - Walk to the lever (must be within 2.0 units)
   - Press E key to interact with lever
   - Lever switches from off to on state
   - Lever texture changes (visual feedback)
   - Lever glows green when activated
   - Audio cue: `slever.ogg` plays

3. **After Lever is Pressed:**
   - Movable block appears near the lever position
   - Oak block appears as target destination
   - Step 2 unlocks
   - Riddle UI updates: "Step 2: Move Block to Oak Block"

### **Step 2: Push Block to Oak Block**
1. **After Step 1 Completes:**
   - Movable block is near the lever position
   - Oak block is the target (similar to Riddle #2)

2. **Pushing the Block:**
   - Walk to the movable block
   - Press movement keys (W/A/S/D) while near the block
   - Block moves in the direction you're pressing
   - Push block to within 1.5 units of the oak block

3. **Completing Step 2:**
   - Block automatically snaps to oak block position
   - Oak block glows permanently (completion indicator)
   - Audio cue: `block_moved_correct.ogg` plays
   - **Huge portal appears** (5.0x scale, very bright, 2.0x brightness)
   - Step 3 unlocks
   - Riddle UI updates: "Step 3: Enter the Portal"

### **Step 3: Enter the Portal**
1. **After Step 2 Completes:**
   - Massive portal appears (very large and bright)
   - Portal is positioned near the end of the room
   - Portal is hard to miss (5.0x scale, very bright)

2. **Entering the Portal:**
   - Walk into the portal (must be within 2.5 units)
   - Portal entrance triggers completion
   - Audio cue: `LEVEL UP!.wav` plays

3. **Completing Riddle #3:**
   - Celebration message appears: "🧩 RIDDLE #3 SOLVED! 🧀"
   - Trait unlocked: `CHEESE_TEMPLE_RIDDLE_03_SOLVED`
   - DSPOINC reward awarded (VIP: 1,500 DSPOINC with 2.0x multiplier)
   - Reward notification shows: "🎉 +1,500 DSPOINC (×2.0)! 🧀"
   - **Level 1 completion screen appears**
   - Options: Go to Level 2, Level 3, Level 4, or back to Level 1

---

## 🎁 **REWARD COLLECTION SUMMARY**

### **All 3 Riddles Complete:**
- **Riddle #1:** 500 DSPOINC base (VIP: 1,000 with 2.0x)
- **Riddle #2:** 500 DSPOINC base (VIP: 1,000 with 2.0x)
- **Riddle #3:** 750 DSPOINC base (VIP: 1,500 with 2.0x)
- **Total Base:** 1,750 DSPOINC
- **Total VIP (×2.0):** 3,500 DSPOINC 🧀

### **Reward Tracking:**
- All rewards automatically added to balance
- Balance updates in pause menu (press P)
- Balance updates in HUD
- All 3 rewards appear in "Recent Score Changes" on profile page
- Format for each: `Riddle completion (CHEESE_TEMPLE_RIDDLE_XX): base [amount] × [multiplier] = [total] DSPOINC`

---

## 🧩 **RIDDLE #4: THE HIDDEN SECRET (SECRET RIDDLE)**

**Riddle ID:** `CHEESE_TEMPLE_RIDDLE_04_SECRET`  
**Type:** Sequence-based combination puzzle  
**Reward:** 1,000 DSPOINC (fixed, no role multiplier)  
**Status:** ✅ **IMPLEMENTED** (November 21, 2025)

### **Objective:**
Solve the hidden secret riddle by performing a specific sequence of lever activations on the wall.

### **Location:**
- **Wall Coordinates:** x: 10.5 (10 blocks from spawn, on the left wall)
- **Lever Positions (Horizontal Line):**
  - **Lever 1 (Left):** x: 10.5, y: 2.5, z: 95.7
  - **Lever 2 (Middle):** x: 10.5, y: 2.5, z: 100.7
  - **Lever 3 (Right):** x: 10.5, y: 2.5, z: 105.7
- **Wall Position:** Left wall of Level 1 (z: 100.5 wall, levers positioned slightly forward at z: 100.7 for visibility)

### **Riddle Description:**
Players must discover and interact with 3 hidden levers on the wall, performing a specific sequence:
1. **Step 1:** Switch all 3 levers ON (lever1, lever2, lever3 all ON)
2. **Step 2:** Switch all 3 levers OFF (lever1, lever2, lever3 all OFF)
3. **Step 3:** Activate only the middle lever (lever2 ON, lever1 and lever3 OFF)

### **How to Solve:**
1. **Find the Levers:**
   - Explore the left wall of Level 1 (x: 10.5)
   - Look for 3 grey cube-shaped objects with levers
   - Levers are positioned horizontally in a line
   - Levers are always visible (unlike Riddle #3 lever)

2. **Interact with Levers:**
   - Walk close to a lever (within 2.0 units)
   - Press E key to toggle lever state
   - Lever switches between OFF (slever1.png) and ON (slever2.png)
   - When ON, lever glows green (emissive intensity 1.5)
   - Audio cue: `slever.ogg` plays on toggle

3. **Perform the Sequence:**
   - **Step 1:** Toggle all 3 levers to ON state
     - Lever 1: ON
     - Lever 2: ON
     - Lever 3: ON
   - **Step 2:** Toggle all 3 levers to OFF state
     - Lever 1: OFF
     - Lever 2: OFF
     - Lever 3: OFF
   - **Step 3:** Toggle only the middle lever (Lever 2) to ON
     - Lever 1: OFF
     - Lever 2: ON
     - Lever 3: OFF

4. **Completing the Riddle:**
   - **Sound Effect:** `hidden-slever.mp3` plays immediately when riddle is solved
   - Success message appears: "🎉 You found a hidden riddle! 🎉"
   - Reward notification: "+1,000 DSPOINC"
   - Riddle is marked as complete
   - Sequence resets if wrong combination is attempted

### **Sequence Logic:**
- **Sequence is tracked step-by-step:** Must complete steps in order
- **Wrong combinations reset sequence:** If player deviates from sequence, it resets to Step 0
- **No partial credit:** Must complete all 3 steps in exact order
- **Hint system:** After 5 failed attempts (with 10-second cooldown), hint messages appear
- **Reset Logic (Fixed November 22, 2025):**
  - **Step 1:** Only resets if all levers are turned back ON (player went backwards)
  - **Step 2:** Only resets if lever1 or lever3 is turned ON (wrong levers)
  - **Player can turn levers OFF one by one** in Step 1 without resetting
  - **Player can turn lever2 ON** in Step 2 without resetting

### **Reward:**
- **Fixed Reward:** 1,000 DSPOINC (no role multiplier)
- **Reward ID:** `CHEESE_TEMPLE_RIDDLE_04_SECRET`
- **Description:** "Secret Riddle #4 - Hidden Lever Combination"
- **API Endpoint:** `/api/user/award-level1-dspoinc-reward.php`
- **Database:** Recorded in `tbl_user_scores` and `tbl_score_adjustments`

### **Audio Feedback:**
- **Lever Toggle Sound:** `slever.ogg` plays when any lever is toggled ON or OFF
- **Riddle Solved Sound:** `hidden-slever.mp3` plays when riddle is successfully completed (Step 3: only middle lever ON)
- **Sound Volume:** 0.8 (80% volume)
- **Sound Loading:** Loaded at game start in `loadCharacterAudio()`
- **Sound Playback:** Plays immediately before UI messages/rewards (ensures sound plays even if UI code fails)

### **Visual Feedback:**
- **Lever OFF State:** Grey texture (`slever1.png`), no glow
- **Lever ON State:** Green texture (`slever2.png`), green emissive glow (intensity 1.5)
- **Success Message:** Green toast notification with celebration message
- **Hint Messages:** Yellow toast notification with random hint text

### **Hint Messages (After 5 Failed Attempts):**
- "🔍 You need more information to solve this riddle..."
- "🧠 This riddle requires more skills..."
- "💡 Keep exploring to find clues..."
- "🔎 The answer lies elsewhere in the temple..."

### **Technical Details:**
- **Lever Click Distance:** 2.0 units (RIDDLE4_LEVER_CLICK_DISTANCE)
- **Hint Cooldown:** 10 seconds (RIDDLE4_HINT_COOLDOWN)
- **Hint Threshold:** 5 attempts (RIDDLE4_HINT_ATTEMPT_THRESHOLD)
- **Sequence Tracking:** `riddleState.riddle4.sequenceStep` (0 = initial, 1 = all ON, 2 = all OFF, 3 = solved)
- **Reset on Level Restart:** Sequence resets to Step 0 when Level 1 restarts

### **Code Locations:**
- **Lever Creation:** `three.js/main.js` ~13928 (`createRiddle4Levers()`)
- **Lever Interaction:** `three.js/main.js` ~14644 (`handleRiddle4LeverClick()`)
- **Sequence Checking:** `three.js/main.js` ~14701 (`checkRiddle4Combination()`)
- **Reward Unlock:** `three.js/main.js` ~14748 (`unlockRiddle4Reward()`)
- **Success Message:** `three.js/main.js` ~14760 (`showRiddle4SuccessMessage()`)
- **Hint Messages:** `three.js/main.js` ~14773 (`showRiddle4HintMessage()`)
- **Sound Loading:** `three.js/main.js` ~1237-1253 (`loadCharacterAudio()` - hidden slever sound)
- **Sound Function:** `three.js/main.js` ~1336-1343 (`playHiddenSleverSound()`)
- **Sound Playback:** `three.js/main.js` ~15020 (called in `checkRiddle4Combination()` when riddle solved)

### **Wall Coordinates Note:**
The levers are positioned on the left wall of Level 1:
- **Wall X Position:** 10.5 (10 blocks from spawn point at x: 60)
- **Wall Z Position:** 100.5 (wall extends along z-axis)
- **Lever Z Positions:** 95.7, 100.7, 105.7 (spaced 5 blocks apart horizontally)
- **Lever Y Position:** 2.5 (same height for all, horizontal line)
- **Lever Forward Offset:** +0.2 units from wall (z: 100.7 vs z: 100.5) for visibility and clickability

---

## 🎯 **TIPS & STRATEGY**

### **For Riddle #1:**
- **Step 0 (Discovery):**
  - **Exploration:** Don't rush - take time to explore the level
  - **Visual Cues:** Look for golden glow in the distance
  - **Audio Cues:** Listen for the audio cue when you step on the block
  - **Patience:** Must stand on block for full 10 seconds
- **Step 1 (Aim at Cheese):**
  - **Tracking:** Cheese moves around - follow it with crosshair
  - **Precision:** Keep crosshair directly on cheese (no tolerance)
  - **Focus:** Don't move crosshair away even slightly
  - **Practice:** Cheese movement is predictable - learn the pattern
- **Step 2 (Aim at Block):**
  - **Positioning:** Block is stationary - easier than Step 1
  - **Stability:** Keep crosshair steady on block center
  - **Patience:** 10 seconds feels long but stay focused
  - **Completion:** Audio cue confirms completion

### **For Riddle #2:**
- **Step 1 (Push Block):**
  - **Block Pushing:** Works in both normal mode and God Mode
  - **Movement:** Press W/A/S/D while near block to push it
  - **Direction:** Block moves in the direction you're pressing
  - **Oak Stone:** Look for blinking oak stone - it blinks every 15 seconds
  - **Proximity:** Block must be within 1.5 units of oak stone
- **Step 2 (Aim at Cheese):**
  - **Same as Riddle #1 Step 1:** Follow cheese with crosshair for 10 seconds
  - **Strict Aiming:** Must keep crosshair directly on cheese pixels

### **For Riddle #3:**
- **Step 1 (Press Lever):**
  - **Lever Location:** Near the end of the room (approximately x: 20.5, z: 100.5)
  - **Interaction:** Walk within 2.0 units and press E key
  - **Visual Feedback:** Lever texture changes and glows green when pressed
  - **Audio Cue:** `slever.ogg` plays when lever is activated
- **Step 2 (Push Block):**
  - **Block Location:** Movable block appears near lever after it's pressed
  - **Target:** Oak block is the destination (similar to Riddle #2)
  - **Pushing:** Same mechanics as Riddle #2 Step 1
- **Step 3 (Enter Portal):**
  - **Portal Size:** Very large (5.0x scale) - hard to miss
  - **Portal Brightness:** Very bright (2.0x brightness) - highly visible
  - **Proximity:** Must be within 2.5 units to enter
  - **Completion:** Audio cue `LEVEL UP!.wav` plays

### **General Tips for All Riddles:**
- **God Mode:** Press G to enable God Mode for easier navigation (faster movement, flying)
- **Level Selector:** Press L (in God Mode) to jump to any level
- **Pause:** Press P to pause and check balance
- **Camera:** Use mouse to look around, WASD to move
- **Audio:** Keep sound on for audio cues
- **One-Time:** Each riddle can only be completed once - make it count!
- **Role Multipliers:** VIP Holders get 2.0x multiplier on all rewards (double DSPOINC!)
- **Total Rewards:** Complete all 3 riddles for maximum DSPOINC (VIP: 3,500 total)

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

**Document Version:** 2.6  
**Last Updated:** November 22, 2025 (Evening)  
**Maintained By:** Narrrf's Lab Tech Council  
**Status:** ✅ **PRODUCTION VERIFIED** - 3-Step System with Strict Aiming Detection + Role-Based Rewards  
**Riddle Notes:** 
- November 22, 2025 (Afternoon) - Fixed Riddle #4 sequence reset logic bug
- November 22, 2025 (Evening) - Added hidden slever sound (`hidden-slever.mp3`) for Riddle #4 completion

**Changes:** 
- **Version 2.0:** Added Step 0 (hidden discovery challenge) - Riddle UI now hidden until player finds and stands on trigger block
- **Version 2.1:** Implemented STRICT aiming detection for both Step 1 and Step 2 - removed fallback tolerance checks, both steps now require exact raycast hits on target pixels. Timer only increments when crosshair is directly on target, decays immediately when crosshair moves off by even 1 pixel.
- **Version 2.2:** Fixed trait unlock API database schema mismatch - updated SQL queries to match actual table structure (`trait` instead of `trait_name`, `timestamp` instead of `created_at`/`updated_at`). Trait unlock now works correctly with existing database schema.
- **Version 2.3:** Fixed role-based multiplier system - corrected `getRoleMultiplier()` function to query `role_name` column (not non-existent `role_id`). Role multipliers now correctly applied (VIP Holder: 2.0x, Holder: 1.5x, etc.)
- **Version 2.4:** Production testing verified - All 3 riddles tested with fresh database, all systems working perfectly
- **Version 2.5:** Fixed Riddle #4 sequence reset logic bug (November 22, 2025 - Afternoon) - Reset logic was too aggressive, resetting when player was correctly turning levers OFF one by one. Now only resets if player goes backwards or turns wrong levers ON.
- **Version 2.6:** Added hidden slever sound for Riddle #4 completion (November 22, 2025 - Evening) - `hidden-slever.mp3` now plays when riddle is solved. Sound plays before UI code to ensure it always works even if UI fails. Added `playHiddenSleverSound()` function following same pattern as other game sounds.

**Latest Riddle Note (November 19, 2025 - Evening):**
- **Production Testing Complete:** Fresh database test with Narrrf (VIP Holder) account
  - **Test Method:** Normal user mode (no God Mode), fresh database with no riddle data
  - **Results:** ✅ **ALL TESTS PASSED**
    - All 3 riddles completed successfully (Riddle #1, #2, #3)
    - All 3 traits unlocked correctly:
      - `CHEESE_TEMPLE_RIDDLE_SOLVED` - 2025-11-19 01:36:37
      - `CHEESE_TEMPLE_RIDDLE_02_SOLVED` - 2025-11-19 01:38:30
      - `CHEESE_TEMPLE_RIDDLE_03_SOLVED` - 2025-11-19 01:39:13
    - All 3 DSPOINC rewards awarded with correct 2.0x multiplier:
      - Riddle #1: +1000 DSPOINC (base 500 × 2.00)
      - Riddle #2: +1000 DSPOINC (base 500 × 2.00)
      - Riddle #3: +1500 DSPOINC (base 750 × 2.00)
      - **Total: 3,500 DSPOINC** (matches VIP Holder expected total)
    - All database records verified (traits, score adjustments, riddle completions, user scores)
    - All frontend displays working perfectly (Recent Score Changes, 3D Puzzles Achievements)
  - **Status:** ✅ **PRODUCTION VERIFIED** - All systems working correctly

**Previous Riddle Note (November 19, 2025 - Afternoon):**
- **Role-Based Gaming Fix:** Fixed role multiplier system for riddle rewards
  - **Issue:** `getRoleMultiplier()` function was querying non-existent `role_id` column from `tbl_user_roles` table
  - **Root Cause:** Table only has `user_id`, `role_name`, `timestamp` columns (no `role_id`)
  - **Fix:** Updated function to query only `role_name` column and check against priority-based role list
  - **Result:** Role multipliers now correctly applied (VIP Holder: 2.0x = 1000 DSPOINC, Holder: 1.5x = 750 DSPOINC, etc.)
  - **Status:** ✅ **FIXED - Role multipliers working correctly**
- **Block Pushing Fix:** Fixed block pushing in Level 1 Riddle #2 Step 1 for normal mode
  - **Issue:** Block could only be pushed in God Mode, not in normal mode
  - **Fix:** Added fallback push mechanism when world movement direction calculation fails
  - **Result:** Block now pushable in both normal mode and God Mode
  - **Status:** ✅ **FIXED - Block pushing works in all modes**

**Previous Riddle Note (November 13, 2025):**
- **Trait Unlock API Fix:** Fixed 500 Internal Server Error - database schema mismatch resolved
  - **Issue:** Code was using incorrect column names (`trait_name`, `trait_value`, `created_at`, `updated_at`)
  - **Fix:** Updated SQL queries to use correct column names (`trait`, `timestamp`)
  - **Result:** Trait unlock API now works correctly with existing database schema
  - **Status:** ✅ **FIXED - API now works correctly**
- **Database Schema Verified:** Actual table structure uses `user_id`, `trait`, `timestamp` columns
- **Testing Status:** ✅ Trait unlock API fixed and ready for testing
- **DSPOINC Reward:** ✅ Working correctly (500 DSPOINC base, multiplied by role)

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

---

## 💾 DATABASE STRUCTURE & REWARD SYSTEM

### **Database Tables Used for Level 1 Rewards & Traits**

**See:** `12.0/RULES/15_RIDDLE_REWARD_DATABASE_RULE.md` for complete database documentation.

**Key Tables:**
- **`tbl_user_traits`** - Stores trait unlocks (`CHEESE_TEMPLE_RIDDLE_SOLVED`, `CHEESE_TEMPLE_RIDDLE_02_SOLVED`, `CHEESE_TEMPLE_RIDDLE_03_SOLVED`)
- **`tbl_riddle_completions`** - Tracks riddle completions with base reward, multiplier, and total reward
- **`tbl_user_scores`** - Stores DSPOINC balance updates (`game: 'cheese_temple_riddles'`, `source: 'riddle_completion'`)
- **`tbl_score_adjustments`** - Audit trail for "Recent Score Changes" display

**Riddle IDs:**
- `CHEESE_TEMPLE_RIDDLE_01` - Base reward: 500 DSPOINC
- `CHEESE_TEMPLE_RIDDLE_02` - Base reward: 500 DSPOINC
- `CHEESE_TEMPLE_RIDDLE_03` - Base reward: 750 DSPOINC
- `CHEESE_TEMPLE_RIDDLE_04_SECRET` - Base reward: 1,000 DSPOINC (fixed, no multiplier)

**API Endpoints:**
- **Reward API:** `/api/dev/riddle-reward.php` (POST method)
- **Trait API:** `/api/user/traits.php` (POST method)

**Query Examples:**
```sql
-- Check Level 1 traits for user
SELECT * FROM tbl_user_traits 
WHERE user_id = ? AND trait_name LIKE 'CHEESE_TEMPLE_RIDDLE%';

-- Check Level 1 rewards for user (all 4 riddles)
SELECT * FROM tbl_riddle_completions 
WHERE discord_id = ? AND riddle_id LIKE 'CHEESE_TEMPLE_RIDDLE%'
ORDER BY completed_at DESC;
```

**Total Rewards:**
- **Base Total:** 2,750 DSPOINC (500 + 500 + 750 + 1,000)
- **VIP Holder (2.0x):** 4,500 DSPOINC (1,000 + 1,000 + 1,500 + 1,000)
- **Holder (1.5x):** 3,625 DSPOINC (750 + 750 + 1,125 + 1,000)
- **Default (1.0x):** 2,750 DSPOINC

**Note:** Riddle #4 (Secret) always awards 1,000 DSPOINC regardless of role multiplier.

**Last Updated:** November 26, 2025 (Database Documentation Added)

---

## 🌌 **SKY SYSTEM**

### **Sky System Status:**
- ✅ **Fully Integrated** - Dynamic sky system working in Level 1
- ✅ **Per-Level Save** - Level 1 can save its own sky time settings
- ✅ **God Mode Controls** - Real-time sky configuration available

### **Features:**
- **Dynamic Day/Night Cycle** - Smooth transitions (sunrise, midday, sunset, night)
- **Procedural Clouds** - Scrolling cloud layers with color tinting
- **Twinkling Stars** - High-density starfield with individual star flickering
- **Sun Lensflare** - Infinite-distance flare effects
- **Per-Level Save** - Save custom time settings (hour, minute, clouds, stars, lensflare)

### **Default Configuration:**
- **Day/Night Cycle:** Enabled
- **Time of Day:** Day (default)
- **Cloud Density:** 0.7
- **Star Count:** 1500
- **Lensflare:** Enabled

### **Customization:**
When god mode is enabled, you can:
1. Adjust hour (0-23) and minute (0-59) sliders
2. Change cloud density (0.0-1.0)
3. Adjust star count (0-5000)
4. Toggle lensflare on/off
5. Click "💾 Save Time for Level" to save settings for Level 1

**Saved settings persist across sessions** and automatically load when entering Level 1.

**See:** `SKY_SYSTEM_COMPLETE_DOCUMENTATION.md` for full technical documentation.

**Last Updated:** December 2, 2025 - Sky system integrated and per-level save feature working

