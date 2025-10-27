# 🚀 RENDER SPACE INVADERS DEFINITIONS - PART 2

**Run these commands on Render after Part 1:**

```bash
cd /var/www/html/db

# PART 2: Boss (4) + Phoenix (4) + Egg (4) + Mini (3) = 15 achievements
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
echo "Part 2 complete - 15 achievements inserted"
```

