<?php
/**
 * Portal Waypoint Register API
 * Date: January 18, 2026
 * Purpose: Handle portal visitor register/guestbook messages
 * 
 * Endpoints:
 * - GET: Fetch messages for a portal
 * - POST: Add new message to a portal
 * - PUT: Update existing message (future)
 * - DELETE: Delete message (future)
 */

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set JSON response header
header('Content-Type: application/json');

// Handle CORS for local development
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Credentials: true");
}

// Database connection
try {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '❌ Database connection error',
        'details' => $e->getMessage()
    ]);
    exit;
}

// ============================================================
// GET REQUEST: Fetch messages for a portal
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get parameters
        $action = isset($_GET['action']) ? trim($_GET['action']) : 'get';
        $portal_id = isset($_GET['portal_id']) ? trim($_GET['portal_id']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        
        // Validate portal_id
        if (empty($portal_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Missing portal_id parameter'
            ]);
            exit;
        }
        
        // Validate limit (max 100)
        if ($limit < 1) $limit = 50;
        if ($limit > 100) $limit = 100;
        
        // Fetch messages for this portal
        $stmt = $pdo->prepare("
            SELECT 
                id,
                portal_id,
                discord_id,
                username,
                message,
                created_at,
                updated_at
            FROM portal_waypoint_messages
            WHERE portal_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$portal_id, $limit]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM portal_waypoint_messages 
            WHERE portal_id = ?
        ");
        $countStmt->execute([$portal_id]);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'total_count' => $totalCount,
            'portal_id' => $portal_id,
            'limit' => $limit
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => '❌ Error fetching messages',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}

// ============================================================
// POST REQUEST: Add new message
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Read JSON body
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Invalid JSON data'
            ]);
            exit;
        }
        
        // Extract parameters
        $action = isset($data['action']) ? trim($data['action']) : 'add';
        $portal_id = isset($data['portal_id']) ? trim($data['portal_id']) : '';
        $discord_id = isset($data['discord_id']) ? trim($data['discord_id']) : '';
        $username = isset($data['username']) ? trim($data['username']) : '';
        $message = isset($data['message']) ? trim($data['message']) : '';
        
        // Validate required fields
        if (empty($portal_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Missing portal_id'
            ]);
            exit;
        }
        
        if (empty($discord_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Missing discord_id (user not logged in)'
            ]);
            exit;
        }
        
        if (empty($username)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Missing username'
            ]);
            exit;
        }
        
        if (empty($message)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Message cannot be empty'
            ]);
            exit;
        }
        
        // Validate message length (max 500 characters)
        if (strlen($message) > 500) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Message too long (max 500 characters)',
                'current_length' => strlen($message)
            ]);
            exit;
        }
        
        // Optional: Rate limiting - check if user posted recently (within 1 minute)
        $rateLimitStmt = $pdo->prepare("
            SELECT created_at 
            FROM portal_waypoint_messages 
            WHERE discord_id = ? AND portal_id = ?
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $rateLimitStmt->execute([$discord_id, $portal_id]);
        $lastMessage = $rateLimitStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastMessage) {
            $lastMessageTime = strtotime($lastMessage['created_at']);
            $currentTime = time();
            $timeDiff = $currentTime - $lastMessageTime;
            
            // Rate limit: 1 message per minute
            if ($timeDiff < 60) {
                http_response_code(429);
                echo json_encode([
                    'success' => false,
                    'error' => '❌ Please wait before posting another message',
                    'wait_seconds' => 60 - $timeDiff
                ]);
                exit;
            }
        }
        
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO portal_waypoint_messages (
                portal_id,
                discord_id,
                username,
                message,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            $portal_id,
            $discord_id,
            $username,
            $message
        ]);
        
        // Get the inserted message ID
        $messageId = $pdo->lastInsertId();
        
        // Fetch the created message to return
        $fetchStmt = $pdo->prepare("
            SELECT * FROM portal_waypoint_messages WHERE id = ?
        ");
        $fetchStmt->execute([$messageId]);
        $createdMessage = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Entry added successfully',
            'entry' => $createdMessage,
            'entry_id' => $messageId
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => '❌ Error adding message',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}

// ============================================================
// PUT REQUEST: Update existing message (optional feature)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Read JSON body
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Invalid JSON data'
            ]);
            exit;
        }
        
        // Extract parameters
        $entry_id = isset($data['entry_id']) ? (int)$data['entry_id'] : 0;
        $discord_id = isset($data['discord_id']) ? trim($data['discord_id']) : '';
        $message = isset($data['message']) ? trim($data['message']) : '';
        
        // Validate required fields
        if ($entry_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Missing or invalid entry_id'
            ]);
            exit;
        }
        
        if (empty($discord_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Missing discord_id'
            ]);
            exit;
        }
        
        if (empty($message)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Message cannot be empty'
            ]);
            exit;
        }
        
        // Validate message length
        if (strlen($message) > 500) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Message too long (max 500 characters)'
            ]);
            exit;
        }
        
        // Check if entry exists and belongs to user
        $checkStmt = $pdo->prepare("
            SELECT * FROM portal_waypoint_messages 
            WHERE id = ? AND discord_id = ?
        ");
        $checkStmt->execute([$entry_id, $discord_id]);
        $existingEntry = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingEntry) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => '❌ Entry not found or you do not have permission to edit it'
            ]);
            exit;
        }
        
        // Update message
        $stmt = $pdo->prepare("
            UPDATE portal_waypoint_messages 
            SET message = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND discord_id = ?
        ");
        
        $stmt->execute([$message, $entry_id, $discord_id]);
        
        // Fetch updated entry
        $fetchStmt = $pdo->prepare("
            SELECT * FROM portal_waypoint_messages WHERE id = ?
        ");
        $fetchStmt->execute([$entry_id]);
        $updatedEntry = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Entry updated successfully',
            'entry' => $updatedEntry
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => '❌ Error updating message',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}

// ============================================================
// DELETE REQUEST: Delete message (optional feature)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Read JSON body
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Invalid JSON data'
            ]);
            exit;
        }
        
        // Extract parameters
        $entry_id = isset($data['entry_id']) ? (int)$data['entry_id'] : 0;
        $discord_id = isset($data['discord_id']) ? trim($data['discord_id']) : '';
        
        // Validate required fields
        if ($entry_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Missing or invalid entry_id'
            ]);
            exit;
        }
        
        if (empty($discord_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '❌ Missing discord_id'
            ]);
            exit;
        }
        
        // Check if entry exists and belongs to user
        $checkStmt = $pdo->prepare("
            SELECT * FROM portal_waypoint_messages 
            WHERE id = ? AND discord_id = ?
        ");
        $checkStmt->execute([$entry_id, $discord_id]);
        $existingEntry = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingEntry) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => '❌ Entry not found or you do not have permission to delete it'
            ]);
            exit;
        }
        
        // Delete message
        $stmt = $pdo->prepare("
            DELETE FROM portal_waypoint_messages 
            WHERE id = ? AND discord_id = ?
        ");
        
        $stmt->execute([$entry_id, $discord_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Entry deleted successfully',
            'entry_id' => $entry_id
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => '❌ Error deleting message',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}

// ============================================================
// INVALID REQUEST METHOD
// ============================================================
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => '❌ Method not allowed. Use GET, POST, PUT, or DELETE.'
]);
