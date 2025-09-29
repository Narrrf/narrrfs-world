<?php
/**
 * Pool Website Admin Login Handler
 * Handles admin authentication for the pool website management system
 * 
 * Created: September 28, 2025
 * Purpose: Secure admin login for pool website management
 */

// Suppress all output and warnings to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Set content type to JSON for all responses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only POST requests are accepted.'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['username', 'password'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

// Admin credentials (in production, use hashed passwords from database)
$admin_credentials = [
    'username' => 'pooladmin',
    'password' => 'PoolBau2025!'  // Change this to a secure password
];

// Check credentials
$username = trim($input['username']);
$password = trim($input['password']);

if ($username === $admin_credentials['username'] && $password === $admin_credentials['password']) {
    // Set session variables
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $username;
    $_SESSION['login_time'] = time();
    
    // Log successful login
    $log_entry = date('Y-m-d H:i:s') . " - Admin login successful from IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login erfolgreich!',
        'admin_info' => [
            'username' => $username,
            'login_time' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    
} else {
    // Log failed login attempt
    $log_entry = date('Y-m-d H:i:s') . " - Failed admin login attempt from IP: " . $_SERVER['REMOTE_ADDR'] . " (Username: " . $username . ")\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Anmeldedaten. Bitte überprüfen Sie Benutzername und Passwort.'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
?>
