# 🗂️ NARRRFS WORLD 12.0 PROFESSIONAL ORGANIZATION SYSTEM V2.0
# 📅 Created: September 14, 2025
# 🎯 Purpose: Professional directory organization for decades of development documentation
# 🔧 Version: Enhanced Professional Edition with comprehensive error handling

param(
    [switch]$Validate,
    [switch]$Verbose
)

# Initialize progress tracking
$TotalDirectories = 0
$CreatedDirectories = 0
$ExistingDirectories = 0
$ErrorCount = 0
$StartTime = Get-Date

Write-Host "🧀 NARRRFS WORLD 12.0 PROFESSIONAL ORGANIZATION SYSTEM V2.0" -ForegroundColor Magenta
Write-Host "📅 Starting professional organization process..." -ForegroundColor Green
Write-Host "⏰ Started at: $($StartTime.ToString('yyyy-MM-dd HH:mm:ss'))" -ForegroundColor Cyan

# Set the base directory with validation
$BaseDir = "C:\xampp-server\htdocs\narrrfs-world\12.0"
if (!(Test-Path $BaseDir)) {
    Write-Host "❌ ERROR: Base directory not found: $BaseDir" -ForegroundColor Red
    Write-Host "Please ensure you're running from the correct location." -ForegroundColor Yellow
    exit 1
}

Set-Location $BaseDir
Write-Host "📁 Working in: $BaseDir" -ForegroundColor Yellow

# Function to create directory with enhanced error handling
function New-DirectoryProfessional {
    param(
        [string]$Path,
        [string]$Description = ""
    )
    
    $script:TotalDirectories++
    
    try {
        if (!(Test-Path $Path)) {
            New-Item -ItemType Directory -Path $Path -Force | Out-Null
            $script:CreatedDirectories++
            if ($Verbose) {
                Write-Host "✅ Created: $Path" -ForegroundColor Green
                if ($Description) { Write-Host "   📝 $Description" -ForegroundColor Gray }
            }
            return $true
        } else {
            $script:ExistingDirectories++
            if ($Verbose) {
                Write-Host "ℹ️  Exists: $Path" -ForegroundColor Yellow
            }
            return $true
        }
    } catch {
        $script:ErrorCount++
        Write-Host "❌ Error creating $Path`: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Create main directory structure
Write-Host "`n🏗️ Creating main directory structure..." -ForegroundColor Cyan

$MainDirectories = @(
    @{Path="ACTIVE_STATUS"; Description="Current status and daily updates"},
    @{Path="LLM_SYNC_SYSTEM"; Description="LLM synchronization files"},
    @{Path="LAB_NOTES"; Description="Development documentation by date"},
    @{Path="DEPLOYMENT_HISTORY"; Description="Deployment records"},
    @{Path="TECHNICAL_DOCUMENTATION"; Description="System documentation"},
    @{Path="MILESTONE_DOCUMENTATION"; Description="Major achievements"},
    @{Path="DEVELOPMENT_TOOLS"; Description="Scripts and templates"},
    @{Path="ARCHIVE"; Description="Legacy and retired files"}
)

foreach ($dir in $MainDirectories) {
    New-DirectoryProfessional -Path $dir.Path -Description $dir.Description
}

# Create LLM Sync System structure
Write-Host "`n🤖 Creating LLM Sync System structure..." -ForegroundColor Cyan

$LLMDirectories = @(
    @{Path="LLM_SYNC_SYSTEM\GENESIS_MASTER"; Description="Master sync file location"},
    @{Path="LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS"; Description="Individual LLM files"},
    @{Path="LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION"; Description="Sync protocol documentation"}
)

foreach ($dir in $LLMDirectories) {
    New-DirectoryProfessional -Path $dir.Path -Description $dir.Description
}

# Create Lab Notes year/month structure for 2025
Write-Host "`n📝 Creating Lab Notes structure for 2025..." -ForegroundColor Cyan

$Months = @(
    @{Name="01_JANUARY"; Description="January 2025 lab notes"},
    @{Name="02_FEBRUARY"; Description="February 2025 lab notes"},
    @{Name="03_MARCH"; Description="March 2025 lab notes"},
    @{Name="04_APRIL"; Description="April 2025 lab notes"},
    @{Name="05_MAY"; Description="May 2025 lab notes"},
    @{Name="06_JUNE"; Description="June 2025 lab notes"},
    @{Name="07_JULY"; Description="July 2025 lab notes"},
    @{Name="08_AUGUST"; Description="August 2025 lab notes"},
    @{Name="09_SEPTEMBER"; Description="September 2025 lab notes"},
    @{Name="10_OCTOBER"; Description="October 2025 lab notes"},
    @{Name="11_NOVEMBER"; Description="November 2025 lab notes"},
    @{Name="12_DECEMBER"; Description="December 2025 lab notes"}
)

foreach ($month in $Months) {
    $monthPath = "LAB_NOTES\2025\$($month.Name)"
    New-DirectoryProfessional -Path $monthPath -Description $month.Description
    
    # Create weekly folders for September 2025 (current month)
    if ($month.Name -eq "09_SEPTEMBER") {
        $weeks = @(
            @{Name="WEEK_36_2025-09-01_to_2025-09-07"; Description="Week 36: September 1-7, 2025"},
            @{Name="WEEK_37_2025-09-08_to_2025-09-14"; Description="Week 37: September 8-14, 2025"},
            @{Name="WEEK_38_2025-09-15_to_2025-09-21"; Description="Week 38: September 15-21, 2025"},
            @{Name="WEEK_39_2025-09-22_to_2025-09-28"; Description="Week 39: September 22-28, 2025"},
            @{Name="WEEK_40_2025-09-29_to_2025-10-05"; Description="Week 40: September 29 - October 5, 2025"}
        )
        
        foreach ($week in $weeks) {
            $weekPath = "$monthPath\$($week.Name)"
            New-DirectoryProfessional -Path $weekPath -Description $week.Description
        }
    }
}

# Create Deployment History structure
Write-Host "`n🚀 Creating Deployment History structure..." -ForegroundColor Cyan

foreach ($month in $Months) {
    $deployPath = "DEPLOYMENT_HISTORY\2025\$($month.Name.Replace('_', '_DEPLOYMENTS'))"
    New-DirectoryProfessional -Path $deployPath -Description "Deployment records for $($month.Name)"
}

# Create Technical Documentation structure
Write-Host "`n🔧 Creating Technical Documentation structure..." -ForegroundColor Cyan

$TechDirectories = @(
    @{Path="TECHNICAL_DOCUMENTATION\SYSTEM_ARCHITECTURE"; Description="System architecture documentation"},
    @{Path="TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\TETRIS_SYSTEM"; Description="Tetris game system documentation"},
    @{Path="TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SNAKE_SYSTEM"; Description="Snake game system documentation"},
    @{Path="TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM"; Description="Space Invaders system documentation"},
    @{Path="TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\CHEESE_HUNT_SYSTEM"; Description="Cheese Hunt system documentation"},
    @{Path="TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\DISCORD_RACE_SYSTEM"; Description="Discord Race system documentation"},
    @{Path="TECHNICAL_DOCUMENTATION\ADMIN_INTERFACE"; Description="Admin interface documentation"},
    @{Path="TECHNICAL_DOCUMENTATION\INTEGRATION_GUIDES"; Description="Integration guides and APIs"}
)

foreach ($dir in $TechDirectories) {
    New-DirectoryProfessional -Path $dir.Path -Description $dir.Description
}

# Create Milestone Documentation structure
Write-Host "`n🏆 Creating Milestone Documentation structure..." -ForegroundColor Cyan

$MilestoneDirectories = @(
    @{Path="MILESTONE_DOCUMENTATION\SEASON_2_LAUNCH"; Description="Season 2 launch documentation"},
    @{Path="MILESTONE_DOCUMENTATION\SEASON_3_LAUNCH"; Description="Season 3 launch documentation"},
    @{Path="MILESTONE_DOCUMENTATION\ACHIEVEMENT_SYSTEM_COMPLETE"; Description="Achievement system completion"},
    @{Path="MILESTONE_DOCUMENTATION\ADMIN_INTERFACE_COMPLETE"; Description="Admin interface completion"},
    @{Path="MILESTONE_DOCUMENTATION\MOBILE_CONTROLS_COMPLETE"; Description="Mobile controls completion"}
)

foreach ($dir in $MilestoneDirectories) {
    New-DirectoryProfessional -Path $dir.Path -Description $dir.Description
}

# Create Development Tools structure
Write-Host "`n🛠️ Creating Development Tools structure..." -ForegroundColor Cyan

$DevToolDirectories = @(
    @{Path="DEVELOPMENT_TOOLS\SCRIPTS"; Description="PowerShell and automation scripts"},
    @{Path="DEVELOPMENT_TOOLS\TEMPLATES"; Description="Documentation templates"},
    @{Path="DEVELOPMENT_TOOLS\WORKSPACE"; Description="Development workspace files"}
)

foreach ($dir in $DevToolDirectories) {
    New-DirectoryProfessional -Path $dir.Path -Description $dir.Description
}

# Create Archive structure
Write-Host "`n📦 Creating Archive structure..." -ForegroundColor Cyan

$ArchiveDirectories = @(
    @{Path="ARCHIVE\LEGACY_FILES"; Description="Legacy files and old implementations"},
    @{Path="ARCHIVE\OLD_VERSIONS"; Description="Previous version backups"},
    @{Path="ARCHIVE\RETIRED_DOCUMENTATION"; Description="Retired documentation"}
)

foreach ($dir in $ArchiveDirectories) {
    New-DirectoryProfessional -Path $dir.Path -Description $dir.Description
}

# Create templates directory for future use
New-DirectoryProfessional -Path "LAB_NOTES\TEMPLATES" -Description "Lab note templates for standardization"

# Final validation and reporting
$EndTime = Get-Date
$Duration = $EndTime - $StartTime

Write-Host "`n📊 PROFESSIONAL ORGANIZATION SYSTEM COMPLETE!" -ForegroundColor Magenta
Write-Host "Completed at: $($EndTime.ToString('yyyy-MM-dd HH:mm:ss'))" -ForegroundColor Cyan
Write-Host "Duration: $([math]::Round($Duration.TotalSeconds, 2)) seconds" -ForegroundColor Cyan

Write-Host "`n📈 SUMMARY STATISTICS:" -ForegroundColor Yellow
Write-Host "📁 Total Directories Processed: $TotalDirectories" -ForegroundColor White
Write-Host "✅ Directories Created: $CreatedDirectories" -ForegroundColor Green
Write-Host "ℹ️  Directories Already Existed: $ExistingDirectories" -ForegroundColor Yellow
Write-Host "❌ Errors Encountered: $ErrorCount" -ForegroundColor Red

if ($ErrorCount -eq 0) {
    Write-Host "`n🎉 SUCCESS: All directories created successfully!" -ForegroundColor Green
    Write-Host "🚀 Ready for file migration phase..." -ForegroundColor Cyan
} else {
    Write-Host "`n⚠️  WARNING: $ErrorCount errors encountered. Please review." -ForegroundColor Yellow
}

Write-Host "`n🔍 Next Steps:" -ForegroundColor Cyan
Write-Host "1. Review directory structure" -ForegroundColor White
Write-Host "2. Plan file migration strategy" -ForegroundColor White
Write-Host "3. Execute file migration script" -ForegroundColor White
Write-Host "4. Create master navigation index" -ForegroundColor White

Write-Host "`n🧀 PROFESSIONAL ORGANIZATION SYSTEM V2.0 COMPLETE!" -ForegroundColor Magenta
