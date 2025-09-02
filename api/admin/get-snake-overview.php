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

    // Get Snake overview statistics
    $stmt = $db->prepare('
        SELECT 
            COUNT(DISTINCT discord_id) as total_users,
            COUNT(*) as total_games,
            MAX(score) as best_score,
            AVG(score) as avg_score
        FROM tbl_tetris_scores 
        WHERE game = "snake" AND season = "season_2"
    ');
    
    $result = $stmt->execute();
    $data = $result->fetchArray(SQLITE3_ASSOC);
    
    // Get recent activity (last 24 hours)
    $stmt24h = $db->prepare('
        SELECT COUNT(*) as recent_games
        FROM tbl_tetris_scores 
        WHERE game = "snake" AND season = "season_2"
        AND timestamp >= datetime("now", "-1 day")
    ');
    
    $result24h = $stmt24h->execute();
    $data24h = $result24h->fetchArray(SQLITE3_ASSOC);
    
    // Get weekly activity
    $stmtWeek = $db->prepare('
        SELECT COUNT(*) as weekly_games
        FROM tbl_tetris_scores 
        WHERE game = "snake" AND season = "season_2"
        AND timestamp >= datetime("now", "-7 days")
    ');
    
    $resultWeek = $stmtWeek->execute();
    $dataWeek = $resultWeek->fetchArray(SQLITE3_ASSOC);
    
    $response = [
        'success' => true,
        'data' => [
            'total_users' => (int)$data['total_users'],
            'total_games' => (int)$data['total_games'],
            'best_score' => (int)$data['best_score'],
            'avg_score' => round((float)$data['avg_score'], 2),
            'recent_24h' => (int)$data24h['recent_games'],
            'weekly_games' => (int)$dataWeek['weekly_games']
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'data' => [
            'total_users' => 0,
            'total_games' => 0,
            'best_score' => 0,
            'avg_score' => 0,
            'recent_24h' => 0,
            'weekly_games' => 0
        ]
    ];
}

echo json_encode($response);
?>
