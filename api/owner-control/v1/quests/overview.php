<?php
declare(strict_types=1);

// Owner Control Quest/Hunt Overview V1.
// This route intentionally exposes only fixed aggregate operational metadata.
// It cannot inspect player claims, issue rewards, update quest state, or call
// Discord. Any future claim or quest mutation requires a separate protected run.

/** @param array{activeCount:int,activeTypeCounts:list<array{type:string,count:int}>,claimStatusCounts:array{pending:int,approved:int,rejected:int},aggregateClickCount:int,recentWindowClickCount:int} $metrics */
function quest_hunt_overview_payload(array $metrics): array
{
    return [
        'success' => true,
        'capability' => 'owner_control_quest_hunt_overview',
        'version' => 1,
        'mode' => 'read_only',
        'writes' => false,
        'quests' => [
            'active_count' => $metrics['activeCount'],
            'active_type_counts' => $metrics['activeTypeCounts'],
        ],
        'claims' => [
            'status_counts' => $metrics['claimStatusCounts'],
        ],
        'hunt' => [
            'aggregate_click_count' => $metrics['aggregateClickCount'],
            'recent_window_click_count' => $metrics['recentWindowClickCount'],
        ],
    ];
}

if (defined('NARRRFS_QUEST_HUNT_OVERVIEW_LIBRARY_ONLY')) {
    return;
}

define('NARRRFS_SESSION_READ_ONLY', true);

require_once __DIR__ . '/../../../config/admin-authorization.php';

narrrfs_require_admin_request('owner_control.quest_hunt_overview.read.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') {
    exit;
}

$db = null;

try {
    require_once __DIR__ . '/../../../config/database.php';
    $databasePath = getDatabasePath();
    if (!is_file($databasePath) || !is_readable($databasePath)) {
        throw new RuntimeException('Overview unavailable.');
    }

    $db = new SQLite3($databasePath, SQLITE3_OPEN_READONLY);
    if (!$db->exec('PRAGMA query_only = ON')) {
        throw new RuntimeException('Overview unavailable.');
    }

    $activeCount = $db->querySingle(
        'SELECT COUNT(*) FROM tbl_quests WHERE is_active = 1'
    );
    $typeResult = $db->query(
        'SELECT type, COUNT(*) AS count
         FROM tbl_quests
         WHERE is_active = 1
         GROUP BY type
         ORDER BY type COLLATE NOCASE ASC
         LIMIT 16'
    );
    $claimCounts = $db->querySingle(
        "SELECT
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
         FROM tbl_quest_claims",
        true
    );
    $aggregateClickCount = $db->querySingle('SELECT COUNT(*) FROM tbl_cheese_clicks');
    $recentWindowClickCount = $db->querySingle(
        "SELECT COUNT(*) FROM tbl_cheese_clicks
         WHERE timestamp >= datetime('now', '-24 hours')"
    );

    if (
        $activeCount === false ||
        !$typeResult instanceof SQLite3Result ||
        !is_array($claimCounts) ||
        $aggregateClickCount === false ||
        $recentWindowClickCount === false
    ) {
        throw new RuntimeException('Overview unavailable.');
    }

    $activeTypeCounts = [];
    while (($row = $typeResult->fetchArray(SQLITE3_ASSOC)) !== false) {
        $activeTypeCounts[] = [
            'type' => (string)$row['type'],
            'count' => (int)$row['count'],
        ];
    }
    $typeResult->finalize();

    echo json_encode(
        quest_hunt_overview_payload([
            'activeCount' => (int)$activeCount,
            'activeTypeCounts' => $activeTypeCounts,
            'claimStatusCounts' => [
                'pending' => (int)($claimCounts['pending_count'] ?? 0),
                'approved' => (int)($claimCounts['approved_count'] ?? 0),
                'rejected' => (int)($claimCounts['rejected_count'] ?? 0),
            ],
            'aggregateClickCount' => (int)$aggregateClickCount,
            'recentWindowClickCount' => (int)$recentWindowClickCount,
        ]),
        JSON_UNESCAPED_SLASHES
    );
} catch (Throwable) {
    error_log('[OWNER CONTROL] Quest/Hunt overview unavailable.');
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'quest_hunt_overview_unavailable']);
} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}
