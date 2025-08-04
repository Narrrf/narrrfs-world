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

// Handle JSON input
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($content_type, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }
}

$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $type = $input['type'] ?? '';
        $description = $input['description'] ?? '';
        $link = $input['link'] ?? '';
        $reward = intval($input['reward'] ?? 0);
        $created_by = $input['created_by'] ?? '';
        
        // Role granting options
        $grant_role = $input['grant_role'] ?? false;
        $role_id = $input['role_id'] ?? null;
        
        // Cheese quest specific fields
        $cheese_config = null;
        if ($type === 'cheese_hunt') {
            $cheese_config = [
                'movement_pattern' => $input['movement_pattern'] ?? 'random',
                'movement_speed' => $input['movement_speed'] ?? 'normal',
                'hidden_areas' => $input['hidden_areas'] ?? true,
                'cheese_count' => $input['cheese_count'] ?? 3,
                'discord_ticket' => $input['discord_ticket'] ?? true,
                'winner_message' => $input['winner_message'] ?? '🎯 Congratulations! You found the cheese!',
                'screenshot_required' => $input['screenshot_required'] ?? true
            ];
        }
        
        // Validation
        if (empty($type) || empty($description) || $reward <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Type, description, and reward are required. Reward must be positive.'
            ]);
            break;
        }
        
        try {
            // Insert new quest with cheese configuration and role_id
            $stmt = $db->prepare('
                INSERT INTO tbl_quests (type, description, link, reward, created_by, is_active, created_at, cheese_config, role_id) 
                VALUES (?, ?, ?, ?, ?, 1, datetime("now"), ?, ?)
            ');
            
            $stmt->bindValue(1, $type, SQLITE3_TEXT);
            $stmt->bindValue(2, $description, SQLITE3_TEXT);
            $stmt->bindValue(3, $link, SQLITE3_TEXT);
            $stmt->bindValue(4, $reward, SQLITE3_INTEGER);
            $stmt->bindValue(5, $created_by, SQLITE3_TEXT);
            $stmt->bindValue(6, $cheese_config ? json_encode($cheese_config) : null, SQLITE3_TEXT);
            $stmt->bindValue(7, $grant_role ? $role_id : null, SQLITE3_TEXT);
            
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
                        'is_active' => 1,
                        'cheese_config' => $cheese_config,
                        'role_id' => $grant_role ? $role_id : null
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
        $quest_id = intval($input['quest_id'] ?? 0);
        
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