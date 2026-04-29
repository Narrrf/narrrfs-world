<?php
/**
 * All-Time Statistics API
 * Returns aggregate statistics across ALL seasons for a user
 * For profile page overview section
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../db/narrrf_world.sqlite' 
        : '/var/www/html/db/narrrf_world.sqlite';
    
    if (!file_exists($dbPath)) {
        return null;
    }
    
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Get database connection
$db = getSQLite3Connection();

if (!$db) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

// Get user_id from request
$user_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? $input['discord_id'] ?? null;
} else {
    $user_id = $_GET['user_id'] ?? $_GET['discord_id'] ?? null;
}

if (!$user_id) {
    echo json_encode([
        'success' => false,
        'error' => 'User ID required'
    ]);
    exit;
}

try {
    // Initialize response
    $response = [
        'success' => true,
        'user_id' => $user_id,
        'all_time_stats' => [
            'total_games_played' => 0,
            'total_dspoinc_earned' => 0,
            'games' => []
        ]
    ];
    
    // 1. TETRIS - All-time stats (current + historical combined)
    $tetrisStmt = $db->prepare("
        SELECT 
            SUM(games) as total_games,
            MAX(best) as best_score,
            SUM(total) as total_score
        FROM (
            -- Current season data
            SELECT 
                COUNT(*) as games,
                MAX(score) as best,
                SUM(score) as total
            FROM tbl_tetris_scores 
            WHERE discord_id = :user_id AND game = 'tetris'
            
            UNION ALL
            
            -- Historical season data
            SELECT 
                SUM(total_games) as games,
                MAX(best_score) as best,
                SUM(total_score) as total
            FROM tbl_historical_stats
            WHERE discord_id = :user_id2 AND game = 'tetris'
        )
    ");
    $tetrisStmt->execute([':user_id' => $user_id, ':user_id2' => $user_id]);
    $tetris = $tetrisStmt->fetch(PDO::FETCH_ASSOC);
    
    $totalGames = (int)($tetris['total_games'] ?? 0);
    $totalScore = (int)($tetris['total_score'] ?? 0);
    $avgScore = $totalGames > 0 ? round($totalScore / $totalGames, 2) : 0;
    
    $response['all_time_stats']['games']['tetris'] = [
        'name' => 'Tetris',
        'icon' => '🧩',
        'total_games' => $totalGames,
        'best_score' => (int)($tetris['best_score'] ?? 0),
        'total_score' => $totalScore,
        'avg_score' => $avgScore
    ];
    
    // 2. SNAKE - All-time stats (current + historical combined)
    $snakeStmt = $db->prepare("
        SELECT 
            SUM(games) as total_games,
            MAX(best) as best_score,
            SUM(total) as total_score
        FROM (
            -- Current season data
            SELECT 
                COUNT(*) as games,
                MAX(score) as best,
                SUM(score) as total
            FROM tbl_tetris_scores 
            WHERE discord_id = :user_id AND game = 'snake'
            
            UNION ALL
            
            -- Historical season data
            SELECT 
                SUM(total_games) as games,
                MAX(best_score) as best,
                SUM(total_score) as total
            FROM tbl_historical_stats
            WHERE discord_id = :user_id2 AND game = 'snake'
        )
    ");
    $snakeStmt->execute([':user_id' => $user_id, ':user_id2' => $user_id]);
    $snake = $snakeStmt->fetch(PDO::FETCH_ASSOC);
    
    $totalGames = (int)($snake['total_games'] ?? 0);
    $totalScore = (int)($snake['total_score'] ?? 0);
    $avgScore = $totalGames > 0 ? round($totalScore / $totalGames, 2) : 0;
    
    $response['all_time_stats']['games']['snake'] = [
        'name' => 'Snake',
        'icon' => '🐍',
        'total_games' => $totalGames,
        'best_score' => (int)($snake['best_score'] ?? 0),
        'total_score' => $totalScore,
        'avg_score' => $avgScore
    ];
    
    // 3. SPACE INVADERS - All-time stats (current + historical combined)
    $spaceStmt = $db->prepare("
        SELECT 
            SUM(games) as total_games,
            MAX(best) as best_score,
            SUM(total) as total_score
        FROM (
            -- Current season data
            SELECT 
                COUNT(*) as games,
                MAX(score) as best,
                SUM(score) as total
            FROM tbl_tetris_scores 
            WHERE discord_id = :user_id AND game = 'space_invaders'
            
            UNION ALL
            
            -- Historical season data
            SELECT 
                SUM(total_games) as games,
                MAX(best_score) as best,
                SUM(total_score) as total
            FROM tbl_historical_stats
            WHERE discord_id = :user_id2 AND game = 'space_invaders'
        )
    ");
    $spaceStmt->execute([':user_id' => $user_id, ':user_id2' => $user_id]);
    $space = $spaceStmt->fetch(PDO::FETCH_ASSOC);
    
    $totalGames = (int)($space['total_games'] ?? 0);
    $totalScore = (int)($space['total_score'] ?? 0);
    $avgScore = $totalGames > 0 ? round($totalScore / $totalGames, 2) : 0;
    
    $response['all_time_stats']['games']['space_invaders'] = [
        'name' => 'Space Invaders',
        'icon' => '👾',
        'total_games' => $totalGames,
        'best_score' => (int)($space['best_score'] ?? 0),
        'total_score' => $totalScore,
        'avg_score' => $avgScore
    ];
    
    // 4. CHEESE HUNT - All-time stats (current + historical combined)
    // Note: Cheese Hunt data is NEVER deleted (preserved across seasons)
    // But we still check historical table for any manually archived data
    $cheeseStmt = $db->prepare("
        SELECT 
            SUM(clicks) as total_clicks,
            SUM(quest) as total_quest_clicks,
            SUM(days) as days_played
        FROM (
            -- Current/All data from tbl_cheese_clicks (never deleted)
            SELECT 
                COUNT(*) as clicks,
                COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest,
                COUNT(DISTINCT DATE(timestamp)) as days
            FROM tbl_cheese_clicks 
            WHERE user_wallet = :user_id
            
            UNION ALL
            
            -- Historical archived data (if any)
            SELECT 
                SUM(total_clicks) as clicks,
                SUM(quest_clicks) as quest,
                SUM(days_played) as days
            FROM tbl_historical_cheese_stats
            WHERE user_wallet = :user_id2
        )
    ");
    $cheeseStmt->execute([':user_id' => $user_id, ':user_id2' => $user_id]);
    $cheese = $cheeseStmt->fetch(PDO::FETCH_ASSOC);
    
    $response['all_time_stats']['games']['cheese_hunt'] = [
        'name' => 'Cheese Hunt',
        'icon' => '🧀',
        'total_clicks' => (int)($cheese['total_clicks'] ?? 0),
        'total_quest_clicks' => (int)($cheese['total_quest_clicks'] ?? 0),
        'days_played' => (int)($cheese['days_played'] ?? 0)
    ];
    
    // 5. DISCORD RACE - All-time stats (uses user_id field)
    $raceStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_races,
            COUNT(CASE WHEN position = 1 THEN 1 END) as total_wins,
            COUNT(CASE WHEN position <= 3 THEN 1 END) as podium_finishes,
            MIN(position) as best_position
        FROM tbl_race_participants 
        WHERE user_id = :user_id
    ");
    $raceStmt->execute([':user_id' => $user_id]);
    $race = $raceStmt->fetch(PDO::FETCH_ASSOC);
    
    $response['all_time_stats']['games']['discord_race'] = [
        'name' => 'Discord Race',
        'icon' => '🏁',
        'total_races' => (int)($race['total_races'] ?? 0),
        'total_wins' => (int)($race['total_wins'] ?? 0),
        'podium_finishes' => (int)($race['podium_finishes'] ?? 0),
        'best_position' => $race['best_position'] !== null ? (int)$race['best_position'] : null
    ];
    
// 6. CHEESE RUMBLE - All-time stats
$rumbleStmt = $db->prepare("
    SELECT
        COUNT(DISTINCT rp.rumble_id) as total_rumbles,
        COUNT(CASE WHEN rp.status = 'winner' OR rp.final_position = 1 THEN 1 END) as total_wins,
        COUNT(CASE WHEN rp.final_position <= 3 AND rp.final_position IS NOT NULL THEN 1 END) as podium_finishes,
        MIN(rp.final_position) as best_position
    FROM tbl_rumble_participants rp
    LEFT JOIN tbl_cheese_rumbles cr
        ON cr.rumble_id = rp.rumble_id
    WHERE rp.user_id = :user_id
");
$rumbleStmt->execute([':user_id' => $user_id]);
$rumble = $rumbleStmt->fetch(PDO::FETCH_ASSOC);

$response['all_time_stats']['games']['cheese_rumble'] = [
    'name' => 'Cheese Rumble',
    'icon' => '💥',
    'total_rumbles' => (int)($rumble['total_rumbles'] ?? 0),
    'total_wins' => (int)($rumble['total_wins'] ?? 0),
    'podium_finishes' => (int)($rumble['podium_finishes'] ?? 0),
    'best_position' => $rumble['best_position'] !== null ? (int)$rumble['best_position'] : null
];
	
	// 7. GLYPH MEMORY - All-time stats
$glyphStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_runs,
        MIN(time_ms) as best_time_ms,
        AVG(time_ms) as avg_time_ms,
        MAX(timestamp) as last_played
    FROM tbl_glyph_memory_scores
    WHERE discord_id = :user_id
");
$glyphStmt->execute([':user_id' => $user_id]);
$glyph = $glyphStmt->fetch(PDO::FETCH_ASSOC);

$response['all_time_stats']['games']['glyph_memory'] = [
    'name' => 'Glyph Memory',
    'icon' => '🔮',
    'total_runs' => (int)($glyph['total_runs'] ?? 0),
    'best_time_ms' => isset($glyph['best_time_ms']) ? (int)$glyph['best_time_ms'] : null,
    'avg_time_ms' => isset($glyph['avg_time_ms']) ? (int)round($glyph['avg_time_ms']) : null,
    'last_played' => $glyph['last_played'] ?? null
];


// 8. CHEESE RUNNER / CHEESEMAN - All-time stats
$cheesemanStmt = $db->prepare("
    SELECT
        COUNT(*) as total_games,
        MAX(score) as best_score,
        SUM(score) as total_score,
        AVG(score) as avg_score,
        MAX(timestamp) as last_played
    FROM tbl_tetris_scores
    WHERE discord_id = :user_id
    AND game = 'cheeseman'
");
$cheesemanStmt->execute([':user_id' => $user_id]);
$cheesemanData = $cheesemanStmt->fetch(PDO::FETCH_ASSOC);

$totalGames = (int)($cheesemanData['total_games'] ?? 0);
$totalScore = (int)($cheesemanData['total_score'] ?? 0);
$avgScore = $totalGames > 0 ? round($totalScore / $totalGames, 2) : 0;

$response['all_time_stats']['games']['cheeseman'] = [
    'name' => 'Cheese Runner',
    'icon' => '🧀',
    'total_games' => $totalGames,
    'best_score' => (int)($cheesemanData['best_score'] ?? 0),
    'total_score' => $totalScore,
    'avg_score' => $avgScore,
    'last_played' => $cheesemanData['last_played'] ?? null
];

    
    // Calculate total games played across all games (including Cheese Rumble - 6th game)
$response['all_time_stats']['total_games_played'] =
    $response['all_time_stats']['games']['tetris']['total_games'] +
    $response['all_time_stats']['games']['snake']['total_games'] +
    $response['all_time_stats']['games']['space_invaders']['total_games'] +
    $response['all_time_stats']['games']['cheeseman']['total_games'] +
    $response['all_time_stats']['games']['cheese_hunt']['total_clicks'] +
    $response['all_time_stats']['games']['discord_race']['total_races'] +
    $response['all_time_stats']['games']['cheese_rumble']['total_rumbles'] +
    $response['all_time_stats']['games']['glyph_memory']['total_runs'];

    
    // Calculate total DSPOINC earned (from tbl_tetris_scores only)
   $response['all_time_stats']['total_dspoinc_earned'] =
    $response['all_time_stats']['games']['tetris']['total_score'] +
    $response['all_time_stats']['games']['snake']['total_score'] +
    $response['all_time_stats']['games']['space_invaders']['total_score'] +
    $response['all_time_stats']['games']['cheeseman']['total_score'];
    
    // Achievement counts
    $achievementsStmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM tbl_tetris_achievements WHERE user_id = :user_id) as tetris_achievements,
            (SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = :user_id) as snake_achievements,
            (SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id = :user_id) as space_achievements
    ");
    $achievementsStmt->execute([':user_id' => $user_id]);
    $achievements = $achievementsStmt->fetch(PDO::FETCH_ASSOC);
    
    $response['all_time_stats']['total_achievements'] = 
        (int)($achievements['tetris_achievements'] ?? 0) +
        (int)($achievements['snake_achievements'] ?? 0) +
        (int)($achievements['space_achievements'] ?? 0);
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch all-time stats: ' . $e->getMessage()
    ]);
}
?>

