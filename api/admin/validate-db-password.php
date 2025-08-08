<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Check for authentication first
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Check for Discord authentication - multiple methods
    $discord_user_id = null;
    $user_roles = [];
    
    // Method 1: Check for Discord cookies
    if (isset($_COOKIE['discord_user_id'])) {
        $discord_user_id = $_COOKIE['discord_user_id'];
        $user_roles = isset($_COOKIE['discord_roles']) ? json_decode($_COOKIE['discord_roles'], true) : [];
    }
    
    // Method 2: Check for session-based Discord authentication
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
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get the submitted password
    $input = json_decode(file_get_contents('php://input'), true);
    $submitted_password = $input['password'] ?? '';
    
    if (empty($submitted_password)) {
        echo json_encode(['success' => false, 'error' => 'Password is required']);
        exit;
    }
    
    // Get the database password from environment variable
    $db_password = getenv('DB_UNLOCK_PASSWORD');
    
    if (!$db_password) {
        error_log('DB_UNLOCK_PASSWORD environment variable not set');
        echo json_encode(['success' => false, 'error' => 'Database password not configured']);
        exit;
    }
    
    // Validate the password
    if (password_verify($submitted_password, $db_password) || hash_equals($db_password, hash('sha256', $submitted_password))) {
        // Password is correct - set session variable for database access
        $_SESSION['db_access_granted'] = true;
        $_SESSION['db_access_time'] = time();
        
        echo json_encode([
            'success' => true,
            'message' => 'Database access granted',
            'expires' => time() + 3600 // 1 hour
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Incorrect password']);
    }
    
} catch (Exception $e) {
    error_log('Database password validation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Validation error occurred']);
}
?>
