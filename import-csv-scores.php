<?php
// CRITICAL: Import accurate user scores from CSV to fix database
// This will replace all corrupted data with the accurate CSV data

$db_path = '/var/www/html/db/narrrf_world.sqlite';
$csv_file = __DIR__ . '/public/leaderboard.csv';

if (!file_exists($db_path)) {
    echo "❌ Database not found: $db_path\n";
    exit;
}

if (!file_exists($csv_file)) {
    echo "❌ CSV file not found: $csv_file\n";
    exit;
}

try {
    $db = new SQLite3($db_path);
    $db->enableExceptions(true);
    
    echo "🔧 Starting CSV import to fix database...\n";
    
    // Read CSV file
    $csv_data = array_map('str_getcsv', file($csv_file));
    $headers = array_shift($csv_data); // Remove header row
    
    echo "📊 Found " . count($csv_data) . " users in CSV\n";
    
    // Start transaction
    $db->exec('BEGIN TRANSACTION');
    
    // Clear existing user scores table
    $db->exec('DELETE FROM tbl_user_scores');
    echo "🗑️  Cleared existing tbl_user_scores table\n";
    
    $imported_count = 0;
    $errors = [];
    
    foreach ($csv_data as $row) {
        if (count($row) < 3) {
            $errors[] = "Invalid row: " . implode(',', $row);
            continue;
        }
        
        $username = trim($row[0]);
        $user_id = trim($row[1]);
        $points_balance = (int)trim($row[2]);
        
        // Skip if no user_id or invalid points
        if (empty($user_id) || $points_balance === 0) {
            continue;
        }
        
        try {
            // Insert single record per user
            $stmt = $db->prepare('
                INSERT INTO tbl_user_scores (user_id, score, game, source) 
                VALUES (?, ?, ?, ?)
            ');
            $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
            $stmt->bindValue(2, $points_balance, SQLITE3_INTEGER);
            $stmt->bindValue(3, 'discord', SQLITE3_TEXT);
            $stmt->bindValue(4, 'csv_import', SQLITE3_TEXT);
            $stmt->execute();
            
            $imported_count++;
            
            if ($imported_count % 10 === 0) {
                echo "✅ Imported $imported_count users...\n";
            }
            
        } catch (Exception $e) {
            $errors[] = "Error importing $username ($user_id): " . $e->getMessage();
        }
    }
    
    // Commit transaction
    $db->exec('COMMIT');
    
    echo "\n🎉 IMPORT COMPLETE!\n";
    echo "✅ Successfully imported $imported_count users\n";
    
    if (!empty($errors)) {
        echo "⚠️  Errors encountered:\n";
        foreach ($errors as $error) {
            echo "   - $error\n";
        }
    }
    
    // Verify import
    $result = $db->query('SELECT COUNT(*) as count FROM tbl_user_scores');
    $count = $result->fetchArray(SQLITE3_ASSOC)['count'];
    echo "📊 Total records in database: $count\n";
    
    // Check for Narrrf specifically
    $result = $db->query("SELECT user_id, score FROM tbl_user_scores WHERE user_id = '328601656659017732'");
    $narrrf = $result->fetchArray(SQLITE3_ASSOC);
    if ($narrrf) {
        echo "✅ Narrrf balance: " . number_format($narrrf['score']) . " $DSPOINC\n";
    } else {
        echo "❌ Narrrf not found in database!\n";
    }
    
    // Add entry to score adjustments table to record this sync
    $current_time = date('Y-m-d H:i:s');
    $sync_description = "Database sync from CSV - Imported $imported_count users with accurate balances";
    
    $stmt = $db->prepare('
        INSERT INTO tbl_score_adjustments (user_id, adjustment_amount, reason, adjusted_by, adjusted_at) 
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->bindValue(1, 'SYSTEM_SYNC', SQLITE3_TEXT);
    $stmt->bindValue(2, $imported_count, SQLITE3_INTEGER);
    $stmt->bindValue(3, $sync_description, SQLITE3_TEXT);
    $stmt->bindValue(4, 'ADMIN_SYSTEM', SQLITE3_TEXT);
    $stmt->bindValue(5, $current_time, SQLITE3_TEXT);
    $stmt->execute();
    
    echo "📝 Added sync record to score adjustments table\n";
    echo "🕐 Sync completed at: $current_time\n";
    
    echo "\n🔧 Database has been fixed with accurate CSV data!\n";
    
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 