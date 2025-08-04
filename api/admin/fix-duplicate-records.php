<?php
// Fix duplicate user records that are causing incorrect balance calculations
$db_path = '/var/www/html/db/narrrf_world.sqlite';

if (!file_exists($db_path)) {
    echo "❌ Database not found: $db_path\n";
    exit;
}

try {
    $db = new SQLite3($db_path);
    $db->enableExceptions(true);
    
    echo "🔧 Checking for duplicate user records...\n\n";
    
    // Check for duplicates
    $result = $db->query('
        SELECT user_id, COUNT(*) as count, SUM(score) as total_score
        FROM tbl_user_scores 
        GROUP BY user_id 
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ');
    
    $duplicates = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $duplicates[] = $row;
    }
    
    if (empty($duplicates)) {
        echo "✅ No duplicate records found!\n";
        exit;
    }
    
    echo "⚠️  Found " . count($duplicates) . " users with duplicate records:\n\n";
    
    foreach ($duplicates as $dup) {
        echo "User ID: {$dup['user_id']} - {$dup['count']} records - Total: " . number_format($dup['total_score']) . " \$DSPOINC\n";
    }
    
    echo "\n🔧 Fixing duplicate records...\n";
    
    // Start transaction
    $db->exec('BEGIN TRANSACTION');
    
    $fixed_count = 0;
    
    foreach ($duplicates as $dup) {
        $user_id = $dup['user_id'];
        
        // Get all records for this user
        $stmt = $db->prepare('SELECT * FROM tbl_user_scores WHERE user_id = ? ORDER BY score DESC');
        $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $records = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $records[] = $row;
        }
        
        if (count($records) > 1) {
            // Keep the record with the highest score (most recent/accurate)
            $best_record = $records[0];
            
            // Delete all records for this user
            $stmt = $db->prepare('DELETE FROM tbl_user_scores WHERE user_id = ?');
            $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
            $stmt->execute();
            
            // Insert the best record
            $stmt = $db->prepare('INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)');
            $stmt->bindValue(1, $best_record['user_id'], SQLITE3_TEXT);
            $stmt->bindValue(2, $best_record['score'], SQLITE3_INTEGER);
            $stmt->bindValue(3, $best_record['game'], SQLITE3_TEXT);
            $stmt->bindValue(4, $best_record['source'], SQLITE3_TEXT);
            $stmt->execute();
            
            echo "✅ Fixed: {$user_id} - Kept score: " . number_format($best_record['score']) . " \$DSPOINC\n";
            $fixed_count++;
        }
    }
    
    // Commit transaction
    $db->exec('COMMIT');
    
    echo "\n🎉 DUPLICATE FIX COMPLETE!\n";
    echo "✅ Fixed $fixed_count users with duplicate records\n";
    
    // Verify Narrrf's balance
    $result = $db->query("SELECT user_id, score FROM tbl_user_scores WHERE user_id = '328601656659017732'");
    $narrrf = $result->fetchArray(SQLITE3_ASSOC);
    if ($narrrf) {
        echo "✅ Narrrf balance: " . number_format($narrrf['score']) . " \$DSPOINC\n";
    } else {
        echo "❌ Narrrf not found in database!\n";
    }
    
    // Check total user count
    $result = $db->query('SELECT COUNT(*) as count FROM tbl_user_scores');
    $count = $result->fetchArray(SQLITE3_ASSOC)['count'];
    echo "📊 Total users in database: $count\n";
    
    echo "\n🔧 Database cleaned up successfully!\n";
    
} catch (Exception $e) {
    // Only rollback if transaction is active
    try {
        $db->exec('ROLLBACK');
    } catch (Exception $rollback_error) {
        // Transaction already committed or not active
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 