# 📚 MODULE INTEGRATION GUIDE - STABLE VERSION

**Date:** December 20, 2025  
**Status:** ✅ **STABLE VERSION - ALL MODULES DOCUMENTED**  
**Purpose:** Complete guide for integrating and configuring all JS modules in main.js

---

## 🎯 **OVERVIEW**

This document provides comprehensive documentation for all JavaScript modules used in the game, including:
- Module purpose and functionality
- Integration steps in main.js
- Configuration options
- Loading order and dependencies
- Stability status

---

## 📦 **CORE MODULES**

### **1. AudioSystem (`audio-system.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Created:** December 18, 2025  
**Purpose:** Complete audio management system (extracted from main.js)

**Features:**
- Background music management (per-level)
- Sound effects (footsteps, jumps, gameplay sounds)
- Audio state management (enabled/disabled, volume)
- UI control updates

**Integration in main.js:**
```javascript
import { AudioSystem } from "./audio-system.js";

// Initialize in startGame() or after scene/camera/renderer ready
const audioSystem = new AudioSystem({
  audioListener: audioListener,
  audioLoader: audioLoader,
  getCurrentLevel: () => currentLevel,
  getIsGamePaused: () => isGamePaused,
  getOptionsMenu: () => optionsMenu,
  getPlayerVelocity: () => playerVelocity,
  getOnGround: () => onGround
});

// Initialize audio system
audioSystem.initialize();

// Wrapper functions maintain backward compatibility
function loadCharacterAudio() {
  audioSystem.loadCharacterAudio();
}

function playJumpSound() {
  audioSystem.playJumpSound();
}

// ... all other audio functions delegate to audioSystem
```

**Configuration:**
- Uses constants from `config-system.js`
- Settings stored in localStorage
- Per-level background music paths configured in config-system.js

**Dependencies:**
- `config-system.js` (for audio constants)
- THREE.js AudioListener and AudioLoader

**Stability Notes:**
- ✅ Fully modularized (no audio code in main.js)
- ✅ All wrapper functions working
- ✅ UI buttons properly enabled
- ✅ Legacy variables synced correctly

---

### **2. SkySystem (`sky-system.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Purpose:** Sky and environment management (day/night cycle, clouds, stars)

**Integration in main.js:**
```javascript
import { SkySystem } from "./sky-system.js";

// Initialize once
let skySystem = null;

// In startGame() or level initialization
skySystem = new SkySystem({
  scene: scene,
  renderer: renderer
});

// Apply level-specific settings
skySystem.applyLevelEnvironment(levelId);
```

**Configuration:**
- Per-level settings saved/loaded via localStorage
- Time of day, cloud density, star visibility configurable
- Settings persist across level reloads

**Dependencies:**
- THREE.js Scene and WebGLRenderer

**Stability Notes:**
- ✅ All 6 levels working correctly
- ✅ Settings persistence working
- ✅ Day/night cycle smooth

---

### **3. GrassSystem (`grass-system.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Purpose:** Procedural grass generation with wind animation and exclusion zones

**Integration in main.js:**
```javascript
import { GrassSystem } from "./grass-system.js";

// Initialize once
let grassSystem = null;

// In startGame() or level initialization
grassSystem = new GrassSystem({
  scene: scene,
  renderer: renderer
});

// Apply level-specific settings
grassSystem.applyLevelEnvironment(levelId);
```

**Configuration:**
- Per-level settings saved/loaded via localStorage
- Blade count, length, wind settings configurable
- Exclusion zones prevent grass under objects (chests, platforms)

**Dependencies:**
- THREE.js Scene and WebGLRenderer
- Custom grass shaders

**Stability Notes:**
- ✅ Exclusion zones working perfectly
- ✅ All 6 levels display correctly
- ✅ Performance optimized (chunked meshes for large areas)
- ✅ Underground flickering fixed

---

### **4. PlayerControls (`player-controls.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Purpose:** Player input handling (keyboard, mouse, movement)

**Integration in main.js:**
```javascript
import { PlayerControls } from "./player-controls.js";

// Initialize once after scene/camera/renderer ready
let playerControls = null;

function initializePlayerControls() {
  playerControls = new PlayerControls({
    camera: camera,
    scene: scene,
    // ... other config
  });
  
  playerControls.enable();
}

// Update in game loop
function animate() {
  const delta = clock.getDelta();
  if (playerControls) {
    playerControls.update(delta);
  }
}
```

**Configuration:**
- Movement speed, jump height, etc. configurable
- First-person and third-person camera modes
- Mouse look sensitivity

**Dependencies:**
- THREE.js Camera and Scene
- PointerLockControls

**Stability Notes:**
- ✅ All movement working correctly
- ✅ Camera modes switching properly
- ✅ Input handling responsive

---

### **5. PlayerModel (`player-model.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Purpose:** Player character model loading and animation

**Integration in main.js:**
```javascript
import { PlayerModel } from "./player-model.js";

// Initialize once
let playerModel = null;

// Load player model
playerModel = new PlayerModel({
  scene: scene,
  camera: camera,
  // ... config
});

await playerModel.load(modelPath);
```

**Configuration:**
- Supports Mouse and Animation Library character types
- Animation settings configurable
- Movement, rotation, lerp settings

**Dependencies:**
- THREE.js Scene and Camera
- GLTFLoader or FBXLoader (depending on model format)

**Stability Notes:**
- ✅ Both character types working
- ✅ All animations loading correctly
- ✅ Settings centralized and consistent

---

### **6. GUISystem (`gui-system.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Purpose:** User interface management (HUD, menus, notifications)

**Integration in main.js:**
```javascript
import { GUISystem } from "./gui-system.js";

// Initialize once
let guiSystem = null;

guiSystem = new GUISystem({
  scene: scene,
  camera: camera,
  onStartGame: () => { /* start game callback */ },
  // ... other callbacks
});

guiSystem.initialize();
```

**Configuration:**
- HUD elements configurable
- Menu styles and layouts
- Notification system

**Dependencies:**
- THREE.js Scene and Camera
- DOM manipulation

**Stability Notes:**
- ✅ All UI elements working
- ✅ Menus responsive
- ✅ Notifications displaying correctly

---

### **7. WeaponSystem (`weapon-system.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Purpose:** Weapon loading, rendering, and management (Levels 4-6)

**Integration in main.js:**
```javascript
import { WeaponSystem } from "./weapon-system.js";

// Initialize once
let weaponSystem = null;

weaponSystem = new WeaponSystem({
  scene: scene,
  camera: camera,
  // ... config
});

// Load weapon for level
await weaponSystem.loadWeapon(weaponPath, slotNumber);
```

**Configuration:**
- Weapon scale, position configurable
- Multiple weapon slots supported
- Animation settings

**Dependencies:**
- THREE.js Scene and Camera
- FBXLoader (for weapon models)

**Stability Notes:**
- ✅ Working in Levels 4-6
- ✅ Weapon switching functional
- ✅ Animations smooth

---

### **8. ChestSystem (`chest-system.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Purpose:** Chest spawning, interaction, rewards, and persistence

**Integration in main.js:**
```javascript
import { ChestSystem } from "./chest-system.js";

// Initialize once (usually in weapon system initialization)
let chestSystem = null;

// Initialize chest system
chestSystem = new ChestSystem({
  scene: scene,
  player: player,
  // ... config
});

// Create chests for level
chestSystem.createChestsForLevel(levelId, chestData);
```

**Configuration:**
- Chest positions, rewards configurable
- Animation settings
- Persistence via API/database

**Dependencies:**
- THREE.js Scene
- Player object
- API endpoints for rewards

**Stability Notes:**
- ✅ All chests working correctly
- ✅ Animations perfect
- ✅ Rewards system integrated
- ✅ Persistence working
- ✅ Grass exclusion zones working

---

## 🐉 **BOSS MODULES**

### **9. PhoenixBoss2 (`phoenix2.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Created:** December 8, 2025  
**Purpose:** Phoenix Dragon boss with 15 behavior patterns

**Integration in main.js:**
```javascript
import { PhoenixBoss2 } from "./phoenix2.js";

// Initialize in Level 6
let phoenixBoss = null;

// In buildLevel6PhoenixArena()
phoenixBoss = new PhoenixBoss2({
  scene: scene,
  camera: camera,
  levelGroup: level6State.group,
  player: player,
  spawnPosition: new THREE.Vector3(20, 10, 0),
  size: 4.0,
  health: 1000,
  maxHealth: 1000,
  colorVariation: 'Red',
  eyeColor: 'Red',
  emissiveGlow: true,
  emissiveIntensity: 0.3,
  behaviorMode: 'flying_circle',
  onBossDefeated: () => { /* callback */ },
  onBossHit: (health, maxHealth) => { /* callback */ }
});

await phoenixBoss.loadModel("/textures/3d models/phoenix2/Dragons1.glb");

// Update in game loop
function updateLevel6(delta) {
  if (phoenixBoss) {
    phoenixBoss.update(delta);
  }
}
```

**Configuration:**
- 7 color variations (Black, Blue, Brown, Gold, Green, Red, White)
- 3 eye colors (Blue, Red, Yellow)
- Emissive glow toggle and intensity
- 15 behavior patterns
- Health, size configurable

**Dependencies:**
- THREE.js Scene, Camera
- GLTFLoader (for GLB model)
- TextureLoader (for color variations)

**Stability Notes:**
- ✅ All 15 patterns working
- ✅ Color variations working
- ✅ Animations smooth
- ✅ GUI integration complete
- ✅ Settings persistence working

---

### **10. AlienSpiderBoss (`alien-spider.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Created:** December 20, 2025  
**Purpose:** Alien Spider boss with 7 behavior patterns and TGA texture support

**Integration in main.js:**
```javascript
import { AlienSpiderBoss } from "./alien-spider.js";
import { TGALoader } from "three/examples/jsm/loaders/TGALoader.js"; // Required for TGA textures

// Initialize in Level 6
let alienSpiderBoss = null;

// In buildLevel6PhoenixArena()
const spiderConfig = getAlienSpiderBossConfigForLevel(LEVEL_IDS.LEVEL6);

alienSpiderBoss = new AlienSpiderBoss({
  scene: scene,
  camera: camera,
  levelGroup: level6State.group,
  player: player,
  spawnPosition: new THREE.Vector3(-20, 1, 0),
  size: spiderConfig.size || 4.0,
  health: spiderConfig.health || 100,
  maxHealth: spiderConfig.maxHealth || 100,
  brightness: spiderConfig.brightness || 1.5,
  textureVariation: spiderConfig.textureVariation || 'Default',
  behaviorMode: spiderConfig.behaviorMode || 'idle_1',
  onBossDefeated: () => { /* callback */ },
  onBossHit: (health, maxHealth) => { /* callback */ }
});

await alienSpiderBoss.loadModel("/textures/3d models/Alien Spider 1/AFC_03/AFC_03.fbx");

// Update in game loop
function updateLevel6(delta) {
  if (alienSpiderBoss) {
    alienSpiderBoss.update(delta);
  }
}
```

**Configuration:**
- 3 texture variations (Default, Fur_1, Fur_2)
- Brightness multiplier (0.5-3.0)
- 7 behavior patterns (idle_1, idle_2, walk_patrol, run_patrol, attack_1, attack_2, damage_reaction)
- Health, size configurable

**TGA Texture Loading:**
```javascript
// TGALoader is automatically configured in AlienSpiderBoss constructor
// LoadingManager with TGA handler is set up automatically
// Textures load from: /textures/3d models/Alien Spider 1/AFC_03/
// Available textures:
// - AFC_03_color.tga (Default)
// - Fur_1.tga (Fur 1 variation)
// - Fur_2.tga (Fur 2 variation)
// - Eye_color.tga, Eye_normal.tga (Eye textures)
// - AFC_03_normal.tga, AFC_03_ao.tga, AFC_03_metalness.tga, AFC_03_rough.tga
```

**Dependencies:**
- THREE.js Scene, Camera
- FBXLoader (for FBX model)
- **TGALoader** (for TGA textures) - **REQUIRED**
- LoadingManager (configured automatically)

**Stability Notes:**
- ✅ TGA textures loading perfectly
- ✅ All 7 patterns working
- ✅ Texture variations working
- ✅ GUI integration complete
- ✅ Settings persistence working
- ✅ Brightness control functional

**TGA Texture Implementation:**
- Uses `TGALoader` from `three/examples/jsm/loaders/TGALoader.js`
- LoadingManager configured with TGA handler: `manager.addHandler(/\.tga$/i, new TGALoader())`
- FBXLoader uses LoadingManager: `new FBXLoader(loadingManager)`
- Resource path set: `loader.setResourcePath(basePath)`
- Textures load automatically when FBX model loads
- Explicit texture loading in `applyTextureVariation()` also uses TGALoader

---

## 🔧 **CONFIGURATION MODULES**

### **11. ConfigSystem (`config-system.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Purpose:** Centralized configuration constants

**Integration in main.js:**
```javascript
import {
  SOUND_FX_STORAGE_KEY,
  BACKGROUND_MUSIC_STORAGE_KEY,
  BACKGROUND_MUSIC_PATHS,
  // ... other constants
} from "./config-system.js";
```

**Configuration:**
- Audio paths and settings
- Level configurations
- Storage keys
- Game constants

**Dependencies:**
- None (pure constants)

**Stability Notes:**
- ✅ All constants properly exported
- ✅ Used by AudioSystem and other modules

---

## 🎮 **VR MODULE**

### **12. VRInputProvider (`vr-input-provider.js`)**

**Status:** ✅ **STABLE - PRODUCTION READY**  
**Purpose:** VR controller input and session management

**Integration in main.js:**
```javascript
import { VRInputProvider } from "./vr-input-provider.js";

// Initialize if VR available
let vrInputProvider = null;

if (await checkVRSupport()) {
  vrInputProvider = new VRInputProvider({
    renderer: renderer,
    camera: camera,
    // ... config
  });
}
```

**Configuration:**
- VR session settings
- Controller mappings
- Movement settings

**Dependencies:**
- THREE.js WebGLRenderer and Camera
- WebXR API

**Stability Notes:**
- ✅ VR support working
- ✅ Controller input functional

---

## 📋 **MODULE LOADING ORDER**

### **Critical Loading Sequence:**

1. **Core THREE.js Setup:**
   - Scene, Camera, Renderer
   - AudioListener, AudioLoader

2. **Configuration:**
   - Import config-system.js constants

3. **System Initialization (in order):**
   ```
   SkySystem → GrassSystem → PlayerControls → PlayerModel → GUISystem
   ```

4. **Level-Specific Systems:**
   ```
   WeaponSystem (Levels 4-6) → ChestSystem → AudioSystem
   ```

5. **Boss Systems (Level 6):**
   ```
   PhoenixBoss2 → AlienSpiderBoss
   ```

6. **VR (Optional):**
   ```
   VRInputProvider (if VR available)
   ```

---

## 🔗 **MODULE DEPENDENCIES**

```
main.js
├── config-system.js (constants)
├── SkySystem
│   └── THREE.js
├── GrassSystem
│   └── THREE.js
├── PlayerControls
│   └── THREE.js
├── PlayerModel
│   └── THREE.js (GLTFLoader/FBXLoader)
├── GUISystem
│   └── THREE.js
├── WeaponSystem
│   └── THREE.js (FBXLoader)
├── ChestSystem
│   └── THREE.js
├── AudioSystem
│   └── config-system.js
├── PhoenixBoss2
│   └── THREE.js (GLTFLoader, TextureLoader)
├── AlienSpiderBoss
│   └── THREE.js (FBXLoader, TGALoader) ⭐ TGA SUPPORT
└── VRInputProvider
    └── THREE.js (WebXR)
```

---

## ✅ **STABILITY CHECKLIST**

### **All Modules:**
- ✅ Properly imported in main.js
- ✅ Initialized in correct order
- ✅ Configuration options documented
- ✅ Update loops integrated
- ✅ Error handling implemented
- ✅ Settings persistence working (where applicable)

### **Boss Modules:**
- ✅ Model loading working
- ✅ Animation systems functional
- ✅ Texture loading working
- ✅ GUI integration complete
- ✅ Settings persistence working

### **TGA Texture Support:**
- ✅ TGALoader imported correctly
- ✅ LoadingManager configured
- ✅ FBXLoader uses LoadingManager
- ✅ Resource path set correctly
- ✅ Textures loading successfully
- ✅ Texture variations working

---

## 📝 **QUICK REFERENCE**

### **Adding a New Module:**

1. **Create module file:**
   ```javascript
   export class NewModule {
     constructor(config) {
       // Initialize
     }
   }
   ```

2. **Import in main.js:**
   ```javascript
   import { NewModule } from "./new-module.js";
   ```

3. **Initialize:**
   ```javascript
   let newModule = null;
   newModule = new NewModule({ /* config */ });
   ```

4. **Update in game loop:**
   ```javascript
   if (newModule) {
     newModule.update(delta);
   }
   ```

### **TGA Texture Support (for new FBX models):**

1. **Import TGALoader:**
   ```javascript
   import { TGALoader } from "three/examples/jsm/loaders/TGALoader.js";
   ```

2. **Create LoadingManager:**
   ```javascript
   const manager = new THREE.LoadingManager();
   manager.addHandler(/\.tga$/i, new TGALoader());
   ```

3. **Use with FBXLoader:**
   ```javascript
   const loader = new FBXLoader(manager);
   loader.setResourcePath("/path/to/textures/");
   ```

---

## 🎉 **STABLE VERSION STATUS**

**All modules are production-ready and stable as of December 20, 2025.**

- ✅ All 12 modules documented
- ✅ Integration patterns established
- ✅ TGA texture support working perfectly
- ✅ Configuration systems in place
- ✅ Settings persistence functional
- ✅ No breaking changes expected

---

**Last Updated:** December 20, 2025  
**Status:** ✅ **STABLE VERSION - PRODUCTION READY**

