<?php
// Simple test script for missions API
header('Content-Type: text/plain');

echo "🧪 Testing Missions API...\n\n";

// Test with a known user ID
$testUserId = "328601656659017732"; // narrrf's Discord ID

echo "Testing with user ID: $testUserId\n\n";

// Test database connection
    try {
        // Use environment-aware database path with Windows detection
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            // Windows environment - prioritize local database
            if (file_exists(__DIR__ . '/db/narrrf_world.sqlite')) {
                $db = new PDO('sqlite:' . __DIR__ . '/db/narrrf_world.sqlite');
                echo "✅ Connected to local database (Windows)\n";
            } else {
                echo "❌ Local database not found on Windows: " . __DIR__ . '/db/narrrf_world.sqlite' . "\n";
                exit;
            }
        } else {
            // Linux/Unix environment - check production first
            if (file_exists('/var/www/html/db/narrrf_world.sqlite')) {
                $db = new PDO('sqlite:/var/www/html/db/narrrf_world.sqlite');
                echo "✅ Connected to local database\n";
            } elseif (file_exists(__DIR__ . '/db/narrrf_world.sqlite')) {
                $db = new PDO('sqlite:' . __DIR__ . '/db/narrrf_world.sqlite');
                echo "✅ Connected to local database\n";
            } else {
                echo "❌ Database not found at expected paths\n";
                exit;
            }
        }
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test table count
    $stmt = $db->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Database has " . $result['count'] . " tables\n\n";
    
    // Test Tetris data
    echo "🧩 Testing Tetris data:\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count, MAX(score) as best, SUM(score) as total FROM tbl_tetris_scores WHERE discord_id = ? AND game = 'tetris'");
    $stmt->execute([$testUserId]);
    $tetrisData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($tetrisData) {
        echo "  Games: " . $tetrisData['count'] . "\n";
        echo "  Best Score: " . $tetrisData['best'] . "\n";
        echo "  Total Score: " . $tetrisData['total'] . "\n";
        echo "  DSPOINC: " . ($tetrisData['total'] * 10) . "\n";
    } else {
        echo "  ❌ No tetris data found\n";
    }
    echo "\n";
    
    // Test Snake data
    echo "🐍 Testing Snake data:\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count, MAX(score) as best, SUM(score) as total FROM tbl_tetris_scores WHERE discord_id = ? AND game = 'snake'");
    $stmt->execute([$testUserId]);
    $snakeData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($snakeData) {
        echo "  Games: " . $snakeData['count'] . "\n";
        echo "  Best Score: " . $snakeData['best'] . "\n";
        echo "  Total Score: " . $snakeData['total'] . "\n";
        echo "  DSPOINC: " . ($snakeData['total'] * 10) . "\n";
    } else {
        echo "  ❌ No snake data found\n";
    }
    echo "\n";
    
    // Test Discord Race data
    echo "🏁 Testing Discord Race data:\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_race_participants WHERE user_id = ?");
    $stmt->execute([$testUserId]);
    $raceData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($raceData) {
        echo "  Races: " . $raceData['count'] . "\n";
        echo "  DSPOINC: " . ($raceData['count'] * 100) . "\n";
    } else {
        echo "  ❌ No race data found\n";
    }
    echo "\n";
    
    // Test Cheese Hunt data
    echo "🧀 Testing Cheese Hunt data:\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_cheese_clicks WHERE user_wallet = ?");
    $stmt->execute([$testUserId]);
    $cheeseData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cheeseData) {
        echo "  Clicks: " . $cheeseData['count'] . "\n";
        echo "  DSPOINC: " . ($cheeseData['count'] * 10) . "\n";
    } else {
        echo "  ❌ No cheese data found\n";
    }
    echo "\n";
    
    // Test Space Invaders data
    echo "👾 Testing Space Invaders data:\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count, SUM(score) as total FROM tbl_tetris_scores WHERE discord_id = ? AND game = 'space_invaders'");
    $stmt->execute([$testUserId]);
    $spaceData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($spaceData) {
        echo "  Games: " . $spaceData['count'] . "\n";
        echo "  Total Score: " . $spaceData['total'] . "\n";
        echo "  DSPOINC: " . ($spaceData['total'] * 0.1) . "\n";
    } else {
        echo "  ❌ No space invaders data found\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "🧪 Test complete!\n";
?>
