<?php
/**
 * 🔥 INSTANT GAME UPDATE SYSTEM - Push configuration changes to active games
 * 
 * This API allows admins to push configuration changes INSTANTLY to all active games
 * without requiring players to refresh or restart their games.
 * 
 * Supported actions:
 * - phoenix_update: Update Phoenix swarm configuration
 * - boss_update: Update boss configuration
 * - game_settings_update: Update general game settings
 * 
 * @author Narrrf's World Admin System
 * @version 12.0
 * @date 2025-01-28
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get database connection
function getSQLite3Connection() {
    $dbPath = '/data/narrrf_world.sqlite';
    
    // Check if production database exists
    if (file_exists($dbPath)) {
        try {
            $pdo = new PDO("sqlite:$dbPath");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            error_log("Production database connection failed: " . $e->getMessage());
        }
    }
    
    // Fallback to local database
    $localDbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    if (file_exists($localDbPath)) {
        try {
            $pdo = new PDO("sqlite:$localDbPath");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            error_log("Local database connection failed: " . $e->getMessage());
        }
    }
    
    throw new Exception("No database connection available");
}

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Action required');
    }
    
    $action = $input['action'];
    $config = $input['config'] ?? null;
    
    // Get database connection
    $pdo = getSQLite3Connection();
    
    // Process different action types
    switch ($action) {
        case 'phoenix_update':
            if (!$config) {
                throw new Exception('Phoenix configuration required');
            }
            
            // Update Phoenix configuration in database
            $result = updatePhoenixConfiguration($pdo, $config);
            
            // Log the update
            logGameUpdate($pdo, 'phoenix_update', $config);
            
            echo json_encode([
                'success' => true,
                'message' => 'Phoenix configuration updated and pushed to active games',
                'action' => 'phoenix_update',
                'config' => $config,
                'timestamp' => date('Y-m-d H:i:s'),
                'affected_games' => 'all_active_space_invaders'
            ]);
            break;
            
        case 'boss_update':
            if (!$config) {
                throw new Exception('Boss configuration required');
            }
            
            // Update boss configuration in database
            $result = updateBossConfiguration($pdo, $config);
            
            // Log the update
            logGameUpdate($pdo, 'boss_update', $config);
            
            echo json_encode([
                'success' => true,
                'message' => 'Boss configuration updated and pushed to active games',
                'action' => 'boss_update',
                'config' => $config,
                'timestamp' => date('Y-m-d H:i:s'),
                'affected_games' => 'all_active_space_invaders'
            ]);
            break;
            
        case 'game_settings_update':
            if (!$config) {
                throw new Exception('Game settings configuration required');
            }
            
            // Update general game settings
            $result = updateGameSettings($pdo, $config);
            
            // Log the update
            logGameUpdate($pdo, 'game_settings_update', $config);
            
            echo json_encode([
                'success' => true,
                'message' => 'Game settings updated and pushed to active games',
                'action' => 'game_settings_update',
                'config' => $config,
                'timestamp' => date('Y-m-d H:i:s'),
                'affected_games' => 'all_active_games'
            ]);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    error_log("Push game update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Update Phoenix configuration in database
 */
function updatePhoenixConfiguration($pdo, $config) {
    // Update space invaders settings table with Phoenix configuration
    $settings = [
        'phoenix_base_count' => $config['basePhoenixCount'] ?? 3,
        'phoenix_max_per_wave' => $config['maxPhoenixPerWave'] ?? 8,
        'phoenix_wave_frequency' => $config['waveFrequency'] ?? 3,
        'phoenix_base_health' => $config['phoenixHealth'] ?? 80,
        'phoenix_mini_health' => $config['miniPhoenixHealth'] ?? 25,
        'phoenix_difficulty_scaling' => $config['difficultyScaling'] ?? 1.1,
        'phoenix_egg_laying_rate' => $config['eggLayingRate'] ?? 0.15,
        'phoenix_egg_hatch_time' => $config['eggHatchTime'] ?? 450,
        'phoenix_egg_cooldown' => $config['eggCooldown'] ?? 120,
        'phoenix_speed' => $config['phoenixSpeed'] ?? 2.0,
        'phoenix_formation_patterns' => is_array($config['formationPatterns']) ? 
            implode(',', $config['formationPatterns']) : ($config['formationPatterns'] ?? 'v,diamond,spiral'),
        'phoenix_wave_announcement' => $config['waveAnnouncement'] ? 'true' : 'false'
    ];
    
    // Insert or update each setting
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT OR REPLACE INTO tbl_space_invaders_settings 
            (setting_key, setting_value, description, created_at, updated_at) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$key, $value, "Phoenix swarm setting: $key"]);
    }
    
    return true;
}

/**
 * Update boss configuration in database
 */
function updateBossConfiguration($pdo, $config) {
    // Update boss configurations table
    if (isset($config['bossType']) && isset($config['bossConfig'])) {
        $bossType = $config['bossType'];
        $bossConfig = $config['bossConfig'];
        
        $stmt = $pdo->prepare("
            INSERT OR REPLACE INTO boss_configurations 
            (boss_type, name, description, base_health, health_multiplier, base_speed, 
             speed_multiplier, base_attack_cooldown, attack_cooldown_multiplier, 
             base_bullet_speed, bullet_speed_multiplier, base_bullet_damage, 
             bullet_damage_multiplier, size, movement_patterns, attack_patterns, 
             abilities, special_attack_chance, rage_mode_threshold, rage_mode_multipliers, 
             colors, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            $bossType,
            $bossConfig['name'] ?? 'Unknown Boss',
            $bossConfig['description'] ?? '',
            $bossConfig['baseHealth'] ?? 100,
            $bossConfig['healthMultiplier'] ?? 1.0,
            $bossConfig['baseSpeed'] ?? 1.0,
            $bossConfig['speedMultiplier'] ?? 1.0,
            $bossConfig['baseAttackCooldown'] ?? 1000,
            $bossConfig['attackCooldownMultiplier'] ?? 1.0,
            $bossConfig['baseBulletSpeed'] ?? 5.0,
            $bossConfig['bulletSpeedMultiplier'] ?? 1.0,
            $bossConfig['baseBulletDamage'] ?? 10,
            $bossConfig['bulletDamageMultiplier'] ?? 1.0,
            $bossConfig['size'] ?? 1.0,
            json_encode($bossConfig['movementPatterns'] ?? []),
            json_encode($bossConfig['attackPatterns'] ?? []),
            json_encode($bossConfig['abilities'] ?? []),
            $bossConfig['specialAttackChance'] ?? 0.3,
            $bossConfig['rageModeThreshold'] ?? 0.3,
            json_encode($bossConfig['rageModeMultipliers'] ?? []),
            json_encode($bossConfig['colors'] ?? [])
        ]);
    }
    
    return true;
}

/**
 * Update general game settings
 */
function updateGameSettings($pdo, $config) {
    // Update game settings table
    if (isset($config['gameType']) && isset($config['settings'])) {
        $gameType = $config['gameType'];
        $settings = $config['settings'];
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT OR REPLACE INTO tbl_game_settings 
                (game_type, setting_key, setting_value, updated_at) 
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$gameType, $key, $value]);
        }
    }
    
    return true;
}

/**
 * Log game updates for audit trail
 */
function logGameUpdate($pdo, $action, $config) {
    try {
        // Create game updates log table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tbl_game_updates_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                action TEXT NOT NULL,
                config_data TEXT NOT NULL,
                admin_id TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                ip_address TEXT,
                user_agent TEXT
            )
        ");
        
        // Log the update
        $stmt = $pdo->prepare("
            INSERT INTO tbl_game_updates_log 
            (action, config_data, admin_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $action,
            json_encode($config),
            'admin_system', // Could be enhanced to get actual admin ID
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
    } catch (Exception $e) {
        error_log("Failed to log game update: " . $e->getMessage());
    }
}
?>
