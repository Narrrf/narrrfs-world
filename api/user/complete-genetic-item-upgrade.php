<?php
// 🧬 Complete Genetic Item Upgrade API
// Claims a finished upgrade for one user-owned genetic trait item.
//
// Stability-first rules:
// - Genetic upgrades are USER-bound, never NFT-bound
// - Claim belongs to exact genetic_item_id
// - Level increments only after timer is complete
// - Claimed row returns to idle for the next cycle
// - Listed items cannot be upgraded/claimed through this path
// - Level cap is 100 for now
// - Localhost may use request user_id or Narrrf fallback
// - Production stays session-first with mismatch protection

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
const GENETIC_ITEM_MAX_LEVEL = 100;

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

function resolve_user_id() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = $_SESSION['discord_id'] ?? '';
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        error_log("🧬 Complete Genetic Upgrade: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Complete Genetic Upgrade - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧬 Complete Genetic Upgrade: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $user_id;
}

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

    if ($geneticItemId < 1) {
        json_response([
            'success' => false,
            'error' => 'Missing or invalid genetic_item_id'
        ], 400);
    }

    $pdo = getDatabaseConnection();

    $holderAccess = get_holder_access($pdo, $userId);
    if (!$holderAccess['can_buy']) {
        json_response([
            'success' => false,
            'error' => 'Only verified Genesis or VIP holders can claim genetic upgrades',
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
    $upgradeStatus = trim((string)($item['upgrade_status'] ?? 'idle'));
    $isListedForSale = (int)($item['is_listed_for_sale'] ?? 0) === 1;
    $upgradeEndsAt = $item['upgrade_ends_at'] ?? null;

    if ($currentLevel >= GENETIC_ITEM_MAX_LEVEL) {
        json_response([
            'success' => false,
            'error' => 'This genetic trait is already at max level',
            'data' => [
                'genetic_item_id' => $geneticItemId,
                'current_level' => $currentLevel,
                'max_level' => GENETIC_ITEM_MAX_LEVEL
            ]
        ], 409);
    }

    if ($isListedForSale) {
        json_response([
            'success' => false,
            'error' => 'Listed genetic items cannot be claimed through upgrade flow'
        ], 409);
    }

    if ($upgradeStatus !== 'upgrading' && $upgradeStatus !== 'ready_to_claim') {
        json_response([
            'success' => false,
            'error' => 'This genetic trait has no claimable upgrade running'
        ], 409);
    }

    if ($upgradeEndsAt === null) {
        json_response([
            'success' => false,
            'error' => 'Upgrade end time is missing'
        ], 500);
    }

    $nowTs = time();
    $endTs = strtotime((string)$upgradeEndsAt);

    if ($endTs === false) {
        json_response([
            'success' => false,
            'error' => 'Upgrade end time is invalid'
        ], 500);
    }

    if ($endTs > $nowTs && $upgradeStatus !== 'ready_to_claim') {
        json_response([
            'success' => false,
            'error' => 'This genetic trait is still upgrading',
            'data' => [
                'genetic_item_id' => $geneticItemId,
                'upgrade_ends_at' => $upgradeEndsAt,
                'remaining_seconds' => max(0, $endTs - $nowTs)
            ]
        ], 409);
    }

    $newLevel = min(GENETIC_ITEM_MAX_LEVEL, $currentLevel + 1);

    $pdo->beginTransaction();

    $item = fetch_user_genetic_item($pdo, $userId, $geneticItemId);
    if (!$item) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Genetic item not found'
        ], 404);
    }

    $currentLevel = (int)($item['current_level'] ?? 1);
    $upgradeStatus = trim((string)($item['upgrade_status'] ?? 'idle'));
    $isListedForSale = (int)($item['is_listed_for_sale'] ?? 0) === 1;
    $upgradeEndsAt = $item['upgrade_ends_at'] ?? null;

    if ($currentLevel >= GENETIC_ITEM_MAX_LEVEL) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This genetic trait is already at max level'
        ], 409);
    }

    if ($isListedForSale) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Listed genetic items cannot be claimed through upgrade flow'
        ], 409);
    }

    if ($upgradeStatus !== 'upgrading' && $upgradeStatus !== 'ready_to_claim') {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This genetic trait has no claimable upgrade running'
        ], 409);
    }

    $endTs = strtotime((string)$upgradeEndsAt);
    if ($endTs === false) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Upgrade end time is invalid'
        ], 500);
    }

    if ($endTs > time() && $upgradeStatus !== 'ready_to_claim') {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This genetic trait is still upgrading'
        ], 409);
    }

    $newLevel = min(GENETIC_ITEM_MAX_LEVEL, $currentLevel + 1);

    $updateStmt = $pdo->prepare("
        UPDATE tbl_user_genetic_items
        SET
            current_level = ?,
            upgrade_status = 'idle',
            upgrade_started_at = NULL,
            upgrade_ends_at = NULL,
            last_completed_at = CURRENT_TIMESTAMP,
            ready_claim_notified_at = NULL,
            ready_claim_notification_count = 0,
            active_booster_item_id = NULL,
            active_booster_used_at = NULL,
            updated_at = CURRENT_TIMESTAMP
        WHERE genetic_item_id = ?
          AND user_id = ?
    ");
    $updateStmt->execute([
        $newLevel,
        $geneticItemId,
        $userId
    ]);

    $updatedItem = fetch_user_genetic_item($pdo, $userId, $geneticItemId);
    if (!$updatedItem) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Failed to reload updated genetic item'
        ], 500);
    }

    insert_genetic_item_history(
        $pdo,
        $geneticItemId,
        null,
        $userId,
        'upgrade_claimed',
        [
            'previous_level' => $currentLevel,
            'upgrade_status' => $upgradeStatus,
            'upgrade_ends_at' => $upgradeEndsAt
        ],
        [
            'new_level' => $newLevel,
            'upgrade_status' => 'idle',
            'last_completed_at' => $updatedItem['last_completed_at'] ?? null
        ]
    );

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Genetic item upgrade claimed successfully',
        'data' => [
            'genetic_item_id' => (int)($updatedItem['genetic_item_id'] ?? 0),
            'catalog_id' => (int)($updatedItem['catalog_id'] ?? 0),
            'trait_type' => (string)($updatedItem['trait_type'] ?? ''),
            'trait_value' => (string)($updatedItem['trait_value'] ?? ''),
            'display_title' => (string)($updatedItem['display_title'] ?? $updatedItem['trait_value'] ?? ''),
            'previous_level' => $currentLevel,
            'current_level' => (int)($updatedItem['current_level'] ?? $newLevel),
            'max_level' => GENETIC_ITEM_MAX_LEVEL,
            'upgrade_status' => (string)($updatedItem['upgrade_status'] ?? 'idle'),
            'last_completed_at' => $updatedItem['last_completed_at'] ?? null,
            'holder_access' => $holderAccess
        ]
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Complete Genetic Upgrade PDO ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to complete genetic item upgrade',
        'details' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Complete Genetic Upgrade ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to complete genetic item upgrade',
        'details' => $e->getMessage()
    ], 500);
}