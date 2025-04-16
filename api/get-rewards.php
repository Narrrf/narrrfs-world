<?php
session_start();

if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '❌ Not logged in']);
    exit;
}

$userId = $_SESSION['discord_id'];
$dbPath = 'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite';

try {
    $pdo = new PDO("sqlite:$dbPath");

    // 🧠 Step 1: Fetch all user's traits
    $stmt = $pdo->prepare("SELECT trait FROM tbl_user_traits WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userTraits = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$userTraits) $userTraits = [];

    // 🧠 Step 2: Match traits against rewards
    $in = str_repeat('?,', count($userTraits) - 1) . '?';
    $sql = "SELECT reward_id, reward_name FROM tbl_rewards WHERE unlock_trait IN ($in)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($userTraits);

    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['unlocked_rewards' => $rewards]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '❌ DB error', 'details' => $e->getMessage()]);
}
