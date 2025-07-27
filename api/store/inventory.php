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

$user_id = $_GET['user_id'] ?? '';

if (empty($user_id)) {
    echo json_encode([
        'success' => false,
        'error' => 'User ID is required'
    ]);
    exit;
}

try {
                    // Get user's inventory with item details
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
                        $items[] = [
                    'name' => $row['item_name'],
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'image_url' => $row['image_url'],
                    'acquired_at' => $row['acquired_at']
                ];
    }
    
    if (empty($items)) {
        echo json_encode([
            'success' => false,
            'error' => 'No items found'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'items' => $items
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$db->close();
?> 