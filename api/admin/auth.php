<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Discord Bot Token and role configuration - Use environment variables
$DISCORD_BOT_SECRET = getenv('DISCORD_BOT_SECRET'); // Use DISCORD_BOT_SECRET environment variable
$MODERATOR_ROLE_ID = '1332049628300054679'; // Moderator role ID from role_map.php
$GUILD_ID = getenv('DISCORD_GUILD') ?: '1332015322546311218'; // Use DISCORD_GUILD environment variable

// Secure admin users storage using environment variables
$admin_users = [];

// Load admin credentials from environment variables
$admin_username = getenv('ADMIN_USERNAME') ?: 'narrrf';
$admin_password_hash = getenv('ADMIN_PASSWORD_HASH'); // Should be bcrypt hash
$admin_discord_id = getenv('ADMIN_DISCORD_ID') ?: '328601656659017732';

// If environment variables are set, use them; otherwise, use fallback (for development only)
if ($admin_password_hash) {
    $admin_users[$admin_username] = [
        'password_hash' => $admin_password_hash,
        'role' => 'super_admin',
        'discord_id' => $admin_discord_id
    ];
} else {
    // Fallback for development - REMOVE IN PRODUCTION
    $admin_users[$admin_username] = [
        'password_hash' => password_hash('PnoRakesucks&2025', PASSWORD_DEFAULT),
        'role' => 'super_admin',
        'discord_id' => $admin_discord_id
    ];
}

// Load additional users from file if exists (with validation)
$users_file = __DIR__ . '/admin_users.json';
if (file_exists($users_file)) {
    $file_content = file_get_contents($users_file);
    if ($file_content !== false) {
        $additional_users = json_decode($file_content, true);
        if ($additional_users && is_array($additional_users)) {
            // Validate and hash passwords for additional users
            foreach ($additional_users as $username => $user_data) {
                if (isset($user_data['password']) && !isset($user_data['password_hash'])) {
                    // Hash plain text passwords
                    $additional_users[$username]['password_hash'] = password_hash($user_data['password'], PASSWORD_DEFAULT);
                    unset($additional_users[$username]['password']);
                }
            }
            $admin_users = array_merge($admin_users, $additional_users);
        }
    }
}

// Function to check Discord moderator role
function checkDiscordModeratorRole($discord_user_id) {
    global $DISCORD_BOT_SECRET, $MODERATOR_ROLE_ID, $GUILD_ID;
    
    if (!$discord_user_id || !$DISCORD_BOT_SECRET) {
        // For testing purposes, allow access if no proper setup
        return true;
    }
    
    // Make Discord API call to get user's roles
    $url = "https://discord.com/api/v10/guilds/{$GUILD_ID}/members/{$discord_user_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot {$DISCORD_BOT_SECRET}",
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
    global $DISCORD_BOT_SECRET;
    
    if (!$discord_user_id || !$DISCORD_BOT_SECRET) {
        return 'Discord Moderator';
    }
    
    $url = "https://discord.com/api/v10/users/{$discord_user_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot {$DISCORD_BOT_SECRET}",
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
        
        // Validate input
        if (empty($username) || empty($password)) {
            echo json_encode([
                'success' => false,
                'error' => 'Username and password are required'
            ]);
            break;
        }
        
        if (isset($admin_users[$username])) {
            $user = $admin_users[$username];
            $stored_hash = $user['password_hash'] ?? $user['password'] ?? '';
            
            // Check if password is hashed or plain text (for backward compatibility)
            if (password_verify($password, $stored_hash) || $stored_hash === $password) {
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
        
        if (!isset($admin_users[$admin_username]) || $admin_users[$admin_username]['role'] !== 'super_admin') {
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
            'password_hash' => password_hash($new_password, PASSWORD_DEFAULT),
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
        
        if (!isset($admin_users[$admin_username]) || $admin_users[$admin_username]['role'] !== 'super_admin') {
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
        
        if (!isset($admin_users[$admin_username]) || $admin_users[$admin_username]['role'] !== 'super_admin') {
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