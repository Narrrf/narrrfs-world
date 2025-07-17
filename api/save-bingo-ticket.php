<?php
// ✅ Ensure session is available across all paths
ini_set('session.cookie_path', '/');
session_start();

header('Content-Type: application/json');

// 🧪 Debug block (uncomment temporarily for testing session)
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
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['ticket'])) {
    http_response_code(400);
    echo json_encode(["error" => "No ticket data received."]);
    exit;
}

// 🎯 Sanitize + encode ticket cleanly
$ticket = $data['ticket'];
$ticket_json = json_encode($ticket, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

try {
    $pdo = new PDO("sqlite:/var/www/html/db/narrrf_world.sqlite");
    $stmt = $pdo->prepare("INSERT INTO tbl_bingo_tickets (user_id, ticket_json) VALUES (?, ?)");
    $stmt->execute([$user_id, $ticket_json]);

    echo json_encode(["success" => true, "message" => "Ticket saved successfully!"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
