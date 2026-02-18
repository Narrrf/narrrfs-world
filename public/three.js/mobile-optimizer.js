/**
 * ============================================================================
 * MOBILE OPTIMIZER - Performance & Memory for Mobile (Module-safe)
 * ============================================================================
 * Version: 2026-02-18-MODULE-SAFE
 *
 * Key goals:
 * - Never depend on global THREE
 * - Never crash if called early or in mobile-first boot
 * - Idempotent optimize() (safe to call multiple times)
 * - Restore() returns to original renderer/light settings
 * ============================================================================
 */

export class MobileOptimizer {
  /**
   * @param {Object} config
   * @param {import('three').WebGLRenderer} config.renderer
   * @param {import('three').Scene} config.scene
   * @param {boolean} config.isMobile
   * @param {boolean} [config.aggressiveMode=false]
   * @param {Object} [config.THREE]  // pass imported THREE here (IMPORTANT)
   * @param {number} [config.maxDPR=1] // cap DPR on mobile
   * @param {boolean} [config.enableShadows=true]
   * @param {boolean} [config.optimizeTextures=true]
   * @param {boolean} [config.optimizeMaterials=true]
   * @param {boolean} [config.optimizeLights=true]
   */
  constructor(config = {}) {
    this.config = {
      renderer: config.renderer || null,
      scene: config.scene || null,
      isMobile: !!config.isMobile,
      aggressiveMode: !!config.aggressiveMode,
      THREE: config.THREE || null,

      // sensible defaults for games
      maxDPR: typeof config.maxDPR === "number" ? config.maxDPR : 1,
      enableShadows: config.enableShadows !== false,
      optimizeTextures: config.optimizeTextures !== false,
      optimizeMaterials: config.optimizeMaterials !== false,
      optimizeLights: config.optimizeLights !== false,

      ...config,
    };

    // Tier & state
    this.performanceTier = "high"; // high | medium | low
    this.optimizationLevel = "none"; // none | normal | aggressive
    this._applied = false;

    // Cache originals for restore()
    this.original = {
      renderer: {},
      lights: new Map(),     // light.uuid -> { castShadow, mapSizeX, mapSizeY, bias, normalBias, radius }
      textures: new Map(),   // texture.uuid -> { anisotropy, generateMipmaps, minFilter, magFilter }
      materials: new Map(),  // material.uuid -> { envMapIntensity, flatShading, dithering }
    };

    console.log("📱 [MOBILE OPTIMIZER] Initialized", {
      isMobile: this.config.isMobile,
      aggressiveMode: this.config.aggressiveMode,
      hasTHREE: !!this.config.THREE,
    });
  }

  // ---------------------------------------------------------------------------
  // Public API
  // ---------------------------------------------------------------------------

  /**
   * Safe to call multiple times.
   */
  optimize() {
    if (!this.config.isMobile) {
      console.log("📱 [MOBILE OPTIMIZER] Not mobile - skipping optimization");
      return;
    }

    // If already applied, avoid stacking changes
    if (this._applied) {
      console.log("📱 [MOBILE OPTIMIZER] Already applied - skipping re-apply");
      return;
    }

    this.performanceTier = this.detectPerformanceTier();
    this.optimizationLevel =
      this.config.aggressiveMode || this.performanceTier === "low" ? "aggressive" : "normal";

    console.log(`📱 [MOBILE OPTIMIZER] Applying "${this.optimizationLevel}" optimizations...`);

    this.optimizeRenderer();
    if (this.config.enableShadows) this.optimizeShadows();
    if (this.config.optimizeLights) this.optimizeLights();
    if (this.config.optimizeMaterials) this.optimizeMaterials();
    if (this.config.optimizeTextures) this.optimizeTextures();

    this._applied = true;

    this.logMemoryUsage();
    console.log("✅ [MOBILE OPTIMIZER] Optimization complete");
  }

  /**
   * Restore original settings after switching back to desktop or for debugging.
   */
  restore() {
    const renderer = this.config.renderer;
    const scene = this.config.scene;

    if (!renderer) return;

    console.log("📱 [MOBILE OPTIMIZER] Restoring original settings...");

    // Renderer
    if (this.original.renderer.pixelRatio != null) renderer.setPixelRatio(this.original.renderer.pixelRatio);
    if (this.original.renderer.shadowEnabled != null) renderer.shadowMap.enabled = this.original.renderer.shadowEnabled;
    if (this.original.renderer.shadowType != null) renderer.shadowMap.type = this.original.renderer.shadowType;

    // Lights
    if (scene) {
      scene.traverse((obj) => {
        if (!obj || !obj.isLight) return;
        const saved = this.original.lights.get(obj.uuid);
        if (!saved) return;

        if (saved.castShadow != null) obj.castShadow = saved.castShadow;
        if (obj.shadow && saved.mapSizeX != null && saved.mapSizeY != null) {
          obj.shadow.mapSize.set(saved.mapSizeX, saved.mapSizeY);
          if (saved.bias != null) obj.shadow.bias = saved.bias;
          if (saved.normalBias != null) obj.shadow.normalBias = saved.normalBias;
          if (saved.radius != null) obj.shadow.radius = saved.radius;
          obj.shadow.needsUpdate = true;
        }
      });
    }

    // Textures
    if (scene) {
      scene.traverse((obj) => {
        if (!obj || !obj.isMesh) return;
        const mats = Array.isArray(obj.material) ? obj.material : [obj.material];
        for (const mat of mats) {
          if (!mat) continue;
          for (const tex of this._getMaterialTextures(mat)) {
            const saved = tex && tex.uuid ? this.original.textures.get(tex.uuid) : null;
            if (!saved) continue;

            if (saved.anisotropy != null) tex.anisotropy = saved.anisotropy;
            if (saved.generateMipmaps != null) tex.generateMipmaps = saved.generateMipmaps;
            if (saved.minFilter != null) tex.minFilter = saved.minFilter;
            if (saved.magFilter != null) tex.magFilter = saved.magFilter;
            tex.needsUpdate = true;
          }

          // Materials
          const mSaved = mat.uuid ? this.original.materials.get(mat.uuid) : null;
          if (mSaved) {
            if (mSaved.envMapIntensity != null && "envMapIntensity" in mat) mat.envMapIntensity = mSaved.envMapIntensity;
            if (mSaved.flatShading != null && "flatShading" in mat) mat.flatShading = mSaved.flatShading;
            if (mSaved.dithering != null && "dithering" in mat) mat.dithering = mSaved.dithering;
            mat.needsUpdate = true;
          }
        }
      });
    }

    this._applied = false;
    console.log("✅ [MOBILE OPTIMIZER] Restored");
  }

  // ---------------------------------------------------------------------------
  // Tier detection
  // ---------------------------------------------------------------------------

  detectPerformanceTier() {
    // If not mobile, treat as high
    if (!this.config.isMobile) return "high";

    const deviceMemory = navigator.deviceMemory || 4; // GB
    const cores = navigator.hardwareConcurrency || 4;
    const pixels = (window.screen?.width || 0) * (window.screen?.height || 0);

    console.log("📱 [MOBILE OPTIMIZER] Device specs", {
      deviceMemory,
      cores,
      screen: `${window.screen?.width}x${window.screen?.height}`,
      pixels,
    });

    // conservative: many phones lie about power
    const lowMem = deviceMemory <= 2;
    const lowCPU = cores <= 4;
    const smallScreen = pixels > 0 && pixels < 1920 * 1080;

    if (lowMem || (lowCPU && smallScreen)) return "low";
    if (deviceMemory <= 4 || cores <= 8) return "medium";
    return "high";
  }

  // ---------------------------------------------------------------------------
  // Optimizers
  // ---------------------------------------------------------------------------

  optimizeRenderer() {
    const renderer = this.config.renderer;
    if (!renderer) return;

    // Save originals once
    if (this.original.renderer.pixelRatio == null) {
      this.original.renderer.pixelRatio = renderer.getPixelRatio();
    }

    // Cap DPR on mobile
    const cap = this.optimizationLevel === "aggressive" ? 1 : this.config.maxDPR;
    const dpr = Math.min(window.devicePixelRatio || 1, cap);
    renderer.setPixelRatio(dpr);

    console.log(`✅ [MOBILE OPTIMIZER] DPR ${this.original.renderer.pixelRatio} → ${dpr}`);
  }

  optimizeShadows() {
    const renderer = this.config.renderer;
    if (!renderer) return;

    // Save originals once
    if (this.original.renderer.shadowEnabled == null) {
      this.original.renderer.shadowEnabled = renderer.shadowMap.enabled;
      this.original.renderer.shadowType = renderer.shadowMap.type;
    }

    // Aggressive: disable shadows entirely
    if (this.optimizationLevel === "aggressive") {
      renderer.shadowMap.enabled = false;
      console.log("✅ [MOBILE OPTIMIZER] Shadows disabled (aggressive)");
      return;
    }

    // Normal: keep shadows but reduce quality safely
    renderer.shadowMap.enabled = true;

    const THREE = this.config.THREE;
    if (THREE && THREE.BasicShadowMap) {
      renderer.shadowMap.type = THREE.BasicShadowMap;
      console.log("✅ [MOBILE OPTIMIZER] ShadowMap type set to BasicShadowMap");
    } else {
      // Never crash if THREE missing
      console.warn("⚠️ [MOBILE OPTIMIZER] THREE not provided; leaving shadowMap.type unchanged");
    }
  }

  optimizeLights() {
    const scene = this.config.scene;
    if (!scene) return;

    const mapSize = this.getShadowMapSize();

    scene.traverse((obj) => {
      if (!obj || !obj.isLight) return;

      // Save original per light once
      if (!this.original.lights.has(obj.uuid)) {
        const saved = {
          castShadow: obj.castShadow,
          mapSizeX: obj.shadow?.mapSize?.x,
          mapSizeY: obj.shadow?.mapSize?.y,
          bias: obj.shadow?.bias,
          normalBias: obj.shadow?.normalBias,
          radius: obj.shadow?.radius,
        };
        this.original.lights.set(obj.uuid, saved);
      }

      // If shadows disabled, ensure lights don't cast
      if (this.optimizationLevel === "aggressive") {
        obj.castShadow = false;
        return;
      }

      // Reduce map sizes if light uses shadows
      if (obj.castShadow && obj.shadow && mapSize > 0) {
        obj.shadow.mapSize.set(mapSize, mapSize);

        // Bias tweaks reduce acne without expensive PCF
        obj.shadow.bias = -0.0002;
        obj.shadow.normalBias = 0.02;

        // radius helps soften; keep small for perf
        obj.shadow.radius = 1;

        obj.shadow.needsUpdate = true;
      }
    });

    console.log(`✅ [MOBILE OPTIMIZER] Lights optimized (shadowMapSize=${mapSize})`);
  }

  optimizeMaterials() {
    const scene = this.config.scene;
    if (!scene) return;

    let count = 0;

    scene.traverse((obj) => {
      if (!obj || !obj.isMesh) return;
      const mats = Array.isArray(obj.material) ? obj.material : [obj.material];

      for (const mat of mats) {
        if (!mat) continue;

        if (!this.original.materials.has(mat.uuid)) {
          this.original.materials.set(mat.uuid, {
            envMapIntensity: "envMapIntensity" in mat ? mat.envMapIntensity : null,
            flatShading: "flatShading" in mat ? mat.flatShading : null,
            dithering: "dithering" in mat ? mat.dithering : null,
          });
        }

        // Aggressive: cheaper shading
        if (this.optimizationLevel === "aggressive") {
          if ("envMapIntensity" in mat) mat.envMapIntensity = Math.min(mat.envMapIntensity || 0, 0.5);
          if ("dithering" in mat) mat.dithering = false;
          // flatShading changes look; only do in aggressive
          if ("flatShading" in mat) mat.flatShading = true;
        } else {
          // Normal: keep look, just reduce a bit
          if ("envMapIntensity" in mat) mat.envMapIntensity = Math.min(mat.envMapIntensity || 0, 1.0);
          if ("dithering" in mat) mat.dithering = false;
        }

        mat.needsUpdate = true;
        count++;
      }
    });

    console.log(`✅ [MOBILE OPTIMIZER] Materials optimized: ${count}`);
  }

  optimizeTextures() {
    const scene = this.config.scene;
    if (!scene) return;

    const THREE = this.config.THREE;
    const useMipmaps = this.optimizationLevel !== "aggressive";
    let texturesOptimized = 0;

    scene.traverse((obj) => {
      if (!obj || !obj.isMesh) return;

      const mats = Array.isArray(obj.material) ? obj.material : [obj.material];
      for (const mat of mats) {
        if (!mat) continue;

        for (const tex of this._getMaterialTextures(mat)) {
          if (!tex || !tex.isTexture || !tex.uuid) continue;

          // Save once
          if (!this.original.textures.has(tex.uuid)) {
            this.original.textures.set(tex.uuid, {
              anisotropy: tex.anisotropy,
              generateMipmaps: tex.generateMipmaps,
              minFilter: tex.minFilter,
              magFilter: tex.magFilter,
            });
          }

          // Lower anisotropy
          tex.anisotropy = 1;

          // Mipmaps: off in aggressive
          tex.generateMipmaps = useMipmaps;

          // Filters: use fastest safe defaults if THREE is available
          if (THREE) {
            // Aggressive: no mipmaps + linear
            if (!useMipmaps && THREE.LinearFilter) {
              tex.minFilter = THREE.LinearFilter;
              tex.magFilter = THREE.LinearFilter;
            } else {
              // Normal: keep decent look, still fast
              if (THREE.LinearMipmapLinearFilter) tex.minFilter = THREE.LinearMipmapLinearFilter;
              if (THREE.LinearFilter) tex.magFilter = THREE.LinearFilter;
            }
          }

          tex.needsUpdate = true;
          texturesOptimized++;
        }
      }
    });

    console.log(`✅ [MOBILE OPTIMIZER] Textures optimized: ${texturesOptimized}`);
  }

  // ---------------------------------------------------------------------------
  // Helpers used by the rest of the game
  // ---------------------------------------------------------------------------

  getGrassDensityMultiplier() {
    if (!this.config.isMobile) return 1.0;
    return this.optimizationLevel === "aggressive" ? 0.0 : 0.25;
  }

  shouldEnableGrass() {
    if (!this.config.isMobile) return true;
    return this.optimizationLevel !== "aggressive";
  }

  getShadowMapSize() {
    if (!this.config.isMobile) return 2048;
    return this.optimizationLevel === "aggressive" ? 0 : 512;
  }

  getMaxTextureSize() {
    if (!this.config.isMobile) return 2048;
    return this.optimizationLevel === "aggressive" ? 512 : 1024;
  }

  logMemoryUsage() {
    // Only Chrome exposes performance.memory (and not always)
    const mem = performance && performance.memory ? performance.memory : null;
    if (!mem) return;

    const used = (mem.usedJSHeapSize / 1024 / 1024).toFixed(1);
    const total = (mem.totalJSHeapSize / 1024 / 1024).toFixed(1);
    const limit = (mem.jsHeapSizeLimit / 1024 / 1024).toFixed(1);

    console.log(`📊 [MEMORY] used ${used}MB / total ${total}MB (limit ${limit}MB)`);

    const pct = (mem.usedJSHeapSize / mem.jsHeapSizeLimit) * 100;
    if (pct > 80) console.warn(`⚠️ [MEMORY] High heap usage: ${pct.toFixed(1)}%`);
  }

  // ---------------------------------------------------------------------------
  // Internal utils
  // ---------------------------------------------------------------------------

  _getMaterialTextures(material) {
    const props = [
      "map",
      "normalMap",
      "roughnessMap",
      "metalnessMap",
      "emissiveMap",
      "aoMap",
      "bumpMap",
      "displacementMap",
      "alphaMap",
      "lightMap",
    ];

    const out = [];
    for (const p of props) {
      const t = material[p];
      if (t && t.isTexture) out.push(t);
    }
    return out;
  }
}
