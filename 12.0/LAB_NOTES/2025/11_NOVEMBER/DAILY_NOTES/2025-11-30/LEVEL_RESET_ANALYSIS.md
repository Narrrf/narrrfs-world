# 🔍 LEVEL RESET ANALYSIS — GOD MODE vs NORMAL MODE

**Date:** November 30, 2025  
**Issue:** Levels don't reset completely when started via GOD mode menu  
**Status:** 🔄 **IN PROGRESS**

---

## 🎯 PROBLEM SUMMARY

When using GOD mode menu (L key) to warp to levels, the levels don't reset completely:
- Collision detection doesn't work (Level 1)
- Monsters/elements don't spawn correctly (Level 3, 4)
- Old elements interfere with new level

**Root Cause:** Levels need consistent reset logic for both GOD mode warps and normal mode restarts.

---

## 📋 LEVEL-BY-LEVEL ANALYSIS

### **Level 1:**
- **Current Status:** ✅ Has `warpToLevel1()` function (just created)
- **Issues Found:**
  - Collision mesh may not be in scene
  - Trigger block visual may not reset
  - Player position may not reset correctly
- **Required Resets:**
  - ✅ Collision mesh verification and re-add to scene
  - ✅ Trigger block visual position reset
  - ✅ All riddle states reset
  - ✅ Player position reset to spawn
  - ✅ Instanced meshes visibility check

### **Level 2:**
- **Current Status:** ✅ Has `warpToLevel2()` function
- **Needs Review:** Ensure all elements reset properly

### **Level 3:**
- **Current Status:** ✅ Has `warpToLevel3()` function
- **Fixed:** Monster spawning after Step 0
- **Needs Review:** Ensure complete reset on warp

### **Level 4:**
- **Current Status:** ✅ Has `warpToLevel4()` function
- **Fixed:** Element spawning initialization
- **Needs Review:** Ensure complete reset on warp

### **Level 5:**
- **Current Status:** ✅ Has `warpToLevel5()` function
- **Needs Review:** Ensure complete reset on warp

---

## 🔧 SOLUTION APPROACH

1. **Create/Update `warpToLevelX()` functions** for all levels
2. **Ensure consistent reset logic** in both `warpToLevelX()` and `restartLevelX()`
3. **Verify collision meshes** are in scene and have valid geometry
4. **Reset all level-specific state** (monsters, weapons, timers, etc.)
5. **Reset player position** to level spawn point
6. **Apply level environment** (background, fog, music)

---

## ✅ COMPLETED

- ✅ Created `warpToLevel1()` function
- ✅ Updated GOD mode menu to use `warpToLevel1()`
- ✅ Added collision mesh protection in `cleanupAllLevels()`

---

## 🔄 NEXT STEPS

1. Test Level 1 via GOD mode - verify collision works
2. Review all other levels for similar issues
3. Create comprehensive reset checklist for each level
4. Test all levels via GOD mode to ensure proper reset

---

**Last Updated:** November 30, 2025 (Morning Session)

