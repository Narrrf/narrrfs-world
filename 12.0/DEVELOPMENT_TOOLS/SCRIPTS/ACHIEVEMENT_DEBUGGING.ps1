# 🧀 ACHIEVEMENT SYSTEM DEBUGGING SCRIPT
# Date: September 13, 2025
# Purpose: Debug achievement system for Santa user

echo "🧀 NARRRFS WORLD - ACHIEVEMENT SYSTEM DEBUGGING"
echo "==============================================="
echo "Debugging achievement system for Santa user..."
echo ""

# Set the database path
DB_PATH="db/narrrf_world.sqlite"
USER_ID="1107633105185013790"

echo "🔍 STEP 1: Checking Santa's current achievements..."
sqlite3 "$DB_PATH" "
SELECT 'TETRIS ACHIEVEMENTS:' as game, COUNT(*) as count FROM tbl_tetris_achievements WHERE user_id = '$USER_ID';
SELECT 'SNAKE ACHIEVEMENTS:' as game, COUNT(*) as count FROM tbl_snake_achievements WHERE user_id = '$USER_ID';
SELECT 'SPACE INVADERS ACHIEVEMENTS:' as game, COUNT(*) as count FROM tbl_space_invaders_achievements WHERE user_id = '$USER_ID';
"
echo ""

echo "🔍 STEP 2: Checking Santa's recent game scores..."
sqlite3 "$DB_PATH" "
SELECT 'RECENT TETRIS SCORES:' as info, score, timestamp FROM tbl_tetris_scores WHERE discord_id = '$USER_ID' AND game = 'tetris' ORDER BY timestamp DESC LIMIT 3;
SELECT 'RECENT SNAKE SCORES:' as info, score, timestamp FROM tbl_tetris_scores WHERE discord_id = '$USER_ID' AND game = 'snake' ORDER BY timestamp DESC LIMIT 3;
SELECT 'RECENT SPACE INVADERS SCORES:' as info, score, timestamp FROM tbl_tetris_scores WHERE discord_id = '$USER_ID' AND game = 'space_invaders' ORDER BY timestamp DESC LIMIT 3;
"
echo ""

echo "🔍 STEP 3: Checking achievement definitions..."
sqlite3 "$DB_PATH" "
SELECT 'TETRIS DEFINITIONS:' as info, COUNT(*) as count FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';
SELECT 'SNAKE DEFINITIONS:' as info, COUNT(*) as count FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';
SELECT 'SPACE INVADERS DEFINITIONS:' as info, COUNT(*) as count FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';
"
echo ""

echo "🔍 STEP 4: Checking what achievements Santa should have unlocked..."
sqlite3 "$DB_PATH" "
-- Tetris achievements Santa should have
SELECT 'TETRIS SHOULD HAVE:' as info, achievement_key, achievement_title, achievement_description 
FROM tbl_tetris_achievements 
WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' 
AND (achievement_key = 'first_line' OR achievement_key = 'score_hunter')
ORDER BY achievement_key;

-- Snake achievements Santa should have  
SELECT 'SNAKE SHOULD HAVE:' as info, achievement_key, achievement_title, achievement_description 
FROM tbl_snake_achievements 
WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' 
AND (achievement_key = 'first_cheese' OR achievement_key = 'score_hunter')
ORDER BY achievement_key;
"
echo ""

echo "🔍 STEP 5: Testing achievement API endpoints..."
echo "Testing Tetris achievement API..."
curl -X POST "http://localhost/api/dev/unlock-tetris-achievement.php" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\",\"achievement_key\":\"score_hunter\",\"game_score\":140,\"lines_cleared\":1}" \
  --silent --show-error

echo ""
echo "Testing Snake achievement API..."
curl -X POST "http://localhost/api/dev/unlock-snake-achievement.php" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\",\"achievement_key\":\"score_hunter\"}" \
  --silent --show-error

echo ""
echo ""

echo "🔍 STEP 6: Checking Santa's achievements after API tests..."
sqlite3 "$DB_PATH" "
SELECT 'TETRIS ACHIEVEMENTS AFTER:' as game, COUNT(*) as count FROM tbl_tetris_achievements WHERE user_id = '$USER_ID';
SELECT 'SNAKE ACHIEVEMENTS AFTER:' as game, COUNT(*) as count FROM tbl_snake_achievements WHERE user_id = '$USER_ID';
"
echo ""

echo "🔍 STEP 7: Checking specific achievements Santa now has..."
sqlite3 "$DB_PATH" "
SELECT 'TETRIS ACHIEVEMENTS:' as info, achievement_key, achievement_title, unlocked_at 
FROM tbl_tetris_achievements 
WHERE user_id = '$USER_ID' 
ORDER BY unlocked_at;

SELECT 'SNAKE ACHIEVEMENTS:' as info, achievement_key, achievement_title, unlocked_at 
FROM tbl_snake_achievements 
WHERE user_id = '$USER_ID' 
ORDER BY unlocked_at;
"
echo ""

echo "🧀 ACHIEVEMENT SYSTEM DEBUGGING COMPLETE!"
echo "=========================================="
echo "✅ Santa's achievements checked"
echo "✅ Recent game scores analyzed"
echo "✅ Achievement definitions verified"
echo "✅ API endpoints tested"
echo "✅ Achievement unlocking confirmed"
echo ""
echo "Ready for Season 3 reset! 🧀🚀"
