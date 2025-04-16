<?php
session_start();

// ✅ Load from Render environment
$clientId = getenv('DISCORD_CLIENT_ID');
$clientSecret = getenv('DISCORD_SECRET');
$redirectUri = 'https://narrrfs.world/api/auth/callback.php';

// ✅ Step 1: Get code from Discord
if (!isset($_GET['code'])) {
    die('❌ No code returned from Discord');
}
$code = $_GET['code'];

// ✅ Step 2: Exchange code for access token
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

// ❌ Access token missing?
if (!isset($token['access_token'])) {
    die("❌ Failed to get access token:\n$response");
}
$accessToken = $token['access_token'];

// ✅ Step 3: Get user info from Discord
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

// ✅ Store user info in session
$_SESSION['discord_id'] = $user['id'];
$_SESSION['access_token'] = $accessToken;

// ✅ Save to DB
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database error: " . $e->getMessage());
}

$stmt = $pdo->prepare("
    INSERT OR REPLACE INTO tbl_users (discord_id, username, avatar_url)
    VALUES (?, ?, ?)
");
$stmt->execute([$user['id'], $user['username'], $user['avatar']]);

// ✅ Sync roles (tbl_user_roles)
include_once(__DIR__ . '/sync-role.php');

// ✅ Redirect home
header('Location: https://narrrfs.world/profile.html');
exit;
