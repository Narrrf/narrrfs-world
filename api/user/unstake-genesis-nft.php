<?php
/**
 * Unstake Genesis NFT API.
 *
 * Plain language for DEVS:
 * This endpoint consumes a pending Genesis Mouse Freezer unfreeze challenge
 * and closes the active internal freezer row.
 *
 * Safety status:
 * - Localhost can use local_dev_confirm for API testing.
 * - Production intentionally refuses activation until Solana Memo TX verification
 *   is extracted into a reusable helper and wired here.
 *
 * This endpoint does NOT pay DSPOINC.
 * This endpoint does NOT create claim rows.
 * This endpoint does NOT touch tbl_dspoinc_stakes.
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/genesis-nft-staking-helpers.php';

const GENESIS_NFT_LOCAL_DEV_UNFREEZE_CONFIRM_TEXT = 'I_UNDERSTAND_THIS_IS_LOCAL_ONLY';

/**
 * Return a JSON response and stop execution.
 */
function json_response($payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Determine whether the request is running on local development.
 */
function is_localhost_request(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data once from JSON, POST, and GET.
 */
function get_unstake_genesis_nft_request_data(): array
{
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

/**
 * Resolve the authenticated user.
 *
 * Plain language for DEVS:
 * Production must use the Discord session.
 * Localhost may pass user_id for curl testing.
 */
function resolve_unstake_genesis_nft_user_id(array $requestData): string
{
    $LOCAL_TEST_DISCORD_ID = '328601656659017732';

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $requestUserId = trim((string)($requestData['user_id'] ?? ''));

    if (is_localhost_request() && $requestUserId !== '') {
        return $requestUserId;
    }

    if ($requestUserId !== '' && $requestUserId !== $sessionUserId) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($sessionUserId !== '') {
        return $sessionUserId;
    }

    if (is_localhost_request()) {
        return $LOCAL_TEST_DISCORD_ID;
    }

    return '';
}

/**
 * Load one active Genesis NFT freezer row for the user.
 */
function load_active_genesis_nft_stake_for_unstake(PDO $pdo, int $stakeId, string $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_genesis_nft_stakes
        WHERE id = ?
          AND user_id = ?
          AND collection = ?
          AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$stakeId, $userId, GENESIS_NFT_COLLECTION_KEY]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Load the pending unfreeze challenge.
 */
function load_pending_unfreeze_challenge(PDO $pdo, int $challengeId, string $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_genesis_nft_stake_challenges
        WHERE id = ?
          AND user_id = ?
          AND collection = ?
          AND action = 'unfreeze'
          AND status = 'pending'
        LIMIT 1
    ");
    $stmt->execute([$challengeId, $userId, GENESIS_NFT_COLLECTION_KEY]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Confirm the NFT is still verified for the exact same user + wallet + token.
 *
 * Plain language for DEVS:
 * Ownership check stays mandatory before unfreeze.
 */
function verify_unstake_genesis_nft_ownership(PDO $pdo, array $stake): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            ownership_id,
            user_id,
            username,
            wallet,
            token_id,
            collection,
            nft_name,
            image_url,
            is_verified,
            verified_at,
            last_seen_at
        FROM tbl_nft_ownership
        WHERE user_id = ?
          AND wallet = ?
          AND token_id = ?
          AND collection = ?
          AND COALESCE(is_verified, 0) = 1
        ORDER BY
            COALESCE(verified_at, '') DESC,
            COALESCE(acquired_at, '') DESC,
            ownership_id DESC
        LIMIT 1
    ");
    $stmt->execute([
        $stake['user_id'],
        $stake['wallet'],
        $stake['token_id'],
        GENESIS_NFT_COLLECTION_KEY
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Validate local-only unfreeze activation.
 *
 * TODO:
 * Extract real Solana Memo TX verification from verify-nft-holder.php into a
 * reusable helper before enabling production freezer unfreeze activation.
 */
function validate_unfreeze_activation_proof(array $requestData): string
{
    $memoSignature = trim((string)($requestData['memo_signature'] ?? ''));
    $localDevConfirm = trim((string)($requestData['local_dev_confirm'] ?? ''));

    if (is_localhost_request() && $localDevConfirm === GENESIS_NFT_LOCAL_DEV_UNFREEZE_CONFIRM_TEXT) {
        return $memoSignature !== '' ? $memoSignature : 'LOCAL_DEV_UNFREEZE_CONFIRM';
    }

    json_response([
        'success' => false,
        'error' => 'Production unfreeze activation is not enabled yet. Solana Memo TX verification must be extracted and wired before live use.',
        'required_next_step' => 'Extract reusable verifySolanaMemoTransaction helper before production activation.'
    ], 403);
}

/**
 * Build metadata for the closed freezer row.
 */
function build_genesis_nft_unstake_metadata(array $stake, array $challenge, array $ownership): string
{
    $existingMetadata = [];
    if (!empty($stake['metadata'])) {
        $decoded = json_decode((string)$stake['metadata'], true);
        if (is_array($decoded)) {
            $existingMetadata = $decoded;
        }
    }

    $existingMetadata['unfreeze'] = [
        'source' => 'genesis_mouse_freezer',
        'activation_mode' => is_localhost_request() ? 'local_dev_confirm' : 'memo_transaction',
        'challenge_id' => (int)$challenge['id'],
        'challenge_nonce' => $challenge['nonce'] ?? null,
        'challenge_message' => $challenge['message'] ?? null,
        'ownership_id' => $ownership['ownership_id'] ?? null,
        'ownership_verified_at' => $ownership['verified_at'] ?? null,
        'closed_without_reward_write' => true
    ];

    return json_encode($existingMetadata);
}

session_start();

$requestData = get_unstake_genesis_nft_request_data();
$userId = resolve_unstake_genesis_nft_user_id($requestData);

if ($userId === '') {
    json_response([
        'success' => false,
        'error' => 'Not logged in'
    ], 401);
}

$stakeId = isset($requestData['stake_id']) ? (int)$requestData['stake_id'] : 0;
$challengeId = isset($requestData['challenge_id']) ? (int)$requestData['challenge_id'] : 0;

if ($stakeId <= 0) {
    json_response([
        'success' => false,
        'error' => 'stake_id is required'
    ], 400);
}

if ($challengeId <= 0) {
    json_response([
        'success' => false,
        'error' => 'challenge_id is required'
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

try {
    $stake = load_active_genesis_nft_stake_for_unstake($pdo, $stakeId, $userId);

    if (!$stake) {
        json_response([
            'success' => false,
            'error' => 'Active Genesis NFT stake not found for this user.'
        ], 404);
    }

    $challenge = load_pending_unfreeze_challenge($pdo, $challengeId, $userId);

    if (!$challenge) {
        json_response([
            'success' => false,
            'error' => 'Pending unfreeze challenge not found.'
        ], 404);
    }

    if (strtotime((string)$challenge['expires_at']) < time()) {
        json_response([
            'success' => false,
            'error' => 'Unfreeze challenge expired. Please create a new challenge.',
            'expires_at' => $challenge['expires_at']
        ], 409);
    }

    if ((string)$challenge['wallet'] !== (string)$stake['wallet'] || (string)$challenge['token_id'] !== (string)$stake['token_id']) {
        json_response([
            'success' => false,
            'error' => 'Unfreeze challenge does not match the active stake wallet/token.'
        ], 409);
    }

    $ownership = verify_unstake_genesis_nft_ownership($pdo, $stake);

    if (!$ownership) {
        json_response([
            'success' => false,
            'error' => 'Ownership changed. Unfreeze cannot continue until ownership is reverified.',
            'ownership_check_status' => 'ownership_lost'
        ], 409);
    }

    $claimFrom = $stake['last_claimed_at'] ?: $stake['frozen_at'];
    $pendingFullDays = genesis_nft_staking_calculate_full_days($claimFrom);

    if ($pendingFullDays > 0) {
        json_response([
            'success' => false,
            'error' => 'This stake has claimable full days. Claim rewards first, then unfreeze.',
            'pending_full_days' => $pendingFullDays
        ], 409);
    }

    $memoSignature = validate_unfreeze_activation_proof($requestData);
    $metadata = build_genesis_nft_unstake_metadata($stake, $challenge, $ownership);

    $pdo->beginTransaction();

    $updateStakeStmt = $pdo->prepare("
        UPDATE tbl_genesis_nft_stakes
        SET status = 'unstaked',
            unstaked_at = CURRENT_TIMESTAMP,
            unfreeze_challenge_id = :unfreeze_challenge_id,
            unfreeze_memo_signature = :unfreeze_memo_signature,
            ownership_check_status = 'verified',
            ownership_checked_at = CURRENT_TIMESTAMP,
            metadata = :metadata,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :stake_id
          AND user_id = :user_id
          AND status = 'active'
    ");
    $updateStakeStmt->execute([
        ':unfreeze_challenge_id' => $challengeId,
        ':unfreeze_memo_signature' => $memoSignature,
        ':metadata' => $metadata,
        ':stake_id' => $stakeId,
        ':user_id' => $userId
    ]);

    $updateChallengeStmt = $pdo->prepare("
        UPDATE tbl_genesis_nft_stake_challenges
        SET status = 'used',
            memo_signature = ?,
            used_at = CURRENT_TIMESTAMP
        WHERE id = ?
          AND status = 'pending'
    ");
    $updateChallengeStmt->execute([$memoSignature, $challengeId]);

    $pdo->commit();

    json_response([
        'success' => true,
        'data' => [
            'stake_id' => $stakeId,
            'challenge_id' => $challengeId,
            'status' => 'unstaked',
            'wallet' => $stake['wallet'],
            'token_id' => $stake['token_id'],
            'collection' => GENESIS_NFT_COLLECTION_KEY,
            'nft_name' => $stake['nft_name'],
            'total_claimed' => (int)$stake['total_claimed'],
            'ownership_check_status' => 'verified',
            'system_status' => [
                'phase' => 'phase_6_local_unfreeze_completed',
                'local_only_activation' => is_localhost_request(),
                'writes_rewards' => false,
                'creates_claim_rows' => false,
                'touches_dspoinc_stakes' => false
            ]
        ]
    ]);
} catch (Exception $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'success' => false,
        'error' => 'Genesis NFT unstake failed',
        'details' => $e->getMessage()
    ], 500);
}
