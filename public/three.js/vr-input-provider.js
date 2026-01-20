/**
 * ============================================================================
 * VR INPUT PROVIDER - VR Controller Input Handling
 * ============================================================================
 * 
 * ✅ STATUS: UPDATED FOR META QUEST 3 - JANUARY 18, 2026
 * 📅 CREATED: December 6, 2025
 * 📅 LAST UPDATED: January 18, 2026 (Quest 3 Controller Fixes)
 * 
 * ============================================================================
 * 🎯 PURPOSE
 * ============================================================================
 * 
 * Handles VR controller input using WebXR API:
 * - VR controller input (thumbsticks, buttons, triggers)
 * - Movement from VR controllers (left thumbstick)
 * - Rotation from VR controllers (right thumbstick)
 * - Button state management
 * - Extends InputProvider base class for plugin-based architecture
 * 
 * Supported VR devices:
 * - Meta Quest 3 (Primary target - 2026)
 * - Meta Quest 2
 * - Oculus Quest
 * - Other WebXR-compatible VR headsets
 * 
 * ============================================================================
 * ✅ PHASE 1 FIXES (January 18, 2026)
 * ============================================================================
 * 
 * ✅ **QUEST 3 CONTROLLER FIXES:**
 * - ✅ Fixed Y-axis inversion (forward = negative Y)
 * - ✅ Increased deadzone (0.1 → 0.15)
 * - ✅ Added rotation input (right thumbstick)
 * - ✅ Added sprint (left thumbstick click)
 * - ✅ Added jump (X/A buttons)
 * 
 * ============================================================================
 * 📦 WHAT THIS MODULE LOADS
 * ============================================================================
 * 
 * Resources:
 * - No external files
 * - Uses WebXR API (browser native)
 * 
 * Dependencies:
 * - WebXR API (navigator.xr)
 * - PlayerControls (InputProvider base class)
 * 
 * ============================================================================
 * 🔧 INTEGRATION IN MAIN.JS
 * ============================================================================
 * 
 * 1. Import:
 *    import { VRInputProvider } from "./vr-input-provider.js";
 * 
 * 2. Check VR support:
 *    if (await checkVRSupport()) {
 *      const xrSession = await startVRSession();
 *      vrInputProvider = new VRInputProvider(xrSession);
 *      vrInputProvider.enable();
 *    }
 * 
 * 3. In animate loop:
 *    renderer.setAnimationLoop((timestamp, xrFrame) => {
 *      if (vrInputProvider && isVRSessionActive()) {
 *        vrInputProvider.update(delta);
 *      }
 *    });
 * 
 * ============================================================================
 * 🎮 KEY FUNCTIONS
 * ============================================================================
 * 
 * - enable() - Enable VR input provider
 * - disable() - Disable VR input provider
 * - update(delta) - Update controller state (MUST be called every frame!)
 * - getMovementState() - Get movement from left thumbstick
 * - getRotationInput() - Get rotation from right thumbstick (NEW!)
 * - isAvailable() - Check if VR is available (static)
 * 
 * ============================================================================
 */

import { InputProvider } from './player-controls.js';

/**
 * VR Input Provider Class
 * Handles VR controller input and movement
 */
export class VRInputProvider extends InputProvider {
  constructor(xrSession) {
    super();
    this.type = 'vr';
    this.xrSession = xrSession;
    this.enabled = false;
    
    // VR controller state
    this.leftController = null;
    this.rightController = null;
    this.controllerInputSources = [];
    
    // Movement state from VR controllers
    this.thumbstickState = {
      left: { x: 0, y: 0 },
      right: { x: 0, y: 0 }
    };
    
    // Button states
    this.buttonStates = {
      left: {},
      right: {}
    };
    
    console.log('🥽 [VR INPUT] VRInputProvider created');
  }
  
  /**
   * Check if VR is available
   */
  static async isAvailable() {
    if (navigator.xr) {
      try {
        return await navigator.xr.isSessionSupported('immersive-vr');
      } catch (err) {
        return false;
      }
    }
    return false;
  }
  
  /**
   * Enable VR input provider
   */
  enable() {
    if (!this.xrSession) {
      console.error('❌ [VR INPUT] Cannot enable: No XR session');
      return false;
    }
    
    this.enabled = true;
    this.setupControllerHandlers();
    console.log('✅ [VR INPUT] VR input provider enabled');
    return true;
  }
  
  /**
   * Disable VR input provider
   */
  disable() {
    this.enabled = false;
    this.leftController = null;
    this.rightController = null;
    this.controllerInputSources = [];
    console.log('🖥️ [VR INPUT] VR input provider disabled');
  }
  
  /**
   * Setup VR controller handlers
   */
  setupControllerHandlers() {
    if (!this.xrSession) return;
    
    // Get input sources (controllers)
    this.xrSession.addEventListener('inputsourceschange', (event) => {
      this.handleInputSourcesChange(event);
    });
    
    // Initialize existing input sources
    this.controllerInputSources = this.xrSession.inputSources || [];
    this.updateControllers();
  }
  
  /**
   * Handle input sources change (controllers connected/disconnected)
   */
  handleInputSourcesChange(event) {
    // Add new controllers
    event.added.forEach(inputSource => {
      if (inputSource.targetRayMode === 'tracked-pointer') {
        this.controllerInputSources.push(inputSource);
        console.log('🥽 [VR INPUT] Controller connected:', inputSource.handedness);
      }
    });
    
    // Remove disconnected controllers
    event.removed.forEach(inputSource => {
      const index = this.controllerInputSources.indexOf(inputSource);
      if (index > -1) {
        this.controllerInputSources.splice(index, 1);
        console.log('🥽 [VR INPUT] Controller disconnected:', inputSource.handedness);
      }
    });
    
    this.updateControllers();
  }
  
  /**
   * Update controller references
   */
  updateControllers() {
    this.leftController = this.controllerInputSources.find(
      source => source.handedness === 'left'
    ) || null;
    
    this.rightController = this.controllerInputSources.find(
      source => source.handedness === 'right'
    ) || null;
    
    if (this.leftController) {
      console.log('🥽 [VR INPUT] Left controller active');
    }
    if (this.rightController) {
      console.log('🥽 [VR INPUT] Right controller active');
    }
  }
  
  /**
   * Update VR input state (called each frame)
   * @param {number} delta - Time delta (required by InputProvider interface)
   */
  update(delta) {
    if (!this.enabled || !this.xrSession) return;
    
    // Update controller input sources from current session
    const inputSources = this.xrSession.inputSources || [];
    if (inputSources.length !== this.controllerInputSources.length) {
      this.controllerInputSources = inputSources;
      this.updateControllers();
    }
    
    // Update controller button and thumbstick states
    this.controllerInputSources.forEach(inputSource => {
      const gamepad = inputSource.gamepad;
      if (!gamepad) return;
      
      const handedness = inputSource.handedness || 'unknown';
      
      // Initialize state objects if needed
      if (!this.thumbstickState[handedness]) {
        this.thumbstickState[handedness] = { x: 0, y: 0 };
      }
      if (!this.buttonStates[handedness]) {
        this.buttonStates[handedness] = {};
      }
      
      // Get thumbstick input (usually axes 2 and 3)
      if (gamepad.axes && gamepad.axes.length >= 4) {
        this.thumbstickState[handedness] = {
          x: gamepad.axes[2] || 0,
          y: gamepad.axes[3] || 0
        };
      }
      
      // Get button states
      if (gamepad.buttons) {
        gamepad.buttons.forEach((button, index) => {
          this.buttonStates[handedness][index] = button.pressed;
        });
      }
    });
  }
  
  /**
   * Initialize VR input provider (required by InputProvider interface)
   */
  initialize() {
    this.setupControllerHandlers();
    console.log('✅ [VR INPUT] VR input provider initialized');
  }
  
  /**
   * Dispose VR input provider (required by InputProvider interface)
   */
  dispose() {
    this.disable();
    console.log('🗑️ [VR INPUT] VR input provider disposed');
  }
  
  /**
   * Get movement state from VR controllers
   * Left controller thumbstick = movement
   * 
   * ✅ FIXED FOR QUEST 3 (January 18, 2026):
   * - Corrected Y-axis direction (negative = forward)
   * - Increased deadzone to 0.15
   * - Added sprint (thumbstick click)
   * - Added jump (X/A buttons)
   */
  getMovementState() {
    if (!this.enabled) return null;
    
    const leftStick = this.thumbstickState.left || { x: 0, y: 0 };
    
    // ✅ Meta Quest 3 Controller Mapping (2026):
    // Left Thumbstick:
    //   Y: -1.0 (forward/push up) to +1.0 (backward/pull down)
    //   X: -1.0 (left) to +1.0 (right)
    
    const deadzone = 0.15; // ✅ Increased deadzone for Quest 3 (was 0.1)
    
    // Apply deadzone
    const x = Math.abs(leftStick.x) > deadzone ? leftStick.x : 0;
    const y = Math.abs(leftStick.y) > deadzone ? leftStick.y : 0;
    
    // ✅ Log thumbstick values occasionally for debugging
    if (Math.abs(x) > deadzone || Math.abs(y) > deadzone) {
      if (!window.vrThumbstickLogCounter) window.vrThumbstickLogCounter = 0;
      window.vrThumbstickLogCounter++;
      if (window.vrThumbstickLogCounter % 60 === 0) { // Every 60 frames
        console.log(`🕹️ [VR INPUT] Left stick: x=${x.toFixed(2)}, y=${y.toFixed(2)}`);
      }
    }
    
    return {
      forward: y < -deadzone ? Math.abs(y) : 0,  // ✅ Negative Y = forward (push up)
      backward: y > deadzone ? y : 0,            // ✅ Positive Y = backward (pull down)
      left: x < -deadzone ? Math.abs(x) : 0,     // ✅ Negative X = left
      right: x > deadzone ? x : 0,               // ✅ Positive X = right
      sprint: this.getButtonState('thumbstick', 'left'), // ✅ Click left stick to sprint
      jump: this.getButtonState('x', 'left') || this.getButtonState('a', 'right'), // ✅ X or A button to jump
      flyUp: false,   // VR uses jump instead of fly
      flyDown: false  // VR uses jump instead of fly
    };
  }
  
  /**
   * Get rotation input from right thumbstick
   * Used for snap-turn or smooth-turn in VR
   * 
   * ✅ NEW METHOD (January 18, 2026)
   * 
   * @returns {Object} Rotation input { x: horizontal, y: vertical }
   */
  getRotationInput() {
    if (!this.enabled) return { x: 0, y: 0 };
    
    const rightStick = this.thumbstickState.right || { x: 0, y: 0 };
    const deadzone = 0.3; // ✅ Higher deadzone for rotation (prevents accidental turns)
    
    const x = Math.abs(rightStick.x) > deadzone ? rightStick.x : 0;
    const y = Math.abs(rightStick.y) > deadzone ? rightStick.y : 0;
    
    // ✅ Log rotation input occasionally for debugging
    if (Math.abs(x) > deadzone || Math.abs(y) > deadzone) {
      if (!window.vrRotationLogCounter) window.vrRotationLogCounter = 0;
      window.vrRotationLogCounter++;
      if (window.vrRotationLogCounter % 60 === 0) { // Every 60 frames
        console.log(`🔄 [VR INPUT] Right stick: x=${x.toFixed(2)}, y=${y.toFixed(2)}`);
      }
    }
    
    return { x, y };
  }
  
  /**
   * Get rotation from VR headset (for camera)
   * Returns head pose rotation
   */
  getRotation() {
    if (!this.enabled || !this.xrSession) {
      return { x: 0, y: 0, z: 0 };
    }
    
    // Rotation comes from headset pose, not controllers
    // This is handled separately in the camera update
    return { x: 0, y: 0, z: 0 };
  }
  
  /**
   * Get shoot state from VR controller trigger
   * Used for weapon shooting in VR mode (Levels 4-6)
   * ✅ NEW METHOD (January 20, 2026)
   * 
   * @returns {boolean} True if trigger is pressed on either controller
   */
  getShootState() {
    if (!this.enabled) return false;
    
    // Check trigger on both controllers (either can shoot)
    const leftTrigger = this.getButtonState('trigger', 'left');
    const rightTrigger = this.getButtonState('trigger', 'right');
    
    return leftTrigger || rightTrigger;
  }
  
  /**
   * Get button state for VR controller
   * @param {string} buttonId - Button identifier (e.g., 'trigger', 'grip', 'thumbstick')
   * @param {string} handedness - 'left' or 'right'
   */
  getButtonState(buttonId, handedness = 'right') {
    if (!this.enabled) return false;
    
    const buttonStates = this.buttonStates[handedness] || {};
    
    // Map button names to indices (standard WebXR gamepad mapping)
    const buttonMap = {
      'trigger': 0,      // Trigger button
      'grip': 1,         // Grip button
      'thumbstick': 2,   // Thumbstick press
      'x': 3,            // X button (left) / A button (right)
      'y': 4,            // Y button (left) / B button (right)
      'a': 3,            // A button (right controller) - same as X
      'b': 4,            // B button (right controller) - same as Y
    };
    
    const index = buttonMap[buttonId];
    if (index !== undefined && buttonStates[index] !== undefined) {
      // ✅ Log button presses for debugging (January 20, 2026)
      if (buttonStates[index] && (!window.lastButtonLog || window.lastButtonLog !== `${buttonId}_${handedness}`)) {
        console.log(`🎮 [VR INPUT] Button pressed: ${buttonId} (${handedness}) - Index: ${index}`);
        window.lastButtonLog = `${buttonId}_${handedness}`;
        setTimeout(() => { window.lastButtonLog = null; }, 500); // Reset after 500ms
      }
      return buttonStates[index];
    }
    
    return false;
  }
  
  /**
   * Get controller position and rotation
   * @param {string} handedness - 'left' or 'right'
   */
  getControllerPose(frame, handedness) {
    if (!frame || !handedness) return null;
    
    const controller = handedness === 'left' ? this.leftController : this.rightController;
    if (!controller) return null;
    
    const inputPose = frame.getPose(controller.targetRaySpace, frame.session.requestAnimationFrame);
    if (!inputPose) return null;
    
    return {
      position: inputPose.transform.position,
      rotation: inputPose.transform.orientation,
      matrix: inputPose.transform.matrix
    };
  }
}

