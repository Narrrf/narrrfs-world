# 🎮 THREE.JS 3D RIDDLE GAME - DEPLOYMENT PLAN

**Created:** January 4, 2026  
**Status:** 📋 **PLAN READY - AWAITING APPROVAL**  
**Purpose:** Move three.js 3D riddle game to public folder for live server testing  
**Target Size:** Maximum 4 GB (Current: ~2.05 GB essential files)

---

## 📊 **CURRENT STRUCTURE ANALYSIS**

### **Source Location:**
`C:\xampp-server\htdocs\narrrfs-world\three.js\`

### **Size Breakdown:**
- **Essential Files (Root):** ~2 MB
  - `main.js` (1.5 MB)
  - `index.html` (main entry point)
  - All JS modules (~0.5 MB total)
- **Essential Assets (public/):** ~2.03 GB
  - `textures/3d models/` (1.99 GB) - Largest folder
  - `textures/backgrounds/` (0.02 GB)
  - `textures/plants/` (0.01 GB)
  - `textures/blocks/` (0.01 GB)
  - `textures/grass/` (~0 GB)
  - `audio/` (~0 GB)
  - `models/` (~0.01 GB)
  - `sounds/` (~0.02 GB)
  - `videos/` (~0 GB)
- **Total Essential:** ~2.05 GB ✅ (Well under 4 GB limit)

### **Excluded Folders (Not Needed for Production):**
- `Backups/` (5.9 GB) - ❌ Exclude
- `node_modules/` (0.06 GB) - ❌ Exclude (not needed for static deployment)
- `tools/` (0.012 GB) - ❌ Exclude (development tools)
- `three-grass-demo-main/` (0.001 GB) - ❌ Exclude (demo code)

---

## 🎯 **DEPLOYMENT PLAN**

### **Target Structure:**
```
public/
└── three.js/
    ├── index.html (renamed to 3d-riddle-game.html)
    ├── main.js
    ├── alien-spider.js
    ├── audio-system.js
    ├── chest-system.js
    ├── config-system.js
    ├── grass-system.js
    ├── gui-system.js
    ├── phoenix2.js
    ├── player-controls.js
    ├── player-model.js
    ├── sky-system.js
    ├── vr-input-provider.js
    ├── weapon-system.js
    └── public/
        ├── audio/
        ├── models/
        ├── sounds/
        ├── textures/
        └── videos/
```

### **Final URL:**
- **Local:** `http://localhost/public/three.js/3d-riddle-game.html`
- **Production:** `https://narrrfs.world/three.js/3d-riddle-game.html`

---

## 📋 **STEP-BY-STEP DEPLOYMENT PROCESS**

### **Step 1: Create Target Directory**
```powershell
# Create public/three.js directory
New-Item -ItemType Directory -Path "C:\xampp-server\htdocs\narrrfs-world\public\three.js" -Force
```

### **Step 2: Copy Essential JS Files**
**Files to Copy:**
- `index.html` → `public/three.js/3d-riddle-game.html`
- `main.js`
- `alien-spider.js`
- `audio-system.js`
- `chest-system.js`
- `config-system.js`
- `grass-system.js`
- `gui-system.js`
- `phoenix2.js`
- `player-controls.js`
- `player-model.js`
- `sky-system.js`
- `vr-input-provider.js`
- `weapon-system.js`

**Command:**
```powershell
# Copy all JS files
Copy-Item -Path "C:\xampp-server\htdocs\narrrfs-world\three.js\*.js" -Destination "C:\xampp-server\htdocs\narrrfs-world\public\three.js\" -Force

# Copy and rename index.html
Copy-Item -Path "C:\xampp-server\htdocs\narrrfs-world\three.js\index.html" -Destination "C:\xampp-server\htdocs\narrrfs-world\public\three.js\3d-riddle-game.html" -Force
```

### **Step 3: Copy Public Assets Folder**
**Command:**
```powershell
# Copy entire public folder (recursive)
Copy-Item -Path "C:\xampp-server\htdocs\narrrfs-world\three.js\public" -Destination "C:\xampp-server\htdocs\narrrfs-world\public\three.js\public" -Recurse -Force
```

**This will copy:**
- `public/audio/` (~0 GB)
- `public/models/` (~0.01 GB)
- `public/sounds/` (~0.02 GB)
- `public/textures/` (~2.03 GB)
- `public/videos/` (~0 GB)

### **Step 4: Update HTML File Paths**
**File:** `public/three.js/3d-riddle-game.html`

**Changes Needed:**
1. **Update Title:**
   ```html
   <title>3D Riddle Game - Narrrf's World</title>
   ```

2. **Update Script Path:**
   ```html
   <!-- Current (Vite path): -->
   <script type="module" src="/main.js"></script>
   
   <!-- Update to relative path: -->
   <script type="module" src="./main.js"></script>
   ```

3. **Add Meta Tags (Optional):**
   ```html
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <meta name="description" content="3D Riddle Game - The Cheese Temple Adventure" />
   ```

### **Step 5: Update Asset Paths in main.js (If Needed)**
**Current Paths (Absolute from root):**
- `/sounds/music/level1.mp3`
- `/audio/character/footstep_cheese.ogg`
- `/textures/3d models/...`

**Analysis:**
- These paths start with `/` which means they're absolute from web root
- If game is at `public/three.js/3d-riddle-game.html`, paths like `/sounds/` will look for `http://localhost/sounds/`
- **Solution Options:**
  1. **Option A (Recommended):** Keep absolute paths, ensure assets are accessible from root
  2. **Option B:** Update all paths to relative (`./public/sounds/` etc.)
  3. **Option C:** Use base path configuration

**Recommendation:** **Option A** - Keep absolute paths since assets are in `public/three.js/public/` which can be symlinked or accessed via server config. However, for immediate testing, we may need to adjust paths.

**If Path Adjustment Needed:**
- Search for all `/sounds/` → `./public/sounds/`
- Search for all `/audio/` → `./public/audio/`
- Search for all `/textures/` → `./public/textures/`
- Search for all `/models/` → `./public/models/`
- Search for all `/videos/` → `./public/videos/`

---

## 🔧 **PATH CONFIGURATION OPTIONS**

### **Option 1: Absolute Paths (Recommended for Production)**
**Keep paths as `/sounds/`, `/audio/`, `/textures/`**

**Pros:**
- No code changes needed
- Works if server configured correctly
- Clean URLs

**Cons:**
- Requires server configuration
- May not work immediately on localhost

### **Option 2: Relative Paths (Recommended for Quick Testing)**
**Update paths to `./public/sounds/`, `./public/audio/`, etc.**

**Pros:**
- Works immediately
- No server config needed
- Self-contained

**Cons:**
- Requires code changes
- Paths are longer

### **Option 3: Base Path Configuration**
**Add `<base href="/three.js/">` to HTML**

**Pros:**
- Single change in HTML
- All paths work relative to base

**Cons:**
- May affect other resources
- Requires testing

**Recommendation:** Start with **Option 2** (relative paths) for immediate testing, then optimize to Option 1 for production.

---

## 📝 **FILES TO UPDATE**

### **1. HTML File Updates**
**File:** `public/three.js/3d-riddle-game.html`

**Changes:**
```html
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>3D Riddle Game - Narrrf's World</title>
    <style>
      body { margin: 0; }
      canvas { display: block; }
    </style>
  </head>
  <body>
    <script type="module" src="./main.js"></script>
  </body>
</html>
```

### **2. main.js Path Updates (If Using Relative Paths)**
**Search & Replace:**
- `/sounds/` → `./public/sounds/`
- `/audio/` → `./public/audio/`
- `/textures/` → `./public/textures/`
- `/models/` → `./public/models/`
- `/videos/` → `./public/videos/`

**Note:** This is a large file (1.5 MB), so use search/replace carefully.

---

## ✅ **VERIFICATION CHECKLIST**

### **After Deployment:**
- [ ] All JS files copied to `public/three.js/`
- [ ] HTML file renamed to `3d-riddle-game.html`
- [ ] All assets copied to `public/three.js/public/`
- [ ] Paths updated in HTML (script src)
- [ ] Paths updated in main.js (if using relative paths)
- [ ] Test locally: `http://localhost/public/three.js/3d-riddle-game.html`
- [ ] Verify assets load (sounds, textures, models)
- [ ] Test game functionality (level loading, movement, etc.)
- [ ] Check console for errors
- [ ] Verify file size is under 4 GB

---

## 📊 **EXPECTED RESULTS**

### **File Structure:**
```
public/three.js/
├── 3d-riddle-game.html (renamed from index.html)
├── main.js (1.5 MB)
├── alien-spider.js
├── audio-system.js
├── chest-system.js
├── config-system.js
├── grass-system.js
├── gui-system.js
├── phoenix2.js
├── player-controls.js
├── player-model.js
├── sky-system.js
├── vr-input-provider.js
├── weapon-system.js
└── public/
    ├── audio/ (~0 GB)
    ├── models/ (~0.01 GB)
    ├── sounds/ (~0.02 GB)
    ├── textures/ (~2.03 GB)
    │   ├── 3d models/ (1.99 GB)
    │   ├── backgrounds/ (0.02 GB)
    │   ├── plants/ (0.01 GB)
    │   ├── blocks/ (0.01 GB)
    │   └── grass/ (~0 GB)
    └── videos/ (~0 GB)
```

### **Total Size:**
- **Essential Files:** ~2.05 GB ✅
- **Well Under 4 GB Limit:** ✅

### **URLs:**
- **Local:** `http://localhost/public/three.js/3d-riddle-game.html`
- **Production:** `https://narrrfs.world/three.js/3d-riddle-game.html`

---

## 🚨 **IMPORTANT NOTES**

### **1. Vite Development Server:**
- Current setup uses Vite for development (`npm run dev`)
- Production deployment uses static files (no Vite needed)
- ES modules (`type="module"`) work in modern browsers

### **2. Asset Paths:**
- Game uses absolute paths (`/sounds/`, `/textures/`)
- May need adjustment for subdirectory deployment
- Recommend starting with relative paths for testing

### **3. File Size:**
- Largest folder: `textures/3d models/` (1.99 GB)
- Consider compression if needed (but currently under 4 GB limit)
- Most assets are already optimized (GLB/GLTF, compressed textures)

### **4. Testing:**
- Test locally first before pushing to production
- Verify all assets load correctly
- Check browser console for errors
- Test all 6 levels if possible

---

## 🎯 **NEXT STEPS AFTER DEPLOYMENT**

1. **Test Locally:**
   - Open `http://localhost/public/three.js/3d-riddle-game.html`
   - Verify game loads
   - Test level transitions
   - Check asset loading

2. **Fix Path Issues (If Any):**
   - Check browser console for 404 errors
   - Update paths as needed
   - Test again

3. **Production Deployment:**
   - Push to Render
   - Test at `https://narrrfs.world/three.js/3d-riddle-game.html`
   - Verify all assets load
   - Test game functionality

4. **Documentation:**
   - Update technical documentation
   - Add to game list
   - Update status files

---

## 📋 **SUMMARY**

**Plan Status:** ✅ **READY FOR EXECUTION**

**Key Points:**
- ✅ Total size: ~2.05 GB (well under 4 GB limit)
- ✅ Structure: Keep same folder structure
- ✅ Paths: May need adjustment (relative vs absolute)
- ✅ Testing: Test locally before production
- ✅ Process: Similar to Glyph Memory deployment

**Ready to proceed when approved!**

---

**Created:** January 4, 2026  
**Status:** 📋 **PLAN READY - AWAITING USER APPROVAL**

