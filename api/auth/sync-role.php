<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Load role ID → name mapping
$roleMap = require __DIR__ . '/../../discord-tools/role_map.php';

// 🧠 Your Discord Server (Guild) ID
$guildId = '1332015322546311218';

// 🔐 Bot token (make sure this is valid and prefixed with "Bot ")
$botToken = 'Bot ' . getenv('DISCORD_SECRET');

// ✅ Get Discord ID from session
$discordId = $_SESSION['discord_id'] ?? null;

if (!$discordId || !$botToken) {
    http_response_code(401);
    die("❌ Missing session or bot token.");
}

// 🔁 Fetch member object from Discord API
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

// ❌ API failure
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo "❌ Discord API failed with HTTP $httpCode\n";
    echo "🔎 Response: $response\n";
    exit;
}

// ✅ Parse role IDs
$data = json_decode($response, true);
$discordRoleIds = $data['roles'] ?? [];

echo "🎭 Raw Role IDs: " . implode(', ', $discordRoleIds) . "\n";

// 🧀 Map to role names
$userRoles = [];
foreach ($discordRoleIds as $roleId) {
    if (isset($roleMap[$roleId])) {
        $userRoles[] = $roleMap[$roleId];
    }
}

echo "🧪 Mapped Roles: " . implode(', ', $userRoles) . "\n";

// ✅ Write to DB
try {
$pdo = new PDO('sqlite:' . __DIR__ . '/../../db/narrrf_world.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Clear old roles
    $clear = $pdo->prepare("DELETE FROM tbl_user_roles WHERE user_id = ?");
    $clear->execute([$discordId]);

    // Insert new roles
    $stmt = $pdo->prepare("INSERT INTO tbl_user_roles (user_id, role_name, timestamp) VALUES (?, ?, CURRENT_TIMESTAMP)");
    foreach ($userRoles as $roleName) {
        $stmt->execute([$discordId, $roleName]);
    }

    echo "✅ Roles synced to DB for user $discordId\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "❌ DB error: " . $e->getMessage();
}
