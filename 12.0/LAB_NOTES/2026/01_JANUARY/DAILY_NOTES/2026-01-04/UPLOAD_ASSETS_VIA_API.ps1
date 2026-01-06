# 🚀 Upload Three.js Assets via API (Using Discord Bot Authentication)
# Run this from your LOCAL Windows machine (PowerShell)
# Date: January 4, 2026

param(
    [string]$BotSecret = "",
    [string]$LocalBase = "C:\xampp-server\htdocs\narrrfs-world\public\three.js\public",
    [string]$RemoteBase = "/data/public/three.js/public",
    [string]$ApiUrl = "https://narrrfs.world/api/discord/upload-assets.php"
)

# Get bot secret from environment or parameter
if ([string]::IsNullOrEmpty($BotSecret)) {
    # Try to read from .env file
    $envPath = "discord\.env"
    if (Test-Path $envPath) {
        $envContent = Get-Content $envPath
        foreach ($line in $envContent) {
            if ($line -match 'DISCORD_BOT_SECRET=(.+)') {
                $BotSecret = $matches[1].Trim()
                break
            }
        }
    }
    
    # If still empty, try config.js
    if ([string]::IsNullOrEmpty($BotSecret)) {
        $configPath = "discord\config.js"
        if (Test-Path $configPath) {
            $configContent = Get-Content $configPath -Raw
            if ($configContent -match "botToken['`"]:\s*['`"]([^'`"]+)['`"]") {
                $BotSecret = $matches[1]
            }
        }
    }
}

if ([string]::IsNullOrEmpty($BotSecret)) {
    Write-Host "❌ Error: DISCORD_BOT_SECRET not found!" -ForegroundColor Red
    Write-Host "Please provide it as a parameter: -BotSecret 'YOUR_SECRET'" -ForegroundColor Yellow
    Write-Host "Or set it in discord\.env as DISCORD_BOT_SECRET=..." -ForegroundColor Yellow
    exit 1
}

Write-Host "🚀 Starting asset upload via API..." -ForegroundColor Cyan
Write-Host "API URL: $ApiUrl" -ForegroundColor Gray
Write-Host "Local Base: $LocalBase" -ForegroundColor Gray
Write-Host "Remote Base: $RemoteBase" -ForegroundColor Gray
Write-Host ""

# Function to upload a single file
function Upload-File {
    param(
        [string]$LocalPath,
        [string]$RemotePath
    )
    
    $fileName = Split-Path $LocalPath -Leaf
    Write-Host "Uploading: $fileName" -ForegroundColor Yellow
    Write-Host "  From: $LocalPath" -ForegroundColor Gray
    Write-Host "  To: $RemotePath" -ForegroundColor Gray
    
    try {
        # Use curl to upload file
        $result = curl.exe -X POST `
            -H "Authorization: $BotSecret" `
            -F "file=@$LocalPath" `
            -F "target_path=$RemotePath" `
            -s `
            $ApiUrl
        
        $json = $result | ConvertFrom-Json -ErrorAction SilentlyContinue
        if ($json -and $json.success) {
            $sizeKB = [math]::Round($json.file_size/1KB, 2)
            Write-Host "  [OK] Success ($sizeKB KB)" -ForegroundColor Green
            return $true
        } else {
            $errorMsg = if ($json) { $json.error } else { "Parse error" }
            Write-Host "  [FAIL] $errorMsg" -ForegroundColor Red
            return $false
        }
    } catch {
        Write-Host "  ❌ Error: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Upload 3D models
Write-Host "`n📦 Uploading 3D models..." -ForegroundColor Cyan
$modelsPath = Join-Path $LocalBase "textures\3d models"
if (Test-Path $modelsPath) {
    $modelFiles = Get-ChildItem -Path $modelsPath -Recurse -File
    $total = $modelFiles.Count
    $current = 0
    $success = 0
    
    foreach ($file in $modelFiles) {
        $current++
        $relativePath = $file.FullName.Replace($LocalBase, "").Replace("\", "/").TrimStart("/")
        $remotePath = "$RemoteBase/$relativePath"
        
        Write-Host "`n[$current/$total] " -NoNewline -ForegroundColor Gray
        if (Upload-File -LocalPath $file.FullName -RemotePath $remotePath) {
            $success++
        }
    }
    
    Write-Host "`n✅ 3D Models: $success/$total uploaded successfully" -ForegroundColor $(if ($success -eq $total) { "Green" } else { "Yellow" })
} else {
    Write-Host "⚠️  3D models directory not found: $modelsPath" -ForegroundColor Yellow
}

# Upload sounds
Write-Host "`n📦 Uploading sounds..." -ForegroundColor Cyan
$soundsPath = Join-Path $LocalBase "sounds"
if (Test-Path $soundsPath) {
    $soundFiles = Get-ChildItem -Path $soundsPath -Recurse -File
    $total = $soundFiles.Count
    $current = 0
    $success = 0
    
    foreach ($file in $soundFiles) {
        $current++
        $relativePath = $file.FullName.Replace($LocalBase, "").Replace("\", "/").TrimStart("/")
        $remotePath = "$RemoteBase/$relativePath"
        
        Write-Host "`n[$current/$total] " -NoNewline -ForegroundColor Gray
        if (Upload-File -LocalPath $file.FullName -RemotePath $remotePath) {
            $success++
        }
    }
    
    Write-Host "`n✅ Sounds: $success/$total uploaded successfully" -ForegroundColor $(if ($success -eq $total) { "Green" } else { "Yellow" })
} else {
    Write-Host "⚠️  Sounds directory not found: $soundsPath" -ForegroundColor Yellow
}

# Upload audio
Write-Host "`n📦 Uploading audio..." -ForegroundColor Cyan
$audioPath = Join-Path $LocalBase "audio"
if (Test-Path $audioPath) {
    $audioFiles = Get-ChildItem -Path $audioPath -Recurse -File
    $total = $audioFiles.Count
    $current = 0
    $success = 0
    
    foreach ($file in $audioFiles) {
        $current++
        $relativePath = $file.FullName.Replace($LocalBase, "").Replace("\", "/").TrimStart("/")
        $remotePath = "$RemoteBase/$relativePath"
        
        Write-Host "`n[$current/$total] " -NoNewline -ForegroundColor Gray
        if (Upload-File -LocalPath $file.FullName -RemotePath $remotePath) {
            $success++
        }
    }
    
    Write-Host "`n✅ Audio: $success/$total uploaded successfully" -ForegroundColor $(if ($success -eq $total) { "Green" } else { "Yellow" })
} else {
    Write-Host "⚠️  Audio directory not found: $audioPath (optional)" -ForegroundColor Gray
}

Write-Host "`n✅ Upload process complete!" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "1. Go to Render shell and verify files:" -ForegroundColor White
Write-Host "   ls /data/public/three.js/public/textures/3d\ models/" -ForegroundColor Gray
Write-Host "   ls /data/public/three.js/public/sounds/" -ForegroundColor Gray
Write-Host "2. Test game in browser - check for 404 errors" -ForegroundColor White
Write-Host ""

