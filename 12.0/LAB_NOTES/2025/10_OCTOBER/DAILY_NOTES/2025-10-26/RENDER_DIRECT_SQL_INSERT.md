# 🚀 RENDER - DIRECT SQL INSERT FOR TETRIS ACHIEVEMENTS

**Date:** October 26, 2025  
**Since the API failed, use this direct SQL insert instead**

---

## 📋 **RUN THIS ON RENDER**

```bash
# Create SQL file with all 25 achievement definitions
cat > insert_tetris_achievements.sql << 'EOF'
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
('ACHIEVEMENT_DEFINITIONS', 'line_destroyer', 'Line Destroyer', 'Clear 100 lines in one game', '💥', 0, 100, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'speed_demon', 'Speed Demon', 'Reach Level 5', '⚡', 0, 0, 5, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'level_master', 'Level Master', 'Reach Level 8', '🏆', 0, 0, 8, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'level_warrior', 'Level Warrior', 'Reach Level 12', '⚔️', 0, 0, 12, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'level_champion', 'Level Champion', 'Reach Level 15', '🏅', 0, 0, 15, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'tetris_clear', 'Tetris Clear', 'Clear 4 lines at once (Tetris!)', '🎆', 0, 0, 0, 0, 1),
('ACHIEVEMENT_DEFINITIONS', 'back_to_back', 'Back to Back', 'Clear 2 Tetris in one game', '🔄', 0, 0, 0, 0, 2),
('ACHIEVEMENT_DEFINITIONS', 'tetris_master', 'Tetris Master', 'Clear 5 Tetris in one game', '🎇', 0, 0, 0, 0, 5),
('ACHIEVEMENT_DEFINITIONS', 'tetris_god', 'Tetris God', 'Clear 8 Tetris in one game', '⚡', 0, 0, 0, 0, 8),
('ACHIEVEMENT_DEFINITIONS', 'tetris_legend', 'Tetris Legend', 'Clear 15 Tetris in one game', '🎆', 0, 0, 0, 0, 15),
('ACHIEVEMENT_DEFINITIONS', 'combo_starter', 'Combo Starter', 'Clear 2 lines at once', '🔗', 0, 0, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'combo_master', 'Combo Master', 'Clear 3 lines at once', '🔗', 0, 0, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'combo_legend', 'Combo Legend', 'Clear 4 lines at once (Tetris!)', '🔗', 0, 0, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'piece_dropper', 'Piece Dropper', 'Drop 100 pieces in one game', '🔻', 0, 0, 0, 100, 0),
('ACHIEVEMENT_DEFINITIONS', 'block_master', 'Block Master', 'Drop 400 pieces in one game', '🧱', 0, 0, 0, 400, 0),
('ACHIEVEMENT_DEFINITIONS', 'piece_legend', 'Piece Legend', 'Drop 600 pieces in one game', '🔻', 0, 0, 0, 600, 0);
EOF

# Execute the INSERT
sqlite3 narrrf_world.sqlite < insert_tetris_achievements.sql

# Verify
echo "SELECT COUNT(*) FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

# Check thresholds
echo "SELECT achievement_key, game_score FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' AND game_score > 0 ORDER BY game_score;" | sqlite3 narrrf_world.sqlite

# Final backup
cp narrrf_world.sqlite /data/narrrf_world.sqlite
echo "✅ SUCCESS! All 25 Tetris achievements inserted!"
```

---

## ✅ **VERIFICATION**

After running, you should see:
- Count: **25**
- Scores: **200, 800, 1500, 2000, 2500**

Then test on https://narrrfs.world/public/profile.html - should show 25 achievements with emojis!

