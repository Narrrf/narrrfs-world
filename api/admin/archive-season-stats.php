<?php
/**
 * Archive Season Stats API
 * 
 * CRITICAL: Run this BEFORE each season reset!
 * Archives all game stats to historical tables for all-time tracking
 * 
 * Usage: Call this endpoint before running season reset
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../db/narrrf_world.sqlite' 
        : '/var/www/html/db/narrrf_world.sqlite';
    
    if (!file_exists($dbPath)) {
        return null;
    }
    
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

$db = getSQLite3Connection();

if (!$db) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

try {
    $db->beginTransaction();
    
    // Get current active season info
    $seasonStmt = $db->query("SELECT season_name, start_date, end_date FROM tbl_seasons WHERE is_active = 1 LIMIT 1");
    $currentSeason = $seasonStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentSeason) {
        throw new Exception('No active season found');
    }
    
    $seasonName = $currentSeason['season_name'];
    $seasonStart = $currentSeason['start_date'];
    $seasonEnd = $currentSeason['end_date'];
    
    // Archive game stats from tbl_tetris_scores (Tetris, Snake, Space Invaders)
    $archiveGamesStmt = $db->prepare("
        INSERT OR REPLACE INTO tbl_historical_stats 
            (discord_id, game, season, total_games, best_score, total_score, avg_score, season_start_date, season_end_date)
        SELECT 
            discord_id,
            game,
            :season as season,
            COUNT(*) as total_games,
            MAX(score) as best_score,
            SUM(score) as total_score,
            AVG(score) as avg_score,
            :season_start as season_start_date,
            :season_end as season_end_date
        FROM tbl_tetris_scores
        WHERE game IN ('tetris', 'snake', 'space_invaders')
        GROUP BY discord_id, game
    ");
    
    $archiveGamesStmt->execute([
        ':season' => $seasonName,
        ':season_start' => $seasonStart,
        ':season_end' => $seasonEnd
    ]);
    
    $gamesArchived = $archiveGamesStmt->rowCount();
    
    // Archive Cheese Hunt stats from tbl_cheese_clicks
    $archiveCheeseStmt = $db->prepare("
        INSERT OR REPLACE INTO tbl_historical_cheese_stats 
            (user_wallet, season, total_clicks, quest_clicks, days_played, season_start_date, season_end_date)
        SELECT 
            user_wallet,
            :season as season,
            COUNT(*) as total_clicks,
            COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
            COUNT(DISTINCT DATE(timestamp)) as days_played,
            :season_start as season_start_date,
            :season_end as season_end_date
        FROM tbl_cheese_clicks
        GROUP BY user_wallet
    ");
    
    $archiveCheeseStmt->execute([
        ':season' => $seasonName,
        ':season_start' => $seasonStart,
        ':season_end' => $seasonEnd
    ]);
    
    $cheeseArchived = $archiveCheeseStmt->rowCount();
    
    // Note: tbl_race_participants is NEVER deleted, so no need to archive
    // Note: Achievement tables are NEVER deleted, so no need to archive
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Season stats archived successfully',
        'season_archived' => $seasonName,
        'stats' => [
            'games_archived' => $gamesArchived,
            'cheese_users_archived' => $cheeseArchived
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to archive season stats: ' . $e->getMessage()
    ]);
}
?>

