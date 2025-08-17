<?php
header('Content-Type: application/json');

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

try {
    $db = getSQLite3Connection();
    
    // Get all active items
    $stmt = $db->prepare("SELECT * FROM tbl_store_items WHERE is_active = 1 ORDER BY item_name");
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
        'error' => 'Failed to get items: ' . $e->getMessage()
    ]);
}

$db->close();
?> 