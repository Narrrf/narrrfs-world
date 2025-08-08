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
    $spaceInvadersMax = $data['space_invaders_max_score'] ?? null;
    $pointsPerLine = $data['points_per_line'] ?? null;
    $pointsPerCheese = $data['points_per_cheese'] ?? null;
    $pointsPerInvader = $data['points_per_invader'] ?? null;
    
    if (!$tetrisMax || !$snakeMax || !$spaceInvadersMax || !$pointsPerLine || !$pointsPerCheese || !$pointsPerInvader) {
        throw new Exception('Missing required fields');
    }
    
    // Validate ranges
    if ($tetrisMax < 100 || $tetrisMax > 100000) {
        throw new Exception('Tetris max score must be between 100 and 100000');
    }
    if ($snakeMax < 100 || $snakeMax > 100000) {
        throw new Exception('Snake max score must be between 100 and 100000');
    }
    if ($spaceInvadersMax < 100 || $spaceInvadersMax > 100000) {
        throw new Exception('Space Invaders max score must be between 100 and 100000');
    }
    if ($pointsPerLine < 1 || $pointsPerLine > 100) {
        throw new Exception('Points per line must be between 1 and 100');
    }
    if ($pointsPerCheese < 1 || $pointsPerCheese > 100) {
        throw new Exception('Points per cheese must be between 1 and 100');
    }
    if ($pointsPerInvader < 0.001 || $pointsPerInvader > 1) {
        throw new Exception('Points per invader must be between 0.001 and 1');
    }
    
    // Update season settings
    $stmt = $db->prepare("
        INSERT OR REPLACE INTO tbl_season_settings 
        (season_name, tetris_max_score, snake_max_score, space_invaders_max_score, points_per_line, points_per_cheese, points_per_invader) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['season_1', $tetrisMax, $snakeMax, $spaceInvadersMax, $pointsPerLine, $pointsPerCheese, $pointsPerInvader]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Season settings updated successfully',
        'settings' => [
            'tetris_max_score' => $tetrisMax,
            'snake_max_score' => $snakeMax,
            'space_invaders_max_score' => $spaceInvadersMax,
            'points_per_line' => $pointsPerLine,
            'points_per_cheese' => $pointsPerCheese,
            'points_per_invader' => $pointsPerInvader
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
