<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Security headerss
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

try {
    // Use centralized database configuration
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();

    // Get user_id from query parameter
    $userId = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;
    
    if (!$userId || $userId === '' || $userId === 'null' || $userId === 'undefined') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing or invalid user_id parameter'
        ]);
        exit;
    }

    // Get total cheese hunt clicks (all time, not just quest)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_clicks FROM tbl_cheese_clicks WHERE user_wallet = ?");
    $stmt->execute([$userId]);
    $totalClicks = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_clicks'];

    // Get active cheese hunt quest to determine quest progress
    $stmt = $pdo->prepare("
        SELECT quest_id, description, reward, expires_at, cheese_config 
        FROM tbl_quests 
        WHERE type = 'cheese_hunt' AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $quest = $stmt->fetch(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'total_clicks' => $totalClicks,
        'quest_clicks' => 0,
        'required_eggs' => 3, // Default fallback
        'quest_completed' => false
    ];

    if ($quest) {
        // Parse cheese config if it exists
        $cheese_config = null;
        if ($quest['cheese_config']) {
            $cheese_config = json_decode($quest['cheese_config'], true);
        }

        // CRITICAL: Always use actual cheese_count from quest config (not hardcoded or old values)
        $requiredEggs = $cheese_config['cheese_count'] ?? 3;
        $response['required_eggs'] = $requiredEggs; // Always set from actual quest config

        // Check if user has already claimed this quest
        $stmt = $pdo->prepare("SELECT * FROM tbl_quest_claims WHERE quest_id = ? AND user_id = ?");
        $stmt->execute([$quest['quest_id'], $userId]);
        $existing_claim = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_claim) {
            // Quest already completed by this user
            $response['quest_completed'] = true;
            $response['quest_clicks'] = $requiredEggs; // Show required eggs as clicks when completed
        } else {
            // Count how many different cheese eggs this user has clicked for this quest
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT egg_id) as unique_eggs 
                                   FROM tbl_cheese_clicks 
                                   WHERE quest_id = ? AND user_wallet = ?");
            $stmt->execute([$quest['quest_id'], $userId]);
            $egg_count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_eggs'];

            $response['quest_clicks'] = $egg_count;
            $response['quest_completed'] = ($egg_count >= $requiredEggs);
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Cheese hunt stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
