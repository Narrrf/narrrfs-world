# 🧪 LOCAL TESTING GUIDE - SNAKE MULTIPLIER FIX

**Bug:** #104 - Snake Role Multiplier Fix  
**Date:** October 26, 2025  
**Status:** ✅ Fixed, ready for local testing  

---

## 🧪 **HOW TO TEST DIFFERENT ROLES LOCALLY**

### **Step 1: Open Browser Console**

Press `F12` to open DevTools, then click "Console" tab.

---

### **Step 2: Set Test Role**

**To test as HOLDER (Silver Frame, 1.5x):**
```javascript
localStorage.setItem('snake_test_role', 'Holder');
location.reload();
```

**To test as CHAMPION (Red Frame, 1.4x):**
```javascript
localStorage.setItem('snake_test_role', 'Champion');
location.reload();
```

**To test as SEASON TESTER (Rainbow Frame, 1.3x):**
```javascript
localStorage.setItem('snake_test_role', 'Season Tester');
location.reload();
```

**To test as VIP HOLDER (Golden Frame, 2.0x - default):**
```javascript
localStorage.removeItem('snake_test_role');
location.reload();
```

---

### **Step 3: Play Snake and Verify**

**Expected Results:**

**Holder (1.5x - Silver Frame):**
- Eat 1 cheese → **15 DSPOINC** ✅
- Eat 2 cheese → **30 DSPOINC** ✅
- Eat 10 cheese → **150 DSPOINC** ✅

**Champion (1.4x - Red Frame):**
- Eat 1 cheese → **14 DSPOINC** ✅
- Eat 2 cheese → **28 DSPOINC** ✅
- Eat 10 cheese → **140 DSPOINC** ✅

**Season Tester (1.3x - Rainbow Frame):**
- Eat 1 cheese → **13 DSPOINC** ✅
- Eat 2 cheese → **26 DSPOINC** ✅
- Eat 10 cheese → **130 DSPOINC** ✅

**VIP Holder (2.0x - Golden Frame):**
- Eat 1 cheese → **20 DSPOINC** ✅
- Eat 2 cheese → **40 DSPOINC** ✅
- Eat 10 cheese → **200 DSPOINC** ✅

---

## ✅ **VERIFICATION CHECKLIST**

**Visual Checks:**
- [ ] Frame color matches role (Silver for Holder, Golden for VIP, etc.)
- [ ] Snake and food colors match role theme
- [ ] Controls section theme matches role

**Scoring Checks:**
- [ ] First cheese gives correct DSPOINC amount
- [ ] Score accumulates correctly
- [ ] Role bonus indicator shows correctly (e.g., "1.5x Role Bonus!")
- [ ] Final score on game over modal is correct

**Database Checks:**
- [ ] Open browser console, play a game, check for:
  ```
  💾 Snake score saved: {...}
  ```
- [ ] Verify score in database is correct

---

## 🐛 **IF TEST FAILS:**

**Check Console for Errors:**
- Any JavaScript errors?
- Is the role being detected correctly?
- Is the multiplier being calculated?

**Quick Debug Commands:**
```javascript
// Check current test role
console.log('Test Role:', localStorage.getItem('snake_test_role'));

// Check actual roles loaded
console.log('Roles:', snakeUserRoles);

// Check primary role
console.log('Primary Role:', getSnakePrimaryRole());

// Check multiplier
console.log('Multiplier:', getSnakeRoleScoreMultiplier());
```

---

## 📊 **TEST SCENARIOS**

### **Scenario 1: Holder Role (Silver - 1.5x)**
```javascript
localStorage.setItem('snake_test_role', 'Holder');
location.reload();
// Play Snake, eat 10 cheese
// Expected: 150 DSPOINC total
```

### **Scenario 2: Champion Role (Red - 1.4x)**
```javascript
localStorage.setItem('snake_test_role', 'Champion');
location.reload();
// Play Snake, eat 10 cheese
// Expected: 140 DSPOINC total
```

### **Scenario 3: Season Tester (Rainbow - 1.3x)**
```javascript
localStorage.setItem('snake_test_role', 'Season Tester');
location.reload();
// Play Snake, eat 10 cheese
// Expected: 130 DSPOINC total
```

### **Scenario 4: VIP Holder (Golden - 2.0x)**
```javascript
localStorage.removeItem('snake_test_role');
location.reload();
// Play Snake, eat 10 cheese
// Expected: 200 DSPOINC total
```

---

## 🧀 **TESTING QUICK REFERENCE**

### **Role List:**
- `'Holder'` → 1.5x (15 DSPOINC per cheese)
- `'Champion'` → 1.4x (14 DSPOINC per cheese)
- `'Season Tester'` → 1.3x (13 DSPOINC per cheese)
- `'Early Bird'` → 1.2x (12 DSPOINC per cheese)
- `'Cheese Hunter'` → 1.1x (11 DSPOINC per cheese)
- Default/VIP → 2.0x (20 DSPOINC per cheese)

### **Expected Frame Colors:**
- 🟡 **Golden:** VIP Holder (2.0x)
- ⚪ **Silver:** Holder (1.5x) ← **YOUR MAIN TEST**
- 🔴 **Red:** Champion (1.4x)
- 🌈 **Rainbow:** Season Tester (1.3x)
- 🔵 **Blue:** Early Bird (1.2x)
- 🟠 **Orange:** Cheese Hunter (1.1x)

---

**Ready to test! Use the commands above to switch roles locally!** 🎯

