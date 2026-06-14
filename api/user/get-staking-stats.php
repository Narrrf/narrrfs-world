<?php
// 🧊 Get Staking Stats API - Get staking statistics for user (summary for profile page)
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

// Load local Discord secret config if it exists (for local development)
$localDiscordSecretPath = __DIR__ . '/../config/discord-secret.php';
if (file_exists($localDiscordSecretPath)) {
    include $localDiscordSecretPath;
}

/**
 * Return a JSON response and stop execution.
 */
function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Extract Authorization header in a server-compatible way.
 */
function get_authorization_header() {
    $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

    if (!$authHeader && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization'])
            ? $headers['Authorization']
            : (isset($headers['authorization']) ? $headers['authorization'] : '');
    }

    return $authHeader;
}

/**
 * Determine whether the current host is local development.
 */
function is_localhost_request() {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Auto-complete matured active stakes before summary math runs.
 * This keeps profile/lab summary counters in sync with get-stakes.php.
 */
function sync_matured_stakes(PDO $pdo, $user_id) {
    $nowSql = date('Y-m-d H:i:s');

    $updateStmt = $pdo->prepare("
        UPDATE tbl_dspoinc_stakes
        SET status = 'completed',
            completed_at = COALESCE(completed_at, unfreeze_at, ?)
        WHERE user_id = ?
          AND status = 'active'
          AND unfreeze_at IS NOT NULL
          AND datetime(unfreeze_at) <= datetime(?)
    ");

    $updateStmt->execute([$nowSql, $user_id, $nowSql]);

    error_log("🧊 Staking Stats: Auto-completed matured stakes for user {$user_id}. Rows updated: " . $updateStmt->rowCount());
}

// Get user from session
session_start();
$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID for local testing

$session_user_id = isset($_SESSION['discord_id']) ? $_SESSION['discord_id'] : '';
$request_user_id = '';
$botToken = '';

// Check Authorization header first
$authHeader = get_authorization_header();
if ($authHeader) {
    if (strpos($authHeader, 'Bearer ') === 0) {
        $botToken = substr($authHeader, 7);
        error_log("🧊 Staking Stats: Found bot token in Authorization header (Bearer)");
    } else {
        $botToken = $authHeader;
        error_log("🧊 Staking Stats: Found bot token in Authorization header (no Bearer prefix)");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form-encoded fallbacks
    $request_user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    if (empty($botToken)) {
        $botToken = isset($_POST['bot_token']) ? $_POST['bot_token'] : '';
    }

    // JSON body takes precedence
    $raw_input = file_get_contents('php://input');
    $json_input = json_decode($raw_input, true);

    error_log("🧊 Staking Stats: Raw input (first 200 chars): " . substr($raw_input, 0, 200));
    error_log("🧊 Staking Stats: JSON decoded: " . (is_array($json_input) ? 'YES' : 'NO'));

    if (is_array($json_input)) {
        error_log("🧊 Staking Stats: JSON keys: " . implode(', ', array_keys($json_input)));

        if (isset($json_input['user_id'])) {
            $request_user_id = $json_input['user_id'];
        }

        if (isset($json_input['bot_token'])) {
            $botToken = $json_input['bot_token'];
            error_log("🧊 Staking Stats: Found bot_token in JSON (length: " . strlen($botToken) . ")");
        } elseif (isset($json_input['botToken'])) {
            $botToken = $json_input['botToken'];
            error_log("🧊 Staking Stats: Found botToken (camelCase) in JSON (length: " . strlen($botToken) . ")");
        } elseif (!empty($botToken)) {
            error_log("🧊 Staking Stats: Using bot token from Authorization header.");
        } else {
            error_log("🧊 Staking Stats: ⚠️ No bot_token or botToken found in JSON!");
        }

        error_log(
            "🧊 Staking Stats: Extracted from JSON - user_id: " .
            ($request_user_id ?: 'EMPTY') .
            ", bot_token: " .
            (!empty($botToken) ? substr($botToken, 0, 12) . '... (length: ' . strlen($botToken) . ')' : 'EMPTY')
        );
    } else {
        error_log("🧊 Staking Stats: JSON decode failed or not an array. Raw input length: " . strlen($raw_input));
        if ($raw_input) {
            error_log("🧊 Staking Stats: Raw input content: " . substr($raw_input, 0, 500));
        }
    }
} else {
    // GET request
    $request_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
    if (empty($botToken)) {
        $botToken = isset($_GET['bot_token']) ? $_GET['bot_token'] : '';
    }
}

error_log(
    "🧊 Staking Stats: Request extraction - Method: " .
    $_SERVER['REQUEST_METHOD'] .
    ", User ID: " .
    ($request_user_id ?: 'EMPTY') .
    ", Bot Token: " .
    (!empty($botToken) ? substr($botToken, 0, 8) . '...' : 'EMPTY')
);

// Check if this is a Discord bot request
$isBotRequest = !empty($botToken);
$validBotToken = getenv('DISCORD_SECRET') ?: (isset($DISCORD_SECRET) ? $DISCORD_SECRET : 'admin_quest_system');

// Validate token if provided
if ($isBotRequest && $botToken !== $validBotToken) {
    $isBotRequest = false;
}

error_log(
    "🧊 Staking Stats: Bot token check - Provided: " .
    (!empty($botToken) ? substr($botToken, 0, 8) . '... (length: ' . strlen($botToken) . ')' : 'EMPTY') .
    ", Expected: " .
    substr($validBotToken, 0, 8) . '... (length: ' . strlen($validBotToken) . '), Match: ' .
    ($isBotRequest ? 'YES' : 'NO')
);
error_log(
    "🧊 Staking Stats: Environment check - DISCORD_SECRET env var: " .
    (getenv('DISCORD_SECRET')
        ? substr(getenv('DISCORD_SECRET'), 0, 8) . '... (set, length: ' . strlen(getenv('DISCORD_SECRET')) . ')'
        : 'NOT SET (using fallback)')
);
error_log("🧊 Staking Stats: Token comparison - botToken === validBotToken: " . ($botToken === $validBotToken ? 'TRUE' : 'FALSE'));

if ($botToken !== $validBotToken && !empty($botToken) && !empty($validBotToken)) {
    error_log(
        "🧊 Staking Stats: Token mismatch details - First 12 chars provided: " .
        substr($botToken, 0, 12) .
        ", First 12 chars expected: " .
        substr($validBotToken, 0, 12)
    );
}

if (!empty($botToken) && !$isBotRequest) {
    error_log("🚨 SECURITY: Invalid bot token provided for staking stats");
    error_log("🚨 SECURITY: Token comparison failed - Provided length: " . strlen($botToken) . ", Expected length: " . strlen($validBotToken));
    error_log("🚨 SECURITY: Provided token (first 12 chars): " . substr($botToken, 0, 12));
    error_log("🚨 SECURITY: Expected token (first 12 chars): " . substr($validBotToken, 0, 12));
    error_log(
        "🚨 SECURITY: Environment variable DISCORD_SECRET is " .
        (getenv('DISCORD_SECRET')
            ? 'SET (length: ' . strlen(getenv('DISCORD_SECRET')) . ')'
            : 'NOT SET (using fallback: admin_quest_system)')
    );
    error_log("🚨 SECURITY: Token exact match check: " . ($botToken === $validBotToken ? 'MATCH' : 'NO MATCH'));

    json_response([
        'success' => false,
        'error' => 'Invalid bot token for Discord bot requests',
        'debug' => 'Token mismatch. Ensure DISCORD_SECRET environment variable on Render matches the bot\'s DISCORD_SECRET. Check server logs for token comparison details.'
    ], 403);
}

$isLocalhost = is_localhost_request();

// Resolve user_id safely
if ($isBotRequest && $request_user_id) {
    $user_id = $request_user_id;
    error_log("🧊 Staking Stats: Bot request (valid token) - Using user_id: {$user_id}");
} elseif ($isLocalhost && $request_user_id) {
    $user_id = $request_user_id;
    error_log("🧊 Staking Stats: Using request user_id for localhost testing: {$user_id}");
} else {
    if ($session_user_id) {
        $user_id = $session_user_id;

        if ($request_user_id && $request_user_id !== $session_user_id && !$isBotRequest) {
            error_log("🚨 SECURITY: Get Staking Stats - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
            json_response([
                'success' => false,
                'error' => 'Unauthorized: user_id mismatch'
            ], 403);
        }
    } elseif ($request_user_id) {
        $user_id = $request_user_id;
        error_log("🧊 Staking Stats: No session, using request user_id (Discord bot GET request): {$user_id}");
    } else {
        $user_id = null;
    }
}

// Local development fallback
if (!$user_id && $isLocalhost) {
    $user_id = $LOCAL_TEST_DISCORD_ID;
    error_log("🧊 Staking Stats: Using local test user (Narrrf) for localhost");
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

// ✅ Keep summary endpoint in sync with get-stakes.php
try {
    sync_matured_stakes($pdo, $user_id);
} catch (Exception $e) {
    error_log("❌ Staking Stats: Failed during matured stake sync for user {$user_id}: " . $e->getMessage());
}

// Get total balance
$balanceStmt = $pdo->prepare("
    SELECT COALESCE(SUM(score), 0) AS total_balance
    FROM tbl_user_scores
    WHERE user_id = ?
");
$balanceStmt->execute([$user_id]);
$balanceRow = $balanceStmt->fetch(PDO::FETCH_ASSOC);
$total_balance = (int)($balanceRow['total_balance'] ?? 0);

// Get frozen balance (active stakes only)
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
// This is summary/display data only. Reward calculation stays inside
// create-stake.php, claim-stake-reward.php, and unstake-stake.php.
$currentGenesisCount = staking_count_verified_genesis_for_user($pdo, $user_id);
$currentGenesisTier = staking_get_genesis_tier($currentGenesisCount);

// Get active stakes count
$activeCountStmt = $pdo->prepare("
    SELECT COUNT(*) AS active_count
    FROM tbl_dspoinc_stakes
    WHERE user_id = ? AND status = 'active'
");
$activeCountStmt->execute([$user_id]);
$activeCountRow = $activeCountStmt->fetch(PDO::FETCH_ASSOC);
$active_stakes_count = (int)($activeCountRow['active_count'] ?? 0);

// Get pending rewards (expected rewards for active stakes only)
$pendingRewardsStmt = $pdo->prepare("
    SELECT COALESCE(SUM(expected_reward), 0) AS pending_rewards
    FROM tbl_dspoinc_stakes
    WHERE user_id = ? AND status = 'active'
");
$pendingRewardsStmt->execute([$user_id]);
$pendingRewardsRow = $pendingRewardsStmt->fetch(PDO::FETCH_ASSOC);
$pending_rewards = (int)($pendingRewardsRow['pending_rewards'] ?? 0);

// Get total rewards earned (completed stakes that were actually paid)
$earnedRewardsStmt = $pdo->prepare("
    SELECT COALESCE(SUM(expected_reward), 0) AS earned_rewards
    FROM tbl_dspoinc_stakes
    WHERE user_id = ?
      AND status = 'completed'
      AND reward_paid != 0
");
$earnedRewardsStmt->execute([$user_id]);
$earnedRewardsRow = $earnedRewardsStmt->fetch(PDO::FETCH_ASSOC);
$earned_rewards = (int)($earnedRewardsRow['earned_rewards'] ?? 0);

// Get ready-to-claim count (completed stakes with unpaid rewards)
$readyToClaimStmt = $pdo->prepare("
    SELECT COUNT(*) AS ready_count
    FROM tbl_dspoinc_stakes
    WHERE user_id = ?
      AND status = 'completed'
      AND reward_paid = 0
");
$readyToClaimStmt->execute([$user_id]);
$readyToClaimRow = $readyToClaimStmt->fetch(PDO::FETCH_ASSOC);
$ready_to_claim = (int)($readyToClaimRow['ready_count'] ?? 0);

// Return data in format expected by both website and Discord bot
json_response([
    'success' => true,
    'staking_stats' => [
        'total_balance' => $total_balance,
        'total_available' => $available_balance,
        'total_staked' => $frozen_balance,
        'active_stakes' => $active_stakes_count,
        'ready_to_claim' => $ready_to_claim,
        'total_rewards' => $earned_rewards,
        'pending_rewards' => $pending_rewards,

        // Season 13 V2 readiness:
        // Summary-only contract data for Discord bot / older consumers.
        'staking_contracts' => [
            'active_contract_version' => ACTIVE_STAKING_CONTRACT_VERSION,
            'legacy_version' => STAKING_CONTRACT_LEGACY_V1,
            'season13_v2_version' => STAKING_CONTRACT_SEASON13_V2,
            'season13_v2_active' => staking_is_v2_active(),
        ],
        'season13_v2_preview' => [
            'current_genesis_count' => $currentGenesisCount,
            'current_genesis_tier' => $currentGenesisTier,
            'same_tier_rule' => 'same_or_higher_tier_required_at_claim',
            'non_genesis_allowed' => true,
        ]
    ],
    'data' => [
        'total_balance' => $total_balance,
        'available_balance' => $available_balance,
        'frozen_balance' => $frozen_balance,
        'active_stakes_count' => $active_stakes_count,
        'pending_rewards' => $pending_rewards,
        'earned_rewards' => $earned_rewards,
        'ready_to_claim' => $ready_to_claim,

        // Season 13 V2 readiness:
        // Frontend may display this contract state, but backend remains authoritative.
        'staking_contracts' => [
            'active_contract_version' => ACTIVE_STAKING_CONTRACT_VERSION,
            'legacy_version' => STAKING_CONTRACT_LEGACY_V1,
            'season13_v2_version' => STAKING_CONTRACT_SEASON13_V2,
            'season13_v2_active' => staking_is_v2_active(),
        ],

        // Season 13 V2 preview:
        // This lets profile/stake summary panels show current Genesis boost status.
        'season13_v2_preview' => [
            'current_genesis_count' => $currentGenesisCount,
            'current_genesis_tier' => $currentGenesisTier,
            'same_tier_rule' => 'same_or_higher_tier_required_at_claim',
            'non_genesis_allowed' => true,
        ]
    ]
]);