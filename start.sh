#!/bin/bash

# ✅ If a persisted DB exists, restore it to the app
if [ -f /data/narrrf_world.sqlite ]; then
  echo "✅ Found DB in /data — copying to app directory..."
  cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
else
  echo "🆕 No DB in /data — using app DB and copying it into /data..."
  cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
fi

# 🔐 Fix permissions so PHP (www-data) can write
chown www-data:www-data /var/www/html/db/narrrf_world.sqlite
chmod 664 /var/www/html/db/narrrf_world.sqlite

# 📦 Apply database migrations
echo "📦 Applying database migrations..."
sqlite3 /var/www/html/db/narrrf_world.sqlite < /var/www/html/db/migrations/create_score_tables.sql
sqlite3 /var/www/html/db/narrrf_world.sqlite < /var/www/html/db/migrations/create_store_tables.sql

# 🤖 Start Discord bot in background
echo "🤖 Starting Discord bot..."
cd /var/www/html/discord
npm install
node index.js &

# 🚀 Start Apache in foreground
exec apache2-foreground

