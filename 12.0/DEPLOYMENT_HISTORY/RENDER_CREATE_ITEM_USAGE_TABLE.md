# 🚀 RENDER: CREATE ITEM USAGE REQUESTS TABLE

**Date:** November 1, 2025  
**Purpose:** Create `tbl_item_usage_requests` table for `/useitem` command  
**Environment:** Production (Render)  

---

## 📋 RENDER SHELL COMMANDS

### **Step 1: Access Render Shell**
1. Go to Render Dashboard
2. Select your web service
3. Click "Shell" tab
4. Run commands below

---

### **Step 2: Create Table**

```bash
# Navigate to database directory
cd /var/www/html/db

# Create the table
sqlite3 narrrf_world.sqlite "CREATE TABLE IF NOT EXISTS tbl_item_usage_requests (request_id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, username TEXT NOT NULL, item_id INTEGER NOT NULL, item_name TEXT NOT NULL, quantity INTEGER NOT NULL, reason TEXT, ticket_channel_id TEXT NOT NULL, item_value INTEGER NOT NULL, total_value INTEGER NOT NULL, status TEXT DEFAULT 'pending', admin_id TEXT, admin_username TEXT, admin_action TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, processed_at DATETIME);"

# Create indexes for performance
sqlite3 narrrf_world.sqlite "CREATE INDEX IF NOT EXISTS idx_item_usage_user ON tbl_item_usage_requests(user_id);"
sqlite3 narrrf_world.sqlite "CREATE INDEX IF NOT EXISTS idx_item_usage_status ON tbl_item_usage_requests(status);"
sqlite3 narrrf_world.sqlite "CREATE INDEX IF NOT EXISTS idx_item_usage_created ON tbl_item_usage_requests(created_at);"

# Verify table was created
echo ".schema tbl_item_usage_requests" | sqlite3 narrrf_world.sqlite
```

---

### **Step 3: Backup to /data**

```bash
# CRITICAL: Backup database to /data for next deployment
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

echo "✅ Table created and backed up to /data!"
```

---

## ✅ VERIFICATION

**Expected output from `.schema` command:**
```sql
CREATE TABLE tbl_item_usage_requests (
    request_id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    username TEXT NOT NULL,
    item_id INTEGER NOT NULL,
    item_name TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    reason TEXT,
    ticket_channel_id TEXT NOT NULL,
    item_value INTEGER NOT NULL,
    total_value INTEGER NOT NULL,
    status TEXT DEFAULT 'pending',
    admin_id TEXT,
    admin_username TEXT,
    admin_action TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME
);
```

---

## 🎯 WHAT THIS ENABLES

- ✅ `/useitem` command will work in Discord
- ✅ Creates admin approval tickets
- ✅ Tracks item usage history
- ✅ Complete audit trail for all item usage

---

## 🚨 CRITICAL NOTE

**This table must exist in BOTH databases:**
- ✅ Local: `C:\xampp-server\htdocs\narrrfs-world\db\narrrf_world.sqlite` (DONE)
- 🎯 Production: `/var/www/html/db/narrrf_world.sqlite` (RUN COMMANDS ABOVE)

**After creating on Render:**
- Your local bot will be able to write to production database
- All `/useitem` commands will work
- Admin approval system will be functional

---

**STATUS:** Ready to run on Render shell! 🚀

