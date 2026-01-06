# ✅ Verify API Endpoint in Render Shell

**Date:** January 4, 2026  
**Purpose:** Verify `upload-assets.php` is deployed and working

---

## 🔍 **VERIFICATION COMMANDS**

Run these in your Render shell:

### **1. Check if file exists:**
```bash
ls -la /var/www/html/api/discord/upload-assets.php
```

### **2. Check file contents (first few lines):**
```bash
head -20 /var/www/html/api/discord/upload-assets.php
```

### **3. Check file permissions:**
```bash
stat /var/www/html/api/discord/upload-assets.php
```

### **4. Test PHP syntax:**
```bash
php -l /var/www/html/api/discord/upload-assets.php
```

### **5. Check if /data/ directory exists:**
```bash
ls -la /data/public/three.js/public/
```

### **6. Verify symlinks:**
```bash
ls -la /var/www/html/public/three.js/public/textures/
ls -la /var/www/html/public/three.js/public/sounds/
```

---

## ✅ **EXPECTED RESULTS**

- ✅ File exists at `/var/www/html/api/discord/upload-assets.php`
- ✅ File permissions: `-rw-r--r--` (644)
- ✅ PHP syntax: `No syntax errors detected`
- ✅ `/data/` directories exist
- ✅ Symlinks point to `/data/`

---

## 🚀 **NEXT: Test API from Local Machine**

Once verified, test from your local Windows machine:

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

