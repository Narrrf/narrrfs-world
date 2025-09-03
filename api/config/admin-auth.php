<?php
/**
 * Centralized Authentication Functions
 * Provides secure authentication for different API types
 */

/**
 * Check Admin Authentication (for admin-only APIs)
 * Used by admin interface APIs
 */
function checkAdminAuthentication() {
    // 🔧 CRITICAL FIX: Local development bypass (SAFE - only for localhost)
    $isLocalDevelopment = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';
    if ($isLocalDevelopment) {
        // Skip authentication for local development only
        return true;
    }

    // Production authentication - multiple methods
    session_start();
    
    // Method 1: Check for admin session
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return true;
    }

    // Method 2: Check for Discord authentication
    $discord_user_id = null;
    $user_roles = [];
    
    // Check for Discord cookies
    if (isset($_COOKIE['discord_user_id'])) {
        $discord_user_id = $_COOKIE['discord_user_id'];
        $user_roles = isset($_COOKIE['discord_roles']) ? json_decode($_COOKIE['discord_roles'], true) : [];
    }
    
    // Check for session-based Discord authentication
    if (!$discord_user_id && isset($_SESSION['discord_id'])) {
        $discord_user_id = $_SESSION['discord_id'];
        
        // Get user roles from database
        try {
            require_once __DIR__ . '/database.php';
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
    
    // If no Discord authentication, deny access
    if (!$discord_user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized - Admin access required']);
        exit;
    }
    
    // Check Discord roles for admin permissions
    $allowed_roles = ['Moderator', 'Admin', 'super_admin', 'Founder', 'Bot Master'];
    
    $has_permission = false;
    foreach ($allowed_roles as $role) {
        if (in_array($role, $user_roles)) {
            $has_permission = true;
            break;
        }
    }
    
    // Special case for narrrf's ID
    if (!$has_permission && $discord_user_id !== '328601656659017732') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
        exit;
    }
    
    return true;
}

/**
 * Check User Authentication (for user-accessible APIs)
 * Used by profile page APIs - allows any authenticated Discord user
 */
function checkUserAuthentication() {
    // 🔧 CRITICAL FIX: Local development bypass (SAFE - only for localhost)
    $isLocalDevelopment = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';
    if ($isLocalDevelopment) {
        // Skip authentication for local development only
        return true;
    }

    // Production authentication - multiple methods
    session_start();
    
    // Method 1: Check for Discord session
    if (isset($_SESSION['discord_id']) && !empty($_SESSION['discord_id'])) {
        return true;
    }
    
    // Method 2: Check for Discord cookies
    if (isset($_COOKIE['discord_user_id']) && !empty($_COOKIE['discord_user_id'])) {
        return true;
    }
    
    // If no Discord authentication, deny access
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Discord login required']);
    exit;
}

/**
 * Get database path based on environment
 */
function getDatabasePath() {
    $isLocalDevelopment = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';
    if ($isLocalDevelopment) {
        return __DIR__ . '/../../db/narrrf_world.sqlite';
    } else {
        return '/var/www/html/db/narrrf_world.sqlite';
    }
}
?>
