# ⚡ Quick Upload Commands - Three.js Assets to Render

**Date:** January 4, 2026  
**Your Render Hostname:** `srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2`  
**Status:** ✅ **READY TO USE**

---

## 🚀 **OPTION 1: Run the Script (Easiest)**

**From your LOCAL Windows PowerShell:**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_TO_RENDER.ps1
```

**That's it!** The script handles everything.

---

## 🔧 **OPTION 2: Manual Commands (If Script Doesn't Work)**

**From your LOCAL Windows PowerShell, run these commands one by one:**

```powershell
# Navigate to project
cd C:\xampp-server\htdocs\narrrfs-world

# Upload 3D models (takes 10-20 minutes)
scp -r "public\three.js\public\textures\3d models" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/var/www/html/public/three.js/public/textures/

# Upload sounds (takes 2-5 minutes)
scp -r "public\three.js\public\sounds" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/var/www/html/public/three.js/public/

# Upload audio (if exists, takes 2-5 minutes)
scp -r "public\three.js\public\audio" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/var/www/html/public/three.js/public/
```

**Note:** If the hostname doesn't work, try:
- `srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com` (add `.onrender.com`)
- Or check your Render dashboard for the full SSH connection string

---

## ✅ **VERIFICATION (In Render Shell)**

**After upload completes, go back to Render shell and run:**

```bash
# Check 3D models
ls /var/www/html/public/three.js/public/textures/3d\ models/

# Check specific files
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
ls /var/www/html/public/three.js/public/textures/3d\ models/tree-with-arms/tree-with-arms.glb

# Check sounds
ls /var/www/html/public/three.js/public/sounds/

# Count files (should show many)
find /var/www/html/public/three.js/public/textures/3d\ models/ -type f | wc -l
find /var/www/html/public/three.js/public/sounds/ -type f | wc -l

# Fix permissions (if files don't load)
chmod -R 755 /var/www/html/public/three.js/public/
chown -R www-data:www-data /var/www/html/public/three.js/public/
```

---

## 🚨 **TROUBLESHOOTING**

### **Problem: "scp: command not found"**

**Solution:** Install OpenSSH on Windows
```powershell
# Windows 10/11
Add-WindowsCapability -Online -Name OpenSSH.Client~~~~0.0.1.0

# Or use WinSCP (GUI): https://winscp.net/
```

### **Problem: "Permission denied" or "Connection refused"**

**Solution:** 
1. Check Render service is running
2. Try SSH first: `ssh root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com`
3. If SSH works, SCP will work too

### **Problem: "Hostname not found"**

**Solution:** 
- Try adding `.onrender.com`: `srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com`
- Or check Render dashboard for full SSH connection string

### **Problem: Files upload but 404 in browser**

**Solution:** Fix permissions in Render shell
```bash
chmod -R 755 /var/www/html/public/three.js/public/
chown -R www-data:www-data /var/www/html/public/three.js/public/
```

---

## 📋 **QUICK CHECKLIST**

- [ ] **Run script OR manual commands** from local Windows machine
- [ ] **Wait for upload** (10-30 minutes total)
- [ ] **Verify in Render shell** - check files exist
- [ ] **Fix permissions** if needed
- [ ] **Test in browser** - load game, check for 404s

---

## ⏱️ **ESTIMATED TIME**

- **3D Models:** 10-20 minutes (largest files)
- **Sounds:** 2-5 minutes
- **Audio:** 2-5 minutes (if exists)
- **Total:** 15-30 minutes

---

**Status:** ✅ **READY TO RUN**  
**Next:** Run the script or manual commands from your local Windows machine

