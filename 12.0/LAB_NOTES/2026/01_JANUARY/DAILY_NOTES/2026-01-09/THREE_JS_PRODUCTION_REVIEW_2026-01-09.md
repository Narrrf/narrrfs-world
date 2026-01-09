# 🎮 Three.js Production Issues Review - January 9, 2026

**Date:** January 9, 2026  
**Focus:** Production Issues Investigation & Fixes  
**Status:** 🔄 **IN PROGRESS**

---

## 🎯 **CURRENT SITUATION**

### **Local Environment:**
- **Path:** `C:\xampp-server\htdocs\narrrfs-world\public\three.js`
- **Status:** ✅ **WORKING GREAT** - Game starts correctly and plays well
- **HTML Location:** `http://localhost/public/three.js/3d-riddle-game.html`
- **Asset Path:** `/public/three.js/public/...` (absolute path from web root)

### **Production Environment:**
- **Status:** ⚠️ **HAS ISSUES** - Multiple problems preventing gameplay
- **Production URL:** `https://narrrfs.world/three.js/3d-riddle-game.html` or `https://narrrfs.world/public/three.js/3d-riddle-game.html`
- **Asset Path:** Needs verification - may not match local structure

---

## 🐛 **IDENTIFIED ISSUES FROM JANUARY 6 DOCUMENTATION**

### **1. Level 6 Hanging Issue** 🚨 **CRITICAL**
**Symptom:** Level 6 loads (chests, grass, etc.) but then nothing happens - level times out at 60s

**Root Cause Analysis:**
- `buildLevel6PhoenixArena()` is async and awaited
- If this function doesn't resolve, the entire warp times out
- Likely hanging in boss initialization (Phoenix or Alien Spider model loading)

**Fix Strategy:**
- Add timeout protection to boss model loading
- Ensure `buildLevel6PhoenixArena()` always resolves (even if bosses fail to load)
- Add debug logging to track where Level 6 hangs

---

### **2. Level 3 Player Movement Issue** 🚨 **CRITICAL**
**Symptom:** Level 3 spawns but player cannot move (WASD keys don't work)

**Root Cause Analysis:**
- Player controls might not be enabled after warp
- Collision mesh might not be ready
- `onGround` flag might be stuck
- Pointer lock might be blocking input

**Fix Strategy:**
- Ensure `playerControls.setEnabled(true)` is called in `restoreGameStateAfterWarp()`
- Verify collision mesh is ready before allowing movement
- Reset `onGround` flag correctly
- Ensure pointer lock doesn't block keyboard input

---

### **3. Level 1 Map Not Loading** 🚨 **CRITICAL**
**Symptom:** Level 1 times out after 60 seconds, map doesn't appear

**Possible Causes:**
- Level1.json path issue (though Level 2 works, so path resolution seems OK)
- `buildLevel()` function hanging
- `applyLevelEnvironment()` hanging (grass generation taking too long)
- Level JSON file missing or 404 in production

**Fix Strategy:**
- Add debug logging for Level 1 loading steps
- Verify level1.json path resolves correctly in production
- Add timeout protection for grass generation

---

### **4. Player Not Rendered in 3rd Person** 🚨 **CRITICAL**
**Symptom:** Player model not visible when camera mode is set to 3rd person

**Root Cause:**
- Player visibility set once when character loads
- If camera mode changes after character loads, visibility doesn't update

**Fix Strategy:**
- Update player visibility when camera mode changes
- Ensure `setCameraMode()` function updates player visibility

---

### **5. WebGL Errors** ⚠️ **HIGH**
**Symptom:** `WebGL: too many errors, no more errors will be reported`

**Root Cause:**
- Something is repeatedly causing WebGL errors
- Could be from texture loading failures
- Could be from invalid material operations

**Fix Strategy:**
- Fix texture loading paths (many textures using fallback)
- Add error handling to prevent repeated WebGL errors
- Check for invalid material/texture operations

---

## 🔍 **PATH RESOLUTION ANALYSIS**

### **Current Path Resolution Function:**

```javascript
function resolveAssetPath(path) {
  // ... existing logic ...
  
  // Both environments use same path structure:
  let resolvedPath = `/public/three.js/public/${cleanPath}`;
  
  return resolvedPath;
}
```

### **Path Structure:**
- **Local:** `/public/three.js/public/...` (absolute from web root)
- **Production:** `/public/three.js/public/...` (same structure)
- **HTML Location:**
  - Local: `http://localhost/public/three.js/3d-riddle-game.html`
  - Production: `https://narrrfs.world/public/three.js/3d-riddle-game.html`

### **Potential Issues:**
1. **Production HTML Location** - May be at `/three.js/3d-riddle-game.html` instead of `/public/three.js/3d-riddle-game.html`
2. **Asset Path Mismatch** - If HTML is at `/three.js/`, but assets are at `/public/three.js/public/`, paths won't resolve correctly
3. **Symlink Issues** - Render symlinks may not be set up correctly

---

## 🔧 **IMMEDIATE FIXES NEEDED**

### **Fix 1: Verify Production HTML Location**

**Action:** Check actual production URL structure

**Expected Locations:**
- `https://narrrfs.world/public/three.js/3d-riddle-game.html`
- OR: `https://narrrfs.world/three.js/3d-riddle-game.html`

**If HTML is at `/three.js/`:**
- Assets need to resolve relative to `/three.js/`
- Path resolution needs to account for different HTML location

---

### **Fix 2: Fix Path Resolution for Different HTML Locations**

**If Production HTML is at `/three.js/3d-riddle-game.html`:**

```javascript
function resolveAssetPath(path) {
  // ... existing checks ...
  
  // Detect HTML file location
  const htmlPath = window.location.pathname;
  const isHtmlInThreeJs = htmlPath.startsWith('/three.js/');
  const isHtmlInPublicThreeJs = htmlPath.startsWith('/public/three.js/');
  
  // Remove leading ./ if present
  let cleanPath = path.startsWith('./') ? path.slice(2) : path;
  cleanPath = cleanPath.startsWith('/') ? cleanPath.slice(1) : cleanPath;
  cleanPath = cleanPath.startsWith('public/') ? cleanPath.slice(7) : cleanPath;
  
  let resolvedPath;
  if (isHtmlInThreeJs) {
    // HTML is at /three.js/, assets are at /public/three.js/public/...
    resolvedPath = `/public/three.js/public/${cleanPath}`;
  } else if (isHtmlInPublicThreeJs) {
    // HTML is at /public/three.js/, assets are relative
    resolvedPath = `/public/three.js/public/${cleanPath}`;
  } else {
    // Fallback: assume /public/three.js/public/
    resolvedPath = `/public/three.js/public/${cleanPath}`;
  }
  
  console.log(`🔍 [PATH RESOLVE] "${path}" → "${resolvedPath}" (HTML: ${htmlPath})`);
  
  return resolvedPath;
}
```

---

### **Fix 3: Add Timeout Protection to Level 6 Boss Loading**

**Location:** `buildLevel6PhoenixArena()` function

```javascript
// Add timeout wrapper for boss loading
const bossLoadTimeout = 30000; // 30 seconds max
const bossLoadPromise = Promise.race([
  Promise.all([
    phoenixBoss?.loadModel(modelPath),
    alienSpiderBoss?.loadModel(spiderModelPath)
  ]),
  new Promise((resolve) => {
    setTimeout(() => {
      console.warn("⏱️ [LEVEL 6] Boss loading timeout - continuing without bosses");
      resolve();
    }, bossLoadTimeout);
  })
]);

await bossLoadPromise;
```

---

### **Fix 4: Ensure Player Controls Enabled After Warp**

**Location:** `restoreGameStateAfterWarp()` function

```javascript
// CRITICAL: Ensure WASD controls work immediately
if (playerControls) {
  if (typeof playerControls.setEnabled === 'function') {
    playerControls.setEnabled(true);
    console.log("✅ [RESTORE] Player controls enabled");
  }
  if (typeof playerControls.enable === 'function') {
    playerControls.enable();
    console.log("✅ [RESTORE] Player controls enabled (legacy method)");
  }
}

// Reset movement flags
onGround = false;
keyboardMovement.forward = false;
keyboardMovement.backward = false;
keyboardMovement.left = false;
keyboardMovement.right = false;
keyboardMovement.sprint = false;
```

---

### **Fix 5: Update Player Visibility on Camera Mode Change**

**Location:** `setCameraMode()` function

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

## 📋 **INVESTIGATION CHECKLIST**

### **Before Fixes:**
- [ ] Verify production HTML URL (where is `3d-riddle-game.html` located?)
- [ ] Verify asset directory structure in production
- [ ] Check browser console for 404 errors
- [ ] Verify symlinks are set up correctly on Render
- [ ] Test level1.json path in production browser
- [ ] Check Network tab for asset loading failures

### **After Fixes:**
- [ ] Test Level 6 loads without timeout
- [ ] Test Level 3 player movement works
- [ ] Test Level 1 loads correctly
- [ ] Test 3rd person camera shows player model
- [ ] Verify no WebGL errors in console
- [ ] Test all levels load correctly

---

## 🚀 **PRIORITY ORDER**

1. **🔴 CRITICAL:** Verify production HTML location and path structure
2. **🔴 CRITICAL:** Fix path resolution for production HTML location
3. **🔴 CRITICAL:** Fix Level 3 player movement (controls enabled)
4. **🔴 CRITICAL:** Fix Level 6 hanging (timeout protection)
5. **🟡 HIGH:** Fix Level 1 loading timeout
6. **🟡 HIGH:** Fix player 3rd person visibility
7. **🟢 MEDIUM:** Fix WebGL texture errors

---

## 📝 **NOTES**

- **Local works great:** Game plays perfectly on `C:\xampp-server\htdocs\narrrfs-world\public\three.js`
- **Production has issues:** Multiple critical issues preventing gameplay
- **Path resolution:** May need adjustment based on actual production HTML location
- **Symlink structure:** Render symlinks need verification

---

**Status:** 🔄 **IN PROGRESS - INVESTIGATING 404 ERRORS**

---

## 🚨 **NEW CRITICAL ISSUE - JANUARY 9, 2026**

### **Production 404 Errors - Assets Not Loading**

**User Report:**
- Game goes through all 3 GUI loading screens (new game → character selection → level selection)
- User selects mouse character
- User selects Level 1
- **Game shows 404 errors and only loads GUI, sky system, and controls**
- **No level geometry, no models (plants, chests, towers), nothing loads**

**Console Logs Show:**
1. **404 Errors for Critical Assets:**
   - `GET https://narrrfs.world/public/three.js/public/textures/grass/grass.jpg 404 (Not Found)`
   - `GET https://narrrfs.world/public/three.js/public/textures/grass/cloud.jpg 404 (Not Found)`
   - `GET https://narrrfs.world/public/three.js/public/textures/backgrounds/cheesetemple1.png 404 (Not Found)`
   - `GET https://narrrfs.world/public/three.js/public/models/cheese-temple/level1.json 404 (Not Found)`

2. **Path Resolution Working Correctly:**
   - `resolveAssetPath()` is generating correct paths: `/public/three.js/public/...`
   - Logs show: `🔍 [PATH RESOLVE] "textures/grass/grass.jpg" → "/public/three.js/public/textures/grass/grass.jpg"`
   - Path resolution logic is **CORRECT** - the issue is that assets don't exist on production server

3. **Collision Mesh Error:**
   - `❌ [LEVEL 1] Collision mesh not ready!` (repeated)
   - This is a **symptom** of `level1.json` failing to load (404 error)
   - Without level data, collision mesh cannot be built

4. **Level Build Error:**
   - `❌ [GAME START] Error during level loading: TypeError: Cannot read properties of undefined (reading 'size')`
   - This occurs because `level1.json` didn't load, so `levelData` is `undefined`
   - `buildLevel()` tries to access `levelData.size` which doesn't exist

---

## 🔍 **ROOT CAUSE ANALYSIS**

### **Path Resolution is Correct:**
- `resolveAssetPath()` function is working as designed
- Paths are being generated correctly: `/public/three.js/public/...`
- Both local and production use same path structure (per rules)

### **The Real Problem:**
**Assets don't exist on production server at the expected paths!**

**Expected Structure (per rules):**
- **Storage:** `/data/public/three.js/public/` (persistent storage)
- **Symlink:** `/var/www/html/public/three.js/public/` → `/data/public/three.js/public/`
- **Web URL:** `/public/three.js/public/...`

**Possible Issues:**
1. **Assets not uploaded to `/data/public/three.js/public/` on Render**
2. **Symlinks not created** (symlinks are wiped on each Git deployment)
3. **Web server not configured** to serve files from `/public/three.js/public/`
4. **Directory structure mismatch** between local and production

---

## 🔧 **IMMEDIATE FIX STRATEGY**

### **Step 1: Verify Production Asset Structure**

**Action Required:**
1. SSH into Render production server
2. Check if assets exist: `ls -la /data/public/three.js/public/textures/grass/`
3. Check if symlinks exist: `ls -la /var/www/html/public/three.js/public/`
4. Verify web server can access: `curl https://narrrfs.world/public/three.js/public/textures/grass/grass.jpg`

**Expected Results:**
- Assets should exist in `/data/public/three.js/public/`
- Symlinks should exist in `/var/www/html/public/three.js/public/`
- Web server should serve files from symlinked paths

### **Step 2: Fix Asset Upload/Symlink Setup**

**If Assets Missing:**
- Upload assets to `/data/public/three.js/public/` using API upload endpoint
- See: `12.0/RULES/11_THREE_JS_RULE.md` §13 for upload instructions

**If Symlinks Missing:**
- Recreate symlinks after deployment:
  ```bash
  ln -s /data/public/three.js/public/textures /var/www/html/public/three.js/public/textures
  ln -s /data/public/three.js/public/models /var/www/html/public/three.js/public/models
  ln -s /data/public/three.js/public/sounds /var/www/html/public/three.js/public/sounds
  ln -s /data/public/three.js/public/audio /var/www/html/public/three.js/public/audio
  ```
- See: `RECREATE_SYMLINKS.sh` for automation

### **Step 3: Add Better Error Handling (Code Fix)**

**Even if assets are missing, game should handle gracefully:**

```javascript
// In buildLevel() function - add error handling for missing level data
async function buildLevel(levelData) {
  if (!levelData || !levelData.size) {
    console.error("❌ [BUILD LEVEL] Invalid level data:", levelData);
    throw new Error("Level data is missing or invalid");
  }
  // ... rest of function
}

// In warpToLevelWithLoading() - add fallback for level1.json failure
try {
  const level1Data = await fetch(resolveAssetPath("models/cheese-temple/level1.json"))
    .then(res => {
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      return res.json();
    });
  buildLevel(level1Data);
} catch (error) {
  console.error("❌ [LEVEL 1] Failed to load level1.json:", error);
  // Show user-friendly error message
  alert(`Failed to load Level 1: ${error.message}\n\nPlease check that assets are uploaded to the server.`);
  return; // Don't continue if level data is missing
}
```

---

## 📋 **VERIFICATION CHECKLIST**

### **Step 1: Run Verification Script on Render (RECOMMENDED):**

**In Render Shell:**
```bash
# Copy verification script to Render (if not already deployed)
# Then run:
bash /var/www/html/12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-09/VERIFY_RENDER_STATUS.sh
```

**Or run manually:**
```bash
# Check /data/ folder
ls -la /data/public/three.js/public/

# Check symlinks
ls -la /var/www/html/public/three.js/public/textures/
ls -la /var/www/html/public/three.js/public/sounds/

# Check critical missing files
ls -la /data/public/three.js/public/textures/grass/grass.jpg
ls -la /data/public/three.js/public/textures/grass/cloud.jpg
ls -la /data/public/three.js/public/textures/backgrounds/cheesetemple1.png
ls -la /data/public/three.js/public/models/cheese-temple/level1.json

# Count files
find /data/public/three.js/public/ -type f | wc -l
find /var/www/html/public/three.js/public/ -type l | wc -l
```

**Script Output Will Show:**
- ✅/❌ Status of `/data/` directories
- ✅/❌ Status of critical missing files (grass.jpg, cloud.jpg, cheesetemple1.png, level1.json)
- ✅/❌ Status of symlinks in `/var/www/html/`
- File counts for each asset type
- Recommendations for fixes

### **Before Code Changes:**
- [ ] **RUN VERIFICATION SCRIPT FIRST** - See above
- [ ] Verify assets exist in `/data/public/three.js/public/` on Render
- [ ] Verify symlinks exist in `/var/www/html/public/three.js/public/`
- [ ] Test direct URL access: `https://narrrfs.world/public/three.js/public/textures/grass/grass.jpg`
- [ ] Check Render deployment logs for startup script output
- [ ] Verify web server configuration allows serving from `/public/three.js/public/`

### **After Asset Upload/Symlink Fix:**
- [ ] Test Level 1 loads correctly
- [ ] Verify grass textures load (no 404 errors)
- [ ] Verify level1.json loads (no 404 errors)
- [ ] Verify collision mesh builds correctly
- [ ] Test all levels load correctly

---

## 🎯 **PRIORITY ORDER (UPDATED)**

1. **🔴 CRITICAL:** Verify assets exist on production server (`/data/public/three.js/public/`)
2. **🔴 CRITICAL:** Verify symlinks are created (`/var/www/html/public/three.js/public/`)
3. **🔴 CRITICAL:** Upload missing assets if needed (API upload endpoint)
4. **🟡 HIGH:** Add better error handling for missing assets (code fix)
5. **🟡 HIGH:** Fix Level 3 player movement (controls enabled)
6. **🟡 HIGH:** Fix Level 6 hanging (timeout protection)
7. **🟢 MEDIUM:** Fix player 3rd person visibility
8. **🟢 MEDIUM:** Fix WebGL texture errors

---

## 📝 **NOTES**

- **Path resolution is working correctly** - the issue is missing assets on production server
- **Local environment works** because assets exist locally at `C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\`
- **Production needs assets uploaded** to `/data/public/three.js/public/` and symlinks created
- **Symlinks are wiped on each Git deployment** - BUT startup script should recreate them automatically
- **Startup script location:** `/var/www/html/scripts/render-startup.sh` (runs on every deployment)
- **Verification script:** `VERIFY_RENDER_STATUS.sh` - Run this first to check everything

## 🔧 **QUICK FIX INSTRUCTIONS**

### **If Verification Script Shows Missing Files:**

**1. Upload Missing Assets:**
```powershell
# From local Windows machine
cd C:\xampp-server\htdocs\narrrfs-world
# Run upload script (needs Discord bot secret)
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_VIA_API.ps1 -BotSecret "YOUR_SECRET"
```

**2. Recreate Symlinks (if missing):**
```bash
# In Render shell
bash /var/www/html/12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RECREATE_SYMLINKS.sh
```

**3. Verify Again:**
```bash
# In Render shell
bash /var/www/html/12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-09/VERIFY_RENDER_STATUS.sh
```

**4. Test in Browser:**
- Open: `https://narrrfs.world/public/three.js/3d-riddle-game.html`
- Check browser console for 404 errors
- Verify assets load correctly