# 🐉 Dragon Flickering/Disappearing Fix - Level 6

**Date:** December 8, 2025  
**Issue:** Dragon model briefly visible then disappears (flickering issue)  
**Status:** ✅ **FIXES APPLIED**

---

## 🔍 **PROBLEM ANALYSIS**

### **What Was Working:**
- ✅ Model loads successfully (61 animations)
- ✅ Model is visible initially (briefly appears)
- ✅ Model is moving (position updates correctly)
- ✅ Visibility checks pass: `visible=true, inGroup=true, groupInScene=true, groupVisible=true`
- ✅ Size is correct: 3.94-3.98 units (target: 4.0 units)

### **What Was Wrong:**
- ❌ Model disappears after brief appearance (flickering)
- ❌ Scale check using wrong target (2.5 units instead of 4.0 units)
- ❌ Scale check applying incorrect fixes (scaling down to 2.5 units when size is correct)
- ❌ Scale check running too frequently (every 60 frames)
- ❌ Missing frustum culling disable (like Level 4 monsters)

---

## 🔧 **FIXES APPLIED**

### **1. Fixed Scale Check Target**
**File: `three.js/phoenix.js`**

**Before:**
```javascript
// Wrong target - checking for 2.5 units
if (worldScaleFactor > 0.005) {
  console.error(`❌ [PHOENIX] CRITICAL: World scale is ${worldScaleFactor.toFixed(4)} (should be ~0.0023 for 2.5 units)! Model will appear HUGE!`);
}
```

**After:**
```javascript
// Correct target - checking for 4.0 units
const targetSize = 4.0; // Target size in units (matches targetMaxSize from loadModel)
const sizeTolerance = 0.5; // Allow ±0.5 units tolerance

if (finalVisualSize < targetSize - sizeTolerance || finalVisualSize > targetSize + sizeTolerance) {
  console.warn(`⚠️ [PHOENIX] Size check: ${finalVisualSize.toFixed(2)} units (target: ${targetSize} units, tolerance: ±${sizeTolerance})`);
} else {
  // Size is correct - no warning needed, no fixes applied
  // Model is 3.94-3.98 units, which is within tolerance of 4.0 units - CORRECT!
}
```

### **2. Fixed Second Scale Fix**
**File: `three.js/phoenix.js`**

**Before:**
```javascript
// Wrong target - scaling down to 2.5 units
if (verifyFinalSize > 3.5) {
  console.error(`❌ [PHOENIX] Still too large! Applying second fix...`);
  const secondScaleRatio = 2.5 / verifyFinalSize;
}
```

**After:**
```javascript
// Correct target - only fix if > 4.5 units (10% over 4.0 target)
const targetSize = 4.0; // Target size in units
if (verifyFinalSize > targetSize + 0.5) { // Only fix if > 4.5 units
  console.warn(`⚠️ [PHOENIX] Still too large (${verifyFinalSize.toFixed(2)} units)! Applying second fix to reach ${targetSize} units...`);
  const secondScaleRatio = targetSize / verifyFinalSize;
}
```

### **3. Reduced Scale Check Frequency**
**File: `three.js/phoenix.js`**

**Before:**
```javascript
// Too frequent - every 60 frames (1 second at 60fps)
if (this.updateFrameCount % 60 === 0) {
  this._forceScaleCheck();
}
```

**After:**
```javascript
// Less frequent - every 300 frames (5 seconds at 60fps)
// REDUCED FREQUENCY: Less frequent checks prevent unnecessary fixes that cause flickering
if (this.updateFrameCount % 300 === 0) {
  this._forceScaleCheck();
}
```

### **4. Added Frustum Culling Disable**
**File: `three.js/phoenix.js`**

**Added:**
```javascript
// CRITICAL: Disable frustum culling to prevent model from disappearing (like Level 4 monsters)
// This ensures the model is always rendered, even if it's outside the camera frustum
this.phoenixModel.traverse((child) => {
  if (child.isMesh) {
    child.frustumCulled = false; // Disable frustum culling for visibility
    child.renderOrder = 100; // Higher render order ensures dragon renders on top
  }
});
console.log("✅ [PHOENIX] Frustum culling disabled (like Level 4 monsters)");
```

---

## 🎯 **ROOT CAUSE**

### **The Problem:**
1. **Scale Check Running Too Often:** Every 60 frames, the scale check was running and applying fixes
2. **Wrong Target Size:** Scale check was using 2.5 units target instead of 4.0 units
3. **Incorrect Fixes:** When size was 3.94-3.98 units (CORRECT for 4.0 target), the code was applying fixes to scale it down to 2.5 units
4. **Frustum Culling:** Model was being culled when outside camera frustum (like the old Phoenix model)

### **The Solution:**
1. **Fixed Target Size:** Changed from 2.5 units to 4.0 units throughout
2. **Reduced Check Frequency:** From every 60 frames to every 300 frames
3. **Correct Fix Logic:** Only apply fixes if size is > 4.5 units (10% over target)
4. **Disabled Frustum Culling:** Like Level 4 monsters, ensure model is always rendered

---

## 🎮 **EXPECTED BEHAVIOR**

### **After Fixes:**
1. **Model Stays Visible:**
   - ✅ Model remains visible throughout flight
   - ✅ No flickering or disappearing
   - ✅ Frustum culling disabled

2. **Scale Check:**
   - ✅ Only runs every 5 seconds (not every second)
   - ✅ Uses correct target (4.0 units)
   - ✅ Only applies fixes if size is > 4.5 units
   - ✅ No fixes applied when size is 3.94-3.98 units (correct!)

3. **Console Output:**
   - ✅ No more "CRITICAL: World scale is 1.1666 (should be ~0.0023)" errors
   - ✅ Occasional "Size check: X.XX units (target: 4.0 units) - CORRECT" messages
   - ✅ "Frustum culling disabled" message on load

---

## 📋 **VERIFICATION CHECKLIST**

### **After Loading:**
- [ ] Model loads successfully (61 animations)
- [ ] Frustum culling disabled message appears
- [ ] Model stays visible (no flickering)
- [ ] Model moves in circular pattern
- [ ] Animation plays (FlyIdle1/2/3)

### **During Flight:**
- [ ] Model remains visible throughout
- [ ] No disappearing or flickering
- [ ] Position updates correctly
- [ ] Animation continues playing
- [ ] Scale check only runs every 5 seconds

### **Console Output:**
- [ ] No "CRITICAL: World scale" errors
- [ ] Occasional "Size check: CORRECT" messages
- [ ] No unnecessary scale fixes applied
- [ ] Visibility checks all pass

---

## 🔍 **DEBUGGING TIPS**

### **If Model Still Disappears:**

1. **Check Frustum Culling:**
   - Verify `frustumCulled = false` is set on all meshes
   - Check console for "Frustum culling disabled" message

2. **Check Scale Fixes:**
   - Look for "Applying second fix" messages
   - Verify size is 3.94-3.98 units (should NOT trigger fixes)
   - Check if fixes are being applied incorrectly

3. **Check Visibility:**
   - Verify `visible=true` in console
   - Check if model is in level group
   - Verify level group is visible

4. **Check Position:**
   - Verify model position is near player
   - Check if model is too far away
   - Verify camera can see model position

---

## 🚀 **NEXT STEPS**

1. **Test Level 6** - Load and verify dragon stays visible
2. **Check Console** - Look for "Frustum culling disabled" and "CORRECT" messages
3. **Verify Animation** - Ensure idle fly animation plays continuously
4. **Test Movement** - Verify circular flight pattern works
5. **Monitor Scale** - Verify no incorrect fixes are applied

---

**Fixes Applied:** December 8, 2025  
**Status:** ✅ **READY FOR TESTING**  
**Next:** Test Level 6 and verify dragon stays visible without flickering

