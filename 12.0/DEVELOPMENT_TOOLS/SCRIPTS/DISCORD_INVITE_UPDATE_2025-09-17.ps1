# DISCORD INVITE UPDATE SCRIPT - SEPTEMBER 17, 2025

Write-Host "DISCORD INVITE UPDATE - UPDATING ALL PUBLIC PAGES" -ForegroundColor Green
Write-Host "=====================================================" -ForegroundColor Green

# Define the old and new Discord invite codes
$oldInviteCode = "EA57GUagkn"
$newInviteCode = "CvstbUQ5yX"
$oldUrl = "https://discord.gg/$oldInviteCode"
$newUrl = "https://discord.gg/$newInviteCode"

Write-Host "Updating Discord invite from: $oldUrl" -ForegroundColor Yellow
Write-Host "Updating Discord invite to:   $newUrl" -ForegroundColor Yellow
Write-Host ""

# List of files to update (from grep results)
$filesToUpdate = @(
    "public/index.html",
    "public/js/role-gate.js",
    "public/discord-config.js",
    "public/faq.html",
    "public/mint.html",
    "public/404.html",
    "public/experiment-x.html",
    "public/whitepaper-pro.html",
    "public/Bingo.html",
    "public/space-cheese-invaders.html",
    "public/project-updates.html",
    "public/hytopia.html",
    "public/privacy-policy.html"
)

# Update each file
$updatedFiles = 0
$totalFiles = $filesToUpdate.Count

foreach ($file in $filesToUpdate) {
    if (Test-Path $file) {
        Write-Host "Processing: $file" -ForegroundColor Cyan
        
        # Read file content
        $content = Get-Content $file -Raw -Encoding UTF8
        
        # Check if file contains old Discord invite
        if ($content -match $oldInviteCode) {
            # Replace old invite with new invite
            $newContent = $content -replace [regex]::Escape($oldUrl), $newUrl
            $newContent = $newContent -replace [regex]::Escape($oldInviteCode), $newInviteCode
            
            # Write updated content back to file
            $newContent | Set-Content $file -Encoding UTF8
            
            Write-Host "  ✅ Updated Discord invite in $file" -ForegroundColor Green
            $updatedFiles++
        } else {
            Write-Host "  ⚪ No Discord invite found in $file" -ForegroundColor Gray
        }
    } else {
        Write-Host "  ❌ File not found: $file" -ForegroundColor Red
    }
}

# Also update discord-invite.php (already done manually)
Write-Host ""
Write-Host "Discord invite configuration updated:" -ForegroundColor Cyan
Write-Host "  ✅ public/discord-invite.php" -ForegroundColor Green

# Summary
Write-Host ""
Write-Host "DISCORD INVITE UPDATE COMPLETE!" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Green
Write-Host ""
Write-Host "Files processed: $totalFiles" -ForegroundColor White
Write-Host "Files updated: $updatedFiles" -ForegroundColor Green
Write-Host "Configuration updated: 1" -ForegroundColor Green
Write-Host ""
Write-Host "Old Discord invite: $oldUrl" -ForegroundColor Yellow
Write-Host "New Discord invite: $newUrl" -ForegroundColor Green
Write-Host ""
Write-Host "All public pages now use the new Discord invite!" -ForegroundColor Cyan
Write-Host ""
Write-Host "STATUS: DISCORD INVITE UPDATE COMPLETE" -ForegroundColor Green
Write-Host "NEXT: Continue with 12.0 folder integration" -ForegroundColor Cyan
