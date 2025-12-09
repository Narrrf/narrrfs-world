# ⚡ LOCAL TEST vs LIVE EXECUTION - SIDE-BY-SIDE

**Date:** November 30, 2025  
**Time Remaining:** 30 minutes until freeze  
**Purpose:** Test locally first, then execute same steps on live

---

## 📋 **COMMAND COMPARISON**

### **STEP 1: BACKUP**

| **Local (PowerShell)** | **Live (Render Shell)** |
|------------------------|-------------------------|
| ```powershell<br>$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"<br>$backupPath = "db\narrrf_world_backup_$timestamp.sqlite"<br>Copy-Item db\narrrf_world.sqlite $backupPath<br>Write-Host "✅ Backup: $backupPath"<br>``` | ```bash<br>cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite<br>ls -lh /data/narrrf_world_backup_*.sqlite \| tail -1<br>``` |

---

### **STEP 2: ARCHIVE**

| **Local (PowerShell)** | **Live (Render Shell)** |
|------------------------|-------------------------|
| ```powershell<br># Test API connection (won't archive local DB)<br>curl https://narrrfs.world/api/admin/archive-season-stats.php<br>``` | ```bash<br>curl https://narrrfs.world/api/admin/archive-season-stats.php<br>echo "" && \<br>echo "=== VERIFY ARCHIVAL ===" && \<br>sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season, game, COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 5' GROUP BY season, game;"<br>``` |

**Note:** Local test just verifies API connection. Live execution actually archives.

---

### **STEP 3: RESET**

| **Local (PowerShell)** | **Live (Render Shell)** |
|------------------------|-------------------------|
| ```powershell<br>sqlite3 db\narrrf_world.sqlite "<br>BEGIN TRANSACTION;<br>DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');<br>DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');<br>UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;<br>INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1);<br>COMMIT;<br>"<br>``` | ```bash<br>sqlite3 /var/www/html/db/narrrf_world.sqlite "<br>BEGIN TRANSACTION;<br>DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');<br>DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');<br>UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;<br>INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1);<br>COMMIT;<br>"<br>``` |

**✅ Same SQL - Only path differs!**

---

### **STEP 4: VERIFY**

| **Local (PowerShell)** | **Live (Render Shell)** |
|------------------------|-------------------------|
| ```powershell<br>sqlite3 db\narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;"<br>sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"<br>sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"<br>sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"<br>``` | ```bash<br>sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;"<br>sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"<br>sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"<br>sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"<br>``` |

---

### **STEP 5: COPY (Live Only)**

| **Local (PowerShell)** | **Live (Render Shell)** |
|------------------------|-------------------------|
| ```powershell<br># Not needed locally - we restore from backup<br>``` | ```bash<br>cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite<br>ls -lh /data/narrrf_world.sqlite<br>``` |

---

## 🧪 **LOCAL TEST PROCEDURE**

### **Quick Local Test Script (PowerShell):**

```powershell
# Navigate to project
cd C:\xampp-server\htdocs\narrrfs-world

# Step 1: Document pre-test counts
Write-Host "=== PRE-TEST COUNTS ===" -ForegroundColor Cyan
$tetris = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"
$snake = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"
$space = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
Write-Host "Tetris: $tetris" -ForegroundColor Yellow
Write-Host "Snake: $snake" -ForegroundColor Yellow
Write-Host "Space Invaders: $space" -ForegroundColor Yellow

# Step 2: Create backup
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupPath = "db\narrrf_world_backup_$timestamp.sqlite"
Copy-Item db\narrrf_world.sqlite $backupPath
Write-Host "`n✅ Backup created: $backupPath" -ForegroundColor Green

# Step 3: Execute reset
Write-Host "`n=== EXECUTING RESET ===" -ForegroundColor Cyan
sqlite3 db\narrrf_world.sqlite "
BEGIN TRANSACTION;
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1);
COMMIT;
"
Write-Host "✅ Reset executed!" -ForegroundColor Green

# Step 4: Verify
Write-Host "`n=== VERIFICATION ===" -ForegroundColor Cyan
$season = sqlite3 db\narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;"
Write-Host "Active Season: $season" -ForegroundColor Yellow
$tetrisAfter = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"
$snakeAfter = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"
$spaceAfter = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
Write-Host "Tetris after: $tetrisAfter (should be 0)" -ForegroundColor $(if ($tetrisAfter -eq 0) { "Green" } else { "Red" })
Write-Host "Snake after: $snakeAfter (should be 0)" -ForegroundColor $(if ($snakeAfter -eq 0) { "Green" } else { "Red" })
Write-Host "Space Invaders after: $spaceAfter (should be 0)" -ForegroundColor $(if ($spaceAfter -eq 0) { "Green" } else { "Red" })

# Step 5: Restore from backup
Write-Host "`n=== RESTORING FROM BACKUP ===" -ForegroundColor Cyan
Copy-Item $backupPath db\narrrf_world.sqlite -Force
$seasonRestored = sqlite3 db\narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;"
Write-Host "✅ Database restored! Active season: $seasonRestored" -ForegroundColor Green
```

---

## 🚀 **LIVE EXECUTION (After Local Test Success)**

### **Complete Live Execution Script (Render Shell):**

```bash
# Step 1: Backup
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite
ls -lh /data/narrrf_world_backup_*.sqlite | tail -1
echo "BACKUP CREATED: $(date)" >> /data/season_reset_log.txt

# Step 2: Archive
curl https://narrrfs.world/api/admin/archive-season-stats.php
echo "" && \
echo "=== VERIFY ARCHIVAL ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season, game, COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 5' GROUP BY season, game;"

# Step 3: Reset
sqlite3 /var/www/html/db/narrrf_world.sqlite "
BEGIN TRANSACTION;
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1);
COMMIT;
"
echo "SEASON RESET EXECUTED: $(date)" >> /data/season_reset_log.txt

# Step 4: Verify
echo "=== SEASON 6 STATUS ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;" && \
echo "" && \
echo "=== RESET VERIFICATION ===" && \
echo "Tetris:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"

# Step 5: Copy to /data
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
ls -lh /data/narrrf_world.sqlite
echo "DATABASE COPIED TO /data: $(date)" >> /data/season_reset_log.txt
echo "✅ SEASON 5 FREEZE & SEASON 6 RESET COMPLETE!" >> /data/season_reset_log.txt
```

---

## ✅ **TESTING CHECKLIST**

### **Local Test:**
- [ ] Run local test script
- [ ] Verify all steps work
- [ ] Check Season 6 created
- [ ] Check all games reset to 0
- [ ] Check preserved data intact
- [ ] Restore from backup
- [ ] Verify Season 5 restored

### **Live Execution (After Local Success):**
- [ ] Connect to Render shell
- [ ] Execute Step 1 (Backup)
- [ ] Execute Step 2 (Archive) + Verify
- [ ] Execute Step 3 (Reset)
- [ ] Execute Step 4 (Verify)
- [ ] Execute Step 5 (Copy to /data)
- [ ] Post Discord announcement

---

## 🎯 **SUCCESS CRITERIA**

### **Local Test:**
- ✅ All SQL commands execute without errors
- ✅ Season 6 created successfully
- ✅ All games reset to 0
- ✅ Preserved data intact
- ✅ Database restored successfully

### **Live Execution:**
- ✅ Backup created
- ✅ Stats archived (verified)
- ✅ Season 6 active
- ✅ All games reset
- ✅ Zero data loss
- ✅ Database copied to /data

---

## 🚀 **READY TO TEST!**

**Status:** ✅ **LOCAL TEST GUIDE READY**

**Next Steps:**
1. Run local test (PowerShell script above)
2. Verify everything works
3. Restore database
4. Execute on live at freeze time

**Time Remaining:** 30 minutes until freeze

**🎯 TEST LOCALLY FIRST - THEN EXECUTE ON LIVE! 🎯**

---

**Document Created:** November 30, 2025  
**Purpose:** Side-by-side comparison for local test vs live execution  
**Status:** ✅ **READY TO USE**

