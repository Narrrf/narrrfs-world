#!/bin/bash
# 🔍 VERIFY RENDER DATA FOLDER AND SYMLINKS STATUS
# Created: January 9, 2026
# Purpose: Comprehensive verification of Render server setup for three.js assets
# Run this in Render shell to check everything

echo "🔍 RENDER SERVER STATUS VERIFICATION"
echo "======================================"
echo ""

# ============================================
# STEP 1: Check /data/ Persistent Storage
# ============================================
echo "📂 STEP 1: Checking /data/ persistent storage..."
echo ""

echo "1.1. Base /data/ directory:"
if [ -d /data ]; then
    echo "   ✅ /data/ exists"
    ls -ld /data/
else
    echo "   ❌ /data/ DOES NOT EXIST!"
fi

echo ""
echo "1.2. Three.js base path:"
if [ -d /data/public/three.js/public ]; then
    echo "   ✅ /data/public/three.js/public/ exists"
    ls -ld /data/public/three.js/public/
else
    echo "   ❌ /data/public/three.js/public/ DOES NOT EXIST!"
    echo "   Creating directory structure..."
    mkdir -p /data/public/three.js/public/textures/grass
    mkdir -p /data/public/three.js/public/textures/backgrounds
    mkdir -p /data/public/three.js/public/textures/3d\ models/
    mkdir -p /data/public/three.js/public/sounds/
    mkdir -p /data/public/three.js/public/audio/
    mkdir -p /data/public/three.js/public/models/cheese-temple
    chmod -R 755 /data/public/three.js/public/
    chown -R www-data:www-data /data/public/three.js/public/
fi

echo ""
echo "1.3. Critical missing files (from 404 errors):"
echo "   Checking grass.jpg..."
if [ -f /data/public/three.js/public/textures/grass/grass.jpg ]; then
    echo "   ✅ grass.jpg EXISTS"
    ls -lh /data/public/three.js/public/textures/grass/grass.jpg
else
    echo "   ❌ grass.jpg MISSING"
fi

echo "   Checking cloud.jpg..."
if [ -f /data/public/three.js/public/textures/grass/cloud.jpg ]; then
    echo "   ✅ cloud.jpg EXISTS"
    ls -lh /data/public/three.js/public/textures/grass/cloud.jpg
else
    echo "   ❌ cloud.jpg MISSING"
fi

echo "   Checking cheesetemple1.png..."
if [ -f /data/public/three.js/public/textures/backgrounds/cheesetemple1.png ]; then
    echo "   ✅ cheesetemple1.png EXISTS"
    ls -lh /data/public/three.js/public/textures/backgrounds/cheesetemple1.png
else
    echo "   ❌ cheesetemple1.png MISSING"
fi

echo "   Checking level1.json..."
if [ -f /data/public/three.js/public/models/cheese-temple/level1.json ]; then
    echo "   ✅ level1.json EXISTS"
    ls -lh /data/public/three.js/public/models/cheese-temple/level1.json
else
    echo "   ❌ level1.json MISSING"
fi

echo ""
echo "1.4. File counts in /data/:"
echo "   Textures (grass/): $(find /data/public/three.js/public/textures/grass/ -type f 2>/dev/null | wc -l) files"
echo "   Backgrounds: $(find /data/public/three.js/public/textures/backgrounds/ -type f 2>/dev/null | wc -l) files"
echo "   3D Models: $(find /data/public/three.js/public/textures/3d\ models/ -type f 2>/dev/null | wc -l) files"
echo "   Sounds: $(find /data/public/three.js/public/sounds/ -type f 2>/dev/null | wc -l) files"
echo "   Audio: $(find /data/public/three.js/public/audio/ -type f 2>/dev/null | wc -l) files"
echo "   Models (JSON): $(find /data/public/three.js/public/models/ -type f 2>/dev/null | wc -l) files"

echo ""
echo "1.5. Disk usage:"
du -sh /data/public/three.js/public/ 2>/dev/null || echo "   ⚠️ Cannot check disk usage"

echo ""
# ============================================
# STEP 2: Check Symlinks in /var/www/html/
# ============================================
echo "🔗 STEP 2: Checking symlinks in /var/www/html/..."
echo ""

echo "2.1. Base path exists:"
if [ -d /var/www/html/public/three.js/public ]; then
    echo "   ✅ /var/www/html/public/three.js/public/ exists"
else
    echo "   ❌ /var/www/html/public/three.js/public/ DOES NOT EXIST!"
fi

echo ""
echo "2.2. Symlink status:"
echo "   Checking textures/grass/ symlink..."
if [ -L /var/www/html/public/three.js/public/textures/grass ]; then
    echo "   ✅ textures/grass/ is a symlink"
    ls -la /var/www/html/public/three.js/public/textures/ | grep grass
elif [ -d /var/www/html/public/three.js/public/textures/grass ]; then
    echo "   ⚠️ textures/grass/ exists but is NOT a symlink (is a directory)"
else
    echo "   ❌ textures/grass/ DOES NOT EXIST"
fi

echo ""
echo "   Checking textures/3d models/ symlink..."
if [ -L /var/www/html/public/three.js/public/textures/3d\ models ]; then
    echo "   ✅ textures/3d models/ is a symlink"
    ls -la /var/www/html/public/three.js/public/textures/ | grep "3d models"
elif [ -d /var/www/html/public/three.js/public/textures/3d\ models ]; then
    echo "   ⚠️ textures/3d models/ exists but is NOT a symlink (is a directory)"
else
    echo "   ❌ textures/3d models/ DOES NOT EXIST"
fi

echo ""
echo "   Checking sounds/ symlink..."
if [ -L /var/www/html/public/three.js/public/sounds ]; then
    echo "   ✅ sounds/ is a symlink"
    ls -la /var/www/html/public/three.js/public/ | grep sounds
elif [ -d /var/www/html/public/three.js/public/sounds ]; then
    echo "   ⚠️ sounds/ exists but is NOT a symlink (is a directory)"
else
    echo "   ❌ sounds/ DOES NOT EXIST"
fi

echo ""
echo "   Checking audio/ symlink..."
if [ -L /var/www/html/public/three.js/public/audio ]; then
    echo "   ✅ audio/ is a symlink"
    ls -la /var/www/html/public/three.js/public/ | grep audio
elif [ -d /var/www/html/public/three.js/public/audio ]; then
    echo "   ⚠️ audio/ exists but is NOT a symlink (is a directory)"
else
    echo "   ❌ audio/ DOES NOT EXIST"
fi

echo ""
echo "2.3. Testing file access via symlinks:"
if [ -f /var/www/html/public/three.js/public/textures/grass/grass.jpg ]; then
    echo "   ✅ grass.jpg accessible via symlink"
else
    echo "   ❌ grass.jpg NOT accessible via symlink (even if file exists in /data/)"
fi

if [ -f /var/www/html/public/three.js/public/models/cheese-temple/level1.json ]; then
    echo "   ✅ level1.json accessible via symlink"
else
    echo "   ❌ level1.json NOT accessible via symlink"
    echo "   Checking if models/ directory exists..."
    ls -la /var/www/html/public/three.js/public/models/ 2>/dev/null || echo "   ⚠️ models/ directory does not exist"
fi

echo ""
# ============================================
# STEP 3: Check Startup Script
# ============================================
echo "🚀 STEP 3: Checking startup script status..."
echo ""

echo "3.1. Startup script exists:"
if [ -f /var/www/html/scripts/render-startup.sh ]; then
    echo "   ✅ Startup script exists"
    echo "   Location: /var/www/html/scripts/render-startup.sh"
    head -20 /var/www/html/scripts/render-startup.sh | grep -E "^#|echo"
else
    echo "   ❌ Startup script NOT FOUND!"
fi

echo ""
echo "3.2. Check if script was executed (check recent logs):"
echo "   Looking for startup script output in logs..."
echo "   (This would show in Render deployment logs)"

echo ""
# ============================================
# STEP 4: Check Directory Structure
# ============================================
echo "📁 STEP 4: Complete directory structure check..."
echo ""

echo "4.1. /data/ structure:"
echo "   /data/public/three.js/public/textures/grass/:"
ls -la /data/public/three.js/public/textures/grass/ 2>/dev/null | head -5 || echo "   ⚠️ Directory empty or missing"

echo ""
echo "   /data/public/three.js/public/textures/backgrounds/:"
ls -la /data/public/three.js/public/textures/backgrounds/ 2>/dev/null | head -5 || echo "   ⚠️ Directory empty or missing"

echo ""
echo "   /data/public/three.js/public/models/cheese-temple/:"
ls -la /data/public/three.js/public/models/cheese-temple/ 2>/dev/null | head -5 || echo "   ⚠️ Directory empty or missing"

echo ""
echo "4.2. /var/www/html/ structure:"
echo "   /var/www/html/public/three.js/public/textures/:"
ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | head -10 || echo "   ⚠️ Directory missing"

echo ""
echo "   /var/www/html/public/three.js/public/models/:"
ls -la /var/www/html/public/three.js/public/models/ 2>/dev/null | head -5 || echo "   ⚠️ Directory missing"

echo ""
# ============================================
# STEP 5: Recommendations
# ============================================
echo "💡 STEP 5: Recommendations..."
echo ""

# Check if files exist in /data/
DATA_FILES=$(find /data/public/three.js/public/ -type f 2>/dev/null | wc -l)
if [ "$DATA_FILES" -eq 0 ]; then
    echo "   ❌ NO FILES FOUND in /data/public/three.js/public/"
    echo "   ⚠️ ACTION REQUIRED: Upload assets to /data/ first"
    echo "   Use: /var/www/html/api/discord/upload-assets.php"
fi

# Check if symlinks exist
SYMLINKS_COUNT=$(find /var/www/html/public/three.js/public/ -type l 2>/dev/null | wc -l)
if [ "$SYMLINKS_COUNT" -eq 0 ]; then
    echo "   ❌ NO SYMLINKS FOUND in /var/www/html/public/three.js/public/"
    echo "   ⚠️ ACTION REQUIRED: Recreate symlinks"
    echo "   Run: bash /var/www/html/12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RECREATE_SYMLINKS.sh"
    echo "   Or: Wait for next deployment (startup script should create them)"
fi

# Check for missing critical files
MISSING_FILES=0
[ ! -f /data/public/three.js/public/textures/grass/grass.jpg ] && MISSING_FILES=$((MISSING_FILES + 1))
[ ! -f /data/public/three.js/public/textures/grass/cloud.jpg ] && MISSING_FILES=$((MISSING_FILES + 1))
[ ! -f /data/public/three.js/public/textures/backgrounds/cheesetemple1.png ] && MISSING_FILES=$((MISSING_FILES + 1))
[ ! -f /data/public/three.js/public/models/cheese-temple/level1.json ] && MISSING_FILES=$((MISSING_FILES + 1))

if [ "$MISSING_FILES" -gt 0 ]; then
    echo "   ⚠️ $MISSING_FILES critical files are MISSING"
    echo "   These files are causing 404 errors in production"
    echo "   Upload these files to /data/ using upload API"
fi

echo ""
echo "======================================"
echo "✅ VERIFICATION COMPLETE"
echo ""
echo "Next Steps:"
echo "1. If files missing in /data/: Upload assets via API"
echo "2. If symlinks missing: Run RECREATE_SYMLINKS.sh or wait for next deployment"
echo "3. If startup script not running: Check Render deployment logs"
echo ""
