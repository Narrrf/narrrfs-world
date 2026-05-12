<?php
// 🧬 Buy Genetic Marketplace Listing API
// Purchases one listed user-bound Genetic Item from the marketplace.
//
// Stable-first rules:
// - SQLite-safe: NO FOR UPDATE usage
// - Website/session auth in production
// - Localhost may use request user_id or Narrrf fallback
// - Any Discord-authenticated user may buy
// - One user may own only one exact trait_type + trait_value combination
// - Available DSPOINC = total ledger score - active frozen stakes
// - Full user-bound item runtime state transfers to the new owner
// - current_level + upgrade_status + upgrade_started_at + upgrade_ends_at transfer intact
// - Listing is finalized atomically with balance movement and ownership transfer
// - Audit trail is written to tbl_user_scores + tbl_score_adjustments + tbl_genetic_item_history

error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('UTC');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$localDiscordSecretPath = __DIR__ . '/../config/discord-secret.php';
if (file_exists($localDiscordSecretPath)) {
    include $localDiscordSecretPath;
}

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf local fallback

const GENETIC_MAX_OWNED_PER_EXACT_TRAIT = 2;

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function is_localhost_env() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

function get_request_data() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
 * Resolve active buyer user ID.
 * Production = session-first.
 * Localhost = request override allowed.
 */
function resolve_user_id() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = trim((string)($_SESSION['discord_id'] ?? ''));
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        error_log("🧬 Marketplace Buy: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Marketplace Buy - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧬 Marketplace Buy: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $user_id;
}

/**
 * Canonical available DSPOINC = total - frozen active stakes.
 */
function get_user_available_dspoinc(PDO $pdo, string $userId): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0)
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0)
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $frozen = (int)$stmt->fetchColumn();

    return max(0, $total - $frozen);
}

/**
 * Count how many copies of one exact Genetic trait a user owns.
 * Plain language for DEVS:
 * Players may own up to 2 copies of the same exact trait_type + trait_value.
 * This supports the community request to upgrade one copy while listing/selling
 * another copy on the marketplace.
 */
function count_user_genetic_trait_copies(PDO $pdo, string $userId, string $traitType, string $traitValue): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS owned_count
        FROM tbl_user_genetic_items
        WHERE user_id = ?
          AND trait_type = ?
          AND trait_value = ?
    ");
    $stmt->execute([$userId, $traitType, $traitValue]);

    return (int)$stmt->fetchColumn();
}

/**
 * Return true when the player already reached the exact-trait ownership limit.
 */
function user_reached_genetic_trait_limit(PDO $pdo, string $userId, string $traitType, string $traitValue): bool {
    return count_user_genetic_trait_copies($pdo, $userId, $traitType, $traitValue) >= GENETIC_MAX_OWNED_PER_EXACT_TRAIT;
}

function get_genetic_access(string $userId): array {
    $isLoggedIn = trim($userId) !== '';

    return [
        'is_logged_in' => $isLoggedIn,
        'has_genesis' => false,
        'has_vip' => false,
        'can_buy' => $isLoggedIn
    ];
}

function insert_dspoinc_change(PDO $pdo, string $userId, int $amount, string $game, string $source): void {
    $stmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (
            user_id,
            game,
            score,
            source,
            timestamp
        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $userId,
        $game,
        $amount,
        $source
    ]);
}

function insert_score_adjustment(PDO $pdo, string $userId, string $adminId, int $amount, string $action, string $reason): void {
    $stmt = $pdo->prepare("
        INSERT INTO tbl_score_adjustments (
            user_id,
            admin_id,
            amount,
            action,
            reason,
            timestamp
        ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $userId,
        $adminId,
        $amount,
        $action,
        $reason
    ]);
}

function insert_genetic_item_history(
    PDO $pdo,
    ?int $geneticItemId,
    ?int $listingId,
    string $userId,
    string $actionType,
    array $oldValue,
    array $newValue,
    ?string $adminUserId = null
): void {
    $stmt = $pdo->prepare("
        INSERT INTO tbl_genetic_item_history (
            genetic_item_id,
            listing_id,
            user_id,
            action_type,
            old_value_json,
            new_value_json,
            admin_user_id,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $geneticItemId,
        $listingId,
        $userId,
        $actionType,
        json_encode($oldValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        json_encode($newValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $adminUserId
    ]);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $buyerUserId = resolve_user_id();
    if (!$buyerUserId) {
        json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    $request = get_request_data();
    $listingId = (int)($request['listing_id'] ?? 0);

    if ($listingId < 1) {
        json_response([
            'success' => false,
            'error' => 'Missing or invalid listing_id'
        ], 400);
    }

    $pdo = getDatabaseConnection();

// 🧬 Genetic marketplace access rule
// Any valid Discord-authenticated user may buy Genetic marketplace items.

if (!$buyerUserId || trim($buyerUserId) === '') {
    json_response([
        'success' => false,
        'error' => 'Authentication required'
    ], 401);
}

    // Preload listing with catalog display data - SQLite safe, no FOR UPDATE.
    $listingStmt = $pdo->prepare("
        SELECT
            l.listing_id,
            l.genetic_item_id,
            l.seller_user_id,
            l.buyer_user_id,
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
            c.preview_path
        FROM tbl_genetic_market_listings l
        LEFT JOIN tbl_genetic_trait_catalog c
            ON c.catalog_id = l.catalog_id
        WHERE l.listing_id = ?
        LIMIT 1
    ");
    $listingStmt->execute([$listingId]);
    $listing = $listingStmt->fetch(PDO::FETCH_ASSOC);

    if (!$listing) {
        json_response([
            'success' => false,
            'error' => 'Marketplace listing not found'
        ], 404);
    }

    if (strtolower(trim((string)($listing['listing_status'] ?? ''))) !== 'active') {
        json_response([
            'success' => false,
            'error' => 'This marketplace listing is no longer active'
        ], 409);
    }

    $sellerUserId = trim((string)($listing['seller_user_id'] ?? ''));
    $geneticItemId = (int)($listing['genetic_item_id'] ?? 0);
    $catalogId = (int)($listing['catalog_id'] ?? 0);
    $traitType = trim((string)($listing['trait_type'] ?? ''));
    $traitValue = trim((string)($listing['trait_value'] ?? ''));
    $displayTitle = trim((string)($listing['display_title'] ?? $traitValue));
    $price = max(0, (int)($listing['price_dspoinc'] ?? 0));

    if ($sellerUserId === '' || $geneticItemId < 1 || $traitType === '' || $traitValue === '' || $price < 1) {
        json_response([
            'success' => false,
            'error' => 'Marketplace listing is malformed'
        ], 500);
    }

    if ($sellerUserId === $buyerUserId) {
        json_response([
            'success' => false,
            'error' => 'You cannot buy your own marketplace listing'
        ], 409);
    }

    if (user_reached_genetic_trait_limit($pdo, $buyerUserId, $traitType, $traitValue)) {
        json_response([
            'success' => false,
            'error' => 'You already own the maximum 2 copies of this genetic trait',
            'data' => [
                'trait_type' => $traitType,
                'trait_value' => $traitValue,
                'listing_id' => $listingId
            ]
        ], 409);
    }

    $currentBalance = get_user_available_dspoinc($pdo, $buyerUserId);
    if ($currentBalance < $price) {
        json_response([
            'success' => false,
            'error' => 'Not enough DSPOINC',
            'data' => [
                'price_dspoinc' => $price,
                'balance_dspoinc' => $currentBalance,
                'missing_dspoinc' => ($price - $currentBalance)
            ]
        ], 409);
    }

    $pdo->beginTransaction();

    // Re-load listing inside transaction.
    $listingStmt = $pdo->prepare("
        SELECT
            listing_id,
            genetic_item_id,
            seller_user_id,
            buyer_user_id,
            catalog_id,
            trait_type,
            trait_value,
            item_level_snapshot,
            price_dspoinc,
            listing_status,
            created_at,
            updated_at,
            sold_at
        FROM tbl_genetic_market_listings
        WHERE listing_id = ?
        LIMIT 1
    ");
    $listingStmt->execute([$listingId]);
    $listing = $listingStmt->fetch(PDO::FETCH_ASSOC);

    if (!$listing) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Marketplace listing not found'
        ], 404);
    }

    if (strtolower(trim((string)($listing['listing_status'] ?? ''))) !== 'active') {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This marketplace listing is no longer active'
        ], 409);
    }

    if (trim((string)($listing['seller_user_id'] ?? '')) !== $sellerUserId) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Marketplace seller mismatch detected'
        ], 409);
    }

    if (user_reached_genetic_trait_limit($pdo, $buyerUserId, $traitType, $traitValue)) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'You already own the maximum 2 copies of this genetic trait'
        ], 409);
    }

    $currentBalance = get_user_available_dspoinc($pdo, $buyerUserId);
    if ($currentBalance < $price) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Not enough DSPOINC',
            'data' => [
                'price_dspoinc' => $price,
                'balance_dspoinc' => $currentBalance,
                'missing_dspoinc' => ($price - $currentBalance)
            ]
        ], 409);
    }

    // Load owned item row that is being transferred.
    $itemStmt = $pdo->prepare("
        SELECT
            genetic_item_id,
            user_id,
            catalog_id,
            trait_type,
            trait_value,
            current_level,
            upgrade_status,
            upgrade_started_at,
            upgrade_ends_at,
            acquired_method,
            is_listed_for_sale,
            listed_listing_id,
            last_transfer_at,
            last_owner_user_id,
            created_at,
            updated_at
        FROM tbl_user_genetic_items
        WHERE genetic_item_id = ?
        LIMIT 1
    ");
    $itemStmt->execute([$geneticItemId]);
    $ownedItem = $itemStmt->fetch(PDO::FETCH_ASSOC);

    if (!$ownedItem) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Marketplace item no longer exists'
        ], 404);
    }

    if (trim((string)($ownedItem['user_id'] ?? '')) !== $sellerUserId) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Marketplace item owner mismatch detected'
        ], 409);
    }

    if ((int)($ownedItem['is_listed_for_sale'] ?? 0) !== 1) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Marketplace item is not currently marked as listed'
        ], 409);
    }

    if ((int)($ownedItem['listed_listing_id'] ?? 0) !== $listingId) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Marketplace item listing reference mismatch detected'
        ], 409);
    }

    $itemUpgradeStatus = trim((string)($ownedItem['upgrade_status'] ?? 'idle'));
    $itemCurrentLevel = max(1, (int)($ownedItem['current_level'] ?? 1));
    $itemUpgradeStartedAt = (string)($ownedItem['upgrade_started_at'] ?? '');
    $itemUpgradeEndsAt = (string)($ownedItem['upgrade_ends_at'] ?? '');

    // Seller receives full sale price.
    insert_dspoinc_change(
        $pdo,
        $sellerUserId,
        $price,
        'genetic_marketplace_sale',
        'marketplace_sale [listing_id=' . $listingId . ']'
    );

    insert_score_adjustment(
        $pdo,
        $sellerUserId,
        $buyerUserId,
        $price,
        'add',
        'Marketplace sale: ' . $displayTitle . ' (listing_id: ' . $listingId . ', buyer: ' . $buyerUserId . ')'
    );

    // Buyer pays full price.
    insert_dspoinc_change(
        $pdo,
        $buyerUserId,
        -$price,
        'genetic_marketplace_purchase',
        'marketplace_purchase [listing_id=' . $listingId . ']'
    );

    insert_score_adjustment(
        $pdo,
        $buyerUserId,
        $sellerUserId,
        -$price,
        'remove',
        'Marketplace purchase: ' . $displayTitle . ' (listing_id: ' . $listingId . ', seller: ' . $sellerUserId . ')'
    );

    // Transfer FULL user-bound runtime state to buyer.
    // Important: current_level + upgrade status/timers stay with the item.
    $transferStmt = $pdo->prepare("
        UPDATE tbl_user_genetic_items
        SET
            user_id = ?,
            is_listed_for_sale = 0,
            listed_listing_id = NULL,
            last_transfer_at = CURRENT_TIMESTAMP,
            last_owner_user_id = ?,
            acquired_method = 'marketplace',
            updated_at = CURRENT_TIMESTAMP
        WHERE genetic_item_id = ?
          AND user_id = ?
    ");
    $transferStmt->execute([
        $buyerUserId,
        $sellerUserId,
        $geneticItemId,
        $sellerUserId
    ]);

    if ($transferStmt->rowCount() !== 1) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Ownership transfer failed'
        ], 409);
    }

    // Finalize listing.
    $listingUpdateStmt = $pdo->prepare("
        UPDATE tbl_genetic_market_listings
        SET
            buyer_user_id = ?,
            listing_status = 'sold',
            sold_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE listing_id = ?
          AND listing_status = 'active'
    ");
    $listingUpdateStmt->execute([
        $buyerUserId,
        $listingId
    ]);

    if ($listingUpdateStmt->rowCount() !== 1) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Listing finalization failed'
        ], 409);
    }

    $remainingBalance = get_user_available_dspoinc($pdo, $buyerUserId);
    if ($remainingBalance < 0) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Purchase verification failed. Please contact an administrator.'
        ], 500);
    }

    insert_genetic_item_history(
        $pdo,
        $geneticItemId,
        $listingId,
        $sellerUserId,
        'marketplace_sale',
        [
            'user_id' => $sellerUserId,
            'listing_status' => 'active',
            'is_listed_for_sale' => 1,
            'listed_listing_id' => $listingId,
            'current_level' => $itemCurrentLevel,
            'upgrade_status' => $itemUpgradeStatus,
            'upgrade_started_at' => $itemUpgradeStartedAt,
            'upgrade_ends_at' => $itemUpgradeEndsAt
        ],
        [
            'user_id' => $buyerUserId,
            'listing_status' => 'sold',
            'price_dspoinc' => $price,
            'current_level' => $itemCurrentLevel,
            'upgrade_status' => $itemUpgradeStatus,
            'upgrade_started_at' => $itemUpgradeStartedAt,
            'upgrade_ends_at' => $itemUpgradeEndsAt
        ],
        null
    );

    insert_genetic_item_history(
        $pdo,
        $geneticItemId,
        $listingId,
        $buyerUserId,
        'marketplace_purchase',
        [
            'seller_user_id' => $sellerUserId,
            'price_dspoinc' => $price
        ],
        [
            'buyer_user_id' => $buyerUserId,
            'catalog_id' => $catalogId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'price_paid_dspoinc' => $price,
            'remaining_balance_dspoinc' => $remainingBalance,
            'current_level' => $itemCurrentLevel,
            'upgrade_status' => $itemUpgradeStatus,
            'upgrade_started_at' => $itemUpgradeStartedAt,
            'upgrade_ends_at' => $itemUpgradeEndsAt
        ],
        null
    );

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Marketplace purchase completed successfully',
        'data' => [
            'listing_id' => $listingId,
            'genetic_item_id' => $geneticItemId,
            'catalog_id' => $catalogId,
            'seller_user_id' => $sellerUserId,
            'buyer_user_id' => $buyerUserId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'price_paid_dspoinc' => $price,
            'remaining_balance_dspoinc' => $remainingBalance,
            'current_level' => $itemCurrentLevel,
            'upgrade_status' => $itemUpgradeStatus,
            'upgrade_started_at' => $itemUpgradeStartedAt,
            'upgrade_ends_at' => $itemUpgradeEndsAt,
            'listing_status' => 'sold'
        ]
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (strpos(strtolower($e->getMessage()), 'unique') !== false) {
        json_response([
            'success' => false,
            'error' => 'You already own the maximum 2 copies of this genetic trait'
        ], 409);
    }

    error_log('🧬 Marketplace Buy PDO ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Purchase failed',
        'details' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Marketplace Buy ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Purchase failed',
        'details' => $e->getMessage()
    ], 500);
}
?>