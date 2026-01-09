# 🔍 RENDER VERIFICATION INSTRUCTIONS

**Created:** January 9, 2026  
**Purpose:** Step-by-step instructions for verifying Render server status  
**Status:** ✅ **READY TO RUN ON RENDER**

---

## 🎯 **QUICK START - RUN THIS FIRST**

### **In Render Shell, Run:**

```bash
# Option 1: Run automated verification script (RECOMMENDED)
bash /var/www/html/12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-09/VERIFY_RENDER_STATUS.sh

# Option 2: Manual verification commands (see below)
```

---

## 📋 **WHAT TO CHECK**

The verification script will check:

### **1. /data/ Persistent Storage:**
- ✅ If `/data/public/three.js/public/` directories exist
- ✅ If critical missing files exist:
  - `grass.jpg` (404 error)
  - `cloud.jpg` (404 error)
  - `cheesetemple1.png` (404 error)
  - `level1.json` (404 error)
- ✅ File counts for each asset type
- ✅ Disk usage

### **2. Symlinks in /var/www/html/:**
- ✅ If symlinks exist for:
  - `textures/3d models/`
  - `sounds/`
  - `audio/`
- ✅ If symlinks point to correct `/data/` location
- ✅ If files are accessible via symlinks

### **3. Startup Script:**
- ✅ If startup script exists at `/var/www/html/scripts/render-startup.sh`
- ✅ If script should have created symlinks automatically

---

## 🔧 **MANUAL VERIFICATION (If Script Not Available)**

### **Check /data/ Persistent Storage:**

```bash
# 1. Check base directory
ls -la /data/public/three.js/public/

# 2. Check critical missing files
ls -la /data/public/three.js/public/textures/grass/grass.jpg
ls -la /data/public/three.js/public/textures/grass/cloud.jpg
ls -la /data/public/three.js/public/textures/backgrounds/cheesetemple1.png
ls -la /data/public/three.js/public/models/cheese-temple/level1.json

# 3. Count files
find /data/public/three.js/public/textures/3d\ models/ -type f | wc -l
find /data/public/three.js/public/sounds/ -type f | wc -l
find /data/public/three.js/public/audio/ -type f | wc -l
find /data/public/three.js/public/models/ -type f | wc -l
```

### **Check Symlinks:**

```bash
# 1. Check if symlinks exist
ls -la /var/www/html/public/three.js/public/textures/
ls -la /var/www/html/public/three.js/public/sounds/
ls -la /var/www/html/public/three.js/public/audio/

# 2. Verify symlinks point to /data/
ls -la /var/www/html/public/three.js/public/textures/ | grep "3d models"
ls -la /var/www/html/public/three.js/public/ | grep sounds
ls -la /var/www/html/public/three.js/public/ | grep audio

# 3. Test file access via symlink
ls -la /var/www/html/public/three.js/public/textures/grass/grass.jpg
ls -la /var/www/html/public/three.js/public/models/cheese-temple/level1.json
```

### **Check Startup Script:**

```bash
# 1. Check if script exists
ls -la /var/www/html/scripts/render-startup.sh

# 2. View script contents (check symlink creation section)
cat /var/www/html/scripts/render-startup.sh | grep -A 30 "STEP 3"
```

---

## ✅ **EXPECTED RESULTS**

### **If Everything is Set Up Correctly:**

```
✅ /data/public/three.js/public/ exists
✅ grass.jpg EXISTS
✅ cloud.jpg EXISTS
✅ cheesetemple1.png EXISTS
✅ level1.json EXISTS
✅ textures/3d models/ is a symlink
✅ sounds/ is a symlink
✅ audio/ is a symlink
✅ grass.jpg accessible via symlink
✅ level1.json accessible via symlink
✅ Startup script exists
```

### **If Files Are Missing:**

```
❌ grass.jpg MISSING
❌ cloud.jpg MISSING
❌ cheesetemple1.png MISSING
❌ level1.json MISSING
⚠️ ACTION REQUIRED: Upload assets to /data/ first
```

### **If Symlinks Are Missing:**

```
❌ textures/3d models/ DOES NOT EXIST
❌ sounds/ DOES NOT EXIST
❌ audio/ DOES NOT EXIST
⚠️ ACTION REQUIRED: Recreate symlinks
```

---

## 🔧 **FIXES BASED ON VERIFICATION RESULTS**

### **If Files Are Missing in /data/:**

**Upload Missing Assets:**
```powershell
# From local Windows machine
cd C:\xampp-server\htdocs\narrrfs-world
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_VIA_API.ps1 -BotSecret "YOUR_DISCORD_BOT_SECRET"
```

**Or upload specific files manually:**
```powershell
$BOT_SECRET = "YOUR_DISCORD_BOT_SECRET"
curl.exe -X POST `
  -H "Authorization: $BOT_SECRET" `
  -F "file=@public\three.js\public\textures\grass\grass.jpg" `
  -F "target_path=/data/public/three.js/public/textures/grass/grass.jpg" `
  https://narrrfs.world/api/discord/upload-assets.php
```

### **If Symlinks Are Missing:**

**Recreate Symlinks:**
```bash
# In Render shell
bash /var/www/html/12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RECREATE_SYMLINKS.sh
```

**Or recreate manually:**
```bash
# Create parent directories
mkdir -p /var/www/html/public/three.js/public/textures/
mkdir -p /var/www/html/public/three.js/public/

# Remove existing (if any)
rm -rf /var/www/html/public/three.js/public/textures/3d\ models/
rm -rf /var/www/html/public/three.js/public/sounds/
rm -rf /var/www/html/public/three.js/public/audio/

# Create symlinks
ln -s /data/public/three.js/public/textures/3d\ models /var/www/html/public/three.js/public/textures/3d\ models
ln -s /data/public/three.js/public/sounds /var/www/html/public/three.js/public/sounds
ln -s /data/public/three.js/public/audio /var/www/html/public/three.js/public/audio

# Set permissions
chown -h www-data:www-data /var/www/html/public/three.js/public/textures/3d\ models
chown -h www-data:www-data /var/www/html/public/three.js/public/sounds
chown -h www-data:www-data /var/www/html/public/three.js/public/audio
```

### **If Startup Script Not Running:**

**Check Render Deployment Logs:**
- Go to Render dashboard
- Check deployment logs for startup script output
- Look for: "🚀 Narrrf's World - Render Startup Script"
- Verify: "✅ Three.js asset symlinks created"

**If script not running:**
- Verify script exists: `ls -la /var/www/html/scripts/render-startup.sh`
- Check Render build settings (should run script on startup)
- Check script permissions: `chmod +x /var/www/html/scripts/render-startup.sh`

---

## 📊 **VERIFICATION SUMMARY**

After running verification, you should know:

1. **✅ Files Exist?** - Are assets in `/data/`?
2. **✅ Symlinks Exist?** - Are symlinks created in `/var/www/html/`?
3. **✅ Files Accessible?** - Can files be accessed via symlinks?
4. **✅ Startup Script Working?** - Is script creating symlinks automatically?

---

## 🚀 **NEXT STEPS**

Based on verification results:

1. **If files missing:** Upload assets via API (see fixes above)
2. **If symlinks missing:** Recreate symlinks (see fixes above)
3. **If everything exists:** Check web server configuration or path resolution code
4. **If startup script not running:** Check Render deployment settings

---

## 📝 **RELATED DOCUMENTATION**

- **Verification Script:** `VERIFY_RENDER_STATUS.sh`
- **Symlink Recreation:** `RECREATE_SYMLINKS.sh`
- **Asset Upload Rule:** `12.0/RULES/22_ASSET_UPLOAD_API_RULE.md`
- **Production Review:** `THREE_JS_PRODUCTION_REVIEW_2026-01-09.md`

---

**Status:** ✅ **READY TO RUN ON RENDER**  
**Next:** Run verification script in Render shell to check everything
