# 🔫 WEAPON/INVENTORY SYSTEM EXTRACTION PLAN

**Date:** December 6, 2025  
**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Purpose:** Extract all weapon and inventory code from `main.js` into `weapon-system.js` for decades of maintainable code

---

## 🎯 OBJECTIVE

Extract all weapon-related code from `three.js/main.js` into a new modular `three.js/weapon-system.js` file, following the proven architecture pattern established by:
- ✅ `player-controls.js` - Modular player controls (675 lines)
- ✅ `player-model.js` - Modular player model (767 lines)
- ✅ `gui-system.js` - Modular GUI/HUD system (2236 lines)

This will improve:
- **Code Organization** - Better separation of concerns
- **Maintainability** - Easier to find and modify weapon code
- **Testability** - Isolated weapon system for independent testing
- **Long-term Sustainability** - Modular architecture for decades
- **Performance** - Better code splitting and lazy loading potential

---

## 📊 CODE ANALYSIS

### **Estimated Code to Extract:**
- **Weapon Loading:** ~200 lines
- **Weapon Switching:** ~100 lines
- **Shooting Mechanics:** ~300 lines
- **Heat System:** ~150 lines
- **Triple Shot System:** ~100 lines
- **Weapon Audio:** ~50 lines
- **Weapon Transforms:** ~100 lines
- **Constants & Config:** ~200 lines
- **Total:** ~1,200-1,500 lines

### **Current main.js Size:**
- **Before:** ~24,147 lines
- **After Extraction:** ~22,600-22,900 lines (reduction of ~1,200-1,500 lines)

---

## 🔍 IDENTIFIED CODE SECTIONS

### **1. Weapon Constants & Configuration:**
- `LEVEL4_WEAPON_SLOTS` - Weapon slot definitions (1-9)
- `LEVEL4_WEAPON_PATH` - Default weapon path
- `LEVEL4_WEAPON_TRANSFORMS` - Weapon type transforms
- `LEVEL4_SHOOT_AUDIO` - Shooting sound path
- `LEVEL4_SHOOT_RANGE` - Maximum shooting range
- `LEVEL2_WEAPON_LIBRARY` - Level 2 weapon library
- `LEVEL2_PRIMARY_WEAPON_SLOTS` - Level 2 weapon slots

### **2. Weapon State (level4State):**
- `currentWeaponSlot` - Current active slot (1-9)
- `weaponSlots` - Cached weapon models by slot
- `weaponViewmodel` - Current weapon model
- `weaponHeat` - Current heat level (0-150)
- `maxHeat` - Maximum heat before overheating
- `heatPerShot` - Heat generated per shot
- `heatPerTripleShot` - Heat for triple shot
- `heatDecayRate` - Heat decay per frame
- `isOverheated` - Overheated state
- `overheatCooldown` - Cooldown duration
- `lastOverheatTime` - When overheating occurred
- `tripleShotActive` - Triple shot state
- `tripleShotBulletsRemaining` - Bullets in burst
- `tripleShotNextBulletTime` - Next bullet timing

### **3. Weapon Loading Functions:**
- `loadLevel4WeaponViewmodel(weaponPath, slotNumber)` - Load weapon model
- `removeLevel4WeaponViewmodel()` - Remove weapon from camera
- `processWeaponMaterial(material)` - Process weapon materials

### **4. Weapon Switching Functions:**
- `switchLevel4WeaponSlot(slotNumber)` - Switch to weapon slot
- `updateLevel4WeaponHUD()` - Update weapon UI (moved to GUI system)

### **5. Shooting Mechanics:**
- `handleLevel4Shooting()` - Main shooting handler
- `performLevel4Raycast()` - Raycast for hit detection
- `checkLevel4Shooting()` - Check if shooting is allowed
- Shooting cooldown logic
- Projectile creation (if any)

### **6. Heat System:**
- Heat generation on shot
- Heat decay over time
- Overheat detection
- Overheat cooldown logic

### **7. Triple Shot System (SF13):**
- Triple shot activation
- Burst fire logic
- Bullet timing

### **8. Weapon Audio:**
- `level4ShootSound` - Shooting sound
- `level4SF13ShootSound` - Triple shot sound
- Audio loading and playback

### **9. Weapon Transforms & Positioning:**
- Weapon position in first-person view
- Weapon rotation for crosshair alignment
- Weapon scaling based on model size
- Weapon bob animation (if exists)

---

## 🏗️ PROPOSED `weapon-system.js` STRUCTURE

```javascript
import * as THREE from "three";
import { FBXLoader } from "three/examples/jsm/loaders/FBXLoader.js";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";

export class WeaponSystem {
  constructor(scene, camera, config) {
    this.scene = scene;
    this.camera = camera;
    this.config = config; // Callbacks and dependencies
    
    // Weapon state
    this.weapons = {}; // Slot -> Weapon object
    this.currentSlot = 1;
    this.currentWeapon = null;
    this.weaponViewmodel = null;
    
    // Heat system
    this.weaponHeat = 0;
    this.maxHeat = 150;
    this.heatPerShot = 6;
    this.heatPerTripleShot = 18;
    this.heatDecayRate = 0.1;
    this.isOverheated = false;
    this.overheatCooldown = 6000;
    this.lastOverheatTime = 0;
    
    // Triple shot system
    this.tripleShotActive = false;
    this.tripleShotBulletsRemaining = 0;
    this.tripleShotNextBulletTime = 0;
    
    // Audio
    this.shootSound = null;
    this.tripleShotSound = null;
    this.audioReady = false;
    
    // Weapon configurations
    this.weaponSlots = {}; // From config
    this.weaponTransforms = {}; // From config
    
    // Shooting state
    this.isShooting = false;
    this.shootCooldown = 0;
    this.shootRange = 200;
  }
  
  // Initialization
  async initialize() { ... }
  async loadAudio() { ... }
  
  // Weapon loading
  async loadWeapon(slot, weaponPath = null) { ... }
  removeWeapon() { ... }
  processWeaponMaterial(material) { ... }
  
  // Weapon switching
  async switchWeapon(slot) { ... }
  canSwitchWeapon() { ... }
  
  // Shooting mechanics
  fire() { ... }
  canFire() { ... }
  performRaycast() { ... }
  checkShooting() { ... }
  
  // Heat system
  updateHeat(delta) { ... }
  generateHeat(amount) { ... }
  checkOverheat() { ... }
  coolDown() { ... }
  
  // Triple shot system
  activateTripleShot() { ... }
  updateTripleShot(delta) { ... }
  
  // Weapon transforms
  applyWeaponTransforms(weaponModel, weaponType) { ... }
  updateWeaponPosition(delta) { ... }
  
  // Audio
  playShootSound() { ... }
  playTripleShotSound() { ... }
  
  // Update loop
  update(delta) { ... }
  
  // Cleanup
  dispose() { ... }
}
```

---

## 🔗 DEPENDENCIES & INTEGRATION

### **Dependencies:**
- `scene` - Three.js scene
- `camera` - First-person camera (for viewmodel)
- `loadModel` - Global model loader function
- `audioListener` - Three.js audio listener
- `raycaster` - For shooting detection
- `PlayerControls` - For input (fire button, weapon switch keys)
- `GUISystem` - For weapon UI updates
- Level-specific configurations

### **Integration Points:**
- Initialize on Level 4 entry
- Update in animate loop (heat decay, triple shot, weapon bob)
- Listen to input from PlayerControls (fire, weapon switch)
- Update GUISystem on weapon switch/heat change
- Dispose on level changes

### **Callbacks Needed:**
```javascript
config = {
  // State getters
  getCurrentLevel: () => currentLevel,
  getLevel4State: () => level4State,
  getLevel4RiddleState: () => level4RiddleState,
  isFirstPerson: () => isFirstPerson(),
  
  // Actions
  onWeaponSwitched: (slot, weapon) => { ... },
  onWeaponFired: (slot, weapon) => { ... },
  onOverheated: () => { ... },
  onHeatChanged: (heat, maxHeat) => { ... },
  
  // External functions
  loadModel: loadModel, // Global model loader
  audioListener: audioListener,
  performRaycast: performRaycast, // For hit detection
  
  // Constants
  weaponSlots: LEVEL4_WEAPON_SLOTS,
  weaponTransforms: LEVEL4_WEAPON_TRANSFORMS,
  shootRange: LEVEL4_SHOOT_RANGE,
  shootAudio: LEVEL4_SHOOT_AUDIO
}
```

---

## 📋 EXTRACTION CHECKLIST

### **PHASE 1: Create Module Structure**
- [ ] Create `weapon-system.js` file
- [ ] Define `WeaponSystem` class structure
- [ ] Add constructor with dependencies
- [ ] Add basic initialization methods

### **PHASE 2: Extract Weapon Loading**
- [ ] Move `loadLevel4WeaponViewmodel()` to `loadWeapon()`
- [ ] Move `removeLevel4WeaponViewmodel()` to `removeWeapon()`
- [ ] Move `processWeaponMaterial()` to module
- [ ] Move weapon transform logic
- [ ] Test weapon loading

### **PHASE 3: Extract Weapon Switching**
- [ ] Move `switchLevel4WeaponSlot()` to `switchWeapon()`
- [ ] Move weapon slot validation logic
- [ ] Move weapon caching logic
- [ ] Test weapon switching

### **PHASE 4: Extract Shooting Mechanics**
- [ ] Move shooting handler to `fire()`
- [ ] Move raycast logic to `performRaycast()`
- [ ] Move shooting cooldown logic
- [ ] Test shooting mechanics

### **PHASE 5: Extract Heat System**
- [ ] Move heat state to module
- [ ] Move heat generation logic
- [ ] Move heat decay logic
- [ ] Move overheat detection
- [ ] Move overheat cooldown
- [ ] Test heat system

### **PHASE 6: Extract Triple Shot System**
- [ ] Move triple shot state to module
- [ ] Move triple shot activation logic
- [ ] Move burst fire logic
- [ ] Test triple shot system

### **PHASE 7: Extract Audio System**
- [ ] Move audio loading to module
- [ ] Move sound playback methods
- [ ] Test audio system

### **PHASE 8: Integration**
- [ ] Import `WeaponSystem` in `main.js`
- [ ] Initialize weapon system on Level 4 entry
- [ ] Replace direct weapon access with API calls
- [ ] Connect PlayerControls input to WeaponSystem
- [ ] Update GUISystem from WeaponSystem state
- [ ] Test all levels

### **PHASE 9: Cleanup**
- [ ] Remove old weapon code from `main.js`
- [ ] Update documentation
- [ ] Test thoroughly in all levels
- [ ] Verify no memory leaks

---

## ⚠️ CRITICAL CONSIDERATIONS

### **1. Level-Specific Weapons:**
- **Problem:** Level 2 and Level 4 have different weapon systems
- **Solution:** Support multiple weapon configurations via config
- **Pattern:** `weaponSystem.initializeLevel(levelConfig)`

### **2. Weapon Caching:**
- **Problem:** Weapons are cached in `level4State.weaponSlots`
- **Solution:** Move caching to WeaponSystem internal state
- **Pattern:** `this.weapons[slot] = weaponModel`

### **3. Heat System State:**
- **Problem:** Heat state is in `level4State`
- **Solution:** Move all heat state to WeaponSystem
- **Pattern:** Expose heat via getters: `getHeat()`, `getMaxHeat()`, `isOverheated()`

### **4. Weapon UI Updates:**
- **Problem:** `updateLevel4WeaponHUD()` is in main.js
- **Solution:** WeaponSystem calls callback: `onWeaponSwitched(slot, weapon)`
- **Pattern:** GUISystem listens to weapon changes

### **5. Shooting Raycast:**
- **Problem:** Raycast logic may be in main.js
- **Solution:** WeaponSystem performs raycast internally
- **Pattern:** `performRaycast()` method in WeaponSystem

### **6. Audio Listener:**
- **Problem:** Audio listener is global
- **Solution:** Pass audio listener via config
- **Pattern:** `config.audioListener`

---

## 🎯 SUCCESS CRITERIA

### **Technical Success:**
- [ ] All weapons load correctly
- [ ] Weapon switching works (1-9 keys)
- [ ] Shooting mechanics work
- [ ] Heat system works correctly
- [ ] Triple shot system works
- [ ] Audio plays correctly
- [ ] No performance degradation
- [ ] All levels work correctly
- [ ] No memory leaks

### **Architecture Success:**
- [ ] Follows same pattern as existing modules
- [ ] Clear separation of concerns
- [ ] Easy to find and modify code
- [ ] Well-documented API
- [ ] Proper cleanup on dispose

### **Long-term Success:**
- [ ] Easier to add new weapons
- [ ] Easier to modify weapon behavior
- [ ] Better for future developers
- [ ] Supports decades of development
- [ ] Enables team collaboration

---

## 📚 REFERENCE IMPLEMENTATIONS

### **Player Controls Pattern:**
- **File:** `three.js/player-controls.js`
- **Size:** 675 lines
- **Pattern:** ES module, class-based, callback-based config

### **Player Model Pattern:**
- **File:** `three.js/player-model.js`
- **Size:** 767 lines
- **Pattern:** ES module, class-based, dependency injection

### **GUI System Pattern:**
- **File:** `three.js/gui-system.js`
- **Size:** 2236 lines
- **Pattern:** ES module, class-based, comprehensive callbacks

**Follow the same proven pattern for Weapon System!**

---

## 🚀 IMPLEMENTATION ORDER

### **Recommended Sequence:**

1. **Create Module Structure** (30 min)
   - Create file with class structure
   - Add constructor and basic methods
   - Set up dependencies

2. **Extract Weapon Loading** (1 hour)
   - Move weapon loading code
   - Test weapon loading

3. **Extract Weapon Switching** (30 min)
   - Move switching logic
   - Test weapon switching

4. **Extract Shooting Mechanics** (1 hour)
   - Move shooting code
   - Test shooting

5. **Extract Heat System** (30 min)
   - Move heat logic
   - Test heat system

6. **Extract Triple Shot** (30 min)
   - Move triple shot code
   - Test triple shot

7. **Extract Audio** (15 min)
   - Move audio code
   - Test audio

8. **Integration** (1 hour)
   - Integrate with main.js
   - Connect to PlayerControls
   - Connect to GUISystem
   - Test all levels

9. **Cleanup** (30 min)
   - Remove old code
   - Update documentation
   - Final testing

**Total Estimated Time:** ~5-6 hours

---

## 📝 NOTES

- **Level 2 Weapons:** Level 2 has a different weapon system (weapon gallery, weapon shelves). This can be handled via level-specific configurations or a separate method.

- **Weapon Bob Animation:** If weapon bob animation exists, it should be moved to WeaponSystem's `update()` method.

- **Weapon Visibility:** Weapon visibility should be managed by WeaponSystem (hide in third-person, show in first-person).

- **Future Extensions:** The system should be designed to easily add:
  - New weapon types
  - Weapon attachments
  - Weapon upgrades
  - Inventory system
  - Weapon crafting

---

**Plan Created:** December 6, 2025  
**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Priority:** 🔄 **LONG-TERM REFACTORING**  
**Estimated Time:** 5-6 hours (if done incrementally)

🧀 **This modularization will improve code organization for decades of development!** 🧀

