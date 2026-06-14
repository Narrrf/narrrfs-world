<?php
// 🧊 Claim Stake Reward API - Manually claim reward from completed stake
// CORS handled by .htaccess - no duplicate headers here

// Enable error reporting for debugging (disable in production)
$isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
               strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
if ($isLocalhost) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

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

// Get user from session
session_start();
$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID for local testing

$session_user_id = $_SESSION['discord_id'] ?? '';

// Check if user_id is provided in POST/GET (for localhost testing only)
// Also check JSON body for POST requests
$request_user_id = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Try POST data first
    $request_user_id = $_POST['user_id'] ?? '';
    // If not in POST, try JSON body
    if (!$request_user_id) {
        $json_input = json_decode(file_get_contents('php://input'), true);
        $request_user_id = $json_input['user_id'] ?? '';
    }
} else {
    // GET request
    $request_user_id = $_GET['user_id'] ?? '';
}

// SECURITY: Determine if we're on localhost
$isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
               strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;

// SECURITY FIX: Always use session user_id in production
// Only allow request user_id override for localhost testing
if ($isLocalhost && $request_user_id) {
    // Local development: Allow override for testing
    $user_id = $request_user_id;
    error_log("🧊 Claim Stake Reward: Using request user_id for localhost testing: {$user_id}");
} else {
    // Production: Always use session, verify request matches session
    $user_id = $session_user_id;
    
    // If request user_id provided, verify it matches session (security check)
    if ($request_user_id && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Claim Stake Reward - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }
}

// Local development fallback: Use Narrrf's ID on localhost if no user_id
if (!$user_id && $isLocalhost) {
    $user_id = $LOCAL_TEST_DISCORD_ID;
    error_log("🧊 Claim Stake Reward: Using local test user (Narrrf) for localhost");
}

if (!$user_id) {
    json_response([
        'success' => false,
        'error' => 'Not logged in'
    ], 401);
}

// Get request data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

$stake_id = isset($data['stake_id']) ? (int)$data['stake_id'] : 0;

if (!$stake_id) {
    json_response([
        'success' => false,
        'error' => 'Stake ID is required'
    ], 400);
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

// Verify stake exists and belongs to user
$stakeStmt = $pdo->prepare("
    SELECT * FROM tbl_dspoinc_stakes 
    WHERE id = ? AND user_id = ?
");
$stakeStmt->execute([$stake_id, $user_id]);
$stake = $stakeStmt->fetch(PDO::FETCH_ASSOC);

if (!$stake) {
    json_response([
        'success' => false,
        'error' => 'Stake not found or does not belong to you'
    ], 404);
}

// Verify stake is completed
if ($stake['status'] !== 'completed') {
    json_response([
        'success' => false,
        'error' => 'Stake is not completed. Only completed stakes can have rewards claimed.',
        'current_status' => $stake['status']
    ], 400);
}

// Verify reward hasn't been paid already
// reward_paid stores the actual reward amount when paid, or 0 if not paid (matches complete-stake.php pattern)
if ((int)$stake['reward_paid'] !== 0) {
    json_response([
        'success' => false,
        'error' => 'Reward has already been claimed for this stake',
        'stake_id' => $stake_id,
        'already_paid' => true
    ], 409);
}


// Check if this stake was already processed by complete-stake.php
// If complete-stake.php ran, it would have added original + reward and set reward_paid
// If not, we need to add both original + reward
$checkCompleteStmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM tbl_user_scores 
    WHERE user_id = ? 
    AND game = 'staking' 
    AND source = 'unfreeze_reward'
    AND score = ?
");
$total_return_check = (int)$stake['amount'] + (int)$stake['expected_reward'];
$checkCompleteStmt->execute([$user_id, $total_return_check]);
$completeCheck = $checkCompleteStmt->fetch(PDO::FETCH_ASSOC);

// If complete-stake.php already ran, only add the reward (original was already returned)
// Otherwise, add both original + reward (manual claim when complete-stake.php didn't run)
$already_processed = ((int)$completeCheck['count'] > 0);

// Calculate total return (original amount + reward)
$original_amount = (int)$stake['amount'];
$reward_amount = (int)$stake['expected_reward'];
$genesisExitData = null;

/**
 * Season 13 V2 final reward check.
 *
 * Plain language for DEVS:
 * Legacy stakes keep their stored expected_reward.
 * V2 stakes must re-check the user's verified Genesis holder tier at claim time.
 * If the user dropped below the starting tier, they keep the base reward but
 * lose the Genesis multiplier bonus.
 */
if (staking_is_v2_stake($stake)) {
    $currentGenesisCount = staking_count_verified_genesis_for_user($pdo, $user_id);
    $currentGenesisTier = staking_get_genesis_tier($currentGenesisCount);
    $finalRewardData = staking_calculate_v2_final_reward($stake, $currentGenesisTier);

    $reward_amount = (int)$finalRewardData['final_reward_amount'];
    $genesisExitData = [
        'genesis_count_at_exit' => $currentGenesisCount,
        'genesis_tier_at_exit' => $currentGenesisTier['key'],
        'genesis_multiplier_at_exit' => $currentGenesisTier['multiplier'],
        'genesis_terms_status' => $finalRewardData['terms_status'],
        'genesis_terms_penalty_amount' => $finalRewardData['genesis_terms_penalty_amount'],
        'final_reward_amount' => $finalRewardData['final_reward_amount'],
    ];
}

$total_returned = $original_amount + $reward_amount;

// Begin transaction
try {
    $pdo->beginTransaction();
    
    // Double-check reward_paid after transaction starts (prevent race condition)
    $recheckStmt = $pdo->prepare("SELECT reward_paid FROM tbl_dspoinc_stakes WHERE id = ? AND user_id = ?");
    $recheckStmt->execute([$stake_id, $user_id]);
    $recheckStake = $recheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$recheckStake || (int)$recheckStake['reward_paid'] !== 0) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Reward has already been claimed for this stake',
            'stake_id' => $stake_id
        ], 409);
    }
    
    // Check if this stake was already claimed in the last minute (prevent double-claiming)
    $checkClaimStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM tbl_score_adjustments 
        WHERE user_id = ? 
        AND reason LIKE ?
        AND timestamp > datetime('now', '-1 minute')
    ");
    $stake_id_pattern = "%stake_id: {$stake_id}%";
    $checkClaimStmt->execute([$user_id, $stake_id_pattern]);
    $claimCheck = $checkClaimStmt->fetch(PDO::FETCH_ASSOC);
    
    if ((int)$claimCheck['count'] > 0) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'This reward was just claimed. Please wait a moment and refresh.',
            'stake_id' => $stake_id
        ], 409);
    }

    $now = date('Y-m-d H:i:s');

    // Determine what to add based on whether complete-stake.php already ran
    if ($already_processed) {
        // complete-stake.php already added original + reward, so only add reward now
        $amount_to_add = $reward_amount;
        $source_type = 'claim_reward_only';
        $reason_text = sprintf(
            'Stake reward claimed (reward only): %d DSPOINC reward (stake_id: %d)',
            $reward_amount,
            $stake_id
        );
    } else {
        // complete-stake.php didn't run, so add both original + reward
        $amount_to_add = $total_returned;
        $source_type = 'claim_reward';
        $reason_text = sprintf(
            'Stake reward claimed: %d DSPOINC + %d reward = %d total (stake_id: %d)',
            $original_amount,
            $reward_amount,
            $total_returned,
            $stake_id
        );
    }

    // Add amount to tbl_user_scores
    $scoreStmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (user_id, score, game, source, season)
        VALUES (:user_id, :score, :game, :source, :season)
    ");
    $scoreStmt->execute([
        ':user_id' => $user_id,
        ':score' => $amount_to_add,
        ':game' => 'staking',
        ':source' => $source_type,
        ':season' => $currentSeason
    ]);

    // Update stake reward_paid to store the actual reward amount (matches complete-stake.php pattern)
    if ($genesisExitData !== null) {
    $updateStmt = $pdo->prepare("
        UPDATE tbl_dspoinc_stakes
        SET reward_paid = ?,
            genesis_count_at_exit = ?,
            genesis_tier_at_exit = ?,
            genesis_multiplier_at_exit = ?,
            genesis_terms_status = ?,
            genesis_terms_penalty_amount = ?,
            final_reward_amount = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");
    $updateStmt->execute([
        $reward_amount,
        $genesisExitData['genesis_count_at_exit'],
        $genesisExitData['genesis_tier_at_exit'],
        $genesisExitData['genesis_multiplier_at_exit'],
        $genesisExitData['genesis_terms_status'],
        $genesisExitData['genesis_terms_penalty_amount'],
        $genesisExitData['final_reward_amount'],
        $stake_id,
        $user_id
    ]);
} else {
    $updateStmt = $pdo->prepare("
        UPDATE tbl_dspoinc_stakes
        SET reward_paid = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");
    $updateStmt->execute([$reward_amount, $stake_id, $user_id]);
}

    // Create audit trail entry
    $adjustStmt = $pdo->prepare("
        INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp)
        VALUES (:user_id, :admin_id, :amount, :action, :reason, :timestamp)
    ");
    $adjustStmt->execute([
        ':user_id' => $user_id,
        ':admin_id' => 'system-staking',
        ':amount' => $amount_to_add, // Amount actually added
        ':action' => 'add',
        ':reason' => $reason_text,
        ':timestamp' => $now
    ]);

    // Get new balance
    $balanceStmt = $pdo->prepare("SELECT SUM(score) AS total_balance FROM tbl_user_scores WHERE user_id = ?");
    $balanceStmt->execute([$user_id]);
    $balanceRow = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    $new_balance = (int)($balanceRow['total_balance'] ?? 0);

    $pdo->commit();

    json_response([
        'success' => true,
        'data' => [
            'stake_id' => $stake_id,
            'original_amount' => $original_amount,
            'reward_amount' => $reward_amount,
            'total_returned' => $total_returned,
            'amount_added' => $amount_to_add, // Actual amount added (may be reward only or total)
            'already_processed' => $already_processed, // Whether complete-stake.php already ran
            'reward_paid' => true,
            'new_balance' => $new_balance
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error claiming stake reward {$stake_id}: " . $e->getMessage());
    
    json_response([
        'success' => false,
        'error' => 'Failed to claim reward',
        'details' => $isLocalhost ? $e->getMessage() : 'Internal server error'
    ], 500);
}

