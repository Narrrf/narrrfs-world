<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Connect to database - Render production path
    $db = new SQLite3('/var/www/html/db/narrrf_world.sqlite');
    $db->enableExceptions(true);

    // Get recent adjustments (last 10)
    $stmt = $db->prepare('
        SELECT user_id, admin_id, amount, action, reason, timestamp 
        FROM tbl_score_adjustments 
        ORDER BY timestamp DESC 
        LIMIT 10
    ');
    $result = $stmt->execute();

    $adjustments = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $adjustments[] = $row;
    }

    echo json_encode([
        'success' => true,
        'adjustments' => $adjustments
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 