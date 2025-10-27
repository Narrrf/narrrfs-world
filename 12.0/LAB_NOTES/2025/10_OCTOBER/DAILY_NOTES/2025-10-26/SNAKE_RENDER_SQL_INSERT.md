# 🐍 SNAKE ACHIEVEMENT DEFINITIONS - RENDER SQL INSERT

**Created:** October 26, 2025 - 23:30  
**Purpose:** Manually insert 20 Snake achievement definitions into production database  
**Reason:** Profile page shows "Error Loading Achievements" without definitions  

---

## 🚀 **RENDER SHELL COMMANDS**

### **Execute This On Render:**

```bash
cd /var/www/html/db

# Part 1: Cheese + Score achievements (10 total)
cat > insert_snake_part1.sql << 'EOF'
INSERT INTO tbl_snake_achievements (user_id, achievement_key, achievement_title, achievement_description, achievement_icon) VALUES
('ACHIEVEMENT_DEFINITIONS', 'first_cheese', 'First Cheese', 'Eat your first cheese', '🧀'),
('ACHIEVEMENT_DEFINITIONS', 'cheese_collector', 'Cheese Collector', 'Eat 5 cheeses in one game', '🧀'),
('ACHIEVEMENT_DEFINITIONS', 'cheese_hunter', 'Cheese Hunter', 'Eat 10 cheeses in one game', '🧀'),
('ACHIEVEMENT_DEFINITIONS', 'cheese_master', 'Cheese Master', 'Eat 25 cheeses in one game', '🧀'),
('ACHIEVEMENT_DEFINITIONS', 'cheese_legend', 'Cheese Legend', 'Eat 75 cheeses in one game', '🧀'),
('ACHIEVEMENT_DEFINITIONS', 'score_hunter', 'Score Hunter', 'Earn 200 DSPOINC in one game', '🎯'),
('ACHIEVEMENT_DEFINITIONS', 'point_master', 'Point Master', 'Earn 500 DSPOINC in one game', '⭐'),
('ACHIEVEMENT_DEFINITIONS', 'high_scorer', 'High Scorer', 'Earn 1,000 DSPOINC in one game', '🌟'),
('ACHIEVEMENT_DEFINITIONS', 'snake_king', 'Snake King', 'Earn 1,500 DSPOINC in one game', '👑'),
('ACHIEVEMENT_DEFINITIONS', 'score_legend', 'Score Legend', 'Earn 2,000 DSPOINC in one game', '💫');
EOF

sqlite3 narrrf_world.sqlite < insert_snake_part1.sql
echo "Part 1 complete - 10 achievements inserted"

# Part 2: Score God + Level + Length + Time achievements (10 total)
cat > insert_snake_part2.sql << 'EOF'
INSERT INTO tbl_snake_achievements (user_id, achievement_key, achievement_title, achievement_description, achievement_icon) VALUES
('ACHIEVEMENT_DEFINITIONS', 'score_god', 'Score God', 'Earn 3,500 DSPOINC (near maximum!)', '👑'),
('ACHIEVEMENT_DEFINITIONS', 'speed_demon', 'Speed Demon', 'Reach Level 5', '⚡'),
('ACHIEVEMENT_DEFINITIONS', 'level_master', 'Level Master', 'Reach Level 10', '🏆'),
('ACHIEVEMENT_DEFINITIONS', 'level_warrior', 'Level Warrior', 'Reach Level 15', '⚔️'),
('ACHIEVEMENT_DEFINITIONS', 'level_champion', 'Level Champion', 'Reach Level 20', '🏅'),
('ACHIEVEMENT_DEFINITIONS', 'long_snake', 'Long Snake', 'Grow to 10 segments', '🐍'),
('ACHIEVEMENT_DEFINITIONS', 'giant_snake', 'Giant Snake', 'Grow to 25 segments', '🐍'),
('ACHIEVEMENT_DEFINITIONS', 'mega_snake', 'Mega Snake', 'Grow to 50 segments', '🐍'),
('ACHIEVEMENT_DEFINITIONS', 'survivor', 'Survivor', 'Survive for 2 minutes', '⏰'),
('ACHIEVEMENT_DEFINITIONS', 'endurance_master', 'Endurance Master', 'Survive for 5 minutes', '⏰');
EOF

sqlite3 narrrf_world.sqlite < insert_snake_part2.sql
echo "Part 2 complete - 10 more achievements inserted"

# Verify total
echo "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

# Verify score thresholds
echo "SELECT achievement_key, achievement_description FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' AND achievement_description LIKE '%DSPOINC%' ORDER BY achievement_key;" | sqlite3 narrrf_world.sqlite

# Backup
cp narrrf_world.sqlite /data/narrrf_world.sqlite
echo "✅ All 20 Snake achievements live!"
```

---

## ✅ **EXPECTED OUTPUT:**

```
Part 1 complete - 10 achievements inserted
Part 2 complete - 10 more achievements inserted
20
high_scorer|Earn 1,000 DSPOINC in one game
point_master|Earn 500 DSPOINC in one game
score_god|Earn 3,500 DSPOINC (near maximum!)
score_hunter|Earn 200 DSPOINC in one game
score_legend|Earn 2,000 DSPOINC in one game
snake_king|Earn 1,500 DSPOINC in one game
✅ All 20 Snake achievements live!
```

---

**Run these commands on Render now to make Snake achievements visible!** 🐍✨
