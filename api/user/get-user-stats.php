<?php
// 🧠 User Stats API — Get user statistics for Season 2
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current season
    $currentSeason = 'season_1'; // Default to season 1 for now
    
    // Get user's best Tetris score
    $tetrisStmt = $db->prepare("
        SELECT MAX(score) as best_score 
        FROM tbl_tetris_scores 
        WHERE discord_id = ? AND game = 'tetris' AND season = ? AND is_current_season = 1
    ");
    $tetrisStmt->bindValue(1, $user_id);
    $tetrisStmt->bindValue(2, $currentSeason);
    $tetrisStmt->execute();
    $tetrisBest = $tetrisStmt->fetch(PDO::FETCH_ASSOC)['best_score'] ?? 0;
    
    // Get user's best Snake score
    $snakeStmt = $db->prepare("
        SELECT MAX(score) as best_score 
        FROM tbl_tetris_scores 
        WHERE discord_id = ? AND game = 'snake' AND season = ? AND is_current_season = 1
    ");
    $snakeStmt->bindValue(1, $user_id);
    $snakeStmt->bindValue(2, $currentSeason);
    $snakeStmt->execute();
    $snakeBest = $snakeStmt->fetch(PDO::FETCH_ASSOC)['best_score'] ?? 0;
    
    // Get user's Tetris rank
    $tetrisRankStmt = $db->prepare("
        SELECT COUNT(*) + 1 as rank
        FROM (
            SELECT discord_id, MAX(score) as best_score
            FROM tbl_tetris_scores 
            WHERE game = 'tetris' AND season = ? AND is_current_season = 1
            GROUP BY discord_id
            HAVING MAX(score) > ?
        )
    ");
    $tetrisRankStmt->bindValue(1, $currentSeason);
    $tetrisRankStmt->bindValue(2, $tetrisBest);
    $tetrisRankStmt->execute();
    $tetrisRank = $tetrisRankStmt->fetch(PDO::FETCH_ASSOC)['rank'] ?? 1;
    
    // Get user's Snake rank
    $snakeRankStmt = $db->prepare("
        SELECT COUNT(*) + 1 as rank
        FROM (
            SELECT discord_id, MAX(score) as best_score
            FROM tbl_tetris_scores 
            WHERE game = 'snake' AND season = ? AND is_current_season = 1
            GROUP BY discord_id
            HAVING MAX(score) > ?
        )
    ");
    $snakeRankStmt->bindValue(1, $currentSeason);
    $snakeRankStmt->bindValue(2, $snakeBest);
    $snakeRankStmt->execute();
    $snakeRank = $snakeRankStmt->fetch(PDO::FETCH_ASSOC)['rank'] ?? 1;
    
    // Get total DSPOINC earned this season
    $totalDspoincStmt = $db->prepare("
        SELECT SUM(score) as total_dspoinc
        FROM tbl_tetris_scores 
        WHERE discord_id = ? AND season = ? AND is_current_season = 1
    ");
    $totalDspoincStmt->bindValue(1, $user_id);
    $totalDspoincStmt->bindValue(2, $currentSeason);
    $totalDspoincStmt->execute();
    $totalDspoinc = $totalDspoincStmt->fetch(PDO::FETCH_ASSOC)['total_dspoinc'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'tetris_best' => $tetrisBest,
        'snake_best' => $snakeBest,
        'tetris_rank' => $tetrisRank,
        'snake_rank' => $snakeRank,
        'total_dspoinc' => $totalDspoinc,
        'season' => $currentSeason
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?> 