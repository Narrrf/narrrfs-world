<?php
/**
 * SEASON TESTER ELIGIBLE PLAYERS API
 * 
 * Returns all players who have contributed to any of the 5 games
 * and are eligible for the Season Tester role.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Authentication check
$headers = getallheaders();
$authToken = $headers['Authorization'] ?? '';

// Remove "Bearer " prefix if present
if (strpos($authToken, 'Bearer ') === 0) {
    $authToken = substr($authToken, 7);
}

// Check for Discord bot token (more reliable than ENV)
$validTokens = [
    $_ENV['DISCORD_BOT_SECRET'] ?? '',
    $_ENV['DISCORD_SECRET'] ?? '',
    'g4xN1p_uovPq1cZ_LHd9P-iM381t_xRP' // Local development token
];

if (empty($authToken) || !in_array($authToken, $validTokens)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Invalid token']);
    exit;
}

try {
    // Database connection
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Array to store all unique Discord IDs
    $allPlayers = [];
    
    // 1. Get players from Tetris, Snake, Space Invaders (tbl_tetris_scores)
    $stmt = $db->prepare("
        SELECT DISTINCT discord_id, COUNT(*) as game_count
        FROM tbl_tetris_scores 
        WHERE discord_id IS NOT NULL AND discord_id != ''
        GROUP BY discord_id
    ");
    $stmt->execute();
    $tetrisPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tetrisPlayers as $player) {
        $allPlayers[$player['discord_id']] = [
            'discord_id' => $player['discord_id'],
            'tetris_snake_space_games' => (int)$player['game_count'],
            'cheese_hunt_clicks' => 0,
            'discord_races' => 0,
            'total_contributions' => (int)$player['game_count']
        ];
    }
    
    // 2. Get players from Cheese Hunt (tbl_cheese_clicks)
    $stmt = $db->prepare("
        SELECT DISTINCT user_wallet, COUNT(*) as click_count
        FROM tbl_cheese_clicks 
        WHERE user_wallet IS NOT NULL AND user_wallet != ''
        GROUP BY user_wallet
    ");
    $stmt->execute();
    $cheesePlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cheesePlayers as $player) {
        $discordId = $player['user_wallet'];
        if (isset($allPlayers[$discordId])) {
            $allPlayers[$discordId]['cheese_hunt_clicks'] = (int)$player['click_count'];
            $allPlayers[$discordId]['total_contributions'] += (int)$player['click_count'];
        } else {
            $allPlayers[$discordId] = [
                'discord_id' => $discordId,
                'tetris_snake_space_games' => 0,
                'cheese_hunt_clicks' => (int)$player['click_count'],
                'discord_races' => 0,
                'total_contributions' => (int)$player['click_count']
            ];
        }
    }
    
    // 3. Get players from Discord Race (tbl_race_participants)
    $stmt = $db->prepare("
        SELECT DISTINCT user_id, COUNT(*) as race_count
        FROM tbl_race_participants 
        WHERE user_id IS NOT NULL AND user_id != ''
        GROUP BY user_id
    ");
    $stmt->execute();
    $racePlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($racePlayers as $player) {
        $discordId = $player['user_id'];
        if (isset($allPlayers[$discordId])) {
            $allPlayers[$discordId]['discord_races'] = (int)$player['race_count'];
            $allPlayers[$discordId]['total_contributions'] += (int)$player['race_count'];
        } else {
            $allPlayers[$discordId] = [
                'discord_id' => $discordId,
                'tetris_snake_space_games' => 0,
                'cheese_hunt_clicks' => 0,
                'discord_races' => (int)$player['race_count'],
                'total_contributions' => (int)$player['race_count']
            ];
        }
    }
    
    // Sort players by total contributions
    uasort($allPlayers, function($a, $b) {
        return $b['total_contributions'] - $a['total_contributions'];
    });
    
    // Convert to indexed array
    $players = array_values($allPlayers);
    
    // Return response
    echo json_encode([
        'success' => true,
        'data' => [
            'total_players' => count($players),
            'players' => $players,
            'summary' => [
                'tetris_snake_space_players' => count($tetrisPlayers),
                'cheese_hunt_players' => count($cheesePlayers),
                'discord_race_players' => count($racePlayers),
                'unique_players' => count($players)
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
