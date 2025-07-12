<?php
session_start();
require_once __DIR__ . '/../db/db.php';

if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo "Unauthorized: Please log in with Discord.";
    exit;
}

$user_id = $_SESSION['discord_id'];

try {
    $pdo = new PDO("sqlite:/var/www/html/db/narrrf_world.sqlite");
    $stmt = $pdo->prepare("SELECT ticket_json FROM tbl_bingo_tickets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_COLUMN);

    header('Content-Type: application/json');
    echo json_encode($tickets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    echo "Database error: " . htmlspecialchars($e->getMessage());
}