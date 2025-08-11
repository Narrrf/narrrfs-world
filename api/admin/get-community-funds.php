<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Get all community funds entries ordered by date (newest first)
    $stmt = $pdo->prepare("
        SELECT id, type, description, amount, date, created_at 
        FROM tbl_community_funds 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates for display
    foreach ($entries as &$entry) {
        $entry['date'] = date('M j, Y', strtotime($entry['created_at']));
    }
    
    echo json_encode([
        'success' => true,
        'entries' => $entries
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
