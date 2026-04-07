<?php
// 🧬 Start Genetic Item Upgrade API
// Starts a timed upgrade for one user-owned genetic trait item.
//
// Stability-first rules:
// - Genetic upgrades are USER-bound, never NFT-bound
// - Upgrade belongs to exact genetic_item_id
// - Only one active genetic upgrade per user
// - Listed items cannot be upgraded
// - Upgrade timing follows the NFT curve shape at ~2x speed
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
        error_log("🧬 Start Genetic Upgrade: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Start Genetic Upgrade - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧬 Start Genetic Upgrade: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $user_id;
}

function get_genetic_access(string $userId): array {
    $isLoggedIn = trim($userId) !== '';

    return [
        'is_logged_in' => $isLoggedIn,
        'has_genesis' => false,
        'has_vip' => false,
        'can_upgrade' => $isLoggedIn
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
 * Return true if the user already has one active or claim-pending genetic upgrade.
 */
function user_has_active_genetic_upgrade(PDO $pdo, string $userId, int $excludeGeneticItemId = 0): bool {
    $sql = "
        SELECT genetic_item_id
        FROM tbl_user_genetic_items
        WHERE user_id = ?
          AND upgrade_status IN ('upgrading', 'ready_to_claim')
    ";
    $params = [$userId];

    if ($excludeGeneticItemId > 0) {
        $sql .= " AND genetic_item_id != ?";
        $params[] = $excludeGeneticItemId;
    }

    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Mirror the NFT timing shape, then make it about 2x faster for genetic items.
 *
 * NFT shape:
 * - Level 1-4: exponential (24h, 48h, 96h, 192h)
 * - Level 5-15: +24h each
 * - Level 16+: +1h each
 *
 * Genetic items:
 * - same shape
 * - roughly half duration
 */
function calculate_nft_curve_hours_for_level(int $level): int {
    if ($level <= 0) {
        $level = 1;
    }

    if ($level <= 4) {
        return (int)(24 * pow(2, $level - 1));
    }

    if ($level <= 15) {
        return (int)(192 + (($level - 4) * 24));
    }

    return (int)(456 + ($level - 15));
}

/**
 * Return the faster genetic duration for the current level.
 */
function calculate_genetic_upgrade_duration_hours(int $currentLevel): int {
    $nftHours = calculate_nft_curve_hours_for_level($currentLevel);
    return max(1, (int)floor($nftHours / 2));
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

    if ($geneticItemId < 1) {
        json_response([
            'success' => false,
            'error' => 'Missing or invalid genetic_item_id'
        ], 400);
    }

    $pdo = getDatabaseConnection();

    $geneticAccess = get_genetic_access($userId);
if (!$geneticAccess['can_upgrade']) {
    json_response([
        'success' => false,
        'error' => 'Login with Discord to upgrade genetic traits',
        'genetic_access' => $geneticAccess
    ], 401);
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
            'error' => 'Listed genetic items cannot be upgraded',
            'data' => [
                'genetic_item_id' => $geneticItemId,
                'listed_listing_id' => isset($item['listed_listing_id']) ? (int)$item['listed_listing_id'] : null
            ]
        ], 409);
    }

    if ($upgradeStatus === 'upgrading') {
        json_response([
            'success' => false,
            'error' => 'This genetic trait is already upgrading'
        ], 409);
    }

    if ($upgradeStatus === 'ready_to_claim') {
        json_response([
            'success' => false,
            'error' => 'This genetic trait is ready to claim. Claim it before starting another upgrade.'
        ], 409);
    }

    if (user_has_active_genetic_upgrade($pdo, $userId, $geneticItemId)) {
        json_response([
            'success' => false,
            'error' => 'You already have another active genetic upgrade running'
        ], 409);
    }

    $durationHours = calculate_genetic_upgrade_duration_hours($currentLevel);

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
            'error' => 'Listed genetic items cannot be upgraded'
        ], 409);
    }

    if ($upgradeStatus !== 'idle') {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This genetic trait is not idle and cannot start a new upgrade'
        ], 409);
    }

    if (user_has_active_genetic_upgrade($pdo, $userId, $geneticItemId)) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'You already have another active genetic upgrade running'
        ], 409);
    }

    $updateStmt = $pdo->prepare("
        UPDATE tbl_user_genetic_items
        SET
            upgrade_status = 'upgrading',
            upgrade_started_at = CURRENT_TIMESTAMP,
            upgrade_ends_at = datetime('now', ?),
            ready_claim_notified_at = NULL,
            ready_claim_notification_count = 0,
            active_booster_item_id = NULL,
            active_booster_used_at = NULL,
            updated_at = CURRENT_TIMESTAMP
        WHERE genetic_item_id = ?
          AND user_id = ?
    ");
    $updateStmt->execute([
        '+' . $durationHours . ' hours',
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
        'upgrade_started',
        [
            'current_level' => $currentLevel,
            'upgrade_status' => 'idle'
        ],
        [
            'current_level' => $currentLevel,
            'target_level' => $currentLevel + 1,
            'upgrade_status' => 'upgrading',
            'duration_hours' => $durationHours,
            'upgrade_started_at' => $updatedItem['upgrade_started_at'] ?? null,
            'upgrade_ends_at' => $updatedItem['upgrade_ends_at'] ?? null
        ]
    );

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Genetic item upgrade started successfully',
        'data' => [
            'genetic_item_id' => (int)($updatedItem['genetic_item_id'] ?? 0),
            'catalog_id' => (int)($updatedItem['catalog_id'] ?? 0),
            'trait_type' => (string)($updatedItem['trait_type'] ?? ''),
            'trait_value' => (string)($updatedItem['trait_value'] ?? ''),
            'display_title' => (string)($updatedItem['display_title'] ?? $updatedItem['trait_value'] ?? ''),
            'current_level' => (int)($updatedItem['current_level'] ?? 1),
            'target_level' => ((int)($updatedItem['current_level'] ?? 1) + 1),
            'max_level' => GENETIC_ITEM_MAX_LEVEL,
            'upgrade_status' => (string)($updatedItem['upgrade_status'] ?? 'upgrading'),
            'duration_hours' => $durationHours,
            'upgrade_started_at' => $updatedItem['upgrade_started_at'] ?? null,
            'upgrade_ends_at' => $updatedItem['upgrade_ends_at'] ?? null,
            'is_listed_for_sale' => (int)($updatedItem['is_listed_for_sale'] ?? 0) === 1,
            'genetic_access' => $geneticAccess,
            'holder_access' => $geneticAccess
        ]
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Start Genetic Upgrade PDO ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to start genetic item upgrade',
        'details' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Start Genetic Upgrade ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to start genetic item upgrade',
        'details' => $e->getMessage()
    ], 500);
}