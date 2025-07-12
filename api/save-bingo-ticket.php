<?php
session_start();

if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo "Unauthorized: Please log in with Discord.";
    exit;
}

$user_id = $_SESSION['discord_id'];
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['ticket'])) {
    http_response_code(400);
    echo "No ticket data received.";
    exit;
}

// Extra sanitize ticket before saving
$ticket = $data['ticket'];
$ticket_json = json_encode($ticket, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

try {
    $pdo = new PDO("sqlite:/var/www/html/db/narrrf_world.sqlite");
    $stmt = $pdo->prepare("INSERT INTO tbl_bingo_tickets (user_id, ticket_json) VALUES (?, ?)");
    $stmt->execute([$user_id, $ticket_json]);
    echo "Ticket saved successfully!";
} catch (Exception $e) {
    http_response_code(500);
    echo "Database error: " . htmlspecialchars($e->getMessage());
}
