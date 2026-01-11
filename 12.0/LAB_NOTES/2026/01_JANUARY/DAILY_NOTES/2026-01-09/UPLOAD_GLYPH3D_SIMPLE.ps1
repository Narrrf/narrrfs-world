# UPLOAD GLYPH3D FILES TO RENDER
# Simple version based on working upload scripts
param(
    [Parameter(Mandatory=$false)]
    [string]$BotSecret = ""
)

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
}

if ([string]::IsNullOrEmpty($BotSecret)) {
    Write-Host "ERROR: Discord bot secret not found!" -ForegroundColor Red
    exit 1
}

$projectRoot = Get-Location
$uploadUrl = "https://narrrfs.world/api/discord/upload-assets.php"
$localDir = "public\glyph\glyph3d"
$remoteDir = "/data/public/glyph/glyph3d"

Write-Host "Uploading Glyph3d files to Render" -ForegroundColor Green
Write-Host "Project: $projectRoot" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path $localDir)) {
    Write-Host "ERROR: Directory not found: $localDir" -ForegroundColor Red
    exit 1
}

$allFiles = Get-ChildItem -Path $localDir -File
$totalFiles = $allFiles.Count
Write-Host "Found $totalFiles files to upload" -ForegroundColor Green
Write-Host ""

$uploadedCount = 0
$failedCount = 0
$processed = 0

foreach ($file in $allFiles) {
    $processed++
    $fileName = $file.Name
    $fileSizeMB = [math]::Round($file.Length / 1MB, 2)
    $remotePath = "$remoteDir/$fileName"
    
    Write-Host "[$processed/$totalFiles] Uploading: $fileName ($fileSizeMB MB)..." -ForegroundColor Yellow
    
    try {
        $response = curl.exe -X POST `
            -H "Authorization: $BotSecret" `
            -F "file=@$($file.FullName)" `
            -F "target_path=$remotePath" `
            $uploadUrl `
            2>&1
        
        $responseString = $response -join "`n"
        
        if ($responseString -match '"success"\s*:\s*true') {
            Write-Host "  SUCCESS" -ForegroundColor Green
            $uploadedCount++
        } else {
            Write-Host "  FAILED: $responseString" -ForegroundColor Red
            $failedCount++
        }
    } catch {
        Write-Host "  ERROR: $($_.Exception.Message)" -ForegroundColor Red
        $failedCount++
    }
    
    Start-Sleep -Milliseconds 100
}

Write-Host ""
Write-Host "UPLOAD SUMMARY" -ForegroundColor Cyan
Write-Host "  Uploaded: $uploadedCount/$totalFiles" -ForegroundColor $(if ($failedCount -eq 0) { "Green" } else { "Yellow" })
Write-Host "  Failed: $failedCount/$totalFiles" -ForegroundColor $(if ($failedCount -gt 0) { "Red" } else { "Green" })

if ($failedCount -eq 0) {
    Write-Host ""
    Write-Host "All files uploaded successfully!" -ForegroundColor Green
}