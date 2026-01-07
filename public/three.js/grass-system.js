/**
 * 🚨 VERSION MARKER - PATH FIX VERSION
 * Date: January 4, 2026
 * Version: 2026-01-04-PATH-FIX
 * Path fixes: Updated texture paths to use /public/three.js/public/...
 */
console.log("🚨 [VERSION CHECK] grass-system.js v2026-01-04-PATH-FIX loaded!");
/**
 * ============================================================================
 * GRASS SYSTEM - Procedural Grass Generation with Wind Animation
 * ============================================================================
 * 
 * ✅ STATUS: STABLE - PRODUCTION READY
 * 📅 CREATED: December 2025
 * 📅 LAST UPDATED: December 20, 2025
 * 
 * Based on: https://github.com/James-Smyth/three-grass-demo
 * Original Author: James Smyth
 * Enhanced by Narrrfs World Team (December 2025)
 * Reference Article: https://medium.com/antaeus-ar/making-grass-with-triangles-in-glsl-using-three-js-e106771a71ff
 * 
 * ============================================================================
 * 🎯 PURPOSE
 * ============================================================================
 * 
 * Procedural grass generation system with advanced features:
 * - Breath of the Wild style animated grass
 * - Triangle-based blades (5 vertices per blade)
 * - Multiple ground modes (grass, blank, color, gltf)
 * - Exclusion zones (prevents grass under chests/objects)
 * - Auto-registration system (chests auto-register exclusion zones)
 * - Wind animation system (procedural noise, configurable direction)
 * - Chunked grass system (for large fields, performance optimized)
 * - Per-level configuration (save/load settings)
 * 
 * ============================================================================
 * ✅ STABLE VERSION STATUS
 * ============================================================================
 * 
 * ✅ **STABLE VERSION - PRODUCTION READY**
 * 
 * This system has reached a stable, production-ready state with:
 * - ✅ Exclusion zone system working (prevents grass under chests/objects)
 * - ✅ Auto-registration system (chests automatically register exclusion zones)
 * - ✅ Grass regeneration with exclusion zones (applied correctly)
 * - ✅ Performance optimized (supports up to 5M blades)
 * - ✅ Chunked grass system (for large fields)
 * - ✅ Wind system (procedural noise, configurable direction)
 * - ✅ Per-level configuration (save/load settings)
 * - ✅ Underground flickering fixed (z-fighting eliminated, all levels working perfectly)
 * 
 * **VERIFIED WORKING:**
 * - ✅ No grass under chests (Level 1 chests verified December 16, 2025)
 * - ✅ Exclusion zones registering correctly
 * - ✅ Grass regenerating with exclusion zones applied
 * - ✅ Clean visuals - no grass artifacts inside or under chest models
 * - ✅ Underground displays correctly on all levels (Level 5 & 6 flickering fixed)
 * - ✅ All ground types match saved settings without issues
 * 
 * ============================================================================
 * 📦 WHAT THIS MODULE LOADS
 * ============================================================================
 * 
 * Resources:
 * - GLTF map models (optional, per level)
 * - Custom grass shaders (embedded in code)
 * 
 * Dependencies:
 * - THREE.js Scene
 * - THREE.js WebGLRenderer
 * - THREE.js ShaderMaterial
 * 
 * ============================================================================
 * 🔧 INTEGRATION IN MAIN.JS
 * ============================================================================
 * 
 * 1. Import:
 *    import { GrassSystem } from "./grass-system.js";
 * 
 * 2. Initialize (once):
 *    grassSystem = new GrassSystem({
 *      scene: scene,
 *      renderer: renderer
 *    });
 * 
 * 3. Apply per level:
 *    grassSystem.applyLevelEnvironment(levelId);
 * 
 * 4. Register exclusion zones (automatic for chests):
 *    grassSystem.registerExclusionZone(position, radius);
 * 
 * 5. Settings persistence:
 *    - Settings saved via localStorage
 *    - Loaded automatically on level change
 *    - Functions: loadGroundSettingsForLevel(), saveGroundSettingsForLevel()
 * 
 * ============================================================================
 * 🎮 KEY FUNCTIONS
 * ============================================================================
 * 
 * - applyLevelEnvironment(levelId) - Apply grass settings for level
 * - regenerateGrass() - Regenerate grass with current settings
 * - registerExclusionZone(position, radius) - Register exclusion zone
 * - setBladeLengthMultiplier(multiplier) - Set blade length (0.5-2.0)
 * - setWindDirection(angle) - Set wind direction (0-360 degrees)
 * - setWindSpeed(speed) - Set wind speed
 * - setWindTurbulence(intensity) - Set wind turbulence (0.0-1.0)
 * 
 * ============================================================================
 * CURRENT IMPLEMENTATION STATUS (December 13, 2025)
 * ============================================================================
 * 
 * ✅ COMPLETED FEATURES:
 * 
 * 1. BASIC GRASS SYSTEM:
 *    - Breath of the Wild style animated grass
 *    - Triangle-based blades (5 vertices per blade: BL, BR, TR, TL, TC)
 *    - Three ground modes: grass, blank, color, gltf
 *    - Per-level configuration (save/load settings per level)
 *    - Performance optimized (supports up to 5M blades)
 *    - Basic frustum culling enabled
 * 
 * 2. BLADE LENGTH MULTIPLIER (Phase 1 & 2):
 *    - Real-time adjustable blade length (0.5x to 2.0x)
 *    - Shader-based scaling (no geometry regeneration needed)
 *    - UI slider in God Mode → Ground System Configuration
 *    - Per-level save/load support
 *    - Uniform: `bladeLengthMultiplier` (default: 1.0)
 * 
 * 3. NOISE-BASED WIND SYSTEM (Phase 1):
 *    - Procedural noise texture generation (256x256, multi-octave)
 *    - Noise texture sampled in vertex shader for wind variation
 *    - More natural, unpredictable grass movement
 *    - Uniform: `windNoiseTexture` (THREE.DataTexture)
 *    - Memory: ~256KB per grass system instance
 * 
 * 4. WIND DIRECTION CONTROL (Phase 1):
 *    - Configurable wind direction (0-360 degrees)
 *    - UI slider in God Mode → Ground System Configuration
 *    - Per-level save/load support
 *    - Uniform: `windDirection` (THREE.Vector2, normalized)
 *    - Method: `setWindDirection(angle)` - angle in degrees
 * 
 * 5. ADVANCED WIND FEATURES (Phase 4):
 *    - Wind Turbulence: Random turbulence intensity (0.0-1.0)
 *      * Uniform: `windTurbulence` (default: 0.2)
 *      * Method: `setWindTurbulence(intensity)`
 *      * UI slider available
 * 
 *    - Per-Blade Speed Variation: Each blade moves at different speed
 *      * Based on blade position (consistent per blade)
 *      * Range: 70% to 130% of base wind speed
 *      * No UI needed (automatic, shader-based)
 * 
 *    - Wind Gust System: Random wind gusts for dramatic effect
 *      * Configurable frequency (gusts per second, 0-2.0/s)
 *      * Configurable intensity (max multiplier, 1.0-3.0x)
 *      * Smooth gust curves (ease in/out using sine)
 *      * Uniform: `windGust` (updated dynamically in update())
 *      * Methods: `setWindGustFrequency(frequency)`, `setWindGustIntensity(intensity)`
 *      * UI sliders available
 * 
 * 6. PER-LEVEL SETTINGS SYSTEM:
 *    - All grass settings save/load per level automatically
 *    - Settings stored in localStorage with key: `ground_settings_{levelId}`
 *    - Saved settings: groundType, bladeCount, bladeLengthMultiplier, windSpeed,
 *      windStrength, windDirectionAngle, windTurbulence, windGustFrequency,
 *      windGustIntensity, grassColor, groundColor, undergroundType, etc.
 * 
 * ============================================================================
 * SHADER UNIFORMS (Current Implementation)
 * ============================================================================
 * 
 * Vertex Shader Uniforms:
 * - iTime: float - Elapsed time in milliseconds
 * - windSpeed: float - Wind animation speed (0-3.0)
 * - windStrength: float - Wind movement strength (0-1.0)
 * - bladeLengthMultiplier: float - Blade length multiplier (0.5-2.0)
 * - windNoiseTexture: sampler2D - Noise texture for wind variation
 * - windDirection: vec2 - Wind direction vector (normalized X, Z)
 * - windTurbulence: float - Turbulence intensity (0.0-1.0)
 * - windGust: float - Wind gust multiplier (0.0-2.0, updated dynamically)
 * 
 * Fragment Shader Uniforms:
 * - grassTexture: sampler2D - Grass texture
 * - cloudTexture: sampler2D - Cloud texture for sky reflection
 * 
 * ============================================================================
 * WIND SYSTEM ARCHITECTURE
 * ============================================================================
 * 
 * The wind system uses a multi-layered approach:
 * 
 * 1. BASE WIND: Sine wave-based movement (original system)
 *    - Wave size: 10.0
 *    - Tip distance: 0.3 * windStrength * windGust
 *    - Center distance: 0.1 * windStrength * windGust
 * 
 * 2. NOISE VARIATION: Multi-octave noise texture
 *    - Main noise (R channel): -1 to 1 range
 *    - Secondary noise (G channel): -1 to 1 range
 *    - Tertiary noise (B channel): -1 to 1 range
 *    - Applied to base wave for natural variation
 * 
 * 3. TURBULENCE: Additional chaotic movement
 *    - Calculated from noise texture
 *    - Scaled by windTurbulence uniform (0.0-1.0)
 *    - Applied to both tip and center movement
 * 
 * 4. PER-BLADE VARIATION: Individual blade speed
 *    - Based on blade position (sin(position.x * 12.5 + position.z * 7.3))
 *    - Range: -0.3 to +0.3 (70% to 130% of base speed)
 *    - Applied to timeSpeed calculation
 * 
 * 5. WIND DIRECTION: Directional wind control
 *    - Normalized vector (X, Z components)
 *    - Applied to all wind movement
 *    - Allows 360° wind direction control
 * 
 * 6. WIND GUSTS: Dynamic wind strength variation
 *    - Calculated in update() method based on time
 *    - Frequency-based gust generation
 *    - Smooth gust curves (sine-based ease in/out)
 *    - Multiplies windStrength for dramatic effect
 * 
 * ============================================================================
 * PERFORMANCE CONSIDERATIONS
 * ============================================================================
 * 
 * - Noise Texture: 256x256 RGBA = 256KB memory (negligible)
 * - Per-Blade Variation: Calculated in shader (no CPU cost)
 * - Wind Gusts: Calculated once per frame in update() (minimal CPU cost)
 * - Turbulence: Shader-based (no performance impact)
 * 
 * SINGLE MESH MODE (Default for < 2M blades):
 * - Supports up to 2M blades with good performance
 * - Faster initialization (no chunk division)
 * - Simpler rendering (one draw call)
 * - Best for small to medium grass fields
 * 
 * CHUNKED MODE (Auto-enabled for > 2M blades):
 * - Supports unlimited blade counts (scalable)
 * - Better performance for large fields (5M+ blades)
 * - Multiple draw calls (one per chunk)
 * - Frustum culling per chunk (only visible chunks rendered)
 * - Dynamic loading/unloading (memory efficient)
 * - Async generation prevents browser freeze
 * - Recommended chunk size: 50-100 world units
 * - Recommended max blades per chunk: 100K-200K
 * - Performance tip: Smaller chunks = more chunks = more overhead
 * - Performance tip: Larger chunks = fewer chunks = less overhead but less culling benefit
 * 
 * 7. CHUNKED GRASS MESHES SYSTEM (Phase 2 - COMPLETE):
 *    - Hybrid Auto-Detection: Automatically enables chunked mode if blade count > 2M
 *      * Prevents performance issues with large grass fields
 *      * Can be manually toggled on/off in UI
 *      * Default: OFF (backward compatible)
 * 
 *    - Chunk Division: Divides grass field into smaller, manageable chunks
 *      * Configurable chunk size: 10-200 world units (default: 50)
 *      * Each chunk is a separate THREE.Mesh for better frustum culling
 *      * Chunks are positioned in a grid around the origin
 * 
 *    - Dynamic Chunk Loading: Loads/unloads chunks based on camera position
 *      * Load radius: 2 chunks around player (configurable)
 *      * Unload radius: 3 chunks beyond player (configurable)
 *      * Throttled updates: Checks every 10 frames (not every frame)
 *      * Async generation: Chunks generate progressively (prevents freeze)
 * 
 *    - Configurable Max Blades Per Chunk: Safety limit per chunk
 *      * Range: 10,000 - 1,000,000 blades (default: 200,000)
 *      * Prevents memory issues with very dense grass
 *      * Automatically caps chunk blade count if exceeded
 * 
 *    - Performance Optimizations:
 *      * Frustum culling per chunk (Three.js built-in)
 *      * Async chunk generation (setTimeout between chunks)
 *      * Throttled chunk updates (every 10 frames)
 *      * Initialization guard (prevents concurrent generation)
 *      * Reduced max blades per chunk (200K vs 500K) for faster generation
 * 
 *    - UI Controls (God Mode → Ground System Configuration):
 *      * Toggle: "Use Chunked Grass System" checkbox
 *      * Chunk Size Slider: 10-200 world units
 *      * Max Blades Per Chunk Slider: 10K-1M blades
 *      * Note: Changes require toggling chunked mode off/on to apply
 * 
 *    - Per-Level Settings:
 *      * useChunkedGrass: boolean (saved per level)
 *      * chunkSize: number (saved per level)
 *      * maxBladesPerChunk: number (saved per level)
 *      * Settings persist across sessions
 * 
 *    - Methods:
 *      * setChunkSize(size): Set chunk size (10-200, clamped)
 *      * setMaxBladesPerChunk(maxBlades): Set max blades per chunk (10K-1M, clamped)
 *      * setUseChunkedGrass(value): Enable/disable chunked mode (triggers recreation)
 * 
 *    - Usage:
 *      * For small fields (< 2M blades): Single mesh (faster, simpler)
 *      * For large fields (> 2M blades): Chunked mode (better performance)
 *      * For very large fields (5M+ blades): Chunked mode recommended
 * 
 * ============================================================================
 * FUTURE ENHANCEMENTS (Planned)
 * ============================================================================
 * 
 * Phase 3: Procedural Grass Growth
 * - Dynamic grass generation around player
 * - Infinite grass fields
 * - Requires Phase 2 (chunking) - NOW AVAILABLE
 * 
 * ============================================================================
 * IMPORTANT NOTES FOR FUTURE DEVELOPERS
 * ============================================================================
 * 
 * 1. ALWAYS preserve existing working code when adding features
 * 2. All new wind features use shader uniforms (no geometry changes needed)
 * 3. Per-level settings MUST be saved/loaded for all new features
 * 4. UI controls should be added to God Mode → Ground System Configuration
 * 5. Test with various blade counts (1K to 5M) to ensure performance
 * 6. Wind system is fully shader-based for maximum performance
 * 7. Noise texture is generated once on initialization (not per frame)
 * 8. Wind gust calculation happens in update() method (once per frame)
 * 
 * ============================================================================
 * FILE STRUCTURE
 * ============================================================================
 * 
 * - Shader Code: Lines 21-108 (GRASS_VERTEX_SHADER, GRASS_FRAGMENT_SHADER)
 * - GrassSystem Class: Lines 114+
 * - Noise Generation: generateNoiseTexture() method
 * - Wind Methods: setWindSpeed(), setWindStrength(), setWindDirection(),
 *                 setWindTurbulence(), setWindGustFrequency(), setWindGustIntensity()
 * - Update Loop: update() method (calculates wind gusts dynamically)
 * 
 * ============================================================================
 * GRASS EXCLUSION ZONE SYSTEM (December 16, 2025)
 * ============================================================================
 * 
 * Prevents grass from rendering through objects (chests, trees, NPCs, etc.)
 * 
 * HOW IT WORKS:
 * 1. Objects register their bounding boxes as exclusion zones via registerExclusionZone()
 * 2. During grass generation, each blade position is checked against exclusion zones
 * 3. If a position overlaps with any exclusion zone, the blade is NOT generated
 * 4. After all objects register, regenerateGrass() is called to apply exclusion zones
 * 
 * CRITICAL BUG FIX (December 16, 2025):
 * - BUG: Grass was rendering inside chests despite exclusion zone system
 * - ROOT CAUSE: warpToLevel1() was calling initializeGrassSystem() TWICE:
 *   1. First call via applyLevelEnvironment() - exclusion zones registered correctly
 *   2. Second call in Promise - DESTROYED all exclusion zones by recreating grass system!
 * - FIX: Removed duplicate initializeGrassSystem() call from warpToLevel1()
 * - LESSON: NEVER call initializeGrassSystem() directly - use applyLevelEnvironment()
 *           which handles grass system creation AND exclusion zone registration properly
 * 
 * USAGE:
 * - Register: grassSystem.registerExclusionZone('chest_001', boundingBox, 0.5)
 * - Unregister: grassSystem.unregisterExclusionZone('chest_001')
 * - Clear all: grassSystem.clearExclusionZones()
 * - Regenerate: await grassSystem.regenerateGrass()
 * 
 * AUTO-REGISTRATION:
 * - ChestSystem automatically registers exclusion zones when chests load
 * - Use chestSystem.setGrassSystem(grassSystem) to update reference after grass recreated
 * - setGrassSystem() auto-registers all existing loaded chests
 * 
 * VERIFIED: ✅ Working as of December 16, 2025
 * 
 * ============================================================================
 * LAST UPDATED: December 16, 2025
 * VERSION: 2.1 (Grass Exclusion Zone System Complete)
 * ============================================================================
 */

import * as THREE from "three";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";

// ============================================================================
// SHADER CODE
// ============================================================================

const GRASS_VERTEX_SHADER = `
varying vec2 vUv;
varying vec2 cloudUV;
varying vec3 vColor;
uniform float iTime;
uniform float windSpeed;
uniform float windStrength;
uniform float bladeLengthMultiplier; // Blade length multiplier (0.5x to 2.0x)
uniform sampler2D windNoiseTexture; // Noise texture for wind variation
uniform vec2 windDirection; // Wind direction (normalized X, Z components)
uniform float windTurbulence; // NEW: Wind turbulence intensity (0.0-1.0)
uniform float windGust; // NEW: Wind gust multiplier (0.0-2.0, updated dynamically)

void main() {
  vUv = uv;
  cloudUV = uv;
  vColor = color;
  vec3 cpos = position;

  // Scale blade height by multiplier (only for blade vertices, not ground/base)
  // Blade vertices have color.x > 0 (black = 0, gray = 0.5, white = 1.0)
  // Ground/base vertices have color = (0, 0, 0) = black
  if (color.x > 0.0 || color.y > 0.0 || color.z > 0.0) {
    cpos.y *= bladeLengthMultiplier;
  }

  // NEW: Sample noise texture for wind variation
  // Use world position and time to create scrolling noise
  vec2 noiseUV = vec2(
    position.x * 0.1 + iTime * windSpeed * 0.0005,
    position.z * 0.1 + iTime * windSpeed * 0.0005
  );
  vec3 noise = texture2D(windNoiseTexture, noiseUV).rgb;
  
  // Convert noise to wind offset (-1 to 1 range)
  float windNoise = (noise.r - 0.5) * 2.0; // Main noise: -1 to 1
  float windNoiseSecondary = (noise.g - 0.5) * 2.0; // Secondary noise: -1 to 1
  float windNoiseTertiary = (noise.b - 0.5) * 2.0; // Tertiary noise: -1 to 1
  
  // Wind direction vector (normalized)
  vec2 windDir = normalize(windDirection);
  
  // NEW: Per-blade wind speed variation (based on position for consistency)
  float bladeSpeedVariation = sin(position.x * 12.5 + position.z * 7.3) * 0.3; // -0.3 to +0.3
  float bladeSpeedMultiplier = 1.0 + bladeSpeedVariation; // 0.7 to 1.3
  
  // NEW: Apply wind gust multiplier to strength
  float gustStrength = windStrength * windGust;

  float waveSize = 10.0;
  float tipDistance = 0.3 * gustStrength;
  float centerDistance = 0.1 * gustStrength;
  float timeSpeed = windSpeed * bladeSpeedMultiplier; // NEW: Apply per-blade speed variation

  // NEW: Calculate turbulence (additional noise-based variation)
  float turbulence = (noise.r - 0.5) * 2.0 * windTurbulence; // -1 to 1, scaled by intensity

  if (color.x > 0.6) {
    // Tip movement with noise variation and turbulence
    float baseWave = sin((iTime / (500.0 / timeSpeed)) + (uv.x * waveSize));
    float tipMovement = (baseWave + windNoise * 0.3 + windNoiseSecondary * 0.2 + turbulence * 0.4) * tipDistance;
    
    // Apply wind direction
    cpos.x += tipMovement * windDir.x + windNoiseTertiary * 0.05;
    cpos.z += tipMovement * windDir.y + windNoiseTertiary * 0.05;
  } else if (color.x > 0.0) {
    // Center movement with noise variation and turbulence
    float baseWave = sin((iTime / (500.0 / timeSpeed)) + (uv.x * waveSize));
    float centerMovement = (baseWave + windNoise * 0.2 + windNoiseSecondary * 0.1 + turbulence * 0.3) * centerDistance;
    
    // Apply wind direction
    cpos.x += centerMovement * windDir.x + windNoiseTertiary * 0.02;
    cpos.z += centerMovement * windDir.y + windNoiseTertiary * 0.02;
  }

  cloudUV.x += iTime / (20000.0 / timeSpeed);
  cloudUV.y += iTime / (10000.0 / timeSpeed);

  vec4 mvPosition = modelViewMatrix * vec4(cpos, 1.0);
  gl_Position = projectionMatrix * mvPosition;
}
`;

const GRASS_FRAGMENT_SHADER = `
uniform sampler2D grassTexture;
uniform sampler2D cloudTexture;
varying vec2 vUv;
varying vec2 cloudUV;
varying vec3 vColor;

void main() {
  float contrast = 1.5;
  float brightness = 0.1;
  vec3 color = texture2D(grassTexture, vUv).rgb * contrast;
  color = color + vec3(brightness, brightness, brightness);
  color = mix(color, texture2D(cloudTexture, cloudUV).rgb, 0.4);
  gl_FragColor.rgb = color;
  gl_FragColor.a = 1.0;
}
`;

// ============================================================================
// PHASE 2: GRASS CHUNK CLASS
// ============================================================================

/**
 * Represents a single chunk of grass
 * PHASE 2: Chunked Grass Meshes - Individual chunk management
 */
class GrassChunk {
  constructor(chunkX, chunkZ, chunkSize, options, parentSystem) {
    this.chunkX = chunkX; // Grid X position
    this.chunkZ = chunkZ; // Grid Z position
    this.chunkSize = chunkSize; // Size in world units
    this.options = options; // Grass generation options
    this.parentSystem = parentSystem; // Reference to GrassSystem
    this.mesh = null; // THREE.Mesh for this chunk
    this.isLoaded = false;
    this.isVisible = true;
  }
  
  /**
   * Generate grass mesh for this chunk
   * Uses same logic as generateGrassField() but limited to chunk bounds
   */
  generate() {
    if (this.isLoaded) return this.mesh;
    
    const positions = [];
    const uvs = [];
    const indices = [];
    const colors = [];
    
    // Calculate chunk world bounds
    const chunkWorldX = this.chunkX * this.chunkSize;
    const chunkWorldZ = this.chunkZ * this.chunkSize;
    const chunkMinX = chunkWorldX - (this.chunkSize / 2);
    const chunkMaxX = chunkWorldX + (this.chunkSize / 2);
    const chunkMinZ = chunkWorldZ - (this.chunkSize / 2);
    const chunkMaxZ = chunkWorldZ + (this.chunkSize / 2);
    
    // Calculate blade count for this chunk (proportional to area)
    const totalArea = this.options.planeSize * this.options.planeSize;
    const chunkArea = this.chunkSize * this.chunkSize;
    const bladeDensity = this.options.bladeCount / totalArea;
    let chunkBladeCount = Math.max(1, Math.floor(chunkArea * bladeDensity));
    
    // Safety limit: Use configurable max blades per chunk
    // This prevents memory issues with very dense grass fields
    // The limit is configurable via setMaxBladesPerChunk() method
    // Default: 200K blades per chunk (balanced performance)
    const MAX_BLADES_PER_CHUNK = this.parentSystem.maxBladesPerChunk || 200000;
    if (chunkBladeCount > MAX_BLADES_PER_CHUNK) {
      console.warn(`⚠️ [GRASS] Chunk blade count capped at ${MAX_BLADES_PER_CHUNK.toLocaleString()} (was ${chunkBladeCount.toLocaleString()})`);
      console.warn(`💡 [GRASS] Tip: Increase maxBladesPerChunk in UI if you need more detail per chunk`);
      chunkBladeCount = MAX_BLADES_PER_CHUNK;
    }
    
    const VERTEX_COUNT = 5;
    
    // Generate blades only within chunk bounds
    // Note: Blade generation is synchronous within each chunk
    // Chunk generation itself is async (yields between chunks)
    for (let i = 0; i < chunkBladeCount; i++) {
      const x = chunkMinX + Math.random() * this.chunkSize;
      const z = chunkMinZ + Math.random() * this.chunkSize;
      
      // Clamp to chunk bounds
      const clampedX = Math.max(chunkMinX, Math.min(chunkMaxX, x));
      const clampedZ = Math.max(chunkMinZ, Math.min(chunkMaxZ, z));
      
      // GRASS EXCLUSION ZONE CHECK (Phase 2)
      // CRITICAL FIX: clampedX/clampedZ are already in world coordinates (from chunkMinX/chunkMaxX)
      // Do NOT add this.options.position.x again - that would double-add the offset
      const worldX = clampedX; // Already world coordinate
      const worldZ = clampedZ; // Already world coordinate
      
      // Check if this position is excluded (e.g., inside a chest, tree, etc.)
      if (this.parentSystem.isPositionExcluded(worldX, worldZ)) {
        // Skip this blade - position is inside an exclusion zone
        // Retry with a new random position to maintain blade count
        const retryX = chunkMinX + Math.random() * this.chunkSize;
        const retryZ = chunkMinZ + Math.random() * this.chunkSize;
        const retryClampedX = Math.max(chunkMinX, Math.min(chunkMaxX, retryX));
        const retryClampedZ = Math.max(chunkMinZ, Math.min(chunkMaxZ, retryZ));
        const retryWorldX = retryClampedX; // Already world coordinate
        const retryWorldZ = retryClampedZ; // Already world coordinate
        
        // If retry position is also excluded, skip this blade entirely
        if (this.parentSystem.isPositionExcluded(retryWorldX, retryWorldZ)) {
          continue; // Skip this blade iteration
        }
        
        // Use retry position
        const pos = new THREE.Vector3(
          retryClampedX - chunkWorldX, // Relative to chunk center
          (Math.random() * 0.1 - 0.05),
          retryClampedZ - chunkWorldZ  // Relative to chunk center
        );
        
        const uv = [
          this.parentSystem.convertRange(retryClampedX, chunkMinX, chunkMaxX, 0, 1),
          this.parentSystem.convertRange(retryClampedZ, chunkMinZ, chunkMaxZ, 0, 1)
        ];
        
        const blade = this.parentSystem.generateBlade(pos, i * VERTEX_COUNT, uv);
        blade.verts.forEach(vert => {
          positions.push(...vert.pos);
          uvs.push(...vert.uv);
          colors.push(...vert.color);
        });
        blade.indices.forEach(index => indices.push(index));
      } else {
        // Position is not excluded - generate blade normally
        const pos = new THREE.Vector3(
          clampedX - chunkWorldX, // Relative to chunk center
          (Math.random() * 0.1 - 0.05), // Slight height variation
          clampedZ - chunkWorldZ  // Relative to chunk center
        );
        
        // UV coordinates (relative to chunk)
        const uv = [
          this.parentSystem.convertRange(clampedX, chunkMinX, chunkMaxX, 0, 1),
          this.parentSystem.convertRange(clampedZ, chunkMinZ, chunkMaxZ, 0, 1)
        ];
        
        const blade = this.parentSystem.generateBlade(pos, i * VERTEX_COUNT, uv);
        blade.verts.forEach(vert => {
          positions.push(...vert.pos);
          uvs.push(...vert.uv);
          colors.push(...vert.color);
        });
        blade.indices.forEach(index => indices.push(index));
      }
    }
    
    // Create geometry
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(new Float32Array(positions), 3));
    geometry.setAttribute('uv', new THREE.BufferAttribute(new Float32Array(uvs), 2));
    geometry.setAttribute('color', new THREE.BufferAttribute(new Float32Array(colors), 3));
    geometry.setIndex(indices);
    geometry.computeVertexNormals();
    
    // Create shader material (same as single mesh)
    const defaultTexture = new THREE.DataTexture(new Uint8Array([255, 255, 255, 255]), 1, 1);
    defaultTexture.needsUpdate = true;
    
    const uniforms = {
      grassTexture: { value: this.parentSystem.grassTexture || defaultTexture },
      cloudTexture: { value: this.parentSystem.cloudTexture || defaultTexture },
      iTime: { value: 0.0 },
      windSpeed: { value: this.options.windSpeed },
      windStrength: { value: this.options.windStrength },
      bladeLengthMultiplier: { value: this.options.bladeLengthMultiplier || 1.0 },
      windNoiseTexture: { value: this.parentSystem.windNoiseTexture || defaultTexture },
      windDirection: { value: this.parentSystem.windDirection },
      windTurbulence: { value: this.options.windTurbulence || 0.2 },
      windGust: { value: 1.0 }
    };
    
    const material = new THREE.ShaderMaterial({
      uniforms: uniforms,
      vertexShader: GRASS_VERTEX_SHADER,
      fragmentShader: GRASS_FRAGMENT_SHADER,
      vertexColors: true,
      side: THREE.DoubleSide
    });
    
    // Create mesh
    this.mesh = new THREE.Mesh(geometry, material);
    this.mesh.userData.isGrassMesh = true;
    this.mesh.userData.grassUniforms = uniforms;
    this.mesh.userData.isChunk = true;
    this.mesh.userData.chunkX = this.chunkX;
    this.mesh.userData.chunkZ = this.chunkZ;
    
    // Position chunk at world position
    this.mesh.position.set(chunkWorldX, this.options.position.y, chunkWorldZ);
    
    // Enable frustum culling
    this.mesh.frustumCulled = true;
    
    // Update textures if loaded
    if (this.parentSystem.grassTexture && this.parentSystem.cloudTexture) {
      uniforms.grassTexture.value = this.parentSystem.grassTexture;
      uniforms.cloudTexture.value = this.parentSystem.cloudTexture;
      material.needsUpdate = true;
    }
    
    this.isLoaded = true;
    return this.mesh;
  }
  
  /**
   * Dispose of chunk resources
   */
  dispose() {
    if (this.mesh) {
      if (this.mesh.geometry) this.mesh.geometry.dispose();
      if (this.mesh.material) {
        // Dispose of material uniforms textures if needed
        this.mesh.material.dispose();
      }
      if (this.mesh.parent) {
        this.mesh.parent.remove(this.mesh);
      }
      this.mesh = null;
    }
    this.isLoaded = false;
  }
}

// ============================================================================
// GRASS SYSTEM CLASS
// ============================================================================

export class GrassSystem {
  constructor(scene, options = {}) {
    this.scene = scene;
    // FIX: Store resolveAssetPath function if provided in options
    this.resolveAssetPath = options.resolveAssetPath || ((path) => path);
    this.options = {
      groundType: options.groundType || 'grass',  // 'grass', 'blank', 'color', 'gltf'
      planeSize: options.planeSize || 60,          // Field size
      bladeCount: options.bladeCount || 10000,     // Number of grass blades (performance)
      bladeWidth: options.bladeWidth || 0.1,       // Blade width
      bladeHeight: options.bladeHeight || 0.8,     // Blade height
      bladeHeightVariation: options.bladeHeightVariation || 0.6, // Height randomness
      bladeLengthMultiplier: options.bladeLengthMultiplier !== undefined ? options.bladeLengthMultiplier : 1.0, // NEW: Blade length multiplier (0.5x to 2.0x)
      bladeLengthMin: options.bladeLengthMin || 0.5, // NEW: Minimum multiplier
      bladeLengthMax: options.bladeLengthMax || 2.0, // NEW: Maximum multiplier
      windSpeed: options.windSpeed || 1.0,         // Wind animation speed
      windStrength: options.windStrength || 0.3,   // Wind movement strength
      windTurbulence: options.windTurbulence !== undefined ? options.windTurbulence : 0.2, // NEW: Wind turbulence intensity (0.0-1.0)
      windGustFrequency: options.windGustFrequency !== undefined ? options.windGustFrequency : 0.5, // NEW: Gusts per second
      windGustIntensity: options.windGustIntensity !== undefined ? options.windGustIntensity : 1.5, // NEW: Max gust multiplier
      windGustDuration: options.windGustDuration !== undefined ? options.windGustDuration : 0.5, // NEW: Gust duration in seconds
      grassColor: options.grassColor || 0x4a7c59,  // Base grass color
      groundColor: options.groundColor || 0xaaaaaa, // For 'color' mode
      position: options.position || new THREE.Vector3(0, 0, 0), // Ground position
      gltfMapPath: options.gltfMapPath || null,     // For 'gltf' mode: path to GLTF map file
      grassGroundModelPath: options.grassGroundModelPath || null, // For 'grass' mode: path to GLB grass model as underground
      undergroundType: options.undergroundType || 'color', // 'color' | 'texture'
      undergroundColor: options.undergroundColor !== undefined ? options.undergroundColor : 0x777777,
      undergroundTexturePath: options.undergroundTexturePath || null,
      ...options
    };
    
    this.groundMesh = null;
    this.grassMesh = null;
    this.blankMesh = null;
    this.colorMesh = null;
    this.gltfMapModel = null; // For GLTF ground type
    this.grassGroundModel = null; // For 'grass' mode: GLB grass model as underground
    this.undergroundMesh = null; // Plane/texture under grass
    this.elapsedTime = 0;
    this.startTime = Date.now();
    
    // Texture loading
    this.grassTexture = null;
    this.cloudTexture = null;
    this.windNoiseTexture = null; // NEW: Noise texture for wind variation
    this.texturesLoaded = false;
    
    // Wind direction (normalized vector: x, z components)
    this.windDirection = options.windDirection || new THREE.Vector2(1.0, 0.0); // Default: X direction
    this.windDirectionAngle = options.windDirectionAngle !== undefined ? options.windDirectionAngle : 0.0; // 0-360 degrees
    
    // ========================================================================
    // PHASE 2: CHUNKED GRASS SYSTEM (Optional, Backward Compatible)
    // ========================================================================
    // 
    // HYBRID AUTO-DETECTION:
    // - Automatically enables chunked mode if blade count > 2M
    // - Prevents performance issues with large grass fields
    // - Can be manually overridden via useChunkedGrass option
    // - Default: OFF (backward compatible with existing levels)
    // 
    // CHUNK CONFIGURATION:
    // - chunkSize: World units per chunk (10-200, default: 50)
    //   * Smaller = more chunks = better culling but more overhead
    //   * Larger = fewer chunks = less overhead but less culling benefit
    //   * Recommended: 50-100 world units for balanced performance
    // 
    // - maxBladesPerChunk: Safety limit per chunk (10K-1M, default: 200K)
    //   * Prevents memory issues with very dense grass
    //   * Automatically caps chunk blade count if exceeded
    //   * Lower = faster generation, higher = more detail per chunk
    //   * Recommended: 100K-200K for balanced performance
    // 
    // CHUNK MANAGEMENT:
    // - loadRadius: Chunks to load around player (default: 2)
    //   * Total chunks loaded: (2 * loadRadius + 1)²
    //   * Example: loadRadius=2 → 25 chunks loaded
    // 
    // - unloadRadius: Chunks to unload beyond this (default: 3)
    //   * Prevents chunks from being unloaded too quickly
    //   * Should be > loadRadius to avoid flickering
    // 
    // PERFORMANCE OPTIMIZATIONS:
    // - Throttled updates: Checks every 10 frames (not every frame)
    // - Async generation: Chunks generate progressively (prevents freeze)
    // - Initialization guard: Prevents concurrent chunk generation
    // - Frustum culling: Per-chunk (Three.js built-in)
    // 
    // USAGE:
    // - Small fields (< 2M blades): Use single mesh (faster)
    // - Large fields (> 2M blades): Use chunked mode (better performance)
    // - Very large fields (5M+ blades): Chunked mode recommended
    // 
    // ========================================================================
    
    const shouldUseChunked = options.useChunkedGrass !== undefined 
      ? options.useChunkedGrass 
      : (options.bladeCount > 2000000); // Auto-enable if > 2M blades
    this.useChunkedGrass = shouldUseChunked;
    this.chunkSize = options.chunkSize || 50; // World units per chunk (configurable, 10-200)
    this.maxBladesPerChunk = options.maxBladesPerChunk || 200000; // Max blades per chunk (configurable, 10K-1M)
    this.loadRadius = options.loadRadius || 2; // Chunks to load around player (grid units)
    this.unloadRadius = options.unloadRadius || 3; // Chunks to unload beyond this
    this.chunks = new Map(); // Map<"x_z", GrassChunk> - Active chunks
    this.chunkGroup = null; // THREE.Group to hold all chunks
    this.isGeneratingChunks = false; // Flag to prevent concurrent chunk generation
    this.isInitializing = false; // Flag to prevent multiple initialization calls
    this.chunkUpdateThrottle = 0; // Throttle chunk updates (check every N frames)
    this.chunkUpdateInterval = 10; // Update chunks every 10 frames (not every frame)
    
    // ========================================================================
    // GRASS EXCLUSION ZONE SYSTEM (Phase 1)
    // ========================================================================
    // 
    // Prevents grass from rendering through objects (chests, trees, NPCs, etc.)
    // 
    // 🚨 CRITICAL REQUIREMENT: ALL CHESTS MUST HAVE NO GRASS UNDER THEM
    // ===================================================================
    // 
    // Verified Working: December 16, 2025
    // - ✅ Level 1 chests (chest_001, chest_002) have no grass under them
    // - ✅ Exclusion zones auto-register when chests load
    // - ✅ Grass regenerates with exclusion zones applied
    // - ✅ Clean visuals - no grass artifacts inside or under chest models
    // 
    // REFERENCE IMPLEMENTATION:
    // - Level 1 chests are the standard for all future chest creation
    // - All chests MUST follow the same pattern to ensure no grass under them
    // - Chest system automatically handles exclusion zone registration
    // 
    // HOW IT WORKS:
    // - Objects register their bounding boxes as exclusion zones
    // - During grass generation, each blade position is checked against exclusion zones
    // - If a position overlaps with any exclusion zone, the blade is not generated
    // 
    // EXCLUSION ZONE STRUCTURE:
    // {
    //   id: 'chest_001',                    // Unique object identifier
    //   bounds: {                           // Bounding box in world coordinates
    //     minX: 50, maxX: 55,
    //     minZ: 20, maxZ: 25
    //   },
    //   padding: 0.5                        // Extra space around object (world units)
    // }
    // 
    // CHEST INTEGRATION (Automatic):
    // - Chest system automatically registers exclusion zones when chests load
    // - Registration happens 200ms after chest mesh is ready
    // - Grass regenerates 1500ms after registration (batches multiple chests)
    // - No manual setup required - works automatically via chestSystem.addChest()
    // 
    // PERFORMANCE:
    // - O(n) check per blade position where n = number of exclusion zones
    // - Negligible cost for 10-50 objects (typical game scenario)
    // - Checks only during generation (not per frame)
    // 
    // USAGE:
    // - Register: grassSystem.registerExclusionZone('chest_001', boundingBox, 0.5)
    // - Unregister: grassSystem.unregisterExclusionZone('chest_001')
    // - Clear all: grassSystem.clearExclusionZones()
    // 
    // STATUS: ✅ WORKING - VERIFIED DECEMBER 16, 2025
    // ========================================================================
    
    this.exclusionZones = new Map(); // Map<objectId, {bounds, padding}>
    
    // Initialize based on ground type
    this.initialize();
  }
  
  /**
   * Generate procedural noise texture for wind variation
   * 
   * PHASE 1: NOISE-BASED WIND - Creates multi-octave noise for natural wind patterns
   * 
   * This method generates a procedural noise texture used in the vertex shader
   * to create unpredictable, natural wind movement. The texture stores three
   * octaves of noise in RGB channels for layered wind variation.
   * 
   * Technical Details:
   * - Size: 256x256 RGBA texture = 256KB memory
   * - Noise Function: Hash-based pseudo-random (can be upgraded to Simplex/Perlin)
   * - Multi-Octave: Combines 3 frequency levels (low, medium, high)
   * - Storage: R=main noise, G=secondary, B=tertiary
   * - Wrapping: RepeatWrapping for seamless tiling
   * 
   * Usage in Shader:
   * - Sampled using world position + time for scrolling effect
   * - Converted to -1 to 1 range for wind offset
   * - Applied to base sine wave for natural variation
   * 
   * @param {number} size - Texture size (default: 256)
   * @returns {THREE.DataTexture} - Noise texture with RepeatWrapping
   */
  generateNoiseTexture(size = 256) {
    const data = new Uint8Array(size * size * 4);
    
    // Simple noise function (can be replaced with proper Perlin/Simplex noise)
    const noise = (x, y, z) => {
      // Simple hash-based noise
      const n = Math.sin(x * 12.9898 + y * 78.233 + z * 37.719) * 43758.5453;
      return (n - Math.floor(n));
    };
    
    for (let i = 0; i < size; i++) {
      for (let j = 0; j < size; j++) {
        const index = (i * size + j) * 4;
        
        // Generate multi-octave noise
        const x = i / size;
        const y = j / size;
        
        // Main noise (low frequency)
        const n1 = noise(x * 4.0, y * 4.0, 0.0);
        
        // Secondary noise (medium frequency)
        const n2 = noise(x * 8.0, y * 8.0, 1.0) * 0.5;
        
        // Tertiary noise (high frequency)
        const n3 = noise(x * 16.0, y * 16.0, 2.0) * 0.25;
        
        // Combine octaves
        const combined = n1 + n2 + n3;
        const normalized = Math.max(0, Math.min(1, combined / 1.75)); // Normalize to 0-1
        
        // Store in RGB channels (different octaves for variation)
        data[index] = normalized * 255;           // R: Main noise
        data[index + 1] = n2 * 255;               // G: Secondary noise
        data[index + 2] = n3 * 255;               // B: Tertiary noise
        data[index + 3] = 255;                     // A: Full opacity
      }
    }
    
    const texture = new THREE.DataTexture(data, size, size);
    texture.wrapS = THREE.RepeatWrapping;
    texture.wrapT = THREE.RepeatWrapping;
    texture.minFilter = THREE.LinearFilter;
    texture.magFilter = THREE.LinearFilter;
    texture.needsUpdate = true;
    
    console.log("🌱 [GRASS] Wind noise texture generated:", { size, memory: `${(data.length / 1024).toFixed(2)}KB` });
    
    return texture;
  }
  
  async initialize() {
    // Generate wind noise texture (NEW: Phase 1 - Noise-Based Wind)
    this.windNoiseTexture = this.generateNoiseTexture(256);
    
    // Load textures ONLY for grass mode (skip for 'blank', 'gltf', etc.)
    if (this.options.groundType === 'grass') {
      await this.loadTextures();
    }
    
    // Create ground based on type
    // For grass mode, wait for textures to load before creating
    if (this.options.groundType === 'grass' && !this.texturesLoaded) {
      console.warn("🌱 [GRASS] Waiting for textures to load...");
      // Retry after a short delay
      setTimeout(() => {
        if (this.texturesLoaded) {
          this.setGroundType('grass');
        }
      }, 100);
      return;
    }
    
    // Apply wind direction if set in options
    if (this.options.windDirectionAngle !== undefined) {
      this.setWindDirection(this.options.windDirectionAngle);
    }
    
    this.setGroundType(this.options.groundType);
  }
  
  async loadTextures() {
    // FIX: Only load textures if ground type is 'grass' (skip for blank, gltf, etc.)
    if (this.options.groundType !== 'grass') {
      console.log("🌱 [GRASS] Skipping texture loading - ground type is not 'grass'");
      return Promise.resolve();
    }
    
    if (this.texturesLoaded) return;
    
    return new Promise((resolve, reject) => {
      const loader = new THREE.TextureLoader();
      let loadedCount = 0;
      let errorCount = 0;
      const totalTextures = 2;
      
      const onLoad = () => {
        loadedCount++;
        if (loadedCount === totalTextures) {
          this.texturesLoaded = true;
          if (errorCount > 0) {
            console.warn(`⚠️ [GRASS] ${errorCount} texture(s) failed to load, but continuing anyway`);
          } else {
            console.log("🌱 [GRASS] Textures loaded successfully");
          }
          resolve();
        }
      };
      
      const onError = () => {
        errorCount++;
        onLoad(); // Continue anyway with fallback
      };
      
      // FIX: Load grass texture using resolveAssetPath for proper path resolution
      const grassPath = this.resolveAssetPath('textures/grass/grass.jpg');
      console.log(`🔍 [GRASS] Loading grass texture from: ${grassPath}`);
      this.grassTexture = loader.load(
        grassPath,
        (texture) => {
          texture.wrapS = THREE.RepeatWrapping;
          texture.wrapT = THREE.RepeatWrapping;
          texture.needsUpdate = true;
          console.log("🌱 [GRASS] Grass texture loaded:", grassPath);
          onLoad();
        },
        undefined,
        (error) => {
          console.error(`❌ [GRASS] Failed to load grass texture from ${grassPath}:`, error);
          console.error(`🔍 [GRASS] Full URL attempted: ${window.location.origin}${grassPath}`);
          // Try alternative path (fallback) - also use resolveAssetPath
          const altPath = this.resolveAssetPath('textures/grass/grass.jpg');
          console.log(`🔄 [GRASS] Trying alternative path: ${altPath}`);
          this.grassTexture = loader.load(
            altPath,
            (texture) => {
              texture.wrapS = THREE.RepeatWrapping;
              texture.wrapT = THREE.RepeatWrapping;
              texture.needsUpdate = true;
              console.log("🌱 [GRASS] Grass texture loaded from alternative path:", altPath);
              onLoad();
            },
            undefined,
            (error2) => {
              console.error(`❌ [GRASS] Failed to load from alternative path ${altPath}:`, error2);
              onError();
            }
          );
        }
      );
      
      // FIX: Load cloud texture using resolveAssetPath for proper path resolution
      const cloudPath = this.resolveAssetPath('textures/grass/cloud.jpg');
      console.log(`🔍 [GRASS] Loading cloud texture from: ${cloudPath}`);
      this.cloudTexture = loader.load(
        cloudPath,
        (texture) => {
          texture.wrapS = THREE.RepeatWrapping;
          texture.wrapT = THREE.RepeatWrapping;
          texture.needsUpdate = true;
          console.log("🌱 [GRASS] Cloud texture loaded:", cloudPath);
          onLoad();
        },
        undefined,
        (error) => {
          console.error(`❌ [GRASS] Failed to load cloud texture from ${cloudPath}:`, error);
          console.error(`🔍 [GRASS] Full URL attempted: ${window.location.origin}${cloudPath}`);
          // Try alternative path (fallback) - also use resolveAssetPath
          const altPath = this.resolveAssetPath('textures/grass/cloud.jpg');
          console.log(`🔄 [GRASS] Trying alternative path: ${altPath}`);
          this.cloudTexture = loader.load(
            altPath,
            (texture) => {
              texture.wrapS = THREE.RepeatWrapping;
              texture.wrapT = THREE.RepeatWrapping;
              texture.needsUpdate = true;
              console.log("🌱 [GRASS] Cloud texture loaded from alternative path:", altPath);
              onLoad();
            },
            undefined,
            (error2) => {
              console.error(`❌ [GRASS] Failed to load from alternative path ${altPath}:`, error2);
              onError();
            }
          );
        }
      );
    });
  }
  
  convertRange(val, oldMin, oldMax, newMin, newMax) {
    return (((val - oldMin) * (newMax - newMin)) / (oldMax - oldMin)) + newMin;
  }
  
  generateBlade(center, vArrOffset, uv) {
    const BLADE_WIDTH = this.options.bladeWidth;
    const MID_WIDTH = BLADE_WIDTH * 0.5;
    const TIP_OFFSET = 0.1;
    const height = this.options.bladeHeight + (Math.random() * this.options.bladeHeightVariation);
    
    const yaw = Math.random() * Math.PI * 2;
    const yawUnitVec = new THREE.Vector3(Math.sin(yaw), 0, -Math.cos(yaw));
    const tipBend = Math.random() * Math.PI * 2;
    const tipBendUnitVec = new THREE.Vector3(Math.sin(tipBend), 0, -Math.cos(tipBend));
    
    // Find the Bottom Left, Bottom Right, Top Left, Top Right, Top Center vertex positions
    const bl = new THREE.Vector3().addVectors(center, new THREE.Vector3().copy(yawUnitVec).multiplyScalar((BLADE_WIDTH / 2) * 1));
    const br = new THREE.Vector3().addVectors(center, new THREE.Vector3().copy(yawUnitVec).multiplyScalar((BLADE_WIDTH / 2) * -1));
    const tl = new THREE.Vector3().addVectors(center, new THREE.Vector3().copy(yawUnitVec).multiplyScalar((MID_WIDTH / 2) * 1));
    const tr = new THREE.Vector3().addVectors(center, new THREE.Vector3().copy(yawUnitVec).multiplyScalar((MID_WIDTH / 2) * -1));
    const tc = new THREE.Vector3().addVectors(center, new THREE.Vector3().copy(tipBendUnitVec).multiplyScalar(TIP_OFFSET));
    
    tl.y += height / 2;
    tr.y += height / 2;
    tc.y += height;
    
    // Vertex Colors (black = static, gray = slight movement, white = full movement)
    const black = [0, 0, 0];
    const gray = [0.5, 0.5, 0.5];
    const white = [1.0, 1.0, 1.0];
    
    const verts = [
      { pos: bl.toArray(), uv: uv, color: black },
      { pos: br.toArray(), uv: uv, color: black },
      { pos: tr.toArray(), uv: uv, color: gray },
      { pos: tl.toArray(), uv: uv, color: gray },
      { pos: tc.toArray(), uv: uv, color: white }
    ];
    
    const indices = [
      vArrOffset,
      vArrOffset + 1,
      vArrOffset + 2,
      vArrOffset + 2,
      vArrOffset + 4,
      vArrOffset + 3,
      vArrOffset + 3,
      vArrOffset,
      vArrOffset + 2
    ];
    
    return { verts, indices };
  }
  
  generateGrassField() {
    const positions = [];
    const uvs = [];
    const indices = [];
    const colors = [];
    
    const PLANE_SIZE = this.options.planeSize;
    const BLADE_COUNT = this.options.bladeCount;
    const VERTEX_COUNT = 5;
    const halfSize = PLANE_SIZE / 2;
    
    for (let i = 0; i < BLADE_COUNT; i++) {
      // Use GRID-BASED distribution with jitter for more uniform coverage
      // This reduces empty spaces while maintaining natural randomness
      const gridDensity = Math.sqrt(BLADE_COUNT); // Approximate grid size
      const cellSize = PLANE_SIZE / gridDensity;
      const gridX = Math.floor(i / gridDensity);
      const gridZ = i % gridDensity;
      
      // Base position on grid
      const baseX = (gridX * cellSize) - halfSize + (cellSize / 2);
      const baseZ = (gridZ * cellSize) - halfSize + (cellSize / 2);
      
      // Add jitter (random offset) to avoid perfect grid pattern
      const jitterAmount = cellSize * 0.4; // 40% jitter for natural look
      const x = baseX + (Math.random() * jitterAmount * 2 - jitterAmount);
      const z = baseZ + (Math.random() * jitterAmount * 2 - jitterAmount);
      
      // Clamp to plane bounds
      const clampedX = Math.max(-halfSize, Math.min(halfSize, x));
      const clampedZ = Math.max(-halfSize, Math.min(halfSize, z));
      
      // GRASS EXCLUSION ZONE CHECK (Phase 2)
      // Convert to world coordinates (add grass mesh position offset)
      const worldX = clampedX + this.options.position.x;
      const worldZ = clampedZ + this.options.position.z;
      
      // Check if this position is excluded (e.g., inside a chest, tree, etc.)
      if (this.isPositionExcluded(worldX, worldZ)) {
        // Skip this blade - position is inside an exclusion zone
        // Retry with a new random position to maintain blade count
        // (This ensures we still generate approximately the target blade count)
        const retryX = -halfSize + Math.random() * PLANE_SIZE;
        const retryZ = -halfSize + Math.random() * PLANE_SIZE;
        const retryClampedX = Math.max(-halfSize, Math.min(halfSize, retryX));
        const retryClampedZ = Math.max(-halfSize, Math.min(halfSize, retryZ));
        const retryWorldX = retryClampedX + this.options.position.x;
        const retryWorldZ = retryClampedZ + this.options.position.z;
        
        // If retry position is also excluded, skip this blade entirely
        // (Prevents infinite loop if entire field is excluded)
        if (this.isPositionExcluded(retryWorldX, retryWorldZ)) {
          continue; // Skip this blade iteration
        }
        
        // Use retry position
        const pos = new THREE.Vector3(
          retryClampedX,
          (Math.random() * 0.1 - 0.05),
          retryClampedZ
        );
        
        const uv = [
          this.convertRange(retryClampedX, -halfSize, halfSize, 0, 1),
          this.convertRange(retryClampedZ, -halfSize, halfSize, 0, 1)
        ];
        
        const blade = this.generateBlade(pos, i * VERTEX_COUNT, uv);
        blade.verts.forEach(vert => {
          positions.push(...vert.pos);
          uvs.push(...vert.uv);
          colors.push(...vert.color);
        });
        blade.indices.forEach(index => indices.push(index));
      } else {
        // Position is not excluded - generate blade normally
        const pos = new THREE.Vector3(
          clampedX, // Relative to mesh center (X)
          (Math.random() * 0.1 - 0.05), // Slight height variation (Y relative to ground)
          clampedZ  // Relative to mesh center (Z)
        );
        
        const uv = [
          this.convertRange(clampedX, -halfSize, halfSize, 0, 1),
          this.convertRange(clampedZ, -halfSize, halfSize, 0, 1)
        ];
        
        const blade = this.generateBlade(pos, i * VERTEX_COUNT, uv);
        blade.verts.forEach(vert => {
          positions.push(...vert.pos);
          uvs.push(...vert.uv);
          colors.push(...vert.color);
        });
        blade.indices.forEach(index => indices.push(index));
      }
    }
    
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(new Float32Array(positions), 3));
    geometry.setAttribute('uv', new THREE.BufferAttribute(new Float32Array(uvs), 2));
    geometry.setAttribute('color', new THREE.BufferAttribute(new Float32Array(colors), 3));
    geometry.setIndex(indices);
    geometry.computeVertexNormals();
    
    // Create shader material
    // Use default white texture if textures not loaded yet (will be updated in update())
    const defaultTexture = new THREE.DataTexture(new Uint8Array([255, 255, 255, 255]), 1, 1);
    defaultTexture.needsUpdate = true;
    
    const uniforms = {
      grassTexture: { value: this.grassTexture || defaultTexture },
      cloudTexture: { value: this.cloudTexture || defaultTexture },
      iTime: { value: 0.0 },
      windSpeed: { value: this.options.windSpeed },
      windStrength: { value: this.options.windStrength },
      bladeLengthMultiplier: { value: this.options.bladeLengthMultiplier || 1.0 },
      windNoiseTexture: { value: this.windNoiseTexture || defaultTexture },
      windDirection: { value: this.windDirection },
      windTurbulence: { value: this.options.windTurbulence || 0.2 }, // NEW: Wind turbulence
      windGust: { value: 1.0 } // NEW: Wind gust multiplier (updated dynamically in update())
    };
    
    const material = new THREE.ShaderMaterial({
      uniforms: uniforms,
      vertexShader: GRASS_VERTEX_SHADER,
      fragmentShader: GRASS_FRAGMENT_SHADER,
      vertexColors: true,
      side: THREE.DoubleSide
    });
    
    const mesh = new THREE.Mesh(geometry, material);
    mesh.userData.isGrassMesh = true;
    mesh.userData.grassUniforms = uniforms;
    mesh.position.copy(this.options.position);
    
    // If textures loaded after mesh creation, update uniforms
    if (this.grassTexture && this.cloudTexture) {
      uniforms.grassTexture.value = this.grassTexture;
      uniforms.cloudTexture.value = this.cloudTexture;
      material.needsUpdate = true;
    }
    
    return mesh;
  }
  
  /**
   * Create a blank (solid color) ground mesh
   * Used for levels with no grass (e.g., Level 6)
   * 
   * FIXED (December 16, 2025): Underground flickering/z-fighting issue resolved
   * - Uses MeshBasicMaterial for completely unlit ground (zero flickering)
   * - Polygon offset prevents z-fighting with other meshes
   * - Ground color respects saved settings from options.groundColor
   * 
   * @returns {THREE.Mesh} Ground mesh with solid color material
   */
  createBlankGround() {
    const PLANE_SIZE = this.options.planeSize;
    const geometry = new THREE.PlaneGeometry(PLANE_SIZE * 2, PLANE_SIZE * 2);
    // 🚨 CRITICAL FIX: Use MeshBasicMaterial for completely unlit ground (zero flickering)
    // MeshBasicMaterial is completely unaffected by lighting, preventing any color fluctuation
    // Use groundColor from options (e.g., Level 6 uses 0x333333) instead of hardcoded value
    // Add polygonOffset to prevent z-fighting with other meshes at same position
    // FIXED (Dec 16, 2025): Underground flickering issue - all levels now match saved settings perfectly
    const groundColor = this.options.groundColor || 0x888888;
    const material = new THREE.MeshBasicMaterial({
      color: groundColor,
      side: THREE.DoubleSide,
      polygonOffset: true,
      polygonOffsetFactor: -1,
      polygonOffsetUnits: -1
    });
    const mesh = new THREE.Mesh(geometry, material);
    mesh.rotation.x = -Math.PI / 2;
    mesh.position.copy(this.options.position);
    mesh.receiveShadow = false; // MeshBasicMaterial doesn't support shadows
    mesh.userData.isGroundMesh = true;
    return mesh;
  }
  
  /**
   * Create an underground mesh that sits beneath grass blades
   * Used for grass-type levels to provide a base ground texture/color
   * 
   * FIXED (December 16, 2025): Underground flickering/z-fighting issue resolved
   * - Increased depth offset from -0.02 to -0.05 for better separation
   * - Polygon offset added to prevent z-fighting
   * - MeshBasicMaterial for solid colors (completely unlit)
   * - MeshStandardMaterial with high emissive for textures (reduces lighting influence)
   * - Ground color respects saved settings and properly falls back to options.groundColor
   * 
   * @returns {THREE.Mesh} Underground mesh positioned slightly below ground level
   */
  createUndergroundMesh() {
    const PLANE_SIZE = this.options.planeSize;
    const geometry = new THREE.PlaneGeometry(PLANE_SIZE * 2, PLANE_SIZE * 2);
    let material;
    
    if (this.options.undergroundType === 'texture' && this.options.undergroundTexturePath) {
      const loader = new THREE.TextureLoader();
      const texture = loader.load(
        this.options.undergroundTexturePath,
        () => {
          texture.wrapS = THREE.RepeatWrapping;
          texture.wrapT = THREE.RepeatWrapping;
          texture.repeat.set(PLANE_SIZE / 10, PLANE_SIZE / 10);
          texture.needsUpdate = true;
        },
        undefined,
        (error) => {
          console.error(`❌ [GRASS] Failed to load underground texture: ${this.options.undergroundTexturePath}`, error);
        }
      );
      
      // 🚨 CRITICAL FIX: Make material unlit to prevent color fluctuation from lighting changes
      // Set neutral emissive and high emissiveIntensity so texture doesn't fluctuate with lighting
      // Use groundColor tint for better color consistency
      const baseEmissive = this.options.groundColor || 0x444444;
      material = new THREE.MeshStandardMaterial({
        map: texture,
        emissive: baseEmissive, // Use groundColor as emissive base to maintain consistent brightness
        emissiveIntensity: 0.8, // High emissive = less affected by lighting changes (increased from 0.5)
        roughness: 0.8,
        metalness: 0.1
      });
    } else {
      // 🚨 CRITICAL FIX: Use MeshBasicMaterial for completely unlit underground (zero flickering)
      // MeshBasicMaterial is completely unaffected by lighting, preventing any color fluctuation
      // Use groundColor as fallback when undergroundColor is not explicitly set
      // This ensures Level 5 (grass mode underground) uses the correct ground color
      // Add polygonOffset to prevent z-fighting with grass blades or other meshes
      const undergroundColor = this.options.undergroundColor !== undefined 
        ? this.options.undergroundColor 
        : (this.options.groundColor || 0x777777);
      material = new THREE.MeshBasicMaterial({
        color: undergroundColor,
        side: THREE.DoubleSide,
        polygonOffset: true,
        polygonOffsetFactor: -2,
        polygonOffsetUnits: -2
      });
    }
    
    const mesh = new THREE.Mesh(geometry, material);
    mesh.rotation.x = -Math.PI / 2;
    mesh.position.copy(this.options.position);
    // 🚨 CRITICAL FIX: Increased depth offset to prevent z-fighting with other ground meshes
    // This prevents T-shape flickering when multiple ground meshes exist
    // FIXED (Dec 16, 2025): Changed from -0.02 to -0.05 for better depth separation
    // All levels now display underground correctly without flickering
    mesh.position.y -= 0.05; // Increased from 0.02 to 0.05 for better depth separation
    // Only set receiveShadow if using MeshStandardMaterial (textured underground)
    // MeshBasicMaterial doesn't support shadows, so we check material type
    if (material instanceof THREE.MeshStandardMaterial) {
      mesh.receiveShadow = true;
    } else {
      mesh.receiveShadow = false;
    }
    mesh.userData.isUndergroundMesh = true;
    return mesh;
  }
  
  /**
   * Create a color ground mesh (similar to blank but with different default color)
   * Used for levels that need a solid color ground (e.g., Level 5 when not using grass)
   * 
   * FIXED (December 16, 2025): Underground flickering/z-fighting issue resolved
   * - Uses MeshBasicMaterial for completely unlit ground (zero flickering)
   * - Polygon offset prevents z-fighting with other meshes
   * - Ground color respects saved settings from options.groundColor
   * 
   * @returns {THREE.Mesh} Ground mesh with solid color material
   */
  createColorGround() {
    const PLANE_SIZE = this.options.planeSize;
    const geometry = new THREE.PlaneGeometry(PLANE_SIZE * 2, PLANE_SIZE * 2);
    // 🚨 CRITICAL FIX: Use MeshBasicMaterial for completely unlit ground (zero flickering)
    // MeshBasicMaterial is completely unaffected by lighting, preventing any color fluctuation
    // Add polygonOffset to prevent z-fighting with other meshes at same position
    // FIXED (Dec 16, 2025): Underground flickering issue - all levels now match saved settings perfectly
    const groundColor = this.options.groundColor || 0xaaaaaa;
    const material = new THREE.MeshBasicMaterial({
      color: groundColor,
      side: THREE.DoubleSide,
      polygonOffset: true,
      polygonOffsetFactor: -1,
      polygonOffsetUnits: -1
    });
    const mesh = new THREE.Mesh(geometry, material);
    mesh.rotation.x = -Math.PI / 2;
    mesh.position.copy(this.options.position);
    mesh.receiveShadow = false; // MeshBasicMaterial doesn't support shadows
    mesh.userData.isGroundMesh = true;
    return mesh;
  }
  
  setGroundType(type) {
    // CRITICAL: Prevent infinite recursion - if we're already setting ground type, return
    if (this._isSettingGroundType) {
      console.warn("🌱 [GRASS] setGroundType already in progress, skipping recursive call");
      return;
    }
    
    // 🚨 CRITICAL FIX: If ground type is already set to the requested type and meshes exist, skip recreation
    // This prevents unnecessary mesh recreation that can cause flickering
    if (this.options.groundType === type) {
      // Check if appropriate mesh already exists
      if (type === 'blank' || type === 'color') {
        if (this.groundMesh && this.scene.children.includes(this.groundMesh) && !this.undergroundMesh) {
          console.log(`🌱 [GRASS] Ground type ${type} already active, skipping recreation`);
          return;
        }
      } else if (type === 'grass') {
        // For grass type, we need both groundMesh (grass) and undergroundMesh
        if (this.groundMesh && this.scene.children.includes(this.groundMesh) && 
            this.undergroundMesh && this.scene.children.includes(this.undergroundMesh)) {
          console.log(`🌱 [GRASS] Ground type ${type} already active, skipping recreation`);
          return;
        }
      }
    }

    this._isSettingGroundType = true;

    try {
    // Remove existing ground
    this.disposeCurrentGround();
    
    this.options.groundType = type;
    
    switch (type) {
      case 'grass':
          // Load GLB grass model as underground if path is provided (async, don't wait)
          if (this.options.grassGroundModelPath) {
            this.loadGrassGroundModel().catch(err => {
              console.warn("🌱 [GRASS] Failed to load GLB grass model, continuing with shader grass only:", err);
            });
          }
          
        if (!this.texturesLoaded || !this.grassTexture || !this.cloudTexture) {
          // Fallback to blank if textures not loaded yet
          console.warn("🌱 [GRASS] Textures not loaded yet, using blank ground temporarily");
          this.groundMesh = this.createBlankGround();
          this.scene.add(this.groundMesh);
            
            // CRITICAL: Only load textures if not already loading
            if (!this._isLoadingTextures) {
              this._isLoadingTextures = true;
          // Try to reload textures and recreate grass
          this.loadTextures().then(() => {
                this._isLoadingTextures = false;
                // CRITICAL: Reset flag before recursive call
                this._isSettingGroundType = false;
                // Double-check textures are actually loaded before recreating
                if (this.texturesLoaded && this.grassTexture && this.cloudTexture && this.options.groundType === 'grass') {
              console.log("🌱 [GRASS] Textures loaded, recreating grass field");
              this.setGroundType('grass'); // Recreate with textures
                } else {
                  console.warn("🌱 [GRASS] Textures still not ready after load, keeping blank ground");
            }
              }).catch((error) => {
                this._isLoadingTextures = false;
                this._isSettingGroundType = false;
                console.error("🌱 [GRASS] Failed to load textures:", error);
          });
        } else {
              console.warn("🌱 [GRASS] Textures already loading, waiting...");
              this._isSettingGroundType = false;
            }
          } else {
          // PHASE 2: Support chunked or single mesh mode
          if (this.useChunkedGrass) {
            // Chunked mode: Generate initial chunks around origin
            this.generateInitialChunks(() => {
              this.isInitializing = false;
            });
            console.log(`🌱 [GRASS] Chunked grass system initialized (chunkSize: ${this.chunkSize}, loadRadius: ${this.loadRadius})`);
          } else {
            // Single mesh mode (default, backward compatible)
            // Remove existing grass if any
            if (this.groundMesh) {
              this.scene.remove(this.groundMesh);
              if (this.groundMesh.geometry) this.groundMesh.geometry.dispose();
              if (this.groundMesh.material) this.groundMesh.material.dispose();
            }
            
            // Also clean up any existing chunks if switching from chunked mode
            this.disposeAllChunks();
            
          // Ensure textures are set in uniforms
          if (this.grassTexture && this.cloudTexture) {
            this.groundMesh = this.generateGrassField();
            // Update uniforms with textures (in case they loaded after mesh creation)
            if (this.groundMesh.userData.grassUniforms) {
              this.groundMesh.userData.grassUniforms.grassTexture.value = this.grassTexture;
              this.groundMesh.userData.grassUniforms.cloudTexture.value = this.cloudTexture;
            }
            this.scene.add(this.groundMesh);
              console.log(`🌱 [GRASS] Grass field created: ${this.options.bladeCount} blades with textures (single mesh mode)`);
          } else {
            console.warn("🌱 [GRASS] Textures not ready, using blank ground");
            this.groundMesh = this.createBlankGround();
            this.scene.add(this.groundMesh);
          }
        }
        }
        
        // Create underground mesh (color or texture) beneath grass
        // 🚨 CRITICAL FIX: disposeCurrentGround() already handles undergroundMesh disposal
        // But we check here just in case to prevent duplicates
        if (this.undergroundMesh) {
          this.scene.remove(this.undergroundMesh);
          if (this.undergroundMesh.geometry) this.undergroundMesh.geometry.dispose();
          if (this.undergroundMesh.material) {
            if (Array.isArray(this.undergroundMesh.material)) {
              this.undergroundMesh.material.forEach(mat => mat.dispose());
            } else {
              this.undergroundMesh.material.dispose();
            }
          }
          this.undergroundMesh = null;
        }
        this.undergroundMesh = this.createUndergroundMesh();
        // 🚨 CRITICAL FIX: Check mesh isn't already in scene before adding (prevents duplicates)
        if (!this.scene.children.includes(this.undergroundMesh)) {
          this.scene.add(this.undergroundMesh);
        }
        break;
        
      case 'blank':
        // 🚨 CRITICAL FIX: Ensure no undergroundMesh exists when using blank ground
        // This prevents z-fighting if there was a previous undergroundMesh from grass mode
        // Double-check that disposeCurrentGround() removed everything, but be extra safe
        if (this.undergroundMesh) {
          if (this.scene.children.includes(this.undergroundMesh)) {
            this.scene.remove(this.undergroundMesh);
          }
          if (this.undergroundMesh.geometry) this.undergroundMesh.geometry.dispose();
          if (this.undergroundMesh.material) {
            if (Array.isArray(this.undergroundMesh.material)) {
              this.undergroundMesh.material.forEach(mat => mat.dispose());
            } else {
              this.undergroundMesh.material.dispose();
            }
          }
          this.undergroundMesh = null;
        }
        // 🚨 CRITICAL FIX: Also ensure no existing groundMesh before creating new one
        if (this.groundMesh) {
          if (this.scene.children.includes(this.groundMesh)) {
            this.scene.remove(this.groundMesh);
          }
          // Don't dispose here - disposeCurrentGround() already handled it
          // Just clear the reference to be safe
          this.groundMesh = null;
        }
        this.groundMesh = this.createBlankGround();
        // 🚨 CRITICAL FIX: Check mesh isn't already in scene before adding (prevents duplicates)
        if (!this.scene.children.includes(this.groundMesh)) {
          this.scene.add(this.groundMesh);
        }
        console.log("🌱 [GRASS] Blank ground created");
        break;
        
      case 'color':
        // 🚨 CRITICAL FIX: Ensure no undergroundMesh exists when using color ground
        // This prevents z-fighting if there was a previous undergroundMesh from grass mode
        // Double-check that disposeCurrentGround() removed everything, but be extra safe
        if (this.undergroundMesh) {
          if (this.scene.children.includes(this.undergroundMesh)) {
            this.scene.remove(this.undergroundMesh);
          }
          if (this.undergroundMesh.geometry) this.undergroundMesh.geometry.dispose();
          if (this.undergroundMesh.material) {
            if (Array.isArray(this.undergroundMesh.material)) {
              this.undergroundMesh.material.forEach(mat => mat.dispose());
            } else {
              this.undergroundMesh.material.dispose();
            }
          }
          this.undergroundMesh = null;
        }
        // 🚨 CRITICAL FIX: Also ensure no existing groundMesh before creating new one
        if (this.groundMesh) {
          if (this.scene.children.includes(this.groundMesh)) {
            this.scene.remove(this.groundMesh);
          }
          // Don't dispose here - disposeCurrentGround() already handled it
          // Just clear the reference to be safe
          this.groundMesh = null;
        }
        this.groundMesh = this.createColorGround();
        // 🚨 CRITICAL FIX: Check mesh isn't already in scene before adding (prevents duplicates)
        if (!this.scene.children.includes(this.groundMesh)) {
          this.scene.add(this.groundMesh);
        }
        console.log("🌱 [GRASS] Color ground created");
        break;
        
      case 'gltf':
        // GLTF ground type - load GLTF map
        this.loadGLTFMap();
        break;

      default:
        console.warn(`🌱 [GRASS] Unknown ground type: ${type}, using blank`);
        this.groundMesh = this.createBlankGround();
        this.scene.add(this.groundMesh);
      }
    } finally {
      // Always reset the flag, even if there was an error
      this._isSettingGroundType = false;
    }
  }
  
  /**
   * Load GLB grass model as underground for 'grass' mode
   * This creates a billboard-style grass system where the GLB model is the base
   * and the shader-based animated grass is on top
   */
  async loadGrassGroundModel() {
    if (!this.options.grassGroundModelPath) {
      console.warn("🌱 [GRASS] No grassGroundModelPath provided, skipping GLB grass model");
      return;
    }
    
    console.log(`🌱 [GRASS] Loading GLB grass model as underground: ${this.options.grassGroundModelPath}`);
    
    try {
      const loader = new GLTFLoader();
      const gltf = await new Promise((resolve, reject) => {
        loader.load(
          this.options.grassGroundModelPath,
          (gltf) => resolve(gltf),
          (progress) => {
            if (progress.lengthComputable) {
              const percent = (progress.loaded / progress.total) * 100;
              console.log(`🌱 [GRASS] Loading grass model: ${percent.toFixed(1)}%`);
            }
          },
          (error) => reject(error)
        );
      });
      
      // Get the scene from GLTF
      const grassModel = gltf.scene;
      
      // Scale and position the grass model to match the plane size
      const PLANE_SIZE = this.options.planeSize;
      
      // Calculate bounding box to determine scale
      const box = new THREE.Box3().setFromObject(grassModel);
      const size = box.getSize(new THREE.Vector3());
      const maxDimension = Math.max(size.x, size.z);
      
      // Scale to match plane size
      const scale = maxDimension > 0 ? PLANE_SIZE / maxDimension : 1;
      grassModel.scale.set(scale, scale, scale);
      
      // Center the model
      const center = box.getCenter(new THREE.Vector3());
      grassModel.position.sub(center.clone().multiplyScalar(scale));
      
      // Position at ground level
      grassModel.position.copy(this.options.position);
      
      // Ensure all meshes are visible and receive shadows
      grassModel.traverse((child) => {
        if (child.isMesh) {
          child.castShadow = true;
          child.receiveShadow = true;
          child.frustumCulled = true;
        }
      });
      
      // Store reference
      this.grassGroundModel = grassModel;
      
      // Add to scene
      this.scene.add(grassModel);
      
      console.log(`✅ [GRASS] GLB grass model loaded and positioned at:`, {
        position: this.options.position,
        scale: scale.toFixed(2),
        planeSize: PLANE_SIZE
      });
      
    } catch (error) {
      console.error(`❌ [GRASS] Failed to load GLB grass model:`, error);
      // Don't fail completely - continue with shader-based grass only
    }
  }
  
  /**
   * Load GLTF map for ground type 'gltf'
   */
  async loadGLTFMap() {
    if (!this.options.gltfMapPath) {
      console.error("❌ [GRASS] GLTF ground type requires gltfMapPath option");
      // Fallback to blank ground
      this.groundMesh = this.createBlankGround();
      this.scene.add(this.groundMesh);
      return;
    }
    
    console.log(`🗺️ [GRASS] Loading GLTF map: ${this.options.gltfMapPath}`);
    
    try {
      const loader = new GLTFLoader();
      const gltf = await new Promise((resolve, reject) => {
        loader.load(
          this.options.gltfMapPath,
          (gltf) => {
            console.log("✅ [GRASS] GLTF map loaded successfully");
            resolve(gltf);
          },
          (progress) => {
            if (progress.lengthComputable) {
              const percentComplete = (progress.loaded / progress.total) * 100;
              console.log(`📦 [GRASS] Loading map: ${percentComplete.toFixed(1)}%`);
            }
          },
          (error) => {
            console.error("❌ [GRASS] GLTF loader error:", error);
            reject(error);
          }
        );
      });
      
      console.log("✅ [GRASS] GLTF map loaded:", gltf);
      console.log("📊 [GRASS] Map structure:", {
        children: gltf.scene.children.length,
        hasAnimations: gltf.animations.length > 0,
        scene: gltf.scene
      });
      
      // Ensure all children are visible and ready
      gltf.scene.traverse((child) => {
        if (child.isMesh) {
          child.visible = true;
          child.frustumCulled = true; // Enable frustum culling for performance
          child.receiveShadow = true;
          child.castShadow = true;
          // Ensure materials are set up correctly
          if (child.material) {
            if (Array.isArray(child.material)) {
              child.material.forEach(mat => {
                if (mat) {
                  mat.needsUpdate = true;
                }
              });
            } else if (child.material) {
              child.material.needsUpdate = true;
            }
          }
        }
        // Also ensure groups are visible
        if (child.isGroup || child.isObject3D) {
          child.visible = true;
        }
      });
      
      // Ensure the root scene is visible
      gltf.scene.visible = true;
      
      // Calculate map dimensions for scaling/positioning
      const box = new THREE.Box3().setFromObject(gltf.scene);
      const size = box.getSize(new THREE.Vector3());
      const center = box.getCenter(new THREE.Vector3());
      const maxDimension = Math.max(size.x, size.y, size.z);
      
      console.log("📏 [GRASS] Map dimensions:", {
        size: size,
        center: center,
        maxDimension: maxDimension,
        boundingBox: box
      });
      
      // Scale map if needed (use planeSize as target size)
      const targetSize = this.options.planeSize || 200;
      if (maxDimension > 0 && maxDimension !== targetSize) {
        const scaleFactor = targetSize / maxDimension;
        gltf.scene.scale.setScalar(scaleFactor);
        console.log(`📐 [GRASS] Map scaled by ${scaleFactor.toFixed(4)}x to ${targetSize} units`);
      }
      
      // Center map at origin (subtract center offset)
      gltf.scene.position.sub(center.clone().multiplyScalar(gltf.scene.scale.x));
      
      // Apply position offset from options
      if (this.options.position) {
        gltf.scene.position.add(this.options.position);
      }
      
      // Recalculate bounding box after scaling/positioning
      const scaledBox = new THREE.Box3().setFromObject(gltf.scene);
      const scaledMin = scaledBox.min;
      const scaledMax = scaledBox.max;
      
      console.log("📏 [GRASS] Map after scaling/positioning:", {
        min: scaledMin,
        max: scaledMax,
        size: scaledBox.getSize(new THREE.Vector3())
      });
      
      // Store GLTF model
      this.gltfMapModel = gltf.scene;
      
      // Add to scene (GLTF model acts as ground mesh for this type)
      this.scene.add(gltf.scene);
      
      // Store reference in groundMesh for consistency with other ground types
      this.groundMesh = gltf.scene;
      
      console.log(`✅ [GRASS] GLTF map added to scene successfully`);
      console.log("👁️ [GRASS] Map visibility check:", {
        mapModelVisible: this.gltfMapModel.visible,
        mapModelInScene: this.scene.children.includes(this.gltfMapModel)
      });
      
      // Return map info for collision system
      return {
        model: gltf.scene,
        boundingBox: scaledBox,
        groundY: scaledMin.y
      };
    } catch (error) {
      console.error(`❌ [GRASS] Failed to load GLTF map (${this.options.gltfMapPath}):`, error);
      // Fallback to blank ground
      this.groundMesh = this.createBlankGround();
      this.scene.add(this.groundMesh);
      return null;
    }
  }
  
  /**
   * Get GLTF map model (for collision system)
   */
  getGLTFMapModel() {
    return this.gltfMapModel;
  }
  
  update(delta, camera = null) {
    this.elapsedTime += delta * 1000; // Convert to milliseconds
    
    // Update grass wind animation
    if (this.groundMesh && this.groundMesh.userData.isGrassMesh && this.groundMesh.userData.grassUniforms) {
      const elapsedTime = Date.now() - this.startTime;
      this.groundMesh.userData.grassUniforms.iTime.value = elapsedTime;
      this.groundMesh.userData.grassUniforms.windSpeed.value = this.options.windSpeed;
      this.groundMesh.userData.grassUniforms.windStrength.value = this.options.windStrength;
      
      // NEW: Update blade length multiplier
      if (this.groundMesh.userData.grassUniforms.bladeLengthMultiplier) {
        this.groundMesh.userData.grassUniforms.bladeLengthMultiplier.value = this.options.bladeLengthMultiplier || 1.0;
      }
      
      // NEW: Update wind direction
      if (this.groundMesh.userData.grassUniforms.windDirection) {
        this.groundMesh.userData.grassUniforms.windDirection.value = this.windDirection;
      }
      
      // Update wind noise texture if it exists
      if (this.groundMesh.userData.grassUniforms.windNoiseTexture && this.windNoiseTexture) {
        this.groundMesh.userData.grassUniforms.windNoiseTexture.value = this.windNoiseTexture;
      }
      
      // NEW: Update wind turbulence
      if (this.groundMesh.userData.grassUniforms.windTurbulence) {
        this.groundMesh.userData.grassUniforms.windTurbulence.value = this.options.windTurbulence || 0.2;
      }
      
      // NEW: Calculate and update wind gust
      if (this.groundMesh.userData.grassUniforms.windGust && this.options.windGustFrequency > 0) {
        const gustTime = elapsedTime / 1000; // Time in seconds
        const gustCycle = gustTime * this.options.windGustFrequency; // Total cycles
        const gustPhase = gustCycle % 1.0; // Phase within current cycle (0-1)
        
        let gustMultiplier = 1.0;
        const gustDuration = this.options.windGustDuration || 0.5;
        
        if (gustPhase < gustDuration) {
          // Gust is active
          const gustProgress = gustPhase / gustDuration;
          // Smooth gust curve (ease in/out using sine)
          const gustCurve = Math.sin(gustProgress * Math.PI);
          gustMultiplier = 1.0 + (gustCurve * (this.options.windGustIntensity - 1.0));
        }
        
        this.groundMesh.userData.grassUniforms.windGust.value = gustMultiplier;
      } else if (this.groundMesh.userData.grassUniforms.windGust) {
        // No gusts (frequency = 0)
        this.groundMesh.userData.grassUniforms.windGust.value = 1.0;
      }
      
      // CRITICAL: Update textures if they've loaded (in case they loaded after mesh creation)
      if (this.grassTexture && this.cloudTexture) {
        const uniforms = this.groundMesh.userData.grassUniforms;
        if (uniforms.grassTexture.value !== this.grassTexture || uniforms.cloudTexture.value !== this.cloudTexture) {
          uniforms.grassTexture.value = this.grassTexture;
          uniforms.cloudTexture.value = this.cloudTexture;
          this.groundMesh.material.needsUpdate = true;
          console.log("🌱 [GRASS] Textures updated in uniforms");
        }
      }
    }
    
    // PHASE 2: Update chunks if chunked mode is enabled
    if (this.useChunkedGrass && camera) {
      this.updateChunks(camera.position);
      
      // Update all chunk uniforms
      const elapsedTime = Date.now() - this.startTime;
      for (const [key, chunk] of this.chunks) {
        if (chunk.mesh && chunk.mesh.userData.grassUniforms) {
          const uniforms = chunk.mesh.userData.grassUniforms;
          uniforms.iTime.value = elapsedTime;
          uniforms.windSpeed.value = this.options.windSpeed;
          uniforms.windStrength.value = this.options.windStrength;
          uniforms.bladeLengthMultiplier.value = this.options.bladeLengthMultiplier || 1.0;
          uniforms.windDirection.value = this.windDirection;
          if (uniforms.windNoiseTexture && this.windNoiseTexture) {
            uniforms.windNoiseTexture.value = this.windNoiseTexture;
          }
          uniforms.windTurbulence.value = this.options.windTurbulence || 0.2;
          
          // Update wind gust for chunk
          if (uniforms.windGust && this.options.windGustFrequency > 0) {
            const gustTime = elapsedTime / 1000;
            const gustCycle = gustTime * this.options.windGustFrequency;
            const gustPhase = gustCycle % 1.0;
            let gustMultiplier = 1.0;
            const gustDuration = this.options.windGustDuration || 0.5;
            if (gustPhase < gustDuration) {
              const gustProgress = gustPhase / gustDuration;
              const gustCurve = Math.sin(gustProgress * Math.PI);
              gustMultiplier = 1.0 + (gustCurve * (this.options.windGustIntensity - 1.0));
            }
            uniforms.windGust.value = gustMultiplier;
          } else if (uniforms.windGust) {
            uniforms.windGust.value = 1.0;
          }
          
          // Update textures
          if (this.grassTexture && this.cloudTexture) {
            if (uniforms.grassTexture.value !== this.grassTexture || uniforms.cloudTexture.value !== this.cloudTexture) {
              uniforms.grassTexture.value = this.grassTexture;
              uniforms.cloudTexture.value = this.cloudTexture;
              chunk.mesh.material.needsUpdate = true;
            }
          }
        }
      }
    }
  }
  
  /**
   * PHASE 2: Generate initial chunks around origin
   * Called when chunked mode is enabled
   * Uses asynchronous generation to prevent browser freeze
   * @param {Function} onComplete - Callback when generation is complete
   */
  generateInitialChunks(onComplete = null) {
    // Dispose existing chunks if any
    this.disposeAllChunks();
    
    // Create chunk group
    if (!this.chunkGroup) {
      this.chunkGroup = new THREE.Group();
      this.chunkGroup.name = 'GrassChunks';
      this.scene.add(this.chunkGroup);
    }
    
    // Collect all chunk coordinates to generate
    const chunksToGenerate = [];
    for (let x = -this.loadRadius; x <= this.loadRadius; x++) {
      for (let z = -this.loadRadius; z <= this.loadRadius; z++) {
        chunksToGenerate.push({ x, z });
      }
    }
    
    const totalChunks = chunksToGenerate.length;
    console.log(`🌱 [GRASS] Generating ${totalChunks} initial chunks asynchronously (radius: ${this.loadRadius})`);
    
    // Generate chunks progressively to avoid freezing
    let chunkIndex = 0;
    const generateNextChunk = () => {
      if (chunkIndex >= chunksToGenerate.length) {
        console.log(`✅ [GRASS] ${this.chunks.size} chunks generated and added to scene`);
        if (onComplete) onComplete();
        return;
      }
      
      const { x, z } = chunksToGenerate[chunkIndex];
      // Use current chunkSize (may have been updated via UI)
      const currentChunkSize = this.chunkSize;
      const chunk = new GrassChunk(x, z, currentChunkSize, this.options, this);
      chunk.generate();
      this.chunkGroup.add(chunk.mesh);
      this.chunks.set(`${x}_${z}`, chunk);
      
      chunkIndex++;
      
      // Yield to browser every chunk to prevent freeze
      // Use setTimeout with 10ms delay to give browser more breathing room
      if (chunkIndex < chunksToGenerate.length) {
        setTimeout(generateNextChunk, 10); // Increased from 0ms to 10ms for better performance
      } else {
        console.log(`✅ [GRASS] ${this.chunks.size} chunks generated and added to scene`);
        if (onComplete) onComplete();
      }
    };
    
    // Start generating chunks
    generateNextChunk();
  }
  
  /**
   * PHASE 2: Update chunks based on camera position
   * Loads new chunks and unloads distant ones
   * Uses throttling and async generation to prevent stuttering
   */
  updateChunks(cameraPosition) {
    if (!this.chunkGroup) return;
    
    // Throttle chunk updates (don't check every frame to reduce overhead)
    this.chunkUpdateThrottle++;
    if (this.chunkUpdateThrottle < this.chunkUpdateInterval) {
      return; // Skip this frame
    }
    this.chunkUpdateThrottle = 0; // Reset counter
    
    // Prevent concurrent chunk generation
    if (this.isGeneratingChunks) {
      return; // Already generating chunks, skip this update
    }
    
    // Calculate current chunk grid position
    const chunkX = Math.floor(cameraPosition.x / this.chunkSize);
    const chunkZ = Math.floor(cameraPosition.z / this.chunkSize);
    
    const chunksToLoad = [];
    const chunksToUnload = [];
    
    // Find chunks that should be loaded
    for (let x = chunkX - this.loadRadius; x <= chunkX + this.loadRadius; x++) {
      for (let z = chunkZ - this.loadRadius; z <= chunkZ + this.loadRadius; z++) {
        const key = `${x}_${z}`;
        if (!this.chunks.has(key)) {
          chunksToLoad.push({ x, z });
        }
      }
    }
    
    // Find chunks that should be unloaded
    for (const [key, chunk] of this.chunks) {
      const distance = Math.sqrt(
        Math.pow(chunk.chunkX - chunkX, 2) + Math.pow(chunk.chunkZ - chunkZ, 2)
      );
      if (distance > this.unloadRadius) {
        chunksToUnload.push(key);
      }
    }
    
    // Unload distant chunks immediately (fast operation, no blocking)
    chunksToUnload.forEach(key => {
      const chunk = this.chunks.get(key);
      if (chunk) {
        chunk.dispose();
        this.chunks.delete(key);
      }
    });
    
    // Load new chunks asynchronously (prevents stuttering)
    if (chunksToLoad.length > 0) {
      this.isGeneratingChunks = true;
      let loadIndex = 0;
      
      const loadNextChunk = () => {
        if (loadIndex >= chunksToLoad.length) {
          this.isGeneratingChunks = false;
          if (chunksToLoad.length > 0 || chunksToUnload.length > 0) {
            console.log(`🌱 [GRASS] Chunks updated: +${chunksToLoad.length} -${chunksToUnload.length} (total: ${this.chunks.size})`);
          }
          return;
        }
        
        const { x, z } = chunksToLoad[loadIndex];
        // Use current chunkSize (may have been updated via UI)
        const currentChunkSize = this.chunkSize;
        const chunk = new GrassChunk(x, z, currentChunkSize, this.options, this);
        chunk.generate();
        this.chunkGroup.add(chunk.mesh);
        this.chunks.set(`${x}_${z}`, chunk);
        
        loadIndex++;
        
        // Yield to browser between chunks to prevent blocking
        // Use 10ms delay to give browser more breathing room
        if (loadIndex < chunksToLoad.length) {
          setTimeout(loadNextChunk, 10); // Increased from 0ms to 10ms
        } else {
          this.isGeneratingChunks = false;
          if (chunksToLoad.length > 0 || chunksToUnload.length > 0) {
            console.log(`🌱 [GRASS] Chunks updated: +${chunksToLoad.length} -${chunksToUnload.length} (total: ${this.chunks.size})`);
          }
        }
      };
      
      // Start loading chunks asynchronously
      loadNextChunk();
    } else if (chunksToUnload.length > 0) {
      // Only unloads happened
      console.log(`🌱 [GRASS] Chunks updated: +0 -${chunksToUnload.length} (total: ${this.chunks.size})`);
    }
  }
  
  /**
   * PHASE 2: Dispose all chunks
   */
  disposeAllChunks() {
    for (const [key, chunk] of this.chunks) {
      chunk.dispose();
    }
    this.chunks.clear();
    
    if (this.chunkGroup) {
      this.scene.remove(this.chunkGroup);
      this.chunkGroup = null;
    }
  }
  
  setWindSpeed(speed) {
    this.options.windSpeed = Math.max(0, Math.min(3.0, speed));
    if (this.groundMesh && this.groundMesh.userData.grassUniforms) {
      this.groundMesh.userData.grassUniforms.windSpeed.value = this.options.windSpeed;
    }
  }
  
  setWindStrength(strength) {
    this.options.windStrength = Math.max(0, Math.min(1.0, strength));
    if (this.groundMesh && this.groundMesh.userData.grassUniforms) {
      this.groundMesh.userData.grassUniforms.windStrength.value = this.options.windStrength;
    }
  }
  
  /**
   * Set wind turbulence intensity (0.0-1.0)
   * 
   * PHASE 4: ADVANCED WIND FEATURES - Adds chaotic wind variation
   * 
   * Turbulence adds random, chaotic movement to grass blades by scaling
   * noise-based variation. Higher values create more unpredictable,
   * natural-looking wind patterns.
   * 
   * Implementation:
   * - Uniform: `windTurbulence` (0.0-1.0)
   * - Applied in shader: `turbulence = (noise.r - 0.5) * 2.0 * windTurbulence`
   * - Added to base wave movement for chaotic effect
   * - Default: 0.2 (20% turbulence)
   * 
   * @param {number} intensity - Turbulence intensity (0.0-1.0)
   * @returns {number} - Clamped intensity value
   */
  setWindTurbulence(intensity) {
    this.options.windTurbulence = Math.max(0, Math.min(1.0, intensity));
    if (this.groundMesh && this.groundMesh.userData.grassUniforms) {
      const uniforms = this.groundMesh.userData.grassUniforms;
      if (uniforms.windTurbulence) {
        uniforms.windTurbulence.value = this.options.windTurbulence;
        console.log(`🌱 [GRASS] Wind turbulence set to: ${this.options.windTurbulence.toFixed(2)}`);
      }
    }
    return this.options.windTurbulence;
  }
  
  /**
   * Set wind gust frequency (gusts per second)
   * 
   * PHASE 4: ADVANCED WIND FEATURES - Controls how often gusts occur
   * 
   * Wind gusts are temporary increases in wind strength that create
   * dramatic wind effects. This controls how frequently gusts occur.
   * 
   * Implementation:
   * - Calculated in update() method based on elapsed time
   * - Uses sine wave for smooth gust curves (ease in/out)
   * - Frequency = 0 disables gusts completely
   * - Default: 0.5 gusts per second
   * 
   * @param {number} frequency - Gusts per second (0-5.0)
   * @returns {number} - Clamped frequency value
   */
  setWindGustFrequency(frequency) {
    this.options.windGustFrequency = Math.max(0, Math.min(5.0, frequency));
    console.log(`🌱 [GRASS] Wind gust frequency set to: ${this.options.windGustFrequency.toFixed(2)}/s`);
    return this.options.windGustFrequency;
  }
  
  /**
   * Set wind gust intensity (max multiplier)
   * 
   * PHASE 4: ADVANCED WIND FEATURES - Controls gust strength
   * 
   * This controls the maximum multiplier applied to wind strength during
   * gusts. Higher values create more dramatic wind effects.
   * 
   * Implementation:
   * - Multiplies windStrength during active gusts
   * - Applied via `windGust` uniform (updated dynamically)
   * - Range: 1.0 (no effect) to 5.0 (5x wind strength)
   * - Default: 1.5x (50% increase during gusts)
   * 
   * @param {number} intensity - Max gust multiplier (1.0-5.0)
   * @returns {number} - Clamped intensity value
   */
  setWindGustIntensity(intensity) {
    this.options.windGustIntensity = Math.max(1.0, Math.min(5.0, intensity));
    console.log(`🌱 [GRASS] Wind gust intensity set to: ${this.options.windGustIntensity.toFixed(2)}x`);
    return this.options.windGustIntensity;
  }
  
  /**
   * Set wind direction (angle in degrees, 0-360)
   * 
   * PHASE 1: WIND DIRECTION CONTROL - Allows 360° wind direction control
   * 
   * Converts angle to normalized direction vector (X, Z components) and
   * updates the shader uniform. The direction vector is applied to all
   * wind movement calculations in the vertex shader.
   * 
   * Direction Mapping:
   * - 0° = +X direction (east)
   * - 90° = +Z direction (north)
   * - 180° = -X direction (west)
   * - 270° = -Z direction (south)
   * 
   * @param {number} angle - Wind direction angle in degrees (0-360)
   * @returns {number} - Clamped angle value (0-360)
   */
  setWindDirection(angle) {
    // Clamp angle to 0-360
    const clamped = ((angle % 360) + 360) % 360;
    
    // Convert to radians
    const radians = (clamped * Math.PI) / 180;
    
    // Calculate normalized direction vector (X, Z components)
    this.windDirection.set(
      Math.cos(radians),
      Math.sin(radians)
    );
    
    // Store angle for UI
    this.windDirectionAngle = clamped;
    this.options.windDirectionAngle = clamped;
    
    // Update uniform if mesh exists
    if (this.groundMesh && this.groundMesh.userData.grassUniforms) {
      const uniforms = this.groundMesh.userData.grassUniforms;
      if (uniforms.windDirection) {
        uniforms.windDirection.value = this.windDirection;
        console.log(`🌱 [GRASS] Wind direction set to: ${clamped.toFixed(1)}° (${this.windDirection.x.toFixed(2)}, ${this.windDirection.y.toFixed(2)})`);
      }
    }
    
    return clamped;
  }
  
  /**
   * Set blade length multiplier (0.5x to 2.0x)
   * Updates shader uniform in real-time without regenerating geometry
   * @param {number} multiplier - Blade length multiplier (0.5 to 2.0)
   * @returns {number} - Clamped multiplier value
   */
  setBladeLength(multiplier) {
    // Validate and clamp
    const min = this.options.bladeLengthMin || 0.5;
    const max = this.options.bladeLengthMax || 2.0;
    const clamped = Math.max(min, Math.min(max, multiplier));
    
    // Update options
    this.options.bladeLengthMultiplier = clamped;
    
    // Update uniform if mesh exists
    if (this.groundMesh && this.groundMesh.userData.grassUniforms) {
      const uniforms = this.groundMesh.userData.grassUniforms;
      if (uniforms.bladeLengthMultiplier) {
        uniforms.bladeLengthMultiplier.value = clamped;
        console.log(`🌱 [GRASS] Blade length multiplier set to: ${clamped.toFixed(2)}x`);
      }
    }
    
    return clamped;
  }
  
  setBladeCount(count) {
    this.options.bladeCount = Math.max(0, Math.min(2000000, count)); // Increased to 2M for testing
    if (this.options.groundType === 'grass') {
      // Recreate grass field with new count
      this.setGroundType('grass');
    }
  }
  
  setGroundColor(color) {
    this.options.groundColor = color;
    if (this.options.groundType === 'color' && this.groundMesh && this.groundMesh.material) {
      this.groundMesh.material.color.setHex(color);
    }
  }
  
  /**
   * Dispose all ground-related meshes to prevent z-fighting and memory leaks
   * 
   * FIXED (December 16, 2025): Enhanced disposal logic to prevent underground flickering
   * - Added scene.children.includes() checks before removal (prevents errors)
   * - Explicitly disposes undergroundMesh (prevents duplicate meshes)
   * - Explicitly disposes grassGroundModel (GLB grass underground)
   * - Proper geometry and material disposal for all mesh types
   * - Prevents T-shape flickering when switching between ground types
   * 
   * Called automatically when setGroundType() is called to switch ground types
   */
  disposeCurrentGround() {
    // 🚨 CRITICAL FIX: Dispose groundMesh with safety checks
    // FIXED (Dec 16, 2025): Added scene.children checks to prevent errors and ensure proper cleanup
    if (this.groundMesh) {
      // Check if mesh is still in scene before removing (prevents errors)
      if (this.scene.children.includes(this.groundMesh)) {
        this.scene.remove(this.groundMesh);
      }
      
      // For GLTF maps, don't dispose (they're complex models)
      if (this.options.groundType === 'gltf' && this.gltfMapModel) {
        // Just remove from scene, don't dispose GLTF models
        this.gltfMapModel = null;
        this.groundMesh = null;
        // Continue to dispose undergroundMesh even for GLTF
      } else {
        // Dispose geometry
        if (this.groundMesh.geometry) {
          this.groundMesh.geometry.dispose();
        }
        
        // Dispose material
        if (this.groundMesh.material) {
          if (Array.isArray(this.groundMesh.material)) {
            this.groundMesh.material.forEach(mat => mat.dispose());
          } else {
            this.groundMesh.material.dispose();
          }
        }
        
        this.groundMesh = null;
      }
    }
    
    // 🚨 CRITICAL FIX: Also dispose undergroundMesh to prevent z-fighting with multiple ground meshes
    // This prevents flickering when switching between levels (e.g., Level 5 grass -> Level 6 blank)
    if (this.undergroundMesh) {
      // Check if mesh is still in scene before removing (prevents errors)
      if (this.scene.children.includes(this.undergroundMesh)) {
        this.scene.remove(this.undergroundMesh);
      }
      
      // Dispose geometry
      if (this.undergroundMesh.geometry) {
        this.undergroundMesh.geometry.dispose();
      }
      
      // Dispose material
      if (this.undergroundMesh.material) {
        if (Array.isArray(this.undergroundMesh.material)) {
          this.undergroundMesh.material.forEach(mat => mat.dispose());
        } else {
          this.undergroundMesh.material.dispose();
        }
      }
      
      this.undergroundMesh = null;
    }
    
    // Also clear GLTF model reference
    if (this.gltfMapModel) {
      this.gltfMapModel = null;
    }
    
    // Remove grass ground model (GLB grass underground) if it exists
    if (this.grassGroundModel) {
      this.scene.remove(this.grassGroundModel);
      
      // Dispose of GLB model resources
      this.grassGroundModel.traverse((child) => {
        if (child.isMesh) {
          if (child.geometry) child.geometry.dispose();
          if (child.material) {
            if (Array.isArray(child.material)) {
              child.material.forEach(mat => mat.dispose());
            } else {
              child.material.dispose();
            }
          }
        }
      });
      
      this.grassGroundModel = null;
    }
    
    // Remove underground mesh
    if (this.undergroundMesh) {
      this.scene.remove(this.undergroundMesh);
      if (this.undergroundMesh.geometry) this.undergroundMesh.geometry.dispose();
      if (this.undergroundMesh.material) {
        if (Array.isArray(this.undergroundMesh.material)) {
          this.undergroundMesh.material.forEach(mat => mat.dispose());
        } else {
          this.undergroundMesh.material.dispose();
        }
      }
      this.undergroundMesh = null;
    }
  }
  
  dispose() {
    // CRITICAL: Dispose all chunks first (if chunked mode is enabled)
    this.disposeAllChunks();
    
    // Dispose ground mesh and all related meshes
    this.disposeCurrentGround();
    
    // Textures are managed externally, don't dispose here
    this.grassTexture = null;
    this.cloudTexture = null;
    this.windNoiseTexture = null; // Dispose noise texture
    this.texturesLoaded = false;
    
    // CRITICAL: Ensure chunkGroup is removed from scene
    if (this.chunkGroup && this.scene) {
      if (this.scene.children.includes(this.chunkGroup)) {
        this.scene.remove(this.chunkGroup);
      }
      this.chunkGroup = null;
    }
    
    // CRITICAL: Clear all chunk references
    this.chunks.clear();
    
    console.log("🧹 [GRASS SYSTEM] Fully disposed - all meshes and chunks removed from scene");
  }
  
  getOptions() {
    return { 
      ...this.options,
      useChunkedGrass: this.useChunkedGrass,
      chunkSize: this.chunkSize,
      maxBladesPerChunk: this.maxBladesPerChunk
    };
  }
  
  /**
   * Regenerate grass with current exclusion zones applied
   * 
   * GRASS EXCLUSION ZONE SYSTEM (Phase 3)
   * 
   * This method regenerates the grass field, applying all registered exclusion zones.
   * Useful when objects (chests, trees, etc.) are added after initial grass generation.
   * 
   * @returns {Promise<void>} - Resolves when grass regeneration is complete
   */
  async regenerateGrass() {
    const currentGroundType = this.options.groundType;
    
    if (currentGroundType !== 'grass') {
      console.warn("🌱 [GRASS] Cannot regenerate - ground type is not 'grass'");
      return;
    }
    
    console.log("🌱 [GRASS] Regenerating grass with exclusion zones...", {
      exclusionZones: this.exclusionZones.size,
      useChunkedGrass: this.useChunkedGrass
    });
    
    // Regenerate based on current mode
    if (this.useChunkedGrass) {
      // Chunked mode: Dispose all chunks and regenerate
      this.disposeAllChunks();
      this.isInitializing = true;
      // generateInitialChunks uses callback, wrap in Promise
      await new Promise((resolve) => {
        this.generateInitialChunks(() => {
          this.isInitializing = false;
          console.log("✅ [GRASS] Chunked grass regenerated with exclusion zones");
          resolve();
        });
      });
    } else {
      // Single mesh mode: Regenerate the single mesh
      if (this.groundMesh) {
        this.scene.remove(this.groundMesh);
        if (this.groundMesh.geometry) this.groundMesh.geometry.dispose();
        if (this.groundMesh.material) this.groundMesh.material.dispose();
      }
      
      if (this.grassTexture && this.cloudTexture) {
        this.groundMesh = this.generateGrassField();
        // Update uniforms with textures
        if (this.groundMesh.userData.grassUniforms) {
          this.groundMesh.userData.grassUniforms.grassTexture.value = this.grassTexture;
          this.groundMesh.userData.grassUniforms.cloudTexture.value = this.cloudTexture;
        }
        this.scene.add(this.groundMesh);
        console.log("✅ [GRASS] Single mesh grass regenerated with exclusion zones");
      } else {
        console.warn("🌱 [GRASS] Cannot regenerate - textures not loaded");
      }
    }
  }
  
  /**
   * Set chunk size (requires grass recreation)
   * 
   * CHUNK SIZE CONFIGURATION:
   * - Range: 10-200 world units (clamped automatically)
   * - Default: 50 world units
   * - Smaller chunks = more chunks = better frustum culling but more overhead
   * - Larger chunks = fewer chunks = less overhead but less culling benefit
   * - Recommended: 50-100 world units for balanced performance
   * 
   * PERFORMANCE IMPACT:
   * - Changing chunk size requires grass recreation
   * - Toggle chunked mode off/on to apply changes
   * - Smaller chunks improve culling but increase draw calls
   * - Larger chunks reduce draw calls but reduce culling efficiency
   * 
   * @param {number} size - Chunk size in world units (10-200)
   * @returns {number} - Clamped chunk size value
   */
  setChunkSize(size) {
    this.chunkSize = Math.max(10, Math.min(200, size)); // Clamp between 10-200
    console.log(`🌱 [GRASS] Chunk size set to: ${this.chunkSize} world units (toggle chunked mode to apply)`);
    // Note: Changing chunk size requires recreation, handled by setGroundType
    return this.chunkSize;
  }
  
  /**
   * Set max blades per chunk (requires grass recreation)
   * 
   * MAX BLADES PER CHUNK CONFIGURATION:
   * - Range: 10,000 - 1,000,000 blades (clamped automatically)
   * - Default: 200,000 blades per chunk
   * - Safety limit to prevent memory issues with very dense grass
   * - Automatically caps chunk blade count if exceeded
   * - Lower = faster generation, higher = more detail per chunk
   * - Recommended: 100K-200K for balanced performance
   * 
   * PERFORMANCE IMPACT:
   * - Changing max blades requires grass recreation
   * - Toggle chunked mode off/on to apply changes
   * - Lower values = faster chunk generation, less memory per chunk
   * - Higher values = slower chunk generation, more memory per chunk
   * - Each chunk is capped at this value (prevents excessive memory usage)
   * 
   * MEMORY CONSIDERATIONS:
   * - Each blade uses ~5 vertices (BL, BR, TR, TL, TC)
   * - 200K blades = ~1M vertices per chunk
   * - Memory per chunk: ~12MB (positions) + ~12MB (colors) + ~12MB (UVs) = ~36MB
   * - With 25 chunks loaded (loadRadius=2): ~900MB total
   * - Adjust maxBladesPerChunk based on available memory
   * 
   * @param {number} maxBlades - Maximum blades per chunk (10K-1M)
   * @returns {number} - Clamped max blades value
   */
  setMaxBladesPerChunk(maxBlades) {
    this.maxBladesPerChunk = Math.max(10000, Math.min(1000000, maxBlades)); // Clamp between 10K-1M
    console.log(`🌱 [GRASS] Max blades per chunk set to: ${this.maxBladesPerChunk.toLocaleString()} (toggle chunked mode to apply)`);
    // Note: Changing max blades requires recreation, handled by setGroundType
    return this.maxBladesPerChunk;
  }
  
  /**
   * Register an exclusion zone to prevent grass from rendering in that area
   * 
   * GRASS EXCLUSION ZONE SYSTEM (Phase 1)
   * 
   * Registers an object's bounding box as an exclusion zone. During grass generation,
   * any blade positions that overlap with exclusion zones will be skipped.
   * 
   * @param {string} objectId - Unique identifier for the object (e.g., 'chest_001')
   * @param {THREE.Box3} boundingBox - Bounding box of the object in world coordinates
   * @param {number} padding - Extra space around object in world units (default: 0.5)
   * @returns {boolean} - True if successfully registered
   * 
   * Example:
   * ```javascript
   * const box = new THREE.Box3().setFromObject(chestMesh);
   * grassSystem.registerExclusionZone('chest_001', box, 0.5);
   * ```
   */
  registerExclusionZone(objectId, boundingBox, padding = 0.5) {
    if (!objectId || !boundingBox) {
      console.warn(`🌱 [GRASS] Cannot register exclusion zone: missing objectId or boundingBox`);
      return false;
    }
    
    // Calculate bounds with padding
    const min = boundingBox.min.clone();
    const max = boundingBox.max.clone();
    
    // Apply padding (expand bounds outward)
    min.x -= padding;
    min.z -= padding;
    max.x += padding;
    max.z += padding;
    
    // Store exclusion zone
    this.exclusionZones.set(objectId, {
      id: objectId,
      bounds: {
        minX: min.x,
        maxX: max.x,
        minZ: min.z,
        maxZ: max.z
      },
      padding: padding,
      boundingBox: boundingBox // Store original for reference
    });
    
    console.log(`🌱 [GRASS] Exclusion zone registered: ${objectId}`, {
      bounds: {
        minX: min.x.toFixed(2),
        maxX: max.x.toFixed(2),
        minZ: min.z.toFixed(2),
        maxZ: max.z.toFixed(2)
      },
      padding: padding
    });
    
    return true;
  }
  
  /**
   * Unregister an exclusion zone
   * 
   * Removes an object's exclusion zone from the registry. Note: This does not
   * regenerate grass automatically. You may need to recreate the grass field
   * if you want the grass to appear in the previously excluded area.
   * 
   * @param {string} objectId - Unique identifier for the object
   * @returns {boolean} - True if successfully unregistered
   */
  unregisterExclusionZone(objectId) {
    if (!objectId) {
      console.warn(`🌱 [GRASS] Cannot unregister exclusion zone: missing objectId`);
      return false;
    }
    
    const removed = this.exclusionZones.delete(objectId);
    
    if (removed) {
      console.log(`🌱 [GRASS] Exclusion zone unregistered: ${objectId}`);
    } else {
      console.warn(`🌱 [GRASS] Exclusion zone not found: ${objectId}`);
    }
    
    return removed;
  }
  
  /**
   * Clear all exclusion zones
   * 
   * Removes all registered exclusion zones. Useful for level resets or cleanup.
   * Note: This does not regenerate grass automatically.
   */
  clearExclusionZones() {
    const count = this.exclusionZones.size;
    this.exclusionZones.clear();
    console.log(`🌱 [GRASS] Cleared ${count} exclusion zone(s)`);
  }
  
  /**
   * Check if a position is excluded from grass generation
   * 
   * Checks if the given (x, z) position overlaps with any registered exclusion zone.
   * Used during grass generation to skip blade positions that would be inside objects.
   * 
   * @param {number} x - X coordinate (world space)
   * @param {number} z - Z coordinate (world space)
   * @returns {boolean} - True if position should be excluded (no grass here)
   */
  isPositionExcluded(x, z) {
    if (this.exclusionZones.size === 0) {
      return false; // No exclusion zones, position is not excluded
    }
    
    // Check against all exclusion zones
    for (const [objectId, zone] of this.exclusionZones) {
      const bounds = zone.bounds;
      
      // Check if position is inside exclusion zone bounds
      if (x >= bounds.minX && x <= bounds.maxX &&
          z >= bounds.minZ && z <= bounds.maxZ) {
        return true; // Position is excluded
      }
    }
    
    return false; // Position is not excluded
  }
  
  /**
   * Get all registered exclusion zones (for debugging/inspection)
   * 
   * @returns {Array} - Array of exclusion zone objects
   */
  getExclusionZones() {
    return Array.from(this.exclusionZones.values());
  }
  
  setOptions(newOptions) {
    // Update options
    const prev = { ...this.options };
    Object.assign(this.options, newOptions);
    
    // Update wind parameters if changed (no rebuild needed)
    if (newOptions.windSpeed !== undefined) {
      this.setWindSpeed(newOptions.windSpeed);
    }
    if (newOptions.windStrength !== undefined) {
      this.setWindStrength(newOptions.windStrength);
    }
    
    // Recreate ground if type or key parameters change
    if (newOptions.groundType !== undefined && newOptions.groundType !== prev.groundType) {
      this.setGroundType(newOptions.groundType);
    } else if (this.groundMesh && this.groundMesh.userData.isGrassMesh) {
      // If it's a grass mesh and grass-specific options changed, recreate it
      if (newOptions.bladeCount !== undefined || newOptions.bladeWidth !== undefined ||
          newOptions.bladeHeight !== undefined || newOptions.bladeHeightVariation !== undefined ||
          newOptions.grassColor !== undefined) {
        this.setGroundType('grass'); // Recreate grass field
      }
      // Underground changes for grass: recreate underground mesh
      if (newOptions.undergroundType !== undefined ||
          newOptions.undergroundColor !== undefined ||
          newOptions.undergroundTexturePath !== undefined) {
        // Recreate ground to apply underground changes
        this.setGroundType('grass');
      }
    } else if (this.groundMesh && (this.options.groundType === 'blank' || this.options.groundType === 'color')) {
      // If it's a blank/color mesh and color changed, update material
      if (newOptions.groundColor !== undefined && this.groundMesh.material.color) {
        this.groundMesh.material.color.setHex(newOptions.groundColor);
      }
    }
  }
}

