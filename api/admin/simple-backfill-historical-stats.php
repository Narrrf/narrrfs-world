<?php
/**
 * Simple Backfill Historical Stats
 * 
 * Imports historical stats from Season 3 backup
 * Uses two separate PDO connections to avoid database locking
 */

header('Content-Type: application/json');

// Get database connection
function getConnection($dbPath) {
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

$isLocal = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;

// Main database
$mainDbPath = $isLocal 
    ? __DIR__ . '/../../db/narrrf_world.sqlite' 
    : '/var/www/html/db/narrrf_world.sqlite';

// Season 3 backup
$backupDbPath = $isLocal 
    ? __DIR__ . '/../../db/narrrf_world_season3_backup.sqlite' 
    : '/var/www/html/db/narrrf_world_season3_backup.sqlite';

$mainDb = getConnection($mainDbPath);
$backupDb = getConnection($backupDbPath);

if (!$mainDb) {
    echo json_encode(['success' => false, 'error' => 'Main database connection failed']);
    exit;
}

if (!$backupDb) {
    echo json_encode(['success' => false, 'error' => 'Backup database not found at: ' . $backupDbPath]);
    exit;
}

try {
    $imported = [
        'tetris' => 0,
        'snake' => 0,
        'space_invaders' => 0
    ];
    
    // Import Tetris
    $tetrisData = $backupDb->query("
        SELECT 
            discord_id,
            COUNT(*) as total_games,
            MAX(score) as best_score,
            SUM(score) as total_score,
            AVG(score) as avg_score,
            MIN(timestamp) as season_start,
            MAX(timestamp) as season_end
        FROM tbl_tetris_scores
        WHERE game = 'tetris' AND discord_id IS NOT NULL AND discord_id != ''
        GROUP BY discord_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $insertStmt = $mainDb->prepare("
        INSERT OR IGNORE INTO tbl_historical_stats 
            (discord_id, game, season, total_games, best_score, total_score, avg_score, season_start_date, season_end_date)
        VALUES (:discord_id, 'tetris', 'Season 3', :total_games, :best_score, :total_score, :avg_score, :season_start, :season_end)
    ");
    
    foreach ($tetrisData as $row) {
        $insertStmt->execute([
            ':discord_id' => $row['discord_id'],
            ':total_games' => $row['total_games'],
            ':best_score' => $row['best_score'],
            ':total_score' => $row['total_score'],
            ':avg_score' => $row['avg_score'],
            ':season_start' => $row['season_start'],
            ':season_end' => $row['season_end']
        ]);
        $imported['tetris']++;
    }
    
    // Import Snake
    $snakeData = $backupDb->query("
        SELECT 
            discord_id,
            COUNT(*) as total_games,
            MAX(score) as best_score,
            SUM(score) as total_score,
            AVG(score) as avg_score,
            MIN(timestamp) as season_start,
            MAX(timestamp) as season_end
        FROM tbl_tetris_scores
        WHERE game = 'snake' AND discord_id IS NOT NULL AND discord_id != ''
        GROUP BY discord_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $insertStmt = $mainDb->prepare("
        INSERT OR IGNORE INTO tbl_historical_stats 
            (discord_id, game, season, total_games, best_score, total_score, avg_score, season_start_date, season_end_date)
        VALUES (:discord_id, 'snake', 'Season 3', :total_games, :best_score, :total_score, :avg_score, :season_start, :season_end)
    ");
    
    foreach ($snakeData as $row) {
        $insertStmt->execute([
            ':discord_id' => $row['discord_id'],
            ':total_games' => $row['total_games'],
            ':best_score' => $row['best_score'],
            ':total_score' => $row['total_score'],
            ':avg_score' => $row['avg_score'],
            ':season_start' => $row['season_start'],
            ':season_end' => $row['season_end']
        ]);
        $imported['snake']++;
    }
    
    // Import Space Invaders
    $spaceData = $backupDb->query("
        SELECT 
            discord_id,
            COUNT(*) as total_games,
            MAX(score) as best_score,
            SUM(score) as total_score,
            AVG(score) as avg_score,
            MIN(timestamp) as season_start,
            MAX(timestamp) as season_end
        FROM tbl_tetris_scores
        WHERE game = 'space_invaders' AND discord_id IS NOT NULL AND discord_id != ''
        GROUP BY discord_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $insertStmt = $mainDb->prepare("
        INSERT OR IGNORE INTO tbl_historical_stats 
            (discord_id, game, season, total_games, best_score, total_score, avg_score, season_start_date, season_end_date)
        VALUES (:discord_id, 'space_invaders', 'Season 3', :total_games, :best_score, :total_score, :avg_score, :season_start, :season_end)
    ");
    
    foreach ($spaceData as $row) {
        $insertStmt->execute([
            ':discord_id' => $row['discord_id'],
            ':total_games' => $row['total_games'],
            ':best_score' => $row['best_score'],
            ':total_score' => $row['total_score'],
            ':avg_score' => $row['avg_score'],
            ':season_start' => $row['season_start'],
            ':season_end' => $row['season_end']
        ]);
        $imported['space_invaders']++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Season 3 historical data imported successfully!',
        'imported' => $imported,
        'total_records' => array_sum($imported)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to import historical stats: ' . $e->getMessage()
    ]);
}
?>

