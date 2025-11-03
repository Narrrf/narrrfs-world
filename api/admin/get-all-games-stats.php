<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../db/narrrf_world.sqlite' 
        : '/var/www/html/db/narrrf_world.sqlite';
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

try {
    $db = getSQLite3Connection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Get current active season
    $seasonStmt = $db->query("SELECT season_name FROM tbl_seasons WHERE is_active = 1 LIMIT 1");
    $fullSeasonName = $seasonStmt->fetchColumn() ?: 'Season 5';
    
    // Use the full season name directly (no mapping needed)
    $currentSeason = $fullSeasonName;

    $response = [
        'success' => true,
        'data' => [
            'overview' => [
                'total_games' => 0,
                'current_season' => $fullSeasonName,
                'last_updated' => date('Y-m-d H:i:s'),
                'total_active_players' => 0,
                'total_games_played' => 0
            ],
            'games' => []
        ]
    ];

    // 1. TETRIS STATS (Season 3 compatible)
    try {
        // First try to get Season 3 data
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT discord_id) as unique_players,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) as recent_7d
            FROM tbl_tetris_scores 
            WHERE game = 'tetris'
            AND (season = ? OR season IS NULL OR season = '')
        ");
        $stmt->execute([$currentSeason]);
        $tetrisData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Keep Season 5 data even if 0 scores (fresh season start)
        // Do NOT fallback to all-time data - show current season with 0 scores

        // Get top players for Tetris
        $topStmt = $db->prepare("
            SELECT 
                discord_id,
                discord_name as username,
                MAX(score) as best_score,
                COUNT(*) as total_games,
                MAX(timestamp) as last_game
            FROM tbl_tetris_scores 
            WHERE game = 'tetris'
            AND (season = ? OR season IS NULL OR season = '')
            GROUP BY discord_id, discord_name
            ORDER BY best_score DESC
            LIMIT 10
        ");
        $topStmt->execute([$currentSeason]);
        $topPlayers = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        $response['data']['games']['tetris'] = [
            'game_name' => 'Tetris',
            'game_icon' => '🧩',
            'status' => 'active',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_scores' => (int)$tetrisData['total_scores'],
                'unique_players' => (int)$tetrisData['unique_players'],
                'max_score' => (int)$tetrisData['max_score'],
                'avg_score' => round($tetrisData['avg_score'] ?: 0),
                'recent_24h' => (int)$tetrisData['recent_24h'],
                'recent_7d' => (int)$tetrisData['recent_7d']
            ],
            'top_players' => $topPlayers
        ];

        $response['data']['overview']['total_active_players'] += (int)$tetrisData['unique_players'];
        $response['data']['overview']['total_games_played'] += (int)$tetrisData['total_scores'];
        
        error_log("Tetris data loaded successfully: " . json_encode($tetrisData));
    } catch (Exception $e) {
        error_log("Tetris stats error: " . $e->getMessage());
        error_log("Tetris stats error trace: " . $e->getTraceAsString());
    }

    // 2. SNAKE STATS (Season 3 compatible)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT discord_id) as unique_players,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) as recent_7d
            FROM tbl_tetris_scores 
            WHERE game = 'snake'
            AND (season = ? OR season IS NULL OR season = '')
        ");
        $stmt->execute([$currentSeason]);
        $snakeData = $stmt->fetch(PDO::FETCH_ASSOC);

        $response['data']['games']['snake'] = [
            'game_name' => 'Snake',
            'game_icon' => '🐍',
            'status' => 'active',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_scores' => (int)$snakeData['total_scores'],
                'unique_players' => (int)$snakeData['unique_players'],
                'max_score' => (int)$snakeData['max_score'],
                'avg_score' => round($snakeData['avg_score'] ?: 0),
                'recent_24h' => (int)$snakeData['recent_24h'],
                'recent_7d' => (int)$snakeData['recent_7d']
            ]
        ];

        $response['data']['overview']['total_active_players'] += (int)$snakeData['unique_players'];
        $response['data']['overview']['total_games_played'] += (int)$snakeData['total_scores'];
    } catch (Exception $e) {
        error_log("Snake stats error: " . $e->getMessage());
    }

    // 3. SPACE INVADERS STATS (Season 3 compatible)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT user_id) as unique_players,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) as recent_7d
            FROM tbl_user_scores 
            WHERE game = 'space_invaders'
            AND (season = ? OR season IS NULL OR season = '')
        ");
        $stmt->execute([$currentSeason]);
        $spaceData = $stmt->fetch(PDO::FETCH_ASSOC);

        $response['data']['games']['space_invaders'] = [
            'game_name' => 'Space Invaders',
            'game_icon' => '👾',
            'status' => 'active',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_scores' => (int)$spaceData['total_scores'],
                'unique_players' => (int)$spaceData['unique_players'],
                'max_score' => (int)$spaceData['max_score'],
                'avg_score' => round($spaceData['avg_score'] ?: 0),
                'recent_24h' => (int)$spaceData['recent_24h'],
                'recent_7d' => (int)$spaceData['recent_7d']
            ]
        ];

        $response['data']['overview']['total_active_players'] += (int)$spaceData['unique_players'];
        $response['data']['overview']['total_games_played'] += (int)$spaceData['total_scores'];
    } catch (Exception $e) {
        error_log("Space Invaders stats error: " . $e->getMessage());
    }

    // 4. CHEESE HUNT STATS (Season 3 compatible)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT user_wallet) as unique_players,
                COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
                COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) as recent_7d
            FROM tbl_cheese_clicks
        ");
        $stmt->execute();
        $cheeseData = $stmt->fetch(PDO::FETCH_ASSOC);

        $response['data']['games']['cheese_hunt'] = [
            'game_name' => 'Cheese Hunt',
            'game_icon' => '🧀',
            'status' => 'active',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_clicks' => (int)$cheeseData['total_clicks'],
                'unique_players' => (int)$cheeseData['unique_players'],
                'quest_clicks' => (int)$cheeseData['quest_clicks'],
                'recent_24h' => (int)$cheeseData['recent_24h'],
                'recent_7d' => (int)$cheeseData['recent_7d']
            ]
        ];

        $response['data']['overview']['total_active_players'] += (int)$cheeseData['unique_players'];
        $response['data']['overview']['total_games_played'] += (int)$cheeseData['total_clicks'];
    } catch (Exception $e) {
        error_log("Cheese Hunt stats error: " . $e->getMessage());
    }

    // 5. DISCORD RACE STATS (Season 3 compatible)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_races,
                COUNT(DISTINCT user_id) as unique_players,
                COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
                COUNT(CASE WHEN position <= 3 THEN 1 END) as podiums,
                COUNT(CASE WHEN joined_at >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN joined_at >= datetime('now', '-7 days') THEN 1 END) as recent_7d
            FROM tbl_race_participants
        ");
        $stmt->execute();
        $raceData = $stmt->fetch(PDO::FETCH_ASSOC);

        $response['data']['games']['discord_race'] = [
            'game_name' => 'Discord Race',
            'game_icon' => '🏁',
            'status' => 'active',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_races' => (int)$raceData['total_races'],
                'unique_players' => (int)$raceData['unique_players'],
                'wins' => (int)$raceData['wins'],
                'podiums' => (int)$raceData['podiums'],
                'recent_24h' => (int)$raceData['recent_24h'],
                'recent_7d' => (int)$raceData['recent_7d']
            ]
        ];

        $response['data']['overview']['total_active_players'] += (int)$raceData['unique_players'];
        $response['data']['overview']['total_games_played'] += (int)$raceData['total_races'];
    } catch (Exception $e) {
        error_log("Discord Race stats error: " . $e->getMessage());
    }

    // Calculate total games (count games that have data)
    $totalGames = 0;
    if (isset($response['data']['games']['tetris']['season_data']['total_scores']) && $response['data']['games']['tetris']['season_data']['total_scores'] > 0) $totalGames++;
    if (isset($response['data']['games']['snake']['season_data']['total_scores']) && $response['data']['games']['snake']['season_data']['total_scores'] > 0) $totalGames++;
    if (isset($response['data']['games']['space_invaders']['season_data']['total_scores']) && $response['data']['games']['space_invaders']['season_data']['total_scores'] > 0) $totalGames++;
    if (isset($response['data']['games']['cheese_hunt']['season_data']['total_clicks']) && $response['data']['games']['cheese_hunt']['season_data']['total_clicks'] > 0) $totalGames++;
    if (isset($response['data']['games']['discord_race']['season_data']['total_races']) && $response['data']['games']['discord_race']['season_data']['total_races'] > 0) $totalGames++;
    
    $response['data']['overview']['total_games'] = $totalGames;

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("get-all-games-stats.php error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
