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
    $db = getSQLite3Connection();
    
    // Get count of pending Twitter mission verifications
    $query = "
        SELECT COUNT(*) as pending_count
        FROM tbl_twitter_mission_participants 
        WHERE verification_status = 'pending'
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $pendingCount = (int)$result['pending_count'];
    
    // Format the count for display
    if ($pendingCount === 0) {
        $displayText = "0";
    } else {
        $displayText = $pendingCount . " Pending";
    }
    
    echo json_encode([
        'success' => true,
        'pending_count' => $pendingCount,
        'display_text' => $displayText,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'pending_count' => 0,
        'display_text' => "Error"
    ]);
}
?>
