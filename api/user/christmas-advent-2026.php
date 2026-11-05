<?php
/**
 * Christmas Advent 2026 — authenticated GET-only calendar status.
 *
 * This route intentionally has no POST handler, no payout gate, and no
 * writable database connection. It is not a claim or reward endpoint.
 */
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

require_once __DIR__ . '/../config/admin-session.php';
require_once __DIR__ . '/../lib/christmas-advent-2026.php';

/** Send a compact response without exposing database paths or SQLite details. */
function christmas26_status_json(int $httpCode, array $response): void
{
    http_response_code($httpCode);
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
    exit;
}

if (!christmas26_status_feature_is_enabled(getenv(CHRISTMAS26_STATUS_GATE) ?: null)) {
    christmas26_status_json(404, ['success' => false, 'error' => 'status_unavailable']);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    christmas26_status_json(405, ['success' => false, 'error' => 'method_not_allowed']);
}

if (!narrrfs_session_has_trusted_discord_oauth_provenance($_SESSION)) {
    christmas26_status_json(401, ['success' => false, 'error' => 'authentication_required']);
}
$userId = $_SESSION['discord_id'];

$dbPath = dirname(__DIR__, 2) . '/db/narrrf_world.sqlite';
if (!is_file($dbPath) || !is_readable($dbPath)) {
    error_log('Christmas26 status database unavailable');
    christmas26_status_json(503, ['success' => false, 'error' => 'status_unavailable']);
}

try {
    $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    if (!$db->exec('PRAGMA query_only = ON')) {
        throw new RuntimeException('query_only_unavailable');
    }

    $status = christmas26_status($db, $userId, new DateTimeImmutable('now'));
    $db->close();

    if (!$status['ready']) {
        christmas26_status_json(503, [
            'success' => false,
            'event' => CHRISTMAS26_EVENT_KEY,
            'error' => 'status_unavailable',
            'event_state' => $status['event_state'],
            'server_time_vienna' => $status['server_time_vienna'],
            'payout_authorized' => false,
            'doors' => $status['doors'],
        ]);
    }

    christmas26_status_json(200, [
        'success' => true,
        'event' => CHRISTMAS26_EVENT_KEY,
        'event_state' => $status['event_state'],
        'server_time_vienna' => $status['server_time_vienna'],
        'payout_authorized' => false,
        'doors' => $status['doors'],
    ]);
} catch (Throwable $error) {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
    error_log('Christmas26 status unavailable: ' . $error->getMessage());
    christmas26_status_json(503, ['success' => false, 'error' => 'status_unavailable']);
}
