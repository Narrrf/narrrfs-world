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
    
    if (empty($missionId)) {
        throw new Exception('Missing mission_id');
    }
    
    $db = getSQLite3Connection();
    
    // Get mission details
    $query = "SELECT * FROM tbl_twitter_missions WHERE mission_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$missionId]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mission) {
        throw new Exception('Mission not found');
    }
    
    echo json_encode([
        'success' => true,
        'mission' => $mission
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
