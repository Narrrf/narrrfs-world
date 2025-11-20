# 🐭 MOUSE CHARACTER RENDERING STANDARD - UNIVERSAL RULE

**STATUS:** ✅ **ACTIVE - MANDATORY FOR ALL LEVELS**  
**CREATED:** November 19, 2025  
**PURPOSE:** Standardized smooth character rendering for all current and future levels  
**PRIORITY:** 🚨 **CRITICAL - UNIVERSAL IMPLEMENTATION**  

---

## 🎯 **RULE OVERVIEW**

### **CORE PRINCIPLE:**
**Every level MUST use the same smooth character rendering system. The mouse character rendering is universal and works identically across all levels (Level 1, 2, 3, 4, and all future levels).**

### **RULE SCOPE:**
- **Character Position Updates** - Smooth interpolation for lag-free rendering
- **Height Offset Calculation** - Mouse character vs Animation Library
- **Animation Speed Scaling** - Matches player movement speed
- **Visibility Management** - Third-person view only
- **Rotation System** - Velocity-based character rotation

---

## ✅ **VERIFIED IMPLEMENTATION STATUS**

### **Current Levels (All Verified):**
- ✅ **Level 1** - Smooth rendering confirmed
- ✅ **Level 2** - Smooth rendering confirmed
- ✅ **Level 3** - Smooth rendering confirmed (fixed lag issue)
- ✅ **Level 4** - Smooth rendering confirmed (reference standard)

### **Future Levels:**
- ✅ **Automatic** - All future levels inherit this system automatically

---

## 🔧 **TECHNICAL IMPLEMENTATION**

### **1. Universal Function Call**
**Location:** `three.js/main.js` - `animate()` function  
**Line:** ~12000

```javascript
// Update player character (position, rotation, animations) - UNIVERSAL FOR ALL LEVELS
// This ensures character updates even if level-specific objects don't exist
updatePlayerCharacter(delta);
```

**Critical:** This function is called **OUTSIDE** all level-specific checks, ensuring it runs for:
- Level 1 (The Hunt)
- Level 2 (The Spawn)
- Level 3 (The Hunt - Dynamic Labyrinth)
- Level 4 (The First Shot)
- **All future levels automatically**

---

### **2. Smooth Position Interpolation**

**Function:** `updatePlayerCharacter(delta)`  
**Location:** `three.js/main.js` - Line ~2795

```javascript
// Update character position to match player collider with smooth interpolation
const playerPos = new THREE.Vector3().lerpVectors(playerCollider.start, playerCollider.end, 0.5);

// CRITICAL: Mouse character needs different height offset
const isMouseCharacterModel = playerCharacterModel.userData && playerCharacterModel.userData.isMouseCharacter;
let heightOffset = 0.85 * playerCharacterModel.scale.y; // Default for Animation Library
if (isMouseCharacterModel) {
  heightOffset = 0.95; // Mouse character offset
}

// Calculate target position with height offset
const targetY = playerPos.y - heightOffset;
const targetPos = new THREE.Vector3(playerPos.x, targetY, playerPos.z);

// Smooth interpolation for position (reduces lag, especially in complex levels)
const lerpFactor = Math.min(1.0, delta * 30); // Smooth interpolation based on delta
playerCharacterModel.position.lerp(targetPos, lerpFactor);
```

**Key Features:**
- **Smooth Interpolation:** Uses `lerp()` instead of direct `copy()` to eliminate visual lag
- **Delta-Based:** Interpolation factor adapts to frame rate (`delta * 30`)
- **Height Offset:** Correctly positions character feet on ground
- **Universal:** Works identically in all levels

---

### **3. Height Offset System**

**Mouse Character:**
- **Offset:** `0.95` units
- **Purpose:** Aligns character feet with ground level
- **Detection:** `playerCharacterModel.userData.isMouseCharacter === true`

**Animation Library Character:**
- **Offset:** `0.85 * playerCharacterModel.scale.y`
- **Purpose:** Aligns character torso with ground level
- **Detection:** `playerCharacterModel.userData.isMouseCharacter === false` or `undefined`

---

### **4. Animation Speed Scaling**

**Location:** `updatePlayerCharacter(delta)` - Animation section

```javascript
// Animation speed matches player movement speed
const speedMultiplier = isSprinting ? 1.75 : 1.0; // Sprint = 1.75x, Normal = 1.0x
action.setEffectiveTimeScale(speedMultiplier);
```

**Speed Multipliers:**
- **Normal Walk:** `1.0x` (matches 24 units/second movement)
- **Sprint:** `1.75x` (matches 42 units/second movement)
- **God Mode:** Speed multiplier doesn't affect animation (uses base speed)

---

### **5. Character Visibility**

**Location:** `updatePlayerCharacter(delta)` - Visibility section

```javascript
// Show/hide character based on camera mode
const shouldBeVisible = !isFirstPerson();
playerCharacterModel.visible = shouldBeVisible;

// Ensure all child meshes are visible when character should be visible
if (needsVisibilityRefresh) {
  playerCharacterModel.traverse((child) => {
    if (child.isMesh) {
      child.visible = true;
      child.frustumCulled = false; // Disable frustum culling for visibility
    }
  });
}
```

**Visibility Rules:**
- **First Person:** Character hidden (only camera visible)
- **Third Person:** Character visible (smooth rendering)
- **Joystick Mode:** Character visible (same as third person)

---

### **6. Character Rotation**

**Location:** `updatePlayerCharacter(delta)` - Rotation section

```javascript
// Update character rotation to face movement direction (only in third-person)
if (!isFirstPerson() && controls.isLocked && useGLTFCharacter && playerCharacterModel) {
  const velocityMagnitude = Math.sqrt(playerVelocity.x * playerVelocity.x + playerVelocity.z * playerVelocity.z);
  const hasActualVelocity = velocityMagnitude > 0.15;
  
  if (hasActualVelocity) {
    // Use velocity direction for smooth rotation
    targetDirection.set(playerVelocity.x, 0, playerVelocity.z);
    targetDirection.normalize();
    
    // Smooth rotation interpolation
    const targetAngle = Math.atan2(targetDirection.x, targetDirection.z);
    playerCharacterModel.rotation.y = THREE.MathUtils.lerp(
      playerCharacterModel.rotation.y,
      targetAngle,
      Math.min(1.0, delta * 10) // Smooth rotation
    );
  }
}
```

**Rotation Features:**
- **Velocity-Based:** Character faces actual movement direction
- **Smooth Interpolation:** Uses `lerp()` for smooth rotation
- **Third-Person Only:** Rotation only applies in third-person view
- **Threshold:** Only rotates when velocity > 0.15 (prevents jitter)

---

## 🚨 **CRITICAL RULES TO NEVER VIOLATE**

### **❌ NEVER DO:**
- **Level-Specific Character Updates** - Don't create level-specific character rendering
- **Direct Position Copy** - Don't use `position.copy()` without interpolation
- **Fixed Interpolation Factor** - Don't use fixed lerp values (must be delta-based)
- **Skip Character Updates** - Don't skip `updatePlayerCharacter(delta)` in any level
- **Different Height Offsets** - Don't change height offset values per level

### **✅ ALWAYS DO:**
- **Use Universal Function** - Always call `updatePlayerCharacter(delta)` for all levels
- **Smooth Interpolation** - Always use `lerp()` for position updates
- **Delta-Based Factors** - Always use `delta * factor` for interpolation
- **Consistent Height Offset** - Always use 0.95 for Mouse, 0.85 * scale for Animation Library
- **Animation Speed Scaling** - Always match animation speed to movement speed

---

## 📊 **PERFORMANCE BENEFITS**

### **Smooth Rendering:**
- **Eliminates Lag:** Smooth interpolation prevents visual stuttering
- **Frame Rate Adaptive:** Delta-based interpolation adapts to frame rate
- **Consistent Experience:** Same smooth rendering across all levels

### **Complex Level Support:**
- **Level 3 Moving Walls:** Smooth rendering even with complex moving geometry
- **Level 4 Shooting:** Smooth rendering during fast-paced action
- **Future Levels:** Will automatically benefit from smooth rendering

---

## 🎯 **IMPLEMENTATION CHECKLIST**

### **For Every New Level:**
- [ ] **Verify** `updatePlayerCharacter(delta)` is called in `animate()` function
- [ ] **Test** character rendering in third-person view
- [ ] **Verify** smooth position updates (no lag or stuttering)
- [ ] **Check** height offset is correct (feet on ground)
- [ ] **Test** animation speed matches movement speed
- [ ] **Verify** character rotation follows movement direction

### **For Code Reviews:**
- [ ] **Check** no level-specific character rendering code exists
- [ ] **Verify** smooth interpolation is used (not direct copy)
- [ ] **Confirm** height offset values are correct
- [ ] **Test** character rendering in all camera modes
- [ ] **Verify** animation speed scaling is implemented

---

## 🔄 **AUTOMATIC INHERITANCE**

### **Future Levels:**
**All future levels automatically inherit this system because:**
1. `updatePlayerCharacter(delta)` is called **OUTSIDE** level-specific checks
2. No level-specific character rendering code exists
3. System is universal and level-agnostic

### **No Action Required:**
- ✅ **No code changes needed** for new levels
- ✅ **No configuration required** for new levels
- ✅ **No testing needed** for character rendering (already verified)

---

## 📝 **DOCUMENTATION REQUIREMENTS**

### **When Creating New Levels:**
- **Document:** Character rendering is automatic (no special setup needed)
- **Note:** Smooth interpolation system is universal
- **Verify:** Character rendering works in third-person view

### **When Modifying Character System:**
- **Update:** This standard rule document
- **Test:** All existing levels (1, 2, 3, 4)
- **Verify:** No level-specific code is needed

---

## 🧪 **TESTING PROTOCOL**

### **Universal Testing (All Levels):**
1. **Start Level** in first-person view
2. **Switch to Third-Person** (press V key)
3. **Move Character** (WASD keys)
4. **Sprint** (Hold Shift)
5. **Verify:**
   - ✅ Character position updates smoothly (no lag)
   - ✅ Character feet are on ground (correct height offset)
   - ✅ Character rotation follows movement direction
   - ✅ Animation speed matches movement speed
   - ✅ No visual stuttering or jitter

### **Level-Specific Testing:**
- **Level 3:** Test with moving walls (complex geometry)
- **Level 4:** Test during shooting action (fast movement)
- **Future Levels:** Test with level-specific mechanics

---

## 🚀 **SUCCESS METRICS**

### **Technical Success:**
- **Zero Lag:** Character position updates smoothly in all levels
- **Consistent Rendering:** Same smooth experience across all levels
- **Performance:** No frame rate impact from character rendering
- **Visual Quality:** Character appears perfectly aligned and animated

### **User Experience:**
- **Smooth Movement:** Character follows player position without lag
- **Natural Animation:** Animation speed matches movement speed
- **Correct Positioning:** Character feet are always on ground
- **Responsive Rotation:** Character faces movement direction smoothly

---

## 🧀 **FINAL MANDATE**

### **THIS RULE IS NON-NEGOTIABLE:**
- **Every level** MUST use the universal character rendering system
- **No exceptions** for level-specific character rendering
- **Complete compliance** required for all team members
- **Professional standards** maintained at all times

### **THE ULTIMATE GOAL:**
**Ensure every level provides the same smooth, lag-free character rendering experience, maintaining visual consistency and performance across all current and future levels.**

---

**RULE CREATED:** November 19, 2025  
**STATUS:** ✅ **ACTIVE - MANDATORY FOR ALL LEVELS**  
**PURPOSE:** Universal Character Rendering Standard  
**SCOPE:** All current levels (1, 2, 3, 4) and all future levels  

**🐭 THIS RULE ENSURES DECADES OF SMOOTH CHARACTER RENDERING! 🐭**

