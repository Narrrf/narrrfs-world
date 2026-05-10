<?php
/**
 * Samuzi Admin - Logout Handler
 * Handles admin logout and session cleanup.
 */

// Suppress all output and warnings to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

// Log the logout
if (isset($_SESSION['admin_username'])) {
    $log_entry = date('Y-m-d H:i:s') . " - Admin logout: " . $_SESSION['admin_username'] . " from IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
}

// Clear session data
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

ob_clean();
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Successfully logged out',
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);
ob_end_flush();
?>
