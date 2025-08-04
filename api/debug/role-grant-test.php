<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Test role granting system
function testRoleGrant() {
    // Test data
    $test_user_id = '123456789012345678'; // Replace with actual test user ID
    $test_role_id = '1399651053682692208'; // Cheese Hunter role
    
    // Get the actual bot secret for authorization
    $bot_secret = getenv('DISCORD_BOT_SECRET');
    if (!$bot_secret) {
        echo "ERROR: DISCORD_BOT_SECRET environment variable is not set!\n";
        return ['error' => 'Bot secret not configured'];
    }
    
    // First, test the auth-test.php endpoint
    echo "Testing authorization header...\n";
    $auth_test_url = 'https://narrrfs.world/api/debug/auth-test.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $auth_test_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => 'auth']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $bot_secret
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $auth_response = curl_exec($ch);
    $auth_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Auth Test HTTP Code: $auth_http_code\n";
    echo "Auth Test Response: $auth_response\n\n";
    
    // Now test the grant-role.php endpoint
    echo "Testing role grant system...\n";
    $bot_api_url = 'https://narrrfs.world/api/discord/grant-role.php';
    
    $data = [
        'user_id' => $test_user_id,
        'role_id' => $test_role_id,
        'action' => 'add_role'
    ];
    
    echo "URL: $bot_api_url\n";
    echo "Data: " . json_encode($data) . "\n";
    echo "Using bot secret for authorization: " . (strlen($bot_secret) > 10 ? substr($bot_secret, 0, 10) . '...' : 'SHORT') . "\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $bot_api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $bot_secret
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $http_code\n";
    echo "Response: $response\n";
    echo "Curl Error: $curl_error\n";
    
    return [
        'auth_test' => [
            'http_code' => $auth_http_code,
            'response' => $auth_response
        ],
        'role_grant' => [
            'http_code' => $http_code,
            'response' => $response,
            'curl_error' => $curl_error
        ]
    ];
}

// Test environment variables
function testEnvironmentVariables() {
    echo "Environment Variables Test:\n";
    echo "DISCORD_BOT_SECRET: " . (getenv('DISCORD_BOT_SECRET') ? 'SET' : 'MISSING') . "\n";
    echo "DISCORD_GUILD: " . (getenv('DISCORD_GUILD') ? 'SET' : 'MISSING') . "\n";
    echo "DISCORD_CLIENT_ID: " . (getenv('DISCORD_CLIENT_ID') ? 'SET' : 'MISSING') . "\n";
    echo "DISCORD_SECRET: " . (getenv('DISCORD_SECRET') ? 'SET' : 'MISSING') . "\n";
    echo "DISCORD_BASE_URL: " . (getenv('DISCORD_BASE_URL') ? 'SET' : 'MISSING') . "\n";
    echo "API_URL: " . (getenv('API_URL') ? 'SET' : 'MISSING') . "\n";
    echo "DISCORD_INVITE_CODE: " . (getenv('DISCORD_INVITE_CODE') ? 'SET' : 'MISSING') . "\n";
    echo "Note: Only using environment variables from Render screenshot\n";
}

// Run tests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "=== Role Grant System Debug Test ===\n\n";
    
    echo "1. Environment Variables:\n";
    testEnvironmentVariables();
    echo "\n";
    
    echo "2. Role Grant Test:\n";
    $result = testRoleGrant();
    
    echo "\n=== Test Complete ===\n";
} else {
    echo json_encode(['error' => 'Use GET method to run tests']);
}
?>