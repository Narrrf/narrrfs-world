<?php
/**
 * Christmas Advent 2026 — authenticated POST-only daily claim endpoint.
 *
 * Security & economy boundaries:
 * - Default OFF unless NARRRFS_CHRISTMAS_ADVENT_2026_PAYOUT_ENABLED=1 AND valid POLICY_VERSION env.
 * - Requires trusted Discord OAuth provenance session, strict Origin, JSON `{}` empty body, and one-time CSRF.
 * - Ignores/rejects any client-supplied amount, item, roll, or identity.
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

function christmas26_claim_json(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

$policyVersion = getenv(CHRISTMAS26_POLICY_VERSION_ENV) ?: null;
if (!christmas26_claim_runtime_gate_is_enabled(getenv(CHRISTMAS26_PAYOUT_GATE) ?: null, $policyVersion)) {
    christmas26_claim_json(404, ['success' => false, 'error' => 'claim_unavailable']);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    christmas26_claim_json(405, ['success' => false, 'error' => 'method_not_allowed']);
}
if (!narrrfs_session_has_trusted_discord_oauth_provenance($_SESSION)) {
    christmas26_claim_json(401, ['success' => false, 'error' => 'authentication_required']);
}
if (!christmas26_claim_origin_is_allowed($_SERVER)
    || !str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
    christmas26_claim_json(403, ['success' => false, 'error' => 'request_rejected']);
}

$stream = fopen('php://input', 'rb');
$raw = $stream === false ? '' : trim((string)stream_get_contents($stream, 1025));
$body = json_decode($raw, true);
if (!str_starts_with($raw, '{') || !is_array($body) || $body !== []) {
    christmas26_claim_json(400, ['success' => false, 'error' => 'request_rejected']);
}
if (!christmas26_claim_csrf_is_valid($_SESSION, $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    session_write_close();
    christmas26_claim_json(403, ['success' => false, 'error' => 'csrf_rejected']);
}
$userId = (string)$_SESSION['discord_id'];
session_write_close();

$dbPath = getDatabasePath();
if (!is_file($dbPath) || !is_writable($dbPath)) {
    christmas26_claim_json(503, ['success' => false, 'error' => 'claim_unavailable']);
}

try {
    $db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE);
    $receipt = christmas26_claim_execute_v1($db, $userId, new DateTimeImmutable('now'), 'active');
    $db->close();
    christmas26_claim_json(200, ['success' => true, 'claim' => $receipt]);
} catch (Christmas26ClaimException $e) {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
    christmas26_claim_json($e->status, ['success' => false, 'error' => $e->publicCode]);
} catch (Throwable) {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
    christmas26_claim_json(503, ['success' => false, 'error' => 'claim_unavailable']);
}
