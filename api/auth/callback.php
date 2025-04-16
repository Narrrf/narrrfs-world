<?php
session_start();

// ✅ Load credentials securely from environment variables
$clientId = getenv('DISCORD_CLIENT_ID');         // 🧠 Discord App ID from Render
$clientSecret = getenv('DISCORD_SECRET');        // 🧀 Secret Sauce from Render

// 🌐 Render redirect URI
$redirectUri = 'https://narrrfs.world/api/auth/callback.php';

// ✅ Step 1: Get OAuth2 code from Discord
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

// ⛑️ MISSING: Add this now!
$response = curl_exec($tokenRequest);
curl_close($tokenRequest);
$token = json_decode($response, true);

if (!isset($token['access_token'])) {
    die("❌ Failed to get access token:\n$response");
}
$accessToken = $token['access_token']; // 🧀 Cheese token now usable ✅

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

// ✅ Store in session
$_SESSION['discord_id'] = $user['id'];
$_SESSION['access_token'] = $accessToken;

// ✅ Connect to SQLite DB in Apache path
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database error: " . $e->getMessage());
}

// ✅ INSERT or UPDATE user in tbl_users
$stmt = $pdo->prepare("
    INSERT OR REPLACE INTO tbl_users (discord_id, username, avatar_url)
    VALUES (?, ?, ?)
");
$stmt->execute([$user['id'], $user['username'], $user['avatar']]);

// ✅ Step 4: Call role sync (tbl_user_roles)
include_once(__DIR__ . '/sync-role.php');

// ✅ Final Redirect based on environment
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    header('Location: http://localhost/profile.html');
} else {
    header('Location: https://narrrfs.world/profile.html');
}
exit;
