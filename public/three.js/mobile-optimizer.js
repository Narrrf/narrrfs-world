/**
 * ============================================================================
 * MOBILE OPTIMIZER - Performance & Memory Optimization for Mobile Devices
 * ============================================================================
 * 
 * 📅 CREATED: January 19, 2026
 * 🎯 PURPOSE: Prevent RAM crashes on mobile devices by aggressive optimization
 * 
 * ============================================================================
 * 🎯 OPTIMIZATION TARGETS
 * ============================================================================
 * 
 * 1. **Textures** - Biggest memory consumer
 *    - Reduce resolution by 50-75%
 *    - Disable anisotropic filtering
 *    - Use compressed formats
 * 
 * 2. **Shadows** - Heavy GPU/memory cost
 *    - Disable or use low-res shadow maps
 *    - Reduce shadow distance
 * 
 * 3. **Grass System** - Can be 100s of MB
 *    - Reduce density by 75-90%
 *    - Or disable completely for low-end devices
 * 
 * 4. **Geometry** - Polygon count
 *    - Use lower LOD models
 *    - Reduce instance counts
 * 
 * 5. **Rendering** - GPU performance
 *    - Lower pixel ratio
 *    - Disable post-processing
 *    - Reduce draw calls
 * 
 * ============================================================================
 */

export class MobileOptimizer {
  constructor(config = {}) {
    this.config = {
      renderer: config.renderer,
      scene: config.scene,
      isMobile: config.isMobile || false,
      aggressiveMode: config.aggressiveMode || false, // For very low-end devices
      ...config
    };
    
    this.optimizationLevel = 'none'; // none, normal, aggressive
    this.originalSettings = {};
    
    console.log('📱 [MOBILE OPTIMIZER] Initialized', {
      isMobile: this.config.isMobile,
      aggressiveMode: this.config.aggressiveMode
    });
  }
  
  /**
   * Detect device performance tier
   * @returns {string} 'high', 'medium', 'low'
   */
  detectPerformanceTier() {
    if (!this.config.isMobile) return 'high';
    
    // Check device memory (if available)
    const deviceMemory = navigator.deviceMemory || 4; // GB, default to 4GB
    
    // Check hardware concurrency (CPU cores)
    const cores = navigator.hardwareConcurrency || 4;
    
    // Check screen size (proxy for device power)
    const screenSize = window.screen.width * window.screen.height;
    
    console.log('📱 [MOBILE OPTIMIZER] Device specs:', {
      memory: `${deviceMemory}GB`,
      cores: cores,
      screenSize: `${window.screen.width}x${window.screen.height}`,
      screenPixels: screenSize
    });
    
    // Low-end: <=2GB RAM, <=4 cores, small screen
    if (deviceMemory <= 2 || cores <= 4 && screenSize < 1920 * 1080) {
      console.log('📱 [MOBILE OPTIMIZER] Tier: LOW (aggressive optimization)');
      return 'low';
    }
    
    // Medium: 3-4GB RAM, 4-8 cores
    if (deviceMemory <= 4 || cores <= 8) {
      console.log('📱 [MOBILE OPTIMIZER] Tier: MEDIUM (normal optimization)');
      return 'medium';
    }
    
    // High-end mobile (rare)
    console.log('📱 [MOBILE OPTIMIZER] Tier: HIGH (light optimization)');
    return 'high';
  }
  
  /**
   * Apply all mobile optimizations
   */
  optimize() {
    if (!this.config.isMobile) {
      console.log('📱 [MOBILE OPTIMIZER] Not mobile - skipping optimization');
      return;
    }
    
    const tier = this.detectPerformanceTier();
    this.optimizationLevel = tier === 'low' ? 'aggressive' : 'normal';
    
    console.log(`📱 [MOBILE OPTIMIZER] Applying ${this.optimizationLevel} optimization...`);
    
    // Apply optimizations
    this.optimizeRenderer();
    this.optimizeShadows();
    this.optimizeTextures();
    
    console.log('✅ [MOBILE OPTIMIZER] Optimization complete');
  }
  
  /**
   * Optimize renderer settings
   */
  optimizeRenderer() {
    const renderer = this.config.renderer;
    if (!renderer) return;
    
    console.log('📱 [MOBILE OPTIMIZER] Optimizing renderer...');
    
    // Store original settings
    this.originalSettings.pixelRatio = renderer.getPixelRatio();
    
    // Set mobile-appropriate pixel ratio
    const pixelRatio = this.optimizationLevel === 'aggressive' ? 1 : 1;
    renderer.setPixelRatio(pixelRatio);
    
    console.log(`✅ [MOBILE OPTIMIZER] Pixel ratio: ${this.originalSettings.pixelRatio} → ${pixelRatio}`);
  }
  
  /**
   * Optimize shadow settings
   */
  optimizeShadows() {
    const renderer = this.config.renderer;
    if (!renderer) return;
    
    console.log('📱 [MOBILE OPTIMIZER] Optimizing shadows...');
    
    // Store original settings
    this.originalSettings.shadowsEnabled = renderer.shadowMap.enabled;
    this.originalSettings.shadowMapType = renderer.shadowMap.type;
    
    if (this.optimizationLevel === 'aggressive') {
      // Disable shadows completely on low-end devices
      renderer.shadowMap.enabled = false;
      console.log('✅ [MOBILE OPTIMIZER] Shadows: DISABLED (aggressive mode)');
    } else {
      // Keep shadows but use low quality
      renderer.shadowMap.enabled = true;
      renderer.shadowMap.type = THREE.BasicShadowMap; // Fastest shadow type
      console.log('✅ [MOBILE OPTIMIZER] Shadows: LOW QUALITY (normal mode)');
    }
  }
  
  /**
   * Optimize textures in the scene
   */
  optimizeTextures() {
    const scene = this.config.scene;
    if (!scene) return;
    
    console.log('📱 [MOBILE OPTIMIZER] Optimizing textures...');
    
    let texturesOptimized = 0;
    
    scene.traverse((object) => {
      if (object.isMesh) {
        const materials = Array.isArray(object.material) ? object.material : [object.material];
        
        materials.forEach((material) => {
          if (!material) return;
          
          // Optimize all texture properties
          const textureProps = [
            'map', 'normalMap', 'roughnessMap', 'metalnessMap',
            'emissiveMap', 'aoMap', 'bumpMap', 'displacementMap'
          ];
          
          textureProps.forEach((prop) => {
            const texture = material[prop];
            if (texture && texture.isTexture) {
              this.optimizeTexture(texture);
              texturesOptimized++;
            }
          });
        });
      }
    });
    
    console.log(`✅ [MOBILE OPTIMIZER] Textures optimized: ${texturesOptimized}`);
  }
  
  /**
   * Optimize a single texture
   */
  optimizeTexture(texture) {
    // Disable anisotropic filtering (saves memory and GPU)
    if (texture.anisotropy > 0) {
      texture.anisotropy = 1;
    }
    
    // Use lower quality mipmaps
    texture.generateMipmaps = this.optimizationLevel !== 'aggressive';
    
    // Mark for update
    texture.needsUpdate = true;
  }
  
  /**
   * Get grass density multiplier for mobile
   * @returns {number} Multiplier (0.0 to 1.0)
   */
  getGrassDensityMultiplier() {
    if (!this.config.isMobile) return 1.0;
    
    if (this.optimizationLevel === 'aggressive') {
      return 0.0; // Disable grass completely
    } else {
      return 0.25; // 25% density (75% reduction)
    }
  }
  
  /**
   * Should grass be enabled?
   * @returns {boolean}
   */
  shouldEnableGrass() {
    if (!this.config.isMobile) return true;
    return this.optimizationLevel !== 'aggressive';
  }
  
  /**
   * Get shadow map size for mobile
   * @returns {number} Shadow map resolution
   */
  getShadowMapSize() {
    if (!this.config.isMobile) return 2048;
    
    if (this.optimizationLevel === 'aggressive') {
      return 0; // No shadows
    } else {
      return 512; // Low-res shadows
    }
  }
  
  /**
   * Get maximum texture size for mobile
   * @returns {number} Max texture size in pixels
   */
  getMaxTextureSize() {
    if (!this.config.isMobile) return 2048;
    
    if (this.optimizationLevel === 'aggressive') {
      return 512; // Very low res
    } else {
      return 1024; // Medium res
    }
  }
  
  /**
   * Log memory usage (if available)
   */
  logMemoryUsage() {
    if (performance.memory) {
      const used = (performance.memory.usedJSHeapSize / 1024 / 1024).toFixed(2);
      const total = (performance.memory.totalJSHeapSize / 1024 / 1024).toFixed(2);
      const limit = (performance.memory.jsHeapSizeLimit / 1024 / 1024).toFixed(2);
      
      console.log(`📊 [MEMORY] ${used}MB / ${total}MB (limit: ${limit}MB)`);
      
      // Warning if using >80% of available memory
      const usage = (performance.memory.usedJSHeapSize / performance.memory.jsHeapSizeLimit) * 100;
      if (usage > 80) {
        console.warn(`⚠️ [MEMORY] High memory usage: ${usage.toFixed(1)}%`);
      }
    }
  }
  
  /**
   * Restore original settings (for desktop mode)
   */
  restore() {
    const renderer = this.config.renderer;
    if (!renderer) return;
    
    console.log('📱 [MOBILE OPTIMIZER] Restoring original settings...');
    
    if (this.originalSettings.pixelRatio !== undefined) {
      renderer.setPixelRatio(this.originalSettings.pixelRatio);
    }
    
    if (this.originalSettings.shadowsEnabled !== undefined) {
      renderer.shadowMap.enabled = this.originalSettings.shadowsEnabled;
    }
    
    if (this.originalSettings.shadowMapType !== undefined) {
      renderer.shadowMap.type = this.originalSettings.shadowMapType;
    }
    
    console.log('✅ [MOBILE OPTIMIZER] Original settings restored');
  }
}
