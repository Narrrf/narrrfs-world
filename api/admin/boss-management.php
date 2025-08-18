<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

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
                        'movementPatterns' => json_decode($row['movement_patterns'], true),
                        'attackPatterns' => json_decode($row['attack_patterns'], true),
                        'abilities' => json_decode($row['abilities'], true),
                        'specialAttackChance' => (float)$row['special_attack_chance'],
                        'rageModeThreshold' => (float)$row['rage_mode_threshold'],
                        'rageModeMultipliers' => json_decode($row['rage_mode_multipliers'], true),
                        'colors' => json_decode($row['colors'], true)
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'bosses' => $bossConfigs
                ]);
                
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Database error: ' . $e->getMessage()
                ]);
            }
            exit;
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
                        'movementPatterns' => json_decode($row['movement_patterns'], true),
                        'attackPatterns' => json_decode($row['attack_patterns'], true),
                        'abilities' => json_decode($row['abilities'], true),
                        'specialAttackChance' => (float)$row['special_attack_chance'],
                        'rageModeThreshold' => (float)$row['rage_mode_threshold'],
                        'rageModeMultipliers' => json_decode($row['rage_mode_multipliers'], true),
                        'colors' => json_decode($row['colors'], true)
                    ];
                    
                    echo json_encode([
                        'success' => true,
                        'bossType' => $bossType,
                        'config' => $config
                    ]);
                } else {
                    echo json_encode(['error' => 'Boss type not found']);
                }
                
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Database error: ' . $e->getMessage()
                ]);
            }
            exit;
        }
        
        echo json_encode(['error' => 'Invalid action']);
        break;
        
    case 'PUT':
        // Update boss configuration
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['bossType']) || !isset($input['config'])) {
            echo json_encode(['error' => 'Missing bossType or config']);
            exit;
        }
        
        $bossType = $input['bossType'];
        $config = $input['config'];
        
        // Validate boss type
        $validBossTypes = ['cheeseKing', 'cheeseEmperor', 'cheeseGod', 'cheeseDestroyer'];
        if (!in_array($bossType, $validBossTypes)) {
            echo json_encode(['error' => 'Invalid boss type']);
            exit;
        }
        
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
                echo json_encode([
                    'success' => true,
                    'message' => "Boss configuration for $bossType updated successfully in database",
                    'bossType' => $bossType,
                    'config' => $config
                ]);
            } else {
                throw new Exception('Failed to update boss configuration');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'DELETE':
        // Reset boss configuration to defaults (not implemented yet)
        echo json_encode(['error' => 'Reset to defaults not implemented']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
?>
