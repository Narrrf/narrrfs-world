<?php
/**
 * 🔥 ACTIVE GAME CONFIGURATION RETRIEVER
 * 
 * This API retrieves the current active configuration for all games,
 * including Phoenix swarm settings, boss configurations, and game settings.
 * 
 * @author Narrrf's World Admin System
 * @version 12.0
 * @date 2025-01-28
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    // Get database connection
    $pdo = getSQLite3Connection();
    
    // Get Phoenix swarm configuration
    $phoenixConfig = getPhoenixConfiguration($pdo);
    
    // Get boss configurations
    $bossConfigs = getBossConfigurations($pdo);
    
    // Get game settings
    $gameSettings = getGameSettings($pdo);
    
    // Get current season settings
    $seasonSettings = getSeasonSettings($pdo);
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'phoenix' => $phoenixConfig,
        'bosses' => $bossConfigs,
        'game_settings' => $gameSettings,
        'season' => $seasonSettings
    ]);
    
} catch (Exception $e) {
    error_log("Get active game config error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Get Phoenix swarm configuration
 */
function getPhoenixConfiguration($pdo) {
    try {
        // Check if space invaders settings table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tbl_space_invaders_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT UNIQUE NOT NULL,
                setting_value TEXT NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Get Phoenix settings
        $stmt = $pdo->prepare("
            SELECT setting_key, setting_value 
            FROM tbl_space_invaders_settings 
            WHERE setting_key LIKE 'phoenix_%'
        ");
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Return with defaults if no settings found
        return [
            'basePhoenixCount' => intval($settings['phoenix_base_count'] ?? 3),
            'maxPhoenixPerWave' => intval($settings['phoenix_max_per_wave'] ?? 8),
            'waveFrequency' => intval($settings['phoenix_wave_frequency'] ?? 3),
            'phoenixHealth' => intval($settings['phoenix_base_health'] ?? 80),
            'miniPhoenixHealth' => intval($settings['phoenix_mini_health'] ?? 25),
            'difficultyScaling' => floatval($settings['phoenix_difficulty_scaling'] ?? 1.1),
            'eggLayingRate' => floatval($settings['phoenix_egg_laying_rate'] ?? 0.15),
            'eggHatchTime' => intval($settings['phoenix_egg_hatch_time'] ?? 450),
            'eggCooldown' => intval($settings['phoenix_egg_cooldown'] ?? 120),
            'phoenixSpeed' => floatval($settings['phoenix_speed'] ?? 2.0),
            'formationPatterns' => explode(',', $settings['phoenix_formation_patterns'] ?? 'v,diamond,spiral'),
            'waveAnnouncement' => ($settings['phoenix_wave_announcement'] ?? 'true') === 'true'
        ];
        
    } catch (Exception $e) {
        error_log("Error getting Phoenix configuration: " . $e->getMessage());
        // Return default configuration
        return [
            'basePhoenixCount' => 3,
            'maxPhoenixPerWave' => 8,
            'waveFrequency' => 3,
            'phoenixHealth' => 80,
            'miniPhoenixHealth' => 25,
            'difficultyScaling' => 1.1,
            'eggLayingRate' => 0.15,
            'eggHatchTime' => 450,
            'eggCooldown' => 120,
            'phoenixSpeed' => 2.0,
            'formationPatterns' => ['v', 'diamond', 'spiral'],
            'waveAnnouncement' => true
        ];
    }
}

/**
 * Get boss configurations
 */
function getBossConfigurations($pdo) {
    try {
        // Check if boss configurations table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS boss_configurations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                boss_type TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                base_health INTEGER NOT NULL,
                health_multiplier REAL DEFAULT 1.0,
                base_speed REAL NOT NULL,
                speed_multiplier REAL DEFAULT 1.0,
                base_attack_cooldown INTEGER NOT NULL,
                attack_cooldown_multiplier REAL DEFAULT 1.0,
                base_bullet_speed REAL NOT NULL,
                bullet_speed_multiplier REAL DEFAULT 1.0,
                base_bullet_damage INTEGER NOT NULL,
                bullet_damage_multiplier REAL DEFAULT 1.0,
                size REAL DEFAULT 1.0,
                movement_patterns TEXT,
                attack_patterns TEXT,
                abilities TEXT,
                special_attack_chance REAL DEFAULT 0.3,
                rage_mode_threshold REAL DEFAULT 0.3,
                rage_mode_multipliers TEXT,
                colors TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Get all boss configurations
        $stmt = $pdo->prepare("
            SELECT * FROM boss_configurations 
            ORDER BY boss_type
        ");
        $stmt->execute();
        
        $bosses = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $bosses[$row['boss_type']] = [
                'name' => $row['name'],
                'description' => $row['description'],
                'baseHealth' => intval($row['base_health']),
                'healthMultiplier' => floatval($row['health_multiplier']),
                'baseSpeed' => floatval($row['base_speed']),
                'speedMultiplier' => floatval($row['speed_multiplier']),
                'baseAttackCooldown' => intval($row['base_attack_cooldown']),
                'attackCooldownMultiplier' => floatval($row['attack_cooldown_multiplier']),
                'baseBulletSpeed' => floatval($row['base_bullet_speed']),
                'bulletSpeedMultiplier' => floatval($row['bullet_speed_multiplier']),
                'baseBulletDamage' => intval($row['base_bullet_damage']),
                'bulletDamageMultiplier' => floatval($row['bullet_damage_multiplier']),
                'size' => floatval($row['size']),
                'movementPatterns' => json_decode($row['movement_patterns'] ?? '[]', true) ?: [],
                'attackPatterns' => json_decode($row['attack_patterns'] ?? '[]', true) ?: [],
                'abilities' => json_decode($row['abilities'] ?? '{}', true) ?: [],
                'specialAttackChance' => floatval($row['special_attack_chance']),
                'rageModeThreshold' => floatval($row['rage_mode_threshold']),
                'rageModeMultipliers' => json_decode($row['rage_mode_multipliers'] ?? '{}', true) ?: [],
                'colors' => json_decode($row['colors'] ?? '{}', true) ?: []
            ];
        }
        
        return $bosses;
        
    } catch (Exception $e) {
        error_log("Error getting boss configurations: " . $e->getMessage());
        return [];
    }
}

/**
 * Get game settings
 */
function getGameSettings($pdo) {
    try {
        // Check if game settings table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tbl_game_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_type TEXT NOT NULL,
                setting_key TEXT NOT NULL,
                setting_value TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(game_type, setting_key)
            )
        ");
        
        // Get all game settings
        $stmt = $pdo->prepare("
            SELECT game_type, setting_key, setting_value 
            FROM tbl_game_settings 
            ORDER BY game_type, setting_key
        ");
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($settings[$row['game_type']])) {
                $settings[$row['game_type']] = [];
            }
            $settings[$row['game_type']][$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
        
    } catch (Exception $e) {
        error_log("Error getting game settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Get season settings
 */
function getSeasonSettings($pdo) {
    try {
        // Check if season settings table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tbl_season_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                season_name TEXT DEFAULT 'season_1',
                tetris_max_score INTEGER DEFAULT 1000,
                snake_max_score INTEGER DEFAULT 1000,
                points_per_line INTEGER DEFAULT 10,
                points_per_cheese INTEGER DEFAULT 10,
                space_invaders_max_score INTEGER DEFAULT 10000,
                points_per_invader REAL DEFAULT 0.001,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Get current season settings
        $stmt = $pdo->prepare("
            SELECT * FROM tbl_season_settings 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return [
                'seasonName' => $row['season_name'],
                'tetrisMaxScore' => intval($row['tetris_max_score']),
                'snakeMaxScore' => intval($row['snake_max_score']),
                'pointsPerLine' => intval($row['points_per_line']),
                'pointsPerCheese' => intval($row['points_per_cheese']),
                'spaceInvadersMaxScore' => intval($row['space_invaders_max_score']),
                'pointsPerInvader' => floatval($row['points_per_invader'])
            ];
        } else {
            // Return default season settings
            return [
                'seasonName' => 'season_1',
                'tetrisMaxScore' => 1000,
                'snakeMaxScore' => 1000,
                'pointsPerLine' => 10,
                'pointsPerCheese' => 10,
                'spaceInvadersMaxScore' => 10000,
                'pointsPerInvader' => 0.001
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error getting season settings: " . $e->getMessage());
        return [
            'seasonName' => 'season_1',
            'tetrisMaxScore' => 1000,
            'snakeMaxScore' => 1000,
            'pointsPerLine' => 10,
            'pointsPerCheese' => 10,
            'spaceInvadersMaxScore' => 10000,
            'pointsPerInvader' => 0.001
        ];
    }
}
?>
