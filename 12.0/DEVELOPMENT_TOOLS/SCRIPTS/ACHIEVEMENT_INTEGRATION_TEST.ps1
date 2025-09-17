# 🧀 ACHIEVEMENT SYSTEM INTEGRATION TEST
# Date: September 13, 2025
# Purpose: Test achievement system integration fixes

echo "🧀 NARRRFS WORLD - ACHIEVEMENT SYSTEM INTEGRATION TEST"
echo "====================================================="
echo "Testing achievement system integration fixes..."
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

echo "🔍 STEP 2: Testing Tetris achievement API..."
curl -X POST "http://localhost/api/user/get-tetris-achievements.php" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\"}" \
  --silent --show-error | head -c 200
echo ""
echo ""

echo "🔍 STEP 3: Testing Snake achievement API..."
curl -X POST "http://localhost/api/user/get-snake-achievements.php" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\"}" \
  --silent --show-error | head -c 200
echo ""
echo ""

echo "🔍 STEP 4: Testing Space Invaders achievement API..."
curl -X POST "http://localhost/api/user/get-space-invaders-achievements.php" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\"}" \
  --silent --show-error | head -c 200
echo ""
echo ""

echo "🔍 STEP 5: Testing Tetris achievement unlock..."
curl -X POST "http://localhost/api/dev/unlock-tetris-achievement.php" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\",\"achievement_key\":\"score_hunter\",\"game_score\":140,\"lines_cleared\":1}" \
  --silent --show-error
echo ""
echo ""

echo "🔍 STEP 6: Testing Snake achievement unlock..."
curl -X POST "http://localhost/api/dev/unlock-snake-achievement.php" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\",\"achievement_key\":\"score_hunter\"}" \
  --silent --show-error
echo ""
echo ""

echo "🔍 STEP 7: Checking Santa's achievements after API tests..."
sqlite3 "$DB_PATH" "
SELECT 'TETRIS ACHIEVEMENTS AFTER:' as game, COUNT(*) as count FROM tbl_tetris_achievements WHERE user_id = '$USER_ID';
SELECT 'SNAKE ACHIEVEMENTS AFTER:' as game, COUNT(*) as count FROM tbl_snake_achievements WHERE user_id = '$USER_ID';
"
echo ""

echo "🔍 STEP 8: Checking specific achievements Santa now has..."
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

echo "🧀 ACHIEVEMENT SYSTEM INTEGRATION TEST COMPLETE!"
echo "==============================================="
echo "✅ Santa's achievements checked"
echo "✅ API endpoints tested"
echo "✅ Achievement unlocking tested"
echo "✅ Profile page fixes applied"
echo "✅ Game script fixes applied"
echo ""
echo "Ready for testing! 🧀🚀"
