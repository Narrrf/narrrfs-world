<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

// 🔒 SECURITY: Only allow specific Discord roles
$ALLOWED_ROLES = [
    'moderator',      // Discord moderator role
    'admin',          // Discord admin role  
    'owner',          // Discord owner role
    'Narrrf_Admin',   // Custom admin role
    'Narrrf_Mod'      // Custom moderator role
];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['code'])) {
        echo json_encode(['success' => false, 'error' => 'Missing authorization code']);
        exit;
    }
    
    $code = $input['code'];
    
    // �� SECURITY: Validate against Discord OAuth
    $discord_token = validateDiscordCode($code);
    if (!$discord_token) {
        echo json_encode(['success' => false, 'error' => 'Invalid Discord authorization']);
        exit;
    }
    
    // �� SECURITY: Get user info from Discord
    $discord_user = getDiscordUser($discord_token);
    if (!$discord_user) {
        echo json_encode(['success' => false, 'error' => 'Failed to get Discord user info']);
        exit;
    }
    
    // 🔒 SECURITY: Check user roles in Discord server
    $user_roles = getUserDiscordRoles($discord_token, $discord_user['id']);
    if (!$user_roles) {
        echo json_encode(['success' => false, 'error' => 'Failed to get user roles']);
        exit;
    }
    
    // 🔒 SECURITY: Verify user has required role
    $has_access = false;
    foreach ($user_roles as $role) {
        if (in_array($role['name'], $ALLOWED_ROLES)) {
            $has_access = true;
            $user_role = $role['name'];
            break;
        }
    }
    
    if (!$has_access) {
        echo json_encode([
            'success' => false, 
            'error' => 'Access denied - Insufficient permissions',
            'message' => 'Only moderators, admins, and owners can access this system'
        ]);
        exit;
    }
    
    // ✅ ACCESS GRANTED: User has required role
    session_start();
    $_SESSION['discord_user'] = $discord_user;
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['user_role'] = $user_role;
    
    echo json_encode([
        'success' => true,
        'user' => $discord_user,
        'role' => $user_role,
        'message' => 'Discord authentication successful - Access granted'
    ]);
    
} catch (Exception $e) {
    error_log("Discord login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Authentication failed']);
}

// 🔒 SECURITY: Discord OAuth validation functions
function validateDiscordCode($code) {
    $client_id = getenv('DISCORD_CLIENT_ID');
    $client_secret = getenv('DISCORD_BOT_SECRET');
    $redirect_uri = getenv('DISCORD_BASE_URL') . '/api/auth/callback.php';
    
    $data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri
    ];
    
    $ch = curl_init('https://discord.com/api/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($response, true);
    return $token_data['access_token'] ?? null;
}

function getDiscordUser($token) {
    $ch = curl_init('https://discord.com/api/users/@me');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function getUserDiscordRoles($token, $user_id) {
    $guild_id = getenv('DISCORD_GUILD_ID'); // Your Discord server ID
    
    $ch = curl_init("https://discord.com/api/guilds/{$guild_id}/members/{$user_id}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bot ' . getenv('DISCORD_BOT_SECRET')
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $member_data = json_decode($response, true);
    
    if (!$member_data || !isset($member_data['roles'])) {
        return [];
    }
    
    // Get role details for each role ID
    $roles = [];
    foreach ($member_data['roles'] as $role_id) {
        $role = getRoleDetails($role_id, $guild_id);
        if ($role) {
            $roles[] = $role;
        }
    }
    
    return $roles;
}

function getRoleDetails($role_id, $guild_id) {
    $ch = curl_init("https://discord.com/api/guilds/{$guild_id}/roles");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bot ' . getenv('DISCORD_BOT_SECRET')
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $roles = json_decode($response, true);
    
    foreach ($roles as $role) {
        if ($role['id'] === $role_id) {
            return $role;
        }
    }
    
    return null;
}
?>