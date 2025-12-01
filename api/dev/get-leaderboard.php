<?php
// 🧠 Cheese Architect API — Get Leaderboard from SQLite
header('Content-Type: application/json');

// Use the correct database path that contains all user data
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 🔍 Get the active season from tbl_seasons table
    $seasonStmt = $db->prepare("
        SELECT season_name 
        FROM tbl_seasons 
        WHERE is_active = 1
        ORDER BY start_date DESC 
        LIMIT 1
    ");
    $seasonStmt->execute();
    $currentSeason = $seasonStmt->fetchColumn() ?: 'Season 6'; // Fallback to Season 6
    
    // 🔍 Get the previous season (for frozen leaderboard fallback)
    $prevSeasonStmt = $db->prepare("
        SELECT season_name 
        FROM tbl_seasons 
        WHERE is_active = 0
        ORDER BY end_date DESC 
        LIMIT 1
    ");
    $prevSeasonStmt->execute();
    $previousSeason = $prevSeasonStmt->fetchColumn() ?: 'Season 5'; // Fallback to Season 5
    
    // Log for debugging
    error_log("Current season: $currentSeason, Previous season: $previousSeason");
    
    // Helper function to get leaderboard from current season or fallback to previous
    function getLeaderboard($db, $game, $currentSeason, $previousSeason) {
        // First, try to get scores from current season
        $currentStmt = $db->prepare("
            SELECT 
                discord_id,
                discord_name,
                MAX(score) as score,
                MIN(timestamp) as timestamp
            FROM tbl_tetris_scores 
            WHERE game = ? AND season = ?
            GROUP BY discord_id, discord_name
            ORDER BY score DESC, timestamp ASC
            LIMIT 10
        ");
        $currentStmt->execute([$game, $currentSeason]);
        $currentLeaderboard = $currentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If current season has no scores (or very few), use previous season from historical stats
        if (empty($currentLeaderboard) || count($currentLeaderboard) < 3) {
            error_log("Current season $currentSeason has no/few scores for $game, using frozen $previousSeason leaderboard");
            
            // Get from historical stats (previous season's frozen leaderboard)
            $historicalStmt = $db->prepare("
                SELECT 
                    hs.discord_id,
                    COALESCE(u.username, hs.discord_id) as discord_name,
                    hs.best_score as score,
                    hs.season_end_date as timestamp
                FROM tbl_historical_stats hs
                LEFT JOIN tbl_users u ON hs.discord_id = u.discord_id
                WHERE hs.game = ? AND hs.season = ?
                ORDER BY hs.best_score DESC, hs.season_end_date ASC
                LIMIT 10
            ");
            $historicalStmt->execute([$game, $previousSeason]);
            $historicalLeaderboard = $historicalStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return historical with flag indicating it's frozen
            return [
                'leaderboard' => $historicalLeaderboard,
                'is_frozen' => true,
                'season_shown' => $previousSeason
            ];
        }
        
        // Return current season scores
        return [
            'leaderboard' => $currentLeaderboard,
            'is_frozen' => false,
            'season_shown' => $currentSeason
        ];
    }
    
    // Get leaderboards for all three games
    $tetrisResult = getLeaderboard($db, 'tetris', $currentSeason, $previousSeason);
    $snakeResult = getLeaderboard($db, 'snake', $currentSeason, $previousSeason);
    $spaceInvadersResult = getLeaderboard($db, 'space_invaders', $currentSeason, $previousSeason);
    
    // Round Space Invaders scores
    foreach ($spaceInvadersResult['leaderboard'] as &$entry) {
        $entry['score'] = round($entry['score']);
    }
    
    // Determine which season is being displayed (use first non-empty result)
    $displaySeason = $currentSeason;
    $isFrozen = false;
    if ($tetrisResult['is_frozen'] || $snakeResult['is_frozen'] || $spaceInvadersResult['is_frozen']) {
        $isFrozen = true;
        $displaySeason = $previousSeason;
    }
    
    echo json_encode([
        'success' => true,
        'current_season' => $currentSeason,
        'display_season' => $displaySeason,
        'is_frozen' => $isFrozen,
        'tetris' => $tetrisResult['leaderboard'],
        'snake' => $snakeResult['leaderboard'],
        'space_invaders' => $spaceInvadersResult['leaderboard']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
