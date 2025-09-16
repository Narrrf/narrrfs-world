<?php
/**
 * SEASON TESTER ROLE CHECK API
 * 
 * Checks if a user has the Season Tester role and if it's a new season
 * to determine if they should see the popup notification.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get Discord ID from request
$input = json_decode(file_get_contents('php://input'), true);
$discordId = $input['discord_id'] ?? '';

if (empty($discordId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Discord ID required']);
    exit;
}

// Simple validation - this API is for user profile pages, less strict auth
// We'll validate the Discord ID format instead
if (!is_numeric($discordId) || strlen($discordId) < 15) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid Discord ID format']);
    exit;
}

try {
    // Database connection
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user has Season Tester role
    $stmt = $db->prepare("
        SELECT COUNT(*) as has_role
        FROM tbl_role_grants 
        WHERE user_id = ? AND role_id = '1417279348989497532'
    ");
    $stmt->execute([$discordId]);
    $roleCheck = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $hasSeasonTesterRole = $roleCheck['has_role'] > 0;
    
    // Check if it's a new season (Season 4)
    $stmt = $db->prepare("
        SELECT season_name, is_active
        FROM tbl_seasons 
        WHERE is_active = 1
    ");
    $stmt->execute();
    $currentSeason = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isNewSeason = $currentSeason && $currentSeason['season_name'] === 'Season 4';
    
    // Get user's contribution stats
    $contributionStats = [
        'tetris_snake_space_games' => 0,
        'cheese_hunt_clicks' => 0,
        'discord_races' => 0,
        'total_contributions' => 0
    ];
    
    // Get Tetris/Snake/Space Invaders games
    $stmt = $db->prepare("
        SELECT COUNT(*) as game_count
        FROM tbl_tetris_scores 
        WHERE discord_id = ?
    ");
    $stmt->execute([$discordId]);
    $games = $stmt->fetch(PDO::FETCH_ASSOC);
    $contributionStats['tetris_snake_space_games'] = (int)$games['game_count'];
    $contributionStats['total_contributions'] += (int)$games['game_count'];
    
    // Get Cheese Hunt clicks
    $stmt = $db->prepare("
        SELECT COUNT(*) as click_count
        FROM tbl_cheese_clicks 
        WHERE user_wallet = ?
    ");
    $stmt->execute([$discordId]);
    $clicks = $stmt->fetch(PDO::FETCH_ASSOC);
    $contributionStats['cheese_hunt_clicks'] = (int)$clicks['click_count'];
    $contributionStats['total_contributions'] += (int)$clicks['click_count'];
    
    // Get Discord Races
    $stmt = $db->prepare("
        SELECT COUNT(*) as race_count
        FROM tbl_race_participants 
        WHERE user_id = ?
    ");
    $stmt->execute([$discordId]);
    $races = $stmt->fetch(PDO::FETCH_ASSOC);
    $contributionStats['discord_races'] = (int)$races['race_count'];
    $contributionStats['total_contributions'] += (int)$races['race_count'];
    
    // Return response
    echo json_encode([
        'success' => true,
        'data' => [
            'has_season_tester_role' => $hasSeasonTesterRole,
            'is_new_season' => $isNewSeason,
            'should_show_popup' => $hasSeasonTesterRole && $isNewSeason,
            'contribution_stats' => $contributionStats,
            'current_season' => $currentSeason['season_name'] ?? 'Unknown'
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
