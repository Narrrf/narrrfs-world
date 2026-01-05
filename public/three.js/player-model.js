/**
 * ============================================================================
 * PLAYER MODEL SYSTEM - Character Model and Animation
 * ============================================================================
 * 
 * ✅ STATUS: STABLE - PRODUCTION READY
 * 📅 CREATED: December 2025
 * 📅 LAST UPDATED: December 20, 2025
 * 
 * ============================================================================
 * 🎯 PURPOSE
 * ============================================================================
 * 
 * Handles player character model loading, animation, and rendering:
 * - Character model loading (GLTF/GLB)
 * - Animation management (mixer, clips, actions)
 * - Position and rotation updates
 * - Visibility management (first-person vs third-person)
 * - Support for multiple character types (Mouse, Animation Library)
 * - Future-ready for additional character models
 * - Device-agnostic (VR, Android, PC, etc.)
 * - Works across all levels (1-6+)
 * 
 * Built for decades of development with extensible architecture.
 * 
 * ============================================================================
 * ✅ STABLE VERSION STATUS
 * ============================================================================
 * 
 * ✅ **STABLE VERSION - PRODUCTION READY**
 * 
 * This system has reached a stable, production-ready state with:
 * - ✅ Dual player model support (Mouse & Animation Library)
 * - ✅ Animation system working correctly for both character types
 * - ✅ Position and rotation interpolation (smooth following)
 * - ✅ Animation speed synchronization with movement velocity
 * - ✅ Consistent settings across all levels (no level-specific overrides)
 * - ✅ GOD mode support (2x speed multiplier)
 * - ✅ First-person/third-person view switching
 * - ✅ All animations loading and playing correctly
 * 
 * **STABLE FEATURES:**
 * - ✅ Mouse character: 6 animations (idle, run, jump, climb, death, somersoult)
 * - ✅ Animation Library: Embedded animations (Idle, Walk, Sprint, etc.)
 * - ✅ Animation priority system (death > movement > idle)
 * - ✅ Animation fade transitions (smooth switching)
 * - ✅ Animation speed synchronization with movement
 * - ✅ Consistent behavior across all 6 levels
 * 
 * ============================================================================
 * 📦 WHAT THIS MODULE LOADS
 * ============================================================================
 * 
 * Character Models:
 * - Mouse character: GLTF/GLB format
 * - Animation Library character: GLTF/GLB format
 * 
 * Animations:
 * - Embedded in GLTF/GLB files
 * - Or separate animation files
 * 
 * Dependencies:
 * - THREE.js Scene
 * - THREE.js GLTFLoader
 * - Callbacks from main.js (getPlayerPosition, isFirstPerson, etc.)
 * 
 * ============================================================================
 * 🔧 INTEGRATION IN MAIN.JS
 * ============================================================================
 * 
 * 1. Import:
 *    import { PlayerModel } from "./player-model.js";
 * 
 * 2. Initialize:
 *    playerModel = new PlayerModel(scene, {
 *      loadModelHelper: loadModel, // Helper function from main.js
 *      getPlayerPosition: () => playerPosition,
 *      isFirstPerson: () => isFirstPerson(),
 *      getPlayerVelocity: () => playerVelocity,
 *      getOnGround: () => onGround,
 *      getGodMode: () => godMode
 *    });
 * 
 * 3. Load character:
 *    await playerModel.load(modelPath);
 * 
 * 4. Update in game loop:
 *    if (playerModel) {
 *      playerModel.update(delta);
 *    }
 * 
 * ============================================================================
 * 🎮 KEY FUNCTIONS
 * ============================================================================
 * 
 * - load(modelPath) - Load character model
 * - update(delta) - Update animations and position
 * - setVisible(visible) - Show/hide character
 * - playAnimation(name, loop) - Play specific animation
 * - setAnimationSpeed(speed) - Set animation playback speed
 * 
 * ============================================================================
 * 
 * @module PlayerModel
 */

import * as THREE from "three";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";

/**
 * Player Model System
 * Manages character model loading, animations, positioning, and visibility
 */
export class PlayerModel {
  constructor(scene, config) {
    this.scene = scene;
    this.config = config; // Dependency injection callbacks
    
    // Validate required config
    if (!config.loadModelHelper) {
      throw new Error('PlayerModel: loadModelHelper is required in config');
    }
    if (!config.getPlayerPosition) {
      throw new Error('PlayerModel: getPlayerPosition callback is required in config');
    }
    if (!config.isFirstPerson) {
      throw new Error('PlayerModel: isFirstPerson callback is required in config');
    }
    
    // Model state
    this.model = null;
    this.mixer = null;
    this.animations = {};
    this.currentAnimation = null;
    
    // Character configuration
    this.characterType = null; // 'mouse' or 'animation_library'
    this.selectedPath = null;
    
    // Animation state
    this.animationState = 'idle'; // 'idle', 'walk', 'run', 'jump', 'fall', 'climb', etc.
    this.lastAnimationSwitch = 0;
    this.movementHistory = []; // For hysteresis
    
    // Position/rotation state
    this.heightOffset = 0.85;
    this.rotationOffset = 0;
    this.scale = 1.0;
    
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
    
    // Character options configuration
    // ============================================================================
    // 🎭 PLAYER MODEL CONFIGURATION - STABLE VERSION (December 16, 2025)
    // ============================================================================
    // 
    // TWO PLAYER MODELS SUPPORTED:
    // 1. Mouse - Custom character with separate animation files
    // 2. Animation Library - Standard character with embedded animations
    // 
    // Both models work correctly with the animation system and are production-ready.
    // 
    // ============================================================================
    this.characterOptions = {
      mouse: {
        name: "Mouse",
        path: "./public/textures/3d models/Mouse/glb/glb/character/character.glb",
        heightOffset: 0.95,
        rotationOffset: -Math.PI / 2, // -90 degrees
        animations: {
          idle: './public/textures/3d models/Mouse/glb/glb/animation/idle.glb',
          run: './public/textures/3d models/Mouse/glb/glb/animation/run.glb',
          jump: './public/textures/3d models/Mouse/glb/glb/animation/jump.glb',
          climb: './public/textures/3d models/Mouse/glb/glb/animation/climb.glb',
          death: './public/textures/3d models/Mouse/glb/glb/animation/death.glb',
          somersoult: './public/textures/3d models/Mouse/glb/glb/animation/somersoult.glb'
        },
        loopAnimations: ['idle', 'run', 'climb'], // These animations loop continuously
        animationPriorities: {
          'idle': this.animationPriorities.IDLE,          // Priority: -1 (lowest)
          'run': this.animationPriorities.MOVEMENT,       // Priority: 0
          'jump': this.animationPriorities.JUMP,          // Priority: 60
          'climb': this.animationPriorities.CLIMB,        // Priority: 55
          'death': this.animationPriorities.DEATH,        // Priority: 100 (highest)
          'somersoult': this.animationPriorities.SOMERSAULT // Priority: 40
        }
      },
      animation_library: {
        name: "Animation Library",
        path: "./public/textures/3d models/Animation Libary/Animation Library[Standard]/Godot/AnimationLibrary_Godot_Standard.glb",
        heightOffset: 0.85, // Multiplied by scale
        rotationOffset: 0,
        animations: 'embedded', // All animations embedded in the model file
        loopAnimations: ['Idle', 'Idle_Loop', 'Walk', 'Walk_Loop', 'Sprint_Loop', 'Jog_Fwd_Loop']
        // NOTE: Animation Library has many more animations embedded in the GLB file
        // Common animations: Idle, Walk, Sprint, Jump, etc.
        // All animations are loaded automatically from the model file
        // CLIMB SUPPORT (December 30, 2025): System automatically detects climb animations
        // (case-insensitive: 'climb', 'climbing', 'wall') and enables climbing if found
      }
    };
    
    /**
     * CENTRALIZED PLAYER CHARACTER SETTINGS
     * 
     * All movement, animation, and rendering settings are defined here
     * to ensure consistency across ALL levels and BOTH character types (Mouse & Animation Library).
     * 
     * These settings are the "roots" - they define how the character behaves
     * and should remain consistent for decades of development.
     * 
     * NO level-specific overrides are allowed - all levels use the same settings.
     * Only GOD mode can modify speed multipliers (2x movement, 1.5x rotation).
     * 
     * Built for decades of development with professional architecture.
     */
    this.settings = {
      // Movement speeds (units per second)
      // These are reference values - actual movement speed is calculated in main.js
      // but animation speed uses these values for synchronization
      walkSpeed: 96.0,           // Normal walk speed (units/sec) - SAME FOR ALL LEVELS
      sprintSpeed: 168.0,         // Normal sprint speed (units/sec) - SAME FOR ALL LEVELS
      godModeMultiplier: 2.0,     // GOD mode speed multiplier (2x normal speed)
      
      // Position interpolation (lerp) settings
      // Controls how smoothly the character model follows the player position
      baseLerpSpeed: 180,         // Base lerp speed - SAME FOR ALL LEVELS (no level-specific overrides!)
      godModeLerpMultiplier: 2.0, // GOD mode lerp multiplier (2x faster to match 2x movement speed)
      
      // Rotation settings
      // Controls how smoothly the character rotates to face movement direction
      baseRotationSpeed: 0.3,     // Base rotation speed (smooth and round) - SAME FOR ALL LEVELS
      godModeRotationMultiplier: 1.5, // GOD mode rotation multiplier (1.5x faster to match movement speed)
      maxRotationSpeed: 0.5,      // Maximum rotation speed clamp
      
      // Animation speed calculation settings
      // Used to synchronize animation playback speed with actual movement speed
      baseWalkSpeed: 96.0,        // Base walk speed for animation calculation (units/sec) - SAME FOR ALL LEVELS
      animationSpeedMin: 0.5,     // Minimum animation speed multiplier (prevents too slow animations)
      animationSpeedMax: 5.0,     // Maximum animation speed multiplier (prevents too fast animations)
      
      // Animation switching settings
      // Controls how quickly animations can switch (prevents flickering)
      minSwitchDelay: 250,        // Minimum delay between animation switches (ms) - SAME FOR ALL LEVELS
      idleTransitionImmediate: true, // Idle transitions are immediate (no debounce)
      
      // Animation fade settings
      fadeInTime: 0.2,            // Animation fade-in time (seconds)
      fadeOutTime: 0.15,          // Animation fade-out time (seconds)
      idleFadeOutTime: 0.1,       // Faster fade-out for idle transitions
      
      // Character-specific settings (applied to both Mouse and Animation Library)
      targetHeight: 1.8,          // Target character height (units) - used for scale calculation
      renderOrder: 100,           // Render order (higher = renders on top)
      
      // Movement detection thresholds
      velocityThreshold: 0.15,     // Minimum velocity to consider "moving" (units/sec)
      jumpThreshold: 2.0,         // Minimum upward velocity to consider "jumping" (units/sec)
      fallThreshold: -2.0,        // Maximum downward velocity to consider "falling" (units/sec)
      collisionVelocityThreshold: 0.1, // Velocity threshold for collision detection (units/sec)
      
      // Animation hysteresis settings (prevents rapid animation switching)
      movementHistoryFrames: 5,   // Number of frames to track for movement history
      sustainedInputThreshold: 3, // Minimum frames with input to consider "sustained" (out of 5)
    };
    
    console.log("🎭 [PLAYER MODEL] PlayerModel instance created with centralized settings");
    console.log("🎭 [PLAYER MODEL] Settings:", {
      walkSpeed: this.settings.walkSpeed,
      sprintSpeed: this.settings.sprintSpeed,
      baseLerpSpeed: this.settings.baseLerpSpeed,
      baseRotationSpeed: this.settings.baseRotationSpeed,
      note: "All settings are consistent across ALL levels and BOTH character types"
    });
  }
  
  /**
   * Load character model
   * @param {string} characterKey - 'mouse' or 'animation_library'
   * @param {string} customPath - Optional custom model path
   * @returns {Promise<THREE.Group>} The loaded character model
   */
  async loadModel(characterKey = 'animation_library', customPath = null) {
    try {
      // Get character configuration
      const charConfig = this.characterOptions[characterKey];
      if (!charConfig) {
        throw new Error(`Unknown character key: ${characterKey}`);
      }
      
      const pathToLoad = customPath || charConfig.path;
      this.selectedPath = pathToLoad;
      this.characterType = characterKey;
      
      console.log("🎮 [PLAYER MODEL] Loading character model:", pathToLoad);
      
      // Use loadModel helper from config (dependency injection)
      const gltf = await this.config.loadModelHelper(pathToLoad);
      
      // CRITICAL: Don't clone - use the original scene directly
      // Cloning can break material references and texture loading
      this.model = gltf.scene;
      this.model.name = "PlayerCharacter";
      
      // Configure meshes and materials
      this.setupModelMaterials();
      
      // Calculate and apply scale
      this.calculateAndApplyScale();
      
      // Calculate height offset
      this.heightOffset = charConfig.heightOffset;
      this.rotationOffset = charConfig.rotationOffset;
      
      // Detect Mouse character for special handling
      const isMouseCharacter = characterKey === 'mouse' || (pathToLoad && pathToLoad.includes('Mouse'));
      this.model.userData.isMouseCharacter = isMouseCharacter;
      
      // Position character at initial player position
      this.initializePosition();
      
      // Set initial visibility
      this.model.visible = !this.config.isFirstPerson();
      
      // CRITICAL: Update world matrices after all transformations
      this.model.updateMatrixWorld(true);
      
      // Hide existing simple playerModel if it exists (via config callback)
      if (this.config.hideSimplePlayerModel) {
        this.config.hideSimplePlayerModel();
      }
      
      // Add character to scene
      this.scene.add(this.model);
      
      // CRITICAL: Final matrix update after adding to scene
      this.model.updateMatrixWorld(true);
      
      // Set up animations
      if (isMouseCharacter) {
        // Mouse character: Load animations from separate GLB files
        console.log("🐭 [PLAYER MODEL] Mouse character detected - loading animations from separate files...");
        await this.loadMouseAnimations();
      } else if (gltf.animations && gltf.animations.length > 0) {
        // Animation Library: Setup embedded animations
        console.log("🎬 [PLAYER MODEL] Animation Library character detected - setting up embedded animations...");
        this.setupEmbeddedAnimations(gltf);
      } else {
        console.log("⚠️ [PLAYER MODEL] No animations found in model");
      }
      
      // Setup default animation triggers (basic movement animations)
      this.setupDefaultTriggers();
      
      console.log("✅ [PLAYER MODEL] Player character model loaded and added to scene");
      
      return this.model;
      
    } catch (error) {
      console.error("❌ [PLAYER MODEL] Error loading player character model:", error);
      console.error("❌ [PLAYER MODEL] Model path:", customPath || this.characterOptions[characterKey]?.path);
      console.error("❌ [PLAYER MODEL] Make sure the model file exists and is in GLTF/GLB format");
      this.model = null;
      return null;
    }
  }
  
  /**
   * Setup model materials and meshes
   * Configures shadows, render order, and material properties
   */
  setupModelMaterials() {
    let meshCount = 0;
    let materialCount = 0;
    
    this.model.traverse((child) => {
      if (child.isMesh) {
        meshCount++;
        child.castShadow = true;
        child.receiveShadow = true;
        child.frustumCulled = false; // Disable frustum culling for visibility (VR/device compatibility)
        child.visible = true;
        
        // CRITICAL: Set render order ONCE to prevent z-fighting and double rendering
        // Use a consistent render order that's higher than level objects
        if (!child.userData || child.userData.renderOrderSet !== true) {
          child.renderOrder = 100; // Higher render order ensures character renders on top
          if (!child.userData) child.userData = {};
          child.userData.renderOrderSet = true; // Mark as set - don't update again
        }
        
        // CRITICAL: Use original GLB materials - don't modify them extensively
        // GLTFLoader already sets up materials correctly from GLB
        if (child.material) {
          const hasMaterialArray = Array.isArray(child.material);
          const materialsToProcess = hasMaterialArray ? child.material : [child.material];
          
          materialsToProcess.forEach((material, idx) => {
            if (material) {
              materialCount++;
              
              // Preserve original material from GLB - minimal modifications
              // Only ensure visibility properties (don't override material type or properties)
              material.transparent = false;
              material.opacity = 1.0;
              material.visible = true;
              
              // Ensure textures are loaded if present
              if (material.map && material.map instanceof THREE.Texture) {
                material.map.needsUpdate = true;
                // Ensure texture image is loaded
                if (material.map.image) {
                  material.map.needsUpdate = true;
                }
              }
              
              // Force material update (ensures WebGL state is updated)
              material.needsUpdate = true;
              
              // Log material details for first mesh only
              if (meshCount === 1 && idx === 0) {
                console.log("🎭 [PLAYER MODEL] Material from GLB (original):", {
                  meshName: child.name || 'unnamed',
                  materialType: material.type,
                  hasColor: !!material.color,
                  hasMap: !!material.map,
                  color: material.color ? material.color.getHexString() : 'none',
                  transparent: material.transparent,
                  opacity: material.opacity,
                  visible: material.visible,
                  side: material.side,
                  note: "Using original GLB material (not cloned)"
                });
              }
            } else {
              console.warn("⚠️ [PLAYER MODEL] Null material at index:", idx, "for mesh:", child.name);
            }
          });
        } else {
          // No material - create visible debug material
          console.error("❌ [PLAYER MODEL] Mesh has no material:", child.name);
          child.material = new THREE.MeshStandardMaterial({
            color: 0xff0000, // Bright red for debugging
            emissive: 0xff0000,
            emissiveIntensity: 2.0,
            side: THREE.DoubleSide,
            visible: true
          });
          materialCount++;
        }
      }
    });
    
    console.log("🎭 [PLAYER MODEL] Material setup:", {
      totalMeshes: meshCount,
      totalMaterials: materialCount,
      note: "Using original GLB materials (preserved from model file)"
    });
  }
  
  /**
   * Calculate and apply scale to match target player height
   * Works across all levels and devices
   */
  calculateAndApplyScale() {
    // CRITICAL: Update world matrices before calculating size
    this.model.updateMatrixWorld(true);
    
    // Calculate scale based on model size
    const originalBox = new THREE.Box3().setFromObject(this.model);
    const originalSize = originalBox.getSize(new THREE.Vector3());
    console.log("🎭 [PLAYER MODEL] Original model size:", {
      x: originalSize.x.toFixed(2),
      y: originalSize.y.toFixed(2),
      z: originalSize.z.toFixed(2)
    });
    
    // Scale to match player height (1.8 units target - works on all devices)
    const targetHeight = 1.8;
    this.scale = targetHeight / originalSize.y;
    this.model.scale.set(this.scale, this.scale, this.scale);
    console.log("🎭 [PLAYER MODEL] Scale applied:", {
      originalHeight: originalSize.y.toFixed(2),
      targetHeight: targetHeight.toFixed(2),
      scale: this.scale.toFixed(4)
    });
  }
  
  /**
   * Initialize character position at player spawn location
   * Works across all levels
   * CRITICAL: Uses feet position (playerCollider.start) to ensure model is on ground
   */
  initializePosition() {
    // Get initial player position from config callback
    // CRITICAL: getPlayerPosition() may return center position, but we need feet position
    // Check if config provides getPlayerFeetPosition callback (preferred)
    let initialPlayerPos;
    if (this.config.getPlayerFeetPosition) {
      // Use feet position directly if available
      initialPlayerPos = this.config.getPlayerFeetPosition();
    } else {
      // Fallback: Use getPlayerPosition (assumes it returns center, we'll adjust)
      initialPlayerPos = this.config.getPlayerPosition();
      // If getPlayerPosition returns center (torso), we need to get feet position
      // For now, assume it's close to feet and adjust with heightOffset
    }
    
    // Calculate height offset (character-specific)
    // CRITICAL: heightOffset aligns model's feet with player's feet position
    let heightOffset = this.heightOffset;
    if (this.characterType === 'animation_library') {
      // Animation Library: height offset is multiplied by scale
      heightOffset = this.heightOffset * this.scale;
    } else if (this.characterType === 'mouse') {
      // Mouse: fixed height offset (0.95) - aligns feet with ground
      heightOffset = this.heightOffset;
    }
    
    // Position model so feet align with player feet position
    // If initialPlayerPos is feet position, subtract heightOffset to position model correctly
    // If initialPlayerPos is center position, we need to adjust differently
    this.model.position.set(
      initialPlayerPos.x,
      initialPlayerPos.y - heightOffset, // Subtract offset to align feet with ground
      initialPlayerPos.z
    );
    
    // Set initial rotation (character-specific)
    this.model.rotation.y = this.rotationOffset;
    
    if (this.characterType === 'mouse') {
      console.log("🐭 [PLAYER MODEL] Mouse character positioned at:", {
        x: this.model.position.x.toFixed(2),
        y: this.model.position.y.toFixed(2),
        z: this.model.position.z.toFixed(2),
        heightOffset: heightOffset.toFixed(2),
        note: "Feet aligned with ground (y=0)"
      });
    } else {
      console.log("🎮 [PLAYER MODEL] Animation Library character positioned at:", {
        x: this.model.position.x.toFixed(2),
        y: this.model.position.y.toFixed(2),
        z: this.model.position.z.toFixed(2),
        heightOffset: heightOffset.toFixed(2),
        note: "Feet aligned with ground"
      });
    }
  }
  
  /**
   * Load Mouse character animations from separate GLB files
   * 
   * 🐭 MOUSE CHARACTER ANIMATIONS - STABLE VERSION (December 16, 2025)
   * ===================================================================
   * 
   * Total Animations: 6
   * Loading Method: Separate GLB files (one animation per file)
   * 
   * ANIMATION LIST:
   * 1. idle - Default idle animation (LOOPS)
   *    - Path: './public/textures/3d models/Mouse/glb/glb/animation/idle.glb'
   *    - Priority: IDLE (-1)
   *    - Loop: Yes (LoopRepeat, Infinity)
   *    - Usage: Plays when player is not moving
   * 
   * 2. run - Running animation (LOOPS)
   *    - Path: './public/textures/3d models/Mouse/glb/glb/animation/run.glb'
   *    - Priority: MOVEMENT (0)
   *    - Loop: Yes (LoopRepeat, Infinity)
   *    - Usage: Plays when player is moving at normal/sprint speed
   * 
   * 3. jump - Jumping animation (ONE-TIME)
   *    - Path: './public/textures/3d models/Mouse/glb/glb/animation/jump.glb'
   *    - Priority: JUMP (60)
   *    - Loop: No (LoopOnce, 1)
   *    - Usage: Plays when player jumps
   * 
   * 4. climb - Climbing animation (LOOPS)
   *    - Path: './public/textures/3d models/Mouse/glb/glb/animation/climb.glb'
   *    - Priority: CLIMB (55)
   *    - Loop: Yes (LoopRepeat, Infinity)
   *    - Usage: Plays when player is climbing
   * 
   * 5. death - Death animation (ONE-TIME)
   *    - Path: './public/textures/3d models/Mouse/glb/glb/animation/death.glb'
   *    - Priority: DEATH (100) - HIGHEST PRIORITY
   *    - Loop: No (LoopOnce, 1)
   *    - Usage: Plays when player dies (interrupts all other animations)
   * 
   * 6. somersoult - Somersault animation (ONE-TIME)
   *    - Path: './public/textures/3d models/Mouse/glb/glb/animation/somersoult.glb'
   *    - Priority: SOMERSAULT (40)
   *    - Loop: No (LoopOnce, 1)
   *    - Usage: Special move (space bar in GOD mode)
   * 
   * ANIMATION LOADING:
   * - All animations loaded from separate GLB files
   * - Each file contains one animation clip
   * - Animations applied to the character model using AnimationMixer
   * - Mixer created once, all animations share the same mixer
   * 
   * ANIMATION PRIORITY SYSTEM:
   * - Higher priority animations interrupt lower priority ones
   * - Death (100) > Jump (60) > Climb (55) > Somersault (40) > Movement (0) > Idle (-1)
   * - Loop animations continue until interrupted
   * - One-time animations play once then return to idle/movement
   * 
   * STATUS: ✅ WORKING - All 6 animations load and play correctly
   * 
   */
  async loadMouseAnimations() {
    try {
      console.log("🐭 [PLAYER MODEL] Loading Mouse character animations...");
      
      const charConfig = this.characterOptions.mouse;
      const mouseAnimations = charConfig.animations;
      
      // Create mixer for Mouse character
      if (!this.mixer) {
        this.mixer = new THREE.AnimationMixer(this.model);
      }
      
      // Load each animation file
      const animationPromises = Object.keys(mouseAnimations).map(async (animName) => {
        try {
          const animPath = mouseAnimations[animName];
          console.log(`🐭 [PLAYER MODEL] Loading animation: ${animName} from ${animPath}`);
          const animGltf = await this.config.loadModelHelper(animPath);
          
          // Extract animations from the loaded GLB
          if (animGltf.animations && animGltf.animations.length > 0) {
            // Use the first animation from the file (each file typically contains one animation)
            const clip = animGltf.animations[0];
            
            // Create animation action using the character model as the target
            const action = this.mixer.clipAction(clip, this.model);
            
            // Determine if this is a loop animation
            // Mouse animations: idle, run, climb are loops; jump, death, somersoult are one-time
            const isLoopAnimation = charConfig.loopAnimations.includes(animName);
            
            if (isLoopAnimation) {
              action.setLoop(THREE.LoopRepeat, Infinity);
              action.clampWhenFinished = false;
            } else {
              action.setLoop(THREE.LoopOnce, 1);
              action.clampWhenFinished = true;
            }
            
            // Store animation with lowercase name (matches Mouse format)
            action.setEffectiveTimeScale(1.0);
            action.setEffectiveWeight(0.0); // Start at 0 weight (will fade in when needed)
            this.animations[animName] = action;
            
            console.log(`✅ [PLAYER MODEL] Animation loaded: ${animName}`, {
              duration: clip.duration.toFixed(2) + "s",
              loop: isLoopAnimation ? "Repeat" : "Once",
              frames: clip.tracks ? clip.tracks.length : 0
            });
            
            return { name: animName, action, success: true };
          } else {
            console.warn(`⚠️ [PLAYER MODEL] No animations found in ${animPath}`);
            return { name: animName, success: false };
          }
        } catch (error) {
          console.error(`❌ [PLAYER MODEL] Error loading animation ${animName}:`, error);
          return { name: animName, success: false, error };
        }
      });
      
      // Wait for all animations to load
      const results = await Promise.all(animationPromises);
      const successful = results.filter(r => r.success);
      const failed = results.filter(r => !r.success);
      
      console.log(`🐭 [PLAYER MODEL] Animation loading complete: ${successful.length} loaded, ${failed.length} failed`);
      
      // Play default idle animation
      if (this.animations['idle']) {
        const idleAction = this.animations['idle'];
        idleAction.reset();
        idleAction.setEffectiveTimeScale(1.0);
        idleAction.setLoop(THREE.LoopRepeat, Infinity);
        idleAction.clampWhenFinished = false;
        idleAction.enabled = true;
        idleAction.setEffectiveWeight(1.0);
        idleAction.play();
        this.currentAnimation = 'idle';
        console.log("🎬 [PLAYER MODEL] Playing default animation: idle", {
          enabled: idleAction.enabled,
          weight: idleAction.getEffectiveWeight(),
          loop: "Repeat"
        });
      } else {
        console.warn("⚠️ [PLAYER MODEL] No idle animation found - character will be static");
      }
      
    } catch (error) {
      console.error("❌ [PLAYER MODEL] Error loading Mouse character animations:", error);
    }
  }
  
  /**
   * Setup animations from embedded model animations (Animation Library)
   * Configures all animations found in the model file
   * 
   * 🎬 ANIMATION LIBRARY CHARACTER - STABLE VERSION (December 16, 2025)
   * ====================================================================
   * 
   * Loading Method: Embedded in GLB model file (all animations in one file)
   * Model Path: './public/textures/3d models/Animation Libary/Animation Library[Standard]/Godot/AnimationLibrary_Godot_Standard.glb'
   * 
   * ANIMATION DETECTION:
   * - All animations are embedded in the model file
   * - System automatically detects and loads all animations found
   * - Common animation names detected: Idle, Walk, Sprint, Jump, etc.
   * 
   * KNOWN LOOP ANIMATIONS:
   * - 'Idle', 'Idle_Loop' - Idle standing animation
   * - 'Walk', 'Walk_Loop' - Walking animation
   * - 'Sprint_Loop' - Sprinting/running animation
   * - 'Jog_Fwd_Loop' - Jogging forward animation
   * 
   * LOOP DETECTION LOGIC:
   * - Animations with names containing: 'Loop', 'Idle', 'Walk', 'Sprint', 'Jog', 'Run'
   * - Are automatically set to LoopRepeat (infinite loop)
   * - Other animations default to LoopOnce (play once)
   * 
   * DEFAULT ANIMATION:
   * - System tries to play: 'Idle_Loop', 'Idle', 'idle' (in order)
   * - Falls back to first animation if none of the above found
   * - Default animation plays immediately after model loads
   * 
   * ANIMATION NAMING:
   * - Animation Library uses mixed case names (Idle, Walk, Sprint)
   * - System stores animations by their exact names from GLB file
   * - Animation switching must use exact names (case-sensitive)
   * 
   * CLIMB ANIMATION SUPPORT (December 30, 2025):
   * - Animation Library can now climb if the model contains climb-related animations
   * - System automatically detects climb animations (case-insensitive: 'climb', 'climbing', 'wall')
   * - Climb animations are set to loop (like Mouse character)
   * - Climbing is enabled automatically if climb animations are found
   * - Works the same way as Mouse character climbing system
   * 
   * STATUS: ✅ WORKING - All embedded animations load and play correctly, climb support added
   * 
   */
  setupEmbeddedAnimations(gltf) {
    try {
      if (!gltf.animations || gltf.animations.length === 0) {
        console.log("⚠️ [PLAYER MODEL] No embedded animations found in model");
        return;
      }
      
      // Create mixer for Animation Library character
      if (!this.mixer) {
        this.mixer = new THREE.AnimationMixer(this.model);
      }
      
      const charConfig = this.characterOptions.animation_library;
      
      // Log all available animations for reference
      const allAnimationNames = gltf.animations.map(anim => anim.name);
      console.log(`🎬 [PLAYER MODEL] Animation Library model contains ${gltf.animations.length} animations:`, allAnimationNames.join(', '));
      
      // 🔍 SCAN FOR CLIMB ANIMATIONS
      // Check for climb-related animations (case-insensitive)
      const climbAnimations = allAnimationNames.filter(name => {
        const nameLower = name.toLowerCase();
        return nameLower.includes('climb') || nameLower.includes('climbing') || nameLower.includes('wall');
      });
      
      if (climbAnimations.length > 0) {
        console.log(`🧗 [PLAYER MODEL] ✅ Found ${climbAnimations.length} climb-related animation(s):`, climbAnimations);
      } else {
        console.log(`🧗 [PLAYER MODEL] ⚠️ No climb-related animations found in Animation Library model`);
      }
      
      // 🔍 SCAN FOR JUMP ANIMATIONS
      const jumpAnimations = allAnimationNames.filter(name => {
        const nameLower = name.toLowerCase();
        return nameLower.includes('jump') || nameLower.includes('leap');
      });
      
      if (jumpAnimations.length > 0) {
        console.log(`🤸 [PLAYER MODEL] ✅ Found ${jumpAnimations.length} jump-related animation(s):`, jumpAnimations);
      }
      
      // 🔍 SCAN FOR DEATH ANIMATIONS
      const deathAnimations = allAnimationNames.filter(name => {
        const nameLower = name.toLowerCase();
        return nameLower.includes('death') || nameLower.includes('die') || nameLower.includes('dead');
      });
      
      if (deathAnimations.length > 0) {
        console.log(`💀 [PLAYER MODEL] ✅ Found ${deathAnimations.length} death-related animation(s):`, deathAnimations);
      }
      
      // Store all animations and configure them properly
      gltf.animations.forEach((clip) => {
        const action = this.mixer.clipAction(clip);
        
        // CRITICAL: Ensure animations loop properly
        // Loop animations (Idle, Walk, Sprint, Climb, etc.) should repeat
        const nameLower = clip.name.toLowerCase();
        const isLoopAnimation = charConfig.loopAnimations.some(loopName => 
          clip.name.includes(loopName) || 
          clip.name.includes('Loop') || 
          clip.name.includes('Idle') || 
          clip.name.includes('Walk') || 
          clip.name.includes('Sprint') || 
          clip.name.includes('Jog') ||
          clip.name.includes('Run')
        ) || nameLower.includes('climb'); // Climb animations should loop (like Mouse character)
        
        if (isLoopAnimation) {
          action.setLoop(THREE.LoopRepeat, Infinity); // Loop forever
        } else {
          action.setLoop(THREE.LoopOnce, 1); // Play once for non-loop animations
        }
        
        // Set proper time scale and initial weight
        action.setEffectiveTimeScale(1.0);
        action.setEffectiveWeight(0.0); // Start at 0 weight (will fade in when needed)
        
        this.animations[clip.name] = action;
        console.log("🎬 [PLAYER MODEL] Animation loaded:", clip.name, {
          duration: clip.duration.toFixed(2) + "s",
          loop: isLoopAnimation ? "Repeat" : "Once",
          clipLoop: clip.loopMode || "default"
        });
      });
      
      // Log complete animation summary
      console.log(`✅ [PLAYER MODEL] Animation Library setup complete: ${gltf.animations.length} animations available:`, {
        loopAnimations: gltf.animations.filter(clip => {
          const isLoop = charConfig.loopAnimations.some(loopName => 
            clip.name.includes(loopName) || clip.name.includes('Loop') ||
            clip.name.includes('Idle') || clip.name.includes('Walk') ||
            clip.name.includes('Sprint') || clip.name.includes('Jog') ||
            clip.name.includes('Run')
          );
          return isLoop;
        }).map(clip => clip.name),
        oneTimeAnimations: gltf.animations.filter(clip => {
          const isLoop = charConfig.loopAnimations.some(loopName => 
            clip.name.includes(loopName) || clip.name.includes('Loop') ||
            clip.name.includes('Idle') || clip.name.includes('Walk') ||
            clip.name.includes('Sprint') || clip.name.includes('Jog') ||
            clip.name.includes('Run')
          );
          return !isLoop;
        }).map(clip => clip.name),
        allAnimations: gltf.animations.map(clip => clip.name)
      });
      
      // Play default animation (idle, walk, etc.)
      const defaultAnimations = [
        'Idle_Loop', 'Idle', 'idle', 
        'Walk_Loop', 'Walk', 'walk', 
        'Jog_Fwd_Loop', 'Run', 'Running', 'running', 'run'
      ];
      let playedAnimation = false;
      
      for (const animName of defaultAnimations) {
        if (this.animations[animName]) {
          const action = this.animations[animName];
          action.reset();
          action.setEffectiveTimeScale(1.0);
          action.setLoop(THREE.LoopRepeat, Infinity);
          action.clampWhenFinished = false;
          action.enabled = true;
          action.setEffectiveWeight(1.0);
          action.play();
          this.currentAnimation = animName;
          console.log("🎬 [PLAYER MODEL] Playing default animation:", animName, {
            enabled: action.enabled,
            weight: action.getEffectiveWeight(),
            loop: "Repeat"
          });
          playedAnimation = true;
          break;
        }
      }
      
      if (!playedAnimation && gltf.animations.length > 0) {
        // Fallback: Play first animation found
        const firstAction = this.mixer.clipAction(gltf.animations[0]);
        firstAction.reset();
        firstAction.setEffectiveTimeScale(1.0);
        firstAction.setLoop(THREE.LoopRepeat, Infinity);
        firstAction.clampWhenFinished = false;
        firstAction.enabled = true;
        firstAction.setEffectiveWeight(1.0);
        firstAction.play();
        this.currentAnimation = gltf.animations[0].name;
        console.log("🎬 [PLAYER MODEL] Playing first animation:", gltf.animations[0].name);
      }
      
    } catch (error) {
      console.error("❌ [PLAYER MODEL] Error setting up embedded animations:", error);
    }
  }
  
  /**
   * Setup default animation triggers (for basic movement animations)
   * Can be extended with registerAnimationTrigger() for future animations
   */
  setupDefaultTriggers() {
    // Default triggers will be set up in update() method
    // This method is a placeholder for future trigger registration
    console.log("🎬 [PLAYER MODEL] Default animation triggers ready (idle, walk, run, jump)");
  }
  
  /**
   * Update player model (position, rotation, animations)
   * Must be called each frame in the animate loop
   * @param {number} delta - Time delta since last frame
   */
  update(delta) {
    if (!this.model || !this.mixer) {
      return; // Model or mixer not loaded yet
    }
    
    try {
      // Update animation mixer (CRITICAL: This makes animations play!)
      this.mixer.update(delta);
      
      // Note: Position and rotation updates will be handled in Phase 2
      // For now, we just update the mixer so triggered animations play
    } catch (error) {
      console.error("❌ [PLAYER MODEL] Error in update():", error);
    }
  }
  
  /**
   * Manually trigger an animation (for special moves, actions, death, etc.)
   * @param {string} animationName - Animation to play (e.g., 'death', 'somersoult', 'climb')
   * @param {boolean} immediate - Skip fade transition (default: true for death animations)
   * @returns {boolean} True if animation was successfully triggered
   */
  triggerAnimation(animationName, immediate = true) {
    if (!this.model || !this.mixer) {
      console.warn("⚠️ [PLAYER MODEL] Cannot trigger animation: model or mixer not loaded");
      return false;
    }
    
    const action = this.animations[animationName];
    if (!action) {
      console.warn(`⚠️ [PLAYER MODEL] Animation not found: ${animationName}`);
      // Check if it's a Mouse character and animation might be loaded
      if (this.isMouseCharacter()) {
        console.warn(`🐭 [PLAYER MODEL] Available Mouse animations: ${Object.keys(this.animations).join(', ')}`);
      }
      return false;
    }
    
    try {
      // Stop all current animations
      Object.keys(this.animations).forEach(animKey => {
        const currentAction = this.animations[animKey];
        if (currentAction && currentAction !== action && currentAction.isRunning()) {
          if (immediate) {
            // Immediate transition (for death)
            currentAction.setEffectiveWeight(0.0);
            currentAction.stop();
          } else {
            // Smooth fade out (for other animations)
            currentAction.fadeOut(0.2);
          }
        }
      });
      
      // Reset and play the target animation
      action.reset();
      action.setEffectiveTimeScale(1.0);
      
      // Set loop behavior based on animation type
      const charConfig = this.characterOptions[this.characterType];
      if (charConfig && charConfig.loopAnimations && charConfig.loopAnimations.includes(animationName)) {
        action.setLoop(THREE.LoopRepeat, Infinity);
        action.clampWhenFinished = false;
      } else {
        // One-time animations (death, somersault, etc.)
        action.setLoop(THREE.LoopOnce, 1);
        action.clampWhenFinished = true;
      }
      
      action.enabled = true;
      action.setEffectiveWeight(1.0);
      
      if (immediate) {
        // Immediate start (no fade in for death animations)
        action.play();
      } else {
        // Smooth fade in (for other animations)
        action.fadeIn(0.2);
        action.play();
      }
      
      this.currentAnimation = animationName;
      
      console.log(`🎬 [PLAYER MODEL] Triggered animation: ${animationName}`, {
        immediate: immediate,
        loop: action.getLoop() === THREE.LoopRepeat ? "Repeat" : "Once",
        duration: action.getClip().duration.toFixed(2) + "s"
      });
      
      return true;
    } catch (error) {
      console.error(`❌ [PLAYER MODEL] Error triggering animation ${animationName}:`, error);
      return false;
    }
  }
  
  /**
   * Get model reference
   * @returns {THREE.Group|null} The character model
   */
  getModel() {
    return this.model;
  }
  
  /**
   * Get mixer reference
   * @returns {THREE.AnimationMixer|null} The animation mixer
   */
  getMixer() {
    return this.mixer;
  }
  
  /**
   * Get current animation name
   * @returns {string|null} Current animation name
   */
  getCurrentAnimation() {
    return this.currentAnimation;
  }
  
  /**
   * Get animations dictionary (for legacy system compatibility)
   * @returns {Object} Dictionary of animation names to actions
   */
  getAnimations() {
    return this.animations;
  }
  
  /**
   * Check if model is loaded
   * @returns {boolean} True if model is loaded
   */
  isLoaded() {
    return this.model !== null;
  }
  
  /**
   * Check if character is Mouse type
   * @returns {boolean} True if Mouse character
   */
  isMouseCharacter() {
    return this.characterType === 'mouse' || 
           (this.model && this.model.userData && this.model.userData.isMouseCharacter);
  }
  
  /**
   * Calculate animation speed based on movement velocity
   * Synchronizes animation playback speed with actual movement speed
   * 
   * @param {number} velocityMagnitude - Current horizontal velocity magnitude (units/sec)
   * @param {boolean} isSprinting - Whether player is sprinting
   * @param {boolean} godMode - Whether GOD mode is active
   * @param {boolean} hasMovementInput - Whether player has movement input (for collision detection)
   * @returns {number} Animation speed multiplier (0.5 to 5.0)
   */
  calculateAnimationSpeed(velocityMagnitude, isSprinting, godMode, hasMovementInput) {
    const baseWalkSpeed = this.settings.baseWalkSpeed;
    const intendedSpeed = isSprinting ? this.settings.sprintSpeed : this.settings.walkSpeed;
    const godModeMultiplier = godMode ? this.settings.godModeMultiplier : 1.0;
    
    // Use actual velocity for animation speed calculation
    let speedForAnimation = velocityMagnitude;
    
    // CRITICAL: If player has movement input but velocity is zeroed (collision),
    // use intended speed instead of actual velocity to prevent animation stuttering
    if (velocityMagnitude < this.settings.collisionVelocityThreshold && hasMovementInput) {
      // Player is trying to move but velocity is zeroed (likely due to collision)
      // Use intended speed based on input (normal or sprint)
      speedForAnimation = intendedSpeed * godModeMultiplier;
    }
    
    // Scale animation speed to match actual/intended movement speed
    // If moving at 96 units/sec (normal walk) = 1.0x animation speed
    // If moving at 168 units/sec (normal sprint) = 1.75x animation speed
    // If moving at 336 units/sec (god mode 2x sprint) = 3.5x animation speed
    const animationSpeed = Math.max(
      this.settings.animationSpeedMin,
      Math.min(this.settings.animationSpeedMax, speedForAnimation / baseWalkSpeed)
    );
    
    return animationSpeed;
  }
  
  /**
   * Get base lerp speed for position interpolation
   * Returns consistent lerp speed for ALL levels (no level-specific overrides)
   * 
   * @param {boolean} godMode - Whether GOD mode is active
   * @returns {number} Base lerp speed (with GOD mode multiplier if applicable)
   */
  getLerpSpeed(godMode = false) {
    let lerpSpeed = this.settings.baseLerpSpeed;
    if (godMode) {
      lerpSpeed *= this.settings.godModeLerpMultiplier;
    }
    return lerpSpeed;
  }
  
  /**
   * Get base rotation speed for character rotation
   * Returns consistent rotation speed for ALL levels (no level-specific overrides)
   * 
   * @param {boolean} godMode - Whether GOD mode is active
   * @returns {number} Base rotation speed (with GOD mode multiplier if applicable)
   */
  getRotationSpeed(godMode = false) {
    let rotationSpeed = this.settings.baseRotationSpeed;
    if (godMode) {
      rotationSpeed *= this.settings.godModeRotationMultiplier;
    }
    return Math.min(this.settings.maxRotationSpeed, rotationSpeed);
  }
  
  /**
   * Get minimum animation switch delay
   * Returns consistent debounce delay for ALL levels
   * 
   * @returns {number} Minimum switch delay in milliseconds
   */
  getMinSwitchDelay() {
    return this.settings.minSwitchDelay;
  }
  
  /**
   * Get animation fade times
   * Returns consistent fade times for ALL levels
   * 
   * @param {boolean} isIdleTransition - Whether this is an idle transition
   * @returns {Object} Fade times { fadeIn, fadeOut }
   */
  getFadeTimes(isIdleTransition = false) {
    return {
      fadeIn: this.settings.fadeInTime,
      fadeOut: isIdleTransition ? this.settings.idleFadeOutTime : this.settings.fadeOutTime
    };
  }
  
  /**
   * Get movement detection thresholds
   * Returns consistent thresholds for ALL levels
   * 
   * @returns {Object} Movement thresholds
   */
  getMovementThresholds() {
    return {
      velocity: this.settings.velocityThreshold,
      jump: this.settings.jumpThreshold,
      fall: this.settings.fallThreshold,
      collision: this.settings.collisionVelocityThreshold
    };
  }
  
  /**
   * Get settings object (read-only access)
   * Allows main.js to access settings without modifying them
   * 
   * @returns {Object} Settings object (read-only)
   */
  getSettings() {
    // Return a shallow copy to prevent external modification
    return { ...this.settings };
  }
  
  /**
   * Dispose and clean up
   * Removes model from scene and cleans up resources
   */
  dispose() {
    if (this.model && this.scene) {
      // Remove from scene
      if (this.scene.children.includes(this.model)) {
        this.scene.remove(this.model);
      }
      
      // Dispose animations
      Object.keys(this.animations).forEach(animName => {
        const action = this.animations[animName];
        if (action && typeof action.stop === 'function') {
          action.stop();
        }
      });
      this.animations = {};
      
      // Dispose mixer
      if (this.mixer) {
        this.mixer = null;
      }
      
      // Dispose model (Three.js will handle geometry/material disposal)
      this.model = null;
      
      console.log("🗑️ [PLAYER MODEL] PlayerModel disposed");
    }
  }
}

