<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

try {
    // Start session to check authentication
    session_start();
    
    // Check if user is authenticated
    if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
        // User is authenticated
        $user = $_SESSION['discord_user'] ?? null;
        $role = $_SESSION['user_role'] ?? 'moderator';
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'discord_id' => $user['id'],
                    'discord_name' => $user['username'],
                    'avatar_url' => $user['avatar'] ?? null
                ],
                'role' => $role,
                'message' => 'User authenticated via Discord session'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Session data incomplete'
            ]);
        }
    } else {
        // User not authenticated
        echo json_encode([
            'success' => false,
            'error' => 'No active session'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Session check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Session check failed'
    ]);
}
?> 