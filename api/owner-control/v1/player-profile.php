<?php
declare(strict_types=1);
// Owner Control R5.1: account, recorded game activity, regular inventory, role and genetic slot visibility.
// DEVS FOR DECADES: user-specific SELECT-only views are not financial or progression authority.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.profile_read.v1');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') { exit; }
$userId = $_GET['user_id'] ?? null;
if (!is_string($userId) || preg_match('/^\d{17,20}$/D', $userId) !== 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_user_id']);
    exit;
}
$db = null;
try {
    require_once __DIR__ . '/../../config/database.php';
    $path = getDatabasePath();
    if (!is_file($path)) { throw new RuntimeException('Database unavailable.'); }
    $db = new SQLite3($path, SQLITE3_OPEN_READONLY);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    if (!$db->exec('PRAGMA query_only = ON')) { throw new RuntimeException('Read-only enforcement failed.'); }

    // Executes one verified, fixed SQL statement for one Discord user. No dynamic SQL supplied by clients.
    function r51_rows(SQLite3 $db, string $sql, string $userId, int $limit = 0): array {
        $query = $db->prepare($sql);
        if ($query === false) { throw new RuntimeException('Query preparation failed.'); }
        $query->bindValue(':id', $userId, SQLITE3_TEXT);
        if ($limit > 0) { $query->bindValue(':limit', $limit, SQLITE3_INTEGER); }
        $result = $query->execute();
        if ($result === false) { throw new RuntimeException('Query execution failed.'); }
        $rows = [];
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) { $rows[] = $row; }
        return $rows;
    }

    $user = r51_rows($db, 'SELECT discord_id, username, avatar_url, created_at FROM tbl_users WHERE discord_id = :id LIMIT 1', $userId);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'player_not_found']);
        exit;
    }
    $roles = r51_rows($db, 'SELECT role_name, timestamp FROM tbl_user_roles WHERE user_id = :id ORDER BY role_name COLLATE NOCASE ASC LIMIT :limit', $userId, 25);
    // COUNT denotes score ledger entries, not finished matches or prize transactions.
    $gameSummary = r51_rows($db, 'SELECT COALESCE(game, \'Unknown\') AS game, COUNT(*) AS entry_count, MAX(timestamp) AS latest_record FROM tbl_user_scores WHERE user_id = :id GROUP BY COALESCE(game, \'Unknown\') ORDER BY entry_count DESC, game ASC LIMIT :limit', $userId, 25);
    $recentScores = r51_rows($db, 'SELECT id, game, score, source, season, timestamp FROM tbl_user_scores WHERE user_id = :id ORDER BY timestamp DESC, id DESC LIMIT :limit', $userId, 25);
    $inventory = r51_rows($db, 'SELECT ui.inventory_id, ui.item_id, ui.quantity, ui.acquired_at, si.item_name, si.description, si.image_url FROM tbl_user_inventory ui JOIN tbl_store_items si ON ui.item_id = si.item_id WHERE ui.user_id = :id ORDER BY ui.acquired_at DESC, ui.inventory_id DESC LIMIT :limit', $userId, 25);
    $achievements = r51_rows($db, 'SELECT achievement_id, season_id, game, highest_score, dspoinc_earned, achieved_at FROM tbl_user_season_achievements WHERE user_id = :id ORDER BY achieved_at DESC, achievement_id DESC LIMIT :limit', $userId, 25);

    // Compatible with the verified genetic-upgrade-slot helper: missing/invalid entitlement means one slot.
    // Both upgrading and ready_to_claim occupy capacity. No timers or permanent state are updated here.
    $slotLimit = 1;
    $table = $db->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'tbl_user_genetic_upgrade_slots'");
    if ($table === false) { throw new RuntimeException('Entitlement table inspection failed.'); }
    if ($table->fetchArray(SQLITE3_ASSOC) !== false) {
        $limits = r51_rows($db, 'SELECT upgrade_slots FROM tbl_user_genetic_upgrade_slots WHERE user_id = :id LIMIT 1', $userId);
        $value = (string)($limits[0]['upgrade_slots'] ?? '');
        if (preg_match('/^[1-3]$/D', trim($value)) === 1) { $slotLimit = (int)$value; }
    }
    $occupied = r51_rows($db, "SELECT COUNT(*) AS occupied FROM tbl_user_genetic_items WHERE user_id = :id AND upgrade_status IN ('upgrading', 'ready_to_claim')", $userId);
    $slotOccupied = (int)$occupied[0]['occupied'];

    echo json_encode([
        'success' => true,
        'capability' => 'owner_control_player_profile',
        'version' => 1,
        'mode' => 'read_only',
        'writes' => false,
        'player' => $user[0],
        'roles' => $roles,
        'game_summary' => $gameSummary,
        'recent_score_entries' => $recentScores,
        'regular_inventory' => $inventory,
        'season_achievements' => $achievements,
        'genetic_upgrade_slots' => [
            'limit' => $slotLimit,
            'occupied' => $slotOccupied,
            'available' => max(0, $slotLimit - $slotOccupied)
        ],
        'limits' => ['roles' => 25, 'game_summary' => 25, 'recent_score_entries' => 25, 'regular_inventory' => 25, 'season_achievements' => 25]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    error_log('[OWNER CONTROL] R5.1 profile unavailable: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'profile_unavailable']);
} finally {
    if ($db instanceof SQLite3) { $db->close(); }
}
