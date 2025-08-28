<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Test basic database connection
    $testQuery = $pdo->query("SELECT COUNT(*) as total FROM sqlite_master WHERE type='table'");
    $tableCount = $testQuery->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Test specific tables for Game Management
    $tables = [
        'tbl_tetris_scores',
        'tbl_cheese_clicks', 
        'tbl_race_participants',
        'tbl_users',
        'tbl_seasons'
    ];
    
    $tableStatus = [];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $tableStatus[$table] = [
                'exists' => true,
                'count' => $result['count']
            ];
        } catch (Exception $e) {
            $tableStatus[$table] = [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Test game data queries
    $gameStats = [];
    
    // Tetris stats
    try {
        $tetrisStmt = $pdo->prepare("SELECT COUNT(*) as total_scores, MAX(score) as best_score FROM tbl_tetris_scores WHERE game = 'tetris'");
        $tetrisStmt->execute();
        $tetrisResult = $tetrisStmt->fetch(PDO::FETCH_ASSOC);
        $gameStats['tetris'] = [
            'total_scores' => $tetrisResult['total_scores'],
            'best_score' => $tetrisResult['best_score']
        ];
    } catch (Exception $e) {
        $gameStats['tetris'] = ['error' => $e->getMessage()];
    }
    
    // Snake stats
    try {
        $snakeStmt = $pdo->prepare("SELECT COUNT(*) as total_scores, MAX(score) as best_score FROM tbl_tetris_scores WHERE game = 'snake'");
        $snakeStmt->execute();
        $snakeResult = $snakeStmt->fetch(PDO::FETCH_ASSOC);
        $gameStats['snake'] = [
            'total_scores' => $snakeResult['total_scores'],
            'best_score' => $snakeResult['best_score']
        ];
    } catch (Exception $e) {
        $gameStats['snake'] = ['error' => $e->getMessage()];
    }
    
    // Space Invaders stats
    try {
        $spaceStmt = $pdo->prepare("SELECT COUNT(*) as total_scores, MAX(score) as best_score FROM tbl_tetris_scores WHERE game = 'space_invaders'");
        $spaceStmt->execute();
        $spaceResult = $spaceStmt->fetch(PDO::FETCH_ASSOC);
        $gameStats['space_invaders'] = [
            'total_scores' => $spaceResult['total_scores'],
            'best_score' => $spaceResult['best_score']
        ];
    } catch (Exception $e) {
        $gameStats['space_invaders'] = ['error' => $e->getMessage()];
    }
    
    // Cheese Hunt stats
    try {
        $cheeseStmt = $pdo->prepare("SELECT COUNT(*) as total_clicks FROM tbl_cheese_clicks");
        $cheeseStmt->execute();
        $cheeseResult = $cheeseStmt->fetch(PDO::FETCH_ASSOC);
        $gameStats['cheese_hunt'] = [
            'total_clicks' => $cheeseResult['total_clicks']
        ];
    } catch (Exception $e) {
        $gameStats['cheese_hunt'] = ['error' => $e->getMessage()];
    }
    
    // Discord Race stats
    try {
        $raceStmt = $pdo->prepare("SELECT COUNT(*) as total_races FROM tbl_race_participants");
        $raceStmt->execute();
        $raceResult = $raceStmt->fetch(PDO::FETCH_ASSOC);
        $gameStats['discord_race'] = [
            'total_races' => $raceResult['total_races']
        ];
    } catch (Exception $e) {
        $gameStats['discord_race'] = ['error' => $e->getMessage()];
    }
    
    // User stats
    try {
        $userStmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM tbl_users");
        $userStmt->execute();
        $userResult = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userStats = [
            'total_users' => $userResult['total_users']
        ];
    } catch (Exception $e) {
        $userStats = ['error' => $e->getMessage()];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Game Management API test completed successfully',
        'data' => [
            'database_status' => [
                'connected' => true,
                'total_tables' => $tableCount,
                'table_status' => $tableStatus
            ],
            'game_stats' => $gameStats,
            'user_stats' => $userStats,
            'test_timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
