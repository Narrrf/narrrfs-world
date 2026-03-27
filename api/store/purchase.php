<?php
// 🛒 Store Purchase API
// Purchases a store item with DSPOINC and adds it to the user's inventory.
//
// Security and economy rules:
// - Production must use session-first auth
// - Localhost may use request user_id or Narrrf fallback
// - DSPOINC available balance is canonical:
//   available = total_score_ledger - active_frozen_stakes
// - Purchase runs inside a transaction
// - Balance is re-checked inside the transaction
// - Audit trail is written to tbl_score_adjustments
// - Inventory is updated atomically in the same transaction

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error_log.txt');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf local fallback

/**
 * Return a JSON response and stop execution.
 */
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running in localhost-style development.
 */
function is_localhost_env(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request JSON safely.
 */
function get_request_data(): array {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Resolve the active user using session-first auth in production.
 */
function resolve_user_id(array $request): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = $_SESSION['discord_id'] ?? '';
    $requestUserId = trim((string)($request['user_id'] ?? ''));
    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $requestUserId !== '') {
        error_log("🛒 Store Purchase: Using request user_id for localhost testing: {$requestUserId}");
        return $requestUserId;
    }

    $userId = $sessionUserId;

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        error_log("🚨 SECURITY: Store Purchase - user_id mismatch. Session: {$sessionUserId}, Request: {$requestUserId}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($userId === '' && $isLocalhost) {
        error_log('🛒 Store Purchase: Using local test user (Narrrf) for localhost');
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $userId;
}

/**
 * Return total DSPOINC from the score ledger.
 */
function get_user_total_dspoinc(SQLite3 $db, string $userId): int {
    $stmt = $db->prepare('
        SELECT COALESCE(SUM(score), 0) AS total_balance
        FROM tbl_user_scores
        WHERE user_id = ?
    ');
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);

    $result = $stmt->execute();
    $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

    return (int)($row['total_balance'] ?? 0);
}

/**
 * Return frozen DSPOINC from active stakes.
 */
function get_user_frozen_dspoinc(SQLite3 $db, string $userId): int {
    $tableCheck = $db->query("
        SELECT name
        FROM sqlite_master
        WHERE type = 'table'
          AND name = 'tbl_dspoinc_stakes'
    ");
    $tableExists = $tableCheck ? $tableCheck->fetchArray(SQLITE3_ASSOC) : false;

    if (!$tableExists) {
        return 0;
    }

    $stmt = $db->prepare('
        SELECT COALESCE(SUM(amount), 0) AS frozen_balance
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = ?
    ');
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $stmt->bindValue(2, 'active', SQLITE3_TEXT);

    $result = $stmt->execute();
    $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

    return (int)($row['frozen_balance'] ?? 0);
}

/**
 * Return canonical available DSPOINC.
 */
function get_user_available_dspoinc(SQLite3 $db, string $userId): int {
    $total = get_user_total_dspoinc($db, $userId);
    $frozen = get_user_frozen_dspoinc($db, $userId);

    return max(0, $total - $frozen);
}

/**
 * Return a balance snapshot for responses and verification.
 */
function get_user_balance_snapshot(SQLite3 $db, string $userId): array {
    $total = get_user_total_dspoinc($db, $userId);
    $frozen = get_user_frozen_dspoinc($db, $userId);

    return [
        'total_balance' => $total,
        'frozen_balance' => $frozen,
        'available_balance' => max(0, $total - $frozen)
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $request = get_request_data();
    $userId = resolve_user_id($request);
    $itemId = (int)($request['item_id'] ?? 0);
    $quantity = (int)($request['quantity'] ?? 1);

    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    if ($itemId < 1 || $quantity < 1) {
        json_response([
            'success' => false,
            'error' => 'User ID, item ID, and quantity are required. Quantity must be positive.'
        ], 400);
    }

    $db = getSQLite3Connection();
    $db->enableExceptions(true);

    $db->exec('BEGIN IMMEDIATE TRANSACTION');

    // Verify user exists.
    $userStmt = $db->prepare('
        SELECT discord_id
        FROM tbl_users
        WHERE discord_id = ?
        LIMIT 1
    ');
    $userStmt->bindValue(1, $userId, SQLITE3_TEXT);
    $userResult = $userStmt->execute();
    $userRow = $userResult ? $userResult->fetchArray(SQLITE3_ASSOC) : false;

    if (!$userRow) {
        $db->exec('ROLLBACK');
        json_response([
            'success' => false,
            'error' => 'User not found in system. Please contact an administrator.'
        ], 404);
    }

    // Load active store item.
    $itemStmt = $db->prepare('
        SELECT *
        FROM tbl_store_items
        WHERE item_id = ?
          AND is_active = 1
        LIMIT 1
    ');
    $itemStmt->bindValue(1, $itemId, SQLITE3_INTEGER);
    $itemResult = $itemStmt->execute();
    $item = $itemResult ? $itemResult->fetchArray(SQLITE3_ASSOC) : false;

    if (!$item) {
        $db->exec('ROLLBACK');
        json_response([
            'success' => false,
            'error' => 'Item not found or not available'
        ], 404);
    }

    $unitPrice = (int)($item['price'] ?? 0);
    if ($unitPrice <= 0) {
        $db->exec('ROLLBACK');
        json_response([
            'success' => false,
            'error' => 'Invalid item price. Please contact an administrator.'
        ], 409);
    }

    $totalCost = $unitPrice * $quantity;

    // Canonical in-transaction balance check.
    $balanceBefore = get_user_balance_snapshot($db, $userId);
    if ((int)$balanceBefore['available_balance'] < $totalCost) {
        $db->exec('ROLLBACK');
        json_response([
            'success' => false,
            'error' => 'Insufficient available balance',
            'data' => [
                'required_cost' => $totalCost,
                'available_balance' => (int)$balanceBefore['available_balance'],
                'total_balance' => (int)$balanceBefore['total_balance'],
                'frozen_balance' => (int)$balanceBefore['frozen_balance']
            ]
        ], 409);
    }

    // Deduct DSPOINC from score ledger.
    $scoreStmt = $db->prepare('
        INSERT INTO tbl_user_scores (
            user_id,
            score,
            game,
            source,
            timestamp
        ) VALUES (?, ?, ?, ?, datetime("now"))
    ');
    $scoreStmt->bindValue(1, $userId, SQLITE3_TEXT);
    $scoreStmt->bindValue(2, -$totalCost, SQLITE3_INTEGER);
    $scoreStmt->bindValue(3, 'store_purchase', SQLITE3_TEXT);
    $scoreStmt->bindValue(4, 'store', SQLITE3_TEXT);
    $scoreStmt->execute();

    // Audit trail for balance change.
    $adjustStmt = $db->prepare('
        INSERT INTO tbl_score_adjustments (
            user_id,
            admin_id,
            amount,
            action,
            reason,
            timestamp
        ) VALUES (?, ?, ?, ?, ?, datetime("now"))
    ');
    $adjustStmt->bindValue(1, $userId, SQLITE3_TEXT);
    $adjustStmt->bindValue(2, $userId, SQLITE3_TEXT);
    $adjustStmt->bindValue(3, -$totalCost, SQLITE3_INTEGER);
    $adjustStmt->bindValue(4, 'remove', SQLITE3_TEXT);
    $adjustStmt->bindValue(5, 'Store purchase: ' . (string)($item['item_name'] ?? 'Unknown Item') . ' x' . $quantity, SQLITE3_TEXT);
    $adjustStmt->execute();

    // Add purchased item to inventory.
    $inventoryStmt = $db->prepare('
        INSERT INTO tbl_user_inventory (
            user_id,
            item_id,
            quantity,
            acquired_at
        ) VALUES (?, ?, ?, datetime("now"))
        ON CONFLICT(user_id, item_id) DO UPDATE SET
            quantity = quantity + excluded.quantity
    ');
    $inventoryStmt->bindValue(1, $userId, SQLITE3_TEXT);
    $inventoryStmt->bindValue(2, $itemId, SQLITE3_INTEGER);
    $inventoryStmt->bindValue(3, $quantity, SQLITE3_INTEGER);
    $inventoryStmt->execute();

    // Purchase history.
    $historyStmt = $db->prepare('
        INSERT INTO tbl_purchase_history (
            user_id,
            item_id,
            price_paid,
            quantity,
            purchased_at
        ) VALUES (?, ?, ?, ?, datetime("now"))
    ');
    $historyStmt->bindValue(1, $userId, SQLITE3_TEXT);
    $historyStmt->bindValue(2, $itemId, SQLITE3_INTEGER);
    $historyStmt->bindValue(3, $totalCost, SQLITE3_INTEGER);
    $historyStmt->bindValue(4, $quantity, SQLITE3_INTEGER);
    $historyStmt->execute();

    // Final verification using canonical balance, not raw total only.
    $balanceAfter = get_user_balance_snapshot($db, $userId);

    // TODO: If this ever triggers, investigate for ledger desync instead of silently masking it.
    if ((int)$balanceAfter['available_balance'] < 0) {
        $db->exec('ROLLBACK');
        json_response([
            'success' => false,
            'error' => 'Purchase verification failed. Please contact an administrator.'
        ], 500);
    }

    $db->exec('COMMIT');

    json_response([
        'success' => true,
        'message' => 'Purchase successful',
        'item' => [
            'item_id' => $itemId,
            'name' => (string)($item['item_name'] ?? ''),
            'description' => (string)($item['description'] ?? '')
        ],
        'quantity' => $quantity,
        'total_price' => $totalCost,
        'available_balance_before' => (int)$balanceBefore['available_balance'],
        'new_total_balance' => (int)$balanceAfter['total_balance'],
        'new_frozen_balance' => (int)$balanceAfter['frozen_balance'],
        'new_available_balance' => (int)$balanceAfter['available_balance']
    ]);
} catch (Exception $e) {
    if (isset($db)) {
        try {
            $db->exec('ROLLBACK');
        } catch (Exception $rollbackError) {
            // Ignore rollback errors when no active transaction remains.
        }
    }

    error_log('[STORE PURCHASE ERROR] ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());

    json_response([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ], 500);
} finally {
    if (isset($db)) {
        try {
            $db->close();
        } catch (Exception $closeError) {
            // Ignore close errors.
        }
    }
}
?>