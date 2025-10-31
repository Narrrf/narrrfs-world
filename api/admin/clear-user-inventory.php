<?php
/**
 * Admin API - Clear User Inventory
 * 
 * Purpose: Allow admins to clear ALL items from a user's inventory
 * Used by: Admin Interface - Store Management Tab
 * Created: October 31, 2025
 * 
 * ⚠️ WARNING: This is a destructive action that cannot be undone!
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
$isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;

if ($isProduction) {
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
} else {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request data'
    ]);
    exit;
}

$userId = $input['user_id'] ?? '';
$adminUsername = $input['admin_username'] ?? 'Unknown Admin';

// Validate inputs
if (empty($userId)) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required field: user_id'
    ]);
    exit;
}

try {
    // 1. Get current inventory count and value BEFORE clearing
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT ui.item_id) as unique_items,
            SUM(ui.quantity) as total_items,
            SUM(si.price * ui.quantity) as total_value
        FROM tbl_user_inventory ui
        JOIN tbl_store_items si ON ui.item_id = si.item_id
        WHERE ui.user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stats || $stats['total_items'] == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'User inventory is already empty'
        ]);
        exit;
    }

    $itemsRemoved = $stats['total_items'];
    $uniqueItemsRemoved = $stats['unique_items'];
    $totalValue = $stats['total_value'];

    // 2. Get detailed list for logging
    $stmt = $pdo->prepare("
        SELECT ui.item_id, si.item_name, ui.quantity, si.price
        FROM tbl_user_inventory ui
        JOIN tbl_store_items si ON ui.item_id = si.item_id
        WHERE ui.user_id = ?
    ");
    $stmt->execute([$userId]);
    $inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Delete all inventory items
    $stmt = $pdo->prepare("DELETE FROM tbl_user_inventory WHERE user_id = ?");
    $stmt->execute([$userId]);

    // 4. Log admin action
    try {
        $stmt = $pdo->prepare("
            CREATE TABLE IF NOT EXISTS tbl_admin_inventory_actions (
                action_id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_username TEXT NOT NULL,
                action_type TEXT NOT NULL,
                user_id TEXT NOT NULL,
                item_id INTEGER,
                item_name TEXT,
                quantity INTEGER,
                item_value INTEGER,
                action_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $stmt->execute();

        // Log the clear action
        $stmt = $pdo->prepare("
            INSERT INTO tbl_admin_inventory_actions 
            (admin_username, action_type, user_id, item_name, quantity, item_value)
            VALUES (?, 'clear_inventory', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $adminUsername, 
            $userId, 
            "{$uniqueItemsRemoved} unique items", 
            $itemsRemoved, 
            $totalValue
        ]);

        // Log each individual item removed for complete audit trail
        foreach ($inventoryItems as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO tbl_admin_inventory_actions 
                (admin_username, action_type, user_id, item_id, item_name, quantity, item_value)
                VALUES (?, 'clear_inventory_item', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $adminUsername,
                $userId,
                $item['item_id'],
                $item['item_name'],
                $item['quantity'],
                $item['price'] * $item['quantity']
            ]);
        }
    } catch (PDOException $e) {
        // Log creation/insertion failed, but continue
        error_log("Failed to log admin action: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => "Successfully cleared all items from user's inventory",
        'items_removed' => $itemsRemoved,
        'unique_items_removed' => $uniqueItemsRemoved,
        'total_value' => $totalValue,
        'admin' => $adminUsername,
        'cleared_items' => $inventoryItems
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

