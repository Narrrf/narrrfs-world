# 🎮 PLAYER CONTROLS VR-READY ARCHITECTURE

**Date:** December 6, 2025  
**Status:** 🏗️ **ARCHITECTURE DESIGN**  
**Purpose:** VR-Ready, extensible player controls system for decades of development

---

## 🎯 DESIGN PRINCIPLES

### **1. Plugin-Based Input System**
- **Extensible Architecture** - Add new input methods without modifying core code
- **Input Provider Pattern** - Each input type is a separate provider
- **Unified Movement State** - All providers contribute to the same movement state

### **2. VR-Ready from Day One**
- **WebXR Integration Ready** - Architecture supports WebXR controllers
- **Oculus Quest Compatible** - Designed for Meta Quest VR headsets
- **Future-Proof** - Easy to add new VR devices

### **3. Long-Term Sustainability**
- **Modular Design** - Separate concerns, easy to maintain
- **Clear Interfaces** - Well-defined APIs for extension
- **Decades of Support** - Architecture supports future technologies

---

## 🏗️ ARCHITECTURE OVERVIEW

### **Core Components:**

```
PlayerControls (Main Controller)
├── InputProvider (Base Class)
│   ├── KeyboardProvider (Current: WASD, Space, Shift)
│   ├── MouseProvider (Current: Pointer Lock, Camera Rotation)
│   ├── MobileJoystickProvider (Current: Touch Joysticks)
│   └── VRInputProvider (Future: WebXR Controllers) ⬅️ VR-READY
│
├── Movement Aggregator (Combines all input sources)
│
├── Direction Calculator (Forward/Side vectors)
│
└── State Manager (Movement state, pointer lock, etc.)
```

---

## 📋 INPUT PROVIDER INTERFACE

### **Base Interface (All Providers Must Implement):**

```javascript
export class InputProvider {
  constructor() {
    this.type = 'base'; // 'keyboard', 'mouse', 'mobile', 'vr', etc.
    this.enabled = true;
    this.priority = 0; // Higher priority providers override lower priority
  }
  
  // Required Methods:
  initialize() { throw new Error('Must implement initialize()'); }
  update(delta) { throw new Error('Must implement update()'); }
  getMovementState() { throw new Error('Must implement getMovementState()'); }
  dispose() { throw new Error('Must implement dispose()'); }
  
  // Optional Methods:
  getRotation() { return { x: 0, y: 0, z: 0 }; } // For VR controllers
  getButtonState(buttonId) { return false; } // For VR buttons
  isAvailable() { return false; } // Check if device is available
}
```

---

## 🔌 INPUT PROVIDER IMPLEMENTATIONS

### **1. Keyboard Provider (Current):**

```javascript
export class KeyboardInputProvider extends InputProvider {
  constructor() {
    super();
    this.type = 'keyboard';
    this.priority = 1; // Lower priority (can be overridden by VR)
    this.keys = new Set();
    this.movement = {
      forward: false,
      backward: false,
      left: false,
      right: false,
      sprint: false,
      flyUp: false,
      flyDown: false
    };
  }
  
  initialize() {
    document.addEventListener('keydown', this.handleKeyDown.bind(this));
    document.addEventListener('keyup', this.handleKeyUp.bind(this));
  }
  
  getMovementState() {
    return { ...this.movement };
  }
  
  dispose() {
    document.removeEventListener('keydown', this.handleKeyDown);
    document.removeEventListener('keyup', this.handleKeyUp);
  }
}
```

### **2. Mouse Provider (Current):**

```javascript
export class MouseInputProvider extends InputProvider {
  constructor(renderer) {
    super();
    this.type = 'mouse';
    this.priority = 1;
    this.renderer = renderer;
    this.pointerLocked = false;
    this.rotation = { x: 0, y: 0 };
  }
  
  initialize() {
    // Pointer lock setup
    // Mouse movement tracking
  }
  
  getRotation() {
    return { ...this.rotation };
  }
  
  dispose() {
    // Cleanup pointer lock
  }
}
```

### **3. Mobile Joystick Provider (Current):**

```javascript
export class MobileJoystickInputProvider extends InputProvider {
  constructor() {
    super();
    this.type = 'mobile';
    this.priority = 2; // Higher priority on mobile devices
    this.joystickActive = false;
    this.direction = { x: 0, y: 0 };
  }
  
  initialize() {
    // Touch event handlers
    // Joystick UI initialization
  }
  
  getMovementState() {
    // Convert joystick direction to movement
  }
  
  isAvailable() {
    return /Mobi|Android/i.test(navigator.userAgent);
  }
  
  dispose() {
    // Cleanup touch handlers
  }
}
```

### **4. VR Input Provider (Future - VR-Ready Architecture):**

```javascript
export class VRInputProvider extends InputProvider {
  constructor(renderer, session) {
    super();
    this.type = 'vr';
    this.priority = 10; // Highest priority when VR is active
    this.renderer = renderer;
    this.session = session;
    this.controllers = [];
    this.movement = {
      forward: false,
      backward: false,
      left: false,
      right: false,
      sprint: false,
      flyUp: false,
      flyDown: false
    };
  }
  
  initialize() {
    // Setup WebXR controllers
    // Map VR controller inputs
    this.setupVRControllers();
  }
  
  setupVRControllers() {
    // Create VR controller objects
    // Setup controller input mapping
    // Oculus Quest controller mapping
    // Meta Quest controller mapping
  }
  
  update(delta) {
    // Poll VR controller states
    // Update movement from VR controllers
    this.pollVRControllers();
  }
  
  getMovementState() {
    // Return movement from VR controllers
    return { ...this.movement };
  }
  
  getRotation() {
    // Return VR head/controller rotation
    return this.controllers[0]?.rotation || { x: 0, y: 0, z: 0 };
  }
  
  getButtonState(buttonId) {
    // Return VR button state (grip, trigger, thumbstick, etc.)
    return this.controllers[0]?.getButtonState(buttonId) || false;
  }
  
  isAvailable() {
    // Check if WebXR is available and VR session is active
    return this.session && this.session.inputSources.length > 0;
  }
  
  dispose() {
    // Cleanup VR controllers
    this.controllers = [];
  }
}
```

---

## 🎮 PLAYER CONTROLS MAIN CLASS

### **Architecture with VR Support:**

```javascript
export class PlayerControls {
  constructor(scene, camera, renderer, config) {
    this.scene = scene;
    this.camera = camera;
    this.renderer = renderer;
    this.config = config;
    
    // Input providers (plugin system)
    this.inputProviders = new Map();
    this.activeProviders = [];
    
    // Movement state (aggregated from all providers)
    this.movement = {
      forward: false,
      backward: false,
      left: false,
      right: false,
      sprint: false,
      flyUp: false,
      flyDown: false
    };
    
    // VR state
    this.vrMode = false;
    this.vrSession = null;
    
    // Initialize default providers
    this.initializeDefaultProviders();
  }
  
  // Register new input provider (for VR, gamepad, etc.)
  registerInputProvider(provider) {
    if (!(provider instanceof InputProvider)) {
      throw new Error('Provider must extend InputProvider');
    }
    
    this.inputProviders.set(provider.type, provider);
    provider.initialize();
    
    // Auto-enable if VR provider is registered and available
    if (provider.type === 'vr' && provider.isAvailable()) {
      this.enableVR(provider.session);
    }
    
    console.log(`✅ [CONTROLS] Registered input provider: ${provider.type}`);
  }
  
  // Unregister input provider
  unregisterInputProvider(providerType) {
    const provider = this.inputProviders.get(providerType);
    if (provider) {
      provider.dispose();
      this.inputProviders.delete(providerType);
      console.log(`🗑️ [CONTROLS] Unregistered input provider: ${providerType}`);
    }
  }
  
  // Enable VR mode
  async enableVR(session) {
    this.vrMode = true;
    this.vrSession = session;
    
    // Disable pointer lock in VR
    if (this.renderer.domElement.requestPointerLock) {
      document.exitPointerLock();
    }
    
    console.log('🥽 [CONTROLS] VR mode enabled');
  }
  
  // Disable VR mode
  disableVR() {
    this.vrMode = false;
    this.vrSession = null;
    console.log('🖥️ [CONTROLS] VR mode disabled');
  }
  
  // Check if VR mode is active
  isVRMode() {
    return this.vrMode && this.vrSession !== null;
  }
  
  // Aggregate movement from all active providers
  updateAggregatedMovement() {
    // Reset movement
    this.movement = {
      forward: false,
      backward: false,
      left: false,
      right: false,
      sprint: false,
      flyUp: false,
      flyDown: false
    };
    
    // Get active providers sorted by priority (highest first)
    const activeProviders = Array.from(this.inputProviders.values())
      .filter(p => p.enabled && (p.isAvailable ? p.isAvailable() : true))
      .sort((a, b) => b.priority - a.priority);
    
    // Aggregate movement from all providers (VR overrides others)
    for (const provider of activeProviders) {
      const providerMovement = provider.getMovementState();
      if (providerMovement) {
        // VR has highest priority - override others
        if (provider.type === 'vr' && this.isVRMode()) {
          this.movement = { ...providerMovement };
          break; // VR takes full control
        } else {
          // Combine with existing movement (OR logic)
          this.movement.forward = this.movement.forward || providerMovement.forward;
          this.movement.backward = this.movement.backward || providerMovement.backward;
          this.movement.left = this.movement.left || providerMovement.left;
          this.movement.right = this.movement.right || providerMovement.right;
          this.movement.sprint = this.movement.sprint || providerMovement.sprint;
          this.movement.flyUp = this.movement.flyUp || providerMovement.flyUp;
          this.movement.flyDown = this.movement.flyDown || providerMovement.flyDown;
        }
      }
    }
  }
  
  // Get final movement state (for main.js)
  getMovementState() {
    return { ...this.movement };
  }
  
  // Update all providers
  update(delta) {
    // Update all input providers
    for (const provider of this.inputProviders.values()) {
      if (provider.enabled) {
        provider.update(delta);
      }
    }
    
    // Aggregate movement from all providers
    this.updateAggregatedMovement();
  }
  
  // Cleanup
  dispose() {
    // Dispose all providers
    for (const provider of this.inputProviders.values()) {
      provider.dispose();
    }
    this.inputProviders.clear();
    this.activeProviders = [];
  }
}
```

---

## 🔌 VR INTEGRATION PATTERN

### **Future VR Integration (WebXR):**

```javascript
// In main.js (future implementation):
import { PlayerControls } from "./player-controls.js";
import { VRInputProvider } from "./input-providers/vr-input-provider.js";

// Initialize player controls
const playerControls = new PlayerControls(scene, camera, renderer, config);

// When VR session starts (WebXR):
async function startVRSession() {
  const session = await navigator.xr.requestSession('immersive-vr');
  
  // Create VR input provider
  const vrProvider = new VRInputProvider(renderer, session);
  
  // Register VR provider with player controls
  playerControls.registerInputProvider(vrProvider);
  
  // Enable VR mode
  await playerControls.enableVR(session);
}

// VR controller mapping (Oculus Quest / Meta Quest):
// - Left Thumbstick: Movement (forward/backward/left/right)
// - Right Thumbstick: Camera rotation
// - Grip Button: Sprint
// - Trigger: Jump/Fly Up
// - A/X Button: Interact
// - B/Y Button: Weapon Switch
```

---

## 📋 VR CONTROLLER MAPPING

### **Oculus Quest / Meta Quest Controllers:**

| Input | Action | Priority |
|-------|--------|----------|
| **Left Thumbstick** | Move (forward/backward/left/right) | Primary |
| **Right Thumbstick** | Camera rotation | Primary |
| **Grip Button** | Sprint | Secondary |
| **Trigger** | Jump (normal) / Fly Up (god mode) | Primary |
| **A/X Button** | Interact (Level 2 lever, etc.) | Primary |
| **B/Y Button** | Weapon switch (Level 4) | Secondary |
| **Thumbstick Press** | Crouch / Fly Down (god mode) | Secondary |

### **Movement in VR:**
- **Locomotion:** Thumbstick-based movement (comfortable for VR)
- **Teleportation:** (Future option - point and teleport)
- **Room-Scale:** (Future option - use real-world movement)

---

## 🎯 LONG-TERM ARCHITECTURE BENEFITS

### **1. Easy VR Integration:**
- Just register `VRInputProvider` when VR session starts
- No changes to core movement logic
- VR automatically takes priority over keyboard/mouse

### **2. Multiple Input Methods:**
- Support multiple input methods simultaneously
- Easy to add gamepad, touch, eye tracking, etc.
- Priority system ensures correct input selection

### **3. Future-Proof:**
- New VR devices? Just create new provider
- New input methods? Just implement interface
- Architecture supports decades of development

### **4. Testing & Debugging:**
- Each provider can be tested independently
- Easy to mock providers for testing
- Clear separation of concerns

---

## 📁 FILE STRUCTURE (VR-READY)

```
three.js/
├── player-controls.js              # Main PlayerControls class
├── input-providers/
│   ├── input-provider-base.js      # Base InputProvider interface
│   ├── keyboard-input-provider.js  # Keyboard input (WASD)
│   ├── mouse-input-provider.js     # Mouse/pointer lock
│   ├── mobile-joystick-provider.js # Mobile touch joysticks
│   └── vr-input-provider.js        # VR controllers (Future) ⬅️ VR-READY
└── main.js                          # Integration
```

---

## 🔄 MIGRATION STRATEGY

### **Phase 1: Current Implementation (Now)**
1. Create base `PlayerControls` class
2. Extract current keyboard/mouse/mobile code
3. Implement `KeyboardProvider`, `MouseProvider`, `MobileJoystickProvider`
4. Test all existing functionality

### **Phase 2: VR-Ready Architecture (Soon)**
1. Implement `InputProvider` base interface
2. Refactor providers to use interface
3. Add provider registration system
4. Test provider switching

### **Phase 3: VR Integration (Future)**
1. Implement `VRInputProvider` with WebXR
2. Add VR controller mapping
3. Test VR movement and rotation
4. Add VR-specific features (teleportation, room-scale)

---

## ✅ VR-READY CHECKLIST

### **Architecture:**
- [x] Plugin-based input system
- [x] InputProvider interface defined
- [x] Priority system for input selection
- [x] VR provider structure designed
- [ ] VR provider implementation (Future)

### **Integration:**
- [ ] WebXR session handling
- [ ] VR controller mapping
- [ ] VR movement calculation
- [ ] VR camera rotation

### **Testing:**
- [ ] VR device detection
- [ ] VR controller input polling
- [ ] VR movement testing
- [ ] VR/Desktop switching

---

**Architecture Designed:** December 6, 2025  
**Status:** 🏗️ **VR-READY ARCHITECTURE COMPLETE**  
**VR Integration:** 📋 **READY FOR FUTURE IMPLEMENTATION**

🧀 **This architecture supports decades of development including VR!** 🧀

