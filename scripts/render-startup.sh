#!/bin/bash
# 🚀 RENDER STARTUP SCRIPT - PERMANENT PARTNER PERSISTENCE
# Created: October 31, 2025
# Purpose: Auto-restore database and symlinks on every deployment
# This script ensures partner images persist across all deployments forever

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

# STEP 3: Verify Setup
echo ""
echo "🔍 Verification:"
echo "  Symlink: $(ls -la /var/www/html/img/partners 2>/dev/null | grep -o '/var/www/html/img/partners -> /data/img/partners' || echo 'MISSING')"
echo "  Files: $(ls /data/img/partners 2>/dev/null | wc -l) partner files"
echo "  Database: $([ -f /var/www/html/db/narrrf_world.sqlite ] && echo 'EXISTS' || echo 'MISSING')"

echo ""
echo "✅ Startup complete - Partner persistence guaranteed!"
echo "=========================================="

# STEP 4: Start Apache
exec apache2-foreground

