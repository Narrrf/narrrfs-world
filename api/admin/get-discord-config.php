<?php
// Discord Configuration API Endpoint for Admin Interface
// Provides Discord configuration data with admin authentication

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Include admin authentication
require_once '../config/admin-auth.php';

// Check admin authentication
if (!checkAdminAuthentication()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Admin access required']);
    exit;
}

try {
    // Get Discord invite code from environment variable
    $inviteCode = getenv('DISCORD_INVITE_CODE') ?: 'cVWbUgdARq';
    
    // Get Discord bot status and configuration
    $discordConfig = [
        'success' => true,
        'config' => [
            'invite_code' => $inviteCode,
            'discord_url' => "https://discord.gg/$inviteCode",
            'base_url' => 'https://discord.gg/',
            'version' => '12.0',
            'last_updated' => '2025-01-28',
            'environment_variable' => getenv('DISCORD_INVITE_CODE'),
            'environment_set' => getenv('DISCORD_INVITE_CODE') !== false,
            'fallback_used' => getenv('DISCORD_INVITE_CODE') === false || getenv('DISCORD_INVITE_CODE') === null
        ],
        'bot_status' => [
            'bot_token_set' => getenv('DISCORD_BOT_SECRET') !== false,
            'guild_id' => getenv('DISCORD_GUILD') ?: '1332015322546311218',
            'moderator_role_id' => '1332049628300054679'
        ],
        'debug' => [
            'server_time' => date('Y-m-d H:i:s'),
            'environment' => getenv('ENVIRONMENT') ?: 'production',
            'api_version' => '1.0'
        ]
    ];
    
    echo json_encode($discordConfig);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load Discord configuration: ' . $e->getMessage()
    ]);
}
?>
