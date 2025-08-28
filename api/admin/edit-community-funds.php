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
if (!isset($input['id']) || !isset($input['type']) || !isset($input['description']) || !isset($input['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$id = intval($input['id']);
$type = trim($input['type']);
$description = trim($input['description']);
$amount = floatval($input['amount']);

// Validate data
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid entry ID']);
    exit;
}

if (empty($type) || empty($description) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit;
}

// Validate type
if (!in_array(strtolower($type), ['expense', 'income'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid type. Must be "expense" or "income"']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    // Check if entry exists
    $checkStmt = $pdo->prepare("SELECT id FROM tbl_community_funds WHERE id = ?");
    $checkStmt->execute([$id]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Entry not found']);
        exit;
    }
    
    // Update the entry
    $stmt = $pdo->prepare("
        UPDATE tbl_community_funds 
        SET type = ?, description = ?, amount = ?, date = ?, created_at = datetime('now')
        WHERE id = ?
    ");
    
    $stmt->execute([$type, $description, $amount, date('Y-m-d'), $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Community funds entry updated successfully',
        'id' => $id
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
