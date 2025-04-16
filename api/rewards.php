<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '❌ Only POST allowed']);
    exit;
}

// 🔐 Optional admin lock could be added here
$input = json_decode(file_get_contents('php://input'), true);
$required = ['reward_id', 'reward_name', 'unlock_trait'];

foreach ($required as $key) {
    if (!isset($input[$key])) {
        http_response_code(400);
        echo json_encode(['error' => "❌ Missing: $key"]);
        exit;
    }
}

$dbPath = 'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite';
try {
    $pdo = new PDO("sqlite:$dbPath");
    $stmt = $pdo->prepare("
        INSERT OR REPLACE INTO tbl_rewards (reward_id, reward_name, unlock_trait, created_at)
        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $input['reward_id'],
        $input['reward_name'],
        $input['unlock_trait']
    ]);

    echo json_encode(['success' => "✅ Reward '{$input['reward_name']}' created."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '❌ DB error', 'details' => $e->getMessage()]);
}
