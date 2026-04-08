<?php
// 🧬 Admin Remove Genetic Item API
// Safely removes one user-bound Genetic item by genetic_item_id.
//
// Stability-first rules:
// - Genetic items are USER-bound, never NFT-bound
// - Remove only from tbl_user_genetic_items
// - Log every removal to tbl_genetic_item_history
// - Do not touch NFT-bound Genesis progression tables
// - Admin action must be explicit and traceable

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$localDiscordSecretPath = __DIR__ . '/../config/discord-secret.php';
if (file_exists($localDiscordSecretPath)) {
    include $localDiscordSecretPath;
}

session_start();

/**
 * Return JSON response and stop execution.
 */
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Read request data once from JSON, POST, or GET.
 */
function get_request_data(): array {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = [];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
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

/**
 * Load one Genetic item by ID.
 */
function fetch_genetic_item(PDO $pdo, int $geneticItemId): ?array {
    $stmt = $pdo->prepare("
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
            last_completed_at,
            ready_claim_notified_at,
            ready_claim_notification_count,
            active_booster_item_id,
            active_booster_used_at,
            acquired_method,
            is_listed_for_sale,
            listed_listing_id,
            created_at,
            updated_at,
            last_transfer_at,
            last_owner_user_id
        FROM tbl_user_genetic_items
        WHERE genetic_item_id = ?
        LIMIT 1
    ");
    $stmt->execute([$geneticItemId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Write a history row for auditing.
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
    $pdo = function_exists('getDatabaseConnection')
        ? getDatabaseConnection()
        : (function_exists('getDB') ? getDB() : null);

    if (!$pdo instanceof PDO) {
        throw new Exception('Database connection not available');
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $request = get_request_data();
    $adminUserId = require_admin_actor($request);

    $geneticItemId = (int)($request['genetic_item_id'] ?? 0);
    $expectedUserId = trim((string)($request['user_id'] ?? ''));

    if ($geneticItemId <= 0) {
        json_response([
            'success' => false,
            'error' => 'Missing or invalid genetic_item_id'
        ], 400);
    }

    $pdo->beginTransaction();

    $existingItem = fetch_genetic_item($pdo, $geneticItemId);

    if (!$existingItem) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Genetic item not found'
        ], 404);
    }

    $ownerUserId = (string)($existingItem['user_id'] ?? '');

    if ($expectedUserId !== '' && $ownerUserId !== '' && $expectedUserId !== $ownerUserId) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'User mismatch for requested Genetic item'
        ], 409);
    }

    $deleteStmt = $pdo->prepare("
    DELETE FROM tbl_user_genetic_items
    WHERE genetic_item_id = ?
");
$deleteStmt->execute([$geneticItemId]);

    if ($deleteStmt->rowCount() < 1) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Delete failed for Genetic item'
        ], 500);
    }

    insert_genetic_item_history(
        $pdo,
        (int)$existingItem['genetic_item_id'],
        !empty($existingItem['listed_listing_id']) ? (int)$existingItem['listed_listing_id'] : null,
        $ownerUserId,
        'admin_remove',
        $existingItem,
        [
            'removed' => true,
            'removed_at' => gmdate('Y-m-d H:i:s'),
            'removed_by' => $adminUserId
        ],
        $adminUserId
    );

    $remainingCountStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tbl_user_genetic_items
        WHERE user_id = ?
    ");
    $remainingCountStmt->execute([$ownerUserId]);
    $remainingCount = (int)$remainingCountStmt->fetchColumn();

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Genetic item removed successfully',
        'removed_genetic_item_id' => $geneticItemId,
        'user_id' => $ownerUserId,
        'remaining_genetic_inventory_count' => $remainingCount
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'success' => false,
        'error' => 'Failed to remove Genetic item',
        'details' => $e->getMessage()
    ], 500);
}