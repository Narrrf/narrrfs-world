<?php
// 🧬 Create Genetic Marketplace Listing API
// Creates a listing for a user-owned genetic item

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $pdo->exec('PRAGMA busy_timeout = 5000');

    return $pdo;
}

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf local fallback

function is_localhost_env() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

function resolve_user_id() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = $_SESSION['discord_id'] ?? '';
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        error_log("🧬 Marketplace: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Marketplace user_id mismatch. Session={$session_user_id} Request={$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'User verification mismatch'
        ], 403);
    }

    if ($user_id === '' && $isLocalhost) {
        error_log("🧬 Marketplace: Falling back to local Narrrf Discord ID");
        $user_id = $LOCAL_TEST_DISCORD_ID;
    }

    if ($user_id === '') {
        json_response([
            'success' => false,
            'error' => 'Authentication required'
        ], 401);
    }

    return $user_id;
}

/**
 * Send JSON response and exit
 */
function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/**
 * Get JSON body
 */
function get_request_data() {
    $input = file_get_contents("php://input");
    return json_decode($input, true) ?? [];
}


try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $userId = resolve_user_id();

    $request = get_request_data();

    $geneticItemId = (int)($request['genetic_item_id'] ?? 0);
    $price = (int)($request['price_dspoinc'] ?? 0);

    if ($geneticItemId < 1 || $price < 1) {
        json_response([
            'success' => false,
            'error' => 'Invalid genetic_item_id or price'
        ], 400);
    }

    $pdo = getMarketplaceDatabaseConnection();
    $pdo->exec('BEGIN IMMEDIATE TRANSACTION');

    // 🔒 Load owned item
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_user_genetic_items
        WHERE genetic_item_id = ?
        LIMIT 1
    ");
    $stmt->execute([$geneticItemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        json_response([
            'success' => false,
            'error' => 'Genetic item not found'
        ], 404);
    }

    // 🔒 Ownership check
    if ((string)$item['user_id'] !== (string)$userId) {
        json_response([
            'success' => false,
            'error' => 'You do not own this genetic item'
        ], 403);
    }

    // 🔒 Prevent listing while upgrading
    if (!empty($item['upgrade_started_at']) && !empty($item['upgrade_ends_at'])) {
        json_response([
            'success' => false,
            'error' => 'Cannot list item while upgrading'
        ], 400);
    }

    // 🔒 Prevent duplicate listing with stable-first active row authority
    $activeListingStmt = $pdo->prepare("
        SELECT listing_id
        FROM tbl_genetic_market_listings
        WHERE genetic_item_id = ?
          AND listing_status = 'active'
        LIMIT 1
    ");
    $activeListingStmt->execute([$geneticItemId]);
    $activeListing = $activeListingStmt->fetch(PDO::FETCH_ASSOC);

    if ($activeListing) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Item is already listed for sale'
        ], 400);
    }

    // 🔒 Current marketplace rule: one active listing per seller account
    $sellerActiveListingStmt = $pdo->prepare("
        SELECT listing_id, genetic_item_id
        FROM tbl_genetic_market_listings
        WHERE seller_user_id = ?
          AND listing_status = 'active'
        LIMIT 1
    ");
    $sellerActiveListingStmt->execute([$userId]);
    $sellerActiveListing = $sellerActiveListingStmt->fetch(PDO::FETCH_ASSOC);

    if ($sellerActiveListing) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Only one active marketplace listing is allowed per user right now'
        ], 400);
    }

    if ((int)($item['is_listed_for_sale'] ?? 0) === 1) {
        $healStmt = $pdo->prepare("
            UPDATE tbl_user_genetic_items
            SET
                is_listed_for_sale = 0,
                listed_listing_id = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE genetic_item_id = ?
        ");
        $healStmt->execute([$geneticItemId]);

        $item['is_listed_for_sale'] = 0;
        $item['listed_listing_id'] = null;
    }

    // 🔒 Load catalog data for display snapshot
    $catalogStmt = $pdo->prepare("
        SELECT display_title, rarity_tier
        FROM tbl_genetic_trait_catalog
        WHERE trait_type = ? AND trait_value = ?
        LIMIT 1
    ");
    $catalogStmt->execute([
        $item['trait_type'],
        $item['trait_value']
    ]);
    $catalog = $catalogStmt->fetch(PDO::FETCH_ASSOC);

    // 🧠 Insert marketplace listing
$insert = $pdo->prepare("
    INSERT INTO tbl_genetic_market_listings (
        seller_user_id,
        genetic_item_id,
        catalog_id,
        trait_type,
        trait_value,
        item_level_snapshot,
        price_dspoinc,
        listing_status,
        created_at,
        updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
");

$insert->execute([
    $userId,
    $geneticItemId,
    (int)$item['catalog_id'],
    $item['trait_type'],
    $item['trait_value'],
    (int)$item['current_level'],
    $price
]);

    $listingId = $pdo->lastInsertId();

    // 🔒 Mark item as listed
$update = $pdo->prepare("
    UPDATE tbl_user_genetic_items
    SET
        is_listed_for_sale = 1,
        listed_listing_id = ?,
        updated_at = CURRENT_TIMESTAMP
    WHERE genetic_item_id = ?
");
$update->execute([$listingId, $geneticItemId]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Genetic item listed successfully',
        'data' => [
            'listing_id' => (int)$listingId,
            'genetic_item_id' => $geneticItemId,
            'price_dspoinc' => $price
        ]
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('🧬 Marketplace Listing ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to create listing',
        'details' => $e->getMessage()
    ], 500);
}
?>