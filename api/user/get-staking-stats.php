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

// Load local Discord secret config if it exists (for local development)
$localDiscordSecretPath = __DIR__ . '/../config/discord-secret.php';
if (file_exists($localDiscordSecretPath)) {
    include $localDiscordSecretPath;
}

function json_response($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

// Get user from session
session_start();
$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID for local testing

$session_user_id = isset($_SESSION['discord_id']) ? $_SESSION['discord_id'] : '';

// Check if user_id is provided in POST/GET (for localhost testing or bot requests)
// Also check JSON body for POST requests
// Also check Authorization header (like db-access.php and get-season-tester-eligible-players.php)
$request_user_id = '';
$botToken = '';

// Check Authorization header first (like db-access.php does)
$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
if (empty($authHeader) && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');
}

// Extract token from Authorization header (supports "Bearer TOKEN" or just "TOKEN")
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
    // Try POST data first (for form-encoded requests)
    $request_user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $botToken = isset($_POST['bot_token']) ? $_POST['bot_token'] : '';
    
    // Always read JSON body for POST requests (JSON takes precedence)
    // This ensures we get bot_token even if the request is JSON-encoded
    $raw_input = file_get_contents('php://input');
    $json_input = json_decode($raw_input, true);
    
    // Debug: Log raw input (first 200 chars only for security)
    error_log("🧊 Staking Stats: Raw input (first 200 chars): " . substr($raw_input, 0, 200));
    error_log("🧊 Staking Stats: JSON decoded: " . (is_array($json_input) ? 'YES' : 'NO'));
    if (is_array($json_input)) {
        error_log("🧊 Staking Stats: JSON keys: " . implode(', ', array_keys($json_input)));
    }
    
    if (is_array($json_input)) {
        // Override with JSON values if they exist (JSON takes precedence)
        if (isset($json_input['user_id'])) {
            $request_user_id = $json_input['user_id'];
        }
        if (isset($json_input['bot_token'])) {
            $botToken = $json_input['bot_token'];
            error_log("🧊 Staking Stats: Found bot_token in JSON (length: " . strlen($botToken) . ")");
        } elseif (isset($json_input['botToken'])) {
            $botToken = $json_input['botToken'];
            error_log("🧊 Staking Stats: Found botToken (camelCase) in JSON (length: " . strlen($botToken) . ")");
        } else {
            error_log("🧊 Staking Stats: ⚠️ No bot_token or botToken found in JSON!");
        }
        // Debug: Log what we extracted
        error_log("🧊 Staking Stats: Extracted from JSON - user_id: " . ($request_user_id ?: 'EMPTY') . ", bot_token: " . (!empty($botToken) ? substr($botToken, 0, 12) . '... (length: ' . strlen($botToken) . ')' : 'EMPTY'));
    } else {
        error_log("🧊 Staking Stats: JSON decode failed or not an array. Raw input length: " . strlen($raw_input));
        if ($raw_input) {
            error_log("🧊 Staking Stats: Raw input content: " . substr($raw_input, 0, 500));
        }
    }
} else {
    // GET request
    $request_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
    $botToken = isset($_GET['bot_token']) ? $_GET['bot_token'] : '';
}

// Debug logging for request extraction
error_log("🧊 Staking Stats: Request extraction - Method: " . $_SERVER['REQUEST_METHOD'] . ", User ID: " . ($request_user_id ?: 'EMPTY') . ", Bot Token: " . (!empty($botToken) ? substr($botToken, 0, 8) . '...' : 'EMPTY'));

// Check if this is a Discord bot request (bypasses session requirement)
// Match verify-nft-holder.php logic EXACTLY for consistency
$isBotRequest = !empty($botToken);
$validBotToken = getenv('DISCORD_SECRET') ?: (isset($DISCORD_SECRET) ? $DISCORD_SECRET : 'admin_quest_system'); // Use same token as verify-nft-holder.php

// Validate token if provided (EXACT same pattern as verify-nft-holder.php line 79)
if ($isBotRequest && $botToken !== $validBotToken) {
    // Token provided but doesn't match - reject it (same as verify-nft-holder.php)
    $isBotRequest = false;
}

// Debug logging for bot token validation (only log first few chars for security)
error_log("🧊 Staking Stats: Bot token check - Provided: " . (!empty($botToken) ? substr($botToken, 0, 8) . '... (length: ' . strlen($botToken) . ')' : 'EMPTY') . ", Expected: " . substr($validBotToken, 0, 8) . '... (length: ' . strlen($validBotToken) . '), Match: ' . ($isBotRequest ? 'YES' : 'NO'));
error_log("🧊 Staking Stats: Environment check - DISCORD_SECRET env var: " . (getenv('DISCORD_SECRET') ? substr(getenv('DISCORD_SECRET'), 0, 8) . '... (set, length: ' . strlen(getenv('DISCORD_SECRET')) . ')' : 'NOT SET (using fallback)'));
error_log("🧊 Staking Stats: Token comparison - botToken === validBotToken: " . ($botToken === $validBotToken ? 'TRUE' : 'FALSE'));
if ($botToken !== $validBotToken && !empty($botToken) && !empty($validBotToken)) {
    error_log("🧊 Staking Stats: Token mismatch details - First 12 chars provided: " . substr($botToken, 0, 12) . ", First 12 chars expected: " . substr($validBotToken, 0, 12));
}

if (!empty($botToken) && !$isBotRequest) {
    // Bot token provided but invalid (same error handling as verify-nft-holder.php)
    error_log("🚨 SECURITY: Invalid bot token provided for staking stats");
    error_log("🚨 SECURITY: Token comparison failed - Provided length: " . strlen($botToken) . ", Expected length: " . strlen($validBotToken));
    error_log("🚨 SECURITY: Provided token (first 12 chars): " . substr($botToken, 0, 12));
    error_log("🚨 SECURITY: Expected token (first 12 chars): " . substr($validBotToken, 0, 12));
    error_log("🚨 SECURITY: Environment variable DISCORD_SECRET is " . (getenv('DISCORD_SECRET') ? 'SET (length: ' . strlen(getenv('DISCORD_SECRET')) . ')' : 'NOT SET (using fallback: admin_quest_system)'));
    error_log("🚨 SECURITY: Token exact match check: " . ($botToken === $validBotToken ? 'MATCH' : 'NO MATCH'));
    json_response([
        'success' => false,
        'error' => 'Invalid bot token for Discord bot requests',
        'debug' => 'Token mismatch. Ensure DISCORD_SECRET environment variable on Render matches the bot\'s DISCORD_SECRET. Check server logs for token comparison details.'
    ], 403);
}

// SECURITY: Determine if we're on localhost
$isLocalhost = strpos(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '', 'localhost') !== false || 
               strpos(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '', '127.0.0.1') !== false;

// SECURITY FIX: Use session user_id in production, allow bot/user_id override for bot requests or localhost
if ($isBotRequest && $request_user_id) {
    // Bot request with valid token: Use provided user_id (bypasses session check)
    $user_id = $request_user_id;
    error_log("🧊 Staking Stats: Bot request (valid token) - Using user_id: {$user_id}");
} elseif ($isLocalhost && $request_user_id) {
    // Local development: Allow override for testing
    $user_id = $request_user_id;
    error_log("🧊 Staking Stats: Using request user_id for localhost testing: {$user_id}");
} else {
    // Production: Use session if available, otherwise allow user_id from request (for Discord bots via GET)
    // This matches the pattern used by check-holder command which works perfectly
    if ($session_user_id) {
        // Session exists - use it and verify request matches
        $user_id = $session_user_id;
        
        // If request user_id provided, verify it matches session (security check)
        // BUT: Skip this check if it's a bot request (should have been caught above)
        if ($request_user_id && $request_user_id !== $session_user_id && !$isBotRequest) {
            error_log("🚨 SECURITY: Get Staking Stats - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
            json_response([
                'success' => false,
                'error' => 'Unauthorized: user_id mismatch'
            ], 403);
        }
    } elseif ($request_user_id) {
        // No session but user_id provided (Discord bot GET request - like check-holder)
        // Allow this for Discord bot compatibility (same pattern as check-holder which works)
        $user_id = $request_user_id;
        error_log("🧊 Staking Stats: No session, using request user_id (Discord bot GET request): {$user_id}");
    } else {
        // No session and no user_id - reject
        $user_id = null;
    }
}

// Local development fallback: Use Narrrf's ID on localhost if no user_id
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

// Get total balance
$balanceStmt = $pdo->prepare("SELECT SUM(score) AS total_balance FROM tbl_user_scores WHERE user_id = ?");
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

// Get active stakes count
$activeCountStmt = $pdo->prepare("
    SELECT COUNT(*) AS active_count 
    FROM tbl_dspoinc_stakes 
    WHERE user_id = ? AND status = 'active'
");
$activeCountStmt->execute([$user_id]);
$activeCountRow = $activeCountStmt->fetch(PDO::FETCH_ASSOC);
$active_stakes_count = (int)($activeCountRow['active_count'] ?? 0);

// Get pending rewards (sum of expected rewards for active stakes)
$pendingRewardsStmt = $pdo->prepare("
    SELECT COALESCE(SUM(expected_reward), 0) AS pending_rewards 
    FROM tbl_dspoinc_stakes 
    WHERE user_id = ? AND status = 'active'
");
$pendingRewardsStmt->execute([$user_id]);
$pendingRewardsRow = $pendingRewardsStmt->fetch(PDO::FETCH_ASSOC);
$pending_rewards = (int)($pendingRewardsRow['pending_rewards'] ?? 0);

// Get total rewards earned (from completed stakes)
$earnedRewardsStmt = $pdo->prepare("
    SELECT COALESCE(SUM(reward_paid), 0) AS earned_rewards 
    FROM tbl_dspoinc_stakes 
    WHERE user_id = ? AND status = 'completed'
");
$earnedRewardsStmt->execute([$user_id]);
$earnedRewardsRow = $earnedRewardsStmt->fetch(PDO::FETCH_ASSOC);
$earned_rewards = (int)($earnedRewardsRow['earned_rewards'] ?? 0);

// Get ready to claim count (completed stakes with reward_paid = 0)
$readyToClaimStmt = $pdo->prepare("
    SELECT COUNT(*) AS ready_count 
    FROM tbl_dspoinc_stakes 
    WHERE user_id = ? AND status = 'completed' AND reward_paid = 0
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
        'pending_rewards' => $pending_rewards
    ],
    'data' => [
        'total_balance' => $total_balance,
        'available_balance' => $available_balance,
        'frozen_balance' => $frozen_balance,
        'active_stakes_count' => $active_stakes_count,
        'pending_rewards' => $pending_rewards,
        'earned_rewards' => $earned_rewards,
        'ready_to_claim' => $ready_to_claim
    ]
]);

