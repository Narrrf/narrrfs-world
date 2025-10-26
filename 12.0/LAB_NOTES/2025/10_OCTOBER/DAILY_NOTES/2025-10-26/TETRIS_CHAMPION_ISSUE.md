# 🚨 TETRIS CHAMPION CALCULATION ISSUE

**Date:** October 26, 2025  
**Time:** 19:00  
**Role:** Champion (1.4x)  
**Status:** 🔴 **ISSUE IDENTIFIED**  

---

## 🔍 **USER REPORT**

**What Happened:**
- Cleared 1 normal line → Got **2 DSPOINC** (expected 1.4 or 3)
- Cleared 1 bomb line → Got **13 DSPOINC** (expected 14)
- Total score shown: **$15 DSPOINC**

---

## 🧮 **CALCULATION ANALYSIS**

### **Normal Line Calculation:**
```javascript
baseScore = 1 * 2 = 2 DSPOINC
roleMultiplier = 1.4
roleBonus = Math.floor(2 * (1.4 - 1)) = Math.floor(2 * 0.4) = Math.floor(0.8) = 0
total = 2 + 0 = 2 DSPOINC ✅ MATCHES USER RESULT
```

**Issue:** Math.floor() rounds down 0.8 to 0, so no bonus applied!

### **Bomb Line Calculation:**
```javascript
baseBombScore = 10 DSPOINC
roleMultiplier = 1.4
roleBombBonus = Math.floor(10 * (1.4 - 1)) = Math.floor(10 * 0.4) = Math.floor(4) = 4
total = 10 + 4 = 14 DSPOINC ✅ SHOULD BE 14
```

**Issue:** User got 13 instead of 14!

**Possible Explanation:**
- Previous score was already on screen
- The "13" might be from accumulated scoring across multiple actions
- Need to test from fresh game (score = 0)

---

## 🎯 **THE REAL PROBLEM**

### **Math.floor() Kills Small Bonuses:**

**For 1-2 lines with fractional multipliers:**
- 1 line: 2 DSPOINC × 0.4 = **0.8 → rounds to 0** ❌
- 2 lines: 4 DSPOINC × 0.4 = **1.6 → rounds to 1** ⚠️
- 3 lines: 6 DSPOINC × 0.4 = **2.4 → rounds to 2** ⚠️
- 4 lines: 8 DSPOINC × 0.4 = **3.2 → rounds to 3** ✅

**This is why we're testing with 4 lines - it's the minimum for reliable bonus!**

---

## 🔧 **POTENTIAL SOLUTIONS**

### **Option 1: Use Math.round() instead of Math.floor()**
```javascript
// Current (Math.floor - rounds DOWN):
roleBonus = Math.floor(baseScore * (roleMultiplier - 1));
// 2 * 0.4 = 0.8 → Math.floor(0.8) = 0 ❌

// Proposed (Math.round - rounds to nearest):
roleBonus = Math.round(baseScore * (roleMultiplier - 1));
// 2 * 0.4 = 0.8 → Math.round(0.8) = 1 ✅
```

### **Option 2: Use Math.ceil() (rounds UP)**
```javascript
roleBonus = Math.ceil(baseScore * (roleMultiplier - 1));
// 2 * 0.4 = 0.8 → Math.ceil(0.8) = 1 ✅
```

### **Option 3: Keep Math.floor() but accept fractional losses**
- Accept that small clears don't get bonuses
- Only 4+ lines get meaningful bonuses
- This is mathematically fair but less rewarding

---

## 📊 **COMPARISON WITH SNAKE**

### **Snake Uses Direct Multiplication:**
```javascript
// Snake
baseScore = 10
totalScore = Math.floor(10 * 1.4) = Math.floor(14) = 14 ✅
// Always gets full multiplier benefit
```

### **Tetris Uses Base + Bonus:**
```javascript
// Tetris
baseScore = 2 (1 line)
roleBonus = Math.floor(2 * 0.4) = Math.floor(0.8) = 0 ❌
total = 2 + 0 = 2
// Loses fractional bonus!
```

---

## 🎯 **RECOMMENDED FIX**

### **Change Tetris to use Math.round():**

**Why Math.round() is best:**
- **Fair:** Players get bonuses for small clears
- **Balanced:** Doesn't over-reward (like Math.ceil)
- **Consistent:** Predictable rounding behavior
- **Player-Friendly:** More rewarding experience

**Implementation:**
```javascript
// Line 1199 - Bomb bonus
const roleBombBonus = Math.round(baseBombScore * (roleMultiplier - 1));

// Line 1249 - Regular line bonus
const roleBonus = Math.round(baseScore * (roleMultiplier - 1));
```

**New Results with Math.round():**
- 1 line × 1.4x: 2 + round(0.8) = 2 + 1 = **3 DSPOINC** ✅
- Bomb × 1.4x: 10 + round(4) = 10 + 4 = **14 DSPOINC** ✅

---

## 🚨 **DECISION NEEDED**

### **Should we:**
1. ✅ **Change to Math.round()** - Better player experience, fair rounding
2. ❌ **Keep Math.floor()** - Accept that small bonuses = 0
3. ❌ **Change to Math.ceil()** - Over-rewarding

**Recommendation: Change to Math.round() for fairness!**

---

**AWAITING DECISION ON MATH.FLOOR() VS MATH.ROUND()** 🎯

