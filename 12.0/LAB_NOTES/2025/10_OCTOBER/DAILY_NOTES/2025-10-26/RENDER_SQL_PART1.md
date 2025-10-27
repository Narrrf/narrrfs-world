# 🚀 RENDER SQL - PART 1 OF 3

**Run these commands on Render:**

```bash
# Part 1: Score + Line achievements (10 total)
cat > insert_part1.sql << 'EOF'
INSERT INTO tbl_tetris_achievements (user_id, achievement_key, achievement_title, achievement_description, achievement_icon, game_score, lines_cleared, level_reached, pieces_dropped, tetris_clears) VALUES
('ACHIEVEMENT_DEFINITIONS', 'score_hunter', 'Score Hunter', 'Earn 200 DSPOINC in one game', '🎯', 200, 0, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'high_roller', 'High Roller', 'Earn 800 DSPOINC in one game', '💰', 800, 0, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'point_master', 'Point Master', 'Earn 1,500 DSPOINC in one game', '💎', 1500, 0, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'score_legend', 'Score Legend', 'Earn 2,000 DSPOINC in one game', '💫', 2000, 0, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'tetris_king', 'Tetris King', 'Earn 2,500 DSPOINC (maximum score!)', '👑', 2500, 0, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'first_line', 'First Line', 'Clear your first line', '🎯', 0, 1, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'line_master', 'Line Master', 'Clear 10 lines in one game', '⭐', 0, 10, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'tetris_pro', 'Tetris Pro', 'Clear 30 lines in one game', '✨', 0, 30, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'line_legend', 'Line Legend', 'Clear 50 lines in one game', '🌟', 0, 50, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'line_destroyer', 'Line Destroyer', 'Clear 100 lines in one game', '💥', 0, 100, 0, 0, 0);
EOF

sqlite3 narrrf_world.sqlite < insert_part1.sql
echo "Part 1 complete - 10 achievements inserted"
```

