<?php
// api/admin/save-bug-report.php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    $db = getSQLite3Connection();
    
    $insertQuery = "
        INSERT INTO tbl_bug_reports (
            discord_message_id, discord_channel_id, discord_user_id, discord_username,
            title, description, category_id, priority_id, status_id,
            attachments, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $db->prepare($insertQuery);
    $stmt->bindValue(1, $input['discord_message_id']);
    $stmt->bindValue(2, $input['discord_channel_id']);
    $stmt->bindValue(3, $input['discord_user_id']);
    $stmt->bindValue(4, $input['discord_username']);
    $stmt->bindValue(5, $input['title']);
    $stmt->bindValue(6, $input['description']);
    $stmt->bindValue(7, $input['category_id']);
    $stmt->bindValue(8, $input['priority_id']);
    $stmt->bindValue(9, 1); // Default status: Reported
    $stmt->bindValue(10, json_encode($input['attachments']));
    $stmt->bindValue(11, $input['created_at']);
    $stmt->bindValue(12, $input['created_at']);
    
    $result = $stmt->execute();
    
    if ($result) {
        $bugId = $db->lastInsertRowID();
        
        // Log status change
        $statusQuery = "
            INSERT INTO tbl_bug_status_history (
                bug_id, new_status_id, changed_by, changed_at, change_reason
            ) VALUES (?, ?, ?, ?, ?)
        ";
        
        $statusStmt = $db->prepare($statusQuery);
        $statusStmt->bindValue(1, $bugId);
        $statusStmt->bindValue(2, 1); // Reported status
        $statusStmt->bindValue(3, 'Discord Bot');
        $statusStmt->bindValue(4, $input['created_at']);
        $statusStmt->bindValue(5, 'Bug reported via Discord');
        $statusStmt->execute();
        
        echo json_encode([
            'success' => true,
            'bug_id' => $bugId,
            'message' => 'Bug report saved successfully'
        ]);
    } else {
        throw new Exception('Failed to save bug report');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
