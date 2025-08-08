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
    $pdo = getDatabaseConnection();

    // Get total cheese clicks
    $stmt = $pdo->query("SELECT COUNT(*) as total_clicks FROM tbl_cheese_clicks");
    $totalClicks = $stmt->fetch(PDO::FETCH_ASSOC)['total_clicks'];

    // Get clicks by egg type
    $stmt = $pdo->query("SELECT egg_id, COUNT(*) as click_count 
                         FROM tbl_cheese_clicks 
                         GROUP BY egg_id 
                         ORDER BY click_count DESC");
    $clicksByEgg = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent clicks (last 24 hours)
    $stmt = $pdo->query("SELECT COUNT(*) as recent_clicks 
                         FROM tbl_cheese_clicks 
                         WHERE timestamp >= datetime('now', '-1 day')");
    $recentClicks = $stmt->fetch(PDO::FETCH_ASSOC)['recent_clicks'];

    // Get top users by cheese clicks with usernames
    $stmt = $pdo->query("SELECT c.user_wallet, u.username, COUNT(*) as click_count 
                         FROM tbl_cheese_clicks c
                         LEFT JOIN tbl_users u ON c.user_wallet = u.discord_id
                         GROUP BY c.user_wallet 
                         ORDER BY click_count DESC 
                         LIMIT 10");
    $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get top clicker with username
    $stmt = $pdo->query("SELECT c.user_wallet, u.username, COUNT(*) as click_count 
                         FROM tbl_cheese_clicks c
                         LEFT JOIN tbl_users u ON c.user_wallet = u.discord_id
                         GROUP BY c.user_wallet 
                         ORDER BY click_count DESC 
                         LIMIT 1");
    $topClicker = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get clicks by day (last 7 days)
    $stmt = $pdo->query("SELECT DATE(timestamp) as click_date, COUNT(*) as daily_clicks 
                         FROM tbl_cheese_clicks 
                         WHERE timestamp >= datetime('now', '-7 days') 
                         GROUP BY DATE(timestamp) 
                         ORDER BY click_date DESC");
    $dailyClicks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_clicks' => $totalClicks,
            'recent_clicks_24h' => $recentClicks,
            'clicks_by_egg' => $clicksByEgg,
            'top_users' => $topUsers,
            'top_clicker' => $topClicker,
            'daily_clicks' => $dailyClicks
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => '❌ Database error',
        'details' => $e->getMessage()
    ]);
}
?>