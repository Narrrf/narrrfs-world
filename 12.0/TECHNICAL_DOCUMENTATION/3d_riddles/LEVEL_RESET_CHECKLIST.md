# ✅ LEVEL RESET CHECKLIST — GOD MODE & NORMAL MODE

**Purpose:** Ensure all levels reset completely when started via GOD mode menu or normal mode  
**Date:** November 30, 2025  
**Status:** 🔄 **VERIFICATION IN PROGRESS**

---

## 🎯 CRITICAL RESET REQUIREMENTS

Every level MUST reset ALL of the following when warping/restarting:

### **1. Level State:**
- ✅ `currentLevel` set correctly
- ✅ Level group visible and in scene
- ✅ Environment applied (background, fog, music)
- ✅ Player position reset to spawn
- ✅ Camera mode reset (first-person)

### **2. Collision Detection:**
- ✅ Collision mesh exists and in scene
- ✅ Collision mesh has valid geometry and boundsTree
- ✅ Player doesn't fall through ground

### **3. Level-Specific Elements:**
- ✅ Monsters cleared and reset
- ✅ Weapons/items reset
- ✅ Triggers/blocks reset to initial state
- ✅ Timers reset
- ✅ Completion flags reset

### **4. Riddle State:**
- ✅ All riddle steps reset
- ✅ Trigger blocks visible/reset
- ✅ Timers reset to 0
- ✅ Intro toasts reset (can show again)

---

## 📋 LEVEL-BY-LEVEL CHECKLIST

### **LEVEL 1: Cheese Temple**

**Reset Function:** `warpToLevel1()` ✅

**Required Resets:**
- [x] Collision mesh verified and in scene
- [x] Instanced meshes visible
- [x] Trigger block visual reset (position, visibility)
- [x] Bear trap reset
- [x] All riddle states reset (Step 0, 1, 2, 3, 4)
- [x] Floating cheese visible
- [x] Player position reset to spawn
- [x] Environment applied
- [ ] **TEST:** Collision works when warping from GOD mode

---

### **LEVEL 2: The Spawn (Matrix Construct)**

**Reset Function:** `warpToLevel2()` ✅

**Required Resets:**
- [ ] Level group in scene and visible
- [ ] Trigger block reset
- [ ] Lever reset
- [ ] Preview models reset
- [ ] Weapon gallery reset
- [ ] All riddle states reset
- [ ] Player position reset to spawn
- [ ] Environment applied
- [ ] **TEST:** All elements reset correctly

---

### **LEVEL 3: The Hunt**

**Reset Function:** `warpToLevel3()` ✅

**Required Resets:**
- [x] Level group in scene and visible
- [x] Monster spawn reset (verified working)
- [x] Trigger block reset
- [x] Portal reset
- [x] Moving walls reset
- [x] All riddle states reset
- [x] Intro toast reset (`introShown` flag)
- [ ] **TEST:** Complete reset on GOD mode warp

---

### **LEVEL 4: The First Shot**

**Reset Function:** `warpToLevel4()` ✅

**Required Resets:**
- [x] Level group in scene and visible
- [x] Weapon system reset (verified working)
- [x] Monster waves reset
- [x] Cheese waves reset
- [x] Trigger block reset
- [x] All riddle states reset
- [x] Heat/overload system reset
- [x] Intro toast reset (`introShown` flag)
- [ ] **TEST:** Complete reset on GOD mode warp

---

### **LEVEL 5: The Walk**

**Reset Function:** `warpToLevel5()` ✅

**Required Resets:**
- [ ] Level group in scene and visible
- [ ] Collision mesh reset
- [ ] Monster spawn system reset
- [ ] Timer reset
- [ ] All riddle states reset
- [ ] Player position reset to spawn
- [ ] Environment applied
- [ ] **TEST:** Complete reset on GOD mode warp

---

## 🔧 COMMON PATTERNS

### **Standard Warp Function Pattern:**

```javascript
function warpToLevelX() {
  if (currentLevel === LEVEL_IDS.LEVELX) return;
  
  // 1. Cleanup ALL levels
  cleanupAllLevels();
  
  // 2. Build level if needed
  if (!levelXState.built) {
    buildLevelX();
  }
  
  // 3. Reset level progress
  resetLevelXProgress();
  
  // 4. Set current level
  currentLevel = LEVEL_IDS.LEVELX;
  
  // 5. Ensure group is in scene and visible
  if (!scene.children.includes(levelXState.group)) {
    scene.add(levelXState.group);
  }
  levelXState.group.visible = true;
  
  // 6. Apply environment
  applyLevelEnvironment(LEVEL_IDS.LEVELX);
  
  // 7. Reset player position
  setPlayerFeetPosition(levelXConfig.spawnPosition.clone());
  
  // 8. Reset camera
  setCameraMode(0);
  
  // 9. Start music
  ensureBackgroundMusicForCurrentLevel(true);
  
  // 10. Show intro toast (if applicable)
  showLevelXIntroToast();
}
```

### **Standard Restart Function Pattern:**

```javascript
function restartLevelX() {
  // Either call warpToLevelX() or duplicate logic
  // Key: Ensure restart works even if already in level
  const wasInLevelX = (currentLevel === LEVEL_IDS.LEVELX);
  if (wasInLevelX) {
    currentLevel = LEVEL_IDS.LEVELY; // Temporary change
  }
  warpToLevelX();
}
```

---

## 📝 TESTING PROCEDURE

For each level:

1. **Start in Level 1 (normal mode)**
2. **Press L to open GOD mode menu**
3. **Select the level to test**
4. **Verify:**
   - Level loads correctly
   - Collision works (don't fall through ground)
   - All elements spawn correctly
   - Timers/triggers work
   - No old elements interfere
5. **Press R to restart the level**
6. **Verify:**
   - Same as above - everything resets correctly

---

## ✅ VERIFICATION STATUS

- [ ] **Level 1:** Testing in progress
- [ ] **Level 2:** Needs review
- [ ] **Level 3:** Verified working (needs final GOD mode test)
- [ ] **Level 4:** Verified working (needs final GOD mode test)
- [ ] **Level 5:** Needs review

---

**Last Updated:** November 30, 2025

