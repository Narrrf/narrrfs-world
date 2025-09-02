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

    // Get top users by total score
    $stmt = $db->prepare('
        SELECT us.user_id, SUM(us.score) as total_score, u.username
        FROM tbl_user_scores us
        LEFT JOIN tbl_users u ON us.user_id = u.discord_id
        GROUP BY us.user_id
        ORDER BY total_score DESC
        LIMIT 10
    ');
    $result = $stmt->execute();

    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 