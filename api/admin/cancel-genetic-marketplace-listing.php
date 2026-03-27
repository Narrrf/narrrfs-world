<?php
// 🧬 Cancel Genetic Marketplace Listing API
// Cancels an active marketplace listing for a user-owned genetic item.
//
// Stable-first rules:
// - Only the seller can cancel their own active listing
// - Cancelling restores the owned item back to non-listed state
// - Does NOT mutate level, trait identity, or upgrade history
// - Does NOT delete the owned item
// - Listing is preserved with cancelled status for audit/history

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
 * Send JSON response and stop execution.
 */
function json_response($payload, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

/**
 * Read JSON request body.
 */
function get_request_data() {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
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

    $listingId = (int)($request['listing_id'] ?? 0);

    if ($listingId < 1) {
        json_response([
            'success' => false,
            'error' => 'Invalid listing_id'
        ], 400);
    }

    $pdo = getMarketplaceDatabaseConnection();

    // 🧠 Load listing
    $stmt = $pdo->prepare("
SELECT
    listing_id,
    seller_user_id,
    genetic_item_id,
    trait_type,
    trait_value,
    item_level_snapshot,
    price_dspoinc,
    listing_status
FROM tbl_genetic_market_listings
        WHERE listing_id = ?
        LIMIT 1
    ");
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$listing) {
        json_response([
            'success' => false,
            'error' => 'Marketplace listing not found'
        ], 404);
    }

    // 🔒 Seller ownership check
    if ((string)$listing['seller_user_id'] !== (string)$userId) {
        json_response([
            'success' => false,
            'error' => 'You do not own this marketplace listing'
        ], 403);
    }

    // 🔒 Only active listings can be cancelled
    if ((string)($listing['listing_status'] ?? '') !== 'active') {
        json_response([
            'success' => false,
            'error' => 'Only active listings can be cancelled'
        ], 400);
    }

    $geneticItemId = (int)$listing['genetic_item_id'];

    // 🔒 Confirm the owned item still exists and still belongs to seller
    $itemStmt = $pdo->prepare("
        SELECT
            genetic_item_id,
            user_id,
            is_listed_for_sale,
            trait_type,
            trait_value,
            current_level
        FROM tbl_user_genetic_items
        WHERE genetic_item_id = ?
        LIMIT 1
    ");
    $itemStmt->execute([$geneticItemId]);
    $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        json_response([
            'success' => false,
            'error' => 'Owned genetic item linked to this listing was not found'
        ], 404);
    }

    if ((string)$item['user_id'] !== (string)$userId) {
        json_response([
            'success' => false,
            'error' => 'Owned genetic item no longer belongs to this seller'
        ], 409);
    }

    $pdo->beginTransaction();

    // 🧠 Cancel listing but preserve row for history/audit
    $cancelStmt = $pdo->prepare("
UPDATE tbl_genetic_market_listings
SET
    listing_status = 'cancelled',
    updated_at = CURRENT_TIMESTAMP,
    cancelled_at = CURRENT_TIMESTAMP
WHERE listing_id = ?
    ");
    $cancelStmt->execute([$listingId]);

    // 🔓 Restore owned item back to normal inventory state
$unlistStmt = $pdo->prepare("
    UPDATE tbl_user_genetic_items
    SET
        is_listed_for_sale = 0,
        listed_listing_id = NULL,
        updated_at = CURRENT_TIMESTAMP
    WHERE genetic_item_id = ?
");
    $unlistStmt->execute([$geneticItemId]);

    $pdo->commit();

json_response([
    'success' => true,
    'message' => 'Marketplace listing cancelled successfully',
    'data' => [
        'listing_id' => $listingId,
        'genetic_item_id' => $geneticItemId,
        'trait_type' => (string)$listing['trait_type'],
        'trait_value' => (string)$listing['trait_value'],
        'display_title' => (string)$listing['trait_value'],
        'current_level' => (int)($listing['item_level_snapshot'] ?? 1),
        'price_dspoinc' => (int)($listing['price_dspoinc'] ?? 0),
        'status' => 'cancelled',
        'is_listed_for_sale' => 0
    ]
]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Cancel Genetic Marketplace Listing ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to cancel marketplace listing',
        'details' => $e->getMessage()
    ], 500);
}
?>