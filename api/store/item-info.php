<?php
header('Content-Type: application/json');

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use GET.'
    ]);
    exit;
}

// Get item ID from query parameters
$item_id = $_GET['item_id'] ?? null;

if (!$item_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameter: item_id'
    ]);
    exit;
}

try {
    $db = getSQLite3Connection();
    
    // Get the specific item
    $stmt = $db->prepare("SELECT * FROM tbl_store_items WHERE item_id = ?");
    $stmt->bindValue(1, $item_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $item = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$item) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Store item not found'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'item' => $item
    ]);
    
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
