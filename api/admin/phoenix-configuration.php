<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Opions: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Local development bypass
$isLocalDevelopment = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);

// Check admin authentication for production
session_start();
if (!$isLocalDevelopment && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Admin access required']);
    exit;
}

try {
    require_once '../config/database.php';
    $pdo = getDatabaseConnection();
    
    // Get Phoenix-specific settings from space-invaders-settings table
    $phoenixKeys = [
        'phoenix_base_count', 'phoenix_max_per_wave', 'phoenix_wave_frequency',
        'phoenix_base_health', 'phoenix_mini_health', 'phoenix_difficulty_scaling',
        'phoenix_egg_laying_rate', 'phoenix_egg_hatch_time', 'phoenix_egg_cooldown',
        'phoenix_speed', 'phoenix_formation_patterns', 'phoenix_wave_announcement'
    ];
    
    // Create settings table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_space_invaders_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $placeholders = str_repeat('?,', count($phoenixKeys) - 1) . '?';
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM tbl_space_invaders_settings WHERE setting_key IN ($placeholders)");
    $stmt->execute($phoenixKeys);
    $phoenixSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert array to object format expected by Space Invaders script
    $phoenixConfig = [];
    foreach ($phoenixSettings as $setting) {
        $key = str_replace('phoenix_', '', $setting['setting_key']); // Remove phoenix_ prefix
        $value = $setting['setting_value'];
        
        // Convert string values to appropriate types
        if (is_numeric($value)) {
            $value = (float)$value;
        } elseif ($value === 'true' || $value === 'false') {
            $value = $value === 'true';
        } elseif (strpos($value, ',') !== false) {
            $value = explode(',', $value);
        }
        
        $phoenixConfig[$key] = $value;
    }
    
    // Provide default values if no settings found
    if (empty($phoenixConfig)) {
        $phoenixConfig = [
            'base_count' => 3,
            'max_per_wave' => 8,
            'wave_frequency' => 3,
            'base_health' => 80,
            'mini_health' => 25,
            'difficulty_scaling' => 1.1,
            'egg_laying_rate' => 15,
            'egg_hatch_time' => 450,
            'egg_cooldown' => 120,
            'speed' => 2.0,
            'formation_patterns' => ['v', 'diamond', 'spiral'],
            'wave_announcement' => true
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $phoenixConfig
    ]);
    
} catch (Exception $e) {
    // Return default configuration on error
    echo json_encode([
        'success' => true,
        'data' => [
            'base_count' => 3,
            'max_per_wave' => 8,
            'wave_frequency' => 3,
            'base_health' => 80,
            'mini_health' => 25,
            'difficulty_scaling' => 1.1,
            'egg_laying_rate' => 15,
            'egg_hatch_time' => 450,
            'egg_cooldown' => 120,
            'speed' => 2.0,
            'formation_patterns' => ['v', 'diamond', 'spiral'],
            'wave_announcement' => true
        ]
    ]);
}
?>
