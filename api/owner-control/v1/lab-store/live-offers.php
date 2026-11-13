<?php
declare(strict_types=1);

// M2-R is operational visibility only. R0 remains intentionally redacted.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.lab_store_live_offers.read.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') {
    exit;
}

function lab_store_live_offers_safe_media(string $value, string $sourceType): ?string
{
    $value = trim($value);
    if ($value === '' || !preg_match('/^[\x20-\x7E]+$/', $value) || str_contains($value, '%') || str_contains($value, '://') || str_contains($value, '..') || str_contains($value, '\\') || str_contains($value, '?') || str_contains($value, '#') || str_contains($value, '//')) {
        return null;
    }
    if (str_starts_with($value, 'img/')) {
        $value = '/public/' . $value;
    }
    $part = '[A-Za-z0-9][A-Za-z0-9._-]*(?:/[A-Za-z0-9][A-Za-z0-9._-]*)*';
    $suffix = '\\.(?:png|jpe?g|webp|gif)$';
    $patterns = $sourceType === 'normal_store'
        ? ['#^/public/img/' . $part . $suffix . '#i']
        : ['#^/public/img/genetic/previews/' . $part . $suffix . '#i', '#^/public/three\\.js/public/genetic/previews/' . $part . $suffix . '#i'];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value) === 1) {
            return $value;
        }
    }
    return null;
}

function lab_store_live_offers_positive_int(?string $raw, int $fallback, int $maximum): int
{
    if ($raw === null || $raw === '') {
        return $fallback;
    }
    if (!preg_match('/^[1-9][0-9]*$/', $raw)) {
        throw new InvalidArgumentException('invalid_query');
    }
    return min((int) $raw, $maximum);
}

try {
    $limit = lab_store_live_offers_positive_int($_GET['limit'] ?? null, 25, 25);
    $cursor = lab_store_live_offers_positive_int($_GET['cursor'] ?? null, 0, PHP_INT_MAX);
    require_once __DIR__ . '/../../../config/database.php';
    $path = getDatabasePath();
    if (!is_file($path) || !is_readable($path)) {
        throw new RuntimeException();
    }
    $db = new SQLite3($path, SQLITE3_OPEN_READONLY);
    try {
        if (!$db->exec('PRAGMA query_only = ON')) {
            throw new RuntimeException();
        }
        $statement = $db->prepare("SELECT o.offer_id, o.source_type, o.lp_price, o.dspoinc_price, o.stock, CASE WHEN o.is_active=1 THEN 1 ELSE 0 END AS offer_active, s.item_name AS normal_title, s.image_url AS normal_image, CASE WHEN COALESCE(s.is_active,0)=1 THEN 1 ELSE 0 END AS normal_available, g.display_title AS genetic_title, g.preview_path AS genetic_preview, CASE WHEN g.is_active=1 AND g.is_visible=1 THEN 1 ELSE 0 END AS genetic_available FROM tbl_mouse_lp_store_offers o LEFT JOIN tbl_store_items s ON o.source_type='normal_store' AND s.item_id=o.source_item_id LEFT JOIN tbl_genetic_trait_catalog g ON o.source_type='genetic_item' AND g.catalog_id=o.source_item_id WHERE o.source_type IN ('normal_store','genetic_item') AND o.offer_id > :cursor ORDER BY o.offer_id ASC LIMIT :limit");
        if (!$statement instanceof SQLite3Stmt) {
            throw new RuntimeException();
        }
        $statement->bindValue(':cursor', $cursor, SQLITE3_INTEGER);
        $statement->bindValue(':limit', $limit + 1, SQLITE3_INTEGER);
        $result = $statement->execute();
        if (!$result instanceof SQLite3Result) {
            throw new RuntimeException();
        }
        $offers = [];
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $normal = $row['source_type'] === 'normal_store';
            $reference = lab_store_live_offers_safe_media((string) ($normal ? $row['normal_image'] : $row['genetic_preview']), (string) $row['source_type']);
            $title = trim((string) ($normal ? $row['normal_title'] : $row['genetic_title']));
            $offers[] = [
                'offer_id' => (int) $row['offer_id'],
                'title' => $title === '' ? 'Untitled item' : $title,
                'category' => $normal ? 'Normal Store Item' : 'Genetic Item',
                'source_type' => (string) $row['source_type'],
                'offer_active' => (bool) $row['offer_active'],
                'source_available' => $normal ? (bool) $row['normal_available'] : (bool) $row['genetic_available'],
                'lp_price' => $row['lp_price'] === null ? null : (int) $row['lp_price'],
                'dspoinc_price' => $row['dspoinc_price'] === null ? null : (int) $row['dspoinc_price'],
                'stock' => (int) $row['stock'],
                'presentation_ref' => $reference,
                'media_kind' => $reference === null ? 'media_unavailable' : 'image',
            ];
        }
        $result->finalize();
        $hasMore = count($offers) > $limit;
        if ($hasMore) {
            array_pop($offers);
        }
        $nextCursor = $hasMore ? (string) $offers[count($offers) - 1]['offer_id'] : null;
        echo json_encode(['success' => true, 'capability' => 'owner_control_lab_store_live_offers_read', 'version' => 1, 'mode' => 'read_only', 'writes' => false, 'page' => ['limit' => $limit, 'next_cursor' => $nextCursor], 'offers' => $offers], JSON_UNESCAPED_SLASHES);
    } finally {
        $db->close();
    }
} catch (InvalidArgumentException) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_query'], JSON_UNESCAPED_SLASHES);
} catch (Throwable) {
    error_log('[OWNER CONTROL] Lab Store live offers read unavailable.');
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'lab_store_live_offers_unavailable'], JSON_UNESCAPED_SLASHES);
}
