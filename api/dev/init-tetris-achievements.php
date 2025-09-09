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
    
    // Check if achievement definitions already exist
    $checkQuery = "SELECT COUNT(*) as count FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute();
    $existingCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($existingCount > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Tetris achievement definitions already exist',
            'data' => [
                'existing_count' => $existingCount,
                'action' => 'skipped'
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Insert all 29 Tetris achievement definitions
    $achievements = [
        ['first_line', 'First Line', 'Clear your first line', '🎯', 0, 1, 0, 0, 0],
        ['line_master', 'Line Master', 'Clear 10 lines total', '⭐', 0, 10, 0, 0, 0],
        ['tetris_pro', 'Tetris Pro', 'Clear 50 lines total', '✨', 0, 50, 0, 0, 0],
        ['line_legend', 'Line Legend', 'Clear 100 lines total', '🌟', 0, 100, 0, 0, 0],
        ['speed_demon', 'Speed Demon', 'Reach level 5', '⚡', 0, 0, 5, 0, 0],
        ['level_master', 'Level Master', 'Reach level 10', '🏆', 0, 0, 10, 0, 0],
        ['high_roller', 'High Roller', 'Score 10,000 points', '💰', 10000, 0, 0, 0, 0],
        ['score_hunter', 'Score Hunter', 'Score 50,000 points', '🎯', 50000, 0, 0, 0, 0],
        ['point_master', 'Point Master', 'Score 100,000 points', '💎', 100000, 0, 0, 0, 0],
        ['tetris_king', 'Tetris King', 'Score 250,000 points', '👑', 250000, 0, 0, 0, 0],
        ['piece_dropper', 'Piece Dropper', 'Drop 100 pieces', '🔻', 0, 0, 0, 100, 0],
        ['block_master', 'Block Master', 'Drop 500 pieces', '🧱', 0, 0, 0, 500, 0],
        ['tetris_clear', 'Tetris Clear', 'Clear 4 lines at once', '🎆', 0, 0, 0, 0, 1],
        ['tetris_master', 'Tetris Master', 'Clear 4 lines 5 times', '🎇', 0, 0, 0, 0, 5],
        ['tetris_god', 'Tetris God', 'Clear 4 lines 10 times', '⚡', 0, 0, 0, 0, 10],
        ['combo_starter', 'Combo Starter', 'Clear 2 lines in a row', '🔗', 0, 2, 0, 0, 0],
        ['combo_master', 'Combo Master', 'Clear 5 lines in a row', '🔗', 0, 5, 0, 0, 0],
        ['combo_legend', 'Combo Legend', 'Clear 10 lines in a row', '🔗', 0, 10, 0, 0, 0],
        ['back_to_back', 'Back-to-Back', 'Clear Tetris twice in a row', '🔄', 0, 0, 0, 0, 2],
        ['perfect_clear', 'Perfect Clear', 'Clear the entire board', '✨', 0, 0, 0, 0, 0],
        ['level_warrior', 'Level Warrior', 'Reach level 15', '⚔️', 0, 0, 15, 0, 0],
        ['level_champion', 'Level Champion', 'Reach level 20', '🏅', 0, 0, 20, 0, 0],
        ['score_legend', 'Score Legend', 'Score 500,000 points', '💫', 500000, 0, 0, 0, 0],
        ['score_god', 'Score God', 'Score 1,000,000 points', '🌟', 1000000, 0, 0, 0, 0],
        ['line_destroyer', 'Line Destroyer', 'Clear 200 lines total', '💥', 0, 200, 0, 0, 0],
        ['piece_legend', 'Piece Legend', 'Drop 1,000 pieces', '🔻', 0, 0, 0, 1000, 0],
        ['tetris_legend', 'Tetris Legend', 'Clear 4 lines 25 times', '🎆', 0, 0, 0, 0, 25],
        ['ultimate_player', 'Ultimate Player', 'Complete all basic achievements', '🎖️', 0, 0, 0, 0, 0],
        ['tetris_champion', 'Tetris Champion', 'Master all Tetris skills', '🏆', 0, 0, 0, 0, 0]
    ];
    
    $insertQuery = "
        INSERT INTO tbl_tetris_achievements (
            user_id, achievement_key, achievement_title, achievement_description, 
            achievement_icon, game_score, lines_cleared, level_reached, 
            pieces_dropped, tetris_clears
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $insertStmt = $pdo->prepare($insertQuery);
    $insertedCount = 0;
    
    foreach ($achievements as $achievement) {
        $insertStmt->execute([
            'ACHIEVEMENT_DEFINITIONS',
            $achievement[0], // achievement_key
            $achievement[1], // achievement_title
            $achievement[2], // achievement_description
            $achievement[3], // achievement_icon
            $achievement[4], // game_score
            $achievement[5], // lines_cleared
            $achievement[6], // level_reached
            $achievement[7], // pieces_dropped
            $achievement[8]  // tetris_clears
        ]);
        $insertedCount++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tetris achievement definitions initialized successfully',
        'data' => [
            'achievements_inserted' => $insertedCount,
            'total_definitions' => count($achievements),
            'action' => 'initialized'
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
