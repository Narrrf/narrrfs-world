# 🌀 LEVEL 1 CENTER TOWER → PORTAL REPLACEMENT PLAN - January 16, 2026

**Date:** January 16, 2026  
**Status:** 📋 **PLAN CREATED - READY FOR IMPLEMENTATION**  
**Purpose:** Replace single-blocked center tower in Level 1 with Cheese Portal GLB model

---

## 🎯 **OVERVIEW**

Replace the center tower block (single-blocked tower in middle of Level 1) with the Cheese Portal GLB model (`cheese-portal.glb`), following the same rendering patterns used for trees, Cheese Bosses in Level 4, and other GLB models.

---

## 📋 **CURRENT STATE ANALYSIS**

### **Center Tower Location:**
- **Position:** Approximately `x: 60, z: 60` (center of 120x120 Level 1 map)
- **Block Type:** Single-blocked tower (one of the 5 inner towers: 4 corners + 1 center)
- **Current System:** Created via `InstancedMesh` from map JSON blocks
- **Collision:** Part of collision mesh (climbable)
- **Climbing System:** Referenced in comments: "All 5 inner towers (4 corners + 1 center) climbable"

### **Portal Model:**
- **File:** `C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\textures\3d models\cheese portal\cheese-portal.glb`
- **Path Format:** `/textures/3d models/cheese portal/cheese-portal.glb`
- **Type:** GLB model (same as trees, Cheese Bosses)
- **Location:** Same location as other 3D models (persistent `/data` on Render, symlinked)

---

## 🏗️ **IMPLEMENTATION PLAN**

### **STEP 1: Identify Center Tower Block in Map**

**Action:** Filter out center tower block during `buildLevel()` block processing

**Location:** `buildLevel()` function (around line 16468-16498)

**Method:**
```javascript
// Filter out center tower block (x: 60, z: 60, y: any height)
// Center is at block coordinates: x: 60, z: 60 (center of 120x120 map)
// Block center position = block.x * blockSize + blockSize / 2 = 60 * 1 + 0.5 = 60.5
// But we filter by block coordinates (60, 60) in the loop
```

**Code Location:**
- Filter in `list.forEach((block, index) => { ... })` loop (line 16498)
- Before adding block to `InstancedMesh`

**Filter Logic:**
```javascript
// Filter out center tower block (x: 60, z: 60)
// Check if block is at center tower position
const isCenterTower = (block.x === 60 && block.z === 60 && block.y > 0);
if (isCenterTower) {
  console.log("🌀 [LEVEL 1] Skipping center tower block - will replace with portal:", {
    x: block.x,
    y: block.y,
    z: block.z
  });
  return; // Skip this block - portal will replace it
}
```

---

### **STEP 2: Remove Center Tower from Collision Mesh**

**Action:** Exclude center tower block from collision mesh generation

**Location:** Same `buildLevel()` function, collision mesh generation section

**Method:**
- Filter out center tower block when building collision mesh
- Same filter condition: `block.x === 60 && block.z === 60 && block.y > 0`

**Code Location:**
- Collision positions generation (around line 16469-16486)

**Filter Logic:**
```javascript
// Exclude center tower from collision mesh
if (collisionPositions) {
  // Check if this is center tower before adding to collision
  const isCenterTower = (block.x === 60 && block.z === 60 && block.y > 0);
  if (isCenterTower) {
    // Skip collision positions for center tower
    return; // Skip collision generation for this block
  }
  // ... rest of collision generation code
}
```

---

### **STEP 3: Create Portal Loading Function**

**Action:** Create `createLevel1Portal()` function following same pattern as trees and Cheese Bosses

**Pattern Reference:**
- **Trees:** `createLevel1Tree()` (line 37312)
- **Cheese Bosses:** `createLevel4CheeseBosses()` (line 19732)

**Function Name:** `createLevel1Portal(spawnData, blockSize)`

**Location:** After tree creation functions (around line 37454, after `createLevel1Tree4()`)

**Key Requirements:**
1. **Use `loadModel()`** - Same loader as trees and Cheese Bosses
2. **Use `resolveAssetPath()`** - For correct path resolution
3. **Use `encodeURI()`** - Handle spaces in path (`cheese portal/cheese-portal.glb`)
4. **Position:** Center of Level 1 (`x: 60, z: 60`)
5. **Y Position:** Ground level or appropriate height (calculate based on portal model size)
6. **Material Processing:** Use `processWeaponMaterial()` for all meshes
7. **Scale:** Calculate appropriate scale based on model size
8. **Visibility:** Set `visible = true`, `frustumCulled = false`
9. **Shadow:** Set `castShadow = true`, `receiveShadow = true`
10. **State Management:** Store in `level1State.portal`

---

### **STEP 4: Position Calculation**

**Center Position:**
```javascript
// Center of Level 1: x: 60, z: 60 (center of 120x120 map)
const portalX = 60; // Center X
const portalZ = 60; // Center Z

// Y Position: Ground level (same as trees)
// Ground blocks are at y: 0, block top is at y: 1.0
const floorTopY = 0 * blockSize + blockSize; // = 1.0
const portalY = floorTopY; // Ground level (1.0)

// OR: Calculate based on portal model bounding box
// If portal has a base at origin, use: portalY = floorTopY
// If portal center is at middle, use: portalY = floorTopY - (size.y / 2)
```

**Scale Calculation:**
```javascript
// Calculate bounding box to determine scale
const box = new THREE.Box3().setFromObject(portal);
const size = box.getSize(new THREE.Vector3());

// Scale to appropriate size (start with 1.0, adjust if needed)
portal.scale.setScalar(1.0);

// OR: Calculate scale based on desired size (e.g., 3-5 units tall)
const targetHeight = 4.0; // Desired portal height
const scaleFactor = targetHeight / size.y;
portal.scale.setScalar(scaleFactor);
```

---

### **STEP 5: Material Processing**

**Pattern:** Same as trees and Cheese Bosses

**Code:**
```javascript
portal.traverse((child) => {
  if (child.isMesh) {
    child.castShadow = true;
    child.receiveShadow = true;
    if (child.material) {
      // Process material similar to other models
      if (Array.isArray(child.material)) {
        child.material = child.material.map(mat => processWeaponMaterial(mat));
      } else {
        child.material = processWeaponMaterial(child.material);
      }
    }
  }
});
```

---

### **STEP 6: State Management**

**Add to level1State:**
```javascript
const level1State = {
  // ... existing properties ...
  portal: null, // Portal model reference
  portalPosition: null // Portal position (for collision, if needed)
};
```

**Store in Function:**
```javascript
level1State.portal = portal;
level1State.portalPosition = new THREE.Vector3(portalX, portalY, portalZ);
```

---

### **STEP 7: Call Portal Creation Function**

**Location:** In `buildLevel()` after other Level 1 decorations (around line 16744, after tree creation)

**Code:**
```javascript
// Create portal in Level 1 center (replaces center tower)
if (mapData.spawn && !level1State.portal) {
  createLevel1Portal(mapData.spawn, blockSize);
}
```

---

### **STEP 8: Cleanup on Level Change**

**Location:** `cleanupAllLevels()` function (around line 25235-25309)

**Code:**
```javascript
// Remove portal from scene
if (level1State.portal && level1State.portal.parent) {
  scene.remove(level1State.portal);
  level1State.portal.traverse((child) => {
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
}
level1State.portal = null;
level1State.portalPosition = null;
```

---

### **STEP 9: Collision Detection (Optional)**

**Action:** Decide if portal should have collision (likely yes, like trees)

**Pattern:** Similar to `checkLevel1TreeCollision()`

**Decision:**
- **Option 1:** Portal has collision (player can't walk through it)
- **Option 2:** Portal is walkable (player can walk through it)

**Recommendation:** Portal should have collision (solid object like trees)

**Implementation:** Add portal to collision check if needed (similar to trees)

---

## 📝 **CODE STRUCTURE**

### **Function Signature:**
```javascript
// Create portal in Level 1 center (replaces center tower)
// Position: Center of Level 1 (x: 60, z: 60)
function createLevel1Portal(spawnData, blockSize) {
  // ... implementation
}
```

### **Function Body:**
```javascript
function createLevel1Portal(spawnData, blockSize) {
  // 1. Calculate position (center: x: 60, z: 60, y: 1.0)
  const portalX = 60; // Center X
  const portalZ = 60; // Center Z
  const floorTopY = 0 * blockSize + blockSize; // 1.0
  const portalY = floorTopY; // Ground level
  
  level1State.portalPosition = new THREE.Vector3(portalX, portalY, portalZ);
  
  console.log("🌀 [LEVEL 1] Creating portal at:", level1State.portalPosition);
  
  // 2. Load portal model (GLB format)
  const portalPath = "/textures/3d models/cheese portal/cheese-portal.glb";
  
  // 3. Use resolveAssetPath() and encodeURI() for path
  const resolved = resolveAssetPath(portalPath);
  const urlForLoader = encodeURI(resolved); // Handle spaces in path
  
  // 4. Load model using loadModel() (same as trees and Cheese Bosses)
  loadModel(portalPath)
    .then((gltf) => {
      const portal = gltf.scene; // Use directly (or clone if needed)
      
      // 5. Calculate bounding box for positioning and scaling
      const box = new THREE.Box3().setFromObject(portal);
      const size = box.getSize(new THREE.Vector3());
      const center = box.getCenter(new THREE.Vector3());
      
      console.log("🌀 [LEVEL 1] Portal model loaded:", {
        size: size,
        center: center,
        children: portal.children.length
      });
      
      // 6. Position portal
      portal.position.set(portalX, portalY, portalZ);
      
      // Adjust Y if model center is not at base
      if (size.y > 0) {
        portal.position.y = portalY; // Or adjust based on model structure
      }
      
      // 7. Scale portal appropriately
      portal.scale.setScalar(1.0); // Start with 1.0, adjust if needed
      
      // OR: Calculate scale based on desired size
      // const targetHeight = 4.0;
      // const scaleFactor = targetHeight / size.y;
      // portal.scale.setScalar(scaleFactor);
      
      // 8. Rotation (optional - adjust based on model orientation)
      portal.rotation.y = 0; // Adjust if needed
      
      // 9. Process materials to ensure proper rendering
      portal.traverse((child) => {
        if (child.isMesh) {
          child.castShadow = true;
          child.receiveShadow = true;
          if (child.material) {
            if (Array.isArray(child.material)) {
              child.material = child.material.map(mat => processWeaponMaterial(mat));
            } else {
              child.material = processWeaponMaterial(child.material);
            }
          }
        }
      });
      
      // 10. Ensure portal is visible
      portal.visible = true;
      portal.frustumCulled = false; // Ensure it's always rendered
      portal.updateMatrixWorld(true);
      
      // 11. Add to scene
      scene.add(portal);
      level1State.portal = portal;
      
      // 12. Store collision data if needed
      portal.userData.collisionRadius = Math.max(size.x, size.z) * 0.5;
      portal.userData.collisionPosition = level1State.portalPosition;
      
      // 13. Log success
      console.log("✅ [LEVEL 1] Portal created successfully:", {
        position: portal.position,
        scale: portal.scale,
        visible: portal.visible,
        inScene: scene.children.includes(portal),
        boundingBox: { size: size, center: center },
        collisionRadius: portal.userData.collisionRadius,
        children: portal.children.length
      });
      
      // 14. Verify visibility after delay
      setTimeout(() => {
        if (level1State.portal) {
          level1State.portal.visible = true;
          level1State.portal.updateMatrixWorld(true);
          console.log("🌀 [LEVEL 1] Portal visibility verified:", {
            visible: level1State.portal.visible,
            inScene: scene.children.includes(level1State.portal),
            position: level1State.portal.position
          });
        }
      }, 100);
    })
    .catch((error) => {
      console.error("❌ [LEVEL 1] Failed to load portal model:", error);
      console.error("❌ [LEVEL 1] Portal path attempted:", portalPath);
      console.error("❌ [LEVEL 1] Full error:", error.message, error.stack);
    });
}
```

---

## 🎯 **IMPLEMENTATION CHECKLIST**

### **Before Implementation:**
- [ ] Verify center tower position in map JSON (confirm x: 60, z: 60)
- [ ] Check if portal model exists at path
- [ ] Review Level 4 Cheese Boss implementation for pattern reference
- [ ] Review Level 1 tree implementation for pattern reference

### **During Implementation:**
- [ ] **STEP 1:** Filter center tower block from InstancedMesh creation
- [ ] **STEP 2:** Exclude center tower from collision mesh
- [ ] **STEP 3:** Create `createLevel1Portal()` function
- [ ] **STEP 4:** Calculate portal position (center: x: 60, z: 60)
- [ ] **STEP 5:** Process materials using `processWeaponMaterial()`
- [ ] **STEP 6:** Add portal to `level1State`
- [ ] **STEP 7:** Call portal creation in `buildLevel()`
- [ ] **STEP 8:** Add cleanup in `cleanupAllLevels()`
- [ ] **STEP 9:** Test portal visibility and positioning
- [ ] **STEP 10:** Adjust scale if needed (test different sizes)

### **After Implementation:**
- [ ] Test portal loads correctly
- [ ] Verify center tower block is removed
- [ ] Verify portal is positioned at center
- [ ] Test portal visibility (no clipping, proper rendering)
- [ ] Test portal materials (proper brightness, textures)
- [ ] Test collision if portal should be solid
- [ ] Verify cleanup works on level change
- [ ] Test portal persists across level warps

---

## 🔍 **TECHNICAL DETAILS**

### **Path Resolution:**
```javascript
// Use resolveAssetPath() for correct path resolution
const portalPath = "/textures/3d models/cheese portal/cheese-portal.glb";
const resolved = resolveAssetPath(portalPath);
const urlForLoader = encodeURI(resolved); // Handle spaces in path
```

### **Model Loading:**
```javascript
// Use loadModel() (same as trees and Cheese Bosses)
loadModel(portalPath)
  .then((gltf) => {
    const portal = gltf.scene; // Use directly (no clone needed for single instance)
    // ... rest of processing
  });
```

### **Material Processing:**
```javascript
// Process materials using processWeaponMaterial() (same as trees)
portal.traverse((child) => {
  if (child.isMesh) {
    child.castShadow = true;
    child.receiveShadow = true;
    if (child.material) {
      if (Array.isArray(child.material)) {
        child.material = child.material.map(mat => processWeaponMaterial(mat));
      } else {
        child.material = processWeaponMaterial(child.material);
      }
    }
  }
});
```

---

## 🚨 **CRITICAL CONSIDERATIONS**

### **1. Center Tower Block Identification:**
- **Must identify correctly:** Block at `x: 60, z: 60, y: > 0`
- **Filter before InstancedMesh creation:** Prevent block from rendering
- **Filter from collision mesh:** Prevent block from being climbable

### **2. Path Handling:**
- **Use `resolveAssetPath()`:** For correct path resolution (local vs production)
- **Use `encodeURI()`:** Handle spaces in path (`cheese portal/cheese-portal.glb`)
- **Follow Level 4 Cheese Boss pattern:** Same path resolution system

### **3. Model Loading:**
- **Use `loadModel()`:** Same loader as trees and Cheese Bosses
- **Handle async loading:** Use `.then()` and `.catch()`
- **Error handling:** Log errors if model fails to load

### **4. Positioning:**
- **Center position:** `x: 60, z: 60` (center of 120x120 map)
- **Y position:** Ground level (`y: 1.0`) or calculate based on model size
- **Scale:** Adjust based on model size and desired appearance

### **5. State Management:**
- **Store in level1State:** `level1State.portal` and `level1State.portalPosition`
- **Cleanup on level change:** Remove from scene and dispose resources
- **Prevent duplicates:** Check `!level1State.portal` before creating

### **6. Collision (Optional):**
- **Decide if portal is solid:** Should player collide with portal?
- **If yes:** Add to collision check (similar to trees)
- **If no:** Portal is walkable (player can pass through)

---

## 📊 **EXPECTED RESULTS**

### **Before Implementation:**
- ✅ Center tower block visible at `x: 60, z: 60`
- ✅ Center tower is climbable (part of collision mesh)
- ✅ 5 inner towers total (4 corners + 1 center)

### **After Implementation:**
- ✅ Center tower block removed from rendering
- ✅ Portal GLB model visible at `x: 60, z: 60`
- ✅ Portal positioned correctly (ground level or appropriate height)
- ✅ Portal scaled appropriately (visible size)
- ✅ Portal materials processed correctly (visible, no dark materials)
- ✅ Portal persists across level warps
- ✅ Portal cleaned up properly on level change

---

## 🔗 **RELATED PATTERNS**

### **Tree Loading Pattern (Level 1):**
- **Function:** `createLevel1Tree()` (line 37312)
- **Path:** `/textures/3d models/tree-with-arms/tree-with-arms.glb`
- **Position:** Relative to spawn
- **Scale:** `9.0` (Tree 1), `11.5` (Tree 2), `5.5` (Tree 3), `6.5` (Tree 4)
- **Material:** `processWeaponMaterial()`
- **State:** Stored in `level1State.tree`, `level1State.treePosition`

### **Cheese Boss Loading Pattern (Level 4):**
- **Function:** `createLevel4CheeseBosses()` (line 19732)
- **Path:** Various boss models in `/textures/3d models/`
- **Path Resolution:** `resolveAssetPath()` + `encodeURI()`
- **Position:** Corner positions with `bossYOffset = 7.0`
- **Scale:** `8.0` (all bosses)
- **Material:** `processWeaponMaterial()`
- **State:** Stored in `level4State.cheeseBosses[bossKey]`

---

## ✅ **SUCCESS CRITERIA**

1. ✅ **Center tower block removed** from InstancedMesh rendering
2. ✅ **Center tower excluded** from collision mesh
3. ✅ **Portal GLB model loaded** at center position (`x: 60, z: 60`)
4. ✅ **Portal positioned correctly** (ground level or appropriate height)
5. ✅ **Portal scaled appropriately** (visible, not too small/large)
6. ✅ **Portal materials processed** (visible, proper brightness)
7. ✅ **Portal persists** across level warps
8. ✅ **Portal cleaned up** properly on level change
9. ✅ **No console errors** during portal loading
10. ✅ **Portal follows same pattern** as trees and Cheese Bosses

---

## 📚 **FILES TO MODIFY**

1. **`public/three.js/main.js`**
   - **Function:** `buildLevel()` (filter center tower block)
   - **Function:** `createLevel1Portal()` (new function)
   - **Function:** `cleanupAllLevels()` (cleanup portal)
   - **State:** Add `portal` and `portalPosition` to `level1State`

---

## 🎯 **NEXT STEPS**

1. **Verify center tower position** in map JSON or code
2. **Implement STEP 1-2:** Filter center tower block
3. **Implement STEP 3-6:** Create portal loading function
4. **Implement STEP 7:** Call portal creation in `buildLevel()`
5. **Implement STEP 8:** Add cleanup code
6. **Test locally:** Verify portal loads and positions correctly
7. **Adjust scale:** Fine-tune portal size if needed
8. **Test collision:** Add collision if portal should be solid

---

**Created:** January 16, 2026  
**Status:** 📋 **PLAN CREATED - READY FOR IMPLEMENTATION**  
**Pattern Reference:** Level 1 Trees, Level 4 Cheese Bosses

---

**🧀 Following same rendering patterns ensures consistency and reliability! 🧀**
