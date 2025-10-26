# 🚀 SPACE INVADERS TESTING WORKPLAN - OCTOBER 26, 2025

**Date:** October 26, 2025  
**Time:** 19:25  
**Status:** 🟢 **READY TO TEST**  
**Goal:** Apply same systematic testing to Space Invaders as Snake and Tetris  

---

## 🎯 **OBJECTIVE**

Test all 6 role multipliers in Space Invaders to verify scoring and identify any Math.floor() issues (like we found in Tetris).

---

## 🔍 **CURRENT STATE ANALYSIS**

### **Code Issues Found:**
```javascript
// Line 949-951: Phoenix kill scoring
const baseScore = 1; // 🚨 SAME AS SNAKE/TETRIS - May cause Math.floor() issues
const roleMultiplier = getSpaceInvadersRoleScoreMultiplier();
const totalScore = Math.floor(baseScore * roleMultiplier); // 🚨 Math.floor() may be too harsh
```

### **Theme Issues Found:**
```javascript
// Line 201: Season Tester uses rainbow
'Season Tester': 'rainbow', // 🚨 Should be 'green' like Snake/Tetris

// Line 275: Role ID mapping
'1417279348989497532': 'rainbow', // 🚨 Should be 'green'
```

---

## 🚨 **POTENTIAL ISSUES**

### **1. Math.floor() Problem:**
Similar to Tetris, fractional bonuses may round down to 0:
- Champion (1.4x): 1 × 1.4 = 1.4 → Math.floor(1.4) = 1 ❌ (no bonus!)
- Early Bird (1.2x): 1 × 1.2 = 1.2 → Math.floor(1.2) = 1 ❌ (no bonus!)
- Cheese Hunter (1.1x): 1 × 1.1 = 1.1 → Math.floor(1.1) = 1 ❌ (no bonus!)

### **2. Rainbow Theme:**
Season Tester still uses rainbow theme (not green like Snake/Tetris)

---

## 🎯 **TESTING PLAN**

### **Systematic Testing Approach:**
Test each role with killing 10 invaders to verify:
1. **Correct multiplier applied**
2. **Correct frame color displayed**
3. **Math.floor() not killing fractional bonuses**

### **Expected Results (10 invaders):**

| Role | Multiplier | Base (10) | Bonus | Total | Frame |
|------|-----------|-----------|-------|-------|-------|
| VIP Holder | 2.0x | 10 | 10 | 20 | 🟡 Gold |
| Holder | 1.5x | 10 | 5 | 15 | ⚪ Silver |
| Champion | 1.4x | 10 | 4 | 14 | 🔴 Red |
| Season Tester | 1.3x | 10 | 3 | 13 | 🟢 Green |
| Early Bird | 1.2x | 10 | 2 | 12 | 🔵 Blue |
| Cheese Hunter | 1.1x | 10 | 1 | 11 | 🟠 Orange |

**Note:** If Math.floor() is used with baseScore = 1:
- Champion gets 1 bonus (instead of 0.4 × 10 = 4)
- Early Bird gets 1 bonus (instead of 0.2 × 10 = 2)
- Cheese Hunter gets 1 bonus (instead of 0.1 × 10 = 1)

This suggests the system might actually be working differently than Snake/Tetris!

---

## 🔧 **TESTING METHOD**

### **Local Testing Override:**
Add hardcoded role like Snake and Tetris:

```javascript
if (isLocalDevelopment) {
  // 🧪 LOCAL TEST MODE - HARDCODED FOR TESTING
  const LOCAL_TEST_ROLE = 'VIP Holder'; // ← Change to test different roles
  
  const roleMap = {
    'VIP Holder': '1332016526848692345',
    'Holder': '1402668301414563971',
    'Champion': '1332017420591697972',
    'Season Tester': '1417279348989497532',
    'Early Bird': '1332017614108758148',
    'Cheese Hunter': '1399651053682692208'
  };
  
  const testRoleID = roleMap[LOCAL_TEST_ROLE];
  spaceInvadersUserRoleIDs = [testRoleID]; // ONLY this role
  
  applySpaceInvadersRoleTheme();
  return spaceInvadersUserRoleIDs;
}
```

---

## 📋 **TEST SEQUENCE**

### **Test #1 - VIP Holder (2.0x):**
- Kill 10 invaders
- Expected: 20 DSPOINC
- Frame: 🟡 Gold
- Status: ⏳ Pending

### **Test #2 - Holder (1.5x):**
- Kill 10 invaders
- Expected: 15 DSPOINC
- Frame: ⚪ Silver
- Status: ⏳ Pending

### **Test #3 - Champion (1.4x):**
- Kill 10 invaders
- Expected: 14 DSPOINC
- Frame: 🔴 Red
- Status: ⏳ Pending

### **Test #4 - Season Tester (1.3x):**
- Kill 10 invaders
- Expected: 13 DSPOINC
- Frame: 🟢 GREEN (after changing from rainbow)
- Status: ⏳ Pending

### **Test #5 - Early Bird (1.2x):**
- Kill 10 invaders
- Expected: 12 DSPOINC
- Frame: 🔵 Blue
- Status: ⏳ Pending

### **Test #6 - Cheese Hunter (1.1x):**
- Kill 10 invaders
- Expected: 11 DSPOINC
- Frame: 🟠 Orange
- Status: ⏳ Pending

---

## 🔧 **FIXES TO APPLY**

### **1. Change Rainbow to Green:**
```javascript
// Line 201
'Season Tester': 'green', // Changed from 'rainbow'

// Line 275
'1417279348989497532': 'green', // Changed from 'rainbow'

// Line 291
canvas.classList.remove('golden', 'silver', 'cheese', 'green', 'royal', 'blue', 'red');
```

### **2. Add Green CSS (if missing):**
Check `profile.html` or `space-cheese-invaders.html` for green canvas CSS.

### **3. Update Help Text:**
Change "Rainbow Frame" to "Green Frame" in game instructions.

---

## 📊 **EXPECTED OUTCOMES**

### **If baseScore = 1 (current):**
All roles should work, but bonuses may be small (1-10 per kill).

### **If Math.floor() causes issues:**
May need to change to Math.round() like Tetris.

### **If scoring feels too low:**
May need to increase baseScore (like Snake: 1 → 10).

---

## 🚀 **NEXT STEPS**

1. ⏳ Add local testing override to `space-cheese-invaders.js`
2. ⏳ Change Season Tester theme from rainbow to green
3. ⏳ Test VIP Holder role (2.0x)
4. ⏳ Test remaining 5 roles systematically
5. ⏳ Document results in `SPACE_INVADERS_TEST_RESULTS.md`
6. ⏳ Apply any necessary fixes
7. ⏳ Deploy to production

---

**TESTING STARTS NOW!** 🚀✅

