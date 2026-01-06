# 🚨 CRITICAL: Render Persistent Assets Solution - /data Directory

**Date:** January 4, 2026  
**Issue:** Assets in `/var/www/html` get deleted on each deployment  
**Solution:** Store assets in `/data` (persistent) and symlink to `/var/www/html`  
**Status:** ✅ **CORRECT SOLUTION IDENTIFIED**

---

## 🎯 **THE PROBLEM**

**Current Setup:**
- `/var/www/html/` - **Gets wiped on each Git push/deployment**
- `/data/` - **Persistent storage that survives deployments**

**If we upload assets to `/var/www/html/public/three.js/public/`:**
- ❌ **Assets will be DELETED on next Git push**
- ❌ **Must re-upload every time** (waste of time)
- ❌ **Not a sustainable solution**

**Correct Solution:**
- ✅ **Store assets in `/data/`** (persistent)
- ✅ **Symlink to `/var/www/html/`** (web server can access)
- ✅ **Assets survive deployments** (like database)

---

## ✅ **THE CORRECT SOLUTION**

### **Step 1: Create Directories in /data (Persistent)**

**In Render Shell:**
```bash
# Create persistent asset directories in /data
mkdir -p /data/public/three.js/public/textures/3d\ models/
mkdir -p /data/public/three.js/public/sounds/
mkdir -p /data/public/three.js/public/audio/

# Set permissions
chmod -R 755 /data/public/three.js/public/
chown -R www-data:www-data /data/public/three.js/public/
```

### **Step 2: Create Symlinks (So Web Server Can Access)**

**In Render Shell:**
```bash
# Remove existing directories if they exist (from previous setup)
rm -rf /var/www/html/public/three.js/public/textures/3d\ models/
rm -rf /var/www/html/public/three.js/public/sounds/
rm -rf /var/www/html/public/three.js/public/audio/

# Create parent directories if needed
mkdir -p /var/www/html/public/three.js/public/textures/
mkdir -p /var/www/html/public/three.js/public/

# Create symlinks from /var/www/html to /data
ln -s /data/public/three.js/public/textures/3d\ models /var/www/html/public/three.js/public/textures/3d\ models
ln -s /data/public/three.js/public/sounds /var/www/html/public/three.js/public/sounds
ln -s /data/public/three.js/public/audio /var/www/html/public/three.js/public/audio

# Verify symlinks
ls -la /var/www/html/public/three.js/public/textures/
ls -la /var/www/html/public/three.js/public/
# Should show symlinks pointing to /data
```

### **Step 3: Upload Assets to /data (Persistent Location)**

**From Local Windows Machine:**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Upload to /data (persistent location)
scp -r "public\three.js\public\textures\3d models" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/textures/

scp -r "public\three.js\public\sounds" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/

scp -r "public\three.js\public\audio" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/
```

**Note:** Upload to `/data/` NOT `/var/www/html/`!

---

## 🔄 **RENDER STARTUP SCRIPT (If Needed)**

**If symlinks don't work, add to Render startup script:**

```bash
# In Render startup script (if you have one)
# Restore symlinks after deployment

# Create parent directories
mkdir -p /var/www/html/public/three.js/public/textures/
mkdir -p /var/www/html/public/three.js/public/

# Remove old directories (if they exist)
rm -rf /var/www/html/public/three.js/public/textures/3d\ models/
rm -rf /var/www/html/public/three.js/public/sounds/
rm -rf /var/www/html/public/three.js/public/audio/

# Create symlinks
ln -sf /data/public/three.js/public/textures/3d\ models /var/www/html/public/three.js/public/textures/3d\ models
ln -sf /data/public/three.js/public/sounds /var/www/html/public/three.js/public/sounds
ln -sf /data/public/three.js/public/audio /var/www/html/public/three.js/public/audio

# Set permissions
chmod -R 755 /data/public/three.js/public/
chown -R www-data:www-data /data/public/three.js/public/
```

---

## ✅ **VERIFICATION**

**In Render Shell (After Upload):**
```bash
# Check /data directory (persistent)
ls /data/public/three.js/public/textures/3d\ models/
ls /data/public/three.js/public/sounds/

# Check symlinks
ls -la /var/www/html/public/three.js/public/textures/
# Should show: 3d models -> /data/public/three.js/public/textures/3d models

# Verify files accessible via symlink
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
# Should show the file (accessed via symlink)

# Test web access (should work)
curl -I https://narrrfs.world/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
# Should return 200 OK (or 404 if file doesn't exist yet)
```

---

## 📋 **UPDATED UPLOAD COMMANDS**

### **Correct SCP Commands (Upload to /data):**

```powershell
# From Local Windows Machine
cd C:\xampp-server\htdocs\narrrfs-world

# Upload to /data (PERSISTENT - survives deployments)
scp -r "public\three.js\public\textures\3d models" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/textures/

scp -r "public\three.js\public\sounds" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/

scp -r "public\three.js\public\audio" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/
```

**Key Difference:** Upload to `/data/` NOT `/var/www/html/`!

---

## 🔄 **COMPLETE SETUP PROCESS**

### **1. In Render Shell (Prepare Directories & Symlinks):**

```bash
# Create persistent directories
mkdir -p /data/public/three.js/public/textures/3d\ models/
mkdir -p /data/public/three.js/public/sounds/
mkdir -p /data/public/three.js/public/audio/

# Set permissions
chmod -R 755 /data/public/three.js/public/
chown -R www-data:www-data /data/public/three.js/public/

# Remove old directories (if exist from previous setup)
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

# Verify
ls -la /var/www/html/public/three.js/public/textures/
ls -la /var/www/html/public/three.js/public/
```

### **2. From Local Windows (Upload Assets):**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Upload to /data (persistent)
scp -r "public\three.js\public\textures\3d models" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/textures/
scp -r "public\three.js\public\sounds" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/
scp -r "public\three.js\public\audio" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/
```

### **3. In Render Shell (Verify):**

```bash
# Check persistent storage
ls /data/public/three.js/public/textures/3d\ models/

# Check symlinks work
ls /var/www/html/public/three.js/public/textures/3d\ models/
# Should show same files (via symlink)

# Test specific file
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
```

---

## 🚨 **WHY THIS IS CRITICAL**

### **Without /data (Wrong Approach):**
- ❌ Upload to `/var/www/html/` → **Deleted on next Git push**
- ❌ Must re-upload every deployment → **Waste of time**
- ❌ Assets lost on every push → **Game breaks**

### **With /data + Symlinks (Correct Approach):**
- ✅ Upload to `/data/` → **Survives deployments**
- ✅ Symlink to `/var/www/html/` → **Web server can access**
- ✅ One-time upload → **Works forever**
- ✅ Same pattern as database → **Consistent architecture**

---

## 📝 **UPDATED DOCUMENTATION NEEDED**

**Files to Update:**
- ✅ `RENDER_ASSET_UPLOAD_GUIDE.md` - Update paths to `/data/`
- ✅ `FINAL_UPLOAD_INSTRUCTIONS.md` - Update paths to `/data/`
- ✅ `QUICK_UPLOAD_COMMANDS.md` - Update paths to `/data/`
- ✅ `UPLOAD_ASSETS_TO_RENDER.ps1` - Update script to use `/data/`
- ✅ `11_THREE_JS_RULE.md` - Update §13 with `/data/` paths
- ✅ `GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md` - Update asset paths

---

## ✅ **FINAL SOLUTION SUMMARY**

**Storage:** `/data/public/three.js/public/` (persistent)  
**Access:** Symlinks from `/var/www/html/public/three.js/public/` to `/data/`  
**Upload:** SCP to `/data/` (not `/var/www/html/`)  
**Result:** Assets survive all deployments (like database)

---

**Status:** ✅ **CORRECT SOLUTION IDENTIFIED**  
**Next:** Update all documentation and upload scripts to use `/data/` paths

