<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');

$user_id = $_SESSION['discord_id'] ?? '';
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$dbPath = '/var/www/html/db/narrrf_world.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT 
            a.*,
            u1.username as username,
            u2.username as admin_name
        FROM tbl_score_adjustments a
        LEFT JOIN tbl_users u1 ON a.user_id = u1.discord_id
        LEFT JOIN tbl_users u2 ON a.admin_id = u2.discord_id
        WHERE a.user_id = ?
        ORDER BY a.timestamp DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'adjustments' => $adjustments]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch adjustments: ' . $e->getMessage()]);
}
