# ✅ Final Upload Instructions - Three.js Assets to Render

**Date:** January 4, 2026  
**Status:** ✅ **DIRECTORIES READY - READY TO UPLOAD**  
**Render Hostname:** `srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2`

---

## ✅ **STEP 1: COMPLETE (You Already Did This!)**

**In Render Shell - Directories Created:**
```bash
✅ mkdir -p /var/www/html/public/three.js/public/textures/3d\ models/
✅ mkdir -p /var/www/html/public/three.js/public/sounds/
✅ mkdir -p /var/www/html/public/three.js/public/audio/
✅ chmod -R 755 /var/www/html/public/three.js/public/
✅ chown -R www-data:www-data /var/www/html/public/three.js/public/
```

**Verification:**
```
✅ audio/     - Created
✅ sounds/    - Created  
✅ textures/  - Created
```

---

## 🚀 **STEP 2: UPLOAD FROM YOUR LOCAL WINDOWS MACHINE**

**Switch to your LOCAL Windows PowerShell and run:**

### **Option A: Run the Script (Easiest)**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_TO_RENDER.ps1
```

### **Option B: Manual Commands (If Script Doesn't Work)**

```powershell
# Navigate to project
cd C:\xampp-server\htdocs\narrrfs-world

# Upload 3D models (takes 10-20 minutes - LARGEST FILES)
scp -r "public\three.js\public\textures\3d models" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/var/www/html/public/three.js/public/textures/

# Upload sounds (takes 2-5 minutes)
scp -r "public\three.js\public\sounds" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/var/www/html/public/three.js/public/

# Upload audio (if exists, takes 2-5 minutes)
scp -r "public\three.js\public\audio" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/var/www/html/public/three.js/public/
```

**Note:** If hostname doesn't work, try:
- `srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com` (add `.onrender.com`)
- Or check Render dashboard for full SSH connection string

---

## ✅ **STEP 3: VERIFY IN RENDER SHELL (After Upload)**

**Go back to Render shell and run:**

```bash
# Check 3D models directory
ls /var/www/html/public/three.js/public/textures/3d\ models/

# Check specific important files
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
ls /var/www/html/public/three.js/public/textures/3d\ models/tree-with-arms/tree-with-arms.glb
ls /var/www/html/public/three.js/public/textures/3d\ models/secret\ door\ medieval/secret-door.glb

# Check sounds
ls /var/www/html/public/three.js/public/sounds/

# Count files (should show many)
find /var/www/html/public/three.js/public/textures/3d\ models/ -type f | wc -l
find /var/www/html/public/three.js/public/sounds/ -type f | wc -l

# Fix permissions if needed (if files don't load in browser)
chmod -R 755 /var/www/html/public/three.js/public/
chown -R www-data:www-data /var/www/html/public/three.js/public/
```

---

## 📋 **ASSETS THAT MUST BE UPLOADED**

### **3D Models Directory (`textures/3d models/`):**
- ✅ `chest1/` - Chest model 1 (GLB + TGA textures)
- ✅ `chest2/` - Chest model 2 (GLB + TGA textures) - **CRITICAL**
- ✅ `Survival Pack/` - Bear traps, torches, survival items (FBX/OBJ)
- ✅ `phoenix2/` - Dragon boss textures (Black/Blue/Brown/Eye/Gold/Green/Red/White)
- ✅ `secret door medieval/` - Secret door model (GLB)
- ✅ `tree-with-arms/` - Tree model (GLB) - **CRITICAL**
- ✅ `tree dead lians/` - Dead tree model (GLB)
- ✅ All other 3D model subdirectories

### **Sounds Directory (`sounds/`):**
- ✅ All SFX files (`.mp3`, `.wav`, `.ogg`)
- ✅ Footstep sounds
- ✅ Jump sounds
- ✅ Chest opening sounds
- ✅ Weapon sounds
- ✅ Boss sounds

### **Audio Directory (`audio/`):**
- ✅ Background music (if exists)
- ✅ Ambient sounds (if exists)

---

## ⏱️ **ESTIMATED UPLOAD TIME**

- **3D Models:** 10-20 minutes (largest files, ~1-2GB total)
- **Sounds:** 2-5 minutes (~100-500MB)
- **Audio:** 2-5 minutes (if exists, ~50-200MB)
- **Total:** 15-30 minutes

---

## 🚨 **TROUBLESHOOTING**

### **If SCP Command Not Found:**
```powershell
# Install OpenSSH on Windows
Add-WindowsCapability -Online -Name OpenSSH.Client~~~~0.0.1.0

# Or use WinSCP (GUI): https://winscp.net/
```

### **If Permission Denied:**
1. Test SSH first: `ssh root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com`
2. If SSH works, SCP will work too
3. Check Render service is running

### **If Files Upload But 404 in Browser:**
```bash
# In Render shell, fix permissions
chmod -R 755 /var/www/html/public/three.js/public/
chown -R www-data:www-data /var/www/html/public/three.js/public/
```

---

## ✅ **SUCCESS CHECKLIST**

- [ ] **Directories created** in Render (✅ DONE)
- [ ] **Permissions set** in Render (✅ DONE)
- [ ] **Upload 3D models** from local machine
- [ ] **Upload sounds** from local machine
- [ ] **Upload audio** from local machine (if exists)
- [ ] **Verify files** in Render shell
- [ ] **Test game** in browser - check for 404s
- [ ] **Fix permissions** if files don't load

---

## 📝 **NEXT STEPS**

1. **Switch to local Windows machine**
2. **Run upload script OR manual commands**
3. **Wait for upload** (15-30 minutes)
4. **Verify in Render shell**
5. **Test game in browser**

---

**Status:** ✅ **READY TO UPLOAD**  
**Next:** Run upload commands from your local Windows machine

