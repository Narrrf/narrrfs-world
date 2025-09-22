<?php
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
    
    $db = getSQLite3Connection();
    
    // Get pending verifications with mission details and Twitter usernames
    $query = "
        SELECT 
            p.mission_id,
            p.user_id,
            p.username,
            p.joined_at,
            p.verification_status,
            m.mission_type,
            m.reward_dspoinc,
            m.tweet_url,
            m.creator_name,
            u.twitter_username
        FROM tbl_twitter_mission_participants p
        JOIN tbl_twitter_missions m ON p.mission_id = m.mission_id
        LEFT JOIN tbl_users u ON p.user_id = u.discord_id
        WHERE p.verification_status = 'pending' AND m.status = 'active'
        ORDER BY p.joined_at DESC
    ";
    
    $verifications = [];
    $stmt = $db->prepare($query);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $verifications[] = [
            'mission_id' => $row['mission_id'],
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'twitter_username' => $row['twitter_username'] ?? 'Not linked',
            'joined_at' => $row['joined_at'],
            'verification_status' => $row['verification_status'],
            'mission_type' => $row['mission_type'],
            'reward_dspoinc' => (int)$row['reward_dspoinc'],
            'tweet_url' => $row['tweet_url'],
            'creator_name' => $row['creator_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'verifications' => $verifications
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
