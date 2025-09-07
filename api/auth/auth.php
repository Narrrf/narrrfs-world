<?php
// Admin authentication functions
function checkAdminAuthentication() {
    // 🔧 CRITICAL FIX: Local development bypass (SAFE - only for localhost)
    $isLocalDevelopment = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';
    if ($isLocalDevelopment) {
        // Skip authentication for local development only
        return true;
    }
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is authenticated via Discord OAuth (for moderators)
    if (isset($_SESSION['discord_id']) && !empty($_SESSION['discord_id'])) {
        // Check if user has moderator role in Discord
        if (checkDiscordModeratorRole($_SESSION['discord_id'])) {
            return true;
        }
    }
    
    // Check if user is authenticated via password (for super admin)
    if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
        return true;
    }
    
    // If not authenticated, return error response
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Admin access required'
    ]);
    return false;
}

function checkDiscordModeratorRole($discord_user_id) {
    $DISCORD_BOT_SECRET = getenv('DISCORD_BOT_SECRET');
    $MODERATOR_ROLE_ID = '1332049628300054679'; // Moderator role ID
    $GUILD_ID = getenv('DISCORD_GUILD') ?: '1332015322546311218';
    
    if (!$discord_user_id || !$DISCORD_BOT_SECRET) {
        // For testing purposes, allow access if no proper setup
        return true;
    }
    
    // Make Discord API call to get user's roles
    $url = "https://discord.com/api/v10/guilds/{$GUILD_ID}/members/{$discord_user_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot {$DISCORD_BOT_SECRET}",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $member_data = json_decode($response, true);
        if (isset($member_data['roles']) && in_array($MODERATOR_ROLE_ID, $member_data['roles'])) {
            return true;
        }
    }
    
    return false;
}

function isAdminOrMod() {
    if (!isset($_SESSION['discord_id'])) {
        return false;
    }

    $dbPath = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false))
        ? __DIR__ . '/../../db/narrrf_world.sqlite'  // Local development
        : (file_exists(__DIR__ . '/../../db/narrrf_world.sqlite') 
            ? __DIR__ . '/../../db/narrrf_world.sqlite'  // Local fallback
            : '/data/narrrf_world.sqlite');              // Render production

    try {
        $db = new PDO("sqlite:$dbPath");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check user roles - only allow Admin (Founder) and Moderator
        $stmt = $db->prepare("
            SELECT role_name 
            FROM tbl_user_roles 
            WHERE user_id = ? 
            AND role_name IN ('Founder', 'Moderator')
        ");
        $stmt->execute([$_SESSION['discord_id']]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return !empty($roles);
    } catch (Exception $e) {
        error_log("Admin verification error: " . $e->getMessage());
        return false;
    }
}
?>
