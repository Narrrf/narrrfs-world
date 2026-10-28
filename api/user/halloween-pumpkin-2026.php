<?php
/**
 * Halloween 2026 Pumpkin Gift — dedicated, session-bound event endpoint.
 * GET = authenticated status and one-time CSRF token (no DB write).
 * POST = protected one-time gift (disabled unless expressly enabled).
 *
 * DEVS FOR DECADES: NEVER accept a supplied user_id or amount, even locally.
 * This is a new giveaway economy path, NOT a Reward Chamber box, staking,
 * item, permanent Genesis/Lab, or general admin adjustment endpoint.
 */

declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../lib/halloween-pumpkin-2026.php';

/** Send a consistent JSON response without exposing server-side errors. */
function halloween26_json(int $httpCode, array $response): void
{
    http_response_code($httpCode);
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
    exit;
}

// Explicit release gate: pushing code MUST NOT activate a payout system.
if (getenv('NARRRFS_HALLOWEEN_PUMPKIN_2026_ENABLED') !== '1') {
    halloween26_json(404, ['success' => false, 'error' => 'event_unavailable']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method !== 'GET' && $method !== 'POST') {
    header('Allow: GET, POST');
    halloween26_json(405, ['success' => false, 'error' => 'method_not_allowed']);
}

$sessionUser = $_SESSION['discord_id'] ?? null;
$userId = is_string($sessionUser) || is_int($sessionUser) ? trim((string)$sessionUser) : '';
if (!ctype_digit($userId) || strlen($userId) < 15 || strlen($userId) > 20) {
    halloween26_json(401, ['success' => false, 'error' => 'authentication_required']);
}

if ($method === 'POST') {
    // Do not trust a client-provided user, prize, timestamp, or origin.
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    $allowedOrigins = ['https://narrrfs.world', 'http://localhost', 'http://127.0.0.1'];
    if (!in_array($origin, $allowedOrigins, true)) {
        halloween26_json(403, ['success' => false, 'error' => 'origin_rejected']);
    }
    $contentType = strtolower(trim(explode(';', (string)($_SERVER['CONTENT_TYPE'] ?? ''), 2)[0]));
    if ($contentType !== 'application/json') {
        halloween26_json(415, ['success' => false, 'error' => 'json_required']);
    }
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body) || array_keys($body) !== ['event']
        || $body['event'] !== HALLOWEEN26_EVENT_KEY) {
        halloween26_json(400, ['success' => false, 'error' => 'invalid_event_request']);
    }
    $csrfEntry = $_SESSION['halloween26_csrf'] ?? null;
    unset($_SESSION['halloween26_csrf']); // one attempt, including rejection
    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_array($csrfEntry)
        || !is_string($submittedToken)
        || !is_string($csrfEntry['token'] ?? null)
        || !is_int($csrfEntry['created_at'] ?? null)
        || (time() - $csrfEntry['created_at']) > 300
        || (time() - $csrfEntry['created_at']) < 0
        || !hash_equals($csrfEntry['token'], $submittedToken)) {
        halloween26_json(403, ['success' => false, 'error' => 'csrf_rejected']);
    }
}

$dbPath = dirname(__DIR__, 2) . '/db/narrrf_world.sqlite';
if (!is_file($dbPath)) {
    error_log('Halloween26 database unavailable at expected application path');
    halloween26_json(503, ['success' => false, 'error' => 'service_unavailable']);
}

try {
    $db = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    if ($method === 'GET') {
        $state = halloween26_get_claim_state($db, $userId);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $active = halloween26_is_open($now);
        $token = null;
        if (!$state['claimed'] && $active) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['halloween26_csrf'] = ['token' => $token, 'created_at' => time()];
        }
        halloween26_json(200, [
            'success' => true,
            'event' => HALLOWEEN26_EVENT_KEY,
            'active' => $active,
            'claimed' => $state['claimed'],
            'amount' => $state['amount'],
            'csrf_token' => $token,
            'end_at_utc' => HALLOWEEN26_END_EXCLUSIVE_UTC,
        ]);
    }

    $state = halloween26_claim($db, $userId);
    halloween26_json(200, [
        'success' => true,
        'claimed' => $state['claimed'],
        'already_claimed' => $state['already_claimed'],
        'amount' => $state['amount'],
        'event' => HALLOWEEN26_EVENT_KEY,
    ]);
} catch (DomainException $error) {
    $kind = $error->getMessage();
    if ($kind === 'halloween26_event_closed') {
        halloween26_json(410, ['success' => false, 'error' => 'event_closed']);
    }
    if ($kind === 'halloween26_account_not_found') {
        halloween26_json(403, ['success' => false, 'error' => 'account_required']);
    }
    error_log('Halloween26 domain failure: ' . $kind);
    halloween26_json(409, ['success' => false, 'error' => 'claim_unavailable']);
} catch (Throwable $error) {
    // Never return stack traces, DB paths or actual secrets to a browser.
    error_log('Halloween26 claim/status error: ' . $error->getMessage());
    halloween26_json(503, ['success' => false, 'error' => 'claim_unavailable_retry_status']);
}
