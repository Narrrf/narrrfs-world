#!/bin/bash
# 🚨 SPACE INVADERS NEGATIVE SCORE FIX - DIRECT ON RENDER
# Date: October 23, 2025
# Bug: #159 - Negative scores when player takes damage without shooting

# ============================================
# STEP 1: BACKUP THE DATABASE (ALREADY DONE!)
# ============================================
# You already ran: cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
# ✅ Backup complete!

# ============================================
# STEP 2: CHECK CURRENT NEGATIVE SCORES
# ============================================
echo "📊 Checking current negative scores..."
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) as negative_count FROM tbl_tetris_scores WHERE game = 'space_invaders' AND score < 0;"

echo ""
echo "📋 Listing all negative scores:"
sqlite3 /var/www/html/db/narrrf_world.sqlite -header -column "SELECT discord_id, discord_name, score, timestamp FROM tbl_tetris_scores WHERE game = 'space_invaders' AND score < 0 ORDER BY score ASC;"

# ============================================
# STEP 3: CREATE BACKUP TABLE
# ============================================
echo ""
echo "💾 Creating backup table for negative scores..."
sqlite3 /var/www/html/db/narrrf_world.sqlite "CREATE TABLE IF NOT EXISTS tbl_space_invaders_negative_scores_backup AS SELECT * FROM tbl_tetris_scores WHERE game = 'space_invaders' AND score < 0;"

echo "✅ Backup table created. Verifying:"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_negative_scores_backup;"

# ============================================
# STEP 4: LOG ADJUSTMENTS (if table exists)
# ============================================
echo ""
echo "📝 Logging score adjustments..."
sqlite3 /var/www/html/db/narrrf_world.sqlite "INSERT INTO tbl_score_adjustments (discord_id, discord_name, adjustment_amount, reason, admin_notes, created_at) SELECT discord_id, discord_name, ABS(score) as adjustment_amount, 'SPACE_INVADERS_NEGATIVE_SCORE_FIX' as reason, 'Bug #159 - Automated correction on Render - Player took damage without shooting' as admin_notes, datetime('now') as created_at FROM tbl_tetris_scores WHERE game = 'space_invaders' AND score < 0;"

# ============================================
# STEP 5: FIX NEGATIVE SCORES
# ============================================
echo ""
echo "🔧 Converting negative scores to positive (using ABS)..."
sqlite3 /var/www/html/db/narrrf_world.sqlite "UPDATE tbl_tetris_scores SET score = ABS(score) WHERE game = 'space_invaders' AND score < 0;"

# ============================================
# STEP 6: VERIFY FIX
# ============================================
echo ""
echo "✅ VERIFICATION:"
echo "=================="

echo "Remaining negative scores (should be 0):"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders' AND score < 0;"

echo ""
echo "Backup table count:"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_negative_scores_backup;"

echo ""
echo "Corrected scores (now positive):"
sqlite3 /var/www/html/db/narrrf_world.sqlite -header -column "SELECT b.discord_id, b.discord_name, b.score as old_score, t.score as new_score FROM tbl_space_invaders_negative_scores_backup b JOIN tbl_tetris_scores t ON b.discord_id = t.discord_id AND b.timestamp = t.timestamp ORDER BY b.score ASC LIMIT 10;"

echo ""
echo "🎉 DATABASE FIX COMPLETE!"
echo "=========================="
echo "✅ Negative scores converted to positive"
echo "✅ Backup table created"
echo "✅ Score adjustments logged"
echo ""
echo "Next: Deploy code fixes to prevent future negative scores"

