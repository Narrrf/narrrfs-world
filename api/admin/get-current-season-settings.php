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
    // Get current season settings
    $stmt = $db->prepare("SELECT * FROM tbl_season_settings WHERE season_name = ?");
    $stmt->execute(['season_1']);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        // Create default settings if none exist (1:1 ratio for tetris, 10:1 ratio for snake)
        $db->exec("INSERT INTO tbl_season_settings (season_name, tetris_max_score, snake_max_score, points_per_line, points_per_cheese) VALUES ('season_1', 10000, 10000, 1, 10)");
        
        $settings = [
            'tetris_max_score' => 10000,
            'snake_max_score' => 10000,
            'points_per_line' => 1,
            'points_per_cheese' => 10
        ];
    }
    
    echo json_encode([
        'success' => true,
        'tetris_max_score' => $settings['tetris_max_score'] ?? 10000,
        'snake_max_score' => $settings['snake_max_score'] ?? 10000,
        'points_per_line' => $settings['points_per_line'] ?? 1,
        'points_per_cheese' => $settings['points_per_cheese'] ?? 10,
        'season_name' => $settings['season_name'] ?? 'season_1'
    ]);
    
} catch (Exception $e) {
    error_log("Error getting season settings: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get season settings: ' . $e->getMessage()
    ]);
}
?>
