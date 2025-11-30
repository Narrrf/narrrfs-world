# 🧪 LOCAL SEASON 5 FREEZE TEST SCRIPT
# Date: November 30, 2025
# Purpose: Test season reset locally before live execution

Write-Host "🧪 LOCAL SEASON 5 FREEZE TEST" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Navigate to project directory
$projectPath = "C:\xampp-server\htdocs\narrrfs-world"
Set-Location $projectPath

# Verify database exists
if (-not (Test-Path "db\narrrf_world.sqlite")) {
    Write-Host "❌ ERROR: Database not found at db\narrrf_world.sqlite" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Database found!" -ForegroundColor Green
Write-Host ""

# ============================================
# STEP 1: DOCUMENT PRE-TEST COUNTS
# ============================================
Write-Host "=== STEP 1: PRE-TEST DATA COUNTS ===" -ForegroundColor Yellow
Write-Host ""

$tetrisBefore = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"
$snakeBefore = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"
$spaceBefore = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
$cheeseBefore = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;"
$raceBefore = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"

Write-Host "Tetris scores: $tetrisBefore" -ForegroundColor White
Write-Host "Snake scores: $snakeBefore" -ForegroundColor White
Write-Host "Space Invaders scores: $spaceBefore" -ForegroundColor White
Write-Host "Cheese Hunt clicks: $cheeseBefore" -ForegroundColor White
Write-Host "Discord Race participants: $raceBefore" -ForegroundColor White
Write-Host ""

# ============================================
# STEP 2: CREATE BACKUP
# ============================================
Write-Host "=== STEP 2: CREATE BACKUP ===" -ForegroundColor Yellow
Write-Host ""

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupPath = "db\narrrf_world_backup_$timestamp.sqlite"
Copy-Item db\narrrf_world.sqlite $backupPath

if (Test-Path $backupPath) {
    $backupSize = (Get-Item $backupPath).Length / 1MB
    Write-Host "✅ Backup created: $backupPath" -ForegroundColor Green
    Write-Host "   Size: $([math]::Round($backupSize, 2)) MB" -ForegroundColor White
} else {
    Write-Host "❌ ERROR: Backup failed!" -ForegroundColor Red
    exit 1
}
Write-Host ""

# ============================================
# STEP 3: TEST ARCHIVE API (Connection Only)
# ============================================
Write-Host "=== STEP 3: TEST ARCHIVE API ===" -ForegroundColor Yellow
Write-Host ""

try {
    $response = Invoke-WebRequest -Uri "https://narrrfs.world/api/admin/archive-season-stats.php" -UseBasicParsing
    Write-Host "✅ API accessible (returns: $($response.StatusCode))" -ForegroundColor Green
    Write-Host "   Response: $($response.Content.Substring(0, [Math]::Min(100, $response.Content.Length)))..." -ForegroundColor Gray
} catch {
    Write-Host "⚠️  API test failed: $($_.Exception.Message)" -ForegroundColor Yellow
    Write-Host "   (This is OK for local test - API runs on live server)" -ForegroundColor Gray
}
Write-Host ""

# ============================================
# STEP 4: EXECUTE SEASON RESET
# ============================================
Write-Host "=== STEP 4: EXECUTE SEASON RESET ===" -ForegroundColor Yellow
Write-Host ""

$resetSQL = @"
BEGIN TRANSACTION;
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1);
COMMIT;
"@

$resetSQL | sqlite3 db\narrrf_world.sqlite

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Reset executed successfully!" -ForegroundColor Green
} else {
    Write-Host "❌ ERROR: Reset failed!" -ForegroundColor Red
    exit 1
}
Write-Host ""

# ============================================
# STEP 5: VERIFY RESET
# ============================================
Write-Host "=== STEP 5: VERIFY RESET ===" -ForegroundColor Yellow
Write-Host ""

$seasonAfter = sqlite3 db\narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;"
$tetrisAfter = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"
$snakeAfter = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"
$spaceAfter = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
$cheeseAfter = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;"
$raceAfter = sqlite3 db\narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"

Write-Host "Active Season: $seasonAfter" -ForegroundColor $(if ($seasonAfter -eq "Season 6") { "Green" } else { "Red" })
Write-Host "Tetris after: $tetrisAfter (should be 0)" -ForegroundColor $(if ($tetrisAfter -eq 0) { "Green" } else { "Red" })
Write-Host "Snake after: $snakeAfter (should be 0)" -ForegroundColor $(if ($snakeAfter -eq 0) { "Green" } else { "Red" })
Write-Host "Space Invaders after: $spaceAfter (should be 0)" -ForegroundColor $(if ($spaceAfter -eq 0) { "Green" } else { "Red" })
Write-Host "Cheese Hunt: $cheeseAfter (should match: $cheeseBefore)" -ForegroundColor $(if ($cheeseAfter -eq $cheeseBefore) { "Green" } else { "Red" })
Write-Host "Discord Race: $raceAfter (should match: $raceBefore)" -ForegroundColor $(if ($raceAfter -eq $raceBefore) { "Green" } else { "Red" })
Write-Host ""

# Check if all verifications passed
$allPassed = ($seasonAfter -eq "Season 6") -and ($tetrisAfter -eq 0) -and ($snakeAfter -eq 0) -and ($spaceAfter -eq 0) -and ($cheeseAfter -eq $cheeseBefore) -and ($raceAfter -eq $raceBefore)

if ($allPassed) {
    Write-Host "✅ ALL VERIFICATIONS PASSED!" -ForegroundColor Green
} else {
    Write-Host "❌ SOME VERIFICATIONS FAILED!" -ForegroundColor Red
}
Write-Host ""

# ============================================
# STEP 6: RESTORE FROM BACKUP
# ============================================
Write-Host "=== STEP 6: RESTORE FROM BACKUP ===" -ForegroundColor Yellow
Write-Host ""

Copy-Item $backupPath db\narrrf_world.sqlite -Force
$seasonRestored = sqlite3 db\narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;"

if ($seasonRestored -eq "Season 5") {
    Write-Host "✅ Database restored! Active season: $seasonRestored" -ForegroundColor Green
} else {
    Write-Host "❌ ERROR: Restore failed! Season: $seasonRestored" -ForegroundColor Red
}
Write-Host ""

# ============================================
# FINAL SUMMARY
# ============================================
Write-Host "================================" -ForegroundColor Cyan
Write-Host "🧪 LOCAL TEST COMPLETE" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

if ($allPassed -and ($seasonRestored -eq "Season 5")) {
    Write-Host "✅ LOCAL TEST SUCCESSFUL!" -ForegroundColor Green
    Write-Host "   All steps worked correctly" -ForegroundColor White
    Write-Host "   Database restored to original state" -ForegroundColor White
    Write-Host "   Ready to execute on live server!" -ForegroundColor Green
} else {
    Write-Host "⚠️  LOCAL TEST HAD ISSUES" -ForegroundColor Yellow
    Write-Host "   Review the output above" -ForegroundColor White
    Write-Host "   Fix any issues before executing on live" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Backup file: $backupPath" -ForegroundColor Gray
Write-Host ""

