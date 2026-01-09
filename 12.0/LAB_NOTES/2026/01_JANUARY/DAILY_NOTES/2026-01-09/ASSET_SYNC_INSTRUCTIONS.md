# 🚀 URGENT: ASSET SYNC INSTRUCTIONS

**Created:** January 9, 2026  
**Purpose:** Complete guide to sync ALL local assets to Render  
**Status:** ⚠️ **URGENT - ALL ASSETS MUST BE ON RENDER**

---

## 📋 **OVERVIEW**

Local working version has **1,499 files** in `public/three.js/public/` that need to be on Render.

**Current Situation:**
- ✅ Local: All assets exist and working perfectly
- ⚠️ Render: Some assets missing (grass.jpg, cloud.jpg, cheesetemple1.png, level1.json confirmed missing)
- ⚠️ Render: Need to verify all other assets are present

**Goal:**
- ✅ Upload ALL missing assets to `/data/public/three.js/public/` (persistent storage)
- ✅ Ensure symlinks are created correctly
- ✅ Verify all assets are accessible via web

---

## 🚀 **STEP 1: UPLOAD ALL ASSETS (URGENT)**

### **Option A: Upload All Assets (Recommended)**

**Run this script to upload ALL 1,499 files:**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-09\UPLOAD_ALL_ASSETS_URGENT.ps1 -BotSecret "YOUR_DISCORD_BOT_SECRET"
```

**What it does:**
- ✅ Scans all files in `public/three.js/public/`
- ✅ Excludes archives (.tar.gz, .rar), PDFs, .url files
- ✅ Uploads each file to `/data/public/three.js/public/` via API
- ✅ Shows progress, estimated time remaining
- ✅ Tracks successes and failures

**Estimated Time:** 30-60 minutes (depends on file sizes and network)

**Note:** The script will ask for confirmation before starting.

### **Option B: Upload Critical Files First (Faster)**

**If you need critical files immediately, upload these 4 first:**

```powershell
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-09\UPLOAD_MISSING_FILES.ps1 -BotSecret "YOUR_DISCORD_BOT_SECRET"
```

**Then upload all assets later:**

```powershell
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-09\UPLOAD_ALL_ASSETS_URGENT.ps1 -BotSecret "YOUR_DISCORD_BOT_SECRET"
```

---

## ✅ **STEP 2: VERIFY UPLOADS ON RENDER**

**In Render Shell, verify files exist:**

```bash
# Count files in /data/ (should be ~1,499)
find /data/public/three.js/public/ -type f | wc -l

# Check specific directories
ls -la /data/public/three.js/public/textures/grass/
ls -la /data/public/three.js/public/textures/backgrounds/
ls -la /data/public/three.js/public/models/cheese-temple/
ls -la /data/public/three.js/public/textures/3d\ models/narrrf3d/
ls -la /data/public/glyph/glyph3d/

# Check symlinks are working
ls -la /var/www/html/public/three.js/public/textures/grass/grass.jpg
# Should show the file (accessed via symlink)
```

---

## 🔗 **STEP 3: VERIFY SYMLINKS (CRITICAL)**

**Symlinks MUST be created for assets to be web-accessible.**

### **3.1: Deploy Startup Script (if not already deployed)**

The `scripts/render-startup.sh` has been updated to automatically create symlinks. Make sure it's deployed:

```bash
# In Render shell, check if script exists
ls -la /var/www/html/scripts/render-startup.sh

# If not deployed yet, it will be after next Git push
```

### **3.2: Run Startup Script on Render**

```bash
# In Render shell
bash /var/www/html/scripts/render-startup.sh
```

**What it does:**
- ✅ Creates `/data/` directories if needed
- ✅ Creates symlinks for Three.js assets (`/var/www/html/public/three.js/public/` → `/data/public/three.js/public/`)
- ✅ Creates symlinks for Glyph assets (`/var/www/html/public/glyph/glyph3d/` → `/data/public/glyph/glyph3d/`)
- ✅ Sets correct permissions
- ✅ Verifies setup

### **3.3: Verify Symlinks Work**

```bash
# Test if symlink works (should show file)
ls -la /var/www/html/public/three.js/public/textures/grass/grass.jpg

# Test if file is accessible via web
curl -I https://narrrfs.world/public/three.js/public/textures/grass/grass.jpg
# Should return 200 OK
```

---

## 🌐 **STEP 4: TEST IN BROWSER**

**Test the game loads correctly:**

1. **Open:** https://narrrfs.world/public/three.js/3d-riddle-game.html
2. **Check browser console** for 404 errors
3. **Verify:**
   - ✅ No 404 errors for grass.jpg, cloud.jpg, cheesetemple1.png, level1.json
   - ✅ No 404 errors for other textures, models, sounds
   - ✅ Level 1 loads correctly
   - ✅ Grass system loads (should see grass textures)
   - ✅ Background image loads
   - ✅ Collision mesh loads correctly

---

## 📦 **STEP 5: GIT ADD AND PUSH**

**After assets are uploaded and verified:**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Review changes
git status

# Add all changes (API updates, scripts, documentation)
git add .

# Commit
git commit -m "🚀 URGENT: Asset upload scripts and API updates for complete Three.js asset sync

- Created UPLOAD_ALL_ASSETS_URGENT.ps1 for uploading all 1,499 files
- Updated api/discord/upload-assets.php to support glyph paths
- Updated scripts/render-startup.sh to create glyph symlinks
- Created comprehensive asset sync documentation
- Fixed critical missing files: grass.jpg, cloud.jpg, cheesetemple1.png, level1.json
- Uploaded narrrf3d (10 files) and glyph3d (36 files) directories"

# Push to Render
git push origin render-deploy
```

---

## 🔍 **VERIFICATION CHECKLIST**

After upload and push, verify:

- [ ] All files uploaded to `/data/public/three.js/public/`
- [ ] File count matches local (should be ~1,499 files)
- [ ] Symlinks created correctly (`/var/www/html/public/three.js/public/` → `/data/...`)
- [ ] Critical files accessible: grass.jpg, cloud.jpg, cheesetemple1.png, level1.json
- [ ] Game loads in browser without 404 errors
- [ ] Level 1 loads correctly
- [ ] Grass system works
- [ ] Background images load
- [ ] All models and textures load

---

## 🚨 **TROUBLESHOOTING**

### **Error: "Unauthorized - Invalid token"**
- ✅ Check Discord bot secret is correct
- ✅ Verify secret matches Render environment variables

### **Error: "File exceeds upload_max_filesize"**
- ✅ Large files (30-37MB) should work, but may take time
- ✅ Check PHP limits: `php -i | grep upload_max_filesize`

### **Error: "404 Not Found" after upload**
- ✅ Verify symlinks are created: `ls -la /var/www/html/public/three.js/public/`
- ✅ Run startup script: `bash /var/www/html/scripts/render-startup.sh`
- ✅ Check file permissions: `ls -la /data/public/three.js/public/`

### **Upload is taking too long**
- ✅ Normal - 1,499 files will take 30-60 minutes
- ✅ Script shows progress every 25 files
- ✅ Can resume later if interrupted (will skip existing files if re-run)

---

## 📊 **FILES SUMMARY**

**Local Assets:**
- **Total Files:** 1,499 files in `public/three.js/public/`
- **Total Size:** ~500MB+ (varies by directory)
- **Directories:**
  - `textures/` - 3D models, backgrounds, grass, blocks, plants
  - `sounds/` - Music, SFX, weapons
  - `audio/` - Character sounds, gameplay sounds
  - `models/` - Level JSON files
  - `videos/` - Game videos

**Upload Targets:**
- **Persistent Storage:** `/data/public/three.js/public/`
- **Web Access:** `/var/www/html/public/three.js/public/` (symlink)
- **URL Pattern:** `https://narrrfs.world/public/three.js/public/...`

---

## ✅ **SUCCESS CRITERIA**

After completing all steps:
- ✅ All 1,499 files uploaded to `/data/`
- ✅ All symlinks created and working
- ✅ Game loads in browser without 404 errors
- ✅ All levels load correctly
- ✅ All textures, models, sounds work
- ✅ Git changes committed and pushed

---

**Status:** ⚠️ **URGENT - READY TO UPLOAD**  
**Next Step:** Run `UPLOAD_ALL_ASSETS_URGENT.ps1` with Discord bot secret
