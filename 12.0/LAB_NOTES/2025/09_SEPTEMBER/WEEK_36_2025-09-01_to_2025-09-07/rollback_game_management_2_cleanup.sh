#!/bin/bash
# 🔄 ROLLBACK SCRIPT: GAME MANAGEMENT 2.0 CLEANUP - 2025-01-28
# Purpose: Restore admin interface to exact state after Game Management 2.0 cleanup

echo "🔄 Starting Game Management 2.0 cleanup rollback..."

# Step 1: Create backup
echo "📦 Creating backup of current admin interface..."
cp narrrfs-world/public/admin-interface.html narrrfs-world/public/admin-interface.html.backup-$(date +%Y%m%d-%H%M%S)
echo "✅ Backup created successfully"

# Step 2: Remove Game Management 2.0 tab button
echo "🗑️ Removing Game Management 2.0 tab button..."
sed -i '/<button class="tab-btn px-4 py-2 rounded" data-tab="games2">🚀 Game Management 2.0<\/button>/d' narrrfs-world/public/admin-interface.html
echo "✅ Tab button removed"

# Step 3: Remove Game Management 2.0 HTML content section
echo "🗑️ Removing Game Management 2.0 HTML content..."
# Remove from start of section to end
sed -i '/<!-- Game Management 2.0 Tab -->/,/<!-- END OF GAME MANAGEMENT 2.0 TAB -->/d' narrrfs-world/public/admin-interface.html
echo "✅ HTML content removed"

# Step 4: Remove Game Management 2.0 auto-load logic
echo "🗑️ Removing Game Management 2.0 auto-load logic..."
sed -i '/} else if (tabName === '\''games2'\'') {/,/}/d' narrrfs-world/public/admin-interface.html
echo "✅ Auto-load logic removed"

# Step 5: Remove Game Management 2.0 JavaScript functions
echo "🗑️ Removing Game Management 2.0 JavaScript functions..."
# Remove the entire function section
sed -i '/\/\/ 🚀 GAME MANAGEMENT 2.0 FUNCTIONS - Advanced Season Management/,/\/\/ END OF GAME MANAGEMENT 2.0 FUNCTIONS/d' narrrfs-world/public/admin-interface.html
echo "✅ JavaScript functions removed"

# Step 6: Remove individual function calls
echo "🗑️ Removing Game Management 2.0 function calls..."
sed -i '/loadGameManagement2Data()/d' narrrfs-world/public/admin-interface.html
sed -i '/loadSeasonOverview2()/d' narrrfs-world/public/admin-interface.html
sed -i '/loadGameStatsBySeason2()/d' narrrfs-world/public/admin-interface.html
sed -i '/loadTopPerformers2()/d' narrrfs-world/public/admin-interface.html
sed -i '/loadSeasonComparison2()/d' narrrfs-world/public/admin-interface.html
sed -i '/startLiveActivityFeed2()/d' narrrfs-world/public/admin-interface.html
echo "✅ Function calls removed"

# Step 7: Remove console logs and comments
echo "🗑️ Removing Game Management 2.0 console logs and comments..."
sed -i '/console.log('\''🚀 Loading Game Management 2.0 data...'\'')/d' narrrfs-world/public/admin-interface.html
sed -i '/console.log('\''✅ Game Management 2.0 data loaded successfully'\'')/d' narrrfs-world/public/admin-interface.html
sed -i '/console.error('\''❌ Error loading Game Management 2.0 data:'\''/d' narrrfs-world/public/admin-interface.html
sed -i '/addLog('\''🚀 Game management 2.0 tab auto-loaded'\'')/d' narrrfs-world/public/admin-interface.html
sed -i '/addLog('\''🔄 Refreshing all Game Management 2.0 data...'\'')/d' narrrfs-world/public/admin-interface.html
sed -i '/addLog('\''✅ All Game Management 2.0 data refreshed'\'')/d' narrrfs-world/public/admin-interface.html
sed -i '/addLog('\''🧪 Testing Game Management 2.0 tab functions...'\'')/d' narrrfs-world/public/admin-interface.html
sed -i '/addLog('\''🧪 Game Management 2.0 tab test completed'\'')/d' narrrfs-world/public/admin-interface.html
echo "✅ Console logs and comments removed"

# Step 8: Clean up any remaining Game Management 2.0 references
echo "🧹 Cleaning up remaining Game Management 2.0 references..."
sed -i '/Game Management 2.0/d' narrrfs-world/public/admin-interface.html
sed -i '/games2/d' narrrfs-world/public/admin-interface.html
echo "✅ Remaining references cleaned"

# Step 9: Verify cleanup
echo "🔍 Verifying cleanup..."
if grep -q "Game Management 2.0" narrrfs-world/public/admin-interface.html; then
    echo "⚠️ Warning: Some Game Management 2.0 references may still exist"
else
    echo "✅ No Game Management 2.0 references found"
fi

if grep -q "games2" narrrfs-world/public/admin-interface.html; then
    echo "⚠️ Warning: Some games2 references may still exist"
else
    echo "✅ No games2 references found"
fi

echo "🎉 Game Management 2.0 cleanup rollback completed!"
echo "📊 Expected results: ~600 lines removed, clean admin interface"
echo "✅ Admin interface now matches the state after Game Management 2.0 cleanup"
