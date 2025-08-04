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

// Database path
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get Tetris statistics
    $tetris_stats = [];
    
    // Total Tetris scores
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_scores FROM tbl_tetris_scores WHERE game = 'tetris'");
    $stmt->execute();
    $tetris_stats['total_scores'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_scores'];
    
    // Unique Tetris players
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_tetris_scores WHERE game = 'tetris'");
    $stmt->execute();
    $tetris_stats['unique_players'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_players'];
    
    // Highest Tetris score
    $stmt = $pdo->prepare("SELECT MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'tetris'");
    $stmt->execute();
    $tetris_stats['max_score'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['max_score'];
    
    // Average Tetris score
    $stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM tbl_tetris_scores WHERE game = 'tetris'");
    $stmt->execute();
    $tetris_stats['avg_score'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 2);
    
    // Recent Tetris activity (last 24h)
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'tetris' AND timestamp >= datetime('now', '-24 hours')");
    $stmt->execute();
    $tetris_stats['recent_24h'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];
    
    // Recent Tetris activity (last 7 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'tetris' AND timestamp >= datetime('now', '-7 days')");
    $stmt->execute();
    $tetris_stats['recent_7d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];

    // Get Snake statistics
    $snake_stats = [];
    
    // Total Snake scores
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_scores FROM tbl_tetris_scores WHERE game = 'snake'");
    $stmt->execute();
    $snake_stats['total_scores'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_scores'];
    
    // Unique Snake players
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_tetris_scores WHERE game = 'snake'");
    $stmt->execute();
    $snake_stats['unique_players'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_players'];
    
    // Highest Snake score
    $stmt = $pdo->prepare("SELECT MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'snake'");
    $stmt->execute();
    $snake_stats['max_score'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['max_score'];
    
    // Average Snake score
    $stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM tbl_tetris_scores WHERE game = 'snake'");
    $stmt->execute();
    $snake_stats['avg_score'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 2);
    
    // Recent Snake activity (last 24h)
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'snake' AND timestamp >= datetime('now', '-24 hours')");
    $stmt->execute();
    $snake_stats['recent_24h'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];
    
    // Recent Snake activity (last 7 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'snake' AND timestamp >= datetime('now', '-7 days')");
    $stmt->execute();
    $snake_stats['recent_7d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];

    // Get top players for each game
    $tetris_top_players = [];
    $stmt = $pdo->prepare("
        SELECT 
            discord_id,
            discord_name,
            MAX(score) as best_score,
            COUNT(*) as total_games,
            MIN(timestamp) as first_game,
            MAX(timestamp) as last_game
        FROM tbl_tetris_scores 
        WHERE game = 'tetris' 
        GROUP BY discord_id, discord_name 
        ORDER BY best_score DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $tetris_top_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $snake_top_players = [];
    $stmt = $pdo->prepare("
        SELECT 
            discord_id,
            discord_name,
            MAX(score) as best_score,
            COUNT(*) as total_games,
            MIN(timestamp) as first_game,
            MAX(timestamp) as last_game
        FROM tbl_tetris_scores 
        WHERE game = 'snake' 
        GROUP BY discord_id, discord_name 
        ORDER BY best_score DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $snake_top_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent activity
    $recent_activity = [];
    $stmt = $pdo->prepare("
        SELECT 
            game,
            discord_id,
            discord_name,
            score,
            timestamp
        FROM tbl_tetris_scores 
        ORDER BY timestamp DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total statistics
    $total_stats = [
        'total_scores' => $tetris_stats['total_scores'] + $snake_stats['total_scores'],
        'total_players' => max($tetris_stats['unique_players'], $snake_stats['unique_players']), // Approximate unique players across both games
        'recent_24h' => $tetris_stats['recent_24h'] + $snake_stats['recent_24h'],
        'recent_7d' => $tetris_stats['recent_7d'] + $snake_stats['recent_7d']
    ];

    echo json_encode([
        'success' => true,
        'tetris' => $tetris_stats,
        'snake' => $snake_stats,
        'total' => $total_stats,
        'tetris_top_players' => $tetris_top_players,
        'snake_top_players' => $snake_top_players,
        'recent_activity' => $recent_activity,
        'generated_at' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 