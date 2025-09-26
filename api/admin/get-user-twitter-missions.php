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
    $userId = $input['user_id'] ?? '';
    
    if (empty($userId)) {
        throw new Exception("User ID is required");
    }
    
    $db = getSQLite3Connection();
    
    // Get user's Twitter mission history with mission details
    $query = "
        SELECT 
            p.mission_id,
            p.user_id,
            p.username,
            p.joined_at,
            p.status,
            p.verification_status,
            p.verification_attempts,
            p.completed_at,
            p.reward_claimed,
            p.updated_at,
            m.creator_name,
            m.tweet_url,
            m.mission_type,
            m.reward_dspoinc,
            m.duration_hours,
            m.created_at as mission_created_at,
            m.expires_at,
            m.status as mission_status
        FROM tbl_twitter_mission_participants p
        JOIN tbl_twitter_missions m ON p.mission_id = m.mission_id
        WHERE p.user_id = ?
        ORDER BY p.joined_at DESC
    ";
    
    $missions = [];
    $stmt = $db->prepare($query);
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $missions[] = [
            'mission_id' => $row['mission_id'],
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'joined_at' => $row['joined_at'],
            'status' => $row['status'],
            'verification_status' => $row['verification_status'],
            'verification_attempts' => (int)$row['verification_attempts'],
            'completed_at' => $row['completed_at'],
            'reward_claimed' => (int)$row['reward_claimed'],
            'updated_at' => $row['updated_at'],
            'mission_details' => [
                'creator_name' => $row['creator_name'],
                'tweet_url' => $row['tweet_url'],
                'mission_type' => $row['mission_type'],
                'reward_dspoinc' => (int)$row['reward_dspoinc'],
                'duration_hours' => (int)$row['duration_hours'],
                'created_at' => $row['mission_created_at'],
                'expires_at' => $row['expires_at'],
                'status' => $row['mission_status']
            ]
        ];
    }
    
    // Get user summary statistics
    $summaryQuery = "
        SELECT 
            COUNT(*) as total_missions,
            COUNT(CASE WHEN verification_status = 'verified' THEN 1 END) as completed_missions,
            COUNT(CASE WHEN verification_status = 'denied' THEN 1 END) as denied_missions,
            COUNT(CASE WHEN verification_status = 'pending' THEN 1 END) as pending_missions,
            SUM(CASE WHEN verification_status = 'verified' THEN reward_claimed ELSE 0 END) as total_rewards_earned
        FROM tbl_twitter_mission_participants p
        JOIN tbl_twitter_missions m ON p.mission_id = m.mission_id
        WHERE p.user_id = ?
    ";
    
    $stmt = $db->prepare($summaryQuery);
    $stmt->execute([$userId]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'username' => $missions[0]['username'] ?? 'Unknown',
        'summary' => [
            'total_missions' => (int)$summary['total_missions'],
            'completed_missions' => (int)$summary['completed_missions'],
            'denied_missions' => (int)$summary['denied_missions'],
            'pending_missions' => (int)$summary['pending_missions'],
            'total_rewards_earned' => (int)$summary['total_rewards_earned']
        ],
        'missions' => $missions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
