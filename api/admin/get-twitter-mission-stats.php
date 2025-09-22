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
    
    // Get Twitter mission statistics
    $db = getSQLite3Connection();
    
    // Active missions count
    $activeMissionsQuery = "SELECT COUNT(*) as count FROM tbl_twitter_missions WHERE status = 'active'";
    $stmt = $db->prepare($activeMissionsQuery);
    $stmt->execute();
    $activeMissions = $stmt->fetchColumn();
    
    // Pending verifications count
    $pendingVerificationsQuery = "
        SELECT COUNT(*) as count 
        FROM tbl_twitter_mission_participants p
        JOIN tbl_twitter_missions m ON p.mission_id = m.mission_id
        WHERE p.verification_status = 'pending' AND m.status = 'active'
    ";
    $stmt = $db->prepare($pendingVerificationsQuery);
    $stmt->execute();
    $pendingVerifications = $stmt->fetchColumn();
    
    // Total participants count
    $totalParticipantsQuery = "SELECT COUNT(*) as count FROM tbl_twitter_mission_participants";
    $stmt = $db->prepare($totalParticipantsQuery);
    $stmt->execute();
    $totalParticipants = $stmt->fetchColumn();
    
    // Rewards distributed count
    $rewardsDistributedQuery = "SELECT SUM(reward_distributed) as total FROM tbl_twitter_verification_logs WHERE verification_status = 'success'";
    $stmt = $db->prepare($rewardsDistributedQuery);
    $stmt->execute();
    $rewardsDistributed = $stmt->fetchColumn() ?: 0;
    
    // Recent activity (last 5 verifications)
    $recentActivityQuery = "
        SELECT 
            v.user_id,
            v.mission_id,
            v.verification_status,
            v.verification_attempted_at,
            p.username,
            m.mission_type
        FROM tbl_twitter_verification_logs v
        JOIN tbl_twitter_mission_participants p ON v.user_id = p.user_id AND v.mission_id = p.mission_id
        JOIN tbl_twitter_missions m ON v.mission_id = m.mission_id
        ORDER BY v.verification_attempted_at DESC
        LIMIT 5
    ";
    
    $recentActivity = [];
    $stmt = $db->prepare($recentActivityQuery);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['verification_status'] === 'success' ? '✅' : '❌';
        $recentActivity[] = "{$status} {$row['username']} - {$row['mission_type']} mission";
    }
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'active_missions' => (int)$activeMissions,
            'pending_verifications' => (int)$pendingVerifications,
            'total_participants' => (int)$totalParticipants,
            'rewards_distributed' => (int)$rewardsDistributed,
            'recent_activity' => $recentActivity
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
