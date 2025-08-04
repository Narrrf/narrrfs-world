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

// Database path - use production path for Render
$dbPath = '/var/www/html/db/narrrf_world.sqlite';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
}

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get query parameters
    $season = $_GET['season'] ?? 'current';
    $game_type = $_GET['game_type'] ?? 'all';

    // Get current season info
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTR(season, 8) AS INTEGER)) as max_season FROM tbl_tetris_scores WHERE season LIKE 'season_%'");
    $stmt->execute();
    $current_season_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_season = $current_season_result['max_season'] ?? 1;

    // Determine which season to show
    if ($season === 'current') {
        $target_season = "season_$current_season";
    } else {
        $target_season = $season;
    }

    // Get all available seasons
    $stmt = $pdo->prepare("SELECT DISTINCT season FROM tbl_tetris_scores WHERE season LIKE 'season_%' ORDER BY season");
    $stmt->execute();
    $available_seasons = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get season statistics
    $season_stats = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_scores,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(DISTINCT discord_id) as unique_players,
                COUNT(CASE WHEN is_top_performer = 1 THEN 1 END) as top_performers
            FROM tbl_tetris_scores 
            WHERE game = 'tetris' AND season = ?
        ");
        $stmt->execute([$target_season]);
        $season_stats['tetris'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_scores,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(DISTINCT discord_id) as unique_players,
                COUNT(CASE WHEN is_top_performer = 1 THEN 1 END) as top_performers
            FROM tbl_tetris_scores 
            WHERE game = 'snake' AND season = ?
        ");
        $stmt->execute([$target_season]);
        $season_stats['snake'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get top performers for the season
    $top_performers = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("
            SELECT 
                discord_id,
                discord_name,
                score,
                timestamp,
                is_top_performer
            FROM tbl_tetris_scores 
            WHERE game = 'tetris' AND season = ? 
            ORDER BY score DESC 
            LIMIT 10
        ");
        $stmt->execute([$target_season]);
        $top_performers['tetris'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("
            SELECT 
                discord_id,
                discord_name,
                score,
                timestamp,
                is_top_performer
            FROM tbl_tetris_scores 
            WHERE game = 'snake' AND season = ? 
            ORDER BY score DESC 
            LIMIT 10
        ");
        $stmt->execute([$target_season]);
        $top_performers['snake'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all-time top performers (marked as legends)
    $all_time_legends = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("
            SELECT 
                discord_id,
                discord_name,
                score,
                season,
                timestamp
            FROM tbl_tetris_scores 
            WHERE game = 'tetris' AND is_top_performer = 1 
            ORDER BY score DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $all_time_legends['tetris'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("
            SELECT 
                discord_id,
                discord_name,
                score,
                season,
                timestamp
            FROM tbl_tetris_scores 
            WHERE game = 'snake' AND is_top_performer = 1 
            ORDER BY score DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $all_time_legends['snake'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get season timeline
    $season_timeline = [];
    foreach ($available_seasons as $season_name) {
        $stmt = $pdo->prepare("
            SELECT 
                MIN(timestamp) as start_date,
                MAX(season_end_date) as end_date,
                COUNT(*) as total_scores,
                COUNT(DISTINCT discord_id) as unique_players
            FROM tbl_tetris_scores 
            WHERE season = ?
        ");
        $stmt->execute([$season_name]);
        $timeline_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $season_timeline[] = [
            'season' => $season_name,
            'start_date' => $timeline_data['start_date'],
            'end_date' => $timeline_data['end_date'],
            'total_scores' => $timeline_data['total_scores'],
            'unique_players' => $timeline_data['unique_players']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'current_season' => "season_$current_season",
            'target_season' => $target_season,
            'available_seasons' => $available_seasons,
            'season_stats' => $season_stats,
            'top_performers' => $top_performers,
            'all_time_legends' => $all_time_legends,
            'season_timeline' => $season_timeline
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to get season statistics: ' . $e->getMessage()
    ]);
}
?> 