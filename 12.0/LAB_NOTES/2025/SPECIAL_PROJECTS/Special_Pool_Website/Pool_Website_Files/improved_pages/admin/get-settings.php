<?php
/**
 * Pool Website Admin - Get Settings
 * Retrieves saved settings from settings.json
 * 
 * Created: September 29, 2025
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
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

try {
    $settings_file = 'settings.json';
    $settings = [];
    
    // Load settings if file exists
    if (file_exists($settings_file)) {
        $settings = json_decode(file_get_contents($settings_file), true) ?: [];
    }
    
    // Set default values if not present
    $default_settings = [
        'email_address' => 'office@poolbauprofi.at',
        'company_name' => 'Poolbauprofi.at',
        'phone_number' => '+43 660 8669020',
        'slider_speed' => 5,
        'smtp_port' => '587',
        'smtp_encryption' => 'tls',
        'from_email' => 'noreply@poolbauprofi.at',
        'from_name' => 'Poolbauprofi.at'
    ];
    
    // Merge with defaults
    $settings = array_merge($default_settings, $settings);
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading settings: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
?>
