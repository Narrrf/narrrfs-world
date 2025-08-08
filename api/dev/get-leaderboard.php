<?php
// 🧠 Cheese Architect API — Get Leaderboard from SQLite
header('Content-Type: application/json');

// Use the correct database path that contains all user data
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current season
    $currentSeason = 'season_1'; // Default to season 1 for now
    
    // Get Tetris leaderboard
    $tetrisStmt = $db->prepare("
        SELECT 
            discord_id,
            discord_name,
            MAX(score) as score,
            MIN(timestamp) as timestamp
        FROM tbl_tetris_scores 
        WHERE game = 'tetris' AND season = ? AND is_current_season = 1
        GROUP BY discord_id, discord_name
        ORDER BY score DESC, timestamp ASC
        LIMIT 10
    ");
    $tetrisStmt->bindValue(1, $currentSeason);
    $tetrisStmt->execute();
    $tetrisLeaderboard = $tetrisStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get Snake leaderboard
    $snakeStmt = $db->prepare("
        SELECT 
            discord_id,
            discord_name,
            MAX(score) as score,
            MIN(timestamp) as timestamp
        FROM tbl_tetris_scores 
        WHERE game = 'snake' AND season = ? AND is_current_season = 1
        GROUP BY discord_id, discord_name
        ORDER BY score DESC, timestamp ASC
        LIMIT 10
    ");
    $snakeStmt->bindValue(1, $currentSeason);
    $snakeStmt->execute();
    $snakeLeaderboard = $snakeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get Space Invaders leaderboard
    $spaceInvadersStmt = $db->prepare("
        SELECT 
            discord_id,
            discord_name,
            MAX(score) as score,
            MIN(timestamp) as timestamp
        FROM tbl_tetris_scores 
        WHERE game = 'space_invaders' AND season = ? AND is_current_season = 1
        GROUP BY discord_id, discord_name
        ORDER BY score DESC, timestamp ASC
        LIMIT 10
    ");
    $spaceInvadersStmt->bindValue(1, $currentSeason);
    $spaceInvadersStmt->execute();
    $spaceInvadersLeaderboard = $spaceInvadersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'current_season' => $currentSeason,
        'tetris' => $tetrisLeaderboard,
        'snake' => $snakeLeaderboard,
        'space_invaders' => $spaceInvadersLeaderboard
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
