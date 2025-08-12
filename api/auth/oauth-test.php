<?php
/**
 * 🔬 Discord OAuth Test Page
 * 
 * This page helps test and debug Discord OAuth2 authentication
 * Use this to verify OAuth flow is working correctly
 */

header('Content-Type: text/html; charset=utf-8');
session_start();

// Get environment variables
$clientId = getenv('DISCORD_CLIENT_ID') ?: '1357927342265204858';
$clientSecret = getenv('DISCORD_SECRET');
$redirectUri = 'https://narrrfs.world/api/auth/callback.php';

// Check if we have a code parameter (OAuth callback)
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    echo "<h2>🔍 OAuth Code Received</h2>";
    echo "<p><strong>Code:</strong> " . htmlspecialchars($code) . "</p>";
    
    // Test token exchange
    $tokenRequest = curl_init();
    curl_setopt_array($tokenRequest, [
        CURLOPT_URL => 'https://discord.com/api/oauth2/token',
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'scope' => 'guilds+identify+guilds.members.read'
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-form-urlencoded']
    ]);
    
    $response = curl_exec($tokenRequest);
    $httpCode = curl_getinfo($tokenRequest, CURLINFO_HTTP_CODE);
    curl_close($tokenRequest);
    
    echo "<h3>🔑 Token Exchange Result</h3>";
    echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $token = json_decode($response, true);
    
    if (isset($token['access_token'])) {
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
        echo "✅ <strong>SUCCESS!</strong> Access token received successfully!";
        echo "</div>";
        
        // Test user info fetch
        $userRequest = curl_init();
        curl_setopt_array($userRequest, [
            CURLOPT_URL => 'https://discord.com/api/v10/users/@me',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $token['access_token']
            ]
        ]);
        
        $userResponse = curl_exec($userRequest);
        $userHttpCode = curl_getinfo($userRequest, CURLINFO_HTTP_CODE);
        curl_close($userRequest);
        
        echo "<h3>👤 User Info Test</h3>";
        echo "<p><strong>HTTP Code:</strong> " . $userHttpCode . "</p>";
        echo "<p><strong>Response:</strong></p>";
        echo "<pre>" . htmlspecialchars($userResponse) . "</pre>";
        
        $user = json_decode($userResponse, true);
        if (isset($user['id'])) {
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
            echo "✅ <strong>SUCCESS!</strong> User info fetched successfully!";
            echo "<br><strong>Username:</strong> " . htmlspecialchars($user['username']);
            echo "<br><strong>Discord ID:</strong> " . htmlspecialchars($user['id']);
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
            echo "❌ <strong>FAILED!</strong> Could not fetch user info";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
        echo "❌ <strong>FAILED!</strong> Could not get access token";
        if (isset($token['error'])) {
            echo "<br><strong>Error:</strong> " . htmlspecialchars($token['error']);
            if (isset($token['error_description'])) {
                echo "<br><strong>Description:</strong> " . htmlspecialchars($token['error_description']);
            }
        }
        echo "</div>";
    }
    
} else {
    // Show OAuth login link
    $oauthUrl = "https://discord.com/oauth2/authorize?client_id=" . urlencode($clientId) . 
                "&response_type=code&redirect_uri=" . urlencode($redirectUri) . 
                "&scope=" . urlencode('guilds+identify+guilds.members.read');
    
    echo "<h1>🔬 Discord OAuth Test Page</h1>";
    echo "<p>This page helps test Discord OAuth2 authentication flow.</p>";
    
    echo "<h2>🔧 Configuration</h2>";
    echo "<p><strong>Client ID:</strong> " . htmlspecialchars($clientId) . "</p>";
    echo "<p><strong>Client Secret:</strong> " . ($clientSecret ? '✅ Set' : '❌ Missing') . "</p>";
    echo "<p><strong>Redirect URI:</strong> " . htmlspecialchars($redirectUri) . "</p>";
    
    echo "<h2>🔗 Test OAuth Flow</h2>";
    echo "<p>Click the button below to test the Discord OAuth flow:</p>";
    echo "<a href='" . htmlspecialchars($oauthUrl) . "' style='background: #7289da; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-size: 18px;'>";
    echo "🔗 Test Discord OAuth Login";
    echo "</a>";
    
    echo "<h2>📋 OAuth URL</h2>";
    echo "<p><strong>Generated URL:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
    echo htmlspecialchars($oauthUrl);
    echo "</pre>";
    
    echo "<h2>🔍 Debug Information</h2>";
    echo "<p><strong>Current URL:</strong> " . htmlspecialchars($_SERVER['REQUEST_URI']) . "</p>";
    echo "<p><strong>Server Name:</strong> " . htmlspecialchars($_SERVER['SERVER_NAME']) . "</p>";
    echo "<p><strong>HTTPS:</strong> " . (isset($_SERVER['HTTPS']) ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord OAuth Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5; 
            line-height: 1.6;
        }
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        h1, h2, h3 { color: #333; }
        pre { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 4px; 
            overflow-x: auto; 
            border: 1px solid #e9ecef;
        }
        .button {
            background: #7289da;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            font-size: 18px;
            margin: 10px 0;
        }
        .button:hover {
            background: #5b6eae;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isset($_GET['code'])): ?>
            <h1>🔬 Discord OAuth Test Page</h1>
            <p>This page helps test Discord OAuth2 authentication flow.</p>
            
            <h2>🔧 Configuration</h2>
            <div class="info">
                <p><strong>Client ID:</strong> <?php echo htmlspecialchars($clientId); ?></p>
                <p><strong>Client Secret:</strong> <?php echo ($clientSecret ? '✅ Set' : '❌ Missing'); ?></p>
                <p><strong>Redirect URI:</strong> <?php echo htmlspecialchars($redirectUri); ?></p>
            </div>
            
            <h2>🔗 Test OAuth Flow</h2>
            <p>Click the button below to test the Discord OAuth flow:</p>
            <a href="<?php echo htmlspecialchars($oauthUrl); ?>" class="button">
                🔗 Test Discord OAuth Login
            </a>
            
            <h2>📋 OAuth URL</h2>
            <p><strong>Generated URL:</strong></p>
            <pre><?php echo htmlspecialchars($oauthUrl); ?></pre>
            
            <h2>🔍 Debug Information</h2>
            <div class="info">
                <p><strong>Current URL:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
                <p><strong>Server Name:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_NAME']); ?></p>
                <p><strong>HTTPS:</strong> <?php echo (isset($_SERVER['HTTPS']) ? 'Yes' : 'No'); ?></p>
                <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
