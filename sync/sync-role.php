<?php
// 🧠 Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Load role ID → name mapping from file
$roleMap = require __DIR__ . '/../../discord-tools/role_map.php';

// 🏠 Your Discord Guild (server) ID
$guildId = '1332015322546311218';

// 🔐 Bot token securely from Render ENV (must start with "Bot ")
$botToken = 'Bot ' . getenv('DISCORD_BOT_SECRET');

// 👤 Get user Discord ID from session
$discordId = $_SESSION['discord_id'] ?? null;

// ❌ Abort early if token or session data missing
if (!$discordId || !$botToken) {
    http_response_code(401);
    exit;
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
    http_response_code($httpCode);
    error_log("❌ Discord API failed with HTTP $httpCode — Response: $response");
    exit;
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
} catch (Exception $e) {
    http_response_code(500);
    error_log("❌ DB error: " . $e->getMessage());
}
