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

// Check if user_id or user_wallet is provided (for compatibility)
if (!isset($_GET['user_id']) && !isset($_POST['user_id']) && !isset($_GET['user_wallet']) && !isset($_POST['user_wallet'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User ID or User Wallet is required']);
    exit;
}

// Accept both user_id and user_wallet parameters for compatibility
$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? $_GET['user_wallet'] ?? $_POST['user_wallet'];

try {
    // Use centralized database configuration
    require_once __DIR__ . '/../config/database.php';
    $db = getSQLite3Connection();

    // Get user's cheese click statistics
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_clicks,
        COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
        COUNT(CASE WHEN quest_id IS NULL THEN 1 END) as regular_clicks,
        MAX(timestamp) as last_click
        FROM tbl_cheese_clicks 
        WHERE user_wallet = ?");
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $clickStats = $result->fetchArray(SQLITE3_ASSOC);

    // Get user's quest participation
    $stmt = $db->prepare("SELECT 
        quest_id,
        timestamp
        FROM tbl_cheese_clicks 
        WHERE user_wallet = ? AND quest_id IS NOT NULL
        ORDER BY timestamp DESC");
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $questParticipation = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $questParticipation[] = $row;
    }

    // Get user's recent click activity
    $stmt = $db->prepare("SELECT 
        egg_id,
        timestamp,
        quest_id
        FROM tbl_cheese_clicks 
        WHERE user_wallet = ?
        ORDER BY timestamp DESC
        LIMIT 20");
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $recentActivity = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $recentActivity[] = $row;
    }

    // Get user info
    $stmt = $db->prepare("SELECT username, avatar_url FROM tbl_users WHERE discord_id = ?");
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $userInfo = $result->fetchArray(SQLITE3_ASSOC);

    $response = [
        'success' => true,
        'user_id' => $userId,
        'username' => $userInfo['username'] ?? 'Unknown',
        'avatar_url' => $userInfo['avatar_url'] ?? null,
        'statistics' => [
            'total_clicks' => (int)$clickStats['total_clicks'],
            'quest_clicks' => (int)$clickStats['quest_clicks'],
            'regular_clicks' => (int)$clickStats['regular_clicks'],
            'last_click' => $clickStats['last_click']
        ],
        'quest_participation' => $questParticipation,
        'recent_activity' => $recentActivity,
        'status' => $clickStats['total_clicks'] > 0 ? 'Active' : 'Inactive'
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Error in get-user-cheese-stats: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
