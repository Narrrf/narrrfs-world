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

    // Get user's Space Invaders statistics
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_games,
        MAX(score) as best_score,
        AVG(score) as average_score,
        SUM(score) as total_score,
        MAX(timestamp) as last_game,
        MIN(timestamp) as first_game
        FROM tbl_tetris_scores 
        WHERE discord_id = ? AND game = 'space_invaders'");
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $spaceStats = $result->fetchArray(SQLITE3_ASSOC);

    // Get user's recent Space Invaders games
    $stmt = $db->prepare("SELECT 
        score,
        timestamp,
        season,
        is_current_season
        FROM tbl_tetris_scores 
        WHERE discord_id = ? AND game = 'space_invaders'
        ORDER BY timestamp DESC
        LIMIT 10");
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $recentGames = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $recentGames[] = $row;
    }

    // Get user's seasonal performance
    $stmt = $db->prepare("SELECT 
        season,
        COUNT(*) as games_played,
        MAX(score) as best_score,
        AVG(score) as average_score
        FROM tbl_tetris_scores 
        WHERE discord_id = ? AND game = 'space_invaders'
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
            'total_games' => (int)$spaceStats['total_games'],
            'best_score' => (int)$spaceStats['best_score'],
            'average_score' => round((float)$spaceStats['average_score'], 2),
            'total_score' => (int)$spaceStats['total_score'],
            'last_game' => $spaceStats['last_game'],
            'first_game' => $spaceStats['first_game']
        ],
        'recent_games' => $recentGames,
        'seasonal_performance' => $seasonalPerformance,
        'status' => $spaceStats['total_games'] > 0 ? 'Active' : 'Inactive'
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Error in get-user-space-invaders-stats: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
