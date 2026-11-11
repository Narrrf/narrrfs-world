<?php
declare(strict_types=1);

// M1.2a: read-only source picker for private Lab Store drafts. It is not a
// catalog editor, offer reader, purchase, fulfilment, or inventory API.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.lab_store_drafts.catalog_read.v1');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') exit;

function draft_source_media_reference(string $value, string $type): ?string {
    $value = trim($value);
    if ($value === '' || !preg_match('/^[\x20-\x7E]+$/', $value) || str_contains($value, '%') || str_contains($value, '://') || str_contains($value, '..') || str_contains($value, '\\') || str_contains($value, '?') || str_contains($value, '#') || str_contains($value, '//')) return null;
    if (str_starts_with($value, 'img/')) $value = '/public/' . $value;
    $part = '[A-Za-z0-9][A-Za-z0-9._-]*(?:/[A-Za-z0-9][A-Za-z0-9._-]*)*'; $image = '\\.(?:png|jpe?g|webp|gif)$';
    $patterns = $type === 'normal_store' ? ['#^/public/(?:img/|store-items/)' . $part . $image . '#i'] : ['#^/public/img/genetic/previews/' . $part . $image . '#i', '#^/public/three\\.js/public/genetic/previews/' . $part . $image . '#i'];
    foreach ($patterns as $pattern) if (preg_match($pattern, $value) === 1) return $value;
    return null;
}

/** @return array{type:string,query:string,limit:int,offset:int} */
function draft_source_query(): array {
    foreach ($_GET as $key => $value) if (!is_string($key) || !in_array($key, ['type', 'q', 'limit', 'cursor'], true) || is_array($value)) throw new InvalidArgumentException();
    $type = $_GET['type'] ?? null; $query = $_GET['q'] ?? '';
    if (!is_string($query)) throw new InvalidArgumentException();
    $query = trim($query);
    if (!is_string($type) || !in_array($type, ['normal_store', 'genetic_item'], true) || ($query !== '' && !preg_match('/^[\x20-\x7E]{2,80}$/', $query))) throw new InvalidArgumentException();
    $limit = 20; if (isset($_GET['limit'])) { if (!is_string($_GET['limit']) || !preg_match('/^(?:[1-9]|1[0-9]|2[0-5])$/', $_GET['limit'])) throw new InvalidArgumentException(); $limit = (int) $_GET['limit']; }
    $offset = 0; if (isset($_GET['cursor'])) { if (!is_string($_GET['cursor']) || !preg_match('/^(?:0|[1-9][0-9]{0,4})$/', $_GET['cursor'])) throw new InvalidArgumentException(); $offset = (int) $_GET['cursor']; }
    return ['type' => $type, 'query' => $query, 'limit' => $limit, 'offset' => $offset];
}

try {
    $query = draft_source_query(); require_once __DIR__ . '/../../../config/database.php'; $path = getDatabasePath();
    if (!is_file($path) || !is_readable($path)) throw new RuntimeException();
    $db = new SQLite3($path, SQLITE3_OPEN_READONLY);
    try {
        if (!$db->exec('PRAGMA query_only = ON')) throw new RuntimeException();
        $filter = $query['query'] === '' ? '' : ($query['type'] === 'normal_store' ? " WHERE item_name LIKE :pattern ESCAPE '\\'" : " WHERE display_title LIKE :pattern ESCAPE '\\'");
        $sql = $query['type'] === 'normal_store'
            ? "SELECT item_id AS source_item_id, item_name AS title, image_url AS media_value, CASE WHEN is_active=1 THEN 'available' ELSE 'unavailable' END AS source_state FROM tbl_store_items" . $filter . " ORDER BY item_name COLLATE NOCASE ASC, item_id ASC LIMIT :fetch_limit OFFSET :row_offset"
            : "SELECT catalog_id AS source_item_id, display_title AS title, preview_path AS media_value, CASE WHEN is_active=1 AND is_visible=1 THEN 'available' ELSE 'unavailable' END AS source_state FROM tbl_genetic_trait_catalog" . $filter . " ORDER BY display_title COLLATE NOCASE ASC, catalog_id ASC LIMIT :fetch_limit OFFSET :row_offset";
        $statement = $db->prepare($sql); if (!$statement instanceof SQLite3Stmt) throw new RuntimeException();
        if ($query['query'] !== '') { $pattern = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query['query']) . '%'; $statement->bindValue(':pattern', $pattern, SQLITE3_TEXT); }
        $statement->bindValue(':fetch_limit', $query['limit'] + 1, SQLITE3_INTEGER); $statement->bindValue(':row_offset', $query['offset'], SQLITE3_INTEGER);
        $result = $statement->execute(); if (!$result instanceof SQLite3Result) throw new RuntimeException(); $rows = [];
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) $rows[] = $row;
        $result->finalize(); $statement->close(); $hasMore = count($rows) > $query['limit']; if ($hasMore) array_pop($rows);
        $items = array_map(static fn(array $row): array => ['source_type' => $query['type'], 'source_item_id' => (int) $row['source_item_id'], 'title' => (string) $row['title'], 'presentation_ref' => draft_source_media_reference((string) $row['media_value'], $query['type']), 'source_state' => (string) $row['source_state']], $rows);
        echo json_encode(['success' => true, 'capability' => 'owner_control_lab_store_draft_sources_read', 'version' => 1, 'mode' => 'read_only', 'writes' => false, 'items' => $items, 'page' => ['limit' => $query['limit'], 'has_more' => $hasMore, 'next_cursor' => $hasMore ? (string) ($query['offset'] + $query['limit']) : null]], JSON_UNESCAPED_SLASHES);
    } finally { $db->close(); }
} catch (InvalidArgumentException) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'invalid_source_query']);
} catch (Throwable) { error_log('[OWNER CONTROL] Lab Store draft source read unavailable.'); http_response_code(503); echo json_encode(['success' => false, 'error' => 'lab_store_draft_sources_unavailable']); }
