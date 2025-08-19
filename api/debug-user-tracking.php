<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id parameter']);
    exit;
}

$userId = trim($input['user_id']);

try {
    // Use centralized database configuration
    require_once __DIR__ . '/config/database.php';
    $pdo = getDatabaseConnection();

    // Check user existence in tbl_users
    $stmt = $pdo->prepare("SELECT discord_id, username, created_at FROM tbl_users WHERE discord_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get cheese click statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_clicks,
            COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
            COUNT(CASE WHEN quest_id IS NULL THEN 1 END) as non_quest_clicks,
            COUNT(DISTINCT egg_id) as unique_eggs_clicked,
            MIN(timestamp) as first_click,
            MAX(timestamp) as last_click
        FROM tbl_cheese_clicks 
        WHERE user_wallet = ?
    ");
    $stmt->execute([$userId]);
    $clickStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent clicks (last 10)
    $stmt = $pdo->prepare("
        SELECT egg_id, timestamp, quest_id 
        FROM tbl_cheese_clicks 
        WHERE user_wallet = ? 
        ORDER BY timestamp DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $recentClicks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get quest claims
    $stmt = $pdo->prepare("
        SELECT qc.quest_id, qc.status, qc.claimed_at, q.description 
        FROM tbl_quest_claims qc
        LEFT JOIN tbl_quests q ON qc.quest_id = q.quest_id
        WHERE qc.user_id = ?
        ORDER BY qc.claimed_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $questClaims = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'user_info' => $user,
        'click_statistics' => $clickStats,
        'recent_clicks' => $recentClicks,
        'quest_claims' => $questClaims,
        'validation' => [
            'user_exists' => $user !== false,
            'has_clicks' => (int)$clickStats['total_clicks'] > 0,
            'recent_activity' => count($recentClicks) > 0,
            'quest_participation' => (int)$clickStats['quest_clicks'] > 0
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
