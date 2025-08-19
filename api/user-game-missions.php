<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id'])) {
        throw new Exception('user_id is required');
    }
    
    $discordId = $input['user_id'];
    
    // Initialize response data
    $response = [
        'tetris' => [
            'total_games' => 0,
            'best_score' => 0,
            'total_score' => 0,
            'last_played' => null,
            'best_lines' => 0,
            'level_reached' => 0
        ],
        'snake' => [
            'total_games' => 0,
            'best_score' => 0,
            'total_score' => 0,
            'last_played' => null
        ],
        'space_invaders' => [
            'total_games' => 0,
            'best_score' => 0,
            'total_score' => 0,
            'last_played' => null
        ],
        'cheese_hunt' => [
            'total_clicks' => 0,
            'quest_clicks' => 0,
            'unique_eggs' => 0,
            'last_click' => null
        ],
        'discord_race' => [
            'total_races' => 0,
            'wins' => 0,
            'podiums' => 0,
            'best_position' => null
        ],
        'overall' => [
            'total_dspoinc' => 0,
            'games_played' => 0,
            'quests_approved' => 0,
            'level' => 'Beginner Cheese Hunter'
        ]
    ];
    
    // Connect to database
    $db = new PDO('sqlite:../db/narrrf_world.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. TETRIS STATS (using discord_id)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(score) as best_score,
                SUM(score) as total_score,
                MAX(timestamp) as last_played
            FROM tbl_tetris_scores 
            WHERE discord_id = ? AND is_current_season = 1
        ");
        $stmt->execute([$discordId]);
        $tetrisData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tetrisData) {
            $response['tetris']['total_games'] = (int)$tetrisData['total_games'];
            $response['tetris']['best_score'] = (int)$tetrisData['best_score'];
            $response['tetris']['total_score'] = (int)$tetrisData['total_score'];
            $response['tetris']['last_played'] = $tetrisData['last_played'];
        }
    } catch (Exception $e) {
        error_log("Tetris query error: " . $e->getMessage());
    }
    
    // 2. SNAKE STATS (using user_id)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(score) as best_score,
                SUM(score) as total_score,
                MAX(timestamp) as last_played
            FROM tbl_user_scores 
            WHERE user_id = ? AND game_type = 'snake'
        ");
        $stmt->execute([$discordId]);
        $snakeData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($snakeData) {
            $response['snake']['total_games'] = (int)$snakeData['total_games'];
            $response['snake']['best_score'] = (int)$snakeData['best_score'];
            $response['snake']['total_score'] = (int)$snakeData['total_score'];
            $response['snake']['last_played'] = $snakeData['last_played'];
        }
    } catch (Exception $e) {
        error_log("Snake query error: " . $e->getMessage());
    }
    
    // 3. SPACE INVADERS STATS (using user_id)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(score) as best_score,
                SUM(score) as total_score,
                MAX(timestamp) as last_played
            FROM tbl_user_scores 
            WHERE user_id = ? AND game_type = 'space_invaders'
        ");
        $stmt->execute([$discordId]);
        $spaceData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($spaceData) {
            $response['space_invaders']['total_games'] = (int)$spaceData['total_games'];
            $response['space_invaders']['best_score'] = (int)$spaceData['best_score'];
            $response['space_invaders']['total_score'] = (int)$spaceData['total_score'];
            $response['space_invaders']['last_played'] = $spaceData['last_played'];
        }
    } catch (Exception $e) {
        error_log("Space Invaders query error: " . $e->getMessage());
    }
    
    // 4. CHEESE HUNT STATS (using user_wallet)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_clicks,
                COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
                COUNT(DISTINCT egg_id) as unique_eggs,
                MAX(timestamp) as last_click
            FROM tbl_cheese_clicks 
            WHERE user_wallet = ?
        ");
        $stmt->execute([$discordId]);
        $cheeseData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cheeseData) {
            $response['cheese_hunt']['total_clicks'] = (int)$cheeseData['total_clicks'];
            $response['cheese_hunt']['quest_clicks'] = (int)$cheeseData['quest_clicks'];
            $response['cheese_hunt']['unique_eggs'] = (int)$cheeseData['unique_eggs'];
            $response['cheese_hunt']['last_click'] = $cheeseData['last_click'];
        }
    } catch (Exception $e) {
        error_log("Cheese Hunt query error: " . $e->getMessage());
    }
    
    // 5. DISCORD CHEESE RACE STATS (using user_id)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_races,
                COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
                COUNT(CASE WHEN position <= 3 THEN 1 END) as podiums,
                MIN(position) as best_position
            FROM tbl_race_participants 
            WHERE user_id = ?
        ");
        $stmt->execute([$discordId]);
        $raceData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($raceData) {
            $response['discord_race']['total_races'] = (int)$raceData['total_races'];
            $response['discord_race']['wins'] = (int)$raceData['wins'];
            $response['discord_race']['podiums'] = (int)$raceData['podiums'];
            $response['discord_race']['best_position'] = $raceData['best_position'] ? (int)$raceData['best_position'] : null;
        }
    } catch (Exception $e) {
        error_log("Discord Race query error: " . $e->getMessage());
    }
    
    // Calculate overall DSPOINC (using user_id)
    try {
        $stmt = $db->prepare("
            SELECT SUM(score) as total_dspoinc
            FROM tbl_user_scores 
            WHERE user_id = ?
        ");
        $stmt->execute([$discordId]);
        $dspoincData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dspoincData && $dspoincData['total_dspoinc']) {
            $response['overall']['total_dspoinc'] = (int)$dspoincData['total_dspoinc'];
        }
    } catch (Exception $e) {
        error_log("DSPOINC calculation error: " . $e->getMessage());
    }
    
    // Quest stats (using user_id)
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as approved_quests
            FROM tbl_quest_claims 
            WHERE user_id = ? AND status = 'approved'
        ");
        $stmt->execute([$discordId]);
        $questData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($questData) {
            $response['overall']['quests_approved'] = (int)$questData['approved_quests'];
        }
    } catch (Exception $e) {
        error_log("Quest stats error: " . $e->getMessage());
    }
    
    // Calculate games played count
    $gamesPlayed = 0;
    if ($response['tetris']['total_games'] > 0) $gamesPlayed++;
    if ($response['snake']['total_games'] > 0) $gamesPlayed++;
    if ($response['space_invaders']['total_games'] > 0) $gamesPlayed++;
    if ($response['cheese_hunt']['total_clicks'] > 0) $gamesPlayed++;
    if ($response['discord_race']['total_races'] > 0) $gamesPlayed++;
    
    $response['overall']['games_played'] = $gamesPlayed;
    
    // Determine level based on total clicks
    if ($response['cheese_hunt']['total_clicks'] >= 100) {
        $response['overall']['level'] = 'Master Cheese Hunter';
    } elseif ($response['cheese_hunt']['total_clicks'] >= 50) {
        $response['overall']['level'] = 'Advanced Cheese Hunter';
    } elseif ($response['cheese_hunt']['total_clicks'] >= 10) {
        $response['overall']['level'] = 'Intermediate Cheese Hunter';
    } else {
        $response['overall']['level'] = 'Beginner Cheese Hunter';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error loading missions: ' . $e->getMessage()]);
}
?>
