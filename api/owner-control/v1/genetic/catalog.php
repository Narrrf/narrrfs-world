<?php
declare(strict_types=1);

// Narrrfs World Owner Control - Genetic Catalog Read V1.
// This route deliberately reads only catalog presentation metadata. It cannot
// become a shortcut to ownership, effects, pricing, inventory, or progression.

define('NARRRFS_SESSION_READ_ONLY', true);

require_once __DIR__ . '/../../../config/admin-authorization.php';

narrrfs_require_admin_request('owner_control.genetic_catalog.read.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') {
    exit;
}

/** @return array{limit:int,offset:int,traitTypeAll:int,traitTypeExact:string} */
function genetic_catalog_read_query(): array
{
    $allowedKeys = ['limit' => true, 'cursor' => true, 'trait_type' => true];
    foreach ($_GET as $key => $value) {
        if (!is_string($key) || !isset($allowedKeys[$key]) || is_array($value)) {
            throw new InvalidArgumentException('Invalid catalog query.');
        }
    }

    $limit = 25;
    if (array_key_exists('limit', $_GET)) {
        $rawLimit = $_GET['limit'];
        if (!is_string($rawLimit) || !preg_match('/^(?:[1-9]|1[0-9]|2[0-5])$/', $rawLimit)) {
            throw new InvalidArgumentException('Invalid catalog limit.');
        }
        $limit = (int)$rawLimit;
    }

    $offset = 0;
    if (array_key_exists('cursor', $_GET)) {
        $rawCursor = $_GET['cursor'];
        if (!is_string($rawCursor) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $rawCursor)) {
            throw new InvalidArgumentException('Invalid catalog cursor.');
        }
        $decoded = base64_decode(strtr($rawCursor, '-_', '+/'), true);
        if (!is_string($decoded) || !preg_match('/^v1:(?:0|[1-9][0-9]{0,4})$/', $decoded)) {
            throw new InvalidArgumentException('Invalid catalog cursor.');
        }
        $offset = (int)substr($decoded, 3);
        if ($offset > 10000) {
            throw new InvalidArgumentException('Invalid catalog cursor.');
        }
    }

    $traitType = $_GET['trait_type'] ?? 'all';
    $types = ['all' => ['', 1], 'outfit' => ['Outfit', 0], 'accessories' => ['Accessories', 0]];
    if (!is_string($traitType) || !isset($types[$traitType])) {
        throw new InvalidArgumentException('Invalid catalog type.');
    }

    return [
        'limit' => $limit,
        'offset' => $offset,
        'traitTypeAll' => $types[$traitType][1],
        'traitTypeExact' => $types[$traitType][0],
    ];
}

function genetic_catalog_presentation_ref(string $value): ?string
{
    $value = trim($value);
    if ($value === '' || !preg_match('/^[\x20-\x7E]+$/', $value) ||
        str_contains($value, '%') || str_contains($value, '://') || str_contains($value, '..') ||
        str_contains($value, '\\') || str_contains($value, '?') || str_contains($value, '#') ||
        str_contains($value, '//')) {
        return null;
    }

    if (str_starts_with($value, 'img/genetic/')) {
        $value = '/public/' . $value;
    }

    $part = '[A-Za-z0-9][A-Za-z0-9._-]*(?:/[A-Za-z0-9][A-Za-z0-9._-]*)*';
    $patterns = [
        '#^/public/img/genetic/glb/' . $part . '\\.glb$#i',
        '#^/public/img/genetic/previews/' . $part . '\\.(?:png|jpe?g|webp|gif)$#i',
        '#^/public/three\\.js/public/genetic/(?!previews/)' . $part . '\\.glb$#i',
        '#^/public/three\\.js/public/genetic/previews/' . $part . '\\.(?:png|jpe?g|webp|gif)$#i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value) === 1) {
            return $value;
        }
    }

    return null;
}

/** @param list<array<string, mixed>> $rows */
function genetic_catalog_payload(array $rows, int $limit, int $offset): array
{
    $hasMore = count($rows) > $limit;
    if ($hasMore) {
        array_pop($rows);
    }

    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'catalog_id' => (int)$row['catalog_id'],
            'trait_type' => (string)$row['trait_type'],
            'trait_value' => (string)$row['trait_value'],
            'display_title' => (string)$row['display_title'],
            'description' => (string)$row['description'],
            'rarity_tier' => (string)$row['rarity_tier'],
            'image_type' => (string)$row['image_type'],
            'presentation_ref' => genetic_catalog_presentation_ref((string)$row['preview_path']),
            'is_active' => (bool)$row['is_active'],
            'is_visible' => (bool)$row['is_visible'],
        ];
    }

    return [
        'success' => true,
        'capability' => 'owner_control_genetic_catalog_read',
        'version' => 1,
        'mode' => 'read_only',
        'writes' => false,
        'items' => $items,
        'page' => [
            'limit' => $limit,
            'has_more' => $hasMore,
            'next_cursor' => $hasMore
                ? rtrim(strtr(base64_encode('v1:' . ($offset + $limit)), '+/', '-_'), '=')
                : null,
        ],
    ];
}

try {
    $query = genetic_catalog_read_query();
    require_once __DIR__ . '/../../../config/database.php';
    $databasePath = getDatabasePath();
    if (!is_file($databasePath) || !is_readable($databasePath)) {
        throw new RuntimeException('Catalog unavailable.');
    }

    $db = new SQLite3($databasePath, SQLITE3_OPEN_READONLY);
    try {
        if (!$db->exec('PRAGMA query_only = ON')) {
            throw new RuntimeException('Catalog unavailable.');
        }
        $statement = $db->prepare(
            'SELECT catalog_id, trait_type, trait_value, display_title, description, rarity_tier, image_type, preview_path, is_active, is_visible
             FROM tbl_genetic_trait_catalog
             WHERE (:trait_type_all = 1 OR trait_type = :trait_type_exact)
             ORDER BY trait_type ASC, display_title COLLATE NOCASE ASC, catalog_id ASC
             LIMIT :fetch_limit OFFSET :row_offset'
        );
        if (!$statement instanceof SQLite3Stmt) {
            throw new RuntimeException('Catalog unavailable.');
        }
        $statement->bindValue(':trait_type_all', $query['traitTypeAll'], SQLITE3_INTEGER);
        $statement->bindValue(':trait_type_exact', $query['traitTypeExact'], SQLITE3_TEXT);
        $statement->bindValue(':fetch_limit', $query['limit'] + 1, SQLITE3_INTEGER);
        $statement->bindValue(':row_offset', $query['offset'], SQLITE3_INTEGER);
        $result = $statement->execute();
        if (!$result instanceof SQLite3Result) {
            throw new RuntimeException('Catalog unavailable.');
        }
        $rows = [];
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $rows[] = $row;
        }
        $result->finalize();
        $statement->close();
        echo json_encode(genetic_catalog_payload($rows, $query['limit'], $query['offset']), JSON_UNESCAPED_SLASHES);
    } finally {
        $db->close();
    }
} catch (InvalidArgumentException) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_catalog_request'], JSON_UNESCAPED_SLASHES);
} catch (Throwable) {
    error_log('[OWNER CONTROL] Genetic catalog read unavailable.');
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'genetic_catalog_unavailable'], JSON_UNESCAPED_SLASHES);
}
