# 🏹 RIDDLE #1 — THE HUNT (LEVEL 3)

**Document Created:** November 17, 2025  
**Riddle ID:** `CHEESE_TEMPLE_LEVEL3_RIDDLE_01`  
**Level:** Cheese Temple — Level 3 "The Hunt for the Cheese Monsters"  
**Status:** ✅ **3-STEP SYSTEM COMPLETE** — Step 0, Step 1 (5 monsters), Step 2 (5 monsters), Step 3 (portal)  
**Traits / Rewards:** 
- `CHEESE_TEMPLE_LEVEL3_STEP0` (+100 DSPOINC)
- `CHEESE_TEMPLE_LEVEL3_STEP1` (unlocked after catching 5 monsters)
- `CHEESE_TEMPLE_LEVEL3_STEP2` (unlocked after catching all 10 monsters)

---

## 📋 OVERVIEW

### Objective
Enter a massive 160x160 cheese stone arena and hunt animated monsters in two phases. Find the hidden cheese stone to unlock the hunt, then catch 5 monsters in Step 1, followed by 5 more monsters in Step 2, before the portal opens.

### Flow Summary
1. **Step 0 (Hidden Cheese Stone):** Stand on the hidden cheese stone platform for 10 seconds to unlock the hunt. Awards **+100 DSPOINC** and unlocks trait `CHEESE_TEMPLE_LEVEL3_STEP0`, then starts Step 1.
2. **Step 1 (First 5 Monsters):** Hunt and catch 5 monsters (Demon, Frog, Orc, Dino, Ninja). Each monster rewards **+50 DSPOINC**. After all 5 are caught, unlocks trait `CHEESE_TEMPLE_LEVEL3_STEP1` and starts Step 2.
3. **Step 2 (Second 5 Monsters):** Hunt and catch 5 more monsters (Blue Demon, Mushroom King, Tribal, Alien, Yeti). Each monster rewards **+50 DSPOINC**. After all 5 are caught, unlocks trait `CHEESE_TEMPLE_LEVEL3_STEP2` and activates Step 3.
4. **Step 3 (Portal):** Portal opens after completing both hunts. Approach the portal to see the completion screen with options to proceed to Level 4, restart Level 3, or return to Level 1/2.

---

## 🏗️ ENVIRONMENT

### Arena Specifications
- **Size:** 160x160 units (massive hunting ground)
- **Floor:** Cheese stone texture (repeating pattern)
  - **Flickering Fix:** Added `polygonOffset` to material and elevated floor by 0.01 units to prevent z-fighting
  - Texture repeats across the 160x160 area (size/4 pattern)
- **Walls:** Level 1-style dark walls (not white like Level 2)
- **Lighting:** Ambient + directional (darker atmosphere like Level 1)
- **Spawn Position:** Center of arena (x: 0, y: 1, z: 750)
- **Auto-Start:** Level 3 auto-starts in local development (same as Level 2)

### Hidden Cheese Stone Trigger
- **Position:** Random location in arena (currently: x: -20, y: 0.35, z: -30 relative to origin)
- **Visual:** Golden cheese block with emissive glow (same as Level 1/2)
- **Mechanic:** Sinks when player stands on it, rises when they step off
- **Timer:** 10 seconds standing required (RIDDLE_AIM_TIME)

---

## 🎮 GAMEPLAY MECHANICS

### Step 0: Unlock the Hunt
1. Player spawns in the center of the arena
2. Intro toast: "The Hunt for the Cheese Monsters — Find the hidden stone to begin. Hunt 5 monsters in Step 1, then 5 more in Step 2!"
3. Player must explore the arena to find the hidden cheese stone
4. Standing on the stone for 10 seconds:
   - Plays cheese platform sound
   - Visual block sinks down
   - Timer completes → Step 0 complete
   - Trait unlocked: `CHEESE_TEMPLE_LEVEL3_STEP0`
   - DSPOINC reward: +100
   - Toast: "Step 1 begins! Hunt 5 monsters!"
   - First monster (Demon) spawns at random location

### Step 1: Hunt First 5 Monsters
- **Monsters:** Demon, Frog, Orc, Dino, Ninja
- **Models:** `/textures/3d models/Monster 1/Big/glTF/[Monster].gltf`
- **Progressive Scaling:** Each monster gets bigger (0.4x → 0.6x → 0.8x → 1.0x → 1.2x)
- **Rewards:** +50 DSPOINC per monster caught
- **Capture:** Stand within 2.5 units of monster to catch it
- **Completion:** After all 5 monsters caught, unlocks trait `CHEESE_TEMPLE_LEVEL3_STEP1` and starts Step 2

### Step 2: Hunt Second 5 Monsters
- **Monsters:** Blue Demon, Mushroom King, Tribal, Alien, Yeti
- **Models:** `/textures/3d models/Monster 1/Big/glTF/[Monster].gltf`
- **Progressive Scaling:** Each monster gets bigger (0.4x → 0.6x → 0.8x → 1.0x → 1.2x)
- **Rewards:** +50 DSPOINC per monster caught
- **Capture:** Stand within 2.5 units of monster to catch it
- **Completion:** After all 5 monsters caught, unlocks trait `CHEESE_TEMPLE_LEVEL3_STEP2` and activates portal (Step 3)

### Monster System
- **Spawn:** Random position within arena bounds (20-unit margin from walls)
- **Animation:** Full walk/run animation loop for all monsters
- **Movement:** Random waypoint system (picks new target when reaching current)
- **Speed:** 3.0 units/second (slightly slower for bigger monsters)
- **Behavior:** Continuously runs around arena, player must hunt/catch each one
- **Visual Feedback:** Monster name shown in toast when spawned ("🏹 Hunt the [Name]!")

---

## 🎯 REWARDS & TRAITS

### Step 0 Rewards
- **Trait:** `CHEESE_TEMPLE_LEVEL3_STEP0`
- **DSPOINC:** +100 base reward

### Step 1 Rewards
- **Per Monster:** +50 DSPOINC (5 monsters = 250 DSPOINC total)
- **Trait:** `CHEESE_TEMPLE_LEVEL3_STEP1` (unlocked after all 5 monsters caught)

### Step 2 Rewards
- **Per Monster:** +50 DSPOINC (5 monsters = 250 DSPOINC total)
- **Trait:** `CHEESE_TEMPLE_LEVEL3_STEP2` (unlocked after all 5 monsters caught)

### Total Rewards
- **Step 0:** +100 DSPOINC
- **Step 1:** +250 DSPOINC (5 × 50)
- **Step 2:** +250 DSPOINC (5 × 50)
- **Grand Total:** +600 DSPOINC for completing all steps

---

## 🔧 TECHNICAL IMPLEMENTATION

### Core Functions
- `buildLevel3HuntArena()` — Builds 160x160 arena with cheese stone floor
- `createLevel3TriggerBlock()` — Creates hidden cheese stone trigger
- `spawnLevel3Monster(monsterPath)` — Spawns monster with full animation and progressive scaling
- `spawnNextLevel3Monster()` — Spawns next monster in current step's queue
- `startLevel3Step2()` — Transitions from Step 1 to Step 2
- `captureLevel3Monster(monster)` — Handles monster capture, rewards, and step transitions
- `updateLevel3Monsters(delta)` — Updates monster movement and animation
- `updateLevel3Step0(delta)` — Handles trigger block standing logic
- `updateLevel3(delta)` — Main Level 3 update loop
- `handleLevel3Collisions()` — Player collision boundaries
- `warpToLevel3()` — Warp function from Level 2 portal
- `unlockLevel3Step1Trait()` — Unlocks Step 1 completion trait
- `unlockLevel3Step2Trait()` — Unlocks Step 2 completion trait

### State Management
- `level3State` — Arena state, monsters array, portal, completion screen
- `level3RiddleState` — Step progress (0-3), trigger block, trait unlocks, monster tracking
  - `currentStep`: 0 = platform, 1 = first 5 monsters, 2 = second 5 monsters, 3 = portal
  - `monstersCaught`: Total monsters caught across all steps
  - `currentMonsterIndex`: Index within current step (0-4)

### Monster Queues
- `LEVEL3_MONSTER_QUEUE_STEP1`: [Demon, Frog, Orc, Dino, Ninja]
- `LEVEL3_MONSTER_QUEUE_STEP2`: [Blue Demon, Mushroom King, Tribal, Alien, Yeti]

### Integration Points
- Level 2 portal completion screen → "Proceed to Level 3" button → `warpToLevel3()`
- Collision system → `handleLevel3Collisions()` for 160x160 boundaries
- Animate loop → `updateLevel3(delta)` called when `currentLevel === LEVEL_IDS.LEVEL3`
- **G Key (God Mode):** Press G to cycle through Level 3 steps (Step 0 → Step 1 → Step 2 → Step 3 → Step 0)
  - Step 0: Reset to beginning (cheese stone visible)
  - Step 1: Complete Step 0, spawn first 5 monsters
  - Step 2: Complete Step 1, spawn second 5 monsters
  - Step 3: Complete Step 2, activate portal

---

## 🚀 FUTURE EXPANSION

### Completed Features
- ✅ **Step 0:** Hidden cheese stone platform unlock
- ✅ **Step 1:** First 5 monsters with progressive scaling
- ✅ **Step 2:** Second 5 monsters with progressive scaling
- ✅ **Step 3:** Portal activation after completing both hunts
- ✅ **Completion Screen:** Level 4 teaser or return to previous levels

### Potential Enhancements
- Different monster behaviors (faster, slower, evasive)
- Monster respawn system for failed captures
- Additional monster sets for future levels
- Boss monster variants with special mechanics

---

## 📝 NOTES

- Level 3 uses Level 1-style dark walls (not white like Level 2)
- **Floor Flickering Fixed:** Added `polygonOffset` material properties and elevated floor by 0.01 units to prevent z-fighting issues
- Cheese stone floor texture repeats across the 160x160 area (size/4 pattern)
- Monster animation system reuses Level 1 NPC monster patterns
- Demon spawns at random location to make hunt challenging
- Monster continuously moves to random waypoints for dynamic gameplay
- **Auto-Start:** Level 3 auto-starts in local development via `DEBUG_FORCE_LEVEL3_START` flag
- **G Key Support:** God Mode G key cycles through Level 3 steps for quick testing
- **L Key Support:** God Mode L key opens level selector menu to jump to any level (Level 1, 2, or 3)

---

**Last Updated:** November 17, 2025  
**Status:** ✅ **3-STEP SYSTEM COMPLETE** — All steps implemented with traits and DSPOINC rewards

---

## ✅ TESTING VERIFICATION (November 17, 2025)

### Database Verification (LocalTester - LOCAL_TEST_DISCORD)

**All Traits Confirmed:**
- ✅ `CHEESE_TEMPLE_LEVEL3_STEP0` — 2025-11-17 18:52:28
- ✅ `CHEESE_TEMPLE_LEVEL3_STEP1` — 2025-11-17 18:53:29
- ✅ `CHEESE_TEMPLE_LEVEL3_STEP2` — 2025-11-17 18:54:21

**All DSPOINC Rewards Confirmed:**
- ✅ Step 0: +100 DSPOINC (2025-11-17 18:08:13)
- ✅ Step 1 Monsters: 5 × 50 = 250 DSPOINC (18:23:31 to 18:24:22)
- ✅ Step 2 Monsters: 5 × 50 = 250 DSPOINC (18:53:38 to 18:54:21)
- ✅ **Total: 600 DSPOINC** (100 + 250 + 250)

**Verification Summary:**
- ✅ All 3 traits present in database
- ✅ All 10 monsters caught and rewarded (5 + 5)
- ✅ All DSPOINC rewards recorded correctly
- ✅ Portal completion screen working
- ✅ Restart Level 3 button functional

### Step 0 Successfully Tested
- **Trait Unlock:** `CHEESE_TEMPLE_LEVEL3_STEP0` confirmed in database
- **DSPOINC Reward:** +100 DSPOINC confirmed
- **Monster Spawn:** First monster (Demon) spawns correctly
- **Visual Feedback:** Cheese stone trigger block animation working (sinks/rises)
- **Floor Stability:** Flickering issue resolved with polygonOffset fix

### Step 1 Successfully Tested
- **Monsters Caught:** All 5 monsters (Demon, Frog, Orc, Dino, Ninja)
- **Progressive Scaling:** 0.4x → 0.6x → 0.8x → 1.0x → 1.2x working correctly
- **DSPOINC Rewards:** All 5 × 50 = 250 DSPOINC recorded
- **Trait Unlock:** `CHEESE_TEMPLE_LEVEL3_STEP1` confirmed after all 5 caught
- **Step Transition:** Automatically starts Step 2

### Step 2 Successfully Tested
- **Monsters Caught:** All 5 monsters (Blue Demon, Mushroom King, Tribal, Alien, Yeti)
- **Progressive Scaling:** 0.4x → 0.6x → 0.8x → 1.0x → 1.2x working correctly
- **DSPOINC Rewards:** All 5 × 50 = 250 DSPOINC recorded
- **Trait Unlock:** `CHEESE_TEMPLE_LEVEL3_STEP2` confirmed after all 5 caught
- **Portal Activation:** Portal opens correctly after Step 2 completion

### Monster System Verified
- ✅ **Animation:** Full walk/run animation loop playing correctly for all monsters
- ✅ **Movement:** Random waypoint system working - monsters continuously move around arena
- ✅ **Progressive Scaling:** Each monster gets bigger (0.4x to 1.2x) within each step
- ✅ **Positioning:** Monsters spawn at random locations within arena bounds (20-unit margin)
- ✅ **Capture Detection:** Proximity detection (2.5 units) working correctly
- ✅ **Monster Names:** Spawn messages show "🏹 Hunt the [Name]!" correctly

### Portal System Verified
- ✅ **Texture:** Original Portal1.png texture displayed (no blue tint)
- ✅ **Size:** 6×8 units (increased for better visibility)
- ✅ **Entry Distance:** 5.0 units (increased for easier entry)
- ✅ **Completion Screen:** Shows correctly when player approaches portal
- ✅ **Restart Button:** "🧀 Stay in Level 3" button restarts level correctly
- ✅ **Navigation:** All buttons (Level 4, Level 2, Level 1) working

### Known Working Features
- ✅ Hidden cheese stone trigger block (Step 0)
- ✅ 10-second standing timer with audio cue
- ✅ Step 0 trait unlock and DSPOINC reward (+100)
- ✅ Step 1: 5 monsters with progressive scaling
- ✅ Step 1 trait unlock after all 5 monsters caught
- ✅ Step 2: 5 monsters with progressive scaling
- ✅ Step 2 trait unlock after all 5 monsters caught
- ✅ +50 DSPOINC per monster (10 monsters = 500 DSPOINC total)
- ✅ Monster spawning system with step-based queues
- ✅ Monster animation system (walk/run loops)
- ✅ Monster random waypoint movement
- ✅ Monster capture proximity detection (2.5 units)
- ✅ Step transitions (Step 1 → Step 2 → Step 3)
- ✅ Portal activation after Step 2 completion
- ✅ Completion screen with level navigation and restart option
- ✅ Floor flickering fix (polygonOffset)
- ✅ Auto-start to Level 3
- ✅ G key step cycling in God Mode (0 → 1 → 2 → 3 → 0)
- ✅ Monster name display in spawn messages ("🏹 Hunt the [Name]!")
- ✅ God Mode 4x speed (2x faster than before)

