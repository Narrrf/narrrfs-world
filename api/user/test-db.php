<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...\n";

// Test different database paths
$paths = [
    __DIR__ . '/../../db/narrrf_world.sqlite',
    '/var/www/html/db/narrrf_world.sqlite',
    '/data/narrrf_world.sqlite',
    './db/narrrf_world.sqlite'
];

foreach ($paths as $path) {
    echo "Testing path: $path\n";
    
    if (file_exists($path)) {
        echo "✅ File exists: $path\n";
        
        try {
            $db = new SQLite3($path);
            if ($db) {
                echo "✅ Database connection successful: $path\n";
                
                // Test a simple query
                $result = $db->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
                if ($result) {
                    $row = $result->fetchArray(SQLITE3_ASSOC);
                    echo "✅ Tables found: " . $row['count'] . "\n";
                }
                
                $db->close();
                echo "✅ Database closed successfully\n";
                break;
            }
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ File not found: $path\n";
    }
}

echo "Test complete.\n";
?>
