# 🚀 Large File Upload Setup (500MB+)

**Date:** January 4, 2026  
**Status:** ✅ **LIMITS UPDATED TO 512M**

---

## ✅ **UPDATED PHP LIMITS**

**For files up to 500MB:**

- `upload_max_filesize: 512M` (512MB)
- `post_max_size: 600M` (600MB - larger than upload_max for overhead)
- `max_execution_time: 1800s` (30 minutes)
- `max_input_time: 1800s` (30 minutes)
- `memory_limit: 1024M` (1GB)

---

## 📋 **VERIFICATION IN RENDER SHELL**

After deployment, verify limits are applied:

```bash
# Check if .htaccess is working
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit"

# Expected output:
# upload_max_filesize => 512M => 512M
# post_max_size => 600M => 600M
# memory_limit => 1024M => 1024M
```

**Note:** If limits are still 2M/8M, `.htaccess` might not be working on Render. The PHP `ini_set()` fallback should still work for the upload script itself.

---

## 🧪 **TESTING LARGE FILE UPLOAD**

### **Test with 7MB file (Chest2.glb):**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world
# NOTE: Replace [YOUR_BOT_SECRET] with actual bot secret (stored locally, not in Git)
$BOT_SECRET = "[YOUR_BOT_SECRET]"

curl.exe -X POST `
  -H "Authorization: $BOT_SECRET" `
  -F "file=@public\three.js\public\textures\3d models\chest2\Chest2.glb" `
  -F "target_path=/data/public/three.js/public/textures/3d models/chest2/Chest2.glb" `
  https://narrrfs.world/api/discord/upload-assets.php
```

### **Test with larger file (if you have one):**
```powershell
# Find largest file
$largestFile = Get-ChildItem -Path "public\three.js\public" -Recurse -File | Sort-Object Length -Descending | Select-Object -First 1
Write-Host "Largest file: $($largestFile.FullName) ($([math]::Round($largestFile.Length/1MB, 2)) MB)"

# Upload it
$relativePath = $largestFile.FullName.Replace((Get-Location).Path + "\", "").Replace("\", "/")
$targetPath = "/data/public/three.js/public/$relativePath"

curl.exe -X POST `
  -H "Authorization: $BOT_SECRET" `
  -F "file=@$($largestFile.FullName)" `
  -F "target_path=$targetPath" `
  https://narrrfs.world/api/discord/upload-assets.php
```

---

## ⚠️ **IMPORTANT NOTES**

### **Upload Time:**
- Large files (100MB+) may take several minutes to upload
- Progress is not shown - be patient
- Timeout is set to 30 minutes

### **Network Considerations:**
- Upload speed depends on your internet connection
- 500MB file at 10Mbps = ~6-7 minutes
- 500MB file at 1Mbps = ~60+ minutes

### **If Upload Fails:**
1. Check error message in response
2. Verify file size is under 512MB
3. Check Render logs for PHP errors
4. Verify `/data/` directory has enough space

---

## 🚀 **BATCH UPLOAD SCRIPT**

Use the PowerShell script to upload all files:

```powershell
# NOTE: Replace [YOUR_BOT_SECRET] with actual bot secret (stored locally, not in Git)
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_VIA_API.ps1 -BotSecret "[YOUR_BOT_SECRET]"
```

**Note:** The script will upload files one by one. Large files will take time - be patient!

---

## ✅ **SUCCESS CRITERIA**

After upload:
- ✅ Files appear in `/data/public/three.js/public/`
- ✅ Files accessible via symlinks in `/var/www/html/`
- ✅ Game loads without 404 errors
- ✅ All models/textures/sounds work correctly

---

**Status:** ✅ **READY FOR LARGE FILE UPLOADS (up to 500MB)**  
**Next:** Test with 7MB file first, then proceed with batch upload

