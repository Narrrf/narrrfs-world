<?php
// api/admin/add-bug-comment.php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['bug_id']) || !isset($input['comment_text'])) {
        throw new Exception('Invalid input data');
    }
    
    $db = getSQLite3Connection();
    
    // Check if bug exists
    $checkQuery = "SELECT * FROM tbl_bug_reports WHERE bug_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindValue(1, $input['bug_id']);
    $checkResult = $checkStmt->execute();
    $existingBug = $checkResult->fetchArray(SQLITE3_ASSOC);
    
    if (!$existingBug) {
        throw new Exception('Bug not found');
    }
    
    // Insert comment
    $insertQuery = "
        INSERT INTO tbl_bug_comments (
            bug_id, comment_text, comment_type, created_at
        ) VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ";
    
    $stmt = $db->prepare($insertQuery);
    $stmt->bindValue(1, $input['bug_id']);
    $stmt->bindValue(2, $input['comment_text']);
    $stmt->bindValue(3, $input['comment_type'] ?: 'admin_comment');
    
    $result = $stmt->execute();
    
    if ($result) {
        $commentId = $db->lastInsertRowID();
        
        // 🔄 CRITICAL FIX: Update bug's updated_at timestamp when comment is added
        $updateQuery = "UPDATE tbl_bug_reports SET updated_at = CURRENT_TIMESTAMP WHERE bug_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindValue(1, $input['bug_id']);
        $updateStmt->execute();
        
        echo json_encode([
            'success' => true,
            'comment_id' => $commentId,
            'message' => 'Comment added successfully'
        ]);
    } else {
        throw new Exception('Failed to add comment');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
