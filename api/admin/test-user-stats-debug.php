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
    // Test database connection
    require_once __DIR__ . '/../config/database.php';
    
    // Test 1: Check if database file exists
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    $dbExists = file_exists($dbPath);
    
    // Test 2: Try to get connection
    $db = getSQLite3Connection();
    $connectionSuccess = ($db !== null);
    
    // Test 3: Try a simple query
    $testQuery = "SELECT COUNT(*) as user_count FROM tbl_users WHERE discord_id = ?";
    $stmt = $db->prepare($testQuery);
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $userCount = $result->fetchArray(SQLITE3_ASSOC);
    
    // Test 4: Try the actual Tetris query
    $tetrisQuery = "SELECT COUNT(*) as total_games FROM tbl_tetris_scores WHERE discord_id = ? AND game = 'tetris'";
    $stmt = $db->prepare($tetrisQuery);
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $tetrisCount = $result->fetchArray(SQLITE3_ASSOC);
    
    $response = [
        'success' => true,
        'debug_info' => [
            'database_file_exists' => $dbExists,
            'database_path' => $dbPath,
            'connection_success' => $connectionSuccess,
            'user_id_provided' => $userId,
            'user_exists_in_db' => ($userCount['user_count'] > 0),
            'tetris_games_count' => $tetrisCount['total_games'] ?? 0
        ],
        'test_results' => [
            'user_count_query' => $userCount,
            'tetris_count_query' => $tetrisCount
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Error in test-user-stats-debug: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection test failed',
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
