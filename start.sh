#!/bin/bash

# If a persisted DB exists, use it
if [ -f /data/narrrf_world.sqlite ]; then
  echo "✅ Found DB in /data — copying to app directory..."
  cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
else
  echo "🆕 No DB in /data — using app DB and copying it into /data..."
  cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
fi

# 🔐 Fix permissions
chown www-data:www-data /var/www/html/db/narrrf_world.sqlite
chmod 664 /var/www/html/db/narrrf_world.sqlite

# Start Apache
exec apache2-foreground

