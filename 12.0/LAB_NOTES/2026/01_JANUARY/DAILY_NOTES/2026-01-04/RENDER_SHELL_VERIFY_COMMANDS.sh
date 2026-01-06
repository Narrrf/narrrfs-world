#!/bin/bash

# ✅ Verification Commands for Render Shell
# Run these commands one by one in your Render shell

echo "=== 1. Checking PHP Syntax ==="
php -l /var/www/html/api/discord/upload-assets.php

echo ""
echo "=== 2. Checking /data/ directories ==="
ls -la /data/public/three.js/public/

echo ""
echo "=== 3. Checking symlinks ==="
ls -la /var/www/html/public/three.js/public/textures/
ls -la /var/www/html/public/three.js/public/sounds/

echo ""
echo "=== 4. Verifying /data/ directory permissions ==="
stat /data/public/three.js/public/

echo ""
echo "=== 5. Testing if we can write to /data/ ==="
touch /data/public/three.js/public/test.txt 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Can write to /data/"
    rm /data/public/three.js/public/test.txt
else
    echo "❌ Cannot write to /data/ - check permissions"
fi

echo ""
echo "=== Verification Complete ==="

