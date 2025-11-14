<?php
// 🧀 Cheese Hunt Capture API — awards DSPOINC for three.js Cheese Temple hunt

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

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

// Ensure capture log table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS tbl_cheese_hunt_captures (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        discord_id TEXT NOT NULL,
        discord_name TEXT,
        level_id TEXT NOT NULL,
        base_reward INTEGER NOT NULL,
        multiplier REAL NOT NULL,
        total_reward INTEGER NOT NULL,
        capture_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        session_id TEXT,
        metadata TEXT
    )
");

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$discordId = isset($data['discord_id']) ? trim((string)$data['discord_id']) : '';
$discordName = isset($data['discord_name']) ? trim((string)$data['discord_name']) : null;
$levelId = isset($data['level_id']) ? trim((string)$data['level_id']) : 'CHEESE_TEMPLE_LVL1';
$sessionId = isset($data['session_id']) ? trim((string)$data['session_id']) : null;
$baseReward = isset($data['base_reward']) ? (int)$data['base_reward'] : 50;
$clientCaptureIndex = isset($data['capture_index']) ? (int)$data['capture_index'] : null;

if ($discordId === '') {
    json_response([
        'success' => false,
        'error' => 'Missing discord_id'
    ], 400);
}

if ($baseReward <= 0) {
    $baseReward = 50;
}

$levelIdSanitized = preg_replace('/[^A-Z0-9_\-]/i', '', $levelId);
if ($levelIdSanitized === '') {
    $levelIdSanitized = 'CHEESE_TEMPLE_LVL1';
}

$cooldownSeconds = isset($data['cooldown_seconds']) ? max(0, (int)$data['cooldown_seconds']) : 1;

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
 */
function getRoleMultiplier(PDO $pdo, $discordId, $ROLE_MULTIPLIERS_BY_ID, $ROLE_PRIORITY, $ROLE_MULTIPLIERS_BY_NAME) {
    $multiplier = 1.0;
    $source = 'none';

    try {
        $roleStmt = $pdo->prepare("SELECT role_id, role_name FROM tbl_user_roles WHERE user_id = ?");
        $roleStmt->execute([$discordId]);
        $roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [$multiplier, $source];
    }

    $roleIds = [];
    $roleNames = [];
    foreach ($roles as $role) {
        if (!empty($role['role_id'])) {
            $roleIds[] = (string)$role['role_id'];
        }
        if (!empty($role['role_name'])) {
            $roleNames[] = $role['role_name'];
        }
    }

    foreach ($ROLE_PRIORITY as $roleId) {
        if (in_array($roleId, $roleIds, true)) {
            return [$ROLE_MULTIPLIERS_BY_ID[$roleId] ?? 1.0, $roleId];
        }
    }

    foreach ($roleNames as $name) {
        if (isset($ROLE_MULTIPLIERS_BY_NAME[$name])) {
            return [$ROLE_MULTIPLIERS_BY_NAME[$name], $name];
        }
    }

    return [$multiplier, $source];
}

list($multiplier, $multiplierSource) = getRoleMultiplier($pdo, $discordId, $ROLE_MULTIPLIERS_BY_ID, $ROLE_PRIORITY, $ROLE_MULTIPLIERS_BY_NAME);
$totalReward = (int)round($baseReward * $multiplier);

// Enforce cooldown (server-side safety)
if ($cooldownSeconds > 0) {
    $cooldownStmt = $pdo->prepare("SELECT capture_time FROM tbl_cheese_hunt_captures WHERE discord_id = ? ORDER BY capture_time DESC LIMIT 1");
    $cooldownStmt->execute([$discordId]);
    $lastCapture = $cooldownStmt->fetch(PDO::FETCH_ASSOC);

    if ($lastCapture && !empty($lastCapture['capture_time'])) {
        $lastTimestamp = strtotime($lastCapture['capture_time']);
        if ($lastTimestamp !== false && (time() - $lastTimestamp) < $cooldownSeconds) {
            json_response([
                'success' => false,
                'error' => 'Cooldown active',
                'cooldown_seconds' => $cooldownSeconds
            ], 429);
        }
    }
}

try {
    $pdo->beginTransaction();

    // Log capture
    $captureStmt = $pdo->prepare("
        INSERT INTO tbl_cheese_hunt_captures (
            discord_id,
            discord_name,
            level_id,
            base_reward,
            multiplier,
            total_reward,
            session_id,
            metadata
        ) VALUES (
            :discord_id,
            :discord_name,
            :level_id,
            :base_reward,
            :multiplier,
            :total_reward,
            :session_id,
            :metadata
        )
    ");

    $metadata = [
        'client_capture_index' => $clientCaptureIndex,
        'multiplier_source' => $multiplierSource
    ];

    $captureStmt->execute([
        ':discord_id' => $discordId,
        ':discord_name' => $discordName,
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
        ':game' => 'cheese_hunt_3d',
        ':source' => 'game_score',
        ':season' => $currentSeason
    ]);

    // Track adjustment history
    $adjustStmt = $pdo->prepare("
        INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason)
        VALUES (:user_id, :admin_id, :amount, :action, :reason)
    ");
    $reason = sprintf(
        'Cheese Hunt 3D capture (%s): base %d × %.2f = %d DSPOINC',
        $levelIdSanitized,
        $baseReward,
        $multiplier,
        $totalReward
    );
    $adjustStmt->execute([
        ':user_id' => $discordId,
        ':admin_id' => 'system-cheese-hunt',
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
        'error' => 'Failed to record capture',
        'details' => $e->getMessage()
    ], 500);
}

// Totals for response
$totalCapturesStmt = $pdo->prepare("SELECT COUNT(*) AS capture_count FROM tbl_cheese_hunt_captures WHERE discord_id = ?");
$totalCapturesStmt->execute([$discordId]);
$captureCountRow = $totalCapturesStmt->fetch(PDO::FETCH_ASSOC);
$totalCaptures = (int)($captureCountRow['capture_count'] ?? 0);

$sessionTodayStmt = $pdo->prepare("SELECT COUNT(*) AS captures_today FROM tbl_cheese_hunt_captures WHERE discord_id = ? AND DATE(capture_time) = DATE('now', 'localtime')");
$sessionTodayStmt->execute([$discordId]);
$capturesToday = (int)($sessionTodayStmt->fetch(PDO::FETCH_ASSOC)['captures_today'] ?? 0);

$totalPointsStmt = $pdo->prepare("SELECT SUM(score) AS total_ds_poinc FROM tbl_user_scores WHERE user_id = ?");
$totalPointsStmt->execute([$discordId]);
$totalDSPOINC = (int)($totalPointsStmt->fetch(PDO::FETCH_ASSOC)['total_ds_poinc'] ?? 0);

json_response([
    'success' => true,
    'data' => [
        'discord_id' => $discordId,
        'discord_name' => $discordName,
        'level_id' => $levelIdSanitized,
        'base_reward' => $baseReward,
        'multiplier' => $multiplier,
        'multiplier_source' => $multiplierSource,
        'ds_poinc_awarded' => $totalReward,
        'total_captures' => $totalCaptures,
        'captures_today' => $capturesToday,
        'total_ds_poinc' => $totalDSPOINC,
        'season' => $currentSeason
    ]
]);

