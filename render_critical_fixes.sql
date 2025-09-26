-- CRITICAL FIXES FOR LIVE RENDER DATABASE
-- Date: September 26, 2025
-- Purpose: Fix Hambearpig and all Season 4 score issues

-- Step 1: Move all Season 4 scores to Season 3
UPDATE tbl_tetris_scores 
SET season = 'Season 3 - The Ultimate Cheese Challenge' 
WHERE season = 'Season 4 - The Ultimate Cheese Challenge';

-- Step 2: Create Season 3 settings if they don't exist
INSERT OR IGNORE INTO tbl_season_settings (
    season_name, 
    tetris_max_score, 
    snake_max_score, 
    space_invaders_max_score, 
    points_per_line, 
    points_per_cheese, 
    points_per_invader,
    created_at
) VALUES (
    'Season 3 - The Ultimate Cheese Challenge', 
    10000, 
    10000, 
    10000, 
    1, 
    10, 
    0.01,
    datetime('now')
);

-- Step 3: Verify fixes
SELECT 'Season 4 scores remaining:' as check_type, COUNT(*) as count 
FROM tbl_tetris_scores 
WHERE season = 'Season 4 - The Ultimate Cheese Challenge'
UNION ALL
SELECT 'Season 3 scores total:' as check_type, COUNT(*) as count 
FROM tbl_tetris_scores 
WHERE season = 'Season 3 - The Ultimate Cheese Challenge'
UNION ALL
SELECT 'Season 3 settings exist:' as check_type, COUNT(*) as count 
FROM tbl_season_settings 
WHERE season_name = 'Season 3 - The Ultimate Cheese Challenge';

-- Step 4: Check Hambearpig specifically
SELECT 'Hambearpig Season 3 scores:' as user_check, COUNT(*) as count 
FROM tbl_tetris_scores 
WHERE discord_id = '776667871173541909' 
AND season = 'Season 3 - The Ultimate Cheese Challenge';
