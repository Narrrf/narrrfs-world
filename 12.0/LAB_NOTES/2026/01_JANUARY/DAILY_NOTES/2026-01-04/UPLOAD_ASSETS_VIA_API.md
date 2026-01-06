# 🚀 Upload Assets via API (Using Discord Bot Authentication)

**Date:** January 4, 2026  
**Solution:** Use API endpoint with Discord bot authentication to upload assets  
**Status:** ✅ **API ENDPOINT CREATED**

---

## 🎯 **THE SOLUTION**

Since your Discord bot can communicate with Render, we can use the same authentication to upload files via an API endpoint!

**Created:** `api/discord/upload-assets.php`

This endpoint:
- ✅ Uses same authentication as Discord bot (`DISCORD_BOT_SECRET`)
- ✅ Accepts file uploads via POST
- ✅ Saves files to `/data/` (persistent storage)
- ✅ Can be called from local machine using curl or Node.js

---

## 📋 **USAGE METHODS**

### **Method 1: Using curl (From Local Windows PowerShell)**

```powershell
# Set your Discord bot secret (get from Render environment variables or config)
$BOT_SECRET = "YOUR_DISCORD_BOT_SECRET"

# Upload a single file
curl -X POST `
  -H "Authorization: $BOT_SECRET" `
  -F "file=@C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\textures\3d models\chest2\Chest2.glb" `
  -F "target_path=/data/public/three.js/public/textures/3d models/chest2/Chest2.glb" `
  https://narrrfs.world/api/discord/upload-assets.php
```

### **Method 2: Using PowerShell Script (Batch Upload)**

Create a script to upload entire directories:

```powershell
# upload-assets-batch.ps1
$BOT_SECRET = "YOUR_DISCORD_BOT_SECRET"
$BASE_URL = "https://narrrfs.world/api/discord/upload-assets.php"
$LOCAL_BASE = "C:\xampp-server\htdocs\narrrfs-world\public\three.js\public"
$REMOTE_BASE = "/data/public/three.js/public"

# Function to upload a file
function Upload-File {
    param(
        [string]$LocalPath,
        [string]$RemotePath
    )
    
    Write-Host "Uploading: $LocalPath -> $RemotePath" -ForegroundColor Yellow
    
    $result = curl -X POST `
      -H "Authorization: $BOT_SECRET" `
      -F "file=@$LocalPath" `
      -F "target_path=$RemotePath" `
      $BASE_URL
    
    $json = $result | ConvertFrom-Json
    if ($json.success) {
        Write-Host "  ✅ Success" -ForegroundColor Green
    } else {
        Write-Host "  ❌ Failed: $($json.error)" -ForegroundColor Red
    }
}

# Upload 3D models
$modelsPath = "$LOCAL_BASE\textures\3d models"
Get-ChildItem -Path $modelsPath -Recurse -File | ForEach-Object {
    $relativePath = $_.FullName.Replace($LOCAL_BASE, "").Replace("\", "/")
    $remotePath = "$REMOTE_BASE$relativePath"
    Upload-File -LocalPath $_.FullName -RemotePath $remotePath
}

# Upload sounds
$soundsPath = "$LOCAL_BASE\sounds"
Get-ChildItem -Path $soundsPath -Recurse -File | ForEach-Object {
    $relativePath = $_.FullName.Replace($LOCAL_BASE, "").Replace("\", "/")
    $remotePath = "$REMOTE_BASE$relativePath"
    Upload-File -LocalPath $_.FullName -RemotePath $remotePath
}

# Upload audio
$audioPath = "$LOCAL_BASE\audio"
if (Test-Path $audioPath) {
    Get-ChildItem -Path $audioPath -Recurse -File | ForEach-Object {
        $relativePath = $_.FullName.Replace($LOCAL_BASE, "").Replace("\", "/")
        $remotePath = "$REMOTE_BASE$relativePath"
        Upload-File -LocalPath $_.FullName -RemotePath $remotePath
    }
}
```

### **Method 3: Using Node.js Script (Like Discord Bot)**

Create a script that uses the same `fetch` method as your Discord bot:

```javascript
// upload-assets.js
const fs = require('fs');
const path = require('path');
const fetch = require('node-fetch');
const FormData = require('form-data');
require('dotenv').config();

const BOT_SECRET = process.env.DISCORD_BOT_SECRET;
const API_URL = 'https://narrrfs.world/api/discord/upload-assets.php';
const LOCAL_BASE = 'C:\\xampp-server\\htdocs\\narrrfs-world\\public\\three.js\\public';
const REMOTE_BASE = '/data/public/three.js/public';

async function uploadFile(localPath, remotePath) {
    const form = new FormData();
    form.append('file', fs.createReadStream(localPath));
    form.append('target_path', remotePath);
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Authorization': BOT_SECRET
            },
            body: form
        });
        
        const result = await response.json();
        if (result.success) {
            console.log(`✅ ${localPath} -> ${remotePath}`);
        } else {
            console.error(`❌ ${localPath}: ${result.error}`);
        }
    } catch (error) {
        console.error(`❌ ${localPath}: ${error.message}`);
    }
}

async function uploadDirectory(localDir, remoteDir) {
    const files = fs.readdirSync(localDir, { withFileTypes: true, recursive: true });
    
    for (const file of files) {
        if (file.isFile()) {
            const localPath = path.join(file.path || localDir, file.name);
            const relativePath = path.relative(LOCAL_BASE, localPath).replace(/\\/g, '/');
            const remotePath = `${REMOTE_BASE}/${relativePath}`;
            
            await uploadFile(localPath, remotePath);
        }
    }
}

// Upload assets
async function main() {
    console.log('Starting asset upload...');
    
    await uploadDirectory(
        path.join(LOCAL_BASE, 'textures', '3d models'),
        `${REMOTE_BASE}/textures/3d models`
    );
    
    await uploadDirectory(
        path.join(LOCAL_BASE, 'sounds'),
        `${REMOTE_BASE}/sounds`
    );
    
    if (fs.existsSync(path.join(LOCAL_BASE, 'audio'))) {
        await uploadDirectory(
            path.join(LOCAL_BASE, 'audio'),
            `${REMOTE_BASE}/audio`
        );
    }
    
    console.log('Upload complete!');
}

main();
```

---

## 🔐 **AUTHENTICATION**

**Get your Discord bot secret:**
1. Check Render environment variables: `DISCORD_BOT_SECRET`
2. Or check your local `.env` file in the `discord/` directory
3. Or check `discord/config.js`

**Security:**
- Only requests with valid `DISCORD_BOT_SECRET` can upload
- Files are validated and saved to `/data/` only
- Path validation ensures files can't be saved outside allowed directories

---

## ✅ **ADVANTAGES OF THIS METHOD**

- ✅ **No SSH/SCP needed** - Works through HTTP/HTTPS
- ✅ **Same authentication** - Uses existing Discord bot credentials
- ✅ **Persistent storage** - Files saved to `/data/` (survives deployments)
- ✅ **Can be automated** - Scripts can upload entire directories
- ✅ **Works from local machine** - No need for Render shell access

---

## 🚀 **QUICK START**

### **1. Get Your Bot Secret:**
```powershell
# Check your Discord bot config
cat discord\.env
# Or
cat discord\config.js
```

### **2. Test Single File Upload:**
```powershell
$BOT_SECRET = "YOUR_SECRET_HERE"
curl -X POST `
  -H "Authorization: $BOT_SECRET" `
  -F "file=@C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\textures\3d models\chest2\Chest2.glb" `
  -F "target_path=/data/public/three.js/public/textures/3d models/chest2/Chest2.glb" `
  https://narrrfs.world/api/discord/upload-assets.php
```

### **3. If successful, use batch script to upload all files**

---

## 📝 **NOTES**

- **File size limits:** PHP default is usually 2MB. You may need to increase `upload_max_filesize` and `post_max_size` in PHP settings for large files.
- **Timeout:** Large files may timeout. Consider uploading in smaller batches.
- **Permissions:** The endpoint sets permissions to `www-data` - adjust if needed for your Render setup.

---

**Status:** ✅ **API ENDPOINT READY**  
**Next:** Get your `DISCORD_BOT_SECRET` and test with a single file upload

