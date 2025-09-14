<?php
// 🔄 DISCORD TOKEN REFRESH API
// Handles automatic token refresh for mobile compatibility

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');

// 🚀 EXTENDED SESSION LIFETIME FOR MOBILE COMPATIBILITY
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.cookie_lifetime', 86400); // 24 hours
session_start();

// ✅ Load Discord OAuth credentials
$clientId = '1357927342265204858';
$clientSecret = getenv('DISCORD_SECRET');

try {
    // Check if user has refresh token
    if (!isset($_SESSION['refresh_token']) || empty($_SESSION['refresh_token'])) {
        throw new Exception('No refresh token available');
    }
    
    // Check if token is actually expired
    if (isset($_SESSION['token_expires_at']) && $_SESSION['token_expires_at'] > time()) {
        echo json_encode([
            'success' => true,
            'message' => 'Token still valid',
            'expires_in' => $_SESSION['token_expires_at'] - time()
        ]);
        exit;
    }
    
    // Refresh the token
    $refreshRequest = curl_init();
    curl_setopt_array($refreshRequest, [
        CURLOPT_URL => 'https://discord.com/api/oauth2/token',
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $_SESSION['refresh_token']
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    $response = curl_exec($refreshRequest);
    $httpCode = curl_getinfo($refreshRequest, CURLINFO_HTTP_CODE);
    curl_close($refreshRequest);
    
    if ($httpCode !== 200) {
        throw new Exception('Token refresh failed: HTTP ' . $httpCode);
    }
    
    $token = json_decode($response, true);
    
    if (!isset($token['access_token'])) {
        throw new Exception('Invalid refresh response');
    }
    
    // Update session with new token
    $_SESSION['access_token'] = $token['access_token'];
    $_SESSION['token_expires_at'] = time() + ($token['expires_in'] ?? 3600);
    
    if (isset($token['refresh_token'])) {
        $_SESSION['refresh_token'] = $token['refresh_token'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Token refreshed successfully',
        'expires_in' => $token['expires_in'] ?? 3600
    ]);
    
} catch (Exception $e) {
    // Clear invalid session data
    unset($_SESSION['access_token']);
    unset($_SESSION['refresh_token']);
    unset($_SESSION['token_expires_at']);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'action' => 'Please login again'
    ]);
}
?>
