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
    $missionId = $input['mission_id'] ?? '';
    $updates = $input['updates'] ?? [];
    
    if (empty($missionId)) {
        throw new Exception('Missing mission_id');
    }
    
    if (empty($updates)) {
        throw new Exception('No updates provided');
    }
    
    $db = getSQLite3Connection();
    
    // Check if mission exists
    $checkQuery = "SELECT * FROM tbl_twitter_missions WHERE mission_id = ?";
    $stmt = $db->prepare($checkQuery);
    $stmt->execute([$missionId]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mission) {
        throw new Exception('Mission not found');
    }
    
    // Build update query dynamically
    $updateFields = [];
    $updateValues = [];
    
    if (isset($updates['reward_dspoinc'])) {
        $updateFields[] = 'reward_dspoinc = ?';
        $updateValues[] = $updates['reward_dspoinc'];
    }
    
    if (isset($updates['duration_hours'])) {
        $updateFields[] = 'duration_hours = ?';
        $updateValues[] = $updates['duration_hours'];
        // Recalculate expiration time
        $updateFields[] = 'expires_at = ?';
        $updateValues[] = date('Y-m-d H:i:s', time() + ($updates['duration_hours'] * 3600));
    }
    
    if (isset($updates['mission_type'])) {
        $updateFields[] = 'mission_type = ?';
        $updateValues[] = $updates['mission_type'];
    }
    
    if (empty($updateFields)) {
        throw new Exception('No valid fields to update');
    }
    
    $updateFields[] = 'updated_at = datetime(\'now\')';
    $updateValues[] = $missionId;
    
    $updateQuery = "UPDATE tbl_twitter_missions SET " . implode(', ', $updateFields) . " WHERE mission_id = ?";
    
    $stmt = $db->prepare($updateQuery);
    $stmt->execute($updateValues);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mission updated successfully',
        'updated_fields' => array_keys($updates)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
