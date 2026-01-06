#!/bin/bash
# 🚀 RENDER STARTUP SCRIPT - PERMANENT PARTNER & ASSET PERSISTENCE
# Created: October 31, 2025
# Updated: January 6, 2026 - Added three.js asset symlinks
# Purpose: Auto-restore database and symlinks on every deployment
# This script ensures partner images and three.js assets persist across all deployments forever

echo "🚀 Narrrf's World - Render Startup Script"
echo "=========================================="

# STEP 1: Restore Database from Persistent Storage
echo "📊 Restoring database from /data..."
if [ -f /data/narrrf_world.sqlite ]; then
  cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
  chmod 664 /var/www/html/db/narrrf_world.sqlite
  chown www-data:www-data /var/www/html/db/narrrf_world.sqlite
  echo "✅ Database restored successfully"
else
  echo "⚠️ Warning: No database backup found in /data/"
fi

# STEP 2: Create Partner Images Symlink
echo "🤝 Setting up partner images symlink..."

# Ensure persistent directory exists
mkdir -p /data/img/partners
chmod 775 /data/img/partners
chown -R www-data:www-data /data/img/partners

# Remove any existing directory/broken symlink
rm -rf /var/www/html/img/partners

# Create symlink to persistent storage
ln -s /data/img/partners /var/www/html/img/partners

# Set symlink permissions
chown -h www-data:www-data /var/www/html/img/partners

echo "✅ Partner images symlink created"

# STEP 3: Create Three.js Asset Symlinks
echo "🎮 Setting up three.js asset symlinks..."

# Define base paths
DATA_BASE="/data/public/three.js/public"
WEB_BASE="/var/www/html/public/three.js/public"

# Ensure persistent directories exist
mkdir -p "$DATA_BASE/textures/3d models/"
mkdir -p "$DATA_BASE/sounds/"
mkdir -p "$DATA_BASE/audio/"
chmod -R 755 "$DATA_BASE/"
chown -R www-data:www-data "$DATA_BASE/"

# Remove any existing directories/broken symlinks
rm -rf "$WEB_BASE/textures/3d models/"
rm -rf "$WEB_BASE/sounds/"
rm -rf "$WEB_BASE/audio/"

# Create parent directories in /var/www/html/ (if needed for symlinks)
mkdir -p "$WEB_BASE/textures/"
mkdir -p "$WEB_BASE/"

# Create symlinks from /var/www/html/ to /data/
ln -s "$DATA_BASE/textures/3d models" "$WEB_BASE/textures/3d models"
ln -s "$DATA_BASE/sounds" "$WEB_BASE/sounds"
ln -s "$DATA_BASE/audio" "$WEB_BASE/audio"

# Set symlink permissions
chown -h www-data:www-data "$WEB_BASE/textures/3d models" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/sounds" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/audio" 2>/dev/null || true

echo "✅ Three.js asset symlinks created"

# STEP 4: Verify Setup
echo ""
echo "🔍 Verification:"
echo "  Partner Symlink: $(ls -la /var/www/html/img/partners 2>/dev/null | grep -o '/var/www/html/img/partners -> /data/img/partners' || echo 'MISSING')"
echo "  Partner Files: $(ls /data/img/partners 2>/dev/null | wc -l) partner files"
echo "  3D Models Symlink: $(ls -la "$WEB_BASE/textures/" 2>/dev/null | grep -o "3d models -> /data" || echo 'MISSING')"
echo "  Sounds Symlink: $(ls -la "$WEB_BASE/" 2>/dev/null | grep -o "sounds -> /data" || echo 'MISSING')"
echo "  Audio Symlink: $(ls -la "$WEB_BASE/" 2>/dev/null | grep -o "audio -> /data" || echo 'MISSING')"
echo "  3D Models Files: $(find "$DATA_BASE/textures/3d models/" -type f 2>/dev/null | wc -l) files"
echo "  Sounds Files: $(find "$DATA_BASE/sounds/" -type f 2>/dev/null | wc -l) files"
echo "  Audio Files: $(find "$DATA_BASE/audio/" -type f 2>/dev/null | wc -l) files"
echo "  Database: $([ -f /var/www/html/db/narrrf_world.sqlite ] && echo 'EXISTS' || echo 'MISSING')"

echo ""
echo "✅ Startup complete - Partner & Asset persistence guaranteed!"
echo "=========================================="

# STEP 5: Start Apache
exec apache2-foreground

