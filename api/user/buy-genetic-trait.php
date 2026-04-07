<?php
// 🧬 Buy Genetic Trait API
// Purchases one Genetic Shop catalog trait and adds it to the player's genetic inventory.
//
// Stability-first rules:
// - Any Discord-authenticated user may buy
// - Preview is public, purchase requires Discord login
// - Genetic traits are user-bound, not NFT-bound
// - One user may own only one exact trait_type + trait_value
// - DSPOINC is the only currency
// - Catalog source of truth is tbl_genetic_trait_catalog
// - Owned item source of truth is tbl_user_genetic_items
// - Purchase history is logged in tbl_genetic_item_history
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
        error_log("🧬 Buy Genetic Trait: Using request user_id for localhost testing: {$request_user_id}");
        return $request_user_id;
    }

    $user_id = $session_user_id;

    if ($request_user_id !== '' && $session_user_id !== '' && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Buy Genetic Trait - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if (!$user_id && $isLocalhost) {
        error_log("🧬 Buy Genetic Trait: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $user_id;
}

/**
 * Returns available DSPOINC = total - frozen (active stakes)
 */
function get_user_available_dspoinc(PDO $pdo, string $userId): int {
    // total
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0)
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $total = (int)$stmt->fetchColumn();

    // frozen
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

function fetch_catalog_row(PDO $pdo, int $catalogId): ?array {
    $stmt = $pdo->prepare("
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
            is_active,
            is_visible
        FROM tbl_genetic_trait_catalog
        WHERE catalog_id = ?
        LIMIT 1
    ");
    $stmt->execute([$catalogId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
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

function user_owns_genetic_trait(PDO $pdo, string $userId, string $traitType, string $traitValue): bool {
    $stmt = $pdo->prepare("
        SELECT genetic_item_id
        FROM tbl_user_genetic_items
        WHERE user_id = ?
          AND trait_type = ?
          AND trait_value = ?
        LIMIT 1
    ");
    $stmt->execute([$userId, $traitType, $traitValue]);

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

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
        'genetic_shop',
        $negativeAmount,
        $reason . ' [' . $reference . ']'
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

    $userId = resolve_user_id();
    if (!$userId) {
        json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    $request = get_request_data();
    $catalogId = (int)($request['catalog_id'] ?? 0);

    if ($catalogId < 1) {
        json_response([
            'success' => false,
            'error' => 'Missing or invalid catalog_id'
        ], 400);
    }

    $pdo = getDatabaseConnection();

    $catalogRow = fetch_catalog_row($pdo, $catalogId);
    if (!$catalogRow) {
        json_response([
            'success' => false,
            'error' => 'Catalog item not found'
        ], 404);
    }

    if ((int)($catalogRow['is_active'] ?? 0) !== 1 || (int)($catalogRow['is_visible'] ?? 0) !== 1) {
        json_response([
            'success' => false,
            'error' => 'This genetic trait is not currently available for purchase'
        ], 409);
    }

    $traitType = trim((string)($catalogRow['trait_type'] ?? ''));
    $traitValue = trim((string)($catalogRow['trait_value'] ?? ''));
    $displayTitle = trim((string)($catalogRow['display_title'] ?? $traitValue));
    $price = max(0, (int)($catalogRow['base_price_dspoinc'] ?? 0));

    if ($traitType === '' || $traitValue === '') {
        json_response([
            'success' => false,
            'error' => 'Catalog item is malformed'
        ], 500);
    }

    $geneticAccess = get_genetic_access($userId);
    if (user_owns_genetic_trait($pdo, $userId, $traitType, $traitValue)) {
        json_response([
            'success' => false,
            'error' => 'You already own this genetic trait',
            'data' => [
                'catalog_id' => $catalogId,
                'trait_type' => $traitType,
                'trait_value' => $traitValue
            ]
        ], 409);
    }

// Use canonical available balance (total - frozen stakes)
$currentBalance = get_user_available_dspoinc($pdo, $userId);

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

// Re-check ownership inside the transaction for safer duplicate prevention.
if (user_owns_genetic_trait($pdo, $userId, $traitType, $traitValue)) {
    $pdo->rollBack();
    json_response([
        'success' => false,
        'error' => 'You already own this genetic trait'
    ], 409);
}

// Re-check available DSPOINC inside the transaction to reduce race-condition risk.
$currentBalance = get_user_available_dspoinc($pdo, $userId);

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

insert_dspoinc_spend(
    $pdo,
    $userId,
    $price,
    'Genetic Shop Purchase: ' . $displayTitle,
    'catalog_id=' . $catalogId
);

    $insertStmt = $pdo->prepare("
        INSERT INTO tbl_user_genetic_items (
            user_id,
            catalog_id,
            trait_type,
            trait_value,
            current_level,
            upgrade_status,
            acquired_method,
            is_listed_for_sale,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, 1, 'idle', 'shop', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $insertStmt->execute([
        $userId,
        $catalogId,
        $traitType,
        $traitValue
    ]);

    $geneticItemId = (int)$pdo->lastInsertId();
    $remainingBalance = get_user_available_dspoinc($pdo, $userId);

    insert_genetic_item_history(
        $pdo,
        $geneticItemId,
        null,
        $userId,
        'shop_purchase',
        [],
        [
            'catalog_id' => $catalogId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'price_paid_dspoinc' => $price,
            'remaining_balance_dspoinc' => $remainingBalance,
            'acquired_method' => 'shop'
        ]
    );

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Genetic trait purchased successfully',
        'data' => [
            'genetic_item_id' => $geneticItemId,
            'catalog_id' => $catalogId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'current_level' => 1,
            'upgrade_status' => 'idle',
            'price_paid_dspoinc' => $price,
            'remaining_balance_dspoinc' => $remainingBalance,
            'genetic_access' => $geneticAccess,
            'holder_access' => $geneticAccess
        ]
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // SQLite unique constraint collision = duplicate ownership
    if (strpos(strtolower($e->getMessage()), 'unique') !== false) {
        json_response([
            'success' => false,
            'error' => 'You already own this genetic trait'
        ], 409);
    }

    error_log('🧬 Buy Genetic Trait PDO ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to complete genetic trait purchase',
        'details' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Buy Genetic Trait ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to complete genetic trait purchase',
        'details' => $e->getMessage()
    ], 500);
}