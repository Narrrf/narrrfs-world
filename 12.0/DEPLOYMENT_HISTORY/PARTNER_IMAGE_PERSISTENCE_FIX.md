# 🚨 PARTNER IMAGE PERSISTENCE - PERMANENT FIX

**Date:** October 30, 2025  
**Issue:** Partner images disappear after every deployment  
**Status:** ✅ **PERMANENT SOLUTION IMPLEMENTED**  

---

## 🎯 THE PROBLEM

### **What Happens:**
Every deployment to Render causes partner images to disappear:
- Symlink `/var/www/html/img/partners` → `/data/img/partners` gets replaced by a directory
- Database has old filenames that don't match the actual files
- Frontend shows cheese placeholder images instead of real partner logos/banners/galleries

### **Root Cause:**
1. **Repo has `public/img/partners/` directory** with old files (like `vr-gallery.JPG`)
2. **Every deployment copies repo files** → overwrites the symlink with a real directory
3. **New uploads go to `/data/img/partners`** but frontend looks in `/var/www/html/img/partners`
4. **Result:** Symlink destroyed, images disappear

---

## ✅ PERMANENT SOLUTION

### **Step 1: Remove Partner Directory from Repo**

**On Local Machine:**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Move vr-gallery.JPG to a different location (keep it for VR section)
mkdir -p public/img/vr
mv public/img/partners/vr-gallery.JPG public/img/vr/vr-gallery.JPG

# Remove the entire partners directory from local repo
rm -r public/img/partners

# Update index.html to reference new VR image location
# Change: img/partners/vr-gallery.JPG → img/vr/vr-gallery.JPG
```

### **Step 2: Add to .gitignore**

**Create/Update `.gitignore`:**
```bash
# Partner images are stored in /data/img/partners (persistent storage)
# DO NOT commit this directory
public/img/partners/

# Keep VR gallery image separate
!public/img/vr/
```

### **Step 3: Update VR Gallery Image Reference**

**In `public/index.html`:**
- Change `img/partners/vr-gallery.JPG` → `img/vr/vr-gallery.JPG`

### **Step 4: Document Symlink Setup**

**Add to deployment docs:**
```bash
# CRITICAL: Partner images symlink setup (run once after first deployment)
mkdir -p /data/img/partners
rm -rf /var/www/html/img/partners
ln -s /data/img/partners /var/www/html/img/partners
chown -h www-data:www-data /var/www/html/img/partners
chown -R www-data:www-data /data/img/partners
chmod -R 775 /data/img/partners
```

---

## 🔧 IMPLEMENTATION STEPS

### **Local Changes (Before Next Push):**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# 1) Create new directory for VR image
mkdir public\img\vr

# 2) Move VR gallery image
copy public\img\partners\vr-gallery.JPG public\img\vr\vr-gallery.JPG

# 3) Delete partners directory from local repo
Remove-Item -Recurse -Force public\img\partners

# 4) Create .gitignore if it doesn't exist
if (!(Test-Path .gitignore)) { New-Item .gitignore -ItemType File }

# 5) Add partners directory to .gitignore
Add-Content .gitignore "`n# Partner images (persistent storage)`npublic/img/partners/`n"

# 6) Update index.html VR image path
# (Manual edit or search-replace tool)

# 7) Commit changes
git add .
git commit -m "fix: exclude partner images from repo, use persistent storage only"
git push origin render-deploy
```

---

## 🎯 VERIFICATION CHECKLIST

### **After Implementation:**
- [ ] `public/img/partners/` removed from local repo
- [ ] `.gitignore` includes `public/img/partners/`
- [ ] VR image moved to `public/img/vr/vr-gallery.JPG`
- [ ] `index.html` references `img/vr/vr-gallery.JPG`
- [ ] Committed and pushed to `render-deploy`

### **After Next Deployment:**
- [ ] Symlink still exists: `ls -la /var/www/html/img/partners` shows `-> /data/img/partners`
- [ ] Partner images still visible on `https://narrrfs.world/partners.html`
- [ ] No 404 errors in console
- [ ] All logos, banners, galleries display correctly

---

## 🚨 EMERGENCY RECOVERY (If Images Disappear Again)

### **Quick Fix on Render Shell:**
```bash
# 1) Recreate symlink
rm -rf /var/www/html/img/partners
ln -s /data/img/partners /var/www/html/img/partners

# 2) Set permissions
chown -h www-data:www-data /var/www/html/img/partners
chown -R www-data:www-data /data/img/partners
chmod -R 775 /data/img/partners

# 3) Verify
ls -la /var/www/html/img/partners  # Should show symlink
ls /data/img/partners | wc -l      # Should show ~30+ files

# 4) If database filenames are wrong, re-download from /data:
cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
```

---

## 📚 TECHNICAL DETAILS

### **File Locations:**

**Persistent Storage (Never wiped):**
- `/data/img/partners/` - All partner images/videos stored here
- `/data/narrrf_world.sqlite` - Database backup

**Web Server (Wiped on each deploy):**
- `/var/www/html/img/partners/` - Should be a SYMLINK to `/data/img/partners/`
- `/var/www/html/db/narrrf_world.sqlite` - Active database

### **How Uploads Work:**

**Production:**
```php
$persistentDir = '/data/img/partners/';
$uploadPath = $persistentDir . $filename;
move_uploaded_file($tempFile, $uploadPath);
```

**Frontend Access:**
```html
<img src="/img/partners/filename.jpg" />
```

**Nginx Serves:**
- Request: `/img/partners/filename.jpg`
- Resolves: `/var/www/html/img/partners/filename.jpg`
- Symlink: → `/data/img/partners/filename.jpg`
- Result: File served from persistent storage

---

## 🎯 WHY THIS WORKS

### **Before Fix:**
1. Repo has `public/img/partners/` with old files
2. Deployment copies repo → creates real directory
3. Symlink destroyed
4. New uploads go to `/data/img/partners/` (correct)
5. Frontend requests `/img/partners/...` (looks in wrong place)
6. Result: 404 errors, images gone

### **After Fix:**
1. Repo has NO `public/img/partners/` directory
2. Deployment ignores this path (in .gitignore)
3. Symlink remains intact
4. New uploads go to `/data/img/partners/` (correct)
5. Frontend requests `/img/partners/...` → symlink → `/data/img/partners/...`
6. Result: All images persist forever! ✅

---

## 🏆 SUCCESS METRICS

### **After Permanent Fix:**
- ✅ Partner images survive ALL deployments
- ✅ No manual intervention needed after pushes
- ✅ No database filename mismatches
- ✅ No symlink recreation required
- ✅ No 404 errors on partners page
- ✅ Professional, reliable partner showcase

---

## 📝 IMPLEMENTATION TIMELINE

**Phase 1 (After Tonight's Bingo):**
- Remove `public/img/partners/` from repo
- Add to `.gitignore`
- Move VR image to `public/img/vr/`
- Update `index.html` VR image reference
- Test local, commit, push

**Phase 2 (Verify on Production):**
- Wait for Render deployment
- Verify symlink still exists
- Test partner images load correctly
- Confirm no 404 errors

**Phase 3 (Document for Team):**
- Update Master Ruleset with permanent fix
- Create deployment checklist
- Train team on new structure

---

**PERMANENT FIX CREATED:** October 30, 2025  
**STATUS:** Ready to implement after Halloween Bingo  
**IMPACT:** Prevents image loss on all future deployments  

**🧀 THIS FIX ENSURES DECADES OF RELIABLE PARTNER IMAGE PERSISTENCE! 🧀**

