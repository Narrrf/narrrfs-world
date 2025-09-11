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
        // Use environment-aware database path with Windows detection
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            // Windows environment - prioritize local database
            error_log("🔍 DEBUG: Current working directory: " . getcwd());
            error_log("🔍 DEBUG: __DIR__: " . __DIR__);
            error_log("🔍 DEBUG: Checking db/narrrf_world.sqlite: " . (file_exists('db/narrrf_world.sqlite') ? 'EXISTS' : 'NOT FOUND'));
            error_log("🔍 DEBUG: Checking __DIR__/../../db/narrrf_world.sqlite: " . (file_exists(__DIR__ . '/../../db/narrrf_world.sqlite') ? 'EXISTS' : 'NOT FOUND'));
            
            if (file_exists(__DIR__ . '/../../db/narrrf_world.sqlite')) {
                $db = new PDO('sqlite:' . __DIR__ . '/../../db/narrrf_world.sqlite');
                error_log("🏠 Connected to local database (Windows): " . __DIR__ . '/../../db/narrrf_world.sqlite');
            } elseif (file_exists('db/narrrf_world.sqlite')) {
                $db = new PDO('sqlite:db/narrrf_world.sqlite');
                error_log("🏠 Connected to local database (Windows): db/narrrf_world.sqlite");
            } else {
                throw new Exception('Local database not found on Windows: ' . __DIR__ . '/../../db/narrrf_world.sqlite');
            }
        } else {
            // Linux/Unix environment - check production first
            if (file_exists('/var/www/html/db/narrrf_world.sqlite')) {
                $db = new PDO('sqlite:/var/www/html/db/narrrf_world.sqlite');
                error_log("🌐 Connected to production database: /var/www/html/db/narrrf_world.sqlite");
                
                // 🔍 PRODUCTION DEBUG: Check what's actually in the production database
                try {
                    $debugStmt = $db->query("SELECT COUNT(*) as table_count FROM sqlite_master WHERE type='table'");
                    $debugResult = $debugStmt->fetch(PDO::FETCH_ASSOC);
                    error_log("🔍 PRODUCTION DEBUG: Database has " . $debugResult['table_count'] . " tables");
                    
                    // Check if Snake data exists
                    $snakeDebug = $db->query("SELECT COUNT(*) as count FROM tbl_tetris_scores WHERE game = 'snake'");
                    $snakeCount = $snakeDebug->fetch(PDO::FETCH_ASSOC);
                    error_log("🔍 PRODUCTION DEBUG: Snake games found: " . $snakeCount['count']);
                    
                    // Check if Space Invaders data exists
                    $spaceDebug = $db->query("SELECT COUNT(*) as count FROM tbl_tetris_scores WHERE game = 'space_invaders'");
                    $spaceCount = $spaceDebug->fetch(PDO::FETCH_ASSOC);
                    error_log("🔍 PRODUCTION DEBUG: Space Invaders games found: " . $spaceCount['count']);
                    
                } catch (Exception $e) {
                    error_log("🔍 PRODUCTION DEBUG ERROR: " . $e->getMessage());
                }
                
            } elseif (file_exists('db/narrrf_world.sqlite')) {
                $db = new PDO('sqlite:db/narrrf_world.sqlite');
                error_log("🏠 Connected to local database: db/narrrf_world.sqlite");
            } else {
                // Fallback: try to find the database
                $possiblePaths = [
                    __DIR__ . '/../../db/narrrf_world.sqlite',
                    'db/narrrf_world.sqlite',
                    __DIR__ . '/../db/narrrf_world.sqlite',
                    '../db/narrrf_world.sqlite'
                ];
                
                $dbPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $dbPath = $path;
                        break;
                    }
                }
                
                if ($dbPath) {
                    $db = new PDO('sqlite:' . $dbPath);
                    error_log("🔍 Connected to database via fallback path: " . $dbPath);
                } else {
                    throw new Exception('Database file not found. Tried paths: ' . implode(', ', $possiblePaths));
                }
            }
        }
        
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test database connection with a simple query
        $testStmt = $db->query("SELECT COUNT(*) as table_count FROM sqlite_master WHERE type='table'");
        $testResult = $testStmt->fetch(PDO::FETCH_ASSOC);
        error_log("✅ Database connection test: " . $testResult['table_count'] . " tables found");
        
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
            AND (season = 'season_3' OR season IS NULL OR season = '')
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
        error_log("🔍 SNAKE DEBUG: Querying for user $discordId");
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(score) as best_score,
                SUM(score) as total_score,
                MAX(timestamp) as last_played
            FROM tbl_tetris_scores 
            WHERE discord_id = ? AND game = 'snake'
            AND (season = 'season_3' OR season IS NULL OR season = '')
        ");
        $stmt->execute([$discordId]);
        $snakeData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($snakeData) {
            $response['snake']['total_games'] = (int)$snakeData['total_games'];
            $response['snake']['best_score'] = (int)$snakeData['best_score'];
            $response['snake']['total_score'] = (int)$snakeData['total_score'];
            $response['snake']['last_played'] = $snakeData['last_played'];
            $response['snake']['dspoinc_earned'] = (int)$snakeData['total_score'] * 10; // DSPOINC conversion
            error_log("✅ Snake stats found for user $discordId: " . $snakeData['total_games'] . " games, total score: " . $snakeData['total_score']);
        } else {
            error_log("❌ No snake stats found for user $discordId");
            // 🔍 DEBUG: Check if any snake data exists at all
            $debugStmt = $db->query("SELECT COUNT(*) as total FROM tbl_tetris_scores WHERE game = 'snake'");
            $debugResult = $debugStmt->fetch(PDO::FETCH_ASSOC);
            error_log("🔍 SNAKE DEBUG: Total snake games in database: " . $debugResult['total']);
            
            // Check if user exists in tetris_scores at all
            $userDebug = $db->prepare("SELECT COUNT(*) as total FROM tbl_tetris_scores WHERE discord_id = ?");
            $userDebug->execute([$discordId]);
            $userResult = $userDebug->fetch(PDO::FETCH_ASSOC);
            error_log("🔍 SNAKE DEBUG: Total games for user $discordId: " . $userResult['total']);
        }
    } catch (Exception $e) {
        error_log("Snake query error: " . $e->getMessage());
    }

    // 3. SPACE INVADERS STATS (using discord_id from tbl_tetris_scores)
    try {
        error_log("🔍 SPACE INVADERS DEBUG: Querying for user $discordId");
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(score) as best_score,
                SUM(score) as total_score,
                MAX(timestamp) as last_played
            FROM tbl_tetris_scores 
            WHERE discord_id = ? AND game = 'space_invaders'
            AND (season = 'season_3' OR season IS NULL OR season = '')
        ");
        $stmt->execute([$discordId]);
        $spaceData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($spaceData) {
            $response['space_invaders']['total_games'] = (int)$spaceData['total_games'];
            $response['space_invaders']['best_score'] = (int)$spaceData['best_score'];
            $response['space_invaders']['total_score'] = (int)$spaceData['total_score'];
            $response['space_invaders']['last_played'] = $spaceData['last_played'];
            $response['space_invaders']['dspoinc_earned'] = (int)($spaceData['total_score'] * 0.1); // DSPOINC conversion: 1 invader = 0.1 DSPOINC
            error_log("✅ Space Invaders stats found for user $discordId: " . $spaceData['total_games'] . " games, total score: " . $spaceData['total_score']);
        } else {
            error_log("❌ No Space Invaders stats found for user $discordId");
            // 🔍 DEBUG: Check if any space invaders data exists at all
            $debugStmt = $db->query("SELECT COUNT(*) as total FROM tbl_tetris_scores WHERE game = 'space_invaders'");
            $debugResult = $debugStmt->fetch(PDO::FETCH_ASSOC);
            error_log("🔍 SPACE INVADERS DEBUG: Total space invaders games in database: " . $debugResult['total']);
        }
    } catch (Exception $e) {
        error_log("Space Invaders query error: " . $e->getMessage());
    }

        // 🔧 CRITICAL FIX: Direct Discord ID to cheese clicks mapping
        // Try to get cheese clicks directly using Discord ID first
        try {
            $cheeseStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_clicks,
                    COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
                    COUNT(DISTINCT egg_id) as unique_eggs,
                    MAX(timestamp) as last_click
                FROM tbl_cheese_clicks 
                WHERE user_wallet = ?
                AND (season = 'season_3' OR season IS NULL OR season = '')
            ");
            $cheeseStmt->execute([$discordId]);
            $cheeseData = $cheeseStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cheeseData && $cheeseData['total_clicks'] > 0) {
                // Direct Discord ID mapping worked!
                $response['cheese_hunt']['total_clicks'] = (int)$cheeseData['total_clicks'];
                $response['cheese_hunt']['quest_clicks'] = (int)$cheeseData['quest_clicks'];
                $response['cheese_hunt']['unique_eggs'] = (int)$cheeseData['unique_eggs'];
                $response['cheese_hunt']['last_click'] = $cheeseData['last_click'];
                $response['cheese_hunt']['dspoinc_earned'] = (int)$cheeseData['total_clicks'] * 10; // DSPOINC conversion
                error_log("✅ Cheese clicks found directly with Discord ID: " . $discordId . " - Total: " . $cheeseData['total_clicks']);
            } else {
                // Try wallet lookup as fallback
                $walletAddress = null;
                
                // Try to get wallet from tbl_holder_verifications using user_id (Discord ID)
                try {
                    $walletStmt = $db->prepare("SELECT wallet FROM tbl_holder_verifications WHERE user_id = ? LIMIT 1");
                    $walletStmt->execute([$discordId]);
                    $walletResult = $walletStmt->fetch(PDO::FETCH_ASSOC);
                    if ($walletResult && isset($walletResult['wallet'])) {
                        $walletAddress = $walletResult['wallet'];
                    }
                } catch (Exception $e) {
                    error_log("Wallet lookup error: " . $e->getMessage());
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
                        AND (season = 'season_3' OR season IS NULL OR season = '')
                    ");
                    $stmt->execute([$walletAddress]);
                    
                    $cheeseData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($cheeseData) {
                        $response['cheese_hunt']['total_clicks'] = (int)$cheeseData['total_clicks'];
                        $response['cheese_hunt']['quest_clicks'] = (int)$cheeseData['quest_clicks'];
                        $response['cheese_hunt']['unique_eggs'] = (int)$cheeseData['unique_eggs'];
                        $response['cheese_hunt']['last_click'] = $cheeseData['last_click'];
                        $response['cheese_hunt']['dspoinc_earned'] = (int)$cheeseData['total_clicks'] * 10; // DSPOINC conversion
                        error_log("✅ Cheese clicks found via wallet lookup: " . $walletAddress . " - Total: " . $cheeseData['total_clicks']);
                    }
                } else {
                    // If no wallet found, log this for debugging and return 0 stats
                    error_log("❌ No wallet found for Discord ID: " . $discordId . " in tbl_holder_verifications");
                    $response['cheese_hunt']['total_clicks'] = 0;
                    $response['cheese_hunt']['quest_clicks'] = 0;
                    $response['cheese_hunt']['unique_eggs'] = 0;
                    $response['cheese_hunt']['last_click'] = null;
                    $response['cheese_hunt']['dspoinc_earned'] = 0;
                }
            }
        } catch (Exception $e) {
            error_log("Cheese Hunt query error: " . $e->getMessage());
            // Set default values on error
            $response['cheese_hunt']['total_clicks'] = 0;
            $response['cheese_hunt']['quest_clicks'] = 0;
            $response['cheese_hunt']['unique_eggs'] = 0;
            $response['cheese_hunt']['last_click'] = null;
            $response['cheese_hunt']['dspoinc_earned'] = 0;
        }

    // 5. DISCORD CHEESE RACE STATS (using discord_id from tbl_race_participants)
    try {
        error_log("🔍 DISCORD RACE DEBUG: Querying for user $discordId");
        
        // First, let's check if the user has any race data at all
        $debugStmt = $db->prepare("SELECT COUNT(*) as total FROM tbl_race_participants WHERE user_id = ?");
        $debugStmt->execute([$discordId]);
        $debugResult = $debugStmt->fetch(PDO::FETCH_ASSOC);
        error_log("🔍 DISCORD RACE DEBUG: Total race participations for user $discordId: " . $debugResult['total']);
        
        // Check what statuses exist for this user
        $statusStmt = $db->prepare("SELECT status, COUNT(*) as count FROM tbl_race_participants WHERE user_id = ? GROUP BY status");
        $statusStmt->execute([$discordId]);
        $statusResults = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("🔍 DISCORD RACE DEBUG: Status breakdown for user $discordId: " . json_encode($statusResults));
        
        // 🔧 FIXED: Query for Discord Race stats - count ALL races (not just completed ones)
        // This will show total race participation including joined, waiting, and completed races
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_races,
                COUNT(CASE WHEN finished_at IS NOT NULL THEN 1 END) as completed_races, -- Count races that have finished
                COUNT(CASE WHEN position = 1 THEN 1 END) as wins, -- Count first place finishes
                COUNT(CASE WHEN position <= 3 THEN 1 END) as podiums, -- Count podium finishes (top 3)
                MIN(position) as best_position, -- Best position achieved
                SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned
            FROM tbl_race_participants 
            WHERE user_id = ? -- 🔧 CRITICAL FIX: Use user_id field (matches database schema)
            AND (season = 'season_3' OR season IS NULL OR season = '')
        ");
        $stmt->execute([$discordId]);
        $raceData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("🔍 DISCORD RACE DEBUG: Query result for user $discordId: " . json_encode($raceData));
        
        if ($raceData) {
            $response['discord_race']['total_races'] = (int)$raceData['total_races'];
            $response['discord_race']['wins'] = (int)$raceData['wins'];
            $response['discord_race']['podiums'] = (int)$raceData['podiums'];
            $response['discord_race']['best_position'] = $raceData['best_position'] ? (int)$raceData['best_position'] : null;
            $response['discord_race']['dspoinc_earned'] = (int)$raceData['total_dspoinc_earned']; // Use actual DSPOINC earned
            error_log("✅ Discord Race stats found for user $discordId: " . $raceData['total_races'] . " races (ALL races counted!), " . $raceData['total_dspoinc_earned'] . " DSPOINC");
            
            // 🔍 DEBUG: Show breakdown of race statuses
            $statusBreakdown = $db->prepare("SELECT status, COUNT(*) as count FROM tbl_race_participants WHERE user_id = ? GROUP BY status");
            $statusBreakdown->execute([$discordId]);
            $statuses = $statusBreakdown->fetchAll(PDO::FETCH_ASSOC);
            error_log("🔍 DISCORD RACE DEBUG: Race status breakdown for user $discordId: " . json_encode($statuses));
        } else {
            error_log("❌ No Discord Race stats found for user $discordId");
            
            // 🔍 DEBUG: Check if any races exist at all
            $totalDebug = $db->query("SELECT COUNT(*) as total FROM tbl_race_participants");
            $totalResult = $totalDebug->fetch(PDO::FETCH_ASSOC);
            error_log("🔍 DISCORD RACE DEBUG: Total race participations in database: " . $totalResult['total']);
            
            // Check if user exists in race_participants at all
            $userRaceDebug = $db->prepare("SELECT COUNT(*) as total FROM tbl_race_participants WHERE user_id = ?");
            $userRaceDebug->execute([$discordId]);
            $userRaceResult = $userRaceDebug->fetch(PDO::FETCH_ASSOC);
            error_log("🔍 DISCORD RACE DEBUG: Total race participations for user $discordId: " . $userRaceResult['total']);
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
    
    // Summary log for all games
    error_log("🎮 MISSION STATUS SUMMARY for user $discordId:");
    error_log("  Tetris: " . $response['tetris']['total_games'] . " games, " . $response['tetris']['dspoinc_earned'] . " DSPOINC");
    error_log("  Snake: " . $response['snake']['total_games'] . " games, " . $response['snake']['dspoinc_earned'] . " DSPOINC");
    error_log("  Space Invaders: " . $response['space_invaders']['total_games'] . " games, " . $response['space_invaders']['dspoinc_earned'] . " DSPOINC");
    error_log("  Cheese Hunt: " . $response['cheese_hunt']['total_clicks'] . " clicks, " . $response['cheese_hunt']['dspoinc_earned'] . " DSPOINC");
    error_log("  Discord Race: " . $response['discord_race']['total_races'] . " races, " . $response['discord_race']['dspoinc_earned'] . " DSPOINC");
    error_log("  Total Games Played: " . $response['overall']['games_played'] . "/5");
    error_log("  Total DSPOINC: " . $response['overall']['total_dspoinc']);
    
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
                'url' => 'https://discord.com/invite/PFFztgqwe2',
                'status' => $response['discord_race']['total_races'] > 0 ? 'active' : 'not_played',
                'stats' => [
                    'total_races' => $response['discord_race']['total_races'],
                    'wins' => $response['discord_race']['wins'],
                    'podium_finishes' => $response['discord_race']['podiums'],
                    'best_position' => $response['discord_race']['best_position'] ? number_format($response['discord_race']['best_position'], 1) : 'N/A',
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