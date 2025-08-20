<?php
// 🧠 Cheese Architect API — Get Season Data for Admin Interface
header('Content-Type: application/json');

// Use the correct database path
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $season = $_GET['season'] ?? 'season_2';
    
    $data = [
        'total_scores' => 0,
        'unique_players' => 0,
        'max_score' => 0,
        'games' => []
    ];
    
    // Get Tetris data for the season
    if ($season === 'all') {
        $tetrisStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT discord_id) as unique_players,
                MAX(score) as max_score
            FROM tbl_tetris_scores
        ");
        $tetrisStmt->execute();
    } else {
        $tetrisStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT discord_id) as unique_players,
                MAX(score) as max_score
            FROM tbl_tetris_scores 
            WHERE season = ?
        ");
        $tetrisStmt->bindValue(1, $season);
        $tetrisStmt->execute();
    }
    
    $tetrisData = $tetrisStmt->fetch(PDO::FETCH_ASSOC);
    $data['games']['tetris'] = $tetrisData;
    $data['total_scores'] += $tetrisData['total_scores'];
    
    // Get Snake data for the season
    if ($season === 'all') {
        $snakeStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT user_id) as unique_players,
                MAX(score) as max_score
            FROM tbl_user_scores 
            WHERE game = 'snake'
        ");
        $snakeStmt->execute();
    } else {
        $snakeStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT user_id) as unique_players,
                MAX(score) as max_score
            FROM tbl_user_scores 
            WHERE game = 'snake' AND season = ?
        ");
        $snakeStmt->bindValue(1, $season);
        $snakeStmt->execute();
    }
    
    $snakeData = $snakeStmt->fetch(PDO::FETCH_ASSOC);
    $data['games']['snake'] = $snakeData;
    $data['total_scores'] += $snakeData['total_scores'];
    
    // Get Space Invaders data for the season
    if ($season === 'all') {
        $spaceStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT user_id) as unique_players,
                MAX(score) as max_score
            FROM tbl_user_scores 
            WHERE game = 'space_invaders'
        ");
        $spaceStmt->execute();
    } else {
        $spaceStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT user_id) as unique_players,
                MAX(score) as max_score
            FROM tbl_user_scores 
            WHERE game = 'space_invaders' AND season = ?
        ");
        $spaceStmt->bindValue(1, $season);
        $spaceStmt->execute();
    }
    
    $spaceData = $spaceStmt->fetch(PDO::FETCH_ASSOC);
    $data['games']['space_invaders'] = $spaceData;
    $data['total_scores'] += $spaceData['total_scores'];
    
    // Get Cheese Hunt data for the season
    if ($season === 'all') {
        $cheeseStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT user_wallet) as unique_players,
                MAX(cheese_count) as max_score
            FROM tbl_cheese_clicks
        ");
        $cheeseStmt->execute();
    } else {
        $cheeseStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT user_wallet) as unique_players,
                MAX(cheese_count) as max_score
            FROM tbl_cheese_clicks 
            WHERE season = ?
        ");
        $cheeseStmt->bindValue(1, $season);
        $cheeseStmt->execute();
    }
    
    $cheeseData = $cheeseStmt->fetch(PDO::FETCH_ASSOC);
    $data['games']['cheese_hunt'] = $cheeseData;
    $data['total_scores'] += $cheeseData['total_scores'];
    
    // Get Discord Race data for the season
    if ($season === 'all') {
        $raceStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT user_id) as unique_players,
                MAX(cheese_count) as max_score
            FROM tbl_race_participants
        ");
        $raceStmt->execute();
    } else {
        $raceStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_scores,
                COUNT(DISTINCT user_id) as unique_players,
                MAX(cheese_count) as max_score
            FROM tbl_race_participants 
            WHERE season = ?
        ");
        $raceStmt->bindValue(1, $season);
        $raceStmt->execute();
    }
    
    $raceData = $raceStmt->fetch(PDO::FETCH_ASSOC);
    $data['games']['discord_race'] = $raceData;
    $data['total_scores'] += $raceData['total_scores'];
    
    // Calculate unique players across all games
    $uniquePlayers = array_unique(array_merge(
        array_column($data['games']['tetris'], 'unique_players'),
        array_column($data['games']['snake'], 'unique_players'),
        array_column($data['games']['space_invaders'], 'unique_players'),
        array_column($data['games']['cheese_hunt'], 'unique_players'),
        array_column($data['games']['discord_race'], 'unique_players')
    ));
    
    $data['unique_players'] = array_sum($uniquePlayers);
    $data['max_score'] = max(array_column($data['games'], 'max_score'));
    
    echo json_encode([
        'success' => true,
        'season' => $season,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
