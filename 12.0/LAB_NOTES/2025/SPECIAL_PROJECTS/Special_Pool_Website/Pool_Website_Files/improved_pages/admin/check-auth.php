<?php
/**
 * Pool Website Admin - Check Authentication
 * Checks if user is currently logged in via session
 * 
 * Created: September 28, 2025
 */

// Suppress all output and warnings to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

// Check if user is logged in
$logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$username = $logged_in ? ($_SESSION['admin_username'] ?? 'pooladmin') : null;

ob_clean();
http_response_code(200);
echo json_encode([
    'success' => true,
    'logged_in' => $logged_in,
    'username' => $username,
    'session_id' => session_id(),
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);
ob_end_flush();
?>
