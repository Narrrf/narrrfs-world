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
    $missionId = $input['mission_id'] ?? '';
    $userId = $input['user_id'] ?? '';
    $approved = $input['approved'] ?? false;
    
    if (empty($missionId) || empty($userId)) {
        throw new Exception('Missing mission_id or user_id');
    }
    
    $db = getSQLite3Connection();
    
    // Get mission details
    $missionQuery = "SELECT * FROM tbl_twitter_missions WHERE mission_id = ?";
    $stmt = $db->prepare($missionQuery);
    $stmt->execute([$missionId]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mission) {
        throw new Exception('Mission not found');
    }
    
    // Get participant details
    $participantQuery = "SELECT * FROM tbl_twitter_mission_participants WHERE mission_id = ? AND user_id = ?";
    $stmt = $db->prepare($participantQuery);
    $stmt->execute([$missionId, $userId]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participant) {
        throw new Exception('Participant not found');
    }
    
    if ($approved) {
        // Approve the mission
        $db->beginTransaction();
        
        try {
            // Ensure reward amount is integer
            $rewardAmount = (int)$mission['reward_dspoinc'];
            
            // Update participant status
            $updateParticipantQuery = "
                UPDATE tbl_twitter_mission_participants 
                SET verification_status = 'verified', completed_at = datetime('now'), reward_claimed = ?
                WHERE mission_id = ? AND user_id = ?
            ";
            $stmt = $db->prepare($updateParticipantQuery);
            $stmt->execute([$rewardAmount, $missionId, $userId]);
            
            // Add DSPOINC to user's balance
            $addScoreQuery = "
                INSERT INTO tbl_user_scores (user_id, score, game, source, timestamp)
                VALUES (?, ?, 'twitter_mission', 'mission_reward', datetime('now'))
            ";
            $stmt = $db->prepare($addScoreQuery);
            $stmt->execute([$userId, $rewardAmount]);
            
            // Log score adjustment for profile visibility
            $addAdjustmentQuery = "
                INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp)
                VALUES (?, ?, ?, 'add', ?, datetime('now'))
            ";
            $stmt = $db->prepare($addAdjustmentQuery);
            $stmt->execute([$userId, $adminId, $rewardAmount, "Twitter mission reward - Mission {$missionId}"]);
            
            // Log verification
            $logVerificationQuery = "
                INSERT INTO tbl_twitter_verification_logs 
                (user_id, mission_id, verification_status, reward_distributed)
                VALUES (?, ?, 'success', ?)
            ";
            $stmt = $db->prepare($logVerificationQuery);
            $stmt->execute([$userId, $missionId, $rewardAmount]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Mission approved and reward distributed',
                'reward' => $rewardAmount
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        
    } else {
        // Deny the mission
        $updateParticipantQuery = "
            UPDATE tbl_twitter_mission_participants 
            SET verification_status = 'denied', completed_at = datetime('now')
            WHERE mission_id = ? AND user_id = ?
        ";
        $stmt = $db->prepare($updateParticipantQuery);
        $stmt->execute([$missionId, $userId]);
        
        // Log verification
        $logVerificationQuery = "
            INSERT INTO tbl_twitter_verification_logs 
            (user_id, mission_id, verification_status, reward_distributed)
            VALUES (?, ?, 'denied', 0)
        ";
        $stmt = $db->prepare($logVerificationQuery);
        $stmt->execute([$userId, $missionId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Mission denied',
            'reward' => 0
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
