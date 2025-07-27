<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$db_path = '/var/www/html/db/narrrf_world.sqlite';

try {
    $db = new SQLite3($db_path);
    $db->enableExceptions(true);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? '';
$item_id = $input['item_id'] ?? '';
$quantity = intval($input['quantity'] ?? 1);

if (empty($user_id) || empty($item_id) || $quantity <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'User ID, item ID, and quantity are required. Quantity must be positive.'
    ]);
    exit;
}

try {
    // Start transaction
    $db->exec('BEGIN TRANSACTION');
    
    // Get item details
    $stmt = $db->prepare('SELECT * FROM tbl_store_items WHERE item_id = ? AND is_active = 1');
    $stmt->bindValue(1, $item_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $item = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$item) {
        $db->exec('ROLLBACK');
        echo json_encode([
            'success' => false,
            'error' => 'Item not found or not available'
        ]);
        exit;
    }
    
    $total_cost = $item['price'] * $quantity;
    
    // Check user's balance
    $stmt = $db->prepare('SELECT score FROM tbl_user_scores WHERE user_id = ?');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user_score = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$user_score || $user_score['score'] < $total_cost) {
        $db->exec('ROLLBACK');
        echo json_encode([
            'success' => false,
            'error' => 'Insufficient balance'
        ]);
        exit;
    }
    
    // Deduct points from user
    $stmt = $db->prepare('UPDATE tbl_user_scores SET score = score - ? WHERE user_id = ?');
    $stmt->bindValue(1, $total_cost, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user_id, SQLITE3_TEXT);
    $stmt->execute();
    
    // Add item to user's inventory
    $stmt = $db->prepare('
        INSERT INTO tbl_user_inventory (user_id, item_id, quantity, acquired_at) 
        VALUES (?, ?, ?, datetime("now"))
        ON CONFLICT(user_id, item_id) DO UPDATE SET quantity = quantity + ?
    ');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $stmt->bindValue(2, $item_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, $quantity, SQLITE3_INTEGER);
    $stmt->bindValue(4, $quantity, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Record purchase in history
    $stmt = $db->prepare('
        INSERT INTO tbl_purchase_history (user_id, item_id, price_paid, quantity, purchased_at) 
        VALUES (?, ?, ?, ?, datetime("now"))
    ');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $stmt->bindValue(2, $item_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, $total_cost, SQLITE3_INTEGER);
    $stmt->bindValue(4, $quantity, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Commit transaction
    $db->exec('COMMIT');
    
    echo json_encode([
        'success' => true,
        'message' => 'Purchase successful',
        'item' => [
            'name' => $item['item_name'],
            'description' => $item['description']
        ],
        'quantity' => $quantity,
        'total_price' => $total_cost
    ]);
    
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$db->close();
?> 