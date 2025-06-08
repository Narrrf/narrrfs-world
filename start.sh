#!/bin/bash

# ✅ If a persisted DB exists, copy it back into the app directory
if [ -f /data/narrrf_world.sqlite ]; then
  echo "✅ Found persisted DB, restoring to app directory..."
  cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
fi

# ✅ Start Apache
apache2-foreground
