# 🚀 RENDER SQL - PART 3 OF 3

**Run these commands on Render:**

```bash
# Part 3: Combo + Piece achievements (6 total)
cat > insert_part3.sql << 'EOF'
INSERT INTO tbl_tetris_achievements (user_id, achievement_key, achievement_title, achievement_description, achievement_icon, game_score, lines_cleared, level_reached, pieces_dropped, tetris_clears) VALUES
('ACHIEVEMENT_DEFINITIONS', 'combo_starter', 'Combo Starter', 'Clear 2 lines at once', '🔗', 0, 0, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'combo_master', 'Combo Master', 'Clear 3 lines at once', '🔗', 0, 0, 0, 0, 0),
('ACHIEVEMENT_ગ', 'combo_legend', 'Combo Legend', 'Clear 4 lines at once (Tetris!)', '🔗', 0, 0, 0, 0, 0),
('ACHIEVEMENT_DEFINITIONS', 'piece_dropper', 'Piece Dropper', 'Drop 100 pieces in one game', '🔻', 0, 0, 0, 100, 0),
('ACHIEVEMENT_DEFINITIONS', 'block_master', 'Block Master', 'Drop 400 pieces in one game', '🧱', 0, 0, 0, 400, 0),
('ACHIEVEMENT_DEFINITIONS', 'piece_legend', 'Piece Legend', 'Drop 600 pieces in one game', '🔻', 0, 0, 0, 600, 0);
EOF

sqlite3 narrrf_world.sqlite < insert_part3.sql
echo "Part 3 complete - 6 more achievements inserted"

# FINAL VERIFICATION
echo "SELECT COUNT(*) FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite
echo "Expected: 25"

# Check score thresholds
echo "SELECT achievement_key, game_score FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' AND game_score > 0 ORDER BY game_score;" | sqlite3 narrrf_world.sqlite

# Final backup
cp narrrf_world.sqlite /data/narrrf_world.sqlite
echo "✅ SUCCESS! All 25 Tetris achievements live on production!"
```

---

## ✅ **FINAL CHECK**

After all 3 parts run, you should see:
- **Total count:** 25
- **Score thresholds:** score_hunter 200, high_roller 800, point_master 1500, score_legend 2000, tetris_king 2500

**Then visit https://narrrfs.world/public/profile.html and hard refresh (Ctrl+F5) to see the new achievements!** 🎉

