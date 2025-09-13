# EXTENDED SAFE VERIFICATION DELETE SCRIPT - COMPLETE CLEAN DEPLOY
# This script asks for confirmation before each deletion for maximum safety
# Includes ALL remaining files from the comprehensive review

Write-Host "EXTENDED SAFE VERIFICATION DELETE SCRIPT" -ForegroundColor Green
Write-Host "Date: $(Get-Date)" -ForegroundColor Yellow
Write-Host "WARNING: This script will ask for confirmation on EACH file!" -ForegroundColor Red
Write-Host "COMPREHENSIVE: All remaining files from clean deploy review" -ForegroundColor Cyan
Write-Host ""

# Change to project root
Set-Location "C:\xampp-server\htdocs\narrrfs-world"

Write-Host "Current Directory: $(Get-Location)" -ForegroundColor Cyan
Write-Host ""

# Function to safely delete files/directories with user confirmation
function Remove-SafeFileWithConfirmation {
    param($Path, $Description, $Category)
    
    if (Test-Path $Path) {
        Write-Host ""
        Write-Host "FOUND: $Description" -ForegroundColor Cyan
        Write-Host "   Path: $Path" -ForegroundColor Gray
        Write-Host "   Category: $Category" -ForegroundColor Gray
        
        # Show file size if it's a file
        if (Test-Path $Path -PathType Leaf) {
            $fileSize = (Get-Item $Path).Length
            if ($fileSize -gt 1MB) {
                Write-Host "   Size: $([math]::Round($fileSize/1MB, 2)) MB" -ForegroundColor Yellow
            } elseif ($fileSize -gt 1KB) {
                Write-Host "   Size: $([math]::Round($fileSize/1KB, 2)) KB" -ForegroundColor Yellow
            } else {
                Write-Host "   Size: $fileSize bytes" -ForegroundColor Yellow
            }
        }
        
        # Show directory contents count if it's a directory
        if (Test-Path $Path -PathType Container) {
            $itemCount = (Get-ChildItem $Path -Recurse | Measure-Object).Count
            Write-Host "   Items: $itemCount files/folders" -ForegroundColor Yellow
        }
        
        Write-Host ""
        Write-Host "What would you like to do?" -ForegroundColor Magenta
        Write-Host "   [D] DELETE this file/directory" -ForegroundColor Red
        Write-Host "   [S] SKIP this file/directory" -ForegroundColor Yellow
        Write-Host "   [R] REVIEW LATER (add to review list)" -ForegroundColor Blue
        Write-Host "   [Q] QUIT script" -ForegroundColor Red
        Write-Host ""
        
        do {
            $choice = Read-Host "Enter your choice (D/S/R/Q)"
            $choice = $choice.ToUpper()
            
            switch ($choice) {
                "D" {
                    try {
                        if (Test-Path $Path -PathType Container) {
                            Remove-Item $Path -Recurse -Force
                            Write-Host "DELETED DIRECTORY: $Description" -ForegroundColor Green
                        } else {
                            Remove-Item $Path -Force
                            Write-Host "DELETED FILE: $Description" -ForegroundColor Green
                        }
                        return "DELETED"
                    } catch {
                        Write-Host "ERROR DELETING: $Description - $($_.Exception.Message)" -ForegroundColor Red
                        return "ERROR"
                    }
                }
                "S" {
                    Write-Host "SKIPPED: $Description" -ForegroundColor Yellow
                    return "SKIPPED"
                }
                "R" {
                    Write-Host "ADDED TO REVIEW LIST: $Description" -ForegroundColor Blue
                    return "REVIEW"
                }
                "Q" {
                    Write-Host "SCRIPT STOPPED BY USER" -ForegroundColor Red
                    exit
                }
                default {
                    Write-Host "Invalid choice. Please enter D, S, R, or Q." -ForegroundColor Red
                }
            }
        } while ($choice -notin @("D", "S", "R", "Q"))
    } else {
        Write-Host "NOT FOUND: $Description" -ForegroundColor Yellow
        return "NOT_FOUND"
    }
}

# Initialize counters
$deletedCount = 0
$skippedCount = 0
$reviewCount = 0
$errorCount = 0
$notFoundCount = 0
$reviewList = @()

Write-Host "STARTING COMPREHENSIVE ROOT LEVEL FILE REVIEW..." -ForegroundColor Magenta
Write-Host ""

# Complete root level deletions
$rootFiles = @(
    @{Path="AI rules cursor"; Description="Old rule files"; Category="Documentation"},
    @{Path="BOSS_TESTING_GUIDE.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="COMMUNITY_UPDATE_POST.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="COMPLETE_SYNC_FIX_IMPLEMENTATION.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="cookies.txt"; Description="Temporary file"; Category="Temporary"},
    @{Path="cord invite code to 3hRRh3gB"; Description="Temporary file"; Category="Temporary"},
    @{Path="CORRECTED_DEPLOYMENT_PLAN.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="Cursor"; Description="Old workspace files"; Category="Development"},
    @{Path="DISCORD_RACE_SYNC_FIX_SUMMARY.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="discord-tools"; Description="Old tools"; Category="Development"},
    @{Path="Dockerfile"; Description="Not used in current deployment"; Category="Development"},
    @{Path="et --hard afb3184"; Description="Git command remnants"; Category="Temporary"},
    @{Path="et --hard de14c5b"; Description="Git command remnants"; Category="Temporary"},
    @{Path="favicon.ico"; Description="Duplicate favicon"; Category="Duplicate"},
    @{Path="GAME_MANAGEMENT_CONSOLIDATION_PLAN.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="GAME_SCORING_SYSTEM_RULES.md"; Description="Old rules"; Category="Documentation"},
    @{Path="generate_db_password_hash.php"; Description="Development tool"; Category="Development"},
    @{Path="hytopia-demo"; Description="Demo files"; Category="Development"},
    @{Path="import_scores.py"; Description="Development script"; Category="Development"},
    @{Path="includes"; Description="Old includes"; Category="Development"},
    @{Path="leaderboard.csv"; Description="Duplicate file"; Category="Duplicate"},
    @{Path="LIVE_TESTING_CHECKLIST.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="logs"; Description="Log files"; Category="Temporary"},
    @{Path="MISSIONS_3_5_GAMES_FIX_SUMMARY.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="narrrf_world.sqlite"; Description="Duplicate database"; Category="Duplicate"},
    @{Path="node_modules"; Description="Development dependencies"; Category="Development"},
    @{Path="package-lock.json"; Description="Development dependencies"; Category="Development"},
    @{Path="package.json"; Description="Development dependencies"; Category="Development"},
    @{Path="PERFECT_5_GAME_SYNC_STATUS_REPORT.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="PHOENIX_MANAGEMENT_README.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="private"; Description="Private files"; Category="Development"},
    @{Path="QUICK_STATUS.md"; Description="Old status"; Category="Documentation"},
    @{Path="README_ENVIRONMENT.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="README_GITHUB.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="README_NFT_VERIFICATION_SETUP.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="README_ONIONPIPE.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="score download"; Description="Old downloads"; Category="Temporary"},
    @{Path="SCORING_SYSTEM_FIX_SUMMARY.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="scripts"; Description="Old scripts"; Category="Development"},
    @{Path="season-data-2025-09-11.csv"; Description="Old data"; Category="Temporary"},
    @{Path="SECURITY_AUDIT_REPORT.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="SECURITY_STATUS_REPORT.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="space-cheese-invaders-live.js"; Description="Old script"; Category="Development"},
    @{Path="start.sh"; Description="Old script"; Category="Development"},
    @{Path="sync"; Description="Old sync files"; Category="Development"},
    @{Path="test-community-funds.csv"; Description="Test data"; Category="Test"},
    @{Path="test-live-api.php"; Description="Test file"; Category="Test"},
    @{Path="test-missions.php"; Description="Test file"; Category="Test"},
    @{Path="test-upload.html"; Description="Test file"; Category="Test"}
)

foreach ($file in $rootFiles) {
    $result = Remove-SafeFileWithConfirmation -Path $file.Path -Description $file.Description -Category $file.Category
    
    switch ($result) {
        "DELETED" { $deletedCount++ }
        "SKIPPED" { $skippedCount++ }
        "REVIEW" { 
            $reviewCount++
            $reviewList += "ROOT: $($file.Description) - $($file.Path)"
        }
        "ERROR" { $errorCount++ }
        "NOT_FOUND" { $notFoundCount++ }
    }
}

Write-Host ""
Write-Host "STARTING DATABASE BACKUP REVIEW..." -ForegroundColor Magenta
Write-Host ""

# Database backup deletions
$dbBackups = @(
    @{Path="db\backup 0408"; Description="Old backup"; Category="Backup"},
    @{Path="db\backup 0508"; Description="Old backup"; Category="Backup"},
    @{Path="db\backup 0708"; Description="Old backup"; Category="Backup"},
    @{Path="db\backup 0808"; Description="Old backup"; Category="Backup"},
    @{Path="db\BAckup 108"; Description="Old backup"; Category="Backup"},
    @{Path="db\backup 1208"; Description="Old backup"; Category="Backup"},
    @{Path="db\BAckup 1408"; Description="Old backup"; Category="Backup"},
    @{Path="db\backup 2008"; Description="Old backup"; Category="Backup"},
    @{Path="db\backup season 2"; Description="Old backup"; Category="Backup"},
    @{Path="db\Backup1108"; Description="Old backup"; Category="Backup"},
    @{Path="db\Backup1608"; Description="Old backup"; Category="Backup"},
    @{Path="db\DATABASES_BACKUP_2024-06-21.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\DATABASES_BACKUP_2024-06-21+.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\narrrf_world_backup_20250905_214126.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\narrrf_world_backup.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\narrrf_world-backuip.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\narrrf_world-old-scores2206.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\narrrf_world-old-scores2306.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\narrrf_world.sqlite-backup-2006-2.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\narrrf_world.sqlite-backup-2006-3.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\narrrf_world.sqlite-backup-2006.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\narrrf_world.sqlite-backup-2306.sqlite"; Description="Old backup"; Category="Backup"},
    @{Path="db\narrrfs_world_LIVE_PRODUCTION_2025-08-30.sqlite"; Description="Old backup"; Category="Backup"}
)

foreach ($file in $dbBackups) {
    $result = Remove-SafeFileWithConfirmation -Path $file.Path -Description $file.Description -Category $file.Category
    
    switch ($result) {
        "DELETED" { $deletedCount++ }
        "SKIPPED" { $skippedCount++ }
        "REVIEW" { 
            $reviewCount++
            $reviewList += "DB: $($file.Description) - $($file.Path)"
        }
        "ERROR" { $errorCount++ }
        "NOT_FOUND" { $notFoundCount++ }
    }
}

Write-Host ""
Write-Host "STARTING PUBLIC DIRECTORY REVIEW..." -ForegroundColor Magenta
Write-Host ""

# Public directory deletions
$publicFiles = @(
    @{Path="public\admin-interface.html.bak"; Description="Backup file"; Category="Backup"},
    @{Path="public\Bingo-backup.html"; Description="Backup file"; Category="Backup"},
    @{Path="public\DISCORD_TOKEN_README.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="public\env-example.txt"; Description="Example file"; Category="Development"},
    @{Path="public\experiment-x.html"; Description="Test file"; Category="Test"},
    @{Path="public\favicon-phantom.png"; Description="Duplicate favicon"; Category="Duplicate"},
    @{Path="public\leaderboard.csv"; Description="Old data"; Category="Temporary"},
    @{Path="public\profile-backup.html"; Description="Backup file"; Category="Backup"},
    @{Path="public\profile_body_section.html"; Description="Old component"; Category="Development"},
    @{Path="public\README.md"; Description="Old documentation"; Category="Documentation"},
    @{Path="public\scripts\old bckups"; Description="Old backup scripts"; Category="Backup"},
    @{Path="public\SOL-Community-wallet.csv"; Description="Old data"; Category="Temporary"},
    @{Path="public\test-admin-apis.html"; Description="Test file"; Category="Test"},
    @{Path="public\test-api.php"; Description="Test file"; Category="Test"},
    @{Path="public\test-cheese-race-apis.php"; Description="Test file"; Category="Test"},
    @{Path="public\test-cheese-race-db.php"; Description="Test file"; Category="Test"},
    @{Path="public\test-discord-race.html"; Description="Test file"; Category="Test"},
    @{Path="public\test-missions-api.html"; Description="Test file"; Category="Test"},
    @{Path="public\test-space-invaders-score.html"; Description="Test file"; Category="Test"},
    @{Path="public\upload_test.html"; Description="Test file"; Category="Test"},
    @{Path="public\ui\camera-toggle.html"; Description="Old component"; Category="Development"}
)

foreach ($file in $publicFiles) {
    $result = Remove-SafeFileWithConfirmation -Path $file.Path -Description $file.Description -Category $file.Category
    
    switch ($result) {
        "DELETED" { $deletedCount++ }
        "SKIPPED" { $skippedCount++ }
        "REVIEW" { 
            $reviewCount++
            $reviewList += "PUBLIC: $($file.Description) - $($file.Path)"
        }
        "ERROR" { $errorCount++ }
        "NOT_FOUND" { $notFoundCount++ }
    }
}

Write-Host ""
Write-Host "STARTING API DIRECTORY REVIEW..." -ForegroundColor Magenta
Write-Host ""

# API directory deletions
$apiFiles = @(
    @{Path="api\debug-test.php"; Description="Test file"; Category="Test"},
    @{Path="api\debug-user-tracking.php"; Description="Debug file"; Category="Debug"},
    @{Path="api\test-cheese-lookup.php"; Description="Test file"; Category="Test"},
    @{Path="api\test-cheese-stats.php"; Description="Test file"; Category="Test"},
    @{Path="api\debug"; Description="Debug directory"; Category="Debug"},
    @{Path="api\dev\log.txt"; Description="Log file"; Category="Temporary"},
    @{Path="api\dev\db-viewer-data.php"; Description="Development tool"; Category="Development"},
    @{Path="api\dev\db-viewer.php"; Description="Development tool"; Category="Development"},
    @{Path="api\dev\download-db.php"; Description="Development tool"; Category="Development"},
    @{Path="api\dev\upload-db.php"; Description="Development tool"; Category="Development"},
    @{Path="api\dev\init-tetris-achievements.php"; Description="Development tool"; Category="Development"},
    @{Path="api\dev\unlock-snake-achievement.php"; Description="Development tool"; Category="Development"},
    @{Path="api\dev\unlock-tetris-achievement.php"; Description="Development tool"; Category="Development"},
    @{Path="api\auth\oauth-debug.php"; Description="Debug file"; Category="Debug"},
    @{Path="api\auth\oauth-test.php"; Description="Test file"; Category="Test"},
    @{Path="api\discord\test-bot-api.php"; Description="Test file"; Category="Test"},
    @{Path="api\discord\test-bot-connection.php"; Description="Test file"; Category="Test"},
    @{Path="api\user\test-db.php"; Description="Test file"; Category="Test"}
)

foreach ($file in $apiFiles) {
    $result = Remove-SafeFileWithConfirmation -Path $file.Path -Description $file.Description -Category $file.Category
    
    switch ($result) {
        "DELETED" { $deletedCount++ }
        "SKIPPED" { $skippedCount++ }
        "REVIEW" { 
            $reviewCount++
            $reviewList += "API: $($file.Description) - $($file.Path)"
        }
        "ERROR" { $errorCount++ }
        "NOT_FOUND" { $notFoundCount++ }
    }
}

# Final summary
Write-Host ""
Write-Host "COMPREHENSIVE CLEAN DEPLOY REVIEW COMPLETE!" -ForegroundColor Green
Write-Host "FINAL SUMMARY:" -ForegroundColor Cyan
Write-Host "   DELETED: $deletedCount files" -ForegroundColor Green
Write-Host "   SKIPPED: $skippedCount files" -ForegroundColor Yellow
Write-Host "   REVIEW LATER: $reviewCount files" -ForegroundColor Blue
Write-Host "   ERRORS: $errorCount files" -ForegroundColor Red
Write-Host "   NOT FOUND: $notFoundCount files" -ForegroundColor Yellow
Write-Host ""

if ($reviewList.Count -gt 0) {
    Write-Host "FILES MARKED FOR REVIEW:" -ForegroundColor Blue
    foreach ($item in $reviewList) {
        Write-Host "   - $item" -ForegroundColor Gray
    }
    Write-Host ""
}

Write-Host "Comprehensive clean deploy review completed with user confirmation!" -ForegroundColor Green
Write-Host "Next: Review the files marked for review later" -ForegroundColor Yellow
Write-Host ""
Write-Host "NARRRFS WORLD 12.0 - COMPREHENSIVE CLEAN DEPLOY COMPLETE!" -ForegroundColor Magenta
