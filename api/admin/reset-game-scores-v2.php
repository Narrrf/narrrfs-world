<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Database path - use production path for Render
$dbPath = '/var/www/html/db/narrrf_world.sqlite';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
}

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }

    // Check if admin authentication is provided
    if (!isset($input['admin_username']) || !isset($input['admin_password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Admin authentication required']);
        exit;
    }

    // Verify admin credentials
    $admin_username = $input['admin_username'];
    $admin_password = $input['admin_password'];

    // Simple admin verification (you can enhance this with database lookup)
    $valid_admins = [
        'narrrf' => 'PnoRakesucks&2025', // Use actual password from auth.php
        // Add other admin credentials as needed
    ];

    if (!isset($valid_admins[$admin_username]) || $valid_admins[$admin_username] !== $admin_password) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid admin credentials']);
        exit;
    }

    // Get reset parameters
    $game_type = $input['game_type'] ?? 'all'; // 'tetris', 'snake', or 'all'
    $reset_type = $input['reset_type'] ?? 'season'; // 'season', 'user', 'top_scores'
    $user_id = $input['user_id'] ?? null; // Required if reset_type is 'user'
    $top_count = $input['top_count'] ?? 10; // Number of top scores to reset if reset_type is 'top_scores'
    $season_name = $input['season_name'] ?? null; // Custom season name

    // Validate game type
    if (!in_array($game_type, ['tetris', 'snake', 'all'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid game type. Must be tetris, snake, or all']);
        exit;
    }

    // Validate reset type
    if (!in_array($reset_type, ['season', 'user', 'top_scores'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid reset type. Must be season, user, or top_scores']);
        exit;
    }

    // Check if user_id is provided when reset_type is 'user'
    if ($reset_type === 'user' && !$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User ID is required when reset type is user']);
        exit;
    }

    // Get current season info
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTR(season, 8) AS INTEGER)) as max_season FROM tbl_tetris_scores WHERE season LIKE 'season_%'");
    $stmt->execute();
    $current_season_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_season = $current_season_result['max_season'] ?? 1;
    $new_season = $current_season + 1;
    $new_season_name = $season_name ?: "season_$new_season";

    // Get current statistics before reset
    $stats_before = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ?");
        $stmt->execute(["season_$current_season"]);
        $stats_before['tetris'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'snake' AND season = ?");
        $stmt->execute(["season_$current_season"]);
        $stats_before['snake'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Mark top performers from current season before resetting
    $top_performers = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("
            SELECT discord_id, discord_name, score, game 
            FROM tbl_tetris_scores 
            WHERE game = 'tetris' AND season = ? 
            ORDER BY score DESC 
            LIMIT 2
        ");
        $stmt->execute(["season_$current_season"]);
        $top_performers['tetris'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("
            SELECT discord_id, discord_name, score, game 
            FROM tbl_tetris_scores 
            WHERE game = 'snake' AND season = ? 
            ORDER BY score DESC 
            LIMIT 2
        ");
        $stmt->execute(["season_$current_season"]);
        $top_performers['snake'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update top performers in current season
    foreach ($top_performers as $game => $performers) {
        foreach ($performers as $performer) {
            $stmt = $pdo->prepare("
                UPDATE tbl_tetris_scores 
                SET is_top_performer = 1 
                WHERE discord_id = ? AND game = ? AND season = ?
            ");
            $stmt->execute([$performer['discord_id'], $performer['game'], "season_$current_season"]);
        }
    }

    // Set season end date for current season
    $stmt = $pdo->prepare("
        UPDATE tbl_tetris_scores 
        SET season_end_date = CURRENT_TIMESTAMP 
        WHERE season = ? AND season_end_date IS NULL
    ");
    $stmt->execute(["season_$current_season"]);

    // Perform the reset based on type
    $affected_count = 0;

    if ($reset_type === 'season') {
        // For season reset, we don't delete data - we just start a new season
        // The current season data remains as historical data
        $affected_count = 0; // No records deleted, just preserved
    } elseif ($reset_type === 'user') {
        // Delete specific user's scores from current season
        $where_conditions = ["discord_id = ?", "season = ?"];
        $params = [$user_id, "season_$current_season"];
        
        if ($game_type !== 'all') {
            $where_conditions[] = "game = ?";
            $params[] = $game_type;
        }

        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        $sql = "DELETE FROM tbl_tetris_scores $where_clause";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $affected_count = $stmt->rowCount();
    } elseif ($reset_type === 'top_scores') {
        // Delete top scores from current season
        $game_conditions = $game_type === 'all' ? "" : "AND game = '$game_type'";
        
        if ($game_type === 'all') {
            // Get top scores for both games
            $stmt = $pdo->prepare("
                SELECT id FROM tbl_tetris_scores 
                WHERE id IN (
                    SELECT id FROM (
                        SELECT id, ROW_NUMBER() OVER (PARTITION BY game ORDER BY score DESC) as rn
                        FROM tbl_tetris_scores
                        WHERE season = ?
                    ) ranked
                    WHERE rn <= ?
                )
            ");
            $stmt->execute(["season_$current_season", $top_count]);
        } else {
            // Get top scores for specific game
            $stmt = $pdo->prepare("
                SELECT id FROM tbl_tetris_scores 
                WHERE game = ? AND season = ?
                ORDER BY score DESC 
                LIMIT ?
            ");
            $stmt->execute([$game_type, "season_$current_season", $top_count]);
        }
        
        $top_score_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($top_score_ids)) {
            $placeholders = str_repeat('?,', count($top_score_ids) - 1) . '?';
            $sql = "DELETE FROM tbl_tetris_scores WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($top_score_ids);
            $affected_count = $stmt->rowCount();
        }
    }

    // Log the reset action
    $log_entry = date('Y-m-d H:i:s') . " - Game scores reset by admin: $admin_username\n";
    $log_entry .= "  Game Type: $game_type\n";
    $log_entry .= "  Reset Type: $reset_type\n";
    $log_entry .= "  User ID: " . ($user_id ?? 'N/A') . "\n";
    $log_entry .= "  Top Count: " . ($top_count ?? 'N/A') . "\n";
    $log_entry .= "  Current Season: season_$current_season\n";
    $log_entry .= "  New Season: $new_season_name\n";
    $log_entry .= "  Records Affected: $affected_count\n";
    $log_entry .= "  Top Performers: " . json_encode($top_performers) . "\n";
    $log_entry .= "  Stats Before Reset: " . json_encode($stats_before) . "\n\n";
    
    file_put_contents('../../logs/game-score-resets.log', $log_entry, FILE_APPEND | LOCK_EX);

    // Get updated statistics
    $stats_after = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ?");
        $stmt->execute(["season_$current_season"]);
        $stats_after['tetris'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'snake' AND season = ?");
        $stmt->execute(["season_$current_season"]);
        $stats_after['snake'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'message' => "Successfully reset game scores for new season",
        'details' => [
            'game_type' => $game_type,
            'reset_type' => $reset_type,
            'user_id' => $user_id,
            'top_count' => $top_count,
            'current_season' => "season_$current_season",
            'new_season' => $new_season_name,
            'records_affected' => $affected_count,
            'top_performers' => $top_performers,
            'stats_before' => $stats_before,
            'stats_after' => $stats_after
        ],
        'reset_at' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to reset game scores: ' . $e->getMessage()
    ]);
}
?> 