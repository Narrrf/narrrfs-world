<?php
session_start();

// ✅ Load from Render environment with exact values from working OAuth URL
$clientId = '1357927342265204858'; // Use exact client ID from working URL
$clientSecret = getenv('DISCORD_SECRET');
$redirectUri = 'https://narrrfs.world/api/auth/callback.php'; // Exact redirect URI from working URL

// ✅ Step 1: Get code
if (!isset($_GET['code'])) {
    error_log('❌ No authorization code received from Discord');
    die('❌ No authorization code returned from Discord. Please try logging in again.');
}
$code = $_GET['code'];

// ✅ Step 2: Exchange code for token using exact format from working URL
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
        'redirect_uri' => $redirectUri, // Use exact redirect URI without parameters
        'scope' => 'guilds+identify+guilds.members.read' // Exact scope order from working URL
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);

$response = curl_exec($tokenRequest);
curl_close($tokenRequest);
$token = json_decode($response, true);

if (!isset($token['access_token'])) {
    error_log('❌ Failed to get access token: ' . $response);
    
    // Check for specific Discord OAuth errors
    if (isset($token['error'])) {
        switch ($token['error']) {
            case 'invalid_grant':
                die("❌ OAuth-Fehler: Der Autorisierungscode ist abgelaufen oder wurde bereits verwendet. Bitte versuchen Sie sich erneut anzumelden.");
            case 'redirect_uri_mismatch':
                die("❌ OAuth-Fehler: Die Weiterleitungs-URL stimmt nicht überein. Bitte kontaktieren Sie den Administrator.");
            case 'invalid_client':
                die("❌ OAuth-Fehler: Ungültige Client-Konfiguration. Bitte kontaktieren Sie den Administrator.");
            default:
                die("❌ OAuth-Fehler: " . ($token['error_description'] ?? $token['error']) . ". Bitte versuchen Sie es erneut.");
        }
    }
    
    die("❌ Fehler beim Abrufen des Zugriffstokens. Bitte versuchen Sie sich erneut anzumelden.");
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
    error_log('❌ Failed to get user info: ' . $userResponse);
    
    if (isset($user['error'])) {
        die("❌ Discord-Fehler: " . ($user['error_description'] ?? $user['error']) . ". Bitte versuchen Sie es erneut.");
    }
    
    die("❌ Fehler beim Abrufen der Benutzerinformationen. Bitte versuchen Sie es erneut.");
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
$checkStmt->bindValue(1, $user['id'], SQLITE3_TEXT);
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

try {
    $stmt->execute();
} catch (Exception $e) {
    error_log("Database error during user save: " . $e->getMessage());
    // Continue anyway - user can still use the site
}

// ✅ Sync roles
include_once(__DIR__ . '/sync-role.php');

// 📌 Determine redirect target from session (stored during login)
$target = 'https://narrrfs.world/profile.html';
if (isset($_SESSION['oauth_final_redirect'])) {
    $target = 'https://narrrfs.world' . $_SESSION['oauth_final_redirect'];
    unset($_SESSION['oauth_final_redirect']); // clean up
}

// ✅ Inject localStorage and redirect with admin redirect check
echo "<script>
  localStorage.setItem('discord_id', '{$user['id']}');
  localStorage.setItem('discord_name', '{$user['username']}');
  
  // Check if user was redirected from admin interface
  const adminRedirect = localStorage.getItem('adminRedirect');
  if (adminRedirect) {
    localStorage.removeItem('adminRedirect'); // Clear the flag
    console.log('🔄 Admin redirect detected, redirecting to admin interface');
    window.location.href = 'https://narrrfs.world/admin-interface.html';
  } else {
    console.log('🔄 No admin redirect, going to profile page');
    window.location.href = '$target';
  }
</script>";
exit;
