# 🚀 RENDER SQL - PART 2 OF 3

**Run these commands on Render:**

```bash
# Part 2: Level + Tetris Clear achievements (9 total)
cat > insert_part2.sql << 'EOF'
INSERT INTO tbl_tetris_achievements (user_id, achievement_key, achievement_title, achievement_description, achievement_icon, game_score, lines_cleared, level_reached, pieces_dropped, tetris_clears) VALUES
('ACHIEVEMENT_DEFINITIONS', 'speed_demon', 'Speed Demon', 'Reach Level 5', '⚡', 0, 0, 5, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'level_master', 'Level Master', 'Reach Level 8', '🏆', 0, 0, 8, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'level_warrior', 'Level Warrior', 'Reach Level 12', '⚔️', 0, 0, 12, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'level_champion', 'Level Champion', 'Reach Level 15', '🏅', 0, 0, 15, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'tetris_clear', 'Tetris Clear', 'Clear 4 lines at once (Tetris!)', '🎆', 0, 0, 0, 0, 1),
('ACHIEVEMENT_DEFINITIONS', 'back_to_back', 'Back to Back', 'Clear 2 Tetris in one game', '🔄', 0, 0, 0, 0, 2),
('ACHIEVEMENT_DEFINITIONS', 'tetris_master', 'Tetris Master', 'Clear 5 Tetris in one game', '🎇', 0, 0, 0, 0, 5),
('ACHIEVEMENT_DEFINITIONS', 'tetris_god', 'Tetris God', 'Clear 8 Tetris in one game', '⚡', 0, 0, 0, 0, 8),
('ACHIEVEMENT_DEFINITIONS', 'tetris_legend', 'Tetris Legend', 'Clear 15 Tetris in one game', '🎆', 0, 0, 0, 0, 15);
EOF

sqlite3 narrrf_world.sqlite < insert_part2.sql
echo "Part 2 complete - 9 more achievements inserted"
```

