# 🚀 NARRRF3D & GLYPH3D UPLOAD GUIDE

**Created:** January 9, 2026  
**Status:** ✅ **READY TO UPLOAD**  
**Purpose:** Complete guide for uploading narrrf3d and glyph3d directories to Render

---

## 📋 **OVERVIEW**

This guide explains how to upload the `narrrf3d` and `glyph3d` directories to Render's persistent storage using the asset upload API.

### **Directories to Upload:**
1. **Narrrf3d** - `public/three.js/public/textures/3d models/narrrf3d/` (10 files)
   - 7 files in root (webp, glb, zip)
   - 3 files in `Meshy_AI_biped/` subdirectory
2. **Glyph3d** - `public/glyph/glyph3d/` (36 files)
   - 35 GLB files (0-9, A-Z)

---

## ✅ **PRE-UPLOAD CHECKLIST**

### **1. Verify Files Exist Locally:**
```powershell
# Check narrrf3d files
Get-ChildItem -Path "public\three.js\public\textures\3d models\narrrf3d" -Recurse -File | Measure-Object
# Expected: 10 files

# Check glyph3d files
Get-ChildItem -Path "public\glyph\glyph3d" -Recurse -File | Measure-Object
# Expected: 36 files
```

### **2. Verify API Endpoint is Deployed:**
```bash
# In Render shell
ls -la /var/www/html/api/discord/upload-assets.php
php -l /var/www/html/api/discord/upload-assets.php
```

### **3. Get Discord Bot Secret:**
- Check Render environment variables: `DISCORD_BOT_SECRET` or `DISCORD_SECRET`
- Or check local `.env` file in `discord/` directory

---

## 🚀 **UPLOAD PROCESS**

### **METHOD 1: Automated PowerShell Script (RECOMMENDED)**

**From Local Windows Machine:**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Upload both directories
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-09\UPLOAD_NARRRF3D_GLYPH3D.ps1 -BotSecret "YOUR_DISCORD_BOT_SECRET"

# Or upload only narrrf3d
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-09\UPLOAD_NARRRF3D_GLYPH3D.ps1 -BotSecret "YOUR_SECRET" -Narrrf3dOnly

# Or upload only glyph3d
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-09\UPLOAD_NARRRF3D_GLYPH3D.ps1 -BotSecret "YOUR_SECRET" -Glyph3dOnly
```

**Script Features:**
- ✅ Automatically detects bot secret from `.env` or `config.js`
- ✅ Uploads all files recursively (including subdirectories)
- ✅ Shows progress for each file
- ✅ Handles errors gracefully
- ✅ Provides detailed summary

**Expected Output:**
```
🚀 Uploading Narrrf3d & Glyph3d Assets to Render
==============================================

📁 Project root: C:\xampp-server\htdocs\narrrfs-world
🔑 Using bot secret: YOUR_SECRET...

📦 Uploading Narrrf3d directory...
   Local: C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\textures\3d models\narrrf3d
   Remote: /data/public/three.js/public/textures/3d models/narrrf3d

[1/10] 📤 Uploading: image.webp...
   Size: 0.18 MB
   To: /data/public/three.js/public/textures/3d models/narrrf3d/image.webp
   ✅ SUCCESS: image.webp uploaded (0.18 MB)

...

📊 FINAL UPLOAD SUMMARY
==============================================
📦 Narrrf3d:
   ✅ Success: 10/10 files
   ❌ Failed: 0/10 files

📦 Glyph3d:
   ✅ Success: 36/36 files
   ❌ Failed: 0/36 files

📊 Overall:
   ✅ Success: 46/46 files
   ❌ Failed: 0/46 files

✅ All files uploaded successfully!
```

---

### **METHOD 2: Manual Upload (Single File)**

**Test with One File:**
```powershell
$BOT_SECRET = "YOUR_DISCORD_BOT_SECRET"

# Upload narrrf3d file
curl.exe -X POST `
  -H "Authorization: $BOT_SECRET" `
  -F "file=@public\three.js\public\textures\3d models\narrrf3d\image.webp" `
  -F "target_path=/data/public/three.js/public/textures/3d models/narrrf3d/image.webp" `
  https://narrrfs.world/api/discord/upload-assets.php

# Upload glyph3d file
curl.exe -X POST `
  -H "Authorization: $BOT_SECRET" `
  -F "file=@public\glyph\glyph3d\A 3d.glb" `
  -F "target_path=/data/public/glyph/glyph3d/A 3d.glb" `
  https://narrrfs.world/api/discord/upload-assets.php
```

---

## 🔍 **POST-UPLOAD VERIFICATION**

### **1. Verify Files in Render Shell:**
```bash
# Check narrrf3d files
ls -la /data/public/three.js/public/textures/3d\ models/narrrf3d/
find /data/public/three.js/public/textures/3d\ models/narrrf3d/ -type f | wc -l
# Expected: 10 files

# Check glyph3d files
ls -la /data/public/glyph/glyph3d/
find /data/public/glyph/glyph3d/ -type f | wc -l
# Expected: 36 files

# Check symlinks
ls -la /var/www/html/public/three.js/public/textures/3d\ models/narrrf3d/
ls -la /var/www/html/public/glyph/glyph3d/
# Should show files (accessed via symlink)
```

### **2. Test in Browser:**
- ✅ Test three.js game loads narrrf3d models
- ✅ Test glyph game loads glyph3d models
- ✅ Check browser console for missing asset errors

---

## 📊 **FILE BREAKDOWN**

### **Narrrf3d Directory (10 files total):**

**Root Files (7 files):**
1. `image.webp` (180KB)
2. `image (1).webp` (117KB)
3. `image (2).webp` (177KB)
4. `narrf_cutout.webp` (413KB)
5. `narrf clean.glb` (8.2MB)
6. `narrf body.glb` (11MB)
7. `narrrf rig.zip` (31MB)

**Subdirectory: `Meshy_AI_biped/` (3 files):**
1. `Meshy_AI_Animation_Running_withSkin.glb`
2. `Meshy_AI_Animation_Walking_withSkin.glb`
3. `Meshy_AI_Character_output.glb`

**Total Size:** ~50MB+ (large files may take time to upload)

### **Glyph3d Directory (36 files total):**

**Files:**
- `0 3d.glb` through `9 3d.glb` (10 files)
- `A 3d.glb` through `Z 3d.glb` (26 files)

**File Sizes:**
- Smallest: 8.4MB (`G 3d.glb`)
- Largest: 37MB (`Y 3d.glb`, `Z 3d.glb`)
- Total Size: ~500MB+ (large files may take time to upload)

---

## 🚨 **IMPORTANT NOTES**

### **File Size Considerations:**
- **Large Files:** Some files are 30-37MB, upload may take 1-2 minutes per file
- **Total Upload Time:** Estimated 30-60 minutes for all 46 files
- **Timeout:** API has 30-minute timeout, should be sufficient
- **Network:** Ensure stable internet connection

### **Symlink Creation:**
- **Automatic:** Symlinks are created automatically by `scripts/render-startup.sh` on deployment
- **Manual:** If symlinks don't exist, they'll be created on next deployment
- **Verification:** Check symlinks exist after upload

### **API Path Support:**
- ✅ **Three.js Paths:** `/data/public/three.js/public/...`
- ✅ **Glyph Paths:** `/data/public/glyph/...`
- ❌ **Other Paths:** Not allowed (security)

---

## 🔧 **TROUBLESHOOTING**

### **Error: "Unauthorized - Invalid token"**
- ✅ Check Discord bot secret is correct
- ✅ Verify secret matches Render environment variables
- ✅ Ensure `Authorization` header is set correctly

### **Error: "File exceeds upload_max_filesize"**
- ✅ Check PHP limits: `php -i | grep upload_max_filesize`
- ✅ Verify file size is under 512MB
- ✅ Large files (30-37MB) should work, but may take time

### **Error: "Invalid target path - must be under /data/public/three.js/public/ or /data/public/glyph/"**
- ✅ Verify target path starts with `/data/public/three.js/public/` or `/data/public/glyph/`
- ✅ Check path doesn't have double slashes or incorrect characters
- ✅ Ensure path matches expected format

### **Error: "Network timeout"**
- ✅ Large files may timeout, retry upload
- ✅ Check internet connection stability
- ✅ Try uploading files one at a time

### **Files Uploaded But Not Accessible:**
- ✅ Verify symlinks exist: `ls -la /var/www/html/public/glyph/glyph3d/`
- ✅ Recreate symlinks if needed (next deployment will auto-create)
- ✅ Check file permissions: `ls -la /data/public/glyph/glyph3d/`

---

## 📝 **RELATED DOCUMENTATION**

- **Asset Upload API Rule:** `12.0/RULES/22_ASSET_UPLOAD_API_RULE.md`
- **Upload Script:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-09/UPLOAD_NARRRF3D_GLYPH3D.ps1`
- **API Endpoint:** `api/discord/upload-assets.php`
- **Startup Script:** `scripts/render-startup.sh`

---

## ✅ **SUCCESS CRITERIA**

After upload:
- ✅ All 10 narrrf3d files in `/data/public/three.js/public/textures/3d models/narrrf3d/`
- ✅ All 36 glyph3d files in `/data/public/glyph/glyph3d/`
- ✅ Files accessible via symlinks in `/var/www/html/`
- ✅ No 404 errors when loading models in browser
- ✅ Models render correctly in three.js and glyph games

---

**Status:** ✅ **READY TO UPLOAD**  
**Next Step:** Run `UPLOAD_NARRRF3D_GLYPH3D.ps1` script from Windows PowerShell
