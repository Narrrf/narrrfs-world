<?php
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['discord_id'] ?? $_GET['user_id'] ?? '';
if (!$user_id) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$db = new SQLite3(__DIR__ . '/../../db/narrrf_world.sqlite');

// Get ALL active (and expired/closed if you want) quests
$quests = [];
$q = $db->query("
    SELECT q.quest_id, q.type, q.description, q.link, q.reward, q.expires_at, q.is_active,
           qc.status as claim_status, qc.claimed_at
    FROM tbl_quests q
    LEFT JOIN tbl_quest_claims qc ON qc.quest_id = q.quest_id AND qc.user_id = '$user_id'
    ORDER BY q.created_at DESC
");
while ($row = $q->fetchArray(SQLITE3_ASSOC)) {
    $quests[] = $row;
}

echo json_encode(['quests' => $quests]);
$db->close();
?>
