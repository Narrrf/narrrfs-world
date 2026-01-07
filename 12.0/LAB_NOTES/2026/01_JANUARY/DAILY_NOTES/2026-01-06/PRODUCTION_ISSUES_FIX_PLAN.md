# 🔧 Production Issues Fix Plan - January 6, 2026

**Date:** January 6, 2026  
**Status:** 🚨 **CRITICAL ISSUES IDENTIFIED**  
**Production URL:** `https://narrrfs.world/three.js/3d-riddle-game.html`

---

## 🐛 **ISSUES IDENTIFIED**

### **1. Level 1 Map Not Loading** 🚨 **CRITICAL**
**Symptom:** Level 1 times out after 60 seconds, map doesn't appear

**Possible Causes:**
- Level1.json path issue (though Level 2 works, so path resolution seems OK)
- `buildLevel()` function hanging
- `applyLevelEnvironment()` hanging (grass generation taking too long)
- Level JSON file missing or 404 in production

**Investigation Steps:**
1. Check Network tab for level1.json request status
2. Verify level1.json exists at: `/public/three.js/public/models/cheese-temple/level1.json`
3. Check console for `buildLevel` errors
4. Check if grass generation is blocking (Level 1 has heavy grass generation)

---

### **2. Player Not Rendered in 3rd Person** 🚨 **CRITICAL**
**Symptom:** Player model not visible when camera mode is set to 3rd person

**Possible Causes:**
- Player model not loading
- Camera mode not properly set after character selection
- Visibility logic issue: `playerCharacterModel.visible = !isFirstPerson()` might not be updating
- Player model loading but not added to scene

**Current Code:**
```javascript
// Line 7608: Sets camera to first-person by default
setCameraMode(0); // 0 = first-person

// Line 5227: Player visibility based on camera mode
playerCharacterModel.visible = !isFirstPerson(); // Visible in 3rd person only
```

**Investigation Steps:**
1. Verify `loadPlayerCharacter()` completes successfully
2. Check if `setCameraMode(1)` is called when user selects 3rd person
3. Verify `isFirstPerson()` returns correct value
4. Check if player model is actually in scene: `scene.children.includes(playerCharacterModel)`

---

### **3. Grass Texture Loading Failures** ⚠️ **MEDIUM**
**Symptom:** `⚠️ [GRASS] 2 texture(s) failed to load`

**Fix:** Verify grass texture paths resolve correctly in production

---

### **4. Level 2 Anchor Not Found Warnings** ⚠️ **LOW**
**Symptom:** Multiple "Anchor not found for preview" warnings for Level 2 monster previews

**Impact:** Minor - models still load, just positioned incorrectly

---

### **5. Texture Loading Warnings** ⚠️ **MEDIUM**
**Symptom:** 
- `slever1.png` - Using fallback texture
- `slever2.png` - Using fallback texture  
- `yellow-cheese.png` - Using fallback texture

**Fix:** Verify these textures exist and paths resolve correctly

---

## 🔍 **ROOT CAUSE ANALYSIS**

### **Level 1 Timeout Issue:**

The timeout is a **safety feature** that allows gameplay to continue even if heavy operations (like grass generation) are still running. However, if the level doesn't appear, something is blocking or failing silently.

**Possible Blocking Operations:**
1. `buildLevel(mapData)` - Parsing level JSON and creating geometry
2. `applyLevelEnvironment(LEVEL_IDS.LEVEL1)` - Grass generation (can take 30-40 seconds for 40K blades)
3. `loadPlayerCharacter()` - Loading GLTF model and animations

**Check:**
- Does console show "✅ [DEBUG] level1.json fetched successfully"?
- Does console show "✅ [GAME START] Player character loaded"?
- Is grass generation completing?

---

### **Player 3rd Person Visibility Issue:**

The code sets visibility based on camera mode:
```javascript
playerCharacterModel.visible = !isFirstPerson();
```

But this is set **once** when the character loads. If camera mode changes **after** character loads, visibility might not update.

**Fix Needed:**
- Update player visibility when camera mode changes
- Ensure `setCameraMode()` function updates player visibility

---

## ✅ **IMMEDIATE FIXES NEEDED**

### **Fix 1: Update Player Visibility on Camera Mode Change**

**File:** `main.js`  
**Location:** `setCameraMode()` function

**Action:** Add visibility update when camera mode changes:

```javascript
function setCameraMode(mode) {
  // ... existing code ...
  
  // Update player model visibility based on camera mode
  if (playerCharacterModel) {
    const isFirstPersonMode = (mode === 0);
    playerCharacterModel.visible = !isFirstPersonMode;
    console.log(`🎮 [CAMERA] Player model visibility updated: ${playerCharacterModel.visible} (mode: ${mode})`);
  }
}
```

---

### **Fix 2: Add Debug Logging for Level 1 Loading**

**File:** `main.js`  
**Location:** `warpToLevel1` function

**Action:** Add more detailed logging to track where Level 1 loading hangs:

```javascript
console.log("🔍 [LEVEL 1] Step 1: Fetching level1.json...");
fetch(level1Path)
  .then((res) => {
    console.log("🔍 [LEVEL 1] Step 2: level1.json fetched, parsing...");
    return res.json();
  })
  .then(async (mapData) => {
    console.log("🔍 [LEVEL 1] Step 3: Building level geometry...");
    buildLevel(mapData);
    console.log("🔍 [LEVEL 1] Step 4: Level geometry built, applying environment...");
    await applyLevelEnvironment(LEVEL_IDS.LEVEL1);
    console.log("🔍 [LEVEL 1] Step 5: Environment applied, loading player...");
    await loadPlayerCharacter();
    console.log("🔍 [LEVEL 1] Step 6: All loading complete!");
    resolve();
  });
```

---

### **Fix 3: Check Level1.json Path in Production**

**Action:** Verify the resolved path works in production:

**Expected Path:** `https://narrrfs.world/public/three.js/public/models/cheese-temple/level1.json`

**Test:** Open this URL directly in browser - should return JSON, not 404.

---

## 📋 **TESTING CHECKLIST**

### **After Fixes Applied:**

**Level 1 Loading:**
- [ ] Console shows "Step 1-6" logs in sequence
- [ ] Level 1 map appears (no timeout)
- [ ] No hanging on any step
- [ ] Grass renders (if enabled)

**Player 3rd Person:**
- [ ] Select 3rd person camera mode
- [ ] Player model appears behind camera
- [ ] Console shows "Player model visibility updated: true"
- [ ] Switching between 1st and 3rd person updates visibility

**General:**
- [ ] Level 2 still works (regression check)
- [ ] No new errors in console
- [ ] Game playable

---

## 🚀 **PRIORITY ORDER**

1. **🔴 CRITICAL:** Fix player 3rd person visibility (quick fix)
2. **🔴 CRITICAL:** Debug Level 1 loading timeout (investigation + fix)
3. **🟡 MEDIUM:** Fix texture loading warnings (verify paths)
4. **🟢 LOW:** Fix Level 2 anchor warnings (non-critical)

---

**Status:** 📋 **PLAN READY - AWAITING IMPLEMENTATION**

