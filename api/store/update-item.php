<?php
header('Content-Type: application/json');

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON input'
    ]);
    exit;
}

// Validate required fields
if (!isset($input['item_id']) || empty($input['item_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required field: item_id'
    ]);
    exit;
}

// Check if at least one field to update is provided
$updateable_fields = ['item_name', 'description', 'price', 'image_url', 'is_active'];
$has_updates = false;
foreach ($updateable_fields as $field) {
    if (isset($input[$field])) {
        $has_updates = true;
        break;
    }
}

if (!$has_updates) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No fields to update provided'
    ]);
    exit;
}

try {
    $db = getSQLite3Connection();
    
    // First check if the item exists
    $check_stmt = $db->prepare("SELECT * FROM tbl_store_items WHERE item_id = ?");
    $check_stmt->bindValue(1, $input['item_id'], SQLITE3_INTEGER);
    $check_result = $check_stmt->execute();
    
    if (!$check_result->fetchArray()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Store item not found'
        ]);
        exit;
    }
    
    // Build the UPDATE query dynamically
    $update_parts = [];
    $params = [];
    $param_count = 1;
    
    if (isset($input['item_name'])) {
        $update_parts[] = "item_name = ?";
        $params[] = $input['item_name'];
        $param_count++;
    }
    
    if (isset($input['description'])) {
        $update_parts[] = "description = ?";
        $params[] = $input['description'];
        $param_count++;
    }
    
    if (isset($input['price'])) {
        $update_parts[] = "price = ?";
        $params[] = $input['price'];
        $param_count++;
    }
    
    if (isset($input['image_url'])) {
        $update_parts[] = "image_url = ?";
        $params[] = $input['image_url'];
        $param_count++;
    }
    
    if (isset($input['is_active'])) {
        $update_parts[] = "is_active = ?";
        $params[] = $input['is_active'];
        $param_count++;
    }
    
    // Always update the updated_at timestamp
    $update_parts[] = "updated_at = datetime('now')";
    
    $sql = "UPDATE tbl_store_items SET " . implode(', ', $update_parts) . " WHERE item_id = ?";
    $params[] = $input['item_id'];
    
    $stmt = $db->prepare($sql);
    
    // Bind all parameters
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $result = $stmt->execute();
    
    if ($result) {
        // Get the updated item
        $get_stmt = $db->prepare("SELECT * FROM tbl_store_items WHERE item_id = ?");
        $get_stmt->bindValue(1, $input['item_id'], SQLITE3_INTEGER);
        $get_result = $get_stmt->execute();
        $updated_item = $get_result->fetchArray(SQLITE3_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Store item updated successfully',
            'item' => $updated_item
        ]);
    } else {
        throw new Exception('Failed to update store item');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}
?>
