# 🚀 RENDER DEPLOYMENT - ITEM USAGE HISTORY TABLE

**Date:** October 31, 2025  
**Status:** READY FOR DEPLOYMENT  
**Target:** Production Database (`/var/www/html/db/narrrf_world.sqlite`)  

---

## 📋 DEPLOYMENT CHECKLIST

### **Pre-Deployment:**
- [x] Table created and tested locally
- [x] Master Ruleset updated (58 tables)
- [x] Admin interface Database Overview tab updated
- [x] API integration complete (`store-management.php`)
- [x] Frontend integration complete (`admin-interface.html`)
- [x] Test data verified working locally

### **Deployment Steps:**
- [ ] Connect to Render shell
- [ ] Backup production database
- [ ] Create table in production
- [ ] Verify table creation
- [ ] Test API endpoint
- [ ] Update `/data` backup

---

## 🗄️ TABLE SCHEMA

**Table Name:** `tbl_item_usage_history`  
**Purpose:** Track all item usage requests and approvals  
**Created:** October 31, 2025  

### **Columns:**
- `usage_id` - INTEGER PRIMARY KEY AUTOINCREMENT
- `user_id` - TEXT NOT NULL (Discord ID)
- `item_id` - INTEGER NOT NULL (Store item reference)
- `item_name` - TEXT NOT NULL (Item name for display)
- `quantity` - INTEGER DEFAULT 1 (How many used)
- `reason` - TEXT (User's explanation for usage)
- `status` - TEXT DEFAULT 'pending' (pending/approved/denied)
- `approved_by` - TEXT (Admin Discord ID who approved)
- `used_at` - DATETIME DEFAULT CURRENT_TIMESTAMP (When requested)
- `approved_at` - DATETIME (When admin approved/denied)

### **Indexes:**
- `idx_usage_history_user` - ON `user_id` (fast user lookups)
- `idx_usage_history_item` - ON `item_id` (item-based queries)
- `idx_usage_history_date` - ON `used_at` (date-based queries)

---

## 🛠️ RENDER DEPLOYMENT COMMANDS

### **1. Connect to Render Shell:**
```bash
# Connect via Render dashboard
# Service: narrrfs-world > Shell
```

### **2. Backup Production Database:**
```bash
# CRITICAL: Always backup before schema changes!
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# Verify backup created
ls -lh /data/narrrf_world_backup_*
```

### **3. Create Table (Method 1 - Direct SQL):**
```bash
# Single-line command for Render
sqlite3 /var/www/html/db/narrrf_world.sqlite "CREATE TABLE IF NOT EXISTS tbl_item_usage_history (usage_id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, item_id INTEGER NOT NULL, item_name TEXT NOT NULL, quantity INTEGER DEFAULT 1, reason TEXT, status TEXT DEFAULT 'pending', approved_by TEXT, used_at DATETIME DEFAULT CURRENT_TIMESTAMP, approved_at DATETIME, FOREIGN KEY (item_id) REFERENCES tbl_store_items(item_id)); CREATE INDEX IF NOT EXISTS idx_usage_history_user ON tbl_item_usage_history(user_id); CREATE INDEX IF NOT EXISTS idx_usage_history_item ON tbl_item_usage_history(item_id); CREATE INDEX IF NOT EXISTS idx_usage_history_date ON tbl_item_usage_history(used_at);"
```

### **4. Verify Table Creation:**
```bash
# Check if table exists
echo ".tables" | sqlite3 /var/www/html/db/narrrf_world.sqlite | grep "tbl_item_usage_history"

# Check table schema
echo ".schema tbl_item_usage_history" | sqlite3 /var/www/html/db/narrrf_world.sqlite

# Check indexes
echo ".indexes tbl_item_usage_history" | sqlite3 /var/www/html/db/narrrf_world.sqlite

# Check row count (should be 0 initially)
echo "SELECT COUNT(*) FROM tbl_item_usage_history;" | sqlite3 /var/www/html/db/narrrf_world.sqlite
```

### **5. Test API Endpoint:**
```bash
# Test from Render shell
curl "https://narrrfs.world/api/admin/store-management.php?action=get_user_store_activity&user_id=214519511850680320" | grep "usage_history"
```

### **6. Update /data Backup:**
```bash
# CRITICAL: Copy updated DB to /data for persistence
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Verify /data backup
ls -lh /data/narrrf_world.sqlite
```

---

## 🧪 POST-DEPLOYMENT VERIFICATION

### **Database Verification:**
```bash
# Total table count (should be 58 now)
echo "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" | sqlite3 /var/www/html/db/narrrf_world.sqlite

# Verify specific table
echo "SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_item_usage_history';" | sqlite3 /var/www/html/db/narrrf_world.sqlite

# Check all indexes
echo "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='tbl_item_usage_history';" | sqlite3 /var/www/html/db/narrrf_world.sqlite
```

### **API Verification:**
- Visit: `https://narrrfs.world/admin-interface.html`
- Navigate to: Database Overview tab
- Check: Should show "58" total tables
- Look for: ✨ tbl_item_usage_history with green glow

### **Functional Verification:**
- Go to: Store Management tab
- Enter user ID: `214519511850680320`
- Click: "View Full Activity"
- Check: Activity History section should load without errors
- Verify: Combined purchases & usage display works

---

## 📊 EXPECTED RESULTS

### **Success Criteria:**
✅ Table created with 10 columns  
✅ 3 indexes created (user, item, date)  
✅ Foreign key constraint on `item_id`  
✅ API returns `usage_history` array  
✅ Admin interface shows table in Database Overview  
✅ Activity History displays correctly  
✅ `/data` backup updated  

### **Production URLs:**
- Admin Interface: `https://narrrfs.world/admin-interface.html`
- API Endpoint: `https://narrrfs.world/api/admin/store-management.php`
- Database Path: `/var/www/html/db/narrrf_world.sqlite`
- Backup Path: `/data/narrrf_world.sqlite`

---

## 🐛 TROUBLESHOOTING

### **Issue: Table already exists**
```bash
# Check if table exists
echo ".schema tbl_item_usage_history" | sqlite3 /var/www/html/db/narrrf_world.sqlite

# If exists, verify it has correct schema
# If schema is wrong, drop and recreate (DANGEROUS - backup first!)
```

### **Issue: Permission denied**
```bash
# Check database file permissions
ls -la /var/www/html/db/narrrf_world.sqlite

# Should be writable
```

### **Issue: API returns error**
```bash
# Check PHP error logs
tail -n 50 /var/log/error.log

# Test API directly
curl -v "https://narrrfs.world/api/admin/store-management.php?action=get_user_store_activity&user_id=214519511850680320"
```

---

## 🔄 ROLLBACK PROCEDURE

### **If Deployment Fails:**
```bash
# 1. Restore from backup
cp /data/narrrf_world_backup_YYYYMMDD_HHMMSS.sqlite /var/www/html/db/narrrf_world.sqlite

# 2. Verify restoration
echo ".tables" | sqlite3 /var/www/html/db/narrrf_world.sqlite

# 3. Update /data
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# 4. Test API
curl "https://narrrfs.world/api/admin/store-management.php?action=get_user_store_activity&user_id=214519511850680320"
```

---

## 📝 DEPLOYMENT LOG

**Deployment Date:** [TO BE FILLED]  
**Deployed By:** [TO BE FILLED]  
**Deployment Time:** [TO BE FILLED]  
**Result:** [SUCCESS/FAILURE]  
**Issues:** [ANY ISSUES ENCOUNTERED]  
**Resolution:** [HOW ISSUES WERE RESOLVED]  

---

**🧀 READY FOR PRODUCTION DEPLOYMENT! 🧀**

