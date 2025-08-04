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

// Check for existing claim and its status
$stmt = $db->prepare("SELECT status FROM tbl_quest_claims WHERE quest_id = ? AND user_id = ?");
$stmt->bindValue(1, $quest_id, SQLITE3_TEXT);
$stmt->bindValue(2, $user_id, SQLITE3_TEXT);
$result = $stmt->execute();
$existing_claim = $result->fetchArray(SQLITE3_ASSOC);

if ($existing_claim) {
    if ($existing_claim['status'] === 'pending') {
        echo json_encode(['error' => 'Already claimed - pending review']);
        exit;
    } elseif ($existing_claim['status'] === 'approved') {
        echo json_encode(['error' => 'Already claimed and approved']);
        exit;
    } elseif ($existing_claim['status'] === 'rejected') {
        // Allow retry for rejected claims - update the existing record
        $stmt = $db->prepare("UPDATE tbl_quest_claims SET 
                                status = 'pending', 
                                claimed_at = ?, 
                                proof = NULL, 
                                reviewed_at = NULL 
                              WHERE quest_id = ? AND user_id = ?");
        $stmt->bindValue(1, date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(2, $quest_id, SQLITE3_TEXT);
        $stmt->bindValue(3, $user_id, SQLITE3_TEXT);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Quest retry submitted for review']);
        exit;
    }
}

// Create new claim if no existing claim
$stmt = $db->prepare("INSERT INTO tbl_quest_claims (quest_id, user_id, claimed_at, status) VALUES (?, ?, ?, 'pending')");
$stmt->bindValue(1, $quest_id, SQLITE3_TEXT);
$stmt->bindValue(2, $user_id, SQLITE3_TEXT);
$stmt->bindValue(3, date('Y-m-d H:i:s'), SQLITE3_TEXT);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Quest claimed successfully']);
?>
