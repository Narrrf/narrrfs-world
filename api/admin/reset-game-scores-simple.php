<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Database path - use production path for Render
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
    if (!file_exists($dbPath)) {
        $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    }

    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }

    // Simple admin check
    if (!isset($input['admin_username']) || $input['admin_username'] !== 'narrrf') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid admin username']);
        exit;
    }

    // Get reset parameters
    $game_type = $input['game_type'] ?? 'all';
    $reset_type = $input['reset_type'] ?? 'season';

    // Simple response for testing
    echo json_encode([
        'success' => true,
        'message' => 'Simple reset endpoint working',
        'game_type' => $game_type,
        'reset_type' => $reset_type,
        'timestamp' => date('Y-m-d H:i:s'),
        'test_mode' => true
    ]);

} catch (Exception $e) {
    error_log("Simple reset error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 