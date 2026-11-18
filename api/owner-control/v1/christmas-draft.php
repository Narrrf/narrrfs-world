<?php
/** Owner-only Christmas planning drafts. This endpoint never publishes a policy. */
declare(strict_types=1);
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
if ($method === 'GET') define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../config/admin-authorization.php';
$actor = narrrfs_require_admin_request($method === 'POST' ? 'owner_control.christmas_draft.write.v1' : 'owner_control.christmas_draft.read.v1');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/../../lib/christmas-advent-2026-draft-storage-v1.php';
if ($method === 'POST') {
    $stream = fopen('php://input', 'rb');
    $raw = $stream === false ? false : stream_get_contents($stream, 65537);
    if (!is_string($raw) || strlen($raw) > 65536) { http_response_code(413); echo '{"success":false,"error":"body_too_large"}'; exit; }
    try { $input = json_decode($raw, true, 64, JSON_THROW_ON_ERROR); }
    catch (JsonException) { http_response_code(400); echo '{"success":false,"error":"invalid_json"}'; exit; }
    if (!is_array($input) || array_is_list($input) || array_diff(array_keys($input), ['revision','draft']) || !is_int($input['revision'] ?? null) || !is_array($input['draft'] ?? null)) {
        http_response_code(400); echo '{"success":false,"error":"invalid_request"}'; exit;
    }
    // Preflight error codes identify the invalid day/field without exposing SQL.
    $preflight = christmas26_draft_preflight_v2($input['draft']);
    if (!$preflight['valid']) {
        http_response_code(400);
        echo json_encode(['success'=>false, 'error'=>'invalid_draft', 'details'=>array_slice($preflight['errors'], 0, 50)], JSON_UNESCAPED_SLASHES);
        exit;
    }
}
require_once __DIR__ . '/../../config/database.php';
try {
    $path = getDatabasePath();
    if (!is_file($path)) throw new RuntimeException('unavailable');
    $db = new SQLite3($path, $method === 'POST' ? SQLITE3_OPEN_READWRITE : SQLITE3_OPEN_READONLY);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    if ($method === 'GET') $db->exec('PRAGMA query_only=ON');
    $result = $method === 'POST' ? christmas26_draft_save_v1($db, $input['draft'], $input['revision'], $actor) : christmas26_draft_load_v1($db);
    $db->close();
    echo json_encode(['success'=>true, 'result'=>$result, 'write_enabled'=>getenv('NARRRFS_OWNER_CHRISTMAS_DRAFT_WRITE_ENABLED') === '1'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $error) {
    http_response_code(400); echo '{"success":false,"error":"invalid_draft"}';
} catch (Throwable $error) {
    http_response_code($error->getMessage() === 'draft_revision_conflict' ? 409 : 503);
    echo json_encode(['success'=>false,'error'=>$error->getMessage() === 'draft_revision_conflict' ? 'draft_revision_conflict' : 'draft_unavailable']);
}
