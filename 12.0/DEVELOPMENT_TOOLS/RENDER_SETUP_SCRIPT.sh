#!/bin/bash
# 🚀 RENDER DATABASE SETUP SCRIPT
# Three.js Dimension / Riddle System Tables
# Date: November 13, 2025
# Purpose: Create all necessary tables for Three.js Dimension and Riddle System in production (Render)

set -e  # Exit on error

DB_PATH="/var/www/html/db/narrrf_world.sqlite"
BACKUP_PATH="/var/www/html/db/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite"
DATA_PATH="/data/narrrf_world.sqlite"

echo "🚀 RENDER DATABASE SETUP - Three.js Dimension / Riddle System"
echo "=============================================================="
echo ""

# Step 1: Navigate to database directory
echo "📁 Step 1: Navigating to database directory..."
cd /var/www/html/db
echo "✅ Current directory: $(pwd)"
echo ""

# Step 2: Backup database (CRITICAL!)
echo "💾 Step 2: Backing up database..."
if [ -f "$DB_PATH" ]; then
    cp "$DB_PATH" "$BACKUP_PATH"
    echo "✅ Database backed up to: $BACKUP_PATH"
else
    echo "⚠️  Database file not found: $DB_PATH"
    echo "   Creating new database..."
fi
echo ""

# Step 3: Create tbl_cheese_hunt_captures table
echo "🧀 Step 3: Creating tbl_cheese_hunt_captures table..."
sqlite3 "$DB_PATH" <<EOF
CREATE TABLE IF NOT EXISTS tbl_cheese_hunt_captures (
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

CREATE INDEX IF NOT EXISTS idx_cheese_hunt_captures_discord ON tbl_cheese_hunt_captures(discord_id);
CREATE INDEX IF NOT EXISTS idx_cheese_hunt_captures_level ON tbl_cheese_hunt_captures(level_id);
CREATE INDEX IF NOT EXISTS idx_cheese_hunt_captures_time ON tbl_cheese_hunt_captures(capture_time);
EOF
echo "✅ tbl_cheese_hunt_captures table created"
echo ""

# Step 4: Create tbl_riddle_completions table
echo "🧩 Step 4: Creating tbl_riddle_completions table..."
sqlite3 "$DB_PATH" <<EOF
CREATE TABLE IF NOT EXISTS tbl_riddle_completions (
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

CREATE INDEX IF NOT EXISTS idx_riddle_completions_discord_riddle ON tbl_riddle_completions(discord_id, riddle_id);
CREATE INDEX IF NOT EXISTS idx_riddle_completions_riddle ON tbl_riddle_completions(riddle_id);
CREATE INDEX IF NOT EXISTS idx_riddle_completions_level ON tbl_riddle_completions(level_id);
CREATE INDEX IF NOT EXISTS idx_riddle_completions_time ON tbl_riddle_completions(completed_at);
EOF
echo "✅ tbl_riddle_completions table created"
echo ""

# Step 5: Verify tbl_user_traits exists
echo "🔍 Step 5: Verifying tbl_user_traits table exists..."
if sqlite3 "$DB_PATH" ".schema tbl_user_traits" 2>/dev/null | grep -q "CREATE TABLE"; then
    echo "✅ tbl_user_traits table exists"
    sqlite3 "$DB_PATH" ".schema tbl_user_traits"
else
    echo "⚠️  tbl_user_traits table not found - creating it..."
    sqlite3 "$DB_PATH" <<EOF
CREATE TABLE IF NOT EXISTS tbl_user_traits (
    user_id TEXT,
    trait TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, trait),
    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id)
);
EOF
    echo "✅ tbl_user_traits table created"
fi
echo ""

# Step 6: Verify all tables created
echo "📊 Step 6: Verifying all tables created..."
echo "Tables found:"
sqlite3 "$DB_PATH" "SELECT name FROM sqlite_master WHERE type='table' AND (name LIKE '%cheese%' OR name LIKE '%riddle%' OR name LIKE '%trait%') ORDER BY name;"
echo ""

# Step 7: Verify table schemas
echo "📋 Step 7: Verifying table schemas..."
echo ""
echo "--- tbl_cheese_hunt_captures schema ---"
sqlite3 "$DB_PATH" ".schema tbl_cheese_hunt_captures"
echo ""
echo "--- tbl_riddle_completions schema ---"
sqlite3 "$DB_PATH" ".schema tbl_riddle_completions"
echo ""
echo "--- tbl_user_traits schema ---"
sqlite3 "$DB_PATH" ".schema tbl_user_traits"
echo ""

# Step 8: Verify indexes
echo "🔍 Step 8: Verifying indexes..."
echo "Indexes found:"
sqlite3 "$DB_PATH" "SELECT name FROM sqlite_master WHERE type='index' AND (tbl_name='tbl_cheese_hunt_captures' OR tbl_name='tbl_riddle_completions') ORDER BY tbl_name, name;"
echo ""

# Step 9: Copy database to /data (for deployment persistence)
echo "💾 Step 9: Copying database to /data for deployment persistence..."
if [ -d "/data" ]; then
    cp "$DB_PATH" "$DATA_PATH"
    echo "✅ Database copied to: $DATA_PATH"
else
    echo "⚠️  /data directory not found - skipping copy"
fi
echo ""

# Step 10: Final verification
echo "✅ SETUP COMPLETE!"
echo "=============================================================="
echo ""
echo "📊 Summary:"
echo "  - tbl_cheese_hunt_captures: ✅ Created"
echo "  - tbl_riddle_completions: ✅ Created"
echo "  - tbl_user_traits: ✅ Verified"
echo "  - Indexes: ✅ Created"
echo "  - Backup: ✅ Created"
echo ""
echo "🚀 Next Steps:"
echo "  1. Download live database to local"
echo "  2. Verify tables exist in local database"
echo "  3. Test API endpoints in production"
echo "  4. Continue local development with synced database"
echo ""

