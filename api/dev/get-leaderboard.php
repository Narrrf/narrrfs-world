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
    $currentSeason = $seasonStmt->fetchColumn() ?: 'Season 7'; // Fallback to Season 7
    
    // 🔍 Get the previous season (for frozen leaderboard fallback)
    $prevSeasonStmt = $db->prepare("
        SELECT season_name 
        FROM tbl_seasons 
        WHERE is_active = 0
        ORDER BY end_date DESC 
        LIMIT 1
    ");
    $prevSeasonStmt->execute();
    $previousSeason = $prevSeasonStmt->fetchColumn() ?: 'Season 6'; // Fallback to Season 6
    
    // Log for debugging
    error_log("Current season: $currentSeason, Previous season: $previousSeason");
    
    // Helper function to get leaderboard from current season or fallback to previous
    function getLeaderboard($db, $game, $currentSeason, $previousSeason, $useFrozenLeaderboard = false) {
        // If we should use frozen leaderboard (determined by total scores across all games), skip current season check
        if ($useFrozenLeaderboard) {
            error_log("Using frozen $previousSeason leaderboard for $game (total scores across all games < 3)");
            
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
        
        // Get current season scores (only if not using frozen leaderboard)
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
        
        // Return current season scores
        return [
            'leaderboard' => $currentLeaderboard,
            'is_frozen' => false,
            'season_shown' => $currentSeason
        ];
    }
    
    // 🐛 BUG FIX: Check total scores across ALL 3 games combined (not per game)
    // This ensures that if a player plays 1 game of each (3 total), the leaderboard switches to Season 6
    $totalScoresStmt = $db->prepare("
        SELECT COUNT(*) as total_scores
        FROM tbl_tetris_scores 
        WHERE season = ? AND game IN ('tetris', 'snake', 'space_invaders')
    ");
    $totalScoresStmt->execute([$currentSeason]);
    $totalScoresAcrossAllGames = $totalScoresStmt->fetchColumn() ?: 0;
    
    error_log("Total scores across all 3 games in $currentSeason: $totalScoresAcrossAllGames");
    
    // If we have 3+ scores total across all games, use current season; otherwise use frozen
    $useFrozenLeaderboard = ($totalScoresAcrossAllGames < 3);
    
    // Get leaderboards for all three games
    $tetrisResult = getLeaderboard($db, 'tetris', $currentSeason, $previousSeason, $useFrozenLeaderboard);
    $snakeResult = getLeaderboard($db, 'snake', $currentSeason, $previousSeason, $useFrozenLeaderboard);
    $spaceInvadersResult = getLeaderboard($db, 'space_invaders', $currentSeason, $previousSeason, $useFrozenLeaderboard);
    
    // Round Space Invaders scores
    foreach ($spaceInvadersResult['leaderboard'] as &$entry) {
        $entry['score'] = round($entry['score']);
    }
    
    // 🧩 Get Glyph Memory leaderboard (all-time, per difficulty)
    function getGlyphMemoryLeaderboard($db) {
        $difficulties = ['easy', 'medium', 'hard'];
        $result = [];
        
        foreach ($difficulties as $difficulty) {
            // Get best time per player for this difficulty (all-time, no season filter)
            $stmt = $db->prepare("
                SELECT 
                    discord_id,
                    COALESCE(discord_name, 'Guest') as discord_name,
                    difficulty,
                    MIN(time_ms) as best_time_ms,
                    MIN(timestamp) as timestamp
                FROM tbl_glyph_memory_scores 
                WHERE difficulty = ?
                GROUP BY discord_id, discord_name, difficulty
                ORDER BY best_time_ms ASC, timestamp ASC
                LIMIT 10
            ");
            $stmt->execute([$difficulty]);
            $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format time_ms as MM:SS
            foreach ($scores as &$entry) {
                $totalSec = floor($entry['best_time_ms'] / 1000);
                $min = floor($totalSec / 60);
                $sec = $totalSec % 60;
                $entry['best_time_formatted'] = sprintf("%02d:%02d", $min, $sec);
            }
            
            $result[$difficulty] = $scores;
        }
        
        return $result;
    }
    
    $glyphMemoryLeaderboard = [];
    // Check if table exists before querying
    $tableCheck = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_glyph_memory_scores'");
    $tableCheck->execute();
    if ($tableCheck->fetch()) {
        $glyphMemoryLeaderboard = getGlyphMemoryLeaderboard($db);
    }
    
    // Determine which season is being displayed
    $displaySeason = $useFrozenLeaderboard ? $previousSeason : $currentSeason;
    $isFrozen = $useFrozenLeaderboard;
    
    echo json_encode([
        'success' => true,
        'current_season' => $currentSeason,
        'display_season' => $displaySeason,
        'is_frozen' => $isFrozen,
        'tetris' => $tetrisResult['leaderboard'],
        'snake' => $snakeResult['leaderboard'],
        'space_invaders' => $spaceInvadersResult['leaderboard'],
        'glyph_memory' => $glyphMemoryLeaderboard
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
