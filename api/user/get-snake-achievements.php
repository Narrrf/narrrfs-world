<?php
// 🐍 Snake Achievement API - Season 3 Compatible

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../db/narrrf_world.sqlite' 
        : '/var/www/html/db/narrrf_world.sqlite';
    
    if (!file_exists($dbPath)) {
        return null;
    }
    
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

try {
    $pdo = getSQLite3Connection();
    
    if (!$pdo) {
        throw new Exception('Failed to connect to database');
    }
    
    // Get user_id from request
    $user_id = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = $input['user_id'] ?? $input['discord_id'] ?? null;
    } else {
        $user_id = $_GET['user_id'] ?? $_GET['discord_id'] ?? null;
    }
    
    if (!$user_id) {
        throw new Exception('User ID is required');
    }
    
    // Get all Snake achievements from database
    $stmt = $pdo->prepare("
        SELECT achievement_key, achievement_title, achievement_description, achievement_icon 
        FROM tbl_snake_achievements 
        WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
        ORDER BY id
    ");
    $stmt->execute();
    $allAchievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allAchievements)) {
        throw new Exception('No Snake achievements found in database');
    }
    
    // Get user's unlocked achievements with achievement details (Season 3 compatible)
    $stmt = $pdo->prepare("
        SELECT 
            u.achievement_key, 
            u.unlocked_at, 
            u.game_score, 
            u.apples_eaten, 
            u.level_reached, 
            u.games_played, 
            u.longest_snake,
            d.achievement_title,
            d.achievement_description,
            d.achievement_icon
        FROM tbl_snake_achievements u
        JOIN tbl_snake_achievements d ON u.achievement_key = d.achievement_key
        WHERE u.user_id = :user_id 
        AND u.user_id != 'ACHIEVEMENT_DEFINITIONS'
        AND d.user_id = 'ACHIEVEMENT_DEFINITIONS'
        ORDER BY u.unlocked_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $unlockedAchievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create unlocked lookup
    $unlockedLookup = [];
    foreach ($unlockedAchievements as $unlocked) {
        $unlockedLookup[$unlocked['achievement_key']] = $unlocked;
    }
    
    // Process achievements data
    $processedAchievements = [];
    $unlockedKeys = [];
    
    // Add unlocked achievements first
    foreach ($unlockedAchievements as $achievement) {
        $processedAchievements[] = [
            'key' => $achievement['achievement_key'],
            'achievement_title' => $achievement['achievement_title'],
            'achievement_description' => $achievement['achievement_description'],
            'achievement_icon' => $achievement['achievement_icon'],
            'unlocked_at' => $achievement['unlocked_at'],
            'game_score' => (int)($achievement['game_score'] ?? 0),
            'apples_eaten' => (int)($achievement['apples_eaten'] ?? 0),
            'level_reached' => (int)($achievement['level_reached'] ?? 0),
            'games_played' => (int)($achievement['games_played'] ?? 0),
            'longest_snake' => (int)($achievement['longest_snake'] ?? 0),
            'unlocked' => true
        ];
        $unlockedKeys[] = $achievement['achievement_key'];
    }
    
    // Add locked achievements
    foreach ($allAchievements as $achievement) {
        $key = $achievement['achievement_key'];
        if (!in_array($key, $unlockedKeys)) {
            $processedAchievements[] = [
                'key' => $key,
                'achievement_title' => $achievement['achievement_title'],
                'achievement_description' => $achievement['achievement_description'],
                'achievement_icon' => $achievement['achievement_icon'],
                'unlocked_at' => null,
                'game_score' => 0,
                'apples_eaten' => 0,
                'level_reached' => 0,
                'games_played' => 0,
                'longest_snake' => 0,
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
        'latest_achievement' => !empty($unlockedAchievements) ? $unlockedAchievements[0] : null
    ];
    
    echo json_encode([
        'success' => true,
        'achievements' => $processedAchievements,
        'statistics' => $stats,
        'user_id' => $user_id
    ]);
    
} catch (Exception $e) {
    error_log("Snake achievements API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>