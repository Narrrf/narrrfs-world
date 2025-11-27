# 🎯 RIDDLE #1 — THE FIRST SHOT (LEVEL 4)

**Document Created:** November 17, 2025  
**Last Updated:** November 27, 2025 (GOD Mode Initialization Fix)  
**Riddle ID:** `CHEESE_TEMPLE_LEVEL4_RIDDLE_01`  
**Level:** Cheese Temple — Level 4 "The First Shot"  
**Status:** ✅ **FULLY IMPLEMENTED** — 50-cheese shooting challenge + 30-monster wave challenge with wave-based progressive difficulty and enhanced AI  
**Initialization:** ✅ **VERIFIED WORKING** — GOD mode warp works correctly, all elements spawn on first attempt  
**Traits / Rewards:** 
- `CHEESE_TEMPLE_LEVEL4_STEP0` (+100 DSPOINC)
- `CHEESE_TEMPLE_LEVEL4_STEP1` (unlocked after shooting 50 cheeses)
- `CHEESE_TEMPLE_LEVEL4_STEP2` (+200 DSPOINC - unlocked when entering portal)

---

## 📋 OVERVIEW

### Objective
Enter a massive 160x160 cheese stone arena and complete "The First Shot" challenge. Find the hidden cheese stone to unlock Step 1, then shoot 50 floating cheese entities with a weapon. The challenge features progressive difficulty - cheeses get smaller, faster, and smarter as you progress.

### Flow Summary
1. **Step 0 (Hidden Cheese Stone):** Stand on the hidden cheese stone platform for 10 seconds to unlock Step 1. Awards **+100 DSPOINC** and unlocks trait `CHEESE_TEMPLE_LEVEL4_STEP0`, then shows 3-second countdown before starting Step 1.
2. **Step 1 (Shoot 50 Cheeses in Waves):** Shoot 50 floating cheese entities with a first-person weapon in **12 waves of 4 cheeses each, plus 1 final wave of 2 big aggressive cheeses**. Each wave shows a 3-second countdown popup (small mad mode style notification in top-right corner) before spawning. Each cheese rewards **+50 DSPOINC**. Difficulty increases progressively by wave - cheeses get smaller (100% → 60% size), faster (1x → 2.5x speed), smarter (better dodging), and change color (yellow → orange → red) as waves progress. After all 50 are shot, unlocks trait `CHEESE_TEMPLE_LEVEL4_STEP1` and Step 2 (monster waves) begins.
3. **Step 2 (Shoot 30 Monsters in Waves):** After completing cheese waves, players face **10 waves of 3 monsters each (30 total)**. Each wave shows a 3-second countdown popup (small mad mode style notification in top-right corner) before spawning. Each monster rewards **+50 DSPOINC**. Difficulty increases progressively - monsters get larger, faster, and smarter. Final wave features flying monsters that can shoot projectiles at the player. After all 30 are defeated, unlocks trait `CHEESE_TEMPLE_LEVEL4_STEP2` and portal appears.
4. **Step 3 (Portal Completion):** Enter the portal to see completion screen with options to proceed to Level 5, restart Level 4, or return to other levels.

---

## 🏗️ ENVIRONMENT

### Arena Specifications
- **Size:** 160x160 units (same as Level 3)
- **Floor:** Cheese stone texture (repeating pattern)
  - **Flickering Fix:** Added `polygonOffset` to material and elevated floor by 0.01 units to prevent z-fighting
  - Texture repeats across the 160x160 area (size/4 pattern)
- **Walls:** Level 1-style dark walls (not white like Level 2)
- **Lighting:** Ambient + directional (darker atmosphere like Level 1)
- **Spawn Position:** Center of arena (x: 0, y: 1, z: 950)
- **Origin:** (x: 0, y: 0, z: 1000)

### Hidden Cheese Stone Trigger
- **Position:** Random location in arena (currently: x: -20, y: 0.35, z: -30 relative to origin)
- **Visual:** Normal cheese stone texture (no yellow glow)
- **Mechanic:** Sinks when player stands on it, rises when they step off
- **Timer:** 10 seconds standing required (RIDDLE_AIM_TIME)

---

## 🎮 GAMEPLAY MECHANICS

### Step 0: Unlock The First Shot
1. Player spawns in the center of the arena
2. Player must find the hidden cheese stone platform
3. Stand on the platform for 10 seconds
4. Platform sinks down when player stands on it
5. Platform rises back up when player steps off
6. After 10 seconds, Step 0 completes:
   - Awards **+100 DSPOINC**
   - Unlocks trait `CHEESE_TEMPLE_LEVEL4_STEP0`
   - Shows toast: "Step 1 begins! The First Shot awaits..."
   - Ready for Step 1 implementation

### Step 1: Shoot 50 Floating Cheeses (Wave-Based System)
1. After Step 0 completes, **3-second countdown popup** appears (like Snake/Tetris)
2. **Wave System:**
   - **12 waves** of 4 cheeses each = 48 cheeses
   - **Final wave:** 2 extra big aggressive cheeses = 50 total
   - **Wave countdown:** 3-second popup between each wave
   - **Wave completion:** New wave spawns only after all cheeses in current wave are caught
3. **Shooting System:**
   - First-person weapon viewmodel system with **multi-weapon slot support**
   - **Weapon Slot System:** Press number keys (1-9) to switch between weapons
     - **Slot 1:** Pistol Mk I (Fire Weapons 1) - Default weapon
     - **Slot 2:** Sci-Fi Pistol 1 (SF13 from Sci-Fi Modular Gun Pack) - Alternative weapon
     - **Future Slots:** 3-9 can be added by extending `LEVEL4_WEAPON_SLOTS`
     - **Transform System:** Each weapon type uses appropriate rotation/scale via `LEVEL4_WEAPON_TRANSFORMS`
     - **Inventory Indicator:** Both weapons glow green in Level 2 (marked as gameplay inventory items)
   - **Weapon Rendering Standard:** Centered position (0.0, -0.4, -0.5), rotated 90° left (Math.PI/2) to match 3D environment, slight downward tilt (-0.15) to align with crosshair
   - Uses `processWeaponMaterial()` function (same as Level 2) for proper material conversion
   - Automatic scaling based on weapon bounds (target size: 0.3 units)
   - **Weapon Caching:** Weapons are cached to avoid reloading when switching
   - **HUD Display:** Shows current weapon name and slot number
   - Left mouse button to shoot (mousedown event)
   - Raycasting from camera center (crosshair position)
   - Hit indicator shows cheese-themed radial gradient flash
   - Shooting sound: Space Invaders `normal_shoot.wav`
   - Weapon bobbing animation when moving (X, Y, Z axis bobbing + rotation)
   - Recoil animation on each shot (pushes back and up, rotates upward)
   - **See Technical Documentation Section 22 for complete weapon rendering standard**
4. **Wave-Based Spawning:**
   - **Smart Spawn Positioning:** Cheeses spawn strategically based on wave difficulty
     - Waves 1-4: Spawn in visible areas (25-60 units from center)
     - Waves 5-8: Mixed positions, some behind player (30-80 units)
     - Waves 9-12: Difficult positions, behind player, far corners (30-80 units)
     - Final Wave: Far away or behind player (50-90 units), 50% chance behind
   - **Anti-Clustering:** Cheeses maintain minimum 20-unit distance from each other
   - **Wave Countdown:** 3-second popup between waves (small mad mode style notification in top-right corner - doesn't block gameplay view)
5. **Progressive Difficulty System (Wave-Based):**
   - **Wave 1-2:** Easy (20% aggressiveness, 30% smartness, 1.0x speed, 100% size)
   - **Wave 3-4:** Medium (35% aggressiveness, 45% smartness, 1.2x speed, 95% size)
   - **Wave 5-6:** Hard (50% aggressiveness, 60% smartness, 1.4x speed, 90% size)
   - **Wave 7-8:** Very Hard (65% aggressiveness, 75% smartness, 1.6x speed, 85% size)
   - **Wave 9-10:** Extreme (80% aggressiveness, 85% smartness, 1.8x speed, 75% size)
   - **Wave 11-12:** Nightmare (90% aggressiveness, 95% smartness, 2.0x speed, 70% size)
   - **Final Wave:** Maximum (100% aggressiveness, 100% smartness, 2.5x speed, 60% size, but 20% bigger)
   - Difficulty scales by wave number: `waveProgress = (waveNumber - 1) / 13`
6. **Color System (Like Snake Bosses):**
   - **Waves 1-3:** Yellow (easy) - `#ffff00`
   - **Waves 4-6:** Orange (medium) - `#ffaa00`
   - **Waves 7-9:** Dark Orange (hard) - `#ff6600`
   - **Waves 10-12:** Red-Orange (very hard) - `#ff3300`
   - **Final Wave:** Red (extreme) - `#ff0000`
   - Colors applied as emissive glow (intensity 0.4-0.8 based on wave)
7. **Enhanced AI Behaviors (Super Intelligent & Unpredictable):**
   - **Predictive Dodging:** Detects when player is aiming (within 30° of crosshair) and dodges more aggressively (95% chance)
   - **Group Coordination:** Cheeses spread out and avoid clustering (maintains 25-unit minimum distance)
   - **Smart Height Variation:** Flies 3-5 units higher when player is aiming at it
   - **Speed Burst System:** Unpredictable speed bursts (1.0x to 1.5x multiplier, 0.3-0.7 second duration)
   - **Unpredictability Factor:** Random behavior variations (0.5-1.0) make each cheese unique
   - **Direction Changes:** Unpredictable dodge angles based on unpredictability factor
   - **Always Reachable:** Cheeses stay within arena bounds, never impossible to catch
8. **Cheese Behavior:**
   - Cheeses jump and fly around with enhanced AI behavior
   - **Mad Mode:** Can enter "mad mode" randomly (18% chance, 4-second duration, 15-second cooldown)
   - **Mad Mode Color Variants:** Uses wave color as base, intensifies in mad mode (abstract color variants)
   - More aggressive dodging and faster target changes in mad mode
   - Restores wave color after mad mode ends
7. **Explosion Effect:**
   - When hit, cheese explodes into 12 cheese particles
   - Particles fade out over 1 second with gravity
   - Cartoon/arcade style visual feedback
9. **Progress Tracking:**
   - HUD displays:
     - Current wave (color-coded): "Wave X/13" or "FINAL WAVE"
     - Wave progress: "Wave Progress: X/4" (or X/2 for final wave)
     - Total progress: "🔫 Cheeses Shot: X/50 (X%)"
   - Updates in real-time as cheeses are hit
   - Toast notifications for each cheese hit
10. Each cheese shot rewards **+50 DSPOINC**
11. After all 50 cheeses are shot:
    - Unlocks trait `CHEESE_TEMPLE_LEVEL4_STEP1`
    - Shows completion toast: "Step 1 Complete! All cheeses caught! Portal opening..."
    - Portal appears at back of arena
    - Progress to Step 2 (portal completion screen)

### Step 2: Portal Completion
1. Portal appears at back of arena after 50 cheeses shot
2. Portal has suction effect (pulls player closer when within 6 units)
3. Enter portal (within 2.5 units horizontally, 3 units vertically) to complete Step 2:
   - Plays **"LEVEL UP!" sound** (same as Level 1)
   - Awards **+200 DSPOINC**
   - Unlocks trait `CHEESE_TEMPLE_LEVEL4_STEP2`
   - Shows completion screen
4. Completion screen options:
   - "🚀 Proceed to Level 5" (restarts Level 4 for now)
   - "🧀 Stay in Level 4" (restarts Level 4)
   - "🔄 Return to Level 3" (warps to Level 3)
   - "🏠 Return to Level 1" (restarts Level 1)

---

## 🔧 TECHNICAL IMPLEMENTATION

### Core Functions
- `buildLevel4FirstShotArena()` — Builds the 160x160 arena with cheese stone floor and dark walls
- `createLevel4TriggerBlock()` — Creates the hidden cheese stone trigger block
- `calculateLevel4WaveDifficulty(waveNumber)` — Calculates wave-based difficulty (aggressiveness, smartness, speed, size, color)
- `showLevel4WaveCountdown(waveNumber, onComplete)` — Shows 3-second countdown popup between waves (like Snake/Tetris)
- `spawnLevel4Cheeses(count)` — Spawns exactly 4 cheeses per wave (or 2 for final wave) with wave-based difficulty and smart positioning
- `resetLevel4Progress()` — Resets all Level 4 state, clears cheeses array, resets wave system
- `checkLevel4TriggerBlockStanding()` — Checks if player is standing on trigger block
- `updateLevel4Step0(delta)` — Updates Step 0 logic (timer, trait unlock, DSPOINC reward)
- `updateLevel4TriggerBlockVisual(delta)` — Animates the trigger block sinking/rising
- `updateLevel4(delta)` — Main update loop for Level 4 (updates wave countdown, cheeses, portal, HUD, particles)
- `captureLevel4Cheese(cheeseIndex)` — Handles cheese hit, creates explosion, awards DSPOINC, checks wave completion, triggers next wave countdown
- `completeLevel4Step1()` — Completes Step 1, unlocks trait, creates portal
- `createLevel4Portal()` — Creates portal mesh at back of arena
- `handleLevel4PortalProximity(playerPos, delta)` — Handles portal suction and entry detection
- `showLevel4CompletionScreen()` — Shows completion screen with navigation options
- `restartLevel4()` — Restarts Level 4 from beginning
- `hideLevel4CompletionScreen()` — Hides completion screen
- `handleLevel4Shooting()` — Handles left mouse button shooting with raycasting
- `loadLevel4WeaponViewmodel()` — Loads and attaches weapon model to camera
- `removeLevel4WeaponViewmodel()` — Removes weapon model from camera
- `updateLevel4WeaponAnimation(delta, isMoving)` — Updates weapon bobbing and recoil animations
- `createLevel4HitIndicator()` — Creates hit indicator HTML overlay
- `showLevel4HitIndicator()` — Shows hit indicator flash
- `updateLevel4HitIndicator(delta)` — Updates hit indicator fade-out
- `createCheeseExplosionEffect(position)` — Creates 12-particle explosion effect
- `createLevel4ProgressHUD()` — Creates progress HUD element
- `updateLevel4ProgressHUD()` — Updates progress HUD with current wave, wave progress, and total count (color-coded)
- `handleLevel4Collisions()` — Handles player collisions with walls, floor, and ceiling
- `unlockLevel4Trait(traitKey, description)` — Unlocks trait via API
- `awardLevel4DspoincReward(stepId, amount, description)` — Awards DSPOINC via API
- `showLevel4IntroToast()` — Shows intro message when entering Level 4
- `warpToLevel4()` — Warps player to Level 4
- `cycleLevel4Step()` — God Mode G key: cycles between Step 0 and Step 1

### State Management
```javascript
const level4State = {
  built: false,
  group: new THREE.Group(),
  introShown: false,
  introToastElement: null,
  portal: null,
  portalActive: false,
  completionScreenShown: false,
  cheeses: [], // Array of FloatingCheese instances (continuously spawned)
  weaponViewmodel: null, // 3D weapon model in first-person view
  weaponBobPhase: 0, // For weapon bobbing animation
  weaponRecoilOffset: 0, // For recoil animation
  lastShotTime: 0, // Cooldown tracking
  shotCooldown: 0.2, // 200ms between shots (5 shots per second)
  explosionParticles: [] // Array of explosion particle objects
};

const level4RiddleState = {
  step0Complete: false,
  step0StandingSoundPlayed: false,
  triggerBlockTimer: 0,
  triggerBlock: null,
  triggerBlockVisual: null,
  triggerBlockTargetY: 0,
  step0TraitUnlocked: false,
  step1Active: false,
  cheesesCaught: 0, // Total cheeses shot (target: 50)
  currentWave: 1, // Current wave (1-13: 12 waves + 1 final wave)
  cheesesInCurrentWave: 0, // Cheeses caught in current wave (0-4)
  waveCountdownActive: false, // Is countdown popup showing?
  waveCountdownTime: 0, // Countdown timer
  waveCountdownCallback: null, // Callback to execute after countdown
  step1TraitUnlocked: false,
  step2Complete: false,
  step2TraitUnlocked: false,
  difficultyLevel: 1 // Progressive difficulty (1-50)
};
```

### Configuration
```javascript
const level4Config = {
  size: 160,
  origin: new THREE.Vector3(0, 0, 1000),
  wallHeight: 30,
  spawnPosition: new THREE.Vector3(0, 1, 950),
  portalPosition: new THREE.Vector3(0, 5, 1060) // Within level bounds (far edge is z=1080)
};
```

### Constants
- `LEVEL4_STEP0_TRAIT = "CHEESE_TEMPLE_LEVEL4_STEP0"`
- `LEVEL4_STEP1_TRAIT = "CHEESE_TEMPLE_LEVEL4_STEP1"`
- `LEVEL4_STEP2_TRAIT = "CHEESE_TEMPLE_LEVEL4_STEP2"`
- `LEVEL4_CHEESES_TO_CATCH = 50` (total cheeses to shoot)
- `LEVEL4_WAVES_COUNT = 12` (12 waves of 4 cheeses each = 48 cheeses)
- `LEVEL4_CHEESES_PER_WAVE = 4` (4 cheeses per wave)
- `LEVEL4_FINAL_WAVE_CHEESES = 2` (Final wave: 2 extra big aggressive cheeses)
- `LEVEL4_WAVE_COUNTDOWN_TIME = 3` (3 second countdown between waves)
- `LEVEL4_DSPOINC_PER_CHEESE = 50` (DSPOINC reward per cheese)
- `LEVEL4_WEAPON_PATH = "/textures/3d models/Fire Weapons 1/FBX/Pistol_1.fbx"`
- `LEVEL4_SHOOT_RANGE = 200` (maximum shooting range)
- `LEVEL4_HIT_INDICATOR_DURATION = 0.3` (hit indicator flash duration)
- `RIDDLE_AIM_TIME = 10.0` (seconds to stand on trigger block)

### Cheese System (Wave-Based)
- **FloatingCheese Class:** Enhanced with wave-based properties and advanced AI behaviors
- **Wave System:**
  - **12 waves** of 4 cheeses each (48 total)
  - **Final wave:** 2 extra big aggressive cheeses (50 total)
  - **Wave countdown:** 3-second popup between waves (like Snake/Tetris)
  - **Wave completion:** New wave spawns only after all cheeses in current wave are caught
- **Spawn System:** Wave-based spawning with smart positioning
  - **Smart Spawn Positioning:** Strategic spawns based on wave difficulty
    - Waves 1-4: Visible areas (25-60 units from center)
    - Waves 5-8: Mixed positions, some behind player (30-80 units)
    - Waves 9-12: Difficult positions, behind player, far corners (30-80 units)
    - Final Wave: Far away or behind player (50-90 units), 50% chance behind
  - **Anti-Clustering:** Cheeses maintain minimum 20-unit distance from each other
- **Roam Radius:** 70 units (for 160x160 arena)
- **Base Height:** 8 units above ground (with smart height variation)
- **Wave-Based Progressive Difficulty:**
  - Calculated by `calculateLevel4WaveDifficulty(waveNumber)`
  - **Size multiplier:** 100% (wave 1) → 60% (final wave)
  - **Speed multiplier:** 1.0x (wave 1) → 2.5x (final wave)
  - **Aggressiveness:** 20% (wave 1) → 100% (final wave)
  - **Smartness:** 30% (wave 1) → 100% (final wave)
  - **Dodge distance:** 12 units (wave 1) → 30 units (final wave)
  - **Target change speed:** 3.0s (wave 1) → 0.5s (final wave)
- **Color System (Like Snake Bosses):**
  - Waves 1-3: Yellow (`#ffff00`) - Easy
  - Waves 4-6: Orange (`#ffaa00`) - Medium
  - Waves 7-9: Dark Orange (`#ff6600`) - Hard
  - Waves 10-12: Red-Orange (`#ff3300`) - Very Hard
  - Final Wave: Red (`#ff0000`) - Extreme
  - Colors applied as emissive glow (intensity 0.4-0.8 based on wave)
- **Enhanced AI Behaviors:**
  - **Predictive Dodging:** Detects when player is aiming (within 30° of crosshair) and dodges more aggressively (95% chance)
  - **Group Coordination:** Cheeses spread out and avoid clustering (maintains 25-unit minimum distance)
  - **Smart Height Variation:** Flies 3-5 units higher when player is aiming at it
  - **Speed Burst System:** Unpredictable speed bursts (1.0x to 1.5x multiplier, 0.3-0.7 second duration)
  - **Unpredictability Factor:** Random behavior variations (0.5-1.0) make each cheese unique
  - **Direction Changes:** Unpredictable dodge angles based on unpredictability factor
  - **Always Reachable:** Cheeses stay within arena bounds, never impossible to catch
- **Mad Mode:** 18% chance, 4-second duration, 15-second cooldown
  - **Color Variants:** Uses wave color as base, intensifies in mad mode (abstract color variants)
  - Restores wave color after mad mode ends
- **Hit Detection:** Raycasting from camera center (crosshair position)

### Shooting System
- **Weapon Viewmodel:** Pistol_1.fbx model attached to camera in first-person view
- **Weapon Rendering Standard (Established November 19, 2025):**
  - **Position:** Centered (0.0, -0.4, -0.5) - center-bottom of screen
  - **Rotation:** (-0.15, Math.PI/2, 0.0) - 90° left rotation to match 3D environment, slight downward tilt for crosshair alignment
  - **Material Processing:** Uses `processWeaponMaterial()` function (same as Level 2) for proper MeshStandardMaterial conversion
  - **Scaling:** Automatic based on weapon bounds (target size: 0.3 units)
  - **Rendering:** renderOrder 999, frustumCulled false, always visible
  - **Animation:** X/Y/Z bobbing when moving, recoil on shot (back 0.08, up 0.04 units)
  - **See:** Technical Documentation Section 22 "WEAPON VIEWMODEL RENDERING STANDARD" for complete implementation details
- **Controls:** Left mouse button (mousedown event) to shoot
- **Raycasting:** From camera center (0, 0) with 200 unit range
- **Hit Indicator:** Cheese-themed radial gradient overlay (yellow/orange)
- **Weapon Animation:**
  - Bobbing when moving (based on player velocity)
  - Recoil animation on each shot
  - Smooth return to rest position
- **Audio:** Space Invaders `normal_shoot.wav` sound effect (weapon 1), SF13 triple-shot sound (weapon 2)
- **Cooldown:** 0.2 seconds (5 shots per second max)
- **Heat/Overload System (Like Space Invaders):**
  - **Heat Generation:** 6 heat per shot (normal weapon), 18 heat per triple shot (SF13)
  - **Max Heat:** 150 (triggers overheat at 100%)
  - **Heat Decay:** 0.1 per frame (~6 per second at 60 FPS) when not shooting
  - **Overheat Cooldown:** 6 seconds (must wait before shooting again)
  - **Heat Display:** HUD shows heat bar with color-coded status (Green → Yellow → Orange → Red)
  - **Overheat State:** Weapon cannot fire during cooldown period
  - **Status:** Normal (0-49%), Warning (50-79%), Critical (80-99%), OVERHEATED (100%)

---

## 🎯 REWARDS & TRAITS

### Step 0 Rewards
- **DSPOINC:** +100 DSPOINC
- **Trait:** `CHEESE_TEMPLE_LEVEL4_STEP0`
- **Description:** "Level 4 Step 0"

### Step 1 Rewards
- **DSPOINC:** +50 DSPOINC per cheese (50 cheeses = 2,500 DSPOINC total)
- **Trait:** `CHEESE_TEMPLE_LEVEL4_STEP1` (unlocked after all 50 cheeses shot)
- **Description:** "Level 4 Step 1"

### Step 2 Rewards
- **DSPOINC:** +200 DSPOINC
- **Trait:** `CHEESE_TEMPLE_LEVEL4_STEP2` (unlocked when entering portal)
- **Description:** "Level 4 Step 2 - Portal Entry"

### Total Rewards
- **Step 0:** +100 DSPOINC
- **Step 1:** +2,500 DSPOINC (50 × 50)
- **Step 2:** +200 DSPOINC
- **Grand Total:** +2,800 DSPOINC

---

## 🔗 INTEGRATION POINTS

### Game Loop
- `updateLevel4(delta)` called in `animate()` when `currentLevel === LEVEL_IDS.LEVEL4`
- `handleLevel4Collisions()` called in `playerCollisions()` when `currentLevel === LEVEL_IDS.LEVEL4`

### Level Navigation
- **From Level 3:** Portal button "🚀 Proceed to Level 4" calls `warpToLevel4()`
- **Level Selector:** "🎯 Level 4 - The First Shot" button in God Mode (L key)
- **God Mode Support:**
  - **G Key:** Cycles between Step 0 and Step 1 (via `cycleLevel4Step()`)
  - **L Key:** Opens level selector (works in all levels)
- **Auto-Start:** *[Can be added if needed]*

### API Endpoints
- **Trait Unlock:** `/api/user/unlock-trait.php`
- **DSPOINC Reward:** `/api/user/award-dspoinc-reward.php`

---

## 🧪 TESTING

### Step 0 Testing Checklist
- [ ] Player spawns in center of arena
- [ ] Hidden cheese stone trigger block is visible
- [ ] Platform sinks when player stands on it
- [ ] Platform rises when player steps off
- [ ] Timer counts up when standing (10 seconds required)
- [ ] Timer decays when not standing
- [ ] Step 0 completes after 10 seconds
- [ ] Trait `CHEESE_TEMPLE_LEVEL4_STEP0` unlocks
- [ ] +100 DSPOINC reward awarded
- [ ] Toast message appears: "Step 1 begins! The First Shot awaits..."
- [ ] Trigger block disappears after completion
- [ ] 2 cheeses spawn after Step 0 completes

### Step 1 Testing Checklist
- [ ] Initial batch of 1-5 cheeses spawns after Step 0
- [ ] Weapon viewmodel loads and attaches to camera
- [ ] Pointer lock activates (click to lock)
- [ ] Left mouse button shoots (mousedown event)
- [ ] Raycasting detects cheese hits
- [ ] Hit indicator flashes on successful hit
- [ ] Shooting sound plays on each shot
- [ ] Weapon bobbing animation works when moving
- [ ] Recoil animation works on each shot
- [ ] Cheeses jump and fly around the arena
- [ ] Cheeses have AI dodging behavior
- [ ] Mad mode triggers randomly (red-orange glow)
- [ ] Mad mode increases speed and aggressiveness
- [ ] Explosion effect appears when cheese is hit (12 particles)
- [ ] New batch spawns 0.5 seconds after each hit
- [ ] Difficulty increases progressively (smaller, faster, smarter)
- [ ] Progress HUD shows: "🔫 Cheeses Shot: X/50 (X%)"
- [ ] Each cheese shot rewards +50 DSPOINC
- [ ] Toast shows progress: "🧀 Cheese X/50 caught!"
- [ ] After 50 shot, trait `CHEESE_TEMPLE_LEVEL4_STEP1` unlocks
- [ ] Portal appears at back of arena
- [ ] Portal suction pulls player closer
- [ ] Entering portal shows completion screen
- [ ] Completion screen has all navigation options

### Known Working Features
- ✅ Hidden cheese stone trigger block (Step 0)
- ✅ 10-second standing timer with audio cue
- ✅ Step 0 trait unlock and DSPOINC reward (+100)
- ✅ Platform animation (sinks/rises)
- ✅ Step 1: Continuous cheese spawning (1-5 per batch)
- ✅ Step 1: First-person weapon viewmodel (Pistol_1.fbx)
- ✅ Step 1: Shooting system with raycasting
- ✅ Step 1: Hit indicator (cheese-themed radial gradient)
- ✅ Step 1: Weapon bobbing and recoil animations
- ✅ Step 1: Shooting sound (Space Invaders)
- ✅ Step 1: Cheeses jump, fly, and dodge with AI
- ✅ Step 1: Progressive difficulty system (size, speed, smartness)
- ✅ Step 1: Mad mode system (red glow, increased speed)
- ✅ Step 1: Explosion effect (12 particles per hit)
- ✅ Step 1: Progress HUD (real-time count and percentage)
- ✅ Step 1: +50 DSPOINC per cheese (2,500 total)
- ✅ Step 1: Trait unlock after 50 cheeses shot
- ✅ Step 1: Portal creation and activation
- ✅ Step 2: Portal proximity detection and suction
- ✅ Step 2: Completion screen with navigation options
- ✅ Floor flickering fix (polygonOffset)
- ✅ Level navigation from Level 3 portal
- ✅ Level selector integration (L key in God Mode)
- ✅ God Mode G key cycles Step 0/Step 1
- ✅ Collision system (walls, floor, ceiling)

---

## 📝 FUTURE EXPANSION

### Step 1 Implementation
✅ **COMPLETE** - All features implemented:
- ✅ 50-cheese shooting challenge with continuous spawning
- ✅ Progressive difficulty system (size, speed, smartness)
- ✅ First-person weapon viewmodel and shooting system
- ✅ Hit detection with raycasting
- ✅ Explosion effects and visual feedback
- ✅ Progress HUD with real-time tracking
- ✅ Step 1 trait unlock (`CHEESE_TEMPLE_LEVEL4_STEP1`)
- ✅ Step 1 DSPOINC rewards (+50 per cheese = 2,500 total)
- ✅ Step 1 completion condition (shoot all 50 cheeses)

### Portal System
✅ **COMPLETE** - All features implemented:
- ✅ Portal activation after Step 1 completion
- ✅ Portal proximity detection and suction effect
- ✅ Portal completion screen with navigation options
- ✅ Level 5 navigation (placeholder - restarts Level 4 for now)
- ✅ Return to other levels functionality

### Potential Enhancements
- [ ] Additional weapon types (different guns)
- [ ] Power-ups (rapid fire, bigger bullets, slow motion)
- [ ] Combo system (bonus DSPOINC for consecutive hits)
- [ ] Leaderboard for fastest completion time
- [ ] Difficulty modes (Easy: 25 cheeses, Normal: 50, Hard: 100)

---

---

## ✅ PRODUCTION TESTING VERIFICATION (November 19, 2025 - Evening)

### Complete Level 4 Testing Results:
- ✅ **Step 0:** Cheese stone platform works, reward awarded correctly (200 DSPOINC with VIP 2x)
- ✅ **Step 1:** All 50 cheeses shot, all rewards awarded correctly (5,000 DSPOINC with VIP 2x)
- ✅ **Step 2:** Portal entry works, reward awarded correctly (400 DSPOINC with VIP 2x)
- ✅ **Total Rewards:** 5,600 DSPOINC (2,800 base × 2.0 VIP multiplier)
- ✅ **Traits:** All 3 traits (`CHEESE_TEMPLE_LEVEL4_STEP0/1/2`) unlocked correctly
- ✅ **Profile Page:** All 3 achievements appear in "3D Puzzles Achievements" section
- ✅ **Recent Score Changes:** All 52 rewards appear with correct formatting and amounts
  - 1 Step 0 entry: +200 DSPOINC
  - 50 cheese entries: +100 DSPOINC each (`CHEESE_TEMPLE_LEVEL4_CHEESE_1` through `CHEESE_50`)
  - 1 Step 2 entry: +400 DSPOINC
- ✅ **Role Multiplier:** VIP 2x multiplier confirmed working for all rewards
- ✅ **Database Records:** All 52 entries correctly logged in `tbl_riddle_completions` and `tbl_score_adjustments`
- ✅ **Pointer Lock Fix:** Movement works immediately after Step 0 (automatic pointer lock request)

### Production Status:
- ✅ **FULLY VERIFIED** - All systems working correctly in production
- ✅ **Database Integration** - All rewards and traits correctly stored
- ✅ **Frontend Integration** - All achievements and rewards displaying correctly
- ✅ **Role Multipliers** - VIP 2.0x multiplier confirmed working
- ✅ **Complete System** - Ready for community engagement
- ✅ **Bug Fixes** - Pointer lock movement issue fixed (automatic activation)

---

## 🎮 UX Improvements (November 19, 2025 - Late Evening)

### **1. Overheat Cooldown Extended** ✅
- **Change:** Increased overheat cooldown from 3 seconds to 6 seconds
- **Reason:** Players requested longer cooldown period to make overheating more impactful
- **Impact:** Players must wait 6 seconds before shooting again after weapon overheats
- **File Modified:** `three.js/main.js` - `level4State.overheatCooldown: 6000`
- **Status:** ✅ **COMPLETE**

### **2. "Already Completed" Notification Fix** ✅
- **Issue:** "Riddle already completed" popup was too large and poorly positioned, blocking gameplay view
- **Changes Applied:**
  - **Size:** Reduced from 18px to 14px font, 8px padding (was 16px)
  - **Width:** Reduced from 300px to 180px
  - **Position:** Moved to top-right corner (like MAD MODE notification) instead of center
  - **Duration:** Reduced from 4 seconds to 2.5 seconds
  - **Text:** Changed from "🧩 Riddle Already Completed!" to "🧩 Already Completed"
- **Result:** Less intrusive, doesn't block gameplay view
- **File Modified:** `three.js/main.js` - `showRiddleRewardNotification()`
- **Status:** ✅ **COMPLETE**

---

**Last Updated:** November 23, 2025 (Wave Countdown UI Improvements + Monster Waves + Bullet Fix)  
**Status:** ✅ **FULLY IMPLEMENTED & PRODUCTION VERIFIED** — 50-cheese shooting challenge + 30-monster wave challenge with progressive difficulty, weapon system, portal, and completion screen  
**Version:** 4.0 (Updated Nov 23, 2025 - Monster waves implemented, wave countdown UI improved, bullet freeze fixed)

---

## 🎨 UX Improvements (November 23, 2025)

### **1. Wave Countdown UI Redesign** ✅
- **Change:** Redesigned both cheese wave and monster wave countdown popups to use smaller "mad mode" style
- **Position:** Changed from center-screen to top-right corner (20px top, 20px right)
- **Size:** Reduced from large popups (40px padding, 72px font) to compact notifications (12px padding, 18px font)
- **Countdown Numbers:** Reduced from 96px to 24px for better readability without blocking view
- **Style:** Applied mad mode styling (gradient background, blur, pulse animation) for consistency with Level 1
- **Result:** Less intrusive, gameplay remains visible during countdown, professional appearance
- **Files Modified:** `three.js/main.js` - `showLevel4WaveCountdown()`, `showLevel4MonsterWaveCountdown()`, `updateLevel4()` countdown update logic
- **Status:** ✅ **COMPLETE**

### **2. Bullet Freeze Fix** ✅
- **Issue:** Bullets froze in air when portal activated (last monster defeated)
- **Root Cause:** `updateLevel4Bullets()` required `step1Active` or `step2Active` to be true, but these are set to false when portal activates
- **Fix:** Removed step requirement - bullets now update as long as player is in Level 4, allowing them to complete their flight path
- **Result:** Bullets complete their trajectory even after portal appears
- **Files Modified:** `three.js/main.js` - `updateLevel4Bullets()`
- **Status:** ✅ **COMPLETE**

### **3. Function Name Fix** ✅
- **Issue:** `ReferenceError: switchLevel4Weapon is not defined` when pressing number keys 2-9
- **Root Cause:** Function calls used wrong name (`switchLevel4Weapon` instead of `switchLevel4WeaponSlot`)
- **Fix:** Updated all 8 function calls (keys 2-9) to use correct function name
- **Result:** Weapon switching works correctly for all number keys
- **Files Modified:** `three.js/main.js` - `document.addEventListener("keydown", ...)` handler
- **Status:** ✅ **COMPLETE**

---

## 💾 DATABASE STRUCTURE & REWARD SYSTEM

### **Database Tables Used for Level 4 Rewards & Traits**

**See:** `12.0/RULES/15_RIDDLE_REWARD_DATABASE_RULE.md` for complete database documentation.

**Key Tables:**
- **`tbl_user_traits`** - Stores trait unlocks (`CHEESE_TEMPLE_LEVEL4_STEP0`, `CHEESE_TEMPLE_LEVEL4_STEP1`, `CHEESE_TEMPLE_LEVEL4_STEP2`, `CHEESE_TEMPLE_LEVEL4_STEP3`)
- **`tbl_riddle_completions`** - Tracks step completions with base reward, multiplier, and total reward
- **`tbl_user_scores`** - Stores DSPOINC balance updates (`game: 'cheese_temple_riddles'`, `source: 'riddle_completion'`)
- **`tbl_score_adjustments`** - Audit trail for "Recent Score Changes" display

**Riddle IDs:**
- `CHEESE_TEMPLE_LEVEL4_STEP0` - Base reward: 100 DSPOINC
- `CHEESE_TEMPLE_LEVEL4_CHEESE_1` through `CHEESE_TEMPLE_LEVEL4_CHEESE_50` - Base reward: 50 DSPOINC each
- `CHEESE_TEMPLE_LEVEL4_MONSTER_1` through `CHEESE_TEMPLE_LEVEL4_MONSTER_30` - Base reward: 50 DSPOINC each (Step 2)
- `CHEESE_TEMPLE_LEVEL4_STEP3` - Base reward: 200 DSPOINC

**API Endpoints:**
- **Reward API:** `/api/dev/riddle-reward.php` (POST method)
- **Trait API:** `/api/user/traits.php` (POST method)

**Query Examples:**
```sql
-- Check Level 4 traits for user
SELECT * FROM tbl_user_traits 
WHERE user_id = ? AND trait_name LIKE 'CHEESE_TEMPLE_LEVEL4_%';

-- Check Level 4 rewards for user (1 step + 50 cheeses + 30 monsters + 1 step)
SELECT * FROM tbl_riddle_completions 
WHERE discord_id = ? AND (riddle_id LIKE 'CHEESE_TEMPLE_LEVEL4_STEP%' OR riddle_id LIKE 'CHEESE_TEMPLE_LEVEL4_CHEESE_%' OR riddle_id LIKE 'CHEESE_TEMPLE_LEVEL4_MONSTER_%')
ORDER BY completed_at DESC;
```

**Total Rewards:**
- **Base Total:** 2,800 DSPOINC (100 + 50×50 cheeses + 50×30 monsters + 200)
- **VIP Holder (2.0x):** 5,600 DSPOINC
- **Holder (1.5x):** 4,200 DSPOINC
- **Default (1.0x):** 2,800 DSPOINC

**Last Updated:** November 27, 2025 (GOD Mode Initialization Fix)

---

## 🔧 TECHNICAL NOTES

### **Initialization Fix (November 27, 2025):**
- ✅ Added group scene verification to `warpToLevel4()` and `restartLevel4()`
- ✅ `spawnLevel4Monster()` already had group scene verification
- ✅ **Result:** GOD mode warp works correctly, all elements spawn on first attempt (cheeses, weapons, monsters)
- ✅ **Verified:** Level 4 works perfectly with GOD mode (L key) and G key step jumps

