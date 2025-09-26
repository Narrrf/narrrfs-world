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

    // Get current active season
    $seasonStmt = $pdo->prepare("SELECT season_name FROM tbl_seasons WHERE is_active = 1 AND end_date IS NULL ORDER BY start_date DESC LIMIT 1");
    $seasonStmt->execute();
    $currentSeason = $seasonStmt->fetchColumn() ?: 'Season 3 - The Ultimate Cheese Challenge';

    $stmt = $pdo->prepare("INSERT INTO tbl_cheese_clicks (user_wallet, egg_id, timestamp, season)
                           VALUES (?, ?, CURRENT_TIMESTAMP, ?)");
    $stmt->execute([$discordId, $eggId, $currentSeason]);

    echo json_encode(['success' => "🧀 Click on '$eggId' logged for user $discordId"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '❌ DB error', 'details' => $e->getMessage()]);
}
