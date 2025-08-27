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

// Check if user_id is provided
if (!isset($_GET['user_id']) && !isset($_POST['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

$userId = $_GET['user_id'] ?? $_POST['user_id'];

try {
    // Use centralized database configuration
    require_once __DIR__ . '/../config/database.php';
    $db = getSQLite3Connection();

    // Get user's Discord Race statistics
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_races,
        COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
        COUNT(CASE WHEN position <= 3 THEN 1 END) as top_3,
        AVG(position) as average_position,
        MAX(cheese_collected) as best_cheese,
        SUM(cheese_collected) as total_cheese,
        MAX(dspoinc_earned) as best_dspoinc,
        SUM(dspoinc_earned) as total_dspoinc,
        MAX(joined_at) as last_race
        FROM tbl_race_participants 
        WHERE discord_id = ?");
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $raceStats = $result->fetchArray(SQLITE3_ASSOC);

    // Get user's recent races
    $stmt = $db->prepare("SELECT 
        rp.race_id,
        rp.position,
        rp.cheese_collected,
        rp.dspoinc_earned,
        rp.joined_at,
        rp.finished_at,
        cr.race_id as race_name,
        cr.status
        FROM tbl_race_participants rp
        LEFT JOIN tbl_cheese_races cr ON rp.race_id = cr.race_id
        WHERE rp.discord_id = ?
        ORDER BY rp.joined_at DESC
        LIMIT 10");
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $recentRaces = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $recentRaces[] = $row;
    }

    // Get user's seasonal performance
    $stmt = $db->prepare("SELECT 
        season,
        COUNT(*) as races_played,
        COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
        AVG(position) as average_position,
        SUM(cheese_collected) as total_cheese,
        SUM(dspoinc_earned) as total_dspoinc
        FROM tbl_race_participants 
        WHERE discord_id = ?
        GROUP BY season
        ORDER BY season DESC");
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $seasonalPerformance = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $seasonalPerformance[] = $row;
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
            'total_races' => (int)$raceStats['total_races'],
            'wins' => (int)$raceStats['wins'],
            'top_3' => (int)$raceStats['top_3'],
            'average_position' => round((float)$raceStats['average_position'], 2),
            'best_cheese' => (int)$raceStats['best_cheese'],
            'total_cheese' => (int)$raceStats['total_cheese'],
            'best_dspoinc' => (int)$raceStats['best_dspoinc'],
            'total_dspoinc' => (int)$raceStats['total_dspoinc'],
            'last_race' => $raceStats['last_race']
        ],
        'recent_races' => $recentRaces,
        'seasonal_performance' => $seasonalPerformance,
        'status' => $raceStats['total_races'] > 0 ? 'Active' : 'Inactive'
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Error in get-user-discord-race-stats: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
