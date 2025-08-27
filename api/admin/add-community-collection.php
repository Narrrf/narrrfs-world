<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['collection_name']) || !isset($input['collection_symbol'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$collection_name = trim($input['collection_name']);
$collection_symbol = trim($input['collection_symbol']);
$total_supply = isset($input['total_supply']) ? intval($input['total_supply']) : 1;
$floor_price_sol = isset($input['floor_price_sol']) ? floatval($input['floor_price_sol']) : 0;
$description = isset($input['description']) ? trim($input['description']) : '';

// Validate data
if (empty($collection_name) || empty($collection_symbol)) {
    echo json_encode(['success' => false, 'message' => 'Collection name and symbol are required']);
    exit;
}

if ($total_supply <= 0) {
    echo json_encode(['success' => false, 'message' => 'Total supply must be greater than 0']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    // Check if collection already exists
    $stmt = $pdo->prepare("SELECT id FROM tbl_community_collections WHERE collection_name = ? OR collection_symbol = ?");
    $stmt->execute([$collection_name, $collection_symbol]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Collection with this name or symbol already exists']);
        exit;
    }
    
    // Insert new collection
    $stmt = $pdo->prepare("
        INSERT INTO tbl_community_collections (collection_name, collection_symbol, total_supply, floor_price_sol, description, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))
    ");
    
    $stmt->execute([$collection_name, $collection_symbol, $total_supply, $floor_price_sol, $description]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Collection added successfully',
        'id' => $pdo->lastInsertId(),
        'collection' => [
            'id' => $pdo->lastInsertId(),
            'collection_name' => $collection_name,
            'collection_symbol' => $collection_symbol,
            'total_supply' => $total_supply,
            'floor_price_sol' => $floor_price_sol,
            'description' => $description
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
