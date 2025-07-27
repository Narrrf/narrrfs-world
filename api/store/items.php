<?php
header('Content-Type: application/json');

// Connect to database
$dbPath = '/var/www/html/db/narrrf_world.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    // Get all active items
    $stmt = $db->prepare("SELECT * FROM tbl_store_items WHERE is_active = 1 ORDER BY item_name");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to get items']);
} 