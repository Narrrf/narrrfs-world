<?php
// 🧊 Complete Stake API - Process completed stakes and pay rewards
// This should be called by cron job or admin interface daily
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
require_once __DIR__ . '/staking-contract-helpers.php';

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

// Optional: Check for admin authentication (for manual trigger)
// For now, allow any authenticated user (can be restricted later)
session_start();
$user_id = $_SERVER['HTTP_X_ADMIN_ID'] ?? $_SESSION['discord_id'] ?? '';

// For cron jobs, allow without session (can be secured with API key later)
$is_cron = isset($_SERVER['HTTP_X_CRON_KEY']);

if (!$user_id && !$is_cron) {
    json_response([
        'success' => false,
        'error' => 'Not authorized'
    ], 401);
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

// Get current season
$seasonStmt = $pdo->prepare("SELECT season_name FROM tbl_seasons WHERE is_active = 1 LIMIT 1");
$seasonStmt->execute();
$seasonRow = $seasonStmt->fetch(PDO::FETCH_ASSOC);
$currentSeason = $seasonRow['season_name'] ?? 'Season 6';

// Get optional stake_id from request
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}
$specific_stake_id = isset($data['stake_id']) ? (int)$data['stake_id'] : null;

// Find due stakes (unfreeze_at <= NOW() and status = 'active')
$now = date('Y-m-d H:i:s');

if ($specific_stake_id) {
    // Process specific stake
    $dueStakesStmt = $pdo->prepare("
        SELECT * FROM tbl_dspoinc_stakes 
        WHERE id = ? AND status = 'active' AND unfreeze_at <= ?
    ");
    $dueStakesStmt->execute([$specific_stake_id, $now]);
} else {
    // Process all due stakes
    $dueStakesStmt = $pdo->prepare("
        SELECT * FROM tbl_dspoinc_stakes 
        WHERE status = 'active' AND unfreeze_at <= ?
        ORDER BY unfreeze_at ASC
    ");
    $dueStakesStmt->execute([$now]);
}

$due_stakes = $dueStakesStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($due_stakes)) {
    json_response([
        'success' => true,
        'data' => [
            'stakes_processed' => 0,
            'total_rewards_paid' => 0,
            'processed_stakes' => [],
            'message' => 'No due stakes found'
        ]
    ]);
}

$processed_stakes = [];
$total_rewards_paid = 0;

foreach ($due_stakes as $stake) {
    try {
        $pdo->beginTransaction();

        $stake_id = (int)$stake['id'];
        $stake_user_id = $stake['user_id'];
        $stake_amount = (int)$stake['amount'];
        $expected_reward = (int)$stake['expected_reward'];

        /**
 * Season 13 V2 safety:
 * V2 stakes must not be auto-paid here because final Genesis holder-tier
 * validation must happen at the exact user claim moment.
 *
 * Legacy stakes keep the existing auto-complete payout behavior.
 */
if (staking_is_v2_stake($stake)) {
    $updateV2Stmt = $pdo->prepare("
        UPDATE tbl_dspoinc_stakes
        SET status = 'completed',
            completed_at = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $updateV2Stmt->execute([$now, $stake_id]);

    $pdo->commit();

    $processed_stakes[] = $stake_id;
    continue;
}
        
        // Use expected_reward as actual reward (can be adjusted if rates changed)
        $actual_reward = $expected_reward;
        $total_return = $stake_amount + $actual_reward;

        // Add original amount + reward to tbl_user_scores (unfreeze + reward)
        $scoreStmt = $pdo->prepare("
            INSERT INTO tbl_user_scores (user_id, score, game, source, season)
            VALUES (:user_id, :score, :game, :source, :season)
        ");
        $scoreStmt->execute([
            ':user_id' => $stake_user_id,
            ':score' => $total_return, // Original + reward
            ':game' => 'staking',
            ':source' => 'unfreeze_reward',
            ':season' => $currentSeason
        ]);

        // Update stake status to completed
        $updateStmt = $pdo->prepare("
            UPDATE tbl_dspoinc_stakes 
            SET status = 'completed',
                completed_at = ?,
                reward_paid = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([$now, $actual_reward, $stake_id]);

        // Track adjustment history (audit trail)
        $adjustStmt = $pdo->prepare("
            INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason)
            VALUES (:user_id, :admin_id, :amount, :action, :reason)
        ");
        $reason = sprintf(
            'Stake completed: %d DSPOINC + %d reward = %d DSPOINC total (stake_id: %d)',
            $stake_amount,
            $actual_reward,
            $total_return,
            $stake_id
        );
        $adjustStmt->execute([
            ':user_id' => $stake_user_id,
            ':admin_id' => 'system-staking',
            ':amount' => $total_return,
            ':action' => 'add',
            ':reason' => $reason
        ]);

        $pdo->commit();

        $processed_stakes[] = $stake_id;
        $total_rewards_paid += $actual_reward;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error processing stake {$stake_id}: " . $e->getMessage());
        // Continue with next stake
    }
}

json_response([
    'success' => true,
    'data' => [
        'stakes_processed' => count($processed_stakes),
        'total_rewards_paid' => $total_rewards_paid,
        'processed_stakes' => $processed_stakes
    ]
]);

