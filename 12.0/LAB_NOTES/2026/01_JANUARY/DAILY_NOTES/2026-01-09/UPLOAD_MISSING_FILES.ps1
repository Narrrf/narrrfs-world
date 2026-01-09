# 🚀 UPLOAD MISSING CRITICAL FILES TO RENDER
# Created: January 9, 2026
# Purpose: Upload the 4 missing critical files causing 404 errors
# Run from Windows PowerShell

param(
    [Parameter(Mandatory=$false)]
    [string]$BotSecret = ""
)

# Get bot secret from parameter or environment
if ([string]::IsNullOrEmpty($BotSecret)) {
    # Try to get from .env file
    if (Test-Path "discord\.env") {
        $envContent = Get-Content "discord\.env"
        foreach ($line in $envContent) {
            if ($line -match "DISCORD_BOT_SECRET=(.+)") {
                $BotSecret = $matches[1]
                break
            }
            if ($line -match "DISCORD_SECRET=(.+)") {
                $BotSecret = $matches[1]
                break
            }
        }
    }
    
    # Try to get from config.js
    if ([string]::IsNullOrEmpty($BotSecret) -and (Test-Path "discord\config.js")) {
        $configContent = Get-Content "discord\config.js" -Raw
        if ($configContent -match "botToken['\"]:\s*['\"]([^'\"]+)['\"]") {
            $BotSecret = $matches[1]
        }
    }
}

# Verify bot secret
if ([string]::IsNullOrEmpty($BotSecret)) {
    Write-Host "❌ ERROR: Discord bot secret not found!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Usage: .\UPLOAD_MISSING_FILES.ps1 -BotSecret 'YOUR_SECRET'" -ForegroundColor Yellow
    Write-Host "Or set it in discord\.env file as DISCORD_BOT_SECRET=..." -ForegroundColor Yellow
    exit 1
}

Write-Host "🚀 Uploading Missing Critical Files to Render" -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green
Write-Host ""

$basePath = $PSScriptRoot
if ([string]::IsNullOrEmpty($basePath)) {
    $basePath = Get-Location
}

# Change to project root
$projectRoot = Join-Path $basePath "..\..\..\..\.."
Set-Location $projectRoot

Write-Host "📁 Project root: $projectRoot" -ForegroundColor Cyan
Write-Host "🔑 Using bot secret: $($BotSecret.Substring(0, 10))..." -ForegroundColor Cyan
Write-Host ""

$uploadUrl = "https://narrrfs.world/api/discord/upload-assets.php"
$files = @(
    @{
        Name = "grass.jpg"
        LocalPath = "public\textures\grass\grass.jpg"
        TargetPath = "/data/public/three.js/public/textures/grass/grass.jpg"
    },
    @{
        Name = "cloud.jpg"
        LocalPath = "public\textures\grass\cloud.jpg"
        TargetPath = "/data/public/three.js/public/textures/grass/cloud.jpg"
    },
    @{
        Name = "cheesetemple1.png"
        LocalPath = "public\textures\backgrounds\cheesetemple1.png"
        TargetPath = "/data/public/three.js/public/textures/backgrounds/cheesetemple1.png"
    },
    @{
        Name = "level1.json"
        LocalPath = "public\models\cheese-temple\level1.json"
        TargetPath = "/data/public/three.js/public/models/cheese-temple/level1.json"
    }
)

$successCount = 0
$failCount = 0

foreach ($file in $files) {
    $localPath = Join-Path $projectRoot $file.LocalPath
    
    if (-not (Test-Path $localPath)) {
        Write-Host "❌ MISSING: $($file.Name) not found at $localPath" -ForegroundColor Red
        $failCount++
        continue
    }
    
    Write-Host "📤 Uploading: $($file.Name)..." -ForegroundColor Yellow
    
    try {
        $response = curl.exe -X POST `
            -H "Authorization: $BotSecret" `
            -F "file=@$localPath" `
            -F "target_path=$($file.TargetPath)" `
            $uploadUrl `
            2>&1
        
        $responseString = $response -join "`n"
        
        if ($responseString -match '"success"\s*:\s*true') {
            Write-Host "✅ SUCCESS: $($file.Name) uploaded" -ForegroundColor Green
            $successCount++
        } else {
            Write-Host "❌ FAILED: $($file.Name) - $responseString" -ForegroundColor Red
            $failCount++
        }
    } catch {
        Write-Host "❌ ERROR: $($file.Name) - $($_.Exception.Message)" -ForegroundColor Red
        $failCount++
    }
    
    Write-Host ""
}

Write-Host "==============================================" -ForegroundColor Green
Write-Host "📊 Upload Summary:" -ForegroundColor Cyan
Write-Host "   ✅ Success: $successCount files" -ForegroundColor Green
Write-Host "   ❌ Failed: $failCount files" -ForegroundColor $(if ($failCount -gt 0) { "Red" } else { "Green" })
Write-Host ""

if ($successCount -eq $files.Count) {
    Write-Host "✅ All files uploaded successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Verify files in Render shell:" -ForegroundColor Yellow
    Write-Host "   ls -la /data/public/three.js/public/textures/grass/grass.jpg" -ForegroundColor Cyan
    Write-Host "   ls -la /data/public/three.js/public/models/cheese-temple/level1.json" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "2. Test game in browser:" -ForegroundColor Yellow
    Write-Host "   https://narrrfs.world/public/three.js/3d-riddle-game.html" -ForegroundColor Cyan
} else {
    Write-Host "⚠️ Some files failed to upload. Check errors above." -ForegroundColor Yellow
}
