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

    // Get current season
    $seasonStmt = $pdo->prepare("SELECT MAX(CAST(SUBSTR(season, 8) AS INTEGER)) as max_season FROM tbl_tetris_scores WHERE season LIKE 'season_%' AND season NOT LIKE '%_historical'");
    $seasonStmt->execute();
    $current_season_result = $seasonStmt->fetch(PDO::FETCH_ASSOC);
    $current_season = $current_season_result['max_season'] ?? 1;
    $current_season_name = "season_$current_season";

    // Initialize consolidated response
    $consolidated_stats = [
        'overview' => [
            'total_games' => 5,
            'current_season' => $current_season_name,
            'last_updated' => date('Y-m-d H:i:s'),
            'total_active_players' => 0,
            'total_games_played' => 0
        ],
        'games' => []
    ];

    // ===== TETRIS GAME STATISTICS =====
    $tetris_stats = [
        'game_name' => 'Tetris',
        'game_icon' => '🧩',
        'status' => 'active',
        'season_data' => [
            'current_season' => $current_season_name,
            'total_scores' => 0,
            'unique_players' => 0,
            'max_score' => 0,
            'avg_score' => 0,
            'recent_24h' => 0,
            'recent_7d' => 0
        ],
        'all_time' => [
            'total_scores' => 0,
            'unique_players' => 0,
            'max_score' => 0,
            'avg_score' => 0
        ]
    ];

    // Current season Tetris stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_scores FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1");
    $stmt->execute([$current_season_name]);
    $tetris_stats['season_data']['total_scores'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1");
    $stmt->execute([$current_season_name]);
    $tetris_stats['season_data']['unique_players'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_players'];
    
    $stmt = $pdo->prepare("SELECT MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1");
    $stmt->execute([$current_season_name]);
    $tetris_stats['season_data']['max_score'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['max_score'];
    
    $stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1");
    $stmt->execute([$current_season_name]);
    $tetris_stats['season_data']['avg_score'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 2);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1 AND timestamp >= datetime('now', '-24 hours')");
    $stmt->execute([$current_season_name]);
    $tetris_stats['season_data']['recent_24h'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1 AND timestamp >= datetime('now', '-7 days')");
    $stmt->execute([$current_season_name]);
    $tetris_stats['season_data']['recent_7d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1 AND timestamp >= datetime('now', '-30 days')");
    $stmt->execute([$current_season_name]);
    $tetris_stats['season_data']['recent_30d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];

    // All-time Tetris stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_scores FROM tbl_tetris_scores WHERE game = 'tetris'");
    $stmt->execute();
    $tetris_stats['all_time']['total_scores'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_tetris_scores WHERE game = 'tetris'");
    $stmt->execute();
    $tetris_stats['all_time']['unique_players'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_players'];
    
    $stmt = $pdo->prepare("SELECT MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'tetris'");
    $stmt->execute();
    $tetris_stats['all_time']['max_score'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['max_score'];
    
    $stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM tbl_tetris_scores WHERE game = 'tetris'");
    $stmt->execute();
    $tetris_stats['all_time']['avg_score'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 2);

    // Get top Tetris players for current season
    $stmt = $pdo->prepare("SELECT ts.discord_id, u.username, ts.score 
                           FROM tbl_tetris_scores ts 
                           LEFT JOIN tbl_users u ON ts.discord_id = u.discord_id 
                           WHERE ts.game = 'tetris' AND ts.season = ? AND ts.is_current_season = 1 
                           ORDER BY ts.score DESC 
                           LIMIT 10");
    $stmt->execute([$current_season_name]);
    $tetris_stats['top_players'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $consolidated_stats['games']['tetris'] = $tetris_stats;

    // ===== SNAKE GAME STATISTICS =====
    $snake_stats = [
        'game_name' => 'Snake',
        'game_icon' => '🐍',
        'status' => 'active',
        'season_data' => [
            'current_season' => $current_season_name,
            'total_scores' => 0,
            'unique_players' => 0,
            'max_score' => 0,
            'avg_score' => 0,
            'recent_24h' => 0,
            'recent_7d' => 0
        ],
        'all_time' => [
            'total_scores' => 0,
            'unique_players' => 0,
            'max_score' => 0,
            'avg_score' => 0
        ]
    ];

    // Current season Snake stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_scores FROM tbl_tetris_scores WHERE game = 'snake' AND season = ? AND is_current_season = 1");
    $stmt->execute([$current_season_name]);
    $snake_stats['season_data']['total_scores'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_tetris_scores WHERE game = 'snake' AND season = ? AND is_current_season = 1");
    $stmt->execute([$current_season_name]);
    $snake_stats['season_data']['unique_players'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_players'];
    
    $stmt = $pdo->prepare("SELECT MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'snake' AND season = ? AND is_current_season = 1");
    $stmt->execute([$current_season_name]);
    $snake_stats['season_data']['max_score'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['max_score'];
    
    $stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM tbl_tetris_scores WHERE game = 'snake' AND season = ? AND is_current_season = 1");
    $stmt->execute([$current_season_name]);
    $snake_stats['season_data']['avg_score'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 2);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'snake' AND season = ? AND is_current_season = 1 AND timestamp >= datetime('now', '-24 hours')");
    $stmt->execute([$current_season_name]);
    $snake_stats['season_data']['recent_24h'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'snake' AND season = ? AND is_current_season = 1 AND timestamp >= datetime('now', '-7 days')");
    $stmt->execute([$current_season_name]);
    $snake_stats['season_data']['recent_7d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'snake' AND season = ? AND is_current_season = 1 AND timestamp >= datetime('now', '-30 days')");
    $stmt->execute([$current_season_name]);
    $snake_stats['season_data']['recent_30d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];

    // All-time Snake stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_scores FROM tbl_tetris_scores WHERE game = 'snake'");
    $stmt->execute();
    $snake_stats['all_time']['total_scores'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_tetris_scores WHERE game = 'snake'");
    $stmt->execute();
    $snake_stats['all_time']['unique_players'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_players'];
    
    $stmt = $pdo->prepare("SELECT MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'snake'");
    $stmt->execute();
    $snake_stats['all_time']['max_score'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['max_score'];
    
    $stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM tbl_tetris_scores WHERE game = 'snake'");
    $stmt->execute();
    $snake_stats['all_time']['avg_score'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 2);

    // Get top Snake players for current season
    $stmt = $pdo->prepare("SELECT ts.discord_id, u.username, ts.score 
                           FROM tbl_tetris_scores ts 
                           LEFT JOIN tbl_users u ON ts.discord_id = u.discord_id 
                           WHERE ts.game = 'snake' AND ts.season = ? AND ts.is_current_season = 1 
                           ORDER BY ts.score DESC 
                           LIMIT 10");
    $stmt->execute([$current_season_name]);
    $snake_stats['top_players'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $consolidated_stats['games']['snake'] = $snake_stats;

    // ===== CHEESE HUNT GAME STATISTICS =====
    $cheese_hunt_stats = [
        'game_name' => 'Cheese Hunt',
        'game_icon' => '🧀',
        'status' => 'active',
        'current_data' => [
            'total_clicks' => 0,
            'unique_players' => 0,
            'recent_24h' => 0,
            'recent_7d' => 0,
            'top_clicker' => null,
            'clicks_by_egg_type' => []
        ]
    ];

    // Cheese Hunt stats
    $stmt = $pdo->query("SELECT COUNT(*) as total_clicks FROM tbl_cheese_clicks");
    $cheese_hunt_stats['current_data']['total_clicks'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_clicks'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_wallet) as unique_players FROM tbl_cheese_clicks");
    $cheese_hunt_stats['current_data']['unique_players'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_players'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as recent_clicks FROM tbl_cheese_clicks WHERE timestamp >= datetime('now', '-1 day')");
    $cheese_hunt_stats['current_data']['recent_24h'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_clicks'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as recent_clicks FROM tbl_cheese_clicks WHERE timestamp >= datetime('now', '-7 days')");
    $cheese_hunt_stats['current_data']['recent_7d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_clicks'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as recent_clicks FROM tbl_cheese_clicks WHERE timestamp >= datetime('now', '-30 days')");
    $cheese_hunt_stats['current_data']['recent_30d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_clicks'];
    
    // Top clicker
    $stmt = $pdo->query("SELECT c.user_wallet, u.username, COUNT(*) as click_count 
                         FROM tbl_cheese_clicks c
                         LEFT JOIN tbl_users u ON c.user_wallet = u.discord_id
                         GROUP BY c.user_wallet 
                         ORDER BY click_count DESC 
                         LIMIT 1");
    $top_clicker = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($top_clicker) {
        $cheese_hunt_stats['current_data']['top_clicker'] = [
            'username' => $top_clicker['username'] ?? 'Unknown',
            'clicks' => (int)$top_clicker['click_count']
        ];
    }
    
    // Clicks by egg type
    $stmt = $pdo->query("SELECT egg_id, COUNT(*) as click_count 
                         FROM tbl_cheese_clicks 
                         GROUP BY egg_id 
                         ORDER BY click_count DESC");
    $cheese_hunt_stats['current_data']['clicks_by_egg_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $consolidated_stats['games']['cheese_hunt'] = $cheese_hunt_stats;

    // ===== CHEESE INVADERS GAME STATISTICS =====
    $cheese_invaders_stats = [
        'game_name' => 'Cheese Invaders',
        'game_icon' => '👾',
        'status' => 'active',
        'season_data' => [
            'current_season' => $current_season_name,
            'total_scores' => 0,
            'unique_players' => 0,
            'max_score' => 0,
            'avg_score' => 0,
            'recent_24h' => 0,
            'recent_7d' => 0
        ],
        'all_time' => [
            'total_scores' => 0,
            'unique_players' => 0,
            'max_score' => 0,
            'avg_score' => 0
        ]
    ];

    // Current season Cheese Invaders stats (using user_scores table)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_scores FROM tbl_user_scores WHERE game_type = 'cheese_invaders' AND season = ?");
    $stmt->execute([$current_season_name]);
    $cheese_invaders_stats['season_data']['total_scores'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as unique_players FROM tbl_user_scores WHERE game_type = 'cheese_invaders' AND season = ?");
    $stmt->execute([$current_season_name]);
    $cheese_invaders_stats['season_data']['unique_players'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_players'];
    
    $stmt = $pdo->prepare("SELECT MAX(score) as max_score FROM tbl_user_scores WHERE game_type = 'cheese_invaders' AND season = ?");
    $stmt->execute([$current_season_name]);
    $cheese_invaders_stats['season_data']['max_score'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['max_score'];
    
    $stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM tbl_user_scores WHERE game_type = 'cheese_invaders' AND season = ?");
    $stmt->execute([$current_season_name]);
    $cheese_invaders_stats['season_data']['avg_score'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 2);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_user_scores WHERE game_type = 'cheese_invaders' AND season = ? AND created_at >= datetime('now', '-1 day')");
    $stmt->execute([$current_season_name]);
    $cheese_invaders_stats['season_data']['recent_24h'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_user_scores WHERE game_type = 'cheese_invaders' AND season = ? AND created_at >= datetime('now', '-7 days')");
    $stmt->execute([$current_season_name]);
    $cheese_invaders_stats['season_data']['recent_7d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_scores FROM tbl_user_scores WHERE game_type = 'cheese_invaders' AND season = ? AND created_at >= datetime('now', '-30 days')");
    $stmt->execute([$current_season_name]);
    $cheese_invaders_stats['season_data']['recent_30d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_scores'];

    // All-time Cheese Invaders stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_scores FROM tbl_user_scores WHERE game_type = 'cheese_invaders'");
    $stmt->execute();
    $cheese_invaders_stats['all_time']['total_scores'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_scores'];
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as unique_players FROM tbl_user_scores WHERE game_type = 'cheese_invaders'");
    $stmt->execute();
    $cheese_invaders_stats['all_time']['unique_players'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_players'];
    
    $stmt = $pdo->prepare("SELECT MAX(score) as max_score FROM tbl_user_scores WHERE game_type = 'cheese_invaders'");
    $stmt->execute();
    $cheese_invaders_stats['all_time']['max_score'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['max_score'];
    
    $stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM tbl_user_scores WHERE game_type = 'cheese_invaders'");
    $stmt->execute();
    $cheese_invaders_stats['all_time']['avg_score'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 2);

    // Get top Cheese Invaders players for current season
    $stmt = $pdo->prepare("SELECT us.user_id, u.username, us.score 
                           FROM tbl_user_scores us 
                           LEFT JOIN tbl_users u ON us.user_id = u.discord_id 
                           WHERE us.game_type = 'cheese_invaders' AND us.season = ? 
                           ORDER BY us.score DESC 
                           LIMIT 10");
    $stmt->execute([$current_season_name]);
    $cheese_invaders_stats['top_players'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $consolidated_stats['games']['cheese_invaders'] = $cheese_invaders_stats;

    // ===== DISCORD CHEESE RACE GAME STATISTICS =====
    $discord_race_stats = [
        'game_name' => 'Discord Cheese Race',
        'game_icon' => '🏁',
        'status' => 'active',
        'race_data' => [
            'total_races' => 0,
            'total_participants' => 0,
            'total_prizes_awarded' => 0,
            'recent_24h' => 0,
            'recent_7d' => 0,
            'top_racers' => []
        ]
    ];

    // Discord Race stats (using discord_events table)
    $stmt = $pdo->query("SELECT COUNT(*) as total_races FROM tbl_discord_events WHERE event_type = 'cheese_race'");
    $discord_race_stats['race_data']['total_races'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_races'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_participants FROM tbl_discord_events WHERE event_type = 'cheese_race_participant'");
    $discord_race_stats['race_data']['total_participants'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_participants'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_prizes FROM tbl_discord_events WHERE event_type = 'cheese_race_winner'");
    $discord_race_stats['race_data']['total_prizes_awarded'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_prizes'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as recent_races FROM tbl_discord_events WHERE event_type = 'cheese_race' AND created_at >= datetime('now', '-1 day')");
    $discord_race_stats['race_data']['recent_24h'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_races'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as recent_races FROM tbl_discord_events WHERE event_type = 'cheese_race' AND created_at >= datetime('now', '-7 days')");
    $discord_race_stats['race_data']['recent_7d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_races'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as recent_races FROM tbl_discord_events WHERE event_type = 'cheese_race' AND created_at >= datetime('now', '-30 days')");
    $discord_race_stats['race_data']['recent_30d'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['recent_races'];
    
    // Get top racers (winners)
    $stmt = $pdo->query("SELECT de.user_id, u.username, COUNT(*) as wins
                         FROM tbl_discord_events de
                         LEFT JOIN tbl_users u ON de.user_id = u.discord_id
                         WHERE de.event_type = 'cheese_race_winner'
                         GROUP BY de.user_id
                         ORDER BY wins DESC
                         LIMIT 10");
    $discord_race_stats['race_data']['top_racers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity
    $stmt = $pdo->query("SELECT de.event_type, de.user_id, u.username, de.created_at
                         FROM tbl_discord_events de
                         LEFT JOIN tbl_users u ON de.user_id = u.discord_id
                         WHERE de.event_type IN ('cheese_race', 'cheese_race_participant', 'cheese_race_winner')
                         ORDER BY de.created_at DESC
                         LIMIT 10");
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $discord_race_stats['race_data']['recent_activity'] = array_map(function($activity) {
        $event_descriptions = [
            'cheese_race' => 'Race started',
            'cheese_race_participant' => 'Joined race',
            'cheese_race_winner' => 'Won race'
        ];
        
        return [
            'timestamp' => date('M j, Y g:i A', strtotime($activity['created_at'])),
            'description' => ($activity['username'] ?? 'Unknown') . ' ' . ($event_descriptions[$activity['event_type']] ?? $activity['event_type'])
        ];
    }, $recent_activities);

    $consolidated_stats['games']['discord_race'] = $discord_race_stats;

    // ===== CALCULATE OVERVIEW TOTALS =====
    $consolidated_stats['overview']['total_active_players'] = 
        $tetris_stats['season_data']['unique_players'] +
        $snake_stats['season_data']['unique_players'] +
        $cheese_hunt_stats['current_data']['unique_players'] +
        $cheese_invaders_stats['season_data']['unique_players'];

    $consolidated_stats['overview']['total_games_played'] = 
        $tetris_stats['season_data']['total_scores'] +
        $snake_stats['season_data']['total_scores'] +
        $cheese_hunt_stats['current_data']['total_clicks'] +
        $cheese_invaders_stats['season_data']['total_scores'] +
        $discord_race_stats['race_data']['total_races'];

    // Return consolidated statistics
    echo json_encode([
        'success' => true,
        'data' => $consolidated_stats
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("Database error in get-all-games-stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in get-all-games-stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while processing the request',
        'details' => $e->getMessage()
    ]);
}
?>
