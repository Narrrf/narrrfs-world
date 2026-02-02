/**
 * ============================================================================
 * VR INPUT PROVIDER - VR Controller Input Handling
 * ============================================================================
 *
 * Version: 2026-02-01-HEADER-REFRESH
 * Lines: ~420
 * Used by: main.js (vrInputProvider) – Meta Quest 2/3
 *
 * ============================================================================
 * 🤖 AI & HUMAN NAVIGATION – QUICK FIND
 * ============================================================================
 *
 *   update(delta)            ~100  Per-frame controller state
 *   getMovementState()      ~150  Left stick → forward, left, sprint, jump
 *   getButtonState(name,h)  ~200  Grip, trigger for interact/shoot
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
 *
 * Designed for WebXR (Meta Quest 2/3).
 */
export class VRInputProvider extends InputProvider {
  constructor(xrSession) {
    super();
    this.type = 'vr';
    this.xrSession = xrSession;
    this.enabled = false;

    // VR controller references from inputSources
    this.leftController = null;
    this.rightController = null;
    this.controllerInputSources = [];

    // Thumbstick state per hand
    this.thumbstickState = {
      left:  { x: 0, y: 0 },
      right: { x: 0, y: 0 },
      unknown: { x: 0, y: 0 }
    };

    // Raw button state per hand (indexed by button index)
    this.buttonStates = {
      left:   {},
      right:  {},
      unknown:{}
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
        console.warn('⚠️ [VR INPUT] XR isAvailable() failed:', err);
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

    // Listen for controller add/remove
    this.xrSession.addEventListener('inputsourceschange', (event) => {
      this.handleInputSourcesChange(event);
    });

    // Initialize existing input sources
    const initialSources = this.xrSession.inputSources || [];
    this.controllerInputSources = Array.from(initialSources);
    this.updateControllers();
  }

  /**
   * Handle input sources change (controllers connected/disconnected)
   */
  handleInputSourcesChange(event) {
    // Add new controllers
    event.added.forEach((inputSource) => {
      if (inputSource.targetRayMode === 'tracked-pointer') {
        this.controllerInputSources.push(inputSource);
        console.log('🥽 [VR INPUT] Controller connected:', inputSource.handedness);
      }
    });

    // Remove disconnected controllers
    event.removed.forEach((inputSource) => {
      const index = this.controllerInputSources.indexOf(inputSource);
      if (index > -1) {
        this.controllerInputSources.splice(index, 1);
        console.log('🥽 [VR INPUT] Controller disconnected:', inputSource.handedness);
      }
    });

    this.updateControllers();
  }

  /**
   * Update controller references (left/right)
   */
  updateControllers() {
    this.leftController =
      this.controllerInputSources.find((source) => source.handedness === 'left') || null;

    this.rightController =
      this.controllerInputSources.find((source) => source.handedness === 'right') || null;

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

    // Refresh input sources from current session
    const inputSources = this.xrSession.inputSources || [];
    const newSources = Array.from(inputSources);

    // Keep internal list in sync
    this.controllerInputSources = newSources;
    this.updateControllers();

    // Update controller button and thumbstick states
    this.controllerInputSources.forEach((inputSource) => {
      const gamepad = inputSource.gamepad;
      if (!gamepad) return;

      const handedness = inputSource.handedness || 'unknown';

      // Ensure state containers exist
      if (!this.thumbstickState[handedness]) {
        this.thumbstickState[handedness] = { x: 0, y: 0 };
      }
      if (!this.buttonStates[handedness]) {
        this.buttonStates[handedness] = {};
      }

      // 🔄 Read thumbstick axes (Quest-safe mapping)
      // Default: axes[0], axes[1], with fallback to axes[2], axes[3]
      let x = 0;
      let y = 0;

      const axes = gamepad.axes || [];
      if (axes.length >= 2) {
        x = axes[0] || 0;
        y = axes[1] || 0;

        // Fallback for runtimes that put stick on axes 2/3
        if (Math.abs(x) < 0.01 && Math.abs(y) < 0.01 && axes.length >= 4) {
          const altX = axes[2] || 0;
          const altY = axes[3] || 0;
          if (Math.abs(altX) > 0.01 || Math.abs(altY) > 0.01) {
            x = altX;
            y = altY;
          }
        }
      }

      this.thumbstickState[handedness] = { x, y };

      // Get button states (store per index)
      if (gamepad.buttons) {
        gamepad.buttons.forEach((button, index) => {
          this.buttonStates[handedness][index] = !!button.pressed;
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

    // ✅ Meta Quest Controller Mapping:
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
      if (window.vrThumbstickLogCounter % 60 === 0) {
        console.log(`🕹️ [VR INPUT] Left stick: x=${x.toFixed(2)}, y=${y.toFixed(2)}`);
      }
    }

    return {
      forward:  y < -deadzone ? Math.abs(y) : 0, // Negative Y = forward
      backward: y >  deadzone ? y : 0,           // Positive Y = backward
      left:     x < -deadzone ? Math.abs(x) : 0, // Negative X = left
      right:    x >  deadzone ? x : 0,           // Positive X = right
      sprint:   this.getButtonState('thumbstick', 'left'), // Left stick click = sprint
      jump:
        this.getButtonState('x', 'left') ||
        this.getButtonState('a', 'right'), // X or A button to jump
      flyUp:   false,
      flyDown: false
    };
  }

  /**
   * Get rotation input from right thumbstick
   * Used for snap-turn or smooth-turn in VR
   *
   * @returns {Object} Rotation input { x: horizontal, y: vertical }
   */
  getRotationInput() {
    if (!this.enabled) return { x: 0, y: 0 };

    const rightStick = this.thumbstickState.right || { x: 0, y: 0 };
    const deadzone = 0.3; // Higher deadzone for rotation (prevents accidental turns)

    const x = Math.abs(rightStick.x) > deadzone ? rightStick.x : 0;
    const y = Math.abs(rightStick.y) > deadzone ? rightStick.y : 0;

    // ✅ Log rotation input occasionally for debugging
    if (Math.abs(x) > deadzone || Math.abs(y) > deadzone) {
      if (!window.vrRotationLogCounter) window.vrRotationLogCounter = 0;
      window.vrRotationLogCounter++;
      if (window.vrRotationLogCounter % 60 === 0) {
        console.log(`🔄 [VR INPUT] Right stick: x=${x.toFixed(2)}, y=${y.toFixed(2)}`);
      }
    }

    return { x, y };
  }

  /**
   * Headset rotation is handled externally (in main camera update)
   */
  getRotation() {
    return { x: 0, y: 0, z: 0 };
  }

  /**
   * Get shoot state from VR controller trigger
   * Used for weapon shooting in VR mode (Levels 4-6)
   */
  getShootState() {
    if (!this.enabled) return false;

    // Check trigger on both controllers (either can shoot)
    const leftTrigger  = this.getButtonState('trigger', 'left');
    const rightTrigger = this.getButtonState('trigger', 'right');

    return leftTrigger || rightTrigger;
  }

  /**
   * OPTIONAL helper: get menu state (B/Y)
   * Returns true if the "menu" button is pressed on either controller.
   */
  getMenuState() {
    if (!this.enabled) return false;
    return (
      this.getButtonState('menu', 'left') ||
      this.getButtonState('menu', 'right')
    );
  }

  /**
   * Get button state for VR controller
   * @param {string} buttonId - Button identifier (e.g., 'trigger', 'grip', 'thumbstick', 'menu')
   * @param {string} handedness - 'left' or 'right'
   */
  getButtonState(buttonId, handedness = 'right') {
    if (!this.enabled) return false;

    const buttonStates = this.buttonStates[handedness] || {};

    // Map button names to indices (standard-ish WebXR gamepad mapping)
    //
    // Typical Quest mapping:
    //  0: trigger
    //  1: grip (squeeze)
    //  2: thumbstick click
    //  3: primary (A on right, X on left)
    //  4: secondary (B on right, Y on left)
    //
    // We map "menu" → secondary (B/Y) so we can use it as a pause/menu button.
    const buttonMap = {
      trigger:    0,     // Trigger button
      grip:       1,     // Grip button
      thumbstick: 2,     // Thumbstick press
      x:          3,     // X button (left) / A button (right)
      y:          4,     // Y button (left) / B button (right)
      a:          3,     // A button (right controller) - same index as X
      b:          4,     // B button (right controller) - same index as Y
      menu:       4      // 🆕 Use secondary (B/Y) as "menu" button
    };

    const index = buttonMap[buttonId];
    if (index !== undefined && buttonStates[index] !== undefined) {
      if (
        buttonStates[index] &&
        (!window.lastButtonLog || window.lastButtonLog !== `${buttonId}_${handedness}`)
      ) {
        console.log(
          `🎮 [VR INPUT] Button pressed: ${buttonId} (${handedness}) - Index: ${index}`
        );
        window.lastButtonLog = `${buttonId}_${handedness}`;
        setTimeout(() => {
          window.lastButtonLog = null;
        }, 500);
      }
      return buttonStates[index];
    }

    return false;
  }

  /**
   * Get controller position and rotation
   * @param {XRFrame} frame
   * @param {XRReferenceSpace} referenceSpace
   * @param {string} handedness - 'left' or 'right'
   */
  getControllerPose(frame, referenceSpace, handedness) {
    if (!frame || !referenceSpace || !handedness) return null;

    const controller =
      handedness === 'left' ? this.leftController : this.rightController;
    if (!controller || !controller.targetRaySpace) return null;

    const inputPose = frame.getPose(controller.targetRaySpace, referenceSpace);
    if (!inputPose) return null;

    return {
      position: inputPose.transform.position,
      rotation: inputPose.transform.orientation
    };
  }
}



