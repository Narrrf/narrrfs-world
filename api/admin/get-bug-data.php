<?php
// api/admin/get-bug-data.php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
require_once '../config/database.php';

try {
    $db = getSQLite3Connection();
    
    // Get bugs - Sort by status priority (active first) then by updated_at (most recent first)
    $bugsQuery = "
        SELECT *, 
        CASE 
            WHEN status_id IN (SELECT status_id FROM tbl_bug_statuses WHERE status_name IN ('Reported', 'Triaged', 'Assigned', 'In Progress', 'Active Development', 'Ready for Review', 'Needs Discussion', 'Deferred')) THEN 1
            WHEN status_id IN (SELECT status_id FROM tbl_bug_statuses WHERE status_name IN ('Resolved', 'Tested', 'Deployed', 'Rejected', 'Approved', 'Archived')) THEN 3
            ELSE 2
        END as status_priority
        FROM tbl_bug_reports 
        ORDER BY status_priority ASC, updated_at DESC
    ";
    $bugsStmt = $db->prepare($bugsQuery);
    $bugsResult = $bugsStmt->execute();
    $bugs = [];
    while ($row = $bugsResult->fetchArray(SQLITE3_ASSOC)) {
        $bugs[] = $row;
    }
    
    // Get categories
    $categoriesQuery = "SELECT * FROM tbl_bug_categories ORDER BY category_name";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesResult = $categoriesStmt->execute();
    $categories = [];
    while ($row = $categoriesResult->fetchArray(SQLITE3_ASSOC)) {
        $categories[] = $row;
    }
    
    // Get priorities
    $prioritiesQuery = "SELECT * FROM tbl_bug_priorities ORDER BY priority_level";
    $prioritiesStmt = $db->prepare($prioritiesQuery);
    $prioritiesResult = $prioritiesStmt->execute();
    $priorities = [];
    while ($row = $prioritiesResult->fetchArray(SQLITE3_ASSOC)) {
        $priorities[] = $row;
    }
    
    // Get statuses
    $statusesQuery = "SELECT * FROM tbl_bug_statuses ORDER BY status_id";
    $statusesStmt = $db->prepare($statusesQuery);
    $statusesResult = $statusesStmt->execute();
    $statuses = [];
    while ($row = $statusesResult->fetchArray(SQLITE3_ASSOC)) {
        $statuses[] = $row;
    }
    
    // Get team members
    $teamQuery = "SELECT DISTINCT assigned_to FROM tbl_bug_reports WHERE assigned_to IS NOT NULL";
    $teamStmt = $db->prepare($teamQuery);
    $teamResult = $teamStmt->execute();
    $teamMembers = [];
    while ($row = $teamResult->fetchArray(SQLITE3_ASSOC)) {
        $teamMembers[] = $row['assigned_to'];
    }
    
    echo json_encode([
        'success' => true,
        'bugs' => $bugs,
        'categories' => $categories,
        'priorities' => $priorities,
        'statuses' => $statuses,
        'teamMembers' => $teamMembers
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
