# 🐛 KNOWN ISSUES - SPACE INVADERS SEASON 5

**Date:** November 2, 2025  
**Status:** 📋 **TRACKING MINOR ISSUES**  

---

## 🧀 **ISSUE #1: Giant Cheese Boss Not Dropping Hearts**

**Status:** ✅ **FIXED!**  
**Priority:** 🟡 **MEDIUM - QUALITY OF LIFE**  
**Severity:** ⚠️ **MINOR - GAME STILL PLAYABLE**  

### **User Report:**
*"did not get hearts at the end meanwhile"* (after defeating Wave 8 boss)  
*"it seems the cheese enemy drops it but it does not stay or fall down it disappears"*

### **Root Cause:**
**Hearts were spawning but disappearing instantly!**

- Boss `die()` function correctly created hearts (line 1764-1773) ✅
- Hearts were added to `powerUps` array ✅
- **BUT:** `spawnNewWave()` was called **immediately** after boss death (line 7209)
- Hearts didn't have time to fall before next wave started ❌

### **Fix #1 Applied (Line 7199-7215):**
```javascript
// ✅ Added 3-second delay for heart collection
setTimeout(() => {
  console.log('🧀 Hearts collected! Advancing to next wave...');
  spawnNewWave();
}, 3000); // 3 second delay
```

**Result:** Hearts appeared but **still disappeared** - needed second fix!

---

### **Fix #2 Applied (Line 1771) - THE REAL FIX:**
```javascript
// ❌ OLD: Used wrong property name
powerUps.push({
  type: 'life',
  vy: 1 + Math.random() // WRONG! updatePowerUps() looks for 'speed'
});

// ✅ NEW: Use correct property name
powerUps.push({
  type: 'life',
  speed: 2 // Matches other power-ups - will fall down!
});
```

**Root Cause:**
- Boss created hearts with `vy: 1` property
- `updatePowerUps()` function uses `powerUp.y += powerUp.speed;` (line 3164)
- Heart had no `speed` property → `undefined` → **didn't move!**
- Heart stayed frozen at boss Y position, then disappeared

### **Impact:**
- ✅ Hearts now **fall down at speed 2** (like other power-ups)
- ✅ Hearts have **3 seconds** to be collected
- ✅ Player can see and collect hearts
- ✅ Wave transition feels epic (celebration time!)

### **Testing:**
- Defeat Wave 8 boss
- Watch for hearts dropping (1 heart expected at Wave 8)
- Collect hearts before 3-second timer expires
- Next wave spawns after hearts collected

---

**Status:** ✅ **HEART DROP SYSTEM FIXED!**

---

## 📊 **OTHER POTENTIAL ISSUES**

### **Issue #2: Achievement Thresholds (May Need Adjustment)**
**Status:** 🔮 **FUTURE - MONITOR IN SEASON 5**  

With 10:1 conversion, some achievements may be too hard:
- "Getting Started" - 2,500 DSPOINC (now requires 25,000 pre-conversion)
- "Mid-Range Mastery" - 7,500 DSPOINC (now requires 75,000 pre-conversion)
- "High Roller" - 15,000 DSPOINC (now requires 150,000 pre-conversion)

**Action:** Monitor unlock rates in Season 5, adjust thresholds if needed.

---

## ✅ **RESOLVED ISSUES**

### **Issue #215-222: Wave 8 Boss Battle Bugs**
**Status:** ✅ **FIXED - ALL 8 BUGS RESOLVED**

- ✅ Game freeze
- ✅ Instant death
- ✅ Undefined variables (finalScore, playerBullets, playerLives, weakPointScore)
- ✅ Boss attacks above screen
- ✅ Bullets don't damage boss

**Documented in:** `BUG_CRITICAL_WAVE_8_FREEZE_FIX.md`

---

**Last Updated:** November 2, 2025 - 03:20  
**Next Review:** After local testing of 10:1 conversion

