<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

try {
    // Use centralized database configuration
    require_once __DIR__ . '/config/database.php';
    $pdo = getDatabaseConnection();

    // Get active cheese hunt quest
    $stmt = $pdo->prepare("
        SELECT quest_id, description, reward, expires_at, cheese_config 
        FROM tbl_quests 
        WHERE type = 'cheese_hunt' AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $quest = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($quest) {
        // Parse cheese config if it exists
        $cheese_config = null;
        if ($quest['cheese_config']) {
            $cheese_config = json_decode($quest['cheese_config'], true);
        }

        echo json_encode([
            'success' => true,
            'quest' => [
                'quest_id' => (int)$quest['quest_id'],
                'description' => $quest['description'],
                'reward' => (int)$quest['reward'],
                'expires_at' => $quest['expires_at'],
                'cheese_config' => $cheese_config
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'quest' => null
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
