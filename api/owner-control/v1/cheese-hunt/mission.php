<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/admin-authorization.php';

$actor = narrrfs_require_admin_request('owner_control.cheese_hunt.mission.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$db = null;

try {
    $in = json_decode((string)file_get_contents('php://input'), true, 12, JSON_THROW_ON_ERROR);
    if (!is_array($in)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'invalid_request']);
        exit;
    }

    require_once __DIR__ . '/../../../config/database.php';
    require_once __DIR__ . '/../../../lib/owner-cheese-hunt-v1.php';

    $db = new SQLite3(getDatabasePath(), SQLITE3_OPEN_READWRITE);
    $db->busyTimeout(5000);
    $out = cheese_hunt_mission($db, $actor, $in);
    echo json_encode(['success' => true, 'result' => $out], JSON_UNESCAPED_SLASHES);
} catch (JsonException) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_request']);
} catch (CheeseHuntException $e) {
    http_response_code($e->status);
    echo json_encode(['success' => false, 'error' => $e->publicCode]);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'mission_unavailable']);
} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}
