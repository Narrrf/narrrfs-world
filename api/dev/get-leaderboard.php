<?php
// 🧠 Cheese Architect API — Get Leaderboard from SQLite
header('Content-Type: application/json');

// Use the correct database path that contains all user data
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 🔍 Get the active season from tbl_seasons table (same as admin interface)
    $seasonStmt = $db->prepare("
        SELECT season_name 
        FROM tbl_seasons 
        WHERE is_active = 1 AND end_date IS NULL
        ORDER BY start_date DESC 
        LIMIT 1
    ");
    $seasonStmt->execute();
    $currentSeason = $seasonStmt->fetchColumn() ?: 'Season 3 - The Ultimate Cheese Challenge'; // Fallback to Season 3
    
    // Log the current season for debugging (but don't output to response)
    error_log("Current season detected: $currentSeason");
    
    // Get Tetris leaderboard (from tbl_tetris_scores) - current season only
    $tetrisStmt = $db->prepare("
        SELECT 
            discord_id,
            discord_name,
            MAX(score) as score,
            MIN(timestamp) as timestamp
        FROM tbl_tetris_scores 
        WHERE game = 'tetris' AND season = ?
        GROUP BY discord_id, discord_name
        ORDER BY score DESC, timestamp ASC
        LIMIT 10
    ");
    $tetrisStmt->bindValue(1, $currentSeason);
    $tetrisStmt->execute();
    $tetrisLeaderboard = $tetrisStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tetris scores are already in DSPOINC (2 DSPOINC per line) - no conversion needed
    
    // Get Snake leaderboard (from tbl_tetris_scores) - current season
    $snakeStmt = $db->prepare("
        SELECT 
            discord_id,
            discord_name,
            MAX(score) as score,
            MIN(timestamp) as timestamp
        FROM tbl_tetris_scores 
        WHERE game = 'snake' AND season = ?
        GROUP BY discord_id, discord_name
        ORDER BY score DESC, timestamp ASC
        LIMIT 10
    ");
    $snakeStmt->bindValue(1, $currentSeason);
    $snakeStmt->execute();
    $snakeLeaderboard = $snakeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert Snake scores to DSPOINC (multiply by 10)
    foreach ($snakeLeaderboard as &$entry) {
        $entry['score'] = $entry['score'] * 10;
    }
    
    // Get Space Invaders leaderboard (from tbl_tetris_scores) - current season
    $spaceInvadersStmt = $db->prepare("
        SELECT 
            discord_id,
            discord_name,
            MAX(score) as score,
            MIN(timestamp) as timestamp
        FROM tbl_tetris_scores 
        WHERE game = 'space_invaders' AND season = ?
        GROUP BY discord_id, discord_name
        ORDER BY score DESC, timestamp ASC
        LIMIT 10
    ");
    $spaceInvadersStmt->bindValue(1, $currentSeason);
    $spaceInvadersStmt->execute();
    $spaceInvadersLeaderboard = $spaceInvadersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert Space Invaders scores to DSPOINC (divide by 100)
    foreach ($spaceInvadersLeaderboard as &$entry) {
        $entry['score'] = round($entry['score'] / 100);
    }
    
    // Snake and Space Invaders now come with discord_name from tbl_tetris_scores
    
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
