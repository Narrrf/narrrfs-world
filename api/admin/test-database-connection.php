<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

try {
    $db = getSQLite3Connection();
    
    // Test basic connection
    $testStmt = $db->prepare('SELECT COUNT(*) as count FROM sqlite_master WHERE type="table"');
    $result = $testStmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$row) {
        throw new Exception('Database connection test failed');
    }
    
    // Test user scores table
    $userStmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_user_scores');
    $userResult = $userStmt->execute();
    $userRow = $userResult->fetchArray(SQLITE3_ASSOC);
    
    // Test users table
    $usersStmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_users');
    $usersResult = $usersStmt->execute();
    $usersRow = $usersResult->fetchArray(SQLITE3_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'database_path' => getDatabasePath(),
        'tables_count' => $row['count'],
        'user_scores_count' => $userRow ? $userRow['count'] : 'N/A',
        'users_count' => $usersRow ? $usersRow['count'] : 'N/A'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage(),
        'database_path' => getDatabasePath()
    ]);
}

$db->close();
?>
