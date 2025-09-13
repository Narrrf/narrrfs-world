# 🗂️ NARRRFS WORLD 12.0 FILE MIGRATION SYSTEM
# 📅 Created: September 14, 2025
# 🎯 Purpose: Migrate existing files to new organized structure

Write-Host "🧀 NARRRFS WORLD 12.0 FILE MIGRATION SYSTEM" -ForegroundColor Cyan
Write-Host "📅 Starting file migration process..." -ForegroundColor Green

# Set the base directory
$BaseDir = "C:\xampp-server\htdocs\narrrfs-world\12.0"
Set-Location $BaseDir

Write-Host "📁 Working in: $BaseDir" -ForegroundColor Yellow

# Define file migration patterns
$FileMigrations = @{
    # LLM Sync Files
    "LLM_SYNC_STATUS_GENESIS_12.0.json" = "LLM_SYNC_SYSTEM\GENESIS_MASTER\"
    "Update_brain_12.0.json" = "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS\"
    "Corebrain_12.0.json" = "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS\"
    "Coreforge_12.0.json" = "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS\"
    "Cheese_Architect_12.0.json" = "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS\"
    "SQL_Junior_12.0.json" = "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS\"
    "Social_Brain_12.0.json" = "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS\"
    "Riddle_brain__12.0.json" = "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS\"
    "Hytopia_Integrator_12.0.json" = "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS\"
    "NFT Architect 12.0.json" = "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS\"
    "Cursor_LLM_12.0.json" = "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS\"
    
    # LLM Sync Documentation
    "LLM_SYNC_UPDATE_SEPTEMBER_10_2025.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LLM_SYNC_UPDATE_SEPTEMBER_9_2025.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LLM_SYNC_UPDATE_0128.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LLM_SYNC_NOTE_0128.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LLM_SYNC_COMPLETION_SUMMARY_0128.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LLM_SYNC_DISCORD_TOKEN_UPDATE_COMPLETION_0128.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LLM_SYNC_DISCORD_TOKEN_UPDATE_0128.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LLM_SYNC_12.0_COMPLETE_NOTES.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LLM_SYNC_12.0_GAME_MANAGEMENT_CONSOLIDATION_SUMMARY.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LL_SYNC_12.0_SEASON_2_LAUNCH_SUMMARY.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LL_SYNC_12.0_QUICK_REFERENCE.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LL_SYNC_12.0_COMPLETE_NOTES.md" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    "LLM_SYNC_STATUS_ALL_LLMS_12.0.json" = "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION\"
    
    # Development Tools
    "download_production_db.bat" = "DEVELOPMENT_TOOLS\SCRIPTS\"
    "Template-Metadata-with-all-traits-12.0.json" = "DEVELOPMENT_TOOLS\TEMPLATES\"
    "Template-Metadata-with-all-traits-12.0-backup.json" = "DEVELOPMENT_TOOLS\TEMPLATES\"
    "cursor.config_12.0.json" = "DEVELOPMENT_TOOLS\TEMPLATES\"
    "12.0 started.code-workspace" = "DEVELOPMENT_TOOLS\WORKSPACE\"
    "Update to 12.0.docx" = "DEVELOPMENT_TOOLS\TEMPLATES\"
    "LLM_12.0_Final_Reflection_Scroll.json" = "DEVELOPMENT_TOOLS\TEMPLATES\"
    "Project-overview-NarrrfsWorld.v12.0_ultimate.md" = "DEVELOPMENT_TOOLS\TEMPLATES\"
    
    # Technical Documentation - Admin Interface
    "ADMIN_INTERFACE_RULE_12.0.md" = "TECHNICAL_DOCUMENTATION\ADMIN_INTERFACE\"
    "ADMIN_INTERFACE_SECURITY_UPGRADE_SUMMARY.md" = "TECHNICAL_DOCUMENTATION\ADMIN_INTERFACE\"
    "ADMIN_INTERFACE_FRONTEND_BREAKTHROUGH_SUMMARY_0128.md" = "TECHNICAL_DOCUMENTATION\ADMIN_INTERFACE\"
    "CURSOR_RULE_5_GAME_SCORE_RETRIEVAL_SYSTEM_V2.md" = "TECHNICAL_DOCUMENTATION\SYSTEM_ARCHITECTURE\"
    "CURSOR_RULE_5_GAME_SCORE_SYSTEM.md" = "TECHNICAL_DOCUMENTATION\SYSTEM_ARCHITECTURE\"
    
    # Technical Documentation - Space Invaders
    "SPACE_INVADERS_IMPROVEMENTS_WORKSHEET.md" = "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM\"
    "SPACE_INVADERS_EXTENDED_BOTTOM_BOUNDARY_FIX.md" = "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM\"
    "SPACE_INVADERS_GLOBAL_MOUSE_TRACKING_FIX.md" = "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM\"
    "SPACE_INVADERS_SHIP_POSITION_FIX.md" = "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM\"
    "SPACE_INVADERS_SUMMARY.md" = "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM\"
    "SPACE_INVADERS_TECHNICAL_ANALYSIS.md" = "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM\"
    "PHOENIX_INVADERS_TESTING_GUIDE.md" = "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM\"
    "PHOENIX_INVADERS_IMPLEMENTATION_COMPLETE.md" = "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM\"
    "PHOENIX_INVADERS_INTEGRATION_SUMMARY.md" = "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM\"
    
    # Deployment History
    "DEPLOYMENT_STATUS_0128.md" = "DEPLOYMENT_HISTORY\2025\JANUARY_DEPLOYMENTS\"
    "DEPLOYMENT_SUMMARY_0128.md" = "DEPLOYMENT_HISTORY\2025\JANUARY_DEPLOYMENTS\"
    "DEPLOYMENT_CHECKLIST_0128.md" = "DEPLOYMENT_HISTORY\2025\JANUARY_DEPLOYMENTS\"
    "PRODUCTION_READINESS_SUMMARY_0128.md" = "DEPLOYMENT_HISTORY\2025\JANUARY_DEPLOYMENTS\"
    "SECURITY_MILESTONE_API_AUTHENTICATION_0128.md" = "DEPLOYMENT_HISTORY\2025\JANUARY_DEPLOYMENTS\"
    
    # Milestone Documentation
    "SEASON_2_ALPHA_ANNOUNCEMENT.md" = "MILESTONE_DOCUMENTATION\SEASON_2_LAUNCH\"
    "SEASON_3_ACHIEVEMENT_SYSTEM_COMPLETE_SYNC_UPDATE.md" = "MILESTONE_DOCUMENTATION\SEASON_3_LAUNCH\"
    "CHEESE_MISSIONS_STATUS_FEATURE.md" = "MILESTONE_DOCUMENTATION\ACHIEVEMENT_SYSTEM_COMPLETE\"
    "SESSION_17_FINAL_VALIDATION_SUMMARY.md" = "MILESTONE_DOCUMENTATION\ADMIN_INTERFACE_COMPLETE\"
    "SESSION_17_LLM_SYNC_SUMMARY.md" = "MILESTONE_DOCUMENTATION\ADMIN_INTERFACE_COMPLETE\"
    
    # Database Documentation
    "DATABASE_BACKUP_DIAGNOSTIC_IMPLEMENTATION_SUMMARY_0128.md" = "TECHNICAL_DOCUMENTATION\SYSTEM_ARCHITECTURE\"
    "DATABASE_BACKUP_DIAGNOSTIC_TOOLS_0128.md" = "TECHNICAL_DOCUMENTATION\SYSTEM_ARCHITECTURE\"
    "GAME_SYSTEM_STATUS_0128.md" = "TECHNICAL_DOCUMENTATION\SYSTEM_ARCHITECTURE\"
    
    # Discord Integration
    "DISCORD_TOKEN_UPDATE_RULE_0128.md" = "TECHNICAL_DOCUMENTATION\INTEGRATION_GUIDES\"
}

# Migrate files based on patterns
Write-Host "📦 Starting file migration..." -ForegroundColor Cyan

$MigratedCount = 0
$SkippedCount = 0

foreach ($file in $FileMigrations.Keys) {
    $sourcePath = $file
    $destinationDir = $FileMigrations[$file]
    
    if (Test-Path $sourcePath) {
        $destinationPath = Join-Path $destinationDir $file
        
        # Create destination directory if it doesn't exist
        if (!(Test-Path $destinationDir)) {
            New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
        }
        
        # Move the file
        Move-Item -Path $sourcePath -Destination $destinationPath -Force
        Write-Host "✅ Migrated: $file → $destinationDir" -ForegroundColor Green
        $MigratedCount++
    } else {
        Write-Host "⚠️  Not found: $file" -ForegroundColor Yellow
        $SkippedCount++
    }
}

# Handle WE_WORK_ON_NOW directory migration
Write-Host "📝 Migrating WE_WORK_ON_NOW directory..." -ForegroundColor Cyan

if (Test-Path "WE_WORK_ON_NOW") {
    $WorkNowFiles = Get-ChildItem "WE_WORK_ON_NOW" -File
    
    foreach ($file in $WorkNowFiles) {
        $fileName = $file.Name
        $destinationDir = ""
        
        # Determine destination based on file name patterns
        if ($fileName -like "LAB_NOTE_*") {
            # Extract date from filename to determine week
            if ($fileName -match "(\d{4})") {
                $year = $matches[1]
                if ($fileName -match "(\d{2})(\d{2})") {
                    $month = $matches[1]
                    $day = $matches[2]
                    
                    # Determine week based on day
                    $week = [math]::Ceiling($day / 7)
                    if ($week -lt 10) { $week = "0$week" }
                    
                    $destinationDir = "LAB_NOTES\$year\$(Get-MonthName $month)\WEEK_$week"
                }
            }
            
            # Fallback to current week if date extraction fails
            if ($destinationDir -eq "") {
                $destinationDir = "LAB_NOTES\2025\09_SEPTEMBER\WEEK_37_2025-09-08_to_2025-09-14"
            }
        }
        elseif ($fileName -like "QUICK_STATUS*") {
            $destinationDir = "ACTIVE_STATUS"
        }
        elseif ($fileName -like "DEPLOYMENT_*") {
            $destinationDir = "DEPLOYMENT_HISTORY\2025\SEPTEMBER_DEPLOYMENTS"
        }
        elseif ($fileName -like "*SEASON_3*") {
            $destinationDir = "MILESTONE_DOCUMENTATION\SEASON_3_LAUNCH"
        }
        elseif ($fileName -like "*ACHIEVEMENT*") {
            $destinationDir = "MILESTONE_DOCUMENTATION\ACHIEVEMENT_SYSTEM_COMPLETE"
        }
        else {
            $destinationDir = "ARCHIVE\LEGACY_FILES"
        }
        
        # Create destination directory if it doesn't exist
        if (!(Test-Path $destinationDir)) {
            New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
        }
        
        # Move the file
        $destinationPath = Join-Path $destinationDir $fileName
        Move-Item -Path $file.FullName -Destination $destinationPath -Force
        Write-Host "✅ Migrated: $fileName → $destinationDir" -ForegroundColor Green
        $MigratedCount++
    }
    
    # Remove empty WE_WORK_ON_NOW directory
    if ((Get-ChildItem "WE_WORK_ON_NOW" -Force).Count -eq 0) {
        Remove-Item "WE_WORK_ON_NOW" -Force
        Write-Host "🗑️  Removed empty WE_WORK_ON_NOW directory" -ForegroundColor Yellow
    }
}

# Helper function to get month name
function Get-MonthName($monthNum) {
    $months = @("01_JANUARY", "02_FEBRUARY", "03_MARCH", "04_APRIL", "05_MAY", "06_JUNE", 
                "07_JULY", "08_AUGUST", "09_SEPTEMBER", "10_OCTOBER", "11_NOVEMBER", "12_DECEMBER")
    return $months[$monthNum - 1]
}

Write-Host "📊 Migration Summary:" -ForegroundColor Cyan
Write-Host "✅ Files migrated: $MigratedCount" -ForegroundColor Green
Write-Host "⚠️  Files skipped: $SkippedCount" -ForegroundColor Yellow

Write-Host "🎉 FILE MIGRATION COMPLETE!" -ForegroundColor Magenta
Write-Host "📁 All files organized in professional structure" -ForegroundColor Green
Write-Host "🚀 Ready for template creation phase..." -ForegroundColor Cyan
