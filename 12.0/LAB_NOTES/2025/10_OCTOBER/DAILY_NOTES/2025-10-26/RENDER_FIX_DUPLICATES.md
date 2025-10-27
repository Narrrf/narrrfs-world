# 🚨 RENDER FIX DUPLICATES - SPACE INVADERS

**Problem:** 41 definitions instead of 28 (duplicates exist)

**Solution:** Delete all and re-insert

```bash
cd /var/www/html/db

# Delete ALL Space Invaders achievements (definitions + user unlocks)
echo "DELETE FROM tbl_space_invaders_achievements;" | sqlite3 narrrf_world.sqlite

# Verify deletion
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements;" | sqlite3 narrrf_world.sqlite
# Expected: 0

# Re-insert Part 1 (13 achievements)
cat > insert_space_part1.sql << 'EOF'
INSERT INTO tbl_space_invaders_achievements (user_id, achievement_key, achievement_title, achievement_description, achievement_icon) VALUES
('ACHIEVEMENT_DEFINITIONS', 'firstKill', 'First Blood', 'Destroyed your first 100 invaders!', '🎯'),
('ACHIEVEMENT_DEFINITIONS', 'killStreak8', 'Killing Spree', '25 kills in a row!', '🔥'),
('ACHIEVEMENT_DEFINITIONS', 'killStreak15', 'Rampage', '50 kills in a row!', '⚡'),
('ACHIEVEMENT_DEFINITIONS', 'killStreak25', 'Unstoppable', '100 kills in a row!', '💀'),
('ACHIEVEMENT_DEFINITIONS', 'score2500', 'Getting Started', 'Reached 1,000 DSPOINC!', '⭐'),
('ACHIEVEMENT_DEFINITIONS', 'score7500', 'Rising Star', 'Reached 5,000 DSPOINC!', '🌟'),
('ACHIEVEMENT_DEFINITIONS', 'score15000', 'Space Ace', 'Reached 10,000 DSPOINC!', '🚀'),
('ACHIEVEMENT_DEFINITIONS', 'score30000', 'Legend', 'Reached 20,000 DSPOINC - Maximum Score!', '👑'),
('ACHIEVEMENT_DEFINITIONS', 'perfectWave', 'Perfect Wave', 'Cleared 5 waves without taking damage!', '✨'),
('ACHIEVEMENT_DEFINITIONS', 'noHitRun60', 'Untouchable', '5 minutes without taking damage!', '🛡️'),
('ACHIEVEMENT_DEFINITIONS', 'comboMaster8', 'Combo Master', 'Achieved 4x score multiplier!', '💥'),
('ACHIEVEMENT_DEFINITIONS', 'speedDemon20k', 'Speed Demon', 'Reached 5,000 DSPOINC in under 3 minutes!', '⚡'),
('ACHIEVEMENT_DEFINITIONS', 'survivor10min', 'Ultimate Survivor', 'Survived for 20 minutes!', '⏰');
EOF

sqlite3 narrrf_world.sqlite < insert_space_part1.sql
echo "Part 1 done: 13 achievements"

# Re-insert Part 2 (15 achievements)
cat > insert_space_part2.sql << 'EOF'
INSERT INTO tbl_space_invaders_achievements (user_id, achievement_key, achievement_title, achievement_description, achievement_icon) VALUES
('ACHIEVEMENT_DEFINITIONS', 'bossKiller1', 'Boss Novice', 'Defeated Cheese King - First Victory!', '🎯'),
('ACHIEVEMENT_DEFINITIONS', 'bossKiller2', 'Boss Veteran', 'Defeated Cheese Emperor - Rising Power!', '🏆'),
('ACHIEVEMENT_DEFINITIONS', 'bossKiller3', 'Boss Slayer', 'Defeated Cheese God - Master Warrior!', '🗡️'),
('ACHIEVEMENT_DEFINITIONS', 'bossKiller4', 'Boss Destroyer', 'Defeated Cheese Destroyer - Ultimate!', '💀'),
('ACHIEVEMENT_DEFINITIONS', 'phoenixHunter', 'Phoenix Hunter', 'Destroyed 10 Phoenix birds!', '🔥'),
('ACHIEVEMENT_DEFINITIONS', 'phoenixSlayer', 'Phoenix Slayer', 'Destroyed 25 Phoenix birds!', '⚡'),
('ACHIEVEMENT_DEFINITIONS', 'phoenixDestroyer', 'Phoenix Destroyer', 'Destroyed 50 Phoenix birds!', '💥'),
('ACHIEVEMENT_DEFINITIONS', 'phoenixMaster', 'Phoenix Master', 'Destroyed 100 Phoenix birds - Ultimate!', '👑'),
('ACHIEVEMENT_DEFINITIONS', 'eggHunter', 'Egg Hunter', 'Destroyed 50 Phoenix eggs!', '🥚'),
('ACHIEVEMENT_DEFINITIONS', 'eggSlayer', 'Egg Slayer', 'Destroyed 100 Phoenix eggs!', '💣'),
('ACHIEVEMENT_DEFINITIONS', 'eggDestroyer', 'Egg Destroyer', 'Destroyed 150 Phoenix eggs!', '💥'),
('ACHIEVEMENT_DEFINITIONS', 'eggMaster', 'Egg Master', 'Destroyed 250 Phoenix eggs - Ultimate!', '👑'),
('ACHIEVEMENT_DEFINITIONS', 'miniPhoenixHunter', 'Mini-Phoenix Hunter', 'Destroyed 25 Mini-Phoenix!', '🐣'),
('ACHIEVEMENT_DEFINITIONS', 'miniPhoenixSlayer', 'Mini-Phoenix Slayer', 'Destroyed 50 Mini-Phoenix!', '⚡'),
('ACHIEVEMENT_DEFINITIONS', 'miniPhoenixMaster', 'Mini-Phoenix Master', 'Destroyed 75 Mini-Phoenix - Ultimate!', '👑');
EOF

sqlite3 narrrf_world.sqlite < insert_space_part2.sql
echo "Part 2 done: 15 achievements"

# Final verification
echo ""
echo "FINAL COUNT (should be 28):"
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

echo ""
echo "Check for duplicates (should show each key once):"
echo "SELECT achievement_key, COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' GROUP BY achievement_key HAVING COUNT(*) > 1;" | sqlite3 narrrf_world.sqlite

# Copy to /data
cp narrrf_world.sqlite /data/narrrf_world.sqlite

echo ""
echo "✅ SUCCESS! Space Invaders achievements fixed!"
```

