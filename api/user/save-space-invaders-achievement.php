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

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../db/narrrf_world.sqlite' 
        : '/var/www/html/db/narrrf_world.sqlite';
    
    if (!file_exists($dbPath)) {
        throw new Exception("Database file not found: $dbPath");
    }
    
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $requiredFields = ['user_id', 'achievement_key', 'achievement_title', 'achievement_description', 'achievement_icon'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $userId = $input['user_id'];
    $achievementKey = $input['achievement_key'];
    $achievementTitle = $input['achievement_title'];
    $achievementDescription = $input['achievement_description'];
    $achievementIcon = $input['achievement_icon'];
    
    // Optional fields with defaults
    $gameScore = $input['game_score'] ?? 0;
    $gameTime = $input['game_time'] ?? 0;
    $totalKills = $input['total_kills'] ?? 0;
    $comboMultiplier = $input['combo_multiplier'] ?? 0;
    
    // Validate Discord ID format
    if (!preg_match('/^\d{17,19}$/', $userId)) {
        throw new Exception('Invalid Discord ID format');
    }
    
    $pdo = getSQLite3Connection();
    
    // Check if achievement already exists for this user
    $checkStmt = $pdo->prepare("
        SELECT id FROM tbl_space_invaders_achievements 
        WHERE user_id = ? AND achievement_key = ?
    ");
    $checkStmt->execute([$userId, $achievementKey]);
    
    if ($checkStmt->fetch()) {
        // Achievement already exists, update it
        $updateStmt = $pdo->prepare("
            UPDATE tbl_space_invaders_achievements 
            SET 
                achievement_title = ?,
                achievement_description = ?,
                achievement_icon = ?,
                unlocked_at = CURRENT_TIMESTAMP,
                game_score = ?,
                game_time = ?,
                total_kills = ?,
                combo_multiplier = ?
            WHERE user_id = ? AND achievement_key = ?
        ");
        
        $updateStmt->execute([
            $achievementTitle,
            $achievementDescription,
            $achievementIcon,
            $gameScore,
            $gameTime,
            $totalKills,
            $comboMultiplier,
            $userId,
            $achievementKey
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Achievement updated successfully',
            'action' => 'updated',
            'achievement_key' => $achievementKey
        ]);
    } else {
        // Insert new achievement
        $insertStmt = $pdo->prepare("
            INSERT INTO tbl_space_invaders_achievements 
            (user_id, achievement_key, achievement_title, achievement_description, achievement_icon, 
             unlocked_at, game_score, game_time, total_kills, combo_multiplier)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?)
        ");
        
        $insertStmt->execute([
            $userId,
            $achievementKey,
            $achievementTitle,
            $achievementDescription,
            $achievementIcon,
            $gameScore,
            $gameTime,
            $totalKills,
            $comboMultiplier
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Achievement unlocked successfully',
            'action' => 'unlocked',
            'achievement_key' => $achievementKey
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
