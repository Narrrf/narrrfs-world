# 🚀 UPLOAD ALL THREE.JS ASSETS TO RENDER - URGENT
# Created: January 9, 2026
# Purpose: Upload ALL local assets to Render /data/ persistent storage
# This will upload all 1,499+ files from public/three.js/public/ to /data/public/three.js/public/
# Run from local Windows PowerShell with Discord bot secret

param(
    [Parameter(Mandatory=$false)]
    [string]$BotSecret = ""
)

Write-Host "🚀 URGENT: UPLOADING ALL THREE.JS ASSETS TO RENDER" -ForegroundColor Green
Write-Host "===================================================" -ForegroundColor Green
Write-Host "⚠️  This will upload ALL files (1,499+ files)" -ForegroundColor Yellow
Write-Host "⚠️  Estimated time: 30-60 minutes depending on file sizes" -ForegroundColor Yellow
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
# Exclude archive files and non-essential files
$allFiles = Get-ChildItem -Path $localBaseDir -Recurse -File | Where-Object { 
    $_.FullName -notmatch "\.tar\.gz$" -and 
    $_.FullName -notmatch "\.rar$" -and 
    $_.FullName -notmatch "\.pdf$" -and 
    $_.FullName -notmatch "\.url$" -and
    $_.FullName -notmatch "License\.txt" -and
    $_.FullName -notmatch "Preview\.ogg"
}
$totalFiles = $allFiles.Count
Write-Host "✅ Found $totalFiles files to upload" -ForegroundColor Green
Write-Host ""

# Calculate total size
$totalSizeMB = [math]::Round(($allFiles | Measure-Object -Property Length -Sum).Sum / 1MB, 2)
Write-Host "📊 Total size: $totalSizeMB MB" -ForegroundColor Cyan
Write-Host ""

# Confirm before proceeding
$confirmation = Read-Host "Continue with upload? (Y/N)"
if ($confirmation -ne "Y" -and $confirmation -ne "y") {
    Write-Host "❌ Upload cancelled by user" -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "🚀 Starting upload..." -ForegroundColor Green
Write-Host ""

# Track statistics
$uploadedCount = 0
$failedCount = 0
$errors = @()
$startTime = Get-Date

# Function to upload a single file
function Upload-File {
    param(
        [System.IO.FileInfo]$File,
        [string]$RemotePath
    )
    
    $fileName = $File.Name
    $fileSizeMB = [math]::Round($File.Length / 1MB, 2)
    
    Write-Host "  📤 Uploading: $fileName" -ForegroundColor Yellow
    Write-Host "     Size: $fileSizeMB MB | To: $RemotePath" -ForegroundColor Gray
    
    try {
        $response = curl.exe -X POST `
            -H "Authorization: $BotSecret" `
            -F "file=@$($File.FullName)" `
            -F "target_path=$RemotePath" `
            $uploadUrl `
            2>&1
        
        $responseString = $response -join "`n"
        
        if ($responseString -match '"success"\s*:\s*true') {
            Write-Host "     ✅ SUCCESS" -ForegroundColor Green
            return $true
        } else {
            Write-Host "     ❌ FAILED: $responseString" -ForegroundColor Red
            $script:errors += "$fileName: $responseString"
            return $false
        }
    } catch {
        Write-Host "     ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
        $script:errors += "$fileName: $($_.Exception.Message)"
        return $false
    }
}

# Process all files
$processed = 0
foreach ($file in $allFiles) {
    $processed++
    $relativePath = $file.FullName.Replace((Join-Path $projectRoot $localBaseDir), "").Replace("\", "/").TrimStart("/")
    $remotePath = "$remoteBaseDir/$relativePath"
    
    $progressPercent = [math]::Round(($processed / $totalFiles) * 100, 1)
    $elapsed = (Get-Date) - $startTime
    $avgTimePerFile = if ($processed -gt 0) { $elapsed.TotalSeconds / $processed } else { 0 }
    $remainingFiles = $totalFiles - $processed
    $estimatedRemaining = [TimeSpan]::FromSeconds($avgTimePerFile * $remainingFiles)
    
    Write-Host "[$processed/$totalFiles] ($progressPercent%) | ✅ $uploadedCount | ❌ $failedCount | Elapsed: $($elapsed.ToString('mm\:ss')) | Est: $($estimatedRemaining.ToString('mm\:ss'))" -ForegroundColor Cyan
    
    # Upload file
    $result = Upload-File -File $file -RemotePath $remotePath
    
    if ($result) {
        $uploadedCount++
    } else {
        $failedCount++
        # If upload fails, wait a bit longer before retrying next file
        Start-Sleep -Milliseconds 500
    }
    
    # Small delay to avoid overwhelming the server (reduced for speed)
    Start-Sleep -Milliseconds 50
    
    # Progress update every 25 files
    if ($processed % 25 -eq 0) {
        Write-Host ""
        Write-Host "📊 Progress: ✅ $uploadedCount uploaded | ❌ $failedCount failed | Elapsed: $($elapsed.ToString('mm\:ss'))" -ForegroundColor Cyan
        Write-Host ""
    }
}

$totalTime = (Get-Date) - $startTime

Write-Host ""
Write-Host "========================================================" -ForegroundColor Green
Write-Host "📊 UPLOAD SUMMARY" -ForegroundColor Cyan
Write-Host "   Total Files: $totalFiles" -ForegroundColor White
Write-Host "   ✅ Uploaded: $uploadedCount files" -ForegroundColor Green
Write-Host "   ❌ Failed: $failedCount files" -ForegroundColor $(if ($failedCount -gt 0) { "Red" } else { "Green" })
Write-Host "   ⏱️  Total Time: $($totalTime.ToString('mm\:ss'))" -ForegroundColor White
Write-Host ""

if ($errors.Count -gt 0 -and $errors.Count -le 20) {
    Write-Host "❌ ERRORS (first 20):" -ForegroundColor Red
    foreach ($error in $errors[0..([Math]::Min(19, $errors.Count-1))]) {
        Write-Host "   - $error" -ForegroundColor Red
    }
    if ($errors.Count -gt 20) {
        Write-Host "   ... and $($errors.Count - 20) more errors" -ForegroundColor Red
    }
    Write-Host ""
}

if ($failedCount -eq 0) {
    Write-Host "✅ ALL FILES UPLOADED SUCCESSFULLY!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Deploy updated 'scripts/render-startup.sh' to Render (if not already deployed)" -ForegroundColor Yellow
    Write-Host "2. Run startup script on Render to create symlinks:" -ForegroundColor Yellow
    Write-Host "   bash /var/www/html/scripts/render-startup.sh" -ForegroundColor Cyan
    Write-Host "3. Verify files on Render:" -ForegroundColor Yellow
    Write-Host "   ls -la /data/public/three.js/public/ | head -20" -ForegroundColor Cyan
    Write-Host "   find /data/public/three.js/public/ -type f | wc -l" -ForegroundColor Cyan
    Write-Host "4. Test game: https://narrrfs.world/public/three.js/3d-riddle-game.html" -ForegroundColor Cyan
} else {
    Write-Host "⚠️ Some files failed to upload ($failedCount/$totalFiles)" -ForegroundColor Yellow
    Write-Host "   Check errors above and retry failed files" -ForegroundColor Yellow
}
