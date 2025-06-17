<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/discord.php';

// Verify bot token
$headers = getallheaders();
if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bot ' . DISCORD_BOT_SECRET) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get user ID from query params
if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id parameter']);
    exit;
}

$user_id = $_GET['user_id'];

// Connect to database - use production path on Render
$dbPath = '/data/narrrf_world.sqlite';
if (!file_exists($dbPath)) {
    // Fallback to development path
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Log database path for debugging
    error_log("Using database at: " . $dbPath);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage() . " (Path: $dbPath)");
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    // Get user's total DSPOINC - using same query as score-total.php
    $stmt = $db->prepare("SELECT SUM(score) AS total_dspoinc FROM tbl_user_scores WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get username
    $stmt = $db->prepare("SELECT username FROM tbl_users WHERE discord_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log query results for debugging
    error_log("Query results for user $user_id: " . json_encode(['balance' => $result, 'user' => $user]));
    
    if ($user) {
        echo json_encode([
            'success' => true,
            'balance' => (int)($result['total_dspoinc'] ?? 0),
            'username' => $user['username']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
    }
} catch (PDOException $e) {
    error_log("Query error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed']);
    exit;
}
?> 