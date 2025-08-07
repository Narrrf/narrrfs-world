<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Parse JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    $tetrisMax = $data['tetris_max_score'] ?? null;
    $snakeMax = $data['snake_max_score'] ?? null;
    $pointsPerLine = $data['points_per_line'] ?? null;
    $pointsPerCheese = $data['points_per_cheese'] ?? null;
    
    if (!$tetrisMax || !$snakeMax || !$pointsPerLine || !$pointsPerCheese) {
        throw new Exception('Missing required fields');
    }
    
    // Validate ranges
    if ($tetrisMax < 100 || $tetrisMax > 100000) {
        throw new Exception('Tetris max score must be between 100 and 100000');
    }
    if ($snakeMax < 100 || $snakeMax > 100000) {
        throw new Exception('Snake max score must be between 100 and 100000');
    }
    if ($pointsPerLine < 1 || $pointsPerLine > 100) {
        throw new Exception('Points per line must be between 1 and 100');
    }
    if ($pointsPerCheese < 1 || $pointsPerCheese > 100) {
        throw new Exception('Points per cheese must be between 1 and 100');
    }
    
    // Update season settings
    $stmt = $db->prepare("
        INSERT OR REPLACE INTO tbl_season_settings 
        (season_name, tetris_max_score, snake_max_score, points_per_line, points_per_cheese) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute(['season_1', $tetrisMax, $snakeMax, $pointsPerLine, $pointsPerCheese]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Season settings updated successfully',
        'settings' => [
            'tetris_max_score' => $tetrisMax,
            'snake_max_score' => $snakeMax,
            'points_per_line' => $pointsPerLine,
            'points_per_cheese' => $pointsPerCheese
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error updating season settings: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update season settings: ' . $e->getMessage()
    ]);
}
?>
