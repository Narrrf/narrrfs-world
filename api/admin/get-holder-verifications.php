<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$dbPath = '/data/narrrf_world.sqlite';

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $collection = $input['collection'] ?? '';
    $status = $input['status'] ?? '';
    $limit = $input['limit'] ?? 50;

    // Connect to database
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Build query with filters
    $query = "SELECT * FROM tbl_holder_verifications WHERE 1=1";
    $params = [];

    if (!empty($collection)) {
        $query .= " AND collection = ?";
        $params[] = $collection;
    }

    if ($status !== '') {
        $query .= " AND role_granted = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY verified_at DESC LIMIT ?";
    $params[] = $limit;

    // Execute query
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'verifications' => $verifications,
        'count' => count($verifications)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?> 