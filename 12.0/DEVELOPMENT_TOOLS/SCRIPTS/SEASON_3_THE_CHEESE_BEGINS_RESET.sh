#!/bin/bash
# 🧀 SEASON 3 - THE CHEESE BEGINS - COMPLETE RESET & SYNC SCRIPT
# Date: September 13, 2025
# Purpose: Clean up duplicate seasons and start fresh Season 3 with reset scores

echo "🧀 NARRRFS WORLD - SEASON 3 THE CHEESE BEGINS"
echo "=============================================="
echo "Starting complete season reset and synchronization..."
echo ""

# Set the database path
DB_PATH="/var/www/html/db/narrrf_world.sqlite"
BACKUP_PATH="/data/narrrf_world_backup_season3_reset.sqlite"

# Create backup before making changes
echo "📦 Creating backup before Season 3 reset..."
cp "$DB_PATH" "$BACKUP_PATH"
echo "✅ Backup created: $BACKUP_PATH"
echo ""

# Get current timestamp for Season 3 start
CURRENT_TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
echo "🕐 Season 3 start time: $CURRENT_TIMESTAMP"
echo ""

echo "🧹 STEP 1: Cleaning up duplicate seasons..."
sqlite3 "$DB_PATH" "
-- Deactivate all existing seasons
UPDATE tbl_seasons SET is_active = 0;

-- Delete duplicate Season 3 entries (keep only the latest one)
DELETE FROM tbl_seasons WHERE season_name LIKE '%Season 3%' AND season_id != (
    SELECT MAX(season_id) FROM tbl_seasons WHERE season_name LIKE '%Season 3%'
);

-- Delete any other duplicate seasons
DELETE FROM tbl_seasons WHERE season_id IN (
    SELECT season_id FROM tbl_seasons 
    GROUP BY season_name, start_date 
    HAVING COUNT(*) > 1
);
"
echo "✅ Duplicate seasons cleaned up"
echo ""

echo "🎯 STEP 2: Creating fresh Season 3 - The Cheese Begins..."
sqlite3 "$DB_PATH" "
-- Insert new Season 3
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 3 - The Cheese Begins', '$CURRENT_TIMESTAMP', NULL, 1);
"
echo "✅ Season 3 - The Cheese Begins created and activated"
echo ""

echo "🔄 STEP 3: Resetting all game scores for Season 3..."
sqlite3 "$DB_PATH" "
-- Reset Tetris scores (mark as previous season)
UPDATE tbl_tetris_scores 
SET season = 'season_2_legacy', 
    is_current_season = 0,
    season_end_date = '$CURRENT_TIMESTAMP'
WHERE game = 'tetris' AND is_current_season = 1;

-- Reset Snake scores (mark as previous season)
UPDATE tbl_tetris_scores 
SET season = 'season_2_legacy', 
    is_current_season = 0,
    season_end_date = '$CURRENT_TIMESTAMP'
WHERE game = 'snake' AND is_current_season = 1;

-- Reset Space Invaders scores (mark as previous season)
UPDATE tbl_tetris_scores 
SET season = 'season_2_legacy', 
    is_current_season = 0,
    season_end_date = '$CURRENT_TIMESTAMP'
WHERE game = 'space_invaders' AND is_current_season = 1;
"
echo "✅ All game scores reset for Season 3"
echo ""

echo "🧀 STEP 4: Resetting Cheese Hunt scores..."
sqlite3 "$DB_PATH" "
-- Reset Cheese Hunt scores (mark as previous season)
UPDATE tbl_cheese_clicks 
SET season = 'season_2_legacy', 
    is_current_season = 0,
    season_end_date = '$CURRENT_TIMESTAMP'
WHERE is_current_season = 1;
"
echo "✅ Cheese Hunt scores reset for Season 3"
echo ""

echo "🏁 STEP 5: Resetting Discord Race scores..."
sqlite3 "$DB_PATH" "
-- Reset Discord Race scores (mark as previous season)
UPDATE tbl_race_participants 
SET season = 'season_2_legacy', 
    is_current_season = 0,
    season_end_date = '$CURRENT_TIMESTAMP'
WHERE is_current_season = 1;
"
echo "✅ Discord Race scores reset for Season 3"
echo ""

echo "📊 STEP 6: Resetting user scores..."
sqlite3 "$DB_PATH" "
-- Reset user scores (mark as previous season)
UPDATE tbl_user_scores 
SET season = 'season_2_legacy'
WHERE season = 'season_2' OR season = 'season_3';
"
echo "✅ User scores reset for Season 3"
echo ""

echo "🏆 STEP 7: Resetting season leaderboards..."
sqlite3 "$DB_PATH" "
-- Clear current season leaderboards
DELETE FROM tbl_season_leaderboards WHERE season_id IN (
    SELECT season_id FROM tbl_seasons WHERE season_name LIKE '%Season 3%'
);
"
echo "✅ Season leaderboards reset for Season 3"
echo ""

echo "🎮 STEP 8: Resetting season achievements..."
sqlite3 "$DB_PATH" "
-- Clear current season achievements
DELETE FROM tbl_user_season_achievements WHERE season_id IN (
    SELECT season_id FROM tbl_seasons WHERE season_name LIKE '%Season 3%'
);
"
echo "✅ Season achievements reset for Season 3"
echo ""

echo "🔧 STEP 9: Updating season settings..."
sqlite3 "$DB_PATH" "
-- Update season settings for Season 3
UPDATE tbl_season_settings 
SET season_name = 'Season 3 - The Cheese Begins',
    created_at = '$CURRENT_TIMESTAMP'
WHERE season_name LIKE '%season_3%' OR season_name LIKE '%Season 3%';

-- Insert new season settings if none exist
INSERT OR IGNORE INTO tbl_season_settings (
    season_name, tetris_max_score, snake_max_score, points_per_line, 
    points_per_cheese, space_invaders_max_score, points_per_invader, created_at
) VALUES (
    'Season 3 - The Cheese Begins', 1000, 1000, 10, 10, 10000, 0.001, '$CURRENT_TIMESTAMP'
);
"
echo "✅ Season settings updated for Season 3"
echo ""

echo "📈 STEP 10: Verifying Season 3 setup..."
sqlite3 "$DB_PATH" "
-- Show current active season
SELECT 'Active Season:' as info, season_id, season_name, start_date, is_active 
FROM tbl_seasons WHERE is_active = 1;

-- Show season statistics
SELECT 'Season Stats:' as info, COUNT(*) as total_seasons 
FROM tbl_seasons;

-- Show game score counts
SELECT 'Game Scores:' as info, game, COUNT(*) as count 
FROM tbl_tetris_scores WHERE is_current_season = 1 
GROUP BY game;
"
echo "✅ Season 3 verification complete"
echo ""

echo "🎯 STEP 11: Final cleanup and optimization..."
sqlite3 "$DB_PATH" "
-- Optimize database after changes
VACUUM;
ANALYZE;
"
echo "✅ Database optimized"
echo ""

echo "🧀 SEASON 3 - THE CHEESE BEGINS - RESET COMPLETE!"
echo "================================================="
echo "✅ All duplicate seasons cleaned up"
echo "✅ Fresh Season 3 created and activated"
echo "✅ All game scores reset (Tetris, Snake, Space Invaders)"
echo "✅ Cheese Hunt scores reset"
echo "✅ Discord Race scores reset"
echo "✅ User scores reset"
echo "✅ Season leaderboards reset"
echo "✅ Season achievements reset"
echo "✅ Season settings updated"
echo "✅ Database optimized"
echo ""
echo "🎮 Season 3 is now active and ready for fresh competition!"
echo "🏆 All leaderboards and admin interfaces will show Season 3 data"
echo "📊 Profile pages will display Season 3 progress"
echo ""
echo "Backup created at: $BACKUP_PATH"
echo "Season 3 started at: $CURRENT_TIMESTAMP"
echo ""
echo "Ready for The Cheese Begins! 🧀🚀"
