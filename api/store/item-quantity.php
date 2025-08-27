<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get item ID from query parameters
if (!isset($_GET['item_id'])) {
    echo json_encode(['success' => false, 'error' => 'Item ID is required']);
    exit();
}

$itemId = (int)$_GET['item_id'];

try {
    // Connect to database using environment-aware path
    if (file_exists('/var/www/html/db/narrrf_world.sqlite')) {
        // Production environment (Render)
        $db = new PDO('sqlite:/var/www/html/db/narrrf_world.sqlite');
    } else {
        // Local environment (XAMPP)
        $db = new PDO('sqlite:' . __DIR__ . '/../db/narrrf_world.sqlite');
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get item details including quantity and status
    $stmt = $db->prepare("SELECT * FROM tbl_store_items WHERE item_id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Item not found']);
        exit();
    }

    // Format quantity display
    $quantityDisplay = $item['quantity'] > 0 ? $item['quantity'] : 'Unlimited';
    $statusText = $item['is_active'] == 1 ? 'Active' : 'Inactive';

    echo json_encode([
        'success' => true,
        'message' => 'Item quantity and status retrieved successfully',
        'item' => [
            'item_id' => $item['item_id'],
            'item_name' => $item['item_name'],
            'description' => $item['description'],
            'price' => (int)$item['price'],
            'quantity' => $item['quantity'] > 0 ? (int)$item['quantity'] : 'Unlimited',
            'quantity_raw' => (int)$item['quantity'],
            'is_active' => (int)$item['is_active'],
            'status_text' => $statusText,
            'image_url' => $item['image_url'],
            'created_at' => $item['created_at'],
            'updated_at' => $item['updated_at']
        ],
        'display_info' => [
            'quantity_display' => $quantityDisplay,
            'status_display' => $statusText,
            'price_formatted' => number_format($item['price'])
        ]
    ]);

} catch (Exception $e) {
    error_log("Get item quantity error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
