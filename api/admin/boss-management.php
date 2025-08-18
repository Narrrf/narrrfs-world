<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all boss configurations
        if (!isset($_GET['action']) || $_GET['action'] === 'get_all') {
            echo json_encode([
                'success' => true,
                'bosses' => [
                    'cheeseKing' => [
                        'name' => 'Cheese King',
                        'description' => 'The first boss - fast and agile with teleport abilities',
                        'baseHealth' => 3000,
                        'healthMultiplier' => 1.0,
                        'baseSpeed' => 2.5,
                        'speedMultiplier' => 1.0,
                        'baseAttackCooldown' => 800,
                        'attackCooldownMultiplier' => 1.0,
                        'baseBulletSpeed' => 4,
                        'bulletSpeedMultiplier' => 1.0,
                        'baseBulletDamage' => 2,
                        'bulletDamageMultiplier' => 1.0,
                        'size' => 0.8,
                        'movementPatterns' => ['sideways', 'zigzag', 'dash'],
                        'attackPatterns' => [0, 1, 2, 3, 4],
                        'abilities' => [
                            'canTeleport' => true,
                            'canShield' => true,
                            'canSummonMinions' => false,
                            'canUseLaser' => false,
                            'canCreateExplosions' => false
                        ],
                        'specialAttackChance' => 0.3,
                        'rageModeThreshold' => 0.4,
                        'rageModeMultipliers' => [
                            'speed' => 2.0,
                            'attackCooldown' => 0.4,
                            'bulletSpeed' => 1.8,
                            'bulletDamage' => 1.5
                        ],
                        'colors' => [
                            'primary' => '#ff6b35',
                            'secondary' => '#ff8c42',
                            'particles' => '#ffdd00'
                        ]
                    ],
                    'cheeseEmperor' => [
                        'name' => 'Cheese Emperor',
                        'description' => 'The second boss - balanced with minion summoning and laser attacks',
                        'baseHealth' => 4000,
                        'healthMultiplier' => 1.0,
                        'baseSpeed' => 2.0,
                        'speedMultiplier' => 1.0,
                        'baseAttackCooldown' => 700,
                        'attackCooldownMultiplier' => 1.0,
                        'baseBulletSpeed' => 5,
                        'bulletSpeedMultiplier' => 1.0,
                        'baseBulletDamage' => 3,
                        'bulletDamageMultiplier' => 1.0,
                        'size' => 1.0,
                        'movementPatterns' => ['sideways', 'hover', 'circle'],
                        'attackPatterns' => [0, 1, 2, 3, 4],
                        'abilities' => [
                            'canTeleport' => false,
                            'canShield' => false,
                            'canSummonMinions' => true,
                            'canUseLaser' => true,
                            'canCreateExplosions' => false
                        ],
                        'specialAttackChance' => 0.4,
                        'rageModeThreshold' => 0.35,
                        'rageModeMultipliers' => [
                            'speed' => 2.2,
                            'attackCooldown' => 0.35,
                            'bulletSpeed' => 2.0,
                            'bulletDamage' => 1.8
                        ],
                        'colors' => [
                            'primary' => '#8b5cf6',
                            'secondary' => '#a78bfa',
                            'particles' => '#c084fc'
                        ]
                    ],
                    'cheeseGod' => [
                        'name' => 'Cheese God',
                        'description' => 'The third boss - powerful with explosions and enhanced abilities',
                        'baseHealth' => 5000,
                        'healthMultiplier' => 1.0,
                        'baseSpeed' => 1.8,
                        'speedMultiplier' => 1.0,
                        'baseAttackCooldown' => 600,
                        'attackCooldownMultiplier' => 1.0,
                        'baseBulletSpeed' => 6,
                        'bulletSpeedMultiplier' => 1.0,
                        'baseBulletDamage' => 4,
                        'bulletDamageMultiplier' => 1.0,
                        'size' => 1.2,
                        'movementPatterns' => ['sideways', 'zigzag', 'circle', 'dash'],
                        'attackPatterns' => [0, 1, 2, 3, 4],
                        'abilities' => [
                            'canTeleport' => false,
                            'canShield' => true,
                            'canSummonMinions' => false,
                            'canUseLaser' => true,
                            'canCreateExplosions' => true
                        ],
                        'specialAttackChance' => 0.5,
                        'rageModeThreshold' => 0.3,
                        'rageModeMultipliers' => [
                            'speed' => 2.5,
                            'attackCooldown' => 0.3,
                            'bulletSpeed' => 2.2,
                            'bulletDamage' => 2.0
                        ],
                        'colors' => [
                            'primary' => '#f59e0b',
                            'secondary' => '#fbbf24',
                            'particles' => '#fde047'
                        ]
                    ],
                    'cheeseDestroyer' => [
                        'name' => 'Cheese Destroyer',
                        'description' => 'The final boss - ultimate challenge with all abilities unlocked',
                        'baseHealth' => 6000,
                        'healthMultiplier' => 1.0,
                        'baseSpeed' => 1.5,
                        'speedMultiplier' => 1.0,
                        'baseAttackCooldown' => 500,
                        'attackCooldownMultiplier' => 1.0,
                        'baseBulletSpeed' => 7,
                        'bulletSpeedMultiplier' => 1.0,
                        'baseBulletDamage' => 5,
                        'bulletDamageMultiplier' => 1.0,
                        'size' => 1.5,
                        'movementPatterns' => ['sideways', 'zigzag', 'hover', 'circle', 'dash'],
                        'attackPatterns' => [0, 1, 2, 3, 4],
                        'abilities' => [
                            'canTeleport' => true,
                            'canShield' => true,
                            'canSummonMinions' => true,
                            'canUseLaser' => true,
                            'canCreateExplosions' => true
                        ],
                        'specialAttackChance' => 0.6,
                        'rageModeThreshold' => 0.25,
                        'rageModeMultipliers' => [
                            'speed' => 3.0,
                            'attackCooldown' => 0.25,
                            'bulletSpeed' => 2.5,
                            'bulletDamage' => 2.5
                        ],
                        'colors' => [
                            'primary' => '#dc2626',
                            'secondary' => '#ef4444',
                            'particles' => '#fca5a5'
                        ]
                    ]
                ]
            ]);
        } else {
            echo json_encode(['error' => 'Invalid action']);
        }
        break;
        
    case 'POST':
        // Save boss configuration
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
        
        // Save to database (you can implement this based on your needs)
        // For now, we'll just return success
        echo json_encode([
            'success' => true,
            'message' => "Boss configuration for $bossType saved successfully",
            'bossType' => $bossType,
            'config' => $config
        ]);
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
        
        // Update in database (you can implement this based on your needs)
        // For now, we'll just return success
        echo json_encode([
            'success' => true,
            'message' => "Boss configuration for $bossType updated successfully",
            'bossType' => $bossType,
            'config' => $config
        ]);
        break;
        
    case 'DELETE':
        // Reset boss configuration to defaults
        if (isset($_GET['bossType'])) {
            $bossType = $_GET['bossType'];
            
            // Validate boss type
            $validBossTypes = ['cheeseKing', 'cheeseEmperor', 'cheeseGod', 'cheeseDestroyer'];
            if (!in_array($bossType, $validBossTypes)) {
                echo json_encode(['error' => 'Invalid boss type']);
                exit;
            }
            
            // Reset in database (you can implement this based on your needs)
            // For now, we'll just return success
            echo json_encode([
                'success' => true,
                'message' => "Boss configuration for $bossType reset to defaults",
                'bossType' => $bossType
            ]);
        } else {
            echo json_encode(['error' => 'Missing bossType parameter']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>
