# 🎯 RIDDLE #1 — THE FIRST SHOT (LEVEL 4)

**Document Created:** November 17, 2025  
**Riddle ID:** `CHEESE_TEMPLE_LEVEL4_RIDDLE_01`  
**Level:** Cheese Temple — Level 4 "The First Shot"  
**Status:** ✅ **FULLY IMPLEMENTED** — 50-cheese shooting challenge with progressive difficulty  
**Traits / Rewards:** 
- `CHEESE_TEMPLE_LEVEL4_STEP0` (+100 DSPOINC)
- `CHEESE_TEMPLE_LEVEL4_STEP1` (unlocked after shooting 50 cheeses)
- `CHEESE_TEMPLE_LEVEL4_STEP2` (+200 DSPOINC - unlocked when entering portal)

---

## 📋 OVERVIEW

### Objective
Enter a massive 160x160 cheese stone arena and complete "The First Shot" challenge. Find the hidden cheese stone to unlock Step 1, then shoot 50 floating cheese entities with a weapon. The challenge features progressive difficulty - cheeses get smaller, faster, and smarter as you progress.

### Flow Summary
1. **Step 0 (Hidden Cheese Stone):** Stand on the hidden cheese stone platform for 10 seconds to unlock Step 1. Awards **+100 DSPOINC** and unlocks trait `CHEESE_TEMPLE_LEVEL4_STEP0`, then starts Step 1.
2. **Step 1 (Shoot 50 Cheeses):** Shoot 50 floating cheese entities with a first-person weapon. Cheeses spawn in batches of 1-5 randomly. Each cheese rewards **+50 DSPOINC**. Difficulty increases progressively - cheeses get smaller (100% → 60% size), faster (1x → 2.5x speed), and smarter (better dodging) as you progress. After all 50 are shot, unlocks trait `CHEESE_TEMPLE_LEVEL4_STEP1` and portal appears.
3. **Step 2 (Portal Completion):** Enter the portal to see completion screen with options to proceed to Level 5, restart Level 4, or return to other levels.

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

### Step 1: Shoot 50 Floating Cheeses
1. After Step 0 completes, weapon viewmodel loads and pointer lock activates
2. Initial batch of 1-5 cheeses spawns randomly in the arena
3. **Shooting System:**
   - First-person weapon viewmodel (Pistol_1.fbx) attached to camera
   - Left mouse button to shoot (mousedown event)
   - Raycasting from camera center (crosshair position)
   - Hit indicator shows cheese-themed radial gradient flash
   - Shooting sound: Space Invaders `normal_shoot.wav`
   - Weapon bobbing animation when moving
   - Recoil animation on each shot
4. **Continuous Spawning:**
   - New batch (1-5 cheeses) spawns 0.5 seconds after each cheese is hit
   - Spawning continues until 50 total cheeses are shot
   - Random spawn positions within arena bounds
5. **Progressive Difficulty System:**
   - **Size:** Decreases from 100% to 60% (smaller = harder to hit)
   - **Speed:** Increases from 1x to 2.5x (faster movement)
   - **Dodging:** Aggressiveness increases from 30% to 80%
   - **Smartness:** Pathfinding improves from 40% to 90%
   - Difficulty scales based on progress: `progress = cheesesCaught / 50`
6. **Cheese Behavior:**
   - Cheeses jump and fly around with AI behavior
   - **Mad Mode:** Can enter "mad mode" randomly (18% chance, 4-second duration, 15-second cooldown)
   - Red-orange pulsing glow in mad mode
   - More aggressive dodging and faster target changes
7. **Explosion Effect:**
   - When hit, cheese explodes into 12 cheese particles
   - Particles fade out over 1 second with gravity
   - Cartoon/arcade style visual feedback
8. **Progress Tracking:**
   - HUD displays: "🔫 Cheeses Shot: X/50 (X%)"
   - Updates in real-time as cheeses are hit
   - Toast notifications for each cheese hit
9. Each cheese shot rewards **+50 DSPOINC**
10. After all 50 cheeses are shot:
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
- `spawnLevel4Cheeses(count)` — Spawns 1-5 FloatingCheese instances (random if count not specified)
- `resetLevel4Progress()` — Resets all Level 4 state, clears cheeses array
- `checkLevel4TriggerBlockStanding()` — Checks if player is standing on trigger block
- `updateLevel4Step0(delta)` — Updates Step 0 logic (timer, trait unlock, DSPOINC reward)
- `updateLevel4TriggerBlockVisual(delta)` — Animates the trigger block sinking/rising
- `updateLevel4(delta)` — Main update loop for Level 4 (updates cheeses, portal, HUD, particles)
- `captureLevel4Cheese(cheeseIndex)` — Handles cheese hit, creates explosion, awards DSPOINC, spawns new batch
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
- `updateLevel4ProgressHUD()` — Updates progress HUD with current count
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
- `LEVEL4_MIN_SPAWN_COUNT = 1` (minimum cheeses to spawn at once)
- `LEVEL4_MAX_SPAWN_COUNT = 5` (maximum cheeses to spawn at once)
- `LEVEL4_DSPOINC_PER_CHEESE = 50` (DSPOINC reward per cheese)
- `LEVEL4_WEAPON_PATH = "/textures/3d models/Fire Weapons 1/FBX/Pistol_1.fbx"`
- `LEVEL4_SHOOT_RANGE = 200` (maximum shooting range)
- `LEVEL4_HIT_INDICATOR_DURATION = 0.3` (hit indicator flash duration)
- `RIDDLE_AIM_TIME = 10.0` (seconds to stand on trigger block)

### Cheese System
- **FloatingCheese Class:** Same as Level 1 - includes mad mode, jumping, flying, AI dodging
- **Spawn System:** Continuous spawning - 1-5 cheeses per batch, new batch spawns 0.5s after each hit
- **Spawn Positions:** Random positions within arena bounds (20-70 units from center)
- **Roam Radius:** 70 units (for 160x160 arena)
- **Base Height:** 8 units above ground (with height variation)
- **Progressive Difficulty:**
  - Size multiplier: `1 - (progress * 0.4)` → 100% to 60%
  - Speed multiplier: `1 + (progress * 1.5)` → 1x to 2.5x
  - Aggressiveness: `0.3 + (progress * 0.5)` → 30% to 80%
  - Smartness: `0.4 + (progress * 0.5)` → 40% to 90%
- **Mad Mode:** 18% chance, 4-second duration, 15-second cooldown
- **Hit Detection:** Raycasting from camera center (crosshair position)

### Shooting System
- **Weapon Viewmodel:** Pistol_1.fbx model attached to camera in first-person view
- **Controls:** Left mouse button (mousedown event) to shoot
- **Raycasting:** From camera center (0, 0) with 200 unit range
- **Hit Indicator:** Cheese-themed radial gradient overlay (yellow/orange)
- **Weapon Animation:**
  - Bobbing when moving (based on player velocity)
  - Recoil animation on each shot
  - Smooth return to rest position
- **Audio:** Space Invaders `normal_shoot.wav` sound effect
- **Cooldown:** 0.2 seconds (5 shots per second max)

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

**Last Updated:** November 17, 2025  
**Status:** ✅ **FULLY IMPLEMENTED** — 50-cheese shooting challenge with progressive difficulty, weapon system, portal, and completion screen  
**Version:** 3.0 (Updated Nov 17, 2025 - Complete 50-cheese system with shooting mechanics)

