<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$db_path = '/var/www/html/db/narrrf_world.sqlite';

try {
    $db = new SQLite3($db_path);
    $db->enableExceptions(true);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $type = $_POST['type'] ?? '';
        $description = $_POST['description'] ?? '';
        $link = $_POST['link'] ?? '';
        $reward = intval($_POST['reward'] ?? 0);
        $created_by = $_POST['created_by'] ?? '';
        
        // Validation
        if (empty($type) || empty($description) || $reward <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Type, description, and reward are required. Reward must be positive.'
            ]);
            break;
        }
        
        try {
            // Insert new quest
            $stmt = $db->prepare('
                INSERT INTO tbl_quests (type, description, link, reward, created_by, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, 1, datetime("now"))
            ');
            
            $stmt->bindValue(1, $type, SQLITE3_TEXT);
            $stmt->bindValue(2, $description, SQLITE3_TEXT);
            $stmt->bindValue(3, $link, SQLITE3_TEXT);
            $stmt->bindValue(4, $reward, SQLITE3_INTEGER);
            $stmt->bindValue(5, $created_by, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            
            if ($result) {
                $quest_id = $db->lastInsertRowID();
                echo json_encode([
                    'success' => true,
                    'message' => 'Quest created successfully',
                    'quest_id' => $quest_id,
                    'quest' => [
                        'quest_id' => $quest_id,
                        'type' => $type,
                        'description' => $description,
                        'link' => $link,
                        'reward' => $reward,
                        'created_by' => $created_by,
                        'is_active' => 1
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to create quest'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'delete':
        $quest_id = intval($_POST['quest_id'] ?? 0);
        
        if ($quest_id <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Valid quest ID is required'
            ]);
            break;
        }
        
        try {
            $stmt = $db->prepare('UPDATE tbl_quests SET is_active = 0 WHERE quest_id = ?');
            $stmt->bindValue(1, $quest_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($result && $db->changes() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Quest deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Quest not found or already deleted'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
        break;
}

$db->close();
?> 