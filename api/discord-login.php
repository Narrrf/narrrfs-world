<?php
// --- Set redirect target (default is profile) ---
$redirect = $_GET['redirect'] ?? '/profile.html';
$encodedRedirect = urlencode($redirect);

// --- Build Discord URL ---
$clientId = getenv('DISCORD_CLIENT_ID');
$scope = 'identify guilds guilds.members.read';
$redirectUri = urlencode("https://narrrfs.world/api/auth/callback.php?final_redirect=$encodedRedirect");

$discordUrl = "https://discord.com/oauth2/authorize?client_id=$clientId&response_type=code&redirect_uri=$redirectUri&scope=$scope";

// --- Forward user to Discord login ---
header("Location: $discordUrl");
exit;
