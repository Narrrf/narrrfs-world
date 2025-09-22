<?php
// Disable error reporting for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../db/narrrf_world.sqlite' 
        : '/var/www/html/db/narrrf_world.sqlite';
    
    // Debug: Log the database path
    error_log("Database path: " . $dbPath);
    
    // Check if database file exists
    if (!file_exists($dbPath)) {
        throw new Exception("Database file not found at: " . $dbPath);
    }
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $adminId = $input['admin_id'] ?? 'unknown';
    $tweetUrl = $input['tweet_url'] ?? '';
    $missionType = $input['mission_type'] ?? '';
    $durationHours = (int)($input['duration_hours'] ?? 24);
    $rewardDspoinc = (int)($input['reward_dspoinc'] ?? 1000);
    
    if (empty($tweetUrl) || empty($missionType)) {
        throw new Exception('Missing required fields: tweet_url and mission_type');
    }
    
    // Validate tweet URL (support both twitter.com and x.com)
    if (!filter_var($tweetUrl, FILTER_VALIDATE_URL) || 
        (strpos($tweetUrl, 'twitter.com') === false && strpos($tweetUrl, 'x.com') === false)) {
        throw new Exception('Invalid Twitter URL - must be from twitter.com or x.com');
    }
    
    // Extract tweet ID from URL
    preg_match('/\/status\/(\d+)/', $tweetUrl, $matches);
    $tweetId = $matches[1] ?? '';
    
    if (empty($tweetId)) {
        throw new Exception('Could not extract tweet ID from URL');
    }
    
    // Generate mission ID
    $missionId = 'mission_' . time() . '_' . substr(md5($tweetUrl), 0, 8);
    
    $db = getSQLite3Connection();
    
    // Calculate expiration time
    $expiresAt = date('Y-m-d H:i:s', time() + ($durationHours * 3600));
    
    // Insert new mission
    $insertQuery = "
        INSERT INTO tbl_twitter_missions 
        (mission_id, creator_id, creator_name, tweet_url, tweet_id, mission_type, reward_dspoinc, duration_hours, status, channel_id, expires_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, datetime('now'))
    ";
    
    $stmt = $db->prepare($insertQuery);
    $stmt->execute([
        $missionId,
        $adminId,
        'Admin',
        $tweetUrl,
        $tweetId,
        $missionType,
        $rewardDspoinc,
        $durationHours,
        '1419688285223260250', // Discord channel ID for mission display
        $expiresAt
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mission created successfully',
        'mission_id' => $missionId,
        'expires_at' => $expiresAt
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
