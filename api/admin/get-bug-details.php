<?php
// api/admin/get-bug-details.php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $bugId = $_GET['id'] ?? null;
    
    if (!$bugId) {
        throw new Exception('Bug ID is required');
    }
    
    $db = getSQLite3Connection();
    
    // Get bug details
    $bugQuery = "SELECT * FROM tbl_bug_reports WHERE bug_id = ?";
    $bugStmt = $db->prepare($bugQuery);
    $bugStmt->bindValue(1, $bugId);
    $bugResult = $bugStmt->execute();
    $bug = $bugResult->fetchArray(SQLITE3_ASSOC);
    
    if (!$bug) {
        throw new Exception('Bug not found');
    }
    
    // Get comments
    $commentsQuery = "SELECT * FROM tbl_bug_comments WHERE bug_id = ? ORDER BY created_at ASC";
    $commentsStmt = $db->prepare($commentsQuery);
    $commentsStmt->bindValue(1, $bugId);
    $commentsResult = $commentsStmt->execute();
    $comments = [];
    while ($row = $commentsResult->fetchArray(SQLITE3_ASSOC)) {
        $comments[] = $row;
    }
    
    // Get timeline
    $timelineQuery = "
        SELECT h.*, 
               os.status_name as old_status_name,
               ns.status_name as new_status_name
        FROM tbl_bug_status_history h
        LEFT JOIN tbl_bug_statuses os ON h.old_status_id = os.status_id
        LEFT JOIN tbl_bug_statuses ns ON h.new_status_id = ns.status_id
        WHERE h.bug_id = ? 
        ORDER BY h.changed_at ASC
    ";
    $timelineStmt = $db->prepare($timelineQuery);
    $timelineStmt->bindValue(1, $bugId);
    $timelineResult = $timelineStmt->execute();
    $timeline = [];
    while ($row = $timelineResult->fetchArray(SQLITE3_ASSOC)) {
        $timeline[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'bug' => $bug,
        'comments' => $comments,
        'timeline' => $timeline
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
