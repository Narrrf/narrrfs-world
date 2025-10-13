<?php
// api/admin/bulk-update-bug-status.php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['bug_ids']) || !isset($input['status_id'])) {
        throw new Exception('Invalid input data');
    }
    
    $bugIds = $input['bug_ids'];
    $newStatusId = $input['status_id'];
    $reason = $input['reason'] ?? 'Bulk status update via admin interface';
    
    if (!is_array($bugIds) || count($bugIds) === 0) {
        throw new Exception('No bugs selected');
    }
    
    $db = getSQLite3Connection();
    $updatedCount = 0;
    
    // Begin transaction for atomic operation
    $db->exec('BEGIN TRANSACTION');
    
    try {
        foreach ($bugIds as $bugId) {
            // Get current bug status
            $checkQuery = "SELECT status_id FROM tbl_bug_reports WHERE bug_id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindValue(1, $bugId);
            $checkResult = $checkStmt->execute();
            $currentBug = $checkResult->fetchArray(SQLITE3_ASSOC);
            
            if (!$currentBug) {
                continue; // Skip if bug doesn't exist
            }
            
            $oldStatusId = $currentBug['status_id'];
            
            // Update bug status and timestamp
            $updateQuery = "
                UPDATE tbl_bug_reports 
                SET status_id = ?, 
                    updated_at = CURRENT_TIMESTAMP,
                    resolution_notes = CASE 
                        WHEN resolution_notes IS NULL OR resolution_notes = '' 
                        THEN ? 
                        ELSE resolution_notes || '\n\n' || ? 
                    END
                WHERE bug_id = ?
            ";
            
            $stmt = $db->prepare($updateQuery);
            $stmt->bindValue(1, $newStatusId);
            $stmt->bindValue(2, $reason);
            $stmt->bindValue(3, $reason);
            $stmt->bindValue(4, $bugId);
            
            if ($stmt->execute()) {
                $updatedCount++;
                
                // Log status change in history if status actually changed
                if ($oldStatusId != $newStatusId) {
                    $historyQuery = "
                        INSERT INTO tbl_bug_status_history (
                            bug_id, old_status_id, new_status_id, changed_by, changed_at, change_reason
                        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?)
                    ";
                    
                    $historyStmt = $db->prepare($historyQuery);
                    $historyStmt->bindValue(1, $bugId);
                    $historyStmt->bindValue(2, $oldStatusId);
                    $historyStmt->bindValue(3, $newStatusId);
                    $historyStmt->bindValue(4, 'Admin Interface (Bulk)');
                    $historyStmt->bindValue(5, $reason);
                    $historyStmt->execute();
                }
            }
        }
        
        // Commit transaction
        $db->exec('COMMIT');
        
        echo json_encode([
            'success' => true,
            'updated_count' => $updatedCount,
            'message' => "Successfully updated {$updatedCount} bug report(s)"
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $db->exec('ROLLBACK');
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

