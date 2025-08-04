<?php
// 🔧 Fix Missing Score Records Script
// This script finds users who have adjustment records but no score records
// and creates the missing score records based on their adjustment history

// Use Render production database path
$db_path = '/var/www/html/db/narrrf_world.sqlite';

if (!file_exists($db_path)) {
    echo "❌ Database not found: $db_path\n";
    exit(1);
}

try {
    $db = new SQLite3($db_path);
    echo "🔧 Starting fix for missing score records...\n";
    
    // Find users who have adjustments but no scores
    $result = $db->query("
        SELECT DISTINCT sa.user_id, u.username
        FROM tbl_score_adjustments sa
        LEFT JOIN tbl_users u ON sa.user_id = u.discord_id
        WHERE sa.user_id NOT IN (
            SELECT DISTINCT user_id FROM tbl_user_scores
        )
        ORDER BY sa.user_id
    ");
    
    $usersToFix = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $usersToFix[] = $row;
    }
    
    if (empty($usersToFix)) {
        echo "✅ No users found with missing score records!\n";
        exit(0);
    }
    
    echo "📊 Found " . count($usersToFix) . " users with missing score records:\n";
    foreach ($usersToFix as $user) {
        echo "  - {$user['username']} ({$user['user_id']})\n";
    }
    
    echo "\n🔧 Fixing missing score records...\n";
    $fixedCount = 0;
    
    foreach ($usersToFix as $user) {
        $userId = $user['user_id'];
        $username = $user['username'];
        
        // Calculate total balance from adjustments
        $stmt = $db->prepare("
            SELECT 
                SUM(CASE WHEN action = 'add' THEN amount ELSE 0 END) as total_added,
                SUM(CASE WHEN action = 'remove' THEN amount ELSE 0 END) as total_removed,
                SUM(CASE WHEN action = 'set' THEN amount ELSE 0 END) as total_set
            FROM tbl_score_adjustments 
            WHERE user_id = ?
        ");
        $stmt->bindValue(1, $userId, SQLITE3_TEXT);
        $result = $stmt->execute();
        $adjustment = $result->fetchArray(SQLITE3_ASSOC);
        
        // Calculate final balance
        $totalAdded = $adjustment['total_added'] ?? 0;
        $totalRemoved = $adjustment['total_removed'] ?? 0;
        $totalSet = $adjustment['total_set'] ?? 0;
        
        // For 'set' actions, we need to find the final set amount
        $finalSetStmt = $db->prepare("
            SELECT amount FROM tbl_score_adjustments 
            WHERE user_id = ? AND action = 'set' 
            ORDER BY timestamp DESC 
            LIMIT 1
        ");
        $finalSetStmt->bindValue(1, $userId, SQLITE3_TEXT);
        $finalSetResult = $finalSetStmt->execute();
        $finalSet = $finalSetResult->fetchArray(SQLITE3_ASSOC);
        $finalSetAmount = $finalSet ? $finalSet['amount'] : 0;
        
        // Calculate balance: if there are 'set' actions, use the final set amount
        // Otherwise, use added - removed
        if ($totalSet > 0) {
            $balance = $finalSetAmount;
        } else {
            $balance = max(0, $totalAdded - $totalRemoved);
        }
        
        // Insert the score record
        $insertStmt = $db->prepare("
            INSERT INTO tbl_user_scores (user_id, score, game, source, timestamp) 
            VALUES (?, ?, ?, ?, datetime('now'))
        ");
        $insertStmt->bindValue(1, $userId, SQLITE3_TEXT);
        $insertStmt->bindValue(2, $balance, SQLITE3_INTEGER);
        $insertStmt->bindValue(3, 'discord', SQLITE3_TEXT);
        $insertStmt->bindValue(4, 'fix_missing_scores', SQLITE3_TEXT);
        $insertStmt->execute();
        
        echo "✅ Fixed: {$username} - Balance: {$balance} \$DSPOINC\n";
        $fixedCount++;
    }
    
    echo "\n🎉 FIX COMPLETE!\n";
    echo "✅ Fixed {$fixedCount} users with missing score records\n";
    echo "📊 All users should now have proper balance display\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 