# 🎭 PLAYER MODEL SYSTEM EXTRACTION PLAN

**Date:** December 6, 2025  
**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Purpose:** Extract Player Model loading and animation system from `main.js` into `player-model.js`

---

## 🎯 OBJECTIVE

Extract all player character model loading, animation, positioning, and visibility logic from `three.js/main.js` into a new modular `three.js/player-model.js` file, following the proven architecture pattern established by `player-controls.js`, `sky-system.js`, and `grass-system.js`.

---

## 📊 CHARACTER OPTIONS ANALYSIS

### **Two Available Character Models:**

#### **1. Mouse Character** 🐭
- **Path:** `/textures/3d models/Mouse/glb/glb/character/character.glb`
- **Type:** Separate animation files (GLB format)
- **Animations (6 total):**
  - `idle` - Standing still (loop)
  - `run` - Moving (loop, used for both walk and sprint)
  - `jump` - Jumping/falling (one-time)
  - `climb` - Climbing (loop)
  - `death` - Death animation (one-time)
  - `somersoult` - Somersault (one-time)
- **Special Properties:**
  - Height offset: 0.95
  - Rotation offset: -90 degrees (Math.PI / 2) for correct facing
  - Separate animation files in `/glb/glb/animation/` directory
  - Model pivot at feet (not torso)

#### **2. Animation Library [Standard]** 🎮
- **Path:** `/textures/3d models/Animation Libary/Animation Library[Standard]/Godot/AnimationLibrary_Godot_Standard.glb`
- **Type:** Embedded animations (all in one GLB file)
- **Animations (12+ possible):**
  - **Idle:** `Idle`, `Idle_Loop`
  - **Walk:** `Walk`, `Walk_Loop`
  - **Run/Sprint:** `Sprint_Loop`, `Jog_Fwd_Loop`, `Run`, `Running`, `running`, `run`
  - **Jump:** `Jump`, `Jump_Start`, `Jump_Loop`, `Jump_Land`
- **Special Properties:**
  - Height offset: 0.85 * scale
  - Standard rotation (no offset needed)
  - All animations embedded in model file
  - Model pivot at torso (center of body)

---

## 🎬 ANIMATION STATE SYSTEM

### **Current Animation Logic:**

#### **Priority Order:**
1. **Jumping** (velocity.y > 2.0)
   - Mouse: `jump`
   - Animation Library: `Jump_Start` → `Jump_Loop` → `Jump`

2. **Falling** (velocity.y < -2.0)
   - Mouse: `jump` or `idle`
   - Animation Library: `Jump_Land` → `Jump_Loop` → `Jump`

3. **Moving** (has movement input + velocity > threshold)
   - Mouse: `run` (always, no separate walk/sprint)
   - Animation Library:
     - **Sprint:** `Sprint_Loop` → `Jog_Fwd_Loop` → `Run` → `running` → `run`
     - **Walk:** `Walk_Loop` → `Walk` → `walk`

4. **Idle** (no movement)
   - Mouse: `idle`
   - Animation Library: `Idle_Loop` → `Idle`

### **Animation Features:**
- ✅ Velocity-based animation speed scaling
- ✅ Hysteresis system (prevents rapid switching)
- ✅ Debounce delay (250ms for run/idle, immediate for idle transitions)
- ✅ Smooth fade in/out (0.2s default, 0.1s for idle)
- ✅ Loop vs one-time detection
- ✅ Weight management (0.0 to 1.0)
- ✅ Time scale adjustment (matches movement speed)
- ✅ **Priority-based state machine** (for future animations)
- ✅ **Extensible trigger system** (for custom animations)
- ✅ **Manual animation triggering** (for special moves)

### **⚠️ CRITICAL: Currently Loaded But Unused Animations:**
- ⚠️ **`climb`** - Loaded but NOT triggered in state machine
- ⚠️ **`death`** - Loaded but NOT triggered in state machine
- ⚠️ **`somersoult`** - Loaded but NOT triggered in state machine

**These animations MUST be accessible via the new system!**

---

## 📋 CODE SECTIONS TO EXTRACT

### **1. Character Model Variables (Lines ~3605-3611):**
```javascript
let playerCharacterModel = null;
let playerCharacterMixer = null;
let playerCharacterAnimations = {};
let playerCharacterClock = new THREE.Clock();
let useGLTFCharacter = false;
let selectedCharacterPath = null;
```

### **2. Character Options Configuration (Lines ~3613-3625):**
```javascript
const CHARACTER_OPTIONS = {
  2: { name: "Mouse", path: "...", description: "Mouse Character" },
  3: { name: "Animation Library", path: "...", description: "Animation Library [Standard]" }
};
```

### **3. Model Loading Function (Lines ~3627-3931):**
- `loadPlayerCharacter(modelPath)` - Main loading function
- Handles both Mouse and Animation Library models
- Sets up materials, scaling, positioning
- Detects character type (Mouse vs Animation Library)

### **4. Mouse Animation Loading (Lines ~3933-4032):**
- `loadMouseCharacterAnimations()` - Loads separate animation files
- Animation file mapping (idle.glb, run.glb, jump.glb, etc.)
- Creates mixer and animation actions
- Sets up loop behavior

### **5. Character Update Function (Lines ~4294-5223):**
- `updatePlayerCharacter(delta)` - Main update loop
- Position interpolation (lerp)
- Rotation calculation (face movement direction)
- Animation state machine (idle/walk/run/jump)
- Visibility management (first-person vs third-person)
- Animation speed scaling
- Animation transitions

### **6. Visibility Logic:**
- Character visible only in third-person mode
- Child mesh visibility management
- Render order settings
- Shadow casting/receiving
- Material visibility

---

## 🏗️ PROPOSED MODULE STRUCTURE

### **File: `three.js/player-model.js`**

```javascript
import * as THREE from "three";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";

/**
 * Player Model System for 3D Riddle Game
 * 
 * Handles:
 * - Character model loading (GLTF/GLB)
 * - Animation management (mixer, clips, actions)
 * - Position and rotation updates
 * - Visibility management (first-person vs third-person)
 * - Animation state machine (idle/walk/run/jump)
 * - Support for multiple character types (Mouse, Animation Library)
 * - Future-ready for additional character models
 */

export class PlayerModel {
  constructor(scene, config) {
    this.scene = scene;
    this.config = config; // { getPlayerPosition, getPlayerVelocity, isFirstPerson, isGodMode, etc. }
    
    // Model state
    this.model = null;
    this.mixer = null;
    this.animations = {};
    this.currentAnimation = null;
    
    // Character configuration
    this.characterType = null; // 'mouse' or 'animation_library'
    this.selectedPath = null;
    
    // Animation state
    this.animationState = 'idle'; // 'idle', 'walk', 'run', 'jump', 'fall'
    this.lastAnimationSwitch = 0;
    this.movementHistory = []; // For hysteresis
    
    // Position/rotation state
    this.heightOffset = 0.85;
    this.rotationOffset = 0;
    
    // Visibility state
    this.visible = true;
    
    // Animation priority system (extensible for future animations)
    this.animationPriorities = {
      DEATH: 100,           // Highest priority (interrupts everything)
      STUNNED: 90,
      ATTACK: 80,          // Combat actions
      DODGE: 75,
      THROW: 70,
      JUMP: 60,            // Movement actions
      CLIMB: 55,
      SWIM: 50,
      SOMERSAULT: 40,      // Special moves
      BACKFLIP: 35,
      WALL_RUN: 30,
      WAVE: 20,            // Social actions
      DANCE: 15,
      POINT: 10,
      SIT: 5,              // Idle variations
      LAY: 3,
      SLEEP: 1,
      MOVEMENT: 0,         // Walk, Run, Sprint
      IDLE: -1             // Default idle
    };
    
    // Animation triggers (extensible system)
    this.animationTriggers = new Map(); // animationName -> { trigger: Function, priority: number }
    
    // Character options
    this.characterOptions = {
      mouse: {
        name: "Mouse",
        path: "/textures/3d models/Mouse/glb/glb/character/character.glb",
        heightOffset: 0.95,
        rotationOffset: -Math.PI / 2, // -90 degrees
        animations: {
          idle: '/textures/3d models/Mouse/glb/glb/animation/idle.glb',
          run: '/textures/3d models/Mouse/glb/glb/animation/run.glb',
          jump: '/textures/3d models/Mouse/glb/glb/animation/jump.glb',
          climb: '/textures/3d models/Mouse/glb/glb/animation/climb.glb',      // ⚠️ Loaded but not triggered
          death: '/textures/3d models/Mouse/glb/glb/animation/death.glb',     // ⚠️ Loaded but not triggered
          somersoult: '/textures/3d models/Mouse/glb/glb/animation/somersoult.glb' // ⚠️ Loaded but not triggered
        },
        loopAnimations: ['idle', 'run', 'climb'],
        animationPriorities: {
          'idle': this.animationPriorities.IDLE,
          'run': this.animationPriorities.MOVEMENT,
          'jump': this.animationPriorities.JUMP,
          'climb': this.animationPriorities.CLIMB,        // Ready for future use
          'death': this.animationPriorities.DEATH,        // Ready for future use
          'somersoult': this.animationPriorities.SOMERSAULT // Ready for future use
        }
      },
      animation_library: {
        name: "Animation Library",
        path: "/textures/3d models/Animation Libary/Animation Library[Standard]/Godot/AnimationLibrary_Godot_Standard.glb",
        heightOffset: 0.85, // Multiplied by scale
        rotationOffset: 0,
        animations: 'embedded', // All animations in model file
        loopAnimations: ['Idle', 'Idle_Loop', 'Walk', 'Walk_Loop', 'Sprint_Loop', 'Jog_Fwd_Loop']
      }
    };
  }
  
  /**
   * Load character model
   * @param {string} characterKey - 'mouse' or 'animation_library'
   * @param {string} customPath - Optional custom model path
   */
  async loadModel(characterKey = 'animation_library', customPath = null) { ... }
  
  /**
   * Load Mouse character animations from separate files
   */
  async loadMouseAnimations() { ... }
  
  /**
   * Setup animations from embedded model animations
   */
  setupEmbeddedAnimations(gltf) { ... }
  
  /**
   * Update character position, rotation, and animations
   * @param {number} delta - Time delta
   */
  update(delta) { ... }
  
  /**
   * Update character position (lerp to player position)
   */
  updatePosition(delta) { ... }
  
  /**
   * Update character rotation (face movement direction)
   */
  updateRotation() { ... }
  
  /**
   * Update animations based on movement state
   * Checks all registered triggers and priority system
   */
  updateAnimations(delta) { ... }
  
  /**
   * Setup default animation triggers (idle, walk, run, jump, etc.)
   * Can be extended with registerAnimationTrigger() for future animations
   */
  setupDefaultTriggers() { ... }
  
  /**
   * Determine target animation based on movement state
   * Uses priority-based system to handle ALL possible animations
   */
  determineTargetAnimation(movementState, playerVelocity, gameState) { ... }
  
  /**
   * Switch to animation with smooth transition
   * Supports priority-based interruption system
   */
  switchToAnimation(animationName, immediate = false, priority = 0) { ... }
  
  /**
   * Update animation speed to match movement velocity
   */
  updateAnimationSpeed(velocityMagnitude) { ... }
  
  /**
   * Register custom animation trigger (for future animations)
   * @param {string} animationName - Animation name
   * @param {Function} triggerFunction - Function that returns true when animation should play
   * @param {number} priority - Animation priority (higher = interrupts lower priority)
   */
  registerAnimationTrigger(animationName, triggerFunction, priority) { ... }
  
  /**
   * Manually trigger animation (for special moves, actions, etc.)
   * @param {string} animationName - Animation to play
   * @param {boolean} immediate - Skip fade transition
   */
  triggerAnimation(animationName, immediate = false) { ... }
  
  /**
   * Update visibility based on camera mode
   */
  updateVisibility() { ... }
  
  /**
   * Set visibility explicitly
   */
  setVisibility(visible) { ... }
  
  /**
   * Get current animation name
   */
  getCurrentAnimation() { ... }
  
  /**
   * Get model reference
   */
  getModel() { ... }
  
  /**
   * Get mixer reference (for external updates if needed)
   */
  getMixer() { ... }
  
  /**
   * Dispose and clean up
   */
  dispose() { ... }
}
```

---

## 📋 DETAILED EXTRACTION CHECKLIST

### **PHASE 1: Identify All Code Sections**

#### **1. Variables & State (Lines ~3605-3611):**
- [x] `playerCharacterModel` - Model object
- [x] `playerCharacterMixer` - Animation mixer
- [x] `playerCharacterAnimations` - Animation actions dictionary
- [x] `playerCharacterClock` - Clock (may not be needed)
- [x] `useGLTFCharacter` - Flag for GLTF character usage
- [x] `selectedCharacterPath` - Selected character path

#### **2. Character Configuration (Lines ~3613-3625):**
- [x] `CHARACTER_OPTIONS` - Character selection options
- [x] Character paths and descriptions

#### **3. Model Loading (Lines ~3627-3931):**
- [x] `loadPlayerCharacter(modelPath)` function
- [x] GLTF loader usage
- [x] Material setup
- [x] Scaling logic
- [x] Positioning logic
- [x] Character type detection
- [x] Height offset calculation

#### **4. Mouse Animation Loading (Lines ~3933-4032):**
- [x] `loadMouseCharacterAnimations()` function
- [x] Animation file mapping
- [x] Animation loading promises
- [x] Mixer creation
- [x] Animation action creation
- [x] Loop behavior setup
- [x] Default animation (idle) playing

#### **5. Character Update (Lines ~4294-5223):**
- [x] `updatePlayerCharacter(delta)` function
- [x] Position interpolation (lerp)
- [x] Height offset calculation
- [x] Rotation calculation
- [x] Animation mixer update
- [x] Movement state detection
- [x] Animation state machine
- [x] Animation transitions
- [x] Animation speed scaling
- [x] Visibility management

#### **6. Animation Logic:**
- [x] Jump detection (velocity.y > 2.0)
- [x] Fall detection (velocity.y < -2.0)
- [x] Movement detection (input + velocity)
- [x] Sprint detection (movement.sprint)
- [x] Idle detection (no movement)
- [x] Animation priority system
- [x] Hysteresis system (movement history)
- [x] Debounce delays
- [x] Fade in/out timing
- [x] Loop detection

#### **7. Visibility Logic:**
- [x] First-person check (`isFirstPerson()`)
- [x] Third-person visibility
- [x] Child mesh visibility
- [x] Render order settings
- [x] Shadow settings
- [x] Material visibility

---

## 🔗 DEPENDENCIES & INTEGRATION POINTS

### **Dependencies Required:**
- `scene` - Three.js scene
- `loadModel()` function - GLTF loader helper (needs to be passed or imported)
- `playerCollider` - Player collider (for position)
- `playerVelocity` - Player velocity (for animation state)
- `movement` - Movement state (from PlayerControls)
- `godMode` - God mode flag
- `isFirstPerson()` - Camera mode check
- `currentLevel` - Current level ID (for level-specific logic)

### **Integration in main.js:**
```javascript
// Import
import { PlayerModel } from "./player-model.js";

// Initialize
let playerModel = null;

function initializePlayerModel() {
  playerModel = new PlayerModel(scene, {
    // Dependency injection via callbacks
    getPlayerPosition: () => {
      const pos = new THREE.Vector3().lerpVectors(playerCollider.start, playerCollider.end, 0.5);
      return pos;
    },
    getPlayerVelocity: () => playerVelocity,
    getMovementState: () => playerControls ? playerControls.getMovementState() : movement,
    isFirstPerson: () => isFirstPerson(),
    isGodMode: () => godMode,
    getCurrentLevel: () => currentLevel,
    loadModelHelper: loadModel // Pass the loadModel function
  });
  
  // Load default character
  await playerModel.loadModel('animation_library');
}

// In animate loop:
if (playerModel) {
  playerModel.update(delta);
}

// Update visibility on camera mode change:
playerModel.updateVisibility();
```

---

## 🎬 ANIMATION MAPPING

### **Mouse Character Animation States:**

| State | Animation | Loop | Trigger |
|-------|-----------|------|---------|
| Idle | `idle` | ✅ Yes | No movement input |
| Walking | `run` | ✅ Yes | Movement input (no sprint) |
| Sprinting | `run` | ✅ Yes | Movement input + sprint |
| Jumping | `jump` | ❌ No | velocity.y > 2.0 |
| Falling | `jump` or `idle` | ❌ No | velocity.y < -2.0 |
| Climbing | `climb` | ✅ Yes | (Future: on ladder/climbable) |
| Death | `death` | ❌ No | (Future: player death) |
| Somersault | `somersoult` | ❌ No | (Future: special move) |

### **Animation Library Animation States:**

| State | Animation (Priority Order) | Loop | Trigger |
|-------|---------------------------|------|---------|
| Idle | `Idle_Loop` → `Idle` | ✅ Yes | No movement input |
| Walking | `Walk_Loop` → `Walk` → `walk` | ✅ Yes | Movement input (no sprint) |
| Sprinting | `Sprint_Loop` → `Jog_Fwd_Loop` → `Run` → `running` → `run` | ✅ Yes | Movement input + sprint |
| Jump Start | `Jump_Start` | ❌ No | velocity.y > 2.0 (rising) |
| Jump Loop | `Jump_Loop` | ✅ Yes | In air (velocity.y > 0) |
| Jump Land | `Jump_Land` | ❌ No | velocity.y < -2.0 (falling) |
| Jump (fallback) | `Jump` | ❌ No | If other jump animations not found |

---

## 🔧 IMPLEMENTATION STEPS

### **STEP 1: Create Module File Structure**
1. Create `three.js/player-model.js`
2. Set up ES module structure
3. Import required Three.js components
4. Define `PlayerModel` class skeleton
5. Add constructor with config parameter

### **STEP 2: Move Character Configuration**
1. Move `CHARACTER_OPTIONS` to class property `characterOptions`
2. Update paths to be relative/absolute as needed
3. Add character type detection logic

### **STEP 3: Move Model Loading**
1. Move `loadPlayerCharacter()` to `loadModel()` method
2. Update to use class properties (`this.model`, `this.mixer`)
3. Pass `loadModel` helper via config
4. Handle both Mouse and Animation Library models
5. Store character type for later use

### **STEP 4: Move Animation Loading**
1. Move `loadMouseCharacterAnimations()` to `loadMouseAnimations()` method
2. Move embedded animation setup to `setupEmbeddedAnimations()` method
3. Update to use class properties (`this.animations`)
4. Store animation metadata (loop, one-time, etc.)

### **STEP 5: Move Update Logic**
1. Move `updatePlayerCharacter()` to `update()` method
2. Split into sub-methods:
   - `updatePosition()` - Position interpolation
   - `updateRotation()` - Rotation calculation
   - `updateAnimations()` - Animation state machine (with priority system)
   - `updateVisibility()` - Visibility management
3. Use callbacks from config for dependencies
4. Update to use class properties
5. **CRITICAL:** Implement priority-based animation selection
6. **CRITICAL:** Setup default triggers for all loaded animations (including climb, death, somersault)
7. **CRITICAL:** Make system extensible for future animations

### **STEP 6: Extract Animation State Machine**
1. Move animation decision logic to `determineTargetAnimation()`
2. **CRITICAL:** Implement priority-based animation selection
3. **CRITICAL:** Add support for ALL loaded animations (climb, death, somersault)
4. Move animation switching logic to `switchToAnimation()` with priority support
5. Move animation speed scaling to `updateAnimationSpeed()`
6. Preserve all existing logic (hysteresis, debounce, etc.)
7. **CRITICAL:** Implement trigger registration system
8. **CRITICAL:** Setup default triggers for basic animations
9. **CRITICAL:** Allow manual animation triggering for special moves

### **STEP 7: Update main.js Integration**
1. Import `PlayerModel` class
2. Create instance after scene setup
3. Replace `loadPlayerCharacter()` calls with `playerModel.loadModel()`
4. Replace `updatePlayerCharacter(delta)` with `playerModel.update(delta)`
5. Replace visibility checks with `playerModel.updateVisibility()`
6. Test all levels

### **STEP 8: Testing & Cleanup**
1. Test character loading in all levels
2. Test animations (idle, walk, run, jump)
3. **CRITICAL:** Test that ALL loaded animations are accessible (climb, death, somersault)
4. **CRITICAL:** Test priority-based animation system
5. **CRITICAL:** Test manual animation triggering (`triggerAnimation()`)
6. **CRITICAL:** Test trigger registration system (`registerAnimationTrigger()`)
7. Test visibility (first-person vs third-person)
8. Test position updates (movement)
9. Test rotation updates (facing direction)
10. Test both character types (Mouse and Animation Library)
11. Test animation transitions and priority interruption
12. Remove old code from main.js
13. Update comments/documentation
14. **CRITICAL:** Document how to add new animations in the future

---

## 🎬 FUTURE-READY ANIMATION SYSTEM

### **Priority-Based Animation System:**

The new system MUST support priority-based animation selection:

```javascript
// Higher priority animations interrupt lower priority
// Example: Death (100) interrupts everything, Attack (80) interrupts movement, etc.

const ANIMATION_PRIORITY = {
  DEATH: 100,        // Interrupts everything
  STUNNED: 90,
  ATTACK: 80,        // Interrupts movement
  DODGE: 75,
  JUMP: 60,          // Interrupts movement
  CLIMB: 55,         // Interrupts movement
  MOVEMENT: 0,       // Walk, Run, Sprint
  IDLE: -1           // Default
};
```

### **Extensible Trigger System:**

```javascript
// Register custom animation triggers (for future animations)
playerModel.registerAnimationTrigger('swim', () => {
  return isInWater() || playerPosition.y < waterLevel;
}, ANIMATION_PRIORITY.SWIM);

// Manual animation triggering (for special moves)
playerModel.triggerAnimation('somersoult', true); // immediate = true
```

### **Currently Loaded But Unused Animations:**

These animations are already loaded and MUST be accessible:

1. **`climb`** - Ready to use when climb detection is implemented
   - Trigger: `isOnClimbableSurface()` or `isClimbing` flag
   - Priority: `ANIMATION_PRIORITY.CLIMB` (55)

2. **`death`** - Ready to use when death system is implemented
   - Trigger: `isDead` flag or `playerHealth <= 0`
   - Priority: `ANIMATION_PRIORITY.DEATH` (100 - highest)

3. **`somersoult`** - Ready to use when special moves are implemented
   - Trigger: `isSomersaulting` flag or special key combo
   - Priority: `ANIMATION_PRIORITY.SOMERSAULT` (40)

### **Future Animation Support:**

The system MUST be designed to easily add:
- New animation files (just add to config)
- New trigger conditions (registerAnimationTrigger)
- New priority levels (add to ANIMATION_PRIORITY)
- Character-specific animations (per-character config)

---

## ⚠️ CRITICAL CONSIDERATIONS

### **1. Character Type Detection:**
- **Problem:** Mouse and Animation Library have different setups
- **Solution:** Detect character type from path or explicit parameter
- **Pattern:** Store character type in `this.characterType`

### **2. Animation File Loading:**
- **Problem:** Mouse uses separate files, Animation Library uses embedded
- **Solution:** Separate methods: `loadMouseAnimations()` vs `setupEmbeddedAnimations()`
- **Pattern:** Conditional logic based on character type

### **3. Height Offset:**
- **Problem:** Mouse uses fixed 0.95, Animation Library uses 0.85 * scale
- **Solution:** Store offset in character config
- **Pattern:** Calculate offset in `loadModel()` based on character type

### **4. Rotation Offset:**
- **Problem:** Mouse needs -90 degree offset, Animation Library doesn't
- **Solution:** Store rotation offset in character config
- **Pattern:** Apply offset in `updateRotation()` based on character type

### **5. Animation Naming:**
- **Problem:** Mouse uses lowercase ('idle', 'run'), Animation Library uses mixed case ('Idle', 'Walk_Loop')
- **Solution:** Normalize animation names or handle both formats
- **Pattern:** Character-specific animation lookup tables

### **6. Dependency Injection:**
- **Problem:** Needs access to playerCollider, playerVelocity, movement, etc.
- **Solution:** Pass callbacks via config parameter
- **Pattern:** `config.getPlayerPosition()`, `config.getPlayerVelocity()`, etc.

### **7. Level-Specific Logic:**
- **Problem:** Level 3 has special character duplication checks
- **Solution:** Pass level-specific handlers via config or handle in main.js
- **Pattern:** Keep level-specific logic in main.js, pass to model via callback

### **8. LoadModel Helper:**
- **Problem:** `loadModel()` function is defined in main.js
- **Solution:** Pass as callback or move to separate utility file
- **Pattern:** `config.loadModelHelper = loadModel`

### **9. Currently Loaded But Unused Animations:**
- **Problem:** `climb`, `death`, `somersoult` are loaded but not accessible
- **Solution:** Implement priority-based state machine with trigger system
- **Pattern:** Register triggers for these animations, allow manual triggering
- **Requirement:** System MUST support these animations for future use

### **10. Future Animation Extensibility:**
- **Problem:** Need to add new animations without code changes
- **Solution:** Configuration-based animation system with trigger registration
- **Pattern:** Add animation to config, register trigger, system handles the rest
- **Requirement:** System MUST be extensible for decades of animation additions

---

## 🎯 FUTURE-READY ARCHITECTURE

### **Prepared for:**
- ✅ Multiple character models (currently 2, ready for more)
- ✅ Additional animations (death, climb, somersault already loaded and ready)
- ✅ Different animation file formats (separate files vs embedded)
- ✅ Character-specific behaviors (height offsets, rotation offsets)
- ✅ Animation state extensions (crouch, swim, fly, attack, dodge, etc.)
- ✅ Multiplayer character models (other players)
- ✅ Character customization (colors, accessories)
- ✅ **Priority-based animation system** (handles all animation types)
- ✅ **Extensible trigger system** (easy to add new animations)
- ✅ **Manual animation triggering** (for special moves and actions)

### **Currently Loaded Animations Ready for Use:**
- ✅ `climb` - Ready when climb detection is added
- ✅ `death` - Ready when death system is added
- ✅ `somersoult` - Ready when special moves are added

### **Future Animation Categories Supported:**
- ✅ **Environmental:** Climb, Swim, Crouch, Crawl, Slide
- ✅ **Combat:** Attack, Punch, Kick, Block, Dodge, Throw
- ✅ **Special Moves:** Somersault, Backflip, Wall Run, Double Jump
- ✅ **Social:** Wave, Dance, Point, Sit, Lay, Sleep
- ✅ **Status:** Death, Stunned, Injured, Exhausted
- ✅ **Vehicle:** Ride, Drive, Fly

### **Extensibility:**
- Character options can be expanded easily
- Animation mappings can be extended per character
- New character types can be added with configuration
- Animation state machine can be extended with priority system
- Future animation triggers can be added via `registerAnimationTrigger()`
- Manual animation triggering for special moves
- Priority-based interruption system handles all animation types
- Configuration-based system (no code changes needed for new animations)

---

## 📊 ESTIMATED CODE REDUCTION

### **Current main.js:**
- **Character Model Code:** ~1,600 lines (loading + update + animations)
- **Related Variables:** ~10 global variables

### **After Extraction:**
- **main.js:** Reduction of ~1,600 lines
- **player-model.js:** ~800-1,000 lines (NEW)
- **Cleaner main.js:** Character logic isolated
- **Better organization:** All character code in one place

---

## 🧪 TESTING CHECKLIST

### **Character Loading:**
- [ ] Mouse character loads correctly
- [ ] Animation Library character loads correctly
- [ ] Model scales correctly
- [ ] Model positions correctly
- [ ] Materials render correctly
- [ ] Shadows work correctly

### **Animations:**
- [ ] Idle animation plays when not moving
- [ ] Walk animation plays when moving (Animation Library)
- [ ] Run animation plays when sprinting (Animation Library)
- [ ] Run animation plays when moving (Mouse - always)
- [ ] Jump animation plays when jumping
- [ ] **CRITICAL:** Climb animation is accessible (can be triggered manually)
- [ ] **CRITICAL:** Death animation is accessible (can be triggered manually)
- [ ] **CRITICAL:** Somersault animation is accessible (can be triggered manually)
- [ ] Animation transitions are smooth (fade in/out)
- [ ] Animation speed scales with movement speed
- [ ] Animation loops correctly
- [ ] Animation debouncing works (no flickering)
- [ ] **CRITICAL:** Priority-based animation system works (higher priority interrupts lower)
- [ ] **CRITICAL:** Manual animation triggering works (`triggerAnimation()`)
- [ ] **CRITICAL:** Trigger registration works (`registerAnimationTrigger()`)

### **Position & Rotation:**
- [ ] Character position follows player collider smoothly
- [ ] Character faces movement direction correctly
- [ ] Mouse character rotation offset works (-90 degrees)
- [ ] Animation Library rotation works (no offset)
- [ ] Height offset is correct for both character types

### **Visibility:**
- [ ] Character is hidden in first-person mode
- [ ] Character is visible in third-person mode
- [ ] Visibility switches smoothly on camera mode change
- [ ] Child meshes are visible when character is visible
- [ ] Render order is correct (no z-fighting)

### **All Levels:**
- [ ] Level 1 - Character works correctly
- [ ] Level 2 - Character works correctly
- [ ] Level 3 - Character works correctly (special duplication checks)
- [ ] Level 4 - Character works correctly
- [ ] Level 5 - Character works correctly

---

## ✅ SUCCESS CRITERIA

### **Technical Success:**
- [ ] All character animations work identically to before
- [ ] Both character types (Mouse and Animation Library) work
- [ ] Position and rotation updates correctly
- [ ] Visibility management works correctly
- [ ] No performance degradation
- [ ] Code is cleaner and more organized

### **Architecture Success:**
- [ ] Follows same pattern as PlayerControls/Sky/Grass systems
- [ ] Clear separation of concerns
- [ ] Easy to find and modify character code
- [ ] Well-documented API
- [ ] Proper cleanup on dispose
- [ ] Future-ready for additional characters
- [ ] **CRITICAL:** Priority-based animation system implemented
- [ ] **CRITICAL:** Extensible trigger system implemented
- [ ] **CRITICAL:** ALL loaded animations are accessible
- [ ] **CRITICAL:** System ready for decades of animation additions

---

## 📝 NOTES FOR FUTURE CHARACTERS

### **When Adding New Characters:**
1. Add entry to `characterOptions` object
2. Specify model path
3. Configure height offset
4. Configure rotation offset (if needed)
5. Map animations (if separate files) or use 'embedded'
6. Specify loop animations array
7. Configure animation priorities (if different from defaults)
8. Test in all levels

### **When Adding New Animations:**

#### **For Mouse Character (Separate Files):**
1. Add animation file to `/textures/3d models/Mouse/glb/glb/animation/`
2. Add entry to `characterOptions.mouse.animations`:
   ```javascript
   swim: '/textures/3d models/Mouse/glb/glb/animation/swim.glb'
   ```
3. Add to `loopAnimations` array if it's a loop animation
4. Add priority to `animationPriorities` object
5. Register trigger (or use manual triggering):
   ```javascript
   playerModel.registerAnimationTrigger('swim', () => isInWater(), ANIMATION_PRIORITY.SWIM);
   ```

#### **For Animation Library (Embedded):**
1. Animation is already in model file
2. Add priority mapping if needed
3. Register trigger or use manual triggering
4. System automatically detects animation name from model

#### **Manual Triggering (Special Moves):**
```javascript
// Trigger animation immediately (no trigger condition needed)
playerModel.triggerAnimation('somersoult', true); // immediate = true
```

### **Animation Requirements:**
- **Minimum:** idle, walk/run, jump
- **Recommended:** idle, walk, run/sprint, jump, fall/land
- **Optional:** climb, death, somersault, crouch, swim, etc.

### **Character Configuration Example:**
```javascript
new_character: {
  name: "New Character",
  path: "/path/to/model.glb",
  heightOffset: 0.9, // or function: (scale) => 0.9 * scale
  rotationOffset: 0, // or -Math.PI / 2 for -90 degrees
  animations: 'embedded', // or object with paths
  loopAnimations: ['idle', 'walk', 'run']
}
```

---

**Plan Created:** December 6, 2025  
**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Next Step:** Create `player-model.js` and extract code

🧀 **This extraction will improve code organization and prepare for future character additions!** 🧀

