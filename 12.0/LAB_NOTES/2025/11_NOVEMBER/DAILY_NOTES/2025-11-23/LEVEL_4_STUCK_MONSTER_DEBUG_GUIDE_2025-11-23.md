# 🐛 LEVEL 4 STUCK MONSTER DEBUG GUIDE — November 23, 2025

**Date:** November 23, 2025  
**Issue:** Player stuck at 11/30 monsters - can't find a monster  
**Status:** ✅ **DEBUG TOOLS ADDED**

---

## 🚨 **THE PROBLEM**

Player is stuck at 11/30 monsters in Level 4. This means:
- **Progress:** 11 monsters defeated out of 30 total
- **Current Wave:** Wave 4 (monsters 10-12 should be active)
- **Issue:** Cannot find the remaining monster(s) to shoot

**Possible Causes:**
1. Monster spawned but is invisible (rendering issue)
2. Monster spawned outside visible area
3. Monster moved out of bounds
4. Monster is stuck in a wall or underground
5. Monster count is off due to a bug

---

## ✅ **SOLUTION: DEBUG TOOLS ADDED**

### **1. Debug Monster Status (Console Command)**

**Open browser console (F12) and type:**

```javascript
window.debugLevel4Monsters()
```

**What it shows:**
- Current progress (e.g., 11/30 defeated)
- Current wave number
- Monsters in current wave (e.g., 1/3)
- **List of ALL active monsters** with:
  - Name (e.g., "Birb", "Cat", "Chicken")
  - Visibility status
  - Position (X, Y, Z coordinates)
  - Distance from player
  - Health status
  - Whether it's in the scene

**Example Output:**
```
🔍 [DEBUG] Level 4 Monster Status:
  Progress: 11/30 defeated
  Current Wave: 4
  Monsters in Current Wave: 1/3
  Active Monsters: 2
  Monster 0: { name: 'Birb', visible: true, position: { x: '5.23', y: '1.60', z: '962.45' }, distance: '18.52' }
  Monster 1: { name: 'Cat', visible: false, position: { x: '-50.12', y: '1.20', z: '890.33' }, distance: '89.45' }
```

---

### **2. Teleport to Monster (Console Command)**

**If you find a monster that's far away, teleport to it:**

```javascript
window.teleportToLevel4Monster(0)  // Teleport to monster index 0
```

**Usage:**
1. First run `window.debugLevel4Monsters()` to see all monsters
2. Find the monster index (0, 1, or 2)
3. Run `window.teleportToLevel4Monster(index)` to teleport to it
4. The player will teleport 5 units above the monster so you can see it

---

### **3. Force Kill Monster (Console Command)**

**If you find a stuck monster, force kill it:**

```javascript
window.killLevel4Monster(0)  // Kill monster index 0
```

**Usage:**
1. First run `window.debugLevel4Monsters()` to see all monsters
2. Find the stuck monster index
3. Run `window.killLevel4Monster(index)` to force kill it
4. This will count as defeating the monster and update progress

---

### **4. Respawn Current Wave (Console Command)**

**If the wave is stuck, force respawn it:**

```javascript
window.forceRespawnLevel4Wave()
```

**What it does:**
- Clears all current monsters
- Resets wave progress
- Respawns the current wave
- Useful if monsters are invisible or stuck

---

### **5. Skip to Next Wave (Emergency Bypass)**

**If a wave is completely stuck, skip to the next one:**

```javascript
window.skipToNextLevel4Wave()
```

**⚠️ WARNING:** This bypasses the current wave. Use only as last resort!

**What it does:**
- Marks current wave as complete
- Clears all monsters
- Advances to next wave
- Spawns the next wave

---

## 🔧 **AUTOMATIC SAFETY CHECK**

**Added automatic stuck monster detection:**

- **Detects:** When monsters exist but none are visible
- **Time Limit:** 10 seconds stuck
- **Action:** Automatically clears stuck monsters and respawns the wave
- **No action needed:** Works automatically in background

---

## 📋 **STEP-BY-STEP TROUBLESHOOTING**

### **If You're Stuck at 11/30:**

1. **Open Console (F12)**

2. **Check Monster Status:**
   ```javascript
   window.debugLevel4Monsters()
   ```

3. **Look for:**
   - How many active monsters exist
   - Which monsters are visible
   - Monster positions and distances

4. **If a monster is far away:**
   ```javascript
   window.teleportToLevel4Monster(0)  // Replace 0 with monster index
   ```

5. **If a monster is stuck/invisible:**
   ```javascript
   window.killLevel4Monster(0)  // Replace 0 with monster index
   ```

6. **If no monsters are visible but they exist:**
   ```javascript
   window.forceRespawnLevel4Wave()
   ```

7. **If completely stuck:**
   ```javascript
   window.skipToNextLevel4Wave()
   ```

---

## 🎯 **COMMON SCENARIOS**

### **Scenario 1: Monster Spawned Outside Arena**
- **Symptom:** Monster distance is very high (e.g., >100 units)
- **Solution:** `window.teleportToLevel4Monster(index)` to find it, or `window.killLevel4Monster(index)` to kill it

### **Scenario 2: Monster is Invisible**
- **Symptom:** `visible: false` in debug output
- **Solution:** `window.forceRespawnLevel4Wave()` to respawn the wave

### **Scenario 3: No Active Monsters**
- **Symptom:** Debug shows "No active monsters found"
- **Solution:** `window.forceRespawnLevel4Wave()` to respawn, or check if wave should be complete

### **Scenario 4: Wave Progress Mismatch**
- **Symptom:** Progress shows 11/30 but wave shows 2/3 monsters defeated
- **Solution:** This is normal - wave 4 has monsters 10-12, so 1/3 means monster 11 is left

---

## 📝 **NOTES**

- **All debug functions** are available in the browser console
- **No need to restart** the game - functions work immediately
- **Progress is preserved** - killing/respawning doesn't lose progress
- **Automatic safety check** runs every frame - will auto-fix after 10 seconds

---

## 🔍 **TECHNICAL DETAILS**

### **Debug Functions Added:**
- `window.debugLevel4Monsters()` - List all active monsters
- `window.teleportToLevel4Monster(index)` - Teleport to monster
- `window.killLevel4Monster(index)` - Force kill monster
- `window.forceRespawnLevel4Wave()` - Respawn current wave
- `window.skipToNextLevel4Wave()` - Skip to next wave

### **Automatic Safety Check:**
- Detects when monsters exist but none are visible
- Auto-clears and respawns after 10 seconds
- Prevents permanent stuck states

---

**LAB NOTE CREATED:** November 23, 2025  
**STATUS:** ✅ **DEBUG TOOLS READY**  
**NEXT:** 🎯 **USE DEBUG COMMANDS TO FIND STUCK MONSTER**

---

## 🔧 **ADDITIONAL FIXES APPLIED (November 23, 2025 - Evening)**

### **Skeleton Error Fix:**
- **Issue:** Cat model (Blob) has broken skeleton causing `Cannot read properties of null (reading 'update')` errors
- **Fix:** Replaced Cat model in Wave 4 with Demon model (known working model)
- **Impact:** Wave 4 now uses Birb, Demon, Chicken instead of Birb, Cat, Chicken
- **Status:** ✅ **FIXED**

### **Render Error Handling:**
- **Issue:** Skeleton errors during rendering crash the game loop
- **Fix:** Wrapped `renderer.render()` in try-catch with emergency skeleton fix
- **Impact:** Game continues even if skeleton errors occur
- **Status:** ✅ **FIXED**

### **Enhanced Skeleton Cleanup:**
- **Improvement:** Final skeleton cleanup pass before adding to scene
- **Improvement:** Runtime skeleton fix in `updateLevel4Monsters()` 
- **Improvement:** Complete removal of SkinnedMesh type when skeleton is broken
- **Status:** ✅ **ENHANCED**

---

## 🚨 **KNOWN ISSUE: Cat Model (Blob)**

**Model:** `/textures/3d models/Monster 1/Blob/glTF/Cat.gltf`  
**Problem:** Broken skeleton causes rendering crashes  
**Status:** ❌ **NOT USED IN WAVE 4** (replaced with Demon)  
**Future:** May need to fix Cat model or use alternative in other waves

---

**UPDATED:** November 23, 2025 - Evening  
**STATUS:** ✅ **SKELETON ERRORS FIXED, MODEL REPLACED**

