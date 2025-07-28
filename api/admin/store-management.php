<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// === DATABASE PERSISTENCE FUNCTION ===
function triggerDatabaseBackup() {
    try {
        $backup_script = '/var/www/html/scripts/db-backup.sh';
        
        if (!file_exists($backup_script)) {
            error_log('Backup script not found: ' . $backup_script);
            return false;
        }
        
        // Make script executable
        chmod($backup_script, 0755);
        
        // Execute backup script in background to avoid blocking the response
        exec("$backup_script > /dev/null 2>&1 &");
        
        return true;
    } catch (Exception $e) {
        error_log('Database backup failed: ' . $e->getMessage());
        return false;
    }
}

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

// Handle JSON input
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($content_type, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }
}

$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create_item':
        $name = $input['name'] ?? '';
        $description = $input['description'] ?? '';
        $price = intval($input['price'] ?? 0);
        $image_url = $input['image_url'] ?? '';
        $created_by = $input['created_by'] ?? '';
        
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
                INSERT INTO tbl_store_items (item_name, description, price, image_url, created_at, is_active) 
                VALUES (?, ?, ?, ?, datetime("now"), 1)
            ');
            
            $stmt->bindValue(1, $name, SQLITE3_TEXT);
            $stmt->bindValue(2, $description, SQLITE3_TEXT);
            $stmt->bindValue(3, $price, SQLITE3_INTEGER);
            $stmt->bindValue(4, $created_by, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            
            if ($result) {
                $item_id = $db->lastInsertRowID();
                
                // Trigger database backup after successful creation
                triggerDatabaseBackup();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Store item created successfully',
                    'item_id' => $item_id,
                    'item' => [
                        'item_id' => $item_id,
                        'item_name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'category' => $created_by,
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
        $user_id = $input['user_id'] ?? '';
        $item_name = $input['item_name'] ?? '';
        $quantity = intval($input['quantity'] ?? 1);
        $given_by = $input['given_by'] ?? '';
        
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
                    'error' => 'Item not found'
                ]);
                break;
            }
            
            // Check if user already has this item
            $stmt = $db->prepare('SELECT * FROM tbl_user_inventory WHERE user_id = ? AND item_name = ?');
            $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
            $stmt->bindValue(2, $item_name, SQLITE3_TEXT);
            $result = $stmt->execute();
            $existing = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($existing) {
                // Update quantity
                $new_quantity = $existing['quantity'] + $quantity;
                $stmt = $db->prepare('UPDATE tbl_user_inventory SET quantity = ? WHERE user_id = ? AND item_name = ?');
                $stmt->bindValue(1, $new_quantity, SQLITE3_INTEGER);
                $stmt->bindValue(2, $user_id, SQLITE3_TEXT);
                $stmt->bindValue(3, $item_name, SQLITE3_TEXT);
                $stmt->execute();
            } else {
                // Insert new inventory entry
                $stmt = $db->prepare('
                    INSERT INTO tbl_user_inventory (user_id, item_name, quantity, acquired_at) 
                    VALUES (?, ?, ?, datetime("now"))
                ');
                $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
                $stmt->bindValue(2, $item_name, SQLITE3_TEXT);
                $stmt->bindValue(3, $quantity, SQLITE3_INTEGER);
                $stmt->execute();
            }
            
            // Trigger database backup after successful item assignment
            triggerDatabaseBackup();
            
            echo json_encode([
                'success' => true,
                'message' => "Item '{$item_name}' given to user successfully",
                'quantity' => $quantity
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'delete_item':
        $item_id = intval($input['item_id'] ?? 0);
        
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
                // Trigger database backup after successful deletion
                triggerDatabaseBackup();
                
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
        $user_id = $input['user_id'] ?? $_GET['user_id'] ?? '';
        
        if (empty($user_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'User ID is required'
            ]);
            break;
        }
        
        try {
            $stmt = $db->prepare('
                SELECT ui.*, si.item_name, si.description, si.image_url as category 
                FROM tbl_user_inventory ui 
                JOIN tbl_store_items si ON ui.item_name = si.item_name 
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