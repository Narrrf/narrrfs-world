<?php
// api/admin/delete-bug-report.php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['bug_id'])) {
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
    
    // Delete related records first (foreign key constraints)
    $deleteCommentsQuery = "DELETE FROM tbl_bug_comments WHERE bug_id = ?";
    $deleteCommentsStmt = $db->prepare($deleteCommentsQuery);
    $deleteCommentsStmt->bindValue(1, $input['bug_id']);
    $deleteCommentsStmt->execute();
    
    $deleteAssignmentsQuery = "DELETE FROM tbl_bug_assignments WHERE bug_id = ?";
    $deleteAssignmentsStmt = $db->prepare($deleteAssignmentsQuery);
    $deleteAssignmentsStmt->bindValue(1, $input['bug_id']);
    $deleteAssignmentsStmt->execute();
    
    $deleteHistoryQuery = "DELETE FROM tbl_bug_status_history WHERE bug_id = ?";
    $deleteHistoryStmt = $db->prepare($deleteHistoryQuery);
    $deleteHistoryStmt->bindValue(1, $input['bug_id']);
    $deleteHistoryStmt->execute();
    
    // Delete the main bug report
    $deleteBugQuery = "DELETE FROM tbl_bug_reports WHERE bug_id = ?";
    $deleteBugStmt = $db->prepare($deleteBugQuery);
    $deleteBugStmt->bindValue(1, $input['bug_id']);
    $result = $deleteBugStmt->execute();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Bug report deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete bug report');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
