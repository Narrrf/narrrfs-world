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
$request_user_id = '';
$botToken = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Try POST data first
    $request_user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $botToken = isset($_POST['bot_token']) ? $_POST['bot_token'] : '';
    // If not in POST, try JSON body
    if (!$request_user_id) {
        $json_input = json_decode(file_get_contents('php://input'), true);
        $request_user_id = isset($json_input['user_id']) ? $json_input['user_id'] : '';
        $botToken = isset($json_input['bot_token']) ? $json_input['bot_token'] : (isset($json_input['botToken']) ? $json_input['botToken'] : '');
    }
} else {
    // GET request
    $request_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
    $botToken = isset($_GET['bot_token']) ? $_GET['bot_token'] : '';
}

// Check if this is a Discord bot request (bypasses session requirement)
$isBotRequest = !empty($botToken);
$validBotToken = getenv('DISCORD_SECRET') ?: 'admin_quest_system'; // Use same token as grant-role.php

if ($isBotRequest && $botToken !== $validBotToken) {
    json_response([
        'success' => false,
        'error' => 'Invalid bot token for Discord bot requests'
    ], 403);
}

// SECURITY: Determine if we're on localhost
$isLocalhost = strpos(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '', 'localhost') !== false || 
               strpos(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '', '127.0.0.1') !== false;

// SECURITY FIX: Use session user_id in production, allow bot/user_id override for bot requests or localhost
if ($isBotRequest && $request_user_id) {
    // Bot request: Use provided user_id (bot token already validated)
    $user_id = $request_user_id;
    error_log("🧊 Staking Stats: Bot request - Using user_id: {$user_id}");
} elseif ($isLocalhost && $request_user_id) {
    // Local development: Allow override for testing
    $user_id = $request_user_id;
    error_log("🧊 Staking Stats: Using request user_id for localhost testing: {$user_id}");
} else {
    // Production: Always use session, verify request matches session
    $user_id = $session_user_id;
    
    // If request user_id provided, verify it matches session (security check)
    if ($request_user_id && $request_user_id !== $session_user_id) {
        error_log("🚨 SECURITY: Get Staking Stats - user_id mismatch. Session: {$session_user_id}, Request: {$request_user_id}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
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

