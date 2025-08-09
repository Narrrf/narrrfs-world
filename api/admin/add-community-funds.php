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
if (!isset($input['type']) || !isset($input['description']) || !isset($input['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$type = trim($input['type']);
$description = trim($input['description']);
$amount = floatval($input['amount']);

// Validate data
if (empty($type) || empty($description) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit;
}

try {
    $pdo = new PDO("sqlite:../../db/narrrf_world.sqlite");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insert new community funds entry
    $stmt = $pdo->prepare("
        INSERT INTO tbl_community_funds (type, description, amount, date, created_at) 
        VALUES (?, ?, ?, ?, datetime('now'))
    ");
    
    $stmt->execute([$type, $description, $amount, date('Y-m-d')]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Community funds entry added successfully',
        'id' => $pdo->lastInsertId()
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
