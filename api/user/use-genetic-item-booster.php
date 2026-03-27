<?php
// 🧪 Use Genetic Item Booster API
// Applies one Lab booster item to an active user-bound Genetic Item upgrade.
//
// Stability-first rules:
// - Genetic boosters are USER-bound, never NFT-bound
// - Uses the shared Lab booster pool from tbl_user_inventory
// - Booster applies to exact genetic_item_id
// - Booster only works on an actively upgrading item
// - Booster reduces REMAINING time, never total base duration
// - Booster consumes 1 inventory item
// - Booster usage is logged in tbl_item_usage_history
// - Genetic item history is also logged in tbl_genetic_item_history
// - Listed items cannot receive boosters
// - Localhost may use request user_id or Narrrf fallback
// - Production stays session-first with mismatch protection

date_default_timezone_set('UTC');

error_reporting(0);
ini_set('display_errors', 0);

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

const LAB_GREEN_ELIXIR_ITEM_ID = 33;
const LAB_BLUE_ELIXIR_ITEM_ID = 34;
const LAB_RED_ELIXIR_ITEM_ID = 35;

const GENETIC_ITEM_MAX_LEVEL = 100;

/**
 * Return a JSON response and stop execution.
 */
function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running in localhost-style development.
 */
function is_localhost_env() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data once from JSON, POST, or GET.
 */
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
 * Resolve the active user using the same production/local pattern as the Lab APIs.
 */
function resolve_user_id() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = $_SESSION['discord_id'] ?? '';
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        error_log("🧪 Use Genetic Booster: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Use Genetic Booster - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧪 Use Genetic Booster: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $user_id;
}

/**
 * Return holder access summary using holder verification data as backend authority.
 */
function get_holder_access(PDO $pdo, string $userId): array {
    if ($userId === '') {
        return [
            'has_genesis' => false,
            'has_vip' => false,
            'can_buy' => false
        ];
    }

    $stmt = $pdo->prepare("
        SELECT collection, nft_count
        FROM tbl_holder_verifications
        WHERE user_id = ?
        ORDER BY verified_at DESC
    ");
    $stmt->execute([$userId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $hasGenesis = false;
    $hasVip = false;

    foreach ($rows as $row) {
        $collection = strtolower(trim((string)($row['collection'] ?? '')));
        $nftCount = (int)($row['nft_count'] ?? 0);

        if ($nftCount < 1) {
            continue;
        }

        if (strpos($collection, 'genesis') !== false) {
            $hasGenesis = true;
        }

        if (strpos($collection, 'vip') !== false) {
            $hasVip = true;
        }
    }

    return [
        'has_genesis' => $hasGenesis,
        'has_vip' => $hasVip,
        'can_buy' => ($hasGenesis || $hasVip)
    ];
}

/**
 * Return canonical booster config by item ID.
 */
function get_booster_config_by_item_id(int $itemId): ?array {
    $configs = [
        LAB_GREEN_ELIXIR_ITEM_ID => [
            'item_name' => 'Green Elixir',
            'effect' => '-6 hours remaining time',
            'reduction_hours' => 6,
            'reduction_seconds' => 6 * 3600
        ],
        LAB_BLUE_ELIXIR_ITEM_ID => [
            'item_name' => 'Blue Elixir',
            'effect' => '-18 hours remaining time',
            'reduction_hours' => 18,
            'reduction_seconds' => 18 * 3600
        ],
        LAB_RED_ELIXIR_ITEM_ID => [
            'item_name' => 'Red Elixir',
            'effect' => '-48 hours remaining time',
            'reduction_hours' => 48,
            'reduction_seconds' => 48 * 3600
        ]
    ];

    return $configs[$itemId] ?? null;
}

/**
 * Fetch one user-owned genetic item with catalog enrichment.
 */
function fetch_user_genetic_item(PDO $pdo, string $userId, int $geneticItemId): ?array {
    $stmt = $pdo->prepare("
        SELECT
            ugi.genetic_item_id,
            ugi.user_id,
            ugi.catalog_id,
            ugi.trait_type,
            ugi.trait_value,
            ugi.current_level,
            ugi.upgrade_status,
            ugi.upgrade_started_at,
            ugi.upgrade_ends_at,
            ugi.last_completed_at,
            ugi.ready_claim_notified_at,
            ugi.ready_claim_notification_count,
            ugi.active_booster_item_id,
            ugi.active_booster_used_at,
            ugi.acquired_method,
            ugi.is_listed_for_sale,
            ugi.listed_listing_id,
            ugi.created_at,
            ugi.updated_at,
            ugi.last_transfer_at,
            ugi.last_owner_user_id,

            gtc.display_title,
            gtc.description,
            gtc.rarity_tier,
            gtc.rarity_count,
            gtc.base_price_dspoinc,
            gtc.image_type,
            gtc.image_path,
            gtc.preview_path,
            gtc.source_origin,
            gtc.effect_metadata_json,
            gtc.is_active,
            gtc.is_visible
        FROM tbl_user_genetic_items ugi
        LEFT JOIN tbl_genetic_trait_catalog gtc
            ON gtc.catalog_id = ugi.catalog_id
        WHERE ugi.user_id = ?
          AND ugi.genetic_item_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId, $geneticItemId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Fetch a booster inventory row from the shared user inventory.
 */
function get_inventory_row(PDO $pdo, string $userId, int $itemId): ?array {
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_user_inventory
        WHERE user_id = ?
          AND item_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId, $itemId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Insert an audit/history row for genetic items.
 */
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

    $userId = resolve_user_id();
    if (!$userId) {
        json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    $request = get_request_data();

    $geneticItemId = (int)($request['genetic_item_id'] ?? 0);
    $itemId = (int)($request['item_id'] ?? 0);

    if ($geneticItemId < 1 || $itemId < 1) {
        json_response([
            'success' => false,
            'error' => 'Missing required fields: genetic_item_id, item_id'
        ], 400);
    }

    $boosterConfig = get_booster_config_by_item_id($itemId);
    if (!$boosterConfig) {
        json_response([
            'success' => false,
            'error' => 'Invalid Lab booster item'
        ], 400);
    }

    $pdo = getDatabaseConnection();

    $holderAccess = get_holder_access($pdo, $userId);
    if (!$holderAccess['can_buy']) {
        json_response([
            'success' => false,
            'error' => 'Only verified Genesis or VIP holders can use Genetic Item boosters',
            'holder_access' => $holderAccess
        ], 403);
    }

    $item = fetch_user_genetic_item($pdo, $userId, $geneticItemId);
    if (!$item) {
        json_response([
            'success' => false,
            'error' => 'Genetic item not found'
        ], 404);
    }

    $currentLevel = (int)($item['current_level'] ?? 1);
    $upgradeStatus = strtolower(trim((string)($item['upgrade_status'] ?? 'idle')));
    $upgradeEndsAt = $item['upgrade_ends_at'] ?? null;
    $isListedForSale = (int)($item['is_listed_for_sale'] ?? 0) === 1;

    if ($currentLevel >= GENETIC_ITEM_MAX_LEVEL) {
        json_response([
            'success' => false,
            'error' => 'This genetic trait is already at max level'
        ], 409);
    }

    if ($isListedForSale) {
        json_response([
            'success' => false,
            'error' => 'Listed genetic items cannot receive boosters'
        ], 409);
    }

    if ($upgradeStatus !== 'upgrading') {
        json_response([
            'success' => false,
            'error' => 'Booster can only be used on an actively upgrading Genetic Item'
        ], 409);
    }

    $endTs = !empty($upgradeEndsAt) ? strtotime((string)$upgradeEndsAt) : false;
    $nowTs = time();

    if (!$endTs || $endTs <= $nowTs) {
        json_response([
            'success' => false,
            'error' => 'This Genetic Item is already ready to claim or has no valid active timer'
        ], 409);
    }

    $inventoryRow = get_inventory_row($pdo, $userId, $itemId);
    if (!$inventoryRow || (int)($inventoryRow['quantity'] ?? 0) < 1) {
        json_response([
            'success' => false,
            'error' => 'You do not own this Lab booster item'
        ], 409);
    }

    $remainingSeconds = max(1, $endTs - $nowTs);
    $reductionSeconds = (int)($boosterConfig['reduction_seconds'] ?? 0);
    $newRemainingSeconds = max(1, $remainingSeconds - $reductionSeconds);
    $newEndsAt = gmdate('Y-m-d H:i:s', $nowTs + $newRemainingSeconds);
    $newQuantity = max(0, (int)($inventoryRow['quantity'] ?? 0) - 1);

    $usageReason = sprintf(
        'genetic_booster:%s:%s:%s',
        $geneticItemId,
        (string)($item['trait_type'] ?? ''),
        (string)($item['trait_value'] ?? '')
    );

    $pdo->beginTransaction();

    // Re-fetch in transaction for safer state validation.
    $item = fetch_user_genetic_item($pdo, $userId, $geneticItemId);
    if (!$item) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Genetic item not found'
        ], 404);
    }

    $upgradeStatus = strtolower(trim((string)($item['upgrade_status'] ?? 'idle')));
    $upgradeEndsAt = $item['upgrade_ends_at'] ?? null;
    $isListedForSale = (int)($item['is_listed_for_sale'] ?? 0) === 1;

    if ($isListedForSale) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Listed genetic items cannot receive boosters'
        ], 409);
    }

    if ($upgradeStatus !== 'upgrading') {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Booster can only be used on an actively upgrading Genetic Item'
        ], 409);
    }

    $endTs = !empty($upgradeEndsAt) ? strtotime((string)$upgradeEndsAt) : false;
    $nowTs = time();

    if (!$endTs || $endTs <= $nowTs) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This Genetic Item is already ready to claim or has no valid active timer'
        ], 409);
    }

    $inventoryRow = get_inventory_row($pdo, $userId, $itemId);
    if (!$inventoryRow || (int)($inventoryRow['quantity'] ?? 0) < 1) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'You do not own this Lab booster item'
        ], 409);
    }

    $remainingSeconds = max(1, $endTs - $nowTs);
    $newRemainingSeconds = max(1, $remainingSeconds - $reductionSeconds);
    $newEndsAt = gmdate('Y-m-d H:i:s', $nowTs + $newRemainingSeconds);
    $newQuantity = max(0, (int)($inventoryRow['quantity'] ?? 0) - 1);

    $updateUpgradeStmt = $pdo->prepare("
        UPDATE tbl_user_genetic_items
        SET
            upgrade_ends_at = ?,
            active_booster_item_id = ?,
            active_booster_used_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE genetic_item_id = ?
          AND user_id = ?
    ");
    $updateUpgradeStmt->execute([
        $newEndsAt,
        $itemId,
        $geneticItemId,
        $userId
    ]);

    if ($updateUpgradeStmt->rowCount() < 1) {
        throw new Exception('Booster update did not modify the genetic item row.');
    }

    if ($newQuantity > 0) {
        $updateInventoryStmt = $pdo->prepare("
            UPDATE tbl_user_inventory
            SET quantity = ?,
                last_used_at = CURRENT_TIMESTAMP
            WHERE inventory_id = ?
        ");
        $updateInventoryStmt->execute([
            $newQuantity,
            $inventoryRow['inventory_id']
        ]);
    } else {
        $deleteInventoryStmt = $pdo->prepare("
            DELETE FROM tbl_user_inventory
            WHERE inventory_id = ?
        ");
        $deleteInventoryStmt->execute([
            $inventoryRow['inventory_id']
        ]);
    }

    $insertUsageStmt = $pdo->prepare("
        INSERT INTO tbl_item_usage_history (
            user_id,
            item_id,
            item_name,
            quantity,
            reason,
            status,
            approved_by,
            used_at,
            approved_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $insertUsageStmt->execute([
        $userId,
        $itemId,
        $boosterConfig['item_name'],
        1,
        $usageReason,
        'approved',
        'genetic_system'
    ]);

    insert_genetic_item_history(
        $pdo,
        $geneticItemId,
        null,
        $userId,
        'booster_used',
        [
            'item_id' => $itemId,
            'item_name' => $boosterConfig['item_name'],
            'previous_ends_at' => $upgradeEndsAt,
            'previous_remaining_seconds' => $remainingSeconds,
            'previous_inventory_quantity' => (int)($inventoryRow['quantity'] ?? 0)
        ],
        [
            'item_id' => $itemId,
            'item_name' => $boosterConfig['item_name'],
            'effect' => $boosterConfig['effect'],
            'reduction_hours' => (int)($boosterConfig['reduction_hours'] ?? 0),
            'new_ends_at' => $newEndsAt,
            'new_remaining_seconds' => $newRemainingSeconds,
            'remaining_inventory_quantity' => $newQuantity
        ]
    );

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => $boosterConfig['item_name'] . ' applied successfully',
        'data' => [
            'genetic_item_id' => $geneticItemId,
            'catalog_id' => (int)($item['catalog_id'] ?? 0),
            'trait_type' => (string)($item['trait_type'] ?? ''),
            'trait_value' => (string)($item['trait_value'] ?? ''),
            'display_title' => (string)($item['display_title'] ?? $item['trait_value'] ?? ''),
            'item_id' => $itemId,
            'item_name' => $boosterConfig['item_name'],
            'effect' => $boosterConfig['effect'],
            'reduction_hours' => (int)($boosterConfig['reduction_hours'] ?? 0),
            'previous_ends_at' => $upgradeEndsAt,
            'new_ends_at' => $newEndsAt,
            'previous_remaining_seconds' => $remainingSeconds,
            'new_remaining_seconds' => $newRemainingSeconds,
            'remaining_inventory_quantity' => $newQuantity,
            'holder_access' => $holderAccess
        ]
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧪 Use Genetic Booster PDO ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to use Genetic Item booster',
        'details' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧪 Use Genetic Booster ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to use Genetic Item booster',
        'details' => $e->getMessage()
    ], 500);
}