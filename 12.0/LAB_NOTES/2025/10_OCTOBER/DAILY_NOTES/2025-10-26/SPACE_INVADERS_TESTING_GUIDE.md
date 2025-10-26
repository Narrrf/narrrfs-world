# 🚀 SPACE INVADERS LOCAL TESTING GUIDE

**Date:** October 26, 2025  
**Purpose:** Local testing guide for Space Invaders role multipliers  
**Method:** Hardcoded role override (no console needed!)  

---

## 🧪 **HOW TO TEST DIFFERENT ROLES**

### **Step 1: Edit the File**
Open `public/scripts/space-cheese-invaders.js`

### **Step 2: Find Line 215**
Look for this section:
```javascript
const LOCAL_TEST_ROLE = 'VIP Holder'; // ← CHANGE THIS
```

### **Step 3: Change the Role**
Replace `'VIP Holder'` with one of these:
- `'VIP Holder'` - 2.0x multiplier, gold frame
- `'Holder'` - 1.5x multiplier, silver frame
- `'Champion'` - 1.4x multiplier, red frame
- `'Season Tester'` - 1.3x multiplier, green frame
- `'Early Bird'` - 1.2x multiplier, blue frame
- `'Cheese Hunter'` - 1.1x multiplier, orange frame

### **Step 4: Save and Refresh**
- Save the file
- Refresh the Space Invaders page: `http://localhost/public/space-cheese-invaders.html`

### **Step 5: Test**
- Start the game
- Kill 10 invaders
- Note the total DSPOINC score
- Verify the frame color

---

## 📋 **TESTING CHECKLIST**

For each role test, verify:
- [ ] Correct frame color appears
- [ ] Multiplier applies correctly
- [ ] Score matches expected amount
- [ ] No visual glitches

---

## 🎯 **EXPECTED RESULTS (10 INVADERS)**

| Role | Multiplier | Expected Score | Frame Color |
|------|-----------|----------------|-------------|
| VIP Holder | 2.0x | 20 DSPOINC | 🟡 Gold |
| Holder | 1.5x | 15 DSPOINC | ⚪ Silver |
| Champion | 1.4x | 14 DSPOINC | 🔴 Red |
| Season Tester | 1.3x | 13 DSPOINC | 🟢 Green |
| Early Bird | 1.2x | 12 DSPOINC | 🔵 Blue |
| Cheese Hunter | 1.1x | 11 DSPOINC | 🟠 Orange |

---

## 🚨 **KNOWN ISSUES TO WATCH FOR**

### **1. Math.floor() Issue:**
Like Tetris, if baseScore = 1 and Math.floor() is used:
- Fractional bonuses may round to 0
- Example: Math.floor(1 * 1.4) = 1 (no bonus!)

**If this happens:** Change Math.floor() to Math.round() (like Tetris fix)

### **2. Rainbow Theme:**
Season Tester now uses green theme (changed from rainbow).
- Verify green frame appears
- No animation glitches
- Stable solid color

---

## 📝 **TESTING NOTES**

Record results in `SPACE_INVADERS_TEST_RESULTS.md`:
- Actual DSPOINC earned
- Frame color observed
- Any issues or bugs
- Pass/Fail status

---

**START WITH VIP HOLDER - KILL 10 INVADERS AND REPORT!** 🚀✅

