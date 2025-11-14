# 📊 DATABASE SETUP AND SYNC - PRODUCTION TO LOCAL

**Date:** November 13, 2025  
**Purpose:** Document the database setup and sync process for Three.js Dimension / Riddle System  
**Status:** ✅ **COMPLETE - DATABASE SYNCED AND VERIFIED**

---

## 🎯 **OBJECTIVE**

Create production database tables in Render and sync to local for 2-3 weeks of local development, ensuring all tables, indexes, and schemas match between production and local databases.

---

## 📊 **DATABASE SETUP RESULTS**

### **✅ Production Database (Render):**

**Tables Created:**
1. **`tbl_cheese_hunt_captures`** - ✅ Created
   - Schema: ✅ Verified
   - Indexes: ✅ 3 indexes created (discord_id, level_id, capture_time)
   - Status: ✅ Ready for production

2. **`tbl_riddle_completions`** - ✅ Created
   - Schema: ✅ Verified
   - Indexes: ✅ 4 indexes created (discord_id+riddle_id, riddle_id, level_id, completed_at)
   - Unique Constraint: ✅ Verified (discord_id, riddle_id)
   - Status: ✅ Ready for production

3. **`tbl_user_traits`** - ✅ Verified (Already exists)
   - Schema: ✅ Verified
   - Foreign Key: ✅ Verified (references tbl_users(discord_id))
   - Status: ✅ Ready for production

### **✅ Local Database (After Download):**

**Tables Verified:**
1. **`tbl_cheese_hunt_captures`** - ✅ Synced
   - Schema: ✅ Matches production
   - Indexes: ✅ All indexes verified
   - Rows: 0 (empty - expected for new table)
   - Status: ✅ Ready for local development

2. **`tbl_riddle_completions`** - ✅ Synced
   - Schema: ✅ Matches production
   - Indexes: ✅ All indexes verified
   - Unique Constraint: ✅ Verified
   - Rows: 0 (empty - expected for new table)
   - Status: ✅ Ready for local development

3. **`tbl_user_traits`** - ✅ Verified
   - Schema: ✅ Matches production
   - Primary Key: ✅ Verified (user_id, trait)
   - Foreign Key: ✅ Verified (references tbl_users(discord_id))
   - Status: ✅ Ready for local development

---

## 🔍 **DATABASE SCHEMAS**

### **1. tbl_cheese_hunt_captures:**
```sql
CREATE TABLE tbl_cheese_hunt_captures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,
    discord_name TEXT,
    level_id TEXT NOT NULL,
    base_reward INTEGER NOT NULL,
    multiplier REAL NOT NULL,
    total_reward INTEGER NOT NULL,
    capture_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    session_id TEXT,
    metadata TEXT
);

CREATE INDEX idx_cheese_hunt_captures_discord ON tbl_cheese_hunt_captures(discord_id);
CREATE INDEX idx_cheese_hunt_captures_level ON tbl_cheese_hunt_captures(level_id);
CREATE INDEX idx_cheese_hunt_captures_time ON tbl_cheese_hunt_captures(capture_time);
```

### **2. tbl_riddle_completions:**
```sql
CREATE TABLE tbl_riddle_completions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,
    discord_name TEXT,
    riddle_id TEXT NOT NULL,
    level_id TEXT NOT NULL,
    base_reward INTEGER NOT NULL,
    multiplier REAL NOT NULL,
    total_reward INTEGER NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    session_id TEXT,
    metadata TEXT,
    UNIQUE(discord_id, riddle_id)
);

CREATE INDEX idx_riddle_completions_discord_riddle ON tbl_riddle_completions(discord_id, riddle_id);
CREATE INDEX idx_riddle_completions_riddle ON tbl_riddle_completions(riddle_id);
CREATE INDEX idx_riddle_completions_level ON tbl_riddle_completions(level_id);
CREATE INDEX idx_riddle_completions_time ON tbl_riddle_completions(completed_at);
```

### **3. tbl_user_traits:**
```sql
CREATE TABLE tbl_user_traits (
    user_id TEXT,
    trait TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, trait),
    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id)
);
```

---

## 🚀 **SETUP PROCESS**

### **1. Production Database Setup (Render):**

**Commands Executed:**
```bash
# Navigate to database directory
cd /var/www/html/db

# Backup database
cp narrrf_world.sqlite narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# Create tbl_cheese_hunt_captures table
sqlite3 narrrf_world.sqlite "CREATE TABLE IF NOT EXISTS tbl_cheese_hunt_captures (...); CREATE INDEX ..."

# Create tbl_riddle_completions table
sqlite3 narrrf_world.sqlite "CREATE TABLE IF NOT EXISTS tbl_riddle_completions (...); CREATE INDEX ..."

# Copy database to /data for persistence
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
```

**Results:**
- ✅ All tables created successfully
- ✅ All indexes created successfully
- ✅ Database backed up
- ✅ Database copied to `/data` for persistence

### **2. Database Verification (Production):**

**Verification Commands:**
```bash
# Verify all Three.js related tables
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND (name LIKE '%cheese%' OR name LIKE '%riddle%' OR name LIKE '%trait%') ORDER BY name;"

# Verify schemas
sqlite3 /var/www/html/db/narrrf_world.sqlite ".schema tbl_cheese_hunt_captures"
sqlite3 /var/www/html/db/narrrf_world.sqlite ".schema tbl_riddle_completions"
sqlite3 /var/www/html/db/narrrf_world.sqlite ".schema tbl_user_traits"

# Verify indexes
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT name FROM sqlite_master WHERE type='index' AND (tbl_name='tbl_cheese_hunt_captures' OR tbl_name='tbl_riddle_completions') ORDER BY tbl_name, name;"
```

**Results:**
- ✅ All tables exist in production
- ✅ All schemas verified
- ✅ All indexes created
- ✅ Unique constraints verified
- ✅ Foreign keys verified

### **3. Database Sync (Production to Local):**

**Sync Process:**
1. **Download Database:** Downloaded live database from Render to local
2. **Verify Tables:** Verified all tables exist in local database
3. **Verify Schemas:** Verified schemas match production
4. **Verify Indexes:** Verified all indexes exist in local database
5. **Verify Constraints:** Verified unique constraints and foreign keys

**Verification Results:**
- ✅ All tables synced successfully
- ✅ All schemas match production
- ✅ All indexes verified
- ✅ All constraints verified
- ✅ Ready for local development

---

## 📋 **ALL THREE.JS RELATED TABLES**

### **Production & Local:**
- ✅ `tbl_cheese_clicks` (already existed)
- ✅ `tbl_cheese_hunt_captures` (NEW - created in production, synced to local)
- ✅ `tbl_cheese_races` (already existed)
- ✅ `tbl_historical_cheese_stats` (already existed)
- ✅ `tbl_riddle_completions` (NEW - created in production, synced to local)
- ✅ `tbl_user_traits` (already existed, verified)

---

## 🔄 **DATABASE SYNC WORKFLOW**

### **Regular Sync (Every Few Days):**

1. **Download Latest Database:**
   ```bash
   # From local machine
   scp root@your-render-host:/var/www/html/db/narrrf_world.sqlite C:\xampp-server\htdocs\narrrfs-world\db\narrrf_world.sqlite
   ```

2. **Verify Tables Still Exist:**
   ```bash
   # Verify all Three.js related tables
   sqlite3 db/narrrf_world.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND (name LIKE '%cheese%' OR name LIKE '%riddle%' OR name LIKE '%trait%') ORDER BY name;"
   ```

3. **Verify Schemas Match:**
   ```bash
   # Verify schemas
   sqlite3 db/narrrf_world.sqlite ".schema tbl_cheese_hunt_captures"
   sqlite3 db/narrrf_world.sqlite ".schema tbl_riddle_completions"
   sqlite3 db/narrrf_world.sqlite ".schema tbl_user_traits"
   ```

### **Before Pushing to Production:**

1. **Backup Production Database:**
   ```bash
   # In Render shell
   cp /var/www/html/db/narrrf_world.sqlite /var/www/html/db/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite
   ```

2. **Verify Tables Still Exist:**
   ```bash
   # In Render shell
   sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND (name LIKE '%cheese%' OR name LIKE '%riddle%' OR name LIKE '%trait%') ORDER BY name;"
   ```

3. **Push Code Changes:**
   ```bash
   # Local
   git add .
   git commit -m "Three.js Dimension updates"
   git push origin render-deploy
   ```

---

## 🎯 **SUMMARY**

### **✅ What Was Accomplished:**

1. **Production Database Setup:**
   - ✅ Created `tbl_cheese_hunt_captures` table with 3 indexes
   - ✅ Created `tbl_riddle_completions` table with 4 indexes
   - ✅ Verified `tbl_user_traits` table exists
   - ✅ Created all indexes for performance optimization
   - ✅ Backed up database before changes
   - ✅ Copied database to `/data` for persistence

2. **Database Sync:**
   - ✅ Downloaded live database from Render to local
   - ✅ Verified all tables exist in local database
   - ✅ Verified all schemas match production
   - ✅ Verified all indexes exist in local database
   - ✅ Verified all constraints exist in local database

3. **Documentation:**
   - ✅ Created database setup commands
   - ✅ Created database sync commands
   - ✅ Created verification commands
   - ✅ Updated technical documentation
   - ✅ Updated daily status files

### **✅ Current Status:**

- **Production:** ✅ All tables created, verified, backed up
- **Local:** ✅ Database synced, tables verified, schemas match
- **Ready For:** ✅ 2-3 weeks of local development

### **🚀 Next Steps:**

1. ✅ **Database Setup:** Complete
2. ✅ **Database Sync:** Complete
3. ⏳ **Local Development:** Continue working locally for 2-3 weeks
4. ⏳ **Regular Sync:** Sync database from production every few days
5. ⏳ **Production Testing:** Test with real Discord users on production
6. ⏳ **Production Deployment:** Deploy code changes when ready

---

## 📝 **NOTES**

- **Database Location (Production):** `/var/www/html/db/narrrf_world.sqlite`
- **Database Location (Local):** `C:\xampp-server\htdocs\narrrfs-world\db\narrrf_world.sqlite`
- **Backup Location (Production):** `/var/www/html/db/narrrf_world_backup_YYYYMMDD_HHMMSS.sqlite`
- **Persistence Location (Production):** `/data/narrrf_world.sqlite`

- **Sync Frequency:** Every few days (or before major changes)
- **Backup Frequency:** Before every production push
- **Verification:** Always verify tables exist after sync

---

**🧀 Database Setup and Sync: ✅ COMPLETE - READY FOR LOCAL DEVELOPMENT!**

---

**Last Updated:** November 13, 2025  
**Status:** ✅ **COMPLETE - DATABASE SYNCED AND VERIFIED**

