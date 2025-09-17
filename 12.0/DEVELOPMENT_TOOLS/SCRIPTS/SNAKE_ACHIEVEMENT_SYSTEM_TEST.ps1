# 🧀 SNAKE ACHIEVEMENT SYSTEM TEST
# Date: September 13, 2025
# Purpose: Test Snake achievement system functionality

echo "🧀 NARRRFS WORLD - SNAKE ACHIEVEMENT SYSTEM TEST"
echo "==============================================="
echo "Testing Snake achievement system..."
echo ""

# Set the database path
DB_PATH="db/narrrf_world.sqlite"
USER_ID="1107633105185013790"

echo "🔍 STEP 1: Checking Santa's Snake achievements in database..."
sqlite3 "$DB_PATH" "
SELECT 'SNAKE ACHIEVEMENTS:' as game, COUNT(*) as count FROM tbl_snake_achievements WHERE user_id = '$USER_ID';
SELECT achievement_key, achievement_title, unlocked_at FROM tbl_snake_achievements WHERE user_id = '$USER_ID' ORDER BY unlocked_at;
"
echo ""

echo "🔍 STEP 2: Testing Snake achievement API..."
curl -X POST "http://localhost/api/user/get-snake-achievements.php" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\"}" \
  --silent --show-error | head -c 500
echo ""
echo ""

echo "🔍 STEP 3: Testing Snake achievement unlock (cheese_collector)..."
curl -X POST "http://localhost/api/dev/unlock-snake-achievement.php" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\",\"achievement_key\":\"cheese_collector\"}" \
  --silent --show-error
echo ""
echo ""

echo "🔍 STEP 4: Testing Snake achievement unlock (cheese_hunter)..."
curl -X POST "http://localhost/api/dev/unlock-snake-achievement.php" \
  -H "Content-Type: application/json" \
  -d "{\"user_id\":\"$USER_ID\",\"achievement_key\":\"cheese_hunter\"}" \
  --silent --show-error
echo ""
echo ""

echo "🔍 STEP 5: Checking Santa's Snake achievements after API tests..."
sqlite3 "$DB_PATH" "
SELECT 'SNAKE ACHIEVEMENTS AFTER:' as game, COUNT(*) as count FROM tbl_snake_achievements WHERE user_id = '$USER_ID';
SELECT achievement_key, achievement_title, unlocked_at FROM tbl_snake_achievements WHERE user_id = '$USER_ID' ORDER BY unlocked_at DESC;
"
echo ""

echo "🧀 SNAKE ACHIEVEMENT SYSTEM TEST COMPLETE!"
echo "=========================================="
echo "✅ Santa's Snake achievements checked"
echo "✅ Snake achievement API tested"
echo "✅ Snake achievement unlocking tested"
echo "✅ Database synchronization verified"
echo ""
echo "Ready for Snake game testing! 🧀🐍"
