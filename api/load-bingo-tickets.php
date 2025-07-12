<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo "Unauthorized: Please log in with Discord.";
    exit;
}

$user_id = $_SESSION['discord_id'];

$pdo = new PDO("sqlite:/var/www/html/db/narrrf_world.sqlite");
$stmt = $pdo->prepare("SELECT ticket_json FROM tbl_bingo_tickets WHERE user_id = ?");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_COLUMN);

header('Content-Type: application/json');
echo json_encode($tickets);
