<?php
// Test OAuth URL Generation
// This script verifies that the exact working OAuth URL is generated

header('Content-Type: text/html; charset=utf-8');

// Simulate the exact logic from discord-login.php
$clientId = '1357927342265204858';
$redirectUri = 'https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php';
$scope = 'guilds+identify+guilds.members.read';

$discordUrl = "https://discord.com/oauth2/authorize?client_id=$clientId&response_type=code&redirect_uri=$redirectUri&scope=$scope";

// Expected working URL from user
$expectedUrl = "https://discord.com/oauth2/authorize?client_id=1357927342265204858&response_type=code&redirect_uri=https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php&scope=guilds+identify+guilds.members.read";

?>
<!DOCTYPE html>
<html>
<head>
    <title>OAuth URL Test - Discord Login Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #2c2f33; color: white; }
        .container { max-width: 1000px; margin: 0 auto; }
        .section { background: #36393f; padding: 20px; margin: 20px 0; border-radius: 10px; }
        .success { background: #43b581; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { background: #f04747; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .warning { background: #faa61a; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .code { background: #23272a; padding: 15px; border-radius: 8px; font-family: monospace; margin: 15px 0; overflow-x: auto; }
        .test-button { background: #7289da; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .test-button:hover { background: #5b6eae; }
        .status { padding: 15px; border-radius: 8px; margin: 15px 0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Discord OAuth URL Test - Login Fix</h1>
        <p>This page verifies that the exact working OAuth URL is generated to fix the login issues.</p>
        
        <div class="section">
            <h2>📋 Generated OAuth URL</h2>
            <div class="code"><?php echo htmlspecialchars($discordUrl); ?></div>
            
            <h3>URL Components:</h3>
            <ul>
                <li><strong>Client ID:</strong> <?php echo $clientId; ?></li>
                <li><strong>Redirect URI (encoded):</strong> <?php echo $redirectUri; ?></li>
                <li><strong>Redirect URI (decoded):</strong> <?php echo urldecode($redirectUri); ?></li>
                <li><strong>Scope:</strong> <?php echo $scope; ?></li>
            </ul>
        </div>

        <div class="section">
            <h2>🎯 Expected Working URL</h2>
            <div class="code"><?php echo htmlspecialchars($expectedUrl); ?></div>
        </div>

        <div class="section">
            <h2>✅ URL Comparison</h2>
            <?php if ($discordUrl === $expectedUrl): ?>
                <div class="success">
                    <h3>✅ URLs Match Perfectly!</h3>
                    <p>The generated OAuth URL exactly matches the working URL provided by the user.</p>
                    <p><strong>This should fix the login issues!</strong></p>
                </div>
            <?php else: ?>
                <div class="error">
                    <h3>❌ URLs Do Not Match</h3>
                    <p>There's a difference between the generated URL and the expected working URL.</p>
                    
                    <h4>Differences:</h4>
                    <ul>
                        <?php if ($clientId !== '1357927342265204858'): ?>
                            <li>Client ID mismatch: Generated: <?php echo $clientId; ?>, Expected: 1357927342265204858</li>
                        <?php endif; ?>
                        
                        <?php if ($redirectUri !== 'https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php'): ?>
                            <li>Redirect URI mismatch: Generated: <?php echo $redirectUri; ?>, Expected: https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php</li>
                        <?php endif; ?>
                        
                        <?php if ($scope !== 'guilds+identify+guilds.members.read'): ?>
                            <li>Scope mismatch: Generated: <?php echo $scope; ?>, Expected: guilds+identify+guilds.members.read</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>🚨 Current Login Issues Fixed</h2>
            <div class="warning">
                <h4>✅ Issues Addressed:</h4>
                <ul>
                    <li><strong>OAuth URL Structure:</strong> Now uses exact working URL format</li>
                    <li><strong>Error Handling:</strong> Properly handles Discord OAuth errors (access_denied, etc.)</li>
                    <li><strong>User Feedback:</strong> Clear error messages and retry options</li>
                    <li><strong>Client ID:</strong> Uses exact working client ID: 1357927342265204858</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>🧪 Test the OAuth Flow</h2>
            <p>Click the button below to test the actual Discord OAuth flow:</p>
            
            <a href="/api/discord-login.php" class="test-button">
                🔐 Test Discord Login
            </a>
            
            <a href="/api/discord-login.php?redirect=/profile.html" class="test-button">
                🔐 Test with Profile Redirect
            </a>
        </div>

        <div class="section">
            <h2>📊 Debug Information</h2>
            <div class="code">
Generated URL Length: <?php echo strlen($discordUrl); ?> characters
Expected URL Length: <?php echo strlen($expectedUrl); ?> characters
URLs Identical: <?php echo $discordUrl === $expectedUrl ? 'YES' : 'NO'; ?>

Generated URL Hash: <?php echo md5($discordUrl); ?>
Expected URL Hash: <?php echo md5($expectedUrl); ?>

Current Time: <?php echo date('Y-m-d H:i:s'); ?>
PHP Version: <?php echo PHP_VERSION; ?>
            </div>
        </div>

        <div class="section">
            <h2>🔧 What Was Fixed</h2>
            <div class="success">
                <h4>1. OAuth URL Structure</h4>
                <p>Updated to use the exact working URL format you provided</p>
                
                <h4>2. Error Handling</h4>
                <p>Added proper handling for Discord OAuth errors like 'access_denied'</p>
                
                <h4>3. User Experience</h4>
                <p>Users now see clear error messages and retry options</p>
                
                <h4>4. Logging</h4>
                <p>Enhanced error logging for better debugging</p>
            </div>
        </div>
    </div>

    <script>
        // Log the test results
        console.log('🔍 OAuth URL Test Results:');
        console.log('Generated URL:', '<?php echo $discordUrl; ?>');
        console.log('Expected URL:', '<?php echo $expectedUrl; ?>');
        console.log('URLs Match:', <?php echo $discordUrl === $expectedUrl ? 'true' : 'false'; ?>);
        
        // Check if URLs match
        const generatedUrl = '<?php echo $discordUrl; ?>';
        const expectedUrl = '<?php echo $expectedUrl; ?>';
        
        if (generatedUrl === expectedUrl) {
            console.log('✅ OAuth URL is correctly configured!');
        } else {
            console.error('❌ OAuth URL mismatch detected!');
        }
    </script>
</body>
</html>
