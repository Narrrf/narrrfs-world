# 🚀 UPLOAD NARRRF3D & GLYPH3D ASSETS TO RENDER
# Created: January 9, 2026
# Purpose: Upload narrrf3d and glyph3d directories recursively to Render /data/ persistent storage
# Run from Windows PowerShell

param(
    [Parameter(Mandatory=$false)]
    [string]$BotSecret = "",
    [Parameter(Mandatory=$false)]
    [switch]$Narrrf3dOnly = $false,
    [Parameter(Mandatory=$false)]
    [switch]$Glyph3dOnly = $false
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
    Write-Host "Usage: .\UPLOAD_NARRRF3D_GLYPH3D.ps1 -BotSecret 'YOUR_SECRET'" -ForegroundColor Yellow
    Write-Host "Or set it in discord\.env file as DISCORD_BOT_SECRET=..." -ForegroundColor Yellow
    exit 1
}

Write-Host "🚀 Uploading Narrrf3d & Glyph3d Assets to Render" -ForegroundColor Green
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

# Function to upload a single file
function Upload-File {
    param(
        [string]$LocalPath,
        [string]$RemotePath,
        [string]$FileName
    )
    
    Write-Host "📤 Uploading: $FileName..." -ForegroundColor Yellow
    
    # Get file size for progress
    $fileSize = (Get-Item $LocalPath).Length
    $fileSizeMB = [math]::Round($fileSize / 1MB, 2)
    Write-Host "   Size: $fileSizeMB MB" -ForegroundColor Gray
    Write-Host "   To: $RemotePath" -ForegroundColor Gray
    
    try {
        $response = curl.exe -X POST `
            -H "Authorization: $BotSecret" `
            -F "file=@$LocalPath" `
            -F "target_path=$RemotePath" `
            $uploadUrl `
            2>&1
        
        $responseString = $response -join "`n"
        
        if ($responseString -match '"success"\s*:\s*true') {
            Write-Host "   ✅ SUCCESS: $FileName uploaded ($fileSizeMB MB)" -ForegroundColor Green
            return $true
        } else {
            $errorMatch = $responseString | Select-String -Pattern '"error"\s*:\s*"([^"]+)"'
            $errorMsg = if ($errorMatch) { $errorMatch.Matches[0].Groups[1].Value } else { "Unknown error" }
            Write-Host "   ❌ FAILED: $FileName - $errorMsg" -ForegroundColor Red
            Write-Host "   Response: $responseString" -ForegroundColor Gray
            return $false
        }
    } catch {
        Write-Host "   ❌ ERROR: $FileName - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Function to upload directory recursively
function Upload-Directory {
    param(
        [string]$LocalDir,
        [string]$RemoteBase,
        [string]$DirectoryName
    )
    
    if (-not (Test-Path $LocalDir)) {
        Write-Host "❌ MISSING: $DirectoryName directory not found at $LocalDir" -ForegroundColor Red
        return @{ Success = 0; Failed = 0; Total = 0 }
    }
    
    Write-Host "📦 Uploading $DirectoryName directory..." -ForegroundColor Cyan
    Write-Host "   Local: $LocalDir" -ForegroundColor Gray
    Write-Host "   Remote: $RemoteBase" -ForegroundColor Gray
    Write-Host ""
    
    $allFiles = Get-ChildItem -Path $LocalDir -Recurse -File
    $total = $allFiles.Count
    $current = 0
    $success = 0
    $failed = 0
    
    if ($total -eq 0) {
        Write-Host "⚠️  No files found in $DirectoryName directory" -ForegroundColor Yellow
        return @{ Success = 0; Failed = 0; Total = 0 }
    }
    
    foreach ($file in $allFiles) {
        $current++
        
        # Calculate relative path from local directory
        $relativePath = $file.FullName.Replace($LocalDir, "").Replace("\", "/").TrimStart("/")
        $remotePath = "$RemoteBase/$relativePath"
        
        Write-Host "[$current/$total] " -NoNewline -ForegroundColor Gray
        
        if (Upload-File -LocalPath $file.FullName -RemotePath $remotePath -FileName $file.Name) {
            $success++
        } else {
            $failed++
        }
        
        Write-Host ""
    }
    
    Write-Host "==============================================" -ForegroundColor Cyan
    Write-Host "📊 $DirectoryName Upload Summary:" -ForegroundColor Cyan
    Write-Host "   ✅ Success: $success/$total files" -ForegroundColor $(if ($success -eq $total) { "Green" } else { "Yellow" })
    Write-Host "   ❌ Failed: $failed/$total files" -ForegroundColor $(if ($failed -gt 0) { "Red" } else { "Green" })
    Write-Host ""
    
    return @{ Success = $success; Failed = $failed; Total = $total }
}

# Upload Narrrf3d directory
$narrrf3dStats = @{ Success = 0; Failed = 0; Total = 0 }
if (-not $Glyph3dOnly) {
    $narrrf3dLocal = Join-Path $projectRoot "public\three.js\public\textures\3d models\narrrf3d"
    $narrrf3dRemote = "/data/public/three.js/public/textures/3d models/narrrf3d"
    $narrrf3dStats = Upload-Directory -LocalDir $narrrf3dLocal -RemoteBase $narrrf3dRemote -DirectoryName "Narrrf3d"
}

# Upload Glyph3d directory
$glyph3dStats = @{ Success = 0; Failed = 0; Total = 0 }
if (-not $Narrrf3dOnly) {
    $glyph3dLocal = Join-Path $projectRoot "public\glyph\glyph3d"
    $glyph3dRemote = "/data/public/glyph/glyph3d"
    $glyph3dStats = Upload-Directory -LocalDir $glyph3dLocal -RemoteBase $glyph3dRemote -DirectoryName "Glyph3d"
}

# Final summary
Write-Host "==============================================" -ForegroundColor Green
Write-Host "📊 FINAL UPLOAD SUMMARY" -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green
Write-Host ""

if (-not $Glyph3dOnly) {
    Write-Host "📦 Narrrf3d:" -ForegroundColor Cyan
    Write-Host "   ✅ Success: $($narrrf3dStats.Success)/$($narrrf3dStats.Total) files" -ForegroundColor $(if ($narrrf3dStats.Success -eq $narrrf3dStats.Total -and $narrrf3dStats.Total -gt 0) { "Green" } else { "Yellow" })
    Write-Host "   ❌ Failed: $($narrrf3dStats.Failed)/$($narrrf3dStats.Total) files" -ForegroundColor $(if ($narrrf3dStats.Failed -gt 0) { "Red" } else { "Green" })
    Write-Host ""
}

if (-not $Narrrf3dOnly) {
    Write-Host "📦 Glyph3d:" -ForegroundColor Cyan
    Write-Host "   ✅ Success: $($glyph3dStats.Success)/$($glyph3dStats.Total) files" -ForegroundColor $(if ($glyph3dStats.Success -eq $glyph3dStats.Total -and $glyph3dStats.Total -gt 0) { "Green" } else { "Yellow" })
    Write-Host "   ❌ Failed: $($glyph3dStats.Failed)/$($glyph3dStats.Total) files" -ForegroundColor $(if ($glyph3dStats.Failed -gt 0) { "Red" } else { "Green" })
    Write-Host ""
}

$totalSuccess = $narrrf3dStats.Success + $glyph3dStats.Success
$totalFailed = $narrrf3dStats.Failed + $glyph3dStats.Failed
$totalFiles = $narrrf3dStats.Total + $glyph3dStats.Total

Write-Host "📊 Overall:" -ForegroundColor Cyan
Write-Host "   ✅ Success: $totalSuccess/$totalFiles files" -ForegroundColor $(if ($totalSuccess -eq $totalFiles -and $totalFiles -gt 0) { "Green" } else { "Yellow" })
Write-Host "   ❌ Failed: $totalFailed/$totalFiles files" -ForegroundColor $(if ($totalFailed -gt 0) { "Red" } else { "Green" })
Write-Host ""

if ($totalSuccess -eq $totalFiles -and $totalFiles -gt 0) {
    Write-Host "✅ All files uploaded successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Verify files in Render shell:" -ForegroundColor Yellow
    if (-not $Glyph3dOnly) {
        Write-Host "   ls -la /data/public/three.js/public/textures/3d\ models/narrrf3d/" -ForegroundColor Cyan
    }
    if (-not $Narrrf3dOnly) {
        Write-Host "   ls -la /data/public/glyph/glyph3d/" -ForegroundColor Cyan
    }
    Write-Host ""
    Write-Host "2. Count files:" -ForegroundColor Yellow
    if (-not $Glyph3dOnly) {
        Write-Host "   find /data/public/three.js/public/textures/3d\ models/narrrf3d/ -type f | wc -l" -ForegroundColor Cyan
    }
    if (-not $Narrrf3dOnly) {
        Write-Host "   find /data/public/glyph/glyph3d/ -type f | wc -l" -ForegroundColor Cyan
    }
    Write-Host ""
    Write-Host "3. Test in browser:" -ForegroundColor Yellow
    Write-Host "   https://narrrfs.world/public/three.js/3d-riddle-game.html (for narrrf3d)" -ForegroundColor Cyan
    Write-Host "   https://narrrfs.world/public/glyph/ (for glyph3d)" -ForegroundColor Cyan
} else {
    Write-Host "⚠️ Some files failed to upload. Check errors above." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Common issues:" -ForegroundColor Yellow
    Write-Host "- File size exceeds 512MB limit" -ForegroundColor Gray
    Write-Host "- Network timeout (retry large files)" -ForegroundColor Gray
    Write-Host "- Invalid bot secret" -ForegroundColor Gray
}
