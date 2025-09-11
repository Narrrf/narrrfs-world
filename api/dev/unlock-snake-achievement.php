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

    // 🏆 Check if achievement is already unlocked (achievements are lifetime, not season-specific)
    $checkStmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM tbl_snake_achievements 
        WHERE user_id = ? AND achievement_key = ? AND unlocked_at IS NOT NULL
    ");
    $checkStmt->execute([$user_id, $achievement_key]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing['count'] > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Achievement already unlocked',
            'already_unlocked' => true
        ]);
        exit;
    }

    // 🏆 Unlock the achievement (achievements are lifetime accomplishments)
    $unlockStmt = $db->prepare("
        INSERT OR REPLACE INTO tbl_snake_achievements 
        (user_id, achievement_key, unlocked_at) 
        VALUES (?, ?, CURRENT_TIMESTAMP)
    ");
    $unlockStmt->execute([$user_id, $achievement_key]);

    error_log("🐍 Snake achievement unlocked: $achievement_key for user $user_id");

    echo json_encode([
        'success' => true, 
        'message' => 'Achievement unlocked successfully',
        'achievement_key' => $achievement_key,
        'season' => $currentSeason
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
