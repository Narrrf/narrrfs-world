# ⚡ LIVE EXECUTION - FINAL COMMANDS

**Date:** November 30, 2025  
**Time:** [FREEZE TIME - EXECUTE NOW]  
**Status:** ✅ **LOCAL TEST PASSED - READY FOR LIVE EXECUTION**

---

## ✅ **LOCAL TEST VERIFICATION**

**Test Results:**
- ✅ All SQL commands worked perfectly
- ✅ Reset logic validated
- ✅ Data preservation verified
- ✅ Backup/restore confirmed working
- ✅ **READY FOR LIVE EXECUTION!**

---

## 🚨 **LIVE EXECUTION COMMANDS - EXECUTE IN ORDER**

**Connect to Render Shell first, then execute:**

---

### **STEP 1: CREATE BACKUP (DO FIRST!)**

```bash
# Create timestamped backup
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# Verify backup created
ls -lh /data/narrrf_world_backup_*.sqlite | tail -1

# Log backup
echo "BACKUP CREATED: $(date)" >> /data/season_reset_log.txt
```

**✅ Check:** File created, size matches database

---

### **STEP 2: ARCHIVE SEASON 5 STATS (MOST CRITICAL!)**

```bash
# Archive Season 5 data
curl https://narrrfs.world/api/admin/archive-season-stats.php

# VERIFY ARCHIVAL WORKED (MANDATORY!)
echo "" && \
echo "=== VERIFY ARCHIVAL ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season, game, COUNT(*) as count FROM tbl_historical_stats WHERE season = 'Season 5' GROUP BY season, game;"
```

**✅ Expected Output:**
```
Season 5|tetris|[count]
Season 5|snake|[count]
Season 5|space_invaders|[count]
```

**🚨 IF THIS DOESN'T SHOW 3 ROWS - STOP! DO NOT PROCEED!**

---

### **STEP 3: EXECUTE SEASON RESET**

```bash
# Execute reset in single transaction
sqlite3 /var/www/html/db/narrrf_world.sqlite "
BEGIN TRANSACTION;
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;
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
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name, start_date, end_date FROM tbl_seasons WHERE is_active = 1;"

# Verify all games reset (MUST ALL BE 0!)
echo "" && \
echo "=== RESET VERIFICATION (MUST ALL BE 0) ===" && \
echo "Tetris:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"

# Verify preserved data
echo "" && \
echo "=== PRESERVED DATA VERIFICATION ===" && \
echo "Cheese Hunt:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;" && \
echo "Discord Race:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"
```

**✅ All Checks Must Pass:**
- Season 6 active ✅
- Tetris = 0 ✅
- Snake = 0 ✅
- Space Invaders = 0 ✅
- Preserved data intact ✅

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

**✅ Check:** File copied successfully

---

## 📋 **EXECUTION CHECKLIST**

### **Before Starting:**
- [ ] Connected to Render shell
- [ ] All commands ready to copy/paste
- [ ] Timer set for exact freeze time
- [ ] Ready to execute immediately

### **During Execution:**
- [ ] Step 1: Backup created ✅
- [ ] Step 2: Archive executed + Verified ✅
- [ ] Step 3: Reset executed ✅
- [ ] Step 4: All verifications passed ✅
- [ ] Step 5: Database copied to /data ✅

### **After Execution:**
- [ ] Final verification complete
- [ ] Discord announcement posted
- [ ] Lab note created
- [ ] All documentation updated

---

## ⏱️ **ESTIMATED TIME: ~15 MINUTES**

**Breakdown:**
- Step 1 (Backup): ~2 minutes
- Step 2 (Archive): ~3 minutes
- Step 3 (Reset): ~2 minutes
- Step 4 (Verify): ~3 minutes
- Step 5 (Copy): ~1 minute
- **Buffer:** ~4 minutes
- **Total:** ~15 minutes

---

## ✅ **SUCCESS CRITERIA**

- ✅ Backup created
- ✅ Stats archived (verified)
- ✅ Season 6 active
- ✅ All games reset to 0
- ✅ Preserved data intact
- ✅ Database copied to /data
- ✅ Zero data loss

---

## 🚨 **CRITICAL REMINDERS**

### **✅ ALWAYS DO:**
- ✅ Backup BEFORE everything
- ✅ Archive BEFORE delete
- ✅ Verify each step before proceeding
- ✅ Copy to /data after reset

### **❌ NEVER DO:**
- ❌ Skip backup
- ❌ Skip archival verification
- ❌ Proceed if verification fails
- ❌ Forget /data copy

---

## 🚀 **YOU'RE READY!**

**Status:** ✅ **LOCAL TEST PASSED - READY FOR LIVE**

**What You Have:**
- ✅ All commands tested locally
- ✅ Process validated
- ✅ No errors encountered
- ✅ Ready to execute

**Time Remaining:** ~30 minutes until freeze

**🎯 EXECUTE AT FREEZE TIME - YOU'VE GOT THIS! 🎯**

---

**Document Created:** November 30, 2025  
**Status:** ✅ **READY FOR LIVE EXECUTION**  
**Local Test:** ✅ **ALL TESTS PASSED**

