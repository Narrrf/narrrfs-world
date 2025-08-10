<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Get active races count
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_races FROM tbl_cheese_races WHERE status = 'active'");
    $stmt->execute();
    $activeRaces = $stmt->fetch(PDO::FETCH_ASSOC)['active_races'];
    
    // Get total races today
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_races FROM tbl_cheese_races WHERE DATE(created_at) = DATE('now')");
    $stmt->execute();
    $totalRacesToday = $stmt->fetch(PDO::FETCH_ASSOC)['total_races'];
    
    // Get participants today
    $stmt = $pdo->prepare("SELECT COUNT(*) as participants FROM tbl_race_participants WHERE DATE(joined_at) = DATE('now')");
    $stmt->execute();
    $participantsToday = $stmt->fetch(PDO::FETCH_ASSOC)['participants'];
    
    // Get DSPOINC distributed today
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(rp.dspoinc_earned), 0) as dspoinc_distributed 
        FROM tbl_race_participants rp 
        JOIN tbl_cheese_races cr ON rp.race_id = cr.race_id 
        WHERE DATE(rp.finished_at) = DATE('now') AND rp.dspoinc_earned > 0
    ");
    $stmt->execute();
    $dspoincDistributed = $stmt->fetch(PDO::FETCH_ASSOC)['dspoinc_distributed'];
    
    $stats = [
        'activeRaces' => (int)$activeRaces,
        'totalRacesToday' => (int)$totalRacesToday,
        'participantsToday' => (int)$participantsToday,
        'dspoincDistributed' => (int)$dspoincDistributed
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
