# 🧩 TETRIS ROLE MULTIPLIER TESTING WORKPLAN

**Date:** October 26, 2025  
**Time:** 18:45  
**Status:** 🔄 **IN PROGRESS**  
**Goal:** Systematically test all Tetris role multipliers + change rainbow to green  

---

## 🎯 **WORKPLAN OVERVIEW**

### **Phase 1: Preparation (5 minutes)**
- [x] Create testing workplan
- [ ] Add local testing mode to `tetris-scroll.js`
- [ ] Change Season Tester theme from rainbow to green
- [ ] Update help text to show green instead of rainbow
- [ ] Document expected results for 4 lines

### **Phase 2: Testing (15 minutes)**
- [ ] Test VIP Holder (2.0x) - 16 DSPOINC expected
- [ ] Test Holder (1.5x) - 12 DSPOINC expected
- [ ] Test Champion (1.4x) - 11 DSPOINC expected
- [ ] Test Season Tester (1.3x) - 10 DSPOINC expected (GREEN frame)
- [ ] Test Early Bird (1.2x) - 10 DSPOINC expected
- [ ] Test Cheese Hunter (1.1x) - 9 DSPOINC expected
- [ ] Test No Role (1.0x) - 8 DSPOINC expected

### **Phase 3: Verification (5 minutes)**
- [ ] Verify all frame colors correct
- [ ] Verify all DSPOINC amounts correct
- [ ] Verify role bonus indicators show
- [ ] Remove local testing mode
- [ ] Update test results documentation

### **Phase 4: Deployment (5 minutes)**
- [ ] Git add all changes
- [ ] Commit with comprehensive message
- [ ] Push to render-deploy
- [ ] Wait for deployment
- [ ] Test on production

---

## 🧪 **LOCAL TESTING MODE IMPLEMENTATION**

### **Where to Add:**
File: `public/scripts/tetris-scroll.js`  
Location: Inside `fetchUserRoleIDs()` function (around line 100-122)

### **Code to Add:**
```javascript
async function fetchUserRoleIDs() {
  try {
    // 🌍 Local development bypass - use test role IDs
    const isLocalDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' || window.location.hostname === '';
    
    if (isLocalDevelopment) {
      // 🧪 LOCAL TEST MODE - HARDCODED FOR TESTING
      // Change this line to test different roles (no console needed!)
      const LOCAL_TEST_ROLE = 'VIP Holder'; // ← CHANGE THIS TO TEST DIFFERENT ROLES
      
      const roleMap = {
        'VIP Holder': '1332016526848692345',
        'Holder': '1402668301414563971',
        'Champion': '1332017420591697972',
        'Season Tester': '1417279348989497532',
        'Early Bird': '1332017614108758148',
        'Cheese Hunter': '1399651053682692208'
      };
      
      const testRoleID = roleMap[LOCAL_TEST_ROLE];
      console.log(`🧪 LOCAL TEST MODE - Testing as ${LOCAL_TEST_ROLE} (ID: ${testRoleID})`);
      
      userRoleIDs = [testRoleID]; // ONLY this role for testing
      
      console.log('🏆 Local test role ID loaded for Tetris:', userRoleIDs);
      
      // Apply role-based theme on load
      applyRoleTheme();
      
      return userRoleIDs;
    }
    
    // ... rest of production code
  }
}
```

---

## 🎨 **THEME CHANGE: RAINBOW → GREEN**

### **Changes Needed:**

**1. Tetris Script (tetris-scroll.js):**
```javascript
// Line ~77 - Role themes
let roleThemes = {
  'VIP Holder': 'golden',
  '🎴 VIP Holder': 'golden',
  'Holder': 'silver',
  '🏆 Holder': 'silver', 
  'Cheese Hunter': 'cheese',
  '🧀 Cheese Hunter': 'cheese',
  'Season Tester': 'green',  // Changed from 'rainbow'
  'Early Bird': 'blue',
  'Champion': 'red'
};

// Line ~157 - Theme mapping
const roleIDToTheme = {
  '1332016526848692345': 'golden',    // 🎴 VIP Holder
  '1402668301414563971': 'silver',    // 🏆 Holder
  '1332017420591697972': 'red',       // Champion
  '1417279348989497532': 'green',     // Season Tester (changed from 'rainbow')
  '1332017614108758148': 'blue',      // Early Bird
  '1399651053682692208': 'cheese',    // 🧀 Cheese Hunter
  '1332108350518857842': 'blue'       // WL (blue theme)
};

// Line ~170 - Remove rainbow from classList
canvas.classList.remove('golden', 'silver', 'cheese', 'green', 'blue', 'red');

// Line ~182 - Remove rainbow from controls
controlsSection.classList.remove('golden', 'silver', 'cheese', 'green', 'blue', 'red');
```

**2. Profile HTML (profile.html):**
```html
<!-- Add green CSS for Tetris canvas (already exists for Snake) -->

<!-- Line ~1708 - Update help text -->
<p class="text-xs text-gray-300 mb-1">
  <span style="color: #00FF00;">🟢 Green Frame:</span> Season Tester (1.3x scoring, green particles)
</p>
```

**3. Profile HTML CSS:**
```css
/* Add to Tetris canvas styles if missing */
#tetris-canvas.green {
  box-shadow: 0 0 20px rgba(0, 255, 0, 0.6), 0 0 40px rgba(0, 255, 0, 0.4) !important;
  border-color: #00FF00 !important;
}
```

---

## 📊 **EXPECTED TEST RESULTS (4 LINES CLEARED)**

### **Base Calculation:**
4 lines × 2 DSPOINC per line = **8 DSPOINC base**

### **Role-Based Results:**

| # | Role | Role ID | Multiplier | Base | Bonus Formula | Bonus | Total | Frame |
|---|------|---------|-----------|------|---------------|-------|-------|-------|
| 1 | VIP Holder | 1332016526848692345 | 2.0x | 8 | floor(8×1.0) | 8 | **16** | 🟡 Gold |
| 2 | Holder | 1402668301414563971 | 1.5x | 8 | floor(8×0.5) | 4 | **12** | ⚪ Silver |
| 3 | Champion | 1332017420591697972 | 1.4x | 8 | floor(8×0.4) | 3 | **11** | 🔴 Red |
| 4 | Season Tester | 1417279348989497532 | 1.3x | 8 | floor(8×0.3) | 2 | **10** | 🟢 **GREEN** |
| 5 | Early Bird | 1332017614108758148 | 1.2x | 8 | floor(8×0.2) | 2 | **10** | 🔵 Blue |
| 6 | Cheese Hunter | 1399651053682692208 | 1.1x | 8 | floor(8×0.1) | 1 | **9** | 🟠 Orange |
| 7 | No Role | (none) | 1.0x | 8 | floor(8×0.0) | 0 | **8** | 🟡 Yellow |

---

## ✅ **TEST PROCEDURE FOR EACH ROLE**

### **Step-by-Step:**

1. **Edit tetris-scroll.js:**
   - Change `LOCAL_TEST_ROLE` to desired role name
   - Save file

2. **Refresh Page:**
   - Open http://localhost/public/profile.html
   - Hard refresh (Ctrl+Shift+R) to clear cache

3. **Verify Theme:**
   - Check Tetris canvas frame color
   - Should match expected frame color for role

4. **Play Game:**
   - Start Tetris
   - Clear exactly 4 lines (1 Tetris clear)
   - Note the in-game score

5. **Check Game Over:**
   - Let game end
   - Note final score in modal

6. **Verify Database:**
   - Check console for "Score saved" message
   - Should match game over score (not 10x or 2x)

7. **Record Results:**
   - Fill in test results table
   - Mark PASS or FAIL

---

## 🎯 **FIRST TEST: VIP HOLDER**

### **Setup:**
```javascript
const LOCAL_TEST_ROLE = 'VIP Holder';
```

### **Expected Results:**
- **Frame:** 🟡 Golden glow
- **In-Game Display:** "💰 Tetris Score: $16 DSPOINC (2.0x Role Bonus!)"
- **Game Over Modal:** "You earned $16 DSPOINC"
- **Database:** 16 (NOT 32, NOT 160)
- **Calculation:** 8 base + 8 bonus = 16 ✅

### **What to Watch For:**
- ❌ Score shows 32 → Backend doubling
- ❌ Score shows 160 → Backend multiplying by 10
- ❌ Frame not golden → Theme not applied
- ❌ No role bonus indicator → Display not updated

---

## 🚨 **KNOWN DIFFERENCES FROM SNAKE**

### **Tetris vs Snake Scoring:**

**Snake:**
```javascript
score = baseScore × roleMultiplier
// 10 × 1.5 = 15 (simple multiplication)
```

**Tetris:**
```javascript
score = baseScore + (baseScore × (roleMultiplier - 1))
// 8 + (8 × 0.5) = 8 + 4 = 12 (base + bonus)
```

**Why Different?**
- Tetris: More granular control, shows base + bonus separately
- Snake: Simpler calculation, direct multiplication
- Both are valid approaches, just different implementations

---

## 📝 **TEST RESULTS TABLE**

### **To Be Filled During Testing:**

**Test #1 - VIP Holder (2.0x):**
- Expected: 16 DSPOINC
- In-Game: ___
- Game Over: ___
- Database: ___
- Frame: Golden? ___
- Status: ⏳ PENDING

**Test #2 - Holder (1.5x):**
- Expected: 12 DSPOINC
- In-Game: ___
- Game Over: ___
- Database: ___
- Frame: Silver? ___
- Status: ⏳ PENDING

**Test #3 - Champion (1.4x):**
- Expected: 11 DSPOINC
- In-Game: ___
- Game Over: ___
- Database: ___
- Frame: Red? ___
- Status: ⏳ PENDING

**Test #4 - Season Tester (1.3x):**
- Expected: 10 DSPOINC
- In-Game: ___
- Game Over: ___
- Database: ___
- Frame: 🟢 GREEN? ___
- Status: ⏳ PENDING

**Test #5 - Early Bird (1.2x):**
- Expected: 10 DSPOINC
- In-Game: ___
- Game Over: ___
- Database: ___
- Frame: Blue? ___
- Status: ⏳ PENDING

**Test #6 - Cheese Hunter (1.1x):**
- Expected: 9 DSPOINC
- In-Game: ___
- Game Over: ___
- Database: ___
- Frame: Orange? ___
- Status: ⏳ PENDING

**Test #7 - No Role (1.0x):**
- Expected: 8 DSPOINC
- In-Game: ___
- Game Over: ___
- Database: ___
- Frame: Yellow? ___
- Status: ⏳ PENDING

---

## 🔧 **CONSOLE COMMANDS FOR TESTING**

### **Quick Role Switching:**
```javascript
// VIP Holder
userRoleIDs = ['1332016526848692345']; applyRoleTheme(); console.log('Testing VIP Holder');

// Holder
userRoleIDs = ['1402668301414563971']; applyRoleTheme(); console.log('Testing Holder');

// Champion
userRoleIDs = ['1332017420591697972']; applyRoleTheme(); console.log('Testing Champion');

// Season Tester
userRoleIDs = ['1417279348989497532']; applyRoleTheme(); console.log('Testing Season Tester');

// Early Bird
userRoleIDs = ['1332017614108758148']; applyRoleTheme(); console.log('Testing Early Bird');

// Cheese Hunter
userRoleIDs = ['1399651053682692208']; applyRoleTheme(); console.log('Testing Cheese Hunter');

// No Role
userRoleIDs = []; applyRoleTheme(); console.log('Testing No Role');
```

---

**WORKPLAN CREATED - READY TO START TESTING!** 🧩✅

