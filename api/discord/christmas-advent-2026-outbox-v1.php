<?php
/**
 * Bot-only narrow endpoint for Christmas Advent 2026 #lootwinners outbox delivery.
 * Replaces raw SQL bridge usage for Christmas announcements; defaults OFF.
 */
declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function christmas26_outbox_reply(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if (getenv('NARRRFS_CHRISTMAS_ADVENT_2026_OUTBOX_ENABLED') !== '1') {
    christmas26_outbox_reply(404, ['success' => false, 'error' => 'outbox_unavailable']);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    christmas26_outbox_reply(405, ['success' => false, 'error' => 'method_not_allowed']);
}

$secret = getenv('NARRRFS_CHRISTMAS_ADVENT_2026_OUTBOX_SECRET');
if (!is_string($secret) || !preg_match('/\A[A-Za-z0-9_-]{32,256}\z/D', $secret)) {
    christmas26_outbox_reply(503, ['success' => false, 'error' => 'outbox_auth_unavailable']);
}

$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (!is_string($auth) && function_exists('getallheaders')) {
    $auth = '';
}
if ($auth === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
        if (strcasecmp((string)$k, 'Authorization') === 0 && is_string($v)) {
            $auth = trim($v);
            break;
        }
    }
}
if (!preg_match('/\ABearer ([A-Za-z0-9_-]{32,256})\z/D', $auth, $m) || !hash_equals($secret, $m[1])) {
    christmas26_outbox_reply(401, ['success' => false, 'error' => 'unauthorized']);
}

$contentType = strtolower(trim((string)($_SERVER['CONTENT_TYPE'] ?? '')));
if (!preg_match('/\Aapplication\/json(?:\s*;.*)?\z/D', $contentType)) {
    christmas26_outbox_reply(415, ['success' => false, 'error' => 'json_required']);
}

$stream = fopen('php://input', 'rb');
$raw = $stream === false ? '' : stream_get_contents($stream, 2049);
if (!is_string($raw) || $raw === '' || strlen($raw) > 2048) {
    christmas26_outbox_reply(413, ['success' => false, 'error' => 'invalid_request_size']);
}

try {
    $input = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    christmas26_outbox_reply(400, ['success' => false, 'error' => 'invalid_json']);
}
if (!is_array($input) || array_is_list($input) || !is_string($input['action'] ?? null)) {
    christmas26_outbox_reply(400, ['success' => false, 'error' => 'invalid_request']);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/christmas-advent-2026-outbox-v1.php';

$dbPath = getDatabasePath();
if (!is_file($dbPath) || !is_writable($dbPath)) {
    christmas26_outbox_reply(503, ['success' => false, 'error' => 'outbox_unavailable']);
}

try {
    $db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE);
    $action = $input['action'];
    if ($action === 'claim_batch') {
        $allowed = ['action', 'limit'];
        if (array_diff(array_keys($input), $allowed) || (isset($input['limit']) && !is_int($input['limit']))) {
            throw new Christmas26OutboxException(400, 'invalid_request');
        }
        $res = christmas26_outbox_claim_batch($db, (int)($input['limit'] ?? 10));
    } elseif ($action === 'mark_sent') {
        $allowed = ['action', 'claim_id', 'claim_token', 'discord_channel_id', 'discord_message_id'];
        if (array_diff(array_keys($input), $allowed)
            || !is_int($input['claim_id'] ?? null)
            || !is_string($input['claim_token'] ?? null)
            || !is_string($input['discord_channel_id'] ?? null)
            || !is_string($input['discord_message_id'] ?? null)) {
            throw new Christmas26OutboxException(400, 'invalid_request');
        }
        $res = christmas26_outbox_mark_sent(
            $db,
            $input['claim_id'],
            $input['claim_token'],
            $input['discord_channel_id'],
            $input['discord_message_id']
        );
    } elseif ($action === 'mark_retry') {
        $allowed = ['action', 'claim_id', 'claim_token', 'error_code', 'retry_after_seconds'];
        if (array_diff(array_keys($input), $allowed)
            || !is_int($input['claim_id'] ?? null)
            || !is_string($input['claim_token'] ?? null)
            || !is_string($input['error_code'] ?? null)
            || (isset($input['retry_after_seconds']) && !is_int($input['retry_after_seconds']))) {
            throw new Christmas26OutboxException(400, 'invalid_request');
        }
        $res = christmas26_outbox_mark_retry(
            $db,
            $input['claim_id'],
            $input['claim_token'],
            $input['error_code'],
            (int)($input['retry_after_seconds'] ?? 60)
        );
    } else {
        throw new Christmas26OutboxException(400, 'unsupported_action');
    }

    $db->close();
    christmas26_outbox_reply(200, ['success' => true, 'result' => $res]);
} catch (Christmas26OutboxException $e) {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
    christmas26_outbox_reply($e->status, ['success' => false, 'error' => $e->publicCode]);
} catch (Throwable) {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
    christmas26_outbox_reply(503, ['success' => false, 'error' => 'outbox_unavailable']);
}
