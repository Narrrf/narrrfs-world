#!/bin/bash

# === DATABASE BACKUP CLEANUP SCRIPT ===
# This script removes old backup files to manage storage space
# Keeps only the 5 most recent backups

echo "🧹 Starting database backup cleanup..."

# Find all backup files and keep only the 5 most recent
BACKUP_DIR="/data"
BACKUP_PATTERN="narrrf_world.sqlite.backup.*"

# Count total backups
TOTAL_BACKUPS=$(find "$BACKUP_DIR" -name "$BACKUP_PATTERN" | wc -l)

if [ "$TOTAL_BACKUPS" -le 5 ]; then
    echo "📊 Found $TOTAL_BACKUPS backup(s) - no cleanup needed (keeping up to 5)"
    exit 0
fi

echo "📊 Found $TOTAL_BACKUPS backup(s) - cleaning up old ones..."

# List all backups sorted by modification time (newest first)
BACKUP_FILES=$(find "$BACKUP_DIR" -name "$BACKUP_PATTERN" -printf '%T@ %p\n' | sort -nr | cut -d' ' -f2-)

# Keep track of files to delete
FILES_TO_DELETE=""
KEEP_COUNT=0

while IFS= read -r file; do
    if [ "$KEEP_COUNT" -lt 5 ]; then
        echo "✅ Keeping: $(basename "$file")"
        ((KEEP_COUNT++))
    else
        FILES_TO_DELETE="$FILES_TO_DELETE $file"
    fi
done <<< "$BACKUP_FILES"

# Delete old backups
if [ -n "$FILES_TO_DELETE" ]; then
    echo "🗑️  Deleting old backups..."
    for file in $FILES_TO_DELETE; do
        if rm "$file"; then
            echo "✅ Deleted: $(basename "$file")"
        else
            echo "❌ Failed to delete: $(basename "$file")"
        fi
    done
fi

# Show final status
REMAINING_BACKUPS=$(find "$BACKUP_DIR" -name "$BACKUP_PATTERN" | wc -l)
echo "📊 Cleanup complete! $REMAINING_BACKUPS backup(s) remaining"

echo "🎉 Database backup cleanup completed!" 