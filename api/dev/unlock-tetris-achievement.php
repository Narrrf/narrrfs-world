<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

try {
    $pdo = getSQLite3Connection();
    
    // Get parameters from request
    $user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
    $achievement_key = $_POST['achievement_key'] ?? $_GET['achievement_key'] ?? null;
    $game_score = $_POST['game_score'] ?? $_GET['game_score'] ?? 0;
    $lines_cleared = $_POST['lines_cleared'] ?? $_GET['lines_cleared'] ?? 0;
    $level_reached = $_POST['level_reached'] ?? $_GET['level_reached'] ?? 0;
    $pieces_dropped = $_POST['pieces_dropped'] ?? $_GET['pieces_dropped'] ?? 0;
    $tetris_clears = $_POST['tetris_clears'] ?? $_GET['tetris_clears'] ?? 0;
    
    if (!$user_id || !$achievement_key) {
        throw new Exception('User ID and achievement key are required');
    }
    
    // Check if achievement definition exists
    $definitionQuery = "
        SELECT achievement_title, achievement_description, achievement_icon
        FROM tbl_tetris_achievements 
        WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' AND achievement_key = ?
    ";
    
    $definitionStmt = $pdo->prepare($definitionQuery);
    $definitionStmt->execute([$achievement_key]);
    $definition = $definitionStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$definition) {
        throw new Exception('Achievement definition not found');
    }
    
    // Check if user already has this achievement
    $existingQuery = "
        SELECT id FROM tbl_tetris_achievements 
        WHERE user_id = ? AND achievement_key = ? AND user_id != 'ACHIEVEMENT_DEFINITIONS'
    ";
    
    $existingStmt = $pdo->prepare($existingQuery);
    $existingStmt->execute([$user_id, $achievement_key]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo json_encode([
            'success' => false,
            'message' => 'Achievement already unlocked',
            'data' => [
                'achievement_key' => $achievement_key,
                'achievement_title' => $definition['achievement_title'],
                'already_unlocked' => true
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Insert the unlocked achievement
    $insertQuery = "
        INSERT INTO tbl_tetris_achievements (
            user_id, achievement_key, achievement_title, achievement_description, 
            achievement_icon, game_score, lines_cleared, level_reached, 
            pieces_dropped, tetris_clears, unlocked_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([
        $user_id,
        $achievement_key,
        $definition['achievement_title'],
        $definition['achievement_description'],
        $definition['achievement_icon'],
        $game_score,
        $lines_cleared,
        $level_reached,
        $pieces_dropped,
        $tetris_clears,
        date('Y-m-d H:i:s')
    ]);
    
    // Get updated achievement count
    $countQuery = "
        SELECT COUNT(*) as count 
        FROM tbl_tetris_achievements 
        WHERE user_id = ? AND user_id != 'ACHIEVEMENT_DEFINITIONS'
    ";
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute([$user_id]);
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Achievement unlocked successfully',
        'data' => [
            'achievement_key' => $achievement_key,
            'achievement_title' => $definition['achievement_title'],
            'achievement_description' => $definition['achievement_description'],
            'achievement_icon' => $definition['achievement_icon'],
            'unlocked_at' => date('Y-m-d H:i:s'),
            'total_achievements' => $count,
            'game_stats' => [
                'game_score' => $game_score,
                'lines_cleared' => $lines_cleared,
                'level_reached' => $level_reached,
                'pieces_dropped' => $pieces_dropped,
                'tetris_clears' => $tetris_clears
            ]
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
