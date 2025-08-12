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

// Helper function to check if a table exists
function tableExists($pdo, $tableName) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
        $stmt->execute([$tableName]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Error checking table existence for $tableName: " . $e->getMessage());
        return false;
    }
}

// Helper function to safely execute a query and return default value if table doesn't exist
function safeQuery($pdo, $tableName, $query, $params = [], $defaultValue = 0) {
    if (!tableExists($pdo, $tableName)) {
        error_log("Table $tableName does not exist, returning default value: $defaultValue");
        return $defaultValue;
    }
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result[array_keys($result)[0]] : $defaultValue;
    } catch (Exception $e) {
        error_log("Error executing query on table $tableName: " . $e->getMessage());
        return $defaultValue;
    }
}

// Helper function to safely execute a query and return default array if table doesn't exist
function safeQueryArray($pdo, $tableName, $query, $params = [], $defaultValue = []) {
    if (!tableExists($pdo, $tableName)) {
        error_log("Table $tableName does not exist, returning default array");
        return $defaultValue;
    }
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error executing query on table $tableName: " . $e->getMessage());
        return $defaultValue;
    }
}

try {
    $pdo = getDatabaseConnection();

    // Get current season (only if tetris_scores table exists)
    $current_season = 1;
    $current_season_name = "season_1";
    
    if (tableExists($pdo, 'tbl_tetris_scores')) {
        try {
            $seasonStmt = $pdo->prepare("SELECT MAX(CAST(SUBSTR(season, 8) AS INTEGER)) as max_season FROM tbl_tetris_scores WHERE season LIKE 'season_%' AND season NOT LIKE '%_historical'");
            $seasonStmt->execute();
            $current_season_result = $seasonStmt->fetch(PDO::FETCH_ASSOC);
            $current_season = $current_season_result['max_season'] ?? 1;
            $current_season_name = "season_$current_season";
        } catch (Exception $e) {
            error_log("Error getting current season: " . $e->getMessage());
            // Use default values
        }
    }

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

    // Current season Tetris stats (with table existence checks)
    if (tableExists($pdo, 'tbl_tetris_scores')) {
        $tetris_stats['season_data']['total_scores'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(*) as total_scores FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1", 
            [$current_season_name]);
        
        $tetris_stats['season_data']['unique_players'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1", 
            [$current_season_name]);
        
        $tetris_stats['season_data']['max_score'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1", 
            [$current_season_name]);
        
        $tetris_stats['season_data']['avg_score'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT AVG(score) as avg_score FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1", 
            [$current_season_name]);
        
        $tetris_stats['season_data']['recent_24h'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1 AND timestamp >= datetime('now', '-24 hours')", 
            [$current_season_name]);
        
        $tetris_stats['season_data']['recent_7d'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ? AND is_current_season = 1 AND timestamp >= datetime('now', '-7 days')", 
            [$current_season_name]);

        // All-time Tetris stats
        $tetris_stats['all_time']['total_scores'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(*) as total_scores FROM tbl_tetris_scores WHERE game = 'tetris'");
        
        $tetris_stats['all_time']['unique_players'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_tetris_scores WHERE game = 'tetris'");
        
        $tetris_stats['all_time']['max_score'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'tetris'");
        
        $tetris_stats['all_time']['avg_score'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT AVG(score) as avg_score FROM tbl_tetris_scores WHERE game = 'tetris'");
    }

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

    // Snake stats (with table existence checks)
    if (tableExists($pdo, 'tbl_user_scores')) {
        $snake_stats['season_data']['total_scores'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT COUNT(*) as total_scores FROM tbl_user_scores WHERE game = 'snake' AND season = ?", 
            [$current_season_name]);
        
        $snake_stats['season_data']['unique_players'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_user_scores WHERE game = 'snake' AND season = ?", 
            [$current_season_name]);
        
        $snake_stats['season_data']['max_score'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT MAX(score) as max_score FROM tbl_user_scores WHERE game = 'snake' AND season = ?", 
            [$current_season_name]);
        
        $snake_stats['season_data']['avg_score'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT AVG(score) as avg_score FROM tbl_user_scores WHERE game = 'snake' AND season = ?", 
            [$current_season_name]);

        // All-time Snake stats
        $snake_stats['all_time']['total_scores'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT COUNT(*) as total_scores FROM tbl_user_scores WHERE game = 'snake'");
        
        $snake_stats['all_time']['unique_players'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_user_scores WHERE game = 'snake'");
        
        $snake_stats['all_time']['max_score'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT MAX(score) as max_score FROM tbl_user_scores WHERE game = 'snake'");
        
        $snake_stats['all_time']['avg_score'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT AVG(score) as avg_score FROM tbl_user_scores WHERE game = 'snake'");
    }

    $consolidated_stats['games']['snake'] = $snake_stats;

    // ===== SPACE INVADERS GAME STATISTICS =====
    $space_invaders_stats = [
        'game_name' => 'Space Invaders',
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

    // Space Invaders stats (with table existence checks)
    if (tableExists($pdo, 'tbl_tetris_scores')) {
        $space_invaders_stats['season_data']['total_scores'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(*) as total_scores FROM tbl_tetris_scores WHERE game = 'space_invaders' AND season = ? AND is_current_season = 1", 
            [$current_season_name]);
        
        $space_invaders_stats['season_data']['unique_players'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_tetris_scores WHERE game = 'space_invaders' AND season = ? AND is_current_season = 1", 
            [$current_season_name]);
        
        $space_invaders_stats['season_data']['max_score'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'space_invaders' AND season = ? AND is_current_season = 1", 
            [$current_season_name]);
        
        $space_invaders_stats['season_data']['avg_score'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT AVG(score) as avg_score FROM tbl_tetris_scores WHERE game = 'space_invaders' AND season = ? AND is_current_season = 1", 
            [$current_season_name]);
        
        $space_invaders_stats['season_data']['recent_24h'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'space_invaders' AND season = ? AND is_current_season = 1 AND timestamp >= datetime('now', '-24 hours')", 
            [$current_season_name]);
        
        $space_invaders_stats['season_data']['recent_7d'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(*) as recent_scores FROM tbl_tetris_scores WHERE game = 'space_invaders' AND season = ? AND is_current_season = 1 AND timestamp >= datetime('now', '-7 days')", 
            [$current_season_name]);

        // All-time Space Invaders stats
        $space_invaders_stats['all_time']['total_scores'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(*) as total_scores FROM tbl_tetris_scores WHERE game = 'space_invaders'");
        
        $space_invaders_stats['all_time']['unique_players'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_tetris_scores WHERE game = 'space_invaders'");
        
        $space_invaders_stats['all_time']['max_score'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'space_invaders'");
        
        $space_invaders_stats['all_time']['avg_score'] = safeQuery($pdo, 'tbl_tetris_scores', 
            "SELECT AVG(score) as avg_score FROM tbl_tetris_scores WHERE game = 'space_invaders'");
    }

    $consolidated_stats['games']['space_invaders'] = $space_invaders_stats;

    // ===== CHEESE INVADERS GAME STATISTICS =====
    $cheese_invaders_stats = [
        'game_name' => 'Cheese Invaders',
        'game_icon' => '🧀👾',
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

    // Cheese Invaders stats (with table existence checks)
    if (tableExists($pdo, 'tbl_user_scores')) {
        $cheese_invaders_stats['season_data']['total_scores'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT COUNT(*) as total_scores FROM tbl_user_scores WHERE game = 'cheese_invaders' AND season = ?", 
            [$current_season_name]);
        
        $cheese_invaders_stats['season_data']['unique_players'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_user_scores WHERE game = 'cheese_invaders' AND season = ?", 
            [$current_season_name]);
        
        $cheese_invaders_stats['season_data']['max_score'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT MAX(score) as max_score FROM tbl_user_scores WHERE game = 'cheese_invaders' AND season = ?", 
            [$current_season_name]);
        
        $cheese_invaders_stats['season_data']['avg_score'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT AVG(score) as avg_score FROM tbl_user_scores WHERE game = 'cheese_invaders' AND season = ?", 
            [$current_season_name]);

        // All-time Cheese Invaders stats
        $cheese_invaders_stats['all_time']['total_scores'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT COUNT(*) as total_scores FROM tbl_user_scores WHERE game = 'cheese_invaders'");
        
        $cheese_invaders_stats['all_time']['unique_players'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_user_scores WHERE game = 'cheese_invaders'");
        
        $cheese_invaders_stats['all_time']['max_score'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT MAX(score) as max_score FROM tbl_user_scores WHERE game = 'cheese_invaders'");
        
        $cheese_invaders_stats['all_time']['avg_score'] = safeQuery($pdo, 'tbl_user_scores', 
            "SELECT AVG(score) as avg_score FROM tbl_user_scores WHERE game = 'cheese_invaders'");
    }

    $consolidated_stats['games']['cheese_invaders'] = $cheese_invaders_stats;

    // ===== CHEESE HUNT GAME STATISTICS =====
    $cheese_hunt_stats = [
        'game_name' => 'Cheese Hunt',
        'game_icon' => '🧀🎯',
        'status' => 'active',
        'current_data' => [
            'total_clicks' => 0,
            'unique_players' => 0,
            'max_clicks' => 0,
            'avg_clicks' => 0,
            'recent_24h' => 0,
            'recent_7d' => 0
        ],
        'all_time' => [
            'total_clicks' => 0,
            'unique_players' => 0,
            'max_clicks' => 0,
            'avg_clicks' => 0
        ]
    ];

    // Cheese Hunt stats (with table existence checks)
    if (tableExists($pdo, 'tbl_cheese_clicks')) {
        $cheese_hunt_stats['current_data']['total_clicks'] = safeQuery($pdo, 'tbl_cheese_clicks', 
            "SELECT COUNT(*) as total_clicks FROM tbl_cheese_clicks");
        
        $cheese_hunt_stats['current_data']['unique_players'] = safeQuery($pdo, 'tbl_cheese_clicks', 
            "SELECT COUNT(DISTINCT discord_id) as unique_players FROM tbl_cheese_clicks");
        
        $cheese_hunt_stats['current_data']['max_clicks'] = safeQuery($pdo, 'tbl_cheese_clicks', 
            "SELECT MAX(clicks) as max_clicks FROM tbl_cheese_clicks");
        
        $cheese_hunt_stats['current_data']['avg_clicks'] = safeQuery($pdo, 'tbl_cheese_clicks', 
            "SELECT AVG(clicks) as avg_clicks FROM tbl_cheese_clicks");

        // All-time Cheese Hunt stats
        $cheese_hunt_stats['all_time']['total_clicks'] = $cheese_hunt_stats['current_data']['total_clicks'];
        $cheese_hunt_stats['all_time']['unique_players'] = $cheese_hunt_stats['current_data']['unique_players'];
        $cheese_hunt_stats['all_time']['max_clicks'] = $cheese_hunt_stats['current_data']['max_clicks'];
        $cheese_hunt_stats['all_time']['avg_clicks'] = $cheese_hunt_stats['current_data']['avg_clicks'];
    }

    $consolidated_stats['games']['cheese_hunt'] = $cheese_hunt_stats;

    // ===== DISCORD CHEESE RACE GAME STATISTICS =====
    $discord_race_stats = [
        'game_name' => 'Discord Cheese Race',
        'game_icon' => '🏁🧀',
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

    // Discord Race stats (with table existence checks)
    if (tableExists($pdo, 'tbl_cheese_races')) {
        // Count total races
        $discord_race_stats['race_data']['total_races'] = safeQuery($pdo, 'tbl_cheese_races', 
            "SELECT COUNT(*) as total_races FROM tbl_cheese_races");
        
        // Count total participants from race_participants table
        if (tableExists($pdo, 'tbl_race_participants')) {
            $discord_race_stats['race_data']['total_participants'] = safeQuery($pdo, 'tbl_race_participants', 
                "SELECT COUNT(*) as total_participants FROM tbl_race_participants");
        }
        
        // Count races with winners
        $discord_race_stats['race_data']['total_prizes_awarded'] = safeQuery($pdo, 'tbl_cheese_races', 
            "SELECT COUNT(*) as total_prizes FROM tbl_cheese_races WHERE status = 'finished' AND winner_id IS NOT NULL");
        
        // Count recent races (last 24 hours)
        $discord_race_stats['race_data']['recent_24h'] = safeQuery($pdo, 'tbl_cheese_races', 
            "SELECT COUNT(*) as recent_races FROM tbl_cheese_races WHERE created_at >= datetime('now', '-1 day')");
        
        // Count recent races (last 7 days)
        $discord_race_stats['race_data']['recent_7d'] = safeQuery($pdo, 'tbl_cheese_races', 
            "SELECT COUNT(*) as recent_races FROM tbl_cheese_races WHERE created_at >= datetime('now', '-7 days')");
        
        // Get active races with participant counts
        $active_races = safeQueryArray($pdo, 'tbl_cheese_races', 
            "SELECT cr.race_id, cr.creator_name, cr.status, cr.max_players, cr.created_at,
                    COUNT(rp.user_id) as current_participants
             FROM tbl_cheese_races cr
             LEFT JOIN tbl_race_participants rp ON cr.race_id = rp.race_id
             WHERE cr.status IN ('waiting', 'active')
             GROUP BY cr.race_id
             ORDER BY cr.created_at DESC
             LIMIT 5");
        
        // Get top racers (winners) - only if both tables exist
        if (tableExists($pdo, 'tbl_users')) {
            $discord_race_stats['race_data']['top_racers'] = safeQueryArray($pdo, 'tbl_cheese_races', 
                "SELECT cr.winner_id as user_id, cr.winner_name as username, COUNT(*) as wins
                 FROM tbl_cheese_races cr
                 WHERE cr.status = 'finished' AND cr.winner_id IS NOT NULL
                 GROUP BY cr.winner_id
                 ORDER BY wins DESC
                 LIMIT 10");
        }
        
        // Get recent activity from both tables
        if (tableExists($pdo, 'tbl_race_participants')) {
            $recent_activities = safeQueryArray($pdo, 'tbl_cheese_races', 
                "SELECT 'race_started' as event_type, creator_id as user_id, creator_name as username, created_at
                 FROM tbl_cheese_races 
                 WHERE created_at >= datetime('now', '-7 days')
                 UNION ALL
                 SELECT 'player_joined' as event_type, user_id, username, joined_at as created_at
                 FROM tbl_race_participants 
                 WHERE joined_at >= datetime('now', '-7 days')
                 ORDER BY created_at DESC
                 LIMIT 10");
            
            $discord_race_stats['race_data']['recent_activity'] = array_map(function($activity) {
                $event_descriptions = [
                    'race_started' => 'Started race',
                    'player_joined' => 'Joined race'
                ];
                
                return [
                    'timestamp' => date('M j, Y g:i A', strtotime($activity['created_at'])),
                    'description' => ($activity['username'] ?? 'Unknown') . ' ' . ($event_descriptions[$activity['event_type']] ?? $activity['event_type'])
                ];
            }, $recent_activities);
        }
        
        // Add current active race information
        $current_race = safeQueryArray($pdo, 'tbl_cheese_races', 
            "SELECT cr.race_id, cr.creator_name, cr.status, cr.max_players, cr.created_at,
                    COUNT(rp.user_id) as current_participants
             FROM tbl_cheese_races cr
             LEFT JOIN tbl_race_participants rp ON cr.race_id = rp.race_id
             WHERE cr.status = 'waiting'
             GROUP BY cr.race_id
             ORDER BY cr.created_at DESC
             LIMIT 1");
        
        if (!empty($current_race)) {
            $discord_race_stats['race_data']['current_race'] = $current_race[0];
        }
    }

    $consolidated_stats['games']['discord_race'] = $discord_race_stats;

    // ===== CALCULATE OVERVIEW TOTALS =====
    $consolidated_stats['overview']['total_active_players'] = 
        $tetris_stats['season_data']['unique_players'] +
        $snake_stats['season_data']['unique_players'] +
        $space_invaders_stats['season_data']['unique_players'] +
        $cheese_hunt_stats['current_data']['unique_players'] +
        $cheese_invaders_stats['season_data']['unique_players'];

    $consolidated_stats['overview']['total_games_played'] = 
        $tetris_stats['season_data']['total_scores'] +
        $snake_stats['season_data']['total_scores'] +
        $space_invaders_stats['season_data']['total_scores'] +
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
