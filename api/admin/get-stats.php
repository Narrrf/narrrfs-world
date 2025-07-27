<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Connect to database - Render production path
    $db = new SQLite3('/var/www/html/db/narrrf_world.sqlite');
    $db->enableExceptions(true);

    // Get total users
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_users');
    $result = $stmt->execute();
    $totalUsers = $result->fetchArray(SQLITE3_ASSOC)['count'];

    // Get total score records
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_user_scores');
    $result = $stmt->execute();
    $totalScores = $result->fetchArray(SQLITE3_ASSOC)['count'];

    // Get total store items
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_store_items WHERE is_active = 1');
    $result = $stmt->execute();
    $totalItems = $result->fetchArray(SQLITE3_ASSOC)['count'];

    // Get active quests
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_quests WHERE is_active = 1');
    $result = $stmt->execute();
    $activeQuests = $result->fetchArray(SQLITE3_ASSOC)['count'];

    echo json_encode([
        'success' => true,
        'totalUsers' => $totalUsers,
        'totalScores' => $totalScores,
        'totalItems' => $totalItems,
        'activeQuests' => $activeQuests
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 