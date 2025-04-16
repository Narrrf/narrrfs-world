<?php
session_start();

if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '❌ Not logged in']);
    exit;
}

$discordId = $_SESSION['discord_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['egg_id'])) {
    http_response_code(400);
    echo json_encode(['error' => '❌ Missing egg_id']);
    exit;
}

$eggId = trim($input['egg_id']);
$dbPath = 'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite';

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO tbl_cheese_clicks (user_wallet, egg_id, timestamp)
                           VALUES (?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$discordId, $eggId]);

    echo json_encode(['success' => "🧀 Click on '$eggId' logged for user $discordId"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '❌ DB error', 'details' => $e->getMessage()]);
}
