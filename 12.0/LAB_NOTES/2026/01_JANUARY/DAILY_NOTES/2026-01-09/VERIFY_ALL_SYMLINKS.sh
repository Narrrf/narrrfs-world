#!/bin/bash
# 🔍 VERIFY ALL PERSISTENT STORAGE & SYMLINKS
# Created: January 9, 2026
# Purpose: Verify all persistent directories and symlinks on Render

echo "=== PERSISTENT STORAGE & SYMLINK VERIFICATION ==="
echo ""

# 1. PARTNER IMAGES
echo "📁 PARTNER IMAGES:"
echo "  Persistent: /data/img/partners/"
echo "  Symlink: /var/www/html/img/partners/"
ls -la /var/www/html/img/partners 2>/dev/null | head -2
echo "  Files in persistent storage: $(find /data/img/partners/ -type f 2>/dev/null | wc -l)"
echo ""

# 2. THREE.JS ASSETS
echo "📁 THREE.JS ASSETS:"
echo "  Base: /data/public/three.js/public/"
echo ""

echo "  ✓ textures/3d models/"
ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | grep "3d models" || echo "    MISSING"
echo "    Files: $(find /data/public/three.js/public/textures/3d\ models/ -type f 2>/dev/null | wc -l)"
echo ""

echo "  ✓ textures/grass/"
ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | grep grass || echo "    MISSING"
echo "    Files: $(find /data/public/three.js/public/textures/grass/ -type f 2>/dev/null | wc -l)"
echo ""

echo "  ✓ textures/backgrounds/"
ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | grep backgrounds || echo "    MISSING"
echo "    Files: $(find /data/public/three.js/public/textures/backgrounds/ -type f 2>/dev/null | wc -l)"
echo ""

echo "  ✓ textures/blocks/"
ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | grep blocks || echo "    MISSING"
echo "    Files: $(find /data/public/three.js/public/textures/blocks/ -type f 2>/dev/null | wc -l)"
echo ""

echo "  ✓ textures/plants/"
ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | grep plants || echo "    MISSING"
echo "    Files: $(find /data/public/three.js/public/textures/plants/ -type f 2>/dev/null | wc -l)"
echo ""

echo "  ✓ sounds/"
ls -la /var/www/html/public/three.js/public/ 2>/dev/null | grep "^l.*sounds" || echo "    MISSING"
echo "    Files: $(find /data/public/three.js/public/sounds/ -type f 2>/dev/null | wc -l)"
echo ""

echo "  ✓ audio/"
ls -la /var/www/html/public/three.js/public/ 2>/dev/null | grep "^l.*audio" || echo "    MISSING"
echo "    Files: $(find /data/public/three.js/public/audio/ -type f 2>/dev/null | wc -l)"
echo ""

echo "  ✓ models/"
ls -la /var/www/html/public/three.js/public/ 2>/dev/null | grep "^l.*models" || echo "    MISSING"
echo "    Files: $(find /data/public/three.js/public/models/ -type f 2>/dev/null | wc -l)"
echo ""

echo "  ✓ videos/"
ls -la /var/www/html/public/three.js/public/ 2>/dev/null | grep "^l.*videos" || echo "    MISSING"
echo "    Files: $(find /data/public/three.js/public/videos/ -type f 2>/dev/null | wc -l)"
echo ""

# 3. GLYPH ASSETS
echo "📁 GLYPH ASSETS:"
echo "  Persistent: /data/public/glyph/glyph3d/"
echo "  Symlink: /var/www/html/public/glyph/glyph3d/"
ls -la /var/www/html/public/glyph/glyph3d 2>/dev/null | head -2 || echo "    MISSING"
echo "  Files in persistent storage: $(find /data/public/glyph/glyph3d/ -type f 2>/dev/null | wc -l)"
echo ""

echo "=== VERIFICATION COMPLETE ==="
echo ""
echo "📊 SUMMARY:"
echo "  Partner Images: $(find /data/img/partners/ -type f 2>/dev/null | wc -l) files"
echo "  3D Models: $(find /data/public/three.js/public/textures/3d\ models/ -type f 2>/dev/null | wc -l) files"
echo "  Grass: $(find /data/public/three.js/public/textures/grass/ -type f 2>/dev/null | wc -l) files"
echo "  Backgrounds: $(find /data/public/three.js/public/textures/backgrounds/ -type f 2>/dev/null | wc -l) files"
echo "  Blocks: $(find /data/public/three.js/public/textures/blocks/ -type f 2>/dev/null | wc -l) files"
echo "  Plants: $(find /data/public/three.js/public/textures/plants/ -type f 2>/dev/null | wc -l) files"
echo "  Sounds: $(find /data/public/three.js/public/sounds/ -type f 2>/dev/null | wc -l) files"
echo "  Audio: $(find /data/public/three.js/public/audio/ -type f 2>/dev/null | wc -l) files"
echo "  Models: $(find /data/public/three.js/public/models/ -type f 2>/dev/null | wc -l) files"
echo "  Videos: $(find /data/public/three.js/public/videos/ -type f 2>/dev/null | wc -l) files"
echo "  Glyph3d: $(find /data/public/glyph/glyph3d/ -type f 2>/dev/null | wc -l) files"
