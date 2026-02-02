/**
 * ============================================================================
 * ALIEN SPIDER BOSS - Level 6 Ground Boss
 * ============================================================================
 *
 * Version: 2026-02-01-HEADER-REFRESH
 * Lines: ~1,090
 * Used by: main.js (alienSpiderBoss) – Level 6 only
 *
 * ============================================================================
 * 🤖 AI & HUMAN NAVIGATION – QUICK FIND
 * ============================================================================
 *
 *   loadModel(path)          ~100  Load AFC_03.fbx (TGA textures)
 *   update(delta)           ~180  7 behavior patterns
 *   setBehaviorMode         ~220  idle_1, walk_patrol, attack_1, etc.
 *
 * ============================================================================
 * 🎯 PURPOSE – Similar to Phoenix 2.0, but ground-based
 * 
 * This module implements the Alien Spider boss for Level 6, featuring:
 * - 7 behavior patterns with separate animations
 * - 3 texture variations (Default, Fur_1, Fur_2)
 * - TGA texture loading support (TGALoader)
 * - Brightness control for material visibility
 * - Full GUI integration with settings persistence
 * 
 * ✅ BEHAVIOR PATTERNS (7 patterns):
 * - idle_1: Idle animation 1
 * - idle_2: Idle animation 2
 * - walk_patrol: Walking patrol in circle
 * - run_patrol: Running patrol (faster)
 * - attack_1: Attack pattern 1 (melee)
 * - attack_2: Attack pattern 2 (jump attack)
 * - damage_reaction: Damage taken reaction
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

console.log("🕷️🕷️🕷️ [ALIEN_SPIDER.JS] FILE LOADED - December 20, 2025 VERSION 🕷️🕷️🕷️");

export class AlienSpiderBoss {
  constructor(config = {}) {
    this.scene = config.scene;
    this.camera = config.camera;
    this.levelGroup = config.levelGroup || null;
    this.player = config.player || null; // Player object for tracking
    this.resolveAssetPath = config.resolveAssetPath || ((path) => path); // Path resolver function
    
    // Model
    this.model = null;
    this.mixer = null;
    this.animationActions = {};
    this.currentAction = null;
    
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
    
    // Animation file paths (FBX files) - will be resolved when needed
    // Store relative paths, resolve when loading
    this.animationPathsRelative = {
      idle_1: "textures/3d models/Alien Spider 1/AFC_03/AFC_03@Idle_1.fbx",
      idle_2: "textures/3d models/Alien Spider 1/AFC_03/AFC_03@Idle_2.fbx",
      walk: "textures/3d models/Alien Spider 1/AFC_03/AFC_03@Walk.fbx",
      run: "textures/3d models/Alien Spider 1/AFC_03/AFC_03@Run.fbx",
      attack_1: "textures/3d models/Alien Spider 1/AFC_03/AFC_03@Attack_1.fbx",
      attack_2: "textures/3d models/Alien Spider 1/AFC_03/AFC_03@Attack_2.fbx",
      damage: "textures/3d models/Alien Spider 1/AFC_03/AFC_03@Damage_taken.fbx"
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
    
    const basePath = this.resolveAssetPath("textures/3d models/Alien Spider 1/AFC_03/");
    
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
    
    const basePath = this.resolveAssetPath("textures/3d models/Alien Spider 1/AFC_03/");
    
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
    return new Promise((resolve, reject) => {
      // Use LoadingManager with TGA handler so FBXLoader can load TGA textures
      const loader = new FBXLoader(this.loadingManager);
      
      // Set resource path so FBXLoader knows where to find textures
      const basePath = this.resolveAssetPath("textures/3d models/Alien Spider 1/AFC_03/");
      loader.setResourcePath(basePath);
      
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
    // Use LoadingManager with TGA handler for animation files too (in case they have textures)
    const loader = new FBXLoader(this.loadingManager);
    const basePath = this.resolveAssetPath("textures/3d models/Alien Spider 1/AFC_03/");
    loader.setResourcePath(basePath);
    
    const animationPromises = [];
    
    // Resolve animation paths using resolveAssetPath
    for (const [key, relativePath] of Object.entries(this.animationPathsRelative)) {
      const path = this.resolveAssetPath(relativePath);
      const promise = loader.loadAsync(path).then((fbx) => {
        if (fbx.animations && fbx.animations.length > 0) {
          // Extract animation clip
          const animation = fbx.animations[0];
          
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
    
    // CRITICAL FIX (January 6, 2026): Debug mixer update to verify animations are running
    if (!this.mixer) {
      console.warn("⚠️ [ALIEN_SPIDER] update() called but mixer not initialized yet");
      return; // Can't update animations without mixer
    }
    
    // Update animation mixer (CRITICAL: This makes animations play!)
    this.mixer.update(delta);
    
    // Debug: Log mixer update every 60 frames (~1 second) when animations not playing
    if (!window.spiderMixerDebugShown) {
      const currentActionRunning = this.currentAction && this.currentAction.isRunning();
      if (!currentActionRunning && this.currentAction) {
        console.warn("⚠️ [ALIEN_SPIDER] Mixer updating but current action not running:", {
          actionName: this.currentAction.getClip().name,
          isRunning: currentActionRunning,
          delta: delta.toFixed(4),
          behaviorMode: this.behaviorMode
        });
        window.spiderMixerDebugShown = true;
        setTimeout(() => { window.spiderMixerDebugShown = false; }, 2000);
      }
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

