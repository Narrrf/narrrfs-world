<?php
/**
 * Backfill Historical Stats from Season 3 Backup
 * 
 * This script imports historical stats from backup databases
 * Run this ONCE to populate historical data from previous seasons
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
    
    // Attach Season 3 backup database
    $backupPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? __DIR__ . '/../../db/narrrf_world_season3_backup.sqlite' 
        : '/var/www/html/db/narrrf_world_season3_backup.sqlite';
    
    $realPath = realpath($backupPath);
    
    if (!$realPath || !file_exists($realPath)) {
        // Try alternative path
        $backupPath = __DIR__ . '/../../db/narrrf_world_season3_backup.sqlite';
        $realPath = realpath($backupPath);
        
        if (!$realPath || !file_exists($realPath)) {
            throw new Exception('Season 3 backup database not found. Tried: ' . $backupPath);
        }
    }
    
    $backupPath = $realPath;
    
    // Attach the backup database
    $db->exec("ATTACH DATABASE '" . $backupPath . "' AS season3");
    
    // Import Tetris stats from Season 3
    $tetrisImport = $db->exec("
        INSERT OR IGNORE INTO tbl_historical_stats 
            (discord_id, game, season, total_games, best_score, total_score, avg_score, season_start_date, season_end_date)
        SELECT 
            discord_id,
            'tetris' as game,
            'Season 3' as season,
            COUNT(*) as total_games,
            MAX(score) as best_score,
            SUM(score) as total_score,
            AVG(score) as avg_score,
            MIN(timestamp) as season_start_date,
            MAX(timestamp) as season_end_date
        FROM season3.tbl_tetris_scores
        WHERE game = 'tetris'
        GROUP BY discord_id
    ");
    
    // Import Snake stats from Season 3
    $snakeImport = $db->exec("
        INSERT OR IGNORE INTO tbl_historical_stats 
            (discord_id, game, season, total_games, best_score, total_score, avg_score, season_start_date, season_end_date)
        SELECT 
            discord_id,
            'snake' as game,
            'Season 3' as season,
            COUNT(*) as total_games,
            MAX(score) as best_score,
            SUM(score) as total_score,
            AVG(score) as avg_score,
            MIN(timestamp) as season_start_date,
            MAX(timestamp) as season_end_date
        FROM season3.tbl_tetris_scores
        WHERE game = 'snake'
        GROUP BY discord_id
    ");
    
    // Import Space Invaders stats from Season 3
    $spaceImport = $db->exec("
        INSERT OR IGNORE INTO tbl_historical_stats 
            (discord_id, game, season, total_games, best_score, total_score, avg_score, season_start_date, season_end_date)
        SELECT 
            discord_id,
            'space_invaders' as game,
            'Season 3' as season,
            COUNT(*) as total_games,
            MAX(score) as best_score,
            SUM(score) as total_score,
            AVG(score) as avg_score,
            MIN(timestamp) as season_start_date,
            MAX(timestamp) as season_end_date
        FROM season3.tbl_tetris_scores
        WHERE game = 'space_invaders'
        GROUP BY discord_id
    ");
    
    // Detach the backup database
    $db->exec("DETACH DATABASE season3");
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Historical stats backfilled successfully from Season 3',
        'stats' => [
            'tetris_players_imported' => $tetrisImport,
            'snake_players_imported' => $snakeImport,
            'space_invaders_players_imported' => $spaceImport
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to backfill historical stats: ' . $e->getMessage()
    ]);
}
?>

