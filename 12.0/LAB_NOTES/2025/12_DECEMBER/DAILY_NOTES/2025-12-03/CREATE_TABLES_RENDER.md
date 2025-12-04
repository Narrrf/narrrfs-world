# 🗄️ CREATE CHEESE RUMBLE TABLES ON RENDER

**Date:** December 3, 2025  
**Status:** ✅ **BOT RUNNING - READY TO CREATE TABLES**

---

## 🎯 **QUICK SETUP - RENDER DATABASE**

Since your database is live on Render and mirrored locally, you can create the tables directly in Render.

---

## 📋 **METHOD 1: RENDER SHELL (Recommended)**

### **Step 1: Connect to Render Shell**
- Go to your Render dashboard
- Open the Shell for your web service

### **Step 2: Navigate to Database Directory**
```bash
cd /var/www/html/db
```

### **Step 3: Execute SQL File**
```bash
sqlite3 narrrf_world.sqlite < /var/www/html/db/migrations/create_cheese_rumble_tables.sql
```

**OR execute SQL directly:**
```bash
sqlite3 narrrf_world.sqlite
```

Then paste this SQL:
```sql
-- Table 1: Main rumble events
CREATE TABLE IF NOT EXISTS tbl_cheese_rumbles (
    rumble_id TEXT PRIMARY KEY,
    creator_id TEXT NOT NULL,
    creator_name TEXT NOT NULL,
    channel_id TEXT NOT NULL,
    message_id TEXT,
    status TEXT NOT NULL DEFAULT 'waiting',
    max_players INTEGER DEFAULT 10,
    duration INTEGER DEFAULT 300,
    dspoinc_reward INTEGER DEFAULT 5000,
    role_reward TEXT,
    tag_role TEXT,
    auto_start INTEGER DEFAULT 0,
    comment TEXT,
    current_round INTEGER DEFAULT 0,
    players_alive INTEGER DEFAULT 0,
    events_log TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,
    finished_at DATETIME,
    ended_at DATETIME,
    winner_id TEXT,
    winner_name TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table 2: Rumble participants
CREATE TABLE IF NOT EXISTS tbl_rumble_participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rumble_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    username TEXT NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'alive',
    kills INTEGER DEFAULT 0,
    eliminated_by TEXT,
    elimination_reason TEXT,
    eliminated_in_round INTEGER,
    final_position INTEGER,
    dspoinc_earned INTEGER DEFAULT 0,
    season TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rumble_id) REFERENCES tbl_cheese_rumbles(rumble_id)
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_rumble_status ON tbl_cheese_rumbles(status);
CREATE INDEX IF NOT EXISTS idx_rumble_creator ON tbl_cheese_rumbles(creator_id);
CREATE INDEX IF NOT EXISTS idx_rumble_participants_rumble ON tbl_rumble_participants(rumble_id);
CREATE INDEX IF NOT EXISTS idx_rumble_participants_user ON tbl_rumble_participants(user_id);
CREATE INDEX IF NOT EXISTS idx_rumble_participants_status ON tbl_rumble_participants(status);
```

Type `.exit` to exit sqlite3.

---

## 📋 **METHOD 2: LOCAL DATABASE (Will Sync)**

If you create tables locally, they should sync to Render on next deployment.

### **Step 1: Navigate to Database**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world\db
```

### **Step 2: Execute SQL**
```powershell
sqlite3 narrrf_world.sqlite < migrations\create_cheese_rumble_tables.sql
```

**OR manually:**
```powershell
sqlite3 narrrf_world.sqlite
```
Then paste the SQL from above.

---

## 🔍 **VERIFY TABLES CREATED**

After creating tables, verify they exist:

**Render:**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite ".tables" | grep rumble
```

**Local:**
```powershell
sqlite3 narrrf_world.sqlite ".tables" | findstr rumble
```

**Expected Output:**
```
tbl_cheese_rumbles
tbl_rumble_participants
```

---

## ✅ **TEST BOT**

After creating tables, the bot should already be running. Test by:

1. **Check bot logs** - Should see:
   ```
   ✅ Races loaded from database
   ✅ Rumbles loaded from database
   ```

2. **Test command** - In Discord:
   ```
   /cheese-rumble create players:10 rounds:5 reward:5000
   ```

---

## 🚨 **TROUBLESHOOTING**

### **If bot shows errors about tables:**
- Tables might not exist yet - Create them using Method 1 above
- Check database connection is working

### **If tables already exist:**
- The SQL uses `CREATE TABLE IF NOT EXISTS` so it's safe to run multiple times

---

**Status:** ✅ **BOT RUNNING** - Ready to create tables and test! 🚀

