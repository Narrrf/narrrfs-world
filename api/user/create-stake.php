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

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

// Get user from session
session_start();
$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID for local testing

$user_id = $_SESSION['discord_id'] ?? '';

// Check if user_id is provided in POST/GET (works for both local and production)
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

// Use request user_id if provided (takes priority over session)
if ($request_user_id) {
    $user_id = $request_user_id;
} else if (!$user_id) {
    // Local development fallback: Use Narrrf's ID on localhost
    $isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                   strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
    if ($isLocalhost) {
        $user_id = $LOCAL_TEST_DISCORD_ID;
        error_log("🧊 Create Stake: Using local test user (Narrrf) for localhost");
    }
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
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Create indexes
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_stakes_user_status ON tbl_dspoinc_stakes(user_id, status)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_stakes_unfreeze_at ON tbl_dspoinc_stakes(unfreeze_at, status)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_stakes_user_active ON tbl_dspoinc_stakes(user_id, status, unfreeze_at)");

// Get request data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

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
if (!in_array($freeze_duration_months, $allowed_durations)) {
    json_response([
        'success' => false,
        'error' => 'Invalid duration. Allowed: 1, 3, 6, 12, 24, 36 months'
    ], 400);
}

// Reward rates (must match frontend REWARD_RATES)
$reward_rates = [
    1 => 0.02,   // 2%
    3 => 0.05,   // 5%
    6 => 0.10,   // 10%
    12 => 0.20,  // 20%
    24 => 0.35,  // 35% (matches frontend)
    36 => 0.50   // 50% (matches frontend)
];

$reward_rate = $reward_rates[$freeze_duration_months];
$expected_reward = (int)round($amount * $reward_rate);

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
$unfreeze_at = date('Y-m-d H:i:s', strtotime("+{$freeze_duration_months} months"));

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
                metadata
            ) VALUES (
                :user_id,
                :amount,
                :freeze_duration_months,
                :reward_rate,
                :expected_reward,
                :frozen_at,
                :unfreeze_at,
                'active',
                :metadata
            )
        ");

        $metadata = json_encode([
            'created_via' => 'stake-lab',
            'season' => $currentSeason
        ]);

        $stakeStmt->execute([
            ':user_id' => $user_id,
            ':amount' => $amount,
            ':freeze_duration_months' => $freeze_duration_months,
            ':reward_rate' => $reward_rate,
            ':expected_reward' => $expected_reward,
            ':frozen_at' => $frozen_at,
            ':unfreeze_at' => $unfreeze_at,
            ':metadata' => $metadata
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
        
        $reason = sprintf(
            'DSPOINC frozen for staking: %d DSPOINC for %d months (expected reward: %d DSPOINC)',
            $amount,
            $freeze_duration_months,
            $expected_reward
        );
        
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

