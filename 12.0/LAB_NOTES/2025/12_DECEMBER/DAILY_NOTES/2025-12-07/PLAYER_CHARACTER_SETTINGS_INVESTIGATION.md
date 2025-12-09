# 🔍 PLAYER CHARACTER SETTINGS INVESTIGATION - December 7, 2025

**Date:** December 7, 2025  
**Time:** Current Session  
**Status:** 🔍 **INVESTIGATION COMPLETE - INCONSISTENCIES FOUND**  
**Issue:** Mouse character moving and rendering differently across 5 levels

---

## 🎯 PROBLEM STATEMENT

The mouse character is moving and rendering differently across the 5 levels. The user wants:
1. ✅ **Consistent mouse character rendering** across all levels
2. ✅ **Consistent movement speed** across all levels
3. ✅ **Consistent animation frames/rendering** across all levels
4. ✅ **Only GOD mode should double speed** for faster development
5. ✅ **All settings should be in player-model.js**, not scattered in main.js per level

---

## 📊 CURRENT SETTINGS ANALYSIS

### **1. MOVEMENT SPEED** (Defined in `main.js`)

**Location:** `main.js` lines ~20100-20104

```javascript
// Normal player speed: 96 walk, 168 sprint (same as old god mode speed)
// God mode speed: 192 walk, 336 sprint (2x normal)
const baseSpeed = currentMovement.sprint ? 168 : 96; // Normal mode uses old god mode speed
const speed = godMode ? baseSpeed * 2 : baseSpeed; // God mode is 2x faster than normal
```

**Status:** ✅ **CONSISTENT** - Same for all levels (96/168 normal, 192/336 god mode)

**Problem:** ❌ **NOT IN player-model.js** - Should be moved to player-controls.js or player-model.js

---

### **2. ANIMATION SPEED** (Defined in `main.js`)

**Location:** `main.js` lines ~4907-4924 and ~4983-5001 (in `updatePlayerCharacter()`)

```javascript
const baseWalkSpeed = 96.0; // Base walk speed (units/sec) - synchronized across all levels
const intendedSpeed = isSprinting ? 168.0 : 96.0; // New normal speed (old god mode speed)
const godModeMultiplier = godMode ? 2.0 : 1.0; // God mode is 2x faster than new normal
speedForAnimation = intendedSpeed * godModeMultiplier;
animationSpeed = Math.max(0.5, Math.min(5.0, speedForAnimation / baseWalkSpeed)); // Clamp between 0.5x and 5.0x
```

**Status:** ✅ **CONSISTENT** - Same calculation for all levels

**Problem:** ❌ **NOT IN player-model.js** - Should be moved to player-model.js

---

### **3. POSITION LERP SPEED** (Defined in `main.js`)

**Location:** `main.js` lines ~4514-4526 (in `updatePlayerCharacter()`)

```javascript
let baseLerpSpeed = 180; // Much faster lerp to match new normal speed (96 vs 24) - prevents animation lag

// LEVEL 3 SPECIFIC: Use even faster lerp to prevent flickering with moving walls and fast movement
if (currentLevel === LEVEL_IDS.LEVEL3) {
  baseLerpSpeed = 250; // Very fast lerp in Level 3 to match new speed and prevent flickering
}

// GOD MODE: Apply 2x lerp multiplier to ALL levels for consistency
if (godMode) {
  baseLerpSpeed *= 2.0; // 2x faster lerp in god mode to match 2x movement speed
}
```

**Status:** ❌ **INCONSISTENT** - Level 3 has different lerp speed (250 vs 180)

**Problem:** ❌ **LEVEL-SPECIFIC OVERRIDE** - Level 3 uses 250, all other levels use 180

---

### **4. ROTATION SPEED** (Defined in `main.js`)

**Location:** `main.js` lines ~4599-4604 (in `updatePlayerCharacter()`)

```javascript
let baseRotationSpeed = 0.3; // Default rotation speed (smooth and round) - SAME FOR ALL LEVELS

// GOD MODE: Apply 1.5x rotation multiplier to ALL levels for consistency
if (godMode) {
  baseRotationSpeed *= 1.5; // 1.5x faster rotation in god mode to match movement speed
}
```

**Status:** ✅ **CONSISTENT** - Same for all levels (0.3 base, 0.45 in god mode)

**Problem:** ❌ **NOT IN player-model.js** - Should be moved to player-model.js

---

### **5. ANIMATION DEBOUNCE DELAY** (Defined in `main.js`)

**Location:** `main.js` lines ~4797-4813 (in `updatePlayerCharacter()`)

```javascript
// CRITICAL: Use longer debounce delay for run/idle switches to prevent flickering
// Increased from 100ms to 250ms for smoother animation in Level 3 (especially at low FPS)
const minSwitchDelay = 250; // Minimum 250ms between animation switches (4 switches/second max)
```

**Status:** ✅ **CONSISTENT** - Same for all levels (250ms)

**Problem:** ❌ **NOT IN player-model.js** - Should be moved to player-model.js

---

## 🚨 IDENTIFIED INCONSISTENCIES

### **1. Level 3 Lerp Speed Override** ❌
- **Level 3:** `baseLerpSpeed = 250`
- **All Other Levels:** `baseLerpSpeed = 180`
- **Impact:** Character position interpolation is faster in Level 3, causing different visual feel
- **Fix:** Remove level-specific override, use consistent 180 for all levels

### **2. Settings Scattered in main.js** ❌
- **Movement Speed:** Defined in main.js (line ~20100)
- **Animation Speed:** Defined in main.js (line ~4907, ~4983)
- **Lerp Speed:** Defined in main.js (line ~4514)
- **Rotation Speed:** Defined in main.js (line ~4599)
- **Debounce Delay:** Defined in main.js (line ~4797)
- **Impact:** Hard to maintain consistency, level-specific overrides possible
- **Fix:** Move all settings to player-model.js or player-controls.js

### **3. Animation Speed Calculation in main.js** ❌
- **Location:** `updatePlayerCharacter()` function in main.js
- **Impact:** Animation speed logic is not in player-model.js where it should be
- **Fix:** Move animation speed calculation to player-model.js

---

## 📋 CURRENT SETTINGS SUMMARY

### **Movement Speed:**
- **Normal Walk:** 96 units/sec
- **Normal Sprint:** 168 units/sec
- **God Mode Walk:** 192 units/sec (2x)
- **God Mode Sprint:** 336 units/sec (2x)
- **Location:** `main.js` line ~20100
- **Status:** ✅ Consistent across all levels

### **Animation Speed:**
- **Base Walk Speed:** 96.0 units/sec (for calculation)
- **Animation Speed Range:** 0.5x to 5.0x (clamped)
- **Calculation:** `speedForAnimation / baseWalkSpeed`
- **Location:** `main.js` lines ~4907, ~4983
- **Status:** ✅ Consistent calculation, but Level 3 has special handling

### **Position Lerp Speed:**
- **Default:** 180 (all levels except Level 3)
- **Level 3:** 250 (level-specific override!)
- **God Mode:** 2x multiplier (360 default, 500 Level 3)
- **Location:** `main.js` line ~4514
- **Status:** ❌ **INCONSISTENT** - Level 3 different

### **Rotation Speed:**
- **Base:** 0.3 (all levels)
- **God Mode:** 0.45 (1.5x multiplier)
- **Location:** `main.js` line ~4599
- **Status:** ✅ Consistent across all levels

### **Animation Debounce:**
- **Min Switch Delay:** 250ms (all levels)
- **Idle Transitions:** Immediate (no debounce)
- **Location:** `main.js` line ~4797
- **Status:** ✅ Consistent across all levels

---

## 🎯 RECOMMENDED FIXES

### **1. Remove Level 3 Lerp Speed Override** ✅
**Action:** Remove the Level 3 specific `baseLerpSpeed = 250` override  
**Result:** All levels use consistent `baseLerpSpeed = 180`

### **2. Move Settings to player-model.js** ✅
**Action:** Create a settings object in player-model.js with:
- Movement speed constants
- Animation speed calculation
- Lerp speed constants
- Rotation speed constants
- Debounce delay constants

### **3. Move Animation Speed Calculation to player-model.js** ✅
**Action:** Move animation speed calculation from `updatePlayerCharacter()` to `player-model.js`  
**Result:** All animation logic in one place

### **4. Ensure GOD Mode Only Affects Speed** ✅
**Action:** Verify GOD mode only multiplies speed, not rendering/animation frames  
**Result:** Consistent character rendering, only speed changes

---

## 📝 FILES TO MODIFY

### **1. player-model.js:**
- Add settings object with all constants
- Add animation speed calculation method
- Add lerp speed constant
- Add rotation speed constant
- Add debounce delay constant

### **2. main.js:**
- Remove level-specific lerp speed override (Level 3)
- Use player-model.js settings instead of hardcoded values
- Call player-model.js methods for animation speed calculation

### **3. player-controls.js:**
- Move movement speed constants here (or keep in main.js if used for physics)

---

## 🔧 PROPOSED SETTINGS STRUCTURE

### **In player-model.js:**
```javascript
// Character movement and animation settings
this.settings = {
  // Movement speeds (from player-controls.js or main.js)
  walkSpeed: 96.0,        // units/sec
  sprintSpeed: 168.0,     // units/sec
  godModeMultiplier: 2.0, // GOD mode speed multiplier
  
  // Position interpolation
  baseLerpSpeed: 180,     // Same for ALL levels (no level-specific overrides)
  godModeLerpMultiplier: 2.0,
  
  // Rotation
  baseRotationSpeed: 0.3, // Same for ALL levels
  godModeRotationMultiplier: 1.5,
  
  // Animation
  baseWalkSpeed: 96.0,    // For animation speed calculation
  animationSpeedMin: 0.5, // Minimum animation speed multiplier
  animationSpeedMax: 5.0, // Maximum animation speed multiplier
  
  // Animation switching
  minSwitchDelay: 250,    // ms between animation switches
  idleTransitionImmediate: true, // Idle transitions are immediate
};
```

---

## 🚨 CRITICAL FINDINGS

### **Level 3 Special Case:**
- **Line 4519:** `baseLerpSpeed = 250` (Level 3 only)
- **Reason:** "Very fast lerp in Level 3 to match new speed and prevent flickering"
- **Problem:** This creates inconsistency - Level 3 feels different from other levels
- **Solution:** Remove level-specific override, use consistent 180 for all levels

### **Settings Location:**
- **Current:** All settings in `main.js` `updatePlayerCharacter()` function
- **Problem:** Hard to maintain, level-specific overrides possible
- **Solution:** Move to `player-model.js` for centralized management

### **GOD Mode Implementation:**
- **Current:** GOD mode multiplies speed correctly (2x)
- **Status:** ✅ Working correctly
- **Note:** Only speed should change, not rendering/animation frames

---

## 📊 COMPARISON: LEVEL 1-5 SETTINGS

| Setting | Level 1 | Level 2 | Level 3 | Level 4 | Level 5 |
|---------|---------|---------|---------|---------|---------|
| **Movement Speed (Walk)** | 96 | 96 | 96 | 96 | 96 |
| **Movement Speed (Sprint)** | 168 | 168 | 168 | 168 | 168 |
| **Lerp Speed** | 180 | 180 | **250** ❌ | 180 | 180 |
| **Rotation Speed** | 0.3 | 0.3 | 0.3 | 0.3 | 0.3 |
| **Animation Speed Calc** | Same | Same | Same | Same | Same |
| **Debounce Delay** | 250ms | 250ms | 250ms | 250ms | 250ms |

**Issue:** Level 3 has different lerp speed (250 vs 180) - this causes inconsistent character movement feel!

---

## 🎯 NEXT STEPS

1. ✅ **Document current settings** - COMPLETE
2. ⏳ **Remove Level 3 lerp speed override** - PENDING
3. ⏳ **Move settings to player-model.js** - PENDING
4. ⏳ **Move animation speed calculation to player-model.js** - PENDING
5. ⏳ **Test all 5 levels for consistency** - PENDING

---

**LAB NOTE COMPLETED:** December 7, 2025  
**STATUS:** 🔍 **INVESTIGATION COMPLETE - INCONSISTENCIES IDENTIFIED**  
**IMPACT:** 🚨 **LEVEL 3 HAS DIFFERENT LERP SPEED - CAUSES INCONSISTENT FEEL**  
**NEXT:** 🔧 **FIX INCONSISTENCIES AND MOVE SETTINGS TO player-model.js**

