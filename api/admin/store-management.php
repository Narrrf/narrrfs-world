<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

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

try {
    $db = getSQLite3Connection();
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
    case 'get_items':
        try {
            // Get all active store items
            $stmt = $db->prepare('SELECT * FROM tbl_store_items WHERE is_active = 1 ORDER BY item_name');
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
        
    case 'create_item':
        $name = $input['name'] ?? '';
        $description = $input['description'] ?? '';
        $price = intval($input['price'] ?? 0);
        $quantity = intval($input['quantity'] ?? 0);
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
                INSERT INTO tbl_store_items (item_name, description, price, quantity, image_url, created_at, is_active) 
                VALUES (?, ?, ?, ?, ?, datetime("now"), 1)
            ');
            
            $stmt->bindValue(1, $name, SQLITE3_TEXT);
            $stmt->bindValue(2, $description, SQLITE3_TEXT);
            $stmt->bindValue(3, $price, SQLITE3_INTEGER);
            $stmt->bindValue(4, $quantity, SQLITE3_INTEGER);
            $stmt->bindValue(5, $image_url, SQLITE3_TEXT);
            
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
                        'quantity' => $quantity,
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
        
    case 'get_user_store_activity':
        $user_id = $input['user_id'] ?? $_GET['user_id'] ?? '';
        
        if (empty($user_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'User ID is required'
            ]);
            break;
        }
        
        try {
            // Get basic user info
            $stmt = $db->prepare('SELECT username, avatar_url as avatar, created_at FROM tbl_users WHERE discord_id = ?');
            $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$user) {
                echo json_encode([
                    'success' => false,
                    'error' => 'User not found'
                ]);
                break;
            }
            
            // Get user's DSPOINC balance
            $stmt = $db->prepare('SELECT SUM(score) as balance FROM tbl_user_scores WHERE user_id = ?');
            $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
            $result = $stmt->execute();
            $score_row = $result->fetchArray(SQLITE3_ASSOC);
            $balance = $score_row ? ($score_row['balance'] ?? 0) : 0;
            
            // Get user's inventory with item details
            // First, try with item_id join (new schema)
            $stmt = $db->prepare('
                SELECT ui.*, si.item_name, si.description, si.price, si.image_url, si.item_id
                FROM tbl_user_inventory ui 
                JOIN tbl_store_items si ON ui.item_id = si.item_id 
                WHERE ui.user_id = ?
                ORDER BY ui.acquired_at DESC
            ');
            $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            $inventory = [];
            $total_inventory_value = 0;
            $has_data = false;
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $has_data = true;
                $item_value = ($row['price'] ?? 0) * ($row['quantity'] ?? 0);
                $total_inventory_value += $item_value;
                $inventory[] = [
                    'item_id' => $row['item_id'] ?? 0,
                    'item_name' => $row['item_name'] ?? 'Unknown',
                    'quantity' => $row['quantity'] ?? 0,
                    'description' => $row['description'] ?? '',
                    'price' => $row['price'] ?? 0,
                    'item_value' => $item_value,
                    'acquisition_date' => $row['acquired_at'] ?? date('Y-m-d H:i:s')
                ];
            }
            
            // If no data with item_id join, try item_name join (old schema fallback)
            if (!$has_data) {
                $stmt = $db->prepare('
                    SELECT ui.*, si.item_name, si.description, si.price, si.image_url, si.item_id
                    FROM tbl_user_inventory ui 
                    JOIN tbl_store_items si ON ui.item_name = si.item_name 
                    WHERE ui.user_id = ?
                    ORDER BY ui.acquired_at DESC
                ');
                $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
                $result = $stmt->execute();
                
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $item_value = ($row['price'] ?? 0) * ($row['quantity'] ?? 0);
                    $total_inventory_value += $item_value;
                    $inventory[] = [
                        'item_id' => $row['item_id'] ?? 0,
                        'item_name' => $row['item_name'] ?? 'Unknown',
                        'quantity' => $row['quantity'] ?? 0,
                        'description' => $row['description'] ?? '',
                        'price' => $row['price'] ?? 0,
                        'item_value' => $item_value,
                        'acquisition_date' => $row['acquired_at'] ?? date('Y-m-d H:i:s')
                    ];
                }
            }
            
            // Get user's purchase history (if table exists)
            $purchase_history = [];
            $total_spent = 0;
            
            try {
                $stmt = $db->prepare('
                    SELECT ph.*, si.item_name, si.description, si.image_url
                    FROM tbl_purchase_history ph
                    JOIN tbl_store_items si ON ph.item_id = si.item_id
                    WHERE ph.user_id = ?
                    ORDER BY ph.purchased_at DESC
                    LIMIT 20
                ');
                $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
                $result = $stmt->execute();
                
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $total_spent += ($row['price_paid'] ?? 0) * ($row['quantity'] ?? 0);
                    $purchase_history[] = [
                        'purchase_id' => $row['purchase_id'] ?? 0,
                        'item_name' => $row['item_name'] ?? 'Unknown',
                        'description' => $row['description'] ?? '',
                        'price_paid' => $row['price_paid'] ?? 0,
                        'quantity' => $row['quantity'] ?? 0,
                        'purchase_date' => $row['purchased_at'] ?? date('Y-m-d H:i:s')
                    ];
                }
            } catch (Exception $e) {
                // Purchase history table might not exist, continue without it
                error_log('Purchase history error: ' . $e->getMessage());
            }
            
            // Get user's usage history
            $usage_history = [];
            
            try {
                $stmt = $db->prepare('
                    SELECT uh.*, si.description, si.image_url
                    FROM tbl_item_usage_history uh
                    LEFT JOIN tbl_store_items si ON uh.item_id = si.item_id
                    WHERE uh.user_id = ?
                    ORDER BY uh.used_at DESC
                    LIMIT 20
                ');
                $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
                $result = $stmt->execute();
                
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $usage_history[] = [
                        'usage_id' => $row['usage_id'] ?? 0,
                        'item_name' => $row['item_name'] ?? 'Unknown',
                        'description' => $row['description'] ?? '',
                        'quantity' => $row['quantity'] ?? 0,
                        'reason' => $row['reason'] ?? '',
                        'status' => $row['status'] ?? 'pending',
                        'approved_by' => $row['approved_by'] ?? null,
                        'used_at' => $row['used_at'] ?? date('Y-m-d H:i:s'),
                        'approved_at' => $row['approved_at'] ?? null
                    ];
                }
            } catch (Exception $e) {
                // Usage history table might not exist, continue without it
                error_log('Usage history error: ' . $e->getMessage());
            }
            
            // Get admin removal history
            $removal_history = [];
            
            try {
                $stmt = $db->prepare('
                    SELECT aia.*, si.description, si.image_url
                    FROM tbl_admin_inventory_actions aia
                    LEFT JOIN tbl_store_items si ON aia.item_id = si.item_id
                    WHERE aia.user_id = ? AND aia.action_type IN (\'remove_item\', \'clear_inventory\')
                    ORDER BY aia.action_timestamp DESC
                    LIMIT 20
                ');
                $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
                $result = $stmt->execute();
                
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $removal_history[] = [
                        'action_id' => $row['action_id'] ?? 0,
                        'action_type' => $row['action_type'] ?? 'remove_item',
                        'item_name' => $row['item_name'] ?? 'Unknown',
                        'description' => $row['description'] ?? '',
                        'quantity' => $row['quantity'] ?? 0,
                        'item_value' => $row['item_value'] ?? 0,
                        'admin_username' => $row['admin_username'] ?? 'Unknown Admin',
                        'action_timestamp' => $row['action_timestamp'] ?? date('Y-m-d H:i:s')
                    ];
                }
            } catch (Exception $e) {
                // Admin actions table might not exist, continue without it
                error_log('Admin removal history error: ' . $e->getMessage());
            }
            
            // Calculate statistics
            $inventory_stats = [
                'total_items' => $inventory ? array_sum(array_column($inventory, 'quantity')) : 0,
                'unique_items' => count($inventory),
                'total_value' => $total_inventory_value,
                'most_valuable_item' => $inventory ? max(array_column($inventory, 'item_value')) : 0,
                'recent_purchases' => count(array_filter($purchase_history, function($p) {
                    return strtotime($p['purchase_date']) > strtotime('-7 days');
                }))
            ];
            
            $purchase_stats = [
                'total_purchases' => count($purchase_history),
                'total_spent' => $total_spent,
                'average_purchase' => count($purchase_history) > 0 ? round($total_spent / count($purchase_history)) : 0,
                'largest_purchase' => $purchase_history ? max(array_column($purchase_history, 'price_paid')) : 0
            ];
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'basic_info' => $user,
                    'financial' => [
                        'balance' => $balance,
                        'total_spent' => $total_spent,
                        'net_worth' => $balance + $total_inventory_value
                    ],
                    'stats' => [
                        'inventory' => $inventory_stats,
                        'purchases' => $purchase_stats,
                        'usage' => [
                            'total_uses' => count($usage_history),
                            'approved_uses' => count(array_filter($usage_history, function($u) { return $u['status'] === 'approved'; })),
                            'pending_uses' => count(array_filter($usage_history, function($u) { return $u['status'] === 'pending'; })),
                            'denied_uses' => count(array_filter($usage_history, function($u) { return $u['status'] === 'denied'; }))
                        ]
                    ],
                    'inventory' => $inventory,
                    'purchase_history' => $purchase_history,
                    'usage_history' => $usage_history,
                    'removal_history' => $removal_history
                ]
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