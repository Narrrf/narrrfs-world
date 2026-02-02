/**
 * ============================================================================
 * VR UI RAYCASTER - VR Controller UI Interaction
 * ============================================================================
 *
 * Version: 2026-02-01-HEADER-REFRESH
 * Lines: ~315
 * Used by: main.js (VRUIRaycaster) – Menu interaction in VR
 *
 * ============================================================================
 * 🤖 AI & HUMAN NAVIGATION – QUICK FIND
 * ============================================================================
 *
 *   initialize(xrSession)    ~70   Setup controller rays
 *   update()                 ~120  Raycast to DOM, click/hover
 *
 * ============================================================================
 * 🎯 PURPOSE
 * ============================================================================
 * 
 * Enables VR controller ray-casting for UI menu interaction:
 * - Ray-cast from VR controllers to UI elements
 * - Visual ray pointer showing where controller is pointing
 * - Trigger button to click UI elements
 * - Hover effects on buttons when pointed at
 * - Works in both VR mode and VR browser mode
 * 
 * ============================================================================
 * 🎮 FEATURES
 * ============================================================================
 * 
 * - Visual ray line from controller
 * - Raycasting against HTML elements
 * - Trigger button click detection
 * - Hover state management
 * - Click event simulation
 * - Works with all menu buttons
 * 
 * ============================================================================
 */

import * as THREE from 'three';

export class VRUIRaycaster {
  constructor(scene, camera, renderer) {
    this.scene = scene;
    this.camera = camera;
    this.renderer = renderer;
    this.enabled = false;
    
    // VR controllers
    this.controller1 = null;
    this.controller2 = null;
    
    // Ray visualization
    this.rayLine1 = null;
    this.rayLine2 = null;
    
    // Raycasting
    this.raycaster = new THREE.Raycaster();
    this.tempMatrix = new THREE.Matrix4();
    
    // UI interaction state
    this.hoveredElement = null;
    this.clickableElements = [];
    
    // Controller state
    this.controller1TriggerPressed = false;
    this.controller2TriggerPressed = false;
    
    console.log('✅ [VR UI] VRUIRaycaster created');
  }
  
  /**
   * Initialize VR UI raycaster with XR session
   */
  initialize(xrSession) {
    if (!xrSession) {
      console.error('❌ [VR UI] Cannot initialize: No XR session');
      return false;
    }
    
    this.xrSession = xrSession;
    
    // Create controller objects
    this.controller1 = this.renderer.xr.getController(0);
    this.controller2 = this.renderer.xr.getController(1);
    
    // Create ray line visualization
    this.createRayLine();
    
    // Add controllers to scene
    if (this.controller1) this.scene.add(this.controller1);
    if (this.controller2) this.scene.add(this.controller2);
    
    // Add ray lines to controllers
    if (this.controller1 && this.rayLine1) this.controller1.add(this.rayLine1);
    if (this.controller2 && this.rayLine2) this.controller2.add(this.rayLine2);
    
    this.enabled = true;
    console.log('✅ [VR UI] Raycaster initialized with controllers');
    return true;
  }
  
  /**
   * Create visual ray line for controllers
   */
  createRayLine() {
    const geometry = new THREE.BufferGeometry();
    const positions = new Float32Array([0, 0, 0, 0, 0, -5]); // Ray length: 5 units
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    
    const material = new THREE.LineBasicMaterial({
      color: 0x00ffff, // Cyan ray
      linewidth: 2,
      transparent: true,
      opacity: 0.8
    });
    
    this.rayLine1 = new THREE.Line(geometry.clone(), material.clone());
    this.rayLine2 = new THREE.Line(geometry.clone(), material.clone());
    
    // Initially hidden
    this.rayLine1.visible = false;
    this.rayLine2.visible = false;
  }
  
  /**
   * Update VR UI raycaster (call every frame)
   */
update(xrFrame) {
  if (!this.enabled || !xrFrame || !this.xrSession) return;

  const controllers = [this.controller1, this.controller2];

  controllers.forEach((controller, idx) => {
    if (!controller) return;

    const handedness = controller.userData.handedness || 'unknown';
    const gamepad    = controller.gamepad || (controller.userData && controller.userData.gamepad);

    if (!gamepad) return;

    let rayLine, triggerStateProp;

    if (handedness === 'left') {
      rayLine          = this.rayLine1;
      triggerStateProp = 'controller1TriggerPressed';
    } else if (handedness === 'right') {
      rayLine          = this.rayLine2;
      triggerStateProp = 'controller2TriggerPressed';
    } else {
      return; // ignore unknown/gaze
    }

    // Trigger = button 0 on Quest
    const triggerButton  = gamepad.buttons[0];
    const triggerPressed = !!(triggerButton && triggerButton.pressed);

    const wasTriggerPressed = this[triggerStateProp] || false;
    this[triggerStateProp]  = triggerPressed;

    const shouldShowRay = this.isPointingAtUI();

    if (rayLine) {
      rayLine.visible = shouldShowRay;

      if (triggerPressed) {
        rayLine.material.color.setHex(0xff00ff);
        rayLine.material.opacity = 1.0;
      } else {
        rayLine.material.color.setHex(0x00ffff);
        rayLine.material.opacity = 0.8;
      }
    }

    // UI hit-test using this controller
    this.raycastUI(controller, triggerPressed, wasTriggerPressed);
  });
}


  /**
   * Check if controller is pointing at UI elements
   */
  isPointingAtUI() {
    // Check if any menu is visible
    const optionsMenu = document.getElementById('optionsMenu') || document.querySelector('[style*="z-index: 100000"]');
    const pauseMenu = document.querySelector('[style*="Cheese Temple Paused"]');
    const mainMenu = document.querySelector('.main-menu'); // ✅ CRITICAL FIX: Check main menu (January 20, 2026)
    
    return (optionsMenu && optionsMenu.style.display !== 'none') ||
           (pauseMenu && pauseMenu.style.display !== 'none') ||
           (mainMenu && mainMenu.style.display !== 'none'); // ✅ Include main menu
  }
  
  /**
   * Raycast from controller to UI elements
   */
  raycastUI(controller, triggerPressed, wasTriggerPressed) {
    if (!controller) return;
    
    // Get controller world position and direction
    this.tempMatrix.identity().extractRotation(controller.matrixWorld);
    
    const rayOrigin = new THREE.Vector3();
    const rayDirection = new THREE.Vector3(0, 0, -1);
    
    rayOrigin.setFromMatrixPosition(controller.matrixWorld);
    rayDirection.applyMatrix4(this.tempMatrix);
    
    // Project ray to screen coordinates
    const screenPos = this.projectRayToScreen(rayOrigin, rayDirection);
    
    if (screenPos) {
      // Find UI element at screen position
      const element = document.elementFromPoint(screenPos.x, screenPos.y);
      
      // Handle hover state
      if (element && (element.tagName === 'BUTTON' || element.classList.contains('clickable'))) {
        if (this.hoveredElement !== element) {
          // Remove hover from previous element
          if (this.hoveredElement) {
            this.hoveredElement.style.filter = '';
            this.hoveredElement.style.transform = '';
          }
          
          // Add hover to new element
          this.hoveredElement = element;
          element.style.filter = 'brightness(1.3) drop-shadow(0 0 8px rgba(255, 224, 102, 0.8))';
          element.style.transform = 'scale(1.05)';
          
          console.log('🎯 [VR UI] Hovering:', element.textContent || element.id);
        }
        
        // Handle click (trigger pressed and released)
        if (!triggerPressed && wasTriggerPressed) {
          console.log('🖱️ [VR UI] Clicking:', element.textContent || element.id);
          element.click(); // Simulate click
          
          // Visual feedback
          element.style.transform = 'scale(0.95)';
          setTimeout(() => {
            if (element === this.hoveredElement) {
              element.style.transform = 'scale(1.05)';
            }
          }, 100);
        }
      } else {
        // Not hovering over clickable element
        if (this.hoveredElement) {
          this.hoveredElement.style.filter = '';
          this.hoveredElement.style.transform = '';
          this.hoveredElement = null;
        }
      }
    }
  }
  
  /**
   * Project 3D ray to screen coordinates
   */
  projectRayToScreen(rayOrigin, rayDirection) {
    // Create a point along the ray at distance 2 units (typical UI distance)
    const rayPoint = rayOrigin.clone().add(rayDirection.multiplyScalar(2));
    
    // Project to screen space
    const projected = rayPoint.project(this.camera);
    
    // Convert to screen coordinates
    const screenX = (projected.x + 1) / 2 * window.innerWidth;
    const screenY = (-projected.y + 1) / 2 * window.innerHeight;
    
    // Check if point is on screen
    if (screenX >= 0 && screenX <= window.innerWidth && 
        screenY >= 0 && screenY <= window.innerHeight) {
      return { x: screenX, y: screenY };
    }
    
    return null;
  }
  
  /**
   * Enable VR UI raycaster
   */
  enable() {
    this.enabled = true;
    if (this.rayLine1) this.rayLine1.visible = true;
    if (this.rayLine2) this.rayLine2.visible = true;
    console.log('✅ [VR UI] Raycaster enabled');
  }
  
  /**
   * Disable VR UI raycaster
   */
  disable() {
    this.enabled = false;
    if (this.rayLine1) this.rayLine1.visible = false;
    if (this.rayLine2) this.rayLine2.visible = false;
    
    // Clear hover state
    if (this.hoveredElement) {
      this.hoveredElement.style.filter = '';
      this.hoveredElement.style.transform = '';
      this.hoveredElement = null;
    }
    
    console.log('🖥️ [VR UI] Raycaster disabled');
  }
  
  /**
   * Dispose VR UI raycaster
   */
  dispose() {
    this.disable();
    
    // Remove controllers from scene
    if (this.controller1) {
      this.scene.remove(this.controller1);
      this.controller1 = null;
    }
    if (this.controller2) {
      this.scene.remove(this.controller2);
      this.controller2 = null;
    }
    
    // Dispose ray lines
    if (this.rayLine1) {
      this.rayLine1.geometry.dispose();
      this.rayLine1.material.dispose();
      this.rayLine1 = null;
    }
    if (this.rayLine2) {
      this.rayLine2.geometry.dispose();
      this.rayLine2.material.dispose();
      this.rayLine2 = null;
    }
    
    console.log('🗑️ [VR UI] Raycaster disposed');
  }
}
