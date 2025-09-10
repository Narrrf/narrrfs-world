<?php
// 🎮 Season Management API - Handle Season 2 operations
header('Content-Type: application/json');

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_current_season':
            getCurrentSeason($db);
            break;
            
        case 'start_new_season':
            startNewSeason($db);
            break;
            
        case 'end_current_season':
            endCurrentSeason($db);
            break;
            
        case 'get_season_statistics':
            getSeasonStatistics($db);
            break;
            
        case 'get_season_leaderboard':
            getSeasonLeaderboard($db);
            break;
            
        case 'reset_season':
            resetSeason($db);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getCurrentSeason($db) {
    $stmt = $db->prepare("
        SELECT season_id, season_name, start_date, end_date, is_active 
        FROM tbl_seasons 
        WHERE is_active = 1 
        ORDER BY season_id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $season = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$season) {
        // Create Season 2 if no active season exists
        $stmt = $db->prepare("
            INSERT INTO tbl_seasons (season_name, start_date, is_active) 
            VALUES ('Season 2 - The Cheese Season of Glory', CURRENT_TIMESTAMP, 1)
        ");
        $stmt->execute();
        $season_id = $db->lastInsertId();
        
        $season = [
            'season_id' => $season_id,
            'season_name' => 'Season 2 - The Cheese Season of Glory',
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => null,
            'is_active' => 1
        ];
    }
    
    echo json_encode(['success' => true, 'season' => $season]);
}

function startNewSeason($db) {
    // End current season first
    $stmt = $db->prepare("UPDATE tbl_seasons SET is_active = 0, end_date = CURRENT_TIMESTAMP WHERE is_active = 1");
    $stmt->execute();
    
    // Start new season
    $seasonName = $_POST['season_name'] ?? 'Season 3';
    $stmt = $db->prepare("
        INSERT INTO tbl_seasons (season_name, start_date, is_active) 
        VALUES (?, CURRENT_TIMESTAMP, 1)
    ");
    $stmt->bindValue(1, $seasonName);
    $stmt->execute();
    
    $season_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => "New season started: $seasonName",
        'season_id' => $season_id
    ]);
}

function endCurrentSeason($db) {
    $stmt = $db->prepare("UPDATE tbl_seasons SET is_active = 0, end_date = CURRENT_TIMESTAMP WHERE is_active = 1");
    $stmt->execute();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Current season ended successfully'
    ]);
}

function getSeasonStatistics($db) {
    $season_id = $_GET['season_id'] ?? null;
    
    if (!$season_id) {
        // Get current active season
        $stmt = $db->prepare("SELECT season_id FROM tbl_seasons WHERE is_active = 1 ORDER BY season_id DESC LIMIT 1");
        $stmt->execute();
        $season = $stmt->fetch(PDO::FETCH_ASSOC);
        $season_id = $season['season_id'];
    }
    
    // Get total DSPOINC earned
    $stmt = $db->prepare("
        SELECT SUM(dspoinc_earned) as total_dspoinc 
        FROM tbl_user_season_achievements 
        WHERE season_id = ?
    ");
    $stmt->bindValue(1, $season_id, PDO::PARAM_INT);
    $stmt->execute();
    $totalDspoinc = $stmt->fetch(PDO::FETCH_ASSOC)['total_dspoinc'] ?? 0;
    
    // Get active players
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT user_id) as active_players 
        FROM tbl_user_season_achievements 
        WHERE season_id = ?
    ");
    $stmt->bindValue(1, $season_id, PDO::PARAM_INT);
    $stmt->execute();
    $activePlayers = $stmt->fetch(PDO::FETCH_ASSOC)['active_players'] ?? 0;
    
    // Get games played
    $stmt = $db->prepare("
        SELECT COUNT(*) as games_played 
        FROM tbl_tetris_scores 
        WHERE season_id = ?
    ");
    $stmt->bindValue(1, $season_id, PDO::PARAM_INT);
    $stmt->execute();
    $gamesPlayed = $stmt->fetch(PDO::FETCH_ASSOC)['games_played'] ?? 0;
    
    // Get top achievements
    $stmt = $db->prepare("
        SELECT 
            usa.user_id,
            usa.game,
            usa.highest_score,
            usa.dspoinc_earned,
            usa.achieved_at
        FROM tbl_user_season_achievements usa
        WHERE usa.season_id = ?
        ORDER BY usa.highest_score DESC
        LIMIT 10
    ");
    $stmt->bindValue(1, $season_id, PDO::PARAM_INT);
    $stmt->execute();
    $topAchievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'statistics' => [
            'total_dspoinc' => $totalDspoinc,
            'active_players' => $activePlayers,
            'games_played' => $gamesPlayed,
            'top_achievements' => $topAchievements
        ]
    ]);
}

function getSeasonLeaderboard($db) {
    $season_id = $_GET['season_id'] ?? null;
    $game = $_GET['game'] ?? 'total';
    
    if (!$season_id) {
        // Get current active season
        $stmt = $db->prepare("SELECT season_id FROM tbl_seasons WHERE is_active = 1 ORDER BY season_id DESC LIMIT 1");
        $stmt->execute();
        $season = $stmt->fetch(PDO::FETCH_ASSOC);
        $season_id = $season['season_id'];
    }
    
    if ($game === 'total') {
        // Get total DSPOINC leaderboard
        $stmt = $db->prepare("
            SELECT 
                usa.user_id,
                SUM(usa.dspoinc_earned) as total_dspoinc,
                MAX(CASE WHEN usa.game = 'tetris' THEN usa.highest_score ELSE 0 END) as tetris_best,
                MAX(CASE WHEN usa.game = 'snake' THEN usa.highest_score ELSE 0 END) as snake_best
            FROM tbl_user_season_achievements usa
            WHERE usa.season_id = ?
            GROUP BY usa.user_id
            ORDER BY total_dspoinc DESC
            LIMIT 20
        ");
        $stmt->bindValue(1, $season_id, PDO::PARAM_INT);
    } else {
        // Get specific game leaderboard
        $stmt = $db->prepare("
            SELECT 
                usa.user_id,
                usa.highest_score,
                usa.dspoinc_earned,
                usa.achieved_at
            FROM tbl_user_season_achievements usa
            WHERE usa.season_id = ? AND usa.game = ?
            ORDER BY usa.highest_score DESC
            LIMIT 20
        ");
        $stmt->bindValue(1, $season_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $game);
    }
    
    $stmt->execute();
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'leaderboard' => $leaderboard,
        'game' => $game,
        'season_id' => $season_id
    ]);
}

function resetSeason($db) {
    try {
        // Get current season info
        $stmt = $db->prepare("SELECT MAX(CAST(SUBSTR(season, 8) AS INTEGER)) as max_season FROM tbl_tetris_scores WHERE season LIKE 'season_%'");
        $stmt->execute();
        $current_season_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_season = $current_season_result['max_season'] ?? 1;
        $new_season = $current_season + 1;

        // Mark top performers from current season before resetting
        $top_performers = [];
        
        // Tetris top performers
        $stmt = $db->prepare("
            SELECT discord_id, discord_name, score, game 
            FROM tbl_tetris_scores 
            WHERE game = 'tetris' AND season = ? 
            ORDER BY score DESC 
            LIMIT 2
        ");
        $stmt->execute(["season_$current_season"]);
        $top_performers['tetris'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Snake top performers
        $stmt = $db->prepare("
            SELECT discord_id, discord_name, score, game 
            FROM tbl_tetris_scores 
            WHERE game = 'snake' AND season = ? 
            ORDER BY score DESC 
            LIMIT 2
        ");
        $stmt->execute(["season_$current_season"]);
        $top_performers['snake'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Update top performers in current season
        foreach ($top_performers as $game => $performers) {
            foreach ($performers as $performer) {
                $stmt = $db->prepare("
                    UPDATE tbl_tetris_scores 
                    SET is_top_performer = 1 
                    WHERE discord_id = ? AND game = ? AND season = ?
                ");
                $stmt->execute([$performer['discord_id'], $performer['game'], "season_$current_season"]);
            }
        }

        // Set season end date for current season
        $stmt = $db->prepare("
            UPDATE tbl_tetris_scores 
            SET season_end_date = CURRENT_TIMESTAMP 
            WHERE season = ? AND season_end_date IS NULL
        ");
        $stmt->execute(["season_$current_season"]);

        // Reset Tetris scores
        $stmt = $db->prepare("
            UPDATE tbl_tetris_scores 
            SET season = ?, is_current_season = 0 
            WHERE game = 'tetris' AND season = ? AND is_current_season = 1
        ");
        $stmt->execute(["season_${current_season}_historical", "season_$current_season"]);
        
        // Reset Snake scores
        $stmt = $db->prepare("
            UPDATE tbl_tetris_scores 
            SET season = ?, is_current_season = 0 
            WHERE game = 'snake' AND season = ? AND is_current_season = 1
        ");
        $stmt->execute(["season_${current_season}_historical", "season_$current_season"]);

        // Reset Cheese Hunt clicks
        $stmt = $db->prepare("
            UPDATE tbl_cheese_clicks 
            SET season = ?, is_current_season = 0 
            WHERE season = ? AND is_current_season = 1
        ");
        $stmt->execute(["season_${current_season}_historical", "season_$current_season"]);

        // Reset Discord Race participants
        $stmt = $db->prepare("
            UPDATE tbl_race_participants 
            SET season = ?, is_current_season = 0 
            WHERE season = ? AND is_current_season = 1
        ");
        $stmt->execute(["season_${current_season}_historical", "season_$current_season"]);

        // Reset User Scores
        $stmt = $db->prepare("
            UPDATE tbl_user_scores 
            SET season = ?, is_current_season = 0 
            WHERE season = ? AND is_current_season = 1
        ");
        $stmt->execute(["season_${current_season}_historical", "season_$current_season"]);

        // Get count of affected records
        $affected_count = 0;
        
        // Count Tetris records
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_tetris_scores WHERE game = 'tetris' AND season = ?");
        $stmt->execute(["season_${current_season}_historical"]);
        $affected_count += $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count Snake records
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_tetris_scores WHERE game = 'snake' AND season = ?");
        $stmt->execute(["season_${current_season}_historical"]);
        $affected_count += $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Count Cheese Hunt records
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_cheese_clicks WHERE season = ?");
        $stmt->execute(["season_${current_season}_historical"]);
        $affected_count += $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Count Discord Race records
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_race_participants WHERE season = ?");
        $stmt->execute(["season_${current_season}_historical"]);
        $affected_count += $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Count User Scores records
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_user_scores WHERE season = ?");
        $stmt->execute(["season_${current_season}_historical"]);
        $affected_count += $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        echo json_encode([
            'success' => true,
            'message' => "Successfully reset all game data for Season $new_season",
            'details' => [
                'current_season' => "season_$current_season",
                'new_season' => "season_$new_season",
                'records_affected' => $affected_count,
                'top_performers' => $top_performers,
                'historical_season' => "season_${current_season}_historical",
                'games_reset' => ['tetris', 'snake', 'cheese_hunt', 'discord_race', 'user_scores']
            ],
            'reset_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        error_log("Reset season error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Season reset failed: ' . $e->getMessage()
        ]);
    }
}
?> 