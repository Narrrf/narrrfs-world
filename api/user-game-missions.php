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
    // Get user_id from query parameters or try to get from session
    $discordId = null;
    
    if (isset($_GET['user_id'])) {
        $discordId = $_GET['user_id'];
    } elseif (isset($_GET['discord_id'])) {
        $discordId = $_GET['discord_id'];
    } else {
        // Try to get from session or cookies
        session_start();
        if (isset($_SESSION['discord_id'])) {
            $discordId = $_SESSION['discord_id'];
        } elseif (isset($_COOKIE['discord_id'])) {
            $discordId = $_COOKIE['discord_id'];
        }
    }
    
    if (!$discordId) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No user_id or discord_id provided. Use ?user_id=YOUR_DISCORD_ID or ?discord_id=YOUR_DISCORD_ID'
        ]);
        exit;
    }
    
    // Continue with the existing logic using the discordId
} else {
    // Handle POST requests
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
}

error_log("Processing request for Discord ID: " . $discordId);

try {
    // Connect to database using the correct path
    try {
        // Use environment-aware database path
        if (file_exists('/var/www/html/db/narrrf_world.sqlite')) {
            // Production environment (Render)
            $db = new PDO('sqlite:/var/www/html/db/narrrf_world.sqlite');
        } else {
            // Local environment (XAMPP)
            $db = new PDO('sqlite:' . __DIR__ . '/../db/narrrf_world.sqlite');
        }
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
            'last_played' => null,
            'dspoinc_earned' => 0
        ],
        'snake' => [
            'total_games' => 0,
            'best_score' => 0,
            'total_score' => 0,
            'last_played' => null,
            'dspoinc_earned' => 0
        ],
        'space_invaders' => [
            'total_games' => 0,
            'best_score' => 0,
            'total_score' => 0,
            'last_played' => null,
            'dspoinc_earned' => 0
        ],
        'cheese_hunt' => [
            'total_clicks' => 0,
            'quest_clicks' => 0,
            'unique_eggs' => 0,
            'last_click' => null,
            'dspoinc_earned' => 0
        ],
        'discord_race' => [
            'total_races' => 0,
            'wins' => 0,
            'podiums' => 0,
            'best_position' => null,
            'dspoinc_earned' => 0
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
            WHERE discord_id = ? AND game = 'tetris'
        ");
        $stmt->execute([$discordId]);
        $tetrisData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tetrisData) {
            $response['tetris']['total_games'] = (int)$tetrisData['total_games'];
            $response['tetris']['best_score'] = (int)$tetrisData['best_score'];
            $response['tetris']['total_score'] = (int)$tetrisData['total_score'];
            $response['tetris']['last_played'] = $tetrisData['last_played'];
            $response['tetris']['dspoinc_earned'] = (int)$tetrisData['total_score'] * 10; // DSPOINC conversion
        }
    } catch (Exception $e) {
        error_log("Tetris query error: " . $e->getMessage());
    }

    // 2. SNAKE STATS (using discord_id from tbl_tetris_scores)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(score) as best_score,
                SUM(score) as total_score,
                MAX(timestamp) as last_played
            FROM tbl_tetris_scores 
            WHERE discord_id = ? AND game = 'snake'
        ");
        $stmt->execute([$discordId]);
        $snakeData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($snakeData) {
            $response['snake']['total_games'] = (int)$snakeData['total_games'];
            $response['snake']['best_score'] = (int)$snakeData['best_score'];
            $response['snake']['total_score'] = (int)$snakeData['total_score'];
            $response['snake']['last_played'] = $snakeData['last_played'];
            $response['snake']['dspoinc_earned'] = (int)$snakeData['total_score'] * 10; // DSPOINC conversion
        }
    } catch (Exception $e) {
        error_log("Snake query error: " . $e->getMessage());
    }

    // 3. SPACE INVADERS STATS (using discord_id from tbl_tetris_scores)
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(score) as best_score,
                SUM(score) as total_score,
                MAX(timestamp) as last_played
            FROM tbl_tetris_scores 
            WHERE discord_id = ? AND game = 'space_invaders'
        ");
        $stmt->execute([$discordId]);
        $spaceData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($spaceData) {
            $response['space_invaders']['total_games'] = (int)$spaceData['total_games'];
            $response['space_invaders']['best_score'] = (int)$spaceData['best_score'];
            $response['space_invaders']['total_score'] = (int)$spaceData['total_score'];
            $response['space_invaders']['last_played'] = $spaceData['last_played'];
            $response['space_invaders']['dspoinc_earned'] = (int)$spaceData['total_score'] * 10; // DSPOINC conversion - FIXED: was using tetrisData
        }
    } catch (Exception $e) {
        error_log("Space Invaders query error: " . $e->getMessage());
    }

    // 4. CHEESE HUNT STATS (using discord_id from tbl_cheese_clicks)
    try {
        // First try to find the user's wallet address from their Discord ID
        // We'll look in tbl_users or other tables that might have this mapping
        $walletAddress = null;
        
        // Try to get wallet from tbl_users if there's a wallet field
        try {
            $walletStmt = $db->prepare("SELECT wallet FROM tbl_users WHERE discord_id = ?");
            $walletStmt->execute([$discordId]);
            $walletResult = $walletStmt->fetch(PDO::FETCH_ASSOC);
            if ($walletResult && isset($walletResult['wallet'])) {
                $walletAddress = $walletResult['wallet'];
            }
        } catch (Exception $e) {
            // No wallet field in tbl_users, continue without it
        }
        
        // If we have a wallet address, query cheese clicks with it
        if ($walletAddress) {
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_clicks,
                    COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
                    COUNT(DISTINCT egg_id) as unique_eggs,
                    MAX(timestamp) as last_click
                FROM tbl_cheese_clicks 
                WHERE user_wallet = ?
            ");
            $stmt->execute([$walletAddress]);
        } else {
            // If no wallet, try to query with discord_id (in case the table was updated)
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_clicks,
                    COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
                    COUNT(DISTINCT egg_id) as unique_eggs,
                    MAX(timestamp) as last_click
                FROM tbl_cheese_clicks 
                WHERE discord_id = ?
            ");
            $stmt->execute([$discordId]);
        }
        
        $cheeseData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cheeseData) {
            $response['cheese_hunt']['total_clicks'] = (int)$cheeseData['total_clicks'];
            $response['cheese_hunt']['quest_clicks'] = (int)$cheeseData['quest_clicks'];
            $response['cheese_hunt']['unique_eggs'] = (int)$cheeseData['unique_eggs'];
            $response['cheese_hunt']['last_click'] = $cheeseData['last_click'];
            $response['cheese_hunt']['dspoinc_earned'] = (int)$cheeseData['total_clicks'] * 10; // DSPOINC conversion
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
            $response['discord_race']['dspoinc_earned'] = (int)$raceData['total_races'] * 100; // DSPOINC conversion
        }
    } catch (Exception $e) {
        error_log("Discord Race query error: " . $e->getMessage());
    }

    // Calculate total DSPOINC from all games
    $response['overall']['total_dspoinc'] = 
        $response['tetris']['dspoinc_earned'] + 
        $response['snake']['dspoinc_earned'] + 
        $response['space_invaders']['dspoinc_earned'] + 
        $response['cheese_hunt']['dspoinc_earned'] + 
        $response['discord_race']['dspoinc_earned'];

    // Count how many games the user has played
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
    
    // Additional debugging for Cheese Hunt
    if (isset($response['cheese_hunt'])) {
        error_log("Cheese Hunt debug - Total clicks: " . $response['cheese_hunt']['total_clicks'] . 
                 ", Quest clicks: " . $response['cheese_hunt']['quest_clicks'] . 
                 ", Unique eggs: " . $response['cheese_hunt']['unique_eggs']);
    }
    
    // Additional debugging for Snake
    if (isset($response['snake'])) {
        error_log("Snake debug - Total games: " . $response['snake']['total_games'] . 
                 ", Best score: " . $response['snake']['best_score'] . 
                 ", Total score: " . $response['snake']['total_score']);
    }

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
                'status' => $response['tetris']['total_games'] > 0 ? 'active' : 'not_played',
                'stats' => [
                    'total_games' => $response['tetris']['total_games'],
                    'best_score' => $response['tetris']['best_score'],
                    'total_score' => $response['tetris']['total_score'],
                    'dspoinc_earned' => $response['tetris']['dspoinc_earned'],
                    'last_played' => $response['tetris']['last_played']
                ]
            ],
            'snake' => [
                'name' => 'Snake Scroll',
                'icon' => '🐍',
                'url' => '/profile.html#cheese-snake',
                'status' => $response['snake']['total_games'] > 0 ? 'active' : 'not_played',
                'stats' => [
                    'total_games' => $response['snake']['total_games'],
                    'best_score' => $response['snake']['best_score'],
                    'total_score' => $response['snake']['total_score'],
                    'dspoinc_earned' => $response['snake']['dspoinc_earned'],
                    'last_played' => $response['snake']['last_played']
                ]
            ],
            'space_invaders' => [
                'name' => 'Space Cheese Invaders',
                'icon' => '👾',
                'url' => '/space-invaders-test.html',
                'status' => $response['space_invaders']['total_games'] > 0 ? 'active' : 'not_played',
                'stats' => [
                    'total_games' => $response['space_invaders']['total_games'],
                    'best_score' => $response['space_invaders']['best_score'],
                    'total_score' => $response['space_invaders']['total_score'],
                    'dspoinc_earned' => $response['space_invaders']['dspoinc_earned'],
                    'last_played' => $response['space_invaders']['last_played']
                ]
            ],
            'cheese_hunt' => [
                'name' => 'Cheese Hunt',
                'icon' => '🧀',
                'url' => '/',
                'status' => $response['cheese_hunt']['total_clicks'] > 0 ? 'active' : 'not_played',
                'stats' => [
                    'total_clicks' => $response['cheese_hunt']['total_clicks'],
                    'quest_clicks' => $response['cheese_hunt']['quest_clicks'],
                    'unique_eggs' => $response['cheese_hunt']['unique_eggs'],
                    'dspoinc_earned' => $response['cheese_hunt']['dspoinc_earned'],
                    'last_click' => $response['cheese_hunt']['last_click']
                ]
            ],
            'discord_race' => [
                'name' => 'Discord Cheese Race',
                'icon' => '🏁',
                'url' => 'https://discord.com/invite/qYYNGJrR43',
                'status' => $response['discord_race']['total_races'] > 0 ? 'active' : 'not_played',
                'stats' => [
                    'total_races' => $response['discord_race']['total_races'],
                    'wins' => $response['discord_race']['wins'],
                    'podium_finishes' => $response['discord_race']['podiums'],
                    'best_position' => $response['discord_race']['best_position'] ? $response['discord_race']['best_position'] : 'N/A',
                    'dspoinc_earned' => $response['discord_race']['dspoinc_earned']
                ]
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("User game missions API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error loading missions: ' . $e->getMessage()]);
}
?>
