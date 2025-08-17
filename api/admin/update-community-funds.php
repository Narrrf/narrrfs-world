<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../config/database.php';

try {
    $pdo = getPDOConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        // Validate required fields
        $id = $input['id'] ?? null;
        $type = $input['type'] ?? null;
        $description = $input['description'] ?? null;
        $amount = $input['amount'] ?? null;
        
        if (!$id || !$type || !$description || $amount === null) {
            throw new Exception('Missing required fields: id, type, description, amount');
        }
        
        // Validate type
        if (!in_array($type, ['income', 'expense'])) {
            throw new Exception('Invalid type. Must be "income" or "expense"');
        }
        
        // Validate amount
        if (!is_numeric($amount) || $amount <= 0) {
            throw new Exception('Amount must be a positive number');
        }
        
        // Check if entry exists
        $checkStmt = $pdo->prepare("SELECT id FROM tbl_community_funds WHERE id = ?");
        $checkStmt->execute([$id]);
        
        if (!$checkStmt->fetch()) {
            throw new Exception('Community funds entry not found');
        }
        
        // Update the entry
        $updateStmt = $pdo->prepare("
            UPDATE tbl_community_funds 
            SET type = ?, description = ?, amount = ?, created_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $result = $updateStmt->execute([$type, $description, $amount, $id]);
        
        if ($result) {
            // Get updated entry for response
            $getStmt = $pdo->prepare("
                SELECT id, type, description, amount, date, created_at 
                FROM tbl_community_funds 
                WHERE id = ?
            ");
            $getStmt->execute([$id]);
            $updatedEntry = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            // Format date for display
            $updatedEntry['date'] = date('M j, Y', strtotime($updatedEntry['created_at']));
            
            echo json_encode([
                'success' => true,
                'message' => 'Community funds entry updated successfully',
                'entry' => $updatedEntry
            ]);
        } else {
            throw new Exception('Failed to update entry');
        }
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
