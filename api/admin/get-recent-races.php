<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Get recent races (last 20)
    $stmt = $pdo->prepare("
        SELECT 
            cr.race_id,
            cr.creator_id,
            cr.creator_name,
            cr.status,
            cr.max_players,
            cr.duration,
            cr.dspoinc_reward,
            cr.role_reward,
            cr.created_at,
            cr.started_at,
            cr.ended_at,
            COUNT(rp.id) as participant_count
        FROM tbl_cheese_races cr
        LEFT JOIN tbl_race_participants rp ON cr.race_id = rp.race_id
        GROUP BY cr.race_id
        ORDER BY cr.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedRaces = [];
    foreach ($races as $race) {
        $formattedRaces[] = [
            'race_id' => $race['race_id'],
            'creator_id' => $race['creator_id'],
            'creator_name' => $race['creator_name'],
            'status' => $race['status'],
            'max_players' => (int)$race['max_players'],
            'duration' => (int)$race['duration'],
            'dspoinc_reward' => (int)$race['dspoinc_reward'],
            'role_reward' => $race['role_reward'],
            'created_at' => $race['created_at'],
            'started_at' => $race['started_at'],
            'ended_at' => $race['ended_at'],
            'participant_count' => (int)$race['participant_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedRaces
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
