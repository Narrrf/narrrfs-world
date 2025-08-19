<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Handle GET requests for testing
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'status' => 'API is working',
        'method' => 'GET',
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => 'Use POST method with user_id to get game missions'
    ]);
    exit;
}

try {
    // Get POST data
    $rawInput = file_get_contents('php://input');
    error_log("Raw input received: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    
    if (!isset($input['user_id'])) {
        throw new Exception('user_id is required');
    }
    
    $discordId = $input['user_id'];
    error_log("Processing request for Discord ID: " . $discordId);
    
    // Connect to database using the correct path
    try {
        $db = new PDO('sqlite:/var/www/html/db/narrrf_world.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
    
    // Initialize response data structure
    $response = [
        'tetris' => [
            'total_games' => 0,
            'best_score' => 0,
            'total_score' => 0,
            'last_played' => null
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
    
    // 1. TETRIS STATS (using discord_id from tbl_tetris_scores)
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
    
    // 2. SNAKE STATS (using user_id from tbl_user_scores)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(score) as best_score,
                SUM(score) as total_score,
                MAX(timestamp) as last_played
            FROM tbl_user_scores 
            WHERE user_id = ? AND game = 'snake'
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
    
    // 3. SPACE INVADERS STATS (using user_id from tbl_user_scores)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(score) as best_score,
                SUM(score) as total_score,
                MAX(timestamp) as last_played
            FROM tbl_user_scores 
            WHERE user_id = ? AND game = 'space_invaders'
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
    
    // 4. CHEESE HUNT STATS (using user_wallet from tbl_cheese_clicks)
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
    
    // 5. DISCORD CHEESE RACE STATS (using user_id from tbl_race_participants)
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
    
    // Calculate overall DSPOINC (using user_id from tbl_user_scores)
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
    
    // Quest stats (using user_id from tbl_quest_claims)
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
    
    // Log the final response for debugging
    error_log("Final response for user $discordId: " . json_encode($response));
    
    // Return the response in the format expected by the frontend
    echo json_encode([
        'success' => true,
        'total_dspoinc' => $response['overall']['total_dspoinc'],
        'achievements' => [
            'games_played' => $response['overall']['games_played'],
            'cheese_hunter_level' => $response['overall']['level']
        ],
        'quest_stats' => [
            'approved_claims' => $response['overall']['quests_approved'],
            'pending_claims' => 0, // We can add this later if needed
            'total_claims' => $response['overall']['quests_approved']
        ],
        'games' => [
            'tetris' => [
                'name' => 'Tetris Scroll',
                'icon' => '🧩',
                'url' => '/profile.html#cheese-tetris',
                'total_games' => $response['tetris']['total_games'],
                'best_score' => $response['tetris']['best_score'],
                'total_score' => $response['tetris']['total_score'],
                'last_played' => $response['tetris']['last_played'],
                'status' => $response['tetris']['total_games'] > 0 ? 'active' : 'not_played'
            ],
            'snake' => [
                'name' => 'Snake Scroll',
                'icon' => '🐍',
                'url' => '/profile.html#cheese-snake',
                'total_games' => $response['snake']['total_games'],
                'best_score' => $response['snake']['best_score'],
                'total_score' => $response['snake']['total_score'],
                'last_played' => $response['snake']['last_played'],
                'status' => $response['snake']['total_games'] > 0 ? 'active' : 'not_played'
            ],
            'space_invaders' => [
                'name' => 'Space Cheese Invaders',
                'icon' => '👾',
                'url' => '/profile.html#cheese-space-invaders',
                'total_games' => $response['space_invaders']['total_games'],
                'best_score' => $response['space_invaders']['best_score'],
                'total_score' => $response['space_invaders']['total_score'],
                'last_played' => $response['space_invaders']['last_played'],
                'status' => $response['space_invaders']['total_games'] > 0 ? 'active' : 'not_played'
            ],
            'cheese_hunt' => [
                'name' => 'Cheese Hunt',
                'icon' => '🧀',
                'url' => '/',
                'total_clicks' => $response['cheese_hunt']['total_clicks'],
                'quest_clicks' => $response['cheese_hunt']['quest_clicks'],
                'unique_eggs' => $response['cheese_hunt']['unique_eggs'],
                'last_click' => $response['cheese_hunt']['last_click'],
                'status' => $response['cheese_hunt']['total_clicks'] > 0 ? 'active' : 'not_played'
            ],
            'discord_race' => [
                'name' => 'Discord Cheese Race',
                'icon' => '🏁',
                'url' => 'https://discord.gg/narrrfs',
                'total_races' => $response['discord_race']['total_races'],
                'wins' => $response['discord_race']['wins'],
                'podium_finishes' => $response['discord_race']['podiums'],
                'best_position' => $response['discord_race']['best_position'] ? $response['discord_race']['best_position'] : 'N/A',
                'status' => $response['discord_race']['total_races'] > 0 ? 'active' : 'not_played'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("User game missions API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error loading missions: ' . $e->getMessage()]);
}
?>
