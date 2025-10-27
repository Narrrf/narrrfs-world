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
        WHERE is_active = 1
        ORDER BY start_date DESC 
        LIMIT 1
    ");
    $seasonStmt->execute();
    $currentSeason = $seasonStmt->fetchColumn() ?: 'Season 4'; // Fallback to Season 4
    
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
    
    // ✅ FIX (2025-10-27): Snake scores are ALREADY in DSPOINC (baseScore = 10)
    // No conversion needed - database stores correct DSPOINC values
    // Legacy multiplication by 10 removed (was causing 1220 to show as 12200)
    
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
    
        // Space Invaders scores are already in DSPOINC format (no conversion needed)
        foreach ($spaceInvadersLeaderboard as &$entry) {
            $entry['score'] = round($entry['score']); // Just round to integer
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
