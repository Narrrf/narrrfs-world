<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test cheese click lookup with Discord ID
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Test 1: Check total cheese clicks
    $totalClicks = $pdo->query("SELECT COUNT(*) as total FROM tbl_cheese_clicks")->fetchColumn();
    
    // Test 2: Check sample cheese clicks with user_wallet
    $sampleClicks = $pdo->query("SELECT user_wallet, egg_id, timestamp, quest_id FROM tbl_cheese_clicks LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 3: Check if any clicks use Discord ID format (15-20 digits)
    $discordIdClicks = $pdo->query("SELECT COUNT(*) as count FROM tbl_cheese_clicks WHERE user_wallet REGEXP '^[0-9]{15,20}$'")->fetchColumn();
    
    // Test 4: Check if any clicks use wallet format (longer alphanumeric)
    $walletClicks = $pdo->query("SELECT COUNT(*) as count FROM tbl_cheese_clicks WHERE user_wallet REGEXP '^[A-Za-z0-9]{30,}$'")->fetchColumn();
    
    // Test 5: Sample Discord ID format clicks
    $discordSample = $pdo->query("SELECT user_wallet, COUNT(*) as click_count FROM tbl_cheese_clicks WHERE user_wallet REGEXP '^[0-9]{15,20}$' GROUP BY user_wallet LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'test_results' => [
            'total_cheese_clicks' => $totalClicks,
            'discord_id_format_clicks' => $discordIdClicks,
            'wallet_format_clicks' => $walletClicks,
            'sample_clicks' => $sampleClicks,
            'discord_id_samples' => $discordSample
        ],
        'message' => 'Cheese click lookup test completed'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
