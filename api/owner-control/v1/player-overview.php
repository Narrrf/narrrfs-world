<?php
declare(strict_types=1);
// Owner Control R4.3: selected-player financial source display, SELECT ONLY.
// DEVS FOR DECADES: the score-ledger calculation is informational, not an authorization to spend or settle funds.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.player_overview.v1');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') { exit; }

$userId = $_GET['user_id'] ?? null;
$rawOffset = $_GET['offset'] ?? '0';
if (!is_string($userId) || preg_match('/^\d{17,20}$/D', $userId) !== 1 ||
    !is_string($rawOffset) || preg_match('/^\d{1,6}$/D', $rawOffset) !== 1 || (int)$rawOffset > 100000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_request']);
    exit;
}
$offset = (int)$rawOffset;
$historyLimit = 20;
$db = null;
try {
    require_once __DIR__ . '/../../config/database.php';
    $path = getDatabasePath();
    if (!is_file($path)) { throw new RuntimeException('Database unavailable.'); }
    $db = new SQLite3($path, SQLITE3_OPEN_READONLY);
    $db->busyTimeout(5000);
    if (!$db->exec('PRAGMA query_only = ON')) { throw new RuntimeException('Read-only enforcement failed.'); }

    $user = $db->prepare('SELECT discord_id, username, created_at FROM tbl_users WHERE discord_id = :id LIMIT 1');
    if (!$user) { throw new RuntimeException('User lookup preparation failed.'); }
    $user->bindValue(':id', $userId, SQLITE3_TEXT);
    $person = $user->execute()->fetchArray(SQLITE3_ASSOC);
    if (!$person) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'player_not_found']);
        exit;
    }
    // Mirrors the existing profile display formula; do not treat as a spend/claim permission.
    $balance = $db->prepare('SELECT COALESCE(SUM(score), 0) AS total FROM tbl_user_scores WHERE user_id = :id');
    $balance->bindValue(':id', $userId, SQLITE3_TEXT);
    $total = (int)$balance->execute()->fetchArray(SQLITE3_ASSOC)['total'];
    // DEVS FOR DECADES: match the legacy profile's missing-stakes-table
    // display fallback. A missing table is not the same as a query failure.
    $frozen = 0;
    $stakeCount = 0;
    $stakeRows = [];
    $stakesTable = $db->query(
        "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'tbl_dspoinc_stakes'"
    );
    if ($stakesTable === false) {
        throw new RuntimeException('Staking table inspection failed.');
    }
    if ($stakesTable->fetchArray(SQLITE3_ASSOC) !== false) {
        $frozenQuery = $db->prepare("SELECT COALESCE(SUM(amount), 0) AS frozen, COUNT(*) AS active_count FROM tbl_dspoinc_stakes WHERE user_id = :id AND status = 'active'");
        if ($frozenQuery === false) {
            throw new RuntimeException('Frozen principal query preparation failed.');
        }
        $frozenQuery->bindValue(':id', $userId, SQLITE3_TEXT);
        $frozenResultSet = $frozenQuery->execute();
        if ($frozenResultSet === false) {
            throw new RuntimeException('Frozen principal query failed.');
        }
        $frozenResult = $frozenResultSet->fetchArray(SQLITE3_ASSOC);
        if ($frozenResult === false) {
            throw new RuntimeException('Frozen principal result unavailable.');
        }
        $frozen = (int)$frozenResult['frozen'];
        $stakeCount = (int)$frozenResult['active_count'];

        $stakes = $db->prepare("SELECT id, amount, status, frozen_at, unfreeze_at, staking_contract_version, lock_duration_days FROM tbl_dspoinc_stakes WHERE user_id = :id ORDER BY CASE status WHEN 'active' THEN 0 ELSE 1 END, id DESC LIMIT 25");
        if ($stakes === false) {
            throw new RuntimeException('Staking contracts query preparation failed.');
        }
        $stakes->bindValue(':id', $userId, SQLITE3_TEXT);
        $stakeResult = $stakes->execute();
        if ($stakeResult === false) {
            throw new RuntimeException('Staking contracts query failed.');
        }
        while (($stake = $stakeResult->fetchArray(SQLITE3_ASSOC)) !== false) {
            $stakeRows[] = [
                'id' => (int)$stake['id'], 'amount' => (int)$stake['amount'],
                'status' => (string)$stake['status'], 'frozen_at' => $stake['frozen_at'],
                'unfreeze_at' => $stake['unfreeze_at'],
                'version' => (string)$stake['staking_contract_version'],
                'lock_duration_days' => $stake['lock_duration_days'] === null ? null : (int)$stake['lock_duration_days']
            ];
        }
    }
    // Score adjustments are only one history category; no claim of full transaction coverage.
    $history = $db->prepare('SELECT id, amount, action, reason, timestamp FROM tbl_score_adjustments WHERE user_id = :id ORDER BY timestamp DESC, id DESC LIMIT :limit OFFSET :offset');
    $history->bindValue(':id', $userId, SQLITE3_TEXT);
    $history->bindValue(':limit', $historyLimit + 1, SQLITE3_INTEGER);
    $history->bindValue(':offset', $offset, SQLITE3_INTEGER);
    $historyResult = $history->execute();
    $historyRows = [];
    while (($item = $historyResult->fetchArray(SQLITE3_ASSOC)) !== false) {
        $historyRows[] = ['id' => (int)$item['id'], 'amount' => (int)$item['amount'],
            'action' => (string)$item['action'], 'reason' => (string)$item['reason'], 'timestamp' => $item['timestamp']];
    }
    $hasMore = count($historyRows) > $historyLimit;
    if ($hasMore) { array_pop($historyRows); }
    echo json_encode([
        'success' => true, 'capability' => 'owner_control_player_overview', 'mode' => 'read_only', 'writes' => false,
        'player' => ['discord_id' => (string)$person['discord_id'], 'username' => (string)$person['username']],
        'score_ledger' => ['total' => $total, 'frozen_active_principal' => $frozen,
            'available_display' => max(0, $total - $frozen), 'active_staking_contracts' => $stakeCount,
            'definition' => 'existing_profile_display_formula_not_settlement_authority'],
        'contracts' => ['returned' => count($stakeRows), 'limit' => 25, 'rows' => $stakeRows],
        'score_adjustment_history' => ['offset' => $offset, 'limit' => $historyLimit,
            'has_more' => $hasMore, 'rows' => $historyRows],
    ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    error_log('[OWNER CONTROL] R4.3 overview unavailable: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'overview_unavailable']);
} finally {
    if ($db instanceof SQLite3) { $db->close(); }
}
