# 🎭 PLAYER CHARACTER SETTINGS - CURRENT STATE ANALYSIS

**Document Created:** December 7, 2025  
**Last Updated:** December 7, 2025  
**Status:** 🔍 **INVESTIGATION COMPLETE - INCONSISTENCIES IDENTIFIED**  
**Issue:** Mouse character moving and rendering differently across 5 levels

---

## 🎯 USER REQUIREMENTS

1. ✅ **Consistent mouse character rendering** across all levels
2. ✅ **Consistent movement speed** across all levels
3. ✅ **Consistent animation frames/rendering** across all levels
4. ✅ **Only GOD mode should double speed** for faster development
5. ✅ **All settings should be in player-model.js**, not scattered in main.js per level

---

## 📊 CURRENT SETTINGS LOCATION & VALUES

### **1. MOVEMENT SPEED** ✅ (Consistent)

**Location:** `main.js` line ~20100-20104  
**Function:** Player velocity calculation

```javascript
// Normal player speed: 96 walk, 168 sprint (same as old god mode speed)
// God mode speed: 192 walk, 336 sprint (2x normal)
const baseSpeed = currentMovement.sprint ? 168 : 96; // Normal mode uses old god mode speed
const speed = godMode ? baseSpeed * 2 : baseSpeed; // God mode is 2x faster than normal
```

**Values:**
- **Normal Walk:** 96 units/sec
- **Normal Sprint:** 168 units/sec
- **God Mode Walk:** 192 units/sec (2x)
- **God Mode Sprint:** 336 units/sec (2x)

**Status:** ✅ **CONSISTENT** - Same for all levels  
**Problem:** ❌ **NOT IN player-model.js** - Should be in player-controls.js or player-model.js

---

### **2. POSITION LERP SPEED** ❌ (INCONSISTENT!)

**Location:** `main.js` line ~4514-4526  
**Function:** `updatePlayerCharacter()` - Character position interpolation

```javascript
let baseLerpSpeed = 180; // Much faster lerp to match new normal speed (96 vs 24) - prevents animation lag

// LEVEL 3 SPECIFIC: Use even faster lerp to prevent flickering with moving walls and fast movement
// Level 3 needs extra speed to keep up with moving walls and faster movement
if (currentLevel === LEVEL_IDS.LEVEL3) {
  baseLerpSpeed = 250; // Very fast lerp in Level 3 to match new speed and prevent flickering
}

// GOD MODE: Apply 2x lerp multiplier to ALL levels for consistency
if (godMode) {
  baseLerpSpeed *= 2.0; // 2x faster lerp in god mode to match 2x movement speed
}
```

**Values:**
- **Level 1, 2, 4, 5:** `baseLerpSpeed = 180`
- **Level 3:** `baseLerpSpeed = 250` ❌ **INCONSISTENT!**
- **God Mode (Levels 1,2,4,5):** `baseLerpSpeed = 360` (180 × 2)
- **God Mode (Level 3):** `baseLerpSpeed = 500` (250 × 2) ❌ **INCONSISTENT!**

**Status:** ❌ **INCONSISTENT** - Level 3 has different lerp speed  
**Problem:** ❌ **LEVEL-SPECIFIC OVERRIDE** - Causes different visual feel in Level 3  
**Impact:** Character position interpolation is faster in Level 3, making it feel different

---

### **3. ROTATION SPEED** ✅ (Consistent)

**Location:** `main.js` line ~4599-4604  
**Function:** `updatePlayerCharacter()` - Character rotation

```javascript
let baseRotationSpeed = 0.3; // Default rotation speed (smooth and round) - SAME FOR ALL LEVELS

// GOD MODE: Apply 1.5x rotation multiplier to ALL levels for consistency
if (godMode) {
  baseRotationSpeed *= 1.5; // 1.5x faster rotation in god mode to match movement speed
}
```

**Values:**
- **All Levels:** `baseRotationSpeed = 0.3`
- **God Mode (All Levels):** `baseRotationSpeed = 0.45` (0.3 × 1.5)

**Status:** ✅ **CONSISTENT** - Same for all levels  
**Problem:** ❌ **NOT IN player-model.js** - Should be in player-model.js

---

### **4. ANIMATION SPEED CALCULATION** ✅ (Consistent Calculation, but in main.js)

**Location:** `main.js` lines ~4907-4924 and ~4983-5001  
**Function:** `updatePlayerCharacter()` - Animation time scale

```javascript
const baseWalkSpeed = 96.0; // Base walk speed (units/sec) - synchronized across all levels
const intendedSpeed = isSprinting ? 168.0 : 96.0; // New normal speed (old god mode speed)
const godModeMultiplier = godMode ? 2.0 : 1.0; // God mode is 2x faster than new normal
speedForAnimation = intendedSpeed * godModeMultiplier;
animationSpeed = Math.max(0.5, Math.min(5.0, speedForAnimation / baseWalkSpeed)); // Clamp between 0.5x and 5.0x
```

**Values:**
- **Base Walk Speed:** 96.0 units/sec (for calculation)
- **Animation Speed Range:** 0.5x to 5.0x (clamped)
- **Calculation:** `speedForAnimation / baseWalkSpeed`
- **Normal Walk:** 1.0x animation speed (96 / 96)
- **Normal Sprint:** 1.75x animation speed (168 / 96)
- **God Mode Walk:** 2.0x animation speed (192 / 96)
- **God Mode Sprint:** 3.5x animation speed (336 / 96)

**Status:** ✅ **CONSISTENT** - Same calculation for all levels  
**Problem:** ❌ **NOT IN player-model.js** - Should be in player-model.js

---

### **5. ANIMATION DEBOUNCE DELAY** ✅ (Consistent)

**Location:** `main.js` line ~4797  
**Function:** `updatePlayerCharacter()` - Animation switching debounce

```javascript
// CRITICAL: Use longer debounce delay for run/idle switches to prevent flickering
// Increased from 100ms to 250ms for smoother animation in Level 3 (especially at low FPS)
const minSwitchDelay = 250; // Minimum 250ms between animation switches (4 switches/second max)
```

**Values:**
- **Min Switch Delay:** 250ms (all levels)
- **Idle Transitions:** Immediate (no debounce)

**Status:** ✅ **CONSISTENT** - Same for all levels  
**Problem:** ❌ **NOT IN player-model.js** - Should be in player-model.js

---

## 🚨 IDENTIFIED INCONSISTENCIES

### **1. Level 3 Lerp Speed Override** ❌ **CRITICAL**

**Location:** `main.js` line 4518-4520

```javascript
if (currentLevel === LEVEL_IDS.LEVEL3) {
  baseLerpSpeed = 250; // Very fast lerp in Level 3 to match new speed and prevent flickering
}
```

**Problem:**
- Level 3 uses `baseLerpSpeed = 250`
- All other levels use `baseLerpSpeed = 180`
- This causes character position interpolation to be faster in Level 3
- Creates inconsistent visual feel across levels

**Impact:**
- Character model position updates faster in Level 3
- May cause visual "lag" or "smoothing" differences
- Makes Level 3 feel different from other levels

**Fix Required:**
- Remove level-specific override
- Use consistent `baseLerpSpeed = 180` for all levels
- If Level 3 needs faster lerp, investigate root cause (moving walls, etc.)

---

### **2. Settings Scattered in main.js** ❌ **ARCHITECTURE ISSUE**

**Current State:**
- Movement speed: `main.js` line ~20100
- Lerp speed: `main.js` line ~4514
- Rotation speed: `main.js` line ~4599
- Animation speed: `main.js` line ~4907, ~4983
- Debounce delay: `main.js` line ~4797

**Problem:**
- All settings are in `main.js` `updatePlayerCharacter()` function
- Hard to maintain consistency
- Level-specific overrides possible (Level 3 example)
- Not in player-model.js where they should be

**Fix Required:**
- Move all settings to `player-model.js`
- Create centralized settings object
- Remove level-specific overrides

---

### **3. Animation Speed Calculation in main.js** ❌ **ARCHITECTURE ISSUE**

**Current State:**
- Animation speed calculation is in `updatePlayerCharacter()` function in `main.js`
- Logic is duplicated (lines ~4907 and ~4983)
- Not in player-model.js where animation logic should be

**Problem:**
- Animation logic should be in player-model.js
- Duplicated code (two places with same calculation)
- Hard to maintain

**Fix Required:**
- Move animation speed calculation to `player-model.js`
- Create method: `calculateAnimationSpeed(velocityMagnitude, isSprinting, godMode)`
- Call from main.js instead of calculating inline

---

## 📋 SETTINGS COMPARISON TABLE

| Setting | Level 1 | Level 2 | Level 3 | Level 4 | Level 5 | Status |
|---------|---------|---------|---------|---------|---------|--------|
| **Movement Speed (Walk)** | 96 | 96 | 96 | 96 | 96 | ✅ Consistent |
| **Movement Speed (Sprint)** | 168 | 168 | 168 | 168 | 168 | ✅ Consistent |
| **Lerp Speed** | 180 | 180 | **250** ❌ | 180 | 180 | ❌ **Level 3 Different** |
| **Rotation Speed** | 0.3 | 0.3 | 0.3 | 0.3 | 0.3 | ✅ Consistent |
| **Animation Speed Calc** | Same | Same | Same | Same | Same | ✅ Consistent |
| **Debounce Delay** | 250ms | 250ms | 250ms | 250ms | 250ms | ✅ Consistent |
| **God Mode Multiplier** | 2.0x | 2.0x | 2.0x | 2.0x | 2.0x | ✅ Consistent |

**Summary:** Only Level 3 has different lerp speed (250 vs 180) - this is the inconsistency!

---

## 🔧 PROPOSED FIXES

### **Fix 1: Remove Level 3 Lerp Speed Override** ✅

**Action:**
- Remove lines 4518-4520 from `main.js`
- Use consistent `baseLerpSpeed = 180` for all levels

**Code Change:**
```javascript
// BEFORE (main.js line ~4514):
let baseLerpSpeed = 180;
if (currentLevel === LEVEL_IDS.LEVEL3) {
  baseLerpSpeed = 250; // ❌ REMOVE THIS
}

// AFTER:
let baseLerpSpeed = 180; // ✅ Same for ALL levels
```

**Result:** All levels use consistent lerp speed (180)

---

### **Fix 2: Move Settings to player-model.js** ✅

**Action:**
- Create settings object in `player-model.js` constructor
- Move all constants to settings object
- Use settings from player-model.js in main.js

**Code Structure:**
```javascript
// In player-model.js constructor:
this.settings = {
  // Movement speeds (reference from player-controls.js or main.js)
  walkSpeed: 96.0,
  sprintSpeed: 168.0,
  godModeMultiplier: 2.0,
  
  // Position interpolation
  baseLerpSpeed: 180, // Same for ALL levels (no level-specific overrides)
  godModeLerpMultiplier: 2.0,
  
  // Rotation
  baseRotationSpeed: 0.3, // Same for ALL levels
  godModeRotationMultiplier: 1.5,
  
  // Animation
  baseWalkSpeed: 96.0, // For animation speed calculation
  animationSpeedMin: 0.5,
  animationSpeedMax: 5.0,
  
  // Animation switching
  minSwitchDelay: 250, // ms
  idleTransitionImmediate: true
};
```

---

### **Fix 3: Move Animation Speed Calculation to player-model.js** ✅

**Action:**
- Create method in `player-model.js`: `calculateAnimationSpeed()`
- Move calculation logic from main.js to player-model.js
- Call from main.js instead of calculating inline

**Code Structure:**
```javascript
// In player-model.js:
calculateAnimationSpeed(velocityMagnitude, isSprinting, godMode, hasMovementInput) {
  const baseWalkSpeed = this.settings.baseWalkSpeed;
  const intendedSpeed = isSprinting ? this.settings.sprintSpeed : this.settings.walkSpeed;
  const godModeMultiplier = godMode ? this.settings.godModeMultiplier : 1.0;
  
  let speedForAnimation = velocityMagnitude;
  if (velocityMagnitude < 0.1 && hasMovementInput) {
    // Player trying to move but velocity zeroed (collision)
    speedForAnimation = intendedSpeed * godModeMultiplier;
  }
  
  const animationSpeed = Math.max(
    this.settings.animationSpeedMin,
    Math.min(this.settings.animationSpeedMax, speedForAnimation / baseWalkSpeed)
  );
  
  return animationSpeed;
}
```

---

## 📝 FILES TO MODIFY

### **1. player-model.js:**
- Add settings object in constructor
- Add `calculateAnimationSpeed()` method
- Add getter methods for settings

### **2. main.js:**
- Remove Level 3 lerp speed override (line ~4518-4520)
- Use `playerModelModule.settings.baseLerpSpeed` instead of hardcoded 180
- Use `playerModelModule.settings.baseRotationSpeed` instead of hardcoded 0.3
- Use `playerModelModule.calculateAnimationSpeed()` instead of inline calculation
- Use `playerModelModule.settings.minSwitchDelay` instead of hardcoded 250

---

## 🎯 IMPLEMENTATION PLAN

### **Step 1: Add Settings to player-model.js** ✅
1. Add settings object in constructor
2. Add getter methods for settings
3. Add `calculateAnimationSpeed()` method

### **Step 2: Remove Level 3 Override** ✅
1. Remove lines 4518-4520 from main.js
2. Use consistent `baseLerpSpeed = 180` for all levels

### **Step 3: Update main.js to Use player-model.js Settings** ✅
1. Replace hardcoded values with `playerModelModule.settings.*`
2. Replace animation speed calculation with `playerModelModule.calculateAnimationSpeed()`
3. Remove duplicated animation speed calculation code

### **Step 4: Test All 5 Levels** ✅
1. Verify consistent movement speed
2. Verify consistent lerp speed (no Level 3 difference)
3. Verify consistent rotation speed
4. Verify consistent animation speed
5. Verify GOD mode only affects speed (2x multiplier)

---

## 🚨 CRITICAL FINDINGS

### **Level 3 Special Case:**
- **Line 4519:** `baseLerpSpeed = 250` (Level 3 only)
- **Reason:** "Very fast lerp in Level 3 to match new speed and prevent flickering"
- **Problem:** Creates inconsistency - Level 3 feels different
- **Solution:** Remove override, investigate root cause if needed

### **Settings Location:**
- **Current:** All in `main.js` `updatePlayerCharacter()` function
- **Problem:** Hard to maintain, level-specific overrides possible
- **Solution:** Move to `player-model.js` for centralized management

### **GOD Mode Implementation:**
- **Current:** GOD mode multiplies speed correctly (2x)
- **Status:** ✅ Working correctly
- **Note:** Only speed should change, not rendering/animation frames

---

## 📊 EXPECTED RESULTS AFTER FIXES

### **Before Fixes:**
- Level 3: Lerp speed 250 (different from others)
- Settings scattered in main.js
- Level-specific overrides possible

### **After Fixes:**
- All Levels: Lerp speed 180 (consistent)
- Settings in player-model.js (centralized)
- No level-specific overrides
- Consistent character feel across all levels

---

**DOCUMENT COMPLETED:** December 7, 2025  
**STATUS:** 🔍 **INVESTIGATION COMPLETE - INCONSISTENCIES IDENTIFIED**  
**IMPACT:** 🚨 **LEVEL 3 HAS DIFFERENT LERP SPEED - CAUSES INCONSISTENT FEEL**  
**NEXT:** 🔧 **IMPLEMENT FIXES TO REMOVE INCONSISTENCIES**

