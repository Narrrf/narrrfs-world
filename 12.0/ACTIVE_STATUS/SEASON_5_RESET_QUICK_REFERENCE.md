# ⚡ SEASON 5 RESET - QUICK REFERENCE

**Date:** November 30, 2025  
**Urgency:** 🚨 **SEASON 5 ENDING IN <3 HOURS**  
**Purpose:** Quick copy/paste commands for Season 5 freeze & reset

---

## 🚨 **CRITICAL ORDER - EXECUTE IN EXACT SEQUENCE**

---

## **STEP 1: VERIFY CURRENT STATUS**

```bash
# Connect to Render shell, then run:
echo "=== SEASON 5 STATUS ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name, end_date, datetime('now') as current_time FROM tbl_seasons WHERE is_active = 1;"
```

---

## **STEP 2: BACKUP DATABASE (DO FIRST!)**

```bash
# Create backup BEFORE any operations
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite && \
echo "✅ Backup created: $(ls -lh /data/narrrf_world_backup_*.sqlite | tail -1)"
```

---

## **STEP 3: ARCHIVE SEASON 5 STATS (CRITICAL!)**

```bash
# Archive Season 5 data BEFORE deletion
curl https://narrrfs.world/api/admin/archive-season-stats.php && \
echo "" && \
echo "=== VERIFY ARCHIVAL ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season, game, COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 5' GROUP BY season, game;"
```

**Expected:** Should show Season 5 data for tetris, snake, space_invaders

**🚨 IF ARCHIVAL FAILS - STOP! DO NOT PROCEED!**

---

## **STEP 4: EXECUTE SEASON RESET**

```bash
# Execute reset (only after archival succeeds!)
sqlite3 /var/www/html/db/narrrf_world.sqlite "
BEGIN TRANSACTION;
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1);
COMMIT;
" && echo "✅ Season 6 reset complete!"
```

---

## **STEP 5: VERIFY RESET**

```bash
# Verify Season 6 is active and all games reset
echo "=== VERIFICATION ===" && \
echo "Season:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;" && \
echo "Tetris (should be 0):" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake (should be 0):" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders (should be 0):" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
```

---

## **STEP 6: COPY TO /DATA (CRITICAL!)**

```bash
# Copy database to persistent storage
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite && \
echo "✅ Database copied to /data"
```

---

## 📝 **COMPLETE ONE-LINER (FOR EXPERIENCED USERS ONLY)**

**⚠️ Only use after completing Steps 1-3 (Backup + Archive)!**

```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "BEGIN TRANSACTION; DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders'); DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders'); UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1; INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1); COMMIT;" && cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite && echo "✅ Season 6 reset complete!"
```

---

**Full Plan:** See `SEASON_5_FREEZE_AND_RESET_PLAN_2025-11-30.md` for complete details

