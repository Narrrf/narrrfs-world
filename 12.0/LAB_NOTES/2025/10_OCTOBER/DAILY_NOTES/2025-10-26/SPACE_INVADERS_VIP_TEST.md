# 🚀 SPACE INVADERS VIP TEST - QUICK INSTRUCTIONS

**Role:** VIP Holder (2.0x)  
**Expected:** 20 DSPOINC for 10 invaders  
**Frame:** 🟡 Gold  

---

## ✅ **READY TO TEST**

### **What's Changed:**
1. ✅ Local testing override added (line 215)
2. ✅ Season Tester theme changed to green
3. ✅ Green CSS added to profile.html
4. ✅ File saved and ready

### **Current Test Role:**
```javascript
const LOCAL_TEST_ROLE = 'VIP Holder'; // Line 215
```

---

## 🎮 **HOW TO TEST**

1. **Open Space Invaders:**
   - Go to: `http://localhost/public/space-cheese-invaders.html`

2. **Start Game:**
   - Click "Start Game" button

3. **Kill 10 Invaders:**
   - Shoot until you've killed 10 regular invaders
   - Watch the score counter

4. **Report Results:**
   - Total DSPOINC earned
   - Frame color (should be gold)
   - Any issues

---

## 📊 **EXPECTED CALCULATION**

```
baseScore = 1 per invader
roleMultiplier = 2.0 (VIP Holder)
totalScore = Math.floor(1 * 2.0) = 2 per invader
10 invaders × 2 = 20 DSPOINC ✅
```

---

## 🟡 **WHAT TO LOOK FOR**

- ✅ Gold frame/border on canvas
- ✅ Score shows multiplier bonus in display
- ✅ Total = 20 DSPOINC after 10 kills
- ✅ No visual glitches

---

**REFRESH PAGE AND START TESTING!** 🚀🟡

