# 🚀 RENDER COMMANDS: Add gallery_images Field to Production

**Date:** October 29, 2025  
**Purpose:** Add `gallery_images` field to `tbl_partners` table on production  
**Database:** `/var/www/html/db/narrrf_world.sqlite`

---

## 📋 **COPY-PASTE COMMANDS FOR RENDER SHELL:**

```bash
# 🚨 STEP 1: BACKUP DATABASE (CRITICAL!)
cd /var/www/html/db
cp narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# 📸 STEP 2: ADD gallery_images FIELD
echo "ALTER TABLE tbl_partners ADD COLUMN gallery_images TEXT;" | sqlite3 narrrf_world.sqlite

# ✅ STEP 3: VERIFY FIELD WAS ADDED
echo "SELECT sql FROM sqlite_master WHERE name = 'tbl_partners';" | sqlite3 narrrf_world.sqlite | grep -i gallery

# 💾 STEP 4: SAVE FINAL BACKUP TO /data (for next deployment)
cp narrrf_world.sqlite /data/narrrf_world.sqlite

# 📊 STEP 5: VERIFY PARTNER COUNT (OPTIONAL)
echo "SELECT COUNT(*) as partner_count FROM tbl_partners;" | sqlite3 narrrf_world.sqlite
```

---

## ✅ **EXPECTED OUTPUT:**

### **Step 2 Output:**
```
(No error = Success!)
```

### **Step 3 Output:**
Should show SQL schema with `gallery_images TEXT` in the CREATE statement.

### **Step 5 Output:**
```
3
```

---

## 🎯 **QUICK ONE-LINER (All Steps Combined):**

```bash
cd /var/www/html/db && cp narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite && echo "ALTER TABLE tbl_partners ADD COLUMN gallery_images TEXT;" | sqlite3 narrrf_world.sqlite && echo "SELECT sql FROM sqlite_master WHERE name = 'tbl_partners';" | sqlite3 narrrf_world.sqlite | grep gallery && cp narrrf_world.sqlite /data/narrrf_world.sqlite && echo "✅ Migration complete! Partner count:" && echo "SELECT COUNT(*) FROM tbl_partners;" | sqlite3 narrrf_world.sqlite
```

---

## 🚨 **IF FIELD ALREADY EXISTS:**

If you see error: `Parse error near line 1: duplicate column name: gallery_images`

**That's OK!** It means the field is already there. Just skip Step 2 and continue with verification:

```bash
# Just verify it exists
echo "SELECT sql FROM sqlite_master WHERE name = 'tbl_partners';" | sqlite3 narrrf_world.sqlite | grep gallery
```

---

## ✅ **VERIFICATION CHECKLIST:**

- [ ] Backup created in `/data/`
- [ ] No error in Step 2 (or field already exists)
- [ ] Step 3 shows `gallery_images TEXT` in schema
- [ ] Final backup saved to `/data/narrrf_world.sqlite`
- [ ] Partner count shows correct number

---

## 📝 **NOTES:**

- **Field Type:** TEXT (stores JSON array of filenames)
- **Default:** NULL (optional field)
- **Storage Format:** `["1_gallery_1_1761748000.png", "1_gallery_2_1761748001.jpg"]`
- **Max Images:** 5 per partner (enforced in API)

---

**Ready to run! Just copy-paste the commands above! 🚀**

