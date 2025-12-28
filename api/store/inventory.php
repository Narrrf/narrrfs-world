<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

try {
    $db = getSQLite3Connection();
    $db->enableExceptions(true);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Local development fallback
$isLocalDevelopment = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
                      strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID for local testing

$user_id = $_GET['user_id'] ?? '';

// For local development, use Narrrf's account if no user_id provided
if (!$user_id && $isLocalDevelopment) {
    $user_id = $LOCAL_TEST_DISCORD_ID;
}

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
                    WHERE ui.user_id = ? 
                      AND si.is_active = 1
                      AND ui.quantity > 0
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