<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// User API - no admin authentication required

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../db/narrrf_world.sqlite' 
        : '/var/www/html/db/narrrf_world.sqlite';
    
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

try {
    // Get Discord ID from POST data or query parameters
    $discordId = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $discordId = $input['user_id'] ?? $input['discord_id'] ?? '';
    } else {
        $discordId = $_GET['discord_id'] ?? $_GET['user_id'] ?? '';
    }
    
    if (empty($discordId)) {
        throw new Exception('Discord ID is required');
    }
    
    // Validate Discord ID format
    if (!preg_match('/^\d{17,19}$/', $discordId)) {
        throw new Exception('Invalid Discord ID format');
    }
    
    $pdo = getSQLite3Connection();
    
    // Fetch achievement DEFINITIONS from database (LIKE TETRIS AND SNAKE!)
    $stmt = $pdo->prepare("
        SELECT 
            achievement_key,
            achievement_title,
            achievement_description,
            achievement_icon
        FROM tbl_space_invaders_achievements 
        WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' 
        ORDER BY achievement_key
    ");
    
    $stmt->execute();
    $definitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build achievement definitions array from database
    $allAchievements = [];
    foreach ($definitions as $def) {
        $allAchievements[$def['achievement_key']] = [
            'title' => $def['achievement_title'],
            'description' => $def['achievement_description'],
            'icon' => $def['achievement_icon']
        ];
    }
    
    // Fetch user's unlocked Space Cheese Invaders achievements
    $stmt = $pdo->prepare("
        SELECT 
            achievement_key,
            achievement_title,
            achievement_description,
            achievement_icon,
            unlocked_at,
            game_score,
            game_time,
            total_kills,
            combo_multiplier
        FROM tbl_space_invaders_achievements 
        WHERE user_id = ? 
        ORDER BY unlocked_at DESC
    ");
    
    $stmt->execute([$discordId]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process achievements data
    $processedAchievements = [];
    $unlockedKeys = [];
    
    foreach ($achievements as $achievement) {
        $processedAchievements[] = [
            'key' => $achievement['achievement_key'],
            'achievement_title' => $achievement['achievement_title'],
            'achievement_description' => $achievement['achievement_description'],
            'achievement_icon' => $achievement['achievement_icon'],
            'unlocked_at' => $achievement['unlocked_at'],
            'game_score' => (int)$achievement['game_score'],
            'game_time' => (int)$achievement['game_time'],
            'total_kills' => (int)$achievement['total_kills'],
            'combo_multiplier' => (int)$achievement['combo_multiplier'],
            'unlocked' => true
        ];
        $unlockedKeys[] = $achievement['achievement_key'];
    }
    
    // Add locked achievements
    foreach ($allAchievements as $key => $achievement) {
        if (!in_array($key, $unlockedKeys)) {
            $processedAchievements[] = [
                'key' => $key,
                'achievement_title' => $achievement['title'],
                'achievement_description' => $achievement['description'],
                'achievement_icon' => $achievement['icon'],
                'unlocked_at' => null,
                'game_score' => 0,
                'game_time' => 0,
                'total_kills' => 0,
                'combo_multiplier' => 0,
                'unlocked' => false
            ];
        }
    }
    
    // Sort achievements: unlocked first (by unlock date), then locked
    usort($processedAchievements, function($a, $b) {
        if ($a['unlocked'] && $b['unlocked']) {
            return strtotime($b['unlocked_at']) - strtotime($a['unlocked_at']);
        } elseif ($a['unlocked'] && !$b['unlocked']) {
            return -1;
        } elseif (!$a['unlocked'] && $b['unlocked']) {
            return 1;
        } else {
            return 0;
        }
    });
    
    // Calculate statistics
    $stats = [
        'total_achievements' => count($allAchievements),
        'unlocked_achievements' => count($unlockedKeys),
        'locked_achievements' => count($allAchievements) - count($unlockedKeys),
        'completion_percentage' => count($allAchievements) > 0 ? round((count($unlockedKeys) / count($allAchievements)) * 100, 1) : 0,
        'latest_achievement' => !empty($achievements) ? $achievements[0] : null
    ];
    
    echo json_encode([
        'success' => true,
        'achievements' => $processedAchievements,
        'stats' => $stats,
        'user_id' => $discordId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'achievements' => [],
        'stats' => [
            'total_achievements' => 0,
            'unlocked_achievements' => 0,
            'locked_achievements' => 0,
            'completion_percentage' => 0,
            'latest_achievement' => null
        ]
    ]);
}
?>
