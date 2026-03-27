<?php
// 🧬 Get User Genetic Items API
// Returns all Genetic Shop traits owned by the current Discord user.
//
// Stability-first rules:
// - User-bound system only (NOT NFT-bound)
// - Requires logged-in user in production
// - Localhost may use request user_id or Narrrf fallback
// - Reads owned traits from tbl_user_genetic_items
// - Enriches rows from tbl_genetic_trait_catalog
// - Returns inventory-ready data for future Lab tabs
// - Does NOT mutate any upgrade state
// - Does NOT touch marketplace ownership state beyond displaying listed flags

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
 * Resolve the active user ID using the same production/local pattern as the live Lab APIs.
 */
function resolve_user_id() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = $_SESSION['discord_id'] ?? '';
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        error_log("🧬 Get User Genetic Items: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Get User Genetic Items - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧬 Get User Genetic Items: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $user_id;
}

/**
 * Normalize trait type filters to the current Genetic Shop canon.
 */
function normalize_trait_type_filter($raw) {
    $value = trim((string)$raw);

    if ($value === '') {
        return '';
    }

    $lower = strtolower($value);

    if ($lower === 'outfit') {
        return 'Outfit';
    }

    if ($lower === 'accessories' || $lower === 'accessory') {
        return 'Accessories';
    }

    return '';
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
 * Build a stable summary block for the user's owned genetic items.
 */
function build_inventory_summary(array $items): array {
    $summary = [
        'total_items' => 0,
        'outfit_items' => 0,
        'accessories_items' => 0,
        'idle_items' => 0,
        'upgrading_items' => 0,
        'ready_to_claim_items' => 0,
        'listed_items' => 0,
        'max_level' => 0
    ];

    foreach ($items as $item) {
        $summary['total_items']++;

        $traitType = (string)($item['trait_type'] ?? '');
        $status = (string)($item['upgrade_status'] ?? 'idle');
        $level = (int)($item['current_level'] ?? 1);
        $isListed = !empty($item['is_listed_for_sale']);

        if ($traitType === 'Outfit') {
            $summary['outfit_items']++;
        } elseif ($traitType === 'Accessories') {
            $summary['accessories_items']++;
        }

        if ($status === 'upgrading') {
            $summary['upgrading_items']++;
        } elseif ($status === 'ready_to_claim') {
            $summary['ready_to_claim_items']++;
        } else {
            $summary['idle_items']++;
        }

        if ($isListed) {
            $summary['listed_items']++;
        }

        if ($level > $summary['max_level']) {
            $summary['max_level'] = $level;
        }
    }

    return $summary;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

    $pdo = getDatabaseConnection();
    $request = get_request_data();

    $traitTypeFilter = normalize_trait_type_filter($request['trait_type'] ?? '');
    $statusFilter = trim((string)($request['status'] ?? ''));
    $search = trim((string)($request['search'] ?? ''));

    $allowedStatuses = ['idle', 'upgrading', 'ready_to_claim'];
    if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
        json_response([
            'success' => false,
            'error' => 'Invalid status filter'
        ], 400);
    }

    $where = [
        "ugi.user_id = ?"
    ];
    $params = [$userId];

    if ($traitTypeFilter !== '') {
        $where[] = "ugi.trait_type = ?";
        $params[] = $traitTypeFilter;
    }

    if ($statusFilter !== '') {
        $where[] = "ugi.upgrade_status = ?";
        $params[] = $statusFilter;
    }

    if ($search !== '') {
        $where[] = "(
            ugi.trait_value LIKE ?
            OR COALESCE(gtc.display_title, ugi.trait_value) LIKE ?
            OR COALESCE(gtc.description, '') LIKE ?
        )";
        $searchLike = '%' . $search . '%';
        $params[] = $searchLike;
        $params[] = $searchLike;
        $params[] = $searchLike;
    }

    $sql = "
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
        WHERE " . implode(' AND ', $where) . "
        ORDER BY
            CASE
                WHEN ugi.upgrade_status = 'ready_to_claim' THEN 1
                WHEN ugi.upgrade_status = 'upgrading' THEN 2
                ELSE 3
            END ASC,
            ugi.is_listed_for_sale DESC,
            ugi.current_level DESC,
            COALESCE(gtc.display_title, ugi.trait_value) ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];

    foreach ($rows as $row) {
        $items[] = [
            'genetic_item_id' => (int)($row['genetic_item_id'] ?? 0),
            'user_id' => (string)($row['user_id'] ?? ''),
            'catalog_id' => (int)($row['catalog_id'] ?? 0),
            'trait_type' => (string)($row['trait_type'] ?? ''),
            'trait_value' => (string)($row['trait_value'] ?? ''),
            'display_title' => (string)($row['display_title'] ?? $row['trait_value'] ?? ''),
            'description' => (string)($row['description'] ?? ''),
            'rarity_tier' => (string)($row['rarity_tier'] ?? 'common'),
            'rarity_count' => (int)($row['rarity_count'] ?? 0),
            'base_price_dspoinc' => (int)($row['base_price_dspoinc'] ?? 0),
            'image_type' => (string)($row['image_type'] ?? 'png'),
            'image_path' => (string)($row['image_path'] ?? ''),
            'preview_path' => (string)($row['preview_path'] ?? ''),
            'source_origin' => (string)($row['source_origin'] ?? 'mint_seed'),
            'effect_metadata_json' => (string)($row['effect_metadata_json'] ?? '{}'),

            'current_level' => (int)($row['current_level'] ?? 1),
            'upgrade_status' => (string)($row['upgrade_status'] ?? 'idle'),
            'upgrade_started_at' => $row['upgrade_started_at'] ?? null,
            'upgrade_ends_at' => $row['upgrade_ends_at'] ?? null,
            'last_completed_at' => $row['last_completed_at'] ?? null,
            'ready_claim_notified_at' => $row['ready_claim_notified_at'] ?? null,
            'ready_claim_notification_count' => (int)($row['ready_claim_notification_count'] ?? 0),

            'active_booster_item_id' => isset($row['active_booster_item_id']) ? (int)$row['active_booster_item_id'] : null,
            'active_booster_used_at' => $row['active_booster_used_at'] ?? null,

            'acquired_method' => (string)($row['acquired_method'] ?? 'shop'),
            'is_listed_for_sale' => (int)($row['is_listed_for_sale'] ?? 0) === 1,
            'listed_listing_id' => isset($row['listed_listing_id']) ? (int)$row['listed_listing_id'] : null,

            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
            'last_transfer_at' => $row['last_transfer_at'] ?? null,
            'last_owner_user_id' => (string)($row['last_owner_user_id'] ?? ''),

            'catalog_is_active' => (int)($row['is_active'] ?? 1) === 1,
            'catalog_is_visible' => (int)($row['is_visible'] ?? 1) === 1
        ];
    }

    $summary = build_inventory_summary($items);
    $holderAccess = get_holder_access($pdo, $userId);

    json_response([
        'success' => true,
        'data' => [
            'user_id' => $userId,
            'holder_access' => $holderAccess,
            'filters' => [
                'trait_type' => $traitTypeFilter,
                'status' => $statusFilter,
                'search' => $search
            ],
            'summary' => $summary,
            'items' => $items
        ]
    ]);
} catch (Exception $e) {
    error_log('🧬 Get User Genetic Items ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to load genetic inventory',
        'details' => $e->getMessage()
    ], 500);
}
