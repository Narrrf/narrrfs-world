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
if (!isset($input['collection_id']) || !isset($input['token_name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$collection_id = intval($input['collection_id']);
$token_name = trim($input['token_name']);
$token_symbol = isset($input['token_symbol']) ? trim($input['token_symbol']) : '';
$purchase_price_sol = isset($input['purchase_price_sol']) ? floatval($input['purchase_price_sol']) : 0;
$current_value_sol = isset($input['current_value_sol']) ? floatval($input['current_value_sol']) : 0;
$purchase_date = isset($input['purchase_date']) ? trim($input['purchase_date']) : date('Y-m-d');
$notes = isset($input['notes']) ? trim($input['notes']) : '';

// Validate data
if ($collection_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid collection ID']);
    exit;
}

if (empty($token_name)) {
    echo json_encode(['success' => false, 'message' => 'Token name is required']);
    exit;
}

if ($purchase_price_sol < 0) {
    echo json_encode(['success' => false, 'message' => 'Purchase price cannot be negative']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    // Verify collection exists
    $stmt = $pdo->prepare("SELECT id FROM tbl_community_collections WHERE id = ?");
    $stmt->execute([$collection_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Collection not found']);
        exit;
    }
    
    // Insert new NFT
    $stmt = $pdo->prepare("
        INSERT INTO tbl_community_nfts (collection_id, token_name, token_symbol, purchase_price_sol, current_value_sol, purchase_date, notes, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
    ");
    
    $stmt->execute([$collection_id, $token_name, $token_symbol, $purchase_price_sol, $current_value_sol, $purchase_date, $notes]);
    
    echo json_encode([
        'success' => true,
        'message' => 'NFT added successfully',
        'id' => $pdo->lastInsertId(),
        'nft' => [
            'id' => $pdo->lastInsertId(),
            'collection_id' => $collection_id,
            'token_name' => $token_name,
            'token_symbol' => $token_symbol,
            'purchase_price_sol' => $purchase_price_sol,
            'current_value_sol' => $current_value_sol,
            'purchase_date' => $purchase_date,
            'notes' => $notes
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
