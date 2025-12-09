# 🖥️ GUI/HUD SYSTEM EXTRACTION PLAN

**Date:** December 6, 2025  
**Status:** 🔄 **IN PROGRESS**  
**Purpose:** Extract GUI/HUD system from `main.js` into `gui-system.js` (Phase 2 of 3)

---

## 📊 CURRENT PROGRESS (December 6, 2025)

### ✅ **COMPLETED COMPONENTS:**
1. ✅ Base structure and initialization (~100 lines)
2. ✅ Score HUD (~20 lines)
3. ✅ Debug Overlay (~60 lines)
4. ✅ Crosshair (~40 lines)
5. ✅ Toast Notifications (~80 lines)
6. ✅ Mad Mode Notification (~70 lines)
7. ✅ Pause Menu (~250 lines)

**Current File Size:** ~750 lines  
**Components Added:** 7/14  
**Progress:** ~50% complete

### ⏳ **REMAINING COMPONENTS:**
1. ⏳ Options Menu (~500 lines) - **IN PROGRESS**
2. ⏳ Game Over Screen (~330 lines)
3. ⏳ Completion Screens (~800 lines - Level 1, 2, 3, 4)
4. ⏳ Character Selection Menu (~110 lines)
5. ⏳ Level Selector Screen (~100 lines)
6. ⏳ Level 4 Progress HUD (~200 lines)
7. ⏳ Level 4 Hit Indicator (~50 lines)
8. ⏳ Level 2 Inspection HUD (~50 lines)
9. ⏳ Sound/Music Controls (~200 lines)

**Remaining:** ~2,340 lines  
**Target File Size:** ~3,000-3,200 lines

---

## 🎯 OBJECTIVE

Extract all GUI/HUD elements from `three.js/main.js` into a new modular `three.js/gui-system.js` file, following the proven architecture pattern established by `player-controls.js`, `player-model.js`, `sky-system.js`, and `grass-system.js`.

---

## ✅ PHASE 1 STATUS: COMPLETE

- ✅ **Player Model Module** (`player-model.js`) - WORKING
  - Model loading ✅
  - Animation system ✅
  - Visibility management ✅
  - All 6 Mouse animations working ✅

---

## 📋 GUI/HUD COMPONENTS TO EXTRACT

### **1. Score HUD** 📊
- **Function:** `createScoreHud()`
- **Location:** ~line 6160-6178
- **Purpose:** Displays "Cheese Collected: X"
- **Lines:** ~20 lines

### **2. Debug Overlay** 🐛
- **Functions:** `createDebugOverlay()`, `refreshDebugOverlay()`
- **Location:** ~line 6180-6234
- **Purpose:** Shows environment info, debug state, camera mode
- **Lines:** ~55 lines

### **3. Pause Menu** ⏸️
- **Functions:** `getPauseMenu()`, `showPauseMenu()`, `updatePausePlayerInfo()`
- **Location:** ~line 6286-7000+ (large section)
- **Purpose:** Pause menu with resume, options, restart, back to portal
- **Includes:** Landscape mode controls, player info, DSPOINC display
- **Lines:** ~700+ lines

### **4. Options Menu** ⚙️
- **Functions:** `getOptionsMenu()`, `showOptionsMenu()`, `updateOptionsMenu()`
- **Location:** ~line 7000+ (large section)
- **Purpose:** Game options (sound, music, camera, VR, landscape mode)
- **Lines:** ~500+ lines

### **5. Toast Notifications** 🔔
- **Function:** `showRiddleToast()`
- **Location:** Need to find
- **Purpose:** Display temporary messages (riddle hints, achievements, etc.)
- **Lines:** ~50-100 lines

### **6. Level 4 Hit Indicator** 🎯
- **Functions:** `createLevel4HitIndicator()`, `showLevel4HitIndicator()`, `updateLevel4HitIndicator()`
- **Location:** ~line 2440-2490
- **Purpose:** Visual feedback when hitting targets in Level 4
- **Lines:** ~50 lines

### **7. Level 4 Weapon HUD** 🔫
- **Function:** `updateLevel4WeaponHUD()`
- **Location:** ~line 2731-2743
- **Purpose:** Display weapon name, ammo, heat, slot
- **Lines:** ~15 lines

### **8. Sound/Music Control Buttons** 🔊
- **Functions:** `updateSoundFxButtons()`, `updateBackgroundMusicButtons()`, `updateBackgroundMusicVolumeSlider()`
- **Location:** ~line 2056-2263
- **Purpose:** Update button states for sound/music controls
- **Lines:** ~200 lines

---

## 🏗️ PROPOSED CLASS STRUCTURE

```javascript
export class GUISystem {
  constructor(config) {
    this.config = config;
    
    // DOM elements
    this.container = document.body;
    this.scoreHud = null;
    this.debugOverlay = null;
    this.pauseMenu = null;
    this.optionsMenu = null;
    this.toastContainer = null;
    
    // Level-specific elements
    this.level4HitIndicator = null;
    this.level4WeaponHUD = null;
    
    // State
    this.isPaused = false;
    this.currentToast = null;
    this.toastQueue = [];
    
    // Callbacks (dependency injection)
    this.config = {
      getPlayerName: config.getPlayerName || (() => 'Player'),
      getDSPOINC: config.getDSPOINC || (() => 0),
      getCameraMode: config.getCameraMode || (() => 0),
      getDebugState: config.getDebugState || (() => ({})),
      onResume: config.onResume || (() => {}),
      onRestart: config.onRestart || (() => {}),
      onBackToPortal: config.onBackToPortal || (() => {}),
      onShowOptions: config.onShowOptions || (() => {}),
      onToggleSoundFx: config.onToggleSoundFx || (() => {}),
      onToggleMusic: config.onToggleMusic || (() => {}),
      onVolumeChange: config.onVolumeChange || (() => {}),
      // ... more callbacks
    };
  }
  
  // Initialization
  initialize() { ... }
  
  // Score HUD
  createScoreHud() { ... }
  updateScore(score) { ... }
  
  // Debug Overlay
  createDebugOverlay() { ... }
  refreshDebugOverlay() { ... }
  update(delta) { ... } // Update debug overlay each frame
  
  // Pause Menu
  createPauseMenu() { ... }
  showPauseMenu() { ... }
  hidePauseMenu() { ... }
  updatePausePlayerInfo() { ... }
  
  // Options Menu
  createOptionsMenu() { ... }
  showOptionsMenu() { ... }
  hideOptionsMenu() { ... }
  updateOptionsMenu() { ... }
  
  // Toast Notifications
  showToast(message, options) { ... }
  showRiddleToast(message, options) { ... }
  hideToast() { ... }
  
  // Level-specific HUD
  createLevel4HitIndicator() { ... }
  showLevel4HitIndicator() { ... }
  updateLevel4HitIndicator(delta) { ... }
  createLevel4WeaponHUD() { ... }
  updateLevel4WeaponHUD(weaponName, ammo, maxAmmo, heat, slot) { ... }
  
  // Sound/Music Controls
  updateSoundFxButtons(enabled) { ... }
  updateBackgroundMusicButtons(enabled) { ... }
  updateBackgroundMusicVolumeSlider(volume) { ... }
  
  // Cleanup
  dispose() { ... }
  disposeLevelHUD(levelId) { ... }
}
```

---

## 📊 ESTIMATED CODE SIZE

### **Current GUI Code in main.js:**
- Score HUD: ~20 lines
- Debug Overlay: ~55 lines
- Pause Menu: ~700 lines
- Options Menu: ~500 lines
- Toast System: ~100 lines
- Level 4 HUD: ~65 lines
- Sound Controls: ~200 lines
- **Total:** ~1,640 lines

### **After Extraction:**
- `gui-system.js`: ~1,200-1,500 lines (with proper structure)
- `main.js`: Reduced by ~1,640 lines

---

## 🔗 DEPENDENCIES

### **External Dependencies:**
- DOM (document, body)
- No Three.js dependencies (pure DOM manipulation)

### **Internal Dependencies (Callbacks):**
- `getPlayerName()` - Get current player name
- `getDSPOINC()` - Get current DSPOINC balance
- `getCameraMode()` - Get current camera mode (0=1st, 1=3rd, 2=joystick)
- `getDebugState()` - Get debug state object
- `onResume()` - Resume game callback
- `onRestart()` - Restart level callback
- `onBackToPortal()` - Navigate to portal callback
- `onShowOptions()` - Show options menu callback
- `onToggleSoundFx()` - Toggle sound effects callback
- `onToggleMusic()` - Toggle background music callback
- `onVolumeChange()` - Music volume change callback

---

## 🔄 INTEGRATION POINTS

### **In main.js:**
```javascript
import { GUISystem } from "./gui-system.js";

let guiSystem = null;

function initializeGUISystem() {
  guiSystem = new GUISystem({
    getPlayerName: () => playerDisplayName,
    getDSPOINC: () => currentTotalDspoinc,
    getCameraMode: () => cameraMode,
    getDebugState: () => debugState,
    onResume: () => togglePause(false),
    onRestart: () => window.location.reload(),
    onBackToPortal: () => window.location.href = PROFILE_URL,
    onShowOptions: () => showOptionsMenu(),
    onToggleSoundFx: (enabled) => { soundFxEnabled = enabled; },
    onToggleMusic: (enabled) => { backgroundMusicEnabled = enabled; },
    onVolumeChange: (volume) => { backgroundMusicVolume = volume; }
  });
  guiSystem.initialize();
}

// In animate loop:
if (guiSystem) {
  guiSystem.update(delta);
}

// Replace direct calls:
// OLD: showRiddleToast("Message");
// NEW: guiSystem.showRiddleToast("Message");

// OLD: getPauseMenu();
// NEW: guiSystem.showPauseMenu();
```

---

## 📋 EXTRACTION CHECKLIST

### **Step 1: Create Module File**
- [ ] Create `three.js/gui-system.js`
- [ ] Add class structure with constructor
- [ ] Add basic initialization method
- [ ] Export class

### **Step 2: Extract Score HUD**
- [ ] Move `createScoreHud()` to module
- [ ] Add `updateScore()` method
- [ ] Test score display

### **Step 3: Extract Debug Overlay**
- [ ] Move `createDebugOverlay()` to module
- [ ] Move `refreshDebugOverlay()` to module
- [ ] Add `update(delta)` method for per-frame updates
- [ ] Test debug overlay

### **Step 4: Extract Toast System**
- [ ] Find and move `showRiddleToast()` to module
- [ ] Add toast queue system
- [ ] Test toast notifications

### **Step 5: Extract Pause Menu**
- [ ] Move `getPauseMenu()` to module
- [ ] Move `showPauseMenu()` to module
- [ ] Move `updatePausePlayerInfo()` to module
- [ ] Replace callbacks with dependency injection
- [ ] Test pause menu

### **Step 6: Extract Options Menu**
- [ ] Move `getOptionsMenu()` to module
- [ ] Move `showOptionsMenu()` to module
- [ ] Move `updateOptionsMenu()` to module
- [ ] Replace callbacks with dependency injection
- [ ] Test options menu

### **Step 7: Extract Level 4 HUD**
- [ ] Move `createLevel4HitIndicator()` to module
- [ ] Move `showLevel4HitIndicator()` to module
- [ ] Move `updateLevel4HitIndicator()` to module
- [ ] Move `updateLevel4WeaponHUD()` to module
- [ ] Test Level 4 HUD

### **Step 8: Extract Sound Controls**
- [ ] Move `updateSoundFxButtons()` to module
- [ ] Move `updateBackgroundMusicButtons()` to module
- [ ] Move `updateBackgroundMusicVolumeSlider()` to module
- [ ] Test sound controls

### **Step 9: Update main.js**
- [ ] Import `GUISystem` class
- [ ] Initialize GUI system
- [ ] Replace all direct GUI calls with `guiSystem.*` methods
- [ ] Remove old GUI code from main.js
- [ ] Test all levels

### **Step 10: Testing**
- [ ] Score HUD displays correctly
- [ ] Debug overlay updates correctly
- [ ] Pause menu works correctly
- [ ] Options menu works correctly
- [ ] Toast notifications appear correctly
- [ ] Level 4 HUD works correctly
- [ ] Sound controls work correctly
- [ ] All UI elements dispose correctly

---

## ⚠️ CRITICAL CONSIDERATIONS

### **1. Callback Dependencies:**
- **Problem:** GUI needs access to game state (player name, DSPOINC, etc.)
- **Solution:** Use dependency injection via config callbacks
- **Pattern:** `getPlayerName: () => playerDisplayName`

### **2. Event Handlers:**
- **Problem:** Buttons need to trigger game actions
- **Solution:** Pass action callbacks in config
- **Pattern:** `onResume: () => togglePause(false)`

### **3. Level-Specific Elements:**
- **Problem:** Different levels have different HUD elements
- **Solution:** Create/remove level-specific elements on level entry/exit
- **Pattern:** `guiSystem.createLevelHUD('level4')` / `guiSystem.disposeLevelHUD('level4')`

### **4. State Synchronization:**
- **Problem:** GUI needs to stay in sync with game state
- **Solution:** Update methods called from main.js on state changes
- **Pattern:** `guiSystem.updateScore(cheeseCount)` when score changes

### **5. Memory Management:**
- **Problem:** DOM elements need proper cleanup
- **Solution:** Implement `dispose()` method
- **Pattern:** Remove all DOM elements, clear references

---

## 🎯 SUCCESS CRITERIA

### **Technical Success:**
- [ ] All GUI elements work identically to before
- [ ] No visual changes
- [ ] All callbacks work correctly
- [ ] No memory leaks
- [ ] Proper cleanup on level changes

### **Architecture Success:**
- [ ] Follows same pattern as `player-controls.js` and `player-model.js`
- [ ] Clear separation of concerns
- [ ] Easy to find and modify GUI code
- [ ] Well-documented API
- [ ] Proper dependency injection

### **Code Quality:**
- [ ] main.js reduced by ~1,640 lines
- [ ] GUI code is organized and maintainable
- [ ] Easy to add new GUI elements
- [ ] Supports decades of development

---

## 📚 REFERENCE IMPLEMENTATIONS

### **Player Controls Pattern:**
- **File:** `three.js/player-controls.js`
- **Size:** 675 lines
- **Pattern:** ES module, class-based, callback-based config

### **Player Model Pattern:**
- **File:** `three.js/player-model.js`
- **Size:** 767 lines
- **Pattern:** ES module, class-based, dependency injection

**Follow the same proven pattern for GUI System!**

---

## 🚀 NEXT STEPS

1. **Review this plan** with user
2. **Start extraction** (incremental, test after each step)
3. **Extract Score HUD first** (simplest)
4. **Extract Debug Overlay** (simple)
5. **Extract Toast System** (medium)
6. **Extract Pause Menu** (complex)
7. **Extract Options Menu** (complex)
8. **Extract Level 4 HUD** (level-specific)
9. **Extract Sound Controls** (medium)
10. **Test thoroughly** in all levels
11. **Update documentation**

---

**Plan Created:** December 6, 2025  
**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Priority:** 🔄 **PHASE 2 OF 3**  
**Estimated Time:** 2-3 sessions (if done incrementally)

🧀 **This will make main.js significantly smaller and more maintainable!** 🧀

