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

try {
    // Database path - use production path for Render
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
    if (!file_exists($dbPath)) {
        $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    }

    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }

    // Simple admin check
    if (!isset($input['admin_username']) || $input['admin_username'] !== 'narrrf') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid admin username']);
        exit;
    }

    // Get reset parameters
    $game_type = $input['game_type'] ?? 'all';
    $reset_type = $input['reset_type'] ?? 'season';

    // Get current season info
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTR(season, 8) AS INTEGER)) as max_season FROM tbl_tetris_scores WHERE season LIKE 'season_%'");
    $stmt->execute();
    $current_season_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_season = $current_season_result['max_season'] ?? 1;
    $new_season = $current_season + 1;

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

    // CRITICAL FIX: Clear current season data for public display
    // This moves current season data to historical status so new season starts fresh
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("
            UPDATE tbl_tetris_scores 
            SET season = ?, is_current_season = 0 
            WHERE game = 'tetris' AND season = ? AND is_current_season = 1
        ");
        $stmt->execute(["season_${current_season}_historical", "season_$current_season"]);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("
            UPDATE tbl_tetris_scores 
            SET season = ?, is_current_season = 0 
            WHERE game = 'snake' AND season = ? AND is_current_season = 1
        ");
        $stmt->execute(["season_${current_season}_historical", "season_$current_season"]);
    }

    // Get count of affected records
    $affected_count = 0;
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ?");
        $stmt->execute(["season_${current_season}_historical"]);
        $affected_count += $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tbl_tetris_scores WHERE game = 'snake' AND season = ?");
        $stmt->execute(["season_${current_season}_historical"]);
        $affected_count += $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => "Successfully reset game scores for new season",
        'details' => [
            'game_type' => $game_type,
            'reset_type' => $reset_type,
            'current_season' => "season_$current_season",
            'new_season' => "season_$new_season",
            'records_affected' => $affected_count,
            'top_performers' => $top_performers,
            'historical_season' => "season_${current_season}_historical"
        ],
        'reset_at' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Reset game scores v2 error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 