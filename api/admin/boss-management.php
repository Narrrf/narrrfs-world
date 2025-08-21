<?php
// 🚀 CRITICAL FIX: Ensure no output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1); // Log errors instead

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 🚀 CRITICAL FIX: Function to ensure JSON response
function sendJsonResponse($success, $data = null, $error = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// 🚀 CRITICAL FIX: Function to handle exceptions
function handleException($e, $context = 'Unknown operation') {
    error_log("Boss Management API Error in $context: " . $e->getMessage());
    sendJsonResponse(false, null, "Error in $context: " . $e->getMessage(), 500);
}

// Include database connection
try {
    require_once __DIR__ . '/../config/database.php';
} catch (Exception $e) {
    handleException($e, 'Database configuration loading');
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all boss configurations from database
            if (!isset($_GET['action']) || $_GET['action'] === 'get_all') {
                try {
                    $db = getSQLite3Connection();
                    if (!$db) {
                        throw new Exception('Database connection failed');
                    }
                    
                    // Get all boss configurations from database
                    $stmt = $db->prepare("SELECT * FROM boss_configurations ORDER BY boss_type");
                    $result = $stmt->execute();
                    
                    $bossConfigs = [];
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        $bossConfigs[$row['boss_type']] = [
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'baseHealth' => (int)$row['base_health'],
                            'healthMultiplier' => (float)$row['health_multiplier'],
                            'baseSpeed' => (float)$row['base_speed'],
                            'speedMultiplier' => (float)$row['speed_multiplier'],
                            'baseAttackCooldown' => (int)$row['base_attack_cooldown'],
                            'attackCooldownMultiplier' => (float)$row['attack_cooldown_multiplier'],
                            'baseBulletSpeed' => (float)$row['base_bullet_speed'],
                            'bulletSpeedMultiplier' => (float)$row['bullet_speed_multiplier'],
                            'baseBulletDamage' => (int)$row['base_bullet_damage'],
                            'bulletDamageMultiplier' => (float)$row['bullet_damage_multiplier'],
                            'size' => (float)$row['size'],
                            'movementPatterns' => json_decode($row['movement_patterns'], true) ?: ['sideways'],
                            'attackPatterns' => json_decode($row['attack_patterns'], true) ?: [0, 1],
                            'abilities' => json_decode($row['abilities'], true) ?: [
                                'canTeleport' => false,
                                'canShield' => false,
                                'canSummonMinions' => false,
                                'canUseLaser' => false,
                                'canCreateExplosions' => false
                            ],
                            'specialAttackChance' => (float)$row['special_attack_chance'],
                            'rageModeThreshold' => (float)$row['rage_mode_threshold'],
                            'rageModeMultipliers' => json_decode($row['rage_mode_multipliers'], true) ?: [
                                'speed' => 1.3,
                                'attackCooldown' => 0.8,
                                'bulletSpeed' => 1.2,
                                'bulletDamage' => 1.0
                            ],
                            'colors' => json_decode($row['colors'], true) ?: [
                                'primary' => '#ff6b35',
                                'secondary' => '#ff8c42',
                                'particles' => '#ffdd00'
                            ]
                        ];
                    }
                    
                    sendJsonResponse(true, ['bosses' => $bossConfigs]);
                    
                } catch (Exception $e) {
                    handleException($e, 'Loading all boss configurations');
                }
            }
            
            // Get specific boss configuration
            if (isset($_GET['bossType'])) {
                $bossType = $_GET['bossType'];
                
                try {
                    $db = getSQLite3Connection();
                    if (!$db) {
                        throw new Exception('Database connection failed');
                    }
                    
                    $stmt = $db->prepare("SELECT * FROM boss_configurations WHERE boss_type = ?");
                    $stmt->bindValue(1, $bossType, SQLITE3_TEXT);
                    $result = $stmt->execute();
                    $row = $result->fetchArray(SQLITE3_ASSOC);
                    
                    if ($row) {
                        $config = [
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'baseHealth' => (int)$row['base_health'],
                            'healthMultiplier' => (float)$row['health_multiplier'],
                            'baseSpeed' => (float)$row['base_speed'],
                            'speedMultiplier' => (float)$row['speed_multiplier'],
                            'baseAttackCooldown' => (int)$row['base_attack_cooldown'],
                            'attackCooldownMultiplier' => (float)$row['attack_cooldown_multiplier'],
                            'baseBulletSpeed' => (float)$row['base_bullet_speed'],
                            'bulletSpeedMultiplier' => (float)$row['bullet_speed_multiplier'],
                            'baseBulletDamage' => (int)$row['base_bullet_damage'],
                            'bulletDamageMultiplier' => (float)$row['bullet_damage_multiplier'],
                            'size' => (float)$row['size'],
                            'movementPatterns' => json_decode($row['movement_patterns'], true) ?: ['sideways'],
                            'attackPatterns' => json_decode($row['attack_patterns'], true) ?: [0, 1],
                            'abilities' => json_decode($row['abilities'], true) ?: [
                                'canTeleport' => false,
                                'canShield' => false,
                                'canSummonMinions' => false,
                                'canUseLaser' => false,
                                'canCreateExplosions' => false
                            ],
                            'specialAttackChance' => (float)$row['special_attack_chance'],
                            'rageModeThreshold' => (float)$row['rage_mode_threshold'],
                            'rageModeMultipliers' => json_decode($row['rage_mode_multipliers'], true) ?: [
                                'speed' => 1.3,
                                'attackCooldown' => 0.8,
                                'bulletSpeed' => 1.2,
                                'bulletDamage' => 1.0
                            ],
                            'colors' => json_decode($row['colors'], true) ?: [
                                'primary' => '#ff6b35',
                                'secondary' => '#ff8c42',
                                'particles' => '#ffdd00'
                            ]
                        ];
                        
                        sendJsonResponse(true, ['bossType' => $bossType, 'config' => $config]);
                    } else {
                        sendJsonResponse(false, null, 'Boss type not found', 404);
                    }
                    
                } catch (Exception $e) {
                    handleException($e, 'Loading boss configuration for ' . $bossType);
                }
            }
            
            sendJsonResponse(false, null, 'Invalid action', 400);
            break;
            
        case 'PUT':
            // Update boss configuration
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['bossType']) || !isset($input['config'])) {
                sendJsonResponse(false, null, 'Missing bossType or config', 400);
            }
            
            $bossType = $input['bossType'];
            $config = $input['config'];
            
            // Validate boss type
            $validBossTypes = ['cheeseKing', 'cheeseEmperor', 'cheeseGod', 'cheeseDestroyer'];
            if (!in_array($bossType, $validBossTypes)) {
                sendJsonResponse(false, null, 'Invalid boss type', 400);
            }
            
            // 🚀 CRITICAL FIX: Ensure all required fields have defaults
            $config = array_merge([
                'name' => 'Unknown Boss',
                'description' => 'Boss description',
                'baseHealth' => 100,
                'healthMultiplier' => 1.0,
                'baseSpeed' => 1.0,
                'speedMultiplier' => 1.0,
                'baseAttackCooldown' => 1000,
                'attackCooldownMultiplier' => 1.0,
                'baseBulletSpeed' => 2.0,
                'bulletSpeedMultiplier' => 1.0,
                'baseBulletDamage' => 1,
                'bulletDamageMultiplier' => 1.0,
                'size' => 1.0,
                'movementPatterns' => ['sideways'],
                'attackPatterns' => [0, 1],
                'abilities' => [
                    'canTeleport' => false,
                    'canShield' => false,
                    'canSummonMinions' => false,
                    'canUseLaser' => false,
                    'canCreateExplosions' => false
                ],
                'specialAttackChance' => 0.3,
                'rageModeThreshold' => 0.3,
                'rageModeMultipliers' => [
                    'speed' => 1.3,
                    'attackCooldown' => 0.8,
                    'bulletSpeed' => 1.2,
                    'bulletDamage' => 1.0
                ],
                'colors' => [
                    'primary' => '#ff6b35',
                    'secondary' => '#ff8c42',
                    'particles' => '#ffdd00'
                ]
            ], $config);
            
            // Update in database
            try {
                $db = getSQLite3Connection();
                if (!$db) {
                    throw new Exception('Database connection failed');
                }
                
                $stmt = $db->prepare("
                    UPDATE boss_configurations SET
                        name = ?,
                        description = ?,
                        base_health = ?,
                        health_multiplier = ?,
                        base_speed = ?,
                        speed_multiplier = ?,
                        base_attack_cooldown = ?,
                        attack_cooldown_multiplier = ?,
                        base_bullet_speed = ?,
                        bullet_speed_multiplier = ?,
                        base_bullet_damage = ?,
                        bullet_damage_multiplier = ?,
                        size = ?,
                        movement_patterns = ?,
                        attack_patterns = ?,
                        abilities = ?,
                        special_attack_chance = ?,
                        rage_mode_threshold = ?,
                        rage_mode_multipliers = ?,
                        colors = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE boss_type = ?
                ");
                
                $stmt->bindValue(1, $config['name'], SQLITE3_TEXT);
                $stmt->bindValue(2, $config['description'], SQLITE3_TEXT);
                $stmt->bindValue(3, $config['baseHealth'], SQLITE3_INTEGER);
                $stmt->bindValue(4, $config['healthMultiplier'], SQLITE3_FLOAT);
                $stmt->bindValue(5, $config['baseSpeed'], SQLITE3_FLOAT);
                $stmt->bindValue(6, $config['speedMultiplier'], SQLITE3_FLOAT);
                $stmt->bindValue(7, $config['baseAttackCooldown'], SQLITE3_INTEGER);
                $stmt->bindValue(8, $config['attackCooldownMultiplier'], SQLITE3_FLOAT);
                $stmt->bindValue(9, $config['baseBulletSpeed'], SQLITE3_FLOAT);
                $stmt->bindValue(10, $config['bulletSpeedMultiplier'], SQLITE3_FLOAT);
                $stmt->bindValue(11, $config['baseBulletDamage'], SQLITE3_INTEGER);
                $stmt->bindValue(12, $config['bulletDamageMultiplier'], SQLITE3_FLOAT);
                $stmt->bindValue(13, $config['size'], SQLITE3_FLOAT);
                $stmt->bindValue(14, json_encode($config['movementPatterns']), SQLITE3_TEXT);
                $stmt->bindValue(15, json_encode($config['attackPatterns']), SQLITE3_TEXT);
                $stmt->bindValue(16, json_encode($config['abilities']), SQLITE3_TEXT);
                $stmt->bindValue(17, $config['specialAttackChance'], SQLITE3_FLOAT);
                $stmt->bindValue(18, $config['rageModeThreshold'], SQLITE3_FLOAT);
                $stmt->bindValue(19, json_encode($config['rageModeMultipliers']), SQLITE3_TEXT);
                $stmt->bindValue(20, json_encode($config['colors']), SQLITE3_TEXT);
                $stmt->bindValue(21, $bossType, SQLITE3_TEXT);
                
                $result = $stmt->execute();
                
                if ($result) {
                    sendJsonResponse(true, [
                        'message' => "Boss configuration for $bossType updated successfully in database",
                        'bossType' => $bossType,
                        'config' => $config
                    ]);
                } else {
                    throw new Exception('Failed to update boss configuration');
                }
            } catch (Exception $e) {
                handleException($e, 'Updating boss configuration for ' . $bossType);
            }
            break;
            
        case 'DELETE':
            // Reset boss configuration to defaults (not implemented yet)
            sendJsonResponse(false, null, 'Reset to defaults not implemented', 501);
            break;
            
        default:
            sendJsonResponse(false, null, 'Method not allowed', 405);
            break;
    }
} catch (Exception $e) {
    handleException($e, 'General operation');
}
?>
