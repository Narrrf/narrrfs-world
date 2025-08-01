<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Discord Bot Token and role configuration
$DISCORD_BOT_TOKEN = 'YOUR_DISCORD_BOT_TOKEN'; // Replace with your actual bot token
$MODERATOR_ROLE_ID = '1332049628300054679'; // Moderator role ID from role_map.php
$GUILD_ID = '1332015322546311218'; // Guild ID from sync-role.php

// Simple admin users storage (in production, you'd want to use a database)
$admin_users = [
    'narrrf' => [
        'password' => $_ENV['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?? 'PnoRakesucks&2025', // Use environment variable
        'role' => 'super_admin',
        'discord_id' => '328601656659017732'
    ]
];

// Load additional users from file if exists
$users_file = __DIR__ . '/admin_users.json';
if (file_exists($users_file)) {
    $additional_users = json_decode(file_get_contents($users_file), true);
    if ($additional_users) {
        $admin_users = array_merge($admin_users, $additional_users);
    }
}

// Function to check Discord moderator role
function checkDiscordModeratorRole($discord_user_id) {
    global $DISCORD_BOT_TOKEN, $MODERATOR_ROLE_ID, $GUILD_ID;
    
    if (!$discord_user_id || $DISCORD_BOT_TOKEN === 'YOUR_DISCORD_BOT_TOKEN') {
        // For testing purposes, allow access if no proper setup
        return true;
    }
    
    // Make Discord API call to get user's roles
    $url = "https://discord.com/api/v10/guilds/{$GUILD_ID}/members/{$discord_user_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot {$DISCORD_BOT_TOKEN}",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $member_data = json_decode($response, true);
        if (isset($member_data['roles']) && in_array($MODERATOR_ROLE_ID, $member_data['roles'])) {
            return true;
        }
    }
    
    return false;
}

// Function to get Discord username
function getDiscordUsername($discord_user_id) {
    global $DISCORD_BOT_TOKEN;
    
    if (!$discord_user_id || $DISCORD_BOT_TOKEN === 'YOUR_DISCORD_BOT_TOKEN') {
        return 'Discord Moderator';
    }
    
    $url = "https://discord.com/api/v10/users/{$discord_user_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot {$DISCORD_BOT_TOKEN}",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $user_data = json_decode($response, true);
        return $user_data['username'] ?? 'Discord Moderator';
    }
    
    return 'Discord Moderator';
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (isset($admin_users[$username]) && $admin_users[$username]['password'] === $password) {
            $user = $admin_users[$username];
            echo json_encode([
                'success' => true,
                'user' => [
                    'username' => $username,
                    'role' => $user['role'],
                    'discord_id' => $user['discord_id'],
                    'auth_type' => 'password'
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid username or password'
            ]);
        }
        break;
        
    case 'discord_auth':
        $discord_user_id = $_POST['discord_user_id'] ?? $_GET['discord_user_id'] ?? '';
        
        if (!$discord_user_id) {
            echo json_encode([
                'success' => false,
                'error' => 'Discord user ID is required'
            ]);
            break;
        }
        
        if (checkDiscordModeratorRole($discord_user_id)) {
            $username = getDiscordUsername($discord_user_id);
            echo json_encode([
                'success' => true,
                'user' => [
                    'username' => $username,
                    'discord_id' => $discord_user_id,
                    'role' => 'moderator',
                    'auth_type' => 'discord'
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'You do not have the required moderator role to access this interface.'
            ]);
        }
        break;
        
    case 'add_user':
        // Only super admin can add users
        $admin_username = $_POST['admin_username'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        
        if (!isset($admin_users[$admin_username]) || $admin_users[$admin_username]['password'] !== $admin_password || $admin_users[$admin_username]['role'] !== 'super_admin') {
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized - Super admin access required'
            ]);
            break;
        }
        
        $new_username = $_POST['new_username'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $new_role = $_POST['new_role'] ?? 'moderator';
        $new_discord_id = $_POST['new_discord_id'] ?? '';
        
        if (empty($new_username) || empty($new_password)) {
            echo json_encode([
                'success' => false,
                'error' => 'Username and password are required'
            ]);
            break;
        }
        
        // Add new user
        $admin_users[$new_username] = [
            'password' => $new_password,
            'role' => $new_role,
            'discord_id' => $new_discord_id
        ];
        
        // Save to file
        file_put_contents($users_file, json_encode($admin_users, JSON_PRETTY_PRINT));
        
        echo json_encode([
            'success' => true,
            'message' => "User '$new_username' added successfully"
        ]);
        break;
        
    case 'list_users':
        // Only super admin can list users
        $admin_username = $_POST['admin_username'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        
        if (!isset($admin_users[$admin_username]) || $admin_users[$admin_username]['password'] !== $admin_password || $admin_users[$admin_username]['role'] !== 'super_admin') {
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized - Super admin access required'
            ]);
            break;
        }
        
        $users_list = [];
        foreach ($admin_users as $username => $user) {
            $users_list[] = [
                'username' => $username,
                'role' => $user['role'],
                'discord_id' => $user['discord_id']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'users' => $users_list
        ]);
        break;
        
    case 'remove_user':
        // Only super admin can remove users
        $admin_username = $_POST['admin_username'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        
        if (!isset($admin_users[$admin_username]) || $admin_users[$admin_username]['password'] !== $admin_password || $admin_users[$admin_username]['role'] !== 'super_admin') {
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized - Super admin access required'
            ]);
            break;
        }
        
        $remove_username = $_POST['remove_username'] ?? '';
        
        if ($remove_username === 'narrrf') {
            echo json_encode([
                'success' => false,
                'error' => 'Cannot remove super admin account'
            ]);
            break;
        }
        
        if (isset($admin_users[$remove_username])) {
            unset($admin_users[$remove_username]);
            file_put_contents($users_file, json_encode($admin_users, JSON_PRETTY_PRINT));
            
            echo json_encode([
                'success' => true,
                'message' => "User '$remove_username' removed successfully"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'User not found'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
        break;
}
?> 