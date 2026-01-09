#!/bin/bash
# 🚀 UPLOAD MISSING CRITICAL FILES TO RENDER
# Created: January 9, 2026
# Purpose: Upload the 4 missing critical files causing 404 errors
# Run from local Windows PowerShell with Discord bot secret

# ============================================
# CRITICAL MISSING FILES (from Render verification)
# ============================================
# 1. grass.jpg - Missing in /data/public/three.js/public/textures/grass/
# 2. cloud.jpg - Missing in /data/public/three.js/public/textures/grass/
# 3. cheesetemple1.png - Missing in /data/public/three.js/public/textures/backgrounds/
# 4. level1.json - Missing in /data/public/three.js/public/models/cheese-temple/

# ============================================
# FILES TO UPLOAD (Local paths)
# ============================================
# Local paths (Windows):
# 1. public\textures\grass\grass.jpg
# 2. public\textures\grass\cloud.jpg
# 3. public\textures\backgrounds\cheesetemple1.png
# 4. public\models\cheese-temple\level1.json

# ============================================
# TARGET PATHS (Render /data/)
# ============================================
# Target paths (Render):
# 1. /data/public/three.js/public/textures/grass/grass.jpg
# 2. /data/public/three.js/public/textures/grass/cloud.jpg
# 3. /data/public/three.js/public/textures/backgrounds/cheesetemple1.png
# 4. /data/public/three.js/public/models/cheese-temple/level1.json

# ============================================
# UPLOAD COMMANDS (PowerShell)
# ============================================
# Run these commands from Windows PowerShell:
#
# $BOT_SECRET = "YOUR_DISCORD_BOT_SECRET"
#
# # Upload grass.jpg
# curl.exe -X POST `
#   -H "Authorization: $BOT_SECRET" `
#   -F "file=@public\textures\grass\grass.jpg" `
#   -F "target_path=/data/public/three.js/public/textures/grass/grass.jpg" `
#   https://narrrfs.world/api/discord/upload-assets.php
#
# # Upload cloud.jpg
# curl.exe -X POST `
#   -H "Authorization: $BOT_SECRET" `
#   -F "file=@public\textures\grass\cloud.jpg" `
#   -F "target_path=/data/public/three.js/public/textures/grass/cloud.jpg" `
#   https://narrrfs.world/api/discord/upload-assets.php
#
# # Upload cheesetemple1.png
# curl.exe -X POST `
#   -H "Authorization: $BOT_SECRET" `
#   -H "Content-Type: multipart/form-data" `
#   -F "file=@public\textures\backgrounds\cheesetemple1.png" `
#   -F "target_path=/data/public/three.js/public/textures/backgrounds/cheesetemple1.png" `
#   https://narrrfs.world/api/discord/upload-assets.php
#
# # Upload level1.json
# curl.exe -X POST `
#   -H "Authorization: $BOT_SECRET" `
#   -F "file=@public\models\cheese-temple\level1.json" `
#   -F "target_path=/data/public/three.js/public/models/cheese-temple/level1.json" `
#   https://narrrfs.world/api/discord/upload-assets.php

echo "📋 MISSING FILES UPLOAD GUIDE"
echo "======================================"
echo ""
echo "Run these commands from Windows PowerShell:"
echo ""
echo "1. Set bot secret:"
echo "   \$BOT_SECRET = 'YOUR_DISCORD_BOT_SECRET'"
echo ""
echo "2. Upload grass.jpg:"
echo "   curl.exe -X POST -H \"Authorization: \$BOT_SECRET\" -F \"file=@public\\textures\\grass\\grass.jpg\" -F \"target_path=/data/public/three.js/public/textures/grass/grass.jpg\" https://narrrfs.world/api/discord/upload-assets.php"
echo ""
echo "3. Upload cloud.jpg:"
echo "   curl.exe -X POST -H \"Authorization: \$BOT_SECRET\" -F \"file=@public\\textures\\grass\\cloud.jpg\" -F \"target_path=/data/public/three.js/public/textures/grass/cloud.jpg\" https://narrrfs.world/api/discord/upload-assets.php"
echo ""
echo "4. Upload cheesetemple1.png:"
echo "   curl.exe -X POST -H \"Authorization: \$BOT_SECRET\" -F \"file=@public\\textures\\backgrounds\\cheesetemple1.png\" -F \"target_path=/data/public/three.js/public/textures/backgrounds/cheesetemple1.png\" https://narrrfs.world/api/discord/upload-assets.php"
echo ""
echo "5. Upload level1.json:"
echo "   curl.exe -X POST -H \"Authorization: \$BOT_SECRET\" -F \"file=@public\\models\\cheese-temple\\level1.json\" -F \"target_path=/data/public/three.js/public/models/cheese-temple/level1.json\" https://narrrfs.world/api/discord/upload-assets.php"
echo ""
echo "======================================"
echo "✅ After upload, verify files exist:"
echo "   bash /var/www/html/12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-09/VERIFY_RENDER_STATUS.sh"
echo ""
