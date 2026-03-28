<?php
// 🧬 Get Genetic Marketplace Listings API
// Returns active marketplace listings for the Lab marketplace tab.
//
// Stable-first rules:
// - Read-only endpoint
// - Public preview is allowed
// - Returns active listings only by default
// - Preserves catalog/media + owned level snapshot for frontend cards

error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('UTC');

header('Content-Type: application/json');

/**
 * Resolve the SQLite database path in local/dev and server environments.
 */
function resolve_sqlite_database_path() {
    $possible_paths = [
        dirname(__DIR__) . '/../db/narrrf_world.sqlite',
        dirname(dirname(__DIR__)) . '/db/narrrf_world.sqlite',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/db/narrrf_world.sqlite',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/narrrfs-world/db/narrrf_world.sqlite',
        '/var/www/html/db/narrrf_world.sqlite',
        '/data/narrrf_world.sqlite'
    ];

    foreach ($possible_paths as $path) {
        if ($path && file_exists($path)) {
            return $path;
        }
    }

    throw new Exception('SQLite database file not found in known locations');
}

/**
 * Open the marketplace database connection.
 *
 * Stable-first rules:
 * - Uses SQLite directly for local/dev compatibility
 * - Returns PDO with exceptions enabled
 */
function getMarketplaceDatabaseConnection() {
    $dbPath = resolve_sqlite_database_path();

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

session_start();

/**
 * Send JSON response and stop execution.
 */
function json_response($payload, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $pdo = getMarketplaceDatabaseConnection();

    $traitType = trim((string)($_GET['trait_type'] ?? ''));
    $rarityTier = trim((string)($_GET['rarity_tier'] ?? ''));
    $search = trim((string)($_GET['search'] ?? ''));
    $limit = (int)($_GET['limit'] ?? 100);

    if ($limit < 1) {
        $limit = 100;
    }
    if ($limit > 200) {
        $limit = 200;
    }

    $where = ["l.listing_status = 'active'"];
    $params = [];

    if ($traitType !== '') {
        $where[] = "l.trait_type = ?";
        $params[] = $traitType;
    }

    if ($rarityTier !== '') {
        $where[] = "LOWER(COALESCE(c.rarity_tier, 'common')) = LOWER(?)";
        $params[] = $rarityTier;
    }

    if ($search !== '') {
$where[] = "(
    LOWER(COALESCE(c.display_title, '')) LIKE LOWER(?)
    OR LOWER(COALESCE(l.trait_value, '')) LIKE LOWER(?)
    OR LOWER(COALESCE(l.trait_type, '')) LIKE LOWER(?)
)";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = implode(' AND ', $where);

$sql = "
    SELECT
        l.listing_id,
        l.seller_user_id,
        l.genetic_item_id,
        l.catalog_id,
        l.trait_type,
        l.trait_value,
        l.item_level_snapshot,
        l.price_dspoinc,
        l.listing_status,
        l.created_at,
        l.updated_at,
        l.sold_at,
        c.display_title,
        c.rarity_tier,
        c.image_type,
        c.image_path,
        c.preview_path,
        u.username AS seller_username,
        u.avatar_url AS seller_avatar_url
    FROM tbl_genetic_market_listings l
    LEFT JOIN tbl_genetic_trait_catalog c
        ON c.catalog_id = l.catalog_id
    LEFT JOIN tbl_users u
        ON u.discord_id = l.seller_user_id
    WHERE {$whereSql}
    ORDER BY l.created_at DESC
    LIMIT {$limit}
";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $listings = array_map(function ($row) {
        return [
            'listing_id' => (int)($row['listing_id'] ?? 0),
            'seller_user_id' => (string)($row['seller_user_id'] ?? ''),
            'seller_username' => (string)($row['seller_username'] ?? 'Holder'),
            'seller_avatar_url' => (string)($row['seller_avatar_url'] ?? ''),
            'genetic_item_id' => (int)($row['genetic_item_id'] ?? 0),
            'trait_type' => (string)($row['trait_type'] ?? ''),
            'trait_value' => (string)($row['trait_value'] ?? ''),
            'display_title' => (string)($row['display_title'] ?? $row['trait_value'] ?? ''),
            'rarity_tier' => (string)($row['rarity_tier'] ?? 'common'),
            'current_level' => (int)($row['item_level_snapshot'] ?? 1),
            'price_dspoinc' => (int)($row['price_dspoinc'] ?? 0),
            'status' => (string)($row['listing_status'] ?? 'active'),
            'created_at' => (string)($row['created_at'] ?? ''),
            'updated_at' => (string)($row['updated_at'] ?? ''),
            'purchased_at' => (string)($row['sold_at'] ?? ''),
            'image_type' => (string)($row['image_type'] ?? 'png'),
            'image_path' => (string)($row['image_path'] ?? ''),
            'preview_path' => (string)($row['preview_path'] ?? '')
        ];
    }, $rows);

    $summary = [
        'total_active_listings' => count($listings),
        'highest_price_dspoinc' => 0,
        'lowest_price_dspoinc' => 0,
        'average_price_dspoinc' => 0
    ];

    if (!empty($listings)) {
        $prices = array_map(function ($listing) {
            return (int)($listing['price_dspoinc'] ?? 0);
        }, $listings);

        $summary['highest_price_dspoinc'] = max($prices);
        $summary['lowest_price_dspoinc'] = min($prices);
        $summary['average_price_dspoinc'] = (int)round(array_sum($prices) / max(count($prices), 1));
    }

    json_response([
        'success' => true,
        'data' => [
            'listings' => $listings,
            'summary' => $summary
        ]
    ]);
} catch (Exception $e) {
    error_log('🧬 Get Genetic Marketplace Listings ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to load marketplace listings',
        'details' => $e->getMessage()
    ], 500);
}
?>