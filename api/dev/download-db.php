<?php
session_start();

$source = "/var/www/html/db/narrrf_world.sqlite";

// Check for authentication - multiple methods
$is_authenticated = false;

// Method 1: Check for secret token (for direct access)
$secret = $_GET['secret'] ?? '';
$expected_secret = getenv('DB_DOWNLOAD_SECRET') ?: 'MyUltraSecretKey123'; // Fallback for development

if ($secret === $expected_secret) {
    $is_authenticated = true;
}

// Method 2: Check for admin session authentication
if (!$is_authenticated) {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $is_authenticated = true;
    } else {
        // Check for Discord authentication
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
        
        // Check Discord roles
        if ($discord_user_id) {
            $allowed_roles = ['Moderator', 'Admin', 'super_admin', 'Founder', 'Bot Master'];
            
            foreach ($allowed_roles as $role) {
                if (in_array($role, $user_roles)) {
                    $is_authenticated = true;
                    break;
                }
            }
            
            // Allow narrrf's ID
            if ($discord_user_id === '328601656659017732') {
                $is_authenticated = true;
            }
        }
    }
}

if (!$is_authenticated) {
    http_response_code(403);
    exit('❌ Forbidden - Authentication required');
}

// ✅ Check if DB exists
if (!file_exists($source)) {
    http_response_code(404);
    exit('❌ Database not found.');
}

// 📦 Serve file for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="narrrf_world.sqlite"');
readfile($source);
exit;
?>
