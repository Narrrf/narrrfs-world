<?php
declare(strict_types=1);

// 🐛 Narrrfs World Owner Control V2 — Bug Tracker update.
//
// DEVS FOR DECADES:
// This is the first guarded Owner Control Bug Tracker mutation.
// The acting owner comes only from the trusted Discord OAuth server session.
// Browser input may never choose the acting admin identity.
//
// The route guard validates method, JSON content type, exact origin, current
// owner authority and a one-time CSRF token before SQLite is opened.
//
// Discord is NOT called here. The existing Discord bot remains responsible for
// observing committed status-history changes and updating message reactions.

require_once __DIR__
    . '/../../config/admin-authorization.php';

$ownerSubject = narrrfs_require_admin_request(
    'owner_control.bug_tracker.update.v1'
);

header(
    'Content-Type: application/json; charset=utf-8'
);
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');


$rawBody = file_get_contents('php://input');

if (!is_string($rawBody) || $rawBody === '') {
    http_response_code(400);

    echo json_encode(
        [
            'success' => false,
            'error' => 'invalid_json',
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


try {
    $input = json_decode(
        $rawBody,
        true,
        32,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    http_response_code(400);

    echo json_encode(
        [
            'success' => false,
            'error' => 'invalid_json',
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


if (!is_array($input)) {
    http_response_code(400);

    echo json_encode(
        [
            'success' => false,
            'error' => 'invalid_json',
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


$bugId = $input['bug_id'] ?? null;

if (!is_int($bugId) || $bugId < 1) {
    http_response_code(400);

    echo json_encode(
        [
            'success' => false,
            'error' => 'invalid_bug_id',
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


require_once __DIR__
    . '/../../config/database.php';

require_once __DIR__
    . '/../../lib/owner-bug-tracker-update-v1.php';


$db = null;

try {
    $databasePath = getDatabasePath();

    if (!is_file($databasePath)) {
        throw new RuntimeException(
            'Bug Tracker database unavailable.'
        );
    }

    $db = new SQLite3(
        $databasePath,
        SQLITE3_OPEN_READWRITE
    );

    $db->busyTimeout(5000);

    $result = narrrfs_owner_bug_tracker_update(
        $db,
        $bugId,
        $input,
        $ownerSubject
    );

    $retryMetadata = $result['retry_metadata'] ?? null;
    $publicBug = is_array($retryMetadata)
        ? [
            'bug_id' => $retryMetadata['bug_id'] ?? null,
            'owner_control_version' =>
                $retryMetadata['owner_control_version'] ?? null,
            'status_id' => $retryMetadata['status_id'] ?? null,
            'updated_at' => $retryMetadata['updated_at'] ?? null,
        ]
        : $result['bug'];

    echo json_encode(
        [
            'success' => true,
            'capability' =>
                'owner_control_bug_tracker_update',
            'version' => 1,
            'writes' => true,
            'bug' => $publicBug,
            'audit' => [
                'status_history_written' =>
                    $retryMetadata['status_history_written']
                    ?? $result['status_history_written'],
                'assignment_changed' =>
                    $retryMetadata['assignment_changed']
                    ?? $result['assignment_changed'],
            ],
            'idempotent_replay' =>
                $result['idempotent_replay'] ?? false,
            'discord_sync' =>
                'existing_bot_observer',
        ],
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );

} catch (
    NarrrfsOwnerBugTrackerUpdateException $error
) {
    http_response_code(
        $error->httpStatus()
    );

    echo json_encode(
        [
            'success' => false,
            'error' => $error->publicCode(),
        ],
        JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $error) {
    error_log(
        '[OWNER CONTROL] Bug Tracker update failed: '
        . $error->getMessage()
    );

    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'error' => 'update_failed',
        ],
        JSON_UNESCAPED_SLASHES
    );

} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}
