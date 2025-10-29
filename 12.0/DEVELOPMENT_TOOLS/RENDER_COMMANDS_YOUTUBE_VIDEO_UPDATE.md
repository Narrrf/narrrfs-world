# 🚀 YouTube URL + Video Gallery - Production Deployment Commands

**Date:** October 29, 2025  
**Feature:** YouTube URL support + Video uploads in gallery  

---

## 📋 **DATABASE MIGRATION (RENDER SHELL):**

```bash
# 🚨 STEP 1: BACKUP DATABASE (CRITICAL!)
cd /var/www/html/db
cp narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# 📺 STEP 2: ADD youtube_url FIELD
echo "ALTER TABLE tbl_partners ADD COLUMN youtube_url TEXT;" | sqlite3 narrrf_world.sqlite

# ✅ STEP 3: VERIFY FIELD WAS ADDED
echo "SELECT sql FROM sqlite_master WHERE name = 'tbl_partners';" | sqlite3 narrrf_world.sqlite | grep -i youtube

# 💾 STEP 4: SAVE FINAL BACKUP TO /data (for next deployment)
cp narrrf_world.sqlite /data/narrrf_world.sqlite

echo "✅ YouTube URL + Video Gallery deployment complete!"
```

---

## 🎯 **FEATURES ADDED:**

### **1. YouTube URL Support:**
- ✅ YouTube URL input field in admin form (4-column grid)
- ✅ YouTube link displayed in partner modal (red gradient button with icon)
- ✅ Saved/loaded in database
- ✅ API handles youtube_url in add/update

### **2. Video Gallery Support:**
- ✅ Gallery accepts MP4 and WebM videos (max 10MB each)
- ✅ Videos display with `<video>` player in gallery grid
- ✅ Video preview in admin interface
- ✅ Videos show "🎥 Video" badge
- ✅ Max 5 items total (images + videos combined)

---

## 📊 **FILE CHANGES:**

1. **Database:** `db/migrations/add_partner_youtube_url.sql` - NEW!
2. **Admin API:** `api/admin/partner-management.php` - Updated (youtube_url + video support)
3. **Public API:** `api/user/get-partners.php` - Added youtube_url to SELECT
4. **Admin Interface:** `public/admin-interface.html` - YouTube field + video previews
5. **Frontend:** `public/partners.html` - YouTube link + video gallery display

---

## ✅ **TESTING CHECKLIST:**

### **Admin Interface:**
- [ ] Add YouTube URL to partner form
- [ ] Upload video to gallery (MP4/WebM)
- [ ] Upload images + videos mixed
- [ ] Verify video preview shows in admin
- [ ] Edit partner - verify YouTube URL loads
- [ ] Delete gallery video

### **Frontend Display:**
- [ ] YouTube link appears in partner modal
- [ ] Videos display with player in gallery
- [ ] Images still clickable for lightbox
- [ ] Videos not clickable (play directly)
- [ ] Gallery mixes images and videos correctly

---

**🧀 Ready for production deployment! 🚀**

