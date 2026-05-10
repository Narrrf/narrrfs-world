<?php
// 🧬 Get Genetic Marketplace History API
// Returns the active user's Genetic Marketplace trading history.
//
// DEVS FOR DECADES:
// - Read-only endpoint.
// - Does not mutate listings, inventory, DSPOINC, or item history.
// - Shows user-involved marketplace rows only.
// - Seller sees listed / sold / cancelled rows.
// - Buyer sees bought rows.
// - Genetic items are Discord-user-bound, not Genesis NFT-bound.
// - Backend remains authoritative; Lab frontend only displays this response.

error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('UTC');

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$databaseIncludeCandidates = [
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../config/database.php'
];

foreach ($databaseIncludeCandidates as $databaseIncludePath) {
    if (file_exists($databaseIncludePath)) {
        require_once $databaseIncludePath;
        break;
    }
}

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf local fallback

/**
 * Return a JSON response and stop execution.
 */
function json_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running on localhost.
 */
function is_localhost_env(): bool {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data from JSON, GET, or POST.
 */
function get_request_data(): array {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = [];
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            $cached = $json;
        } elseif (!empty($_POST) && is_array($_POST)) {
            $cached = $_POST;
        }
    } else {
        $cached = $_GET ?? [];
    }

    return is_array($cached) ? $cached : [];
}

/**
 * Resolve active Discord user ID.
 *
 * Production:
 * - session-first auth
 *
 * Localhost:
 * - request user_id allowed for safe local testing
 */
function resolve_user_id(): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $request = get_request_data();
    $requestUserId = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $requestUserId !== '') {
        error_log("🧬 Marketplace History: Using request user_id for localhost testing: {$requestUserId}");
        return $requestUserId;
    }

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        error_log("🚨 SECURITY: Marketplace History user_id mismatch. Session={$sessionUserId} Request={$requestUserId}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($sessionUserId !== '') {
        return $sessionUserId;
    }

    if ($isLocalhost) {
        error_log('🧬 Marketplace History: Falling back to local Narrrf Discord ID');
        return $LOCAL_TEST_DISCORD_ID;
    }

    return '';
}

/**
 * Open database connection using project helpers first, then SQLite fallbacks.
 */
function get_marketplace_history_database_connection(): PDO {
    if (function_exists('getDatabaseConnection')) {
        $pdo = getDatabaseConnection();

        if ($pdo instanceof PDO) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA busy_timeout = 5000');
            return $pdo;
        }
    }

    if (function_exists('getDB')) {
        $pdo = getDB();

        if ($pdo instanceof PDO) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA busy_timeout = 5000');
            return $pdo;
        }
    }

    $dbPathCandidates = [];

    if (is_localhost_env()) {
        $dbPathCandidates[] = __DIR__ . '/../../db/narrrf_world.sqlite';
        $dbPathCandidates[] = 'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite';
    }

    $dbPathCandidates[] = __DIR__ . '/../../db/narrrf_world.sqlite';
    $dbPathCandidates[] = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/db/narrrf_world.sqlite';
    $dbPathCandidates[] = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/narrrfs-world/db/narrrf_world.sqlite';
    $dbPathCandidates[] = '/var/www/html/db/narrrf_world.sqlite';
    $dbPathCandidates[] = '/data/narrrf_world.sqlite';

    foreach ($dbPathCandidates as $dbPath) {
        if (!$dbPath || !file_exists($dbPath)) {
            continue;
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        return $pdo;
    }

    throw new Exception('Database connection helper not available and SQLite file not found');
}

/**
 * Check if a SQLite table exists before querying it.
 */
function sqlite_table_exists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("
        SELECT name
        FROM sqlite_master
        WHERE type = 'table'
          AND name = ?
        LIMIT 1
    ");
    $stmt->execute([$tableName]);

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Return available columns for safe older/local DB compatibility.
 */
function get_table_columns(PDO $pdo, string $tableName): array {
    $stmt = $pdo->query("PRAGMA table_info({$tableName})");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $columns = [];
    foreach ($rows as $row) {
        $name = trim((string)($row['name'] ?? ''));
        if ($name !== '') {
            $columns[$name] = true;
        }
    }

    return $columns;
}

/**
 * Return a safe SELECT expression for an optional listing column.
 */
function optional_listing_column(array $columns, string $columnName, string $fallbackSql, string $alias): string {
    if (isset($columns[$columnName])) {
        return "l.{$columnName} AS {$alias}";
    }

    return "{$fallbackSql} AS {$alias}";
}

/**
 * Normalize listing status for display logic.
 */
function normalize_marketplace_status(string $status): string {
    $status = strtolower(trim($status));

    if (in_array($status, ['active', 'sold', 'cancelled'], true)) {
        return $status;
    }

    return $status !== '' ? $status : 'unknown';
}

/**
 * Resolve the user-facing action for this logged-in user.
 */
function resolve_marketplace_action(array $row, string $userId): string {
    $status = normalize_marketplace_status((string)($row['listing_status'] ?? ''));
    $sellerUserId = (string)($row['seller_user_id'] ?? '');
    $buyerUserId = (string)($row['buyer_user_id'] ?? '');

    if ($status === 'sold' && $buyerUserId === $userId) {
        return 'bought';
    }

    if ($status === 'sold' && $sellerUserId === $userId) {
        return 'sold';
    }

    if ($status === 'cancelled' && $sellerUserId === $userId) {
        return 'cancelled';
    }

    if ($status === 'active' && $sellerUserId === $userId) {
        return 'listed';
    }

    return $status;
}

/**
 * Resolve the best timestamp for the history row.
 */
function resolve_marketplace_event_time(array $row, string $action): string {
    if ($action === 'bought' || $action === 'sold') {
        return (string)($row['sold_at'] ?? $row['updated_at'] ?? $row['created_at'] ?? '');
    }

    if ($action === 'cancelled') {
        return (string)($row['cancelled_at'] ?? $row['updated_at'] ?? $row['created_at'] ?? '');
    }

    return (string)($row['created_at'] ?? $row['updated_at'] ?? '');
}

try {
    if (!in_array(($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['GET', 'POST'], true)) {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $userId = resolve_user_id();

    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Authentication required'
        ], 401);
    }

    $request = get_request_data();
    $limit = (int)($request['limit'] ?? 100);
    $statusFilter = strtolower(trim((string)($request['status'] ?? '')));
    $roleFilter = strtolower(trim((string)($request['role'] ?? '')));

    if ($limit < 1) {
        $limit = 100;
    }

    if ($limit > 200) {
        $limit = 200;
    }

    $pdo = get_marketplace_history_database_connection();

    if (!sqlite_table_exists($pdo, 'tbl_genetic_market_listings')) {
        json_response([
            'success' => false,
            'error' => 'Marketplace listings table not found'
        ], 500);
    }

    $listingColumns = get_table_columns($pdo, 'tbl_genetic_market_listings');

    $buyerUserIdSelect = optional_listing_column($listingColumns, 'buyer_user_id', "''", 'buyer_user_id');
    $soldAtSelect = optional_listing_column($listingColumns, 'sold_at', "''", 'sold_at');
    $cancelledAtSelect = optional_listing_column($listingColumns, 'cancelled_at', "''", 'cancelled_at');

    $where = [
        "(l.seller_user_id = ? OR COALESCE(l.buyer_user_id, '') = ?)"
    ];

    $params = [$userId, $userId];

    if ($statusFilter !== '' && in_array($statusFilter, ['active', 'sold', 'cancelled'], true)) {
        $where[] = 'LOWER(l.listing_status) = LOWER(?)';
        $params[] = $statusFilter;
    }

    if ($roleFilter === 'seller') {
        $where[] = 'l.seller_user_id = ?';
        $params[] = $userId;
    }

    if ($roleFilter === 'buyer') {
        $where[] = "COALESCE(l.buyer_user_id, '') = ?";
        $params[] = $userId;
    }

    $whereSql = implode(' AND ', $where);

    $sql = "
        SELECT
            l.listing_id,
            l.seller_user_id,
            {$buyerUserIdSelect},
            l.genetic_item_id,
            l.catalog_id,
            l.trait_type,
            l.trait_value,
            l.item_level_snapshot,
            l.price_dspoinc,
            l.listing_status,
            l.created_at,
            l.updated_at,
            {$soldAtSelect},
            {$cancelledAtSelect},
            c.display_title,
            c.rarity_tier,
            c.image_type,
            c.image_path,
            c.preview_path,
            seller.username AS seller_username,
            seller.avatar_url AS seller_avatar_url,
            buyer.username AS buyer_username,
            buyer.avatar_url AS buyer_avatar_url
        FROM tbl_genetic_market_listings l
        LEFT JOIN tbl_genetic_trait_catalog c
            ON c.catalog_id = l.catalog_id
        LEFT JOIN tbl_users seller
            ON seller.discord_id = l.seller_user_id
        LEFT JOIN tbl_users buyer
            ON buyer.discord_id = COALESCE(l.buyer_user_id, '')
        WHERE {$whereSql}
        ORDER BY
            COALESCE(l.sold_at, l.cancelled_at, l.updated_at, l.created_at) DESC,
            l.listing_id DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $history = [];
    $summary = [
        'total_buys' => 0,
        'total_sells' => 0,
        'total_active_listings' => 0,
        'total_cancelled' => 0,
        'dspoinc_spent' => 0,
        'dspoinc_earned' => 0
    ];

    foreach ($rows as $row) {
        $action = resolve_marketplace_action($row, $userId);
        $status = normalize_marketplace_status((string)($row['listing_status'] ?? ''));
        $price = max(0, (int)($row['price_dspoinc'] ?? 0));

        if ($action === 'bought') {
            $summary['total_buys']++;
            $summary['dspoinc_spent'] += $price;
        }

        if ($action === 'sold') {
            $summary['total_sells']++;
            $summary['dspoinc_earned'] += $price;
        }

        if ($action === 'listed') {
            $summary['total_active_listings']++;
        }

        if ($action === 'cancelled') {
            $summary['total_cancelled']++;
        }

        $history[] = [
            'listing_id' => (int)($row['listing_id'] ?? 0),
            'genetic_item_id' => (int)($row['genetic_item_id'] ?? 0),
            'catalog_id' => (int)($row['catalog_id'] ?? 0),

            'role' => ((string)($row['buyer_user_id'] ?? '') === $userId) ? 'buyer' : 'seller',
            'action' => $action,
            'status' => $status,

            'trait_type' => (string)($row['trait_type'] ?? ''),
            'trait_value' => (string)($row['trait_value'] ?? ''),
            'display_title' => (string)($row['display_title'] ?? $row['trait_value'] ?? 'Genetic Item'),
            'rarity_tier' => (string)($row['rarity_tier'] ?? 'common'),
            'current_level' => (int)($row['item_level_snapshot'] ?? 1),
            'price_dspoinc' => $price,

            'seller_user_id' => (string)($row['seller_user_id'] ?? ''),
            'seller_username' => (string)($row['seller_username'] ?? 'Seller'),
            'seller_avatar_url' => (string)($row['seller_avatar_url'] ?? ''),

            'buyer_user_id' => (string)($row['buyer_user_id'] ?? ''),
            'buyer_username' => (string)($row['buyer_username'] ?? 'Buyer'),
            'buyer_avatar_url' => (string)($row['buyer_avatar_url'] ?? ''),

            'created_at' => (string)($row['created_at'] ?? ''),
            'updated_at' => (string)($row['updated_at'] ?? ''),
            'sold_at' => (string)($row['sold_at'] ?? ''),
            'cancelled_at' => (string)($row['cancelled_at'] ?? ''),
            'event_time' => resolve_marketplace_event_time($row, $action),

            'image_type' => (string)($row['image_type'] ?? 'png'),
            'image_path' => (string)($row['image_path'] ?? ''),
            'preview_path' => (string)($row['preview_path'] ?? '')
        ];
    }

    json_response([
        'success' => true,
        'data' => [
            'summary' => $summary,
            'history' => $history,
            'limit' => $limit
        ]
    ]);
} catch (Throwable $e) {
    error_log('🧬 Get Genetic Marketplace History ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to load marketplace history',
        'details' => $e->getMessage()
    ], 500);
}
?>