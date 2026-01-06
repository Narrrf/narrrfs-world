#!/bin/bash

# 🚨 RECREATE SYMLINKS - Run this in Render Shell
# Date: January 6, 2026
# Purpose: Recreate symlinks after deployment (they get wiped)

echo "🔗 Recreating symlinks for three.js assets..."

# 1. Verify files exist in /data/
echo ""
echo "✅ Step 1: Verifying files in /data/..."
ls /data/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
ls /data/public/three.js/public/sounds/chest.mp3

# 2. Remove any existing directories (if they exist)
echo ""
echo "🗑️  Step 2: Removing old directories (if exist)..."
rm -rf /var/www/html/public/three.js/public/textures/3d\ models/
rm -rf /var/www/html/public/three.js/public/sounds/
rm -rf /var/www/html/public/three.js/public/audio/

# 3. Create parent directories
echo ""
echo "📁 Step 3: Creating parent directories..."
mkdir -p /var/www/html/public/three.js/public/textures/
mkdir -p /var/www/html/public/three.js/public/

# 4. Create symlinks
echo ""
echo "🔗 Step 4: Creating symlinks..."
ln -s /data/public/three.js/public/textures/3d\ models /var/www/html/public/three.js/public/textures/3d\ models
ln -s /data/public/three.js/public/sounds /var/www/html/public/three.js/public/sounds
ln -s /data/public/three.js/public/audio /var/www/html/public/three.js/public/audio

# 5. Verify symlinks
echo ""
echo "✅ Step 5: Verifying symlinks..."
ls -la /var/www/html/public/three.js/public/textures/ | grep '3d models'
ls -la /var/www/html/public/three.js/public/ | grep sounds
ls -la /var/www/html/public/three.js/public/ | grep audio

# 6. Test file access
echo ""
echo "🧪 Step 6: Testing file access..."
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
ls /var/www/html/public/three.js/public/sounds/chest.mp3

echo ""
echo "✅ Symlink recreation complete!"
echo ""
echo "Next: Test games in browser to verify assets load correctly."

