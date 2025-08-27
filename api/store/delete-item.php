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

try {
    $db = getSQLite3Connection();
    
    // First check if the item exists
    $check_stmt = $db->prepare("SELECT * FROM tbl_store_items WHERE item_id = ?");
    $check_stmt->bindValue(1, $input['item_id'], SQLITE3_INTEGER);
    $check_result = $check_stmt->execute();
    $item = $check_result->fetchArray(SQLITE3_ASSOC);
    
    if (!$item) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Store item not found'
        ]);
        exit;
    }
    
    // Delete the item
    $delete_stmt = $db->prepare("DELETE FROM tbl_store_items WHERE item_id = ?");
    $delete_stmt->bindValue(1, $input['item_id'], SQLITE3_INTEGER);
    $result = $delete_stmt->execute();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Store item deleted successfully',
            'deleted_item' => $item
        ]);
    } else {
        throw new Exception('Failed to delete store item');
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
