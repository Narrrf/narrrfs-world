<?php
// api/admin/update-bug-report.php
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
    
    // Check if status is changing to "Resolved" (status_id = 5)
    $isResolvedStatus = ($input['status_id'] == 5);
    $wasResolved = ($existingBug['status_id'] == 5);
    $shouldSetResolvedAt = $isResolvedStatus && !$wasResolved && empty($existingBug['resolved_at']);
    
    // Update bug report
    $updateQuery = "
        UPDATE tbl_bug_reports SET
            title = ?,
            description = ?,
            category_id = ?,
            priority_id = ?,
            status_id = ?,
            assigned_to = ?,
            resolution_notes = ?,
            resolved_at = CASE 
                WHEN ? = 5 AND (resolved_at IS NULL OR resolved_at = '') THEN CURRENT_TIMESTAMP
                ELSE resolved_at
            END,
            updated_at = CURRENT_TIMESTAMP
        WHERE bug_id = ?
    ";
    
    $stmt = $db->prepare($updateQuery);
    $stmt->bindValue(1, $input['title']);
    $stmt->bindValue(2, $input['description']);
    $stmt->bindValue(3, $input['category_id']);
    $stmt->bindValue(4, $input['priority_id']);
    $stmt->bindValue(5, $input['status_id']);
    $stmt->bindValue(6, $input['assigned_to'] ?: null);
    $stmt->bindValue(7, $input['resolution_notes'] ?: null);
    $stmt->bindValue(8, $input['status_id']); // For CASE condition
    $stmt->bindValue(9, $input['bug_id']);
    
    $result = $stmt->execute();
    
    if ($result) {
        // Log status change if status changed
        if ($existingBug['status_id'] != $input['status_id']) {
            $statusQuery = "
                INSERT INTO tbl_bug_status_history (
                    bug_id, old_status_id, new_status_id, changed_by, changed_at, change_reason
                ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?)
            ";
            
            $statusStmt = $db->prepare($statusQuery);
            $statusStmt->bindValue(1, $input['bug_id']);
            $statusStmt->bindValue(2, $existingBug['status_id']);
            $statusStmt->bindValue(3, $input['status_id']);
            $statusStmt->bindValue(4, 'Admin Interface');
            $statusStmt->bindValue(5, 'Bug updated via admin interface');
            $statusStmt->execute();
        }
        
        // Log assignment change if assigned_to changed
        if ($existingBug['assigned_to'] != $input['assigned_to']) {
            $assignmentQuery = "
                INSERT INTO tbl_bug_assignments (
                    bug_id, assigned_to, assigned_by, assigned_at, assignment_notes
                ) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)
            ";
            
            $assignmentStmt = $db->prepare($assignmentQuery);
            $assignmentStmt->bindValue(1, $input['bug_id']);
            $assignmentStmt->bindValue(2, $input['assigned_to'] ?: 'Unassigned');
            $assignmentStmt->bindValue(3, 'Admin Interface');
            $assignmentStmt->bindValue(4, 'Assignment updated via admin interface');
            $assignmentStmt->execute();
        }
        
        // Get updated bug to return resolved_at if it was set
        $updatedQuery = "SELECT resolved_at FROM tbl_bug_reports WHERE bug_id = ?";
        $updatedStmt = $db->prepare($updatedQuery);
        $updatedStmt->bindValue(1, $input['bug_id']);
        $updatedResult = $updatedStmt->execute();
        $updatedBug = $updatedResult->fetchArray(SQLITE3_ASSOC);
        
        $message = 'Bug report updated successfully';
        if ($shouldSetResolvedAt && $updatedBug['resolved_at']) {
            $message .= '. Bug marked as resolved - Discord bot will add 🟢 reaction.';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'resolved_at' => $updatedBug['resolved_at'] ?? null
        ]);
    } else {
        throw new Exception('Failed to update bug report');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
