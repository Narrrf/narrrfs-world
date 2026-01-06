# ✅ CRITICAL CORRECTION: Use /data/ for Persistent Asset Storage

**Date:** January 4, 2026  
**Issue Identified:** Assets in `/var/www/html/` get deleted on each Git push  
**Correct Solution:** Store assets in `/data/` (persistent) with symlinks to `/var/www/html/`  
**Status:** ✅ **SOLUTION IDENTIFIED AND DOCUMENTED**

---

## 🎯 **THE PROBLEM YOU IDENTIFIED**

**You correctly identified:**
- ✅ `/var/www/html/` gets wiped on each Git push/deployment
- ✅ `/data/` is persistent storage (like database)
- ✅ Assets uploaded to `/var/www/html/` would be **LOST on next push**

**This is exactly correct!** We need to use `/data/` for persistent storage.

---

## ✅ **THE CORRECT SOLUTION**

### **Storage Strategy:**
1. **Persistent Storage:** `/data/public/three.js/public/` (survives deployments)
2. **Web Access:** Symlinks from `/var/www/html/public/three.js/public/` to `/data/`
3. **Upload Target:** Always upload to `/data/` (NOT `/var/www/html/`)

### **Why This Works:**
- ✅ `/data/` survives deployments (same as database)
- ✅ Symlinks allow web server to access files
- ✅ One-time upload (assets persist forever)
- ✅ Same pattern as database (`/data/narrrf_world.sqlite`)

---

## 📋 **CORRECTED SETUP PROCESS**

### **Step 1: In Render Shell (Prepare Directories & Symlinks)**

```bash
# Run the updated preparation script
bash PREPARE_RENDER_DIRECTORIES_UPDATED.sh

# OR manually:
# Create persistent directories in /data/
mkdir -p /data/public/three.js/public/textures/3d\ models/
mkdir -p /data/public/three.js/public/sounds/
mkdir -p /data/public/three.js/public/audio/
chmod -R 755 /data/public/three.js/public/
chown -R www-data:www-data /data/public/three.js/public/

# Remove old directories (if exist)
rm -rf /var/www/html/public/three.js/public/textures/3d\ models/
rm -rf /var/www/html/public/three.js/public/sounds/
rm -rf /var/www/html/public/three.js/public/audio/

# Create parent directories
mkdir -p /var/www/html/public/three.js/public/textures/
mkdir -p /var/www/html/public/three.js/public/

# Create symlinks
ln -s /data/public/three.js/public/textures/3d\ models /var/www/html/public/three.js/public/textures/3d\ models
ln -s /data/public/three.js/public/sounds /var/www/html/public/three.js/public/sounds
ln -s /data/public/three.js/public/audio /var/www/html/public/three.js/public/audio
```

### **Step 2: From Local Windows (Upload to /data/)**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Upload to /data/ (PERSISTENT - survives deployments)
scp -r "public\three.js\public\textures\3d models" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/textures/

scp -r "public\three.js\public\sounds" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/

scp -r "public\three.js\public\audio" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/
```

**Key:** Upload to `/data/` NOT `/var/www/html/`!

### **Step 3: Verify (In Render Shell)**

```bash
# Check persistent storage
ls /data/public/three.js/public/textures/3d\ models/

# Check symlinks work
ls /var/www/html/public/three.js/public/textures/3d\ models/
# Should show same files (accessed via symlink)

# Test specific file
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
# Should work (accessed via symlink)
```

---

## 📝 **UPDATED DOCUMENTATION**

**All documentation has been updated to use `/data/` paths:**

- ✅ `RENDER_PERSISTENT_ASSETS_SOLUTION.md` - Complete solution guide
- ✅ `UPLOAD_ASSETS_TO_RENDER.ps1` - Updated script (uploads to `/data/`)
- ✅ `PREPARE_RENDER_DIRECTORIES_UPDATED.sh` - Updated preparation script
- ✅ `11_THREE_JS_RULE.md` §13 - Updated rule with `/data/` paths
- ✅ `GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md` - Updated technical docs

---

## 🚨 **WHY THIS IS CRITICAL**

### **Without /data/ (Wrong - What We Almost Did):**
- ❌ Upload to `/var/www/html/` → **Deleted on next Git push**
- ❌ Must re-upload every deployment → **Waste of time**
- ❌ Assets lost on every push → **Game breaks**

### **With /data/ + Symlinks (Correct - What We Should Do):**
- ✅ Upload to `/data/` → **Survives deployments**
- ✅ Symlink to `/var/www/html/` → **Web server can access**
- ✅ One-time upload → **Works forever**
- ✅ Same pattern as database → **Consistent architecture**

---

## ✅ **FINAL SUMMARY**

**Storage:** `/data/public/three.js/public/` (persistent, like database)  
**Access:** Symlinks from `/var/www/html/public/three.js/public/` to `/data/`  
**Upload:** SCP to `/data/` (NOT `/var/www/html/`)  
**Result:** Assets survive all deployments (just like `/data/narrrf_world.sqlite`)

---

**Status:** ✅ **CORRECT SOLUTION IDENTIFIED**  
**Next:** Run preparation script in Render shell, then upload assets to `/data/`

