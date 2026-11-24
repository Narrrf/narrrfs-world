<?php
declare(strict_types=1);

define('NARRRFS_SESSION_READ_ONLY', true);

require_once __DIR__ . '/../../../config/admin-authorization.php';

narrrfs_require_admin_request('owner_control.cheese_hunt.read.v1');

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
    $db->busyTimeout(5000);
    if (!$db->exec('PRAGMA query_only = ON')) {
        throw new RuntimeException('Overview unavailable.');
    }

    $hasUsersTable = (int)$db->querySingle(
        "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'tbl_users'"
    ) === 1;
    $hasUsernameCol = false;
    if ($hasUsersTable) {
        $colResult = $db->query('PRAGMA table_info(tbl_users)');
        while ($colResult instanceof SQLite3Result && ($colRow = $colResult->fetchArray(SQLITE3_ASSOC)) !== false) {
            if (($colRow['name'] ?? '') === 'username') {
                $hasUsernameCol = true;
                break;
            }
        }
        if ($colResult instanceof SQLite3Result) {
            $colResult->finalize();
        }
    }

    $missions = [];
    $missionResult = $db->query(
        'SELECT mission_id, description, reward_amount, required_clicks, movement_pattern,
                movement_speed, hidden_areas, winner_message, role_id, expires_at,
                status, version, created_by, created_at, updated_at, activated_at
         FROM tbl_owner_cheese_hunt_missions
         ORDER BY mission_id DESC
         LIMIT 25'
    );
    if (!$missionResult instanceof SQLite3Result) {
        throw new RuntimeException('Overview unavailable.');
    }
    while (($row = $missionResult->fetchArray(SQLITE3_ASSOC)) !== false) {
        $missions[] = $row;
    }
    $missionResult->finalize();

    $userSelect = ($hasUsersTable && $hasUsernameCol) ? 'u.username AS username' : 'NULL AS username';
    $userJoin = ($hasUsersTable && $hasUsernameCol) ? 'LEFT JOIN tbl_users u ON u.discord_id = c.player_id' : '';

    $pendingClaims = [];
    $pendingResult = $db->query(
        "SELECT c.claim_id, c.mission_id, c.player_id, {$userSelect},
                m.description AS mission_description, c.reward_amount, c.role_id,
                c.status, c.version, c.created_at
         FROM tbl_owner_cheese_hunt_claims c
         INNER JOIN tbl_owner_cheese_hunt_missions m ON m.mission_id = c.mission_id
         {$userJoin}
         WHERE c.status = 'pending'
         ORDER BY c.claim_id ASC
         LIMIT 100"
    );
    if (!$pendingResult instanceof SQLite3Result) {
        throw new RuntimeException('Overview unavailable.');
    }
    while (($row = $pendingResult->fetchArray(SQLITE3_ASSOC)) !== false) {
        $pendingClaims[] = $row;
    }
    $pendingResult->finalize();

    $recentClaims = [];
    $recentResult = $db->query(
        "SELECT c.claim_id, c.mission_id, c.player_id, {$userSelect},
                m.description AS mission_description, c.reward_amount, c.role_id,
                c.status, c.reviewed_by, c.review_reason, c.reviewed_at, c.credited_at,
                c.created_at, rd.status AS role_delivery_status
         FROM tbl_owner_cheese_hunt_claims c
         INNER JOIN tbl_owner_cheese_hunt_missions m ON m.mission_id = c.mission_id
         LEFT JOIN tbl_owner_cheese_hunt_role_delivery rd ON rd.claim_id = c.claim_id
         {$userJoin}
         WHERE c.status IN ('approved', 'rejected')
         ORDER BY c.claim_id DESC
         LIMIT 25"
    );
    if (!$recentResult instanceof SQLite3Result) {
        throw new RuntimeException('Overview unavailable.');
    }
    while (($row = $recentResult->fetchArray(SQLITE3_ASSOC)) !== false) {
        $recentClaims[] = $row;
    }
    $recentResult->finalize();

    echo json_encode([
        'success' => true,
        'missions' => $missions,
        'pending_claims' => $pendingClaims,
        'recent_claims' => $recentClaims,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'overview_unavailable']);
} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}
