# 🚀 COMPLETE DEPLOYMENT PROCESS - ALL ASSETS TO RENDER

**Created:** January 9, 2026  
**Status:** ✅ **READY - ALL SYMLINKS CONFIGURED**  
**Purpose:** Complete step-by-step guide to deploy all assets and code

---

## ✅ **CONFIRMED: ALL FILES WILL BE SYMLINKED**

The updated `scripts/render-startup.sh` now creates symlinks for **ALL** directories:

**Three.js Asset Symlinks:**
- ✅ `textures/3d models/` → `/data/public/three.js/public/textures/3d models/`
- ✅ `textures/grass/` → `/data/public/three.js/public/textures/grass/` (NEW - for grass.jpg, cloud.jpg)
- ✅ `textures/backgrounds/` → `/data/public/three.js/public/textures/backgrounds/` (NEW - for cheesetemple1.png)
- ✅ `textures/blocks/` → `/data/public/three.js/public/textures/blocks/` (NEW)
- ✅ `textures/plants/` → `/data/public/three.js/public/textures/plants/` (NEW)
- ✅ `sounds/` → `/data/public/three.js/public/sounds/`
- ✅ `audio/` → `/data/public/three.js/public/audio/`
- ✅ `models/` → `/data/public/three.js/public/models/` (NEW - for level1.json)
- ✅ `videos/` → `/data/public/three.js/public/videos/` (NEW)

**Glyph Asset Symlinks:**
- ✅ `glyph3d/` → `/data/public/glyph/glyph3d/`

---

## 📋 **STEP-BY-STEP DEPLOYMENT PROCESS**

### **STEP 1: Commit and Push Code (FIRST)**

**What gets committed:**
- ✅ Code changes (API, scripts, Three.js files)
- ✅ Updated startup script (with ALL symlinks)
- ✅ Documentation
- ✅ Upload scripts
- ❌ **NO asset files** (glyph3d, narrrf3d excluded via .gitignore)

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Review what will be committed
git status

# Add all changes (excluding assets via .gitignore)
git add .

# Commit
git commit -m "🚀 URGENT: Complete asset upload system and symlink configuration

- Updated scripts/render-startup.sh to create ALL required symlinks
  * Added symlinks for grass/, backgrounds/, blocks/, plants/, models/, videos/
  * All directories now symlinked correctly
- Updated api/discord/upload-assets.php to support glyph paths
- Created comprehensive asset upload scripts
- Updated .gitignore to exclude glyph3d and narrrf3d (uploaded via API)
- Created complete deployment documentation
- Fixed critical missing files path resolution"

# Push to Render
git push origin render-deploy
```

**Wait:** 1-2 minutes for Render deployment to complete

---

### **STEP 2: Upload ALL Assets to /data/ (AFTER DEPLOYMENT)**

**After Render deployment completes, upload all assets:**

```powershell
# Upload ALL 1,499+ files from local to Render /data/
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-09\UPLOAD_ALL_ASSETS_URGENT.ps1 -BotSecret "YOUR_DISCORD_BOT_SECRET"
```

**What gets uploaded:**
- ✅ ALL files from `public/three.js/public/` → `/data/public/three.js/public/`
- ✅ Includes: grass.jpg, cloud.jpg, cheesetemple1.png, level1.json
- ✅ Includes: all 3D models, textures, sounds, audio, backgrounds, blocks, plants, models, videos
- ✅ Excludes: archives (.tar.gz, .rar), PDFs, .url files

**Estimated Time:** 30-60 minutes (depends on file sizes and network)

---

### **STEP 3: Run Startup Script on Render (CREATE SYMLINKS)**

**In Render Shell (after upload completes):**

```bash
# Run startup script to create all symlinks
bash /var/www/html/scripts/render-startup.sh
```

**What this does:**
- ✅ Creates `/data/` directories if needed
- ✅ Creates ALL symlinks listed above
- ✅ Sets correct permissions
- ✅ Verifies setup

**Note:** The startup script runs automatically on deployment, but you can run it manually to ensure symlinks are created immediately.

---

### **STEP 4: Verify Everything Works**

**In Render Shell:**
```bash
# Verify file counts
find /data/public/three.js/public/ -type f | wc -l
# Should show ~1,499 files

# Verify critical files exist
ls -la /data/public/three.js/public/textures/grass/grass.jpg
ls -la /data/public/three.js/public/textures/grass/cloud.jpg
ls -la /data/public/three.js/public/textures/backgrounds/cheesetemple1.png
ls -la /data/public/three.js/public/models/cheese-temple/level1.json

# Verify symlinks work
ls -la /var/www/html/public/three.js/public/textures/grass/grass.jpg
# Should show file (accessed via symlink)

# Verify all symlinks exist
ls -la /var/www/html/public/three.js/public/textures/ | grep " -> /data"
ls -la /var/www/html/public/three.js/public/ | grep " -> /data"
```

**In Browser:**
1. **Test game:** https://narrrfs.world/public/three.js/3d-riddle-game.html
2. **Check console:** No 404 errors
3. **Verify:**
   - ✅ Grass textures load (grass.jpg, cloud.jpg)
   - ✅ Background image loads (cheesetemple1.png)
   - ✅ Level 1 loads (level1.json)
   - ✅ All models, textures, sounds work
   - ✅ Collision mesh loads correctly

---

## ✅ **CONFIRMATION: ALL ASSETS WILL BE AVAILABLE**

**After completing all steps:**

✅ **All 1,499+ files uploaded to `/data/`**
- Persistent storage survives deployments
- Files stored in `/data/public/three.js/public/`

✅ **All directories symlinked correctly**
- Web-accessible via `/var/www/html/public/three.js/public/`
- Symlinks point to `/data/`
- All game assets accessible: grass, backgrounds, models, sounds, audio, videos, etc.

✅ **Game will work with all assets**
- ✅ Grass system (grass.jpg, cloud.jpg)
- ✅ Background images (cheesetemple1.png, floor1-5.png)
- ✅ Level data (level1.json, map.json)
- ✅ 3D models (all in textures/3d models/)
- ✅ Sounds and music (sounds/, audio/)
- ✅ Blocks, plants, videos (textures/blocks/, textures/plants/, videos/)
- ✅ Everything needed for complete gameplay

---

## 🔄 **AUTOMATIC SYMLINK CREATION**

**The startup script runs automatically on every deployment, so:**

1. **First deployment:** Upload all assets → Run startup script → Symlinks created
2. **Future deployments:** Startup script runs automatically → Symlinks recreated
3. **Assets persist:** Files in `/data/` survive deployments → Always available

---

## 🚨 **IMPORTANT NOTES**

### **Why Two Steps?**
1. **Code first:** Startup script must be deployed before uploading assets
2. **Assets second:** Upload assets after script is deployed
3. **Symlinks automatic:** Script creates symlinks on deployment (or manually)

### **No Need to Push Again**
- ❌ **Do NOT push again** after uploading assets
- ✅ Assets are in `/data/` (persistent storage)
- ✅ Symlinks are created by startup script (automatic on deployment)
- ✅ Everything is configured after Step 1 (code push) and Step 2 (asset upload)

### **Asset Files Excluded from Git**
- ✅ `.gitignore` excludes `glyph3d/` and `narrrf3d/`
- ✅ Asset files never committed to Git
- ✅ Assets uploaded via API to `/data/`
- ✅ Keeps Git repository clean and small

---

## ✅ **SUCCESS CRITERIA**

After completing all steps:
- ✅ All code changes committed and pushed
- ✅ All 1,499+ files uploaded to `/data/`
- ✅ All symlinks created and working
- ✅ Game loads without 404 errors
- ✅ All assets accessible and working
- ✅ Level 1 loads correctly
- ✅ Grass system works
- ✅ Background images display
- ✅ All models and textures render

---

**Status:** ✅ **READY TO DEPLOY**  
**Next Step:** Run Step 1 (git commit and push), then Step 2 (upload assets)
