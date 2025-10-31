# ✅ RENDER POST-DEPLOYMENT CHECKLIST

**Purpose:** Ensure critical persistent resources are configured after every deployment  
**When to Use:** After EVERY push to `render-deploy` branch  
**Time Required:** ~2 minutes  

---

## 🚨 CRITICAL: RUN AFTER EVERY DEPLOYMENT

### **Step 1: Verify Partner Images Symlink**

```bash
# Check if symlink exists and points to correct location
ls -la /var/www/html/img/partners

# Expected output: lrwxrwxrwx ... /var/www/html/img/partners -> /data/img/partners
# If NOT a symlink, fix immediately:
```

### **Step 2: Fix Symlink (If Broken)**

```bash
# Remove directory/broken symlink
rm -rf /var/www/html/img/partners

# Recreate symlink to persistent storage
ln -s /data/img/partners /var/www/html/img/partners

# Set permissions
chown -h www-data:www-data /var/www/html/img/partners
chown -R www-data:www-data /data/img/partners
chmod -R 775 /data/img/partners
```

### **Step 3: Verify Database Backup**

```bash
# Ensure latest database is backed up to persistent storage
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Verify backup exists
ls -lh /data/narrrf_world.sqlite
```

### **Step 4: Test Critical Endpoints**

```bash
# Test partner images load
curl -I https://narrrfs.world/img/partners/1_logo_1761797505.jpg | grep "200 OK"

# Test partners API
curl https://narrrfs.world/api/user/get-partners.php | grep -o "success.*true"

# Test admin interface loads
curl -I https://narrrfs.world/admin-interface.html | grep "200 OK"
```

### **Step 5: Verify Frontend**

**Open in browser:**
- `https://narrrfs.world/` - Check homepage loads
- `https://narrrfs.world/partners.html` - Check partner images display
- Browser console: No 404 errors for `/img/partners/...` files

---

## 🔧 QUICK REFERENCE COMMANDS

### **Full Post-Deployment Sequence:**
```bash
# All-in-one verification and fix
cd /var/www/html

# Check/fix symlink
if [ ! -L img/partners ]; then
  rm -rf img/partners
  ln -s /data/img/partners img/partners
  chown -h www-data:www-data img/partners
fi

# Backup database
cp db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Verify
ls -la img/partners
ls /data/img/partners | wc -l

echo "✅ Post-deployment check complete!"
```

---

## 📊 EXPECTED RESULTS

### **Healthy Deployment:**
- ✅ Symlink exists: `/var/www/html/img/partners` → `/data/img/partners`
- ✅ Permissions: `www-data:www-data` with `775`
- ✅ Files: 30+ image/video files in `/data/img/partners/`
- ✅ Database: Backed up to `/data/narrrf_world.sqlite`
- ✅ Frontend: All partner images visible, no 404s

### **If Anything Fails:**
- 🚨 Run the fix commands immediately
- 🚨 Test partners page in browser
- 🚨 Check console for 404 errors
- 🚨 Verify database was backed up

---

## 🎯 WHY THIS IS CRITICAL

**Without This Checklist:**
- Partner images disappear after every push
- Manual intervention required every time
- Community sees broken partner page
- Professional appearance damaged
- Hours wasted fixing the same issue

**With This Checklist:**
- 2-minute verification after each push
- Images persist across all deployments
- Professional, reliable partner showcase
- Zero manual image re-uploads needed
- Team confidence in deployment process

---

## 📝 DEPLOYMENT LOG TEMPLATE

**Copy this after each deployment:**

```markdown
## Deployment: [DATE] - [TIME]
**Commit:** [HASH]
**Branch:** render-deploy

### Post-Deployment Checklist:
- [ ] Symlink verified/fixed
- [ ] Database backed up
- [ ] Partner images loading
- [ ] No 404 errors
- [ ] Frontend tested

**Notes:** [Any issues or observations]
**Status:** ✅ READY FOR PRODUCTION USE
```

---

**CHECKLIST CREATED:** October 30, 2025  
**STATUS:** ✅ **ACTIVE - USE AFTER EVERY DEPLOYMENT**  
**PURPOSE:** Prevent partner image loss forever  

**🚨 BOOKMARK THIS - RUN AFTER EVERY PUSH! 🚨**

