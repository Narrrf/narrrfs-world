# 🚀 ASSET UPLOAD API RULE - THREE.JS & GLYPH GAME ASSETS

**STATUS:** ✅ **ACTIVE - MANDATORY FOR ALL ASSET DEPLOYMENTS**  
**CREATED:** January 6, 2026  
**PURPOSE:** Standardized API-based asset upload system for large game assets (three.js and glyph game)  
**PRIORITY:** 🚨 **CRITICAL - PRODUCTION DEPLOYMENT RULE**

---

## 🎯 **RULE OVERVIEW**

### **CORE PRINCIPLE:**
**All large game assets (3D models, textures, sounds, audio) for three.js and glyph games MUST be uploaded via API to `/data/` persistent storage. Assets are NEVER committed to Git.**

### **RULE SCOPE:**
- **Three.js Game Assets** - 3D models, textures, sounds, audio files
- **Glyph Game Assets** - Game-specific assets (if applicable)
- **Upload Method** - API endpoint with Discord bot authentication
- **Storage Location** - `/data/` persistent storage (survives deployments)
- **Web Access** - Symlinks from `/var/www/html/` to `/data/`
- **File Size Limit** - Up to 512MB per file

---

## 📋 **MANDATORY PRE-UPLOAD CHECKLIST**

### **✅ PRE-UPLOAD VERIFICATION (MANDATORY):**

1. **Verify API Endpoint is Deployed:**
   ```bash
   # In Render shell
   ls -la /var/www/html/api/discord/upload-assets.php
   php -l /var/www/html/api/discord/upload-assets.php
   ```

2. **Verify Persistent Storage Exists:**
   ```bash
   # In Render shell
   ls -la /data/public/three.js/public/
   ls -la /data/public/glyph/  # If applicable
   ```

3. **Verify Symlinks are Set Up:**
   ```bash
   # In Render shell
   ls -la /var/www/html/public/three.js/public/textures/
   ls -la /var/www/html/public/three.js/public/sounds/
   ```
   **Note:** Symlinks are wiped on deployment - must be recreated after each Git push.

4. **Get Discord Bot Secret:**
   - Check Render environment variables: `DISCORD_BOT_SECRET` or `DISCORD_SECRET`
   - Or check local `.env` file in `discord/` directory

---

## 🚀 **UPLOAD EXECUTION PROTOCOL**

### **✅ METHOD 1: Automated PowerShell Script (RECOMMENDED)**

**From Local Windows Machine:**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Run automated upload script
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_VIA_API.ps1 -BotSecret "YOUR_DISCORD_BOT_SECRET"
```

**Script Features:**
- ✅ Automatically detects bot secret from `.env` or `config.js`
- ✅ Uploads all files in batches (3D models, sounds, audio)
- ✅ Shows progress for each file
- ✅ Handles errors gracefully

### **✅ METHOD 2: Manual curl Commands**

**Test Single File:**
```powershell
$BOT_SECRET = "YOUR_DISCORD_BOT_SECRET"

curl.exe -X POST `
  -H "Authorization: $BOT_SECRET" `
  -F "file=@public\three.js\public\textures\3d models\chest2\Chest2.glb" `
  -F "target_path=/data/public/three.js/public/textures/3d models/chest2/Chest2.glb" `
  https://narrrfs.world/api/discord/upload-assets.php
```

**Expected Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "target_path": "/data/public/three.js/public/textures/3d models/chest2/Chest2.glb",
  "file_size": 7340032,
  "upload_info": {
    "original_name": "Chest2.glb",
    "uploaded_size": 7340032,
    "mime_type": "model/gltf-binary"
  }
}
```

### **✅ METHOD 3: Render Shell SCP/SFTP (Fallback)**

**If API is unavailable:**
```bash
# From local machine (PowerShell)
scp -r "public\three.js\public\textures\3d models" root@RENDER_HOST:/data/public/three.js/public/textures/
```

---

## 🔍 **POST-UPLOAD VERIFICATION PROTOCOL**

### **✅ MANDATORY VERIFICATION STEPS:**

**In Render Shell:**
```bash
# 1. Verify files are in persistent storage
ls /data/public/three.js/public/textures/3d\ models/
ls /data/public/three.js/public/sounds/

# 2. Verify symlinks work
ls /var/www/html/public/three.js/public/textures/3d\ models/
# Should show files (accessed via symlink)

# 3. Test specific file
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
# Should work (accessed via symlink)

# 4. Count files
find /data/public/three.js/public/textures/3d\ models/ -type f | wc -l
find /data/public/three.js/public/sounds/ -type f | wc -l
```

**In Browser:**
- ✅ Test game loads without 404 errors
- ✅ Verify 3D models render correctly
- ✅ Verify sounds play correctly
- ✅ Check browser console for missing asset errors

---

## 🚨 **CRITICAL REQUIREMENTS**

### **✅ STORAGE LOCATION:**
- **MUST:** Upload to `/data/` (persistent storage)
- **NEVER:** Upload to `/var/www/html/` (gets wiped on deployments)

### **✅ SYMLINKS:**
- **MUST:** Create symlinks after each deployment
- **WHEN:** After every Git push (symlinks are wiped)
- **HOW:** Run `RECREATE_SYMLINKS.sh` or manual commands

### **✅ FILE SIZE LIMITS:**
- **Maximum:** 512MB per file
- **Configured:** Via `.htaccess` and PHP `ini_set()`
- **Timeout:** 30 minutes for large files

### **✅ AUTHENTICATION:**
- **Required:** Discord bot secret (`DISCORD_BOT_SECRET` or `DISCORD_SECRET`)
- **Method:** HTTP header `Authorization: YOUR_SECRET`
- **Security:** Only authenticated requests can upload

---

## 📝 **ASSET CATEGORIES**

### **Three.js Game Assets:**
- **3D Models:** `/data/public/three.js/public/textures/3d models/`
  - GLB files (`.glb`)
  - GLTF files (`.gltf`)
  - FBX files (`.fbx`)
  - Texture files (`.tga`, `.png`, `.jpg`)
- **Sounds:** `/data/public/three.js/public/sounds/`
  - SFX files (`.mp3`, `.wav`, `.ogg`)
- **Audio:** `/data/public/three.js/public/audio/`
  - Background music, ambient sounds

### **Glyph Game Assets:**
- **Game Assets:** `/data/public/glyph/`
  - Any glyph game-specific assets

---

## 🔄 **DEPLOYMENT WORKFLOW**

### **Standard Deployment Process:**

1. **Code Changes:**
   ```powershell
   git add .
   git commit -m "Update game code"
   git push origin render-deploy
   ```

2. **Wait for Render Deployment** (1-2 minutes)

3. **Recreate Symlinks (CRITICAL):**
   ```bash
   # In Render shell
   bash 12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RECREATE_SYMLINKS.sh
   ```

4. **Upload New Assets (if any):**
   ```powershell
   # From local machine
   .\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_VIA_API.ps1 -BotSecret "YOUR_SECRET"
   ```

5. **Verify Assets:**
   ```bash
   # In Render shell
   ls /data/public/three.js/public/textures/3d\ models/
   ls /var/www/html/public/three.js/public/textures/3d\ models/
   ```

6. **Test Game in Browser:**
   - Check for 404 errors
   - Verify models load
   - Verify sounds play

---

## 🚨 **TROUBLESHOOTING**

### **Error: "Unauthorized - Invalid token"**
- ✅ Check Discord bot secret is correct
- ✅ Verify secret matches Render environment variables
- ✅ Ensure `Authorization` header is set correctly

### **Error: "File exceeds upload_max_filesize"**
- ✅ Check PHP limits: `php -i | grep upload_max_filesize`
- ✅ Verify `.htaccess` is deployed
- ✅ Check file size is under 512MB

### **Error: "No file uploaded or upload error"**
- ✅ Check file path is correct
- ✅ Verify file exists locally
- ✅ Check file size is under limit
- ✅ Verify curl command syntax

### **Error: "Symlink not found" (404 in browser)**
- ✅ Recreate symlinks after deployment
- ✅ Verify symlinks point to `/data/`
- ✅ Check symlink permissions

### **Error: "Failed to create target directory"**
- ✅ Check `/data/` directory exists
- ✅ Verify write permissions on `/data/`
- ✅ Check disk space

---

## 📚 **DOCUMENTATION REFERENCES**

- **Setup Guide:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RENDER_PERSISTENT_ASSETS_SOLUTION.md`
- **API Upload Guide:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/LARGE_FILE_UPLOAD_SETUP.md`
- **Quick Start:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/QUICK_START_API_UPLOAD.md`
- **Upload Script:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/UPLOAD_ASSETS_VIA_API.ps1`
- **Symlink Script:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RECREATE_SYMLINKS.sh`
- **Technical Docs:** 
  - `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md`
  - `12.0/YEAR_END_2025/GAME_08_GLYPH_MEMORY_COMPLETE_TECHNICAL.md`

---

## ✅ **SUCCESS CRITERIA**

After upload:
- ✅ Files appear in `/data/public/three.js/public/` or `/data/public/glyph/`
- ✅ Files accessible via symlinks in `/var/www/html/`
- ✅ Game loads without 404 errors
- ✅ All models/textures/sounds work correctly
- ✅ Browser console shows no missing asset errors

---

## 🎯 **PREVENTION CHECKLIST**

Before deploying assets:
- [ ] Verify API endpoint is deployed
- [ ] Check persistent storage exists (`/data/`)
- [ ] Verify symlinks are set up
- [ ] Get Discord bot secret
- [ ] Test with single file first
- [ ] Verify file sizes are under 512MB
- [ ] Check disk space on Render

After deployment:
- [ ] Recreate symlinks (they're wiped on deployment)
- [ ] Upload any new assets
- [ ] Verify assets in `/data/`
- [ ] Test game in browser
- [ ] Check for 404 errors

---

**Status:** ✅ **ACTIVE - MANDATORY FOR ALL ASSET DEPLOYMENTS**  
**Last Updated:** January 6, 2026  
**Next Review:** After first major asset deployment

