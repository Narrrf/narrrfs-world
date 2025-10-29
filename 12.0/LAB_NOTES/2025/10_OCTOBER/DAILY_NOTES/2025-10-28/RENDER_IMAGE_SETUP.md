# 🖼️ RENDER IMAGE DIRECTORY SETUP - PARTNER PORTAL

**Date:** October 29, 2025  
**Issue:** Partner images showing 404 on production  
**Reason:** Image directory doesn't exist on Render yet  

---

## 🚨 **THE PROBLEM:**

**Console Errors:**
```
Failed to load resource: img/partners/1_logo_1761747131.PNG:1 (404 Not Found)
Failed to load resource: img/partners/1_banner_1761747131.PNG:1 (404 Not Found)
```

**Root Cause:**
- Images uploaded via admin interface are saved to `public/img/partners/`
- This directory exists locally but NOT on production server
- Need to create directory and upload images to Render

---

## ✅ **RENDER COMMANDS TO RUN:**

```bash
# Connect to Render shell, then run:

# 1. Navigate to image directory
cd /var/www/html/img

# 2. Create partners directory if it doesn't exist
mkdir -p partners

# 3. Set proper permissions
chmod 755 partners

# 4. Verify directory exists
ls -la | grep partners

# 5. Check if any images exist
ls -la partners/

echo "✅ Partner image directory ready!"
```

---

## 📤 **UPLOAD IMAGES TO RENDER:**

**Option 1: Re-upload via Admin Interface (RECOMMENDED):**
1. After directory is created on Render
2. Go to admin interface Partners tab
3. Edit each partner
4. Re-upload their logo and banner
5. Images will be saved directly to production

**Option 2: Manual SCP Upload (if you have images locally):**
```bash
# If you have partner images locally:
scp public/img/partners/*.PNG user@render:/var/www/html/img/partners/
scp public/img/partners/*.png user@render:/var/www/html/img/partners/
```

---

## 🔍 **VERIFICATION:**

After creating directory on Render:

**Test Image Access:**
```bash
# On Render shell:
ls -la /var/www/html/img/partners/

# Should show uploaded images with proper permissions
```

**Test Browser Access:**
```
https://narrrfs.world/img/partners/1_logo_1761747131.PNG
```

Should return the image, not 404!

---

## ✅ **QUICK FIX WORKFLOW:**

1. **Run Render commands** (create directory)
2. **Go to admin interface** → Partners tab
3. **Edit Gensuki** partner
4. **Re-upload logo and banner**
5. **Save**
6. **Refresh partners.html** → Images should load! ✅

---

**🎯 This is the final piece for complete Partner Portal functionality! 🎯**

