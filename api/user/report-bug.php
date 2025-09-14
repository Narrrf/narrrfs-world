<?php
// api/user/report-bug.php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['title']) || !isset($input['description'])) {
        throw new Exception('Title and description are required');
    }
    
    // Get user session info (allow anonymous reports)
    session_start();
    $discord_id = $_SESSION['discord_id'] ?? null;
    $discord_username = $_SESSION['discord_username'] ?? 'Anonymous User';
    
    // If no session, use anonymous user
    if (!$discord_id) {
        $discord_id = 'anonymous_' . time() . '_' . rand(1000, 9999);
        $discord_username = 'Anonymous User';
    }
    
    $db = getSQLite3Connection();
    
    // Get default category and priority (UI/UX Issues category, Medium priority)
    $categoryQuery = "SELECT category_id FROM tbl_bug_categories WHERE category_name = 'UI/UX Issues' LIMIT 1";
    $categoryResult = $db->query($categoryQuery);
    $category_id = $categoryResult ? $categoryResult->fetchArray(SQLITE3_ASSOC)['category_id'] : 4; // Default to UI/UX Issues
    
    $priorityQuery = "SELECT priority_id FROM tbl_bug_priorities WHERE priority_name = 'Medium' LIMIT 1";
    $priorityResult = $db->query($priorityQuery);
    $priority_id = $priorityResult ? $priorityResult->fetchArray(SQLITE3_ASSOC)['priority_id'] : 3; // Default to Medium
    
    // Adjust priority based on severity if provided
    if (isset($input['severity'])) {
        switch ($input['severity']) {
            case 'low':
                $priority_id = 4; // Low priority
                break;
            case 'high':
                $priority_id = 2; // High priority
                break;
            case 'critical':
                $priority_id = 1; // Critical priority
                break;
            default:
                $priority_id = 3; // Medium priority
        }
    }
    
    $insertQuery = "
        INSERT INTO tbl_bug_reports (
            discord_message_id, discord_channel_id, discord_user_id, discord_username,
            title, description, category_id, priority_id, status_id,
            attachments, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $db->prepare($insertQuery);
    $stmt->bindValue(1, 'web_' . time() . '_' . rand(1000, 9999)); // Unique web message ID
    $stmt->bindValue(2, 'web_interface'); // Web interface channel ID
    $stmt->bindValue(3, $discord_id);
    $stmt->bindValue(4, $discord_username);
    $stmt->bindValue(5, $input['title']);
    $stmt->bindValue(6, $input['description']);
    $stmt->bindValue(7, $category_id);
    $stmt->bindValue(8, $priority_id);
    $stmt->bindValue(9, 1); // Default status: Reported
    $stmt->bindValue(10, json_encode($input['attachments'] ?? []));
    $stmt->bindValue(11, date('Y-m-d H:i:s'));
    $stmt->bindValue(12, date('Y-m-d H:i:s'));
    
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
        $statusStmt->bindValue(3, 'Web Interface');
        $statusStmt->bindValue(4, date('Y-m-d H:i:s'));
        $statusStmt->bindValue(5, 'Bug reported via web interface');
        $statusStmt->execute();
        
        echo json_encode([
            'success' => true,
            'bug_id' => $bugId,
            'message' => 'Bug report submitted successfully! Thank you for helping improve Narrrfs World! 🧀'
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
