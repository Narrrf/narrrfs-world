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
    error_log("Sync Error: Database connection failed - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Debug: Log sync start
error_log("Sync Debug: Starting user sync");

// Get user roles
$stmt = $db->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
$stmt->execute([$_SESSION['discord_id']]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Debug: Log roles
error_log("Sync Debug: User roles for " . $_SESSION['discord_id'] . ": " . implode(", ", $roles));

// Check if user is moderator or founder
if (!in_array('Moderator', $roles) && !in_array('Founder', $roles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Fetch all Discord members
$guild_id = DISCORD_GUILD_ID; // Add this to discord.php
$all_discord_members = [];
$after = null;

do {
    $url = "https://discord.com/api/v10/guilds/$guild_id/members?limit=1000" . ($after ? "&after=$after" : "");
    $headers = [
        "Authorization: Bot " . DISCORD_BOT_TOKEN,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    // Check for cURL errors
    if ($response === false) {
        error_log("Sync Error: cURL error - " . curl_error($ch));
        http_response_code(500);
        echo json_encode(['error' => 'Failed to connect to Discord API']);
        curl_close($ch);
        exit;
    }

    // Check HTTP response code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
        error_log("Sync Error: Discord API returned HTTP $httpCode - Response: $response");
        http_response_code(500);
        echo json_encode(['error' => "Discord API returned error $httpCode"]);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    $members = json_decode($response, true);

    if (!$members) {
        error_log("Sync Error: Failed to decode Discord members response. JSON error: " . json_last_error_msg());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch Discord members']);
        exit;
    }

    // Debug: Log member details
    error_log("Sync Debug: Number of members fetched: " . count($members));
    error_log("Sync Debug: First member example: " . json_encode(array_slice($members, 0, 1)));

    $all_discord_members = array_merge($all_discord_members, $members);
    
    // Get the last user's ID for pagination
    if (count($members) === 1000) {
        $after = end($members)['user']['id'];
    } else {
        $after = null;
    }
} while ($after);

// Debug: Log member count
error_log("Sync Debug: Fetched " . count($all_discord_members) . " members from Discord");

// Get existing users from database
$stmt = $db->prepare("SELECT discord_id, username FROM tbl_users");
$stmt->execute();
$db_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$db_user_ids = array_column($db_users, 'discord_id');

// Debug: Log existing users
error_log("Sync Debug: Number of existing users in DB: " . count($db_users));
error_log("Sync Debug: First DB user example: " . json_encode(array_slice($db_users, 0, 1)));

// Find missing users
$missing_users = [];
$updated_users = [];

foreach ($all_discord_members as $member) {
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

// Debug: Log final results
error_log("Sync Debug: Added " . count($missing_users) . " new users, updated " . count($updated_users) . " existing users");

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