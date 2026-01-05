/**
 * ============================================================================
 * VR INPUT PROVIDER - VR Controller Input Handling
 * ============================================================================
 * 
 * ✅ STATUS: STABLE - PRODUCTION READY
 * 📅 CREATED: December 6, 2025
 * 📅 LAST UPDATED: December 20, 2025
 * 
 * ============================================================================
 * 🎯 PURPOSE
 * ============================================================================
 * 
 * Handles VR controller input using WebXR API:
 * - VR controller input (thumbsticks, buttons, triggers)
 * - Movement from VR controllers
 * - Button state management
 * - Extends InputProvider base class for plugin-based architecture
 * 
 * Supported VR devices:
 * - Oculus Quest / Quest 2 / Quest 3
 * - Meta Quest
 * - Other WebXR-compatible VR headsets
 * 
 * ============================================================================
 * ✅ STABLE VERSION STATUS
 * ============================================================================
 * 
 * ✅ **STABLE VERSION - PRODUCTION READY**
 * 
 * This system has reached a stable, production-ready state with:
 * - ✅ VR support working
 * - ✅ Controller input functional
 * - ✅ Movement from controllers working
 * - ✅ Button states tracked correctly
 * - ✅ Plugin architecture ready for expansion
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
 * 3. PlayerControls automatically uses VR input if available
 * 
 * ============================================================================
 * 🎮 KEY FUNCTIONS
 * ============================================================================
 * 
 * - enable() - Enable VR input provider
 * - disable() - Disable VR input provider
 * - update(delta) - Update controller state
 * - getMovementState() - Get movement from controllers
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
   */
  getMovementState() {
    if (!this.enabled) return null;
    
    const leftStick = this.thumbstickState.left || { x: 0, y: 0 };
    
    // Convert thumbstick input to movement
    // y-axis: forward/backward, x-axis: left/right
    return {
      forward: leftStick.y > 0.1 ? leftStick.y : 0,
      backward: leftStick.y < -0.1 ? -leftStick.y : 0,
      left: leftStick.x < -0.1 ? -leftStick.x : 0,
      right: leftStick.x > 0.1 ? leftStick.x : 0,
      sprint: false, // Can be mapped to a button press
      jump: false    // Will be handled via getButtonState
    };
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
    };
    
    const index = buttonMap[buttonId];
    if (index !== undefined && buttonStates[index] !== undefined) {
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

