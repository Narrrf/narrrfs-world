<?php
// 🔍 Discord OAuth Debug Page
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord OAuth Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Discord OAuth Debug Information</h1>
        
        <h2>Environment Variables</h2>
        <div class="status <?php echo getenv('DISCORD_CLIENT_ID') ? 'success' : 'error'; ?>">
            DISCORD_CLIENT_ID: <?php echo getenv('DISCORD_CLIENT_ID') ? '✅ Set' : '❌ Missing'; ?>
        </div>
        <div class="status <?php echo getenv('DISCORD_SECRET') ? 'success' : 'error'; ?>">
            DISCORD_SECRET: <?php echo getenv('DISCORD_SECRET') ? '✅ Set' : '❌ Missing'; ?>
        </div>
        <div class="status <?php echo getenv('DISCORD_GUILD') ? 'success' : 'warning'; ?>">
            DISCORD_GUILD: <?php echo getenv('DISCORD_GUILD') ?: '⚠️ Using default'; ?>
        </div>
        <div class="status <?php echo getenv('DISCORD_BOT_SECRET') ? 'success' : 'warning'; ?>">
            DISCORD_BOT_SECRET: <?php echo getenv('DISCORD_BOT_SECRET') ? '✅ Set' : '❌ Missing'; ?>
        </div>

        <h2>OAuth Configuration</h2>
        <div class="status info">
            <strong>Redirect URI:</strong> https://narrrfs.world/api/auth/callback.php<br>
            <strong>Current URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?><br>
            <strong>Server Name:</strong> <?php echo $_SERVER['SERVER_NAME']; ?><br>
            <strong>HTTPS:</strong> <?php echo isset($_SERVER['HTTPS']) ? 'Yes' : 'No'; ?>
        </div>

        <h2>OAuth URL Test</h2>
        <div class="status info">
            <strong>Generated OAuth URL:</strong><br>
            <pre><?php 
                $clientId = getenv('DISCORD_CLIENT_ID') ?: '1357927342265204858';
                $redirectUri = urlencode('https://narrrfs.world/api/auth/callback.php');
                $oauthUrl = "https://discord.com/oauth2/authorize?client_id={$clientId}&response_type=code&redirect_uri={$redirectUri}&scope=identify+guilds+guilds.members.read";
                echo htmlspecialchars($oauthUrl);
            ?></pre>
        </div>

        <h2>Common Issues & Solutions</h2>
        <div class="status warning">
            <h3>❌ "invalid_grant" Error</h3>
            <p><strong>Cause:</strong> Authorization code has expired or was already used</p>
            <p><strong>Solution:</strong> Try logging in again - codes are single-use and expire quickly</p>
        </div>
        
        <div class="status warning">
            <h3>❌ "redirect_uri_mismatch" Error</h3>
            <p><strong>Cause:</strong> Redirect URI in Discord OAuth app doesn't match exactly</p>
            <p><strong>Solution:</strong> Ensure Discord OAuth app has: <code>https://narrrfs.world/api/auth/callback.php</code></p>
        </div>

        <div class="status warning">
            <h3>❌ "invalid_client" Error</h3>
            <p><strong>Cause:</strong> Client ID or secret is incorrect</p>
            <p><strong>Solution:</strong> Check DISCORD_CLIENT_ID and DISCORD_SECRET environment variables</p>
        </div>

        <h2>Test OAuth Flow</h2>
        <div class="status info">
            <a href="<?php echo htmlspecialchars($oauthUrl); ?>" class="button" style="background: #7289da; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
                🔗 Test Discord OAuth Login
            </a>
        </div>

        <h2>Session Information</h2>
        <div class="status info">
            <pre><?php 
                session_start();
                echo "Session ID: " . session_id() . "\n";
                echo "Session Data: " . print_r($_SESSION, true);
            ?></pre>
        </div>

        <h2>Error Logs</h2>
        <div class="status info">
            <p>Check your server's error logs for detailed OAuth errors. Common locations:</p>
            <ul>
                <li>Apache: <code>/var/log/apache2/error.log</code></li>
                <li>Nginx: <code>/var/log/nginx/error.log</code></li>
                <li>PHP: <code>/var/log/php_errors.log</code></li>
            </ul>
        </div>
    </div>
</body>
</html>
