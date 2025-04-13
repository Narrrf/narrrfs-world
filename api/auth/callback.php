<?php
session_start();

$clientId = '1357927342265204858';
$clientSecret = 'g4xN1p_uovPq1cZ_LHd9P-iM38lt_xRP';
$redirectUri = 'https://38f5-2001-871-268-d865-c808-d1b5-59d9-3bf9.ngrok-free.app/api/auth/callback.php';

if (!isset($_GET['code'])) {
    die('❌ No code returned from Discord');
}

$code = $_GET['code'];

// Exchange code for access token
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
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded'
    ]
]);

$response = curl_exec($tokenRequest);
curl_close($tokenRequest);
$token = json_decode($response, true);

if (!isset($token['access_token'])) {
    die("❌ Failed to get access token:\n$response");
}

$accessToken = $token['access_token'];

// Fetch user info
$userRequest = curl_init();
curl_setopt_array($userRequest, [
    CURLOPT_URL => 'https://discord.com/api/users/@me',
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

// Save session data
$_SESSION['discord_id'] = $user['id'];
$_SESSION['access_token'] = $accessToken;

// Run role sync
include_once(__DIR__ . '/../../sync/sync-role.php');

// ✅ FINAL STEP: Redirect user
header('Location: https://narrrfs.world/profile.html'); // 🔁 Replace with your real destination
exit;
