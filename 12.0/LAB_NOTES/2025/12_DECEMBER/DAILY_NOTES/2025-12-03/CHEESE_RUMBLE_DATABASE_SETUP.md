# 🗄️ CHEESE RUMBLE - DATABASE TABLE SETUP

**Date:** December 3, 2025  
**Status:** ⏳ **READY TO CREATE TABLES**

---

## ✅ **SYNTAX ERROR FIXED**

The syntax error in `discord/index.js` has been fixed. The bot should now start correctly.

---

## 📋 **CREATE DATABASE TABLES**

### **Option 1: Local Database (Recommended First)**

**Location:** `C:\xampp-server\htdocs\narrrfs-world\db\narrrf_world.sqlite`

**Steps:**
1. Navigate to the db folder:
   ```powershell
   cd C:\xampp-server\htdocs\narrrfs-world\db
   ```

2. Run the SQL migration:
   ```powershell
   sqlite3 narrrf_world.sqlite < ..\Backup Start Season 4 all Last test\db\migrations\create_cheese_rumble_tables.sql
   ```

   OR manually execute the SQL:
   ```powershell
   sqlite3 narrrf_world.sqlite
   ```
   Then paste the SQL from `db/migrations/create_cheese_rumble_tables.sql`

---

### **Option 2: Production Database (Render)**

**Location:** `/var/www/html/db/narrrf_world.sqlite` on Render

**Steps:**
1. Connect to Render shell
2. Navigate to database directory:
   ```bash
   cd /var/www/html/db
   ```

3. Create the tables using sqlite3:
   ```bash
   sqlite3 narrrf_world.sqlite < /path/to/create_cheese_rumble_tables.sql
   ```

   OR execute SQL directly:
   ```bash
   sqlite3 narrrf_world.sqlite
   ```
   Then paste the SQL commands

---

## 🔍 **VERIFY TABLES CREATED**

After creating tables, verify they exist:

**Local:**
```powershell
sqlite3 narrrf_world.sqlite ".tables" | findstr rumble
```

**Production:**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite ".tables" | grep rumble
```

**Expected Output:**
```
tbl_cheese_rumbles
tbl_rumble_participants
```

---

## 📝 **SQL FILE LOCATION**

The SQL migration file is located at:
- **Local:** `C:\xampp-server\htdocs\Backup Start Season 4 all Last test\db\migrations\create_cheese_rumble_tables.sql`
- **Or copy from:** `db/migrations/create_cheese_rumble_tables.sql` (if exists in project)

---

## ✅ **TEST BOT STARTUP**

After creating tables:

1. **Deploy commands** (if not already done):
   ```powershell
   cd C:\xampp-server\htdocs\narrrfs-world\discord
   node deploy-commands.js
   ```

2. **Start the bot**:
   ```powershell
   npm start
   ```

3. **Expected output:**
   ```
   ✅ Races loaded from database
   ✅ Rumbles loaded from database
   🚀 Narrrf's World Bot is ready as [bot name]
   ```

---

## 🚨 **TROUBLESHOOTING**

### **If bot still won't start:**
1. Check for syntax errors: `node -c index.js`
2. Check database connection
3. Verify tables exist: Run verification command above

### **If tables already exist:**
- The SQL uses `CREATE TABLE IF NOT EXISTS` so it's safe to run multiple times

---

**Status:** ✅ **SYNTAX ERROR FIXED** - Ready to create tables and test!

