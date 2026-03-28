<?php
// 🛡️ Admin Auth API
// Handles admin login for the Narrrfs World admin interface.
//
// Stable-first rules:
// - Creates PHP session state for production-protected admin APIs
// - Keeps password login and Discord moderator login flows
// - Preserves add/list/remove user actions
// - Avoids frontend-only auth by syncing successful login into $_SESSION

declare(strict_types=1);

/**
 * Return JSON and stop execution.
 */
function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

/**
 * Check whether the current request is coming from localhost.
 */
function is_localhost_request(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Configure CORS safely for production session-based auth.
 *
 * Important:
 * - Session cookies do not work with Access-Control-Allow-Origin: *
 * - We only allow known origins
 */
function apply_cors_headers(): void
{
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

    $allowedOrigins = [
        'https://narrrfs.world',
        'https://www.narrrfs.world',
        'http://localhost',
        'http://127.0.0.1',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:8080',
        'http://127.0.0.1:8080',
    ];

    if ($requestOrigin !== '' && in_array($requestOrigin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Vary: Origin');
}

/**
 * Read a request value from POST first, then GET.
 */
function request_value(string $key, string $default = ''): string
{
    $postValue = $_POST[$key] ?? null;
    if ($postValue !== null) {
        return trim((string)$postValue);
    }

    $getValue = $_GET[$key] ?? null;
    if ($getValue !== null) {
        return trim((string)$getValue);
    }

    return $default;
}

/**
 * Check whether the current authenticated admin is super admin.
 *
 * Stable note:
 * Existing add/list/remove flows pass admin_username from the frontend.
 * We keep that behavior to avoid breaking current UI, but also support session fallback.
 */
function is_super_admin_request(array $adminUsers): bool
{
    $sessionIsAdmin = $_SESSION['is_admin'] ?? false;
    $sessionRole = (string)($_SESSION['admin_role'] ?? '');
    $sessionUsername = (string)($_SESSION['admin_username'] ?? '');

    if (!($sessionIsAdmin === true || $sessionIsAdmin === 1 || $sessionIsAdmin === '1')) {
        return false;
    }

    if (strtolower($sessionRole) !== 'super_admin') {
        return false;
    }

    if ($sessionUsername === '' || !isset($adminUsers[$sessionUsername])) {
        return false;
    }

    return (($adminUsers[$sessionUsername]['role'] ?? '') === 'super_admin');
}

/**
 * Check whether a Discord user has the configured moderator role.
 */
function checkDiscordModeratorRole(string $discordUserId, ?string $discordBotSecret, string $moderatorRoleId, string $guildId): bool
{
    if ($discordUserId === '') {
        return false;
    }

if (!$discordBotSecret) {
    return is_localhost_request();
}

    $url = "https://discord.com/api/v10/guilds/{$guildId}/members/{$discordUserId}";

    $ch = curl_init();
    if ($ch === false) {
        return false;
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot {$discordBotSecret}",
        "Content-Type: application/json",
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return false;
    }

    $memberData = json_decode($response, true);
    if (!is_array($memberData)) {
        return false;
    }

    $roles = $memberData['roles'] ?? [];
    if (!is_array($roles)) {
        return false;
    }

    return in_array($moderatorRoleId, $roles, true);
}

/**
 * Resolve a readable Discord username for admin display.
 */
function getDiscordUsername(string $discordUserId, ?string $discordBotSecret): string
{
    if ($discordUserId === '') {
        return 'Discord Moderator';
    }

    if (!$discordBotSecret) {
        return 'Discord Moderator';
    }

    $url = "https://discord.com/api/v10/users/{$discordUserId}";

    $ch = curl_init();
    if ($ch === false) {
        return 'Discord Moderator';
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot {$discordBotSecret}",
        "Content-Type: application/json",
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return 'Discord Moderator';
    }

    $userData = json_decode($response, true);
    if (!is_array($userData)) {
        return 'Discord Moderator';
    }

    return (string)($userData['username'] ?? 'Discord Moderator');
}

/**
 * Store authenticated admin identity in the PHP session.
 *
 * This is the critical bridge that production-only protected admin APIs require.
 */
function store_admin_session(string $username, string $role, string $discordId, string $authType): void
{
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_role'] = $role;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_discord_id'] = $discordId;
    $_SESSION['admin_auth_type'] = $authType;
}

/**
 * Load admin users from environment variables and optional admin_users.json file.
 *
 * Rules:
 * - Environment primary admin stays supported
 * - Additional JSON users stay supported
 * - Plaintext legacy passwords in JSON are auto-hashed in memory for compatibility
 */
function load_admin_users(): array
{
    $adminUsers = [];

    $primaryUsername = getenv('ADMIN_USERNAME') ?: 'narrrf';
    $primaryPasswordHash = getenv('ADMIN_PASSWORD_HASH');
    $primaryDiscordId = getenv('ADMIN_DISCORD_ID') ?: '328601656659017732';

    if ($primaryPasswordHash) {
        $adminUsers[$primaryUsername] = [
            'password_hash' => $primaryPasswordHash,
            'role' => 'super_admin',
            'discord_id' => $primaryDiscordId,
        ];
    } else {
        // TODO: Remove fallback development password from production environments.
        $adminUsers[$primaryUsername] = [
            'password_hash' => password_hash('PnoRakesucks&2025', PASSWORD_DEFAULT),
            'role' => 'super_admin',
            'discord_id' => $primaryDiscordId,
        ];
    }

    $usersFile = __DIR__ . '/admin_users.json';
    if (!file_exists($usersFile)) {
        return [$adminUsers, $usersFile];
    }

    $fileContent = file_get_contents($usersFile);
    if ($fileContent === false || trim($fileContent) === '') {
        return [$adminUsers, $usersFile];
    }

    $additionalUsers = json_decode($fileContent, true);
    if (!is_array($additionalUsers)) {
        return [$adminUsers, $usersFile];
    }

    foreach ($additionalUsers as $username => $userData) {
        if (!is_array($userData)) {
            continue;
        }

        if (isset($userData['password']) && !isset($userData['password_hash'])) {
            $userData['password_hash'] = password_hash((string)$userData['password'], PASSWORD_DEFAULT);
            unset($userData['password']);
        }

        $adminUsers[$username] = $userData;
    }

    return [$adminUsers, $usersFile];
}

// ------------------------------------------------------------
// Headers / session bootstrap
// ------------------------------------------------------------

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

apply_cors_headers();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

// ------------------------------------------------------------
// Configuration
// ------------------------------------------------------------

$discordBotSecret = getenv('DISCORD_BOT_SECRET') ?: null;
$moderatorRoleId = '1332049628300054679';
$guildId = getenv('DISCORD_GUILD') ?: '1332015322546311218';
$primaryAdminUsername = getenv('ADMIN_USERNAME') ?: 'narrrf';

[$adminUsers, $usersFile] = load_admin_users();

$action = request_value('action');

// ------------------------------------------------------------
// Actions
// ------------------------------------------------------------

switch ($action) {
    case 'login': {
        $username = request_value('username');
        $password = request_value('password');

        if ($username === '' || $password === '') {
            json_response([
                'success' => false,
                'error' => 'Username and password are required',
            ], 400);
        }

        if (!isset($adminUsers[$username])) {
            json_response([
                'success' => false,
                'error' => 'Invalid username or password',
            ], 401);
        }

        $user = $adminUsers[$username];
        $storedHash = (string)($user['password_hash'] ?? $user['password'] ?? '');

        $passwordMatches = $storedHash !== '' && (
            password_verify($password, $storedHash) ||
            hash_equals($storedHash, $password)
        );

        if (!$passwordMatches) {
            json_response([
                'success' => false,
                'error' => 'Invalid username or password',
            ], 401);
        }

        $role = (string)($user['role'] ?? 'admin');
        $discordId = (string)($user['discord_id'] ?? '');

        store_admin_session($username, $role, $discordId, 'password');

        json_response([
            'success' => true,
            'user' => [
                'username' => $username,
                'role' => $role,
                'discord_id' => $discordId,
                'auth_type' => 'password',
            ],
        ]);
    }

    case 'discord_auth': {
        $discordUserId = request_value('discord_user_id');

        if ($discordUserId === '') {
            json_response([
                'success' => false,
                'error' => 'Discord user ID is required',
            ], 400);
        }

        $hasModeratorAccess = checkDiscordModeratorRole(
            $discordUserId,
            $discordBotSecret,
            $moderatorRoleId,
            $guildId
        );

        if (!$hasModeratorAccess) {
            json_response([
                'success' => false,
                'error' => 'You do not have the required moderator role to access this interface.',
            ], 403);
        }

        $username = getDiscordUsername($discordUserId, $discordBotSecret);
        $role = 'moderator';

        store_admin_session($username, $role, $discordUserId, 'discord');

        json_response([
            'success' => true,
            'user' => [
                'username' => $username,
                'discord_id' => $discordUserId,
                'role' => $role,
                'auth_type' => 'discord',
            ],
        ]);
    }

    case 'add_user': {
        if (!is_super_admin_request($adminUsers)) {
            json_response([
                'success' => false,
                'error' => 'Unauthorized - Super admin access required',
            ], 403);
        }

        $newUsername = request_value('new_username');
        $newPassword = request_value('new_password');
        $newRole = request_value('new_role', 'moderator');
        $newDiscordId = request_value('new_discord_id');

        if ($newUsername === '' || $newPassword === '') {
            json_response([
                'success' => false,
                'error' => 'Username and password are required',
            ], 400);
        }

        $adminUsers[$newUsername] = [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'role' => $newRole !== '' ? $newRole : 'moderator',
            'discord_id' => $newDiscordId,
        ];

        $saved = file_put_contents($usersFile, json_encode($adminUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($saved === false) {
            json_response([
                'success' => false,
                'error' => 'Failed to save admin user file',
            ], 500);
        }

        json_response([
            'success' => true,
            'message' => "User '{$newUsername}' added successfully",
        ]);
    }

    case 'list_users': {
        if (!is_super_admin_request($adminUsers)) {
            json_response([
                'success' => false,
                'error' => 'Unauthorized - Super admin access required',
            ], 403);
        }

        $usersList = [];
        foreach ($adminUsers as $username => $user) {
            $usersList[] = [
                'username' => $username,
                'role' => (string)($user['role'] ?? 'moderator'),
                'discord_id' => (string)($user['discord_id'] ?? ''),
            ];
        }

        json_response([
            'success' => true,
            'users' => $usersList,
        ]);
    }

    case 'remove_user': {
        if (!is_super_admin_request($adminUsers)) {
            json_response([
                'success' => false,
                'error' => 'Unauthorized - Super admin access required',
            ], 403);
        }

        $removeUsername = request_value('remove_username');

        if ($removeUsername === '') {
            json_response([
                'success' => false,
                'error' => 'Username is required',
            ], 400);
        }

        if ($removeUsername === $primaryAdminUsername) {
    json_response([
        'success' => false,
        'error' => 'Cannot remove super admin account',
    ], 400);
        }

        if (!isset($adminUsers[$removeUsername])) {
            json_response([
                'success' => false,
                'error' => 'User not found',
            ], 404);
        }

        unset($adminUsers[$removeUsername]);

        $saved = file_put_contents($usersFile, json_encode($adminUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($saved === false) {
            json_response([
                'success' => false,
                'error' => 'Failed to save admin user file',
            ], 500);
        }

        json_response([
            'success' => true,
            'message' => "User '{$removeUsername}' removed successfully",
        ]);
    }

    default:
        json_response([
            'success' => false,
            'error' => 'Invalid action',
        ], 400);
}