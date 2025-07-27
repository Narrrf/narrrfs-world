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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
                    case 'create_item':
                    $name = $_POST['name'] ?? '';
                    $description = $_POST['description'] ?? '';
                    $price = intval($_POST['price'] ?? 0);
                    $image_url = $_POST['image_url'] ?? '';
                    $created_by = $_POST['created_by'] ?? '';
                    
                    // Validation
                    if (empty($name) || empty($description) || $price <= 0) {
                        echo json_encode([
                            'success' => false,
                            'error' => 'Name, description, and price are required. Price must be positive.'
                        ]);
                        break;
                    }
                    
                    try {
                        // Insert new store item
                        $stmt = $db->prepare('
                            INSERT INTO tbl_store_items (item_name, description, price, image_url, is_active, created_at) 
                            VALUES (?, ?, ?, ?, 1, datetime("now"))
                        ');
                        
                        $stmt->bindValue(1, $name, SQLITE3_TEXT);
                        $stmt->bindValue(2, $description, SQLITE3_TEXT);
                        $stmt->bindValue(3, $price, SQLITE3_INTEGER);
                        $stmt->bindValue(4, $image_url, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            
            if ($result) {
                $item_id = $db->lastInsertRowID();
                echo json_encode([
                    'success' => true,
                    'message' => 'Store item created successfully',
                    'item_id' => $item_id,
                                                'item' => [
                                'item_id' => $item_id,
                                'item_name' => $name,
                                'description' => $description,
                                'price' => $price,
                                'image_url' => $image_url,
                                'is_active' => 1
                            ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to create store item'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'give_item':
        $user_id = $_POST['user_id'] ?? '';
        $item_name = $_POST['item_name'] ?? '';
        $quantity = intval($_POST['quantity'] ?? 1);
        $given_by = $_POST['given_by'] ?? '';
        
        // Validation
        if (empty($user_id) || empty($item_name) || $quantity <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'User ID, item name, and quantity are required. Quantity must be positive.'
            ]);
            break;
        }
        
        try {
                                    // First, find the item
                        $stmt = $db->prepare('SELECT * FROM tbl_store_items WHERE item_name = ? AND is_active = 1');
            $stmt->bindValue(1, $item_name, SQLITE3_TEXT);
            $result = $stmt->execute();
            $item = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$item) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Item not found or not active'
                ]);
                break;
            }
            
            // Check if user already has this item
            $stmt = $db->prepare('SELECT * FROM tbl_user_inventory WHERE user_id = ? AND item_id = ?');
            $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
            $stmt->bindValue(2, $item['item_id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $existing = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($existing) {
                // Update existing inventory
                $stmt = $db->prepare('UPDATE tbl_user_inventory SET quantity = quantity + ? WHERE user_id = ? AND item_id = ?');
                $stmt->bindValue(1, $quantity, SQLITE3_INTEGER);
                $stmt->bindValue(2, $user_id, SQLITE3_TEXT);
                $stmt->bindValue(3, $item['item_id'], SQLITE3_INTEGER);
            } else {
                // Insert new inventory entry
                $stmt = $db->prepare('INSERT INTO tbl_user_inventory (user_id, item_id, quantity, acquired_at) VALUES (?, ?, ?, datetime("now"))');
                $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
                $stmt->bindValue(2, $item['item_id'], SQLITE3_INTEGER);
                $stmt->bindValue(3, $quantity, SQLITE3_INTEGER);
            }
            
            $result = $stmt->execute();
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => "Successfully gave {$quantity}x {$item_name} to user",
                    'item' => $item,
                    'quantity' => $quantity
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to give item to user'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'delete_item':
        $item_id = intval($_POST['item_id'] ?? 0);
        
        if ($item_id <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Valid item ID is required'
            ]);
            break;
        }
        
        try {
            $stmt = $db->prepare('UPDATE tbl_store_items SET is_active = 0 WHERE item_id = ?');
            $stmt->bindValue(1, $item_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($result && $db->changes() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Store item deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Item not found or already deleted'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'get_user_inventory':
        $user_id = $_GET['user_id'] ?? '';
        
        if (empty($user_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'User ID is required'
            ]);
            break;
        }
        
        try {
                                    $stmt = $db->prepare('
                            SELECT ui.*, si.item_name, si.description, si.image_url 
                            FROM tbl_user_inventory ui 
                            JOIN tbl_store_items si ON ui.item_id = si.item_id 
                            WHERE ui.user_id = ? AND si.is_active = 1
                            ORDER BY si.item_name
                        ');
            $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            $items = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $items[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'items' => $items
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
        break;
}

$db->close();
?> 