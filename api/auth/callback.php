<?php
session_start();

// ✅ Load from Render environment
$clientId = getenv('DISCORD_CLIENT_ID');
$clientSecret = getenv('DISCORD_SECRET');
$redirectUri = 'https://narrrfs.world/api/auth/callback.php';

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
$_SESSION['username'] = $user['username'];
$_SESSION['discriminator'] = $user['discriminator'] ?? '0000';
$_SESSION['avatar_url'] = $user['avatar']
    ? "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.png"
    : 'https://cdn.discordapp.com/embed/avatars/0.png';
$_SESSION['email'] = $user['email'] ?? null;
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
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database error: " . $e->getMessage());
}

// Generate full avatar URL
$avatarUrl = $user['avatar']
    ? "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.png"
    : 'https://cdn.discordapp.com/embed/avatars/0.png';

$stmt = $pdo->prepare("
    INSERT OR REPLACE INTO tbl_users (discord_id, username, avatar_url)
    VALUES (?, ?, ?)
");
$stmt->execute([$user['id'], $user['username'], $avatarUrl]);

// ✅ Sync roles
include_once(__DIR__ . '/sync-role.php');

// ✅ Inject localStorage for Tetris + redirect
$target = ($_SERVER['HTTP_HOST'] === 'localhost')
    ? 'http://localhost/profile.html'
    : 'https://narrrfs.world/profile.html';

echo "<script>
  localStorage.setItem('discord_id', '{$user['id']}');
  localStorage.setItem('discord_name', '{$user['username']}');
  window.location.href = '$target';
</script>";
exit;
