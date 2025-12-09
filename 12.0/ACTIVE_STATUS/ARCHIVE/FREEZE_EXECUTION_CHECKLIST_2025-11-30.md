# ⚡ SEASON 5 FREEZE - EXECUTION CHECKLIST

**Date:** November 30, 2025  
**Time:** [EXACT FREEZE TIME]  
**Status:** 🚨 **EXECUTE AT FREEZE TIME ONLY**

---

## 🚨 **CRITICAL EXECUTION ORDER - FOLLOW EXACTLY**

**⏰ Execute these steps AT THE EXACT TIME Season 5 ends!**

---

## ✅ **STEP 1: CREATE BACKUP (DO FIRST!)**

**Time:** [START TIME]  
**Duration:** ~2 minutes

```bash
# Connect to Render shell, then execute:
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# Verify backup created
ls -lh /data/narrrf_world_backup_*.sqlite | tail -1

# Log backup
echo "BACKUP CREATED: $(date)" >> /data/season_reset_log.txt
```

**✅ Verification:**
- [ ] Backup file created
- [ ] File size matches current database
- [ ] Logged in season_reset_log.txt

---

## ✅ **STEP 2: ARCHIVE SEASON 5 STATS (CRITICAL!)**

**Time:** [START TIME + 2 minutes]  
**Duration:** ~3 minutes

```bash
# Archive Season 5 data
curl https://narrrfs.world/api/admin/archive-season-stats.php

# Verify archival worked (MANDATORY!)
echo "=== VERIFY ARCHIVAL ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "
SELECT season, game, COUNT(*) as count 
FROM tbl_historical_stats 
WHERE season = 'Season 5' 
GROUP BY season, game;"

# Expected output:
# Season 5|tetris|[count]
# Season 5|snake|[count]
# Season 5|space_invaders|[count]
```

**✅ Verification:**
- [ ] API returned success
- [ ] Historical stats show Season 5 data
- [ ] All 3 games archived
- [ ] Counts match pre-freeze counts

**🚨 IF ARCHIVAL FAILS - STOP! DO NOT PROCEED!**

---

## ✅ **STEP 3: EXECUTE SEASON RESET**

**Time:** [START TIME + 5 minutes]  
**Duration:** ~2 minutes

```bash
# Execute reset in single transaction
sqlite3 /var/www/html/db/narrrf_world.sqlite "
BEGIN TRANSACTION;

-- Reset 3 main games only
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');

-- Deactivate Season 5
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;

-- Create Season 6
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1);

COMMIT;
"

# Log reset
echo "SEASON RESET EXECUTED: $(date)" >> /data/season_reset_log.txt
```

**✅ Verification:**
- [ ] No error messages
- [ ] Transaction committed
- [ ] Logged in season_reset_log.txt

---

## ✅ **STEP 4: VERIFY RESET SUCCESS**

**Time:** [START TIME + 7 minutes]  
**Duration:** ~3 minutes

```bash
# Verify Season 6 is active
echo "=== SEASON 6 STATUS ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"

# Verify all games reset (MUST ALL BE 0!)
echo "" && \
echo "=== RESET VERIFICATION (MUST ALL BE 0) ===" && \
echo "Tetris:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"

# Verify preserved data (should match pre-freeze counts)
echo "" && \
echo "=== PRESERVED DATA (SHOULD MATCH PRE-FREEZE) ===" && \
echo "Cheese Hunt:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;" && \
echo "Discord Race:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"
```

**✅ Verification:**
- [ ] Season 6 active (is_active = 1)
- [ ] Tetris scores = 0
- [ ] Snake scores = 0
- [ ] Space Invaders scores = 0
- [ ] Preserved data matches pre-freeze counts

---

## ✅ **STEP 5: COPY DATABASE TO /DATA (CRITICAL!)**

**Time:** [START TIME + 10 minutes]  
**Duration:** ~1 minute

```bash
# Copy to persistent storage
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Verify copy
ls -lh /data/narrrf_world.sqlite

# Log copy
echo "DATABASE COPIED TO /data: $(date)" >> /data/season_reset_log.txt
echo "✅ SEASON 5 FREEZE & SEASON 6 RESET COMPLETE!" >> /data/season_reset_log.txt
```

**✅ Verification:**
- [ ] Database copied successfully
- [ ] File size matches
- [ ] Logged in season_reset_log.txt

---

## ✅ **STEP 6: FINAL VERIFICATION**

**Time:** [START TIME + 11 minutes]  
**Duration:** ~2 minutes

```bash
# Final comprehensive check
echo "=== FINAL VERIFICATION ===" && \
echo "Active Season:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;" && \
echo "" && \
echo "Game Scores (should all be 0):" && \
echo "Tetris: $(sqlite3 /var/www/html/db/narrrf_world.sqlite 'SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = \"tetris\";')" && \
echo "Snake: $(sqlite3 /var/www/html/db/narrrf_world.sqlite 'SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = \"snake\";')" && \
echo "Space Invaders: $(sqlite3 /var/www/html/db/narrrf_world.sqlite 'SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = \"space_invaders\";')" && \
echo "" && \
echo "Historical Stats Archived:" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 5';"
```

**✅ Final Checklist:**
- [ ] Season 6 active
- [ ] All games reset to 0
- [ ] Historical stats archived
- [ ] Preserved data intact
- [ ] Database copied to /data
- [ ] Everything logged

---

## ⏱️ **ESTIMATED TOTAL TIME: ~15 minutes**

**Breakdown:**
- Step 1 (Backup): ~2 minutes
- Step 2 (Archive): ~3 minutes
- Step 3 (Reset): ~2 minutes
- Step 4 (Verify): ~3 minutes
- Step 5 (Copy): ~1 minute
- Step 6 (Final): ~2 minutes
- **Buffer:** ~2 minutes
- **Total:** ~15 minutes

---

## 🚨 **EMERGENCY PROCEDURES**

### **If Archival Fails:**
1. **STOP IMMEDIATELY**
2. Check API endpoint: `curl https://narrrfs.world/api/admin/archive-season-stats.php`
3. Check error logs
4. **DO NOT PROCEED** with reset until archival works
5. Contact team if needed

### **If Reset Fails:**
1. **STOP IMMEDIATELY**
2. Check database status: `sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons;"`
3. Use backup to restore if needed
4. Document error
5. Fix issue before retrying

### **If Verification Fails:**
1. **DO NOT COPY TO /data** yet
2. Check what failed
3. Review logs
4. Fix issue
5. Re-verify before proceeding

---

## 📝 **EXECUTION LOG TEMPLATE**

```
=== SEASON 5 FREEZE EXECUTION LOG ===
Date: [DATE]
Start Time: [TIME]
End Time: [TIME]

Step 1 - Backup:
  Time: [TIME]
  Status: [SUCCESS/FAILURE]
  Notes: [ANY NOTES]

Step 2 - Archive:
  Time: [TIME]
  Status: [SUCCESS/FAILURE]
  Notes: [ANY NOTES]
  Archived Counts: [COUNTS]

Step 3 - Reset:
  Time: [TIME]
  Status: [SUCCESS/FAILURE]
  Notes: [ANY NOTES]

Step 4 - Verify:
  Time: [TIME]
  Status: [SUCCESS/FAILURE]
  Notes: [ANY NOTES]
  Reset Counts: [ALL SHOULD BE 0]

Step 5 - Copy:
  Time: [TIME]
  Status: [SUCCESS/FAILURE]
  Notes: [ANY NOTES]

Step 6 - Final:
  Time: [TIME]
  Status: [SUCCESS/FAILURE]
  Notes: [ANY NOTES]

Issues Encountered: [LIST ANY ISSUES]
Resolution: [HOW RESOLVED]
Final Status: [SUCCESS/FAILURE]
```

---

## ✅ **READY TO EXECUTE!**

**Status:** ⏰ **WAITING FOR FREEZE TIME**

**When freeze time arrives:**
1. Follow steps 1-6 in exact order
2. Check ✅ after each verification
3. Log everything
4. Take your time - accuracy over speed

**🚀 GOOD LUCK! 🚀**

---

**Document Created:** November 30, 2025  
**Purpose:** Step-by-step execution guide for Season 5 freeze  
**Status:** ✅ **READY TO USE AT FREEZE TIME**

