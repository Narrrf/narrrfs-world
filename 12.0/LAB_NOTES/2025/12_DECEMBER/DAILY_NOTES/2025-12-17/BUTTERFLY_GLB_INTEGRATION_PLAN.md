# 🦋 BUTTERFLY GLB MODEL INTEGRATION PLAN - LEVEL 1

**Date:** December 17, 2025  
**Session:** Butterfly GLB Model Integration  
**Status:** 📋 **PLANNING PHASE**  
**Model:** `butterfly.glb`  
**Target Position:** (67, 2, 100)  
**Level:** Level 1  

---

## 🎯 **OBJECTIVE**

Add a beautiful butterfly GLB model to Level 1 that flies around the game field in an elegant, natural pattern. The butterfly should:
- ✅ Load from `/textures/3d models/Butterfly1/butterfly.glb`
- ✅ Be positioned at coordinates (67, 2, 100)
- ✅ Fly in a beautiful, natural pattern around the game field
- ✅ Be visible and animated throughout Level 1 gameplay
- ✅ Follow Level 1 entity creation patterns (similar to chests, trees)

---

## 📋 **INTEGRATION STEPS**

### **STEP 1: Create Butterfly Creation Function**

**Location:** `three.js/main.js` (after `createLevel1Tree4` function, around line 30326)

**Function Name:** `createLevel1Butterfly(spawnData, blockSize)`

**Pattern to Follow:**
- Similar structure to `createLevel1Chests()` or `createLevel1Tree()` functions
- Use GLTFLoader to load the butterfly model
- Position at exact coordinates (67, 2, 100)
- Set up animation mixer if model has animations
- Add to scene

**Key Requirements:**
- ✅ Load model using existing `gltfLoader` instance
- ✅ Handle model scaling appropriately
- ✅ Set up shadow casting/receiving
- ✅ Configure materials if needed
- ✅ Store butterfly reference globally for animation updates

---

### **STEP 2: Implement Flying Animation System**

**Location:** `three.js/main.js` (in animation loop, similar to `updateNPCMonster`)

**Function Name:** `updateLevel1Butterfly(delta)`

**Animation Pattern:**
- **Circular/Elliptical Path:** Butterfly flies in a large circle or figure-8 pattern
- **Vertical Variation:** Add gentle up/down movement (sine wave)
- **Smooth Rotation:** Butterfly faces direction of travel
- **Natural Speed:** Moderate flying speed (2-4 units/second)

**Animation Parameters:**
```javascript
// Butterfly flight parameters
let butterflyFlightRadius = 15; // Flight radius around center point
let butterflyCenterX = 67; // Center X position
let butterflyCenterY = 2; // Base Y position
let butterflyCenterZ = 100; // Center Z position
let butterflyVerticalAmplitude = 2; // Vertical movement range (±2 units)
let butterflyFlightSpeed = 0.5; // Rotation speed (radians per second)
let butterflyVerticalSpeed = 0.8; // Vertical oscillation speed
let butterflyAngle = 0; // Current angle in flight circle
```

**Animation Logic:**
1. Calculate circular path position (X, Z)
2. Add vertical sine wave variation (Y)
3. Calculate direction vector for rotation
4. Update butterfly position
5. Rotate butterfly to face movement direction
6. Update animation mixer if model has animations

---

### **STEP 3: Add Butterfly to Level 1 Initialization**

**Location:** `three.js/main.js` (in `buildLevel1` function, around line 13500)

**Code Pattern:**
```javascript
// Create butterfly in Level 1
if (mapData.spawn && !level1State.butterfly) {
  createLevel1Butterfly(mapData.spawn, blockSize);
}
```

**State Management:**
- Add `butterfly: null` to `level1State` object (around line 686)
- Set `level1State.butterfly = butterflyModel` after creation
- Clean up butterfly in `cleanupAllLevels()` function

---

### **STEP 4: Add Butterfly Update to Animation Loop**

**Location:** `three.js/main.js` (in main `animate()` function)

**Code Pattern:**
```javascript
// Update Level 1 butterfly animation
if (currentLevel === LEVEL_IDS.LEVEL1 && level1State.butterfly) {
  updateLevel1Butterfly(clock.getDelta());
}
```

**Requirements:**
- ✅ Only update when in Level 1
- ✅ Only update if butterfly exists
- ✅ Use `clock.getDelta()` for frame-independent animation
- ✅ Update animation mixer if model has animations

---

### **STEP 5: Cleanup on Level Change**

**Location:** `three.js/main.js` (in `cleanupAllLevels()` function)

**Code Pattern:**
```javascript
// Cleanup Level 1 butterfly
if (level1State.butterfly) {
  if (level1State.butterfly.parent) {
    level1State.butterfly.parent.remove(level1State.butterfly);
  }
  if (level1State.butterflyMixer) {
    level1State.butterflyMixer = null;
  }
  level1State.butterfly = null;
}
```

**Requirements:**
- ✅ Remove butterfly from scene
- ✅ Dispose animation mixer
- ✅ Clear state reference

---

## 🔧 **TECHNICAL IMPLEMENTATION DETAILS**

### **Model Loading Pattern:**
```javascript
const butterflyModelPath = "/textures/3d models/Butterfly1/butterfly.glb";
const gltf = await loadModel(butterflyModelPath);
const butterfly = gltf.scene.clone(true);
```

### **Positioning:**
```javascript
// Exact position as requested
butterfly.position.set(67, 2, 100);
```

### **Scaling:**
```javascript
// Calculate appropriate scale (similar to NPC monster scaling)
const butterflyBox = new THREE.Box3().setFromObject(butterfly);
const butterflySize = butterflyBox.getSize(new THREE.Vector3());
const targetSize = 0.5; // Butterfly should be small (0.5 units)
const scale = targetSize / Math.max(butterflySize.x, butterflySize.y, butterflySize.z);
butterfly.scale.set(scale, scale, scale);
```

### **Material Configuration:**
```javascript
butterfly.traverse((child) => {
  if (child.isMesh) {
    child.castShadow = true;
    child.receiveShadow = true;
    child.frustumCulled = false; // Keep visible even if off-screen
  }
});
```

### **Animation Setup:**
```javascript
// Set up animation mixer if model has animations
if (gltf.animations && gltf.animations.length > 0) {
  butterflyMixer = new THREE.AnimationMixer(butterfly);
  gltf.animations.forEach((clip) => {
    const action = butterflyMixer.clipAction(clip);
    action.play();
  });
}
```

---

## 🎨 **FLYING ANIMATION ALGORITHM**

### **Circular Path with Vertical Variation:**

```javascript
function updateLevel1Butterfly(delta) {
  if (!level1State.butterfly) return;
  
  // Update animation mixer
  if (level1State.butterflyMixer) {
    level1State.butterflyMixer.update(delta);
  }
  
  // Update flight angle
  butterflyAngle += butterflyFlightSpeed * delta;
  
  // Calculate circular path position
  const x = butterflyCenterX + Math.cos(butterflyAngle) * butterflyFlightRadius;
  const z = butterflyCenterZ + Math.sin(butterflyAngle) * butterflyFlightRadius;
  
  // Add vertical sine wave variation
  const y = butterflyCenterY + Math.sin(butterflyAngle * butterflyVerticalSpeed) * butterflyVerticalAmplitude;
  
  // Update position
  level1State.butterfly.position.set(x, y, z);
  
  // Calculate direction for rotation
  const direction = new THREE.Vector3(
    -Math.sin(butterflyAngle), // Forward direction X
    0, // Keep horizontal (no pitch)
    Math.cos(butterflyAngle)  // Forward direction Z
  );
  
  // Rotate butterfly to face movement direction
  if (direction.lengthSq() > 0.01) {
    const targetRotation = Math.atan2(direction.x, direction.z);
    level1State.butterfly.rotation.y = targetRotation;
  }
}
```

---

## 📊 **FILE MODIFICATIONS CHECKLIST**

### **Files to Modify:**

1. **`three.js/main.js`**
   - [ ] Add `butterfly: null` to `level1State` object (line ~686)
   - [ ] Add `butterflyMixer: null` to `level1State` object
   - [ ] Create `createLevel1Butterfly()` function (after `createLevel1Tree4`)
   - [ ] Create `updateLevel1Butterfly()` function (near `updateNPCMonster`)
   - [ ] Add butterfly creation call in `buildLevel1()` (line ~13500)
   - [ ] Add butterfly update call in `animate()` function
   - [ ] Add butterfly cleanup in `cleanupAllLevels()` function

### **Global Variables to Add:**

```javascript
// Butterfly flight parameters (add near top of file with other constants)
let butterflyFlightRadius = 15;
let butterflyCenterX = 67;
let butterflyCenterY = 2;
let butterflyCenterZ = 100;
let butterflyVerticalAmplitude = 2;
let butterflyFlightSpeed = 0.5;
let butterflyVerticalSpeed = 0.8;
let butterflyAngle = 0;
```

---

## ✅ **TESTING CHECKLIST**

### **Visual Testing:**
- [ ] Butterfly appears at position (67, 2, 100) when Level 1 loads
- [ ] Butterfly model loads correctly (no console errors)
- [ ] Butterfly is properly scaled (not too large/small)
- [ ] Butterfly casts/receives shadows correctly
- [ ] Butterfly is visible from various camera angles

### **Animation Testing:**
- [ ] Butterfly flies in circular/elliptical pattern
- [ ] Vertical movement is smooth and natural
- [ ] Butterfly rotates to face movement direction
- [ ] Animation speed is appropriate (not too fast/slow)
- [ ] Animation continues smoothly throughout Level 1

### **Performance Testing:**
- [ ] No performance impact from butterfly animation
- [ ] Butterfly doesn't cause frame rate drops
- [ ] Animation mixer updates correctly
- [ ] No memory leaks (butterfly cleans up on level change)

### **Integration Testing:**
- [ ] Butterfly appears only in Level 1
- [ ] Butterfly is removed when leaving Level 1
- [ ] Butterfly doesn't interfere with other Level 1 entities
- [ ] Butterfly doesn't collide with player or other objects

---

## 🚨 **CRITICAL REQUIREMENTS**

### **Code Preservation:**
- ✅ **NEVER delete or modify existing working code**
- ✅ **ONLY add new functions and code**
- ✅ **Follow existing Level 1 entity patterns exactly**
- ✅ **Maintain code consistency with existing codebase**

### **Performance:**
- ✅ Use efficient animation calculations
- ✅ Avoid expensive operations in animation loop
- ✅ Use frame-independent animation (delta time)
- ✅ Optimize model loading and setup

### **Error Handling:**
- ✅ Handle model loading failures gracefully
- ✅ Check for model existence before operations
- ✅ Log errors clearly for debugging
- ✅ Fallback behavior if model fails to load

---

## 📝 **IMPLEMENTATION NOTES**

### **Model Path:**
- **Full Path:** `/textures/3d models/Butterfly1/butterfly.glb`
- **Verified:** File exists at `c:\xampp-server\htdocs\narrrfs-world\three.js\public\textures\3d models\Butterfly1\butterfly.glb`

### **Position Details:**
- **X:** 67 (within Level 1 bounds: 0-120)
- **Y:** 2 (above ground level, allows for vertical movement)
- **Z:** 100 (within Level 1 bounds: 0-120)
- **Note:** Position is in world coordinates (not block coordinates)

### **Animation Considerations:**
- Butterfly should fly in a large enough pattern to be visible
- Flight radius should be 10-20 units for good visibility
- Vertical movement should be subtle (±1-3 units)
- Animation should be smooth and natural-looking

---

## 🎯 **SUCCESS CRITERIA**

### **Functional Requirements:**
- ✅ Butterfly loads and displays correctly in Level 1
- ✅ Butterfly flies in a beautiful, natural pattern
- ✅ Butterfly animation is smooth and continuous
- ✅ Butterfly is positioned at (67, 2, 100) initially
- ✅ Butterfly doesn't interfere with gameplay

### **Technical Requirements:**
- ✅ Code follows existing Level 1 entity patterns
- ✅ No performance impact
- ✅ Proper cleanup on level change
- ✅ Error handling for model loading
- ✅ Clean, maintainable code

### **Visual Requirements:**
- ✅ Butterfly is visible and recognizable
- ✅ Animation looks natural and beautiful
- ✅ Butterfly doesn't clip through terrain
- ✅ Shadows work correctly

---

## 🚀 **NEXT STEPS**

1. **Review this plan** - Ensure all requirements are clear
2. **Implement Step 1** - Create `createLevel1Butterfly()` function
3. **Implement Step 2** - Create `updateLevel1Butterfly()` function
4. **Implement Step 3** - Add butterfly to Level 1 initialization
5. **Implement Step 4** - Add butterfly update to animation loop
6. **Implement Step 5** - Add cleanup code
7. **Test thoroughly** - Verify all functionality works
8. **Update daily status** - Document completion

---

**Plan Created:** December 17, 2025  
**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Next Action:** Begin Step 1 - Create butterfly creation function  

**🦋 This plan ensures a beautiful, natural butterfly animation in Level 1! 🦋**
