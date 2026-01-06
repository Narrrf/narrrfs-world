# 🚀 Render Asset Upload Guide - Three.js Assets

**Date:** January 4, 2026  
**Purpose:** Step-by-step guide to upload large Three.js assets to Render  
**Status:** ✅ **READY TO USE**

---

## 🎯 **QUICK START (From Your Local Windows Machine)**

You're currently in the Render shell (`/var/www/html#`), but **uploads must be done from your LOCAL Windows machine** using SCP (Secure Copy).

---

## 📋 **METHOD 1: SCP from Windows PowerShell (RECOMMENDED)**

### **Step 1: Get Your Render SSH Connection Info**

From your Render dashboard:
1. Go to your service
2. Find "Shell" or "SSH" connection details
3. Note: **Host**, **Port**, **Username** (usually `root`), **SSH Key location**

**Example Render SSH info:**
- Host: `srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com`
- Port: `22` (default)
- Username: `root`
- SSH Key: Usually in `~/.ssh/` or Render provides connection string

### **Step 2: Test SSH Connection (From Local PowerShell)**

```powershell
# Test connection (replace with your Render host)
ssh root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com

# If it works, you'll see the Render shell prompt
# Type 'exit' to return to local
```

### **Step 3: Upload Assets Using SCP**

**From your LOCAL Windows PowerShell (NOT in Render shell):**

```powershell
# Navigate to your project directory
cd C:\xampp-server\htdocs\narrrfs-world

# Upload textures directory (3D models)
scp -r "public\three.js\public\textures\3d models" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/var/www/html/public/three.js/public/textures/

# Upload sounds directory
scp -r "public\three.js\public\sounds" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/var/www/html/public/three.js/public/

# Upload audio directory (if exists)
scp -r "public\three.js\public\audio" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/var/www/html/public/three.js/public/
```

**Note:** Replace `srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com` with your actual Render hostname.

---

## 📋 **METHOD 2: PowerShell Script (Automated)**

Create this script on your local machine:

```powershell
# RENDER_ASSET_UPLOAD.ps1
# Upload Three.js assets to Render

$RENDER_HOST = "srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com"
$RENDER_USER = "root"
$LOCAL_BASE = "C:\xampp-server\htdocs\narrrfs-world"
$REMOTE_BASE = "/var/www/html"

Write-Host "🚀 Starting asset upload to Render..." -ForegroundColor Cyan

# Upload 3D models
Write-Host "📦 Uploading 3D models..." -ForegroundColor Yellow
scp -r "$LOCAL_BASE\public\three.js\public\textures\3d models" "${RENDER_USER}@${RENDER_HOST}:${REMOTE_BASE}/public/three.js/public/textures/"

# Upload sounds
Write-Host "🔊 Uploading sounds..." -ForegroundColor Yellow
scp -r "$LOCAL_BASE\public\three.js\public\sounds" "${RENDER_USER}@${RENDER_HOST}:${REMOTE_BASE}/public/three.js/public/"

# Upload audio (if exists)
if (Test-Path "$LOCAL_BASE\public\three.js\public\audio") {
    Write-Host "🎵 Uploading audio..." -ForegroundColor Yellow
    scp -r "$LOCAL_BASE\public\three.js\public\audio" "${RENDER_USER}@${RENDER_HOST}:${REMOTE_BASE}/public/three.js/public/"
}

Write-Host "✅ Upload complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Next: Verify in Render shell:" -ForegroundColor Cyan
Write-Host "  ls /var/www/html/public/three.js/public/textures/3d\ models/" -ForegroundColor White
Write-Host "  ls /var/www/html/public/three.js/public/sounds/" -ForegroundColor White
```

**Run it:**
```powershell
.\RENDER_ASSET_UPLOAD.ps1
```

---

## 🔍 **VERIFICATION (In Render Shell)**

**While you're in the Render shell, run these commands to verify:**

```bash
# Check if directories exist
ls -la /var/www/html/public/three.js/public/textures/
ls -la /var/www/html/public/three.js/public/sounds/
ls -la /var/www/html/public/three.js/public/audio/

# Check specific models
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/
ls /var/www/html/public/three.js/public/textures/3d\ models/tree-with-arms/

# Count files (should show many files)
find /var/www/html/public/three.js/public/textures/3d\ models/ -type f | wc -l
find /var/www/html/public/three.js/public/sounds/ -type f | wc -l
```

---

## 🚨 **TROUBLESHOOTING**

### **Problem: SCP command not found**

**Solution:** Install OpenSSH on Windows
```powershell
# Windows 10/11: Enable OpenSSH
Add-WindowsCapability -Online -Name OpenSSH.Client~~~~0.0.1.0

# Or use WinSCP (GUI tool): https://winscp.net/
```

### **Problem: Permission denied**

**Solution:** Check SSH key authentication
```powershell
# Test SSH connection first
ssh root@YOUR_RENDER_HOST

# If it works, SCP will work too
```

### **Problem: Connection timeout**

**Solution:** 
- Check Render service is running
- Verify hostname is correct
- Try Render dashboard SSH connection first

### **Problem: Files upload but 404 in browser**

**Solution:** Check file permissions
```bash
# In Render shell, fix permissions
chmod -R 755 /var/www/html/public/three.js/public/textures/
chmod -R 755 /var/www/html/public/three.js/public/sounds/
chown -R www-data:www-data /var/www/html/public/three.js/public/
```

---

## 📋 **ALTERNATIVE: WinSCP (GUI Tool - Easier)**

If command line is difficult, use **WinSCP** (free GUI tool):

1. **Download WinSCP:** https://winscp.net/
2. **Connect to Render:**
   - Host: `srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com`
   - Username: `root`
   - Port: `22`
   - Use your SSH key
3. **Drag and drop folders:**
   - Local: `C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\textures\3d models`
   - Remote: `/var/www/html/public/three.js/public/textures/3d models`
4. **Upload sounds and audio the same way**

---

## 🎯 **QUICK REFERENCE**

### **From Local Windows (PowerShell):**
```powershell
# Upload 3D models
scp -r "C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\textures\3d models" root@YOUR_RENDER_HOST:/var/www/html/public/three.js/public/textures/

# Upload sounds
scp -r "C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\sounds" root@YOUR_RENDER_HOST:/var/www/html/public/three.js/public/

# Upload audio
scp -r "C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\audio" root@YOUR_RENDER_HOST:/var/www/html/public/three.js/public/
```

### **In Render Shell (Verification):**
```bash
# Check directories exist
ls /var/www/html/public/three.js/public/textures/3d\ models/
ls /var/www/html/public/three.js/public/sounds/

# Check specific files
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
ls /var/www/html/public/three.js/public/textures/3d\ models/tree-with-arms/tree-with-arms.glb
```

---

## ✅ **UPLOAD CHECKLIST**

- [ ] **Get Render SSH hostname** from dashboard
- [ ] **Test SSH connection** from local machine
- [ ] **Upload 3D models** directory (`textures/3d models/`)
- [ ] **Upload sounds** directory (`sounds/`)
- [ ] **Upload audio** directory (`audio/`) - if exists
- [ ] **Verify in Render shell** - check files exist
- [ ] **Test in browser** - load game, check for 404s
- [ ] **Fix permissions** if needed (`chmod -R 755`)

---

## 📝 **NOTES**

- **Upload from LOCAL machine** - Not from Render shell
- **Use SCP or WinSCP** - Both work, WinSCP is easier for beginners
- **Large files take time** - Be patient, 3D models can be 100MB+
- **Verify after upload** - Always check files exist in Render shell
- **Keep .gitignore intact** - Never commit these files to git

---

**Status:** ✅ **READY TO USE**  
**Next Step:** Get your Render SSH hostname and run the SCP commands from your local Windows machine

