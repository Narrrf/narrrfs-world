<?php
// 📱 MOBILE AUTHENTICATION CHECK API
// Provides robust authentication status for mobile devices

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');

// 🚀 EXTENDED SESSION LIFETIME FOR MOBILE COMPATIBILITY
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.cookie_lifetime', 86400); // 24 hours
session_start();

try {
    // Check if user is authenticated
    if (!isset($_SESSION['discord_id']) || empty($_SESSION['discord_id'])) {
        throw new Exception('Not authenticated');
    }
    
    $discordId = $_SESSION['discord_id'];
    $username = $_SESSION['user']['username'] ?? 'Unknown';
    
    // Check if token is expired
    $tokenExpired = false;
    if (isset($_SESSION['token_expires_at']) && $_SESSION['token_expires_at'] <= time()) {
        $tokenExpired = true;
    }
    
    // Return comprehensive auth status
    echo json_encode([
        'success' => true,
        'authenticated' => true,
        'discord_id' => $discordId,
        'username' => $username,
        'token_expired' => $tokenExpired,
        'session_lifetime' => 86400, // 24 hours
        'mobile_optimized' => true,
        'message' => 'User authenticated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'authenticated' => false,
        'error' => $e->getMessage(),
        'action' => 'Please login with Discord',
        'mobile_optimized' => true
    ]);
}
?>
