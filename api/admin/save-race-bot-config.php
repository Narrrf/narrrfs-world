<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON input'
        ]);
        exit;
    }
    
    // Validate required fields
    $requiredFields = ['maxPlayers', 'duration', 'dspoincReward'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            echo json_encode([
                'success' => false,
                'error' => "Missing required field: $field"
            ]);
            exit;
        }
    }
    
    $pdo = getDatabaseConnection();
    
    // Check if config table exists, if not create it
    $stmt = $pdo->prepare("
        CREATE TABLE IF NOT EXISTS tbl_race_bot_config (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            config_key TEXT UNIQUE NOT NULL,
            config_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $stmt->execute();
    
    // Save configuration values
    $configs = [
        'max_players' => $input['maxPlayers'],
        'duration' => $input['duration'],
        'dspoinc_reward' => $input['dspoincReward'],
        'role_reward' => $input['roleReward'] ?? ''
    ];
    
    foreach ($configs as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT OR REPLACE INTO tbl_race_bot_config (config_key, config_value, updated_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$key, $value]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Race bot configuration saved successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
