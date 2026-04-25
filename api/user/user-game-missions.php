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

    // Get current active season from database
    $currentSeason = 'Season 9'; // Default fallback
    $currentSeasonStart = null;
    $currentSeasonEnd = null;
    try {
        $seasonStmt = $db->query("SELECT season_name, start_date, end_date FROM tbl_seasons WHERE is_active = 1 ORDER BY season_id DESC LIMIT 1");
        $seasonResult = $seasonStmt->fetch(PDO::FETCH_ASSOC);
        if ($seasonResult && isset($seasonResult['season_name'])) {
            $currentSeason = $seasonResult['season_name'];
            $currentSeasonStart = $seasonResult['start_date'] ?? null;
            $currentSeasonEnd = $seasonResult['end_date'] ?? null;
        }
        error_log("🎯 Current active season detected: " . $currentSeason);
    } catch (Exception $e) {
        error_log("⚠️ Could not get current season, using fallback: " . $currentSeason);
    }

    if (!$currentSeasonStart) {
        // Fallback: assume current season started today (should not happen if database is correct)
        $currentSeasonStart = date('Y-m-d 00:00:00');
    }
    
    // Ensure we're using the exact start time (00:00:00) for strict filtering
    // This prevents including data from the same day but before the season officially started
    if ($currentSeasonStart && strpos($currentSeasonStart, ' ') !== false) {
        // Extract just the date part and set to 00:00:00 to ensure strict filtering
        $datePart = explode(' ', $currentSeasonStart)[0];
        $currentSeasonStart = $datePart . ' 00:00:00';
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
                'cheeseman' => [
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
        'cheese_rumble' => [
            'total_rumbles' => 0,
            'wins' => 0,
            'podiums' => 0,
            'best_position' => null,
            'dspoinc_earned' => 0,
            'last_played' => null
        ],
		// 🔮 Glyph Memory - current season stats only
'glyph_memory' => [
    'total_runs' => 0,
    'best_time_ms' => null,
    'avg_time_ms' => null,
    'best_pairs_matched' => 0,
    'last_played' => null,
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
        error_log("🔍 TETRIS DEBUG: Querying for user $discordId");
        $tetrisData = null;
        $tetrisSeasonFilters = [
            ['condition' => 'season = ?', 'label' => 'exact'],
            ['condition' => 'season LIKE ? || "%"', 'label' => 'prefix']
        ];

        foreach ($tetrisSeasonFilters as $filter) {
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_games,
                    MAX(score) as best_score,
                    SUM(score) as total_score,
                    MAX(timestamp) as last_played
                FROM tbl_tetris_scores 
                WHERE discord_id = ? AND game = 'tetris'
                AND (
                    " . $filter['condition'] . "
                    OR (timestamp >= ? AND (? IS NULL OR timestamp < ?))
                )
            ");
            $stmt->execute([
                $discordId,
                $currentSeason,
                $currentSeasonStart,
                $currentSeasonEnd,
                $currentSeasonEnd
            ]);
            $tetrisData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tetrisData && (int)$tetrisData['total_games'] > 0) {
                error_log("✅ TETRIS: season match (" . $filter['label'] . ") for $discordId");
                break;
            }
        }
        
        if (!$tetrisData || (int)$tetrisData['total_games'] === 0) {
            error_log("⚠️ TETRIS: No Season $currentSeason data for $discordId, showing 0 (current season only)");
            $tetrisData = null; // Don't fallback to all-time for current season stats
        }
        
        if ($tetrisData && (int)$tetrisData['total_games'] > 0) {
            $response['tetris']['total_games'] = (int)$tetrisData['total_games'];
            $response['tetris']['best_score'] = (int)$tetrisData['best_score'];
            $response['tetris']['total_score'] = (int)$tetrisData['total_score'];
            $response['tetris']['last_played'] = $tetrisData['last_played'];
            $response['tetris']['dspoinc_earned'] = (int)$tetrisData['total_score']; // Tetris saves DSPOINC directly
        }
    } catch (Exception $e) {
        error_log("Tetris query error: " . $e->getMessage());
    }

    // 2. SNAKE STATS (using discord_id from tbl_tetris_scores)
    try {
        error_log("🔍 SNAKE DEBUG: Querying for user $discordId");
        $snakeData = null;
        $snakeSeasonFilters = [
            ['condition' => 'season = ?', 'label' => 'exact'],
            ['condition' => 'season LIKE ? || "%"', 'label' => 'prefix']
        ];

        foreach ($snakeSeasonFilters as $filter) {
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_games,
                    MAX(score) as best_score,
                    SUM(score) as total_score,
                    MAX(timestamp) as last_played
                FROM tbl_tetris_scores 
                WHERE discord_id = ? AND game = 'snake'
                AND (
                    " . $filter['condition'] . "
                    OR (timestamp >= ? AND (? IS NULL OR timestamp < ?))
                )
            ");
            $stmt->execute([
                $discordId,
                $currentSeason,
                $currentSeasonStart,
                $currentSeasonEnd,
                $currentSeasonEnd
            ]);
            $snakeData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($snakeData && (int)$snakeData['total_games'] > 0) {
                error_log("✅ SNAKE: season match (" . $filter['label'] . ") for $discordId");
                break;
            }
        }
        
        if (!$snakeData || (int)$snakeData['total_games'] === 0) {
            error_log("⚠️ SNAKE: No Season $currentSeason data for $discordId, showing 0 (current season only)");
            $snakeData = null; // Don't fallback to all-time for current season stats
        }
        
        if ($snakeData && (int)$snakeData['total_games'] > 0) {
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
        error_log("🔍 SPACE INVADERS DEBUG: Querying for user $discordId");
        $spaceData = null;
        $spaceSeasonFilters = [
            ['condition' => 'season = ?', 'label' => 'exact'],
            ['condition' => 'season LIKE ? || "%"', 'label' => 'prefix']
        ];

        foreach ($spaceSeasonFilters as $filter) {
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_games,
                    MAX(score) as best_score,
                    SUM(score) as total_score,
                    MAX(timestamp) as last_played
                FROM tbl_tetris_scores 
                WHERE discord_id = ? AND game = 'space_invaders'
                AND (
                    " . $filter['condition'] . "
                    OR (timestamp >= ? AND (? IS NULL OR timestamp < ?))
                )
            ");
            $stmt->execute([
                $discordId,
                $currentSeason,
                $currentSeasonStart,
                $currentSeasonEnd,
                $currentSeasonEnd
            ]);
            $spaceData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($spaceData && (int)$spaceData['total_games'] > 0) {
                error_log("✅ SPACE INVADERS: season match (" . $filter['label'] . ") for $discordId");
                break;
            }
        }
        
        if (!$spaceData || (int)$spaceData['total_games'] === 0) {
            error_log("⚠️ SPACE INVADERS: No Season $currentSeason data for $discordId, showing 0 (current season only)");
            $spaceData = null; // Don't fallback to all-time for current season stats
        }
        
        if ($spaceData && (int)$spaceData['total_games'] > 0) {
            $response['space_invaders']['total_games'] = (int)$spaceData['total_games'];
            $response['space_invaders']['best_score'] = (int)$spaceData['best_score'];
            $response['space_invaders']['total_score'] = (int)$spaceData['total_score'];
            $response['space_invaders']['last_played'] = $spaceData['last_played'];
            $response['space_invaders']['dspoinc_earned'] = (int)($spaceData['total_score'] * 0.1); // DSPOINC conversion
        }
    } catch (Exception $e) {
        error_log("Space Invaders query error: " . $e->getMessage());
    }

        // 4. CHEESE RUNNER / CHEESEMAN STATS
    try {
        error_log("🔍 CHEESEMAN DEBUG: Querying for user $discordId");

        $cheesemanData = null;
        $cheesemanSeasonFilters = [
            ['condition' => 'season = ?', 'label' => 'exact'],
            ['condition' => 'season LIKE ? || "%"', 'label' => 'prefix']
        ];

        foreach ($cheesemanSeasonFilters as $filter) {
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_games,
                    MAX(score) as best_score,
                    SUM(score) as total_score,
                    MAX(timestamp) as last_played
                FROM tbl_tetris_scores 
                WHERE discord_id = ? 
                AND game = 'cheeseman'
                AND (
                    " . $filter['condition'] . "
                    OR (timestamp >= ? AND (? IS NULL OR timestamp < ?))
                )
            ");

            $stmt->execute([
                $discordId,
                $currentSeason,
                $currentSeasonStart,
                $currentSeasonEnd,
                $currentSeasonEnd
            ]);

            $cheesemanData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cheesemanData && (int)$cheesemanData['total_games'] > 0) {
                error_log("✅ CHEESEMAN: season match (" . $filter['label'] . ") for $discordId");
                break;
            }
        }

        if ($cheesemanData && (int)$cheesemanData['total_games'] > 0) {
            $response['cheeseman'] = [
                'total_games' => (int)$cheesemanData['total_games'],
                'best_score' => (int)$cheesemanData['best_score'],
                'total_score' => (int)$cheesemanData['total_score'],
                'last_played' => $cheesemanData['last_played'],
                'dspoinc_earned' => (int)$cheesemanData['total_score']
            ];

            $response['overall']['games_played'] += 1;
            $response['overall']['total_dspoinc'] += (int)$cheesemanData['total_score'];
        }
    } catch (Exception $e) {
        error_log("Cheeseman stats query error: " . $e->getMessage());
    }

        // 🔧 CRITICAL FIX: Direct Discord ID to cheese clicks mapping
        // Try to get cheese clicks directly using Discord ID first
        try {
            $cheeseData = null;
            $seasonFilters = [
                ['condition' => 'season = ?', 'label' => 'exact'],
                ['condition' => 'season LIKE ? || "%"', 'label' => 'prefix']
            ];

            foreach ($seasonFilters as $filter) {
                $cheeseStmt = $db->prepare("
                    SELECT 
                        COUNT(*) as total_clicks,
                        COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
                        COUNT(DISTINCT egg_id) as unique_eggs,
                        MAX(timestamp) as last_click
                    FROM tbl_cheese_clicks 
                    WHERE user_wallet = ?
                    AND (
                        " . $filter['condition'] . "
                        OR (timestamp >= ? AND (? IS NULL OR timestamp < ?))
                    )
                ");
                $params = [$discordId, $currentSeason, $currentSeasonStart, $currentSeasonEnd, $currentSeasonEnd];
                $cheeseStmt->execute($params);
                $cheeseData = $cheeseStmt->fetch(PDO::FETCH_ASSOC);

                if ($cheeseData && $cheeseData['total_clicks'] > 0) {
                    error_log("✅ Cheese Hunt season match (" . $filter['label'] . ") for $discordId: " . $cheeseData['total_clicks']);
                    break;
                }
            }

            if (!$cheeseData || $cheeseData['total_clicks'] == 0) {
                error_log("ℹ️ Cheese Hunt: no Season $currentSeason data for $discordId (no fallback applied)");
                $cheeseData = null;
            }

            if ($cheeseData && $cheeseData['total_clicks'] > 0) {
                $response['cheese_hunt']['total_clicks'] = (int)$cheeseData['total_clicks'];
                $response['cheese_hunt']['quest_clicks'] = (int)$cheeseData['quest_clicks'];
                $response['cheese_hunt']['unique_eggs'] = (int)$cheeseData['unique_eggs'];
                $response['cheese_hunt']['last_click'] = $cheeseData['last_click'];
                $response['cheese_hunt']['dspoinc_earned'] = (int)$cheeseData['total_clicks'] * 10; // DSPOINC conversion
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
        $raceData = null;
        $raceSeasonFilters = [
            ['condition' => 'season = ?', 'label' => 'exact'],
            ['condition' => 'season LIKE ? || "%"', 'label' => 'prefix']
        ];

        foreach ($raceSeasonFilters as $filter) {
            // Try season column match first
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_races,
                    COUNT(CASE WHEN finished_at IS NOT NULL THEN 1 END) as completed_races,
                    COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
                    COUNT(CASE WHEN position <= 3 THEN 1 END) as podiums,
                    MIN(position) as best_position,
                    SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned
                FROM tbl_race_participants 
                WHERE user_id = ?
                AND " . $filter['condition'] . "
            ");
            $stmt->execute([
                $discordId,
                $currentSeason
            ]);
            $raceData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($raceData && (int)$raceData['total_races'] > 0) {
                error_log("✅ DISCORD RACE: season match (" . $filter['label'] . ") for $discordId: " . $raceData['total_races']);
                break;
            }
        }
        
        // 🔧 FIX: Timestamp fallback for recent races (January 11, 2026)
        // Some races may have old season names but were created during current season
        // Check timestamp as fallback (similar to Cheese Hunt pattern)
        if (!$raceData || (int)$raceData['total_races'] === 0) {
            if ($currentSeasonStart && $currentSeasonEnd) {
                error_log("🔍 DISCORD RACE: Trying timestamp fallback for Season $currentSeason (from $currentSeasonStart to $currentSeasonEnd)");
                $timestampStmt = $db->prepare("
                    SELECT 
                        COUNT(*) as total_races,
                        COUNT(CASE WHEN finished_at IS NOT NULL THEN 1 END) as completed_races,
                        COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
                        COUNT(CASE WHEN position <= 3 THEN 1 END) as podiums,
                        MIN(position) as best_position,
                        SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned
                    FROM tbl_race_participants 
                    WHERE user_id = ?
                    AND finished_at >= ?
                    AND finished_at <= ?
                ");
                $timestampStmt->execute([
                    $discordId,
                    $currentSeasonStart,
                    $currentSeasonEnd
                ]);
                $timestampData = $timestampStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($timestampData && (int)$timestampData['total_races'] > 0) {
                    error_log("✅ DISCORD RACE: Timestamp fallback found " . $timestampData['total_races'] . " races for $discordId");
                    $raceData = $timestampData;
                } else {
                    error_log("ℹ️ DISCORD RACE: No Season $currentSeason data found for user $discordId (no timestamp matches either)");
                    $raceData = null;
                }
            } else {
                error_log("ℹ️ DISCORD RACE DEBUG: No Season $currentSeason data found for user $discordId (no timestamp fallback - missing dates)");
                $raceData = null;
            }
        }
        
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

    // 6. CHEESE RUMBLE STATS (using user_id from tbl_rumble_participants)
    try {
        error_log("🔍 CHEESE RUMBLE DEBUG: Querying for user $discordId");

        $rumbleData = null;

        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_rumbles,
                COUNT(CASE WHEN rp.status = 'winner' OR rp.final_position = 1 THEN 1 END) as wins,
                COUNT(CASE WHEN rp.final_position <= 3 AND rp.final_position IS NOT NULL THEN 1 END) as podiums,
                MIN(CASE WHEN rp.final_position IS NOT NULL THEN rp.final_position END) as best_position,
                SUM(COALESCE(rp.dspoinc_earned, 0)) as total_dspoinc_earned,
                MAX(COALESCE(
                    datetime(replace(replace(rp.updated_at, 'T', ' '), 'Z', '')),
                    datetime(rp.joined_at / 1000, 'unixepoch')
                )) as last_played
            FROM tbl_rumble_participants rp
            WHERE rp.user_id = ?
              AND COALESCE(
                    datetime(replace(replace(rp.updated_at, 'T', ' '), 'Z', '')),
                    datetime(rp.joined_at / 1000, 'unixepoch')
                  ) >= datetime(?)
              AND COALESCE(
                    datetime(replace(replace(rp.updated_at, 'T', ' '), 'Z', '')),
                    datetime(rp.joined_at / 1000, 'unixepoch')
                  ) < datetime(?)
        ");
        $stmt->execute([
            $discordId,
            $currentSeasonStart,
            $currentSeasonEnd
        ]);
        $rumbleData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rumbleData || (int)$rumbleData['total_rumbles'] === 0) {
            error_log("ℹ️ CHEESE RUMBLE: No Season $currentSeason data for $discordId, showing 0 (current season only)");
            $rumbleData = null;
        }

        if ($rumbleData && (int)$rumbleData['total_rumbles'] > 0) {
            $response['cheese_rumble']['total_rumbles'] = (int)$rumbleData['total_rumbles'];
            $response['cheese_rumble']['wins'] = (int)$rumbleData['wins'];
            $response['cheese_rumble']['podiums'] = (int)$rumbleData['podiums'];
            $response['cheese_rumble']['best_position'] = $rumbleData['best_position'] ? (int)$rumbleData['best_position'] : null;
            $response['cheese_rumble']['dspoinc_earned'] = (int)$rumbleData['total_dspoinc_earned'];
            $response['cheese_rumble']['last_played'] = $rumbleData['last_played'];
            error_log("✅ Cheese Rumble stats found for user $discordId: " . $rumbleData['total_rumbles'] . " rumbles");
        }
    } catch (Exception $e) {
        error_log("Cheese Rumble query error: " . $e->getMessage());
    }

    // 7. GLYPH MEMORY STATS (current season only)
    try {
        error_log("🔍 GLYPH MEMORY DEBUG: Querying current season stats for user $discordId");

        $glyphData = null;
        $glyphSeasonFilters = [
            ['condition' => 'season = ?', 'label' => 'exact'],
            ['condition' => 'season LIKE ? || "%"', 'label' => 'prefix']
        ];

        foreach ($glyphSeasonFilters as $filter) {
            $stmt = $db->prepare("
                SELECT
                    COUNT(*) as total_runs,
                    MIN(time_ms) as best_time_ms,
                    AVG(time_ms) as avg_time_ms,
                    MAX(pairs_matched) as best_pairs_matched,
                    MAX(timestamp) as last_played
                FROM tbl_glyph_memory_scores
                WHERE discord_id = ?
                AND (
                    " . $filter['condition'] . "
                    OR (timestamp >= ? AND (? IS NULL OR timestamp < ?))
                )
            ");
            $stmt->execute([
                $discordId,
                $currentSeason,
                $currentSeasonStart,
                $currentSeasonEnd,
                $currentSeasonEnd
            ]);
            $glyphData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($glyphData && (int)$glyphData['total_runs'] > 0) {
                error_log("✅ GLYPH MEMORY: season match (" . $filter['label'] . ") for $discordId");
                break;
            }
        }

        // Do not fallback to all-time for current season stats
        if (!$glyphData || (int)$glyphData['total_runs'] === 0) {
            error_log("⚠️ GLYPH MEMORY: No Season $currentSeason data for $discordId, showing 0 (current season only)");
            $glyphData = null;
        }

        if ($glyphData && (int)$glyphData['total_runs'] > 0) {
            $response['glyph_memory']['total_runs'] = (int)$glyphData['total_runs'];
            $response['glyph_memory']['best_time_ms'] = $glyphData['best_time_ms'] !== null ? (int)$glyphData['best_time_ms'] : null;
            $response['glyph_memory']['avg_time_ms'] = $glyphData['avg_time_ms'] !== null ? (float)$glyphData['avg_time_ms'] : null;
            $response['glyph_memory']['last_played'] = $glyphData['last_played'];
            $response['glyph_memory']['best_pairs_matched'] = $glyphData['best_pairs_matched'] !== null ? (int)$glyphData['best_pairs_matched'] : 0;

            // Keep DSPoinc at 0 unless you later add a dedicated Glyph reward source
            $response['glyph_memory']['dspoinc_earned'] = 0;

            error_log(
                "✅ GLYPH MEMORY stats found for user $discordId: " .
                $glyphData['total_runs'] . " runs, best " .
                ($glyphData['best_time_ms'] ?? 'null') . " ms"
            );
        }
    } catch (Exception $e) {
        error_log("Glyph Memory query error: " . $e->getMessage());
    }

    // Calculate total DSPOINC from all games (including Cheese Rumble)
    $response['overall']['total_dspoinc'] = 
        $response['tetris']['dspoinc_earned'] + 
        $response['snake']['dspoinc_earned'] + 
        $response['space_invaders']['dspoinc_earned'] + 
        $response['cheese_hunt']['dspoinc_earned'] + 
        $response['discord_race']['dspoinc_earned'] +
        $response['cheese_rumble']['dspoinc_earned'] +
        $response['glyph_memory']['dspoinc_earned'];

    // Count how many games the user has played (including Cheese Rumble - 6th game)
    $gamesPlayed = 0;
    if ($response['tetris']['total_games'] > 0) $gamesPlayed++;
    if ($response['snake']['total_games'] > 0) $gamesPlayed++;
    if ($response['space_invaders']['total_games'] > 0) $gamesPlayed++;
    if ($response['cheese_hunt']['total_clicks'] > 0) $gamesPlayed++;
    if ($response['discord_race']['total_races'] > 0) $gamesPlayed++;
    if ($response['cheese_rumble']['total_rumbles'] > 0) $gamesPlayed++; // 6th game
    if ($response['glyph_memory']['total_runs'] > 0) $gamesPlayed++;     // 7th game
    
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
    error_log("  Total Games Played: " . $response['overall']['games_played'] . "/8");
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

    // 🏆 TETRIS ACHIEVEMENTS - Add achievement data to Tetris game stats
    try {
        $tetrisAchievementStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_achievements,
                COUNT(CASE WHEN unlocked_at IS NOT NULL THEN 1 END) as unlocked_achievements
            FROM tbl_tetris_achievements 
            WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
        ");
        $tetrisAchievementStmt->execute();
        $tetrisAchievementData = $tetrisAchievementStmt->fetch(PDO::FETCH_ASSOC);
        
        $userTetrisAchievementStmt = $db->prepare("
            SELECT COUNT(*) as unlocked_count
            FROM tbl_tetris_achievements 
            WHERE user_id = ? AND unlocked_at IS NOT NULL
        ");
        $userTetrisAchievementStmt->execute([$discordId]);
        $userTetrisAchievementData = $userTetrisAchievementStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tetrisAchievementData && $userTetrisAchievementData) {
            $response['tetris']['achievements'] = [
                'total_available' => (int)$tetrisAchievementData['total_achievements'],
                'unlocked' => (int)$userTetrisAchievementData['unlocked_count'],
                'completion_percentage' => $tetrisAchievementData['total_achievements'] > 0 
                    ? round(($userTetrisAchievementData['unlocked_count'] / $tetrisAchievementData['total_achievements']) * 100, 1) 
                    : 0
            ];
            error_log("✅ Tetris achievements found for user $discordId: " . $userTetrisAchievementData['unlocked_count'] . "/" . $tetrisAchievementData['total_achievements']);
        } else {
            $response['tetris']['achievements'] = [
                'total_available' => 0,
                'unlocked' => 0,
                'completion_percentage' => 0
            ];
            error_log("❌ No Tetris achievements found for user $discordId");
        }
    } catch (Exception $e) {
        error_log("Tetris achievements query error: " . $e->getMessage());
        $response['tetris']['achievements'] = [
            'total_available' => 0,
            'unlocked' => 0,
            'completion_percentage' => 0
        ];
    }

    // 🏆 SNAKE ACHIEVEMENTS - Add achievement data to Snake game stats
    try {
        $snakeAchievementStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_achievements,
                COUNT(CASE WHEN unlocked_at IS NOT NULL THEN 1 END) as unlocked_achievements
            FROM tbl_snake_achievements 
            WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
        ");
        $snakeAchievementStmt->execute();
        $snakeAchievementData = $snakeAchievementStmt->fetch(PDO::FETCH_ASSOC);
        
        $userSnakeAchievementStmt = $db->prepare("
            SELECT COUNT(*) as unlocked_count
            FROM tbl_snake_achievements 
            WHERE user_id = ? AND unlocked_at IS NOT NULL
        ");
        $userSnakeAchievementStmt->execute([$discordId]);
        $userSnakeAchievementData = $userSnakeAchievementStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($snakeAchievementData && $userSnakeAchievementData) {
            $response['snake']['achievements'] = [
                'total_available' => (int)$snakeAchievementData['total_achievements'],
                'unlocked' => (int)$userSnakeAchievementData['unlocked_count'],
                'completion_percentage' => $snakeAchievementData['total_achievements'] > 0 
                    ? round(($userSnakeAchievementData['unlocked_count'] / $snakeAchievementData['total_achievements']) * 100, 1) 
                    : 0
            ];
            error_log("✅ Snake achievements found for user $discordId: " . $userSnakeAchievementData['unlocked_count'] . "/" . $snakeAchievementData['total_achievements']);
        } else {
            $response['snake']['achievements'] = [
                'total_available' => 0,
                'unlocked' => 0,
                'completion_percentage' => 0
            ];
            error_log("❌ No Snake achievements found for user $discordId");
        }
    } catch (Exception $e) {
        error_log("Snake achievements query error: " . $e->getMessage());
        $response['snake']['achievements'] = [
            'total_available' => 0,
            'unlocked' => 0,
            'completion_percentage' => 0
        ];
    }

    // 🏆 SPACE INVADERS ACHIEVEMENTS - Add achievement data to Space Invaders game stats
    try {
        // Space Invaders achievements don't have template definitions like Snake, so we count all unique achievement keys
        $spaceAchievementStmt = $db->prepare("
            SELECT COUNT(DISTINCT achievement_key) as total_achievements
            FROM tbl_space_invaders_achievements
            WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
        ");
        $spaceAchievementStmt->execute();
        $spaceAchievementData = $spaceAchievementStmt->fetch(PDO::FETCH_ASSOC);
        if ($spaceAchievementData && (int)$spaceAchievementData['total_achievements'] === 0) {
            // Fallback to known Season 5 total if definition rows are missing
            $spaceAchievementData['total_achievements'] = 28;
        }
        
        $userSpaceAchievementStmt = $db->prepare("
            SELECT COUNT(*) as unlocked_count
            FROM tbl_space_invaders_achievements 
            WHERE user_id = ? AND unlocked_at IS NOT NULL
        ");
        $userSpaceAchievementStmt->execute([$discordId]);
        $userSpaceAchievementData = $userSpaceAchievementStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($spaceAchievementData && $userSpaceAchievementData) {
            $response['space_invaders']['achievements'] = [
                'total_available' => (int)$spaceAchievementData['total_achievements'],
                'unlocked' => (int)$userSpaceAchievementData['unlocked_count'],
                'completion_percentage' => $spaceAchievementData['total_achievements'] > 0 
                    ? round(($userSpaceAchievementData['unlocked_count'] / $spaceAchievementData['total_achievements']) * 100, 1) 
                    : 0
            ];
            error_log("✅ Space Invaders achievements found for user $discordId: " . $userSpaceAchievementData['unlocked_count'] . "/" . $spaceAchievementData['total_achievements']);
        } else {
            $response['space_invaders']['achievements'] = [
                'total_available' => 0,
                'unlocked' => 0,
                'completion_percentage' => 0
            ];
            error_log("❌ No Space Invaders achievements found for user $discordId");
        }
    } catch (Exception $e) {
        error_log("Space Invaders achievements query error: " . $e->getMessage());
        $response['space_invaders']['achievements'] = [
            'total_available' => 0,
            'unlocked' => 0,
            'completion_percentage' => 0
        ];
    }

    // Return the response in the format expected by the frontend
echo json_encode([
    'success' => true,
    // 👇 NEW: expose season info to the frontend
    'current_season' => $currentSeason,
    'season_start'   => $currentSeasonStart,
    'season_end'     => $currentSeasonEnd,

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
                ],
                'achievements' => $response['tetris']['achievements'] ?? [
                    'total_available' => 0,
                    'unlocked' => 0,
                    'completion_percentage' => 0
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
                ],
                'achievements' => $response['snake']['achievements'] ?? [
                    'total_available' => 0,
                    'unlocked' => 0,
                    'completion_percentage' => 0
                ]
            ],
            'space_invaders' => [
                'name' => 'Space Cheese Invaders',
                'icon' => '👾',
                'url' => '/space-cheese-invaders.html',
                'status' => $response['space_invaders']['total_games'] > 0 ? 'active' : 'not_played',
                'stats' => [
                    'total_games' => $response['space_invaders']['total_games'],
                    'best_score' => $response['space_invaders']['best_score'],
                    'total_score' => $response['space_invaders']['total_score'],
                    'dspoinc_earned' => $response['space_invaders']['dspoinc_earned'],
                    'last_played' => $response['space_invaders']['last_played']
                ],
                'achievements' => $response['space_invaders']['achievements'] ?? [
                    'total_available' => 0,
                    'unlocked' => 0,
                    'completion_percentage' => 0
                ]
            ],
                        'cheeseman' => [
    'name' => 'Cheese Runner',
    'icon' => '🧀',
    'url' => '/cheeseman.html',
    'status' => ($response['cheeseman']['total_games'] ?? 0) > 0 ? 'active' : 'not_played',
    'stats' => [
        'total_games' => $response['cheeseman']['total_games'] ?? 0,
        'best_score' => $response['cheeseman']['best_score'] ?? 0,
        'total_score' => $response['cheeseman']['total_score'] ?? 0,
        'dspoinc_earned' => $response['cheeseman']['dspoinc_earned'] ?? 0,
        'last_played' => $response['cheeseman']['last_played'] ?? null
    ],
    'achievements' => [
        'total_available' => 0,
        'unlocked' => 0,
        'completion_percentage' => 0
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
                    'best_position' => $response['discord_race']['best_position'] !== null ? $response['discord_race']['best_position'] : 'N/A',
                    'dspoinc_earned' => $response['discord_race']['dspoinc_earned']
                ]
            ],
            'cheese_rumble' => [
                'name' => 'Cheese Rumble',
                'icon' => '💥',
                'url' => 'https://discord.com/invite/PFFztgqwe2',
                'status' => $response['cheese_rumble']['total_rumbles'] > 0 ? 'active' : 'not_played',
                'stats' => [
                    'total_rumbles' => $response['cheese_rumble']['total_rumbles'],
                    'wins' => $response['cheese_rumble']['wins'],
                    'podium_finishes' => $response['cheese_rumble']['podiums'],
                    'best_position' => $response['cheese_rumble']['best_position'] !== null ? $response['cheese_rumble']['best_position'] : 'N/A',
                    'dspoinc_earned' => $response['cheese_rumble']['dspoinc_earned'],
                    'last_played' => $response['cheese_rumble']['last_played']
                ]
            ],
'glyph_memory' => [
    'name' => 'Glyph Memory',
    'icon' => '🔮',
    'url' => '/glyph/glyph.html',
    'status' => $response['glyph_memory']['total_runs'] > 0 ? 'active' : 'not_played',
    'stats' => [
        'total_runs' => $response['glyph_memory']['total_runs'],
        'best_time_ms' => $response['glyph_memory']['best_time_ms'],
        'avg_time_ms' => $response['glyph_memory']['avg_time_ms'],
        'best_pairs_matched' => $response['glyph_memory']['best_pairs_matched'],
        'dspoinc_earned' => $response['glyph_memory']['dspoinc_earned'],
        'last_played' => $response['glyph_memory']['last_played']
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