#!/bin/bash

# === AUTOMATED DATABASE BACKUP SCRIPT ===
# This script automatically backs up the database to persistent storage
# Run this after any database modifications to ensure data persistence

echo "🔄 Starting database backup process..."

# Check if source database exists
if [ ! -f /var/www/html/db/narrrf_world.sqlite ]; then
    echo "❌ Error: Source database not found at /var/www/html/db/narrrf_world.sqlite"
    exit 1
fi

# Create backup with timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_PATH="/data/narrrf_world.sqlite.backup.$TIMESTAMP"

# Create backup first
echo "📦 Creating backup at $BACKUP_PATH..."
cp /var/www/html/db/narrrf_world.sqlite "$BACKUP_PATH"

if [ $? -eq 0 ]; then
    echo "✅ Backup created successfully"
else
    echo "❌ Failed to create backup"
    exit 1
fi

# Copy to persistent storage
echo "💾 Copying to persistent storage..."
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

if [ $? -eq 0 ]; then
    echo "✅ Database successfully backed up to persistent storage"
    echo "📊 Backup file: $BACKUP_PATH"
    echo "💾 Active database: /data/narrrf_world.sqlite"
else
    echo "❌ Failed to copy to persistent storage"
    exit 1
fi

# Set proper permissions
chmod 664 /data/narrrf_world.sqlite
echo "🔐 Permissions set correctly"

echo "🎉 Database backup completed successfully!" 