<?php
declare(strict_types=1);

// Narrrfs World Owner Control R3 - Dashboard Summary V1.
// DEVS FOR DECADES:
// This endpoint reports fixed aggregate counts only.
// It cannot create, purchase, award, burn, freeze or transfer assets.
// Every request must pass trusted owner authorization before SQLite opens.

define('NARRRFS_SESSION_READ_ONLY', true);

require_once __DIR__ . '/../../config/admin-authorization.php';

narrrfs_require_admin_request(
    'owner_control.dashboard.summary.v1'
);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') {
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$db = null;

try {
    $databasePath = getDatabasePath();

    if (!is_file($databasePath)) {
        throw new RuntimeException('Database unavailable.');
    }

    $db = new SQLite3($databasePath, SQLITE3_OPEN_READONLY);
    $db->busyTimeout(5000);

    if (!$db->exec('PRAGMA query_only = ON')) {
        throw new RuntimeException('Unable to enforce read-only mode.');
    }

    // Fixed queries and fixed response fields prevent caller-controlled SQL.
    $queries = [
        'totalUsers' => 'SELECT COUNT(*) FROM tbl_users',
        'totalScores' => 'SELECT COUNT(*) FROM tbl_user_scores',
        'totalItems' => 'SELECT COUNT(*) FROM tbl_store_items WHERE is_active = 1',
        'activeLabOffers' => 'SELECT COUNT(*) FROM tbl_mouse_lp_store_offers WHERE is_active = 1',
        'publicLabOffers' => "
            SELECT COUNT(*)
            FROM tbl_mouse_lp_store_offers o
            LEFT JOIN tbl_store_items s
              ON o.source_type = 'normal_store'
             AND s.item_id = o.source_item_id
             AND COALESCE(s.is_active, 0) = 1
            LEFT JOIN tbl_genetic_trait_catalog g
              ON o.source_type = 'genetic_item'
             AND g.catalog_id = o.source_item_id
             AND g.is_active = 1
             AND g.is_visible = 1
            WHERE o.is_active = 1
              AND o.stock > 0
              AND (
                (o.source_type = 'normal_store' AND s.item_id IS NOT NULL)
                OR
                (o.source_type = 'genetic_item' AND g.catalog_id IS NOT NULL)
              )
        ",
        'activeQuests' => 'SELECT COUNT(*) FROM tbl_quests WHERE is_active = 1',
    ];

    $metrics = [];

    foreach ($queries as $name => $sql) {
        $result = $db->querySingle($sql);

        if ($result === false || $result === null) {
            throw new RuntimeException("Dashboard metric unavailable: {$name}");
        }

        $metrics[$name] = (int)$result;
    }

    echo json_encode(
        [
            'success' => true,
            'capability' => 'owner_control_dashboard_summary',
            'version' => 1,
            'mode' => 'read_only',
            'writes' => false,
        ] + $metrics,
        JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $error) {
    error_log('[OWNER CONTROL] Dashboard summary failed: ' . $error->getMessage());
    http_response_code(503);

    echo json_encode(
        [
            'success' => false,
            'error' => 'dashboard_unavailable',
        ],
        JSON_UNESCAPED_SLASHES
    );
} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}
