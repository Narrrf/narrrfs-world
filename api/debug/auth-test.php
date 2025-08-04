<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

echo "=== Authorization Debug Test ===\n\n";

echo "1. All Headers:\n";
foreach (getallheaders() as $name => $value) {
    echo "   $name: " . substr($value, 0, 50) . "...\n";
}

echo "\n2. Authorization Header Access Methods:\n";
$headers = getallheaders();
echo "   getallheaders()['Authorization']: " . (isset($headers['Authorization']) ? substr($headers['Authorization'], 0, 50) . "..." : 'NOT_SET') . "\n";
echo "   getallheaders()['authorization']: " . (isset($headers['authorization']) ? substr($headers['authorization'], 0, 50) . "..." : 'NOT_SET') . "\n";
echo "   \$_SERVER['HTTP_AUTHORIZATION']: " . (isset($_SERVER['HTTP_AUTHORIZATION']) ? substr($_SERVER['HTTP_AUTHORIZATION'], 0, 50) . "..." : 'NOT_SET') . "\n";
echo "   \$_SERVER['HTTP_AUTH']: " . (isset($_SERVER['HTTP_AUTH']) ? substr($_SERVER['HTTP_AUTH'], 0, 50) . "..." : 'NOT_SET') . "\n";

// Use the same logic as grant-role.php
$auth_header = '';
if (isset($headers['Authorization'])) {
    $auth_header = $headers['Authorization'];
} elseif (isset($headers['authorization'])) {
    $auth_header = $headers['authorization'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['HTTP_AUTH'])) {
    $auth_header = $_SERVER['HTTP_AUTH'];
}

echo "   Final auth_header: " . (empty($auth_header) ? 'NOT_SET' : substr($auth_header, 0, 50) . "...") . "\n";

echo "\n3. Request Method:\n";
echo "   Method: " . $_SERVER['REQUEST_METHOD'] . "\n";

echo "\n4. Environment Variables:\n";
echo "   DISCORD_BOT_SECRET: " . (getenv('DISCORD_BOT_SECRET') ? 'SET (' . strlen(getenv('DISCORD_BOT_SECRET')) . ' chars)' : 'MISSING') . "\n";

echo "\n5. Test Bearer Token Validation:\n";
if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    $token = $matches[1];
    $bot_secret = getenv('DISCORD_BOT_SECRET');
    
    echo "   Token received: " . substr($token, 0, 20) . "...\n";
    echo "   Token length: " . strlen($token) . "\n";
    echo "   Bot secret length: " . strlen($bot_secret) . "\n";
    echo "   Tokens match: " . ($token === $bot_secret ? 'YES' : 'NO') . "\n";
} else {
    echo "   No Bearer token found in authorization header\n";
}

echo "\n=== Test Complete ===\n";
?>