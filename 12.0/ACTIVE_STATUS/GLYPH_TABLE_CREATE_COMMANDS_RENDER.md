# 🧩 GLYPH MEMORY TABLE - RENDER CREATION COMMANDS

**Date:** January 6, 2026  
**Location:** Render Shell  
**Database:** `/var/www/html/db/narrrf_world.sqlite`

---

## ✅ **CREATE TABLE COMMANDS**

Run these commands in your Render shell:

```bash
# Navigate to database directory
cd /var/www/html/db

# Create the table and indexes
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

# Verify table was created
sqlite3 narrrf_world.sqlite ".schema tbl_glyph_memory_scores"

# Verify indexes
sqlite3 narrrf_world.sqlite ".indexes tbl_glyph_memory_scores"

# Verify table exists
sqlite3 narrrf_world.sqlite ".tables" | grep glyph
```

---

## ✅ **ALTERNATIVE: USE MIGRATION FILE**

If the migration file exists in the repo:

```bash
cd /var/www/html

# Run migration file
sqlite3 db/narrrf_world.sqlite < db/migrations/create_glyph_memory_scores_table.sql

# Verify
sqlite3 db/narrrf_world.sqlite ".schema tbl_glyph_memory_scores"
```

---

## ✅ **COPY TO /data/ AFTER CREATION**

After creating the table, copy the updated database to `/data/`:

```bash
# Copy updated database to persistent storage
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Verify copy
ls -lh /data/narrrf_world.sqlite
```

---

**Status:** ✅ **READY TO RUN IN RENDER SHELL**

