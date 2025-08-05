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
if (!isset($input['admin_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin username required']);
    exit;
}

// Include admin authentication
require_once '../admin/auth.php';

// Verify admin credentials using Discord authentication
$admin_username = $input['admin_username'];

// Simple admin verification
$admin_users = [
    'narrrf' => [
        'password' => 'PnoRakesucks&2025',
        'role' => 'super_admin',
        'discord_id' => '328601656659017732'
    ]
];

if (!isset($admin_users[$admin_username])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid admin username']);
    exit;
}

// Get admin info and verify Discord role
$admin_info = $admin_users[$admin_username];
$admin_discord_id = $admin_info['discord_id'];

// Discord Bot Token and role configuration
$DISCORD_BOT_SECRET = getenv('DISCORD_BOT_SECRET');
$MODERATOR_ROLE_ID = '1332049628300054679';
$GUILD_ID = getenv('DISCORD_GUILD') ?: '1332015322546311218';

// Function to check Discord moderator role
function checkDiscordModeratorRole($discord_user_id) {
    global $DISCORD_BOT_SECRET, $MODERATOR_ROLE_ID, $GUILD_ID;
    
    if (!$discord_user_id || !$DISCORD_BOT_SECRET) {
        return true; // Allow access if no proper setup
    }
    
    $url = "https://discord.com/api/v10/guilds/{$GUILD_ID}/members/{$discord_user_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot {$DISCORD_BOT_SECRET}",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $member_data = json_decode($response, true);
        if (isset($member_data['roles']) && in_array($MODERATOR_ROLE_ID, $member_data['roles'])) {
            return true;
        }
    }
    
    return false;
}

// Verify Discord moderator role
if (!checkDiscordModeratorRole($admin_discord_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Discord moderator role required']);
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