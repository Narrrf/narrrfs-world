# NARRRFS WORLD 12.0 PROFESSIONAL ORGANIZATION SYSTEM
# Created: September 14, 2025
# Purpose: Organize 12.0 directory for decades of development documentation

Write-Host "NARRRFS WORLD 12.0 PROFESSIONAL ORGANIZATION SYSTEM" -ForegroundColor Cyan
Write-Host "Starting organization process..." -ForegroundColor Green

# Set the base directory
$BaseDir = "C:\xampp-server\htdocs\narrrfs-world\12.0"
Set-Location $BaseDir

Write-Host "Working in: $BaseDir" -ForegroundColor Yellow

# Create main directory structure
Write-Host "Creating main directory structure..." -ForegroundColor Cyan

$MainDirectories = @(
    "ACTIVE_STATUS",
    "LLM_SYNC_SYSTEM",
    "LAB_NOTES",
    "DEPLOYMENT_HISTORY", 
    "TECHNICAL_DOCUMENTATION",
    "MILESTONE_DOCUMENTATION",
    "DEVELOPMENT_TOOLS",
    "ARCHIVE"
)

foreach ($dir in $MainDirectories) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force
        Write-Host "Created: $dir" -ForegroundColor Green
    } else {
        Write-Host "Exists: $dir" -ForegroundColor Yellow
    }
}

# Create LLM Sync System structure
Write-Host "Creating LLM Sync System structure..." -ForegroundColor Cyan

$LLMDirectories = @(
    "LLM_SYNC_SYSTEM\GENESIS_MASTER",
    "LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS", 
    "LLM_SYNC_SYSTEM\SYNC_DOCUMENTATION"
)

foreach ($dir in $LLMDirectories) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force
        Write-Host "Created: $dir" -ForegroundColor Green
    }
}

# Create Lab Notes year/month structure for 2025
Write-Host "Creating Lab Notes structure for 2025..." -ForegroundColor Cyan

$Months = @(
    "01_JANUARY", "02_FEBRUARY", "03_MARCH", "04_APRIL",
    "05_MAY", "06_JUNE", "07_JULY", "08_AUGUST", "09_SEPTEMBER",
    "10_OCTOBER", "11_NOVEMBER", "12_DECEMBER"
)

foreach ($month in $Months) {
    $monthPath = "LAB_NOTES\2025\$month"
    if (!(Test-Path $monthPath)) {
        New-Item -ItemType Directory -Path $monthPath -Force
        Write-Host "Created: $monthPath" -ForegroundColor Green
    }
    
    # Create weekly folders for September 2025 (current month)
    if ($month -eq "09_SEPTEMBER") {
        $weeks = @(
            "WEEK_36_2025-09-01_to_2025-09-07",
            "WEEK_37_2025-09-08_to_2025-09-14", 
            "WEEK_38_2025-09-15_to_2025-09-21",
            "WEEK_39_2025-09-22_to_2025-09-28",
            "WEEK_40_2025-09-29_to_2025-10-05"
        )
        
        foreach ($week in $weeks) {
            $weekPath = "$monthPath\$week"
            if (!(Test-Path $weekPath)) {
                New-Item -ItemType Directory -Path $weekPath -Force
                Write-Host "Created: $weekPath" -ForegroundColor Green
            }
        }
    }
}

# Create Deployment History structure
Write-Host "Creating Deployment History structure..." -ForegroundColor Cyan

foreach ($month in $Months) {
    $deployPath = "DEPLOYMENT_HISTORY\2025\$($month.Replace('_', '_DEPLOYMENTS'))"
    if (!(Test-Path $deployPath)) {
        New-Item -ItemType Directory -Path $deployPath -Force
        Write-Host "Created: $deployPath" -ForegroundColor Green
    }
}

# Create Technical Documentation structure
Write-Host "Creating Technical Documentation structure..." -ForegroundColor Cyan

$TechDirectories = @(
    "TECHNICAL_DOCUMENTATION\SYSTEM_ARCHITECTURE",
    "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\TETRIS_SYSTEM",
    "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SNAKE_SYSTEM", 
    "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\SPACE_INVADERS_SYSTEM",
    "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\CHEESE_HUNT_SYSTEM",
    "TECHNICAL_DOCUMENTATION\GAME_SYSTEMS\DISCORD_RACE_SYSTEM",
    "TECHNICAL_DOCUMENTATION\ADMIN_INTERFACE",
    "TECHNICAL_DOCUMENTATION\INTEGRATION_GUIDES"
)

foreach ($dir in $TechDirectories) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force
        Write-Host "Created: $dir" -ForegroundColor Green
    }
}

# Create Milestone Documentation structure
Write-Host "Creating Milestone Documentation structure..." -ForegroundColor Cyan

$MilestoneDirectories = @(
    "MILESTONE_DOCUMENTATION\SEASON_2_LAUNCH",
    "MILESTONE_DOCUMENTATION\SEASON_3_LAUNCH", 
    "MILESTONE_DOCUMENTATION\ACHIEVEMENT_SYSTEM_COMPLETE",
    "MILESTONE_DOCUMENTATION\ADMIN_INTERFACE_COMPLETE",
    "MILESTONE_DOCUMENTATION\MOBILE_CONTROLS_COMPLETE"
)

foreach ($dir in $MilestoneDirectories) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force
        Write-Host "Created: $dir" -ForegroundColor Green
    }
}

# Create Development Tools structure
Write-Host "Creating Development Tools structure..." -ForegroundColor Cyan

$DevToolDirectories = @(
    "DEVELOPMENT_TOOLS\SCRIPTS",
    "DEVELOPMENT_TOOLS\TEMPLATES", 
    "DEVELOPMENT_TOOLS\WORKSPACE"
)

foreach ($dir in $DevToolDirectories) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force
        Write-Host "Created: $dir" -ForegroundColor Green
    }
}

# Create Archive structure
Write-Host "Creating Archive structure..." -ForegroundColor Cyan

$ArchiveDirectories = @(
    "ARCHIVE\LEGACY_FILES",
    "ARCHIVE\OLD_VERSIONS",
    "ARCHIVE\RETIRED_DOCUMENTATION"
)

foreach ($dir in $ArchiveDirectories) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force
        Write-Host "Created: $dir" -ForegroundColor Green
    }
}

# Create templates directory for future use
$TemplatesDir = "LAB_NOTES\TEMPLATES"
if (!(Test-Path $TemplatesDir)) {
    New-Item -ItemType Directory -Path $TemplatesDir -Force
    Write-Host "Created: $TemplatesDir" -ForegroundColor Green
}

Write-Host "Directory structure creation complete!" -ForegroundColor Green
Write-Host "Next: Run file migration script..." -ForegroundColor Cyan

Write-Host "PROFESSIONAL ORGANIZATION SYSTEM READY!" -ForegroundColor Magenta
Write-Host "All directories created successfully" -ForegroundColor Green
Write-Host "Ready for file migration phase..." -ForegroundColor Cyan
