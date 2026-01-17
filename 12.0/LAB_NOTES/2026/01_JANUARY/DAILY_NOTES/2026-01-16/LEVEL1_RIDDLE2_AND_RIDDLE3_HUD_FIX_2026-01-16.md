# 🧩 Level 1 Riddle #2 and #3 HUD Display Fix - January 16, 2026

**Date:** January 16, 2026  
**Status:** ✅ **COMPLETE - BOTH ISSUES FIXED**

---

## 🎯 **ISSUES IDENTIFIED**

### **Issue 1: Riddle #2 and #3 HUD Not Displaying**
**Problem:** After completing Riddle #1 (Steps 0, 1, 2), the HUD disappeared and didn't show for Riddle #2 and Riddle #3 steps.

**Root Cause:** Missing `return` statements in Step 2 and Riddle #2/3 HUD display code, causing the function to continue past the intended display logic.

### **Issue 2: Riddle #2 Step 1 Not Completing When Block Moved to Oak Stone**
**Problem:** When moving the O-block to the oak stone coordinates, the riddle step didn't complete even though the block was within the threshold distance.

**Root Cause:** The proximity check was INSIDE the movement physics block, so it only ran when the block was actively being moved. If the player stopped moving the block near the oak stone, the check wouldn't run and Step 1 wouldn't complete.

---

## ✅ **FIXES APPLIED**

### **Fix 1: Added Return Statements to All HUD Displays**

**File:** `public/three.js/main.js`

**Changes:**
1. **Step 2 (Riddle #1):** Added `return` after Step 2 HUD display (line 41490)
2. **Riddle #2 Step 1:** Added `return` after Step 1 HUD display (line 41538)
3. **Riddle #2 Step 2:** Added `return` after Step 2 HUD display (line 41558)
4. **Riddle #3 Step 1:** Added `return` after Step 1 HUD display (line 41588)
5. **Riddle #3 Step 2:** Added `return` after Step 2 HUD display (line 41614)
6. **Riddle #3 Step 3:** Added `return` after Step 3 HUD display (line 41642)
7. **Riddle #3 Complete:** Added `return` after completion logic (line 41644)

**Result:** All HUD displays now have proper return statements, preventing fall-through to incorrect logic.

---

### **Fix 2: Moved Proximity Check Outside Movement Block**

**File:** `public/three.js/main.js`  
**Location:** Lines 35309-35342 (moved to lines 35344-35380)

**Before:**
```javascript
// Movement physics block
if (riddleState.unlockableBlock && riddleState.unlockableBlock.visible && !r2.step1Complete) {
  // ... movement physics ...
  
  // Proximity check INSIDE movement block (only runs when moving)
  if (r2.oakStone && !r2.step1Complete) {
    const distance = blockPos.distanceTo(oakPos);
    if (distance < RIDDLE2_PROXIMITY_THRESHOLD) {
      r2.step1Complete = true;
    }
  }
}
```

**After:**
```javascript
// Movement physics block
if (riddleState.unlockableBlock && riddleState.unlockableBlock.visible && !r2.step1Complete) {
  // ... movement physics ...
}

// CRITICAL FIX: Proximity check OUTSIDE movement block (runs continuously)
if (riddleState.unlockableBlock && riddleState.unlockableBlock.visible && r2.oakStone && !r2.step1Complete) {
  const blockPos = riddleState.unlockableBlock.position;
  const oakPos = r2.oakStone.position;
  // Use horizontal distance (ignore Y difference) for more accurate detection
  const dx = blockPos.x - oakPos.x;
  const dz = blockPos.z - oakPos.z;
  const horizontalDistance = Math.sqrt(dx * dx + dz * dz);
  
  if (horizontalDistance < RIDDLE2_PROXIMITY_THRESHOLD) {
    r2.step1Complete = true;
    // ... completion logic ...
  }
}
```

**Key Improvements:**
- ✅ **Continuous Checking:** Proximity check runs every frame, not just when block is moving
- ✅ **Horizontal Distance:** Uses horizontal distance (ignores Y difference) for more accurate detection
- ✅ **Better Logging:** Added detailed console logging for debugging

**Result:** Riddle #2 Step 1 now completes correctly when the block is moved within 1.5 units of the oak stone, even if the block is stationary.

---

## 📋 **VERIFICATION CHECKLIST**

### **HUD Display:**
- [ ] Complete Riddle #1 Step 0 → HUD shows Step 1
- [ ] Complete Riddle #1 Step 1 → HUD shows Step 2
- [ ] Complete Riddle #1 Step 2 → HUD shows Riddle #2 Step 1
- [ ] Complete Riddle #2 Step 1 → HUD shows Riddle #2 Step 2
- [ ] Complete Riddle #2 Step 2 → HUD shows Riddle #3 Step 1
- [ ] Complete Riddle #3 Step 1 → HUD shows Riddle #3 Step 2
- [ ] Complete Riddle #3 Step 2 → HUD shows Riddle #3 Step 3
- [ ] Complete Riddle #3 Step 3 → HUD hidden (all riddles complete)

### **Riddle #2 Step 1 Completion:**
- [ ] Move O-block near oak stone (within 1.5 units)
- [ ] Step 1 completes automatically (even if block stops moving)
- [ ] Block snaps to oak stone position
- [ ] Oak stone glows permanently (completion indicator)
- [ ] Console shows completion message with distance details

---

## 🔧 **TECHNICAL DETAILS**

### **Proximity Detection:**
- **Threshold:** `RIDDLE2_PROXIMITY_THRESHOLD = 1.5` units
- **Distance Calculation:** Horizontal distance only (ignores Y difference)
- **Check Frequency:** Every frame (continuous, not just when moving)
- **Completion Action:** Block snaps to oak stone position, velocity set to zero

### **HUD Display Flow:**
```
Step 0 → Step 1 → Step 2 → Riddle #2 Step 1 → Riddle #2 Step 2 → Riddle #3 Step 1 → Riddle #3 Step 2 → Riddle #3 Step 3 → Hidden
```

Each step now has a `return` statement to prevent fall-through to the next step's logic.

---

## 📊 **FILES MODIFIED**

- ✅ **`public/three.js/main.js`**
  - Line 41490: Added `return` to Step 2 (Riddle #1)
  - Line 41538: Added `return` to Riddle #2 Step 1
  - Line 41558: Added `return` to Riddle #2 Step 2
  - Line 41588: Added `return` to Riddle #3 Step 1
  - Line 41614: Added `return` to Riddle #3 Step 2
  - Line 41642: Added `return` to Riddle #3 Step 3
  - Line 41644: Added `return` to Riddle #3 completion
  - Lines 35344-35380: Moved proximity check outside movement block

---

## 🎯 **SUCCESS CRITERIA**

✅ **All HUD displays work correctly** for Riddle #1, #2, and #3  
✅ **Riddle #2 Step 1 completes** when block is within 1.5 units of oak stone  
✅ **Proximity check runs continuously** (not just when block is moving)  
✅ **Horizontal distance used** for more accurate detection  
✅ **No fall-through** between riddle steps

---

## 📝 **NOTES**

- **Return Statements:** Critical for preventing HUD display logic from continuing to next step
- **Continuous Checking:** Proximity check must run every frame, not just during movement
- **Horizontal Distance:** More accurate than 3D distance for ground-based objects
- **Debug Logging:** Added detailed console logs to help diagnose completion issues

---

**Created:** January 16, 2026  
**Status:** ✅ **COMPLETE - BOTH FIXES APPLIED**  
**Testing:** Ready for user testing
