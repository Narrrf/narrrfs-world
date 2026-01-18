<?php
/**
 * Portal Waypoint Register - Table Creation Script
 * Date: January 18, 2026
 * Purpose: Create portal_waypoint_messages table in local database
 * Run once: http://localhost/test-create-portal-waypoint-table.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database path
$dbPath = __DIR__ . '/db/narrrf_world.sqlite';

echo "<h1>📖 Portal Waypoint Register - Table Creation</h1>";
echo "<hr>";

// Check if database exists
if (!file_exists($dbPath)) {
    echo "<p style='color: red;'>❌ <strong>Error:</strong> Database not found at: <code>$dbPath</code></p>";
    exit;
}

echo "<p>✅ Database found: <code>$dbPath</code></p>";

try {
    // Connect to database
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database</p>";
    
    // Check if table already exists
    $checkTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='portal_waypoint_messages'");
    $tableExists = $checkTable->fetch();
    
    if ($tableExists) {
        echo "<p style='color: orange;'>⚠️ <strong>Warning:</strong> Table 'portal_waypoint_messages' already exists!</p>";
        echo "<p>Skipping table creation. If you want to recreate it, manually drop the table first.</p>";
    } else {
        // Create table
        echo "<h2>Creating table...</h2>";
        
        $pdo->exec("
            CREATE TABLE portal_waypoint_messages (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              portal_id TEXT NOT NULL,
              discord_id TEXT NOT NULL,
              username TEXT NOT NULL,
              message TEXT NOT NULL,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        echo "<p>✅ Table 'portal_waypoint_messages' created successfully</p>";
    }
    
    // Create indexes
    echo "<h2>Creating indexes...</h2>";
    
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_portal_waypoint_portal_id ON portal_waypoint_messages(portal_id)");
        echo "<p>✅ Index 'idx_portal_waypoint_portal_id' created</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Index 'idx_portal_waypoint_portal_id' may already exist</p>";
    }
    
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_portal_waypoint_created_at ON portal_waypoint_messages(created_at DESC)");
        echo "<p>✅ Index 'idx_portal_waypoint_created_at' created</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Index 'idx_portal_waypoint_created_at' may already exist</p>";
    }
    
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_portal_waypoint_discord_id ON portal_waypoint_messages(discord_id)");
        echo "<p>✅ Index 'idx_portal_waypoint_discord_id' created</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Index 'idx_portal_waypoint_discord_id' may already exist</p>";
    }
    
    // Verify table structure
    echo "<h2>Verifying table structure...</h2>";
    
    $tableInfo = $pdo->query("PRAGMA table_info(portal_waypoint_messages)");
    $columns = $tableInfo->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr style='background: #f0f0f0;'><th>Column</th><th>Type</th><th>Not Null</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['name']}</strong></td>";
        echo "<td>{$column['type']}</td>";
        echo "<td>" . ($column['notnull'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($column['dflt_value'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count existing messages
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM portal_waypoint_messages");
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<h2>Current Data:</h2>";
    echo "<p>Total messages in database: <strong>{$count}</strong></p>";
    
    // Optional: Add sample data
    if ($count == 0) {
        echo "<h2>Adding sample test data...</h2>";
        
        $sampleData = [
            ['LEVEL4_CENTER_PORTAL', '111111111', 'Narrrfs', 'First visitor here! This portal is amazing! 🧀'],
            ['LEVEL4_CENTER_PORTAL', '222222222', 'CheeseKing', 'Narrrfs was here before me! Great game!'],
            ['LEVEL4_CENTER_PORTAL', '333333333', 'PortalMaster', 'This is the coolest feature ever!'],
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO portal_waypoint_messages (portal_id, discord_id, username, message) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($sampleData as $data) {
            $stmt->execute($data);
        }
        
        echo "<p>✅ Added 3 sample messages</p>";
        
        // Show sample data
        echo "<h3>Sample Messages:</h3>";
        $messages = $pdo->query("SELECT * FROM portal_waypoint_messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin-top: 10px; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Portal ID</th><th>Username</th><th>Message</th><th>Created At</th></tr>";
        foreach ($messages as $msg) {
            echo "<tr>";
            echo "<td>{$msg['id']}</td>";
            echo "<td>{$msg['portal_id']}</td>";
            echo "<td><strong>{$msg['username']}</strong></td>";
            echo "<td>{$msg['message']}</td>";
            echo "<td>{$msg['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Final status
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ SUCCESS!</h2>";
    echo "<p>Portal Waypoint Register table is ready to use!</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test the API endpoint: <a href='/api/user/portal-waypoint.php?action=get&portal_id=LEVEL4_CENTER_PORTAL' target='_blank'>Test GET Request</a></li>";
    echo "<li>Deploy to Render manually (see instructions below)</li>";
    echo "</ol>";
    
    echo "<h3>Render Deployment SQL:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow: auto;'>";
    echo "sqlite3 /var/www/html/db/narrrf_world.sqlite\n\n";
    echo "CREATE TABLE IF NOT EXISTS portal_waypoint_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  portal_id TEXT NOT NULL,
  discord_id TEXT NOT NULL,
  username TEXT NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_portal_waypoint_portal_id ON portal_waypoint_messages(portal_id);
CREATE INDEX IF NOT EXISTS idx_portal_waypoint_created_at ON portal_waypoint_messages(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_portal_waypoint_discord_id ON portal_waypoint_messages(discord_id);

.tables
.schema portal_waypoint_messages
.exit";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Error:</strong> {$e->getMessage()}</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: #f9f9f9;
    }
    h1 { color: #333; }
    h2 { color: #555; margin-top: 30px; }
    h3 { color: #666; }
    code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
    pre { background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 5px; overflow: auto; }
    table { font-size: 14px; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
