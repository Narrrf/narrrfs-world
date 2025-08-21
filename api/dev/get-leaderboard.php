<?php
// 🧠 Cheese Architect API — Get Leaderboard from SQLite
header('Content-Type: application/json');

// Use the correct database path that contains all user data
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 🔍 Get the most recent season from season_settings table
    $seasonStmt = $db->prepare("
        SELECT season_name 
        FROM tbl_season_settings 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $seasonStmt->execute();
    $currentSeason = $seasonStmt->fetchColumn() ?: 'season_1'; // Fallback to season_1
    
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
    
    // Get Snake leaderboard (from tbl_user_scores) - current season
    $snakeStmt = $db->prepare("
        SELECT 
            user_id as discord_id,
            MAX(score) as score,
            MIN(timestamp) as timestamp
        FROM tbl_user_scores 
        WHERE game = 'snake' AND season = ?
        GROUP BY user_id
        ORDER BY score DESC, timestamp ASC
        LIMIT 10
    ");
    $snakeStmt->bindValue(1, $currentSeason);
    $snakeStmt->execute();
    $snakeLeaderboard = $snakeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get Space Invaders leaderboard (from tbl_user_scores) - current season
    $spaceInvadersStmt = $db->prepare("
        SELECT 
            user_id as discord_id,
            MAX(score) as score,
            MIN(timestamp) as timestamp
        FROM tbl_user_scores 
        WHERE game = 'space_invaders' AND season = ?
        GROUP BY user_id
        ORDER BY score DESC, timestamp ASC
        LIMIT 10
    ");
    $spaceInvadersStmt->bindValue(1, $currentSeason);
    $spaceInvadersStmt->execute();
    $spaceInvadersLeaderboard = $spaceInvadersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 🧀 Get Discord names for Snake and Space Invaders from tbl_users
    $snakeLeaderboardWithNames = [];
    foreach ($snakeLeaderboard as $entry) {
        $nameStmt = $db->prepare("SELECT username FROM tbl_users WHERE discord_id = ?");
        $nameStmt->bindValue(1, $entry['discord_id']);
        $nameStmt->execute();
        $user = $nameStmt->fetch(PDO::FETCH_ASSOC);
        
        $snakeLeaderboardWithNames[] = [
            'discord_id' => $entry['discord_id'],
            'discord_name' => $user ? $user['username'] : 'Unknown Player',
            'score' => $entry['score'],
            'timestamp' => $entry['timestamp']
        ];
    }
    
    $spaceInvadersLeaderboardWithNames = [];
    foreach ($spaceInvadersLeaderboard as $entry) {
        $nameStmt = $db->prepare("SELECT username FROM tbl_users WHERE discord_id = ?");
        $nameStmt->bindValue(1, $entry['discord_id']);
        $nameStmt->execute();
        $user = $nameStmt->fetch(PDO::FETCH_ASSOC);
        
        $spaceInvadersLeaderboardWithNames[] = [
            'discord_id' => $entry['discord_id'],
            'discord_name' => $user ? $user['username'] : 'Unknown Player',
            'score' => $entry['score'],
            'timestamp' => $entry['timestamp']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'current_season' => $currentSeason,
        'tetris' => $tetrisLeaderboard,
        'snake' => $snakeLeaderboardWithNames,
        'space_invaders' => $spaceInvadersLeaderboardWithNames
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
