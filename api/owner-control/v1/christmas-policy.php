<?php
/** Owner-only read overview for Christmas Advent 2026 policy publication & item eligibility. */
declare(strict_types=1);

define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.christmas_policy.read.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/christmas-advent-2026-policy-v1.php';

try {
    $path = getDatabasePath();
    if (!is_file($path) || !is_readable($path)) {
        throw new RuntimeException('db_unavailable');
    }
    $db = new SQLite3($path, SQLITE3_OPEN_READONLY);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA query_only = ON');
    $overview = christmas26_policy_overview($db);
    $db->close();
    echo json_encode(['success' => true, 'result' => $overview], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable) {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
    http_response_code(503);
    echo '{"success":false,"error":"policy_overview_unavailable"}';
}
