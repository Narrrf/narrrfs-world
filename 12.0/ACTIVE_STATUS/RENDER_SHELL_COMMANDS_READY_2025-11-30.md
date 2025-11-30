# ⚡ RENDER SHELL COMMANDS - READY TO EXECUTE

**Date:** November 30, 2025  
**Status:** ✅ **ARCHIVE API TESTED - READY FOR EXECUTION**  
**Archive API Response:** ✅ Success (48 games archived, 37 cheese users archived)

---

## ✅ **PRE-FREEZE VERIFICATION (DO NOW - READ-ONLY)**

### **Step 1: Check Current Season 5 Status**

```bash
# Get exact end time and time remaining
echo "=== SEASON 5 STATUS & END TIME ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "
SELECT 
  season_name,
  start_date,
  end_date,
  datetime('now') as current_time,
  CASE 
    WHEN datetime(end_date) > datetime('now') 
    THEN printf('%d hours, %d minutes, %d seconds remaining', 
         (julianday(end_date) - julianday('now')) * 24,
         ((julianday(end_date) - julianday('now')) * 24 * 60) % 60,
         ((julianday(end_date) - julianday('now')) * 24 * 3600) % 60)
    ELSE 'EXPIRED'
  END as time_remaining
FROM tbl_seasons WHERE is_active = 1;"
```

**✅ Expected:** Shows Season 5, end date, and exact time remaining

---

### **Step 2: Document Current Data Counts**

```bash
# Count Season 5 data (for verification after reset)
echo "=== PRE-FREEZE DATA COUNTS ===" && \
echo "Tetris scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';" && \
echo "Total Season 5 scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');" && \
echo "" && \
echo "=== PRESERVED DATA COUNTS (MUST NOT CHANGE) ===" && \
echo "Cheese Hunt clicks:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;" && \
echo "Discord Race participants:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;" && \
echo "Tetris achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;" && \
echo "Snake achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements;" && \
echo "Space Invaders achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_achievements;"
```

**✅ Save these counts!** You'll need them to verify preserved data after reset.

---

## 🚨 **EXECUTION COMMANDS (AT FREEZE TIME ONLY!)**

### **STEP 1: CREATE BACKUP (DO FIRST!)**

```bash
# Create timestamped backup
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# Verify backup created
ls -lh /data/narrrf_world_backup_*.sqlite | tail -1

# Log backup
echo "BACKUP CREATED: $(date)" >> /data/season_reset_log.txt
```

**✅ Check:** File should be created, size should match current database

---

### **STEP 2: ARCHIVE SEASON 5 STATS (MOST CRITICAL!)**

```bash
# Archive Season 5 data (API already tested - should work!)
curl https://narrrfs.world/api/admin/archive-season-stats.php

# VERIFY ARCHIVAL WORKED (MANDATORY!)
echo "" && \
echo "=== VERIFY ARCHIVAL ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "
SELECT season, game, COUNT(*) as count 
FROM tbl_historical_stats 
WHERE season = 'Season 5' 
GROUP BY season, game;"
```

**✅ Expected Output:**
```
Season 5|tetris|[count]
Season 5|snake|[count]
Season 5|space_invaders|[count]
```

**🚨 IF THIS DOESN'T SHOW 3 ROWS - STOP! DO NOT PROCEED!**

**✅ Current Test Result:** API returned success with 48 games archived and 37 cheese users archived - **GOOD SIGN!**

---

### **STEP 3: EXECUTE SEASON RESET**

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

**✅ Check:** No error messages, transaction committed

---

### **STEP 4: VERIFY RESET SUCCESS**

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

**✅ All Checks Must Pass:**
- Season 6 active ✅
- Tetris = 0 ✅
- Snake = 0 ✅
- Space Invaders = 0 ✅
- Preserved data matches ✅

---

### **STEP 5: COPY DATABASE TO /DATA (CRITICAL!)**

```bash
# Copy to persistent storage
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Verify copy
ls -lh /data/narrrf_world.sqlite

# Log copy
echo "DATABASE COPIED TO /data: $(date)" >> /data/season_reset_log.txt
echo "✅ SEASON 5 FREEZE & SEASON 6 RESET COMPLETE!" >> /data/season_reset_log.txt
```

**✅ Check:** File copied successfully, size matches

---

## 📊 **ARCHIVE API TEST RESULT**

**API Endpoint:** `https://narrrfs.world/api/admin/archive-season-stats.php`

**Response:** ✅ **SUCCESS**
```json
{
    "success": true,
    "message": "Season stats archived successfully",
    "season_archived": "Season 5",
    "stats": {
        "games_archived": 48,
        "cheese_users_archived": 37
    }
}
```

**Status:** ✅ **API WORKING PERFECTLY!**

**Note:** This test run has already archived Season 5 data! When you execute at freeze time, it will archive again (safe operation - can run multiple times).

---

## ⏰ **TIMELINE REMINDER**

**Current Status:** ⏰ **2h 30min until freeze**

**What to Do Now:**
1. ✅ **Run Pre-Freeze Verification** (Steps 1-2 above) - Safe, read-only
2. ✅ **Save the counts** - Document for later verification
3. ✅ **Wait for freeze time** - Don't execute Steps 1-5 until season ends

**What to Do at Freeze Time:**
1. ⏳ Execute Steps 1-5 in exact order
2. ⏳ Verify each step before proceeding
3. ⏳ Document everything

---

## 🚨 **CRITICAL REMINDERS**

### **✅ Before Executing Reset:**
- ✅ Have backup ready
- ✅ Archive API tested (✅ DONE!)
- ✅ All commands ready
- ✅ Know exact freeze time

### **🚨 During Execution:**
- 🚨 **Archive BEFORE delete** - Always archive first!
- 🚨 **Backup BEFORE everything** - Always backup first!
- 🚨 **Verify each step** - Don't proceed if verification fails
- 🚨 **Copy to /data** - Always copy after reset

---

## 📝 **QUICK EXECUTION SUMMARY**

**At Freeze Time - Execute in Order:**

1. **BACKUP** → `cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite`
2. **ARCHIVE** → `curl https://narrrfs.world/api/admin/archive-season-stats.php` + **VERIFY!**
3. **RESET** → SQL transaction (delete scores, create Season 6)
4. **VERIFY** → Check Season 6 active, all games = 0
5. **COPY** → `cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite`

**Total Time: ~15 minutes**

---

## ✅ **YOU'RE READY!**

**Archive API:** ✅ **TESTED & WORKING**  
**Commands:** ✅ **READY**  
**Documentation:** ✅ **COMPLETE**

**Next Steps:**
1. Run pre-freeze verification in Render shell (Steps 1-2 above)
2. Save the counts
3. Wait for freeze time
4. Execute Steps 1-5 at exact freeze time

**🚀 Everything is prepared - you've got this! 🚀**

---

**Document Created:** November 30, 2025  
**Status:** ✅ **COMMANDS READY - ARCHIVE API TESTED SUCCESSFULLY**

