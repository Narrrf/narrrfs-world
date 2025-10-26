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
    
    // 🏆 Insert all 24 Tetris achievement definitions (REVISED 2025-10-26 - Bug #131, #136, #127, #134)
    // Based on max score ~2500 DSPOINC, balanced for realistic gameplay
    // Format: [key, title, description, icon, game_score, lines_cleared, level_reached, pieces_dropped, tetris_clears]
    $achievements = [
        // === SCORE-BASED (5 achievements - 8% to 100% of max 2500) ===
        ['score_hunter', 'Score Hunter', 'Earn 200 DSPOINC in one game', 'TARGET', 200, 0, 0, 0, 0],
        ['high_roller', 'High Roller', 'Earn 800 DSPOINC in one game', 'CASH', 800, 0, 0, 0, 0],
        ['point_master', 'Point Master', 'Earn 1,500 DSPOINC in one game', 'DIAMOND', 1500, 0, 0, 0, 0],
        ['score_legend', 'Score Legend', 'Earn 2,000 DSPOINC in one game', 'STAR', 2000, 0, 0, 0, 0],
        ['tetris_king', 'Tetris King', 'Earn 2,500 DSPOINC (maximum score!)', 'CROWN', 2500, 0, 0, 0, 0],
        
        // === LINE-BASED (5 achievements - beginner to expert) ===
        ['first_line', 'First Line', 'Clear your first line', '🎯', 0, 1, 0, 0, 0],
        ['line_master', 'Line Master', 'Clear 10 lines in one game', '⭐', 0, 10, 0, 0, 0],
        ['tetris_pro', 'Tetris Pro', 'Clear 30 lines in one game', '✨', 0, 30, 0, 0, 0],
        ['line_legend', 'Line Legend', 'Clear 50 lines in one game', '🌟', 0, 50, 0, 0, 0],
        ['line_destroyer', 'Line Destroyer', 'Clear 100 lines in one game', '💥', 0, 100, 0, 0, 0],
        
        // === LEVEL-BASED (4 achievements - speed progression) ===
        ['speed_demon', 'Speed Demon', 'Reach Level 5', '⚡', 0, 0, 5, 0, 0],
        ['level_master', 'Level Master', 'Reach Level 8', '🏆', 0, 0, 8, 0, 0],
        ['level_warrior', 'Level Warrior', 'Reach Level 12', '⚔️', 0, 0, 12, 0, 0],
        ['level_champion', 'Level Champion', 'Reach Level 15', '🏅', 0, 0, 15, 0, 0],
        
        // === TETRIS CLEARS (5 achievements - 4-line mastery) ===
        ['tetris_clear', 'Tetris Clear', 'Clear 4 lines at once (Tetris!)', '🎆', 0, 0, 0, 0, 1],
        ['back_to_back', 'Back to Back', 'Clear 2 Tetris in one game', '🔄', 0, 0, 0, 0, 2],
        ['tetris_master', 'Tetris Master', 'Clear 5 Tetris in one game', '🎇', 0, 0, 0, 0, 5],
        ['tetris_god', 'Tetris God', 'Clear 8 Tetris in one game', '⚡', 0, 0, 0, 0, 8],
        ['tetris_legend', 'Tetris Legend', 'Clear 15 Tetris in one game', '🎆', 0, 0, 0, 0, 15],
        
        // === COMBO-BASED (3 achievements - FIXED: realistic thresholds) ===
        ['combo_starter', 'Combo Starter', 'Clear 2 lines at once', '🔗', 0, 0, 0, 0, 0],
        ['combo_master', 'Combo Master', 'Clear 3 lines at once', '🔗', 0, 0, 0, 0, 0],
        ['combo_legend', 'Combo Legend', 'Clear 4 lines at once (Tetris!)', '🔗', 0, 0, 0, 0, 0],
        
        // === PIECE-BASED (3 achievements - endurance) ===
        ['piece_dropper', 'Piece Dropper', 'Drop 100 pieces in one game', '🔻', 0, 0, 0, 100, 0],
        ['block_master', 'Block Master', 'Drop 400 pieces in one game', '🧱', 0, 0, 0, 400, 0],
        ['piece_legend', 'Piece Legend', 'Drop 600 pieces in one game', '🔻', 0, 0, 0, 600, 0]
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
