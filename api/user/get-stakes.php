<?php
// 🧊 Get Stakes API - Get all stakes for logged-in user
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
    error_log("🧊 Get Stakes: Using request user_id for localhost testing: {$user_id}");
} else {
    // Production: Always use session, verify request matches session
    $user_id = $session_user_id;
    
    // If request user_id provided, verify it matches session (security check)
    if ($request_user_id && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Get Stakes - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }
}

// Local development fallback: Use Narrrf's ID on localhost if no user_id
if (!$user_id && $isLocalhost) {
    $user_id = $LOCAL_TEST_DISCORD_ID;
    error_log("🧊 Get Stakes: Using local test user (Narrrf) for localhost");
}

if (!$user_id) {
    json_response([
        'success' => false,
        'error' => 'Not logged in'
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

// Get filter status (optional)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}
$status_filter = $data['status'] ?? 'all'; // 'active', 'completed', 'cancelled', 'claimable', 'all'

// Get total balance
$balanceStmt = $pdo->prepare("SELECT SUM(score) AS total_balance FROM tbl_user_scores WHERE user_id = ?");
$balanceStmt->execute([$user_id]);
$balanceRow = $balanceStmt->fetch(PDO::FETCH_ASSOC);
$total_balance = (int)($balanceRow['total_balance'] ?? 0);

// Get frozen balance
$frozenStmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) AS frozen_balance 
    FROM tbl_dspoinc_stakes 
    WHERE user_id = ? AND status = 'active'
");
$frozenStmt->execute([$user_id]);
$frozenRow = $frozenStmt->fetch(PDO::FETCH_ASSOC);
$frozen_balance = (int)($frozenRow['frozen_balance'] ?? 0);

$available_balance = $total_balance - $frozen_balance;
// Season 13 V2 readiness:
// This is display-only data for the Stake Lab frontend.
// Backend reward calculation remains authoritative in create/claim/unstake endpoints.
$currentGenesisCount = staking_count_verified_genesis_for_user($pdo, $user_id);
$currentGenesisTier = staking_get_genesis_tier($currentGenesisCount);

// Get stakes based on filter
if ($status_filter === 'active') {
    $stakesStmt = $pdo->prepare("
        SELECT * FROM tbl_dspoinc_stakes 
        WHERE user_id = ? AND status = 'active'
        ORDER BY unfreeze_at ASC
    ");
    $stakesStmt->execute([$user_id]);
} elseif ($status_filter === 'completed') {
    $stakesStmt = $pdo->prepare("
        SELECT * FROM tbl_dspoinc_stakes 
        WHERE user_id = ? AND status = 'completed'
        ORDER BY completed_at DESC
    ");
    $stakesStmt->execute([$user_id]);
} elseif ($status_filter === 'cancelled') {
    $stakesStmt = $pdo->prepare("
        SELECT * FROM tbl_dspoinc_stakes 
        WHERE user_id = ? AND status = 'cancelled'
        ORDER BY cancelled_at DESC
    ");
    $stakesStmt->execute([$user_id]);
} elseif ($status_filter === 'claimable') {
    // Get completed stakes with unpaid rewards
    $stakesStmt = $pdo->prepare("
        SELECT * FROM tbl_dspoinc_stakes 
        WHERE user_id = ? AND status = 'completed' AND reward_paid = 0
        ORDER BY completed_at DESC
    ");
    $stakesStmt->execute([$user_id]);
} else {
    // All stakes
    $stakesStmt = $pdo->prepare("
        SELECT * FROM tbl_dspoinc_stakes 
        WHERE user_id = ?
        ORDER BY 
            CASE status 
                WHEN 'active' THEN 1 
                WHEN 'completed' THEN 2 
                WHEN 'cancelled' THEN 3 
                ELSE 4 
            END,
            unfreeze_at ASC
    ");
    $stakesStmt->execute([$user_id]);
}

$all_stakes = $stakesStmt->fetchAll(PDO::FETCH_ASSOC);

// Backfill: Create missing score adjustment entries for existing stakes
// This ensures all stakes appear in "Recent Score Changes"
foreach ($all_stakes as $stake) {
    // Check if score adjustment entry exists for this stake
    // Use amount and action to match (more reliable than LIKE pattern)
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM tbl_score_adjustments 
        WHERE user_id = ? 
        AND action = 'remove' 
        AND amount = ?
        AND reason LIKE ?
    ");
    $reasonPattern = '%DSPOINC frozen for staking: ' . (int)$stake['amount'] . ' DSPOINC for ' . (int)$stake['freeze_duration_months'] . ' months%';
    $checkStmt->execute([$user_id, -(int)$stake['amount'], $reasonPattern]);
    $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // If no entry exists, create it
    if ($checkResult['count'] == 0) {
        try {
            $reason = sprintf(
                'DSPOINC frozen for staking: %d DSPOINC for %d months (expected reward: %d DSPOINC)',
                (int)$stake['amount'],
                (int)$stake['freeze_duration_months'],
                (int)$stake['expected_reward']
            );
            
            $backfillStmt = $pdo->prepare("
                INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp)
                VALUES (:user_id, :admin_id, :amount, :action, :reason, :timestamp)
            ");
            
            $backfillResult =             $backfillStmt->execute([
                ':user_id' => $user_id,
                ':admin_id' => 'system-staking',
                ':amount' => -(int)$stake['amount'], // Negative for freeze
                ':action' => 'remove', // Use 'remove' since CHECK constraint only allows 'add', 'remove', 'set'
                ':reason' => $reason,
                ':timestamp' => $stake['frozen_at'] // Use the original frozen_at timestamp
            ]);
            
            if ($backfillResult) {
                $insertId = $pdo->lastInsertId();
                error_log("✅ Backfilled score adjustment entry (ID: {$insertId}) for stake #{$stake['id']}");
            } else {
                error_log("⚠️ Backfill INSERT returned false for stake #{$stake['id']}");
            }
        } catch (Exception $e) {
            error_log("❌ Failed to backfill score adjustment for stake #{$stake['id']}: " . $e->getMessage());
            error_log("❌ Error trace: " . $e->getTraceAsString());
        }
    } else {
        error_log("ℹ️ Score adjustment entry already exists for stake #{$stake['id']}");
    }
}
// Process stakes and calculate days remaining
$active_stakes = [];
$completed_stakes = [];
$cancelled_stakes = [];
$claimable_rewards = [];

$now_timestamp = time();
$completedStatusUpdated = false;

foreach ($all_stakes as &$stake) {
    $stake_data = [
    'stake_id' => (int)$stake['id'],
    'amount' => (int)$stake['amount'],
    'freeze_duration_months' => (int)$stake['freeze_duration_months'],
    'reward_rate' => (float)$stake['reward_rate'],
    'expected_reward' => (int)$stake['expected_reward'],
    'frozen_at' => $stake['frozen_at'],
    'unfreeze_at' => $stake['unfreeze_at'],
    'status' => $stake['status'],
    'reward_paid' => (int)($stake['reward_paid'] ?? 0),
    'completed_at' => $stake['completed_at'] ?? null,
    'cancelled_at' => $stake['cancelled_at'] ?? null,
    'penalty_amount' => isset($stake['penalty_amount']) ? (int)$stake['penalty_amount'] : null,
    'returned_amount' => isset($stake['returned_amount']) ? (int)$stake['returned_amount'] : null,
    'unstake_reason' => $stake['unstake_reason'] ?? null,

    // Season 13 V2 display fields.
    // Plain language for DEVS:
    // These values let Stake Lab visually separate future V2 stakes from legacy V1 stakes.
    // They are display/audit values only. Backend claim/unstake endpoints still decide final rewards.
    'staking_contract_version' => $stake['staking_contract_version'] ?? 'legacy_v1',
    'lock_duration_days' => isset($stake['lock_duration_days']) ? (int)$stake['lock_duration_days'] : null,
    'base_reward_rate' => isset($stake['base_reward_rate']) ? (float)$stake['base_reward_rate'] : null,
    'base_expected_reward' => isset($stake['base_expected_reward']) ? (int)$stake['base_expected_reward'] : 0,
    'genesis_count_at_stake' => isset($stake['genesis_count_at_stake']) ? (int)$stake['genesis_count_at_stake'] : 0,
    'genesis_tier_at_stake' => $stake['genesis_tier_at_stake'] ?? null,
    'genesis_multiplier_at_stake' => isset($stake['genesis_multiplier_at_stake']) ? (float)$stake['genesis_multiplier_at_stake'] : 1.0,
    'genesis_count_at_exit' => isset($stake['genesis_count_at_exit']) ? (int)$stake['genesis_count_at_exit'] : null,
    'genesis_tier_at_exit' => $stake['genesis_tier_at_exit'] ?? null,
    'genesis_multiplier_at_exit' => isset($stake['genesis_multiplier_at_exit']) ? (float)$stake['genesis_multiplier_at_exit'] : null,
    'genesis_terms_status' => $stake['genesis_terms_status'] ?? 'unchecked',
    'genesis_terms_penalty_amount' => isset($stake['genesis_terms_penalty_amount']) ? (int)$stake['genesis_terms_penalty_amount'] : 0,
    'final_reward_amount' => isset($stake['final_reward_amount']) ? (int)$stake['final_reward_amount'] : null
];

    $unfreeze_timestamp = strtotime($stake['unfreeze_at']);
    $frozen_timestamp = strtotime($stake['frozen_at']);

    // ✅ AUTO-TRANSITION: mature active stakes become completed
    if ($stake['status'] === 'active' && $unfreeze_timestamp <= $now_timestamp) {
        $completed_at = $stake['completed_at'] ?: date('Y-m-d H:i:s', $unfreeze_timestamp);

        try {
            $updateStmt = $pdo->prepare("
                UPDATE tbl_dspoinc_stakes
                SET status = 'completed',
                    completed_at = COALESCE(completed_at, ?)
                WHERE id = ? AND status = 'active'
            ");
            $updateStmt->execute([$completed_at, $stake['id']]);

            $stake['status'] = 'completed';
            $stake['completed_at'] = $completed_at;
            $stake_data['status'] = 'completed';
            $stake_data['completed_at'] = $completed_at;

            $completedStatusUpdated = true;

            error_log("✅ Auto-completed matured stake #{$stake['id']} for user {$user_id}");
        } catch (Exception $e) {
            error_log("❌ Failed to auto-complete matured stake #{$stake['id']}: " . $e->getMessage());
        }
    }

    if ($stake['status'] === 'active') {
        $seconds_remaining = max(0, $unfreeze_timestamp - $now_timestamp);
        $days_remaining = (int)ceil($seconds_remaining / 86400);

        $total_seconds = max(1, $unfreeze_timestamp - $frozen_timestamp);
        $elapsed_seconds = max(0, $now_timestamp - $frozen_timestamp);
        $progress_percentage = min(100, max(0, ($elapsed_seconds / $total_seconds) * 100));

        $stake_data['days_remaining'] = $days_remaining;
        $stake_data['progress_percentage'] = round($progress_percentage, 2);

        $active_stakes[] = $stake_data;
    } elseif ($stake['status'] === 'completed') {
        $stake_data['total_received'] = (int)$stake['amount'] + (int)$stake['expected_reward'];
        $completed_stakes[] = $stake_data;

        // ✅ Claimable if completed and reward not paid yet
        if ((int)($stake['reward_paid'] ?? 0) === 0) {
            $claimable_rewards[] = $stake_data;
        }
    } elseif ($stake['status'] === 'cancelled') {
        $cancelled_stakes[] = $stake_data;
    }
}
unset($stake);

json_response([
    'success' => true,
    'data' => [
        'active_stakes' => $active_stakes,
        'completed_stakes' => $completed_stakes,
        'cancelled_stakes' => $cancelled_stakes,
        'claimable_rewards' => $claimable_rewards,
        'total_balance' => $total_balance,
        'available_balance' => $available_balance,
        'frozen_balance' => $frozen_balance,
        'active_stakes_count' => count($active_stakes),
        'completed_stakes_count' => count($completed_stakes),
        'cancelled_stakes_count' => count($cancelled_stakes),
        'claimable_rewards_count' => count($claimable_rewards),

        // Season 13 V2 readiness:
        // Frontend may display this contract state, but backend remains authoritative.
        'staking_contracts' => [
            'active_contract_version' => ACTIVE_STAKING_CONTRACT_VERSION,
            'legacy_version' => STAKING_CONTRACT_LEGACY_V1,
            'season13_v2_version' => STAKING_CONTRACT_SEASON13_V2,
            'season13_v2_active' => staking_is_v2_active(),
        ],

        // Season 13 V2 preview:
        // This lets stake-lab.html render pool/tier previews before V2 activation.
        'season13_v2_preview' => [
            'pools' => staking_get_v2_pools(),
            'genesis_tiers' => STAKING_GENESIS_MULTIPLIER_TIERS,
            'current_genesis_count' => $currentGenesisCount,
            'current_genesis_tier' => $currentGenesisTier,
            'same_tier_rule' => 'same_or_higher_tier_required_at_claim',
            'non_genesis_allowed' => true,
        ]
    ]
]);

