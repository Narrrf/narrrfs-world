# 🚀 PHASE 1 REFACTORING - IMPLEMENTATION PLAN

**Date:** December 18, 2025  
**Status:** 📋 **PLANNING COMPLETE - READY FOR IMPLEMENTATION**  
**Phase:** Phase 1 - Quick Wins

---

## ✅ **COMPLETED**

1. ✅ **phoenix.js deleted** (1,907 lines removed) - DONE BY USER
2. ✅ **config-system.js created** - Configuration constants extracted

---

## 📋 **REMAINING TASKS**

### **Task 1: Create audio-system.js** (Estimated: ~800 lines)

**What to Extract:**
- All audio-related functions from main.js
- Background music management
- Sound effects management
- Audio state management
- UI update functions for audio controls

**Dependencies:**
- Requires `audioListener` and `audioLoader` from main.js (passed as constructor params)
- Requires access to `currentLevel`, `isGamePaused`, `optionsMenu` (passed as getters/callbacks)
- Requires access to `playerVelocity`, `onGround` for footstep sounds (passed as getters)

**Functions to Extract:**
1. `loadCharacterAudio()` - Lines 2449-2625
2. `resumeAudioContextIfNeeded()` - Lines 2629-2639
3. `stopFootstepSound()` - Lines 2645-2649
4. `updateFootstepSoundState()` - Lines 2651-2670
5. `playJumpSound()` - Lines 2672-2679
6. `playCheesePlatformSound()` - Lines 2681-2688
7. `playCheeseAimClearSound()` - Lines 2690-2697
8. `playLeverSound()` - Lines 2699-2706
9. `playHiddenSleverSound()` - Lines 2708-2723
10. `playLevelUpSound()` - Lines 2725-2732
11. `playBlockMovedSound()` - Lines 2786-2793
12. `setSoundFxEnabled()` - Lines 2795-2806
13. `updateSoundFxButtons()` - Lines 2808-2818
14. `stopBackgroundMusic()` - Lines 2821-2826
15. `pauseBackgroundMusic()` - Lines 2828-2832
16. `resumeBackgroundMusic()` - Lines 2834-2840
17. `loadBackgroundMusic()` - Lines 2842-2894
18. `playBackgroundMusic()` - Lines 2896-2959
19. `setBackgroundMusicEnabled()` - Lines 2961-2978
20. `setBackgroundMusicVolume()` - Lines 2980-3001
21. `updateBackgroundMusicButtons()` - Lines 3003-3013
22. `updateBackgroundMusicVolumeSlider()` - Lines 3015-3025
23. `ensureBackgroundMusicForCurrentLevel()` - Lines 3027-3055

**Variables to Extract:**
- `soundFxEnabled` - Line 491
- `backgroundMusicEnabled` - Line 502
- `backgroundMusicVolume` - Line 503
- `currentBackgroundMusic` - Line 504
- `backgroundMusicObjects` - Line 505
- All sound effect objects (footstepSound, jumpSound, etc.)

**Audio File Paths:**
- Already extracted to config-system.js

---

### **Task 2: Update main.js to use new modules**

**Changes Required:**

1. **Add imports at top:**
```javascript
import { 
  isProduction, 
  API_BASE_URL, 
  PROFILE_URL,
  LEVEL_IDS,
  LEVEL_MAP_CONFIG,
  BACKGROUND_MUSIC_PATHS,
  initializeDebugFlags,
  // ... other config exports
} from "./config-system.js";

import { AudioSystem } from "./audio-system.js";
```

2. **Remove extracted code:**
- Remove all configuration constants (lines 477-669)
- Remove all audio functions (lines 2449-3055)
- Remove audio variable declarations (lines 491-505, 1470-1477)

3. **Initialize AudioSystem:**
```javascript
// After audioListener and audioLoader are created
const audioSystem = new AudioSystem({
  audioListener,
  audioLoader,
  getCurrentLevel: () => currentLevel,
  getIsGamePaused: () => isGamePaused,
  getOptionsMenu: () => optionsMenu,
  getPlayerVelocity: () => playerVelocity,
  getOnGround: () => onGround
});

// Load character audio
audioSystem.loadCharacterAudio();
```

4. **Replace function calls:**
- Replace `playBackgroundMusic(levelId)` with `audioSystem.playBackgroundMusic(levelId)`
- Replace `stopBackgroundMusic()` with `audioSystem.stopBackgroundMusic()`
- Replace `playJumpSound()` with `audioSystem.playJumpSound()`
- Replace all other audio function calls with `audioSystem.*` equivalents

---

## 📊 **ESTIMATED IMPACT**

### **Lines Removed from main.js:**
- Configuration constants: ~200 lines
- Audio functions: ~600 lines
- Audio variables: ~50 lines
- **Total: ~850 lines**

### **New Files Created:**
- `config-system.js`: ~150 lines
- `audio-system.js`: ~800 lines

### **Net Reduction:**
- **main.js reduced by ~850 lines** (2.6% reduction)
- **Codebase organization improved** (better separation of concerns)

---

## 🎯 **SUCCESS CRITERIA**

- [ ] Game loads without errors
- [ ] Background music plays correctly
- [ ] Sound effects work correctly
- [ ] Audio controls in options menu work
- [ ] No console errors related to audio
- [ ] Configuration constants accessible from config-system.js

---

## 📝 **NEXT STEPS**

1. Create `audio-system.js` with all audio functions
2. Update `main.js` to import and use new modules
3. Test game functionality
4. Update documentation

---

**Document Created:** December 18, 2025  
**Status:** ✅ **PLANNING COMPLETE**  
**Next:** Create audio-system.js module
