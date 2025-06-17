#!/bin/bash

# Ensure we're in the right directory
cd /var/www/html

# Backup current database from /data if it exists
if [ -f /data/narrrf_world.sqlite ]; then
    echo "Backing up existing database..."
    cp /data/narrrf_world.sqlite /data/narrrf_world.sqlite.bak
fi

# Copy new database to /data
echo "Copying new database to /data..."
cp db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Set proper permissions
echo "Setting permissions..."
chmod 644 /data/narrrf_world.sqlite

# Restart the service to trigger start.sh
echo "Database updated. Please restart the service in Render dashboard to apply changes." 