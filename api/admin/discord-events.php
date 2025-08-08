<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Check for authentication
session_start();
$is_authenticated = false;

// Method 1: Check for bot token authentication (for Discord bot)
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
    $bot_token = substr($auth_header, 7);
    $expected_token = getenv('DISCORD_BOT_TOKEN');
    
    if ($bot_token === $expected_token) {
        $is_authenticated = true;
    }
}

// Method 2: Check for admin session authentication
if (!$is_authenticated) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Check for Discord authentication - multiple methods
        $discord_user_id = null;
        $user_roles = [];
        
        // Method 2a: Check for Discord cookies
        if (isset($_COOKIE['discord_user_id'])) {
            $discord_user_id = $_COOKIE['discord_user_id'];
            $user_roles = isset($_COOKIE['discord_roles']) ? json_decode($_COOKIE['discord_roles'], true) : [];
        }
        
        // Method 2b: Check for session-based Discord authentication
        if (!$discord_user_id && isset($_SESSION['discord_id'])) {
            $discord_user_id = $_SESSION['discord_id'];
            
            // Get user roles from database
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = getSQLite3Connection();
                $stmt = $db->prepare('SELECT role_name FROM tbl_user_roles WHERE user_id = ?');
                $stmt->bindValue(1, $discord_user_id, SQLITE3_TEXT);
                $result = $stmt->execute();
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $user_roles[] = $row['role_name'];
                }
            } catch (Exception $e) {
                error_log('Error fetching user roles: ' . $e->getMessage());
            }
        }
        
        // If no Discord authentication, require admin login
        if (!$discord_user_id) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized - Admin access required']);
            exit;
        }
        
        // Check Discord roles (basic check - should be enhanced)
        $allowed_roles = ['Moderator', 'Admin', 'super_admin', 'Founder', 'Bot Master'];
        
        $has_permission = false;
        foreach ($allowed_roles as $role) {
            if (in_array($role, $user_roles)) {
                $has_permission = true;
                break;
            }
        }
        
        if (!$has_permission && $discord_user_id !== '328601656659017732') { // narrrf's ID
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
            exit;
        }
        
        $is_authenticated = true;
    } else {
        $is_authenticated = true;
    }
}

if (!$is_authenticated) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle incoming Discord events
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
            exit;
        }
        
        $event_type = $input['type'] ?? 'messageCreate';
        $user_name = $input['user_name'] ?? 'Unknown';
        $user_id = $input['user_id'] ?? '000000000000000000';
        $channel_name = $input['channel_name'] ?? 'unknown';
        $description = $input['description'] ?? '';
        $timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');
        
        // Store the event in the database
        $stmt = $pdo->prepare("
            INSERT INTO tbl_discord_events (event_type, user_name, user_id, channel_name, description, timestamp)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$event_type, $user_name, $user_id, $channel_name, $description, $timestamp]);
        
        echo json_encode(['success' => true, 'message' => 'Event stored successfully']);
        
    } else {
        // GET request - retrieve recent events
        $stmt = $pdo->prepare("
            SELECT 
                event_type as type,
                user_name,
                user_id,
                channel_name,
                description,
                timestamp
            FROM tbl_discord_events 
            WHERE timestamp >= datetime('now', '-1 hour')
            ORDER BY timestamp DESC 
            LIMIT 20
        ");
        
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'events' => $events
        ]);
    }
    
} catch (Exception $e) {
    error_log('Discord events error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
