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
if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing entry ID']);
    exit;
}

$id = intval($input['id']);

// Validate ID
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid entry ID']);
    exit;
}

try {
    $pdo = new PDO("sqlite:../../db/narrrf_world.sqlite");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if entry exists
    $checkStmt = $pdo->prepare("SELECT id FROM tbl_community_funds WHERE id = ?");
    $checkStmt->execute([$id]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Entry not found']);
        exit;
    }
    
    // Delete the entry
    $stmt = $pdo->prepare("DELETE FROM tbl_community_funds WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Community funds entry deleted successfully'
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
