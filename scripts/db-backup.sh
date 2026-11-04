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

PUBLISHER="/var/www/html/scripts/sqlite-durability-publish.sh"

if [ ! -x "$PUBLISHER" ]; then
    echo "❌ Error: Verified SQLite publisher is unavailable at $PUBLISHER"
    exit 1
fi

echo "📦 Publishing verified SQLite backup to persistent storage..."
"$PUBLISHER" publish
echo "✅ Verified canonical database publication completed"

echo "🎉 Database backup completed successfully!"
