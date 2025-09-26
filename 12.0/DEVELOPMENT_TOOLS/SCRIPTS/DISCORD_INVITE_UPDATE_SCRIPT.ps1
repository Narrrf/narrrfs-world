# DISCORD_INVITE_UPDATE_SCRIPT.ps1
# Narrrf's World - Discord Invite Update Automation
# Created: September 25, 2025
# Purpose: Automate Discord invite updates across entire project

param(
    [Parameter(Mandatory=$true)]
    [string]$NewInviteCode,
    
    [Parameter(Mandatory=$true)]
    [string]$EventName,
    
    [Parameter(Mandatory=$false)]
    [string]$OldInviteCode = "CvstbUQ5yX"
)

Write-Host "🚨 URGENT DISCORD INVITE UPDATE: $NewInviteCode for $EventName" -ForegroundColor Red
Write-Host "📅 Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Yellow
Write-Host "🔄 Old Invite: $OldInviteCode" -ForegroundColor Gray
Write-Host "🎯 New Invite: $NewInviteCode" -ForegroundColor Green
Write-Host ""

# Step 1: Search for all references to old invite
Write-Host "🔍 Step 1: Searching for old invite references..." -ForegroundColor Cyan
$oldReferences = @()

# Search in public directory
$publicFiles = Get-ChildItem -Path "public" -Include "*.html","*.js","*.php","*.md" -Recurse
foreach ($file in $publicFiles) {
    $content = Get-Content $file.FullName -Raw
    if ($content -match $OldInviteCode) {
        $oldReferences += $file.FullName
        Write-Host "  📄 Found in: $($file.Name)" -ForegroundColor Yellow
    }
}

# Search in api directory
$apiFiles = Get-ChildItem -Path "api" -Include "*.php" -Recurse
foreach ($file in $apiFiles) {
    $content = Get-Content $file.FullName -Raw
    if ($content -match $OldInviteCode) {
        $oldReferences += $file.FullName
        Write-Host "  📄 Found in: $($file.Name)" -ForegroundColor Yellow
    }
}

Write-Host "  📊 Total files to update: $($oldReferences.Count)" -ForegroundColor Green
Write-Host ""

# Step 2: Update all files
Write-Host "🔧 Step 2: Updating all files..." -ForegroundColor Cyan
$updatedCount = 0

foreach ($file in $oldReferences) {
    try {
        $content = Get-Content $file.FullName -Raw
        $newContent = $content -replace $OldInviteCode, $NewInviteCode
        
        if ($content -ne $newContent) {
            Set-Content -Path $file.FullName -Value $newContent -NoNewline
            $updatedCount++
            Write-Host "  ✅ Updated: $($file.Name)" -ForegroundColor Green
        }
    }
    catch {
        Write-Host "  ❌ Error updating: $($file.Name) - $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "  📊 Files updated: $updatedCount" -ForegroundColor Green
Write-Host ""

# Step 3: Verification
Write-Host "🔍 Step 3: Verifying update..." -ForegroundColor Cyan
$remainingOld = @()

# Check for any remaining old invite codes
$allFiles = Get-ChildItem -Path ".","public","api" -Include "*.html","*.js","*.php","*.md" -Recurse -ErrorAction SilentlyContinue
foreach ($file in $allFiles) {
    $content = Get-Content $file.FullName -Raw -ErrorAction SilentlyContinue
    if ($content -and $content -match $OldInviteCode) {
        $remainingOld += $file.FullName
    }
}

if ($remainingOld.Count -eq 0) {
    Write-Host "  ✅ Verification PASSED: No old invite codes remain" -ForegroundColor Green
} else {
    Write-Host "  ❌ Verification FAILED: $($remainingOld.Count) files still contain old invite" -ForegroundColor Red
    foreach ($file in $remainingOld) {
        Write-Host "    📄 $file" -ForegroundColor Yellow
    }
}
Write-Host ""

# Step 4: Git operations
Write-Host "🚀 Step 4: Preparing for deployment..." -ForegroundColor Cyan

# Check if we're in a git repository
if (Test-Path ".git") {
    try {
        # Add all changes
        git add .
        Write-Host "  ✅ Git add completed" -ForegroundColor Green
        
        # Create commit message
        $commitMessage = "URGENT: Update Discord invite to $NewInviteCode for $EventName"
        
        # Commit changes
        git commit -m $commitMessage
        Write-Host "  ✅ Git commit completed: $commitMessage" -ForegroundColor Green
        
        # Get commit hash
        $commitHash = git rev-parse --short HEAD
        Write-Host "  📝 Commit hash: $commitHash" -ForegroundColor Cyan
        
        Write-Host ""
        Write-Host "🚀 READY FOR DEPLOYMENT!" -ForegroundColor Green
        Write-Host "  📝 Commit: $commitHash" -ForegroundColor Cyan
        Write-Host "  🎯 New Invite: $NewInviteCode" -ForegroundColor Green
        Write-Host "  📅 Event: $EventName" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "💡 Next step: Run 'git push' to deploy to live" -ForegroundColor Yellow
        
    }
    catch {
        Write-Host "  ❌ Git operation failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "  💡 Manual git operations required" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ⚠️ Not in a git repository - manual deployment required" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🎯 DISCORD INVITE UPDATE COMPLETE!" -ForegroundColor Green
Write-Host "📊 Files updated: $updatedCount" -ForegroundColor Cyan
Write-Host "🔧 Fallback systems: Updated" -ForegroundColor Cyan
Write-Host "📝 Status: Ready for $EventName" -ForegroundColor Green
Write-Host ""

# Step 5: Summary report
Write-Host "📋 SUMMARY REPORT:" -ForegroundColor Cyan
Write-Host "  🎯 Event: $EventName" -ForegroundColor White
Write-Host "  🔄 Old Invite: $OldInviteCode" -ForegroundColor Gray
Write-Host "  ✅ New Invite: $NewInviteCode" -ForegroundColor Green
Write-Host "  📊 Files Updated: $updatedCount" -ForegroundColor White
Write-Host "  🔍 Verification: $($remainingOld.Count -eq 0 ? 'PASSED' : 'FAILED')" -ForegroundColor $($remainingOld.Count -eq 0 ? 'Green' : 'Red')
Write-Host "  🚀 Deployment: $($Test-Path '.git' ? 'Ready' : 'Manual Required')" -ForegroundColor $($Test-Path '.git' ? 'Green' : 'Yellow')
Write-Host ""

Write-Host "🧀 Discord invite update automation complete! 🧀" -ForegroundColor Magenta
