<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check authorization - Use getallheaders() for better server compatibility
$headers = getallheaders();
$auth_header = '';

// Try multiple ways to get the authorization header
if (isset($headers['Authorization'])) {
    $auth_header = $headers['Authorization'];
} elseif (isset($headers['authorization'])) {
    $auth_header = $headers['authorization'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['HTTP_AUTH'])) {
    $auth_header = $_SERVER['HTTP_AUTH'];
}

error_log("Authorization header: " . substr($auth_header, 0, 20) . "...");

if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing authorization', 'debug' => 'No Bearer token found in authorization header']);
    exit;
}

$token = $matches[1];
$valid_tokens = [
    'admin_quest_system',
    getenv('DISCORD_BOT_SECRET') // Use DISCORD_BOT_SECRET as valid token
];

// Remove empty values from valid_tokens array
$valid_tokens = array_filter($valid_tokens);

error_log("Received token length: " . strlen($token));
error_log("Valid tokens count: " . count($valid_tokens));
error_log("Bot secret length: " . strlen(getenv('DISCORD_BOT_SECRET')));

if (!in_array($token, $valid_tokens)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid authorization', 'received_token_length' => strlen($token)]);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$user_id = $input['user_id'] ?? '';
$role_id = $input['role_id'] ?? '';

error_log("grant-role.php called: action=$action, user_id=$user_id, role_id=$role_id");

if (($action !== 'add_role' && $action !== 'remove_role') || !$user_id || !$role_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters or invalid action']);
    exit;
}

// Discord API configuration
$discord_guild_id = getenv('DISCORD_GUILD') ?: '1332015322546311218'; // Use DISCORD_GUILD instead of DISCORD_GUILD_ID
$discord_bot_token = getenv('DISCORD_BOT_SECRET'); // Use DISCORD_BOT_SECRET instead of DISCORD_BOT_TOKEN

error_log("Discord config: guild_id=$discord_guild_id, bot_token=" . (empty($discord_bot_token) ? 'MISSING' : 'SET'));

if (!$discord_bot_token) {
    http_response_code(500);
    echo json_encode(['error' => 'Discord bot token not configured']);
    exit;
}

try {
    // Add or remove role to/from user via Discord API
    $discord_api_url = "https://discord.com/api/v10/guilds/{$discord_guild_id}/members/{$user_id}/roles/{$role_id}";
    
    error_log("Making Discord API call to: $discord_api_url for action: $action");
    error_log("Bot token length: " . strlen($discord_bot_token));
    error_log("Bot token preview: " . substr($discord_bot_token, 0, 10) . "...");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $discord_api_url);
    
    // Use PUT for adding role, DELETE for removing role
    if ($action === 'add_role') {
        curl_setopt($ch, CURLOPT_PUT, true);
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bot ' . $discord_bot_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    // Capture verbose output
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);
    
    // Get verbose output
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    fclose($verbose);
    
    error_log("Discord API response: HTTP $http_code, Response: $response, Curl error: $curl_error");
    error_log("Curl info: " . json_encode($curl_info));
    error_log("Verbose curl log: " . $verbose_log);
    
    if ($http_code === 204) {
        // Success - role added or removed
        $action_text = $action === 'add_role' ? 'granted' : 'removed';
        error_log("Discord role $action_text successfully: User $user_id, Role $role_id");
        echo json_encode([
            'success' => true,
            'message' => "Role $action_text successfully",
            'user_id' => $user_id,
            'role_id' => $role_id,
            'action' => $action
        ]);
    } else {
        // Error from Discord API
        error_log("Discord API error: HTTP $http_code, Response: $response");
        echo json_encode([
            'success' => false,
            'error' => 'Discord API error',
            'http_code' => $http_code,
            'response' => $response,
            'action' => $action,
            'details' => [
                'url' => $discord_api_url,
                'guild_id' => $discord_guild_id,
                'user_id' => $user_id,
                'role_id' => $role_id,
                'bot_token_length' => strlen($discord_bot_token)
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("Role grant error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'details' => $e->getMessage()
    ]);
}
?>