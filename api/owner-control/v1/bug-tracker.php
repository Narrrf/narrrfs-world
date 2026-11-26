<?php
declare(strict_types=1);

// 🐛 Narrrfs World Owner Control V2 — Bug Tracker read model.
//
// DEVS FOR DECADES:
// This endpoint intentionally exposes only the fields needed for the
// read-only Bug Tracker list and dashboard. Detailed report content,
// Discord transport identifiers, comments, uploaded-file metadata and all
// mutation operations remain outside this capability.
//
// The database connection is opened with SQLite READONLY and query_only.
// This endpoint must never become a shortcut to legacy Admin mutations.

define('NARRRFS_SESSION_READ_ONLY', true);

require_once __DIR__ . '/../../config/admin-authorization.php';

narrrfs_require_admin_request(
    'owner_control.bug_tracker.read.v1'
);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (
    strtoupper(
        (string)($_SERVER['REQUEST_METHOD'] ?? '')
    ) === 'HEAD'
) {
    exit;
}

require_once __DIR__ . '/../../config/database.php';


/**
 * Read one bounded non-negative integer query parameter.
 *
 * Plain language:
 * Pagination is server-controlled. The browser may request a page size
 * or offset, but values outside the approved range are rejected.
 */
function narrrfs_owner_bug_tracker_query_integer(
    string $name,
    int $default,
    int $minimum,
    int $maximum
): int {
    if (!array_key_exists($name, $_GET)) {
        return $default;
    }

    $rawValue = $_GET[$name];

    if (
        !is_string($rawValue)
        || !preg_match('/^\d+$/', $rawValue)
    ) {
        http_response_code(400);

        echo json_encode(
            [
                'success' => false,
                'error' => 'invalid_pagination',
            ],
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    $value = (int)$rawValue;

    if (
        $value < $minimum
        || $value > $maximum
    ) {
        http_response_code(400);

        echo json_encode(
            [
                'success' => false,
                'error' => 'invalid_pagination',
            ],
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    return $value;
}


/**
 * Read all rows for one fixed SELECT statement.
 *
 * Plain language:
 * Only hard-coded SQL from this file reaches this helper. User input
 * never becomes SQL text.
 *
 * @return list<array<string, mixed>>
 */
function narrrfs_owner_bug_tracker_read_rows(
    SQLite3 $database,
    string $sql
): array {
    $result = $database->query($sql);

    if ($result === false) {
        throw new RuntimeException(
            'Bug Tracker read query failed.'
        );
    }

    $rows = [];

    while (
        ($row = $result->fetchArray(SQLITE3_ASSOC)) !== false
    ) {
        $rows[] = $row;
    }

    return $rows;
}


$limit = narrrfs_owner_bug_tracker_query_integer(
    'limit',
    50,
    1,
    100
);

$offset = narrrfs_owner_bug_tracker_query_integer(
    'offset',
    0,
    0,
    100000
);

// Filter values are bound as data; the ordering comes only from this allowlist.
$filters = [];
foreach (['search', 'status', 'priority', 'assignee'] as $name) {
    $value = $_GET[$name] ?? '';
    if (!is_string($value) || strlen($value) > 100 || preg_match('/[\x00-\x1f\x7f]/', $value)) {
        http_response_code(400); echo json_encode(['success'=>false,'error'=>'invalid_filter']); exit;
    }
    $filters[$name] = trim($value);
}
foreach (['status','priority'] as $name) {
    if ($filters[$name] !== '' && !preg_match('/^[1-9][0-9]{0,8}$/D', $filters[$name])) {
        http_response_code(400); echo json_encode(['success'=>false,'error'=>'invalid_filter']); exit;
    }
}
$orders = ['recent'=>'CASE WHEN COALESCE(s.is_resolved, 0) = 0 THEN 0 ELSE 1 END ASC, b.updated_at DESC, b.bug_id DESC', 'oldest'=>'b.created_at ASC, b.bug_id ASC', 'newest'=>'b.created_at DESC, b.bug_id DESC', 'priority'=>'p.priority_level ASC, b.bug_id DESC'];
$sort = $_GET['sort'] ?? 'recent';
if (!is_string($sort) || !isset($orders[$sort])) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'invalid_sort']); exit; }
$where = ['1=1']; $bindings = [];
if ($filters['search'] !== '') { $where[] = '(b.title LIKE :search ESCAPE \'\\\' OR b.discord_username LIKE :search ESCAPE \'\\\' OR CAST(b.bug_id AS TEXT) = :search_id)'; $bindings[':search'] = '%' . str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $filters['search']) . '%'; $bindings[':search_id'] = $filters['search']; }
foreach (['status'=>'status_id','priority'=>'priority_id'] as $key=>$column) if ($filters[$key] !== '') { $where[] = "b.$column = :$key"; $bindings[":$key"] = (int)$filters[$key]; }
if ($filters['assignee'] === '__unassigned__') $where[] = "(b.assigned_to IS NULL OR TRIM(b.assigned_to) = '')";
elseif ($filters['assignee'] !== '') { $where[] = 'b.assigned_to = :assignee'; $bindings[':assignee'] = $filters['assignee']; }
$whereSql = implode(' AND ', $where);

$database = null;

try {
    $databasePath = getDatabasePath();

    if (!is_file($databasePath)) {
        throw new RuntimeException(
            'Owner Control database is unavailable.'
        );
    }

    $database = new SQLite3(
        $databasePath,
        SQLITE3_OPEN_READONLY
    );

    $database->busyTimeout(5000);

    if (
        !$database->exec(
            'PRAGMA query_only = ON'
        )
    ) {
        throw new RuntimeException(
            'Unable to enforce query-only database mode.'
        );
    }

    $summaryResult = $database->query(
        "
        SELECT
            COUNT(*) AS total,
            SUM(
                CASE
                    WHEN COALESCE(s.is_resolved, 0) = 0
                    THEN 1
                    ELSE 0
                END
            ) AS open,
            SUM(
                CASE
                    WHEN COALESCE(s.is_resolved, 0) = 1
                    THEN 1
                    ELSE 0
                END
            ) AS resolved
        FROM tbl_bug_reports AS b
        LEFT JOIN tbl_bug_statuses AS s
            ON s.status_id = b.status_id
        "
    );

    if ($summaryResult === false) {
        throw new RuntimeException(
            'Bug Tracker summary query failed.'
        );
    }

    $summary = $summaryResult->fetchArray(
        SQLITE3_ASSOC
    );

    if (!is_array($summary)) {
        throw new RuntimeException(
            'Bug Tracker summary is unavailable.'
        );
    }

    $countStatement = $database->prepare("SELECT COUNT(*) FROM tbl_bug_reports AS b WHERE $whereSql");
    foreach ($bindings as $key=>$value) $countStatement->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
    $filteredTotal = (int)$countStatement->execute()->fetchArray(SQLITE3_NUM)[0];
    $bugsStatement = $database->prepare(
        "
        SELECT
            b.bug_id,
            b.owner_control_version,
            b.discord_username,
            b.title,
            b.category_id,
            b.priority_id,
            b.status_id,
            b.assigned_to,
            b.created_at,
            b.updated_at,
            b.resolved_at
        FROM tbl_bug_reports AS b
        LEFT JOIN tbl_bug_statuses AS s
            ON s.status_id = b.status_id
        LEFT JOIN tbl_bug_priorities AS p
            ON p.priority_id = b.priority_id
        WHERE $whereSql
        ORDER BY {$orders[$sort]}
        LIMIT :limit
        OFFSET :offset
        "
    );

    if ($bugsStatement === false) {
        throw new RuntimeException(
            'Unable to prepare Bug Tracker list.'
        );
    }

    foreach ($bindings as $key=>$value) $bugsStatement->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);

    $bugsStatement->bindValue(
        ':limit',
        $limit,
        SQLITE3_INTEGER
    );

    $bugsStatement->bindValue(
        ':offset',
        $offset,
        SQLITE3_INTEGER
    );

    $bugsResult = $bugsStatement->execute();

    if ($bugsResult === false) {
        throw new RuntimeException(
            'Unable to read Bug Tracker list.'
        );
    }

    $bugs = [];

    while (
        ($row = $bugsResult->fetchArray(
            SQLITE3_ASSOC
        )) !== false
    ) {
        $bugs[] = $row;
    }

    $categories = narrrfs_owner_bug_tracker_read_rows(
        $database,
        "
        SELECT
            category_id,
            category_name,
            color_code,
            icon
        FROM tbl_bug_categories
        ORDER BY category_name ASC
        "
    );

    $priorities = narrrfs_owner_bug_tracker_read_rows(
        $database,
        "
        SELECT
            priority_id,
            priority_name,
            priority_level,
            color_code,
            icon
        FROM tbl_bug_priorities
        ORDER BY priority_level ASC
        "
    );

    $statuses = narrrfs_owner_bug_tracker_read_rows(
        $database,
        "
        SELECT
            status_id,
            status_name,
            color_code,
            icon,
            is_active,
            is_resolved
        FROM tbl_bug_statuses
        ORDER BY status_id ASC
        "
    );

    echo json_encode(
        [
            'success' => true,
            'capability' => 'owner_control_bug_tracker_read',
            'version' => 1,
            'mode' => 'read_only',
            'writes' => false,
            'summary' => [
                'total' => (int)($summary['total'] ?? 0),
                'open' => (int)($summary['open'] ?? 0),
                'resolved' => (int)($summary['resolved'] ?? 0),
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'returned' => count($bugs),
                'filtered_total' => $filteredTotal,
            ],
            'bugs' => $bugs,
            'categories' => $categories,
            'priorities' => $priorities,
            'statuses' => $statuses,
        ],
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE
    );
} catch (Throwable $error) {
    error_log(
        'Owner Control Bug Tracker read failed: '
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
    if ($database instanceof SQLite3) {
        $database->close();
    }
}
