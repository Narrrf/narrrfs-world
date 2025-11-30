# 🧪 LOCAL TESTING GUIDE - SEASON 5 FREEZE

**Date:** November 30, 2025  
**Time Remaining:** 30 minutes until freeze  
**Purpose:** Test all steps locally first, then repeat on live  
**Database Location:** `C:\xampp-server\htdocs\narrrfs-world\db\narrrf_world.sqlite`

---

## 🎯 **LOCAL TESTING PROCEDURE**

### **Step 1: Verify Local Database**

**Execute in PowerShell:**
```powershell
# Navigate to project directory
cd C:\xampp-server\htdocs\narrrfs-world

# Verify database exists
Test-Path db\narrrf_world.sqlite

# Check database size
(Get-Item db\narrrf_world.sqlite).Length / 1MB

# Verify Season 5 is active
sqlite3 db\narrrf_world.sqlite "SELECT season_name, end_date, datetime('now') as current_time FROM tbl_seasons WHERE is_active = 1;"
```

**✅ Expected:** Database exists, shows Season 5 active

---

### **Step 2: Document Pre-Test Data Counts**

**Execute in PowerShell:**
```powershell
# Count Season 5 data
Write-Host "=== PRE-TEST DATA COUNTS ===" -ForegroundColor Cyan
Write-Host "Tetris scores:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"
Write-Host "Snake scores:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"
Write-Host "Space Invaders scores:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
Write-Host "Total Season 5 scores:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');"

Write-Host "`n=== PRESERVED DATA COUNTS ===" -ForegroundColor Cyan
Write-Host "Cheese Hunt clicks:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;"
Write-Host "Discord Race participants:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"
Write-Host "Tetris achievements:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;"
Write-Host "Snake achievements:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements;"
Write-Host "Space Invaders achievements:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_achievements;"
```

**✅ Save these counts!** You'll verify them after the test.

---

### **Step 3: Create Local Backup**

**Execute in PowerShell:**
```powershell
# Create backup with timestamp
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupPath = "db\narrrf_world_backup_$timestamp.sqlite"
Copy-Item db\narrrf_world.sqlite $backupPath

# Verify backup
Write-Host "✅ Backup created: $backupPath" -ForegroundColor Green
Write-Host "Backup size: $((Get-Item $backupPath).Length / 1MB) MB" -ForegroundColor Green
```

**✅ Expected:** Backup file created, size matches original

---

### **Step 4: Test Archive API (Local - Skip or Test Connection)**

**Note:** The archive API runs on the live server, so we can't fully test it locally. However, we can verify the endpoint is accessible:

```powershell
# Test API endpoint (won't actually archive local DB, but tests connection)
curl https://narrrfs.world/api/admin/archive-season-stats.php
```

**✅ Expected:** Returns JSON response (we already know this works from earlier test)

**Note:** For local testing, we'll skip the actual archival step since it requires the live database. We'll verify the archival logic works when we do it live.

---

### **Step 5: Execute Season Reset (LOCAL TEST)**

**Execute in PowerShell:**
```powershell
# Execute reset in single transaction
sqlite3 db\narrrf_world.sqlite "
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

Write-Host "✅ Season reset executed locally!" -ForegroundColor Green
```

**✅ Expected:** No error messages, transaction committed

---

### **Step 6: Verify Reset (LOCAL TEST)**

**Execute in PowerShell:**
```powershell
# Verify Season 6 is active
Write-Host "=== SEASON 6 STATUS ===" -ForegroundColor Cyan
sqlite3 db\narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"

# Verify all games reset (MUST ALL BE 0!)
Write-Host "`n=== RESET VERIFICATION (MUST ALL BE 0) ===" -ForegroundColor Cyan
Write-Host "Tetris:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"
Write-Host "Snake:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"
Write-Host "Space Invaders:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"

# Verify preserved data (should match pre-test counts)
Write-Host "`n=== PRESERVED DATA (SHOULD MATCH PRE-TEST) ===" -ForegroundColor Cyan
Write-Host "Cheese Hunt:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;"
Write-Host "Discord Race:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"
Write-Host "Tetris achievements:" -ForegroundColor Yellow
sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;"
```

**✅ All Checks Must Pass:**
- Season 6 active ✅
- Tetris = 0 ✅
- Snake = 0 ✅
- Space Invaders = 0 ✅
- Preserved data matches pre-test counts ✅

---

### **Step 7: Restore from Backup (After Testing)**

**Execute in PowerShell:**
```powershell
# Restore original database from backup
Copy-Item $backupPath db\narrrf_world.sqlite -Force

# Verify restore
Write-Host "✅ Database restored from backup!" -ForegroundColor Green
sqlite3 db\narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;"
# Should show: Season 5
```

**✅ Expected:** Database restored, Season 5 active again

---

## 📋 **LOCAL TESTING CHECKLIST**

### **Before Testing:**
- [ ] Database downloaded to local `/db` folder
- [ ] sqlite3 command available in PowerShell
- [ ] Backup location ready

### **During Testing:**
- [ ] Step 1: Database verified
- [ ] Step 2: Data counts documented
- [ ] Step 3: Backup created
- [ ] Step 4: Archive API tested (connection)
- [ ] Step 5: Reset executed
- [ ] Step 6: Verification passed
- [ ] Step 7: Database restored

### **After Testing:**
- [ ] All steps worked correctly
- [ ] No errors encountered
- [ ] Ready to execute on live
- [ ] Commands ready for Render shell

---

## 🚀 **AFTER LOCAL TEST SUCCESS - LIVE EXECUTION**

Once local test is successful, use these **EXACT SAME COMMANDS** on Render shell:

### **Live Execution Commands (Render Shell):**

**Step 1: Backup**
```bash
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite
ls -lh /data/narrrf_world_backup_*.sqlite | tail -1
```

**Step 2: Archive**
```bash
curl https://narrrfs.world/api/admin/archive-season-stats.php
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season, game, COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 5' GROUP BY season, game;"
```

**Step 3: Reset**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "
BEGIN TRANSACTION;
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1);
COMMIT;
"
```

**Step 4: Verify**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
```

**Step 5: Copy to /data**
```bash
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
ls -lh /data/narrrf_world.sqlite
```

---

## ⚠️ **IMPORTANT NOTES**

### **Local Testing:**
- ✅ Safe to test - we'll restore from backup
- ✅ Tests the SQL logic
- ✅ Verifies the process works
- ⚠️ Archive API runs on live server (can't fully test locally)

### **Live Execution:**
- 🚨 **DO NOT TEST ON LIVE** - Execute only at freeze time
- 🚨 **Follow exact order** - Same as local test
- 🚨 **Verify each step** - Check before proceeding
- 🚨 **Copy to /data** - Critical for next deployment

---

## 🎯 **SUCCESS CRITERIA**

### **Local Test:**
- ✅ All SQL commands work
- ✅ Reset successful
- ✅ Verification passed
- ✅ Database restored

### **Live Execution:**
- ✅ Backup created
- ✅ Stats archived
- ✅ Season 6 created
- ✅ All games reset
- ✅ Zero data loss
- ✅ Database copied to /data

---

## 🚀 **READY TO TEST LOCALLY!**

**Status:** ✅ **LOCAL TESTING GUIDE READY**

**Next Steps:**
1. Run local test (Steps 1-7)
2. Verify everything works
3. Restore database from backup
4. Execute on live at freeze time

**Time Remaining:** 30 minutes until freeze

**🎯 TEST LOCALLY FIRST, THEN EXECUTE ON LIVE! 🎯**

---

**Document Created:** November 30, 2025  
**Purpose:** Test season reset locally before live execution  
**Status:** ✅ **READY TO USE**

