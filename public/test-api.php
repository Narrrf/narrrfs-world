<?php
// Simple test file to check API functionality
header('Content-Type: text/html');

echo "<h1>API Test</h1>";
echo "<p>Testing API access...</p>";

// Test database connection
try {
    require_once '../api/config/database.php';
    $pdo = getDatabaseConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tbl_tetris_scores");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Database query successful: " . $result['count'] . " tetris scores found</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test file paths
echo "<h2>File Paths</h2>";
echo "<p>Current file: " . __FILE__ . "</p>";
echo "<p>API config exists: " . (file_exists('../api/config/database.php') ? 'YES' : 'NO') . "</p>";
echo "<p>Database exists: " . (file_exists('../db/narrrf_world.sqlite') ? 'YES' : 'NO') . "</p>";

// Test API file
echo "<h2>API Files</h2>";
$api_file = '../api/admin/get-all-games-stats.php';
echo "<p>API file exists: " . (file_exists($api_file) ? 'YES' : 'NO') . "</p>";
echo "<p>API file path: " . realpath($api_file) . "</p>";

// Try to include and test the API
echo "<h2>API Test</h2>";
try {
    ob_start();
    include $api_file;
    $api_output = ob_get_clean();
    
    // Check if output is valid JSON
    $json_data = json_decode($api_output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p>✅ API returned valid JSON</p>";
        echo "<pre>" . htmlspecialchars(substr($api_output, 0, 500)) . "...</pre>";
    } else {
        echo "<p>❌ API returned invalid JSON: " . json_last_error_msg() . "</p>";
        echo "<pre>" . htmlspecialchars($api_output) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p>❌ API error: " . $e->getMessage() . "</p>";
}
?>
