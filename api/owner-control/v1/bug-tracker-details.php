<?php
declare(strict_types=1);

// 🐛 Narrrfs World Owner Control V2 — Bug Tracker details.
//
// DEVS FOR DECADES:
// This is an owner-only READ model for one Bug Tracker record.
// It may expose the Discord transport identifiers needed to locate the
// original report, but it performs no Discord action and no database write.
//
// Mutations must live behind a separate guarded capability.

define('NARRRFS_SESSION_READ_ONLY', true);

require_once __DIR__
    . '/../../config/admin-authorization.php';

narrrfs_require_admin_request(
    'owner_control.bug_tracker.details.v1'
);

header(
    'Content-Type: application/json; charset=utf-8'
);
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');


$method = strtoupper(
    (string)($_SERVER['REQUEST_METHOD'] ?? '')
);

if ($method === 'HEAD') {
    exit;
}


$rawBugId = $_GET['id'] ?? null;

if (
    !is_string($rawBugId)
    || !preg_match('/^[1-9]\d*$/', $rawBugId)
) {
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

$bugId = (int)$rawBugId;


require_once __DIR__
    . '/../../config/database.php';


/**
 * Read every row from one already-prepared SELECT.
 *
 * Plain language:
 * This helper only consumes SQLite result rows. SQL remains fixed at each
 * caller so request data can never become SQL structure.
 *
 * @return list<array<string, mixed>>
 */
function narrrfs_owner_bug_details_rows(
    SQLite3Stmt $statement
): array {
    $result = $statement->execute();

    if (!$result instanceof SQLite3Result) {
        throw new RuntimeException(
            'Unable to read Bug Tracker data.'
        );
    }

    $rows = [];

    while (
        $row = $result->fetchArray(
            SQLITE3_ASSOC
        )
    ) {
        $rows[] = $row;
    }

    $result->finalize();

    return $rows;
}


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
        SQLITE3_OPEN_READONLY
    );

    $db->busyTimeout(5000);

    if (!$db->exec('PRAGMA query_only = ON')) {
        throw new RuntimeException(
            'Unable to enforce read-only mode.'
        );
    }


    $bugStatement = $db->prepare(
        '
        SELECT
            bug_id,
            discord_message_id,
            discord_channel_id,
            discord_user_id,
            discord_username,
            title,
            description,
            category_id,
            priority_id,
            status_id,
            assigned_to,
            created_at,
            updated_at,
            resolved_at,
            resolution_notes
        FROM tbl_bug_reports
        WHERE bug_id = :bug_id
        LIMIT 1
        '
    );

    if (!$bugStatement instanceof SQLite3Stmt) {
        throw new RuntimeException(
            'Unable to prepare Bug Tracker query.'
        );
    }

    $bugStatement->bindValue(
        ':bug_id',
        $bugId,
        SQLITE3_INTEGER
    );

    $bugRows = narrrfs_owner_bug_details_rows(
        $bugStatement
    );

    if ($bugRows === []) {
        http_response_code(404);

        echo json_encode(
            [
                'success' => false,
                'error' => 'bug_not_found',
            ],
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    $bug = $bugRows[0];


    $commentStatement = $db->prepare(
        '
        SELECT
            comment_id,
            discord_username,
            comment_text,
            comment_type,
            created_at
        FROM tbl_bug_comments
        WHERE bug_id = :bug_id
        ORDER BY created_at ASC, comment_id ASC
        '
    );

    if (!$commentStatement instanceof SQLite3Stmt) {
        throw new RuntimeException(
            'Unable to prepare comment query.'
        );
    }

    $commentStatement->bindValue(
        ':bug_id',
        $bugId,
        SQLITE3_INTEGER
    );

    $comments = narrrfs_owner_bug_details_rows(
        $commentStatement
    );


    $historyStatement = $db->prepare(
        '
        SELECT
            history.history_id,
            history.old_status_id,
            old_status.status_name
                AS old_status_name,
            history.new_status_id,
            new_status.status_name
                AS new_status_name,
            history.changed_by,
            history.changed_at,
            history.change_reason
        FROM tbl_bug_status_history AS history
        LEFT JOIN tbl_bug_statuses AS old_status
            ON old_status.status_id
                = history.old_status_id
        LEFT JOIN tbl_bug_statuses AS new_status
            ON new_status.status_id
                = history.new_status_id
        WHERE history.bug_id = :bug_id
        ORDER BY
            history.changed_at ASC,
            history.history_id ASC
        '
    );

    if (!$historyStatement instanceof SQLite3Stmt) {
        throw new RuntimeException(
            'Unable to prepare history query.'
        );
    }

    $historyStatement->bindValue(
        ':bug_id',
        $bugId,
        SQLITE3_INTEGER
    );

    $statusHistory =
        narrrfs_owner_bug_details_rows(
            $historyStatement
        );


    $assignmentStatement = $db->prepare(
        '
        SELECT
            assignment_id,
            assigned_to,
            assigned_by,
            assigned_at,
            unassigned_at,
            assignment_notes
        FROM tbl_bug_assignments
        WHERE bug_id = :bug_id
        ORDER BY
            assigned_at ASC,
            assignment_id ASC
        '
    );

    if (
        !$assignmentStatement
        instanceof SQLite3Stmt
    ) {
        throw new RuntimeException(
            'Unable to prepare assignment query.'
        );
    }

    $assignmentStatement->bindValue(
        ':bug_id',
        $bugId,
        SQLITE3_INTEGER
    );

    $assignments =
        narrrfs_owner_bug_details_rows(
            $assignmentStatement
        );


    echo json_encode(
        [
            'success' => true,
            'capability' =>
                'owner_control_bug_tracker_details',
            'version' => 1,
            'mode' => 'read_only',
            'writes' => false,
            'bug' => $bug,
            'comments' => $comments,
            'status_history' => $statusHistory,
            'assignments' => $assignments,
        ],
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $error) {
    error_log(
        '[OWNER CONTROL] Bug Tracker details failed: '
        . $error->getMessage()
    );

    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'error' => 'data_unavailable',
        ],
        JSON_UNESCAPED_SLASHES
    );

} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}
