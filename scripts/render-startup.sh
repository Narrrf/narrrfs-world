#!/bin/bash
# 🚀 RENDER STARTUP SCRIPT - PERMANENT PARTNER & ASSET PERSISTENCE
# Created: October 31, 2025
# Updated: January 6, 2026 - Added three.js asset symlinks
# Updated: January 9, 2026 - Added glyph asset symlinks
# Updated: March 30, 2026 - Added Genetic GLB asset symlinks
# Purpose: Auto-restore database and symlinks on every deployment
# This script ensures partner images, three.js assets, glyph assets, and Genetic GLB assets persist across all deployments forever

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

# Ensure persistent directories exist (all directories needed for game)
mkdir -p "$DATA_BASE/textures/3d models/"
mkdir -p "$DATA_BASE/textures/grass/"
mkdir -p "$DATA_BASE/textures/backgrounds/"
mkdir -p "$DATA_BASE/textures/blocks/"
mkdir -p "$DATA_BASE/textures/plants/"
mkdir -p "$DATA_BASE/sounds/"
mkdir -p "$DATA_BASE/audio/"
mkdir -p "$DATA_BASE/models/"
mkdir -p "$DATA_BASE/videos/"

# === GENETIC ASSETS (NEW) ===
echo "🧬 Setting up genetic asset directories..."
mkdir -p "$DATA_BASE/genetic/accessories/"
mkdir -p "$DATA_BASE/genetic/outfit/"
mkdir -p "$DATA_BASE/genetic/previews/accessories/"
mkdir -p "$DATA_BASE/genetic/previews/outfit/"

chmod -R 755 "$DATA_BASE/"
chown -R www-data:www-data "$DATA_BASE/"

# Remove any existing directories/broken symlinks
rm -rf "$WEB_BASE/textures/3d models/"
rm -rf "$WEB_BASE/textures/grass/"
rm -rf "$WEB_BASE/textures/backgrounds/"
rm -rf "$WEB_BASE/textures/blocks/"
rm -rf "$WEB_BASE/textures/plants/"
rm -rf "$WEB_BASE/sounds/"
rm -rf "$WEB_BASE/audio/"
rm -rf "$WEB_BASE/models/"
rm -rf "$WEB_BASE/videos/"
rm -rf "$WEB_BASE/genetic/accessories/"
rm -rf "$WEB_BASE/genetic/outfit/"
rm -rf "$WEB_BASE/genetic/previews/accessories/"
rm -rf "$WEB_BASE/genetic/previews/outfit/"

# Create parent directories in /var/www/html/ (if needed for symlinks)
mkdir -p "$WEB_BASE/textures/"
mkdir -p "$WEB_BASE/genetic/previews/"
mkdir -p "$WEB_BASE/genetic/"
mkdir -p "$WEB_BASE/"

# Create symlinks from /var/www/html/ to /data/ (ALL directories needed)
ln -s "$DATA_BASE/textures/3d models" "$WEB_BASE/textures/3d models"
ln -s "$DATA_BASE/textures/grass" "$WEB_BASE/textures/grass"
ln -s "$DATA_BASE/textures/backgrounds" "$WEB_BASE/textures/backgrounds"
ln -s "$DATA_BASE/textures/blocks" "$WEB_BASE/textures/blocks"
ln -s "$DATA_BASE/textures/plants" "$WEB_BASE/textures/plants"
ln -s "$DATA_BASE/sounds" "$WEB_BASE/sounds"
ln -s "$DATA_BASE/audio" "$WEB_BASE/audio"
ln -s "$DATA_BASE/models" "$WEB_BASE/models"
ln -s "$DATA_BASE/videos" "$WEB_BASE/videos"
ln -s "$DATA_BASE/genetic/accessories" "$WEB_BASE/genetic/accessories"
ln -s "$DATA_BASE/genetic/outfit" "$WEB_BASE/genetic/outfit"
ln -s "$DATA_BASE/genetic/previews/accessories" "$WEB_BASE/genetic/previews/accessories"
ln -s "$DATA_BASE/genetic/previews/outfit" "$WEB_BASE/genetic/previews/outfit"

# Set symlink permissions
chown -h www-data:www-data "$WEB_BASE/textures/3d models" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/textures/grass" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/textures/backgrounds" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/textures/blocks" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/textures/plants" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/sounds" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/audio" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/models" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/videos" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/genetic/accessories" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/genetic/outfit" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/genetic/previews/accessories" 2>/dev/null || true
chown -h www-data:www-data "$WEB_BASE/genetic/previews/outfit" 2>/dev/null || true

echo "✅ Three.js asset symlinks created (all directories: 3d models, grass, backgrounds, blocks, plants, sounds, audio, models, videos, genetic accessories, genetic outfit, genetic preview accessories, genetic preview outfit)"

# STEP 3.5: Create Glyph Asset Symlinks
echo "🎮 Setting up glyph asset symlinks..."

# Define glyph paths
GLYPH_DATA_BASE="/data/public/glyph"
GLYPH_WEB_BASE="/var/www/html/public/glyph"

# Ensure persistent directory exists
mkdir -p "$GLYPH_DATA_BASE/glyph3d"
chmod -R 755 "$GLYPH_DATA_BASE/"
chown -R www-data:www-data "$GLYPH_DATA_BASE/"

# Remove any existing directory/broken symlink
rm -rf "$GLYPH_WEB_BASE/glyph3d"

# Create parent directories in /var/www/html/ (if needed)
mkdir -p "$GLYPH_WEB_BASE/"

# Create symlink from /var/www/html/ to /data/
ln -s "$GLYPH_DATA_BASE/glyph3d" "$GLYPH_WEB_BASE/glyph3d"

# Set symlink permissions
chown -h www-data:www-data "$GLYPH_WEB_BASE/glyph3d" 2>/dev/null || true

echo "✅ Glyph asset symlinks created"

# STEP 4: Verify Setup
echo ""
echo "🔍 Verification:"
echo "  Partner Symlink: $(ls -la /var/www/html/img/partners 2>/dev/null | grep -o '/var/www/html/img/partners -> /data/img/partners' || echo 'MISSING')"
echo "  Partner Files: $(ls /data/img/partners 2>/dev/null | wc -l) partner files"
echo "  3D Models Symlink: $(ls -la "$WEB_BASE/textures/" 2>/dev/null | grep -o "3d models -> /data" || echo 'MISSING')"
echo "  Grass Symlink: $(ls -la "$WEB_BASE/textures/" 2>/dev/null | grep -o "grass -> /data" || echo 'MISSING')"
echo "  Backgrounds Symlink: $(ls -la "$WEB_BASE/textures/" 2>/dev/null | grep -o "backgrounds -> /data" || echo 'MISSING')"
echo "  Sounds Symlink: $(ls -la "$WEB_BASE/" 2>/dev/null | grep -o "sounds -> /data" || echo 'MISSING')"
echo "  Audio Symlink: $(ls -la "$WEB_BASE/" 2>/dev/null | grep -o "audio -> /data" || echo 'MISSING')"
echo "  Models Symlink: $(ls -la "$WEB_BASE/" 2>/dev/null | grep -o "models -> /data" || echo 'MISSING')"
echo "  Genetic Accessories Symlink: $(ls -la "$WEB_BASE/genetic/" 2>/dev/null | grep -o "accessories -> /data" || echo 'MISSING')"
echo "  Genetic Outfit Symlink: $(ls -la "$WEB_BASE/genetic/" 2>/dev/null | grep -o "outfit -> /data" || echo 'MISSING')"
echo "  Genetic Preview Accessories Symlink: $(ls -la "$WEB_BASE/genetic/previews/" 2>/dev/null | grep -o "accessories -> /data" || echo 'MISSING')"
echo "  Genetic Preview Outfit Symlink: $(ls -la "$WEB_BASE/genetic/previews/" 2>/dev/null | grep -o "outfit -> /data" || echo 'MISSING')"
echo "  Glyph3d Symlink: $(ls -la "$GLYPH_WEB_BASE/" 2>/dev/null | grep -o "glyph3d -> /data" || echo 'MISSING')"
echo "  3D Models Files: $(find "$DATA_BASE/textures/3d models/" -type f 2>/dev/null | wc -l) files"
echo "  Grass Files: $(find "$DATA_BASE/textures/grass/" -type f 2>/dev/null | wc -l) files"
echo "  Background Files: $(find "$DATA_BASE/textures/backgrounds/" -type f 2>/dev/null | wc -l) files"
echo "  Sounds Files: $(find "$DATA_BASE/sounds/" -type f 2>/dev/null | wc -l) files"
echo "  Audio Files: $(find "$DATA_BASE/audio/" -type f 2>/dev/null | wc -l) files"
echo "  Model Files: $(find "$DATA_BASE/models/" -type f 2>/dev/null | wc -l) files"
echo "  Genetic Accessories Files: $(find "$DATA_BASE/genetic/accessories/" -type f 2>/dev/null | wc -l) files"
echo "  Genetic Outfit Files: $(find "$DATA_BASE/genetic/outfit/" -type f 2>/dev/null | wc -l) files"
echo "  Genetic Preview Accessories Files: $(find "$DATA_BASE/genetic/previews/accessories/" -type f 2>/dev/null | wc -l) files"
echo "  Genetic Preview Outfit Files: $(find "$DATA_BASE/genetic/previews/outfit/" -type f 2>/dev/null | wc -l) files"
echo "  Glyph3d Files: $(find "$GLYPH_DATA_BASE/glyph3d/" -type f 2>/dev/null | wc -l) files"
echo "  Database: $([ -f /var/www/html/db/narrrf_world.sqlite ] && echo 'EXISTS' || echo 'MISSING')"

echo ""
echo "✅ Startup complete - Partner & Asset persistence guaranteed!"
echo "=========================================="

# STEP 4.5: Daily Genesis League Snapshot Automation
# mousefight_league_daily_production_v1
#
# DEVS FOR DECADES:
# This block is additive. All existing Render database, asset, symlink,
# verification and Apache startup behavior remains unchanged.
#
# The League scheduler reads the live DB only through the existing
# read-only V1 League source and writes snapshot files only.
# It never writes Genesis/Lab state, ownership, Genetic Items, economy,
# Fight Recovery, staking, authentication or database schema rows.
LEAGUE_DAILY_LOOP="/var/www/html/scripts/mousefight-league-daily-loop.sh"
LEAGUE_PYTHON_BIN="$(command -v python3 || true)"
LEAGUE_PHP_BIN="$(command -v php || true)"
LEAGUE_SNAPSHOT_DIRECTORY="/data/mousefight-leagues"

if [ -z "$LEAGUE_PYTHON_BIN" ]; then
    echo "⚠️ Daily Genesis League automation disabled: python3 not found."
elif [ -z "$LEAGUE_PHP_BIN" ]; then
    echo "⚠️ Daily Genesis League automation disabled: PHP CLI not found."
elif [ ! -f "$LEAGUE_DAILY_LOOP" ]; then
    echo "⚠️ Daily Genesis League automation disabled: loop script missing."
elif \
    MOUSEFIGHT_LEAGUE_APP_ROOT="/var/www/html" \
    MOUSEFIGHT_LEAGUE_PERSIST_DIR="$LEAGUE_SNAPSHOT_DIRECTORY" \
    bash "$LEAGUE_DAILY_LOOP" --bootstrap
then
    export MOUSEFIGHT_LEAGUE_SNAPSHOT_DIRECTORY="$LEAGUE_SNAPSHOT_DIRECTORY"
    export MOUSEFIGHT_LEAGUE_PYTHON_BIN="$LEAGUE_PYTHON_BIN"
    export MOUSEFIGHT_LEAGUE_PHP_EXE="$LEAGUE_PHP_BIN"

    echo "✅ Daily Genesis League snapshots: persistent chain ready."
    echo "🕑 Daily Genesis League schedule: 02:00 UTC."

    bash "$LEAGUE_DAILY_LOOP" &
else
    echo "⚠️ Daily Genesis League automation disabled: snapshot bootstrap failed."
    echo "⚠️ Existing website startup continues with packaged League fallback."
fi

# STEP 5: Start Apache
exec apache2-foreground