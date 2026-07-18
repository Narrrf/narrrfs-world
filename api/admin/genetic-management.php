<?php
// 🧬 Admin Genetic Management API
// Safe admin gifting for Discord-bound Genetic items.
//
// Supported actions:
// - get_catalog
// - gift_genetic_item
// - bulk_gift_genetic_item
//
// Stability-first rules:
// - Genetic items are USER-bound, never NFT-bound
// - Catalog source of truth = tbl_genetic_trait_catalog
// - Owned item source of truth = tbl_user_genetic_items
// - One user may own up to two exact trait_type + trait_value combinations
// - Admin gifts create owned rows at level 1 / idle state
// - Admin gifts are logged in tbl_genetic_item_history
// - This file is additive and does not modify store item gifting

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');

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

function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function get_request_data(): array {
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
 * Verify the request is coming from an authenticated admin session
 * and return a traceable admin actor name for history logs.
 */
function require_admin_actor(array $request): string {
    $givenBy = trim((string)($request['given_by'] ?? ''));

    $sessionDiscordId = trim((string)($_SESSION['discord_id'] ?? ''));
    $sessionAdminLoggedIn = !empty($_SESSION['admin_logged_in']);
    $sessionIsModerator = !empty($_SESSION['is_moderator']);
    $sessionIsAdmin = !empty($_SESSION['is_admin']);
    $sessionUsername = trim((string)($_SESSION['admin_username'] ?? $_SESSION['username'] ?? ''));

    $hasAdminSession =
        $sessionAdminLoggedIn ||
        $sessionIsAdmin ||
        $sessionIsModerator ||
        $sessionDiscordId !== '';

    if (!$hasAdminSession) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized admin access'
        ], 403);
    }

    if ($givenBy !== '') {
        return $givenBy;
    }

    if ($sessionUsername !== '') {
        return $sessionUsername;
    }

    if ($sessionDiscordId !== '') {
        return $sessionDiscordId;
    }

    return 'admin_session';
}

function fetch_catalog_item(PDO $pdo, int $catalogId): ?array {
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

function fetch_catalog_for_admin(PDO $pdo): array {
    $stmt = $pdo->query("
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
        ORDER BY
            CASE LOWER(COALESCE(trait_type, ''))
                WHEN 'outfit' THEN 1
                WHEN 'accessories' THEN 2
                ELSE 99
            END,
            LOWER(COALESCE(rarity_tier, '')),
            LOWER(COALESCE(display_title, trait_value, ''))
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return is_array($rows) ? $rows : [];
}

const GENETIC_MAX_OWNED_PER_EXACT_TRAIT = 2;

/**
 * Count how many copies of one exact Genetic trait a user owns.
 *
 * Plain language for DEVS FOR DECADES:
 * Genetic Items are Discord-user-bound inventory rows. The Lab rule allows
 * up to 2 copies of the same exact trait_type + trait_value for one user.
 * This function only counts rows. It does not grant, remove, upgrade, list,
 * sell, spend DSPOINC, or touch NFT-bound Genesis progression.
 */
function count_owned_exact_genetic_trait(PDO $pdo, string $userId, string $traitType, string $traitValue): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tbl_user_genetic_items
        WHERE user_id = ?
          AND trait_type = ?
          AND trait_value = ?
    ");
    $stmt->execute([$userId, $traitType, $traitValue]);

    return (int)$stmt->fetchColumn();
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

function create_admin_gifted_genetic_item(PDO $pdo, string $userId, array $catalogRow, string $adminUserId): array {
    $catalogId = (int)($catalogRow['catalog_id'] ?? 0);
    $traitType = trim((string)($catalogRow['trait_type'] ?? ''));
    $traitValue = trim((string)($catalogRow['trait_value'] ?? ''));
    $displayTitle = trim((string)($catalogRow['display_title'] ?? $traitValue));

    if ($catalogId < 1 || $traitType === '' || $traitValue === '') {
        throw new RuntimeException('Catalog row is malformed');
    }

$ownedCopies = count_owned_exact_genetic_trait($pdo, $userId, $traitType, $traitValue);

if ($ownedCopies >= GENETIC_MAX_OWNED_PER_EXACT_TRAIT) {
    return [
        'success' => false,
        'skipped_duplicate' => true,
        'error' => 'User already owns the maximum 2 copies of this genetic trait',
        'data' => [
            'user_id' => $userId,
            'catalog_id' => $catalogId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'owned_copies' => $ownedCopies,
            'max_owned_copies' => GENETIC_MAX_OWNED_PER_EXACT_TRAIT
        ]
    ];
}

    $insertStmt = $pdo->prepare("
        INSERT INTO tbl_user_genetic_items (
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
        ) VALUES (
            ?, ?, ?, ?, 1, 'idle', NULL, NULL, 'admin_gift', 0, NULL, NULL, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
        )
    ");
    $insertStmt->execute([
        $userId,
        $catalogId,
        $traitType,
        $traitValue,
        $userId
    ]);

    $geneticItemId = (int)$pdo->lastInsertId();

    insert_genetic_item_history(
        $pdo,
        $geneticItemId,
        null,
        $userId,
        'admin_gift',
        [],
        [
            'genetic_item_id' => $geneticItemId,
            'user_id' => $userId,
            'catalog_id' => $catalogId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'current_level' => 1,
            'upgrade_status' => 'idle',
            'acquired_method' => 'admin_gift',
            'is_listed_for_sale' => 0
        ],
        $adminUserId
    );

    return [
        'success' => true,
        'skipped_duplicate' => false,
        'data' => [
            'genetic_item_id' => $geneticItemId,
            'user_id' => $userId,
            'catalog_id' => $catalogId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'current_level' => 1,
            'upgrade_status' => 'idle',
            'acquired_method' => 'admin_gift',
            'is_listed_for_sale' => 0
        ]
    ];
}

try {
    $request = get_request_data();
    $action = trim((string)($request['action'] ?? ''));

    if ($action === '') {
        json_response([
            'success' => false,
            'error' => 'Missing action'
        ], 400);
    }

    $pdo = getDatabaseConnection();

    if ($action === 'get_catalog') {
        $items = fetch_catalog_for_admin($pdo);

        json_response([
            'success' => true,
            'data' => [
                'items' => $items,
                'count' => count($items)
            ]
        ]);
    }

    if ($action === 'gift_genetic_item') {
        $adminUserId = require_admin_actor($request);

        $userId = trim((string)($request['user_id'] ?? ''));
        $catalogId = (int)($request['catalog_id'] ?? 0);

        if ($userId === '' || $catalogId < 1) {
            json_response([
                'success' => false,
                'error' => 'Missing or invalid user_id / catalog_id'
            ], 400);
        }

        $catalogRow = fetch_catalog_item($pdo, $catalogId);
        if (!$catalogRow) {
            json_response([
                'success' => false,
                'error' => 'Genetic catalog item not found'
            ], 404);
        }

        $pdo->beginTransaction();

        $result = create_admin_gifted_genetic_item($pdo, $userId, $catalogRow, $adminUserId);

        if (!$result['success']) {
            $pdo->rollBack();
            json_response($result, 409);
        }

        $pdo->commit();

        json_response([
            'success' => true,
            'message' => 'Genetic item gifted successfully',
            'data' => $result['data']
        ]);
    }

    if ($action === 'bulk_gift_genetic_item') {
        $adminUserId = require_admin_actor($request);

        $userIds = $request['user_ids'] ?? [];
        $catalogId = (int)($request['catalog_id'] ?? 0);

        if (!is_array($userIds) || !$userIds || $catalogId < 1) {
            json_response([
                'success' => false,
                'error' => 'Missing or invalid user_ids / catalog_id'
            ], 400);
        }

        $cleanUserIds = array_values(array_unique(array_filter(array_map(
            static fn($value) => trim((string)$value),
            $userIds
        ))));

        if (!$cleanUserIds) {
            json_response([
                'success' => false,
                'error' => 'No valid user_ids supplied'
            ], 400);
        }

        $catalogRow = fetch_catalog_item($pdo, $catalogId);
        if (!$catalogRow) {
            json_response([
                'success' => false,
                'error' => 'Genetic catalog item not found'
            ], 404);
        }

        $gifted = [];
        $skipped = [];
        $failed = [];

        foreach ($cleanUserIds as $userId) {
            try {
                $pdo->beginTransaction();

                $result = create_admin_gifted_genetic_item($pdo, $userId, $catalogRow, $adminUserId);

                if ($result['success']) {
                    $pdo->commit();
                    $gifted[] = $result['data'];
                } else {
                    $pdo->rollBack();
                    $skipped[] = $result['data'];
                }
            } catch (Throwable $innerError) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $failed[] = [
                    'user_id' => $userId,
                    'error' => $innerError->getMessage()
                ];
            }
        }

        json_response([
            'success' => true,
            'message' => 'Bulk genetic gifting completed',
            'data' => [
                'catalog_id' => $catalogId,
                'gifted_count' => count($gifted),
                'skipped_count' => count($skipped),
                'failed_count' => count($failed),
                'gifted' => $gifted,
                'skipped_duplicates' => $skipped,
                'failed' => $failed
            ]
        ]);
    }

    json_response([
        'success' => false,
        'error' => 'Unknown action'
    ], 400);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Admin Genetic Management PDO ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Genetic management request failed',
        'details' => $e->getMessage()
    ], 500);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🧬 Admin Genetic Management ERROR: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Genetic management request failed',
        'details' => $e->getMessage()
    ], 500);
}