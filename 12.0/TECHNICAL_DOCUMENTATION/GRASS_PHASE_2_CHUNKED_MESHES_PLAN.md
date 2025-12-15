# 🌱 PHASE 2: CHUNKED GRASS MESHES - IMPLEMENTATION PLAN

**Date:** December 13, 2025  
**Status:** 🔄 **IN PROGRESS**  
**Priority:** ⭐ **HIGH** (Performance Critical)

---

## 🎯 **OBJECTIVE**

Implement chunked grass mesh system to improve performance for large grass fields (5M+ blades) by:
- Dividing grass into smaller, manageable chunks
- Enabling frustum culling per chunk
- Dynamic chunk loading/unloading based on camera position
- Maintaining backward compatibility with existing single-mesh system

---

## 📋 **IMPLEMENTATION STRATEGY**

### **Approach: Optional Feature (Backward Compatible)**

- **Default Behavior:** Single mesh (existing system) - NO BREAKING CHANGES
- **Chunked Mode:** Enabled via `useChunkedGrass: true` option
- **Gradual Migration:** Existing levels continue using single mesh
- **New Levels:** Can opt-in to chunked system for better performance

---

## 🏗️ **ARCHITECTURE**

### **1. GrassChunk Class**

```javascript
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
  
  generate() {
    // Generate grass only for this chunk's bounds
    // Use modified generateGrassField() logic
  }
  
  dispose() {
    // Clean up geometry, material, and remove from scene
  }
}
```

### **2. Chunk Management in GrassSystem**

```javascript
// New properties in GrassSystem constructor
this.useChunkedGrass = options.useChunkedGrass || false; // Default: false (backward compatible)
this.chunkSize = options.chunkSize || 50; // World units per chunk
this.loadRadius = options.loadRadius || 2; // Chunks to load around player (grid units)
this.unloadRadius = options.unloadRadius || 3; // Chunks to unload beyond this
this.chunks = new Map(); // Map<"x_z", GrassChunk>
this.chunkGroup = null; // THREE.Group to hold all chunks
```

### **3. Modified generateGrassField()**

- **Single Mesh Mode (default):** Works exactly as before
- **Chunked Mode:** Generates initial chunks around origin
- **New Method:** `generateGrassChunk(chunkX, chunkZ)` - generates grass for one chunk

---

## 🔧 **IMPLEMENTATION STEPS**

### **Step 1: Add Chunk Support to Constructor**
- Add chunk-related options (useChunkedGrass, chunkSize, loadRadius, unloadRadius)
- Initialize chunk storage (Map, Group)
- Keep default behavior as single mesh

### **Step 2: Create generateGrassChunk() Method**
- Extract chunk-specific logic from generateGrassField()
- Generate grass only within chunk bounds
- Return mesh with same shader/material as single mesh

### **Step 3: Modify setGroundType('grass')**
- Check if useChunkedGrass is enabled
- If chunked: Generate initial chunks around origin
- If single: Use existing generateGrassField() (no changes)

### **Step 4: Add updateChunks() Method**
- Calculate which chunks should be loaded based on camera position
- Load new chunks within loadRadius
- Unload chunks beyond unloadRadius
- Called from update() method when chunked mode is active

### **Step 5: Add Camera Position Tracking**
- Accept camera reference in update() method
- Calculate chunk grid position from camera
- Update chunks dynamically

### **Step 6: Frustum Culling**
- Enable frustumCulled = true on each chunk mesh
- Three.js handles this automatically
- Optional: Manual frustum check for chunk loading optimization

---

## 📊 **CHUNK GENERATION LOGIC**

### **Blade Distribution Per Chunk**

```javascript
generateGrassChunk(chunkX, chunkZ) {
  const chunkWorldX = chunkX * this.chunkSize;
  const chunkWorldZ = chunkZ * this.chunkSize;
  const chunkMinX = chunkWorldX - (this.chunkSize / 2);
  const chunkMaxX = chunkWorldX + (this.chunkSize / 2);
  const chunkMinZ = chunkWorldZ - (this.chunkSize / 2);
  const chunkMaxZ = chunkWorldZ + (this.chunkSize / 2);
  
  // Calculate blade count for this chunk
  const totalArea = this.options.planeSize * this.options.planeSize;
  const chunkArea = this.chunkSize * this.chunkSize;
  const bladeDensity = this.options.bladeCount / totalArea;
  const chunkBladeCount = Math.floor(chunkArea * bladeDensity);
  
  // Generate blades only within chunk bounds
  for (let i = 0; i < chunkBladeCount; i++) {
    const x = chunkMinX + Math.random() * this.chunkSize;
    const z = chunkMinZ + Math.random() * this.chunkSize;
    // ... generate blade at (x, z)
  }
}
```

---

## 🔄 **UPDATE LOOP INTEGRATION**

```javascript
update(delta, camera = null) {
  // Existing update logic (time, uniforms, etc.)
  
  // NEW: Update chunks if chunked mode is enabled
  if (this.useChunkedGrass && camera) {
    this.updateChunks(camera.position);
  }
}
```

---

## ✅ **TESTING CHECKLIST**

- [ ] Single mesh mode still works (backward compatibility)
- [ ] Chunked mode generates chunks correctly
- [ ] Chunks load/unload based on camera position
- [ ] Frustum culling works (chunks outside view not rendered)
- [ ] Performance improvement with large blade counts (5M+)
- [ ] No visual differences between single and chunked modes
- [ ] Memory usage is reasonable (chunks disposed properly)
- [ ] No memory leaks (chunks properly cleaned up)

---

## 🚨 **IMPORTANT NOTES**

1. **Backward Compatibility:** Default behavior MUST remain single mesh
2. **No Breaking Changes:** Existing levels must continue working
3. **Performance:** Chunking should improve performance for 5M+ blades
4. **Memory:** Chunks must be properly disposed when unloaded
5. **Visual Consistency:** Chunked grass should look identical to single mesh

---

## 📝 **NEXT STEPS AFTER PHASE 2**

- Phase 3: Procedural Grass Growth (requires chunking)
- LOD System: Distance-based level of detail
- Weight-Based Distribution: Density control via weight maps

---

**STATUS:** 🔄 **READY TO IMPLEMENT**

