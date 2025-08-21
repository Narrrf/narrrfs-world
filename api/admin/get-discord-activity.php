<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDatabaseConnection();

    // Get recent Discord activity from various sources
    $events = [];
    
    // 1. Get recent game activity (messages/commands)
    $stmt = $pdo->prepare("
        SELECT 
            'game_score' as type,
            discord_id as user_name,
            discord_id as user_id,
            game as channel_name,
            timestamp,
            CONCAT('Scored ', score, ' points in ', game) as description
        FROM tbl_tetris_scores 
        WHERE timestamp >= datetime('now', '-1 hour')
        ORDER BY timestamp DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $gameActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($gameActivity as $activity) {
        $events[] = [
            'type' => 'command',
            'user_name' => $activity['user_name'],
            'user_id' => $activity['user_id'],
            'channel_name' => $activity['channel_name'],
            'timestamp' => $activity['timestamp'],
            'description' => $activity['description']
        ];
    }
    
    // 2. Get recent score adjustments (admin activity)
    $stmt = $pdo->prepare("
        SELECT 
            'admin_action' as type,
            user_id as user_name,
            admin_id as user_id,
            'admin-panel' as channel_name,
            timestamp,
            CONCAT(action, ' ', amount, ' DSPOINC - ', reason) as description
        FROM tbl_score_adjustments 
        WHERE timestamp >= datetime('now', '-1 hour')
        ORDER BY timestamp DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $adminActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($adminActivity as $activity) {
        $events[] = [
            'type' => 'admin_action',
            'user_name' => $activity['user_name'],
            'user_id' => $activity['user_id'],
            'channel_name' => $activity['channel_name'],
            'timestamp' => $activity['timestamp'],
            'description' => $activity['description']
        ];
    }
    
    // 3. Get recent cheese clicks (user activity)
    $stmt = $pdo->prepare("
        SELECT 
            'cheese_click' as type,
            user_wallet as user_name,
            user_wallet as user_id,
            'cheeseboard' as channel_name,
            timestamp,
            'Clicked the cheese!' as description
        FROM tbl_cheese_clicks 
        WHERE timestamp >= datetime('now', '-1 hour')
        ORDER BY timestamp DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $cheeseActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cheeseActivity as $activity) {
        $events[] = [
            'type' => 'messageCreate',
            'user_name' => $activity['user_name'],
            'user_id' => $activity['user_id'],
            'channel_name' => $activity['channel_name'],
            'timestamp' => $activity['timestamp'],
            'description' => $activity['description']
        ];
    }
    
    // 4. If no recent activity, provide some realistic sample events
    if (empty($events)) {
        $events = [
            [
                'type' => 'messageCreate',
                'user_name' => 'System',
                'user_id' => '000000000000000000',
                'channel_name' => 'general',
                'timestamp' => date('Y-m-d H:i:s'),
                'description' => 'Discord activity feed initialized'
            ]
        ];
    }
    
    // Sort events by timestamp (newest first)
    usort($events, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    echo json_encode([
        'success' => true,
        'events' => array_slice($events, 0, 10) // Limit to 10 most recent events
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
