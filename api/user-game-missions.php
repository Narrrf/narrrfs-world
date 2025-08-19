<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing user_id parameter']);
    exit;
}

$userId = trim($input['user_id']);

try {
    // Use centralized database configuration
    require_once __DIR__ . '/config/database.php';
    $pdo = getDatabaseConnection();

    // Get user basic info
    $stmt = $pdo->prepare("SELECT discord_id, username, created_at FROM tbl_users WHERE discord_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'error' => 'User not found',
            'suggestion' => 'Please run /sync-users command in Discord'
        ]);
        exit;
    }

    // 1. TETRIS STATS
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_games,
            MAX(score) as best_score,
            AVG(score) as average_score,
            SUM(score) as total_score,
            MAX(lines_cleared) as best_lines,
            AVG(lines_cleared) as average_lines,
            SUM(lines_cleared) as total_lines,
            MAX(level_reached) as highest_level,
            MAX(created_at) as last_played
        FROM tbl_tetris_scores 
        WHERE discord_id = ? AND is_current_season = 1
    ");
    $stmt->execute([$userId]);
    $tetris_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. SNAKE STATS (from user_scores table)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_games,
            MAX(score) as best_score,
            AVG(score) as average_score,
            SUM(score) as total_score,
            MAX(timestamp) as last_played
        FROM tbl_user_scores 
        WHERE user_id = ? AND game_type = 'snake'
    ");
    $stmt->execute([$userId]);
    $snake_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. SPACE INVADERS STATS
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_games,
            MAX(score) as best_score,
            AVG(score) as average_score,
            SUM(score) as total_score,
            MAX(timestamp) as last_played
        FROM tbl_user_scores 
        WHERE user_id = ? AND game_type = 'space_invaders'
    ");
    $stmt->execute([$userId]);
    $space_invaders_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. CHEESE HUNT STATS
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_clicks,
            COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
            COUNT(CASE WHEN quest_id IS NULL THEN 1 END) as general_clicks,
            COUNT(DISTINCT egg_id) as unique_eggs_clicked,
            COUNT(DISTINCT quest_id) as quests_participated,
            MAX(timestamp) as last_click
        FROM tbl_cheese_clicks 
        WHERE user_wallet = ?
    ");
    $stmt->execute([$userId]);
    $cheese_hunt_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. DISCORD CHEESE RACE STATS
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_races,
            COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
            COUNT(CASE WHEN position <= 3 THEN 1 END) as podium_finishes,
            AVG(position) as average_position,
            MIN(position) as best_position,
            MAX(event_date) as last_race
        FROM tbl_discord_events 
        WHERE user_id = ? AND event_type = 'cheese_race'
    ");
    $stmt->execute([$userId]);
    $discord_race_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate overall DSPOINC from all games
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0) as total_dspoinc 
        FROM tbl_user_scores 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $total_dspoinc = $stmt->fetch(PDO::FETCH_ASSOC)['total_dspoinc'];

    // Get quest completion stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_claims,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_claims,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_claims,
            MAX(claimed_at) as last_quest_claim
        FROM tbl_quest_claims 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $quest_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'user_info' => $user,
        'total_dspoinc' => (int)$total_dspoinc,
        'games' => [
            'tetris' => [
                'name' => 'Tetris Scroll',
                'icon' => '🧩',
                'url' => '/profile.html#cheese-tetris',
                'total_games' => (int)$tetris_stats['total_games'],
                'best_score' => (int)$tetris_stats['best_score'],
                'total_score' => (int)$tetris_stats['total_score'],
                'best_lines' => (int)$tetris_stats['best_lines'],
                'highest_level' => (int)$tetris_stats['highest_level'],
                'last_played' => $tetris_stats['last_played'],
                'status' => $tetris_stats['total_games'] > 0 ? 'active' : 'not_played'
            ],
            'snake' => [
                'name' => 'Snake Scroll',
                'icon' => '🐍',
                'url' => '/profile.html#cheese-snake',
                'total_games' => (int)$snake_stats['total_games'],
                'best_score' => (int)$snake_stats['best_score'],
                'total_score' => (int)$snake_stats['total_score'],
                'last_played' => $snake_stats['last_played'],
                'status' => $snake_stats['total_games'] > 0 ? 'active' : 'not_played'
            ],
            'space_invaders' => [
                'name' => 'Space Cheese Invaders',
                'icon' => '👾',
                'url' => '/profile.html#cheese-space-invaders',
                'total_games' => (int)$space_invaders_stats['total_games'],
                'best_score' => (int)$space_invaders_stats['best_score'],
                'total_score' => (int)$space_invaders_stats['total_score'],
                'last_played' => $space_invaders_stats['last_played'],
                'status' => $space_invaders_stats['total_games'] > 0 ? 'active' : 'not_played'
            ],
            'cheese_hunt' => [
                'name' => 'Cheese Hunt',
                'icon' => '🧀',
                'url' => '/',
                'total_clicks' => (int)$cheese_hunt_stats['total_clicks'],
                'quest_clicks' => (int)$cheese_hunt_stats['quest_clicks'],
                'unique_eggs' => (int)$cheese_hunt_stats['unique_eggs_clicked'],
                'quests_participated' => (int)$cheese_hunt_stats['quests_participated'],
                'last_click' => $cheese_hunt_stats['last_click'],
                'status' => $cheese_hunt_stats['total_clicks'] > 0 ? 'active' : 'not_played'
            ],
            'discord_race' => [
                'name' => 'Discord Cheese Race',
                'icon' => '🏁',
                'url' => 'https://discord.gg/narrrfs',
                'total_races' => (int)$discord_race_stats['total_races'],
                'wins' => (int)$discord_race_stats['wins'],
                'podium_finishes' => (int)$discord_race_stats['podium_finishes'],
                'best_position' => (int)$discord_race_stats['best_position'] ?: 'N/A',
                'average_position' => round((float)$discord_race_stats['average_position'], 1) ?: 'N/A',
                'last_race' => $discord_race_stats['last_race'],
                'status' => $discord_race_stats['total_races'] > 0 ? 'active' : 'not_played'
            ]
        ],
        'quest_stats' => [
            'total_claims' => (int)$quest_stats['total_claims'],
            'approved_claims' => (int)$quest_stats['approved_claims'],
            'pending_claims' => (int)$quest_stats['pending_claims'],
            'last_quest_claim' => $quest_stats['last_quest_claim']
        ],
        'achievements' => [
            'games_played' => array_sum([
                $tetris_stats['total_games'] > 0 ? 1 : 0,
                $snake_stats['total_games'] > 0 ? 1 : 0,
                $space_invaders_stats['total_games'] > 0 ? 1 : 0,
                $cheese_hunt_stats['total_clicks'] > 0 ? 1 : 0,
                $discord_race_stats['total_races'] > 0 ? 1 : 0
            ]),
            'total_games_played' => (int)$tetris_stats['total_games'] + (int)$snake_stats['total_games'] + (int)$space_invaders_stats['total_games'],
            'cheese_hunter_level' => $cheese_hunt_stats['total_clicks'] >= 100 ? 'Expert' : ($cheese_hunt_stats['total_clicks'] >= 50 ? 'Advanced' : ($cheese_hunt_stats['total_clicks'] >= 10 ? 'Intermediate' : 'Beginner'))
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
