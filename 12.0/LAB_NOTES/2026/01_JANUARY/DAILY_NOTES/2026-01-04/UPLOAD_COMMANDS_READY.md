# 🚀 UPLOAD COMMANDS - Ready to Execute

**Date:** January 6, 2026  
**Status:** ✅ **ALL SETUP COMPLETE - READY TO UPLOAD**  
**Purpose:** Exact commands to upload all three.js and glyph game assets to Render `/data/` directory

---

## 🎯 **QUICK START - AUTOMATED UPLOAD (RECOMMENDED)**

### **From Your Local Windows Machine:**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Run automated upload script
# NOTE: Replace [YOUR_BOT_SECRET] with actual bot secret (stored locally, not in Git)
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_VIA_API.ps1 -BotSecret "[YOUR_BOT_SECRET]"
```

**This will:**
- ✅ Upload all 3D models from `public\three.js\public\textures\3d models\`
- ✅ Upload all sounds from `public\three.js\public\sounds\`
- ✅ Upload all audio from `public\three.js\public\audio\`
- ✅ Show progress for each file
- ✅ Handle errors gracefully

**Estimated Time:** 15-60 minutes (depending on file sizes and internet speed)

---

## 📋 **MANUAL UPLOAD COMMANDS (If Script Doesn't Work)**

### **Step 1: Upload 3D Models**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world
# NOTE: Replace [YOUR_BOT_SECRET] with actual bot secret (stored locally, not in Git)
$BOT_SECRET = "[YOUR_BOT_SECRET]"

# Get all 3D model files
$modelFiles = Get-ChildItem -Path "public\three.js\public\textures\3d models" -Recurse -File

foreach ($file in $modelFiles) {
    $relativePath = $file.FullName.Replace((Get-Location).Path + "\", "").Replace("\", "/")
    $targetPath = "/data/public/three.js/public/$relativePath"
    
    Write-Host "Uploading: $($file.Name) -> $targetPath" -ForegroundColor Yellow
    
    curl.exe -X POST `
      -H "Authorization: $BOT_SECRET" `
      -F "file=@$($file.FullName)" `
      -F "target_path=$targetPath" `
      -s `
      https://narrrfs.world/api/discord/upload-assets.php | ConvertFrom-Json | ForEach-Object {
        if ($_.success) {
            Write-Host "  ✅ Success ($([math]::Round($_.file_size/1KB, 2)) KB)" -ForegroundColor Green
        } else {
            Write-Host "  ❌ Failed: $($_.error)" -ForegroundColor Red
        }
    }
}
```

### **Step 2: Upload Sounds**

```powershell
# Get all sound files
$soundFiles = Get-ChildItem -Path "public\three.js\public\sounds" -Recurse -File

foreach ($file in $soundFiles) {
    $relativePath = $file.FullName.Replace((Get-Location).Path + "\", "").Replace("\", "/")
    $targetPath = "/data/public/three.js/public/$relativePath"
    
    Write-Host "Uploading: $($file.Name) -> $targetPath" -ForegroundColor Yellow
    
    curl.exe -X POST `
      -H "Authorization: $BOT_SECRET" `
      -F "file=@$($file.FullName)" `
      -F "target_path=$targetPath" `
      -s `
      https://narrrfs.world/api/discord/upload-assets.php | ConvertFrom-Json | ForEach-Object {
        if ($_.success) {
            Write-Host "  ✅ Success ($([math]::Round($_.file_size/1KB, 2)) KB)" -ForegroundColor Green
        } else {
            Write-Host "  ❌ Failed: $($_.error)" -ForegroundColor Red
        }
    }
}
```

### **Step 3: Upload Audio**

```powershell
# Get all audio files (if directory exists)
if (Test-Path "public\three.js\public\audio") {
    $audioFiles = Get-ChildItem -Path "public\three.js\public\audio" -Recurse -File
    
    foreach ($file in $audioFiles) {
        $relativePath = $file.FullName.Replace((Get-Location).Path + "\", "").Replace("\", "/")
        $targetPath = "/data/public/three.js/public/$relativePath"
        
        Write-Host "Uploading: $($file.Name) -> $targetPath" -ForegroundColor Yellow
        
        curl.exe -X POST `
          -H "Authorization: $BOT_SECRET" `
          -F "file=@$($file.FullName)" `
          -F "target_path=$targetPath" `
          -s `
          https://narrrfs.world/api/discord/upload-assets.php | ConvertFrom-Json | ForEach-Object {
            if ($_.success) {
                Write-Host "  ✅ Success ($([math]::Round($_.file_size/1KB, 2)) KB)" -ForegroundColor Green
            } else {
                Write-Host "  ❌ Failed: $($_.error)" -ForegroundColor Red
            }
        }
    }
}
```

---

## ✅ **VERIFICATION COMMANDS (After Upload)**

### **In Render Shell:**

```bash
# Check files are in persistent storage
ls /data/public/three.js/public/textures/3d\ models/
ls /data/public/three.js/public/sounds/
ls /data/public/three.js/public/audio/

# Check files accessible via symlink
ls /var/www/html/public/three.js/public/textures/3d\ models/
ls /var/www/html/public/three.js/public/sounds/

# Count files
find /data/public/three.js/public/textures/3d\ models/ -type f | wc -l
find /data/public/three.js/public/sounds/ -type f | wc -l
find /data/public/three.js/public/audio/ -type f | wc -l

# Test specific file
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
```

---

## 🚨 **IMPORTANT NOTES**

### **Upload Time:**
- **Small files (< 10MB):** Seconds per file
- **Medium files (10-100MB):** 1-5 minutes per file
- **Large files (100-500MB):** 5-30 minutes per file
- **Total time:** Depends on total size and internet speed

### **If Upload Fails:**
1. Check error message in response
2. Verify file size is under 512MB
3. Check internet connection
4. Retry the failed file

### **After Upload:**
1. **Recreate symlinks** (if they were wiped during deployment):
   ```bash
   # In Render shell
   bash 12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RECREATE_SYMLINKS.sh
   ```

2. **Test game in browser:**
   - Check for 404 errors
   - Verify models load
   - Verify sounds play

---

## 📊 **UPLOAD PROGRESS TRACKING**

**Before Upload:**
- Count local files:
  ```powershell
  (Get-ChildItem -Path "public\three.js\public\textures\3d models" -Recurse -File).Count
  (Get-ChildItem -Path "public\three.js\public\sounds" -Recurse -File).Count
  (Get-ChildItem -Path "public\three.js\public\audio" -Recurse -File).Count
  ```

**After Upload:**
- Count uploaded files (in Render shell):
  ```bash
  find /data/public/three.js/public/textures/3d\ models/ -type f | wc -l
  find /data/public/three.js/public/sounds/ -type f | wc -l
  find /data/public/three.js/public/audio/ -type f | wc -l
  ```

**Compare:** Local count should match Render count

---

## ✅ **SUCCESS CRITERIA**

After upload completes:
- ✅ All files appear in `/data/` on Render
- ✅ Files accessible via symlinks
- ✅ Game loads without 404 errors
- ✅ All models render correctly
- ✅ All sounds play correctly
- ✅ Browser console shows no missing asset errors

---

**Status:** ✅ **READY TO EXECUTE**  
**Next:** Run automated script or manual commands above

