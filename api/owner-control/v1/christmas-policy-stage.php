<?php
/** Owner-only Step 1: stage a validated draft revision as a release candidate policy. */
declare(strict_types=1);

require_once __DIR__ . '/../../config/admin-authorization.php';
$actor = narrrfs_require_admin_request('owner_control.christmas_policy.stage.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/christmas-advent-2026-policy-v1.php';

try {
    $stream = fopen('php://input', 'rb');
    $raw = $stream === false ? false : stream_get_contents($stream, 4097);
    if (!is_string($raw) || strlen($raw) > 4096 || $raw === '') {
        http_response_code(400);
        echo '{"success":false,"error":"invalid_request"}';
        exit;
    }
    $input = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
    if (!is_array($input) || array_is_list($input) || array_diff(array_keys($input), ['draft_revision', 'policy_version'])
        || !is_int($input['draft_revision'] ?? null) || !is_string($input['policy_version'] ?? null)) {
        http_response_code(400);
        echo '{"success":false,"error":"invalid_request"}';
        exit;
    }

    $path = getDatabasePath();
    if (!is_file($path)) {
        throw new Christmas26PolicyException(503, 'policy_stage_unavailable');
    }
    $db = new SQLite3($path, SQLITE3_OPEN_READWRITE);
    $result = christmas26_stage_policy_candidate($db, $input['draft_revision'], $input['policy_version'], $actor);
    $db->close();
    echo json_encode(['success' => true, 'result' => $result], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (JsonException) {
    http_response_code(400);
    echo '{"success":false,"error":"invalid_json"}';
} catch (Christmas26PolicyException $e) {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
    http_response_code($e->status);
    $payload = ['success' => false, 'error' => $e->publicCode];
    if ($e->details !== []) {
        $payload['details'] = $e->details;
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
} catch (Throwable) {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
    http_response_code(503);
    echo '{"success":false,"error":"policy_stage_unavailable"}';
}
