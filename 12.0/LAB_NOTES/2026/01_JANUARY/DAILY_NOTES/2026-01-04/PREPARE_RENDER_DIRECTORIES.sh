#!/bin/bash
# Prepare Render directories for asset upload
# Run this in Render shell: /var/www/html#

echo "🔧 Preparing directories for asset upload..."
echo ""

# Create directories
echo "📁 Creating directories..."
mkdir -p /var/www/html/public/three.js/public/textures/3d\ models/
mkdir -p /var/www/html/public/three.js/public/sounds/
mkdir -p /var/www/html/public/three.js/public/audio/

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 755 /var/www/html/public/three.js/public/
chown -R www-data:www-data /var/www/html/public/three.js/public/

# Verify
echo ""
echo "✅ Verification:"
echo "Directories created:"
ls -la /var/www/html/public/three.js/public/

echo ""
echo "✅ Ready for upload!"
echo "Next: Run upload commands from your LOCAL Windows machine"
echo "See: QUICK_UPLOAD_COMMANDS.md"

