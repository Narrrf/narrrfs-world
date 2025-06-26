<?php
session_start();
header('Content-Type: application/json');

// Get user_id from session or GET, sanitized
$user_id = $_SESSION['discord_id'] ?? ($_GET['user_id'] ?? '');
if (!$user_id) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$db = new SQLite3(__DIR__ . '/../../db/narrrf_world.sqlite');

// Prepare query to avoid SQL injection (bind user_id as param)
$stmt = $db->prepare("
    SELECT q.quest_id, q.type, q.description, q.link, q.reward, q.expires_at, q.is_active,
           qc.status AS claim_status, qc.claimed_at
    FROM tbl_quests q
    LEFT JOIN tbl_quest_claims qc ON qc.quest_id = q.quest_id AND qc.user_id = :user_id
    WHERE q.is_active = 1
    ORDER BY q.created_at DESC
");
$stmt->bindValue(':user_id', $user_id, SQLITE3_TEXT);

$result = $stmt->execute();

$quests = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $quests[] = $row;
}

echo json_encode(['quests' => $quests]);
$db->close();
?>
