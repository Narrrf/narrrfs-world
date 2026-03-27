<?php
// 🧬 Instant Finish Genetic Item Upgrade API
// Instantly completes one active user-bound Genetic Item upgrade using DSPOINC.
//
// Stability-first rules:
// - Genetic instant finish is USER-bound, never NFT-bound
// - Works on exact genetic_item_id
// - Only verified Genesis or VIP holders may use it
// - Uses DSPOINC only
// - Backend computes the final price authoritatively
// - Listed items cannot be instant-finished
// - Level increments only after the backend finalizes the upgrade
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
const GENETIC_INSTANT_FINISH_BASE_COST = 10000;

/**
 * Return a JSON response and stop execution.
 */
function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running in local development.
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
 * Resolve the active user using the same production/local pattern as the live Lab APIs.
 */
function resolve_user_id() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = $_SESSION['discord_id'] ?? '';
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        error_log("🧬 Instant Finish Genetic Upgrade: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Instant Finish Genetic Upgrade - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧬 Instant Finish Genetic Upgrade: Using local test user (Narrrf) for localhost");
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
 * Fetch one genetic item with catalog enrichment for the owning user.
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
 * Return the user's current total DSPOINC balance from the canonical score ledger.
 */
function get_user_total_dspoinc(PDO $pdo, string $userId): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0) AS total_balance
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($row['total_balance'] ?? 0);
}

/**
 * Insert a negative DSPOINC ledger row for instant finish spend.
 */
function insert_dspoinc_spend(PDO $pdo, string $userId, int $amount, string $reason, string $reference): void {
    $negativeAmount = -abs($amount);

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
        'genetic_instant_finish',
        $negativeAmount,
        $reason . ' [' . $reference . ']'
    ]);
}

/**
 * Returns available DSPOINC = total - frozen (active stakes)
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

/**
 * Mirror the current frontend preview formula so UI and backend stay aligned.
 * Backend remains authoritative for the final charge.
 */
function calculate_genetic_instant_finish_cost(int $currentLevel, int $remainingSeconds): int {
    $level = max(1, $currentLevel);
    $remainingHours = max(0, $remainingSeconds / 3600);
    $levelMultiplier = pow(2.35, max(0, $level - 1));
    $timeMultiplier = max(1, $remainingHours / 24);

    return (int)ceil(GENETIC_INSTANT_FINISH_BASE_COST * $levelMultiplier * $timeMultiplier);
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
            'error' => 'Only verified Genesis or VIP holders can instantly finish genetic upgrades',
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
            'error' => 'Listed genetic items cannot be instant-finished'
        ], 409);
    }

    if ($upgradeStatus !== 'upgrading' && $upgradeStatus !== 'ready_to_claim') {
        json_response([
            'success' => false,
            'error' => 'This genetic trait has no active upgrade to instantly finish'
        ], 409);
    }

    if ($upgradeEndsAt === null) {
        json_response([
            'success' => false,
            'error' => 'Upgrade end time is missing'
        ], 500);
    }

    $endTs = strtotime((string)$upgradeEndsAt);
    if ($endTs === false) {
        json_response([
            'success' => false,
            'error' => 'Upgrade end time is invalid'
        ], 500);
    }

    $nowTs = time();
    $remainingSeconds = max(0, $endTs - $nowTs);

    // If already ready, instant finish is unnecessary and should not charge.
    if ($remainingSeconds === 0 || $upgradeStatus === 'ready_to_claim') {
        json_response([
            'success' => false,
            'error' => 'This genetic trait is already ready to claim. Use the normal claim action instead.',
            'data' => [
                'genetic_item_id' => $geneticItemId,
                'current_level' => $currentLevel,
                'upgrade_ends_at' => $upgradeEndsAt
            ]
        ], 409);
    }

    $instantFinishCost = calculate_genetic_instant_finish_cost($currentLevel, $remainingSeconds);
    $currentBalance = get_user_available_dspoinc($pdo, $userId);

    if ($currentBalance < $instantFinishCost) {
        json_response([
            'success' => false,
            'error' => 'Not enough DSPOINC to instantly finish this upgrade',
            'data' => [
                'genetic_item_id' => $geneticItemId,
                'current_level' => $currentLevel,
                'instant_finish_cost' => $instantFinishCost,
                'balance_dspoinc' => $currentBalance,
                'missing_dspoinc' => ($instantFinishCost - $currentBalance)
            ]
        ], 409);
    }

    $newLevel = min(GENETIC_ITEM_MAX_LEVEL, $currentLevel + 1);

    $pdo->beginTransaction();

    // Re-fetch inside transaction for safer state validation.
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
            'error' => 'Listed genetic items cannot be instant-finished'
        ], 409);
    }

    if ($upgradeStatus !== 'upgrading' && $upgradeStatus !== 'ready_to_claim') {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This genetic trait has no active upgrade to instantly finish'
        ], 409);
    }

    if ($upgradeEndsAt === null) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Upgrade end time is missing'
        ], 500);
    }

    $endTs = strtotime((string)$upgradeEndsAt);
    if ($endTs === false) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Upgrade end time is invalid'
        ], 500);
    }

    $remainingSeconds = max(0, $endTs - time());

    if ($remainingSeconds === 0 || $upgradeStatus === 'ready_to_claim') {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This genetic trait is already ready to claim. Use the normal claim action instead.'
        ], 409);
    }

    $instantFinishCost = calculate_genetic_instant_finish_cost($currentLevel, $remainingSeconds);
    $currentBalance = get_user_available_dspoinc($pdo, $userId);

    if ($currentBalance < $instantFinishCost) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Not enough DSPOINC to instantly finish this upgrade'
        ], 409);
    }

    insert_dspoinc_spend(
        $pdo,
        $userId,
        $instantFinishCost,
        'Genetic Instant Finish: ' . ((string)($item['display_title'] ?? $item['trait_value'] ?? 'Unknown Trait')),
        'genetic_item_id=' . $geneticItemId
    );

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

    $remainingBalance = get_user_available_dspoinc($pdo, $userId);

    insert_genetic_item_history(
        $pdo,
        $geneticItemId,
        null,
        $userId,
        'instant_finished',
        [
            'previous_level' => $currentLevel,
            'upgrade_status' => $upgradeStatus,
            'upgrade_ends_at' => $upgradeEndsAt,
            'remaining_seconds_before_finish' => $remainingSeconds
        ],
        [
            'new_level' => $newLevel,
            'upgrade_status' => 'idle',
            'instant_finish_cost' => $instantFinishCost,
            'remaining_balance_dspoinc' => $remainingBalance,
            'last_completed_at' => $updatedItem['last_completed_at'] ?? null
        ]
    );

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Genetic item instantly finished successfully',
        'data' => [
            'genetic_item_id' => (int)($updatedItem['genetic_item_id'] ?? 0),
            'catalog_id' => (int)($updatedItem['catalog_id'] ?? 0),
            'trait_type' => (string)($updatedItem['trait_type'] ?? ''),
            'trait_value' => (string)($updatedItem['trait_value'] ?? ''),
            'display_title' => (string)($updatedItem['display_title'] ?? $updatedItem['trait_value'] ?? ''),
            'previous_level' => $currentLevel,
            'current_level' => (int)($updatedItem['current_level'] ?? $newLevel),
            'next_level' => min(GENETIC_ITEM_MAX_LEVEL, ((int)($updatedItem['current_level'] ?? $newLevel) + 1)),
            'max_level' => GENETIC_ITEM_MAX_LEVEL,
            'upgrade_status' => (string)($updatedItem['upgrade_status'] ?? 'idle'),
            'last_completed_at' => $updatedItem['last_completed_at'] ?? null,
            'instant_finish_cost' => $instantFinishCost,
            'remaining_balance_dspoinc' => $remainingBalance,
            'holder_access' => $holderAccess
        ]
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Instant Finish Genetic Upgrade PDO ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to instantly finish genetic item upgrade',
        'details' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Instant Finish Genetic Upgrade ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to instantly finish genetic item upgrade',
        'details' => $e->getMessage()
    ], 500);
}