<?php
session_start();

if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['ticket_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing ticket_id']);
    exit;
}

try {
    $pdo = new PDO("sqlite:/var/www/html/db/narrrf_world.sqlite");
    $stmt = $pdo->prepare("DELETE FROM tbl_bingo_tickets WHERE user_id = ? AND ticket_json LIKE ?");
    $stmt->execute([$_SESSION['discord_id'], "%{$data['ticket_id']}%"]);

    echo json_encode(['success' => true, 'message' => 'Ticket deleted']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error']);
}
