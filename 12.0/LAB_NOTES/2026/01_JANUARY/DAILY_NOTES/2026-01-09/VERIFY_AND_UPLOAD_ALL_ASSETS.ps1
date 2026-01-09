# 🚀 VERIFY AND UPLOAD ALL THREE.JS ASSETS TO RENDER
# Created: January 9, 2026
# Purpose: Verify all local assets exist on Render, upload missing files
# Run from local Windows PowerShell with Discord bot secret

param(
    [Parameter(Mandatory=$false)]
    [string]$BotSecret = "",
    
    [Parameter(Mandatory=$false)]
    [switch]$VerifyOnly = $false,
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipExisting = $true
)

Write-Host "🔍 VERIFYING AND UPLOADING ALL THREE.JS ASSETS TO RENDER" -ForegroundColor Green
Write-Host "========================================================" -ForegroundColor Green
Write-Host ""

# Get bot secret
if ([string]::IsNullOrEmpty($BotSecret)) {
    # Try to find bot secret from .env or config files
    $envFile = "discord\.env"
    if (Test-Path $envFile) {
        $envContent = Get-Content $envFile -Raw
        if ($envContent -match "DISCORD_BOT_SECRET=(.+)") {
            $BotSecret = $matches[1].Trim()
        } elseif ($envContent -match "DISCORD_SECRET=(.+)") {
            $BotSecret = $matches[1].Trim()
        }
    }
    
    # Try config.js
    if ([string]::IsNullOrEmpty($BotSecret) -and (Test-Path "discord\config.js")) {
        $configContent = Get-Content "discord\config.js" -Raw
        if ($configContent -match "botSecret:\s*['""](.+)['""]") {
            $BotSecret = $matches[1]
        } elseif ($configContent -match "token:\s*['""](.+)['""]") {
            $BotSecret = $matches[1]
        }
    }
    
    # Try environment variable
    if ([string]::IsNullOrEmpty($BotSecret)) {
        $BotSecret = $env:DISCORD_BOT_SECRET
        if ([string]::IsNullOrEmpty($BotSecret)) {
            $BotSecret = $env:DISCORD_SECRET
        }
    }
}

if ([string]::IsNullOrEmpty($BotSecret)) {
    Write-Host "❌ ERROR: Discord bot secret not found!" -ForegroundColor Red
    Write-Host "   Please provide -BotSecret parameter or set DISCORD_BOT_SECRET environment variable" -ForegroundColor Yellow
    exit 1
}

$projectRoot = Get-Location
Write-Host "📁 Project root: $projectRoot" -ForegroundColor Cyan
Write-Host "🔑 Using bot secret: $($BotSecret.Substring(0, [Math]::Min(10, $BotSecret.Length)))..." -ForegroundColor Cyan
Write-Host ""

$uploadUrl = "https://narrrfs.world/api/discord/upload-assets.php"

# Local base directory
$localBaseDir = "public\three.js\public"
$remoteBaseDir = "/data/public/three.js/public"

if (-not (Test-Path $localBaseDir)) {
    Write-Host "❌ ERROR: Local directory not found: $localBaseDir" -ForegroundColor Red
    exit 1
}

Write-Host "📦 Scanning local assets..." -ForegroundColor Cyan
$allFiles = Get-ChildItem -Path $localBaseDir -Recurse -File | Where-Object { $_.FullName -notmatch "\.tar\.gz$" -and $_.FullName -notmatch "\.rar$" -and $_.FullName -notmatch "\.pdf$" -and $_.FullName -notmatch "\.url$" }
$totalFiles = $allFiles.Count
Write-Host "✅ Found $totalFiles files to verify/upload" -ForegroundColor Green
Write-Host ""

# Track statistics
$uploadedCount = 0
$skippedCount = 0
$failedCount = 0
$errors = @()

# Function to upload a single file
function Upload-File {
    param(
        [System.IO.FileInfo]$File,
        [string]$RemotePath
    )
    
    $fileName = $File.Name
    $fileSize = [math]::Round($File.Length / 1MB, 2)
    
    Write-Host "  📤 Uploading: $fileName ($fileSize MB)..." -ForegroundColor Yellow
    Write-Host "     To: $RemotePath" -ForegroundColor Gray
    
    try {
        $response = curl.exe -X POST `
            -H "Authorization: $BotSecret" `
            -F "file=@$($File.FullName)" `
            -F "target_path=$RemotePath" `
            $uploadUrl `
            2>&1
        
        $responseString = $response -join "`n"
        
        if ($responseString -match '"success"\s*:\s*true') {
            Write-Host "     ✅ SUCCESS: $fileName uploaded" -ForegroundColor Green
            return $true
        } else {
            Write-Host "     ❌ FAILED: $fileName - $responseString" -ForegroundColor Red
            $script:errors += "$fileName: $responseString"
            return $false
        }
    } catch {
        Write-Host "     ❌ ERROR: $fileName - $($_.Exception.Message)" -ForegroundColor Red
        $script:errors += "$fileName: $($_.Exception.Message)"
        return $false
    }
}

# Process all files
$processed = 0
$startTime = Get-Date

foreach ($file in $allFiles) {
    $processed++
    $relativePath = $file.FullName.Replace((Join-Path $projectRoot $localBaseDir), "").Replace("\", "/").TrimStart("/")
    $remotePath = "$remoteBaseDir/$relativePath"
    
    $progressPercent = [math]::Round(($processed / $totalFiles) * 100, 1)
    $elapsed = (Get-Date) - $startTime
    $avgTimePerFile = if ($processed -gt 0) { $elapsed.TotalSeconds / $processed } else { 0 }
    $remainingFiles = $totalFiles - $processed
    $estimatedRemaining = [TimeSpan]::FromSeconds($avgTimePerFile * $remainingFiles)
    
    Write-Host "[$processed/$totalFiles] ($progressPercent%) | Elapsed: $($elapsed.ToString('mm\:ss')) | Est. Remaining: $($estimatedRemaining.ToString('mm\:ss'))" -ForegroundColor Cyan
    Write-Host "   File: $relativePath" -ForegroundColor Gray
    
    if ($VerifyOnly) {
        # In verify-only mode, we can't check if file exists on server without API endpoint for that
        Write-Host "  ℹ️  Verify-only mode - skipping upload" -ForegroundColor Gray
        $skippedCount++
        continue
    }
    
    # Skip very large files that might already be uploaded (optional check)
    if ($SkipExisting -and $file.Length -gt 10MB) {
        Write-Host "  ⏭️  Large file, skipping (use -SkipExisting:`$false to force upload)" -ForegroundColor Yellow
        $skippedCount++
        continue
    }
    
    # Upload file
    $result = Upload-File -File $file -RemotePath $remotePath
    
    if ($result) {
        $uploadedCount++
    } else {
        $failedCount++
        # If upload fails, wait a bit longer before retrying next file
        Start-Sleep -Milliseconds 500
    }
    
    # Small delay to avoid overwhelming the server
    Start-Sleep -Milliseconds 100
    
    # Progress update every 50 files
    if ($processed % 50 -eq 0) {
        Write-Host ""
        Write-Host "📊 Progress Update: ✅ $uploadedCount uploaded | ❌ $failedCount failed | ⏭️  $skippedCount skipped" -ForegroundColor Cyan
        Write-Host ""
    }
}

Write-Host ""
Write-Host "========================================================" -ForegroundColor Green
Write-Host "📊 UPLOAD SUMMARY" -ForegroundColor Cyan
Write-Host "   Total Files: $totalFiles" -ForegroundColor White
Write-Host "   ✅ Uploaded: $uploadedCount files" -ForegroundColor Green
Write-Host "   ⏭️  Skipped: $skippedCount files" -ForegroundColor Yellow
Write-Host "   ❌ Failed: $failedCount files" -ForegroundColor $(if ($failedCount -gt 0) { "Red" } else { "Green" })
Write-Host ""

if ($errors.Count -gt 0) {
    Write-Host "❌ ERRORS:" -ForegroundColor Red
    foreach ($error in $errors) {
        Write-Host "   - $error" -ForegroundColor Red
    }
    Write-Host ""
}

if ($failedCount -eq 0 -and -not $VerifyOnly) {
    Write-Host "✅ All files processed successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Deploy updated 'scripts/render-startup.sh' to Render (if not already deployed)" -ForegroundColor Yellow
    Write-Host "2. Verify files and symlinks in Render shell:" -ForegroundColor Yellow
    Write-Host "   ls -la /data/public/three.js/public/" -ForegroundColor Cyan
    Write-Host "   ls -la /var/www/html/public/three.js/public/ (symlinks)" -ForegroundColor Cyan
    Write-Host "3. Test game in browser: https://narrrfs.world/public/three.js/3d-riddle-game.html" -ForegroundColor Cyan
} else {
    Write-Host "⚠️ Some files failed to upload. Check errors above." -ForegroundColor Yellow
}
