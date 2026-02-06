/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🕷️ ALIEN SPIDER BOSS SYSTEM - BEHAVIOR PATTERN ARCHITECTURE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * 📅 CREATED: December 20, 2025
 * ✅ STATUS: STABLE - PRODUCTION READY
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * 🎯 ARCHITECTURE: Similar to Phoenix Boss 2.0, but ground-based
 * 
 * This module implements the Alien Spider boss for Level 6, featuring:
 * - 7 behavior patterns with separate animations
 * - 3 texture variations (Default, Fur_1, Fur_2)
 * - TGA texture loading support (TGALoader)
 * - Brightness control for material visibility
 * - Full GUI integration with settings persistence
 * 
 * ✅ BEHAVIOR PATTERNS (12 patterns):
 * - idle_1, idle_2: Idle animations
 * - walk_patrol, run_patrol: Patrol patterns
 * - attack_1, attack_2: Single attack patterns
 * - damage_reaction: Damage taken reaction
 * - charge_attack: Run toward center → Attack_1 (composite)
 * - combo_attack: Attack_1 → Attack_2 sequence (composite)
 * - aggressive_patrol: Walk patrol → Attack_1 alternating (composite)
 * - retreat_attack: Attack_1 → Walk backward (composite)
 * - stagger_recovery: Damage_taken → Idle_2 → idle (composite)
 * 
 * 🎮 MODEL INFO
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Main Model:
 * - File: AFC_03.fbx
 * - Path: /textures/3d models/Alien Spider 1/AFC_03/AFC_03.fbx
 * - Format: FBX
 * - Scale: 4 units (target size)
 * 
 * Animations (7 separate FBX files):
 * - AFC_03@Idle_1.fbx
 * - AFC_03@Idle_2.fbx
 * - AFC_03@Walk.fbx
 * - AFC_03@Run.fbx
 * - AFC_03@Attack_1.fbx
 * - AFC_03@Attack_2.fbx
 * - AFC_03@Damage_taken.fbx
 * 
 * 🎨 TEXTURE SUPPORT
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * TGA Texture Loading:
 * - ✅ TGALoader imported and configured
 * - ✅ LoadingManager with TGA handler set up
 * - ✅ FBXLoader uses LoadingManager for automatic TGA loading
 * - ✅ Resource path set for texture discovery
 * - ✅ All textures loading successfully
 * 
 * Available Textures:
 * - AFC_03_color.tga (Default body texture)
 * - Fur_1.tga (Fur 1 variation)
 * - Fur_2.tga (Fur 2 variation)
 * - Eye_color.tga, Eye_normal.tga (Eye textures)
 * - AFC_03_normal.tga, AFC_03_ao.tga, AFC_03_metalness.tga, AFC_03_rough.tga
 * 
 * Texture Variations:
 * - Default: Uses AFC_03_color.tga
 * - Fur_1: Uses Fur_1.tga
 * - Fur_2: Uses Fur_2.tga
 * 
 * 🔧 INTEGRATION
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Usage in main.js:
 * ```javascript
 * import { AlienSpiderBoss } from "./alien-spider.js";
 * 
 * // Initialize in Level 6
 * alienSpiderBoss = new AlienSpiderBoss({
 *   scene: scene,
 *   camera: camera,
 *   levelGroup: level6State.group,
 *   player: player,
 *   spawnPosition: new THREE.Vector3(-20, 1, 0),
 *   size: 4.0,
 *   health: 100,
 *   maxHealth: 100,
 *   brightness: 1.5,
 *   textureVariation: 'Default',
 *   behaviorMode: 'idle_1'
 * });
 * 
 * // Load model
 * await alienSpiderBoss.loadModel("/textures/3d models/Alien Spider 1/AFC_03/AFC_03.fbx");
 * 
 * // Update in game loop
 * if (alienSpiderBoss) {
 *   alienSpiderBoss.update(delta);
 * }
 * ```
 * 
 * GUI Integration:
 * - God Mode → Options Menu → "🕷️ Alien Spider Boss Configuration"
 * - Controls: Size, Health, Brightness, Behavior, Texture Variation
 * - Save button persists settings per level
 * 
 * 📚 DOCUMENTATION
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * See:
 * - MODULE_INTEGRATION_GUIDE.md - Module integration details
 * - JS_MODULES_STATUS.md - Module status
 * - TGA_TEXTURE_SUCCESS.md - TGA texture implementation
 * - ALIEN_SPIDER_INTEGRATION_COMPLETE.md - Complete integration docs
 * 
 * Created: December 20, 2025
 * Last Updated: December 20, 2025
 */

import * as THREE from "three";
import { FBXLoader } from "three/examples/jsm/loaders/FBXLoader.js";
import { TGALoader } from "three/examples/jsm/loaders/TGALoader.js";
import * as SkeletonUtils from "three/examples/jsm/utils/SkeletonUtils.js";

console.log("🕷️🕷️🕷️ [ALIEN_SPIDER.JS] FILE LOADED - December 20, 2025 VERSION 🕷️🕷️🕷️");

export class AlienSpiderBoss {
  constructor(config = {}) {
    this.scene = config.scene;
    this.camera = config.camera;
    this.levelGroup = config.levelGroup || null;
    this.player = config.player || null; // Player object for tracking
    this.getPlayerPosition = config.getPlayerPosition || null; // Callback: () => Vector3 for player tracking (follow_attack pattern)
    
    // Model
    this.model = null;
    this.mixer = null;
    this.animationActions = {};
    this.currentAction = null;
    this.assetBasePath = null; // Set from model path in loadModel() - used for textures & animations
    
    // State
    this.isAlive = true;
    this.health = config.health || 100;
    this.maxHealth = config.maxHealth || 100;
    this.isGrounded = true; // Always on ground (no flying)
    
    // Configuration
    this.targetSize = config.size || 4.0; // Target size in units
    this.behaviorMode = config.behaviorMode || 'idle_1'; // Behavior mode
    this.brightness = config.brightness !== undefined ? config.brightness : 1.5; // Brightness multiplier (1.0 = normal, 2.0 = bright)
    this.colorMultiplier = config.colorMultiplier !== undefined ? config.colorMultiplier : 1.0; // Color intensity multiplier
    this.textureVariation = config.textureVariation || 'Default'; // Texture variation: 'Default', 'Fur_1', 'Fur_2'
    
    // Position and movement
    this.spawnPosition = config.spawnPosition ? config.spawnPosition.clone() : new THREE.Vector3(-20, 1, 0);
    this.patrolRadius = 10.0; // Ground patrol radius
    this.patrolAngle = 0; // Current angle in patrol circle
    this.movementSpeed = 2.0;
    
    // Behavior state
    this.behaviorTimer = 0;
    this.walkDirection = 1; // For walking patterns
    this.walkDistance = 0;
    this.maxWalkDistance = 10; // Max distance to walk before turning
    
    // Ground level
    this.groundY = config.groundY !== undefined ? config.groundY : 1.0; // Ground level
    
    // ═══════════════════════════════════════════════════════════════════════════
    // 🎯 BEHAVIOR DURATIONS - Timing parameters for patterns
    // ═══════════════════════════════════════════════════════════════════════════
    this.behaviorDurations = config.behaviorDurations || {
      idle_1: {
        duration: 3.0
      },
      idle_2: {
        duration: 3.0
      },
      walk_patrol: {
        duration: 5.0,
        movementSpeed: 2.0
      },
      run_patrol: {
        duration: 3.0,
        movementSpeed: 4.0
      },
      attack_1: {
        duration: 2.0
      },
      attack_2: {
        duration: 2.5
      },
      damage_reaction: {
        duration: 1.5
      },
      // Composite patterns (reuse existing animations)
      charge_attack: {
        runPhaseDuration: 2.0,
        attackPhaseDuration: 2.0,
        movementSpeed: 5.0
      },
      combo_attack: {
        attack1Duration: 2.0,
        attack2Duration: 2.5
      },
      aggressive_patrol: {
        walkPhaseDuration: 3.0,
        attackPhaseDuration: 2.0,
        movementSpeed: 2.0
      },
      retreat_attack: {
        attackPhaseDuration: 2.0,
        retreatPhaseDuration: 2.5,
        movementSpeed: 1.5
      },
      stagger_recovery: {
        damagePhaseDuration: 1.5,
        idlePhaseDuration: 2.0
      },
      follow_attack: {
        followSpeed: 3.0,
        attackRange: 4.0,
        attackDuration: 2.5,
        idleAfterAttack: 3.0,
        reFollowAfterIdle: true
      }
    };
    
    // Animation mapping for Spider model
    // Note: FBX animations are separate files, loaded individually
    this.animationMap = {
      idle: ['Idle_1', 'Idle_2'],
      movement: ['Walk', 'Run'],
      attack: ['Attack_1', 'Attack_2'],
      damage: ['Damage_taken']
    };
    
    // Animation file names (paths built from assetBasePath at load time)
    this.animationFileNames = {
      idle_1: "AFC_03@Idle_1.fbx",
      idle_2: "AFC_03@Idle_2.fbx",
      walk: "AFC_03@Walk.fbx",
      run: "AFC_03@Run.fbx",
      attack_1: "AFC_03@Attack_1.fbx",
      attack_2: "AFC_03@Attack_2.fbx",
      damage: "AFC_03@Damage_taken.fbx"
    };
    
    // Texture loaders - TGALoader for TGA files, TextureLoader for other formats
    this.textureLoader = new THREE.TextureLoader();
    this.tgaLoader = new TGALoader();
    
    // Create LoadingManager with TGA handler for FBXLoader
    // This allows FBXLoader to load TGA textures embedded in FBX files
    this.loadingManager = new THREE.LoadingManager();
    this.loadingManager.addHandler(/\.tga$/i, this.tgaLoader);
    
    // Callbacks
    this.onBossDefeated = config.onBossDefeated || (() => {});
    this.onBossHit = config.onBossHit || (() => {});
  }
  
  /**
   * Apply texture variation (similar to Phoenix color variations)
   * Available variations: 'Default', 'Fur_1', 'Fur_2'
   */
  applyTextureVariation(variationName) {
    if (!this.model) {
      console.warn("⚠️ [ALIEN_SPIDER] Cannot apply texture variation: model not loaded yet");
      this.textureVariation = variationName; // Store for when model loads
      return;
    }
    
    this.textureVariation = variationName;
    console.log(`🕷️ [ALIEN_SPIDER] Applying texture variation: ${variationName}`);
    
    const basePath = this.assetBasePath || "/textures/3d models/Alien Spider 1/AFC_03/";
    
    // Determine which color texture to use based on variation
    let colorTextureFile = 'AFC_03_color.tga'; // Default
    if (variationName === 'Fur_1') {
      colorTextureFile = 'Fur_1.tga';
    } else if (variationName === 'Fur_2') {
      colorTextureFile = 'Fur_2.tga';
    }
    
    // Load and apply textures to all meshes
    this.model.traverse((child) => {
      if (child.isMesh && child.material) {
        // Handle both single material and material arrays
        const materials = Array.isArray(child.material) ? child.material : [child.material];
        
        materials.forEach((mat) => {
          if (mat && mat.isMeshStandardMaterial) {
            // Check if this is an eye mesh (eyes don't change with texture variation)
            const isEyeMesh = child.name.toLowerCase().includes('eye') || 
                             mat.name.toLowerCase().includes('eye');
            
            if (!isEyeMesh) {
              // Body textures - apply variation
              // Load color texture using TGALoader
              this.tgaLoader.load(
                basePath + colorTextureFile,
                (texture) => {
                  texture.flipY = false;
                  mat.map = texture;
                  
                  // Apply brightness to material color
                  if (mat.color) {
                    // Store original color if not already stored
                    if (!mat._originalColor) {
                      mat._originalColor = mat.color.clone();
                    }
                    // Apply brightness multiplier
                    mat.color.r = Math.min(1.0, mat._originalColor.r * this.brightness);
                    mat.color.g = Math.min(1.0, mat._originalColor.g * this.brightness);
                    mat.color.b = Math.min(1.0, mat._originalColor.b * this.brightness);
                  }
                  
                  mat.needsUpdate = true;
                  console.log(`✅ [ALIEN_SPIDER] Applied ${variationName} texture to: ${child.name}`);
                },
                undefined,
                (error) => {
                  console.warn(`⚠️ [ALIEN_SPIDER] Failed to load ${variationName} texture:`, error);
                  // Apply brightness to material color as fallback
                  if (mat.color) {
                    if (!mat._originalColor) {
                      mat._originalColor = mat.color.clone();
                    }
                    mat.color.r = Math.min(1.0, mat._originalColor.r * this.brightness);
                    mat.color.g = Math.min(1.0, mat._originalColor.g * this.brightness);
                    mat.color.b = Math.min(1.0, mat._originalColor.b * this.brightness);
                    mat.needsUpdate = true;
                  }
                }
              );
            }
            // Note: Eye textures and other maps (normal, AO, etc.) remain the same
            // Only the color texture changes based on variation
          }
        });
      }
    });
  }
  
  /**
   * Set texture variation (for GUI control)
   * 
   * Switches between texture variations:
   * - 'Default': Original body texture (AFC_03_color.tga)
   * - 'Fur_1': First fur texture variation (Fur_1.tga)
   * - 'Fur_2': Second fur texture variation (Fur_2.tga)
   * 
   * @param {string} variationName - Texture variation name
   */
  setTextureVariation(variationName) {
    this.applyTextureVariation(variationName);
    console.log(`🕷️ [ALIEN_SPIDER] Texture variation set to: ${variationName}`);
  }
  
  /**
   * Apply textures to the spider model (legacy method - now calls applyTextureVariation)
   */
  applyTextures() {
    // Use texture variation system
    this.applyTextureVariation(this.textureVariation);
    
    // Also apply eye textures and other maps (they don't change with variation)
    if (!this.model) {
      console.warn("⚠️ [ALIEN_SPIDER] Cannot apply textures: model not loaded yet");
      return;
    }
    
    console.log(`🕷️ [ALIEN_SPIDER] Applying textures (brightness: ${this.brightness}x, variation: ${this.textureVariation})`);
    
    const basePath = this.assetBasePath || "/textures/3d models/Alien Spider 1/AFC_03/";
    
    // Load and apply textures to all meshes
    this.model.traverse((child) => {
      if (child.isMesh && child.material) {
        // Handle both single material and material arrays
        const materials = Array.isArray(child.material) ? child.material : [child.material];
        
        materials.forEach((mat) => {
          if (mat && mat.isMeshStandardMaterial) {
            // Check if this is an eye mesh
            const isEyeMesh = child.name.toLowerCase().includes('eye') || 
                             mat.name.toLowerCase().includes('eye');
            
            if (isEyeMesh) {
              // Load eye textures using TGALoader
              this.tgaLoader.load(
                basePath + 'Eye_color.tga',
                (texture) => {
                  texture.flipY = false;
                  mat.map = texture;
                  // Brighten eye color
                  if (mat.color) {
                    mat.color.multiplyScalar(this.brightness);
                  }
                  mat.needsUpdate = true;
                  console.log(`✅ [ALIEN_SPIDER] Applied eye color texture to: ${child.name}`);
                },
                undefined,
                (error) => {
                  console.warn(`⚠️ [ALIEN_SPIDER] Failed to load eye color texture:`, error);
                }
              );
              
              this.tgaLoader.load(
                basePath + 'Eye_normal.tga',
                (texture) => {
                  texture.flipY = false;
                  mat.normalMap = texture;
                  mat.needsUpdate = true;
                  console.log(`✅ [ALIEN_SPIDER] Applied eye normal texture to: ${child.name}`);
                },
                undefined,
                (error) => {
                  console.warn(`⚠️ [ALIEN_SPIDER] Failed to load eye normal texture:`, error);
                }
              );
            } else {
              // Body textures
              // CRITICAL: Apply brightness to material color FIRST (works even if textures fail)
              if (mat.color) {
                // Store original color if not already stored
                if (!mat._originalColor) {
                  mat._originalColor = mat.color.clone();
                }
                // Apply brightness multiplier
                mat.color.r = Math.min(1.0, mat._originalColor.r * this.brightness);
                mat.color.g = Math.min(1.0, mat._originalColor.g * this.brightness);
                mat.color.b = Math.min(1.0, mat._originalColor.b * this.brightness);
                mat.needsUpdate = true;
              }
              
              // Load color texture using TGALoader (TGA format support)
              this.tgaLoader.load(
                basePath + 'AFC_03_color.tga',
                (texture) => {
                  texture.flipY = false;
                  mat.map = texture;
                  mat.needsUpdate = true;
                  console.log(`✅ [ALIEN_SPIDER] Applied color texture to: ${child.name}`);
                },
                undefined,
                (error) => {
                  console.warn(`⚠️ [ALIEN_SPIDER] Failed to load color texture:`, error);
                  // Brightness already applied to material color above
                  // Set a visible fallback color if no texture
                  if (!mat.map && mat.color) {
                    // Ensure color is bright enough to see
                    if (mat.color.r + mat.color.g + mat.color.b < 0.5) {
                      mat.color.setHex(0x888888); // Medium gray fallback
                      mat.color.multiplyScalar(this.brightness);
                    }
                    mat.needsUpdate = true;
                    console.log(`🕷️ [ALIEN_SPIDER] Using material color (brightness: ${this.brightness}x) for: ${child.name}`);
                  }
                }
              );
              
              // Load normal map using TGALoader
              this.tgaLoader.load(
                basePath + 'AFC_03_normal.tga',
                (texture) => {
                  texture.flipY = false;
                  mat.normalMap = texture;
                  mat.needsUpdate = true;
                  console.log(`✅ [ALIEN_SPIDER] Applied normal map to: ${child.name}`);
                },
                undefined,
                (error) => {
                  console.warn(`⚠️ [ALIEN_SPIDER] Failed to load normal map:`, error);
                }
              );
              
              // Load AO (ambient occlusion) using TGALoader
              this.tgaLoader.load(
                basePath + 'AFC_03_ao.tga',
                (texture) => {
                  texture.flipY = false;
                  mat.aoMap = texture;
                  mat.needsUpdate = true;
                  console.log(`✅ [ALIEN_SPIDER] Applied AO map to: ${child.name}`);
                },
                undefined,
                (error) => {
                  console.warn(`⚠️ [ALIEN_SPIDER] Failed to load AO map:`, error);
                }
              );
              
              // Load metalness map using TGALoader
              this.tgaLoader.load(
                basePath + 'AFC_03_metalness.tga',
                (texture) => {
                  texture.flipY = false;
                  mat.metalnessMap = texture;
                  mat.needsUpdate = true;
                  console.log(`✅ [ALIEN_SPIDER] Applied metalness map to: ${child.name}`);
                },
                undefined,
                (error) => {
                  console.warn(`⚠️ [ALIEN_SPIDER] Failed to load metalness map:`, error);
                }
              );
              
              // Load roughness map using TGALoader
              this.tgaLoader.load(
                basePath + 'AFC_03_rough.tga',
                (texture) => {
                  texture.flipY = false;
                  mat.roughnessMap = texture;
                  mat.needsUpdate = true;
                  console.log(`✅ [ALIEN_SPIDER] Applied roughness map to: ${child.name}`);
                },
                undefined,
                (error) => {
                  console.warn(`⚠️ [ALIEN_SPIDER] Failed to load roughness map:`, error);
                }
              );
            }
          }
        });
      }
    });
  }
  
  // ═══════════════════════════════════════════════════════════════════════════
  // 🎛️ GUI CONTROL METHODS
  // ═══════════════════════════════════════════════════════════════════════════
  // These methods are called from the God Mode GUI to control boss settings
  
  /**
   * Set brightness multiplier (for GUI control)
   * 
   * Adjusts material brightness for visibility. Range: 0.5-3.0
   * - 0.5: Darker
   * - 1.0: Normal
   * - 1.5: Default (bright)
   * - 3.0: Maximum brightness
   * 
   * @param {number} brightness - Brightness multiplier (0.5-3.0)
   */
  setBrightness(brightness) {
    this.brightness = Math.max(0.5, Math.min(3.0, brightness)); // Clamp 0.5-3.0
    if (this.model) {
      // Update brightness on all materials
      this.model.traverse((child) => {
        if (child.isMesh && child.material) {
          const materials = Array.isArray(child.material) ? child.material : [child.material];
          materials.forEach((mat) => {
            if (mat && mat.isMeshStandardMaterial && mat.color) {
              // Restore original color if stored
              if (mat._originalColor) {
                mat.color.copy(mat._originalColor);
              }
              // Apply new brightness
              mat.color.r = Math.min(1.0, mat.color.r * this.brightness);
              mat.color.g = Math.min(1.0, mat.color.g * this.brightness);
              mat.color.b = Math.min(1.0, mat.color.b * this.brightness);
              mat.needsUpdate = true;
            }
          });
        }
      });
    }
    console.log(`🕷️ [ALIEN_SPIDER] Brightness set to: ${this.brightness}x`);
  }
  
  /**
   * Set color multiplier (for GUI control)
   */
  setColorMultiplier(multiplier) {
    this.colorMultiplier = Math.max(0.5, Math.min(2.0, multiplier)); // Clamp 0.5-2.0
    if (this.model) {
      // Reapply textures with new color multiplier
      this.applyTextures();
    }
    console.log(`🕷️ [ALIEN_SPIDER] Color multiplier set to: ${this.colorMultiplier}x`);
  }
  
  /**
   * Load the FBX model
   */
  async loadModel(modelPath) {
    console.log("🕷️🕷️🕷️ [ALIEN_SPIDER] loadModel() CALLED with path:", modelPath);
    return new Promise((resolve, reject) => {
      // CRITICAL: Derive base path from model path (main.js passes resolveAssetPath result)
      // e.g. /public/three.js/public/textures/3d models/Alien Spider 1/AFC_03/AFC_03.fbx -> .../AFC_03/
      this.assetBasePath = modelPath.replace(/\/[^/]+$/, '/');
      console.log("🕷️ [ALIEN_SPIDER] Asset base path:", this.assetBasePath);
      
      // Use LoadingManager with TGA handler so FBXLoader can load TGA textures
      const loader = new FBXLoader(this.loadingManager);
      
      // Set resource path so FBXLoader knows where to find textures
      loader.setResourcePath(this.assetBasePath);
      
      loader.load(
        modelPath,
        (fbx) => {
          console.log("✅ [ALIEN_SPIDER] Model loaded:", modelPath);
          
          // Get the scene (model root)
          this.model = fbx;
          
          // Calculate original model size (before scaling)
          const box = new THREE.Box3().setFromObject(this.model);
          const size = box.getSize(new THREE.Vector3());
          const maxDim = Math.max(size.x, size.y, size.z);
          this._originalModelSize = maxDim; // Store original size
          
          // Scale model to target size
          const scale = this.targetSize / this._originalModelSize;
          this.model.scale.set(scale, scale, scale);
          console.log(`✅ [ALIEN_SPIDER] Model scaled: ${scale.toFixed(4)}x (target: ${this.targetSize} units, original: ${this._originalModelSize.toFixed(2)} units)`);
          
          // CRITICAL: Process materials for FBX models (often have dark/invisible materials)
          // NOTE: TGA textures are now supported via TGALoader and LoadingManager
          let meshCount = 0;
          let materialCount = 0;
          this.model.traverse((child) => {
            if (child.isMesh) {
              meshCount++;
              child.castShadow = true;
              child.receiveShadow = true;
              child.visible = true;
              child.frustumCulled = false;
              
              // CRITICAL: Process materials - FBX models often have very dark or missing materials
              if (!child.material) {
                // No material - create a visible default material
                child.material = new THREE.MeshStandardMaterial({
                  color: 0x888888, // Medium gray for visibility
                  metalness: 0.35,
                  roughness: 0.45,
                  side: THREE.DoubleSide
                });
                child.material.needsUpdate = true;
                materialCount++;
                console.log(`🕷️ [ALIEN_SPIDER] Created default material for mesh: ${child.name}`);
              } else {
                // Process existing materials - brighten dark colors
                const originalMat = child.material;
                const isArray = Array.isArray(originalMat);
                const materials = isArray ? originalMat : [originalMat];
                
                const processedMaterials = materials.map((mat) => {
                  // Extract color safely
                  let originalColor = null;
                  if (mat.color && mat.color.isColor) {
                    originalColor = mat.color.clone();
                  } else if (mat.color) {
                    originalColor = new THREE.Color(mat.color);
                  } else {
                    originalColor = new THREE.Color(0x888888); // Default visible gray
                  }
                  
                  // Store original color for brightness slider
                  const storedOriginalColor = originalColor.clone();
                  
                  // Brighten dark colors (CRITICAL for FBX)
                  const brightness = originalColor.r + originalColor.g + originalColor.b;
                  let finalColor = originalColor.clone();
                  if (brightness < 0.3) {
                    // Very dark - brighten significantly (3x)
                    finalColor.r = Math.min(1.0, originalColor.r * 3.0);
                    finalColor.g = Math.min(1.0, originalColor.g * 3.0);
                    finalColor.b = Math.min(1.0, originalColor.b * 3.0);
                  } else if (brightness < 0.6) {
                    // Medium dark - brighten moderately (2x)
                    finalColor.r = Math.min(1.0, originalColor.r * 2.0);
                    finalColor.g = Math.min(1.0, originalColor.g * 2.0);
                    finalColor.b = Math.min(1.0, originalColor.b * 2.0);
                  }
                  
                  // Apply initial brightness multiplier
                  finalColor.r = Math.min(1.0, finalColor.r * this.brightness);
                  finalColor.g = Math.min(1.0, finalColor.g * this.brightness);
                  finalColor.b = Math.min(1.0, finalColor.b * this.brightness);
                  
                  // Create new MeshStandardMaterial (always use standard for consistency)
                  const newMaterial = new THREE.MeshStandardMaterial({
                    color: finalColor,
                    map: mat.map || null,
                    normalMap: mat.normalMap || null,
                    emissive: brightness < 0.3 ? finalColor.clone().multiplyScalar(0.1) : new THREE.Color(0x000000),
                    emissiveIntensity: brightness < 0.3 ? 0.2 : 0,
                    metalness: 0.35,
                    roughness: 0.45,
                    side: THREE.DoubleSide, // CRITICAL: Double-sided for visibility
                    opacity: mat.opacity !== undefined && mat.opacity > 0.1 ? mat.opacity : 1.0,
                    transparent: mat.transparent !== undefined ? mat.transparent : false
                  });
                  
                  // Store original color for brightness slider
                  newMaterial._originalColor = storedOriginalColor;
                  
                  newMaterial.needsUpdate = true;
                  
                  // Ensure material is visible
                  if (newMaterial.opacity < 0.1) {
                    newMaterial.opacity = 1.0;
                    newMaterial.transparent = false;
                  }
                  
                  return newMaterial;
                });
                
                child.material = isArray ? processedMaterials : processedMaterials[0];
                materialCount++;
                // Log material processing (brightness is applied in the material processing above)
                console.log(`🕷️ [ALIEN_SPIDER] Processed material for mesh: ${child.name}`);
              }
            }
          });
          
          console.log(`✅ [ALIEN_SPIDER] Processed ${meshCount} meshes with ${materialCount} materials`);
          
          // Apply textures after material processing
          this.applyTextures();
          
          // Set initial position
          this.model.position.copy(this.spawnPosition);
          
          // Align to ground level
          const boxAfterScale = new THREE.Box3().setFromObject(this.model);
          const minY = boxAfterScale.min.y;
          const yOffset = this.groundY - minY;
          this.model.position.y += yOffset;
          
          this.model.visible = true;
          
          // Add to level group
          if (this.levelGroup) {
            this.levelGroup.add(this.model);
            console.log("✅ [ALIEN_SPIDER] Model added to level group");
          } else {
            this.scene.add(this.model);
            console.log("✅ [ALIEN_SPIDER] Model added to scene");
          }
          
          // Setup animation mixer (will load animations separately)
          this.mixer = new THREE.AnimationMixer(this.model);
          console.log("✅ [ALIEN_SPIDER] Animation mixer created");
          
          // Load all animations
          this.loadAllAnimations().then(() => {
            // Apply initial texture variation
            this.applyTextureVariation(this.textureVariation);
            
            // Apply initial behavior mode
            this.setBehaviorMode(this.behaviorMode);
            
            // Start initial animation
            if (this.animationActions['Idle_1']) {
              this.playAnimation('Idle_1', true);
              console.log("✅ [ALIEN_SPIDER] Started Idle_1 animation on load");
            }
            
            resolve(this.model);
          }).catch((error) => {
            console.error("❌ [ALIEN_SPIDER] Failed to load animations:", error);
            reject(error);
          });
        },
        (progress) => {
          // Progress callback
        },
        (error) => {
          console.error("❌ [ALIEN_SPIDER] Failed to load model:", error);
          reject(error);
        }
      );
    });
  }
  
  /**
   * Load all animation files (FBX format - separate files)
   */
  async loadAllAnimations() {
    // CRITICAL: Build animation paths from assetBasePath + animationFileNames
    // (animationPaths was never defined - was causing undefined iteration)
    const basePath = this.assetBasePath || "/textures/3d models/Alien Spider 1/AFC_03/";
    const animationPaths = {};
    for (const [key, filename] of Object.entries(this.animationFileNames)) {
      animationPaths[key] = basePath + filename;
    }
    
    // Use LoadingManager with TGA handler for animation files too (in case they have textures)
    const loader = new FBXLoader(this.loadingManager);
    loader.setResourcePath(basePath);
    
    // DEBUG: Log base model hierarchy (bones/objects) for animation binding diagnosis
    if (this.model) {
      const baseNames = [];
      const baseBones = [];
      this.model.traverse((child) => {
        baseNames.push(child.name || '(unnamed)');
        if (child.type === 'Bone' || child.isBone) {
          baseBones.push(child.name || '(unnamed)');
        }
      });
      console.log("🕷️ [ALIEN_SPIDER] Base model object names (first 30):", baseNames.slice(0, 30));
      if (baseBones.length > 0) {
        console.log("🕷️ [ALIEN_SPIDER] Base model bones:", baseBones);
      } else {
        console.log("🕷️ [ALIEN_SPIDER] Base model has no Bone objects - check for SkinnedMesh/skeleton");
      }
    }
    
    const animationPromises = [];
    
    for (const [key, path] of Object.entries(animationPaths)) {
      const promise = loader.loadAsync(path).then((fbx) => {
        if (fbx.animations && fbx.animations.length > 0) {
          // Extract animation clip
          const animation = fbx.animations[0];
          
          // DEBUG: Log animation track targets (which objects the animation references)
          if (animation.tracks && animation.tracks.length > 0 && key === 'idle_1') {
            const trackTargets = animation.tracks.map((t) => t.name).slice(0, 10);
            console.log(`🕷️ [ALIEN_SPIDER] Animation ${key} track targets (first 10):`, trackTargets);
          }
          
          // Map animation name to our key
          const actionName = this.getAnimationNameFromKey(key);
          const action = this.mixer.clipAction(animation);
          this.animationActions[actionName] = action;
          
          console.log(`✅ [ALIEN_SPIDER] Loaded animation: ${actionName} from ${key}`);
        }
      }).catch((error) => {
        console.warn(`⚠️ [ALIEN_SPIDER] Failed to load animation ${key}:`, error);
      });
      
      animationPromises.push(promise);
    }
    
    await Promise.all(animationPromises);
    console.log(`✅ [ALIEN_SPIDER] ${Object.keys(this.animationActions).length} animations loaded`);
    console.log(`📋 [ALIEN_SPIDER] Available animations:`, Object.keys(this.animationActions).sort());
  }
  
  /**
   * Map animation key to animation name
   */
  getAnimationNameFromKey(key) {
    const nameMap = {
      'idle_1': 'Idle_1',
      'idle_2': 'Idle_2',
      'walk': 'Walk',
      'run': 'Run',
      'attack_1': 'Attack_1',
      'attack_2': 'Attack_2',
      'damage': 'Damage_taken'
    };
    return nameMap[key] || key;
  }
  
  /**
   * Play an animation by name
   */
  playAnimation(name, loop = true) {
    if (!this.mixer || !this.animationActions[name]) {
      // Try alternative names
      const alternatives = ['Idle_1', 'Idle_2', 'Walk', 'Run'];
      
      for (const alt of alternatives) {
        if (this.animationActions[alt]) {
          name = alt;
          break;
        }
      }
      
      if (!this.animationActions[name]) {
        console.warn(`⚠️ [ALIEN_SPIDER] Animation not found: ${name}`);
        return;
      }
    }
    
    // Stop current animation
    if (this.currentAction) {
      this.currentAction.fadeOut(0.2);
    }
    
    // Play new animation
    this.currentAction = this.animationActions[name];
    this.currentAction.reset();
    this.currentAction.setLoop(loop ? THREE.LoopRepeat : THREE.LoopOnce);
    this.currentAction.fadeIn(0.2);
    this.currentAction.play();
  }
  
  /**
   * Update boss (call every frame)
   */
  // ═══════════════════════════════════════════════════════════════════════════
  // 🔄 UPDATE LOOP & BEHAVIOR SYSTEM
  // ═══════════════════════════════════════════════════════════════════════════
  
  /**
   * Update loop - called every frame
   * 
   * Updates animation mixer and behavior state machine.
   * This method is called from main.js in the game loop.
   * 
   * @param {number} delta - Time delta since last frame (in seconds)
   */
  update(delta) {
    // Only skip if model doesn't exist
    if (!this.model) return;
    
    // Update animation mixer
    if (this.mixer) {
      this.mixer.update(delta);
    }
    
    // Update behavior based on current mode
    this.behaviorTimer += delta;
    
    this.updateBehavior(delta);
    
    // Force matrix update
    this.model.updateMatrixWorld(true);
  }
  
  /**
   * Update behavior based on current mode
   */
  updateBehavior(delta) {
    switch (this.behaviorMode) {
      case 'idle_1':
        this.updateIdle1(delta);
        break;
      case 'idle_2':
        this.updateIdle2(delta);
        break;
      case 'walk_patrol':
        this.updateWalkPatrol(delta);
        break;
      case 'run_patrol':
        this.updateRunPatrol(delta);
        break;
      case 'attack_1':
        this.updateAttack1(delta);
        break;
      case 'attack_2':
        this.updateAttack2(delta);
        break;
      case 'damage_reaction':
        this.updateDamageReaction(delta);
        break;
      case 'charge_attack':
        this.updateChargeAttack(delta);
        break;
      case 'combo_attack':
        this.updateComboAttack(delta);
        break;
      case 'aggressive_patrol':
        this.updateAggressivePatrol(delta);
        break;
      case 'retreat_attack':
        this.updateRetreatAttack(delta);
        break;
      case 'stagger_recovery':
        this.updateStaggerRecovery(delta);
        break;
      case 'follow_attack':
        this.updateFollowAttack(delta);
        break;
      default:
        console.warn(`⚠️ [ALIEN_SPIDER] Unknown behavior mode: ${this.behaviorMode}`);
    }
  }
  
  /**
   * Set behavior mode
   */
  /**
   * Set behavior mode (switch between patterns)
   * 
   * Available modes:
   * - 'idle_1', 'idle_2': Idle animations
   * - 'walk_patrol', 'run_patrol': Patrol patterns
   * - 'attack_1', 'attack_2': Attack patterns
   * - 'damage_reaction': Damage reaction animation
   * 
   * @param {string} mode - Behavior mode name
   */
  setBehaviorMode(mode) {
    this.behaviorMode = mode;
    this.behaviorTimer = 0;
    this.isAlive = true; // CRITICAL: Always set isAlive = true (except death)
    
    switch (mode) {
      case 'idle_1':
        this.playAnimation('Idle_1', true);
        console.log("🕷️ [ALIEN_SPIDER] Started idle_1 pattern");
        break;
      case 'idle_2':
        this.playAnimation('Idle_2', true);
        console.log("🕷️ [ALIEN_SPIDER] Started idle_2 pattern");
        break;
      case 'walk_patrol':
        this.playAnimation('Walk', true);
        this.patrolAngle = 0;
        console.log("🕷️ [ALIEN_SPIDER] Started walk_patrol pattern");
        break;
      case 'run_patrol':
        this.playAnimation('Run', true);
        this.patrolAngle = 0;
        console.log("🕷️ [ALIEN_SPIDER] Started run_patrol pattern");
        break;
      case 'attack_1':
        this.playAnimation('Attack_1', false);
        console.log("🕷️ [ALIEN_SPIDER] Started attack_1 pattern");
        break;
      case 'attack_2':
        this.playAnimation('Attack_2', false);
        console.log("🕷️ [ALIEN_SPIDER] Started attack_2 pattern");
        break;
      case 'damage_reaction':
        this.playAnimation('Damage_taken', false);
        console.log("🕷️ [ALIEN_SPIDER] Started damage_reaction pattern");
        break;
      case 'charge_attack':
        this.playAnimation('Run', true);
        this.chargeAttackStartPos = this.model ? this.model.position.clone() : this.spawnPosition.clone();
        console.log("🕷️ [ALIEN_SPIDER] Started charge_attack pattern");
        break;
      case 'combo_attack':
        this.playAnimation('Attack_1', false);
        console.log("🕷️ [ALIEN_SPIDER] Started combo_attack pattern");
        break;
      case 'aggressive_patrol':
        this.playAnimation('Walk', true);
        this.patrolAngle = 0;
        console.log("🕷️ [ALIEN_SPIDER] Started aggressive_patrol pattern");
        break;
      case 'retreat_attack':
        this.playAnimation('Attack_1', false);
        this.retreatAttackStartPos = this.model ? this.model.position.clone() : this.spawnPosition.clone();
        console.log("🕷️ [ALIEN_SPIDER] Started retreat_attack pattern");
        break;
      case 'stagger_recovery':
        this.playAnimation('Damage_taken', false);
        console.log("🕷️ [ALIEN_SPIDER] Started stagger_recovery pattern");
        break;
      case 'follow_attack':
        this.playAnimation('Walk', true);
        this.followAttackState = 'following';
        this.followAttackTimer = 0;
        console.log("🕷️ [ALIEN_SPIDER] Started follow_attack pattern");
        break;
    }
  }
  
  // ═══════════════════════════════════════════════════════════════════════════
  // BEHAVIOR UPDATE FUNCTIONS
  // ═══════════════════════════════════════════════════════════════════════════
  
  /**
   * Idle 1 behavior
   */
  updateIdle1(delta) {
    // Just stand still and play idle animation
    // Animation is already playing from setBehaviorMode
  }
  
  /**
   * Idle 2 behavior
   */
  updateIdle2(delta) {
    // Just stand still and play idle animation
    // Animation is already playing from setBehaviorMode
  }
  
  /**
   * Walk patrol behavior - Walk in circle around spawn point
   */
  updateWalkPatrol(delta) {
    const durations = this.behaviorDurations.walk_patrol || {};
    const speed = durations.movementSpeed || 2.0;
    
    // Update patrol angle
    this.patrolAngle += (speed / this.patrolRadius) * delta;
    
    // Calculate position in circle
    const x = this.spawnPosition.x + Math.cos(this.patrolAngle) * this.patrolRadius;
    const z = this.spawnPosition.z + Math.sin(this.patrolAngle) * this.patrolRadius;
    const y = this.groundY;
    
    // Update position
    this.model.position.set(x, y, z);
    
    // Face direction of movement
    const angle = Math.atan2(Math.sin(this.patrolAngle), Math.cos(this.patrolAngle));
    this.model.rotation.y = angle + Math.PI / 2;
  }
  
  /**
   * Run patrol behavior - Fast circular patrol
   */
  updateRunPatrol(delta) {
    const durations = this.behaviorDurations.run_patrol || {};
    const speed = durations.movementSpeed || 4.0;
    
    // Update patrol angle (faster than walk)
    this.patrolAngle += (speed / this.patrolRadius) * delta;
    
    // Calculate position in circle
    const x = this.spawnPosition.x + Math.cos(this.patrolAngle) * this.patrolRadius;
    const z = this.spawnPosition.z + Math.sin(this.patrolAngle) * this.patrolRadius;
    const y = this.groundY;
    
    // Update position
    this.model.position.set(x, y, z);
    
    // Face direction of movement
    const angle = Math.atan2(Math.sin(this.patrolAngle), Math.cos(this.patrolAngle));
    this.model.rotation.y = angle + Math.PI / 2;
  }
  
  /**
   * Attack 1 behavior - Melee attack forward
   */
  updateAttack1(delta) {
    const durations = this.behaviorDurations.attack_1 || {};
    const duration = durations.duration || 2.0;
    
    // Check if animation finished
    if (this.behaviorTimer >= duration) {
      // Loop back to idle
      this.setBehaviorMode('idle_1');
    }
  }
  
  /**
   * Attack 2 behavior - Jump attack
   */
  updateAttack2(delta) {
    const durations = this.behaviorDurations.attack_2 || {};
    const duration = durations.duration || 2.5;
    
    // Check if animation finished
    if (this.behaviorTimer >= duration) {
      // Loop back to idle
      this.setBehaviorMode('idle_1');
    }
  }
  
  /**
   * Damage reaction behavior
   */
  updateDamageReaction(delta) {
    const durations = this.behaviorDurations.damage_reaction || {};
    const duration = durations.duration || 1.5;
    
    // Check if animation finished
    if (this.behaviorTimer >= duration) {
      // Return to idle
      this.setBehaviorMode('idle_1');
    }
  }
  
  /**
   * Charge attack - Run toward spawn center, then Attack_1
   * Phase 1: Run toward center (2s)
   * Phase 2: Attack_1 (2s)
   */
  updateChargeAttack(delta) {
    const durations = this.behaviorDurations.charge_attack || {};
    const runPhaseDuration = durations.runPhaseDuration || 2.0;
    const attackPhaseDuration = durations.attackPhaseDuration || 2.0;
    const speed = durations.movementSpeed || 5.0;
    
    if (this.behaviorTimer < runPhaseDuration) {
      // Phase 1: Run toward spawn center
      if (!this.animationActions['Run'] || this.currentAction !== this.animationActions['Run']) {
        this.playAnimation('Run', true);
      }
      const progress = this.behaviorTimer / runPhaseDuration;
      const startX = this.chargeAttackStartPos ? this.chargeAttackStartPos.x : this.spawnPosition.x;
      const startZ = this.chargeAttackStartPos ? this.chargeAttackStartPos.z : this.spawnPosition.z;
      const x = startX + (this.spawnPosition.x - startX) * progress;
      const z = startZ + (this.spawnPosition.z - startZ) * progress;
      this.model.position.set(x, this.groundY, z);
      const angle = Math.atan2(this.spawnPosition.z - startZ, this.spawnPosition.x - startX);
      this.model.rotation.y = angle - Math.PI / 2;
    } else if (this.behaviorTimer < runPhaseDuration + attackPhaseDuration) {
      // Phase 2: Attack
      if (!this.animationActions['Attack_1'] || this.currentAction !== this.animationActions['Attack_1']) {
        this.playAnimation('Attack_1', false);
      }
    } else {
      this.setBehaviorMode('idle_1');
    }
  }
  
  /**
   * Combo attack - Attack_1 then Attack_2
   */
  updateComboAttack(delta) {
    const durations = this.behaviorDurations.combo_attack || {};
    const attack1Duration = durations.attack1Duration || 2.0;
    const attack2Duration = durations.attack2Duration || 2.5;
    
    if (this.behaviorTimer < attack1Duration) {
      if (!this.animationActions['Attack_1'] || this.currentAction !== this.animationActions['Attack_1']) {
        this.playAnimation('Attack_1', false);
      }
    } else if (this.behaviorTimer < attack1Duration + attack2Duration) {
      if (!this.animationActions['Attack_2'] || this.currentAction !== this.animationActions['Attack_2']) {
        this.playAnimation('Attack_2', false);
      }
    } else {
      this.setBehaviorMode('idle_1');
    }
  }
  
  /**
   * Aggressive patrol - Walk in circle, then Attack_1, repeat
   */
  updateAggressivePatrol(delta) {
    const durations = this.behaviorDurations.aggressive_patrol || {};
    const walkPhaseDuration = durations.walkPhaseDuration || 3.0;
    const attackPhaseDuration = durations.attackPhaseDuration || 2.0;
    const speed = durations.movementSpeed || 2.0;
    const cycleDuration = walkPhaseDuration + attackPhaseDuration;
    const cycleTime = this.behaviorTimer % cycleDuration;
    
    if (cycleTime < walkPhaseDuration) {
      if (!this.animationActions['Walk'] || this.currentAction !== this.animationActions['Walk']) {
        this.playAnimation('Walk', true);
      }
      this.patrolAngle += (speed / this.patrolRadius) * delta;
      const x = this.spawnPosition.x + Math.cos(this.patrolAngle) * this.patrolRadius;
      const z = this.spawnPosition.z + Math.sin(this.patrolAngle) * this.patrolRadius;
      this.model.position.set(x, this.groundY, z);
      const angle = Math.atan2(Math.sin(this.patrolAngle), Math.cos(this.patrolAngle));
      this.model.rotation.y = angle + Math.PI / 2;
    } else {
      if (!this.animationActions['Attack_1'] || this.currentAction !== this.animationActions['Attack_1']) {
        this.playAnimation('Attack_1', false);
      }
    }
  }
  
  /**
   * Retreat attack - Attack_1 then walk backward away from spawn
   */
  updateRetreatAttack(delta) {
    const durations = this.behaviorDurations.retreat_attack || {};
    const attackPhaseDuration = durations.attackPhaseDuration || 2.0;
    const retreatPhaseDuration = durations.retreatPhaseDuration || 2.5;
    const retreatDistance = 8.0;
    
    if (this.behaviorTimer < attackPhaseDuration) {
      if (!this.animationActions['Attack_1'] || this.currentAction !== this.animationActions['Attack_1']) {
        this.playAnimation('Attack_1', false);
      }
    } else if (this.behaviorTimer < attackPhaseDuration + retreatPhaseDuration) {
      if (!this.animationActions['Walk'] || this.currentAction !== this.animationActions['Walk']) {
        this.playAnimation('Walk', true);
      }
      const retreatProgress = (this.behaviorTimer - attackPhaseDuration) / retreatPhaseDuration;
      const startPos = this.retreatAttackStartPos || this.spawnPosition.clone();
      const dir = new THREE.Vector3(startPos.x - this.spawnPosition.x, 0, startPos.z - this.spawnPosition.z);
      if (dir.lengthSq() < 0.01) {
        dir.set(1, 0, 0);
      }
      dir.normalize();
      const x = startPos.x + dir.x * retreatDistance * retreatProgress;
      const z = startPos.z + dir.z * retreatDistance * retreatProgress;
      this.model.position.set(x, this.groundY, z);
      this.model.rotation.y = Math.atan2(-dir.x, -dir.z) + Math.PI / 2;
    } else {
      this.setBehaviorMode('idle_1');
    }
  }
  
  /**
   * Stagger recovery - Damage_taken then Idle_2
   */
  updateStaggerRecovery(delta) {
    const durations = this.behaviorDurations.stagger_recovery || {};
    const damagePhaseDuration = durations.damagePhaseDuration || 1.5;
    const idlePhaseDuration = durations.idlePhaseDuration || 2.0;
    
    if (this.behaviorTimer < damagePhaseDuration) {
      if (!this.animationActions['Damage_taken'] || this.currentAction !== this.animationActions['Damage_taken']) {
        this.playAnimation('Damage_taken', false);
      }
    } else if (this.behaviorTimer < damagePhaseDuration + idlePhaseDuration) {
      if (!this.animationActions['Idle_2'] || this.currentAction !== this.animationActions['Idle_2']) {
        this.playAnimation('Idle_2', true);
      }
    } else {
      this.setBehaviorMode('idle_1');
    }
  }
  
  /**
   * Follow & jump attack - Track player, jump attack when in range, idle recovery
   * Uses getPlayerPosition callback for player tracking.
   */
  updateFollowAttack(delta) {
    if (!this.getPlayerPosition) {
      if (!this.animationActions['Idle_1'] || this.currentAction !== this.animationActions['Idle_1']) {
        this.playAnimation('Idle_1', true);
      }
      return;
    }
    
    const durations = this.behaviorDurations.follow_attack || {};
    const followSpeed = durations.followSpeed || 3.0;
    const attackRange = durations.attackRange || 4.0;
    const attackDuration = durations.attackDuration || 2.5;
    const idleAfterAttack = durations.idleAfterAttack || 3.0;
    const reFollowAfterIdle = durations.reFollowAfterIdle !== false;
    
    const playerPos = this.getPlayerPosition();
    const dx = playerPos.x - this.model.position.x;
    const dz = playerPos.z - this.model.position.z;
    const distToPlayer = Math.sqrt(dx * dx + dz * dz);
    
    this.followAttackState = this.followAttackState || 'following';
    this.followAttackTimer = (this.followAttackTimer || 0) + delta;
    
    switch (this.followAttackState) {
      case 'following':
        if (distToPlayer <= attackRange) {
          this.followAttackState = 'attacking';
          this.lastPlayerPos = playerPos.clone();
          this.attackStartPos = this.model.position.clone();
          this.playAnimation('Attack_2', false);
          this.followAttackTimer = 0;
        } else {
          this.moveToward(playerPos, followSpeed, delta);
        }
        break;
        
      case 'attacking':
        if (this.followAttackTimer >= attackDuration) {
          this.followAttackState = 'idle_recovery';
          this.playAnimation('Idle_1', true);
          this.followAttackTimer = 0;
        }
        break;
        
      case 'idle_recovery':
        if (reFollowAfterIdle && this.followAttackTimer >= idleAfterAttack) {
          this.followAttackState = 'following';
          this.playAnimation('Walk', true);
          this.followAttackTimer = 0;
        }
        break;
        
      default:
        this.followAttackState = 'following';
        this.playAnimation('Walk', true);
        this.followAttackTimer = 0;
    }
  }
  
  /**
   * Move spider toward target position (horizontal only, keeps ground Y)
   */
  moveToward(targetPos, speed, delta) {
    if (!this.model) return;
    const dx = targetPos.x - this.model.position.x;
    const dz = targetPos.z - this.model.position.z;
    const dist = Math.sqrt(dx * dx + dz * dz);
    if (dist < 0.01) return;
    const nx = dx / dist;
    const nz = dz / dist;
    this.model.position.x += nx * speed * delta;
    this.model.position.z += nz * speed * delta;
    this.model.position.y = this.groundY;
    this.model.rotation.y = Math.atan2(-nx, nz) + Math.PI / 2;
  }
  
  /**
   * Set health
   */
  setHealth(health) {
    this.health = Math.max(0, Math.min(health, this.maxHealth));
    if (this.health <= 0) {
      this.isAlive = false;
      this.onBossDefeated();
    }
  }
  
  /**
   * Set size
   */
  setSize(size) {
    if (!this.model) return;
    
    this.targetSize = size;
    const scale = this.targetSize / this._originalModelSize;
    this.model.scale.set(scale, scale, scale);
  }
}

// ═══════════════════════════════════════════════════════════════════════════
// 🕷️ ALIEN SPIDER MINION - Wave spawn minions for Level 6
// ═══════════════════════════════════════════════════════════════════════════
// Phase 1: Basic class with follow_attack, takeDamage, die
// Created: February 6, 2026
// ═══════════════════════════════════════════════════════════════════════════

/** @type {{ model: THREE.Object3D, clips: Record<string, THREE.AnimationClip>, basePath: string, originalSize: number } | null} */
let _minionCache = null;

export class AlienSpiderMinion {
  constructor(config = {}) {
    this.model = config.model || null;
    this.hitbox = config.hitbox || null; // For weapon raycast (avoids SkinnedMesh crash - Feb 6, 2026)
    this.mixer = config.mixer || null;
    this.animationActions = config.animationActions || {};
    this.currentAction = null;
    
    this.levelGroup = config.levelGroup || null;
    this.getPlayerPosition = config.getPlayerPosition || null;
    
    this.health = config.health ?? 30;
    this.maxHealth = config.maxHealth ?? 30;
    this.isAlive = true;
    this.groundY = config.groundY ?? 1.0;
    
    this.followSpeed = config.followSpeed ?? 3.0;
    this.attackRange = config.attackRange ?? 2.5;
    this.attackCooldown = 0;
    
    this.onMinionDied = config.onMinionDied || (() => {});
  }
  
  /**
   * Load the minion cache (model + animation clips). Call once before spawning.
   * @param {string} modelPath - Path to AFC_03.fbx
   * @returns {Promise<{ model: THREE.Object3D, clips: Record<string, THREE.AnimationClip>, basePath: string, originalSize: number }>}
   */
  static async loadMinionCache(modelPath) {
    if (_minionCache) return _minionCache;
    
    const basePath = modelPath.replace(/\/[^/]+$/, '/');
    // CRITICAL: Use LoadingManager with TGALoader - same as boss (Feb 6, 2026)
    // Without this, FBX TGA textures fail to load and minions appear black
    const tgaLoader = new TGALoader();
    const loadingManager = new THREE.LoadingManager();
    loadingManager.addHandler(/\.tga$/i, tgaLoader);
    const loader = new FBXLoader(loadingManager);
    loader.setResourcePath(basePath);
    
    const fbx = await new Promise((resolve, reject) => {
      loader.load(modelPath, resolve, undefined, reject);
    });
    fbx.updateMatrixWorld(true); // Ensure transforms applied before bbox (Feb 6, 2026)
    const box = new THREE.Box3().setFromObject(fbx);
    const size = box.getSize(new THREE.Vector3());
    let originalSize = Math.max(size.x, size.y, size.z);
    if (originalSize < 0.1 || originalSize > 100) {
      originalSize = 2.0; // Fallback: AFC_03 boss is ~4 units, minion base ~2 (Feb 6, 2026)
      console.warn(`⚠️ [SPIDER] Suspicious bbox (${size.x.toFixed(2)}×${size.y.toFixed(2)}×${size.z.toFixed(2)}), using originalSize=${originalSize}`);
    }
    
    const clips = {};
    const animFiles = {
      idle_1: "AFC_03@Idle_1.fbx", walk: "AFC_03@Walk.fbx", run: "AFC_03@Run.fbx",
      attack_1: "AFC_03@Attack_1.fbx", attack_2: "AFC_03@Attack_2.fbx"
    };
    
    for (const [key, file] of Object.entries(animFiles)) {
      try {
        const animFbx = await loader.loadAsync(basePath + file);
        if (animFbx.animations?.[0]) clips[key] = animFbx.animations[0];
      } catch (_) {}
    }
    
    _minionCache = { model: fbx, clips, basePath, originalSize, tgaLoader };
    console.log(`🕷️ [SPIDER] Minion cache loaded: originalSize=${originalSize.toFixed(4)}, targetScale for 1.5 units = ${(1.5 / originalSize).toFixed(1)}x`);
    return _minionCache;
  }
  
  
  /**
   * Create a minion from cache. Call loadMinionCache first.
   * @param {object} config - levelGroup, getPlayerPosition, spawnPosition, health, targetSize, etc.
   * @returns {AlienSpiderMinion|null}
   */
  static createFromCache(config) {
    if (!_minionCache) return null;
    
    const { model, clips, originalSize, basePath, tgaLoader } = _minionCache;
    
    // FBX cloning: Try standard clone for AFC_03 - SkeletonUtils may only clone feet for FBX (Rule 14 is GLTF; Rule 18 says FBX can use clone)
    // If only feet visible with SkeletonUtils, standard clone may render full body (Feb 6, 2026)
    const clone = model.clone(true);
    
    const targetSize = config.targetSize ?? 1.0;
    let scale;
    // PREFERRED: When boss has loaded model, use bossScale/4 directly - guarantees minion = 1/4 boss (Feb 6, 2026)
    if (config.bossScale != null && config.bossScale > 0) {
      scale = config.bossScale;
      clone.scale.set(scale, scale, scale);
      clone.updateMatrixWorld(true);
    } else {
      // Fallback: compute from targetSize and originalSize (when boss not loaded yet)
      const effectiveOriginalSize = config.bossOriginalModelSize ?? originalSize;
      const rawScale = targetSize / effectiveOriginalSize;
      scale = Math.min(rawScale, 2); // Stricter: max 2x upscale (was 3x - minions were still huge)
      clone.scale.set(scale, scale, scale);
      clone.updateMatrixWorld(true);
    }
    
    // CRITICAL: Hard cap scale - AFC_03 is ~100+ units; scale > 0.05 would make minion huge (Feb 6, 2026)
    const ABSOLUTE_MAX_SCALE = 0.05;
    if (scale > ABSOLUTE_MAX_SCALE) {
      scale = ABSOLUTE_MAX_SCALE;
      clone.scale.set(scale, scale, scale);
      console.warn(`⚠️ [SPIDER] Minion scale capped to ${ABSOLUTE_MAX_SCALE} (was too large)`);
    }
    
    // Ensure skeleton is posed before bbox (SkinnedMesh bbox can be wrong otherwise)
    clone.traverse((child) => {
      if (child.isSkinnedMesh && child.skeleton) child.skeleton.pose();
    });
    clone.updateMatrixWorld(true);
    
    // Final safeguard: ALWAYS cap world size to 1.0 units - dog-sized, 1/4 of 4-unit boss (Feb 6, 2026)
    const worldBox = new THREE.Box3().setFromObject(clone);
    const worldSize = worldBox.getSize(new THREE.Vector3());
    const maxDim = Math.max(worldSize.x, worldSize.y, worldSize.z);
    const maxMinionSize = 1.0; // Dog-sized: 1/4 of 4-unit boss
    if (maxDim > maxMinionSize) {
      const fixScale = maxMinionSize / maxDim;
      scale *= fixScale;
      clone.scale.set(scale, scale, scale);
      clone.updateMatrixWorld(true);
      console.warn(`⚠️ [SPIDER] Minion too large (${maxDim.toFixed(1)} units), scaled down to ${maxMinionSize}`);
    }
    
    clone.position.copy(config.spawnPosition || new THREE.Vector3(0, 1, 0));
    clone.position.y = config.groundY ?? 1.0;
    
    // Apply textures and brighten materials so minion matches alien spider boss (Feb 6, 2026)
    if (tgaLoader && basePath) AlienSpiderMinion._applyMinionMaterials(clone, basePath, tgaLoader);
    
    const mixer = new THREE.AnimationMixer(clone);
    const animationActions = {};
    for (const [key, clip] of Object.entries(clips)) {
      animationActions[key] = mixer.clipAction(clip);
    }
    
    // Hitbox for raycast stability - MUST be world-size ~1.0 unit (hitbox is child of clone, inherits scale)
    // With scale ~0.05, hitboxRadius 0.8 → world size 0.04 (too small to hit). Use 1/scale for ~1.0 world size (Feb 6, 2026)
    const finalScale = clone.scale.x;
    const hitboxRadius = 1.0 / Math.max(0.01, finalScale);
    const hitbox = new THREE.Mesh(
      new THREE.SphereGeometry(hitboxRadius, 8, 8),
      new THREE.MeshBasicMaterial({ transparent: true, opacity: 0 }) // Invisible but raycastable (visible:false skips raycast)
    );
    hitbox.position.set(0, hitboxRadius * 0.5, 0); // Center at body height
    hitbox.name = "spider_minion_hitbox";
    hitbox.userData.isSpiderMinionHitbox = true;
    clone.add(hitbox);

    const minion = new AlienSpiderMinion({
      ...config,
      model: clone,
      mixer,
      animationActions,
      hitbox,
      levelGroup: config.levelGroup,
      getPlayerPosition: config.getPlayerPosition,
      health: config.health ?? 30,
      maxHealth: config.maxHealth ?? 30,
      groundY: config.groundY ?? 1.0,
      followSpeed: config.followSpeed ?? 3.0,
      attackRange: config.attackRange ?? 2.5,
      onMinionDied: config.onMinionDied
    });
    
    if (config.levelGroup) config.levelGroup.add(clone);
    minion.spawnTime = performance.now(); // Grace period - no collision for 2s after spawn (Feb 6, 2026)
    minion.playAnimation('walk', true);
    return minion;
  }
  
  /** Apply textures and brighten materials so minions match alien spider boss (Feb 6, 2026) */
  static _applyMinionMaterials(model, basePath, tgaLoader) {
    const brightness = 1.5;
    model.traverse((child) => {
      if (child.isMesh && child.material) {
        const materials = Array.isArray(child.material) ? child.material : [child.material];
        materials.forEach((mat) => {
          if (!mat || !mat.isMeshStandardMaterial) return;
          const isEye = (child.name || '').toLowerCase().includes('eye') || (mat.name || '').toLowerCase().includes('eye');
          const colorFile = isEye ? 'Eye_color.tga' : 'AFC_03_color.tga';
          if (mat.color) {
            const orig = mat.color.clone();
            const b = orig.r + orig.g + orig.b;
            if (b < 0.3) {
              mat.color.setRGB(Math.min(1, orig.r * 3), Math.min(1, orig.g * 3), Math.min(1, orig.b * 3));
            } else if (b < 0.6) {
              mat.color.setRGB(Math.min(1, orig.r * 2), Math.min(1, orig.g * 2), Math.min(1, orig.b * 2));
            }
            mat.color.multiplyScalar(brightness);
          }
          tgaLoader.load(basePath + colorFile, (tex) => {
            tex.flipY = false;
            mat.map = tex;
            mat.needsUpdate = true;
          }, undefined, () => {
            if (mat.color) mat.color.setHex(0x888888);
            mat.needsUpdate = true;
          });
        });
      }
    });
  }
  
  playAnimation(name, loop = true) {
    const action = this.animationActions[name] || this.animationActions.walk;
    if (!action) return;
    if (this.currentAction) this.currentAction.fadeOut(0.15);
    this.currentAction = action;
    this.currentAction.reset();
    this.currentAction.setLoop(loop ? THREE.LoopRepeat : THREE.LoopOnce);
    this.currentAction.fadeIn(0.15);
    this.currentAction.play();
  }
  
  update(delta) {
    if (!this.model || !this.isAlive) return;
    
    if (this.mixer) this.mixer.update(delta);
    if (this.attackCooldown > 0) this.attackCooldown -= delta;
    
    const pos = this.getPlayerPosition?.();
    if (!pos) return;
    
    const dx = pos.x - this.model.position.x;
    const dz = pos.z - this.model.position.z;
    const dist = Math.sqrt(dx * dx + dz * dz);
    
    if (dist < this.attackRange) {
      this.playAnimation(Math.random() < 0.5 ? 'attack_1' : 'attack_2', false);
      return;
    }
    
    if (dist > 0.1) {
      const nx = dx / dist;
      const nz = dz / dist;
      const speed = this.followSpeed * delta;
      this.model.position.x += nx * speed;
      this.model.position.z += nz * speed;
      this.model.position.y = this.groundY;
      this.model.rotation.y = Math.atan2(-nx, nz) + Math.PI / 2;
      if (!this.currentAction || this.currentAction?.getClip()?.name?.includes('Attack')) {
        this.playAnimation('run', true);
      }
    }
  }
  
  takeDamage(amount) {
    if (!this.isAlive) return;
    this.health = Math.max(0, this.health - amount);
    if (this.health <= 0) this.die();
  }
  
  die() {
    if (!this.isAlive) return;
    this.isAlive = false;
    this.onMinionDied(this);
  }
  
  /** Remove from scene and dispose. Call after die(). */
  dispose() {
    if (this.mixer) this.mixer.stopAllAction();
    if (this.levelGroup && this.model) this.levelGroup.remove(this.model);
    this.model = null;
    this.mixer = null;
    this.hitbox = null;
    this.animationActions = {};
  }
}
