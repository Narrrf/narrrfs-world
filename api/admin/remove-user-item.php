<?php
/**
 * Admin API - Remove Item from User Inventory
 * 
 * Purpose: Allow admins to remove specific items from user inventories
 * Used by: Admin Interface - Store Management Tab
 * Created: October 31, 2025
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
$itemId = $input['item_id'] ?? '';
$quantity = $input['quantity'] ?? 0;
$adminUsername = $input['admin_username'] ?? 'Unknown Admin';

// Validate inputs
if (empty($userId) || empty($itemId) || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: user_id, item_id, quantity'
    ]);
    exit;
}

try {
    // 1. Check if user has the item
    $stmt = $pdo->prepare("
        SELECT ui.quantity, si.item_name, si.price
        FROM tbl_user_inventory ui
        JOIN tbl_store_items si ON ui.item_id = si.item_id
        WHERE ui.user_id = ? AND ui.item_id = ?
    ");
    $stmt->execute([$userId, $itemId]);
    $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inventory) {
        echo json_encode([
            'success' => false,
            'error' => 'User does not have this item in inventory'
        ]);
        exit;
    }

    $currentQuantity = $inventory['quantity'];
    $itemName = $inventory['item_name'];
    $itemPrice = $inventory['price'];

    // 2. Check if user has enough quantity
    if ($quantity > $currentQuantity) {
        echo json_encode([
            'success' => false,
            'error' => "User only has {$currentQuantity}x but tried to remove {$quantity}x"
        ]);
        exit;
    }

    // 3. Remove or reduce quantity
    if ($quantity >= $currentQuantity) {
        // Delete entire row
        $stmt = $pdo->prepare("DELETE FROM tbl_user_inventory WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$userId, $itemId]);
    } else {
        // Reduce quantity
        $stmt = $pdo->prepare("
            UPDATE tbl_user_inventory 
            SET quantity = quantity - ? 
            WHERE user_id = ? AND item_id = ?
        ");
        $stmt->execute([$quantity, $userId, $itemId]);
    }

    $newQuantity = max(0, $currentQuantity - $quantity);
    $removedValue = $itemPrice * $quantity;

    // 4. Log admin action (optional - create table if needed)
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

        $stmt = $pdo->prepare("
            INSERT INTO tbl_admin_inventory_actions 
            (admin_username, action_type, user_id, item_id, item_name, quantity, item_value)
            VALUES (?, 'remove_item', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$adminUsername, $userId, $itemId, $itemName, $quantity, $removedValue]);
    } catch (PDOException $e) {
        // Log creation/insertion failed, but continue
        error_log("Failed to log admin action: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => "Successfully removed {$quantity}x {$itemName}",
        'item_name' => $itemName,
        'quantity_removed' => $quantity,
        'remaining_quantity' => $newQuantity,
        'value_removed' => $removedValue,
        'admin' => $adminUsername
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

