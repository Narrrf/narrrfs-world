<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../db/narrrf_world.sqlite' 
        : '/var/www/html/db/narrrf_world.sqlite';
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

// Parse trait name to extract level, riddle, step info
function parseTraitName($trait) {
    // Pattern 1: CHEESE_TEMPLE_RIDDLE_XX_SOLVED (Level 1 riddles)
    if (preg_match('/CHEESE_TEMPLE_RIDDLE(?:_(\d+))?_SOLVED/', $trait, $matches)) {
        return [
            'type' => 'riddle',
            'level' => 1,
            'riddle_number' => isset($matches[1]) ? (int)$matches[1] : 1,
            'step' => null
        ];
    }
    
    // Pattern 2: CHEESE_TEMPLE_LEVELX_STEPX (Level 2+ steps)
    if (preg_match('/CHEESE_TEMPLE_LEVEL(\d+)_STEP(\d+)/', $trait, $matches)) {
        return [
            'type' => 'step',
            'level' => (int)$matches[1],
            'riddle_number' => null,
            'step' => (int)$matches[2]
        ];
    }
    
    return null; // Unknown pattern
}

// Get level name from level number
function getLevelName($level) {
    $levelNames = [
        1 => 'Cheese Temple',
        2 => 'The Spawn',
        3 => 'The Hunt',
        4 => 'The First Shot'
    ];
    return $levelNames[$level] ?? "Level $level";
}

// Get riddle name from riddle number
function getRiddleName($riddleNumber) {
    $riddleNames = [
        1 => 'The Discovery',
        2 => 'The Movement',
        3 => 'The Portal'
    ];
    return $riddleNames[$riddleNumber] ?? "Riddle #$riddleNumber";
}

// Get step name from level and step
function getStepName($level, $step) {
    $stepNames = [
        2 => [
            0 => 'Hidden Discovery',
            1 => 'Gallery Unlocked',
            2 => 'Complete Tour'
        ],
        3 => [
            0 => 'Hunt Begins',
            1 => 'First Hunt',
            2 => 'Master Hunter'
        ],
        4 => [
            0 => 'First Shot Ready',
            1 => 'Sharpshooter',
            2 => 'Portal Master'
        ]
    ];
    
    return $stepNames[$level][$step] ?? "Step $step";
}

// Get achievement icon
function getAchievementIcon($parsed) {
    if ($parsed['type'] === 'riddle') {
        $icons = [1 => '🔍', 2 => '🧱', 3 => '🚪'];
        return $icons[$parsed['riddle_number']] ?? '🧩';
    }
    
    if ($parsed['type'] === 'step') {
        // Step 0 = Hidden Discovery
        if ($parsed['step'] === 0) return '🧀';
        // Step 1 = Activation
        if ($parsed['step'] === 1) return '🎚️';
        // Step 2 = Completion
        if ($parsed['step'] === 2) {
            $levelIcons = [2 => '👁️', 3 => '👑', 4 => '🚀'];
            return $levelIcons[$parsed['level']] ?? '🚀';
        }
    }
    
    return '🧩'; // Default icon
}

// Get reward amount
function getRewardAmount($parsed) {
    if ($parsed['type'] === 'riddle') {
        $rewards = [1 => 500, 2 => 500, 3 => 750];
        return '+' . ($rewards[$parsed['riddle_number']] ?? 500) . ' DSPOINC';
    }
    
    if ($parsed['type'] === 'step') {
        $rewards = [
            2 => [0 => 100, 1 => 100, 2 => 120],
            3 => [0 => 100, 1 => 250, 2 => 250],
            4 => [0 => 100, 1 => 2500, 2 => 200]
        ];
        $amount = $rewards[$parsed['level']][$parsed['step']] ?? 100;
        return '+' . number_format($amount) . ' DSPOINC';
    }
    
    return '+100 DSPOINC';
}

// Generate achievement definition
function generateAchievementDefinition($trait, $parsed) {
    $title = '';
    $description = '';
    
    if ($parsed['type'] === 'riddle') {
        $riddleName = getRiddleName($parsed['riddle_number']);
        $title = "Riddle #{$parsed['riddle_number']}: $riddleName";
        $description = "Solved the {$parsed['riddle_number']}" . ($parsed['riddle_number'] === 1 ? 'st' : ($parsed['riddle_number'] === 2 ? 'nd' : 'rd')) . " Cheese Temple riddle";
    }
    
    if ($parsed['type'] === 'step') {
        $levelName = getLevelName($parsed['level']);
        $stepName = getStepName($parsed['level'], $parsed['step']);
        $title = "Level {$parsed['level']} - Step {$parsed['step']}: $stepName";
        
        // Generate description based on level and step
        if ($parsed['level'] === 2) {
            $descriptions = [
                0 => 'Found and activated the hidden cheese stone in The Spawn',
                1 => 'Activated the lever to reveal the weapon gallery',
                2 => 'Inspected every display in The Spawn (weapons, accessories, monsters)'
            ];
        } else if ($parsed['level'] === 3) {
            $descriptions = [
                0 => 'Found and activated the hidden cheese stone in The Hunt',
                1 => 'Caught the first 5 monsters in The Hunt',
                2 => 'Caught all 10 monsters in The Hunt (both phases complete)'
            ];
        } else if ($parsed['level'] === 4) {
            $descriptions = [
                0 => 'Found and activated the hidden cheese stone in The First Shot',
                1 => 'Shot all 50 floating cheeses in The First Shot',
                2 => 'Entered the portal after completing The First Shot'
            ];
        } else {
            $descriptions = [
                0 => "Found and activated the hidden cheese stone in {$levelName}",
                1 => "Completed Step 1 in {$levelName}",
                2 => "Completed Step 2 in {$levelName}"
            ];
        }
        $description = $descriptions[$parsed['step']] ?? "Completed Step {$parsed['step']} in {$levelName}";
    }
    
    return [
        'key' => $trait,
        'title' => $title,
        'description' => $description,
        'icon' => getAchievementIcon($parsed),
        'level' => $parsed['level'],
        'type' => $parsed['type'],
        'riddle_number' => $parsed['riddle_number'],
        'step' => $parsed['step'],
        'reward' => getRewardAmount($parsed)
    ];
}

try {
    $pdo = getSQLite3Connection();
    
    if (!$pdo) {
        throw new Exception('Failed to connect to database');
    }
    
    // Get user_id from request (support local test user)
    $user_id = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = $input['user_id'] ?? $input['discord_id'] ?? null;
    } else {
        $user_id = $_GET['user_id'] ?? $_GET['discord_id'] ?? null;
    }
    
    // Support local test user (Narrrf's account)
    $isLocal = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;
    $LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID
    
    if (!$user_id) {
        if ($isLocal) {
            // Use Narrrf's account for local development
            $user_id = $LOCAL_TEST_DISCORD_ID;
        } else {
            throw new Exception('User ID is required');
        }
    }
    
    // Allow Narrrf's account for local development (fallback)
    if ($user_id === 'LOCAL_TEST_DISCORD' && $isLocal) {
        // Legacy support: convert old LOCAL_TEST_DISCORD to Narrrf's ID
        $user_id = $LOCAL_TEST_DISCORD_ID;
    }
    
    // Get all possible CHEESE_TEMPLE_* traits from database
    $allTraitsQuery = "
        SELECT DISTINCT trait
        FROM tbl_user_traits
        WHERE trait LIKE 'CHEESE_TEMPLE_%'
        ORDER BY trait ASC
    ";
    
    $allTraitsStmt = $pdo->prepare($allTraitsQuery);
    $allTraitsStmt->execute();
    $dbTraits = $allTraitsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Always include all known traits (for complete achievement list)
    // This ensures all levels show up even if no one has unlocked them yet
    $knownTraits = [
        'CHEESE_TEMPLE_RIDDLE_SOLVED',
        'CHEESE_TEMPLE_RIDDLE_02_SOLVED',
        'CHEESE_TEMPLE_RIDDLE_03_SOLVED',
        'CHEESE_TEMPLE_LEVEL2_STEP0',
        'CHEESE_TEMPLE_LEVEL2_STEP1',
        'CHEESE_TEMPLE_LEVEL2_STEP2',
        'CHEESE_TEMPLE_LEVEL3_STEP0',
        'CHEESE_TEMPLE_LEVEL3_STEP1',
        'CHEESE_TEMPLE_LEVEL3_STEP2',
        'CHEESE_TEMPLE_LEVEL4_STEP0',
        'CHEESE_TEMPLE_LEVEL4_STEP1',
        'CHEESE_TEMPLE_LEVEL4_STEP2'
    ];
    
    // Merge database traits with known traits (database traits take priority)
    $allTraits = array_unique(array_merge($knownTraits, $dbTraits));
    sort($allTraits);
    
    // Get user's unlocked traits
    $userTraitsQuery = "
        SELECT trait, timestamp
        FROM tbl_user_traits
        WHERE user_id = ? AND trait LIKE 'CHEESE_TEMPLE_%'
        ORDER BY timestamp ASC
    ";
    
    $userTraitsStmt = $pdo->prepare($userTraitsQuery);
    $userTraitsStmt->execute([$user_id]);
    $userTraits = $userTraitsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create map of unlocked traits
    $unlockedMap = [];
    foreach ($userTraits as $trait) {
        $unlockedMap[$trait['trait']] = $trait['timestamp'];
    }
    
    // Generate all achievement definitions
    $allAchievements = [];
    foreach ($allTraits as $trait) {
        $parsed = parseTraitName($trait);
        if ($parsed) {
            $def = generateAchievementDefinition($trait, $parsed);
            $def['unlocked'] = isset($unlockedMap[$trait]);
            $def['unlocked_at'] = $unlockedMap[$trait] ?? null;
            $allAchievements[] = $def;
        }
    }
    
    // Sort achievements: by level, then by type (riddle first, then step), then by number
    usort($allAchievements, function($a, $b) {
        // First by level
        if ($a['level'] !== $b['level']) {
            return $a['level'] <=> $b['level'];
        }
        // Then by type (riddle before step)
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'riddle' ? -1 : 1;
        }
        // Then by riddle_number or step
        $aNum = $a['riddle_number'] ?? $a['step'];
        $bNum = $b['riddle_number'] ?? $b['step'];
        return $aNum <=> $bNum;
    });
    
    // Calculate statistics
    $total = count($allAchievements);
    $unlocked = count(array_filter($allAchievements, function($a) { return $a['unlocked']; }));
    $locked = $total - $unlocked;
    $percentage = $total > 0 ? round(($unlocked / $total) * 100, 1) : 0;
    
    // Group by level
    $byLevel = [];
    foreach ($allAchievements as $achievement) {
        $level = $achievement['level'];
        if (!isset($byLevel[$level])) {
            $byLevel[$level] = [
                'level' => $level,
                'name' => getLevelName($level),
                'total' => 0,
                'unlocked' => 0,
                'achievements' => []
            ];
        }
        $byLevel[$level]['total']++;
        if ($achievement['unlocked']) {
            $byLevel[$level]['unlocked']++;
        }
        $byLevel[$level]['achievements'][] = $achievement;
    }
    
    // Convert to indexed array
    $levels = array_values($byLevel);
    
    // Response
    echo json_encode([
        'success' => true,
        'data' => [
            'achievements' => $allAchievements,
            'statistics' => [
                'total' => $total,
                'unlocked' => $unlocked,
                'locked' => $locked,
                'percentage' => $percentage,
                'by_level' => array_map(function($level) {
                    return [
                        'total' => $level['total'],
                        'unlocked' => $level['unlocked'],
                        'locked' => $level['total'] - $level['unlocked']
                    ];
                }, $byLevel)
            ],
            'levels' => $levels
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

