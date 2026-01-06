<?php
// 🧩 Riddle Reward API — awards DSPOINC for solving riddles in Three.js Dimension
// CORS handled by .htaccess - no duplicate headers here

error_reporting(0);
ini_set('display_errors', 0);

// Set JSON response header (must be before any output)
header('Content-Type: application/json');

// Handle preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

/**
 * Role multiplier lookup (ID → multiplier)
 */
$ROLE_MULTIPLIERS_BY_ID = [
    '1332016526848692345' => 2.0, // 🎴 VIP Holder
    '1402668301414563971' => 1.5, // 🏆 Holder
    '1332017420591697972' => 1.4, // Champion
    '1332108350518857842' => 1.3, // WL
    '1417279348989497532' => 1.3, // Season Tester
    '1332017614108758148' => 1.2, // Early Bird
    '1399651053682692208' => 1.1  // 🧀 Cheese Hunter
];

$ROLE_PRIORITY = [
    '1332016526848692345',
    '1402668301414563971',
    '1332017420591697972',
    '1332108350518857842',
    '1417279348989497532',
    '1332017614108758148',
    '1399651053682692208'
];

$ROLE_MULTIPLIERS_BY_NAME = [
    '🎴 VIP Holder' => 2.0,
    'VIP Holder' => 2.0,
    '🏆 Holder' => 1.5,
    'Holder' => 1.5,
    'Champion' => 1.4,
    'WL' => 1.3,
    'Season Tester' => 1.3,
    'Early Bird' => 1.2,
    '🧀 Cheese Hunter' => 1.1,
    'Cheese Hunter' => 1.1
];

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    json_response([
        'success' => false,
        'error' => 'Database connection failed',
        'details' => $e->getMessage()
    ], 500);
}

// Ensure riddle completions table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS tbl_riddle_completions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        discord_id TEXT NOT NULL,
        discord_name TEXT,
        riddle_id TEXT NOT NULL,
        level_id TEXT NOT NULL,
        base_reward INTEGER NOT NULL,
        multiplier REAL NOT NULL,
        total_reward INTEGER NOT NULL,
        completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        session_id TEXT,
        metadata TEXT,
        UNIQUE(discord_id, riddle_id)
    )
");

// Create index for faster lookups
$pdo->exec("
    CREATE INDEX IF NOT EXISTS idx_riddle_completions_discord_riddle 
    ON tbl_riddle_completions(discord_id, riddle_id)
");

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$discordId = isset($data['discord_id']) ? trim((string)$data['discord_id']) : '';
$discordName = isset($data['discord_name']) ? trim((string)$data['discord_name']) : null;
$riddleId = isset($data['riddle_id']) ? trim((string)$data['riddle_id']) : 'CHEESE_TEMPLE_RIDDLE_01';
$levelId = isset($data['level_id']) ? trim((string)$data['level_id']) : 'CHEESE_TEMPLE_LVL1';
$sessionId = isset($data['session_id']) ? trim((string)$data['session_id']) : null;
$baseReward = isset($data['base_reward']) ? (int)$data['base_reward'] : 500; // Default 500 DSPOINC for riddle completion

if ($discordId === '') {
    json_response([
        'success' => false,
        'error' => 'Missing discord_id'
    ], 400);
}

if ($baseReward <= 0) {
    $baseReward = 500; // Default 500 DSPOINC for riddle completion
}

$riddleIdSanitized = preg_replace('/[^A-Z0-9_\-]/i', '', $riddleId);
if ($riddleIdSanitized === '') {
    $riddleIdSanitized = 'CHEESE_TEMPLE_RIDDLE_01';
}

$levelIdSanitized = preg_replace('/[^A-Z0-9_\-]/i', '', $levelId);
if ($levelIdSanitized === '') {
    $levelIdSanitized = 'CHEESE_TEMPLE_LVL1';
}

// Determine active season
$currentSeason = 'Season 5';
try {
    $seasonStmt = $pdo->query("SELECT season_name FROM tbl_seasons WHERE is_active = 1 ORDER BY start_date DESC LIMIT 1");
    $seasonRow = $seasonStmt->fetch(PDO::FETCH_ASSOC);
    if ($seasonRow && !empty($seasonRow['season_name'])) {
        $currentSeason = $seasonRow['season_name'];
    }
} catch (Exception $e) {
    // Fallback to Season 5
}

/**
 * Get role-based multiplier.
 * FIXED: tbl_user_roles only has role_name (no role_id column)
 */
function getRoleMultiplier(PDO $pdo, $discordId, $ROLE_MULTIPLIERS_BY_ID, $ROLE_PRIORITY, $ROLE_MULTIPLIERS_BY_NAME) {
    $multiplier = 1.0;
    $source = 'none';

    try {
        // FIXED: tbl_user_roles only has role_name column (no role_id)
        $roleStmt = $pdo->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
        $roleStmt->execute([$discordId]);
        $roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("❌ [RIDDLE REWARD] Error fetching roles for user $discordId: " . $e->getMessage());
        return [$multiplier, $source];
    }

    $roleNames = [];
    foreach ($roles as $role) {
        if (!empty($role['role_name'])) {
            $roleNames[] = $role['role_name'];
        }
    }

    // Check role names in priority order (highest multiplier first)
    // Priority order: VIP Holder (2.0) > Holder (1.5) > Champion (1.4) > WL/Season Tester (1.3) > Early Bird (1.2) > Cheese Hunter (1.1)
    $priorityRoleNames = [
        '🎴 VIP Holder',
        'VIP Holder',
        '🏆 Holder',
        'Holder',
        'Champion',
        'WL',
        'Season Tester',
        'Early Bird',
        '🧀 Cheese Hunter',
        'Cheese Hunter'
    ];

    // Check in priority order (highest multiplier first)
    foreach ($priorityRoleNames as $priorityName) {
        if (in_array($priorityName, $roleNames, true)) {
            if (isset($ROLE_MULTIPLIERS_BY_NAME[$priorityName])) {
                $foundMultiplier = $ROLE_MULTIPLIERS_BY_NAME[$priorityName];
                error_log("✅ [RIDDLE REWARD] Found role multiplier for user $discordId: $priorityName = $foundMultiplier");
                return [$foundMultiplier, $priorityName];
            }
        }
    }

    // Fallback: Check any role name against multiplier map
    foreach ($roleNames as $name) {
        if (isset($ROLE_MULTIPLIERS_BY_NAME[$name])) {
            $foundMultiplier = $ROLE_MULTIPLIERS_BY_NAME[$name];
            error_log("✅ [RIDDLE REWARD] Found role multiplier for user $discordId: $name = $foundMultiplier");
            return [$foundMultiplier, $name];
        }
    }

    error_log("⚠️ [RIDDLE REWARD] No role multiplier found for user $discordId (roles: " . implode(', ', $roleNames) . ") - using default 1.0");
    return [$multiplier, $source];
}

// Check if riddle already completed (prevent duplicate rewards)
// SPECIAL HANDLING: Profile lootbox allows daily claims (24h cooldown)
$isProfileLootbox = ($riddleIdSanitized === 'CHEST_PROFILE_LOOTBOX');
$checkStmt = $pdo->prepare("SELECT id, total_reward, completed_at FROM tbl_riddle_completions WHERE discord_id = ? AND riddle_id = ?");
$checkStmt->execute([$discordId, $riddleIdSanitized]);
$existingCompletion = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existingCompletion) {
    // For profile lootbox, check if 24 hours have passed since last claim
    if ($isProfileLootbox) {
        $lastCompleted = strtotime($existingCompletion['completed_at']);
        $now = time();
        $hoursSinceLastClaim = ($now - $lastCompleted) / 3600; // Convert seconds to hours
        
        if ($hoursSinceLastClaim >= 24) {
            // 24 hours have passed, allow new claim - delete old record
            $deleteStmt = $pdo->prepare("DELETE FROM tbl_riddle_completions WHERE discord_id = ? AND riddle_id = ?");
            $deleteStmt->execute([$discordId, $riddleIdSanitized]);
            error_log("✅ [RIDDLE REWARD] Profile lootbox cooldown expired - deleted old record for user $discordId");
            // Continue to process new claim
        } else {
            // Still on cooldown
            $hoursRemaining = 24 - $hoursSinceLastClaim;
            json_response([
                'success' => false,
                'error' => 'Profile lootbox on cooldown',
                'already_completed' => true,
                'previous_reward' => (int)$existingCompletion['total_reward'],
                'hours_remaining' => round($hoursRemaining, 1),
                'cooldown_message' => "Please wait " . round($hoursRemaining, 1) . " more hours before opening again"
            ], 409); // 409 Conflict
        }
    } else {
        // Regular riddle - one-time only
        json_response([
            'success' => false,
            'error' => 'Riddle already completed',
            'already_completed' => true,
            'previous_reward' => (int)$existingCompletion['total_reward']
        ], 409); // 409 Conflict
    }
}

list($multiplier, $multiplierSource) = getRoleMultiplier($pdo, $discordId, $ROLE_MULTIPLIERS_BY_ID, $ROLE_PRIORITY, $ROLE_MULTIPLIERS_BY_NAME);
$totalReward = (int)round($baseReward * $multiplier);

try {
    $pdo->beginTransaction();

    // Log riddle completion
    $completionStmt = $pdo->prepare("
        INSERT INTO tbl_riddle_completions (
            discord_id,
            discord_name,
            riddle_id,
            level_id,
            base_reward,
            multiplier,
            total_reward,
            session_id,
            metadata
        ) VALUES (
            :discord_id,
            :discord_name,
            :riddle_id,
            :level_id,
            :base_reward,
            :multiplier,
            :total_reward,
            :session_id,
            :metadata
        )
    ");

    $metadata = [
        'multiplier_source' => $multiplierSource,
        'level_id' => $levelIdSanitized
    ];

    $completionStmt->execute([
        ':discord_id' => $discordId,
        ':discord_name' => $discordName,
        ':riddle_id' => $riddleIdSanitized,
        ':level_id' => $levelIdSanitized,
        ':base_reward' => $baseReward,
        ':multiplier' => $multiplier,
        ':total_reward' => $totalReward,
        ':session_id' => $sessionId,
        ':metadata' => json_encode($metadata)
    ]);

    // Add DSPOINC to user balance
    $scoreStmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (user_id, score, game, source, season)
        VALUES (:user_id, :score, :game, :source, :season)
    ");
    $scoreStmt->execute([
        ':user_id' => $discordId,
        ':score' => $totalReward,
        ':game' => 'cheese_temple_riddles',
        ':source' => 'riddle_completion',
        ':season' => $currentSeason
    ]);

    // Track adjustment history
    $adjustStmt = $pdo->prepare("
        INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason)
        VALUES (:user_id, :admin_id, :amount, :action, :reason)
    ");
    $reason = sprintf(
        'Riddle completion (%s): base %d × %.2f = %d DSPOINC',
        $riddleIdSanitized,
        $baseReward,
        $multiplier,
        $totalReward
    );
    $adjustStmt->execute([
        ':user_id' => $discordId,
        ':admin_id' => 'system-riddle-reward',
        ':amount' => $totalReward,
        ':action' => 'add',
        ':reason' => $reason
    ]);

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response([
        'success' => false,
        'error' => 'Failed to record riddle completion',
        'details' => $e->getMessage()
    ], 500);
}

// Totals for response
$totalCompletionsStmt = $pdo->prepare("SELECT COUNT(*) AS completion_count FROM tbl_riddle_completions WHERE discord_id = ?");
$totalCompletionsStmt->execute([$discordId]);
$totalCompletions = (int)($totalCompletionsStmt->fetch(PDO::FETCH_ASSOC)['completion_count'] ?? 0);

$totalPointsStmt = $pdo->prepare("SELECT SUM(score) AS total_ds_poinc FROM tbl_user_scores WHERE user_id = ?");
$totalPointsStmt->execute([$discordId]);
$totalDSPOINC = (int)($totalPointsStmt->fetch(PDO::FETCH_ASSOC)['total_ds_poinc'] ?? 0);

json_response([
    'success' => true,
    'data' => [
        'discord_id' => $discordId,
        'discord_name' => $discordName,
        'riddle_id' => $riddleIdSanitized,
        'level_id' => $levelIdSanitized,
        'base_reward' => $baseReward,
        'multiplier' => $multiplier,
        'multiplier_source' => $multiplierSource,
        'ds_poinc_awarded' => $totalReward,
        'total_riddle_completions' => $totalCompletions,
        'total_ds_poinc' => $totalDSPOINC,
        'season' => $currentSeason
    ]
]);

