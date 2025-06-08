#!/bin/bash

# ✅ Restore persistent DB into app — if it exists
if [ -f /data/narrrf_world.sqlite ]; then
  echo "✅ Found DB in /data — copying to app directory..."
  cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
else
  echo "🆕 No DB in /data — using app DB and copying it into /data..."
  cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
fi

# ✅ Start Apache server
exec apache2-foreground

