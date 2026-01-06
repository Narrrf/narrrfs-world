# ⚡ Quick Start: Upload Assets via API

**Date:** January 4, 2026  
**Method:** Use API endpoint with Discord bot authentication  
**Status:** ✅ **READY TO USE**

---

## 🚀 **QUICK START (3 Steps)**

### **Step 1: Get Your Bot Secret**

**Option A: From Render Environment Variables**
- Go to Render Dashboard
- Find your service
- Check environment variables: `DISCORD_BOT_SECRET` or `DISCORD_SECRET`

**Option B: From Local .env File**
```powershell
# Check discord/.env file
cat discord\.env
# Look for: DISCORD_BOT_SECRET=...
```

**Option C: From config.js**
```powershell
# Check discord/config.js
cat discord\config.js
# Look for: botToken or apiSecret
```

### **Step 2: Test Single File Upload**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Replace YOUR_SECRET with your actual bot secret
$BOT_SECRET = "YOUR_SECRET"

# Test with a single file
curl -X POST `
  -H "Authorization: $BOT_SECRET" `
  -F "file=@public\three.js\public\textures\3d models\chest2\Chest2.glb" `
  -F "target_path=/data/public/three.js/public/textures/3d models/chest2/Chest2.glb" `
  https://narrrfs.world/api/discord/upload-assets.php
```

**Expected Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "target_path": "/data/public/three.js/public/textures/3d models/chest2/Chest2.glb",
  "file_size": 1234567,
  "upload_info": {
    "original_name": "Chest2.glb",
    "uploaded_size": 1234567,
    "mime_type": "model/gltf-binary"
  }
}
```

### **Step 3: Upload All Assets (Automated)**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Run the PowerShell script (it will auto-detect your bot secret from .env)
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_VIA_API.ps1

# OR specify bot secret manually
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\UPLOAD_ASSETS_VIA_API.ps1 -BotSecret "YOUR_SECRET"
```

---

## ✅ **VERIFICATION**

**After upload, verify in Render shell:**
```bash
# Check files are in persistent storage
ls /data/public/three.js/public/textures/3d\ models/

# Check files accessible via symlink
ls /var/www/html/public/three.js/public/textures/3d\ models/

# Count files
find /data/public/three.js/public/textures/3d\ models/ -type f | wc -l
find /data/public/three.js/public/sounds/ -type f | wc -l
```

---

## 🚨 **TROUBLESHOOTING**

### **Error: "Unauthorized - Invalid token"**
- ✅ Check your bot secret is correct
- ✅ Make sure you're using `DISCORD_BOT_SECRET` or `DISCORD_SECRET` (not `botToken`)
- ✅ Verify the secret matches what's in Render environment variables

### **Error: "File too large" or Upload fails**
- ✅ PHP upload limits may need to be increased
- ✅ Check `upload_max_filesize` and `post_max_size` in PHP settings
- ✅ For very large files, consider uploading in smaller batches

### **Error: "Failed to create target directory"**
- ✅ Check `/data/` directory exists and has write permissions
- ✅ Verify symlinks are set up correctly (see `PREPARE_RENDER_DIRECTORIES_UPDATED.sh`)

---

## 📝 **NOTES**

- **File Size Limits:** PHP default is usually 2MB. Large files may require increasing PHP limits.
- **Authentication:** Uses same authentication as Discord bot - secure and already working!
- **Persistent Storage:** Files saved to `/data/` - survive deployments!
- **Symlinks:** Files accessible via `/var/www/html/` through symlinks (already set up)

---

**Status:** ✅ **READY TO USE**  
**Next:** Get your bot secret and test with a single file!

