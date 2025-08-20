<?php
// Test endpoint for Discord bot authentication
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Enable debug logging
define('DEBUG', true);

// Check for bot token authentication
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$is_authenticated = false;
$auth_method = 'none';

if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
    $bot_token = substr($auth_header, 7);
    
    // Try multiple authentication methods for the bot
    $expected_tokens = [
        getenv('DISCORD_BOT_SECRET'),  // Primary bot token
        getenv('DISCORD_SECRET'),      // API secret as fallback
        getenv('DISCORD_BOT_TOKEN')    // Legacy token name
    ];
    
    foreach ($expected_tokens as $expected_token) {
        if ($expected_token && $bot_token === $expected_token) {
            $is_authenticated = true;
            $auth_method = $expected_token === getenv('DISCORD_BOT_SECRET') ? 'DISCORD_BOT_SECRET' : 
                          ($expected_token === getenv('DISCORD_SECRET') ? 'DISCORD_SECRET' : 'DISCORD_BOT_TOKEN');
            break;
        }
    }
    
    if (DEBUG) {
        error_log("Bot token received: " . ($bot_token ? substr($bot_token, 0, 10) . "..." : "NULL"));
        error_log("Environment check - DISCORD_BOT_SECRET: " . (getenv('DISCORD_BOT_SECRET') ? "SET" : "NOT SET"));
        error_log("Environment check - DISCORD_SECRET: " . (getenv('DISCORD_SECRET') ? "SET" : "NOT SET"));
        error_log("Environment check - DISCORD_BOT_TOKEN: " . (getenv('DISCORD_BOT_TOKEN') ? "SET" : "NOT SET"));
    }
}

// Return authentication status
echo json_encode([
    'success' => $is_authenticated,
    'authenticated' => $is_authenticated,
    'auth_method' => $auth_method,
    'timestamp' => date('Y-m-d H:i:s'),
    'debug' => [
        'auth_header_present' => !empty($auth_header),
        'auth_header_type' => strpos($auth_header, 'Bearer ') === 0 ? 'Bearer' : 'Other',
        'bot_token_length' => $bot_token ? strlen($bot_token) : 0,
        'environment_variables' => [
            'DISCORD_BOT_SECRET' => getenv('DISCORD_BOT_SECRET') ? 'SET' : 'NOT SET',
            'DISCORD_SECRET' => getenv('DISCORD_SECRET') ? 'SET' : 'NOT SET',
            'DISCORD_BOT_TOKEN' => getenv('DISCORD_BOT_TOKEN') ? 'SET' : 'NOT SET'
        ]
    ]
]);
?>
