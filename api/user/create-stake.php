<?php
// 🧊 Create Stake API - Freeze DSPOINC for selected duration
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

/**
 * Read create-stake request data once.
 *
 * Plain language for DEVS:
 * php://input can become unreliable when a file reads it in multiple places.
 * This helper normalizes JSON, form POST, and GET values into one request array
 * so localhost curl tests and browser Stake Lab calls use the same data path.
 */
function get_create_stake_request_data(): array {
    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);

    $requestData = [];

    if (is_array($jsonInput)) {
        $requestData = $jsonInput;
    }

    if (!empty($_POST)) {
        $requestData = array_merge($requestData, $_POST);
    }

    if (!empty($_GET)) {
        $requestData = array_merge($requestData, $_GET);
    }

    return $requestData;
}

$createStakeRequestData = get_create_stake_request_data();

// Get user from session
session_start();
$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID for local testing

$session_user_id = $_SESSION['discord_id'] ?? '';

// Check if user_id is provided in request data.
// Production still requires this to match the active Discord session.
$request_user_id = trim((string)($createStakeRequestData['user_id'] ?? ''));

// SECURITY: Determine if we're on localhost
$isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
               strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;

// SECURITY FIX: Always use session user_id in production
// Only allow request user_id override for localhost testing
if ($isLocalhost && $request_user_id) {
    // Local development: Allow override for testing
    $user_id = $request_user_id;
    error_log("🧊 Create Stake: Using request user_id for localhost testing: {$user_id}");
} else {
    // Production: Always use session, verify request matches session
    $user_id = $session_user_id;
    
    // If request user_id provided, verify it matches session (security check)
    if ($request_user_id && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Create Stake - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }
}

// Local development fallback: Use Narrrf's ID on localhost if no user_id
if (!$user_id && $isLocalhost) {
    $user_id = $LOCAL_TEST_DISCORD_ID;
    error_log("🧊 Create Stake: Using local test user (Narrrf) for localhost");
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

// Ensure staking table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS tbl_dspoinc_stakes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT NOT NULL,
        amount INTEGER NOT NULL,
        freeze_duration_months INTEGER NOT NULL,
        reward_rate REAL NOT NULL,
        expected_reward INTEGER NOT NULL,
        frozen_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        unfreeze_at DATETIME NOT NULL,
        status TEXT DEFAULT 'active',
        completed_at DATETIME,
        reward_paid INTEGER DEFAULT 0,
        transaction_id TEXT,
        metadata TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        cancelled_at DATETIME,
        penalty_amount INTEGER DEFAULT 0,
        returned_amount INTEGER DEFAULT 0,
        unstake_reason TEXT,
        staking_contract_version TEXT NOT NULL DEFAULT 'legacy_v1',
        lock_duration_days INTEGER,
        base_reward_rate REAL,
        base_expected_reward INTEGER DEFAULT 0,
        genesis_count_at_stake INTEGER DEFAULT 0,
        genesis_tier_at_stake TEXT,
        genesis_multiplier_at_stake REAL DEFAULT 1.0,
        genesis_count_at_exit INTEGER,
        genesis_tier_at_exit TEXT,
        genesis_multiplier_at_exit REAL,
        genesis_terms_status TEXT DEFAULT 'unchecked',
        genesis_terms_penalty_amount INTEGER DEFAULT 0,
        final_reward_amount INTEGER
    )
");

// Create indexes
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_stakes_user_status ON tbl_dspoinc_stakes(user_id, status)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_stakes_unfreeze_at ON tbl_dspoinc_stakes(unfreeze_at, status)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_stakes_user_active ON tbl_dspoinc_stakes(user_id, status, unfreeze_at)");
// Season 13 V2 readiness indexes.
// Plain language for DEVS:
// These indexes are safe for legacy_v1 rows and prepare filtering/reporting
// by contract version and Genesis holder-term status later.
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_stakes_contract_version ON tbl_dspoinc_stakes(staking_contract_version)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_stakes_genesis_terms ON tbl_dspoinc_stakes(genesis_terms_status)");

// Get request data from the shared parser.
// This supports JSON, form POST, and GET consistently.
$data = $createStakeRequestData;

$amount = isset($data['amount']) ? (int)$data['amount'] : 0;
$freeze_duration_months = isset($data['freeze_duration_months']) ? (int)$data['freeze_duration_months'] : 0;

// Validation
if ($amount < 100) {
    json_response([
        'success' => false,
        'error' => 'Minimum stake amount is 100 DSPOINC'
    ], 400);
}

$allowed_durations = [1, 3, 6, 12, 24, 36];

// Reward rates (must match frontend REWARD_RATES)
$reward_rates = [
    1 => 0.02,   // 2%
    3 => 0.05,   // 5%
    6 => 0.10,   // 10%
    12 => 0.20,  // 20%
    24 => 0.35,  // 35% (matches frontend)
    36 => 0.50   // 50% (matches frontend)
];

/**
 * Legacy duration validation.
 *
 * Plain language for DEVS:
 * Legacy V1 uses month buttons from the old Stake Lab UI.
 * Season 13 V2 uses lock_duration_days and validates the day pool inside the
 * V2 branch below. Do not force V2 requests through month validation.
 */
if (!staking_is_v2_active()) {
    if (!in_array($freeze_duration_months, $allowed_durations, true)) {
        json_response([
            'success' => false,
            'error' => 'Invalid duration. Allowed: 1, 3, 6, 12, 24, 36 months'
        ], 400);
    }

    $reward_rate = $reward_rates[$freeze_duration_months];
    $expected_reward = (int)round($amount * $reward_rate);
} else {
    $reward_rate = 0.0;
    $expected_reward = 0;
}
// Phase A safety:
// New stake creation still uses legacy_v1 until Season 13 V2 is intentionally activated.
$staking_contract_version = ACTIVE_STAKING_CONTRACT_VERSION;
$lock_duration_days = null;
$base_reward_rate = $reward_rate * 100;
$base_expected_reward = $expected_reward;
$genesis_count_at_stake = 0;
$genesis_tier_at_stake = 'no_genesis';
$genesis_multiplier_at_stake = 1.0;
$genesis_terms_status = 'legacy_not_required';

if (staking_is_v2_active()) {
    /**
     * Season 13 V2 stake creation.
     *
     * Plain language for DEVS:
     * V2 uses day-based lock pools instead of legacy month buttons.
     * The backend stores the user's Genesis tier at stake time, but the final
     * reward still gets checked again at claim time by claim-stake-reward.php.
     */
    $lock_duration_days = isset($data['lock_duration_days']) ? (int)$data['lock_duration_days'] : 0;
    $v2Pool = staking_get_v2_pool_by_days($lock_duration_days);

    if (!$v2Pool) {
        json_response([
            'success' => false,
            'error' => 'Invalid V2 lock duration. Allowed: 14, 30, 90, 180, 365, 730 days'
        ], 400);
    }

    $staking_contract_version = STAKING_CONTRACT_SEASON13_V2;

    /**
     * Plain language for DEVS:
     * freeze_duration_months is legacy-only, but the column is NOT nullable.
     * For V2 rows we store 0 here and use lock_duration_days as the source of truth.
     */
    $freeze_duration_months = 0;

    $base_reward_rate = (float)$v2Pool['reward_rate'];
    $reward_rate = $base_reward_rate;

    $genesis_count_at_stake = staking_count_verified_genesis_for_user($pdo, $user_id);
    $genesisTier = staking_get_genesis_tier($genesis_count_at_stake);

    $genesis_tier_at_stake = (string)$genesisTier['key'];
    $genesis_multiplier_at_stake = (float)$genesisTier['multiplier'];

    $v2Rewards = staking_calculate_v2_rewards(
        $amount,
        $base_reward_rate,
        $genesis_multiplier_at_stake
    );

    $base_expected_reward = (int)$v2Rewards['base_expected_reward'];
    $expected_reward = (int)$v2Rewards['expected_reward'];
    $genesis_terms_status = 'same_or_higher_tier_required_at_claim';
}

// Get current season
$seasonStmt = $pdo->prepare("SELECT season_name FROM tbl_seasons WHERE is_active = 1 LIMIT 1");
$seasonStmt->execute();
$seasonRow = $seasonStmt->fetch(PDO::FETCH_ASSOC);
$currentSeason = $seasonRow['season_name'] ?? 'Season 6';

// Check user balance
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

if ($amount > $available_balance) {
    json_response([
        'success' => false,
        'error' => 'Insufficient balance',
        'available_balance' => $available_balance,
        'requested_amount' => $amount
    ], 400);
}

// Calculate unfreeze date
$frozen_at = date('Y-m-d H:i:s');
if (staking_is_v2_active()) {
    $unfreeze_at = date('Y-m-d H:i:s', strtotime("+{$lock_duration_days} days"));
} else {
    $unfreeze_at = date('Y-m-d H:i:s', strtotime("+{$freeze_duration_months} months"));
}

try {
    $pdo->beginTransaction();

    // Create stake record
    try {
        $stakeStmt = $pdo->prepare("
    INSERT INTO tbl_dspoinc_stakes (
        user_id,
        amount,
        freeze_duration_months,
        reward_rate,
        expected_reward,
        frozen_at,
        unfreeze_at,
        status,
        metadata,
        staking_contract_version,
        lock_duration_days,
        base_reward_rate,
        base_expected_reward,
        genesis_count_at_stake,
        genesis_tier_at_stake,
        genesis_multiplier_at_stake,
        genesis_terms_status
    ) VALUES (
        :user_id,
        :amount,
        :freeze_duration_months,
        :reward_rate,
        :expected_reward,
        :frozen_at,
        :unfreeze_at,
        'active',
        :metadata,
        :staking_contract_version,
        :lock_duration_days,
        :base_reward_rate,
        :base_expected_reward,
        :genesis_count_at_stake,
        :genesis_tier_at_stake,
        :genesis_multiplier_at_stake,
        :genesis_terms_status
    )
");

$metadata = json_encode([
    'created_via' => 'stake-lab',
    'season' => $currentSeason,
    'contract_version' => $staking_contract_version,
    'legacy_duration_months' => $freeze_duration_months,
    'lock_duration_days' => $lock_duration_days,
    'created_from' => staking_is_v2_active() ? 'stake_lab_v2_local_sim' : 'stake_lab_phase_a'
]);

$stakeStmt->execute([
    ':user_id' => $user_id,
    ':amount' => $amount,
    ':freeze_duration_months' => $freeze_duration_months,
    ':reward_rate' => $reward_rate,
    ':expected_reward' => $expected_reward,
    ':frozen_at' => $frozen_at,
    ':unfreeze_at' => $unfreeze_at,
    ':metadata' => $metadata,
    ':staking_contract_version' => $staking_contract_version,
    ':lock_duration_days' => $lock_duration_days,
    ':base_reward_rate' => $base_reward_rate,
    ':base_expected_reward' => $base_expected_reward,
    ':genesis_count_at_stake' => $genesis_count_at_stake,
    ':genesis_tier_at_stake' => $genesis_tier_at_stake,
    ':genesis_multiplier_at_stake' => $genesis_multiplier_at_stake,
    ':genesis_terms_status' => $genesis_terms_status
]);

        $stake_id = $pdo->lastInsertId();
        error_log("✅ Stake record created: ID = $stake_id");
    } catch (Exception $e) {
        error_log("❌ Failed to create stake record: " . $e->getMessage());
        throw new Exception("Failed to create stake record: " . $e->getMessage());
    }

    // NOTE: We do NOT create a negative entry in tbl_user_scores for the freeze
    // The freeze is tracked in tbl_dspoinc_stakes, and available balance is calculated as:
    // available_balance = total_dspoinc - frozen_balance
    // This way, total_dspoinc shows the full amount (including frozen), and available is correctly calculated

    // Track adjustment history (audit trail) - This is CRITICAL for "Recent Score Changes" display
    // Make this part of the transaction so it's committed together
    try {
        // Check table structure first (some tables might use 'created_at' instead of 'timestamp')
        $checkStmt = $pdo->query("PRAGMA table_info(tbl_score_adjustments)");
        $columns = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
        $hasTimestamp = false;
        $hasCreatedAt = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'timestamp') $hasTimestamp = true;
            if ($col['name'] === 'created_at') $hasCreatedAt = true;
        }
        
        $timestampField = $hasTimestamp ? 'timestamp' : ($hasCreatedAt ? 'created_at' : 'timestamp');
        $timestampValue = $hasTimestamp || $hasCreatedAt ? "datetime('now')" : "datetime('now')";
        
        if (staking_is_v2_active()) {
    $reason = sprintf(
        'DSPOINC frozen for Season 13 V2 staking: %d DSPOINC for %d days (base reward: %d DSPOINC, expected reward with Genesis terms: %d DSPOINC)',
        $amount,
        $lock_duration_days,
        $base_expected_reward,
        $expected_reward
    );
} else {
    $reason = sprintf(
        'DSPOINC frozen for staking: %d DSPOINC for %d months (expected reward: %d DSPOINC)',
        $amount,
        $freeze_duration_months,
        $expected_reward
    );
}
        
        // Try with timestamp field first
        $adjustStmt = $pdo->prepare("
            INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp)
            VALUES (:user_id, :admin_id, :amount, :action, :reason, datetime('now'))
        ");
        
        $result = $adjustStmt->execute([
            ':user_id' => $user_id,
            ':admin_id' => 'system-staking',
            ':amount' => -$amount, // Negative for freeze
            ':action' => 'remove', // Use 'remove' since CHECK constraint only allows 'add', 'remove', 'set'
            ':reason' => $reason
        ]);
        
        if ($result) {
            $insertId = $pdo->lastInsertId();
            error_log("✅ Score adjustment entry created successfully (ID: {$insertId}) for Recent Score Changes");
            error_log("✅ Entry details: user_id={$user_id}, amount=-{$amount}, action=stake_freeze, reason={$reason}");
        } else {
            error_log("⚠️ Score adjustment INSERT returned false (but no exception)");
        }
    } catch (Exception $e) {
        error_log("❌ CRITICAL: Failed to create score adjustment entry: " . $e->getMessage());
        error_log("❌ Error details: " . $e->getTraceAsString());
        error_log("❌ SQL Error Info: " . print_r($adjustStmt->errorInfo() ?? [], true));
        // Don't throw - we want the stake to succeed even if audit trail fails
        // But log it prominently so we can fix it
    }

    $pdo->commit();
    error_log("✅ Transaction committed successfully");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log detailed error for debugging
    error_log("🧊 Create Stake Error: " . $e->getMessage());
    error_log("🧊 Create Stake Trace: " . $e->getTraceAsString());
    
    // Return detailed error in localhost, generic in production
    $errorDetails = $isLocalhost ? [
        'success' => false,
        'error' => 'Failed to create stake',
        'details' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ] : [
        'success' => false,
        'error' => 'Failed to create stake',
        'details' => $e->getMessage()
    ];
    
    json_response($errorDetails, 500);
}

// Get updated balances
$balanceStmt->execute([$user_id]);
$balanceRow = $balanceStmt->fetch(PDO::FETCH_ASSOC);
$new_total_balance = (int)($balanceRow['total_balance'] ?? 0);

$frozenStmt->execute([$user_id]);
$frozenRow = $frozenStmt->fetch(PDO::FETCH_ASSOC);
$new_frozen_balance = (int)($frozenRow['frozen_balance'] ?? 0);

$new_available_balance = $new_total_balance - $new_frozen_balance;

json_response([
    'success' => true,
    'data' => [
        'stake_id' => (int)$stake_id,
        'user_id' => $user_id,
        'amount' => $amount,
        'freeze_duration_months' => $freeze_duration_months,
        'staking_contract_version' => $staking_contract_version,
'lock_duration_days' => $lock_duration_days,
'base_reward_rate' => $base_reward_rate,
'base_expected_reward' => $base_expected_reward,
'genesis_count_at_stake' => $genesis_count_at_stake,
'genesis_tier_at_stake' => $genesis_tier_at_stake,
'genesis_multiplier_at_stake' => $genesis_multiplier_at_stake,
'genesis_terms_status' => $genesis_terms_status,
        'reward_rate' => $reward_rate,
        'expected_reward' => $expected_reward,
        'frozen_at' => $frozen_at,
        'unfreeze_at' => $unfreeze_at,
        'status' => 'active',
        'total_balance' => $new_total_balance,
        'available_balance' => $new_available_balance,
        'frozen_balance' => $new_frozen_balance
    ]
]);

