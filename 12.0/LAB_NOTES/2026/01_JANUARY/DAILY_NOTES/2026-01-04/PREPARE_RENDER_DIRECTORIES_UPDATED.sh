#!/bin/bash

# 🚨 CRITICAL UPDATE: Use /data/ for persistent storage (survives deployments)
# Date: January 4, 2026
# This script prepares directories in /data/ and creates symlinks to /var/www/html/

echo "🚨 Preparing three.js asset directories in /data/ (PERSISTENT STORAGE)..."

# Define base paths
DATA_BASE="/data/public/three.js/public"
WEB_BASE="/var/www/html/public/three.js/public"

# Create persistent directories in /data/
echo "Creating persistent directories in /data/..."
mkdir -p "$DATA_BASE/textures/3d models/"
mkdir -p "$DATA_BASE/sounds/"
mkdir -p "$DATA_BASE/audio/"

# Set permissions for /data/
echo "Setting permissions for /data/..."
chmod -R 755 "$DATA_BASE/"
chown -R www-data:www-data "$DATA_BASE/"

# Remove old directories from /var/www/html/ (if they exist from previous setup)
echo "Removing old directories from /var/www/html/ (if exist)..."
rm -rf "$WEB_BASE/textures/3d models/"
rm -rf "$WEB_BASE/sounds/"
rm -rf "$WEB_BASE/audio/"

# Create parent directories in /var/www/html/
echo "Creating parent directories in /var/www/html/..."
mkdir -p "$WEB_BASE/textures/"
mkdir -p "$WEB_BASE/"

# Create symlinks from /var/www/html/ to /data/
echo "Creating symlinks from /var/www/html/ to /data/..."
ln -s "$DATA_BASE/textures/3d models" "$WEB_BASE/textures/3d models"
ln -s "$DATA_BASE/sounds" "$WEB_BASE/sounds"
ln -s "$DATA_BASE/audio" "$WEB_BASE/audio"

# Verify creation and permissions
echo ""
echo "✅ Verifying persistent storage (/data/):"
ls -la "$DATA_BASE/"
ls -la "$DATA_BASE/textures/"
ls -la "$DATA_BASE/textures/3d models/"
ls -la "$DATA_BASE/sounds/"
ls -la "$DATA_BASE/audio/"

echo ""
echo "✅ Verifying symlinks (/var/www/html/):"
ls -la "$WEB_BASE/textures/"
ls -la "$WEB_BASE/"

echo ""
echo "✅ Directory preparation complete!"
echo ""
echo "Next steps:"
echo "1. Upload assets from local machine to /data/ (NOT /var/www/html/)"
echo "2. Use SCP commands to upload to /data/public/three.js/public/"
echo "3. Assets will be accessible via symlinks in /var/www/html/"
echo ""
echo "Example SCP command:"
echo "  scp -r \"local/path/to/3d models\" root@HOST:/data/public/three.js/public/textures/"

