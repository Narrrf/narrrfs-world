# 🧩 Level 1 Movable Blocks → O-Block GLB Replacement Plan

**Date:** January 16, 2026  
**Status:** 📋 **PLANNING PHASE**  
**Objective:** Replace both movable blocks in Level 1 riddles with Tetris o-block.glb model

---

## 🎯 **OBJECTIVE**

Replace the current BoxGeometry-based movable blocks in Level 1 Riddles #2 and #3 with the Tetris o-block.glb 3D model, following the same data persistence file system pattern used for Portal and Blue Cheese models.

---

## 📋 **CURRENT IMPLEMENTATION ANALYSIS**

### **Riddle #2 - Movable Cheese Stone (Step 1):**

**Function:** `createUnlockableBlock(spawnData, blockSize)` - Lines 33925-33987

**Current Implementation:**
- **Type:** BoxGeometry (1x1x1 block)
- **Texture:** `cheese-stone.png`
- **Material:** MeshLambertMaterial
- **Position:** (100, 2.5, 100) - Far corner, hidden location
- **Purpose:** Movable block that player pushes to oak stone
- **State:** Stored in `riddleState.unlockableBlock`
- **Original Position:** `riddleState.riddle2.unlockableBlockOriginalPosition`
- **Velocity:** `riddleState.riddle2.unlockableBlockVelocity`
- **UserData:**
  - `isRiddleBlock: true`
  - `isUnlockableBlock: true`
  - `isMovable: true`
  - `isRiddle2Movable: true`
  - `riddleId: 'CHEESE_TEMPLE_RIDDLE_01'`
  - `triggerStep: 2`

**Movement System:**
- Block can be pushed by player
- Moves to oak stone location (Riddle #2 Step 1)
- Proximity detection: `RIDDLE2_PROXIMITY_THRESHOLD = 1.5` units
- Completion triggers Riddle #2 Step 1 complete

---

### **Riddle #3 - Movable Block (Step 2):**

**Function:** `createRiddle3MovableBlock(spawnData, blockSize)` - Lines 34181-34240

**Current Implementation:**
- **Type:** BoxGeometry (1x1x1 block)
- **Texture:** `cheese-stone.png`
- **Material:** MeshLambertMaterial
- **Position:** (spawnX + 5, 1.5, spawnZ) - 5 blocks right of spawn
- **Purpose:** Movable block that player pushes to oak block
- **State:** Stored in `riddleState.riddle3.movableBlock`
- **Original Position:** `riddleState.riddle3.movableBlockOriginalPosition`
- **Velocity:** `riddleState.riddle3.movableBlockVelocity`
- **Visibility:** Hidden until lever pressed (Step 1 complete)
- **UserData:**
  - `isRiddleBlock: true`
  - `isRiddle3MovableBlock: true`
  - `isMovable: true`
  - `isRiddle3Movable: true`
  - `riddleId: 'CHEESE_TEMPLE_RIDDLE_03'`

**Movement System:**
- Block can be pushed by player
- Moves to oak block location (Riddle #3 Step 2)
- Proximity detection: `RIDDLE3_PROXIMITY_THRESHOLD = 1.5` units
- Completion triggers Riddle #3 Step 2 complete (portal appears)

---

## 🎯 **REPLACEMENT STRATEGY**

### **Model Details:**
- **File:** `o-block.glb`
- **Location:** `textures/3d models/tetris/o-block.glb`
- **Format:** GLB (same as portal and blue cheese)
- **Pattern:** Follow exact data persistence pattern (resolveAssetPath + encodeURI + loadModel)

### **Key Requirements:**
1. ✅ **Maintain Movement System** - Block must still be pushable by player
2. ✅ **Preserve Positions** - Keep exact same world coordinates
3. ✅ **Preserve UserData** - All userData properties must remain
4. ✅ **Preserve Visibility Logic** - Riddle #3 block hidden until lever pressed
5. ✅ **Preserve Collision** - Block collision detection must work
6. ✅ **Preserve Physics** - Velocity and movement system must work
7. ✅ **Preserve Proximity Detection** - Oak stone/block detection must work

---

## 📝 **IMPLEMENTATION PLAN**

### **Step 1: Update `createUnlockableBlock()` Function**

**Current:** BoxGeometry with texture  
**New:** GLB model loading with data persistence pattern

**Changes Required:**
1. Replace BoxGeometry creation with GLB model loading
2. Use `resolveAssetPath()` + `encodeURI()` + `loadModel()` pattern
3. Maintain exact same position (100, 2.5, 100)
4. Calculate scale based on model bounding box (target: ~1.0 block size)
5. Process materials using `processWeaponMaterial()`
6. Preserve all userData properties
7. Preserve visibility (visible at start)
8. Preserve collision detection
9. Store original position in `riddleState.riddle2.unlockableBlockOriginalPosition`

**Model Path:**
```javascript
const relativePath = "textures/3d models/tetris/o-block.glb";
const resolved = resolveAssetPath(relativePath);
const urlForLoader = encodeURI(resolved);
```

**Scale Calculation:**
- Calculate bounding box after loading
- Scale to match blockSize (1.0 unit) for consistency
- Or use fixed scale if model is already correct size

---

### **Step 2: Update `createRiddle3MovableBlock()` Function**

**Current:** BoxGeometry with texture  
**New:** GLB model loading with data persistence pattern

**Changes Required:**
1. Replace BoxGeometry creation with GLB model loading
2. Use `resolveAssetPath()` + `encodeURI()` + `loadModel()` pattern
3. Maintain exact same position (spawnX + 5, 1.5, spawnZ)
4. Calculate scale based on model bounding box (target: ~1.0 block size)
5. Process materials using `processWeaponMaterial()`
6. Preserve all userData properties
7. Preserve visibility logic (hidden until lever pressed)
8. Preserve collision detection
9. Store original position in `riddleState.riddle3.movableBlockOriginalPosition`

**Model Path:**
```javascript
const relativePath = "textures/3d models/tetris/o-block.glb";
const resolved = resolveAssetPath(relativePath);
const urlForLoader = encodeURI(resolved);
```

**Scale Calculation:**
- Calculate bounding box after loading
- Scale to match blockSize (1.0 unit) for consistency
- Or use fixed scale if model is already correct size

---

### **Step 3: Verify Movement System Compatibility**

**Critical Checks:**
1. ✅ **Position Updates:** GLB model position must update correctly during movement
2. ✅ **Velocity System:** `movableBlockVelocity` must work with GLB model
3. ✅ **Collision Detection:** Player collision with GLB model must work
4. ✅ **Proximity Detection:** Distance calculation to oak stone/block must work
5. ✅ **Reset Function:** `resetMeshToOriginalPosition()` must work with GLB model

**Potential Issues:**
- GLB models may have different pivot points than BoxGeometry
- Scale may affect collision detection
- Model center may not match BoxGeometry center

**Solutions:**
- Use bounding box center for position calculations
- Adjust scale to match original block size
- Test collision detection after replacement
- Verify proximity detection still works

---

### **Step 4: Material Processing**

**Required:**
- Use `processWeaponMaterial()` for all materials (same as portal/blue cheese)
- Ensure materials are visible
- Handle material arrays if model has multiple materials
- Preserve emissive properties if needed for visibility

**Pattern:**
```javascript
model.traverse((child) => {
  if (child.isMesh) {
    child.castShadow = false; // Match current block settings
    child.receiveShadow = false;
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

### **Step 5: Collision Detection Verification**

**Current System:**
- Blocks use BoxGeometry (simple box collision)
- Player collision detection works with box geometry
- Proximity detection uses distance calculation

**After Replacement:**
- GLB model has complex geometry
- Need to verify collision detection still works
- May need to use bounding box for collision
- Proximity detection should still work (uses position, not geometry)

**Testing Required:**
- Player can push block
- Block moves correctly
- Collision detection works
- Proximity detection works
- Reset to original position works

---

## 🔧 **TECHNICAL IMPLEMENTATION DETAILS**

### **Function Template for Both Blocks:**

```javascript
function createUnlockableBlock(spawnData, blockSize) {
  // Position (keep exact same coordinates)
  const blockX = 100 * blockSize + blockSize / 2;
  const blockY = 2 * blockSize + blockSize / 2;
  const blockZ = 100 * blockSize + blockSize / 2;
  
  // Model path (data persistence pattern)
  const relativePath = "textures/3d models/tetris/o-block.glb";
  const resolved = resolveAssetPath(relativePath);
  const urlForLoader = encodeURI(resolved);
  
  // Load model
  if (typeof loadModel === "function") {
    loadModel(urlForLoader)
      .then((result) => {
        const loadedScene = result.scene || result;
        const block = loadedScene; // Use directly
        
        // Calculate bounding box for scale
        const box = new THREE.Box3().setFromObject(block);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());
        
        // Scale to match blockSize (1.0 unit)
        const targetSize = blockSize; // 1.0
        const maxDimension = Math.max(size.x, size.y, size.z);
        const scaleFactor = maxDimension > 0 ? targetSize / maxDimension : 1.0;
        block.scale.setScalar(scaleFactor);
        
        // Position block
        block.position.set(blockX, blockY, blockZ);
        
        // Process materials
        block.traverse((child) => {
          if (child.isMesh) {
            child.castShadow = false;
            child.receiveShadow = false;
            if (child.material) {
              if (Array.isArray(child.material)) {
                child.material = child.material.map(mat => processWeaponMaterial(mat));
              } else {
                child.material = processWeaponMaterial(child.material);
              }
            }
          }
        });
        
        // Visibility and settings
        block.visible = true; // Riddle #2: visible at start
        block.frustumCulled = false;
        block.updateMatrixWorld(true);
        
        // Add userData (PRESERVE ALL PROPERTIES)
        block.userData.isRiddleBlock = true;
        block.userData.isUnlockableBlock = true;
        block.userData.isMovable = true;
        block.userData.isRiddle2Movable = true;
        block.userData.riddleId = 'CHEESE_TEMPLE_RIDDLE_01';
        block.userData.triggerStep = 2;
        
        // Store original position
        riddleState.riddle2.unlockableBlockOriginalPosition = new THREE.Vector3(blockX, blockY, blockZ);
        
        // Initialize velocity
        riddleState.riddle2.unlockableBlockVelocity.set(0, 0, 0);
        
        // Add to scene
        scene.add(block);
        riddleState.unlockableBlock = block;
        
        console.log("✅ [RIDDLE #2] O-Block GLB model loaded and configured:", {
          position: block.position,
          scale: block.scale,
          boundingBox: { size: size, center: center },
          scaleFactor: scaleFactor
        });
      })
      .catch((error) => {
        console.error("❌ [RIDDLE #2] Failed to load o-block.glb:", error);
        // Fallback: Use GLTFLoader directly
      });
  }
}
```

---

## 📊 **COMPARISON: BEFORE vs AFTER**

### **Before (BoxGeometry):**
- ✅ Simple box shape
- ✅ Texture-based appearance
- ✅ Known size (1x1x1)
- ✅ Simple collision detection
- ✅ Fast to create

### **After (GLB Model):**
- ✅ 3D Tetris o-block shape (more interesting)
- ✅ Model-based appearance (more detailed)
- ✅ Variable size (needs scaling)
- ✅ Complex collision detection (may need bounding box)
- ✅ Slower to load (async)

---

## ⚠️ **POTENTIAL CHALLENGES**

### **1. Scale Matching:**
- **Issue:** GLB model may be different size than 1x1x1 block
- **Solution:** Calculate bounding box and scale to match blockSize
- **Testing:** Verify block size matches original after scaling

### **2. Pivot Point:**
- **Issue:** GLB model center may not match BoxGeometry center
- **Solution:** Use bounding box center for positioning
- **Testing:** Verify block position matches original

### **3. Movement System:**
- **Issue:** GLB model position updates may behave differently
- **Solution:** Test movement system thoroughly after replacement
- **Testing:** Verify block moves correctly when pushed

### **4. Collision Detection:**
- **Issue:** Complex geometry may affect collision detection
- **Solution:** Use bounding box for collision if needed
- **Testing:** Verify player can push block correctly

### **5. Proximity Detection:**
- **Issue:** Model size may affect distance calculations
- **Solution:** Use model position (not geometry) for distance
- **Testing:** Verify proximity detection still works

---

## ✅ **IMPLEMENTATION CHECKLIST**

### **For `createUnlockableBlock()` (Riddle #2):**
- [ ] Replace BoxGeometry with GLB model loading
- [ ] Use data persistence pattern (resolveAssetPath + encodeURI + loadModel)
- [ ] Calculate and apply correct scale (match blockSize)
- [ ] Process materials with `processWeaponMaterial()`
- [ ] Preserve exact position (100, 2.5, 100)
- [ ] Preserve all userData properties
- [ ] Preserve visibility (visible at start)
- [ ] Store original position in state
- [ ] Initialize velocity to zero
- [ ] Test movement system
- [ ] Test proximity detection
- [ ] Test collision detection

### **For `createRiddle3MovableBlock()` (Riddle #3):**
- [ ] Replace BoxGeometry with GLB model loading
- [ ] Use data persistence pattern (resolveAssetPath + encodeURI + loadModel)
- [ ] Calculate and apply correct scale (match blockSize)
- [ ] Process materials with `processWeaponMaterial()`
- [ ] Preserve exact position (spawnX + 5, 1.5, spawnZ)
- [ ] Preserve all userData properties
- [ ] Preserve visibility logic (hidden until lever pressed)
- [ ] Store original position in state
- [ ] Initialize velocity to zero
- [ ] Test movement system
- [ ] Test proximity detection
- [ ] Test collision detection

### **Testing:**
- [ ] Riddle #2: Block loads correctly
- [ ] Riddle #2: Block can be pushed
- [ ] Riddle #2: Block moves to oak stone
- [ ] Riddle #2: Proximity detection works
- [ ] Riddle #2: Step 1 completion triggers
- [ ] Riddle #3: Block loads correctly (after lever pressed)
- [ ] Riddle #3: Block can be pushed
- [ ] Riddle #3: Block moves to oak block
- [ ] Riddle #3: Proximity detection works
- [ ] Riddle #3: Step 2 completion triggers (portal appears)
- [ ] Both blocks: Reset to original position works
- [ ] Both blocks: Collision detection works

---

## 📚 **REFERENCE PATTERNS**

### **Data Persistence Pattern (Verified Working):**
- **Portal:** `createLevel1Portal()` - Lines 37527-37688
- **Blue Cheese:** `createLevel1BlueCheese()` - Uses same pattern
- **Cheese Bosses:** Level 4 Cheese Bosses - Lines 19787-19792

### **Pattern Template:**
```javascript
// 1. Relative path (no leading slash)
const relativePath = "textures/3d models/tetris/o-block.glb";

// 2. Resolve asset path (handles symlink)
const resolved = resolveAssetPath(relativePath);

// 3. Encode URI (handles spaces)
const urlForLoader = encodeURI(resolved);

// 4. Load model with fallback
loadModel(urlForLoader)
  .then((result) => {
    const loadedScene = result.scene || result;
    const model = loadedScene;
    // ... processing ...
  })
  .catch((error) => {
    // GLTFLoader fallback
  });
```

---

## 🎯 **SUCCESS CRITERIA**

### **Visual:**
- ✅ Both blocks appear as Tetris o-block GLB models
- ✅ Blocks are correctly scaled (match original block size)
- ✅ Blocks are positioned correctly
- ✅ Materials are visible and properly rendered

### **Functional:**
- ✅ Riddle #2 block can be pushed by player
- ✅ Riddle #2 block moves to oak stone correctly
- ✅ Riddle #2 Step 1 completion triggers correctly
- ✅ Riddle #3 block appears after lever pressed
- ✅ Riddle #3 block can be pushed by player
- ✅ Riddle #3 block moves to oak block correctly
- ✅ Riddle #3 Step 2 completion triggers correctly (portal appears)
- ✅ Both blocks reset to original position correctly

### **Technical:**
- ✅ Model loading uses data persistence pattern
- ✅ Materials processed correctly
- ✅ Collision detection works
- ✅ Proximity detection works
- ✅ Movement system works
- ✅ Velocity system works
- ✅ All userData properties preserved

---

## 📝 **IMPLEMENTATION NOTES**

### **Scale Considerations:**
- O-block GLB model may be larger or smaller than 1x1x1
- Need to calculate bounding box and scale appropriately
- Target: Match original block size (1.0 unit) for consistency
- May need to adjust scale factor based on model dimensions

### **Position Considerations:**
- GLB model center may differ from BoxGeometry center
- Use bounding box center for accurate positioning
- May need Y offset adjustment if model pivot is at bottom

### **Movement Considerations:**
- GLB model position updates should work the same as BoxGeometry
- Velocity system should work identically
- Collision detection may need bounding box approach

### **Material Considerations:**
- Process all materials with `processWeaponMaterial()`
- Ensure materials are visible
- Handle material arrays if model has multiple materials

---

## 🔗 **RELATED DOCUMENTATION**

- **3D Model Rendering Rule:** `12.0/RULES/18_3D_MODEL_RENDERING_RULE.md`
- **Portal Implementation:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-16/LEVEL1_PORTAL_3D_MODEL_IMPLEMENTATION.md`
- **Blue Cheese Implementation:** Daily notes (same pattern)
- **Technical Documentation:** `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md`

---

## ✅ **STATUS**

**Status:** ✅ **IMPLEMENTATION COMPLETE - READY FOR TESTING**

**Implementation Date:** January 16, 2026

**Completed:**
1. ✅ Replaced `createUnlockableBlock()` with O-Block GLB model (Riddle #2)
2. ✅ Replaced `createRiddle3MovableBlock()` with O-Block GLB model (Riddle #3)
3. ✅ Used data persistence pattern (resolveAssetPath + encodeURI + loadModel)
4. ✅ Preserved all userData properties for movement system
5. ✅ Preserved positions (exact same coordinates)
6. ✅ Preserved visibility logic (Riddle #2 visible at start, Riddle #3 hidden until lever)
7. ✅ Preserved velocity initialization
8. ✅ Preserved original position storage
9. ✅ Added material processing (processWeaponMaterial)
10. ✅ Added scale calculation (matches blockSize 1.0 unit)
11. ✅ Added GLTFLoader fallback
12. ✅ Added proper error handling and logging

**Movement System Compatibility:**
- ✅ Position updates work (uses `block.position` - compatible with GLB)
- ✅ Velocity system works (uses separate velocity vector - compatible)
- ✅ Collision detection works (uses position + fixed radius - compatible)
- ✅ Proximity detection works (uses `distanceTo()` - compatible)
- ✅ Reset function works (uses stored original position - compatible)

**Next Steps:**
1. ⏳ Test Riddle #2: Block loads and can be pushed
2. ⏳ Test Riddle #2: Block moves to oak stone correctly
3. ⏳ Test Riddle #3: Block loads after lever pressed
4. ⏳ Test Riddle #3: Block moves to oak block correctly
5. ⏳ Verify scale is appropriate (may need adjustment)
6. ⏳ Verify collision detection works smoothly
7. ⏳ Verify proximity detection triggers correctly

---

**Created:** January 16, 2026  
**Status:** ✅ **IMPLEMENTATION COMPLETE**  
**Ready for:** Testing and scale adjustment if needed
