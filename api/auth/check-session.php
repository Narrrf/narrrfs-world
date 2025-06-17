<?php
header('Content-Type: application/json');
session_start();

// Debug: Log session data
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in and has admin role
if (isset($_SESSION['user']) && isset($_SESSION['discord_id'])) {
    // Get the user's roles from the database
    require_once __DIR__ . '/../config/discord.php';
    $db = new SQLite3('/var/www/html/db/narrrf_world.sqlite');
    
    $stmt = $db->prepare('SELECT roles FROM discord_users WHERE discord_id = :discord_id');
    $stmt->bindValue(':discord_id', $_SESSION['discord_id'], SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    // Debug: Log database query results
    error_log("Database query for discord_id " . $_SESSION['discord_id'] . ": " . print_r($row, true));
    
    if ($row && strpos($row['roles'], 'admin') !== false) {
        // User is an admin
        $response = [
            'success' => true,
            'user' => [
                'id' => $_SESSION['discord_id'],
                'username' => $_SESSION['user']['username'],
                'avatar' => $_SESSION['user']['avatar'],
                'roles' => $row['roles']
            ]
        ];
        error_log("Sending success response: " . print_r($response, true));
        echo json_encode($response);
        exit;
    }
}

// Not logged in or not an admin
$response = [
    'success' => false,
    'error' => 'Not authenticated or not an admin'
];
error_log("Sending error response: " . print_r($response, true));
echo json_encode($response); 