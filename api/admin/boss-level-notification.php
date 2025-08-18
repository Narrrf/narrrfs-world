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

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

try {
    $db = getSQLite3Connection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Create boss_level_notifications table if it doesn't exist
$createTableSQL = "
CREATE TABLE IF NOT EXISTS boss_level_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    player_username TEXT NOT NULL,
    player_id TEXT,
    game_type TEXT DEFAULT 'space_invaders',
    wave_number INTEGER NOT NULL,
    boss_level INTEGER NOT NULL,
    boss_type TEXT,
    boss_name TEXT,
    score INTEGER,
    dspoinc_earned INTEGER,
    notification_type TEXT DEFAULT 'boss_level_reached',
    status TEXT DEFAULT 'pending',
    admin_reviewed_by TEXT,
    admin_reviewed_at DATETIME,
    reward_amount INTEGER DEFAULT 0,
    reward_note TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

try {
    $db->exec($createTableSQL);
} catch (Exception $e) {
    // Table might already exist, continue
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        handleCreateNotification($db);
        break;
    case 'GET':
        handleGetNotifications($db);
        break;
    case 'PUT':
        handleUpdateNotification($db);
        break;
    case 'DELETE':
        handleDeleteNotification($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

function handleCreateNotification($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        return;
    }
    
    // Validate required fields
    $required = ['player_username', 'wave_number', 'boss_level', 'boss_name'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            return;
        }
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO boss_level_notifications (
                player_username, player_id, game_type, wave_number, boss_level, 
                boss_type, boss_name, score, dspoinc_earned, notification_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bindValue(1, $input['player_username'], SQLITE3_TEXT);
        $stmt->bindValue(2, $input['player_id'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(3, $input['game_type'] ?? 'space_invaders', SQLITE3_TEXT);
        $stmt->bindValue(4, $input['wave_number'], SQLITE3_INTEGER);
        $stmt->bindValue(5, $input['boss_level'], SQLITE3_INTEGER);
        $stmt->bindValue(6, $input['boss_type'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(7, $input['boss_name'], SQLITE3_TEXT);
        $stmt->bindValue(8, $input['score'] ?? null, SQLITE3_INTEGER);
        $stmt->bindValue(9, $input['dspoinc_earned'] ?? null, SQLITE3_INTEGER);
        $stmt->bindValue(10, $input['notification_type'] ?? 'boss_level_reached', SQLITE3_TEXT);
        
        $result = $stmt->execute();
        
        if ($result) {
            $notificationId = $db->lastInsertRowID();
            
            echo json_encode([
                'success' => true,
                'message' => 'Boss level notification created successfully',
                'notification_id' => $notificationId,
                'player_username' => $input['player_username'],
                'boss_level' => $input['boss_level'],
                'boss_name' => $input['boss_name']
            ]);
        } else {
            throw new Exception('Failed to insert notification');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleGetNotifications($db) {
    try {
        $status = $_GET['status'] ?? 'all';
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $whereClause = "";
        $params = [];
        $paramTypes = [];
        
        if ($status !== 'all') {
            $whereClause = "WHERE status = ?";
            $params[] = $status;
            $paramTypes[] = SQLITE3_TEXT;
        }
        
        $sql = "
            SELECT * FROM boss_level_notifications 
            $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $db->prepare($sql);
        
        $paramIndex = 1;
        foreach ($params as $i => $param) {
            $stmt->bindValue($paramIndex++, $param, $paramTypes[$i]);
        }
        $stmt->bindValue($paramIndex++, $limit, SQLITE3_INTEGER);
        $stmt->bindValue($paramIndex, $offset, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $notifications = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $notifications[] = $row;
        }
        
        // Get count for pagination
        $countSql = "SELECT COUNT(*) as total FROM boss_level_notifications $whereClause";
        $countStmt = $db->prepare($countSql);
        
        $paramIndex = 1;
        foreach ($params as $i => $param) {
            $countStmt->bindValue($paramIndex++, $param, $paramTypes[$i]);
        }
        
        $countResult = $countStmt->execute();
        $countRow = $countResult->fetchArray(SQLITE3_ASSOC);
        $total = $countRow['total'];
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleUpdateNotification($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['notification_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
        return;
    }
    
    try {
        // First get the notification to check if it exists and get player info
        $getStmt = $db->prepare("SELECT * FROM boss_level_notifications WHERE id = ?");
        $getStmt->bindValue(1, $input['notification_id'], SQLITE3_INTEGER);
        $getResult = $getStmt->execute();
        $notification = $getResult->fetchArray(SQLITE3_ASSOC);
        
        if (!$notification) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Notification not found']);
            return;
        }
        
        // If approving and reward amount specified, award the DSPOINC
        if (isset($input['status']) && $input['status'] === 'approved' && isset($input['reward_amount'])) {
            $rewardAmount = (int)$input['reward_amount'];
            if ($rewardAmount > 0) {
                // Award points using the point management system
                awardBossReward($db, $notification['player_username'], $rewardAmount, $input['reward_note'] ?? '');
            }
        }
        
        // Build update SQL
        $updateFields = [];
        $params = [];
        $paramTypes = [];
        
        if (isset($input['status'])) {
            $updateFields[] = "status = ?";
            $params[] = $input['status'];
            $paramTypes[] = SQLITE3_TEXT;
        }
        
        if (isset($input['admin_id'])) {
            $updateFields[] = "admin_reviewed_by = ?";
            $params[] = $input['admin_id'];
            $paramTypes[] = SQLITE3_TEXT;
            
            $updateFields[] = "admin_reviewed_at = ?";
            $params[] = date('Y-m-d H:i:s');
            $paramTypes[] = SQLITE3_TEXT;
        }
        
        if (isset($input['reward_amount'])) {
            $updateFields[] = "reward_amount = ?";
            $params[] = (int)$input['reward_amount'];
            $paramTypes[] = SQLITE3_INTEGER;
        }
        
        if (isset($input['reward_note'])) {
            $updateFields[] = "reward_note = ?";
            $params[] = $input['reward_note'];
            $paramTypes[] = SQLITE3_TEXT;
        }
        
        $updateFields[] = "updated_at = ?";
        $params[] = date('Y-m-d H:i:s');
        $paramTypes[] = SQLITE3_TEXT;
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            return;
        }
        
        $sql = "UPDATE boss_level_notifications SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $params[] = $input['notification_id'];
        $paramTypes[] = SQLITE3_INTEGER;
        
        $stmt = $db->prepare($sql);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param, $paramTypes[$i]);
        }
        
        $result = $stmt->execute();
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Notification updated successfully',
                'player_username' => $notification['player_username']
            ]);
        } else {
            throw new Exception('Failed to update notification');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteNotification($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM boss_level_notifications WHERE id = ?");
        $stmt->bindValue(1, $input['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        if ($db->changes() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Notification not found']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to award DSPOINC using the point management system
function awardBossReward($db, $username, $amount, $note) {
    try {
        // Insert into score adjustments table
        $stmt = $db->prepare("
            INSERT INTO tbl_score_adjustments (user_id, adjustment_type, adjustment_reason, points_before, points_after, adjustment_amount, admin_notes, created_at)
            VALUES (?, 'boss_achievement', ?, 0, ?, ?, ?, ?)
        ");
        
        $stmt->bindValue(1, $username, SQLITE3_TEXT);
        $stmt->bindValue(2, "Boss Achievement Reward", SQLITE3_TEXT);
        $stmt->bindValue(3, $amount, SQLITE3_INTEGER);
        $stmt->bindValue(4, $amount, SQLITE3_INTEGER);
        $stmt->bindValue(5, $note ?: "Boss achievement reward", SQLITE3_TEXT);
        $stmt->bindValue(6, date('Y-m-d H:i:s'), SQLITE3_TEXT);
        
        $stmt->execute();
        
        // Also add to user scores if they exist
        $checkStmt = $db->prepare("SELECT score FROM tbl_user_scores WHERE user_id = ? LIMIT 1");
        $checkStmt->bindValue(1, $username, SQLITE3_TEXT);
        $checkResult = $checkStmt->execute();
        $existingScore = $checkResult->fetchArray(SQLITE3_ASSOC);
        
        if ($existingScore) {
            // Update existing score
            $updateStmt = $db->prepare("UPDATE tbl_user_scores SET score = score + ? WHERE user_id = ?");
            $updateStmt->bindValue(1, $amount, SQLITE3_INTEGER);
            $updateStmt->bindValue(2, $username, SQLITE3_TEXT);
            $updateStmt->execute();
        } else {
            // Insert new score entry
            $insertStmt = $db->prepare("INSERT INTO tbl_user_scores (user_id, score, game_type) VALUES (?, ?, 'boss_achievement')");
            $insertStmt->bindValue(1, $username, SQLITE3_TEXT);
            $insertStmt->bindValue(2, $amount, SQLITE3_INTEGER);
            $insertStmt->execute();
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Boss reward error: " . $e->getMessage());
        return false;
    }
}
?>