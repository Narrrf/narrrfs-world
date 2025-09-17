#!/bin/bash
# 🧀 SEASON 3 - THE CHEESE BEGINS - CORRECTED RESET SCRIPT
# Date: September 13, 2025
# Purpose: Reset Season 3 while preserving achievements and DSPOINC

echo "🧀 NARRRFS WORLD - SEASON 3 THE CHEESE BEGINS (CORRECTED)"
echo "========================================================"
echo "Starting Season 3 reset with achievements and DSPOINC preservation..."
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

echo "🔄 STEP 3: Resetting GAME SCORES ONLY (preserving achievements and DSPOINC)..."
sqlite3 "$DB_PATH" "
-- Reset Tetris GAME SCORES (mark as previous season)
UPDATE tbl_tetris_scores 
SET season = 'season_2_legacy', 
    is_current_season = 0,
    season_end_date = '$CURRENT_TIMESTAMP'
WHERE game = 'tetris' AND is_current_season = 1;

-- Reset Snake GAME SCORES (mark as previous season)
UPDATE tbl_tetris_scores 
SET season = 'season_2_legacy', 
    is_current_season = 0,
    season_end_date = '$CURRENT_TIMESTAMP'
WHERE game = 'snake' AND is_current_season = 1;

-- Reset Space Invaders GAME SCORES (mark as previous season)
UPDATE tbl_tetris_scores 
SET season = 'season_2_legacy', 
    is_current_season = 0,
    season_end_date = '$CURRENT_TIMESTAMP'
WHERE game = 'space_invaders' AND is_current_season = 1;
"
echo "✅ Game scores reset for Season 3 (achievements preserved)"
echo ""

echo "🧀 STEP 4: Resetting Cheese Hunt GAME SCORES..."
sqlite3 "$DB_PATH" "
-- Reset Cheese Hunt GAME SCORES (mark as previous season)
UPDATE tbl_cheese_clicks 
SET season = 'season_2_legacy', 
    is_current_season = 0,
    season_end_date = '$CURRENT_TIMESTAMP'
WHERE is_current_season = 1;
"
echo "✅ Cheese Hunt game scores reset for Season 3"
echo ""

echo "🏁 STEP 5: Resetting Discord Race GAME SCORES..."
sqlite3 "$DB_PATH" "
-- Reset Discord Race GAME SCORES (mark as previous season)
UPDATE tbl_race_participants 
SET season = 'season_2_legacy', 
    is_current_season = 0,
    season_end_date = '$CURRENT_TIMESTAMP'
WHERE is_current_season = 1;
"
echo "✅ Discord Race game scores reset for Season 3"
echo ""

echo "🏆 STEP 6: Resetting season leaderboards ONLY..."
sqlite3 "$DB_PATH" "
-- Clear current season leaderboards (preserves all-time data)
DELETE FROM tbl_season_leaderboards WHERE season_id IN (
    SELECT season_id FROM tbl_seasons WHERE season_name LIKE '%Season 3%'
);
"
echo "✅ Season leaderboards reset for Season 3"
echo ""

echo "🎮 STEP 7: Resetting season achievements ONLY..."
sqlite3 "$DB_PATH" "
-- Clear current season achievements (preserves all-time achievements)
DELETE FROM tbl_user_season_achievements WHERE season_id IN (
    SELECT season_id FROM tbl_seasons WHERE season_name LIKE '%Season 3%'
);
"
echo "✅ Season achievements reset for Season 3 (all-time achievements preserved)"
echo ""

echo "🔧 STEP 8: Updating season settings..."
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

echo "📊 STEP 9: PRESERVING ACHIEVEMENTS AND DSPOINC..."
sqlite3 "$DB_PATH" "
-- Verify achievements are preserved
SELECT 'Tetris Achievements:' as info, COUNT(*) as count FROM tbl_tetris_achievements;
SELECT 'Snake Achievements:' as info, COUNT(*) as count FROM tbl_snake_achievements;
SELECT 'Space Invaders Achievements:' as info, COUNT(*) as count FROM tbl_space_invaders_achievements;

-- Verify DSPOINC data is preserved
SELECT 'User Scores (DSPOINC):' as info, COUNT(*) as count FROM tbl_user_scores;
SELECT 'Score Adjustments:' as info, COUNT(*) as count FROM tbl_score_adjustments;
"
echo "✅ Achievements and DSPOINC data preserved"
echo ""

echo "📈 STEP 10: Verifying Season 3 setup..."
sqlite3 "$DB_PATH" "
-- Show current active season
SELECT 'Active Season:' as info, season_id, season_name, start_date, is_active 
FROM tbl_seasons WHERE is_active = 1;

-- Show season statistics
SELECT 'Season Stats:' as info, COUNT(*) as total_seasons 
FROM tbl_seasons;

-- Show game score counts (should be 0 for current season)
SELECT 'Current Season Game Scores:' as info, game, COUNT(*) as count 
FROM tbl_tetris_scores WHERE is_current_season = 1 
GROUP BY game;

-- Show preserved data counts
SELECT 'Preserved Achievements:' as info, 
    (SELECT COUNT(*) FROM tbl_tetris_achievements) + 
    (SELECT COUNT(*) FROM tbl_snake_achievements) + 
    (SELECT COUNT(*) FROM tbl_space_invaders_achievements) as total_achievements;
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

echo "🧀 SEASON 3 - THE CHEESE BEGINS - CORRECTED RESET COMPLETE!"
echo "============================================================"
echo "✅ All duplicate seasons cleaned up"
echo "✅ Fresh Season 3 created and activated"
echo "✅ Game scores reset (Tetris, Snake, Space Invaders, Cheese Hunt, Discord Race)"
echo "✅ Season leaderboards reset"
echo "✅ Season achievements reset"
echo "✅ Season settings updated"
echo "✅ Database optimized"
echo ""
echo "🛡️ PRESERVED DATA:"
echo "✅ All-time achievements (Tetris, Snake, Space Invaders)"
echo "✅ User DSPOINC balances"
echo "✅ Score adjustments"
echo "✅ Historical season data (as backup)"
echo ""
echo "🎮 Season 3 is now active and ready for fresh competition!"
echo "🏆 All leaderboards and admin interfaces will show Season 3 data"
echo "📊 Profile pages will display Season 3 progress"
echo "🏅 Achievements and DSPOINC remain intact"
echo ""
echo "Backup created at: $BACKUP_PATH"
echo "Season 3 started at: $CURRENT_TIMESTAMP"
echo ""
echo "Ready for The Cheese Begins! 🧀🚀"
