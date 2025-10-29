#!/bin/bash
# 🚀 RENDER COMMAND: Add gallery_images field to tbl_partners
# Created: October 29, 2025
# Purpose: Production database migration for Partner Gallery system

echo "🚀 Starting gallery_images field migration..."

# STEP 1: Backup database (CRITICAL!)
echo "📦 Step 1: Backing up production database..."
cd /var/www/html/db
cp narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite
echo "✅ Backup created in /data/"

# STEP 2: Add gallery_images field
echo "📸 Step 2: Adding gallery_images field..."
echo "ALTER TABLE tbl_partners ADD COLUMN gallery_images TEXT;" | sqlite3 narrrf_world.sqlite

# STEP 3: Verify field was added
echo "✅ Step 3: Verifying field was added..."
echo "SELECT sql FROM sqlite_master WHERE name = 'tbl_partners';" | sqlite3 narrrf_world.sqlite | grep -i gallery

# STEP 4: Final backup to /data (for next deployment)
echo "💾 Step 4: Saving final backup to /data for next deployment..."
cp narrrf_world.sqlite /data/narrrf_world.sqlite

# STEP 5: Count current partners
echo "📊 Step 5: Partner count:"
echo "SELECT COUNT(*) FROM tbl_partners;" | sqlite3 narrrf_world.sqlite

echo ""
echo "🎉 GALLERY FIELD MIGRATION COMPLETE!"
echo "✅ Database backed up"
echo "✅ gallery_images field added"
echo "✅ Backup saved to /data for deployment"

