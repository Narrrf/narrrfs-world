# 🌀 Level 1 Portal 3D Model Implementation - Working Pattern

**Date:** January 16, 2026  
**Status:** ✅ **WORKING - VERIFIED LOADING CORRECTLY + BLUE CHEESE WITH PULSING GLOW**  
**Purpose:** Document the working implementation pattern for Level 1 Portal and Blue Cheese GLB models

---

## ✅ **CONFIRMED WORKING PATTERN**

The portal 3D model is now loading correctly using this exact pattern. **DO NOT CHANGE** this pattern without testing.

**🚨 CRITICAL:** This is the **VERIFIED WORKING METHOD** for implementing GLB models from the data persistence file system (`/data/public/three.js/public/textures/`). Use this pattern for ALL future GLB model integrations that need to work with the Render deployment symlink system.

---

## 📋 **IMPLEMENTATION SUMMARY**

### **Portal Model Details:**
- **Model File:** `textures/3d models/cheese portal/cheese-portal.glb`
- **Block Coordinates:** (26, 3, 21)
- **World Position:** (26.5, 4.5, 21.5) - Y adjusted by +1 to prevent going underground (3x scale)
- **Scale:** 3.0x (3x larger for better visibility and interaction)
- **Collision:** ✅ Enabled (player cannot walk through portal)

---

## 🎯 **WORKING PATTERN (Level 4 Cheese Boss Style)**

### **Key Pattern Elements:**

#### **1. Path Resolution:**
```javascript
// Relative path (no leading slash) - same as Level 4 Cheese Bosses
const relativePath = "textures/3d models/cheese portal/cheese-portal.glb";

// Use resolveAssetPath() + encodeURI() for symlink support and space handling
const resolved = resolveAssetPath(relativePath);
const urlForLoader = encodeURI(resolved); // Handles spaces in "cheese portal" folder name
```

**Why This Works:**
- ✅ **Data Persistence File System:** Works with `/data/public/three.js/public/textures/` symlink on Render (same as Cheese Bosses)
- ✅ **Symlink Support:** `resolveAssetPath()` resolves relative paths through `/data/` symlink to `/var/www/html/public/three.js/public/`
- ✅ **Space Handling:** `encodeURI()` handles spaces in folder name (`cheese portal`) for proper URL encoding
- ✅ **Cross-Environment:** Works on both local development and production Render environments
- ✅ **Verified Pattern:** This is the **STANDARD METHOD** for loading persistent GLB assets from the data persistence file system

#### **2. Model Loading:**
```javascript
// Use loadModel() with GLTFLoader fallback (same pattern as Level 4 Cheese Bosses)
if (typeof loadModel === "function") {
  loadModel(urlForLoader)
    .then((result) => {
      const loadedScene = result.scene || result;
      const portal = loadedScene; // Use directly (no clone needed for single instance)
      // ... rest of processing
    })
    .catch((error) => {
      // GLTFLoader fallback here
    });
}
```

**Why This Works:**
- ✅ Uses same loader system as other GLB models (trees, Cheese Bosses)
- ✅ GLTFLoader fallback prevents silent failures
- ✅ Consistent with established patterns

#### **3. Position & Scale:**
```javascript
// Portal position: Block coordinates (26, 3, 21) = World (26.5, 4.5, 21.5)
const portalX = 26 * blockSize + blockSize / 2; // 26.5
const portalY = 3 * blockSize + blockSize / 2; // 3.5 (before adjustment)
const portalZ = 21 * blockSize + blockSize / 2; // 21.5

// Y position adjusted by +1 to prevent going underground (model is 3x larger now)
portal.position.set(portalX, portalY + 1, portalZ); // Final Y: 4.5

// Scale portal 3x larger for better visibility and interaction
portal.scale.setScalar(3.0);
```

**Why This Works:**
- ✅ Block coordinates converted to world coordinates correctly
- ✅ Y adjustment (-1) places model correctly on ground
- ✅ 3x scale makes portal visible and interactive

#### **4. Material Processing:**
```javascript
// Process materials to ensure proper rendering (same as trees and plants)
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

**Why This Works:**
- ✅ Same material processing as other GLB models (trees, plants)
- ✅ Ensures proper brightness and visibility
- ✅ Consistent with established patterns

#### **5. Block Filtering:**
```javascript
// Filter out portal location blocks BEFORE InstancedMesh creation
const isPortalLocation = (block.x === 26 && block.z === 21 && block.y === 3);
if (isPortalLocation) {
  console.log("🌀 [LEVEL 1] Skipping portal location block...");
  continue; // Skip this block - portal GLB model will replace it
}
```

**Why This Works:**
- ✅ Prevents original blocks from rendering at portal location
- ✅ Applied BEFORE InstancedMesh creation (critical timing)
- ✅ Also filtered from collision mesh (prevents collision conflicts)

---

## 🚧 **COLLISION DETECTION**

### **Portal Collision Integration:**

The portal is added to the `checkLevel1TreeCollision()` function's obstacles array:

```javascript
const obstacles = [
  // ... trees and plants ...
  { tree: level1State.portal, position: level1State.portalPosition, name: "Portal", type: "portal" }
];
```

**Collision Behavior:**
- ✅ **Push-Away System:** Player is pushed away from portal when colliding
- ✅ **Velocity Cancellation:** Velocity toward portal is canceled to prevent sliding through
- ✅ **Collision Radius:** Calculated from bounding box (same as trees)
- ✅ **Collision Data:** Stored in `portal.userData.collisionRadius` and `portal.userData.collisionPosition`

**Collision Detection:**
- ✅ Uses sphere-to-sphere collision (player capsule vs portal bounding box)
- ✅ Only checks horizontal distance (ignores Y axis)
- ✅ Works automatically via `checkLevel1TreeCollision()` function

---

## 📊 **CONFIGURATION VALUES**

### **Position:**
- **Block Coordinates:** (26, 3, 21)
- **World Position:** (26.5, 4.5, 21.5)
- **Y Adjustment:** +1.0 (raises model to prevent going underground with 3x scale)

### **Scale:**
- **Scale Factor:** 3.0 (3x larger than original model size)
- **Purpose:** Better visibility and easier interaction

### **Collision:**
- **Collision Radius:** Calculated from bounding box: `Math.max(size.x, size.z) * 0.5`
- **Collision Type:** Sphere-to-sphere (same as trees)
- **Player Cannot Walk Through:** ✅ Confirmed working

---

## ✅ **VERIFICATION CHECKLIST**

### **Model Loading:**
- ✅ Portal loads from correct path: `textures/3d models/cheese portal/cheese-portal.glb`
- ✅ Uses `resolveAssetPath()` + `encodeURI()` pattern
- ✅ Works with symlinks on Render
- ✅ GLTFLoader fallback available

### **Positioning:**
- ✅ Portal positioned at world coordinates (26.5, 4.5, 21.5)
- ✅ Y position adjusted by +1 (prevents going underground with 3x scale)
- ✅ Original blocks filtered out (no rendering conflicts)

### **Rendering:**
- ✅ Portal visible and renders correctly
- ✅ Materials processed correctly (proper brightness)
- ✅ Scale 3x larger (visible and interactive)
- ✅ Shadows enabled (castShadow, receiveShadow)

### **Collision:**
- ✅ Portal added to collision obstacles array
- ✅ Player cannot walk through portal
- ✅ Push-away system works correctly
- ✅ Velocity cancellation prevents sliding

### **Cleanup:**
- ✅ Portal cleanup added to `cleanupAllLevels()`
- ✅ Proper disposal of geometries and materials
- ✅ State reset on level change

---

## 🔧 **TECHNICAL DETAILS**

### **Function Location:**
- **File:** `public/three.js/main.js`
- **Function:** `createLevel1Portal(spawnData, blockSize)` - Lines 38273-38460
- **Called In:** `buildLevel()` after NPC creation (line 16891)

### **State Management:**
```javascript
const level1State = {
  // ... other properties ...
  portal: null, // Portal model reference
  portalPosition: null // Portal position for collision detection
};
```

### **Block Filtering:**
- **Location:** `buildLevel()` function (lines 16468-16498)
- **Filter:** Blocks at (x: 26, z: 21, y: 3) filtered out before InstancedMesh creation
- **Collision Filter:** Same blocks excluded from collision mesh generation

### **Collision Integration:**
- **Function:** `checkLevel1TreeCollision()` (line 37281)
- **Obstacles Array:** Portal added to obstacles array (line 37297)
- **Collision Type:** Sphere-to-sphere (same pattern as trees)

### **Cleanup:**
- **Function:** `cleanupAllLevels()` (line 25422)
- **Pattern:** Same cleanup as trees and plants (dispose geometries and materials)

---

## 📝 **KEY LESSONS LEARNED**

### **1. Path Resolution Pattern:**
- ✅ **ALWAYS use:** `relativePath` → `resolveAssetPath()` → `encodeURI()`
- ✅ **NEVER use:** Direct paths with `/public/` prefix (breaks symlink support)
- ✅ **ALWAYS encode:** Spaces in folder names (`encodeURI()`)

### **2. Block Filtering:**
- ✅ **CRITICAL:** Filter blocks BEFORE InstancedMesh creation
- ✅ **ALSO:** Filter from collision mesh to prevent conflicts
- ✅ **TIMING:** Filter during block grouping phase (before rendering)

### **3. Y Position Adjustment:**
- ✅ **Common Issue:** Models often float in air (need Y adjustment)
- ✅ **Solution:** Adjust Y by -1 (or appropriate value) to sit on ground
- ✅ **Test:** Always verify model sits correctly on ground after loading

### **4. Scale Configuration:**
- ✅ **Start with:** 1.0 (test if visible)
- ✅ **Adjust:** Increase scale if model is too small (3.0 for portal)
- ✅ **Purpose:** Make model visible and interactive

### **5. Collision Integration:**
- ✅ **Pattern:** Add to obstacles array in collision check function
- ✅ **Collision Radius:** Calculate from bounding box (same as trees)
- ✅ **Automatic:** Works via existing collision system (no special handling)

---

## 🚨 **CRITICAL REMINDERS**

### **DO NOT CHANGE:**
- ❌ Path resolution pattern (resolveAssetPath + encodeURI)
- ❌ Block filtering logic (must filter BEFORE InstancedMesh)
- ❌ Material processing pattern (processWeaponMaterial for all meshes)
- ❌ Collision integration (add to obstacles array)

### **ALWAYS VERIFY:**
- ✅ Portal loads correctly (check console logs)
- ✅ Original blocks removed (no rendering conflicts)
- ✅ Portal positioned correctly (doesn't go underground, not floating too high)
- ✅ Collision works (player cannot walk through)
- ✅ Cleanup works (portal removed on level change)

---

## 📚 **RELATED DOCUMENTATION**

### **Data Persistence File System:**
- **✅ VERIFIED WORKING METHOD:** This pattern is the standard for loading GLB models from the data persistence file system
- **File System:** `/data/public/three.js/public/textures/3d models/` (symlinked to `/var/www/html/public/three.js/public/textures/3d models/`)
- **Reference:** `12.0/YEAR_END_2025/TECHNICAL_COMPLETE_2025_MASTER_INDEX.md` → Game 7: 3D Hytopia Game

### **Pattern References:**
- **Level 4 Cheese Bosses:** Same path resolution and loading pattern (line 19787-19792) - Original working implementation
- **Level 1 Portal:** This implementation - Verified working pattern for data persistence file system
- **Level 1 Trees:** Same material processing pattern (line 37312)
- **Level 1 Plants:** Same collision integration pattern (line 37296)

### **Rule References:**
- **3D Model Rendering Rule:** `12.0/RULES/18_3D_MODEL_RENDERING_RULE.md` - Updated with this working pattern
- **Data Persistence Pattern:** Level 4 Cheese Bosses + Level 1 Portal (resolveAssetPath + encodeURI + loadModel)

### **Technical Documentation:**
- **Game 7 Technical Doc:** `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md` - Updated with data persistence file system pattern
- **Master Index:** `12.0/YEAR_END_2025/TECHNICAL_COMPLETE_2025_MASTER_INDEX.md` - Complete technical documentation reference

### **Plan Documents:**
- **Implementation Plan:** `LEVEL1_CENTER_TOWER_TO_PORTAL_REPLACEMENT_PLAN.md`
- **Daily Notes:** `DAILY_NOTES_2026-01-16.md`

---

## ✅ **STATUS**

**Implementation:** ✅ **COMPLETE - VERIFIED WORKING**  
**Portal Loading:** ✅ **CONFIRMED - LOADS CORRECTLY**  
**Position:** ✅ **CORRECT - SITS ON GROUND**  
**Scale:** ✅ **CORRECT - 3X LARGER**  
**Collision:** ✅ **WORKING - PLAYER CANNOT WALK THROUGH**  
**Block Filtering:** ✅ **WORKING - ORIGINAL BLOCKS REMOVED**

### **Additional Model Implementations Using This Pattern:**
- ✅ **Level 1 Blue Cheese:** Implemented at world coordinates (100, 5.5, 18) with 10.0x scale - **VERIFIED WORKING** (January 16, 2026)
  - Uses same `resolveAssetPath()` + `encodeURI()` + `loadModel()` pattern
  - Path: `textures/3d models/cheese blue/cheese-blue.glb`
  - Y position adjusted by +3 units for correct placement (final: 5.5)
  - **Pulsing Blue Glow Effect:** ✅ **WORKING PERFECTLY** - Smooth pulsing glow that transitions between original GLB color and blue/purple glow color (`0x4488ff`)
  - **Animation System:** `updateBlueCheeseGlow()` - Sine wave-based pulsing (intensity: 0.0 to 0.6) with color interpolation using `lerpColors()`
  - Collision detection integrated and working

---

**Created:** January 16, 2026  
**Status:** ✅ **WORKING PATTERN DOCUMENTED**  
**Purpose:** Preserve working implementation for future reference

---

**🧀 This pattern works perfectly - use it as reference for future 3D model integrations! 🧀**
