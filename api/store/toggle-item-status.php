<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['item_id'])) {
    echo json_encode(['success' => false, 'error' => 'Item ID is required']);
    exit();
}

$itemId = (int)$input['item_id'];

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

    // First, get current item status
    $stmt = $db->prepare("SELECT item_id, item_name, is_active FROM tbl_store_items WHERE item_id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Item not found']);
        exit();
    }

    // Toggle the status (1 becomes 0, 0 becomes 1)
    $newStatus = $item['is_active'] == 1 ? 0 : 1;
    
    // Update the item status
    $updateStmt = $db->prepare("UPDATE tbl_store_items SET is_active = ?, updated_at = datetime('now') WHERE item_id = ?");
    $updateStmt->execute([$newStatus, $itemId]);

    // Get updated item data
    $stmt = $db->prepare("SELECT * FROM tbl_store_items WHERE item_id = ?");
    $stmt->execute([$itemId]);
    $updatedItem = $stmt->fetch(PDO::FETCH_ASSOC);

    // Log the status change
    error_log("Store item status toggled: Item ID {$itemId} ({$item['item_name']}) status changed from " . ($item['is_active'] == 1 ? 'active' : 'inactive') . " to " . ($newStatus == 1 ? 'active' : 'inactive'));

    echo json_encode([
        'success' => true,
        'message' => 'Item status toggled successfully',
        'item' => $updatedItem
    ]);

} catch (Exception $e) {
    error_log("Toggle item status error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
