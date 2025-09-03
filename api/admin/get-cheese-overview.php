<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// 🔒 SECURE AUTHENTICATION: Use centralized admin auth
require_once __DIR__ . '/../config/admin-auth.php';
checkAdminAuthentication();

try {
    // Use centralized database path function
    $dbPath = getDatabasePath();
    
    // Connect to database with correct path
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);

    // Get Cheese Hunt overview statistics
    $stmt = $db->prepare('
        SELECT 
            COUNT(DISTINCT user_wallet) as total_users,
            COUNT(*) as total_clicks
        FROM tbl_cheese_clicks 
        WHERE season = "season_2"
    ');
    
    $result = $stmt->execute();
    $data = $result->fetchArray(SQLITE3_ASSOC);
    
    // Get recent activity (last 24 hours)
    $stmt24h = $db->prepare('
        SELECT COUNT(*) as recent_clicks
        FROM tbl_cheese_clicks 
        WHERE season = "season_2" 
        AND timestamp >= datetime("now", "-1 day")
    ');
    
    $result24h = $stmt24h->execute();
    $data24h = $result24h->fetchArray(SQLITE3_ASSOC);
    
    // Get weekly activity
    $stmtWeek = $db->prepare('
        SELECT COUNT(*) as weekly_clicks
        FROM tbl_cheese_clicks 
        WHERE season = "season_2" 
        AND timestamp >= datetime("now", "-7 days")
    ');
    
    $resultWeek = $stmtWeek->execute();
    $dataWeek = $resultWeek->fetchArray(SQLITE3_ASSOC);
    
    $response = [
        'success' => true,
        'data' => [
            'total_users' => (int)$data['total_users'],
            'total_clicks' => (int)$data['total_clicks'],
            'recent_24h' => (int)$data24h['recent_clicks'],
            'weekly_clicks' => (int)$dataWeek['weekly_clicks']
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'data' => [
            'total_users' => 0,
            'total_clicks' => 0,
            'recent_24h' => 0,
            'weekly_clicks' => 0
        ]
    ];
}

echo json_encode($response);
?>
