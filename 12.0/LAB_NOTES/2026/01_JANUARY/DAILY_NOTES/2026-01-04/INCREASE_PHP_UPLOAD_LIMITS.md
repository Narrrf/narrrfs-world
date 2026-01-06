# 🔧 Increase PHP Upload Limits on Render

**Date:** January 4, 2026  
**Issue:** PHP upload limits are too low (2MB) for 7MB+ asset files  
**Solution:** Create `.htaccess` file to increase limits

---

## 📋 **CURRENT LIMITS (Found in Render Shell)**

```bash
php -i | grep -E "upload_max_filesize|post_max_size"
```

**Results:**
- `upload_max_filesize => 2M` (2MB) ❌ Too small
- `post_max_size => 8M` (8MB) ❌ Too small

---

## ✅ **SOLUTION: Create .htaccess File**

**Created:** `api/discord/.htaccess`

**Contents:**
```apache
# Increase PHP upload limits for asset uploads
php_value upload_max_filesize 50M
php_value post_max_size 100M
php_value max_execution_time 300
php_value max_input_time 300
```

---

## 🚀 **DEPLOYMENT**

1. **Commit and push the .htaccess file:**
   ```powershell
   cd C:\xampp-server\htdocs\narrrfs-world
   git add api\discord\.htaccess
   git commit -m "Increase PHP upload limits for asset uploads"
   git push origin render-deploy
   ```

2. **Wait for Render deployment (1-2 minutes)**

3. **Verify limits in Render shell:**
   ```bash
   php -i | grep -E "upload_max_filesize|post_max_size"
   ```
   
   **Expected:**
   - `upload_max_filesize => 50M`
   - `post_max_size => 100M`

---

## ⚠️ **ALTERNATIVE: If .htaccess Doesn't Work**

If `.htaccess` doesn't work on Render (some hosts disable it), we can:

1. **Use `ini_set()` in PHP:**
   ```php
   ini_set('upload_max_filesize', '50M');
   ini_set('post_max_size', '100M');
   ```

2. **Or use Render's environment variables/configuration**

3. **Or split large files into chunks**

---

## ✅ **AFTER INCREASING LIMITS**

Once limits are increased, test again:
```powershell
# NOTE: Replace [YOUR_BOT_SECRET] with actual bot secret (stored locally, not in Git)
$BOT_SECRET = "[YOUR_BOT_SECRET]"
curl.exe -X POST -H "Authorization: $BOT_SECRET" -F "file=@public\three.js\public\textures\3d models\chest2\Chest2.glb" -F "target_path=/data/public/three.js/public/textures/3d models/chest2/Chest2.glb" https://narrrfs.world/api/discord/upload-assets.php
```

---

**Status:** ⏳ **WAITING FOR .htaccess DEPLOYMENT**

