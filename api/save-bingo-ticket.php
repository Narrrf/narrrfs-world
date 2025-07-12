<?php
session_start();
require_once '../db.php'; // adjust if your DB file is in a different path

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

$ticket_json = json_encode($data['ticket']);

$pdo = new PDO("sqlite:/var/www/html/db/narrrf_world.sqlite");
$stmt = $pdo->prepare("INSERT INTO tbl_bingo_tickets (user_id, ticket_json) VALUES (?, ?)");
$stmt->execute([$user_id, $ticket_json]);

echo "Ticket saved successfully!";
