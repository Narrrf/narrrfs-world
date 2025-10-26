# 🧩 TETRIS ROLE MULTIPLIER TESTING GUIDE

**Date:** October 26, 2025  
**Purpose:** Systematic testing of all Tetris role multipliers  
**Method:** Same as Snake - test each role individually  

---

## 🎯 **TETRIS SCORING SYSTEM**

### **How Tetris Calculates DSPOINC:**

**Different from Snake:**
- **Snake:** `score = baseScore × roleMultiplier` (e.g., 10 × 1.5 = 15)
- **Tetris:** `score = baseScore + roleBonus` (e.g., 8 + 4 = 12)

**Tetris Calculation:**
```javascript
// Line 1236-1241 in tetris-scroll.js
const baseScore = lines * 2; // 4 lines = 8 DSPOINC
const roleMultiplier = getRoleScoreMultiplier(); // e.g., 1.5x
const roleBonus = Math.floor(baseScore * (roleMultiplier - 1)); // 8 * 0.5 = 4
score += baseScore + roleBonus; // 8 + 4 = 12 total
```

---

## 🧪 **LOCAL TESTING MODE**

### **How to Test Each Role:**

No need to modify code! Tetris has built-in test functions:

**Console Commands:**
```javascript
// Test VIP Holder (2.0x)
userRoleIDs = ['1332016526848692345'];
applyRoleTheme();
updateTetrisScoreDisplay();
console.log('Testing VIP Holder');

// Test Holder (1.5x)
userRoleIDs = ['1402668301414563971'];
applyRoleTheme();
updateTetrisScoreDisplay();
console.log('Testing Holder');

// Test Champion (1.4x)
userRoleIDs = ['1332017420591697972'];
applyRoleTheme();
updateTetrisScoreDisplay();
console.log('Testing Champion');

// Test Season Tester (1.3x)
userRoleIDs = ['1428901285754830858'];
applyRoleTheme();
updateTetrisScoreDisplay();
console.log('Testing Season Tester');

// Test Early Bird (1.2x)
userRoleIDs = ['1405837093829791754'];
applyRoleTheme();
updateTetrisScoreDisplay();
console.log('Testing Early Bird');

// Test Cheese Hunter (1.1x)
userRoleIDs = ['1399651053682692208'];
applyRoleTheme();
updateTetrisScoreDisplay();
console.log('Testing Cheese Hunter');

// Reset to no role (1.0x)
userRoleIDs = [];
applyRoleTheme();
updateTetrisScoreDisplay();
console.log('Testing No Role');
```

---

## 📊 **EXPECTED RESULTS FOR 4 LINES (8 BASE DSPOINC)**

### **Calculation Breakdown:**

| Role | Multiplier | Base | Bonus Calc | Bonus | Total | Frame |
|------|-----------|------|-----------|-------|-------|-------|
| VIP Holder | 2.0x | 8 | 8 × (2.0 - 1) = 8 × 1.0 | 8 | 16 | 🟡 Gold |
| Holder | 1.5x | 8 | 8 × (1.5 - 1) = 8 × 0.5 | 4 | 12 | ⚪ Silver |
| Champion | 1.4x | 8 | 8 × (1.4 - 1) = 8 × 0.4 | 3 | 11 | 🔴 Red |
| Season Tester | 1.3x | 8 | 8 × (1.3 - 1) = 8 × 0.3 | 2 | 10 | 🌈 Rainbow |
| Early Bird | 1.2x | 8 | 8 × (1.2 - 1) = 8 × 0.2 | 2 | 10 | 🔵 Blue |
| Cheese Hunter | 1.1x | 8 | 8 × (1.1 - 1) = 8 × 0.1 | 1 | 9 | 🟠 Orange |
| No Role | 1.0x | 8 | 8 × (1.0 - 1) = 8 × 0.0 | 0 | 8 | 🟡 Yellow |

**Note:** Math.floor() rounds down fractional bonuses

---

## ✅ **TESTING PROCEDURE**

### **For Each Role:**

1. **Set Role ID:**
   - Open browser console (F12)
   - Run role ID command from above
   - Verify frame color changes

2. **Play Game:**
   - Clear exactly 4 lines (easiest to control)
   - Note the in-game score display
   - Continue until game over

3. **Check Game Over:**
   - Note the final score in game over modal
   - Should match in-game display

4. **Verify Database:**
   - Check `tbl_tetris_scores` for saved score
   - Should match game over display
   - Should NOT be multiplied by backend

5. **Record Results:**
   - Note exact DSPOINC amount
   - Verify frame color
   - Confirm matches expected calculation

---

## 🔍 **WHAT TO LOOK FOR**

### **✅ CORRECT BEHAVIOR:**
- In-game score matches expected calculation
- Game over modal matches in-game score
- Database amount matches game over score
- Frame color matches role
- Role bonus indicator shows in display

### **❌ WRONG BEHAVIOR:**
- Score doesn't match calculation
- Database amount is different (too high/low)
- Frame color doesn't match role
- No role bonus indicator
- Backend multiplying again (score × 2 or similar)

---

## 🚨 **CRITICAL BACKEND CHECK**

### **Verify `save-score.php` for Tetris:**

**Should Be:**
```php
if ($game === 'tetris') {
    $pointsPerUnit = 1; // NO multiplication
    $unit = 'lines';
    $dspoinc_score = $raw_score; // Use score as-is
}
```

**Should NOT Be:**
```php
if ($game === 'tetris') {
    $pointsPerUnit = 2; // ❌ WRONG - Double multiplication
    $unit = 'lines';
    $dspoinc_score = $raw_score * $pointsPerUnit; // ❌ WRONG
}
```

**Current Status:** ✅ Tetris backend is correct (uses `$pointsPerUnit = 1`)

---

## 📋 **TEST RESULTS TEMPLATE**

### **Test Date:** [Fill in]
### **Tester:** [Fill in]

| Role | Expected | In-Game | Game Over | Database | Frame | Status |
|------|----------|---------|-----------|----------|-------|--------|
| VIP Holder | 16 | __ | __ | __ | Gold? | ⏳ |
| Holder | 12 | __ | __ | __ | Silver? | ⏳ |
| Champion | 11 | __ | __ | __ | Red? | ⏳ |
| Season Tester | 10 | __ | __ | __ | Rainbow? | ⏳ |
| Early Bird | 10 | __ | __ | __ | Blue? | ⏳ |
| Cheese Hunter | 9 | __ | __ | __ | Orange? | ⏳ |
| No Role | 8 | __ | __ | __ | Yellow? | ⏳ |

**Notes:**
- All tests should clear exactly 4 lines
- Expected values are for 4 lines (8 base DSPOINC)
- Database values must match game over scores

---

## 🎯 **SUCCESS CRITERIA**

### **All Tests Pass If:**
- ✅ All 7 tests show correct DSPOINC amounts
- ✅ Frame colors match roles
- ✅ Database amounts match displays
- ✅ No backend double multiplication
- ✅ Role bonus indicators show correctly

### **If Any Test Fails:**
- 🔍 Check frontend calculation in `tetris-scroll.js`
- 🔍 Check backend in `save-score.php`
- 🔍 Check for hidden multipliers or conversions
- 🔍 Test locally before deploying fixes

---

**READY TO TEST TETRIS SYSTEMATICALLY!** 🧩✅

