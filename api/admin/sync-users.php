<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../config/discord.php';

// Check if user is admin
if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Connect to database
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get user roles
$stmt = $db->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
$stmt->execute([$_SESSION['discord_id']]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Check if user is moderator or founder
if (!in_array('Moderator', $roles) && !in_array('Founder', $roles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Fetch all Discord members
$guild_id = DISCORD_GUILD_ID; // Add this to discord.php
$url = "https://discord.com/api/v10/guilds/$guild_id/members?limit=1000";
$headers = [
    "Authorization: Bot " . DISCORD_BOT_TOKEN,
    "Content-Type: application/json"
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$discord_members = json_decode($response, true);

if (!$discord_members) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch Discord members']);
    exit;
}

// Get existing users from database
$stmt = $db->prepare("SELECT discord_id, username FROM tbl_users");
$stmt->execute();
$db_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$db_user_ids = array_column($db_users, 'discord_id');

// Find missing users
$missing_users = [];
$updated_users = [];

foreach ($discord_members as $member) {
    $discord_id = $member['user']['id'];
    $username = $member['user']['username'];
    
    if (!in_array($discord_id, $db_user_ids)) {
        // Add missing user to database
        $stmt = $db->prepare("INSERT INTO tbl_users (discord_id, username) VALUES (?, ?)");
        $stmt->execute([$discord_id, $username]);
        $missing_users[] = ['id' => $discord_id, 'username' => $username];
    } else {
        // Update existing username if changed
        $db_user = array_filter($db_users, function($u) use ($discord_id) {
            return $u['discord_id'] === $discord_id;
        })[0];
        
        if ($db_user['username'] !== $username) {
            $stmt = $db->prepare("UPDATE tbl_users SET username = ? WHERE discord_id = ?");
            $stmt->execute([$username, $discord_id]);
            $updated_users[] = ['id' => $discord_id, 'username' => $username];
        }
    }
}

echo json_encode([
    'success' => true,
    'missing_users_added' => count($missing_users),
    'users_updated' => count($updated_users),
    'details' => [
        'missing_users' => $missing_users,
        'updated_users' => $updated_users
    ]
]);
?> 