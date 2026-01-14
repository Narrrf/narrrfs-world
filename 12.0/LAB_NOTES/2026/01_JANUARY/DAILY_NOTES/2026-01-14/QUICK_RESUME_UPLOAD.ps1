# ⚡ QUICK RESUME - SKIP FILE EXISTENCE CHECK (FASTER)
# Created: January 14, 2026
# Purpose: Resume upload without checking if files exist (faster, assumes previous uploads succeeded)
# Use this if the file existence check is slow or causing issues

param(
    [Parameter(Mandatory=$false)]
    [string]$BotSecret = "",
    
    [Parameter(Mandatory=$false)]
    [int]$DaysAgo = 1,
    
    [Parameter(Mandatory=$false)]
    [switch]$Force = $false
)

Write-Host "[QUICK RESUME] RESUMING UPLOAD (SKIPPING FILE EXISTENCE CHECK)" -ForegroundColor Green
Write-Host "=============================================================" -ForegroundColor Green
Write-Host "Uploading files modified in last $DaysAgo day(s)" -ForegroundColor Yellow
Write-Host "NOTE: Skipping file existence check for faster upload" -ForegroundColor Cyan
Write-Host ""

# Get bot secret
if ([string]::IsNullOrEmpty($BotSecret)) {
    $envFile = "discord\.env"
    if (Test-Path $envFile) {
        $envContent = Get-Content $envFile -Raw
        if ($envContent -match "DISCORD_BOT_SECRET=(.+)") {
            $BotSecret = $matches[1].Trim()
        } elseif ($envContent -match "DISCORD_SECRET=(.+)") {
            $BotSecret = $matches[1].Trim()
        }
    }
    
    if ([string]::IsNullOrEmpty($BotSecret) -and (Test-Path "discord\config.js")) {
        $configContent = Get-Content "discord\config.js" -Raw
        if ($configContent -match "botSecret:\s*['""](.+)['""]") {
            $BotSecret = $matches[1]
        } elseif ($configContent -match "token:\s*['""](.+)['""]") {
            $BotSecret = $matches[1]
        }
    }
    
    if ([string]::IsNullOrEmpty($BotSecret)) {
        $BotSecret = $env:DISCORD_BOT_SECRET
        if ([string]::IsNullOrEmpty($BotSecret)) {
            $BotSecret = $env:DISCORD_SECRET
        }
    }
}

if ([string]::IsNullOrEmpty($BotSecret)) {
    Write-Host "ERROR: Discord bot secret not found!" -ForegroundColor Red
    Write-Host "   Please provide -BotSecret parameter or set DISCORD_BOT_SECRET environment variable" -ForegroundColor Yellow
    exit 1
}

$projectRoot = Get-Location
Write-Host "Project root: $projectRoot" -ForegroundColor Cyan
Write-Host "Using bot secret: $($BotSecret.Substring(0, [Math]::Min(10, $BotSecret.Length)))..." -ForegroundColor Cyan
Write-Host ""

$uploadUrl = "https://narrrfs.world/api/discord/upload-assets.php"

# Local base directory
$localBaseDir = "public\three.js\public\textures\3d models"
$remoteBaseDir = "/data/public/three.js/public/textures/3d models"

if (-not (Test-Path $localBaseDir)) {
    Write-Host "ERROR: Local directory not found: $localBaseDir" -ForegroundColor Red
    exit 1
}

Write-Host "Scanning for new files (modified in last $DaysAgo day(s))..." -ForegroundColor Cyan

# Calculate cutoff date
$cutoffDate = (Get-Date).AddDays(-$DaysAgo)

# Get all files modified after cutoff date
$newFiles = Get-ChildItem -Path $localBaseDir -Recurse -File | Where-Object { 
    $_.LastWriteTime -ge $cutoffDate -and
    $_.FullName -notmatch "\.tar\.gz$" -and 
    $_.FullName -notmatch "\.rar$" -and 
    $_.FullName -notmatch "\.pdf$" -and 
    $_.FullName -notmatch "\.url$" -and
    $_.FullName -notmatch "License\.txt" -and
    $_.FullName -notmatch "Preview\.ogg" -and
    $_.FullName -notmatch "\.zip$" -and
    $_.FullName -notmatch "\.psd$" -and
    $_.FullName -notmatch "\.ma$" -and
    $_.FullName -notmatch "\.unitypackage$" -and
    $_.FullName -notmatch "\.uproject$" -and
    $_.FullName -notmatch "\.uasset$" -and
    $_.FullName -notmatch "\.umap$"
}

$totalFiles = $newFiles.Count

if ($totalFiles -eq 0) {
    Write-Host "No new files found in last $DaysAgo day(s)" -ForegroundColor Yellow
    Write-Host 'Try increasing -DaysAgo parameter (e.g., use -DaysAgo 7)' -ForegroundColor Yellow
    exit 0
}

Write-Host "Found $totalFiles new files to upload" -ForegroundColor Green
Write-Host "NOTE: Not checking if files exist - will attempt upload for all files" -ForegroundColor Yellow
Write-Host "      (API will handle duplicates gracefully)" -ForegroundColor Yellow
Write-Host ""

# Calculate total size
$totalSizeMB = [math]::Round(($newFiles | Measure-Object -Property Length -Sum).Sum / 1MB, 2)
Write-Host "Total size to upload: $totalSizeMB MB" -ForegroundColor Cyan
Write-Host ""

# Confirm before proceeding (skip if Force flag is set)
if (-not $Force) {
    $confirmation = Read-Host "Continue with upload? (Y/N)"
    if ($confirmation -ne "Y" -and $confirmation -ne "y") {
        Write-Host "Upload cancelled by user" -ForegroundColor Yellow
        exit 0
    }
} else {
    Write-Host "Force flag set - skipping confirmation prompt" -ForegroundColor Yellow
    Write-Host ""
}

Write-Host ""
Write-Host "Starting upload..." -ForegroundColor Green
Write-Host ""

# Progress log file
$progressLogFile = "12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-14\upload_progress.log"
$lastProgressFile = "12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-14\upload_last_file.txt"

# Track statistics
$uploadedCount = 0
$failedCount = 0
$skippedCount = 0
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
    
    Write-Host "  Uploading: $fileName" -ForegroundColor Yellow
    Write-Host "     Size: $fileSizeMB MB | To: $RemotePath" -ForegroundColor Gray
    
    try {
        # Set timeout to 10 minutes per file (600 seconds) for very large files
        # Use --connect-timeout to fail fast if connection can't be established
        $response = curl.exe --max-time 600 --connect-timeout 30 -X POST `
            -H "Authorization: $BotSecret" `
            -F "file=@$($File.FullName)" `
            -F "target_path=$RemotePath" `
            $uploadUrl `
            2>&1
        
        $responseString = $response -join "`n"
        
        # Check for timeout errors
        if ($responseString -match "timeout|timed out|Operation timed out") {
            Write-Host "     [TIMEOUT] Upload timed out after 10 minutes" -ForegroundColor Red
            $script:errors += "${fileName}: Upload timed out"
            return $false
        }
        
        # Check if file already exists (409 or similar)
        if ($responseString -match "already exists|duplicate|409") {
            Write-Host "     [SKIP] File already exists on server" -ForegroundColor Cyan
            $script:skippedCount++
            return $true  # Count as success since file is already there
        }
        
        if ($responseString -match '"success"\s*:\s*true') {
            Write-Host "     [OK] SUCCESS" -ForegroundColor Green
            return $true
        } else {
            Write-Host "     [FAIL] FAILED: $responseString" -ForegroundColor Red
            $script:errors += "${fileName}: $responseString"
            return $false
        }
    } catch {
        Write-Host "     [ERROR] ERROR: $($_.Exception.Message)" -ForegroundColor Red
        $script:errors += "${fileName}: $($_.Exception.Message)"
        return $false
    }
}

# Process all files
$processed = 0
foreach ($file in $newFiles) {
    $processed++
    $relativePath = $file.FullName.Replace((Join-Path $projectRoot $localBaseDir), "").Replace("\", "/").TrimStart("/")
    $remotePath = "$remoteBaseDir/$relativePath"
    
    # Log current file to resume file
    "$relativePath" | Out-File -FilePath $lastProgressFile -Encoding UTF8
    
    $progressPercent = [math]::Round(($processed / $totalFiles) * 100, 1)
    $elapsed = (Get-Date) - $startTime
    $avgTimePerFile = if ($processed -gt 0) { $elapsed.TotalSeconds / $processed } else { 0 }
    $remainingFiles = $totalFiles - $processed
    $estimatedRemaining = [TimeSpan]::FromSeconds($avgTimePerFile * $remainingFiles)
    
    $progressLine = "[$processed/$totalFiles] ($progressPercent%) | OK: $uploadedCount | SKIP: $skippedCount | FAIL: $failedCount | Elapsed: $($elapsed.ToString('mm\:ss')) | Est: $($estimatedRemaining.ToString('mm\:ss')) | File: $($file.Name)"
    Write-Host $progressLine -ForegroundColor Cyan
    "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - $progressLine" | Out-File -FilePath $progressLogFile -Append -Encoding UTF8
    
    # Upload file with timeout protection
    $uploadStartTime = Get-Date
    $result = Upload-File -File $file -RemotePath $remotePath
    $uploadDuration = (Get-Date) - $uploadStartTime
    
    # Log upload result
    if ($result) {
        $uploadedCount++
        "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - [OK] Uploaded: $relativePath (took $($uploadDuration.TotalSeconds.ToString('F1'))s)" | Out-File -FilePath $progressLogFile -Append -Encoding UTF8
    } else {
        $failedCount++
        "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - [FAIL] Failed: $relativePath (took $($uploadDuration.TotalSeconds.ToString('F1'))s)" | Out-File -FilePath $progressLogFile -Append -Encoding UTF8
        # If upload fails, wait a bit longer before retrying next file
        Start-Sleep -Milliseconds 2000
    }
    
    # Small delay to avoid overwhelming the server
    Start-Sleep -Milliseconds 200
    
    # Progress update every 5 files (more frequent updates)
    if ($processed % 5 -eq 0) {
        Write-Host ""
        Write-Host "Progress: OK: $uploadedCount uploaded | SKIP: $skippedCount skipped | FAIL: $failedCount failed | Elapsed: $($elapsed.ToString('mm\:ss'))" -ForegroundColor Cyan
        Write-Host ""
    }
}

$totalTime = (Get-Date) - $startTime

Write-Host ""
Write-Host "========================================================" -ForegroundColor Green
Write-Host "UPLOAD SUMMARY" -ForegroundColor Cyan
Write-Host "   Total Files: $totalFiles" -ForegroundColor White
Write-Host "   [OK] Uploaded: $uploadedCount files" -ForegroundColor Green
Write-Host "   [SKIP] Skipped (already exist): $skippedCount files" -ForegroundColor Cyan
Write-Host "   [FAIL] Failed: $failedCount files" -ForegroundColor $(if ($failedCount -gt 0) { "Red" } else { "Green" })
Write-Host "   Total Time: $($totalTime.ToString('mm\:ss'))" -ForegroundColor White
Write-Host ""

if ($errors.Count -gt 0 -and $errors.Count -le 20) {
    Write-Host "ERRORS (first 20):" -ForegroundColor Red
    foreach ($error in $errors[0..([Math]::Min(19, $errors.Count-1))]) {
        Write-Host "   - $error" -ForegroundColor Red
    }
    if ($errors.Count -gt 20) {
        Write-Host "   ... and $($errors.Count - 20) more errors" -ForegroundColor Red
    }
    Write-Host ""
}

if ($failedCount -eq 0) {
    Write-Host "[OK] ALL FILES UPLOADED SUCCESSFULLY!" -ForegroundColor Green
    Write-Host ""
    Write-Host "[NEXT] Next steps:" -ForegroundColor Yellow
    Write-Host "1. Symlinks are created automatically by the startup script on deployment" -ForegroundColor Cyan
    Write-Host "2. Verify files on Render and test web access" -ForegroundColor Cyan
} else {
    Write-Host "[WARN] Some files failed to upload ($failedCount/$totalFiles)" -ForegroundColor Yellow
    Write-Host "   Check errors above and retry failed files" -ForegroundColor Yellow
    Write-Host "   Progress log saved to: $progressLogFile" -ForegroundColor Cyan
}
