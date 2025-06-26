<?php
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['discord_id'] ?? $_POST['user_id'] ?? '';
$quest_id = $_POST['quest_id'] ?? '';
if (!$user_id || !$quest_id) {
    echo json_encode(['error' => 'Missing user or quest']);
    exit;
}
$db = new SQLite3(__DIR__ . '/../../db/narrrf_world.sqlite');
// Prevent double-claim
$stmt = $db->prepare("SELECT 1 FROM tbl_quest_claims WHERE quest_id = ? AND user_id = ?");
$stmt->bindValue(1, $quest_id, SQLITE3_TEXT);
$stmt->bindValue(2, $user_id, SQLITE3_TEXT);
if ($stmt->execute()->fetchArray()) {
    echo json_encode(['error' => 'Already claimed']);
    exit;
}
$stmt = $db->prepare("INSERT INTO tbl_quest_claims (quest_id, user_id, claimed_at, status) VALUES (?, ?, ?, 'pending')");
$stmt->bindValue(1, $quest_id, SQLITE3_TEXT);
$stmt->bindValue(2, $user_id, SQLITE3_TEXT);
$stmt->bindValue(3, date('Y-m-d H:i:s'), SQLITE3_TEXT);
$stmt->execute();
echo json_encode(['success' => true]);
?>
