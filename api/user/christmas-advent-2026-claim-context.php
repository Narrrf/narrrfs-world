<?php
/**
 * Christmas Advent 2026 — authenticated GET-only calendar context + receipts + conditional CSRF.
 *
 * Keeps /api/user/christmas-advent-2026.php untouched for V2.2 read-only status regressions,
 * while giving Profile 24-door UI server-authoritative claimed receipts and claim CSRF tokens.
 */
declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

require_once __DIR__ . '/../config/admin-session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/christmas-advent-2026-claim.php';

function christmas26_context_json(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

$statusEnabled = christmas26_status_feature_is_enabled(getenv(CHRISTMAS26_STATUS_GATE) ?: null);
$policyVersion = getenv(CHRISTMAS26_POLICY_VERSION_ENV) ?: null;
$payoutGateEnabled = christmas26_claim_runtime_gate_is_enabled(getenv(CHRISTMAS26_PAYOUT_GATE) ?: null, $policyVersion);

if (!$statusEnabled && !$payoutGateEnabled) {
    christmas26_context_json(404, ['success' => false, 'error' => 'status_unavailable']);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    christmas26_context_json(405, ['success' => false, 'error' => 'method_not_allowed']);
}
if (!narrrfs_session_has_trusted_discord_oauth_provenance($_SESSION)) {
    session_write_close();
    christmas26_context_json(401, ['success' => false, 'error' => 'authentication_required']);
}

$userId = (string)$_SESSION['discord_id'];
$dbPath = getDatabasePath();
if (!is_file($dbPath) || !is_readable($dbPath)) {
    session_write_close();
    christmas26_context_json(503, ['success' => false, 'error' => 'status_unavailable']);
}

try {
    $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    if (!$db->exec('PRAGMA query_only = ON')) {
        throw new RuntimeException('query_only_unavailable');
    }

    $now = new DateTimeImmutable('now');
    $status = christmas26_status($db, $userId, $now);
    if (!$status['ready']) {
        $db->close();
        session_write_close();
        christmas26_context_json(503, [
            'success' => false,
            'event' => CHRISTMAS26_EVENT_KEY,
            'error' => 'status_unavailable',
            'event_state' => $status['event_state'],
            'server_time_vienna' => $status['server_time_vienna'],
            'claims_enabled' => false,
            'doors' => $status['doors'],
        ]);
    }

    $receipts = christmas26_player_receipts_v1($db, $userId);
    $approvedPolicy = null;
    if ($payoutGateEnabled) {
        try {
            $approvedPolicy = christmas26_load_active_approved_policy($db);
        } catch (Throwable) {
            $approvedPolicy = null;
        }
    }
    $db->close();

    $claimsEnabled = $payoutGateEnabled && is_array($approvedPolicy);
    $hasAvailableToday = false;
    $doors = [];
    foreach ($status['doors'] as $door) {
        $day = (int)$door['day'];
        $receipt = $receipts[$day] ?? null;
        if ($door['state'] === 'available' && $claimsEnabled) {
            $hasAvailableToday = true;
            $door['claim_authorized'] = true;
        }
        $door['receipt'] = is_array($receipt) ? $receipt : null;
        $doors[] = $door;
    }

    $csrfToken = $hasAvailableToday ? christmas26_claim_issue_csrf($_SESSION) : null;
    session_write_close();

    christmas26_context_json(200, [
        'success' => true,
        'event' => CHRISTMAS26_EVENT_KEY,
        'event_state' => $status['event_state'],
        'server_time_vienna' => $status['server_time_vienna'],
        'claims_enabled' => $claimsEnabled,
        'csrf_token' => $csrfToken,
        'doors' => $doors,
    ]);
} catch (Throwable) {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
    session_write_close();
    christmas26_context_json(503, ['success' => false, 'error' => 'status_unavailable']);
}
