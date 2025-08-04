<?php
// CRITICAL: Clean up multiple records per user in tbl_user_scores
// This script will consolidate all records for each user into a single record

$db_path = '/var/www/html/db/narrrf_world.sqlite';
if (!file_exists($db_path)) {
    echo "Database not found: $db_path\n";
    exit;
}

try {
    $db = new SQLite3($db_path);
    $db->enableExceptions(true);
    
    echo "🔧 Starting database cleanup...\n";
    
    // Get all users with multiple records
    $stmt = $db->prepare('
        SELECT user_id, COUNT(*) as record_count, SUM(score) as total_score
        FROM tbl_user_scores 
        GROUP BY user_id 
        HAVING COUNT(*) > 1
        ORDER BY record_count DESC
    ');
    $result = $stmt->execute();
    
    $usersWithMultipleRecords = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $usersWithMultipleRecords[] = $row;
    }
    
    echo "Found " . count($usersWithMultipleRecords) . " users with multiple records:\n";
    
    foreach ($usersWithMultipleRecords as $user) {
        echo "User ID: {$user['user_id']} - {$user['record_count']} records - Total: {$user['total_score']}\n";
        
        // Delete all existing records for this user
        $deleteStmt = $db->prepare('DELETE FROM tbl_user_scores WHERE user_id = ?');
        $deleteStmt->bindValue(1, $user['user_id'], SQLITE3_TEXT);
        $deleteStmt->execute();
        
        // Insert one new record with the correct total
        $insertStmt = $db->prepare('INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)');
        $insertStmt->bindValue(1, $user['user_id'], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $user['total_score'], SQLITE3_INTEGER);
        $insertStmt->bindValue(3, 'discord', SQLITE3_TEXT);
        $insertStmt->bindValue(4, 'cleanup', SQLITE3_TEXT);
        $insertStmt->execute();
        
        echo "  ✅ Cleaned up - now has 1 record with total: {$user['total_score']}\n";
    }
    
    // Verify cleanup
    $verifyStmt = $db->prepare('
        SELECT user_id, COUNT(*) as record_count
        FROM tbl_user_scores 
        GROUP BY user_id 
        HAVING COUNT(*) > 1
    ');
    $verifyResult = $verifyStmt->execute();
    
    $remainingMultipleRecords = [];
    while ($row = $verifyResult->fetchArray(SQLITE3_ASSOC)) {
        $remainingMultipleRecords[] = $row;
    }
    
    if (empty($remainingMultipleRecords)) {
        echo "\n✅ SUCCESS: All users now have exactly 1 record each!\n";
    } else {
        echo "\n❌ WARNING: " . count($remainingMultipleRecords) . " users still have multiple records!\n";
    }
    
    // Show final stats
    $totalUsers = $db->query('SELECT COUNT(DISTINCT user_id) FROM tbl_user_scores')->fetchArray(SQLITE3_NUM)[0];
    $totalRecords = $db->query('SELECT COUNT(*) FROM tbl_user_scores')->fetchArray(SQLITE3_NUM)[0];
    
    echo "\n📊 Final Database Stats:\n";
    echo "Total Users: $totalUsers\n";
    echo "Total Records: $totalRecords\n";
    echo "Records per User: " . ($totalUsers > 0 ? round($totalRecords / $totalUsers, 2) : 0) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$db->close();
echo "\n🔧 Database cleanup completed!\n";
?> 