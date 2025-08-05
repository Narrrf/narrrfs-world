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
?> 