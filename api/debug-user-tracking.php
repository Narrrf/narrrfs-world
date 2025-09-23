<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? '';
    
    if (empty($userId)) {
        echo json_encode([
            'success' => false,
            'error' => 'User ID is required'
        ]);
        exit;
    }
    
    // Get database connection
    $pdo = getSQLite3Connection();
    
    // Check user tracking status
    $trackingData = [];
    
    // 1. Check if user exists in tbl_users
    $stmt = $pdo->prepare("SELECT discord_id, discord_name, created_at FROM tbl_users WHERE discord_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $trackingData['user_exists'] = true;
        $trackingData['user_name'] = $user['discord_name'];
        $trackingData['user_created'] = $user['created_at'];
    } else {
        $trackingData['user_exists'] = false;
    }
    
    // 2. Check cheese clicks
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_clicks, MAX(created_at) as last_click FROM tbl_cheese_clicks WHERE user_wallet = ?");
    $stmt->execute([$userId]);
    $cheeseData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $trackingData['cheese_clicks'] = [
        'total' => (int)$cheeseData['total_clicks'],
        'last_click' => $cheeseData['last_click']
    ];
    
    // 3. Check recent clicks (last 10)
    $stmt = $pdo->prepare("SELECT clicks, created_at FROM tbl_cheese_clicks WHERE user_wallet = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$userId]);
    $recentClicks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $trackingData['recent_clicks'] = $recentClicks;
    
    // 4. Check game scores
    $stmt = $pdo->prepare("SELECT game, COUNT(*) as games_played, MAX(score) as best_score FROM tbl_tetris_scores WHERE discord_id = ? GROUP BY game");
    $stmt->execute([$userId]);
    $gameScores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $trackingData['game_scores'] = $gameScores;
    
    // 5. Check race participation
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_races, COUNT(CASE WHEN position = 1 THEN 1 END) as wins FROM tbl_race_participants WHERE user_id = ?");
    $stmt->execute([$userId]);
    $raceData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $trackingData['race_participation'] = [
        'total_races' => (int)$raceData['total_races'],
        'wins' => (int)$raceData['wins']
    ];
    
    // 6. Check user roles
    $stmt = $pdo->prepare("SELECT role_name FROM tbl_user_roles WHERE discord_id = ?");
    $stmt->execute([$userId]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $trackingData['user_roles'] = $roles;
    
    // 7. Check quest participation
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_quests, COUNT(CASE WHEN claimed_at IS NOT NULL THEN 1 END) as claimed_quests FROM tbl_quest_claims WHERE user_id = ?");
    $stmt->execute([$userId]);
    $questData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $trackingData['quest_participation'] = [
        'total_quests' => (int)$questData['total_quests'],
        'claimed_quests' => (int)$questData['claimed_quests']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $trackingData,
        'message' => 'Tracking status retrieved successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Debug user tracking error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve tracking status: ' . $e->getMessage()
    ]);
}
?>
