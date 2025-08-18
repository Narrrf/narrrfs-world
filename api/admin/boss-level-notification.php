<?php
/**
 * Boss Level Notification API
 * Handles notifications when players reach boss levels in Space Invaders
 * 
 * Endpoints:
 * - POST: Create a new boss level notification
 * - GET: Retrieve all boss level notifications
 * - PUT: Mark notification as reviewed/claimed
 * - DELETE: Remove old notifications
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once 'auth.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Create boss_level_notifications table if it doesn't exist
$createTableSQL = "
CREATE TABLE IF NOT EXISTS boss_level_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    player_username VARCHAR(255) NOT NULL,
    player_id INTEGER,
    game_type VARCHAR(50) DEFAULT 'space_invaders',
    wave_number INTEGER NOT NULL,
    boss_level INTEGER NOT NULL,
    boss_type VARCHAR(100),
    boss_name VARCHAR(100),
    score INTEGER,
    dspoinc_earned INTEGER,
    notification_type VARCHAR(50) DEFAULT 'boss_level_reached',
    status VARCHAR(50) DEFAULT 'pending',
    admin_reviewed_by VARCHAR(255),
    admin_reviewed_at DATETIME,
    special_reward_given BOOLEAN DEFAULT 0,
    reward_description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

try {
    $conn->exec($createTableSQL);
} catch (PDOException $e) {
    // Table might already exist, continue
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        handleCreateNotification($conn);
        break;
    case 'GET':
        handleGetNotifications($conn);
        break;
    case 'PUT':
        handleUpdateNotification($conn);
        break;
    case 'DELETE':
        handleDeleteNotification($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

function handleCreateNotification($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        return;
    }
    
    $requiredFields = ['player_username', 'wave_number', 'boss_level'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            return;
        }
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO boss_level_notifications (
                player_username, player_id, game_type, wave_number, boss_level, 
                boss_type, boss_name, score, dspoinc_earned, notification_type, status
            ) VALUES (
                :username, :player_id, :game_type, :wave_number, :boss_level,
                :boss_type, :boss_name, :score, :dspoinc_earned, :notification_type, 'pending'
            )
        ");
        
        $stmt->execute([
            ':username' => $input['player_username'],
            ':player_id' => $input['player_id'] ?? null,
            ':game_type' => $input['game_type'] ?? 'space_invaders',
            ':wave_number' => $input['wave_number'],
            ':boss_level' => $input['boss_level'],
            ':boss_type' => $input['boss_type'] ?? null,
            ':boss_name' => $input['boss_name'] ?? null,
            ':score' => $input['score'] ?? null,
            ':dspoinc_earned' => $input['dspoinc_earned'] ?? null,
            ':notification_type' => $input['notification_type'] ?? 'boss_level_reached'
        ]);
        
        $notificationId = $conn->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Boss level notification created successfully',
            'notification_id' => $notificationId,
            'data' => [
                'player' => $input['player_username'],
                'wave' => $input['wave_number'],
                'boss_level' => $input['boss_level'],
                'boss_name' => $input['boss_name'] ?? 'Unknown Boss'
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleGetNotifications($conn) {
    // Check if requesting a specific notification by ID
    if (isset($_GET['id'])) {
        $notificationId = (int)$_GET['id'];
        
        try {
            $stmt = $conn->prepare("
                SELECT * FROM boss_level_notifications 
                WHERE id = :id
            ");
            
            $stmt->execute([':id' => $notificationId]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($notification) {
                echo json_encode([
                    'success' => true,
                    'notifications' => [$notification]
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Notification not found']);
            }
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        return;
    }
    
    // Regular notification listing
    $status = $_GET['status'] ?? 'all';
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    try {
        $whereClause = '';
        $params = [];
        
        if ($status !== 'all') {
            $whereClause = 'WHERE status = :status';
            $params[':status'] = $status;
        }
        
        $stmt = $conn->prepare("
            SELECT * FROM boss_level_notifications 
            $whereClause
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        if ($status !== 'all') {
            $stmt->bindValue(':status', $status);
        }
        
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get count for pagination
        $countStmt = $conn->prepare("
            SELECT COUNT(*) as total FROM boss_level_notifications $whereClause
        ");
        
        if ($status !== 'all') {
            $countStmt->bindValue(':status', $status);
        }
        
        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'pagination' => [
                'total' => (int)$totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleUpdateNotification($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
        return;
    }
    
    try {
        $updateFields = [];
        $params = [':id' => $input['id']];
        
        if (isset($input['status'])) {
            $updateFields[] = 'status = :status';
            $params[':status'] = $input['status'];
        }
        
        if (isset($input['admin_reviewed_by'])) {
            $updateFields[] = 'admin_reviewed_by = :admin_reviewed_by';
            $params[':admin_reviewed_by'] = $input['admin_reviewed_by'];
        }
        
        if (isset($input['special_reward_given'])) {
            $updateFields[] = 'special_reward_given = :special_reward_given';
            $params[':special_reward_given'] = $input['special_reward_given'] ? 1 : 0;
        }
        
        if (isset($input['reward_description'])) {
            $updateFields[] = 'reward_description = :reward_description';
            $params[':reward_description'] = $input['reward_description'];
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            return;
        }
        
        $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
        if (isset($input['admin_reviewed_by'])) {
            $updateFields[] = 'admin_reviewed_at = CURRENT_TIMESTAMP';
        }
        
        $sql = "UPDATE boss_level_notifications SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Notification updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Notification not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteNotification($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM boss_level_notifications WHERE id = :id");
        $stmt->execute([':id' => $input['id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Notification not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
