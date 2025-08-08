<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once '../config/database.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Admin access required']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    // Create settings table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_space_invaders_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Initialize default settings if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM tbl_space_invaders_settings");
    if ($stmt->fetchColumn() == 0) {
        $defaultSettings = [
            ['bomb_activation_level', '4', 'Wave level when bombs start appearing'],
            ['bomb_spawn_chance', '0.2', 'Chance of spawning bombs (0.0-1.0)'],
            ['bomb_damage_multiplier', '2', 'Damage multiplier for bomb hits'],
            ['snake_dna_activation_level', '1', 'Wave level when snake DNA appears'],
            ['snake_head_activation_level', '2', 'Wave level when snake heads appear'],
            ['tetris_spawn_interval_base', '8000', 'Base spawn interval for Tetris blocks (ms)'],
            ['invader_shoot_speed_base', '300', 'Base shooting interval for invaders (ms)'],
            ['game_speed_base', '0.1', 'Base game speed multiplier'],
            ['dspoin_rewards_enabled', '0', 'Enable DSPOINC rewards for Space Invaders (0=off, 1=on)'],
            ['dspoin_conversion_rate', '10000', 'Points needed for 1 DSPOINC (default: 10,000 points = 1 DSPOINC)']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO tbl_space_invaders_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        foreach ($defaultSettings as $setting) {
            $stmt->execute($setting);
        }
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get all settings
            $stmt = $pdo->query("SELECT * FROM tbl_space_invaders_settings ORDER BY setting_key");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'settings' => $settings
            ]);
            break;
            
        case 'POST':
            // Update settings
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['settings']) || !is_array($input['settings'])) {
                throw new Exception('Settings array required');
            }
            
            $stmt = $pdo->prepare("UPDATE tbl_space_invaders_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?");
            
            foreach ($input['settings'] as $setting) {
                if (isset($setting['key']) && isset($setting['value'])) {
                    $stmt->execute([$setting['value'], $setting['key']]);
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
            break;
            
        case 'PUT':
            // Update single setting
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['key']) || !isset($input['value'])) {
                throw new Exception('Key and value required');
            }
            
            $stmt = $pdo->prepare("UPDATE tbl_space_invaders_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?");
            $stmt->execute([$input['value'], $input['key']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Setting updated successfully'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
