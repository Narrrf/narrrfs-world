<?php
header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? '';
if (!$user_id) {
    echo json_encode(['history' => []]);
    exit;
}

try {
    $db = new SQLite3(__DIR__ . '/../../db/narrrf_world.sqlite');
    $history = [];

    // Fetch all score adjustments for the user, ordered by time
    $stmt = $db->prepare("SELECT timestamp, amount, reason FROM tbl_score_adjustments WHERE user_id = ? ORDER BY timestamp ASC");
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $res = $stmt->execute();

    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $history[] = [
            'timestamp' => $row['timestamp'],
            'amount'    => (int)$row['amount'],
            'reason'    => $row['reason'] ?? ''
        ];
    }
    echo json_encode(['history' => $history]);
    $db->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
