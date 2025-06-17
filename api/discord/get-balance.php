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

// Connect to database
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get user's balance
$stmt = $db->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN action = 'add' THEN amount ELSE -amount END), 0) as balance,
        username
    FROM tbl_score_adjustments 
    JOIN tbl_users ON tbl_users.discord_id = tbl_score_adjustments.user_id
    WHERE user_id = ?
    GROUP BY user_id, username
");

$stmt->execute([$user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo json_encode([
        'success' => true,
        'balance' => (int)$result['balance'],
        'username' => $result['username']
    ]);
} else {
    // User exists but has no transactions
    $stmt = $db->prepare("SELECT username FROM tbl_users WHERE discord_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode([
            'success' => true,
            'balance' => 0,
            'username' => $user['username']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
    }
}
?> 