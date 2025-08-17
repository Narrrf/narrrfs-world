<?php
// Discord Configuration API Endpoint
// Serves Discord configuration as JSON for client-side use

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-cache, must-revalidate');

require_once 'discord.php';

// Output the Discord configuration as JSON
$inviteCode = getenv('DISCORD_INVITE_CODE') ?: 'qYYNGJrR43';

// Debug information
$debug = [
    'inviteCode' => $inviteCode,
    'baseUrl' => 'https://discord.gg/',
    'fullUrl' => "https://discord.gg/$inviteCode",
    'version' => '12.0',
    'lastUpdated' => '2025-01-28',
    'debug' => [
        'env_value' => getenv('DISCORD_INVITE_CODE'),
        'env_set' => getenv('DISCORD_INVITE_CODE') !== false,
        'fallback_used' => getenv('DISCORD_INVITE_CODE') === false || getenv('DISCORD_INVITE_CODE') === null
    ]
];

echo json_encode($debug);
?>
