<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Connect to database - Render production path
    $db = new SQLite3('/var/www/html/db/narrrf_world.sqlite');
    $db->enableExceptions(true);

    // Get active quests
    $stmt = $db->prepare('
        SELECT quest_id, type, description, reward, link, created_by, created_at, is_active, role_id
        FROM tbl_quests 
        WHERE is_active = 1
        ORDER BY created_at DESC
    ');
    $result = $stmt->execute();

    $quests = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $quests[] = $row;
    }

    echo json_encode([
        'success' => true,
        'quests' => $quests
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 