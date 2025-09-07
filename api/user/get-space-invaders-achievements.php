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

require_once '../auth/auth.php';

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? 'db/narrrf_world.sqlite' 
        : '/data/narrrf_world.sqlite';
    
    if (!file_exists($dbPath)) {
        throw new Exception("Database file not found: $dbPath");
    }
    
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

try {
    // Get Discord ID from query parameters
    $discordId = $_GET['discord_id'] ?? '';
    
    if (empty($discordId)) {
        throw new Exception('Discord ID is required');
    }
    
    // Validate Discord ID format
    if (!preg_match('/^\d{17,19}$/', $discordId)) {
        throw new Exception('Invalid Discord ID format');
    }
    
    $pdo = getSQLite3Connection();
    
    // Fetch user's Space Cheese Invaders achievements
    $stmt = $pdo->prepare("
        SELECT 
            achievement_key,
            achievement_title,
            achievement_description,
            achievement_icon,
            unlocked_at,
            game_score,
            game_time,
            total_kills,
            combo_multiplier
        FROM tbl_space_invaders_achievements 
        WHERE user_id = ? 
        ORDER BY unlocked_at DESC
    ");
    
    $stmt->execute([$discordId]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Define all possible achievements for reference (REBALANCED)
    $allAchievements = [
        'firstKill' => [
            'title' => 'First Blood',
            'description' => 'Destroyed your first invader!',
            'icon' => '🎯'
        ],
        'killStreak8' => [
            'title' => 'Killing Spree',
            'description' => '8 kills in a row!',
            'icon' => '🔥'
        ],
        'killStreak15' => [
            'title' => 'Rampage',
            'description' => '15 kills in a row!',
            'icon' => '⚡'
        ],
        'killStreak25' => [
            'title' => 'Unstoppable',
            'description' => '25 kills in a row!',
            'icon' => '💀'
        ],
        'score2500' => [
            'title' => 'Getting Started',
            'description' => 'Reached 1,000 points!',
            'icon' => '⭐'
        ],
        'score7500' => [
            'title' => 'Rising Star',
            'description' => 'Reached 3,000 points!',
            'icon' => '🌟'
        ],
        'score15000' => [
            'title' => 'Space Ace',
            'description' => 'Reached 6,000 points!',
            'icon' => '🚀'
        ],
        'score30000' => [
            'title' => 'Legend',
            'description' => 'Reached 12,000 points!',
            'icon' => '👑'
        ],
        'perfectWave' => [
            'title' => 'Perfect Wave',
            'description' => 'Cleared a wave without taking damage!',
            'icon' => '✨'
        ],
        'noHitRun60' => [
            'title' => 'Untouchable',
            'description' => '60 seconds without taking damage!',
            'icon' => '🛡️'
        ],
        'comboMaster8' => [
            'title' => 'Combo Master',
            'description' => 'Achieved 3x score multiplier!',
            'icon' => '💥'
        ],
        'speedDemon20k' => [
            'title' => 'Speed Demon',
            'description' => 'Reached 6k points in under 3 minutes!',
            'icon' => '⚡'
        ],
        'survivor10min' => [
            'title' => 'Ultimate Survivor',
            'description' => 'Survived for 10 minutes!',
            'icon' => '🏆'
        ],
        'bossKiller3' => [
            'title' => 'Boss Slayer',
            'description' => 'Defeated Boss 3!',
            'icon' => '🗡️'
        ],
        'bossKiller4' => [
            'title' => 'Boss Destroyer',
            'description' => 'Defeated Boss 4 - Ultimate Achievement!',
            'icon' => '💀'
        ]
    ];
    
    // Process achievements data
    $processedAchievements = [];
    $unlockedKeys = [];
    
    foreach ($achievements as $achievement) {
        $processedAchievements[] = [
            'key' => $achievement['achievement_key'],
            'title' => $achievement['achievement_title'],
            'description' => $achievement['achievement_description'],
            'icon' => $achievement['achievement_icon'],
            'unlocked_at' => $achievement['unlocked_at'],
            'game_score' => (int)$achievement['game_score'],
            'game_time' => (int)$achievement['game_time'],
            'total_kills' => (int)$achievement['total_kills'],
            'combo_multiplier' => (int)$achievement['combo_multiplier'],
            'unlocked' => true
        ];
        $unlockedKeys[] = $achievement['achievement_key'];
    }
    
    // Add locked achievements
    foreach ($allAchievements as $key => $achievement) {
        if (!in_array($key, $unlockedKeys)) {
            $processedAchievements[] = [
                'key' => $key,
                'title' => $achievement['title'],
                'description' => $achievement['description'],
                'icon' => $achievement['icon'],
                'unlocked_at' => null,
                'game_score' => 0,
                'game_time' => 0,
                'total_kills' => 0,
                'combo_multiplier' => 0,
                'unlocked' => false
            ];
        }
    }
    
    // Sort achievements: unlocked first (by unlock date), then locked
    usort($processedAchievements, function($a, $b) {
        if ($a['unlocked'] && $b['unlocked']) {
            return strtotime($b['unlocked_at']) - strtotime($a['unlocked_at']);
        } elseif ($a['unlocked'] && !$b['unlocked']) {
            return -1;
        } elseif (!$a['unlocked'] && $b['unlocked']) {
            return 1;
        } else {
            return 0;
        }
    });
    
    // Calculate statistics
    $stats = [
        'total_achievements' => count($allAchievements),
        'unlocked_achievements' => count($unlockedKeys),
        'locked_achievements' => count($allAchievements) - count($unlockedKeys),
        'completion_percentage' => count($allAchievements) > 0 ? round((count($unlockedKeys) / count($allAchievements)) * 100, 1) : 0,
        'latest_achievement' => !empty($achievements) ? $achievements[0] : null
    ];
    
    echo json_encode([
        'success' => true,
        'achievements' => $processedAchievements,
        'stats' => $stats,
        'user_id' => $discordId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'achievements' => [],
        'stats' => [
            'total_achievements' => 0,
            'unlocked_achievements' => 0,
            'locked_achievements' => 0,
            'completion_percentage' => 0,
            'latest_achievement' => null
        ]
    ]);
}
?>
