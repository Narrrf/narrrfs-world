# 🚀 Upload Three.js Assets to Render
# Run this from your LOCAL Windows machine (PowerShell)
# Date: January 4, 2026

$RENDER_HOST = "srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com"
$RENDER_USER = "root"
$LOCAL_BASE = "C:\xampp-server\htdocs\narrrfs-world"
$REMOTE_BASE = "/data"  # CRITICAL: Use /data/ for persistent storage (survives deployments)

Write-Host "🚀 Starting asset upload to Render..." -ForegroundColor Cyan
Write-Host "Host: $RENDER_HOST" -ForegroundColor Yellow
Write-Host ""

# Check if local directories exist
Write-Host "📋 Checking local directories..." -ForegroundColor Cyan
$texturesPath = "$LOCAL_BASE\public\three.js\public\textures\3d models"
$soundsPath = "$LOCAL_BASE\public\three.js\public\sounds"
$audioPath = "$LOCAL_BASE\public\three.js\public\audio"

if (Test-Path $texturesPath) {
    Write-Host "   ✅ 3D models directory exists" -ForegroundColor Green
    $textureCount = (Get-ChildItem -Path $texturesPath -Recurse -File).Count
    Write-Host "      Files: $textureCount" -ForegroundColor Gray
} else {
    Write-Host "   ❌ 3D models directory missing: $texturesPath" -ForegroundColor Red
    exit 1
}

if (Test-Path $soundsPath) {
    Write-Host "   ✅ Sounds directory exists" -ForegroundColor Green
    $soundCount = (Get-ChildItem -Path $soundsPath -Recurse -File).Count
    Write-Host "      Files: $soundCount" -ForegroundColor Gray
} else {
    Write-Host "   ⚠️  Sounds directory missing: $soundsPath" -ForegroundColor Yellow
}

if (Test-Path $audioPath) {
    Write-Host "   ✅ Audio directory exists" -ForegroundColor Green
    $audioCount = (Get-ChildItem -Path $audioPath -Recurse -File).Count
    Write-Host "      Files: $audioCount" -ForegroundColor Gray
} else {
    Write-Host "   ⚠️  Audio directory missing (optional): $audioPath" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "📦 Starting uploads (this may take 10-30 minutes for large files)..." -ForegroundColor Cyan
Write-Host ""

# Upload 3D models
Write-Host "1️⃣ Uploading 3D models..." -ForegroundColor Yellow
Write-Host "   Source: $texturesPath" -ForegroundColor Gray
Write-Host "   Destination: ${REMOTE_BASE}/public/three.js/public/textures/ (PERSISTENT - survives deployments)" -ForegroundColor Gray
Write-Host "   This may take 10-20 minutes..." -ForegroundColor Gray

scp -r "$texturesPath" "${RENDER_USER}@${RENDER_HOST}:${REMOTE_BASE}/public/three.js/public/textures/"

if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ 3D models uploaded successfully!" -ForegroundColor Green
} else {
    Write-Host "   ❌ 3D models upload failed!" -ForegroundColor Red
    Write-Host "   Error code: $LASTEXITCODE" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Upload sounds
if (Test-Path $soundsPath) {
    Write-Host "2️⃣ Uploading sounds..." -ForegroundColor Yellow
    Write-Host "   Source: $soundsPath" -ForegroundColor Gray
    Write-Host "   Destination: ${REMOTE_BASE}/public/three.js/public/" -ForegroundColor Gray
    
    scp -r "$soundsPath" "${RENDER_USER}@${RENDER_HOST}:${REMOTE_BASE}/public/three.js/public/"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   ✅ Sounds uploaded successfully!" -ForegroundColor Green
    } else {
        Write-Host "   ❌ Sounds upload failed!" -ForegroundColor Red
    }
    Write-Host ""
}

# Upload audio (if exists)
if (Test-Path $audioPath) {
    Write-Host "3️⃣ Uploading audio..." -ForegroundColor Yellow
    Write-Host "   Source: $audioPath" -ForegroundColor Gray
    Write-Host "   Destination: ${REMOTE_BASE}/public/three.js/public/" -ForegroundColor Gray
    
    scp -r "$audioPath" "${RENDER_USER}@${RENDER_HOST}:${REMOTE_BASE}/public/three.js/public/"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   ✅ Audio uploaded successfully!" -ForegroundColor Green
    } else {
        Write-Host "   ❌ Audio upload failed!" -ForegroundColor Red
    }
    Write-Host ""
}

Write-Host "✅ Upload complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Go back to Render shell" -ForegroundColor White
Write-Host "2. Create symlinks (if not already done):" -ForegroundColor White
Write-Host "   See: RENDER_PERSISTENT_ASSETS_SOLUTION.md" -ForegroundColor Gray
Write-Host "3. Run verification commands:" -ForegroundColor White
Write-Host "   ls /data/public/three.js/public/textures/3d\ models/" -ForegroundColor Gray
Write-Host "   ls /var/www/html/public/three.js/public/textures/3d\ models/ (via symlink)" -ForegroundColor Gray
Write-Host "4. Test game in browser - check for 404 errors" -ForegroundColor White
Write-Host ""

