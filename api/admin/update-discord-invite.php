<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

// Check if admin authentication is provided
if (!isset($input['admin_username']) || !isset($input['admin_password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin authentication required']);
    exit;
}

// Include admin authentication
require_once '../admin/auth.php';

// Verify admin credentials
$admin_username = $input['admin_username'];
$admin_password = $input['admin_password'];

// Simple admin verification (you can enhance this with database lookup)
$valid_admins = [
    'narrrf' => 'your_secure_password_here', // Replace with actual password
    // Add other admin credentials as needed
];

if (!isset($valid_admins[$admin_username]) || $valid_admins[$admin_username] !== $admin_password) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid admin credentials']);
    exit;
}

// Check if new invite code is provided
if (!isset($input['new_invite_code']) || empty($input['new_invite_code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'New invite code is required']);
    exit;
}

$new_invite_code = trim($input['new_invite_code']);

// Validate invite code format (basic validation)
if (!preg_match('/^[a-zA-Z0-9]+$/', $new_invite_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid invite code format']);
    exit;
}

try {
    // Update the Discord config file
    $config_file = '../../public/discord-config.js';
    
    if (!file_exists($config_file)) {
        throw new Exception('Discord config file not found');
    }
    
    // Read the current config
    $config_content = file_get_contents($config_file);
    
    // Update the invite code
    $updated_content = preg_replace(
        "/inviteCode.*?['\"]([^'\"]+)['\"]/",
        "inviteCode: (typeof process !== 'undefined' && process.env && process.env.DISCORD_INVITE_CODE) \n        ? process.env.DISCORD_INVITE_CODE \n        : '$new_invite_code', // Fallback Discord invite code",
        $config_content
    );
    
    // Write the updated config back
    if (file_put_contents($config_file, $updated_content) === false) {
        throw new Exception('Failed to write updated config file');
    }
    
    // Log the update
    $log_entry = date('Y-m-d H:i:s') . " - Discord invite code updated to: $new_invite_code by admin: $admin_username\n";
    file_put_contents('../../logs/discord-updates.log', $log_entry, FILE_APPEND | LOCK_EX);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Discord invite code updated successfully to: $new_invite_code",
        'new_invite_code' => $new_invite_code,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to update Discord invite code: ' . $e->getMessage()
    ]);
}
?> 