<?php
// 🐍 Snake Achievement Unlock API - Season 3 Compatible

// Suppress all errors and warnings to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Set proper headers for JSON response
header('Content-Type: application/json');

// Wrap everything in try-catch to ensure clean JSON output
try {
    // Error handling for local testing
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    
    // Check if database file exists
    if (!file_exists($dbPath)) {
        error_log("Database file not found: $dbPath");
        echo json_encode([
            'success' => false, 
            'error' => 'Database file not found locally. This is expected for local testing.',
            'local_test' => true
        ]);
        exit;
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 📦 Parse JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // ✅ Extract + validate input
    $user_id = $data['user_id'] ?? null;
    $achievement_key = $data['achievement_key'] ?? null;

    if (!$user_id || !$achievement_key) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing user_id or achievement_key']);
        exit;
    }

    // 🏆 Check if achievement definition exists, if not create it
    $checkDefStmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM tbl_snake_achievements 
        WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' AND achievement_key = ?
    ");
    $checkDefStmt->execute([$achievement_key]);
    $defExists = $checkDefStmt->fetch(PDO::FETCH_ASSOC);

    if ($defExists['count'] == 0) {
        // Create achievement definition
        $achievementData = [
            'first_apple' => ['title' => 'First Apple', 'description' => 'Eat your first apple', 'icon' => '🍎'],
            'apple_collector' => ['title' => 'Apple Collector', 'description' => 'Eat 5 apples total', 'icon' => '🍎'],
            'snake_grower' => ['title' => 'Snake Grower', 'description' => 'Eat 10 apples total', 'icon' => '🐍'],
            'apple_master' => ['title' => 'Apple Master', 'description' => 'Eat 25 apples total', 'icon' => '🍎'],
            'speed_demon' => ['title' => 'Speed Demon', 'description' => 'Reach level 5', 'icon' => '⚡'],
            'level_master' => ['title' => 'Level Master', 'description' => 'Reach level 10', 'icon' => '🏆'],
            'score_hunter' => ['title' => 'Score Hunter', 'description' => 'Reach 100 points', 'icon' => '🎯'],
            'point_master' => ['title' => 'Point Master', 'description' => 'Reach 250 points', 'icon' => '⭐'],
            'high_scorer' => ['title' => 'High Scorer', 'description' => 'Reach 500 points', 'icon' => '🌟'],
            'snake_king' => ['title' => 'Snake King', 'description' => 'Reach 1000 points', 'icon' => '👑'],
            'long_snake' => ['title' => 'Long Snake', 'description' => 'Grow to 10 segments', 'icon' => '🐍'],
            'giant_snake' => ['title' => 'Giant Snake', 'description' => 'Grow to 25 segments', 'icon' => '🐍'],
            'mega_snake' => ['title' => 'Mega Snake', 'description' => 'Grow to 50 segments', 'icon' => '🐍'],
            'survivor' => ['title' => 'Survivor', 'description' => 'Survive for 2 minutes', 'icon' => '⏰'],
            'endurance_master' => ['title' => 'Endurance Master', 'description' => 'Survive for 5 minutes', 'icon' => '⏰'],
            'level_warrior' => ['title' => 'Level Warrior', 'description' => 'Reach level 15', 'icon' => '⚔️'],
            'level_champion' => ['title' => 'Level Champion', 'description' => 'Reach level 20', 'icon' => '🏆'],
            'score_legend' => ['title' => 'Score Legend', 'description' => 'Reach 2000 points', 'icon' => '🌟'],
            'score_god' => ['title' => 'Score God', 'description' => 'Reach 5000 points', 'icon' => '👑'],
            'apple_legend' => ['title' => 'Apple Legend', 'description' => 'Eat 100 apples total', 'icon' => '🍎'],
            'snake_legend' => ['title' => 'Snake Legend', 'description' => 'Grow to 100 segments', 'icon' => '🐍']
        ];

        $defData = $achievementData[$achievement_key] ?? ['title' => 'Achievement', 'description' => 'Great job!', 'icon' => '🏆'];
        
        $createDefStmt = $db->prepare("
            INSERT INTO tbl_snake_achievements 
            (user_id, achievement_key, achievement_title, achievement_description, achievement_icon) 
            VALUES ('ACHIEVEMENT_DEFINITIONS', ?, ?, ?, ?)
        ");
        $createDefStmt->execute([$achievement_key, $defData['title'], $defData['description'], $defData['icon']]);
    }

    // 🏆 Check if user already has this achievement unlocked
    $checkUserStmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM tbl_snake_achievements 
        WHERE user_id = ? AND achievement_key = ? AND unlocked_at IS NOT NULL
    ");
    $checkUserStmt->execute([$user_id, $achievement_key]);
    $userHasIt = $checkUserStmt->fetch(PDO::FETCH_ASSOC);

    if ($userHasIt['count'] > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Achievement already unlocked',
            'already_unlocked' => true
        ]);
        exit;
    }

    // 🏆 Unlock the achievement for the user
    $unlockStmt = $db->prepare("
        INSERT INTO tbl_snake_achievements 
        (user_id, achievement_key, achievement_title, achievement_description, achievement_icon, unlocked_at) 
        SELECT ?, ?, achievement_title, achievement_description, achievement_icon, CURRENT_TIMESTAMP
        FROM tbl_snake_achievements 
        WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' AND achievement_key = ?
    ");
    $unlockStmt->execute([$user_id, $achievement_key, $achievement_key]);

    error_log("🐍 Snake achievement unlocked: $achievement_key for user $user_id");

    echo json_encode([
        'success' => true, 
        'message' => 'Achievement unlocked successfully',
        'achievement_key' => $achievement_key
    ]);

} catch (Exception $e) {
    // Log the exception for debugging purposes
    error_log("Snake achievement unlock error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Internal server error: ' . $e->getMessage(),
        'local_test' => false
    ]);
}
?>
