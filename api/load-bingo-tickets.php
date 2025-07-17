<?php
// ✅ Ensure session cookie is valid across all paths
ini_set('session.cookie_path', '/');
session_start();

header('Content-Type: application/json');

// 🧪 Debug mode — enable this block temporarily to verify session behavior
/*
echo json_encode([
  'session_id' => session_id(),
  'discord_id' => $_SESSION['discord_id'] ?? 'not set',
  'cookie_test' => $_COOKIE
]);
exit;
*/

if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized: Please log in with Discord."]);
    exit;
}

$user_id = $_SESSION['discord_id'];

try {
    $pdo = new PDO("sqlite:/var/www/html/db/narrrf_world.sqlite");
    $stmt = $pdo->prepare("SELECT ticket_json FROM tbl_bingo_tickets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($tickets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
