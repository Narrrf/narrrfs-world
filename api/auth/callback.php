<?php
session_start();

// ✅ Load from Render environment
$clientId = getenv('DISCORD_CLIENT_ID');
$clientSecret = getenv('DISCORD_SECRET');
$redirectUri = 'https://narrrfs.world/api/auth/callback.php';
if (isset($_GET['redirect'])) {
    $redirectUri .= '?redirect=' . $_GET['redirect'];
}

// ✅ Step 1: Get code
if (!isset($_GET['code'])) {
    die('❌ No code returned from Discord');
}
$code = $_GET['code'];

// ✅ Step 2: Exchange code for token
$tokenRequest = curl_init();
curl_setopt_array($tokenRequest, [
    CURLOPT_URL => 'https://discord.com/api/oauth2/token',
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri,
        'scope' => 'identify guilds guilds.members.read'
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);

$response = curl_exec($tokenRequest);
curl_close($tokenRequest);
$token = json_decode($response, true);

if (!isset($token['access_token'])) {
    die("❌ Failed to get access token:\n$response");
}
$accessToken = $token['access_token'];

// ✅ Step 3: Fetch user info
$userRequest = curl_init();
curl_setopt_array($userRequest, [
    CURLOPT_URL => 'https://discord.com/api/v10/users/@me',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $accessToken"
    ]
]);
$userResponse = curl_exec($userRequest);
curl_close($userRequest);
$user = json_decode($userResponse, true);

if (!isset($user['id'])) {
    die("❌ Failed to get user info:\n$userResponse");
}

// 🧀 Save key user fields to session
$_SESSION['user'] = [
    'username' => $user['username'],
    'discriminator' => $user['discriminator'] ?? '0000',
    'avatar' => $user['avatar'],
    'email' => $user['email'] ?? null,
];
$_SESSION['discord_id'] = $user['id'];
$_SESSION['access_token'] = $accessToken;

error_log('Discord ID Fetched: ' . $_SESSION['discord_id']);

// Optional: Store guilds if scope includes `guilds`
$guildsResponse = file_get_contents('https://discord.com/api/users/@me/guilds', false, stream_context_create([
    'http' => [
        'header' => "Authorization: Bearer $accessToken"
    ]
]));
$_SESSION['guilds'] = json_decode($guildsResponse, true) ?? [];

// ✅ Save user to DB
try {
    $db = new SQLite3('/var/www/html/db/narrrf_world.sqlite');
} catch (Exception $e) {
    die("❌ Database error: " . $e->getMessage());
}

// Generate full avatar URL
$avatarUrl = $user['avatar']
    ? "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.png"
    : 'https://cdn.discordapp.com/embed/avatars/0.png';

// Check if user already exists
$checkStmt = $db->prepare("SELECT discord_id FROM tbl_users WHERE discord_id = ?");
$checkStmt->bindValue(':discord_id', $user['id'], SQLITE3_TEXT);
$existingUser = $checkStmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($existingUser) {
    // Update existing user (don't change created_at)
    $stmt = $db->prepare("
        UPDATE tbl_users 
        SET username = :username, avatar_url = :avatar_url
        WHERE discord_id = :discord_id
    ");
} else {
    // Insert new user with created_at timestamp
    $stmt = $db->prepare("
        INSERT INTO tbl_users (discord_id, username, avatar_url, created_at)
        VALUES (:discord_id, :username, :avatar_url, :created_at)
    ");
    $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
}

$stmt->bindValue(':discord_id', $user['id'], SQLITE3_TEXT);
$stmt->bindValue(':username', $user['username'], SQLITE3_TEXT);
$stmt->bindValue(':avatar_url', $avatarUrl, SQLITE3_TEXT);
$stmt->execute();

// ✅ Sync roles
include_once(__DIR__ . '/sync-role.php');

// 📌 Determine redirect target (now dynamic)
$target = 'https://narrrfs.world/profile.html';
if (isset($_SESSION['oauth_final_redirect'])) {
    $target = 'https://narrrfs.world' . $_SESSION['oauth_final_redirect'];
    unset($_SESSION['oauth_final_redirect']); // clean up
}

// ✅ Inject localStorage and redirect
echo "<script>
  localStorage.setItem('discord_id', '{$user['id']}');
  localStorage.setItem('discord_name', '{$user['username']}');
  window.location.href = '$target';
</script>";
exit;
