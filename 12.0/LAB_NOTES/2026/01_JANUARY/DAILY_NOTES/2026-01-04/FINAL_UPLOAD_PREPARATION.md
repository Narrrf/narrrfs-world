# 🚀 FINAL UPLOAD PREPARATION - Ready to Execute

**Date:** January 6, 2026  
**Status:** ✅ **ALL SETUP COMPLETE - READY TO UPLOAD**  
**Total Files:** 1,437 files  
**Total Size:** 2,060.85 MB (~2GB)

---

## 📊 **UPLOAD SUMMARY**

### **Files to Upload:**

| Category | Files | Size | Target Path |
|----------|-------|------|-------------|
| **3D Models** | 1,360 | 2,042.76 MB | `/data/public/three.js/public/textures/3d models/` |
| **Sounds** | 18 | 17.02 MB | `/data/public/three.js/public/sounds/` |
| **Audio** | 59 | 1.07 MB | `/data/public/three.js/public/audio/` |
| **TOTAL** | **1,437** | **2,060.85 MB** | `/data/public/three.js/public/` |

### **Upload Time Estimate:**
- **At 10 Mbps:** ~27 minutes
- **At 5 Mbps:** ~55 minutes
- **At 1 Mbps:** ~4.5 hours

**Note:** Large files (100MB+) may take 5-30 minutes each individually.

---

## ✅ **PRE-UPLOAD CHECKLIST**

Before starting upload, verify:

- [x] API endpoint deployed (`upload-assets.php`)
- [x] Persistent storage created (`/data/public/three.js/public/`)
- [x] Symlinks configured
- [x] Discord bot secret available
- [x] Local files verified (1,437 files, 2GB total)
- [x] PHP limits increased (512MB)

---

## 🚀 **UPLOAD COMMANDS - READY TO EXECUTE**

### **OPTION 1: Automated Script (RECOMMENDED)**

**From Local Windows PowerShell:**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Run automated upload script
# NOTE: Replace [YOUR_BOT_SECRET] with actual bot secret (stored locally, not in Git)
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_VIA_API.ps1 -BotSecret "[YOUR_BOT_SECRET]"
```

**What it does:**
- ✅ Uploads all 1,437 files automatically
- ✅ Shows progress for each file
- ✅ Handles errors gracefully
- ✅ Reports success/failure counts

**Estimated Time:** 30-90 minutes (depending on internet speed)

---

### **OPTION 2: Manual Batch Upload (PowerShell Loop)**

**If script doesn't work, use this:**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world
# NOTE: Replace [YOUR_BOT_SECRET] with actual bot secret (stored locally, not in Git)
$BOT_SECRET = "[YOUR_BOT_SECRET]"

# Upload 3D Models
Write-Host "Uploading 3D Models (1,360 files, 2GB)..." -ForegroundColor Cyan
$modelFiles = Get-ChildItem -Path "public\three.js\public\textures\3d models" -Recurse -File
$count = 0
foreach ($file in $modelFiles) {
    $count++
    $relativePath = $file.FullName.Replace((Get-Location).Path + "\", "").Replace("\", "/")
    $targetPath = "/data/public/three.js/public/$relativePath"
    
    Write-Host "[$count/$($modelFiles.Count)] $($file.Name)" -ForegroundColor Yellow
    
    $result = curl.exe -X POST -H "Authorization: $BOT_SECRET" -F "file=@$($file.FullName)" -F "target_path=$targetPath" -s https://narrrfs.world/api/discord/upload-assets.php
    $json = $result | ConvertFrom-Json -ErrorAction SilentlyContinue
    
    if ($json -and $json.success) {
        Write-Host "  ✅ ($([math]::Round($json.file_size/1KB, 2)) KB)" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $($json.error)" -ForegroundColor Red
    }
}

# Upload Sounds
Write-Host "`nUploading Sounds (18 files)..." -ForegroundColor Cyan
$soundFiles = Get-ChildItem -Path "public\three.js\public\sounds" -Recurse -File
$count = 0
foreach ($file in $soundFiles) {
    $count++
    $relativePath = $file.FullName.Replace((Get-Location).Path + "\", "").Replace("\", "/")
    $targetPath = "/data/public/three.js/public/$relativePath"
    
    Write-Host "[$count/$($soundFiles.Count)] $($file.Name)" -ForegroundColor Yellow
    
    $result = curl.exe -X POST -H "Authorization: $BOT_SECRET" -F "file=@$($file.FullName)" -F "target_path=$targetPath" -s https://narrrfs.world/api/discord/upload-assets.php
    $json = $result | ConvertFrom-Json -ErrorAction SilentlyContinue
    
    if ($json -and $json.success) {
        Write-Host "  ✅ ($([math]::Round($json.file_size/1KB, 2)) KB)" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $($json.error)" -ForegroundColor Red
    }
}

# Upload Audio
Write-Host "`nUploading Audio (59 files)..." -ForegroundColor Cyan
$audioFiles = Get-ChildItem -Path "public\three.js\public\audio" -Recurse -File
$count = 0
foreach ($file in $audioFiles) {
    $count++
    $relativePath = $file.FullName.Replace((Get-Location).Path + "\", "").Replace("\", "/")
    $targetPath = "/data/public/three.js/public/$relativePath"
    
    Write-Host "[$count/$($audioFiles.Count)] $($file.Name)" -ForegroundColor Yellow
    
    $result = curl.exe -X POST -H "Authorization: $BOT_SECRET" -F "file=@$($file.FullName)" -F "target_path=$targetPath" -s https://narrrfs.world/api/discord/upload-assets.php
    $json = $result | ConvertFrom-Json -ErrorAction SilentlyContinue
    
    if ($json -and $json.success) {
        Write-Host "  ✅ ($([math]::Round($json.file_size/1KB, 2)) KB)" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $($json.error)" -ForegroundColor Red
    }
}

Write-Host "`n✅ Upload Complete!" -ForegroundColor Green
```

---

## ✅ **POST-UPLOAD VERIFICATION**

### **In Render Shell:**

```bash
# 1. Verify files are in persistent storage
ls /data/public/three.js/public/textures/3d\ models/ | head -20
ls /data/public/three.js/public/sounds/
ls /data/public/three.js/public/audio/

# 2. Count files (should match local count)
find /data/public/three.js/public/textures/3d\ models/ -type f | wc -l
# Expected: 1360

find /data/public/three.js/public/sounds/ -type f | wc -l
# Expected: 18

find /data/public/three.js/public/audio/ -type f | wc -l
# Expected: 59

# 3. Verify symlinks work
ls /var/www/html/public/three.js/public/textures/3d\ models/ | head -20
# Should show files (accessed via symlink)

# 4. Test specific file
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
# Should work (accessed via symlink)

# 5. Check total size
du -sh /data/public/three.js/public/textures/3d\ models/
# Should show ~2GB
```

### **In Browser:**
- ✅ Test game loads without 404 errors
- ✅ Verify 3D models render correctly
- ✅ Verify sounds play correctly
- ✅ Check browser console for missing asset errors

---

## 🚨 **IMPORTANT REMINDERS**

### **After Each Git Push:**
1. **Recreate Symlinks** (they get wiped):
   ```bash
   # In Render shell
   bash 12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RECREATE_SYMLINKS.sh
   ```

### **If Upload Fails:**
- Check error message in response
- Verify file size is under 512MB
- Check internet connection
- Retry the failed file

### **Large Files:**
- Files over 100MB may take 5-30 minutes each
- Be patient - progress is not shown
- Timeout is set to 30 minutes per file

---

## 📋 **UPLOAD PROGRESS TRACKING**

**Before Upload:**
- Local files: 1,437 files, 2,060.85 MB

**After Upload:**
- Render files: Should match local count
- Verify in Render shell using `find` commands above

---

## ✅ **SUCCESS CRITERIA**

After upload completes:
- ✅ All 1,437 files appear in `/data/` on Render
- ✅ Files accessible via symlinks
- ✅ Game loads without 404 errors
- ✅ All models render correctly
- ✅ All sounds play correctly
- ✅ Browser console shows no missing asset errors

---

## 🎯 **QUICK REFERENCE**

**API Endpoint:** `https://narrrfs.world/api/discord/upload-assets.php`  
**Bot Secret:** `[YOUR_BOT_SECRET]` (stored locally, not in Git - see local config files)  
**Upload Limit:** 512MB per file  
**Total Files:** 1,437 files  
**Total Size:** 2,060.85 MB (~2GB)

**Local Source:** `C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\`  
**Render Target:** `/data/public/three.js/public/` (persistent storage)

---

**Status:** ✅ **READY TO EXECUTE**  
**Next:** Run upload script or manual commands above  
**Estimated Time:** 30-90 minutes (depending on internet speed)

