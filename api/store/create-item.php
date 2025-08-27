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
$required_fields = ['item_name', 'description', 'price'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => "Missing required field: $field"
        ]);
        exit;
    }
}

try {
    $db = getSQLite3Connection();
    
    // Prepare the insert statement
    $stmt = $db->prepare("
        INSERT INTO tbl_store_items (item_name, description, price, image_url, is_active, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))
    ");
    
    $stmt->bindValue(1, $input['item_name'], SQLITE3_TEXT);
    $stmt->bindValue(2, $input['description'], SQLITE3_TEXT);
    $stmt->bindValue(3, $input['price'], SQLITE3_INTEGER);
    $stmt->bindValue(4, $input['image_url'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(5, $input['is_active'] ?? 1, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    
    if ($result) {
        $item_id = $db->lastInsertRowID();
        
        echo json_encode([
            'success' => true,
            'message' => 'Store item created successfully',
            'item_id' => $item_id,
            'item' => [
                'item_id' => $item_id,
                'item_name' => $input['item_name'],
                'description' => $input['description'],
                'price' => $input['price'],
                'image_url' => $input['image_url'] ?? '',
                'is_active' => $input['is_active'] ?? 1
            ]
        ]);
    } else {
        throw new Exception('Failed to insert store item');
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
