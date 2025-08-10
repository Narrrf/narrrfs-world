<?php
header('Content-Type: text/html');

echo "<h1>🧀 Cheese Race APIs Test</h1>";
echo "<p>Testing cheese race API endpoints...</p>";

// Test 1: Race Stats API
echo "<h2>Test 1: Race Stats API</h2>";
try {
    $stats_url = 'api/admin/get-race-stats.php';
    if (file_exists($stats_url)) {
        echo "<p>✅ Race stats API file exists</p>";
        
        // Try to include and test
        ob_start();
        include $stats_url;
        $stats_output = ob_get_clean();
        
        $stats_data = json_decode($stats_output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p>✅ Race stats API returned valid JSON</p>";
            echo "<pre>" . htmlspecialchars($stats_output) . "</pre>";
        } else {
            echo "<p>❌ Race stats API returned invalid JSON: " . json_last_error_msg() . "</p>";
            echo "<pre>" . htmlspecialchars(substr($stats_output, 0, 200)) . "...</pre>";
        }
    } else {
        echo "<p>❌ Race stats API file not found at: $stats_url</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Race stats API error: " . $e->getMessage() . "</p>";
}

// Test 2: Recent Races API
echo "<h2>Test 2: Recent Races API</h2>";
try {
    $races_url = 'api/admin/get-recent-races.php';
    if (file_exists($races_url)) {
        echo "<p>✅ Recent races API file exists</p>";
        
        // Try to include and test
        ob_start();
        include $races_url;
        $races_output = ob_get_clean();
        
        $races_data = json_decode($races_output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p>✅ Recent races API returned valid JSON</p>";
            echo "<pre>" . htmlspecialchars($races_output) . "</pre>";
        } else {
            echo "<p>❌ Recent races API returned invalid JSON: " . json_last_error_msg() . "</p>";
            echo "<pre>" . htmlspecialchars(substr($races_output, 0, 200)) . "...</pre>";
        }
    } else {
        echo "<p>❌ Recent races API file not found at: $races_url</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Recent races API error: " . $e->getMessage() . "</p>";
}

// Test 3: Database connection
echo "<h2>Test 3: Database Connection</h2>";
try {
            require_once 'api/config/database.php';
    $pdo = getDatabaseConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test cheese races table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tbl_cheese_races");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Cheese races table accessible: " . $result['count'] . " races found</p>";
    
    // Test race participants table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tbl_race_participants");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Race participants table accessible: " . $result['count'] . " participants found</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 4: File paths
echo "<h2>Test 4: File Paths</h2>";
echo "<p>Current file: " . __FILE__ . "</p>";
        echo "<p>Race stats API exists: " . (file_exists('api/admin/get-race-stats.php') ? 'YES' : 'NO') . "</p>";
        echo "<p>Recent races API exists: " . (file_exists('api/admin/get-recent-races.php') ? 'YES' : 'NO') . "</p>";
        echo "<p>Database config exists: " . (file_exists('api/config/database.php') ? 'YES' : 'NO') . "</p>";
?>
