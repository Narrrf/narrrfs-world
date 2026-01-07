# 🔍 Hardcoded Paths Audit - January 6, 2026

**Status:** 🔄 **IN PROGRESS**  
**Priority:** 🚨 **CRITICAL - MUST FIX BEFORE PRODUCTION PUSH**

---

## 📋 **SUMMARY**

Found **444+ hardcoded paths** across all modules that need to use `resolveAssetPath()`.

---

## 🎯 **MODULES TO FIX**

### **1. main.js** 🚨 **CRITICAL - 400+ paths**
**Status:** ⚠️ **NEEDS FIXING**

**Hardcoded Paths Found:**
- **Weapon paths:** `LEVEL4_WEAPON_PATH`, `LEVEL4_WEAPON_SLOTS` (lines 2604-2618)
- **Monster paths:** `LEVEL4_MONSTER_WAVE_QUEUE` (lines 2622-2688) - 30+ paths
- **Monster preview paths:** `LEVEL2_MONSTER_PREVIEWS` (lines 2729-2740) - 10 paths
- **Monster spawn paths:** `LEVEL3_MONSTER_PATHS` (lines 2751-2760) - 10 paths
- **Weapon slot paths:** Lines 2762-2766
- **Texture paths:** Lines 4117-4118 (cheese bullet textures)
- **Player model paths:** `CHARACTER_OPTIONS` (lines 5045-5056) - 2 paths
- **Player animation paths:** Lines 5383-5388 - 6 paths
- **Monster model paths:** Line 5511
- **Map paths:** Lines 18284, 19068
- **Tree paths:** Lines 33361, 33481, 33593, 33704 - 4 paths
- **Plant paths:** Lines 33971, 34160 - 2 paths
- **Bear trap paths:** Lines 31797-31798, 34403 - 3 paths
- **Butterfly path:** Line 33800
- **Level JSON paths:** Lines 35696, 35756 - 2 paths (one already fixed at 35468)

**Total:** ~400+ hardcoded paths

---

### **2. chest-system.js** 🚨 **CRITICAL**
**Status:** ⚠️ **NEEDS FIXING**

**Hardcoded Paths Found:**
- **Chest model path:** Line 890 - `/textures/3d models/chest2/Chest2.glb`
- **Chest sound path:** Lines 2367-2369 - `/sounds/SFX/chest.mp3` (has environment detection but should use resolveAssetPath)
- **Lowercase path fallback:** Lines 1463, 1563 - `/textures/3d models/chest2/chest2.glb`

**Total:** 4 hardcoded paths

**Fix Required:**
- Add `resolveAssetPath` parameter to ChestSystem constructor
- Pass `resolveAssetPath` from main.js when initializing ChestSystem
- Update all hardcoded paths to use `this.resolveAssetPath()`

---

### **3. weapon-system.js** ⚠️ **PARTIAL**
**Status:** ⚠️ **NEEDS FIXING**

**Hardcoded Paths Found:**
- **Texture paths:** Lines 1796-1797 - `/textures/blocks/cheese-bullet-small.png`, `/textures/blocks/yellow-cheese.png`

**Total:** 2 hardcoded paths

**Note:** `weapon-system.js` already has `resolveAssetPath` passed to it (line 468), but only uses it for audio paths. Need to use it for texture paths too.

---

### **4. audio-system.js** ⚠️ **NEEDS FIXING**
**Status:** ⚠️ **NEEDS FIXING**

**Hardcoded Paths Found:**
- **SFX paths:** Lines 408, 425, 442 - `/sounds/SFX/SF13-Gun-future.mp3`, `/sounds/SFX/bear-trap-103800.mp3`, `/sounds/SFX/hidden-slever.mp3`

**Total:** 3 hardcoded paths

**Note:** `audio-system.js` is loaded from `main.js` which already resolves paths for most audio. These 3 paths are loaded directly in AudioSystem and need to be fixed.

**Fix Required:**
- Add `resolveAssetPath` parameter to AudioSystem constructor
- Pass `resolveAssetPath` from main.js when initializing AudioSystem
- Update all hardcoded paths to use `this.resolveAssetPath()`

---

### **5. main.js - Additional Paths** 🚨 **CRITICAL**
**Status:** ⚠️ **NEEDS FIXING**

**Additional Hardcoded Paths:**
- **Level JSON:** Line 35696 - `/models/cheese-temple/level1.json` (one already fixed at 35468)
- **Level JSON:** Line 35756 - `/models/cheese-temple/level1.json`

**Total:** 2 additional paths

---

## 🔧 **FIX PRIORITY**

### **Priority 1: Critical Modules (Must Fix)**
1. ✅ **gui-system.js** - Already fixed (background images)
2. ⚠️ **chest-system.js** - 4 paths (chest models, sounds)
3. ⚠️ **weapon-system.js** - 2 paths (textures)
4. ⚠️ **audio-system.js** - 3 paths (SFX)

### **Priority 2: Main.js Paths (High Volume)**
1. ⚠️ **main.js** - 400+ paths (weapons, monsters, trees, plants, maps, textures, animations)

---

## 📝 **FIX STRATEGY**

### **Step 1: Fix Module Constructors**
- Add `resolveAssetPath` parameter to:
  - `ChestSystem` constructor
  - `AudioSystem` constructor (if not already present)
- Pass `resolveAssetPath` from `main.js` when initializing these modules

### **Step 2: Fix Module Internal Paths**
- Update all hardcoded paths in modules to use `this.resolveAssetPath()`
- Start with chest-system.js, weapon-system.js, audio-system.js

### **Step 3: Fix main.js Paths**
- Replace all hardcoded paths in main.js with `resolveAssetPath()` calls
- Focus on:
  1. Weapon paths (LEVEL4_WEAPON_SLOTS, LEVEL4_WEAPON_PATH)
  2. Monster paths (LEVEL4_MONSTER_WAVE_QUEUE, LEVEL3_MONSTER_PATHS)
  3. Player model paths (CHARACTER_OPTIONS, animation paths)
  4. Tree/plant/bear trap paths
  5. Map paths
  6. Texture paths
  7. Level JSON paths

---

## ✅ **VERIFICATION CHECKLIST**

After fixes:
- [ ] All chest models load correctly
- [ ] All weapon models load correctly
- [ ] All monster models load correctly
- [ ] All player models/animations load correctly
- [ ] All tree/plant models load correctly
- [ ] All bear trap models load correctly
- [ ] All map models load correctly
- [ ] All textures load correctly
- [ ] All sounds load correctly
- [ ] All level JSON files load correctly
- [ ] No 404 errors in browser console
- [ ] Test locally: `http://localhost/public/three.js/3d-riddle-game.html`
- [ ] Test production: `https://narrrfs.world/public/three.js/3d-riddle-game.html`

---

## 📊 **STATISTICS**

- **Total Hardcoded Paths Found:** 444+
- **Modules Affected:** 5 (main.js, chest-system.js, weapon-system.js, audio-system.js, gui-system.js)
- **gui-system.js:** ✅ Fixed (8 paths)
- **Remaining:** 436+ paths

---

**Next Steps:** Start fixing modules one by one, beginning with chest-system.js (smallest, easiest to verify).

