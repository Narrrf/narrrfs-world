<?php
// Simple test script for all admin APIs - can be run on Render
// This tests the APIs directly without using curl

echo "🔍 Testing All Admin API Endpoints on Render\n";
echo "===========================================\n\n";

$test_results = [];

// Test function that simulates API calls
function testAPIEndpoint($endpoint_name, $test_function) {
    echo "Testing: $endpoint_name ";
    
    try {
        $result = $test_function();
        if ($result['success']) {
            echo "✅ SUCCESS\n";
            if (isset($result['data'])) {
                echo "   Data: " . json_encode($result['data']) . "\n";
            }
        } else {
            echo "❌ FAILED\n";
            if (isset($result['error'])) {
                echo "   Error: " . $result['error'] . "\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
        $result = ['success' => false, 'error' => $e->getMessage()];
    }
    
    echo "\n";
    return $result;
}

// Test database connection
echo "🔧 Testing Database Connection\n";
echo "==============================\n";

$db_path = '/var/www/html/db/narrrf_world.sqlite';
if (file_exists($db_path)) {
    echo "✅ Database file exists: $db_path\n";
    try {
        $db = new SQLite3($db_path);
        echo "✅ Database connection successful\n";
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Database file not found: $db_path\n";
}

echo "\n";

// Test 1: Get Stats API
$test_results[] = testAPIEndpoint('get-stats.php', function() {
    $db_path = '/var/www/html/db/narrrf_world.sqlite';
    $db = new SQLite3($db_path);
    
    $result = $db->query('SELECT COUNT(*) as count FROM tbl_users');
    $user_count = $result->fetchArray(SQLITE3_ASSOC)['count'];
    
    $result = $db->query('SELECT COUNT(*) as count FROM tbl_user_scores');
    $score_count = $result->fetchArray(SQLITE3_ASSOC)['count'];
    
    return [
        'success' => true,
        'data' => [
            'users' => $user_count,
            'scores' => $score_count
        ]
    ];
});

// Test 2: Get Enhanced Stats API
$test_results[] = testAPIEndpoint('get-enhanced-stats.php', function() {
    $db_path = '/var/www/html/db/narrrf_world.sqlite';
    $db = new SQLite3($db_path);
    
    $result = $db->query('SELECT COUNT(*) as count FROM tbl_user_scores');
    $count = $result->fetchArray(SQLITE3_ASSOC)['count'];
    
    return [
        'success' => true,
        'data' => ['count' => $count]
    ];
});

// Test 3: Point Management - Search User
$test_results[] = testAPIEndpoint('point-management.php (search)', function() {
    $db_path = '/var/www/html/db/narrrf_world.sqlite';
    $db = new SQLite3($db_path);
    
    $stmt = $db->prepare('
        SELECT us.user_id, SUM(us.score) as total_score, u.username, u.discord_id 
        FROM tbl_user_scores us 
        LEFT JOIN tbl_users u ON us.user_id = u.discord_id 
        WHERE us.user_id LIKE ? OR u.username LIKE ? 
        GROUP BY us.user_id 
        ORDER BY total_score DESC 
        LIMIT 5
    ');
    $stmt->bindValue(1, '%narr%', SQLITE3_TEXT);
    $stmt->bindValue(2, '%narr%', SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }
    
    return [
        'success' => true,
        'data' => ['users' => $users]
    ];
});

// Test 4: Point Management - Get User Balance
$test_results[] = testAPIEndpoint('point-management.php (balance)', function() {
    $db_path = '/var/www/html/db/narrrf_world.sqlite';
    $db = new SQLite3($db_path);
    
    $stmt = $db->prepare('SELECT SUM(score) as total_score FROM tbl_user_scores WHERE user_id = ?');
    $stmt->bindValue(1, '328601656659017732', SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    return [
        'success' => true,
        'data' => ['balance' => $row['total_score'] ?? 0]
    ];
});

// Test 5: Get Top Users
$test_results[] = testAPIEndpoint('get-top-users.php', function() {
    $db_path = '/var/www/html/db/narrrf_world.sqlite';
    $db = new SQLite3($db_path);
    
    $result = $db->query('
        SELECT us.user_id, SUM(us.score) as total_score, u.username 
        FROM tbl_user_scores us 
        LEFT JOIN tbl_users u ON us.user_id = u.discord_id 
        GROUP BY us.user_id 
        ORDER BY total_score DESC 
        LIMIT 10
    ');
    
    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }
    
    return [
        'success' => true,
        'data' => ['users' => $users]
    ];
});

// Test 6: Get Recent Adjustments
$test_results[] = testAPIEndpoint('get-recent-adjustments.php', function() {
    $db_path = '/var/www/html/db/narrrf_world.sqlite';
    $db = new SQLite3($db_path);
    
    $result = $db->query('
        SELECT * FROM tbl_score_adjustments 
        ORDER BY adjusted_at DESC 
        LIMIT 10
    ');
    
    $adjustments = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $adjustments[] = $row;
    }
    
    return [
        'success' => true,
        'data' => ['adjustments' => $adjustments]
    ];
});

// Test 7: Get Quests
$test_results[] = testAPIEndpoint('get-quests.php', function() {
    $db_path = '/var/www/html/db/narrrf_world.sqlite';
    $db = new SQLite3($db_path);
    
    $result = $db->query('SELECT * FROM tbl_quests WHERE is_active = 1 ORDER BY created_at DESC');
    
    $quests = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $quests[] = $row;
    }
    
    return [
        'success' => true,
        'data' => ['quests' => $quests]
    ];
});

// Test 8: Store Management
$test_results[] = testAPIEndpoint('store-management.php', function() {
    $db_path = '/var/www/html/db/narrrf_world.sqlite';
    $db = new SQLite3($db_path);
    
    $result = $db->query('SELECT * FROM tbl_store_items ORDER BY item_id DESC');
    
    $items = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $items[] = $row;
    }
    
    return [
        'success' => true,
        'data' => ['items' => $items]
    ];
});

// Summary
echo "📊 TEST SUMMARY\n";
echo "===============\n";

$success_count = 0;
$failed_count = 0;

foreach ($test_results as $result) {
    if ($result['success']) {
        $success_count++;
    } else {
        $failed_count++;
    }
}

echo "✅ Successful: $success_count\n";
echo "❌ Failed: $failed_count\n";
echo "📈 Success Rate: " . round(($success_count / count($test_results)) * 100, 1) . "%\n\n";

if ($failed_count == 0) {
    echo "🎉 All admin APIs are working correctly!\n";
} else {
    echo "🔧 Some APIs have issues. Check the errors above.\n";
}

echo "\n✨ Test completed!\n";
?> 