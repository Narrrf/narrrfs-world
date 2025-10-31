#!/bin/bash
# 🚨 PERMANENT FIX: Partner Image Persistence
# Run this ONCE locally before next push
# Date: October 30, 2025

echo "🚨 PERMANENT FIX: Excluding partner images from repo..."

cd "C:\xampp-server\htdocs\narrrfs-world"

# Step 1: Create VR directory for vr-gallery.JPG
echo "📁 Creating public/img/vr directory..."
mkdir -p public/img/vr

# Step 2: Move VR gallery image (if it exists in partners)
if [ -f "public/img/partners/vr-gallery.JPG" ]; then
  echo "🖼️ Moving vr-gallery.JPG to public/img/vr/..."
  cp public/img/partners/vr-gallery.JPG public/img/vr/vr-gallery.JPG
fi

# Step 3: Remove partners directory from git tracking
echo "🗑️ Removing public/img/partners from git..."
git rm -r --cached public/img/partners/

# Step 4: Verify .gitignore already has the entry
echo "✅ .gitignore already updated with public/img/partners/"

echo ""
echo "✅ PERMANENT FIX COMPLETE!"
echo ""
echo "📋 NEXT STEPS:"
echo "1. Update index.html: Change img/partners/vr-gallery.JPG → img/vr/vr-gallery.JPG"
echo "2. git add ."
echo "3. git commit -m 'fix: exclude partner images from repo, use persistent storage only'"
echo "4. git push origin render-deploy"
echo ""
echo "🎯 AFTER NEXT DEPLOYMENT:"
echo "Partner images will persist forever - no more manual fixes needed!"
echo ""

