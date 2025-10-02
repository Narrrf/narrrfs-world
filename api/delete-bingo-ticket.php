<?php
session_start();
header('Content-Type: application/json');

// 🧀 Environment detection for local vs production
$isLocalDevelopment = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';

if (!isset($_SESSION['discord_id'])) {
    // 🔧 LOCAL DEVELOPMENT BYPASS - Use Narrrf's ID for testing
    if ($isLocalDevelopment) {
        $_SESSION['discord_id'] = '328601656659017732'; // Narrrf's Discord ID
        $_SESSION['user'] = [
            'username' => 'Narrrf',
            'discriminator' => '0000',
            'avatar' => null,
            'email' => null
        ];
        error_log('🏠 Local development: Bypassing authentication for Bingo Delete API - using Narrrf\'s ID');
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['ticket_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing ticket_id']);
    exit;
}

try {
    // 🧀 Environment-aware database path for local and production
    $productionPath = '/var/www/html/db/narrrf_world.sqlite';
    $localPath = 'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite';
    
    // Check if we're in local development by checking if localhost is in the host
    $isLocalDev = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';
    
    $dbPath = $isLocalDev ? $localPath : $productionPath;
        
    error_log("🏠 Environment: " . ($isLocalDev ? 'LOCAL' : 'PRODUCTION') . ", Database path selected: $dbPath (exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . ")");
    
    $pdo = new PDO("sqlite:$dbPath");
    $stmt = $pdo->prepare("DELETE FROM tbl_bingo_tickets WHERE user_id = ? AND ticket_json LIKE ?");
    $stmt->execute([$_SESSION['discord_id'], "%{$data['ticket_id']}%"]);

    echo json_encode(['success' => true, 'message' => 'Ticket deleted']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error']);
}
