# 🧪 Production Testing Plan - January 6, 2026

**Date:** January 6, 2026  
**Push:** `89ecbf7` - Three.js Path Resolution & Boss Spawning Fixes  
**Status:** 🚀 **DEPLOYED - READY FOR TESTING**  
**Production URL:** `https://narrrfs.world/public/three.js/3d-riddle-game.html`

---

## 🎯 **TESTING OBJECTIVES**

### **Primary Goals:**
1. ✅ Verify all asset paths resolve correctly (no 404 errors)
2. ✅ Verify Level 6 boss spawning (Phoenix and Alien Spider)
3. ✅ Verify keyboard controls work correctly
4. ✅ Verify level selector functionality
5. ✅ Verify general game functionality

---

## 📋 **PRE-TESTING CHECKLIST**

### **Before Starting Tests:**
- [ ] Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
- [ ] Clear browser cache if needed
- [ ] Open browser DevTools Console (F12)
- [ ] Open browser Network tab (to monitor asset loading)
- [ ] Check game URL: `https://narrrfs.world/public/three.js/3d-riddle-game.html`

---

## 🔍 **SECTION 1: PATH RESOLUTION VERIFICATION**

### **1.1 Browser Console - Initial Load Check**
**Action:** Open game and check console for path resolution logs

**Expected Results:**
- [ ] ✅ No 404 errors for asset files
- [ ] ✅ Console shows: `🔍 [PATH RESOLVE]` logs with correct paths
- [ ] ✅ All paths show: `/public/three.js/public/...` format
- [ ] ✅ No paths starting with `/textures/` or `/sounds/` (missing prefix)

**What to Look For:**
```
✅ GOOD: 🔍 [PATH RESOLVE] "textures/..." → "/public/three.js/public/textures/..."
❌ BAD: GET https://narrrfs.world/textures/... 404 (Not Found)
❌ BAD: GET https://narrrfs.world/audio/... 404 (Not Found)
```

---

### **1.2 Asset Loading - Textures**
**Action:** Start game and check Network tab

**Expected Results:**
- [ ] ✅ Grass textures load: `/public/three.js/public/textures/grass/grass.jpg`
- [ ] ✅ Cloud textures load: `/public/three.js/public/textures/grass/cloud.jpg`
- [ ] ✅ Background images load: `/public/three.js/public/textures/backgrounds/...`
- [ ] ✅ All texture requests return `200 OK` status

**Test Steps:**
1. Start game
2. Check Network tab filter: `img` or `textures`
3. Verify all textures load successfully

---

### **1.3 Asset Loading - Audio Files**
**Action:** Start game and check audio file loading

**Expected Results:**
- [ ] ✅ Character audio loads: `/public/three.js/public/audio/character/...`
- [ ] ✅ Background music loads: `/public/three.js/public/sounds/music/...`
- [ ] ✅ Gameplay audio loads: `/public/three.js/public/audio/gameplay/...`
- [ ] ✅ Weapon audio loads: `/public/three.js/public/sounds/invaders/weapons/...`
- [ ] ✅ All audio requests return `200 OK` status

**Test Steps:**
1. Start game
2. Check Network tab filter: `media` or `audio`
3. Verify all audio files load successfully
4. Verify footstep sounds play when walking
5. Verify background music plays

---

### **1.4 Asset Loading - 3D Models**
**Action:** Load different levels and check model loading

**Expected Results:**
- [ ] ✅ Chest models load: `/public/three.js/public/textures/3d models/chest2/...`
- [ ] ✅ Weapon models load: `/public/three.js/public/textures/3d models/Fire Weapons 1/...`
- [ ] ✅ Character model loads: `/public/three.js/public/textures/3d models/...`
- [ ] ✅ Level JSON files load: `/public/three.js/public/models/cheese-temple/level1.json`
- [ ] ✅ All model requests return `200 OK` status

**Test Steps:**
1. Start game and load Level 1
2. Check Network tab filter: `glb`, `fbx`, or `json`
3. Verify chests render correctly
4. Load Level 4+ and verify weapons render
5. Verify level JSON files load

---

## 🐉 **SECTION 2: LEVEL 6 BOSS SPAWNING**

### **2.1 Phoenix Boss Spawning**
**Action:** Navigate to Level 6 and verify Phoenix boss spawns

**Expected Results:**
- [ ] ✅ Phoenix boss model loads: `/public/three.js/public/textures/3d models/phoenix2/Dragons1.glb`
- [ ] ✅ Phoenix boss appears at spawn position (X: 20, Y: 0, Z: 0)
- [ ] ✅ Phoenix textures load correctly (Red color variation)
- [ ] ✅ Phoenix animations work (idle, fly, etc.)
- [ ] ✅ Phoenix HUD appears (health bar, behavior pattern display)
- [ ] ✅ No 404 errors in console for Phoenix assets

**Test Steps:**
1. Enable God Mode (if needed)
2. Press `L` key to open level selector
3. Select Level 6
4. Wait for level to load
5. Check console for Phoenix loading logs
6. Verify Phoenix spawns and is visible
7. Verify Phoenix HUD displays

**Console Logs to Check:**
```
✅ GOOD: 🔥 [LEVEL 6] Loading Dragon GLB model from: /public/three.js/public/textures/3d models/phoenix2/Dragons1.glb
✅ GOOD: ✅ [LEVEL 6] Phoenix boss 2.0 instance created
❌ BAD: GET https://narrrfs.world/textures/3d models/phoenix2/Dragons1.glb 404
```

---

### **2.2 Alien Spider Boss Spawning**
**Action:** Navigate to Level 6 and verify Alien Spider boss spawns

**Expected Results:**
- [ ] ✅ Alien Spider model loads: `/public/three.js/public/textures/3d models/Alien Spider 1/AFC_03/AFC_03.fbx`
- [ ] ✅ Alien Spider appears at spawn position (X: -20, Y: 0, Z: 0)
- [ ] ✅ Alien Spider textures load correctly
- [ ] ✅ Alien Spider animations load: `/public/three.js/public/textures/3d models/Alien Spider 1/AFC_03/...@*.fbx`
- [ ] ✅ Alien Spider animations play (idle, walk, attack, etc.)
- [ ] ✅ No 404 errors in console for Alien Spider assets

**Test Steps:**
1. In Level 6 (already loaded from Phoenix test)
2. Check console for Alien Spider loading logs
3. Verify Alien Spider spawns and is visible
4. Verify animations play correctly
5. Check Network tab for animation files

**Console Logs to Check:**
```
✅ GOOD: 🕷️ [LEVEL 6] Loading Alien Spider FBX model from: /public/three.js/public/textures/3d models/Alien Spider 1/AFC_03/AFC_03.fbx
✅ GOOD: ✅ [LEVEL 6] Alien Spider boss instance created
❌ BAD: GET https://narrrfs.world/textures/3d models/Alien Spider 1/... 404
```

---

### **2.3 Boss Texture Path Resolution**
**Action:** Verify boss texture paths resolve correctly

**Expected Results:**
- [ ] ✅ Phoenix color variation textures load: `/public/three.js/public/textures/3d models/phoenix2/Red/...`
- [ ] ✅ Phoenix eye textures load: `/public/three.js/public/textures/3d models/phoenix2/Eye/Red/...`
- [ ] ✅ Alien Spider base textures load: `/public/three.js/public/textures/3d models/Alien Spider 1/AFC_03/...`
- [ ] ✅ All boss textures use resolved paths (no hardcoded `/textures/...`)

**Test Steps:**
1. Load Level 6
2. Check Network tab filter: `textures` + `phoenix2` or `Alien Spider`
3. Verify all texture paths include `/public/three.js/public/` prefix
4. Check console for texture loading logs

---

## ⌨️ **SECTION 3: KEYBOARD CONTROLS VERIFICATION**

### **3.1 E Key - Chest Interaction**
**Action:** Test chest interaction with E key

**Expected Results:**
- [ ] ✅ E key opens chests when near them
- [ ] ✅ Chest interaction prompt appears when near chest
- [ ] ✅ Chest opens and awards DSPOINC
- [ ] ✅ Works in all levels (1-6)

**Test Steps:**
1. Load Level 1
2. Approach chest (chest_001 or chest_002)
3. Press `E` key
4. Verify chest opens
5. Verify DSPOINC reward message appears
6. Test in multiple levels

---

### **3.2 P Key - Pause Toggle**
**Action:** Test pause functionality with P key

**Expected Results:**
- [ ] ✅ P key pauses game (first press)
- [ ] ✅ P key unpauses game (second press)
- [ ] ✅ Works even when already paused
- [ ] ✅ Pause menu appears/disappears correctly

**Test Steps:**
1. Start game
2. Press `P` key → Verify game pauses
3. Press `P` key again → Verify game unpauses
4. Repeat multiple times to verify consistency

---

### **3.3 L Key - Level Selector (God Mode Only)**
**Action:** Test level selector with L key (God Mode required)

**Expected Results:**
- [ ] ✅ L key opens level selector (God Mode only)
- [ ] ✅ Level selector shows all 6 levels
- [ ] ✅ Selecting a level loads that level correctly
- [ ] ✅ Does not work in non-God Mode (no action)

**Test Steps:**
1. Enable God Mode (if needed)
2. Press `L` key
3. Verify level selector menu appears
4. Select different level (e.g., Level 3)
5. Verify correct level loads
6. Test in non-God Mode (should not work)

---

### **3.4 G Key - Riddle Jump Cycle (God Mode Only)**
**Action:** Test riddle jump cycling with G key

**Expected Results:**
- [ ] ✅ G key cycles through riddle jumps (God Mode only)
- [ ] ✅ Works in levels with riddles (1-4)
- [ ] ✅ Does not work in non-God Mode
- [ ] ✅ Console shows riddle jump cycle logs

**Test Steps:**
1. Enable God Mode
2. Load Level 1 (or any level with riddles)
3. Press `G` key multiple times
4. Verify riddle jumps cycle
5. Check console for riddle cycle logs

---

### **3.5 N Key - Alien Spider Behavior Cycle (Level 6, God Mode Only)**
**Action:** Test Alien Spider behavior cycling with N key

**Expected Results:**
- [ ] ✅ N key cycles Alien Spider behaviors (Level 6, God Mode only)
- [ ] ✅ Works only in Level 6
- [ ] ✅ Does not work in other levels
- [ ] ✅ Console shows behavior pattern change logs

**Test Steps:**
1. Enable God Mode
2. Load Level 6
3. Verify Alien Spider spawned
4. Press `N` key multiple times
5. Verify Alien Spider behavior changes
6. Check console for behavior pattern logs
7. Test in other levels (should not work)

---

### **3.6 B Key - Phoenix Behavior Cycle (Level 6, God Mode Only)**
**Action:** Test Phoenix behavior cycling with B key

**Expected Results:**
- [ ] ✅ B key cycles Phoenix behaviors (Level 6, God Mode only)
- [ ] ✅ Works only in Level 6
- [ ] ✅ Does not work in other levels
- [ ] ✅ Console shows behavior pattern change logs
- [ ] ✅ Phoenix HUD updates with pattern number (X/15)

**Test Steps:**
1. Enable God Mode
2. Load Level 6
3. Verify Phoenix spawned
4. Press `B` key multiple times
5. Verify Phoenix behavior changes
6. Verify HUD shows pattern count (1/15, 2/15, etc.)
7. Check console for behavior pattern logs
8. Test in other levels (should not work)

---

## 🎯 **SECTION 4: LEVEL SELECTOR FUNCTIONALITY**

### **4.1 Level Selector Access**
**Action:** Access level selector via GUI or L key

**Expected Results:**
- [ ] ✅ Level selector accessible from main menu (God Mode)
- [ ] ✅ Level selector accessible via L key (God Mode)
- [ ] ✅ All 6 levels listed (LEVEL1 - LEVEL6)
- [ ] ✅ Level selector does not appear in non-God Mode

**Test Steps:**
1. Enable God Mode
2. Start game or press `L` key
3. Verify level selector appears
4. Verify all 6 levels are listed
5. Test in non-God Mode (should not appear)

---

### **4.2 Level Selection**
**Action:** Select different levels from selector

**Expected Results:**
- [ ] ✅ Selecting Level 1 loads Level 1 correctly
- [ ] ✅ Selecting Level 2 loads Level 2 correctly
- [ ] ✅ Selecting Level 3 loads Level 3 correctly
- [ ] ✅ Selecting Level 4 loads Level 4 correctly
- [ ] ✅ Selecting Level 5 loads Level 5 correctly
- [ ] ✅ Selecting Level 6 loads Level 6 correctly
- [ ] ✅ Selected level starts immediately (does not default to Level 1)

**Test Steps:**
1. Open level selector
2. Select each level (1-6) one by one
3. Verify correct level loads each time
4. Verify level loads immediately (no defaulting to Level 1)

---

## 🎮 **SECTION 5: GENERAL GAME FUNCTIONALITY**

### **5.1 Game Initialization**
**Action:** Start game and verify initialization

**Expected Results:**
- [ ] ✅ Game loads without errors
- [ ] ✅ Loading screen appears
- [ ] ✅ Loading screen completes (100%)
- [ ] ✅ Main menu appears after loading
- [ ] ✅ Character selection works
- [ ] ✅ Game starts correctly after character selection

**Test Steps:**
1. Open game URL
2. Wait for loading screen
3. Verify loading completes
4. Select character
5. Verify game starts

---

### **5.2 Movement and Controls**
**Action:** Test basic player movement

**Expected Results:**
- [ ] ✅ WASD keys move player correctly
- [ ] ✅ Mouse controls camera rotation
- [ ] ✅ Space bar jumps
- [ ] ✅ Shift runs
- [ ] ✅ Movement feels smooth and responsive

**Test Steps:**
1. Start game
2. Test all movement keys
3. Test camera rotation
4. Test jump and run

---

### **5.3 Asset Rendering**
**Action:** Verify all game assets render correctly

**Expected Results:**
- [ ] ✅ Grass renders correctly (Level 1)
- [ ] ✅ Trees render correctly (Level 1)
- [ ] ✅ Chests render correctly (all levels)
- [ ] ✅ Weapons render correctly (Level 4+)
- [ ] ✅ Character model renders correctly
- [ ] ✅ All textures display correctly (no missing textures)

**Test Steps:**
1. Load Level 1
2. Verify grass and trees visible
3. Verify chests visible
4. Load Level 4+
5. Verify weapons visible
6. Check for missing textures or white/missing models

---

### **5.4 Audio Playback**
**Action:** Verify audio plays correctly

**Expected Results:**
- [ ] ✅ Background music plays (after user interaction)
- [ ] ✅ Footstep sounds play when walking
- [ ] ✅ Jump sound plays when jumping
- [ ] ✅ Chest opening sound plays
- [ ] ✅ Weapon shooting sounds play (Level 4+)
- [ ] ✅ Audio volume controls work

**Test Steps:**
1. Start game
2. Click anywhere (to enable audio context)
3. Walk around → Verify footstep sounds
4. Jump → Verify jump sound
5. Open chest → Verify chest sound
6. Shoot weapon (Level 4+) → Verify shooting sound

---

## 🐛 **SECTION 6: ERROR CHECKING**

### **6.1 Console Error Check**
**Action:** Monitor browser console for errors

**Expected Results:**
- [ ] ✅ No 404 errors for assets
- [ ] ✅ No JavaScript errors (ReferenceError, TypeError, etc.)
- [ ] ✅ No path resolution warnings
- [ ] ✅ No missing function errors

**Test Steps:**
1. Open browser console
2. Start game and play for 5-10 minutes
3. Check console for any errors
4. Document any errors found

**Common Errors to Watch For:**
```
❌ 404 Not Found - Asset path issues
❌ ReferenceError: resolveAssetPath is not defined - Missing function
❌ TypeError: Cannot read property '...' of null - Null reference
❌ Uncaught SyntaxError - Code syntax errors
```

---

### **6.2 Network Request Check**
**Action:** Monitor Network tab for failed requests

**Expected Results:**
- [ ] ✅ All asset requests return `200 OK`
- [ ] ✅ No failed requests (red status codes)
- [ ] ✅ No slow-loading assets (check timing)
- [ ] ✅ All paths use correct format

**Test Steps:**
1. Open Network tab
2. Start game
3. Filter by failed requests (status 4xx, 5xx)
4. Check for slow requests (>5 seconds)
5. Verify all successful requests use correct paths

---

## 📊 **SECTION 7: PERFORMANCE CHECK**

### **7.1 Frame Rate**
**Action:** Monitor game frame rate

**Expected Results:**
- [ ] ✅ Stable frame rate (50-60 FPS)
- [ ] ✅ No significant frame drops
- [ ] ✅ Smooth gameplay experience

**Test Steps:**
1. Enable FPS counter (if available)
2. Play game for 5-10 minutes
3. Monitor frame rate
4. Check for frame drops during boss fights (Level 6)

---

### **7.2 Loading Times**
**Action:** Measure level loading times

**Expected Results:**
- [ ] ✅ Level 1 loads in reasonable time (<30 seconds)
- [ ] ✅ Level 6 loads in reasonable time (<60 seconds)
- [ ] ✅ Loading screen progresses smoothly

**Test Steps:**
1. Time level loading for each level
2. Verify loading screen shows progress
3. Check for hanging on loading screen

---

## ✅ **SECTION 8: COMPARISON - LOCAL VS PRODUCTION**

### **8.1 Path Resolution Comparison**
**Action:** Compare local and production path resolution

**Local Expected:** `/public/three.js/public/...`  
**Production Expected:** `/public/three.js/public/...` (SAME)

**Check:**
- [ ] ✅ Production paths match local paths
- [ ] ✅ Both environments use same path format
- [ ] ✅ No environment-specific path differences

---

### **8.2 Functionality Comparison**
**Action:** Compare functionality between local and production

**Check:**
- [ ] ✅ Boss spawning works same in both environments
- [ ] ✅ Keyboard controls work same in both environments
- [ ] ✅ Level selector works same in both environments
- [ ] ✅ Asset loading works same in both environments

---

## 📝 **SECTION 9: DOCUMENTATION & REPORTING**

### **9.1 Test Results Summary**
**Action:** Document all test results

**Create Report:**
- [ ] ✅ Test date and time
- [ ] ✅ Browser and version
- [ ] ✅ Production URL tested
- [ ] ✅ Pass/Fail status for each test section
- [ ] ✅ Errors found (if any)
- [ ] ✅ Screenshots of issues (if any)
- [ ] ✅ Console logs for errors (if any)

---

### **9.2 Issues Found**
**Action:** Document any issues discovered

**For Each Issue:**
- [ ] ✅ Issue description
- [ ] ✅ Steps to reproduce
- [ ] ✅ Expected behavior
- [ ] ✅ Actual behavior
- [ ] ✅ Console errors (if any)
- [ ] ✅ Screenshots (if applicable)
- [ ] ✅ Severity (Critical, High, Medium, Low)

---

## 🎯 **PRIORITY TESTING ORDER**

### **🔴 CRITICAL (Test First):**
1. ✅ Section 1: Path Resolution Verification
2. ✅ Section 2: Level 6 Boss Spawning
3. ✅ Section 6: Error Checking

### **🟡 HIGH PRIORITY (Test Second):**
4. ✅ Section 3: Keyboard Controls Verification
5. ✅ Section 4: Level Selector Functionality

### **🟢 MEDIUM PRIORITY (Test Third):**
6. ✅ Section 5: General Game Functionality
7. ✅ Section 7: Performance Check

### **🔵 LOW PRIORITY (Test Last):**
8. ✅ Section 8: Comparison - Local vs Production
9. ✅ Section 9: Documentation & Reporting

---

## ✅ **SUCCESS CRITERIA**

### **Must Pass (Production Ready):**
- [x] ✅ No 404 errors for assets
- [x] ✅ Phoenix boss spawns in Level 6
- [x] ✅ Alien Spider boss spawns in Level 6
- [x] ✅ All keyboard controls work (E, P, L, G, N, B)
- [x] ✅ Level selector works correctly
- [x] ✅ Game loads and plays correctly

### **Should Pass (Nice to Have):**
- [ ] ✅ Stable frame rate (50-60 FPS)
- [ ] ✅ Reasonable loading times
- [ ] ✅ All audio plays correctly
- [ ] ✅ All textures render correctly

---

## 🚨 **CRITICAL ISSUES TO WATCH FOR**

1. **404 Errors for Assets:**
   - If assets return 404, check symlinks on Render
   - Verify assets uploaded to `/data/public/three.js/public/`
   - Check symlink recreation after deployment

2. **Bosses Not Spawning:**
   - Check console for model loading errors
   - Verify model paths resolve correctly
   - Check if bosses are disabled in God Mode settings

3. **Keyboard Controls Not Working:**
   - Check if game is paused (P key)
   - Verify God Mode is enabled (for L, G, N, B keys)
   - Check browser console for JavaScript errors

4. **Path Resolution Issues:**
   - Verify all paths use `resolveAssetPath()` function
   - Check console for path resolution logs
   - Verify paths include `/public/three.js/public/` prefix

---

## 📞 **NEXT STEPS AFTER TESTING**

### **If All Tests Pass:**
- ✅ Mark production deployment as successful
- ✅ Update status documents
- ✅ Proceed with normal gameplay testing

### **If Issues Found:**
- 🔴 Document all issues in detail
- 🔴 Create fix plan for critical issues
- 🔴 Test fixes locally before pushing
- 🔴 Re-test in production after fixes deployed

---

**Status:** 📋 **TESTING PLAN READY**  
**Created:** January 6, 2026  
**Last Updated:** January 6, 2026

