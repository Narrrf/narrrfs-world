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
    // Check for Discord authentication
    $discord_user_id = null;
    if (isset($_COOKIE['discord_user_id'])) {
        $discord_user_id = $_COOKIE['discord_user_id'];
    }
    
    // If no Discord authentication, require admin login
    if (!$discord_user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized - Admin access required']);
        exit;
    }
    
    // Check Discord roles (basic check - should be enhanced)
    $allowed_roles = ['Moderator', 'Admin', 'super_admin', 'Founder', 'Bot Master'];
    $user_roles = isset($_COOKIE['discord_roles']) ? json_decode($_COOKIE['discord_roles'], true) : [];
    
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

try {
    // Use centralized database configuration
    require_once __DIR__ . '/../config/database.php';
    $db = getSQLite3Connection();

    // Get total users
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_users');
    $result = $stmt->execute();
    $totalUsers = $result->fetchArray(SQLITE3_ASSOC)['count'];

    // Get total score records
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_user_scores');
    $result = $stmt->execute();
    $totalScores = $result->fetchArray(SQLITE3_ASSOC)['count'];

    // Get total store items
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_store_items WHERE is_active = 1');
    $result = $stmt->execute();
    $totalItems = $result->fetchArray(SQLITE3_ASSOC)['count'];

    // Get active quests
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM tbl_quests WHERE is_active = 1');
    $result = $stmt->execute();
    $activeQuests = $result->fetchArray(SQLITE3_ASSOC)['count'];

    echo json_encode([
        'success' => true,
        'totalUsers' => $totalUsers,
        'totalScores' => $totalScores,
        'totalItems' => $totalItems,
        'activeQuests' => $activeQuests
    ]);

} catch (Exception $e) {
    error_log('Admin stats error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?> 