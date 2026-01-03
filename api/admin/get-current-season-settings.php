<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Get current active season from tbl_seasons
    $stmt = $db->prepare("SELECT season_name FROM tbl_seasons WHERE is_active = 1 ORDER BY season_id DESC LIMIT 1");
    $stmt->execute();
    $activeSeason = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activeSeason) {
        // Fallback to Season 7 if no active season found
        $currentSeason = 'Season 7';
    } else {
        $currentSeason = $activeSeason['season_name'];
    }
    
    // Get current season settings
    $stmt = $db->prepare("SELECT * FROM tbl_season_settings WHERE season_name = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$currentSeason]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        // Create default settings for current season if none exist
        $insertStmt = $db->prepare("INSERT INTO tbl_season_settings (season_name, tetris_max_score, snake_max_score, space_invaders_max_score, points_per_line, points_per_cheese, points_per_invader) VALUES (?, 10000, 10000, 10000, 10, 10, 0.01)");
        $insertStmt->execute([$currentSeason]);
        
        $settings = [
            'season_name' => $currentSeason,
            'tetris_max_score' => 10000,
            'snake_max_score' => 10000,
            'space_invaders_max_score' => 10000,
            'points_per_line' => 10,
            'points_per_cheese' => 10,
            'points_per_invader' => 0.01
        ];
    }
    
    // Get season info from tbl_seasons
    $stmt = $db->prepare("SELECT season_name, start_date, end_date FROM tbl_seasons WHERE season_name = ? ORDER BY season_id DESC LIMIT 1");
    $stmt->execute([$currentSeason]);
    $seasonInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'season_name' => $settings['season_name'] ?? $currentSeason,
        'season_display_name' => $seasonInfo['season_name'] ?? $currentSeason,
        'start_date' => $seasonInfo['start_date'] ?? null,
        'end_date' => $seasonInfo['end_date'] ?? null,
        'tetris_max_score' => $settings['tetris_max_score'] ?? 10000,
        'snake_max_score' => $settings['snake_max_score'] ?? 10000,
        'space_invaders_max_score' => $settings['space_invaders_max_score'] ?? 10000,
        'points_per_line' => $settings['points_per_line'] ?? 10,
        'points_per_cheese' => $settings['points_per_cheese'] ?? 10,
        'points_per_invader' => $settings['points_per_invader'] ?? 0.01
    ]);
    
} catch (Exception $e) {
    error_log("Error getting season settings: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get season settings: ' . $e->getMessage()
    ]);
}
?>
