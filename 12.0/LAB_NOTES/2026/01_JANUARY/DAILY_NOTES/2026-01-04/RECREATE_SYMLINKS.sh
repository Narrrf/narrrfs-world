#!/bin/bash

# Recreate symlinks after deployment
# Run this in Render shell

echo "Recreating symlinks for three.js assets..."

# Create parent directories if needed
mkdir -p /var/www/html/public/three.js/public/textures/
mkdir -p /var/www/html/public/three.js/public/

# Remove any existing directories (if they exist)
rm -rf /var/www/html/public/three.js/public/textures/3d\ models/
rm -rf /var/www/html/public/three.js/public/sounds/
rm -rf /var/www/html/public/three.js/public/audio/

# Create symlinks
ln -s /data/public/three.js/public/textures/3d\ models /var/www/html/public/three.js/public/textures/3d\ models
ln -s /data/public/three.js/public/sounds /var/www/html/public/three.js/public/sounds
ln -s /data/public/three.js/public/audio /var/www/html/public/three.js/public/audio

# Verify
echo ""
echo "Verifying symlinks:"
ls -la /var/www/html/public/three.js/public/textures/
ls -la /var/www/html/public/three.js/public/

echo ""
echo "✅ Symlinks recreated!"

