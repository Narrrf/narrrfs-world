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
    
    // Validate item price
    if ($item['price'] <= 0) {
        $db->exec('ROLLBACK');
        echo json_encode([
            'success' => false,
            'error' => 'Invalid item price. Please contact an administrator.'
        ]);
        exit;
    }
    
    $total_cost = $item['price'] * $quantity;
    
    // Ensure user exists in users table
    $stmt = $db->prepare('SELECT discord_id FROM tbl_users WHERE discord_id = ?');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user_exists = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$user_exists) {
        $db->exec('ROLLBACK');
        echo json_encode([
            'success' => false,
            'error' => 'User not found in system. Please contact an administrator.'
        ]);
        exit;
    }
    
    // Check user's balance - FIXED: Use SUM(score) to match balance command behavior
    $stmt = $db->prepare('SELECT SUM(score) as total FROM tbl_user_scores WHERE user_id = ?');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $result = $stmt->execute();
    $balance_result = $result->fetchArray(SQLITE3_ASSOC);
    
    $current_balance = $balance_result['total'] ?? 0;
    
    if ($current_balance < $total_cost) {
        $db->exec('ROLLBACK');
        echo json_encode([
            'success' => false,
            'error' => 'Insufficient balance. You have ' . $current_balance . ' $DSPOINC, but need ' . $total_cost . ' $DSPOINC'
        ]);
        exit;
    }
    
    // Deduct points from user - Insert negative score entry instead of UPDATE
    $stmt = $db->prepare('
        INSERT INTO tbl_user_scores (user_id, score, game, source, timestamp) 
        VALUES (?, ?, ?, ?, datetime("now"))
    ');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $stmt->bindValue(2, -$total_cost, SQLITE3_INTEGER); // Negative for deduction
    $stmt->bindValue(3, 'store_purchase', SQLITE3_TEXT);
    $stmt->bindValue(4, 'store', SQLITE3_TEXT);
    $stmt->execute();
    
    // Record the points deduction in score adjustments for audit trail
    $stmt = $db->prepare('
        INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp) 
        VALUES (?, ?, ?, ?, ?, datetime("now"))
    ');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $stmt->bindValue(2, $user_id, SQLITE3_TEXT); // User is their own admin for purchases
    $stmt->bindValue(3, -$total_cost, SQLITE3_INTEGER); // Negative amount for deduction
    $stmt->bindValue(4, 'remove', SQLITE3_TEXT); // Action type
    $stmt->bindValue(5, 'Store purchase: ' . $item['item_name'] . ' x' . $quantity, SQLITE3_TEXT);
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
    
    // Verify final balance for security - Use SUM(score)
    $stmt = $db->prepare('SELECT SUM(score) as total FROM tbl_user_scores WHERE user_id = ?');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $result = $stmt->execute();
    $final_balance_result = $result->fetchArray(SQLITE3_ASSOC);
    $final_balance = $final_balance_result['total'] ?? 0;
    
    if ($final_balance < 0) {
        $db->exec('ROLLBACK');
        echo json_encode([
            'success' => false,
            'error' => 'Purchase verification failed. Please contact an administrator.'
        ]);
        exit;
    }
    
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
        'total_price' => $total_cost,
        'new_balance' => $final_balance,
        'balance_before' => $current_balance
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