<?php
declare(strict_types=1);

// Owner Control R4.1: privacy-minimized user search, no economic state.
// DEVS FOR DECADES: authorization precedes database access; only fixed SELECTs.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.user_search.v1');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') {
    exit;
}

// Reject invalid query parameters without reflecting private search terms.
function narrrfs_owner_r4_reject(string $code): never
{
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $code], JSON_UNESCAPED_SLASHES);
    exit;
}

$rawQuery = $_GET['q'] ?? null;
if (!is_string($rawQuery)) {
    narrrfs_owner_r4_reject('invalid_query');
}
$query = trim($rawQuery);
if (strlen($query) < 2 || strlen($query) > 64 || preg_match('/[\x00-\x1F\x7F]/', $query)) {
    narrrfs_owner_r4_reject('invalid_query');
}
if (!preg_match('//u', $query)) {
    narrrfs_owner_r4_reject('invalid_query');
}
$rawOffset = $_GET['offset'] ?? '0';
if (!is_string($rawOffset) || !preg_match('/^\d{1,6}$/D', $rawOffset)) {
    narrrfs_owner_r4_reject('invalid_pagination');
}
$offset = (int)$rawOffset;
if ($offset > 100000) {
    narrrfs_owner_r4_reject('invalid_pagination');
}
$limit = 25;
$isId = (bool)preg_match('/^\d{17,20}$/D', $query);
// Escape LIKE wildcards and the escape character itself.
$literalUsername = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $query);

require_once __DIR__ . '/../../config/database.php';
$db = null;
try {
    $path = getDatabasePath();
    if (!is_file($path)) {
        throw new RuntimeException('Database unavailable.');
    }
    $db = new SQLite3($path, SQLITE3_OPEN_READONLY);
    $db->busyTimeout(5000);
    if (!$db->exec('PRAGMA query_only = ON')) {
        throw new RuntimeException('Read-only enforcement failed.');
    }
    $where = $isId
        ? 'discord_id = :search'
        : "username LIKE :search ESCAPE '\\' COLLATE NOCASE";
    $statement = $db->prepare(
        'SELECT discord_id, username, created_at FROM tbl_users '
        . 'WHERE ' . $where . ' ORDER BY username COLLATE NOCASE ASC, discord_id ASC '
        . 'LIMIT :limit OFFSET :offset'
    );
    if ($statement === false) {
        throw new RuntimeException('Query preparation failed.');
    }
    $statement->bindValue(':search', $isId ? $query : '%' . $literalUsername . '%', SQLITE3_TEXT);
    $statement->bindValue(':limit', $limit + 1, SQLITE3_INTEGER);
    $statement->bindValue(':offset', $offset, SQLITE3_INTEGER);
    $result = $statement->execute();
    if ($result === false) {
        throw new RuntimeException('User search unavailable.');
    }
    $rows = [];
    while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
        $rows[] = [
            'discord_id' => (string)$row['discord_id'],
            'username' => (string)$row['username'],
            'created_at' => is_string($row['created_at']) && trim($row['created_at']) !== ''
                ? $row['created_at'] : null,
        ];
    }
    $hasMore = count($rows) > $limit;
    if ($hasMore) {
        array_pop($rows);
    }
    echo json_encode([
        'success' => true,
        'capability' => 'owner_control_user_search',
        'version' => 1,
        'mode' => 'read_only',
        'writes' => false,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'returned' => count($rows),
            'has_more' => $hasMore,
        ],
        'users' => $rows,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    error_log('[OWNER CONTROL] R4 user search unavailable: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'search_unavailable'], JSON_UNESCAPED_SLASHES);
} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}
