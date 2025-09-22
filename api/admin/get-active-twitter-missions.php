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
    
    // Get active missions with participant counts
    $query = "
        SELECT 
            m.mission_id,
            m.creator_name,
            m.tweet_url,
            m.mission_type,
            m.reward_dspoinc,
            m.duration_hours,
            m.created_at,
            m.expires_at,
            m.status,
            COUNT(p.user_id) as total_participants,
            COUNT(CASE WHEN p.verification_status = 'verified' THEN 1 END) as verified_participants
        FROM tbl_twitter_missions m
        LEFT JOIN tbl_twitter_mission_participants p ON m.mission_id = p.mission_id
        WHERE m.status = 'active'
        GROUP BY m.mission_id
        ORDER BY m.created_at DESC
    ";
    
    $missions = [];
    $stmt = $db->prepare($query);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $missions[] = [
            'mission_id' => $row['mission_id'],
            'creator_name' => $row['creator_name'],
            'tweet_url' => $row['tweet_url'],
            'mission_type' => $row['mission_type'],
            'reward_dspoinc' => (int)$row['reward_dspoinc'],
            'duration_hours' => (int)$row['duration_hours'],
            'created_at' => $row['created_at'],
            'expires_at' => $row['expires_at'],
            'status' => $row['status'],
            'total_participants' => (int)$row['total_participants'],
            'verified_participants' => (int)$row['verified_participants']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'missions' => $missions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
