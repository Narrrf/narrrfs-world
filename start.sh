#!/bin/bash

# === PERSISTENT DATABASE SETUP ===
if [ -f /data/narrrf_world.sqlite ]; then
  echo "✅ Found DB in /data — copying to app directory..."
  cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
else
  echo "🆕 No DB in /data — seeding /data with app DB (first launch)..."
  cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
  # After seeding, copy back just in case (for safety/consistency)
  cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
fi

# === PERMISSIONS ===
chown www-data:www-data /var/www/html/db/narrrf_world.sqlite
chmod 664 /var/www/html/db/narrrf_world.sqlite

# === SETUP DATABASE BACKUP SCRIPTS ===
if [ -d /var/www/html/scripts ]; then
    echo "🔧 Setting up database backup scripts..."
    chmod +x /var/www/html/scripts/db-backup.sh
    chmod +x /var/www/html/scripts/db-cleanup.sh
    echo "✅ Database backup scripts configured"
fi

# === MIGRATIONS ===
echo "📦 Applying database migrations..."
sqlite3 /var/www/html/db/narrrf_world.sqlite < /var/www/html/db/migrations/create_score_tables.sql
sqlite3 /var/www/html/db/narrrf_world.sqlite < /var/www/html/db/migrations/create_store_tables.sql

# === DISCORD BOT MANUAL LAUNCH NOTICE ===
echo ""
echo "🤖 To start the Discord bot locally, run these commands in your terminal:"
echo "cd /var/www/html/discord"
echo "npm install"
echo "node index.js"
echo ""

# === START APACHE ===
exec apache2-foreground
