<?php
// 🧊 Unstake Stake API - Process early unstake with 15% penalty
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
        error_log("🧊 Unstake Stake: Using local test user (Narrrf) for localhost");
    }
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
$unstake_reason = $data['reason'] ?? '';

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

// Verify stake is active
if ($stake['status'] !== 'active') {
    json_response([
        'success' => false,
        'error' => 'Stake is not active. Only active stakes can be unstaked.',
        'current_status' => $stake['status']
    ], 400);
}

// Calculate penalty (15% of original amount)
$original_amount = (int)$stake['amount'];
$penalty_amount = (int)floor($original_amount * 0.15); // 15% penalty, rounded down
$returned_amount = $original_amount - $penalty_amount; // 85% returned

// Begin transaction
try {
    $pdo->beginTransaction();

    $now = date('Y-m-d H:i:s');

    // Update stake status to cancelled
    $updateStmt = $pdo->prepare("
        UPDATE tbl_dspoinc_stakes 
        SET status = 'cancelled',
            cancelled_at = ?,
            penalty_amount = ?,
            returned_amount = ?,
            unstake_reason = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $updateStmt->execute([
        $now,
        $penalty_amount,
        $returned_amount,
        $unstake_reason,
        $stake_id
    ]);

    // Add returned amount to tbl_user_scores (unfreeze return)
    $scoreStmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (user_id, score, game, source, season)
        VALUES (:user_id, :score, :game, :source, :season)
    ");
    $scoreStmt->execute([
        ':user_id' => $user_id,
        ':score' => $returned_amount, // 85% returned
        ':game' => 'staking',
        ':source' => 'unstake_return',
        ':season' => $currentSeason
    ]);

    // Create audit trail entry
    $reason = sprintf(
        'Early unstake (15%% penalty): %d DSPOINC - %d penalty = %d returned (stake_id: %d)',
        $original_amount,
        $penalty_amount,
        $returned_amount,
        $stake_id
    );
    
    $adjustStmt = $pdo->prepare("
        INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp)
        VALUES (:user_id, :admin_id, :amount, :action, :reason, :timestamp)
    ");
    $adjustStmt->execute([
        ':user_id' => $user_id,
        ':admin_id' => 'system-staking',
        ':amount' => $returned_amount, // Positive amount (returned to user)
        ':action' => 'add',
        ':reason' => $reason,
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
            'penalty_amount' => $penalty_amount,
            'returned_amount' => $returned_amount,
            'status' => 'cancelled',
            'cancelled_at' => $now,
            'new_balance' => $new_balance
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error unstaking stake {$stake_id}: " . $e->getMessage());
    
    json_response([
        'success' => false,
        'error' => 'Failed to unstake stake',
        'details' => $isLocalhost ? $e->getMessage() : 'Internal server error'
    ], 500);
}

