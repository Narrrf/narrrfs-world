# ✅ VERIFICATION COMMANDS - STEP 4 & 5

**Status:** Steps 1-3 Complete ✅  
**Next:** Verify reset + Copy to /data

---

## ✅ **STEPS 1-3 COMPLETED:**

- ✅ **Backup created**
- ✅ **Archive executed:** 48 games, 37 cheese users archived
- ✅ **Reset executed:** SQL transaction completed

---

## 📋 **STEP 4: VERIFY RESET SUCCESS**

Execute these commands in Render shell:

```bash
# Verify Season 6 is active
echo "=== SEASON 6 STATUS ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name, start_date, end_date FROM tbl_seasons WHERE is_active = 1;"

# Verify all games reset (MUST ALL BE 0!)
echo "" && \
echo "=== RESET VERIFICATION (MUST ALL BE 0) ===" && \
echo "Tetris:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"

# Verify preserved data (should match original counts)
echo "" && \
echo "=== PRESERVED DATA VERIFICATION ===" && \
echo "Cheese Hunt:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;" && \
echo "Discord Race:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"

# Verify archival worked
echo "" && \
echo "=== ARCHIVAL VERIFICATION ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season, game, COUNT(*) as count FROM tbl_historical_stats WHERE season = 'Season 5' GROUP BY season, game;"
```

**✅ Expected Results:**
- Season 6 active with start_date = current time
- Tetris = 0
- Snake = 0
- Space Invaders = 0
- Cheese Hunt = [original count]
- Discord Race = [original count]
- Historical stats show 3 rows (tetris, snake, space_invaders)

---

## 📋 **STEP 5: COPY DATABASE TO /DATA**

Once verification passes, execute:

```bash
# Copy to persistent storage
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Verify copy
ls -lh /data/narrrf_world.sqlite

# Log completion
echo "DATABASE COPIED TO /data: $(date)" >> /data/season_reset_log.txt
echo "✅ SEASON 5 FREEZE & SEASON 6 RESET COMPLETE!" >> /data/season_reset_log.txt
```

**✅ Check:** File copied successfully, size matches

---

## 🎯 **AFTER STEP 5:**

- ✅ Season 6 is live!
- ✅ All data preserved
- ✅ Ready for new season

**Next Actions:**
1. Post Discord announcement
2. Create lab note documenting execution
3. Update status files

---

**Status:** Steps 1-3 Complete ✅  
**Next:** Verification + Copy to /data

