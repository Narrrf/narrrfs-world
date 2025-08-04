<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database path
$dbPath = __DIR__ . '/../db/narrrf_world.sqlite';

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_cheese_clicks'");
    $tableExists = $stmt->fetch() !== false;

    if (!$tableExists) {
        echo json_encode([
            'error' => 'Table tbl_cheese_clicks does not exist',
            'tables' => $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN)
        ]);
        exit;
    }

    // Get table structure
    $stmt = $pdo->query("PRAGMA table_info(tbl_cheese_clicks)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tbl_cheese_clicks");
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get sample data
    $stmt = $pdo->query("SELECT * FROM tbl_cheese_clicks LIMIT 5");
    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'table_exists' => true,
        'columns' => $columns,
        'total_records' => $totalCount,
        'sample_data' => $sampleData,
        'message' => 'Cheese stats API is working correctly'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
}
?>