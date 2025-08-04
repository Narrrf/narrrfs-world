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

// Database path
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

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

    // Include admin authentication
    require_once 'auth.php';

    // Verify admin credentials
    $admin_username = $input['admin_username'];
    $admin_password = $input['admin_password'];

    // Simple admin verification (you can enhance this with database lookup)
    $valid_admins = [
        'narrrf' => 'your_secure_password_here', // Replace with actual password
        // Add other admin credentials as needed
    ];

    if (!isset($valid_admins[$admin_username]) || $valid_admins[$admin_username] !== $admin_password) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid admin credentials']);
        exit;
    }

    // Get reset parameters
    $game_type = $input['game_type'] ?? 'all'; // 'tetris', 'snake', or 'all'
    $reset_type = $input['reset_type'] ?? 'all'; // 'all', 'user', 'top_scores'
    $user_id = $input['user_id'] ?? null; // Required if reset_type is 'user'
    $top_count = $input['top_count'] ?? 10; // Number of top scores to reset if reset_type is 'top_scores'

    // Validate game type
    if (!in_array($game_type, ['tetris', 'snake', 'all'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid game type. Must be tetris, snake, or all']);
        exit;
    }

    // Validate reset type
    if (!in_array($reset_type, ['all', 'user', 'top_scores'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid reset type. Must be all, user, or top_scores']);
        exit;
    }

    // Check if user_id is provided when reset_type is 'user'
    if ($reset_type === 'user' && !$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User ID is required when reset type is user']);
        exit;
    }

    // Get current statistics before reset
    $stats_before = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'tetris'");
        $stmt->execute();
        $stats_before['tetris'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'snake'");
        $stmt->execute();
        $stats_before['snake'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Perform the reset based on type
    $deleted_count = 0;
    $where_conditions = [];
    $params = [];

    // Build WHERE clause based on game type
    if ($game_type !== 'all') {
        $where_conditions[] = "game = ?";
        $params[] = $game_type;
    }

    // Build WHERE clause based on reset type
    if ($reset_type === 'user') {
        $where_conditions[] = "discord_id = ?";
        $params[] = $user_id;
    } elseif ($reset_type === 'top_scores') {
        // For top scores, we need to identify the top scores first
        $game_conditions = $game_type === 'all' ? "" : "AND game = '$game_type'";
        
        if ($game_type === 'all') {
            // Get top scores for both games
            $stmt = $pdo->prepare("
                SELECT id FROM tbl_tetris_scores 
                WHERE id IN (
                    SELECT id FROM (
                        SELECT id, ROW_NUMBER() OVER (PARTITION BY game ORDER BY score DESC) as rn
                        FROM tbl_tetris_scores
                    ) ranked
                    WHERE rn <= ?
                )
            ");
            $stmt->execute([$top_count]);
        } else {
            // Get top scores for specific game
            $stmt = $pdo->prepare("
                SELECT id FROM tbl_tetris_scores 
                WHERE game = ? 
                ORDER BY score DESC 
                LIMIT ?
            ");
            $stmt->execute([$game_type, $top_count]);
        }
        
        $top_score_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($top_score_ids)) {
            $placeholders = str_repeat('?,', count($top_score_ids) - 1) . '?';
            $where_conditions[] = "id IN ($placeholders)";
            $params = array_merge($params, $top_score_ids);
        }
    }

    // Build the final WHERE clause
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Execute the delete
    $sql = "DELETE FROM tbl_tetris_scores $where_clause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $deleted_count = $stmt->rowCount();

    // Log the reset action
    $log_entry = date('Y-m-d H:i:s') . " - Game scores reset by admin: $admin_username\n";
    $log_entry .= "  Game Type: $game_type\n";
    $log_entry .= "  Reset Type: $reset_type\n";
    $log_entry .= "  User ID: " . ($user_id ?? 'N/A') . "\n";
    $log_entry .= "  Top Count: " . ($top_count ?? 'N/A') . "\n";
    $log_entry .= "  Records Deleted: $deleted_count\n";
    $log_entry .= "  Stats Before Reset: " . json_encode($stats_before) . "\n\n";
    
    file_put_contents('../../logs/game-score-resets.log', $log_entry, FILE_APPEND | LOCK_EX);

    // Get updated statistics
    $stats_after = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'tetris'");
        $stmt->execute();
        $stats_after['tetris'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'snake'");
        $stmt->execute();
        $stats_after['snake'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'message' => "Successfully reset game scores",
        'details' => [
            'game_type' => $game_type,
            'reset_type' => $reset_type,
            'user_id' => $user_id,
            'top_count' => $top_count,
            'records_deleted' => $deleted_count,
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