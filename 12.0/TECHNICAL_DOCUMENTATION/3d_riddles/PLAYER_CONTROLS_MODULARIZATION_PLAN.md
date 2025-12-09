# 🎮 PLAYER CONTROLS MODULARIZATION PLAN

**Date:** December 6, 2025  
**Status:** 📋 **PLANNING PHASE**  
**Purpose:** Extract player controls from `main.js` into separate modular system (similar to `sky-system.js` and `grass-system.js`)

---

## 🎯 OBJECTIVE

Extract all player control logic from `three.js/main.js` into a separate modular file `three.js/player-controls.js`, following the same architecture pattern as:
- ✅ `sky-system.js` - Modular sky system
- ✅ `grass-system.js` - Modular grass/ground system

This will improve:
- **Code Organization** - Better separation of concerns
- **Maintainability** - Easier to find and modify control logic
- **Testability** - Isolated system for testing
- **Long-term Sustainability** - Modular architecture for decades
- **VR-Ready Architecture** - Extensible input system for Oculus, Meta Quest, and future VR devices
- **Future-Proof Design** - Plugin-based architecture for unlimited input method expansion

---

## 📋 CURRENT STATE ANALYSIS

### **Existing Modular Systems Pattern:**

#### **1. Sky System (`sky-system.js`):**
```javascript
// Structure:
import * as THREE from "three";

export class SkySystem {
  constructor(scene, config) { ... }
  update(delta, playerPosition, camera) { ... }
  setTime(hour, minute) { ... }
  dispose() { ... }
}
```

**Integration in `main.js`:**
```javascript
import { SkySystem } from "./sky-system.js";

let skySystem = null;

function initializeSkySystem(levelId) {
  // Create sky system instance
  skySystem = new SkySystem(scene, skyConfig);
}

// In animate loop:
if (skySystem) {
  skySystem.update(delta, playerPosition, camera);
}
```

#### **2. Grass System (`grass-system.js`):**
```javascript
// Structure:
import * as THREE from "three";

export class GrassSystem {
  constructor(scene, config) { ... }
  update(delta, playerPosition) { ... }
  dispose() { ... }
}
```

**Integration in `main.js`:**
```javascript
import { GrassSystem } from "./grass-system.js";

let grassSystem = null;

function initializeGrassSystem(levelId) {
  grassSystem = new GrassSystem(scene, grassConfig);
}

// In animate loop:
if (grassSystem) {
  grassSystem.update(delta, playerPosition);
}
```

---

## 🔍 PLAYER CONTROLS CURRENT IMPLEMENTATION

### **Key Components to Extract:**

#### **1. Movement State Objects:**
```javascript
const movement = { forward: false, backward: false, left: false, right: false, sprint: false, flyUp: false, flyDown: false };
const keyboardMovement = { forward: false, backward: false, left: false, right: false, sprint: false, flyUp: false, flyDown: false };
const joystickMovementFlags = { forward: false, backward: false, left: false, right: false };
```

#### **2. Input Handlers:**
- Keyboard event listeners (keydown, keyup)
- Mouse/pointer lock controls
- Mobile joystick controls
- Touch controls

#### **3. Movement Functions:**
- `updateAggregatedMovement()` - Combines keyboard + joystick
- `refreshJoystickMovementFlags()` - Updates joystick state
- `getForwardVector()` - Calculates forward direction
- `getSideVector()` - Calculates side direction

#### **4. Movement Calculation:**
- Player velocity updates
- Sprint handling
- Fly mode (god mode)
- Ground collision integration

#### **5. Mobile Controls:**
- Joystick initialization
- Joystick position tracking
- Camera joystick handling

---

## 📁 PROPOSED FILE STRUCTURE

### **New File: `three.js/player-controls.js`**

```javascript
/**
 * Player Controls System for Three.js
 * Modular input handling and movement calculation
 * 
 * Features:
 * - Keyboard input (WASD, Space, Shift)
 * - Mouse/Pointer lock controls
 * - Mobile joystick support
 * - Movement state management
 * - Direction vector calculation
 * - VR-Ready Architecture (Oculus, Meta Quest, future devices)
 * - Plugin-based input system for unlimited expansion
 */

import * as THREE from "three";

// Input Provider Interface (for future VR/AR/other input methods)
export class InputProvider {
  constructor() {
    this.type = 'base'; // 'keyboard', 'mouse', 'mobile', 'vr', etc.
  }
  
  initialize() { /* Override in subclasses */ }
  update(delta) { /* Override in subclasses */ }
  getMovementState() { /* Override in subclasses */ }
  dispose() { /* Override in subclasses */ }
}

export class PlayerControls {
  constructor(scene, camera, renderer, config) {
    // Initialize control state
    // Setup input providers
    // Configure mobile joysticks
    // Register VR providers (future-ready)
  }

  // Input Provider Management (VR-Ready)
  registerInputProvider(provider) { ... }
  unregisterInputProvider(providerType) { ... }
  
  // Input handling methods
  handleKeyDown(event) { ... }
  handleKeyUp(event) { ... }
  handlePointerLockChange() { ... }
  
  // Movement calculation
  getMovementState() { ... }
  getForwardVector(camera, isFirstPerson) { ... }
  getSideVector(camera, isFirstPerson) { ... }
  
  // VR Support (future-ready)
  enableVR() { ... }
  disableVR() { ... }
  isVRMode() { ... }
  
  // Update methods
  update(delta) { ... }
  
  // Cleanup
  dispose() { ... }
}
```

### **Future File: `three.js/input-providers/vr-input-provider.js` (VR-Ready)**

```javascript
/**
 * VR Input Provider for WebXR (Oculus, Meta Quest, etc.)
 * Future-ready architecture for VR support
 */

import { InputProvider } from "../player-controls.js";
import * as THREE from "three";

export class VRInputProvider extends InputProvider {
  constructor(renderer, session) {
    super();
    this.type = 'vr';
    this.renderer = renderer;
    this.session = session;
    this.controllers = [];
  }
  
  initialize() {
    // Setup WebXR controllers
    // Map VR controller inputs to movement
  }
  
  update(delta) {
    // Poll VR controller states
    // Update movement from VR controllers
  }
  
  getMovementState() {
    // Return movement from VR controllers
  }
}
```

---

## 🔧 EXTRACTION CHECKLIST

### **Phase 1: Identify All Components**

#### **1. Movement State:**
- [ ] `movement` object (forward, backward, left, right, sprint, flyUp, flyDown)
- [ ] `keyboardMovement` object
- [ ] `joystickMovementFlags` object
- [ ] `updateAggregatedMovement()` function
- [ ] `refreshJoystickMovementFlags()` function

#### **2. Keyboard Input:**
- [ ] Keydown event listener
- [ ] Keyup event listener
- [ ] Key mapping (W, A, S, D, Space, Shift, etc.)
- [ ] Special key handlers (G, L, etc. for god mode features)

#### **3. Mouse/Pointer Controls:**
- [ ] Pointer lock controls setup
- [ ] Pointer lock change event listener
- [ ] Mouse movement tracking
- [ ] Third-person camera rotation

#### **4. Mobile Joystick:**
- [ ] Joystick initialization
- [ ] Joystick position tracking
- [ ] Camera joystick handling
- [ ] Touch event handlers

#### **5. Movement Vectors:**
- [ ] `getForwardVector()` function
- [ ] `getSideVector()` function
- [ ] Camera-based direction calculation

#### **6. Configuration:**
- [ ] Movement speed constants
- [ ] Sprint multiplier
- [ ] God mode multipliers
- [ ] Level-specific speed settings

---

### **Phase 2: Dependencies to Consider**

#### **External Dependencies:**
- [ ] `THREE.Vector3` - For direction vectors
- [ ] `camera` object - For first/third-person direction
- [ ] `PointerLockControls` - For pointer lock
- [ ] Mobile joystick library (if external)

#### **Internal Dependencies (from main.js):**
- [ ] `isFirstPerson()` function
- [ ] `isThirdPerson()` function
- [ ] `isJoystickView()` function
- [ ] `godMode` flag
- [ ] `cameraMode` variable
- [ ] `currentLevel` variable (for level-specific settings)
- [ ] `onGround` flag (for jump logic)
- [ ] `playerVelocity` object (needs to be updated by controls)

#### **Shared State:**
- [ ] Player position (read-only for controls)
- [ ] Camera reference (for direction calculation)
- [ ] Renderer DOM element (for pointer lock)

---

### **Phase 3: Integration Points**

#### **1. Initialization:**
- [ ] Initialize in `main.js` after camera/scene setup
- [ ] Pass required dependencies (camera, renderer, scene)
- [ ] Configure per-level settings

#### **2. Update Loop:**
- [ ] Call `playerControls.update(delta)` in animate loop
- [ ] Get movement state for player physics
- [ ] Get direction vectors for movement calculation

#### **3. Event Listeners:**
- [ ] Document existing listeners to move
- [ ] Ensure listeners are cleaned up on dispose
- [ ] Handle level changes (remove/add listeners)

#### **4. State Access:**
- [ ] Expose movement state via getter methods
- [ ] Provide callbacks for state changes
- [ ] Maintain backward compatibility with existing code

---

## 🏗️ PROPOSED ARCHITECTURE

### **Class Structure:**

```javascript
export class PlayerControls {
  constructor(scene, camera, renderer, config) {
    this.scene = scene;
    this.camera = camera;
    this.renderer = renderer;
    this.config = config;
    
    // Movement state
    this.movement = { forward: false, backward: false, left: false, right: false, sprint: false, flyUp: false, flyDown: false };
    this.keyboardMovement = { forward: false, backward: false, left: false, right: false, sprint: false, flyUp: false, flyDown: false };
    this.joystickMovementFlags = { forward: false, backward: false, left: false, right: false };
    
    // Input state
    this.keys = new Set();
    this.pointerLocked = false;
    
    // Mobile controls
    this.mobileJoystick = null;
    this.mobileCameraJoystick = null;
    this.joystickActive = false;
    this.cameraJoystickActive = false;
    
    // Initialize
    this.setupEventListeners();
    this.initializeMobileControls();
  }
  
  // Public API
  getMovementState() { return this.movement; }
  getForwardVector() { ... }
  getSideVector() { ... }
  update(delta) { ... }
  dispose() { ... }
  
  // Internal methods
  setupEventListeners() { ... }
  handleKeyDown(event) { ... }
  handleKeyUp(event) { ... }
  updateAggregatedMovement() { ... }
  // ... more methods
}
```

---

## 🔗 INTEGRATION STRATEGY

### **Step 1: Create New Module File**
1. Create `three.js/player-controls.js`
2. Define `PlayerControls` class structure
3. Export class for import

### **Step 2: Extract Code to Module**
1. Move movement state objects
2. Move input handlers
3. Move movement calculation functions
4. Move mobile joystick code
5. Update references to use `this` instead of global scope

### **Step 3: Update main.js**
1. Import `PlayerControls` class
2. Create instance after camera/scene setup
3. Replace direct movement access with `playerControls.getMovementState()`
4. Call `playerControls.update(delta)` in animate loop
5. Remove old movement code (keep references for now)

### **Step 4: Handle Dependencies**
1. Pass required dependencies via constructor
2. Use callbacks/events for state changes
3. Provide access to shared state (godMode, cameraMode, etc.)
4. Handle level-specific configurations

### **Step 5: Testing & Cleanup**
1. Test all movement in all levels
2. Test mobile controls
3. Test god mode features
4. Remove commented-out old code
5. Update documentation

---

## ⚠️ CRITICAL CONSIDERATIONS

### **1. Shared State Access:**
- **Problem:** Controls need access to `godMode`, `cameraMode`, `currentLevel`
- **Solution:** Pass as config or provide getter methods in main.js
- **Pattern:** Use dependency injection or event callbacks

### **2. Player Velocity Updates:**
- **Problem:** Movement state affects `playerVelocity` in main.js
- **Solution:** Controls return movement state, main.js applies to velocity
- **Pattern:** Separate input from physics

### **3. Level-Specific Settings:**
- **Problem:** Different levels have different movement speeds
- **Solution:** Pass level config to controls or use getter methods
- **Pattern:** Config-based initialization

### **4. Event Listener Lifecycle:**
- **Problem:** Listeners need to be cleaned up on level changes
- **Solution:** Implement `dispose()` method to remove listeners
- **Pattern:** Cleanup on level warp/restart

### **5. Mobile Joystick Initialization:**
- **Problem:** Mobile controls depend on DOM elements
- **Solution:** Initialize after DOM is ready
- **Pattern:** Deferred initialization

### **6. Pointer Lock Integration:**
- **Problem:** Pointer lock is used by multiple systems
- **Solution:** Controls manage pointer lock, expose state
- **Pattern:** Single source of truth

---

## 📊 CODE LOCATION MAPPING

### **Current Locations in main.js:**

#### **Movement State (Lines ~8762-8774):**
- `const movement = {...}`
- `const keyboardMovement = {...}`
- `const joystickMovementFlags = {...}`
- `function updateAggregatedMovement() {...}`
- `function refreshJoystickMovementFlags() {...}`

#### **Direction Vectors (Lines ~8799-8864):**
- `function getForwardVector() {...}`
- `function getSideVector() {...}`

#### **Keyboard Handlers:**
- Search for `keydown` event listeners
- Search for `keyup` event listeners
- Key mapping logic

#### **Mobile Joystick (Lines ~52-58):**
- `let mobileJoystick = null`
- `let mobileCameraJoystick = null`
- `let joystickActive = false`
- Joystick initialization code

#### **Pointer Lock:**
- `PointerLockControls` setup
- `pointerlockchange` event listener
- Pointer lock click handlers

---

## 🎯 IMPLEMENTATION STEPS

### **Step 1: Create Module File Structure**
1. Create `three.js/player-controls.js`
2. Set up ES module structure (`import/export`)
3. Define `PlayerControls` class skeleton
4. Add basic constructor

### **Step 2: Extract Movement State**
1. Move movement objects to class properties
2. Move aggregation functions to class methods
3. Update references to use `this.movement`

### **Step 3: Extract Input Handlers**
1. Move keyboard event listeners
2. Move pointer lock handlers
3. Move mobile joystick code
4. Ensure proper cleanup in `dispose()`

### **Step 4: Extract Movement Calculations**
1. Move `getForwardVector()` to class method
2. Move `getSideVector()` to class method
3. Update camera/person mode checks

### **Step 5: Handle Dependencies**
1. Pass camera/renderer via constructor
2. Create callback system for shared state
3. Handle level-specific configurations

### **Step 6: Integration in main.js**
1. Import `PlayerControls` class
2. Create instance after setup
3. Replace movement access with getter methods
4. Call update in animate loop

### **Step 7: Testing**
1. Test all movement directions
2. Test sprint functionality
3. Test god mode (fly up/down)
4. Test mobile joystick
5. Test all camera modes
6. Test all 5 levels

### **Step 8: Cleanup**
1. Remove old code from main.js
2. Update comments/documentation
3. Verify no broken references

---

## 🔄 DEPENDENCY INJECTION PATTERN

### **Solution for Shared State:**

Instead of direct access to global variables, use dependency injection:

```javascript
// In main.js
const playerControls = new PlayerControls(scene, camera, renderer, {
  // Callback to get current god mode
  getGodMode: () => godMode,
  
  // Callback to get current camera mode
  getCameraMode: () => cameraMode,
  
  // Callback to check first person
  isFirstPerson: () => isFirstPerson(),
  
  // Callback to check third person
  isThirdPerson: () => isThirdPerson(),
  
  // Callback to get current level
  getCurrentLevel: () => currentLevel,
  
  // Movement speed config
  movementSpeeds: {
    walk: 96,
    sprint: 168,
    godModeMultiplier: 2.0
  }
});
```

This keeps the controls module independent while still accessing needed state.

---

## 📝 PUBLIC API DESIGN

### **Getter Methods:**
```javascript
// Get current movement state
getMovementState() {
  return { ...this.movement }; // Return copy, not reference
}

// Get forward direction vector
getForwardVector() {
  // Uses injected callbacks to get camera mode
  // Returns THREE.Vector3
}

// Get side direction vector
getSideVector() {
  // Uses injected callbacks to get camera mode
  // Returns THREE.Vector3
}

// Get pointer lock state
isPointerLocked() {
  return this.pointerLocked;
}
```

### **Update Method:**
```javascript
update(delta) {
  // Update joystick flags
  this.refreshJoystickMovementFlags();
  
  // Aggregate movement state
  this.updateAggregatedMovement();
  
  // Update pointer lock state
  // ... other updates
}
```

### **Event Handlers (Public):**
```javascript
// Allow external code to trigger pointer lock
requestPointerLock() {
  this.renderer.domElement.requestPointerLock();
}

// Allow external code to release pointer lock
releasePointerLock() {
  document.exitPointerLock();
}
```

---

## 🚨 CRITICAL WARNINGS

### **1. Don't Break Existing Functionality:**
- **Test thoroughly** before removing old code
- **Keep backup** of original movement code
- **Verify** all levels work after extraction

### **2. Maintain Performance:**
- **Don't add overhead** with unnecessary callbacks
- **Cache** frequently accessed values
- **Optimize** direction vector calculations

### **3. Handle Edge Cases:**
- **Level changes** - Cleanup and reinitialize
- **Camera mode switches** - Update direction vectors
- **Mobile/Desktop switching** - Handle joystick lifecycle
- **God mode toggling** - Update movement multipliers

### **4. Preserve Global State:**
- Some code in main.js may directly access `movement` object
- Need to find all references and update
- Consider keeping getter that returns same structure

---

## ✅ SUCCESS CRITERIA

### **Technical Success:**
- [ ] All movement works identically to before
- [ ] All camera modes work correctly
- [ ] Mobile controls function properly
- [ ] God mode features work
- [ ] No performance degradation
- [ ] Code is cleaner and more organized

### **Architecture Success:**
- [ ] Follows same pattern as Sky/Grass systems
- [ ] Clear separation of concerns
- [ ] Easy to find and modify controls
- [ ] Well-documented API
- [ ] Proper cleanup on dispose

### **Long-term Success:**
- [ ] Easier to add new input methods
- [ ] Easier to modify movement logic
- [ ] Better for future developers
- [ ] Supports decades of development

---

## 📚 REFERENCE IMPLEMENTATIONS

### **Sky System Pattern:**
- **File:** `three.js/sky-system.js`
- **Export:** `export class SkySystem`
- **Import:** `import { SkySystem } from "./sky-system.js"`
- **Instance:** `let skySystem = null`
- **Init:** `skySystem = new SkySystem(scene, config)`
- **Update:** `skySystem.update(delta, playerPosition, camera)`
- **Cleanup:** `skySystem.dispose()`

### **Grass System Pattern:**
- **File:** `three.js/grass-system.js`
- **Export:** `export class GrassSystem`
- **Import:** `import { GrassSystem } from "./grass-system.js"`
- **Instance:** `let grassSystem = null`
- **Init:** `grassSystem = new GrassSystem(scene, config)`
- **Update:** `grassSystem.update(delta, playerPosition)`
- **Cleanup:** `grassSystem.dispose()`

**Follow the same pattern for Player Controls!**

---

## 🎯 NEXT STEPS

1. **Review this plan** with user
2. **Identify all code sections** to extract
3. **Create module file** structure
4. **Extract code incrementally** (test after each step)
5. **Integrate in main.js**
6. **Test thoroughly** in all levels
7. **Clean up** old code
8. **Update documentation**

---

**Plan Created:** December 6, 2025  
**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Priority:** 🔄 **LONG-TERM REFACTORING**

🧀 **This modularization will improve code organization for decades of development!** 🧀

