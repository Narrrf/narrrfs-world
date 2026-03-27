<?php
// 🧬 Get Genetic Trait Catalog API
// Returns visible Genetic Shop catalog rows for Outfit and Accessories.
// Stable-first rules:
// - Preview is public
// - Logged-in users also receive ownership and holder-access flags
// - Buy eligibility is backend-derived, never frontend-trusted
// - Catalog source of truth is tbl_genetic_trait_catalog
// - Genetic item ownership source of truth is tbl_user_genetic_items

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

function resolve_user_id_optional() {
    global $LOCAL_TEST_DISCORD_ID;

    $session_user_id = $_SESSION['discord_id'] ?? '';
    $request = get_request_data();
    $request_user_id = trim((string)($request['user_id'] ?? ''));
    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $request_user_id !== '') {
        return $request_user_id;
    }

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($session_user_id !== '') {
        return $session_user_id;
    }

    if ($isLocalhost && $request_user_id === '' && $session_user_id === '') {
        return '';
    }

    return '';
}

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

function get_user_total_dspoinc(PDO $pdo, string $userId): ?int {
    if ($userId === '') {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0) AS total_dspoinc
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return 0;
    }

    return (int)($row['total_dspoinc'] ?? 0);
}

function get_holder_access(PDO $pdo, string $userId): array {
    if ($userId === '') {
        return [
            'has_genesis' => false,
            'has_vip' => false,
            'can_buy' => false
        ];
    }

    // TODO: If the project has a stricter canonical verification source than tbl_holder_verifications,
    // switch this helper to that source only. For Sprint 2 this is a safe backend-first starting point.
    $stmt = $pdo->prepare("
        SELECT collection, nft_count, role_granted
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

function get_owned_trait_keys(PDO $pdo, string $userId): array {
    if ($userId === '') {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT trait_type, trait_value
        FROM tbl_user_genetic_items
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $owned = [];

    foreach ($rows as $row) {
        $traitType = trim((string)($row['trait_type'] ?? ''));
        $traitValue = trim((string)($row['trait_value'] ?? ''));

        if ($traitType === '' || $traitValue === '') {
            continue;
        }

        $owned[$traitType . '::' . $traitValue] = true;
    }

    return $owned;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $pdo = getDatabaseConnection();
    $request = get_request_data();
    $userId = resolve_user_id_optional();

    $traitTypeFilter = normalize_trait_type_filter($request['trait_type'] ?? '');
    $search = trim((string)($request['search'] ?? ''));

    $where = [
        "is_active = 1",
        "is_visible = 1"
    ];
    $params = [];

    if ($traitTypeFilter !== '') {
        $where[] = "trait_type = ?";
        $params[] = $traitTypeFilter;
    }

    if ($search !== '') {
        $where[] = "(trait_value LIKE ? OR display_title LIKE ? OR description LIKE ?)";
        $searchLike = '%' . $search . '%';
        $params[] = $searchLike;
        $params[] = $searchLike;
        $params[] = $searchLike;
    }

    $sql = "
        SELECT
            catalog_id,
            trait_type,
            trait_value,
            display_title,
            description,
            rarity_tier,
            rarity_count,
            base_price_dspoinc,
            image_type,
            image_path,
            preview_path,
            source_origin,
            effect_metadata_json,
            sort_order
        FROM tbl_genetic_trait_catalog
        WHERE " . implode(' AND ', $where) . "
        ORDER BY trait_type ASC, sort_order ASC, display_title ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $holderAccess = get_holder_access($pdo, $userId);
    $ownedKeys = get_owned_trait_keys($pdo, $userId);
    $balance = get_user_total_dspoinc($pdo, $userId);

    $catalog = [
        'Outfit' => [],
        'Accessories' => []
    ];

    $ownedCount = 0;

    foreach ($rows as $row) {
        $traitType = (string)($row['trait_type'] ?? '');
        $traitValue = (string)($row['trait_value'] ?? '');
        $owned = isset($ownedKeys[$traitType . '::' . $traitValue]);

        if ($owned) {
            $ownedCount++;
        }

        $item = [
            'catalog_id' => (int)($row['catalog_id'] ?? 0),
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => (string)($row['display_title'] ?? $traitValue),
            'description' => (string)($row['description'] ?? ''),
            'rarity_tier' => (string)($row['rarity_tier'] ?? 'common'),
            'rarity_count' => (int)($row['rarity_count'] ?? 0),
            'base_price_dspoinc' => (int)($row['base_price_dspoinc'] ?? 0),
            'image_type' => (string)($row['image_type'] ?? 'png'),
            'image_path' => (string)($row['image_path'] ?? ''),
            'preview_path' => (string)($row['preview_path'] ?? ''),
            'source_origin' => (string)($row['source_origin'] ?? 'mint_seed'),
            'effect_metadata_json' => (string)($row['effect_metadata_json'] ?? '{}'),
            'owned' => $owned,
            'can_buy' => $holderAccess['can_buy'] && !$owned
        ];

        if (!isset($catalog[$traitType])) {
            $catalog[$traitType] = [];
        }

        $catalog[$traitType][] = $item;
    }

    json_response([
        'success' => true,
        'data' => [
            'user_id' => $userId,
            'is_logged_in' => ($userId !== ''),
            'can_buy_genetic_traits' => $holderAccess['can_buy'],
            'holder_access' => $holderAccess,
            'balance_dspoinc' => $balance,
            'filters' => [
                'trait_type' => $traitTypeFilter,
                'search' => $search
            ],
            'summary' => [
                'total_visible_items' => count($rows),
                'owned_items_in_result' => $ownedCount
            ],
            'catalog' => $catalog
        ]
    ]);
} catch (Exception $e) {
    error_log('🧬 Genetic Trait Catalog ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to load genetic trait catalog',
        'details' => $e->getMessage()
    ], 500);
}