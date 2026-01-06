# 🧩 GLYPH MEMORY LEADERBOARD - DEPLOYMENT CHECKLIST

**Date:** January 6, 2026  
**Status:** ✅ **READY FOR DEPLOYMENT**  
**Action:** Create database table in Render, then push code

---

## ✅ **STEP 1: CREATE TABLE IN RENDER DATABASE**

You're already in Render shell. Run these commands:

```bash
# Navigate to database location
cd /data

# Create the table using SQLite
sqlite3 narrrf_world.sqlite "
CREATE TABLE IF NOT EXISTS tbl_glyph_memory_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,
    discord_name TEXT,
    difficulty TEXT NOT NULL,
    time_ms INTEGER NOT NULL,
    pairs_matched INTEGER NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    season TEXT
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_glyph_discord_id ON tbl_glyph_memory_scores(discord_id);
CREATE INDEX IF NOT EXISTS idx_glyph_difficulty ON tbl_glyph_memory_scores(difficulty);
CREATE INDEX IF NOT EXISTS idx_glyph_time ON tbl_glyph_memory_scores(time_ms);
CREATE INDEX IF NOT EXISTS idx_glyph_season ON tbl_glyph_memory_scores(season);
"

# Verify table was created
sqlite3 narrrf_world.sqlite ".schema tbl_glyph_memory_scores"

# Verify indexes were created
sqlite3 narrrf_world.sqlite ".indexes tbl_glyph_memory_scores"
```

**Expected Output:**
- Table schema should show all columns
- Indexes should show 4 indexes created

---

## ✅ **STEP 2: VERIFY TABLE EXISTS**

```bash
# Check if table exists
sqlite3 narrrf_world.sqlite ".tables" | grep glyph

# Should output: tbl_glyph_memory_scores
```

---

## ✅ **STEP 3: PUSH CODE TO GIT**

After table is created, push all changes:

```bash
# From your local machine
cd C:\xampp-server\htdocs\narrrfs-world

# Add all changes
git add api/dev/get-leaderboard.php
git add public/glyph/glyph.html
git add public/glyph/styles.css
git add public/glyph/game.js
git add db/migrations/create_glyph_memory_scores_table.sql
git add 12.0/ACTIVE_STATUS/GLYPH_LEADERBOARD_*.md

# Commit
git commit -m "🧩 Add Glyph Memory leaderboard system (all-time, per difficulty)"

# Push to render-deploy branch
git push origin render-deploy
```

---

## ✅ **STEP 4: VERIFY DEPLOYMENT**

After Render deploys, test:

1. **Visit:** `https://narrrfs.world/public/glyph/glyph.html`
2. **Check:** Leaderboard section appears in menu
3. **Test:** Tabs switch between Easy/Medium/Hard
4. **Test:** Play a game and verify score saves
5. **Test:** Leaderboard updates after new score

---

## 📋 **FILES CHANGED**

### **Backend:**
- ✅ `api/dev/get-leaderboard.php` - Added glyph_memory support

### **Frontend:**
- ✅ `public/glyph/glyph.html` - Added leaderboard HTML
- ✅ `public/glyph/styles.css` - Added leaderboard CSS
- ✅ `public/glyph/game.js` - Added leaderboard JavaScript

### **Database:**
- ✅ `db/migrations/create_glyph_memory_scores_table.sql` - Migration file

---

## 🎯 **QUICK COMMANDS SUMMARY**

**In Render Shell:**
```bash
cd /data
sqlite3 narrrf_world.sqlite < /var/www/html/db/migrations/create_glyph_memory_scores_table.sql
sqlite3 narrrf_world.sqlite ".schema tbl_glyph_memory_scores"
```

**Or manually:**
```bash
cd /data
sqlite3 narrrf_world.sqlite "
CREATE TABLE IF NOT EXISTS tbl_glyph_memory_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,
    discord_name TEXT,
    difficulty TEXT NOT NULL,
    time_ms INTEGER NOT NULL,
    pairs_matched INTEGER NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    season TEXT
);
CREATE INDEX IF NOT EXISTS idx_glyph_discord_id ON tbl_glyph_memory_scores(discord_id);
CREATE INDEX IF NOT EXISTS idx_glyph_difficulty ON tbl_glyph_memory_scores(difficulty);
CREATE INDEX IF NOT EXISTS idx_glyph_time ON tbl_glyph_memory_scores(time_ms);
CREATE INDEX IF NOT EXISTS idx_glyph_season ON tbl_glyph_memory_scores(season);
"
```

---

**Status:** ✅ **READY - CREATE TABLE THEN PUSH CODE**

