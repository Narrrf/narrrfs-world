<?php
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
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
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
    
    // Validate Discord ID format (allow test users like "1337")
    if (!preg_match('/^\d{4,19}$/', $user_id)) {
        throw new Exception('Invalid Discord ID format');
    }
    
    // Check if table exists
    $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_tetris_achievements'");
    if (!$tableCheck->fetchColumn()) {
        throw new Exception('Table tbl_tetris_achievements does not exist');
    }
    
    // Get all achievement definitions
    $definitionsQuery = "
        SELECT DISTINCT 
            achievement_key,
            achievement_title,
            achievement_description,
            achievement_icon,
            game_score,
            lines_cleared,
            level_reached,
            pieces_dropped,
            tetris_clears
        FROM tbl_tetris_achievements 
        WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
        ORDER BY 
            CASE 
                WHEN achievement_key LIKE 'first_%' THEN 1
                WHEN achievement_key LIKE 'line_%' THEN 2
                WHEN achievement_key LIKE 'tetris_%' THEN 3
                WHEN achievement_key LIKE 'score_%' THEN 4
                WHEN achievement_key LIKE 'level_%' THEN 5
                WHEN achievement_key LIKE 'piece_%' THEN 6
                WHEN achievement_key LIKE 'combo_%' THEN 7
                WHEN achievement_key LIKE 'back_%' THEN 8
                WHEN achievement_key LIKE 'perfect_%' THEN 9
                WHEN achievement_key LIKE 'ultimate_%' THEN 10
                WHEN achievement_key LIKE 'champion' THEN 11
                ELSE 12
            END,
            achievement_key
    ";
    
    $definitionsStmt = $pdo->prepare($definitionsQuery);
    $definitionsStmt->execute();
    $definitions = $definitionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's unlocked achievements (Season 3 compatible)
    $unlockedQuery = "
        SELECT achievement_key, unlocked_at, game_score, lines_cleared, level_reached, pieces_dropped, tetris_clears
        FROM tbl_tetris_achievements 
        WHERE user_id = ? AND user_id != 'ACHIEVEMENT_DEFINITIONS'
        ORDER BY unlocked_at DESC
    ";
    
    $unlockedStmt = $pdo->prepare($unlockedQuery);
    $unlockedStmt->execute([$user_id]);
    $unlockedAchievements = $unlockedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create lookup for unlocked achievements
    $unlockedLookup = [];
    foreach ($unlockedAchievements as $achievement) {
        $unlockedLookup[$achievement['achievement_key']] = $achievement;
    }
    
    // Combine definitions with unlock status
    $achievements = [];
    foreach ($definitions as $definition) {
        $key = $definition['achievement_key'];
        $isUnlocked = isset($unlockedLookup[$key]);
        
        $achievements[] = [
            'key' => $key,
            'achievement_title' => $definition['achievement_title'],
            'achievement_description' => $definition['achievement_description'],
            'achievement_icon' => $definition['achievement_icon'],
            'unlocked' => $isUnlocked,
            'unlocked_at' => $isUnlocked ? $unlockedLookup[$key]['unlocked_at'] : null,
            'requirements' => [
                'game_score' => $definition['game_score'],
                'lines_cleared' => $definition['lines_cleared'],
                'level_reached' => $definition['level_reached'],
                'pieces_dropped' => $definition['pieces_dropped'],
                'tetris_clears' => $definition['tetris_clears']
            ],
            'progress' => $isUnlocked ? $unlockedLookup[$key] : null
        ];
    }
    
    // Calculate statistics
    $totalAchievements = count($achievements);
    $unlockedCount = count($unlockedLookup); // Use deduplicated count
    $unlockedPercentage = $totalAchievements > 0 ? round(($unlockedCount / $totalAchievements) * 100, 1) : 0;
    
    echo json_encode([
        'success' => true,
        'achievements' => $achievements,
        'statistics' => [
            'total' => $totalAchievements,
            'unlocked' => $unlockedCount,
            'locked' => $totalAchievements - $unlockedCount,
            'percentage' => $unlockedPercentage
        ],
        'user_id' => $user_id
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
