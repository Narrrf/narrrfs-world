# 🔧 Render Shell Commands - Asset Verification & Setup

**Date:** January 4, 2026  
**Purpose:** Commands to run IN Render shell to verify and prepare for asset uploads  
**Current Location:** `/var/www/html#` (Render shell)

---

## 🔍 **STEP 1: Check What's Currently There**

**Run these commands in your Render shell:**

```bash
# Check if directories exist
ls -la /var/www/html/public/three.js/public/

# Check textures directory
ls -la /var/www/html/public/three.js/public/textures/

# Check if 3d models directory exists
ls -la /var/www/html/public/three.js/public/textures/3d\ models/ 2>/dev/null || echo "Directory does not exist"

# Check sounds directory
ls -la /var/www/html/public/three.js/public/sounds/ 2>/dev/null || echo "Directory does not exist"

# Check audio directory
ls -la /var/www/html/public/three.js/public/audio/ 2>/dev/null || echo "Directory does not exist"
```

---

## 📁 **STEP 2: Create Directories (If They Don't Exist)**

**If directories are missing, create them:**

```bash
# Create textures directory structure
mkdir -p /var/www/html/public/three.js/public/textures/3d\ models/
mkdir -p /var/www/html/public/three.js/public/sounds/
mkdir -p /var/www/html/public/three.js/public/audio/

# Set correct permissions
chmod -R 755 /var/www/html/public/three.js/public/
chown -R www-data:www-data /var/www/html/public/three.js/public/

# Verify directories created
ls -la /var/www/html/public/three.js/public/
```

---

## 📊 **STEP 3: Get Your Render Hostname (For SCP)**

**You need this for uploading FROM your local machine:**

```bash
# Get hostname
hostname

# Or check Render environment
echo $RENDER_SERVICE_NAME
echo $RENDER_EXTERNAL_HOSTNAME

# Full hostname format is usually:
# srv-XXXXX.onrender.com
```

**Write down the hostname** - You'll need it for SCP commands from your local machine.

---

## ✅ **STEP 4: After Upload - Verification**

**After uploading from local machine, verify files are there:**

```bash
# Count files in 3d models
find /var/www/html/public/three.js/public/textures/3d\ models/ -type f | wc -l

# Count files in sounds
find /var/www/html/public/three.js/public/sounds/ -type f | wc -l

# Check specific important files
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
ls /var/www/html/public/three.js/public/textures/3d\ models/tree-with-arms/tree-with-arms.glb
ls /var/www/html/public/three.js/public/textures/3d\ models/secret\ door\ medieval/secret-door.glb

# Check file sizes (should be large for models)
du -sh /var/www/html/public/three.js/public/textures/3d\ models/
du -sh /var/www/html/public/three.js/public/sounds/
```

---

## 🚨 **STEP 5: Fix Permissions (If Files Don't Load)**

**If files upload but give 404 errors in browser:**

```bash
# Fix permissions recursively
chmod -R 755 /var/www/html/public/three.js/public/
chown -R www-data:www-data /var/www/html/public/three.js/public/

# Verify permissions
ls -la /var/www/html/public/three.js/public/textures/3d\ models/ | head -5
```

---

## 📋 **QUICK CHECKLIST (Run in Render Shell)**

```bash
# 1. Check current state
echo "=== Checking directories ==="
ls -la /var/www/html/public/three.js/public/ 2>/dev/null || echo "Base directory missing"

# 2. Create if missing
echo "=== Creating directories ==="
mkdir -p /var/www/html/public/three.js/public/textures/3d\ models/
mkdir -p /var/www/html/public/three.js/public/sounds/
mkdir -p /var/www/html/public/three.js/public/audio/

# 3. Set permissions
echo "=== Setting permissions ==="
chmod -R 755 /var/www/html/public/three.js/public/
chown -R www-data:www-data /var/www/html/public/three.js/public/

# 4. Get hostname for SCP
echo "=== Render Hostname ==="
hostname
echo "Use this for SCP: root@$(hostname)"

# 5. Verify structure
echo "=== Directory structure ==="
tree -L 3 /var/www/html/public/three.js/public/ 2>/dev/null || find /var/www/html/public/three.js/public/ -maxdepth 3 -type d
```

---

## 🎯 **WHAT TO DO NEXT**

1. **Run the checklist above** in Render shell to prepare directories
2. **Note your hostname** (from `hostname` command)
3. **Go back to your LOCAL Windows machine**
4. **Run SCP commands** (see `RENDER_ASSET_UPLOAD_GUIDE.md`)
5. **Return to Render shell** to verify files uploaded

---

## 📝 **IMPORTANT NOTES**

- **You can't upload FROM Render shell** - Must use SCP from local machine
- **Render shell is for verification** - Check files, fix permissions, etc.
- **Hostname format:** Usually `srv-XXXXX.onrender.com` or similar
- **Permissions matter** - Files must be readable by web server (`www-data`)

---

**Status:** ✅ **READY TO USE**  
**Next:** Run the checklist commands in your Render shell, then switch to local machine for upload

