<?php
// 🧠 Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only set JSON headers if this file is being called directly (not included)
if (basename($_SERVER['SCRIPT_NAME']) === 'sync-role.php') {
    // Set JSON headers for AJAX requests
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: https://narrrfs.world');
    header('Access-Control-Allow-Credentials: true');

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        exit;
    }
}

// ✅ Load role ID → name mapping from file
$roleMap = require __DIR__ . '/../../discord-tools/role_map.php';

// 🏠 Your Discord Guild (server) ID - Use environment variable from Render
$guildId = getenv('DISCORD_GUILD') ?: '1332015322546311218';

// 🔐 Bot token securely from Render ENV (must start with "Bot ")
$botToken = 'Bot ' . getenv('DISCORD_BOT_SECRET');

// 👤 Get user Discord ID from session
$discordId = $_SESSION['discord_id'] ?? null;

// ❌ Abort early if token or session data missing
if (!$discordId || !$botToken) {
    if (basename($_SERVER['SCRIPT_NAME']) === 'sync-role.php') {
        http_response_code(401);
        echo json_encode(['error' => 'User not logged in or bot token missing']);
        exit;
    }
    return; // Just return if included
}

// 🌐 Fetch user's full member object (includes role IDs)
$apiUrl = "https://discord.com/api/v10/guilds/$guildId/members/$discordId";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: $botToken"
    ]
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ❌ Discord API rejected request
if ($httpCode !== 200) {
    if (basename($_SERVER['SCRIPT_NAME']) === 'sync-role.php') {
        http_response_code($httpCode);
        error_log("❌ Discord API failed with HTTP $httpCode — Response: $response");
        echo json_encode(['error' => 'Discord API failed', 'http_code' => $httpCode]);
        exit;
    }
    return; // Just return if included
}

// ✅ Decode JSON response
$data = json_decode($response, true);
$discordRoleIds = $data['roles'] ?? [];

// 🧀 Map Discord role IDs to human-readable names
$userRoles = [];
foreach ($discordRoleIds as $roleId) {
    if (isset($roleMap[$roleId])) {
        $userRoles[] = $roleMap[$roleId];
    }
}

try {
    // 📦 Connect to SQLite DB
    $pdo = new PDO('sqlite:' . __DIR__ . '/../../db/narrrf_world.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 🧹 Clear previous roles for user
    $clear = $pdo->prepare("DELETE FROM tbl_user_roles WHERE user_id = ?");
    $clear->execute([$discordId]);

    // 💾 Insert current roles into DB
    $stmt = $pdo->prepare("INSERT INTO tbl_user_roles (user_id, role_name, timestamp) VALUES (?, ?, CURRENT_TIMESTAMP)");
    foreach ($userRoles as $roleName) {
        $stmt->execute([$discordId, $roleName]);
    }

    // ✅ Success log
    error_log("✅ Roles synced to DB for user $discordId: " . implode(', ', $userRoles));
    
    // Only return JSON response if this file is being called directly
    if (basename($_SERVER['SCRIPT_NAME']) === 'sync-role.php') {
        echo json_encode([
            'success' => true,
            'message' => 'Roles synced successfully',
            'roles' => $userRoles,        // Role names (existing)
            'role_ids' => $discordRoleIds, // Role IDs (NEW!)
            'user_id' => $discordId
        ]);
    }
    
} catch (Exception $e) {
    if (basename($_SERVER['SCRIPT_NAME']) === 'sync-role.php') {
        http_response_code(500);
        error_log("❌ DB error: " . $e->getMessage());
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    } else {
        error_log("❌ DB error: " . $e->getMessage());
    }
}
?>
