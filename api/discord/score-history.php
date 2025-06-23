<?php
// /api/discord/score-history.php
header('Content-Type: application/json');

$uid = $_GET['user_id'] ?? '';
if (!$uid) {
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

try {
    $db = new SQLite3(__DIR__ . '/../../db/narrrf_world.sqlite');
    // Use a prepared statement for security (always council best practice)
    $stmt = $db->prepare("SELECT timestamp, amount FROM tbl_score_adjustments WHERE user_id = ? ORDER BY timestamp ASC");
    $stmt->bindValue(1, $uid, SQLITE3_TEXT);
    $res = $stmt->execute();
    $data = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) $data[] = $row;
    echo json_encode(['history' => $data]);
    $db->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
