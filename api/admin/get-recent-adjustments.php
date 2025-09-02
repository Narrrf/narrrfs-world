<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 🔧 CRITICAL FIX: Local development bypass and database path
$isLocalDevelopment = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';
if ($isLocalDevelopment) {
    // Use local database path
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
} else {
    // Use production database path
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
}

try {
    // Connect to database with correct path
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);

    // Get recent adjustments (last 10)
    $stmt = $db->prepare('
        SELECT user_id, admin_id, amount, action, reason, timestamp 
        FROM tbl_score_adjustments 
        ORDER BY timestamp DESC 
        LIMIT 10
    ');
    $result = $stmt->execute();

    $adjustments = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $adjustments[] = $row;
    }

    echo json_encode([
        'success' => true,
        'adjustments' => $adjustments
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 