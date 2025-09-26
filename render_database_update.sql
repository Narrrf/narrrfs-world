-- Render Database Update Script
-- Purpose: Consolidate Season 4 into Season 3 and set proper active season
-- Date: September 26, 2025

-- Step 1: Backup live database first
-- cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

-- Step 2: Set Season 4 as inactive
UPDATE tbl_seasons SET is_active = 0 WHERE season_name = 'Season 4 - The Ultimate Cheese Challenge';

-- Step 3: Set Season 3 as active
UPDATE tbl_seasons SET is_active = 1 WHERE season_name = 'Season 3 - The Ultimate Cheese Challenge';

-- Step 4: Update Season 3 start date to September 16th, 2025
UPDATE tbl_seasons 
SET start_date = '2025-09-16 00:00:00', end_date = NULL 
WHERE season_name = 'Season 3 - The Ultimate Cheese Challenge';

-- Step 5: Consolidate Season 4 data into Season 3 for all games
UPDATE tbl_tetris_scores 
SET season = 'Season 3 - The Ultimate Cheese Challenge' 
WHERE season = 'Season 4 - The Ultimate Cheese Challenge';

-- Step 6: Move pre-September 16th data to season_2
UPDATE tbl_tetris_scores 
SET season = 'season_2' 
WHERE season = 'Season 3 - The Ultimate Cheese Challenge' 
AND timestamp < '2025-09-16 00:00:00';

-- Step 7: Update cheese clicks data (if exists)
UPDATE tbl_cheese_clicks 
SET season = 'Season 3 - The Ultimate Cheese Challenge' 
WHERE season = 'season_1';

-- Move pre-September 16th cheese clicks to season_2
UPDATE tbl_cheese_clicks 
SET season = 'season_2' 
WHERE season = 'Season 3 - The Ultimate Cheese Challenge' 
AND timestamp < '2025-09-16 00:00:00';
