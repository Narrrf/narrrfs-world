<?php
// 🧬 Buy Genetic Marketplace Listing API
// Handles purchase and ownership transfer of genetic items

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
    $pdo->exec('PRAGMA busy_timeout = 5000');

    return $pdo;
}

/**
 * Return available DSPOINC using the ledger model already used by the Lab.
 */
function get_user_available_dspoinc(PDO $pdo, string $userId): int {
    $totalStmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0)
        FROM tbl_user_scores
        WHERE user_id = ?
    " );
    $totalStmt->execute([$userId]);
    $total = (int)$totalStmt->fetchColumn();

    $frozen = 0;
    $stakesTableStmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'tbl_dspoinc_stakes'");
    $stakesTableStmt->execute();
    $stakesTableExists = (bool)$stakesTableStmt->fetch(PDO::FETCH_ASSOC);

    if ($stakesTableExists) {
        $frozenStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM tbl_dspoinc_stakes
            WHERE user_id = ?
              AND status = 'active'
        " );
        $frozenStmt->execute([$userId]);
        $frozen = (int)$frozenStmt->fetchColumn();
    }

    return max(0, $total - $frozen);
}

/**
 * Record DSPOINC movement in the shared score ledger.
 */
function insert_dspoinc_ledger_entry(PDO $pdo, string $userId, int $amount, string $game, string $source): void {
    $stmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (user_id, game, score, source, timestamp)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    " );

    $stmt->execute([$userId, $game, $amount, $source]);
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
 * Send JSON response
 */
function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/**
 * Parse request
 */
function get_request_data() {
    $raw = file_get_contents("php://input");
    return json_decode($raw, true) ?? [];
}


try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'error' => 'Method not allowed'], 405);
    }

    $buyerId = resolve_user_id();
    $request = get_request_data();

    $listingId = (int)($request['listing_id'] ?? 0);

    if ($listingId < 1) {
        json_response(['success' => false, 'error' => 'Invalid listing_id'], 400);
    }

    $pdo = getMarketplaceDatabaseConnection();

    $pdo->beginTransaction();

    // 🔒 Lock listing row
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_genetic_market_listings
        WHERE listing_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$listing) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Listing not found'], 404);
    }

    if (($listing['listing_status'] ?? '') !== 'active') {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Listing is not active'], 400);
    }

    $sellerId = (string)$listing['seller_user_id'];
    $price = (int)$listing['price_dspoinc'];
    $geneticItemId = (int)$listing['genetic_item_id'];

    if ((string)$sellerId === (string)$buyerId) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Cannot buy your own listing'], 400);
    }

    // 🔒 Load buyer balance from the shared DSPOINC ledger
    $buyerAvailableBalance = get_user_available_dspoinc($pdo, $buyerId);

    if ($buyerAvailableBalance < $price) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Insufficient DSPOINC'], 400);
    }

    // 🔒 Load item
    $itemStmt = $pdo->prepare("
        SELECT *
        FROM tbl_user_genetic_items
        WHERE genetic_item_id = ?
        LIMIT 1
    ");
    $itemStmt->execute([$geneticItemId]);
    $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Item not found'], 404);
    }

    if ((string)$item['user_id'] !== (string)$sellerId) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ownership mismatch'], 409);
    }

    // 🔒 Prevent duplicate ownership
    $dupCheck = $pdo->prepare("
        SELECT 1 FROM tbl_user_genetic_items
        WHERE user_id = ?
        AND trait_type = ?
        AND trait_value = ?
        LIMIT 1
    ");
    $dupCheck->execute([
        $buyerId,
        $item['trait_type'],
        $item['trait_value']
    ]);

    if ($dupCheck->fetch()) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'You already own this genetic trait'
        ], 400);
    }

    // 💰 Deduct from buyer
    $pdo->prepare("
        UPDATE tbl_users
        SET dspoinc_balance = dspoinc_balance - ?
        WHERE user_id = ?
    ")->execute([$price, $buyerId]);

    // 💰 Add to seller
    $pdo->prepare("
        UPDATE tbl_users
        SET dspoinc_balance = dspoinc_balance + ?
        WHERE user_id = ?
    ")->execute([$price, $sellerId]);

    // 🔄 Transfer ownership
    $pdo->prepare("
        UPDATE tbl_user_genetic_items
        SET user_id = ?, is_listed_for_sale = 0
        WHERE genetic_item_id = ?
    ")->execute([$buyerId, $geneticItemId]);

    // 📦 Mark listing as sold
    $pdo->prepare("
UPDATE tbl_genetic_market_listings
SET
    listing_status = 'sold',
    buyer_user_id = ?,
    sold_at = CURRENT_TIMESTAMP,
    updated_at = CURRENT_TIMESTAMP
WHERE listing_id = ?
    ")->execute([$buyerId, $listingId]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Purchase successful',
        'data' => [
            'listing_id' => $listingId,
            'genetic_item_id' => $geneticItemId,
            'buyer_id' => $buyerId,
            'price' => $price,
            'buyer_available_balance' => get_user_available_dspoinc($pdo, $buyerId)
        ]
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Buy Listing ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Purchase failed',
        'details' => $e->getMessage()
    ], 500);
}
?>