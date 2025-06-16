<?php
session_start();
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');

// Use a safe relative path so it works both locally and on Render!
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
$db = new PDO('sqlite:' . $dbPath);

// First try to get user from session
$user_id = $_SESSION['discord_id'] ?? '';

// If not in session, try GET param as fallback
if (!$user_id) {
    $user_id = $_GET['user_id'] ?? '';
}

// If still no user_id, return error
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get user info
$stmt = $db->prepare("SELECT username, avatar_url FROM tbl_users WHERE discord_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If avatar_url is just a hash, convert it to full URL
if ($user && $user['avatar_url'] && !str_starts_with($user['avatar_url'], 'http')) {
    $user['avatar_url'] = "https://cdn.discordapp.com/avatars/{$user_id}/{$user['avatar_url']}.png";
}

// Get total DSPOINC for this user
$stmt = $db->prepare("SELECT SUM(score) AS total_dspoinc FROM tbl_user_scores WHERE user_id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch();

// Default to 0 if not found
$total_dspoinc = (int)($row['total_dspoinc'] ?? 0);

// Get guilds from session
$guilds = $_SESSION['guilds'] ?? [];

// Respond with user info and scores
echo json_encode([
    'discord_id' => $user_id,
    'discord_name' => $user['username'] ?? 'Guest',
    'avatar_url' => $user['avatar_url'] ?? 'https://cdn.discordapp.com/embed/avatars/0.png',
    'guilds' => $guilds,
    'total_dspoinc' => $total_dspoinc,
    'total_spoinc' => floor($total_dspoinc / 10000)
]);
?>
