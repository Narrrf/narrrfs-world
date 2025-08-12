<?php
// --- Set redirect target (default is profile) ---
$redirect = $_GET['redirect'] ?? '/profile.html';

// --- Use EXACT Discord OAuth2 URL structure as specified by user ---
// This exact URL works for members: https://discord.com/oauth2/authorize?client_id=1357927342265204858&response_type=code&redirect_uri=https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php&scope=guilds+identify+guilds.members.read

$clientId = '1357927342265204858'; // Use exact client ID from working URL
$redirectUri = 'https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php'; // Exact encoded redirect URI
$scope = 'guilds+identify+guilds.members.read'; // Exact scope format with + separators

// Build the exact Discord OAuth2 URL that works for members
$discordUrl = "https://discord.com/oauth2/authorize?client_id=$clientId&response_type=code&redirect_uri=$redirectUri&scope=$scope";

// Store the redirect target in session for the callback to use
session_start();
$_SESSION['oauth_final_redirect'] = $redirect;

// Log the OAuth request for debugging
error_log('🔍 Discord OAuth Login Request - ' . date('Y-m-d H:i:s'));
error_log('🔍 Using exact working URL structure');
error_log('🔍 Client ID: ' . $clientId);
error_log('🔍 Redirect URI: ' . urldecode($redirectUri));
error_log('🔍 Scope: ' . $scope);
error_log('🔍 Final Redirect Target: ' . $redirect);
error_log('🔍 Full Discord URL: ' . $discordUrl);

// --- Forward user to Discord login with exact working URL ---
header("Location: $discordUrl");
exit;
