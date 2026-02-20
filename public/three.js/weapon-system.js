/**
 * ============================================================================
 * WEAPON SYSTEM - Weapon Loading, Shooting, and Inventory
 * ============================================================================
 *
 * Version: 2026-02-01-HEADER-REFRESH
 * Lines: ~1,960
 * Used by: main.js (weaponSystem) – Levels 4, 5, 6
 *
 * ============================================================================
 * 🤖 AI & HUMAN NAVIGATION – QUICK FIND
 * ============================================================================
 *
 *   loadWeapon(path, slot)  ~100  Load FBX weapon into slot
 *   switchWeapon(slot)      ~200  Switch active weapon
 *   fire()                  ~300  Raycast shoot
 *   update(delta)           ~400  Animation, heat, bobbing
 *   processWeaponMaterial   ~500  Dark material brightening (main.js)
 *
 * ============================================================================
 * 🎯 PURPOSE
 * ============================================================================
 * 
 * Modular weapon loading, switching, shooting, and inventory management:
 * - Weapon model loading (FBX/GLTF viewmodels)
 * - Weapon slot management (1-9 keys)
 * - Weapon switching logic
 * - Shooting mechanics (raycasting, projectiles)
 * - Heat/overheat system
 * - Triple shot system (SF13)
 * - Weapon audio (fire sounds, reload sounds)
 * - Weapon transforms and positioning
 * 
 * Built for decades of development with extensible architecture.
 * Device-agnostic (VR, Android, PC, etc.) - works on all devices.
 * 
 * ============================================================================
 * ✅ STABLE VERSION STATUS
 * ============================================================================
 * 
 * ✅ **STABLE VERSION - PRODUCTION READY**
 * 
 * This system has reached a stable, production-ready state with:
 * - ✅ Working in Levels 4-6
 * - ✅ Weapon switching functional
 * - ✅ Shooting mechanics working
 * - ✅ Animations smooth
 * - ✅ Heat system functional
 * - ✅ Triple shot system working
 * 
 * ============================================================================
 * 📦 WHAT THIS MODULE LOADS
 * ============================================================================
 * 
 * Weapon Models:
 * - FBX format viewmodels
 * - Path: /textures/3d models/Fire Weapons 1/FBX/
 * - Weapons: Assault Rifles, Pistols, etc.
 * 
 * Audio:
 * - Fire sounds: /sounds/invaders/weapons/normal_shoot.wav
 * - Reload sounds (if implemented)
 * 
 * Dependencies:
 * - THREE.js Scene
 * - THREE.js Camera
 * - FBXLoader (for weapon models)
 * - AudioSystem (for weapon sounds)
 * 
 * ============================================================================
 * 🔧 INTEGRATION IN MAIN.JS
 * ============================================================================
 * 
 * 1. Import:
 *    import { WeaponSystem } from "./weapon-system.js";
 * 
 * 2. Initialize:
 *    weaponSystem = new WeaponSystem({
 *      scene: scene,
 *      camera: camera,
 *      getCurrentLevel: () => currentLevel,
 *      // ... other config
 *    });
 * 
 * 3. Load weapon for level:
 *    await weaponSystem.loadWeapon(weaponPath, slotNumber);
 * 
 * 4. Update in game loop:
 *    if (weaponSystem) {
 *      weaponSystem.update(delta);
 *    }
 * 
 * ============================================================================
 * 🎮 KEY FUNCTIONS
 * ============================================================================
 * 
 * - loadWeapon(path, slot) - Load weapon into slot
 * - switchWeapon(slot) - Switch to weapon slot
 * - fire() - Fire weapon
 * - update(delta) - Update weapon (animation, heat, etc.)
 * - getCurrentSlot() - Get current weapon slot
 * 
 * ============================================================================
 * 
 * @module WeaponSystem
 * 
 * ============================================================================
 * 🎯 SETTING UP WEAPONS FOR A NEW LEVEL - COMPLETE GUIDE
 * ============================================================================
 * 
 * This guide explains how to properly integrate the weapon system into a new level.
 * Follow these steps exactly to ensure weapons load, shoot, and switch correctly.
 * 
 * STEP 1: UPDATE LEVEL CONSTANTS
 * -------------------------------
 * In main.js, add your new level to LEVEL_IDS:
 * 
 *   const LEVEL_IDS = {
 *     LEVEL1: "LEVEL1",
 *     LEVEL2: "LEVEL2",
 *     // ... existing levels ...
 *     LEVEL6: "LEVEL6",  // Your new level
 *     LEVEL7: "LEVEL7"   // Example new level
 *   };
 * 
 * STEP 2: UPDATE WEAPON SYSTEM LOGIC
 * -----------------------------------
 * In weapon-system.js, update these methods to include your new level:
 * 
 *   _canShoot() {
 *     // Add your level to the check:
 *     if (currentLevel !== "LEVEL4" && currentLevel !== "LEVEL5" && 
 *         currentLevel !== "LEVEL6" && currentLevel !== "LEVEL7") {
 *       return false;
 *     }
 *     // ... rest of method
 *   }
 * 
 *   _canSwitchWeapon() {
 *     // Add your level to the check:
 *     if (currentLevel !== "LEVEL4" && currentLevel !== "LEVEL5" && 
 *         currentLevel !== "LEVEL6" && currentLevel !== "LEVEL7") {
 *       return false;
 *     }
 *     // ... rest of method
 *   }
 * 
 *   fire() {
 *     // Add your level to the fallback check:
 *     if (currentLevel === "LEVEL4" || currentLevel === "LEVEL5" || 
 *         currentLevel === "LEVEL6" || currentLevel === "LEVEL7") {
 *       this.loadWeapon(this.currentSlot).catch(...);
 *     }
 *     // ... rest of method
 *   }
 * 
 *   _fireSingleShot() {
 *     // Add your level to Phoenix boss hit detection (if applicable):
 *     if ((currentLevel === "LEVEL5" || currentLevel === "LEVEL6" || 
 *          currentLevel === "LEVEL7") && phoenixBossInstance) {
 *       // ... Phoenix hit detection
 *     }
 *     // ... rest of method
 *   }
 * 
 * STEP 3: UPDATE INPUT HANDLERS IN main.js
 * -----------------------------------------
 * CRITICAL: This is the most common mistake! You MUST update input handlers.
 * 
 * A) Mouse Click Handler (for shooting):
 *    Location: Around line 3462 in main.js
 *    
 *    Find this code:
 *      const isLevel4WithActiveStep = currentLevel === LEVEL_IDS.LEVEL4 && ...;
 *      const isLevel5 = currentLevel === LEVEL_IDS.LEVEL5;
 *      const canShoot = (isLevel4WithActiveStep || isLevel5) && ...;
 *    
 *    Update to:
 *      const isLevel4WithActiveStep = currentLevel === LEVEL_IDS.LEVEL4 && ...;
 *      const isLevel5 = currentLevel === LEVEL_IDS.LEVEL5;
 *      const isLevel6 = currentLevel === LEVEL_IDS.LEVEL6;  // ADD THIS
 *      const isLevel7 = currentLevel === LEVEL_IDS.LEVEL7;  // ADD THIS
 *      const canShoot = (isLevel4WithActiveStep || isLevel5 || 
 *                        isLevel6 || isLevel7) && ...;  // UPDATE THIS
 * 
 * B) Keyboard Handlers (for weapon switching):
 *    Location: Around line 20406 in main.js (switch statement for keydown)
 *    
 *    For EACH weapon slot key (Digit1-9, Numpad1-9):
 *    
 *    Find this code:
 *      case "Digit1":
 *      case "Numpad1":
 *        if ((currentLevel === LEVEL_IDS.LEVEL4 || 
 *             currentLevel === LEVEL_IDS.LEVEL5) && !event.repeat) {
 *    
 *    Update to:
 *      case "Digit1":
 *      case "Numpad1":
 *        if ((currentLevel === LEVEL_IDS.LEVEL4 || 
 *             currentLevel === LEVEL_IDS.LEVEL5 ||
 *             currentLevel === LEVEL_IDS.LEVEL6 ||  // ADD THIS
 *             currentLevel === LEVEL_IDS.LEVEL7) && !event.repeat) {  // ADD THIS
 *    
 *    Repeat for ALL keys: Digit2-9 and Numpad2-9
 * 
 * STEP 4: INITIALIZE WEAPONS IN YOUR LEVEL'S WARP FUNCTION
 * ---------------------------------------------------------
 * In your level's warp function (e.g., warpToLevel7()), add this code:
 * 
 *   async function warpToLevel7() {
 *     console.log("🚀 [LEVEL 7] Warping to Your Level...");
 *     
 *     // ... level cleanup and setup ...
 *     
 *     // CRITICAL: Set current level BEFORE weapon initialization
 *     currentLevel = LEVEL_IDS.LEVEL7;
 *     console.log("🎯 [LEVEL 7] Current level set to:", currentLevel);
 *     
 *     // CRITICAL: Verify weapon system can see the current level
 *     if (weaponSystem && typeof weaponSystem.getCurrentLevel === 'function') {
 *       const weaponSystemLevel = weaponSystem.getCurrentLevel();
 *       console.log("🔍 [LEVEL 7] Weapon system sees level as:", weaponSystemLevel);
 *       if (weaponSystemLevel !== LEVEL_IDS.LEVEL7) {
 *         console.warn("⚠️ [LEVEL 7] Weapon system level mismatch!");
 *       }
 *     }
 *     
 *     // ... environment setup, player positioning ...
 *     
 *     // CRITICAL: Set camera to first-person view for weapon system
 *     setCameraMode(0); // 0 = first-person
 *     await new Promise(resolve => setTimeout(resolve, 50));
 *     
 *     // CRITICAL: Load weapon system at level start
 *     if (weaponSystem && typeof weaponSystem.loadWeapon === 'function') {
 *       console.log("🔫 [LEVEL 7] Initializing weapon system...");
 *       
 *       // Load slot 1 (active weapon)
 *       console.log("🔫 [LEVEL 7] Loading weapon slot 1 (active)...");
 *       try {
 *         const loadedWeapon = await weaponSystem.loadWeapon(1, null, false);
 *         console.log("✅ [LEVEL 7] Weapon slot 1 loaded and active");
 *         
 *         // Verify weapon is attached and visible
 *         if (loadedWeapon && weaponSystem.weaponViewmodel) {
 *           if (!camera.children.includes(weaponSystem.weaponViewmodel)) {
 *             console.warn("⚠️ [LEVEL 7] Weapon loaded but not attached! Re-attaching...");
 *             camera.add(weaponSystem.weaponViewmodel);
 *           }
 *           weaponSystem.weaponViewmodel.visible = true;
 *           weaponSystem.weaponViewmodel.traverse((child) => {
 *             if (child.isMesh) {
 *               child.visible = true;
 *             }
 *           });
 *           console.log("✅ [LEVEL 7] Weapon slot 1 verified and visible");
 *         }
 *         
 *         // Preload slot 2 (for fast switching)
 *         console.log("🔫 [LEVEL 7] Preloading weapon slot 2...");
 *         weaponSystem.loadWeapon(2, null, true).then((weapon) => {
 *           if (weapon) {
 *             console.log("✅ [LEVEL 7] Weapon slot 2 preloaded successfully");
 *           }
 *         }).catch(err => {
 *           console.error("❌ [LEVEL 7] Failed to preload weapon slot 2:", err);
 *         });
 *         console.log("✅ [LEVEL 7] Both weapon slots loading initiated");
 *       } catch (err) {
 *         console.error("❌ [LEVEL 7] Failed to load weapon slot 1:", err);
 *       }
 *     }
 *     
 *     // CRITICAL: Request pointer lock for shooting
 *     if (playerControls && !playerControls.getPointerLockControls().isLocked && 
 *         !isJoystickView() && !isGamePaused) {
 *       try {
 *         playerControls.getPointerLockControls().lock();
 *         console.log("🎯 [LEVEL 7] Pointer lock automatically requested");
 *       } catch (err) {
 *         console.warn("⚠️ [LEVEL 7] Failed to request pointer lock:", err);
 *       }
 *     }
 *     
 *     // CRITICAL: Restore game state after warp
 *     restoreGameStateAfterWarp();
 *     await new Promise(resolve => setTimeout(resolve, 100));
 *     
 *     // CRITICAL: Re-verify current level is set correctly
 *     if (currentLevel !== LEVEL_IDS.LEVEL7) {
 *       console.warn("⚠️ [LEVEL 7] Current level was changed! Resetting...");
 *       currentLevel = LEVEL_IDS.LEVEL7;
 *     }
 *     
 *     // CRITICAL: Ensure weapon is properly initialized after restoreGameStateAfterWarp()
 *     if (weaponSystem && weaponSystem.weaponViewmodel && isFirstPerson()) {
 *       if (!camera.children.includes(weaponSystem.weaponViewmodel)) {
 *         console.warn("⚠️ [LEVEL 7] Weapon was removed! Re-attaching...");
 *         camera.add(weaponSystem.weaponViewmodel);
 *       }
 *       weaponSystem.weaponViewmodel.visible = true;
 *       weaponSystem.weaponViewmodel.traverse((child) => {
 *         if (child.isMesh) {
 *           child.visible = true;
 *         }
 *       });
 *       console.log("✅ [LEVEL 7] Weapon verified after restoreGameStateAfterWarp()");
 *     }
 *     
 *     // ... rest of level initialization ...
 *     
 *     console.log("✅ [LEVEL 7] Warped to Your Level. Weapons loaded and ready!");
 *   }
 * 
 * STEP 5: UPDATE CAMERA MODE HANDLER
 * ------------------------------------
 * In setCameraMode() function in main.js, add logic for your level:
 * 
 *   if (isFirstPerson()) {
 *     // ... existing Level 4 and Level 5 logic ...
 *     
 *     // Load weapon if in your new level
 *     if (currentLevel === LEVEL_IDS.LEVEL7) {
 *       if (weaponSystem && typeof weaponSystem.loadWeapon === 'function') {
 *         const currentSlot = weaponSystem.getCurrentSlot() || 1;
 *         weaponSystem.loadWeapon(currentSlot).then((weapon) => {
 *           if (weapon && weaponSystem.weaponViewmodel) {
 *             weaponSystem.weaponViewmodel.visible = true;
 *             weaponSystem.weaponViewmodel.traverse((child) => {
 *               if (child.isMesh) {
 *                 child.visible = true;
 *               }
 *             });
 *           }
 *         }).catch(err => {
 *           console.error("❌ [LEVEL 7] Failed to load weapon in camera switch:", err);
 *         });
 *       }
 *     }
 *   } else if (isThirdPerson()) {
 *     // Remove weapon when switching to third-person
 *     if (currentLevel === LEVEL_IDS.LEVEL4 || currentLevel === LEVEL_IDS.LEVEL5 || 
 *         currentLevel === LEVEL_IDS.LEVEL6 || currentLevel === LEVEL_IDS.LEVEL7) {
 *       if (weaponSystem && typeof weaponSystem.removeWeapon === 'function') {
 *         weaponSystem.removeWeapon();
 *         // ... hide weapon logic ...
 *       }
 *     }
 *   }
 * 
 * STEP 6: UPDATE LEVEL UPDATE FUNCTION
 * --------------------------------------
 * In your level's update function (e.g., updateLevel7()), add:
 * 
 *   function updateLevel7(delta) {
 *     if (weaponSystem && typeof weaponSystem.update === 'function') {
 *       weaponSystem.update(delta);
 *     }
 *     // ... other level updates ...
 *   }
 * 
 * And in the main animate() loop:
 * 
 *   if (currentLevel === LEVEL_IDS.LEVEL7) {
 *     updateLevel7(delta);
 *   }
 * 
 * COMMON PITFALLS TO AVOID
 * -------------------------
 * 
 * ❌ FORGOT TO UPDATE INPUT HANDLERS
 *    - Symptoms: Weapon loads but can't shoot or switch
 *    - Fix: Update mouse click handler AND all keyboard handlers (keys 1-9)
 * 
 * ❌ SETTING currentLevel AFTER weapon initialization
 *    - Symptoms: Weapon system doesn't recognize the level
 *    - Fix: Set currentLevel BEFORE calling weaponSystem.loadWeapon()
 * 
 * ❌ NOT REQUESTING POINTER LOCK
 *    - Symptoms: Can't shoot (pointer lock required)
 *    - Fix: Request pointer lock after setting camera mode
 * 
 * ❌ NOT RE-INITIALIZING AFTER restoreGameStateAfterWarp()
 *    - Symptoms: Weapon disappears or becomes non-functional
 *    - Fix: Re-check and re-attach weapon after restoreGameStateAfterWarp()
 * 
 * ❌ NOT PRELOADING SECONDARY WEAPONS
 *    - Symptoms: Slow weapon switching
 *    - Fix: Preload slot 2 (and others) with loadWeapon(slot, null, true)
 * 
 * VERIFICATION CHECKLIST
 * -----------------------
 * After implementing, verify:
 * 
 * ✅ Weapon loads when entering level
 * ✅ Weapon is visible in first-person view
 * ✅ Can shoot with mouse click
 * ✅ Can switch weapons with number keys (1-9)
 * ✅ Weapon disappears in third-person view
 * ✅ Weapon reappears when switching back to first-person
 * ✅ Console shows no errors about level mismatch
 * ✅ Pointer lock is active
 * ✅ Both weapon slots are loaded (check console logs)
 * 
 * ============================================================================
 * Last Updated: 2025-12-07
 * Based on: Level 6 (Phoenix Boss Arena) implementation
 * ============================================================================
 */

import * as THREE from "three";

/**
 * Weapon System
 * Manages all weapon-related functionality for the game
 */
export class WeaponSystem {
  constructor(scene, camera, config = {}) {
    this.scene = scene;
    this.camera = camera;
    this.config = config;

    // Weapon state
    this.weapons = {}; // Slot -> Weapon model cache
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
    this.overheatCooldown = 6000; // ms
    this.lastOverheatTime = 0;

    // Triple shot system
    this.tripleShotActive = false;
    this.tripleShotBulletsRemaining = 0;
    this.tripleShotNextBulletTime = 0;

    // Audio
    this.shootSound = null;
    this.tripleShotSound = null;
    this.audioReady = false;
    this.tripleShotAudioReady = false;

    // Weapon configurations (from config)
    this.weaponSlots = config.weaponSlots || {};
    this.weaponTransforms = config.weaponTransforms || {};
    this.shootRange = config.shootRange || 200;
    this.shootAudioPath = config.shootAudioPath || "";
    this.tripleShotAudioPath = config.tripleShotAudioPath || "";

    // Shooting state
    this.isShooting = false;
    this.shootCooldown = 0;
    this.lastShotTime = 0;
    this.weaponRecoilOffset = 0;

    // Bullet system
    this.bullets = [];
    this.bulletSize = config.bulletSize || 0.05;
    this.bulletSpeed = config.bulletSpeed || 100;
    this.bulletLifetime = config.bulletLifetime || 2.0;

    // Dependencies (from config)
    this.loadModel = config.loadModel || null; // Global model loader
    this.audioListener = config.audioListener || null;
    this.audioLoader = config.audioLoader || null;
    this.processWeaponMaterial = config.processWeaponMaterial || null; // Material processor
    this.loadTexture = config.loadTexture || null; // Texture loader
    this.textureCache = config.textureCache || null; // Texture cache
    this.resumeAudioContextIfNeeded = config.resumeAudioContextIfNeeded || (() => {}); // Audio context resumer
    this.resolveAssetPath = config.resolveAssetPath || ((path) => path); // Path resolver function

    // Callbacks (from config)
    this.onWeaponSwitched = config.onWeaponSwitched || (() => {});
    this.onWeaponFired = config.onWeaponFired || (() => {});
    this.onOverheated = config.onOverheated || (() => {});
    this.onHeatChanged = config.onHeatChanged || (() => {});
    this.onMonsterHit = config.onMonsterHit || ((index) => {}); // Level 4 monster hit
    // Level 5 monster hit callback (January 11, 2026)
    // Used when player shoots and hits a Level 5 monster during Step 1 (monster hunt)
    // Calls defeatLevel5Monster(index) which handles:
    // - Awarding 50 DSPOINC per monster (with role multipliers)
    // - Creating sparkling explosion particles
    // - Removing monster from scene
    // - Checking for Step 1 completion (all monsters defeated)
    this.onLevel5MonsterHit = config.onLevel5MonsterHit || ((index) => {});
    this.onCheeseHit = config.onCheeseHit || ((index) => {});
    this.onHitIndicator = config.onHitIndicator || (() => {});

    // State getters (from config)
    this.getCurrentLevel = config.getCurrentLevel || (() => null);
    this.getLevel4State = config.getLevel4State || (() => ({}));
    this.getLevel4RiddleState = config.getLevel4RiddleState || (() => ({}));
    this.getLevel5State = config.getLevel5State || (() => ({})); // Level 5 state getter (January 11, 2026)
    this.getLevel5RiddleState = config.getLevel5RiddleState || (() => ({})); // Level 5 riddle state getter (January 11, 2026)
    this.getLevel6State = config.getLevel6State || (() => null); // Level 6 state getter (February 6, 2026 - spider minions)
    this.isFirstPerson = config.isFirstPerson || (() => false);
    this.isGamePaused = config.isGamePaused || (() => false);
    this.isPointerLocked = config.isPointerLocked || (() => false);
    this.getPhoenixBoss = config.getPhoenixBoss || (() => null); // Phoenix boss getter
  }

  /**
   * Initialize the weapon system
   * Load audio and prepare system
   */
  async initialize() {
    if (this.audioLoader && this.audioListener) {
      await this.loadAudio();
    }
  }

  /**
   * Load weapon audio files
   */
  async loadAudio() {
    if (!this.audioLoader || !this.audioListener) {
      console.warn("⚠️ [WEAPON] Audio loader or listener not available");
      return;
    }

    // Load normal shoot sound
    if (this.shootAudioPath) {
      const resolvedShootPath = this.resolveAssetPath(this.shootAudioPath);
      console.log(`🔍 [WEAPON AUDIO DEBUG] Loading shoot sound: "${this.shootAudioPath}" → "${resolvedShootPath}"`);
      this.audioLoader.load(
        resolvedShootPath,
        (buffer) => {
          this.shootSound = new THREE.Audio(this.audioListener);
          this.shootSound.setBuffer(buffer);
          this.shootSound.setLoop(false);
          this.shootSound.setVolume(0.6);
          this.audioReady = true;
          console.log("✅ [WEAPON] Shoot audio loaded");
        },
        undefined,
        (error) => {
          console.error("❌ [WEAPON] Failed to load shoot audio:", error);
        }
      );
    }

    // Load triple shot sound
    if (this.tripleShotAudioPath) {
      const resolvedTripleShotPath = this.resolveAssetPath(this.tripleShotAudioPath);
      console.log(`🔍 [WEAPON AUDIO DEBUG] Loading triple shot sound: "${this.tripleShotAudioPath}" → "${resolvedTripleShotPath}"`);
      this.audioLoader.load(
        resolvedTripleShotPath,
        (buffer) => {
          this.tripleShotSound = new THREE.Audio(this.audioListener);
          this.tripleShotSound.setBuffer(buffer);
          this.tripleShotSound.setLoop(false);
          this.tripleShotSound.setVolume(0.6);
          this.tripleShotAudioReady = true;
          console.log("✅ [WEAPON] Triple shot audio loaded");
        },
        undefined,
        (error) => {
          console.error("❌ [WEAPON] Failed to load triple shot audio:", error);
        }
      );
    }
  }

  /**
   * Load a weapon model for a specific slot
   * @param {number} slotNumber - Weapon slot (1-9)
   * @param {string} weaponPath - Optional custom weapon path
   * @returns {Promise<THREE.Object3D|null>} Loaded weapon model or null
   */
  async loadWeapon(slotNumber = null, weaponPath = null, preloadOnly = false) {
    const targetSlot = slotNumber || this.currentSlot;
    
    // Validate slot
    if (!this.weaponSlots[targetSlot]) {
      console.warn("⚠️ [WEAPON] Invalid weapon slot:", targetSlot);
      return null;
    }

    // Get weapon path
    const targetPath = weaponPath || this.weaponSlots[targetSlot].path;

    // Check if weapon is already cached
    if (this.weapons[targetSlot]) {
      const cachedWeapon = this.weapons[targetSlot];
      
      // If this is the active slot, ensure it's attached and visible
      if (this.currentSlot === targetSlot) {
        if (this.camera.children.includes(cachedWeapon)) {
          // Weapon exists and is attached - ensure visibility and reapply transforms
          // CRITICAL: Ensure weapon root and all children are visible (same as Level 2)
          cachedWeapon.visible = true;
          cachedWeapon.frustumCulled = false; // Disable frustum culling
          cachedWeapon.renderOrder = 999; // Render on top
          
          cachedWeapon.traverse((child) => {
            // CRITICAL: Ensure ALL objects are visible (not just meshes)
            child.visible = true;
            child.frustumCulled = false; // Disable frustum culling for all children
            
            if (child.isMesh) {
              child.visible = true;
              child.frustumCulled = false;
              child.renderOrder = 999; // Render on top
              
              // CRITICAL: Ensure material is visible
              if (child.material) {
                const mat = Array.isArray(child.material) ? child.material[0] : child.material;
                if (mat) {
                  mat.visible = true;
                  mat.transparent = false;
                  mat.opacity = 1.0;
                  mat.needsUpdate = true;
                }
              }
            }
          });
          this._applyWeaponTransforms(cachedWeapon, targetSlot);
          console.log("🔫 [WEAPON] Weapon already loaded (slot " + targetSlot + "), ensuring visibility", {
            weaponVisible: cachedWeapon.visible,
            isInCamera: this.camera.children.includes(cachedWeapon),
            position: cachedWeapon.position.toArray().map(n => n.toFixed(2))
          });
          return cachedWeapon;
        } else {
          // Weapon exists but not attached - reattach it
          console.log("🔫 [WEAPON] Weapon exists but not attached, reattaching...");
          cachedWeapon.visible = true;
          this.camera.add(cachedWeapon);
          // CRITICAL: Ensure all children are visible
          cachedWeapon.traverse((child) => {
            if (child.isMesh) {
              child.visible = true;
              if (child.material) {
                const mat = Array.isArray(child.material) ? child.material[0] : child.material;
                if (mat) {
                  mat.visible = true;
                  mat.transparent = false;
                  mat.opacity = 1.0;
                  mat.needsUpdate = true;
                }
              }
            }
          });
          this._applyWeaponTransforms(cachedWeapon, targetSlot);
          console.log("✅ [WEAPON] Cached weapon reattached and visible", {
            weaponVisible: cachedWeapon.visible,
            isInCamera: this.camera.children.includes(cachedWeapon),
            position: cachedWeapon.position.toArray().map(n => n.toFixed(2))
          });
          return cachedWeapon;
        }
      } else if (preloadOnly) {
        // Preload only - weapon is cached, don't attach or change current slot
        console.log("🔫 [WEAPON] Weapon slot " + targetSlot + " already preloaded (cached)");
        return cachedWeapon;
      }
    }

    // If preload only and weapon is cached, return it without attaching
    if (preloadOnly && this.weapons[targetSlot]) {
      return this.weapons[targetSlot];
    }

    // Remove current weapon if switching slots (only if not preloading)
    if (!preloadOnly && this.weaponViewmodel && this.currentSlot !== targetSlot) {
      this.removeWeapon();
    }

    // Load weapon model
    if (!this.loadModel) {
      console.error("❌ [WEAPON] loadModel function not available");
      return null;
    }

    try {
      console.log("🔫 [WEAPON] Loading weapon (Slot " + targetSlot + "):", targetPath);
      const weaponData = await this.loadModel(targetPath);
      const weaponModel = weaponData.scene.clone(true);

      // CRITICAL: Process weapon model - ensure root is visible and not culled
      weaponModel.visible = true;
      weaponModel.frustumCulled = false; // Disable frustum culling for visibility (like Level 2)
      weaponModel.renderOrder = 999; // Render on top
      let totalVertices = 0;
      
      weaponModel.traverse((child) => {
        // CRITICAL: Ensure ALL objects in the weapon model are visible (not just meshes)
        child.visible = true;
        child.frustumCulled = false; // Disable frustum culling for all children
        
        if (child.isMesh) {
          child.visible = true;
          child.castShadow = false;
          child.receiveShadow = false;
          child.frustumCulled = false; // Disable frustum culling (critical for visibility)
          child.renderOrder = 999; // Render on top

          // Process material if processor available
          if (this.processWeaponMaterial) {
            const originalMaterial = child.material;
            child.material = this.processWeaponMaterial(child.material);
            
            // CRITICAL: Ensure textures are properly loaded and assigned
            const processedMaterial = Array.isArray(child.material) ? child.material[0] : child.material;
            if (processedMaterial && processedMaterial.map) {
              // If texture exists, ensure it's ready
              if (processedMaterial.map.image) {
                if (processedMaterial.map.image.complete) {
                  processedMaterial.map.needsUpdate = true;
                } else {
                  // Wait for texture to load
                  processedMaterial.map.image.onload = () => {
                    processedMaterial.map.needsUpdate = true;
                    processedMaterial.needsUpdate = true;
                    console.log("🔫 [WEAPON] Texture loaded for weapon material");
                  };
                }
              }
              // Force material update to ensure texture renders
              processedMaterial.needsUpdate = true;
            }
            
            // Log material processing for debugging
            if (originalMaterial) {
              const hasMap = originalMaterial.map || (Array.isArray(originalMaterial) && originalMaterial[0]?.map);
              console.log("🔫 [WEAPON] Material processed for mesh:", {
                originalType: originalMaterial.type || (Array.isArray(originalMaterial) ? originalMaterial[0]?.type : 'unknown'),
                newType: processedMaterial?.type || 'unknown',
                hasMap: !!processedMaterial?.map,
                mapLoaded: processedMaterial?.map ? (processedMaterial.map.image ? processedMaterial.map.image.complete : false) : false,
                color: processedMaterial?.color ? processedMaterial.color.getHexString() : 'unknown'
              });
            }
          }

          // Log mesh info
          if (child.geometry) {
            const vertices = child.geometry.attributes?.position?.count || 0;
            totalVertices += vertices;
          }
        }
      });

      // Calculate proper scale
      const weaponBounds = new THREE.Box3().setFromObject(weaponModel);
      const weaponSize = weaponBounds.getSize(new THREE.Vector3());
      const maxDimension = Math.max(weaponSize.x, weaponSize.y, weaponSize.z);

      // Apply transforms
      const weaponInfo = this.weaponSlots[targetSlot];
      const weaponType = weaponInfo?.type || "pistol";
      const transform = this.weaponTransforms[weaponType] || this.weaponTransforms.pistol || {};
      const targetSize = transform.targetSize || 0.35; // CRITICAL: Default 0.35 for proper first-person weapon size (reduced from 0.45 for smaller weapons)
      const scaleFactor = maxDimension > 0 ? targetSize / maxDimension : 0.2;

      // CRITICAL: Position weapon in camera space (negative Z = in front of camera in Three.js)
      // Backup uses -0.5 which works correctly - weapon appears in front of player
      weaponModel.position.set(0.0, -0.4, -0.5); // Negative Z = in front of camera (matches backup)
      weaponModel.rotation.set(
        transform.rotationX || -0.15,
        transform.rotationY || Math.PI / 2,
        transform.rotationZ || 0.0
      );
      weaponModel.scale.set(scaleFactor, scaleFactor, scaleFactor);
      
      // CRITICAL: Scale calculation complete
      // With targetSize of 1.0 and maxDimension of ~180, scaleFactor should be ~0.0055
      // However, if maxDimension is smaller (e.g., ~93), scaleFactor will be ~0.0107
      // Both are acceptable for weapon viewmodels - they should be relatively small in first-person
      // The weapon will be visible at these scales
      
      // CRITICAL: Log scale factor for debugging
      console.log("🔫 [WEAPON] Weapon transforms applied:", {
        position: `(${weaponModel.position.x.toFixed(2)}, ${weaponModel.position.y.toFixed(2)}, ${weaponModel.position.z.toFixed(2)})`,
        scale: `(${weaponModel.scale.x.toFixed(4)}, ${weaponModel.scale.y.toFixed(4)}, ${weaponModel.scale.z.toFixed(4)})`,
        scaleFactor: scaleFactor.toFixed(4),
        targetSize: targetSize,
        maxDimension: maxDimension.toFixed(2),
        rotation: `(${weaponModel.rotation.x.toFixed(2)}, ${weaponModel.rotation.y.toFixed(2)}, ${weaponModel.rotation.z.toFixed(2)})`
      });

      // Cache weapon first
      this.weapons[targetSlot] = weaponModel;

      // CRITICAL: Only attach to camera and set as current if NOT preloading
      if (!preloadOnly) {
        // CRITICAL: Ensure weapon model is visible before attaching
        weaponModel.visible = true;
        
        // Add to camera
        this.camera.add(weaponModel);
        this.weaponViewmodel = weaponModel;
        this.currentSlot = targetSlot;
        
        // ⚠️ CRITICAL REMINDER FOR FUTURE DEVELOPERS (December 13, 2025):
        // - ALWAYS brighten dark materials (3x for brightness < 0.3)
        // - ALWAYS set frustumCulled = false (prevents culling issues)
        // - ALWAYS set renderOrder = 999 (ensures weapon renders on top)
        // - See WEAPON_RENDERING_RULES.md for complete material processing guide
        // CRITICAL: Final visibility check - ensure weapon and all children are visible
        // Also brighten dark materials for visibility
        weaponModel.traverse((child) => {
          if (child.isMesh) {
            child.visible = true;
            if (child.material) {
              // Process all materials (handle both single and array)
              const materials = Array.isArray(child.material) ? child.material : [child.material];
              materials.forEach((mat) => {
                if (mat) {
                  mat.visible = true;
                  mat.transparent = false;
                  mat.opacity = 1.0;
                  
                  // CRITICAL: Brighten dark materials for visibility
                  if (mat.color) {
                    const brightness = mat.color.r + mat.color.g + mat.color.b;
                    if (brightness < 0.3) {
                      // Very dark material - brighten it significantly
                      mat.color.r = Math.min(1.0, mat.color.r * 3.0);
                      mat.color.g = Math.min(1.0, mat.color.g * 3.0);
                      mat.color.b = Math.min(1.0, mat.color.b * 3.0);
                      // Add slight emissive for visibility
                      if (!mat.emissive || (mat.emissive.r === 0 && mat.emissive.g === 0 && mat.emissive.b === 0)) {
                        mat.emissive = mat.color.clone().multiplyScalar(0.1);
                        mat.emissiveIntensity = 0.2;
                      }
                      console.log("🔫 [WEAPON] Brightened dark material in final pass:", {
                        meshName: child.name,
                        originalBrightness: brightness.toFixed(3),
                        newColor: mat.color.getHexString(),
                        hasEmissive: !!mat.emissive
                      });
                    }
                  }
                  
                  mat.needsUpdate = true;
                }
              });
            }
          }
        });
        
        // CRITICAL: Verify weapon is in camera
        const isInCamera = this.camera.children.includes(weaponModel);
        const cameraChildrenCount = this.camera.children.length;
        
        // CRITICAL: Log detailed weapon state for debugging
        let meshCount = 0;
        let visibleMeshCount = 0;
        let materialCount = 0;
        weaponModel.traverse((child) => {
          if (child.isMesh) {
            meshCount++;
            if (child.visible) visibleMeshCount++;
            if (child.material) {
              materialCount++;
              const mat = Array.isArray(child.material) ? child.material[0] : child.material;
              if (mat && !mat.visible) {
                console.warn("⚠️ [WEAPON] Material is not visible!", {
                  meshName: child.name,
                  materialType: mat.type,
                  materialVisible: mat.visible,
                  materialOpacity: mat.opacity
                });
              }
            }
          }
        });
        
        console.log("✅ [WEAPON] Weapon loaded and attached", {
          slot: targetSlot,
          name: weaponInfo?.name || "Unknown",
          totalVertices: totalVertices,
          weaponVisible: weaponModel.visible,
          isInCamera: isInCamera,
          cameraChildren: cameraChildrenCount,
          position: `(${weaponModel.position.x.toFixed(2)}, ${weaponModel.position.y.toFixed(2)}, ${weaponModel.position.z.toFixed(2)})`,
          scale: `(${weaponModel.scale.x.toFixed(3)}, ${weaponModel.scale.y.toFixed(3)}, ${weaponModel.scale.z.toFixed(3)})`,
          rotation: `(${weaponModel.rotation.x.toFixed(2)}, ${weaponModel.rotation.y.toFixed(2)}, ${weaponModel.rotation.z.toFixed(2)})`,
          meshCount: meshCount,
          visibleMeshCount: visibleMeshCount,
          materialCount: materialCount,
          renderOrder: weaponModel.renderOrder
        });

        // Update HUD via callback (only for active weapons, not preloaded)
        this.onWeaponSwitched(targetSlot, weaponInfo);
      } else {
        // Preload only - don't attach to camera, don't change current slot, don't update HUD
        weaponModel.visible = false; // Keep hidden until actually switched to
        console.log("🔫 [WEAPON] Weapon preloaded (slot " + targetSlot + ") - not attached, current slot remains " + this.currentSlot + ", HUD not updated");
        // CRITICAL: Don't call onWeaponSwitched for preloaded weapons - this was causing HUD confusion
      }

      weaponModel.updateMatrixWorld(true);
      return weaponModel;
    } catch (error) {
      console.error("❌ [WEAPON] Failed to load weapon:", error);
      return null;
    }
  }

  /**
   * Remove current weapon from camera
   */
  removeWeapon() {
    if (this.weaponViewmodel) {
      // Hide weapon
      this.weaponViewmodel.visible = false;
      this.weaponViewmodel.traverse((child) => {
        if (child.isMesh) {
          child.visible = false;
        }
      });
      // CRITICAL: Remove from camera if attached (force removal)
      if (this.camera.children.includes(this.weaponViewmodel)) {
        this.camera.remove(this.weaponViewmodel);
        console.log("🔫 [WEAPON] Weapon removed from camera");
      }
      // CRITICAL: Also check if weapon is in scene and remove it
      if (this.scene && this.scene.children.includes(this.weaponViewmodel)) {
        this.scene.remove(this.weaponViewmodel);
        console.log("🔫 [WEAPON] Weapon removed from scene");
      }
      // Don't null the weaponViewmodel if it's cached - just remove from camera
      console.log("🔫 [WEAPON] Weapon removed from camera (hidden)");
    } else {
      // CRITICAL: Even if weaponViewmodel is null, check camera for any weapon children
      // This handles cases where weapon might be attached but weaponViewmodel reference is lost
      const weaponChildren = this.camera.children.filter(child => {
        // Check if child looks like a weapon (has meshes, is not a light, etc.)
        let hasWeaponMeshes = false;
        child.traverse((descendant) => {
          if (descendant.isMesh) {
            hasWeaponMeshes = true;
          }
        });
        return hasWeaponMeshes && !child.isLight && !child.isCamera;
      });
      if (weaponChildren.length > 0) {
        weaponChildren.forEach(weapon => {
          weapon.visible = false;
          weapon.traverse((child) => {
            if (child.isMesh) {
              child.visible = false;
            }
          });
          this.camera.remove(weapon);
          console.log("🔫 [WEAPON] Removed orphaned weapon from camera");
        });
      }
    }
  }

  /**
   * Apply weapon transforms based on weapon type
   * @private
   */
  /**
   * Apply weapon transforms (position, rotation, scale)
   * 
   * ⚠️ CRITICAL REMINDER FOR FUTURE DEVELOPERS (December 13, 2025):
   * - NEVER reset weapon position during bobbing/recoil animations (use preservePosition = true)
   * - ALWAYS use targetSize: 0.35 for proper first-person weapon scale (reduced from 0.45 for smaller weapons)
   * - Position (0.0, -0.4, -0.5) is correct - negative Z places weapon in front of camera
   * - See WEAPON_RENDERING_RULES.md for complete implementation guide
   * 
   * @param {THREE.Object3D} weaponModel - Weapon model to transform
   * @param {number} slotNumber - Weapon slot number
   * @param {boolean} preservePosition - If true, don't reset position (for animations)
   */
  _applyWeaponTransforms(weaponModel, slotNumber, preservePosition = false) {
    const weaponInfo = this.weaponSlots[slotNumber];
    const weaponType = weaponInfo?.type || "pistol";
    const transform = this.weaponTransforms[weaponType] || this.weaponTransforms.pistol || {};

    // CRITICAL: Only set position if not preserving (prevents resetting during bobbing animation)
    // Backup uses -0.5 which works correctly - weapon appears in front of player
    if (!preservePosition) {
      weaponModel.position.set(0.0, -0.4, -0.5); // Negative Z = in front of camera (matches backup)
    }
    
    // CRITICAL: Set rotation (always apply rotation, but preserve position if requested)
    weaponModel.rotation.set(
      transform.rotationX || -0.15,
      transform.rotationY || Math.PI / 2,
      transform.rotationZ || 0.0
    );
    
    // CRITICAL: Ensure scale is reasonable (not too small)
    // If scale was not set or is too small, recalculate it
    const currentScale = weaponModel.scale.x;
    if (currentScale < 0.001 || currentScale === 1.0) { // 1.0 means scale wasn't applied yet
      // Recalculate scale using the transform's targetSize
      const weaponBounds = new THREE.Box3().setFromObject(weaponModel);
      const weaponSize = weaponBounds.getSize(new THREE.Vector3());
      const maxDimension = Math.max(weaponSize.x, weaponSize.y, weaponSize.z);
      const targetSize = transform.targetSize || 0.35; // CRITICAL: Use 0.35 default (matches main.js, reduced from 0.45 for smaller weapons)
      if (maxDimension > 0) {
        const scaleFactor = targetSize / maxDimension;
        weaponModel.scale.set(scaleFactor, scaleFactor, scaleFactor);
        console.log("🔫 [WEAPON] Recalculated scale in _applyWeaponTransforms:", {
          oldScale: currentScale,
          newScale: scaleFactor.toFixed(4),
          maxDimension: maxDimension.toFixed(2),
          targetSize: targetSize
        });
      }
    }
    
    // CRITICAL: Update matrix world to apply transforms
    weaponModel.updateMatrixWorld(true);
    
    // CRITICAL: Log transforms for debugging
    console.log("🔫 [WEAPON] Transforms applied:", {
      position: `(${weaponModel.position.x.toFixed(2)}, ${weaponModel.position.y.toFixed(2)}, ${weaponModel.position.z.toFixed(2)})`,
      scale: `(${weaponModel.scale.x.toFixed(4)}, ${weaponModel.scale.y.toFixed(4)}, ${weaponModel.scale.z.toFixed(4)})`,
      rotation: `(${weaponModel.rotation.x.toFixed(2)}, ${weaponModel.rotation.y.toFixed(2)}, ${weaponModel.rotation.z.toFixed(2)})`,
      slot: slotNumber,
      weaponType: weaponType
    });
  }

  /**
   * Switch to a different weapon slot
   * @param {number} slotNumber - Weapon slot (1-9)
   */
  async switchWeapon(slotNumber) {
    // CRITICAL: Always log switchWeapon() calls for debugging
    const currentLevel = this.getCurrentLevel();
    console.log("🔄 [WEAPON] switchWeapon() called", {
      requestedSlot: slotNumber,
      currentLevel: currentLevel,
      currentSlot: this.currentSlot,
      hasWeapon: !!this.weaponViewmodel,
      weaponAttached: this.weaponViewmodel ? this.camera.children.includes(this.weaponViewmodel) : false
    });
    
    // Validate slot
    if (!this.weaponSlots[slotNumber]) {
      console.warn("⚠️ [WEAPON] Invalid weapon slot:", slotNumber);
      return;
    }

    // Don't switch if already on this slot
    if (this.currentSlot === slotNumber && this.weaponViewmodel && this.camera.children.includes(this.weaponViewmodel)) {
      console.log("🔫 [WEAPON] Already using weapon slot", slotNumber);
      return;
    }

    // Check if switching is allowed (Level 4, first-person, step active)
    if (!this._canSwitchWeapon()) {
      console.log("🔫 [WEAPON] Switching blocked by _canSwitchWeapon()");
      return;
    }

    console.log("🔄 [WEAPON] Switching to weapon slot", slotNumber, this.weaponSlots[slotNumber].name, "(current:", this.currentSlot + ")");

    // Hide current weapon
    if (this.weaponViewmodel) {
      this.weaponViewmodel.visible = false;
      // Remove from camera if switching to different slot
      if (this.currentSlot !== slotNumber && this.camera.children.includes(this.weaponViewmodel)) {
        this.camera.remove(this.weaponViewmodel);
      }
    }

    // Load new weapon (not preload - this is an actual switch)
    const newWeapon = await this.loadWeapon(slotNumber, null, false);
    
    // If weapon was preloaded, attach it now
    if (newWeapon && !this.camera.children.includes(newWeapon)) {
      this.camera.add(newWeapon);
      newWeapon.visible = true;
      this.weaponViewmodel = newWeapon;
      this.currentSlot = slotNumber;
      newWeapon.traverse((child) => {
        if (child.isMesh) {
          child.visible = true;
        }
      });
      this._applyWeaponTransforms(newWeapon, slotNumber);
      console.log("✅ [WEAPON] Preloaded weapon attached and activated (slot " + slotNumber + ")");
    }
  }

  /**
   * Check if weapon switching is allowed
   * @private
   */
  _canSwitchWeapon() {
    const currentLevel = this.getCurrentLevel();
    const isLevel6 = currentLevel === "LEVEL6";
    
    // CRITICAL: Don't allow switching if game is paused
    if (this.isGamePaused()) {
      console.log("🔫 [WEAPON] Switching blocked: game is paused");
      return false;
    }

    const level4RiddleState = this.getLevel4RiddleState();
    const isFirstPerson = this.isFirstPerson();

    // DEBUG: Log all conditions (always log for Level 6 debugging)
    if (isLevel6) {
      console.log("🔍 [WEAPON] _canSwitchWeapon check (Level 6):", {
        currentLevel: currentLevel,
        expectedLevels: ["LEVEL4", "LEVEL5", "LEVEL6"],
        step1Active: level4RiddleState?.step1Active,
        step2Active: level4RiddleState?.step2Active,
        isFirstPerson: isFirstPerson,
        level4RiddleStateExists: !!level4RiddleState,
        currentSlot: this.currentSlot,
        weaponViewmodel: !!this.weaponViewmodel,
        isGamePaused: this.isGamePaused()
      });
    }

    // Only allow switching in Level 4, Level 5, or Level 6 (weapons are available from level start)
    // This allows weapon switching as soon as the level loads
    // NOTE: currentLevel is a string "LEVEL4", "LEVEL5", or "LEVEL6", not a number
    if (currentLevel !== "LEVEL4" && currentLevel !== "LEVEL5" && currentLevel !== "LEVEL6") {
      if (isLevel6 || Math.random() < 0.2) {
        console.log("🔫 [WEAPON] Switching blocked: wrong level", currentLevel, "(expected: 'LEVEL4', 'LEVEL5', or 'LEVEL6')");
      }
      return false;
    }
    // REMOVED: Step check - weapons should be switchable from Level 4 start
    // if (!level4RiddleState?.step1Active && !level4RiddleState?.step2Active) {
    //   console.log("🔫 [WEAPON] Switching blocked: step not active", {
    //     step1: level4RiddleState?.step1Active,
    //     step2: level4RiddleState?.step2Active,
    //     level4RiddleState: level4RiddleState
    //   });
    //   return false;
    // }
    if (!isFirstPerson) {
      if (isLevel6 || Math.random() < 0.2) {
        console.log("🔫 [WEAPON] Switching blocked: not first-person (isFirstPerson:", isFirstPerson, ")");
      }
      return false;
    }
    if (isLevel6) {
      console.log("✅ [WEAPON] Switching allowed - all conditions met for Level 6");
    }
    return true;
  }

  /**
   * Fire weapon (shooting mechanics)
   */
  fire() {
    const currentLevel = this.getCurrentLevel();

    // 🔫 Only log when weapon fire debug is enabled
    if (
      typeof DEBUG_SETTINGS !== "undefined" &&
      DEBUG_SETTINGS.logWeaponFire
    ) {
      console.log("🔫 [WEAPON] fire() called", {
        currentLevel,
        currentSlot: this.currentSlot,
        hasWeapon: !!this.weaponViewmodel,
        weaponAttached: this.weaponViewmodel
          ? this.camera.children.includes(this.weaponViewmodel)
          : false,
      });
    }

    // CRITICAL: Validate shooting conditions first
    if (!this._canShoot()) {
      // Optional: detailed "blocked" debug, only when weapon fire debug is on
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logWeaponFire
      ) {
        const level4RiddleState = this.getLevel4RiddleState();
        const isFirstPerson = this.isFirstPerson();
        const isPaused = this.isGamePaused();
        const isLocked = this.isPointerLocked();
        const level = this.getCurrentLevel();
        console.log("🔫 [WEAPON] Shooting blocked:", {
          level,
          paused: isPaused,
          pointerLocked: isLocked,
          firstPerson: isFirstPerson,
          step1Active: level4RiddleState?.step1Active,
          step2Active: level4RiddleState?.step2Active,
          weaponLoaded: this.weaponViewmodel !== null,
          weaponAttached: this.weaponViewmodel
            ? this.camera.children.includes(this.weaponViewmodel)
            : false,
          currentSlot: this.currentSlot,
          weaponViewmodelExists: !!this.weaponViewmodel,
        });
      }
      return;
    }

    // Check for overheating
    if (this.isOverheated) {
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logWeaponFire
      ) {
        console.log("🚫 [WEAPON] Cannot shoot - weapon is overheated!");
      }
      return;
    }

    // CRITICAL: Ensure weapon is loaded before firing
    if (
      !this.weaponViewmodel ||
      !this.camera.children.includes(this.weaponViewmodel)
    ) {
      console.warn(
        "⚠️ [WEAPON] Cannot shoot - weapon not loaded. Attempting to load..."
      );

      // Try to load weapon if in Level 4, Level 5, or Level 6
      const lvl = this.getCurrentLevel();
      if (lvl === "LEVEL4" || lvl === "LEVEL5" || lvl === "LEVEL6") {
        this.loadWeapon(this.currentSlot).catch((err) => {
          console.error("❌ [WEAPON] Failed to load weapon:", err);
        });
      }
      return;
    }

    // Debug: Log successful fire attempt (gated)
    if (
      typeof DEBUG_SETTINGS !== "undefined" &&
      DEBUG_SETTINGS.logWeaponFire
    ) {
      console.log(
        "🔫 [WEAPON] Fire() called - conditions met, proceeding to fire..."
      );
    }

    // Check if SF13 (slot 2) - triple shot mode
    const isSF13 = this.currentSlot === 2;

    if (isSF13) {
      // SF13: Start triple-shot burst
      if (!this.tripleShotActive) {
        this.tripleShotActive = true;
        this.tripleShotBulletsRemaining = 3;
        this.tripleShotNextBulletTime = performance.now() / 1000;

        // Add heat for triple shot
        this.generateHeat(this.heatPerTripleShot);

        // Fire first bullet immediately (purple bullet for SF13)
        if (
          typeof DEBUG_SETTINGS !== "undefined" &&
          DEBUG_SETTINGS.logWeaponFire
        ) {
          console.log(
            "🔫 [WEAPON] Starting triple shot burst (purple bullets) from slot 2"
          );
        }

        this._fireSingleShot(true); // true = purple bullet

        // Play SF13 sound
        this.playTripleShotSound();

        if (
          typeof DEBUG_SETTINGS !== "undefined" &&
          DEBUG_SETTINGS.logWeaponFire
        ) {
          console.log(
            "✅ [WEAPON] Triple shot burst started, first bullet fired, sound played"
          );
        }

        // Update cooldown for burst
        this.lastShotTime = performance.now() / 1000;
        this.shootCooldown = 0.4; // 400ms cooldown between bursts
      }
      // Triple shot system will handle the rest in update()
      return;
    } else {
      // Normal weapon (slot 1): Single shot
      const currentTime = performance.now() / 1000;
      if (currentTime - this.lastShotTime < this.shootCooldown) {
        return; // Still on cooldown
      }

      // Fire single shot (yellow bullet for cheese)
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logWeaponFire
      ) {
        console.log(
          "🔫 [WEAPON] Firing single shot (yellow bullet) from slot 1"
        );
      }

      this._fireSingleShot(false); // false = yellow bullet

      // Add heat for single shot
      this.generateHeat(this.heatPerShot);

      // Play shooting sound
      this.playShootSound();
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logWeaponFire
      ) {
        console.log(
          "✅ [WEAPON] Single shot fired, sound played, heat added"
        );
      }

      // Update cooldown
      this.lastShotTime = currentTime;
      this.shootCooldown = 0.2; // 200ms between shots
    }
  }

  /**
   * Fire a single shot (used by both normal and triple-shot systems)
   * @private
   */
  _fireSingleShot(isPurple = false) {
    // CRITICAL: Verify scene and camera exist
    if (!this.scene) {
      console.error("❌ [WEAPON] Cannot fire - scene is null!");
      return;
    }
    if (!this.camera) {
      console.error("❌ [WEAPON] Cannot fire - camera is null!");
      return;
    }

    const level4RiddleState = this.getLevel4RiddleState();
    const level4State = this.getLevel4State();

    // Perform raycast from camera center (crosshair position)
    const raycaster = new THREE.Raycaster();
    raycaster.setFromCamera(new THREE.Vector2(0, 0), this.camera); // Center of screen
    raycaster.far = this.shootRange;

    // Calculate bullet start position (from gun/weapon position)
    const gunOffset = new THREE.Vector3(0.0, -0.4, -0.5);
    const bulletStartPos = new THREE.Vector3();
    this.camera.getWorldPosition(bulletStartPos);

    // Apply camera rotation to offset
    const forward = new THREE.Vector3(0, 0, -1).applyQuaternion(
      this.camera.quaternion
    );
    const right = new THREE.Vector3(1, 0, 0).applyQuaternion(
      this.camera.quaternion
    );
    const up = new THREE.Vector3(0, 1, 0).applyQuaternion(
      this.camera.quaternion
    );

    bulletStartPos.addScaledVector(right, gunOffset.x);
    bulletStartPos.addScaledVector(up, gunOffset.y);
    bulletStartPos.addScaledVector(forward, -gunOffset.z);

    // Calculate target position
    let targetPos = new THREE.Vector3();
    const rayDirection = raycaster.ray.direction.clone();
    targetPos.copy(bulletStartPos).addScaledVector(
      rayDirection,
      this.shootRange
    );

    // Check for hits
    let hitCheese = null;
    let hitMonster = null;
    let hitDistance = Infinity;
    let hitIndex = -1;
    let monsterHitIndex = -1;
    let level5MonsterHitIndex = -1; // Level 5 monster hit index (January 11, 2026)

    // 🔥 PHOENIX BOSS: Check for Phoenix hit (Level 5 or Level 6) - takes highest priority
    let hitPhoenix = false;
    let hitSpiderMinion = null;
    const currentLevel = this.getCurrentLevel();
    const phoenixBossInstance = this.getPhoenixBoss
      ? this.getPhoenixBoss()
      : null;

    if (
      (currentLevel === "LEVEL5" || currentLevel === "LEVEL6") &&
      phoenixBossInstance &&
      phoenixBossInstance.isAlive
    ) {
      const phoenixModel = phoenixBossInstance.getModel();
      if (phoenixModel) {
        const intersects = raycaster.intersectObject(phoenixModel, true);
        if (intersects.length > 0) {
          const distance = intersects[0].distance;
          if (distance < hitDistance) {
            hitDistance = distance;
            hitPhoenix = true;
            hitMonster = null;
            hitCheese = null;
            hitIndex = -1;
            monsterHitIndex = -1;
            level5MonsterHitIndex = -1;
            targetPos.copy(intersects[0].point);

            // Distance log for Phoenix hit – boss debug only
            if (
              typeof DEBUG_SETTINGS !== "undefined" &&
              DEBUG_SETTINGS.logWeaponBossHits
            ) {
              console.log(
                `🔥 [WEAPON] Phoenix HIT! Distance: ${distance.toFixed(2)}`
              );
            }
          }
        }
      }
    }

    // 🕷️ LEVEL 6 SPIDER MINIONS: Check for spider minion hits (February 6, 2026)
    const level6State = this.getLevel6State ? this.getLevel6State() : null;
    if (
      currentLevel === "LEVEL6" &&
      level6State?.spiderMinions &&
      !hitPhoenix
    ) {
      level6State.spiderMinions.forEach((minion) => {
        if (!minion || !minion.isAlive) return;
        const raycastTarget = minion.hitbox || minion.model;
        if (!raycastTarget) return;
        try {
          const intersects = raycaster.intersectObject(raycastTarget, true);
          if (intersects.length > 0) {
            const distance = intersects[0].distance;
            if (distance < hitDistance) {
              hitDistance = distance;
              hitSpiderMinion = minion;
              hitMonster = null;
              hitCheese = null;
              hitPhoenix = false;
              hitIndex = -1;
              monsterHitIndex = -1;
              level5MonsterHitIndex = -1;
              targetPos.copy(intersects[0].point);

              if (
                typeof DEBUG_SETTINGS !== "undefined" &&
                DEBUG_SETTINGS.logLevel6SpiderHits
              ) {
                console.log(
                  `🕷️ [WEAPON] Level 6 Spider Minion HIT! Distance: ${distance.toFixed(
                    2
                  )}`
                );
              }
            }
          }
        } catch (e) {
          console.warn("⚠️ [WEAPON] Spider minion raycast error:", e);
        }
      });
    }

    // 🎯 LEVEL 5 MONSTERS: Check for Level 5 monster hits (takes priority over Level 4 monsters)
    const level5RiddleState = this.getLevel5RiddleState();
    const level5State = this.getLevel5State();

    // Level 5 detection debug – only when enabled
    if (
      currentLevel === "LEVEL5" &&
      typeof DEBUG_SETTINGS !== "undefined" &&
      DEBUG_SETTINGS.logLevel5MonsterDetection
    ) {
      console.log("🔍 [WEAPON] Level 5 detection check:", {
        currentLevel,
        currentLevelType: typeof currentLevel,
        step1Active: level5RiddleState?.step1Active,
        hasMonsters: !!level5State?.monsters,
        monstersCount: level5State?.monsters?.length || 0,
        hitPhoenix,
        conditionMet:
          currentLevel === "LEVEL5" &&
          level5RiddleState?.step1Active &&
          level5State?.monsters &&
          !hitPhoenix,
        level5RiddleStateType: typeof level5RiddleState,
        level5StateType: typeof level5State,
        level5RiddleStateKeys: level5RiddleState
          ? Object.keys(level5RiddleState)
          : [],
        level5StateKeys: level5State ? Object.keys(level5State) : [],
      });
    }

    // Level 5 Monster Detection (January 11, 2026)
    if (
      currentLevel === "LEVEL5" &&
      level5RiddleState?.step1Active &&
      level5State?.monsters &&
      !hitPhoenix
    ) {
      const cameraPos = this.camera.position.clone();

      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logLevel5MonsterDetection &&
        Math.random() < 0.05
      ) {
        console.log(
          "🔍 [WEAPON] Checking Level 5 monsters, count:",
          level5State.monsters.length
        );
      }

      level5State.monsters.forEach((monster, index) => {
        if (!monster || monster.defeated) return;
        if (!monster.mesh || !monster.mesh.visible) return;

        // Get monster world position
        const monsterWorldPos = new THREE.Vector3();
        monster.mesh.getWorldPosition(monsterWorldPos);

        // CRITICAL STABILITY FIX:
        const raycastTarget = monster.hitbox || monster.mesh;
        let intersects = [];
        try {
          // Use recursive=false for hitboxes (faster), true only as fallback
          const recursive = !monster.hitbox;
          intersects = raycaster.intersectObject(raycastTarget, recursive);
        } catch (raycastError) {
          console.warn(
            `⚠️ [WEAPON] Level 5 raycast error on monster ${index} (${
              monster.path?.split("/").pop() || "Unknown"
            }):`,
            raycastError
          );
          intersects = [];
        }

        if (intersects.length > 0) {
          const distance = intersects[0].distance;
          if (distance < hitDistance) {
            hitDistance = distance;
            hitMonster = monster; // Store monster reference
            hitCheese = null;
            hitIndex = -1;
            monsterHitIndex = -1; // Clear Level 4 monster hit
            level5MonsterHitIndex = index; // Store Level 5 monster index
            targetPos.copy(intersects[0].point);

            if (
              typeof DEBUG_SETTINGS !== "undefined" &&
              DEBUG_SETTINGS.logWeaponBossHits
            ) {
              console.log(
                `🎯 [WEAPON] Level 5 Monster ${index} HIT! Distance: ${distance.toFixed(
                  2
                )}`
              );
            }
          }
        } else if (
          typeof DEBUG_SETTINGS !== "undefined" &&
          DEBUG_SETTINGS.logLevel5MonsterDetection &&
          Math.random() < 0.05
        ) {
          // Debug: occasional raycast miss log
          const distToMonster = cameraPos.distanceTo(monsterWorldPos);
          console.log(
            `🔍 [WEAPON] Level 5 Raycast missed monster ${index}:`,
            {
              distance: distToMonster.toFixed(2),
              visible: monster.mesh.visible,
              defeated: monster.defeated,
              meshInScene: level5State.group?.children.includes(monster.mesh),
              groupVisible: level5State.group?.visible,
            }
          );
        }
      });
    }

    // Step 2 (monsters) takes priority over Step 1 (cheese)
    if (
      level4RiddleState?.step2Active &&
      level4State?.monsters &&
      !hitPhoenix
    ) {
      const cameraPos = this.camera.position.clone();

      level4State.monsters.forEach((monster, index) => {
        if (
          !monster ||
          !monster.mesh ||
          !monster.mesh.visible ||
          monster.defeated
        )
          return;

        const monsterWorldPos = new THREE.Vector3();
        monster.mesh.getWorldPosition(monsterWorldPos);

        // Raycast against monster mesh with recursive check
        const intersects = raycaster.intersectObject(monster.mesh, true);

        if (intersects.length > 0) {
          const distance = intersects[0].distance;
          if (distance < hitDistance) {
            hitDistance = distance;
            hitMonster = monster;
            hitCheese = null;
            hitIndex = -1;
            monsterHitIndex = index; // Store the index from the loop
            targetPos.copy(intersects[0].point);

            if (
              typeof DEBUG_SETTINGS !== "undefined" &&
              DEBUG_SETTINGS.logWeaponFire
            ) {
              console.log(
                `🎯 [WEAPON] Monster ${index} HIT! Distance: ${distance.toFixed(
                  2
                )}, Name: ${
                  monster.path?.split("/").pop() || "Unknown"
                }`
              );
            }
          }
        } else if (
          typeof DEBUG_SETTINGS !== "undefined" &&
          DEBUG_SETTINGS.logWeaponFire &&
          Math.random() < 0.01
        ) {
          const distanceToMonster = cameraPos.distanceTo(monsterWorldPos);
          console.log(
            `🔍 [WEAPON] Raycast missed monster ${index} (distance: ${distanceToMonster.toFixed(
              2
            )}, visible: ${monster.mesh.visible}, defeated: ${
              monster.defeated
            })`
          );
        }
      });
    }

    // Check for cheese hits (Step 1) only if no monster or Phoenix hit found
    if (
      level4RiddleState?.step1Active &&
      !hitMonster &&
      !hitPhoenix &&
      level4State?.cheeses
    ) {
      const cheeseCount = level4State.cheeses.length;
      let checkedCheeses = 0;

      level4State.cheeses.forEach((cheese, index) => {
        if (!cheese || !cheese.mesh || !cheese.mesh.visible) {
          checkedCheeses++;
          return;
        }

        const intersects = raycaster.intersectObject(cheese.mesh, true);
        if (intersects.length > 0) {
          const distance = intersects[0].distance;
          if (distance < hitDistance) {
            hitDistance = distance;
            hitCheese = cheese;
            hitIndex = index;
            targetPos.copy(intersects[0].point);

            if (
              typeof DEBUG_SETTINGS !== "undefined" &&
              DEBUG_SETTINGS.logLevel4CheeseWaves
            ) {
              console.log(
                `🎯 [WEAPON] Cheese ${index} HIT! Distance: ${distance.toFixed(
                  2
                )}`
              );
            }
          }
        }
        checkedCheeses++;
      });

      // Only log in special cheese-wave debug mode
      if (
        !hitCheese &&
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logLevel4CheeseWaves &&
        Math.random() < 0.1
      ) {
        console.log(
          `🔍 [WEAPON] Shot fired but no cheese hit. Active cheeses: ${cheeseCount}, checked: ${checkedCheeses}, Step1Active: ${level4RiddleState.step1Active}`
        );
      }
    }

    // Create visible bullet
    if (isPurple) {
      this._createSF13Bullet(bulletStartPos, rayDirection, targetPos);
    } else {
      this._createCheeseBullet(bulletStartPos, rayDirection, targetPos);
    }

    // Instant hit detection & outcome handling
    if (hitPhoenix && phoenixBossInstance) {
      // Phoenix boss hit - deal damage
      const damage = isPurple ? 75 : 50; // Purple bullets do more damage
      phoenixBossInstance.takeDamage(damage);
      this.onHitIndicator();
      const health = phoenixBossInstance.getHealth();

      // 🔥 Phoenix hit logs – only in boss debug mode
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logWeaponBossHits
      ) {
        console.log(
          `🔥 [WEAPON] Phoenix hit! Damage: ${damage}, Health: ${health.current}/${health.max}`
        );
      }
    } else if (hitSpiderMinion) {
      const damage = isPurple ? 75 : 50;
      hitSpiderMinion.takeDamage(damage);
      if (typeof this.onSpiderMinionHit === "function") {
        this.onSpiderMinionHit(hitSpiderMinion);
      }
      this.onHitIndicator();

      // 🕷️ Spider hit logs – only in spider debug mode
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logLevel6SpiderHits
      ) {
        console.log(
          `🕷️ [WEAPON] Spider minion hit! Damage: ${damage}, Health: ${hitSpiderMinion.health}/${hitSpiderMinion.maxHealth}`
        );
      }
    } else if (
      hitMonster &&
      level5MonsterHitIndex >= 0 &&
      (currentLevel === "LEVEL5" || currentLevel === LEVEL_IDS?.LEVEL5)
    ) {
      // Level 5 Monster hit processing (January 11, 2026)
      const level5StateNow = this.getLevel5State();
      const currentIndex = level5StateNow.monsters.indexOf(hitMonster);

      if (
        currentIndex >= 0 &&
        currentIndex < level5StateNow.monsters.length
      ) {
        const targetMonster = level5StateNow.monsters[currentIndex];
        if (targetMonster && !targetMonster.defeated) {
          // 🧪 Only log Level 5 monster hits in boss debug mode
          if (
            typeof DEBUG_SETTINGS !== "undefined" &&
            DEBUG_SETTINGS.logWeaponBossHits
          ) {
            console.log(
              "🎯 [WEAPON] Level 5 Monster hit! Index:",
              currentIndex,
              "Monster:",
              hitMonster.path?.split("/").pop() || "Unknown"
            );
          }

          this.onLevel5MonsterHit(currentIndex);
          this.onHitIndicator();
        } else {
          console.warn(
            `⚠️ [WEAPON] Level 5 Monster at index ${currentIndex} already defeated or doesn't exist`
          );
        }
      } else {
        console.warn(
          `⚠️ [WEAPON] Invalid Level 5 monster index ${currentIndex}, array length: ${level5StateNow.monsters.length}`
        );
      }
    } else if (hitMonster && monsterHitIndex >= 0) {
      // CRITICAL: Validate monster index for Level 4 monsters
      const currentIndex = level4State.monsters.indexOf(hitMonster);
      const finalIndex = currentIndex >= 0 ? currentIndex : monsterHitIndex;

      if (finalIndex >= 0 && finalIndex < level4State.monsters.length) {
        const targetMonster = level4State.monsters[finalIndex];
        if (targetMonster && !targetMonster.defeated) {
          // 🧪 Generic Level 4 monster hit logs – use weapon fire debug
          if (
            typeof DEBUG_SETTINGS !== "undefined" &&
            DEBUG_SETTINGS.logWeaponFire
          ) {
            console.log(
              "🎯 [WEAPON] Monster hit! Index:",
              finalIndex,
              "Monster:",
              hitMonster.path?.split("/").pop() || "Unknown"
            );
          }

          this.onMonsterHit(finalIndex);
          this.onHitIndicator();
        } else {
          console.warn(
            `⚠️ [WEAPON] Monster at index ${finalIndex} already defeated or doesn't exist`
          );
        }
      } else {
        console.warn(
          `⚠️ [WEAPON] Invalid monster index ${finalIndex}, array length: ${level4State.monsters.length}`
        );
      }
    } else if (hitCheese && hitIndex >= 0) {
      // 🎯 Cheese hit logs – only in cheese-wave debug mode
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logLevel4CheeseWaves
      ) {
        console.log(
          "🎯 [WEAPON] Cheese hit! Index:",
          hitIndex,
          "Calling onCheeseHit callback..."
        );
      }

      // CRITICAL: Call cheese hit callback
      if (typeof this.onCheeseHit === "function") {
        this.onCheeseHit(hitIndex);

        if (
          typeof DEBUG_SETTINGS !== "undefined" &&
          DEBUG_SETTINGS.logLevel4CheeseWaves
        ) {
          console.log(
            "✅ [WEAPON] onCheeseHit callback executed for index:",
            hitIndex
          );
        }
      } else {
        console.warn(
          "⚠️ [WEAPON] onCheeseHit callback is not a function!"
        );
      }
      this.onHitIndicator();
    } else if (
      level4RiddleState?.step1Active &&
      !hitCheese &&
      level4State?.cheeses &&
      level4State.cheeses.length > 0
    ) {
      // Debug: only occasionally log no-cheese shots in Step 1
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logLevel4CheeseWaves &&
        Math.random() < 0.1
      ) {
        console.log(
          "🔫 [WEAPON] Shot fired in Step 1 but no cheese hit. Active cheeses:",
          level4State.cheeses.length,
          "Step1Active:",
          level4RiddleState.step1Active
        );
      }
    } else if (
      level4RiddleState?.step2Active &&
      level4State?.monsters &&
      level4State.monsters.length > 0
    ) {
      // Debug: only occasionally log no-monster shots in Step 2
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logWeaponFire &&
        Math.random() < 0.05
      ) {
        console.log(
          "🔫 [WEAPON] Shot fired but no monster hit. Active monsters:",
          level4State.monsters.length,
          "Step2Active:",
          level4RiddleState.step2Active
        );
      }
    }

    // Apply recoil animation
    this.weaponRecoilOffset = 1.0;

    // Fire callback
    this.onWeaponFired(this.currentSlot, this.weaponSlots[this.currentSlot]);
  }

  /**
   * Check if shooting is allowed
   * @private
   */
  _canShoot() {
    const currentLevel = this.getCurrentLevel();
    const isLevel6 = currentLevel === "LEVEL6";

    // CRITICAL: Check if game is paused first
    if (this.isGamePaused()) {
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logWeaponFire &&
        (isLevel6 || Math.random() < 0.2)
      ) {
        console.log("🔫 [WEAPON] Shooting blocked: game is paused");
      }
      return false;
    }

    // CRITICAL: Check pointer lock state (required for shooting)
    if (!this.isPointerLocked()) {
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logWeaponFire &&
        (isLevel6 || Math.random() < 0.2)
      ) {
        console.log("🔫 [WEAPON] Shooting blocked: pointer not locked");
      }
      return false;
    }

    const isFirstPerson = this.isFirstPerson();

    // Only allow shooting in Level 4, Level 5, or Level 6, and first-person view
    if (
      currentLevel !== "LEVEL4" &&
      currentLevel !== "LEVEL5" &&
      currentLevel !== "LEVEL6"
    ) {
      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logWeaponFire &&
        (isLevel6 || Math.random() < 0.2)
      ) {
        console.log(
          "🔫 [WEAPON] Shooting blocked: wrong level",
          currentLevel,
          "(expected: 'LEVEL4', 'LEVEL5', or 'LEVEL6')"
        );
      }
      return false;
    }

// CRITICAL: First-person requirement
// Desktop: must be first-person
// Mobile: allow 3rd-person aiming/shooting (touch controls)
if (!isFirstPerson) {
  // Detect mobile from global flag defined in main.js
  const mobileAllowed =
    typeof isMobile !== "undefined" &&
    isMobile === true;

  if (!mobileAllowed) {
    if (
      typeof DEBUG_SETTINGS !== "undefined" &&
      DEBUG_SETTINGS.logWeaponFire &&
      (isLevel6 || Math.random() < 0.2)
    ) {
      console.log(
        "🔫 [WEAPON] Shooting blocked: not first-person (isFirstPerson:",
        isFirstPerson,
        ", isMobile:",
        typeof isMobile !== "undefined" ? isMobile : "unknown",
        ")"
      );
    }
    return false;
  }

  // On mobile we *allow* shooting in 3rd-person.
  if (
    typeof DEBUG_SETTINGS !== "undefined" &&
    DEBUG_SETTINGS.logWeaponFire &&
    (isLevel6 || Math.random() < 0.2)
  ) {
    console.log(
      "📱🔫 [WEAPON] Allowing 3rd-person shooting on mobile (isFirstPerson:",
      isFirstPerson,
      ", isMobile:",
      typeof isMobile !== "undefined" ? isMobile : "unknown",
      ")"
    );
  }
}


    if (
      isLevel6 &&
      typeof DEBUG_SETTINGS !== "undefined" &&
      DEBUG_SETTINGS.logWeaponFire
    ) {
      console.log("✅ [WEAPON] _canShoot() returns TRUE for Level 6");
    }
    return true;
  }

  /**
   * Check if weapon can fire
   */
  canFire() {
    if (this.isOverheated) {
      return false;
    }
    if (!this._canShoot()) {
      return false;
    }
    return true;
  }

  /**
   * Update heat system
   * @param {number} delta - Time delta in seconds
   */
  updateHeat(delta) {
    if (this.isOverheated) {
      // Check if cooldown period is over
      if (Date.now() - this.lastOverheatTime >= this.overheatCooldown) {
        this.isOverheated = false;
        this.weaponHeat = 0;

        if (
          typeof DEBUG_SETTINGS !== "undefined" &&
          DEBUG_SETTINGS.logWeaponFire
        ) {
          console.log(
            "❄️ [WEAPON] Weapon cooled down! Ready to fire again!"
          );
        }

        this.onHeatChanged(this.weaponHeat, this.maxHeat);
      }
      return;
    }

    // Natural heat decay when not shooting
    if (this.weaponHeat > 0) {
      this.weaponHeat = Math.max(0, this.weaponHeat - this.heatDecayRate);
      this.onHeatChanged(this.weaponHeat, this.maxHeat);
    }
  }

  /**
   * Generate heat (call when firing)
   * @param {number} amount - Heat amount to add
   */
  generateHeat(amount) {
    if (this.isOverheated) {
      return;
    }

    this.weaponHeat = Math.min(this.weaponHeat + amount, this.maxHeat);

    // Check for overheat
    if (this.weaponHeat >= this.maxHeat && !this.isOverheated) {
      this.isOverheated = true;
      this.lastOverheatTime = Date.now();
      this.onOverheated();

      if (
        typeof DEBUG_SETTINGS !== "undefined" &&
        DEBUG_SETTINGS.logWeaponFire
      ) {
        console.log("🔥 [WEAPON] Weapon overheated!");
      }
    }

    // Critical heat warning (at 80% heat)
    if (
      this.weaponHeat >= 120 &&
      this.weaponHeat < this.maxHeat &&
      typeof DEBUG_SETTINGS !== "undefined" &&
      DEBUG_SETTINGS.logWeaponFire
    ) {
      console.log(
        "⚠️ [WEAPON] WARNING: Weapon heat critical! Consider cooling down..."
      );
    }

    this.onHeatChanged(this.weaponHeat, this.maxHeat);
  }

  /**
   * Play shoot sound
   */
  playShootSound() {
    if (this.audioReady && this.shootSound) {
      // Ensure audio context is resumed
      if (this.resumeAudioContextIfNeeded) {
        this.resumeAudioContextIfNeeded();
      }
      // Stop any currently playing sound and restart it (for rapid firing)
      if (this.shootSound.isPlaying) {
        this.shootSound.stop();
      }
      this.shootSound.play();
    }
  }

  /**
   * Play triple shot sound
   */
  playTripleShotSound() {
    if (this.tripleShotAudioReady && this.tripleShotSound) {
      if (this.tripleShotSound.isPlaying) {
        this.tripleShotSound.stop();
      }
      this.tripleShotSound.play();
    }
  }

  /**
   * Update weapon system (call in animate loop)
   * @param {number} delta - Time delta in seconds
   */
  update(delta) {
    // Update heat system
    this.updateHeat(delta);

    // Update triple shot system
    this._updateTripleShot(delta);

    // Update bullets
    this._updateBullets(delta);

    // Update recoil
    if (this.weaponRecoilOffset > 0) {
      this.weaponRecoilOffset = Math.max(
        0,
        this.weaponRecoilOffset - delta * 5
      );
    }
  }

  /**
   * Update triple shot system
   * @private
   */
  _updateTripleShot(delta) {
    if (!this.tripleShotActive || this.tripleShotBulletsRemaining <= 0) {
      this.tripleShotActive = false;
      return;
    }

    const currentTime = performance.now() / 1000;
    const burstDelay = 0.11; // 110ms between shots

    if (
      currentTime >= this.tripleShotNextBulletTime &&
      this.tripleShotBulletsRemaining > 0
    ) {
      this.tripleShotBulletsRemaining--;
      this.tripleShotNextBulletTime = currentTime + burstDelay;

      // Fire bullet
      this._fireSingleShot(true); // Purple bullet

      // Play SF13 sound
      this.playTripleShotSound();

      // If all bullets fired, end burst
      if (this.tripleShotBulletsRemaining <= 0) {
        this.tripleShotActive = false;
      }
    }
  }

  /**
   * Update all active bullets
   * @private
   */
  _updateBullets(delta) {
    const currentLevel = this.getCurrentLevel();
    const level4RiddleState = this.getLevel4RiddleState();
    const level4State = this.getLevel4State();

    for (let i = this.bullets.length - 1; i >= 0; i--) {
      const bullet = this.bullets[i];

      // Update lifetime
      bullet.lifetime += delta;

      // Check if bullet should be removed
      if (bullet.lifetime >= bullet.maxLifetime || bullet.hit) {
        if (bullet.mesh) {
          if (bullet.mesh.parent) {
            bullet.mesh.parent.remove(bullet.mesh);
          } else if (this.scene.children.includes(bullet.mesh)) {
            this.scene.remove(bullet.mesh);
          }
          if (bullet.mesh.geometry) bullet.mesh.geometry.dispose();
          if (bullet.mesh.material) {
            if (Array.isArray(bullet.mesh.material)) {
              bullet.mesh.material.forEach((mat) => mat.dispose());
            } else {
              bullet.mesh.material.dispose();
            }
          }
        }
        this.bullets.splice(i, 1);
        continue;
      }

      // Move bullet forward
      const moveDistance = bullet.speed * delta;
      bullet.traveled += moveDistance;

      // Update position
      bullet.mesh.position.addScaledVector(bullet.direction, moveDistance);

      // Rotate bullet for visual effect
      bullet.mesh.rotation.x += delta * 10;
      bullet.mesh.rotation.y += delta * 8;

      // Check if bullet reached target or max distance
      if (bullet.traveled >= bullet.distance) {
        bullet.hit = true;
      }

      // Visual collision check with monsters (Step 2)
      if (
        level4RiddleState?.step2Active &&
        !bullet.hit &&
        level4State?.monsters
      ) {
        level4State.monsters.forEach((monster) => {
          if (
            !monster ||
            !monster.mesh ||
            !monster.mesh.visible ||
            monster.defeated
          )
            return;

          const distanceToMonster = bullet.mesh.position.distanceTo(
            monster.mesh.position
          );
          if (distanceToMonster < 2.0) {
            bullet.hit = true;
          }
        });
      }

      // Phoenix Boss hit detection (Level 6)
      if (
        currentLevel === this.LEVEL_IDS?.LEVEL6 &&
        !bullet.hit &&
        window.phoenixBoss
      ) {
        if (
          window.phoenixBoss.checkHit &&
          window.phoenixBoss.checkHit(bullet.mesh.position)
        ) {
          bullet.hit = true;
          const damage = bullet.isPurple ? 15 : 10; // SF13 does more damage
          window.phoenixBoss.takeDamage(damage);

          if (
            typeof DEBUG_SETTINGS !== "undefined" &&
            DEBUG_SETTINGS.logWeaponBossHits
          ) {
            console.log(
              `💥 [WEAPON] Bullet hit Phoenix Boss! Damage: ${damage}`
            );
          }
        }
      }
    }
  }

  /**
   * Create SF13 bullet (purple)
   * @private
   */
  _createSF13Bullet(startPos, direction, targetPos) {
    const bulletGeometry = new THREE.SphereGeometry(
      this.bulletSize,
      8,
      8
    );
    const purpleColor = new THREE.Color(0x7c3aed);

    const bulletMaterial = new THREE.MeshStandardMaterial({
      color: purpleColor,
      emissive: purpleColor,
      emissiveIntensity: 1.2,
      metalness: 0.5,
      roughness: 0.3,
    });

    const bulletMesh = new THREE.Mesh(bulletGeometry, bulletMaterial);
    bulletMesh.position.copy(startPos);

    bulletMesh.visible = true;
    bulletMesh.renderOrder = 1000;
    bulletMesh.castShadow = true;
    bulletMesh.receiveShadow = false;

    if (!this.scene) {
      console.error("❌ [WEAPON] Scene is null! Cannot add bullet.");
      return null;
    }

    this.scene.add(bulletMesh);

    if (
      typeof DEBUG_SETTINGS !== "undefined" &&
      DEBUG_SETTINGS.logWeaponFire
    ) {
      if (!this.scene.children.includes(bulletMesh)) {
        console.error(
          "❌ [WEAPON] Failed to add SF13 bullet to scene!"
        );
      } else {
        console.log(
          "💥 [WEAPON] SF13 bullet created at:",
          startPos.toArray().map((n) => n.toFixed(2)),
          "Scene children:",
          this.scene.children.length
        );
      }
    }

    const directionVec = new THREE.Vector3().subVectors(
      targetPos,
      startPos
    );
    const distance = directionVec.length();
    directionVec.normalize();

    const bullet = {
      mesh: bulletMesh,
      material: bulletMaterial,
      direction: directionVec,
      speed: this.bulletSpeed,
      distance: distance,
      traveled: 0,
      lifetime: 0,
      maxLifetime: this.bulletLifetime,
      targetPos: targetPos.clone(),
      hit: false,
      isPurple: true,
    };

    this.bullets.push(bullet);
    return bullet;
  }

  /**
   * Create cheese bullet (yellow)
   * @private
   */
  _createCheeseBullet(startPos, direction, targetPos) {
    const bulletGeometry = new THREE.SphereGeometry(
      this.bulletSize,
      8,
      8
    );

    const primaryTexturePath = this.resolveAssetPath(
      "textures/blocks/cheese-bullet-small.png"
    );
    const fallbackTexturePath = this.resolveAssetPath(
      "textures/blocks/yellow-cheese.png"
    );

    let cheeseTexture = null;
    if (this.textureCache && this.textureCache.has(primaryTexturePath)) {
      const cached = this.textureCache.get(primaryTexturePath);
      if (
        cached &&
        cached.image &&
        cached.image.complete &&
        cached.image.width > 0
      ) {
        cheeseTexture = cached;
      }
    }

    const fallbackTexture = this.loadTexture
      ? this.loadTexture(fallbackTexturePath)
      : null;

    const bulletMaterial = new THREE.MeshStandardMaterial({
      map: cheeseTexture || fallbackTexture,
      emissive: new THREE.Color(0xffe066),
      emissiveIntensity: 0.8,
      metalness: 0.3,
      roughness: 0.7,
    });

    // Load primary texture asynchronously if not cached
    if (!cheeseTexture && this.loadTexture && this.textureCache) {
      const textureLoader = new THREE.TextureLoader();
      textureLoader.load(
        primaryTexturePath,
        (texture) => {
          if (texture && texture.image) {
            texture.needsUpdate = true;
            texture.flipY = false;
            bulletMaterial.map = texture;
            bulletMaterial.needsUpdate = true;

            const bullet = this.bullets.find(
              (b) => b.material === bulletMaterial
            );
            if (bullet && bullet.mesh) {
              bullet.mesh.material = bulletMaterial;
              bullet.mesh.material.needsUpdate = true;
            }

            this.textureCache.set(primaryTexturePath, texture);
          }
        },
        undefined,
        (error) => {
          console.warn(
            "⚠️ [WEAPON] Cheese bullet texture not found, using fallback:",
            error
          );
        }
      );
    }

    const bulletMesh = new THREE.Mesh(bulletGeometry, bulletMaterial);
    bulletMesh.position.copy(startPos);

    bulletMesh.visible = true;
    bulletMesh.renderOrder = 1000;
    bulletMesh.castShadow = true;
    bulletMesh.receiveShadow = false;

    if (!this.scene) {
      console.error("❌ [WEAPON] Scene is null! Cannot add bullet.");
      return null;
    }

    this.scene.add(bulletMesh);

    if (
      typeof DEBUG_SETTINGS !== "undefined" &&
      DEBUG_SETTINGS.logWeaponFire
    ) {
      if (!this.scene.children.includes(bulletMesh)) {
        console.error(
          "❌ [WEAPON] Failed to add Cheese bullet to scene!"
        );
      } else {
        console.log(
          "💥 [WEAPON] Cheese bullet created at:",
          startPos.toArray().map((n) => n.toFixed(2)),
          "Scene children:",
          this.scene.children.length
        );
      }
    }

    const directionVec = new THREE.Vector3().subVectors(
      targetPos,
      startPos
    );
    const distance = directionVec.length();
    directionVec.normalize();

    const bullet = {
      mesh: bulletMesh,
      material: bulletMaterial,
      direction: directionVec,
      speed: this.bulletSpeed,
      distance: distance,
      traveled: 0,
      lifetime: 0,
      maxLifetime: this.bulletLifetime,
      targetPos: targetPos.clone(),
      hit: false,
      isPurple: false,
    };

    this.bullets.push(bullet);
    return bullet;
  }

  /**
   * Get current weapon heat
   */
  getHeat() {
    return this.weaponHeat;
  }

  /**
   * Get max heat
   */
  getMaxHeat() {
    return this.maxHeat;
  }

  /**
   * Check if weapon is overheated
   */
  isWeaponOverheated() {
    return this.isOverheated;
  }

  /**
   * Get current weapon slot
   */
  getCurrentSlot() {
    return this.currentSlot;
  }

  /**
   * Get current weapon info
   */
  getCurrentWeapon() {
    return this.weaponSlots[this.currentSlot] || null;
  }

  /**
   * Reset weapon system (for level changes)
   */
  reset() {
    this.weaponHeat = 0;
    this.isOverheated = false;
    this.currentSlot = 1;
    this.tripleShotActive = false;
    this.tripleShotBulletsRemaining = 0;
    this.removeWeapon();
  }

  /**
   * Clean up all bullets
   */
  cleanupBullets() {
    this.bullets.forEach((bullet) => {
      if (bullet.mesh && bullet.mesh.parent) {
        this.scene.remove(bullet.mesh);
        bullet.mesh.geometry.dispose();
        bullet.mesh.material.dispose();
      }
    });
    this.bullets.length = 0;
  }

  /**
   * Dispose weapon system (cleanup)
   */
  dispose() {
    // Remove all weapons from camera
    this.removeWeapon();

    // Clean up bullets
    this.cleanupBullets();

    // Clear cache
    this.weapons = {};

    // Reset state
    this.weaponViewmodel = null;
    this.currentSlot = 1;
    this.weaponHeat = 0;
    this.isOverheated = false;
    this.tripleShotActive = false;
    this.tripleShotBulletsRemaining = 0;

    console.log("🗑️ [WEAPON] Weapon system disposed");
  }
}

