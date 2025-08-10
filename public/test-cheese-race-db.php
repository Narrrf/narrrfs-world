<?php
header('Content-Type: text/html');

echo "<h1>🧀 Cheese Race Database Test</h1>";
echo "<p>Testing database queries directly...</p>";

try {
    require_once 'api/config/database.php';
    $pdo = getDatabaseConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test 1: Check cheese races table
    echo "<h2>Test 1: Cheese Races Table</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tbl_cheese_races");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Total races: " . $result['count'] . "</p>";
    
    // Show recent races
    $stmt = $pdo->query("SELECT race_id, creator_name, status, created_at FROM tbl_cheese_races ORDER BY created_at DESC LIMIT 5");
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Recent races:</p><ul>";
    foreach ($races as $race) {
        echo "<li>{$race['race_id']} - {$race['creator_name']} ({$race['status']}) - {$race['created_at']}</li>";
    }
    echo "</ul>";
    
    // Test 2: Check race participants table
    echo "<h2>Test 2: Race Participants Table</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tbl_race_participants");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Total participants: " . $result['count'] . "</p>";
    
    // Test 3: Test the specific queries from the API
    echo "<h2>Test 3: API Query Tests</h2>";
    
    // Active races count
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_races FROM tbl_cheese_races WHERE status = 'active'");
    $stmt->execute();
    $activeRaces = $stmt->fetch(PDO::FETCH_ASSOC)['active_races'];
    echo "<p>✅ Active races: " . $activeRaces . "</p>";
    
    // Total races today
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_races FROM tbl_cheese_races WHERE DATE(created_at) = DATE('now')");
    $stmt->execute();
    $totalRacesToday = $stmt->fetch(PDO::FETCH_ASSOC)['total_races'];
    echo "<p>✅ Races today: " . $totalRacesToday . "</p>";
    
    // Participants today
    $stmt = $pdo->prepare("SELECT COUNT(*) as participants FROM tbl_race_participants WHERE DATE(joined_at) = DATE('now')");
    $stmt->execute();
    $participantsToday = $stmt->fetch(PDO::FETCH_ASSOC)['participants'];
    echo "<p>✅ Participants today: " . $participantsToday . "</p>";
    
    // DSPOINC distributed today
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(rp.dspoinc_earned), 0) as dspoinc_distributed 
        FROM tbl_race_participants rp 
        JOIN tbl_cheese_races cr ON rp.race_id = cr.race_id 
        WHERE DATE(rp.finished_at) = DATE('now') AND rp.dspoinc_earned > 0
    ");
    $stmt->execute();
    $dspoincDistributed = $stmt->fetch(PDO::FETCH_ASSOC)['dspoinc_distributed'];
    echo "<p>✅ DSPOINC distributed today: " . $dspoincDistributed . "</p>";
    
    // Test 4: Check if we can build the API response
    echo "<h2>Test 4: API Response Structure</h2>";
    $stats = [
        'activeRaces' => (int)$activeRaces,
        'totalRacesToday' => (int)$totalRacesToday,
        'participantsToday' => (int)$participantsToday,
        'dspoincDistributed' => (int)$dspoincDistributed
    ];
    
    echo "<p>✅ Stats array built successfully:</p>";
    echo "<pre>" . json_encode($stats, JSON_PRETTY_PRINT) . "</pre>";
    
    // Test 5: Recent races query
    echo "<h2>Test 5: Recent Races Query</h2>";
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
        LIMIT 5
    ");
    $stmt->execute();
    $recentRaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Recent races query successful: " . count($recentRaces) . " races found</p>";
    echo "<p>Sample race data:</p>";
    if (!empty($recentRaces)) {
        echo "<pre>" . json_encode($recentRaces[0], JSON_PRETTY_PRINT) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
