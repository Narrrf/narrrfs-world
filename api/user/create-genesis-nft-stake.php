<?php
/**
 * Create Genesis NFT Stake API.
 *
 * Plain language for DEVS:
 * This endpoint consumes a pending Genesis Mouse Freezer freeze challenge
 * and creates the active internal freezer row.
 *
 * Safety status:
 * - Localhost can use local_dev_confirm for API testing.
 * - Production intentionally refuses activation until Solana Memo TX verification
 *   is extracted into a reusable helper and wired here.
 *
 * This endpoint does NOT pay DSPOINC.
 * This endpoint does NOT touch tbl_dspoinc_stakes.
 * This endpoint does NOT create claim rows.
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

const GENESIS_NFT_LOCAL_DEV_CONFIRM_TEXT = 'I_UNDERSTAND_THIS_IS_LOCAL_ONLY';

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
function get_create_genesis_nft_stake_request_data(): array
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
function resolve_create_genesis_nft_stake_user_id(array $requestData): string
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
 * Load the pending freeze challenge.
 */
function load_pending_freeze_challenge(PDO $pdo, int $challengeId, string $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_genesis_nft_stake_challenges
        WHERE id = ?
          AND user_id = ?
          AND collection = ?
          AND action = 'freeze'
          AND status = 'pending'
        LIMIT 1
    ");
    $stmt->execute([$challengeId, $userId, GENESIS_NFT_COLLECTION_KEY]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Load exact verified ownership for the challenged NFT.
 *
 * Plain language for DEVS:
 * A Discord user can have Genesis NFTs across multiple wallets.
 * The freezer row must bind to the exact wallet that owns this token.
 */
function load_exact_verified_genesis_ownership(PDO $pdo, string $userId, string $wallet, string $tokenId): ?array
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
    $stmt->execute([$userId, $wallet, $tokenId, GENESIS_NFT_COLLECTION_KEY]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Count active freezer rows for one user.
 */
function count_create_genesis_nft_active_stakes(PDO $pdo, string $userId): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS active_count
        FROM tbl_genesis_nft_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $stmt->execute([$userId]);

    return genesis_nft_staking_int($stmt->fetchColumn());
}

/**
 * Check if the token is already actively frozen.
 */
function create_genesis_nft_has_active_stake(PDO $pdo, string $tokenId): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS active_count
        FROM tbl_genesis_nft_stakes
        WHERE token_id = ?
          AND collection = ?
          AND status = 'active'
    ");
    $stmt->execute([$tokenId, GENESIS_NFT_COLLECTION_KEY]);

    return genesis_nft_staking_int($stmt->fetchColumn()) > 0;
}

/**
 * Validate local-only activation.
 *
 * TODO:
 * Extract real Solana Memo TX verification from verify-nft-holder.php into a
 * reusable helper before enabling production freezer activation.
 */
function validate_freeze_activation_proof(array $requestData, array $challenge): string
{
    $memoSignature = trim((string)($requestData['memo_signature'] ?? ''));
    $localDevConfirm = trim((string)($requestData['local_dev_confirm'] ?? ''));

    if (is_localhost_request() && $localDevConfirm === GENESIS_NFT_LOCAL_DEV_CONFIRM_TEXT) {
        return $memoSignature !== '' ? $memoSignature : 'LOCAL_DEV_FREEZE_CONFIRM';
    }

    json_response([
        'success' => false,
        'error' => 'Production freeze activation is not enabled yet. Solana Memo TX verification must be extracted and wired before live use.',
        'required_next_step' => 'Extract reusable verifySolanaMemoTransaction helper before production activation.'
    ], 403);
}

/**
 * Build metadata for the active freezer row.
 */
function build_genesis_nft_freeze_metadata(array $challenge, array $ownership, array $tier, bool $hasVip): string
{
    return json_encode([
        'source' => 'genesis_mouse_freezer',
        'activation_mode' => is_localhost_request() ? 'local_dev_confirm' : 'memo_transaction',
        'challenge_nonce' => $challenge['nonce'] ?? null,
        'challenge_message' => $challenge['message'] ?? null,
        'ownership_verified_at' => $ownership['verified_at'] ?? null,
        'tier_key_at_freeze' => $tier['key'] ?? null,
        'tier_label_at_freeze' => $tier['label'] ?? null,
        'vip_bonus_active_at_freeze' => $hasVip
    ]);
}

session_start();

$requestData = get_create_genesis_nft_stake_request_data();
$userId = resolve_create_genesis_nft_stake_user_id($requestData);

if ($userId === '') {
    json_response([
        'success' => false,
        'error' => 'Not logged in'
    ], 401);
}

$challengeId = isset($requestData['challenge_id']) ? (int)$requestData['challenge_id'] : 0;

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
    $challenge = load_pending_freeze_challenge($pdo, $challengeId, $userId);

    if (!$challenge) {
        json_response([
            'success' => false,
            'error' => 'Pending freeze challenge not found.'
        ], 404);
    }

    if (strtotime((string)$challenge['expires_at']) < time()) {
        json_response([
            'success' => false,
            'error' => 'Freeze challenge expired. Please create a new challenge.',
            'expires_at' => $challenge['expires_at']
        ], 409);
    }

    $wallet = (string)$challenge['wallet'];
    $tokenId = (string)$challenge['token_id'];

    $ownership = load_exact_verified_genesis_ownership($pdo, $userId, $wallet, $tokenId);

    if (!$ownership) {
        json_response([
            'success' => false,
            'error' => 'Verified Genesis ownership no longer matches this challenge.'
        ], 409);
    }

    if (create_genesis_nft_has_active_stake($pdo, $tokenId)) {
        json_response([
            'success' => false,
            'error' => 'This Genesis NFT is already actively frozen.'
        ], 409);
    }

    $genesisCount = genesis_nft_staking_count_verified_collection($pdo, $userId, GENESIS_NFT_COLLECTION_KEY);
    $vipCount = genesis_nft_staking_count_verified_collection($pdo, $userId, VIP_NFT_COLLECTION_KEY);
    $hasVip = $vipCount > 0;

    $tier = genesis_nft_staking_get_tier($genesisCount);
    $totalSlots = genesis_nft_staking_calculate_slots($tier, $hasVip);
    $usedSlots = count_create_genesis_nft_active_stakes($pdo, $userId);

    if ($totalSlots <= 0) {
        json_response([
            'success' => false,
            'error' => 'No Genesis Mouse Freezer slots available for this account.'
        ], 403);
    }

    if ($usedSlots >= $totalSlots) {
        json_response([
            'success' => false,
            'error' => 'All Genesis Mouse Freezer slots are already used.',
            'slots' => [
                'used' => $usedSlots,
                'total' => $totalSlots,
                'available' => 0
            ]
        ], 409);
    }

    $memoSignature = validate_freeze_activation_proof($requestData, $challenge);
    $dailyReward = genesis_nft_staking_int($tier['daily_reward'] ?? 0);
    $metadata = build_genesis_nft_freeze_metadata($challenge, $ownership, $tier, $hasVip);

    $pdo->beginTransaction();

    $insertStakeStmt = $pdo->prepare("
        INSERT INTO tbl_genesis_nft_stakes (
            user_id,
            username,
            wallet,
            token_id,
            collection,
            nft_name,
            image_url,
            status,
            frozen_at,
            last_claimed_at,
            total_claimed,
            genesis_count_at_freeze,
            genesis_tier_at_freeze,
            daily_reward_at_freeze,
            freezer_slots_at_freeze,
            vip_verified_at_freeze,
            vip_bonus_percent_at_freeze,
            freeze_challenge_id,
            freeze_memo_signature,
            ownership_check_status,
            ownership_checked_at,
            metadata,
            created_at,
            updated_at
        )
        VALUES (
            :user_id,
            :username,
            :wallet,
            :token_id,
            :collection,
            :nft_name,
            :image_url,
            'active',
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP,
            0,
            :genesis_count_at_freeze,
            :genesis_tier_at_freeze,
            :daily_reward_at_freeze,
            :freezer_slots_at_freeze,
            :vip_verified_at_freeze,
            :vip_bonus_percent_at_freeze,
            :freeze_challenge_id,
            :freeze_memo_signature,
            'verified',
            CURRENT_TIMESTAMP,
            :metadata,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        )
    ");

    $insertStakeStmt->execute([
    ':user_id' => $userId,
    ':username' => $ownership['username'] ?? null,
    ':wallet' => $wallet,
    ':token_id' => $tokenId,
    ':collection' => GENESIS_NFT_COLLECTION_KEY,
    ':nft_name' => $ownership['nft_name'] ?? null,
    ':image_url' => $ownership['image_url'] ?? null,
    ':genesis_count_at_freeze' => $genesisCount,
    ':genesis_tier_at_freeze' => $tier['label'] ?? '',
    ':daily_reward_at_freeze' => $dailyReward,
    ':freezer_slots_at_freeze' => $totalSlots,
    ':vip_verified_at_freeze' => $hasVip ? 1 : 0,
    ':vip_bonus_percent_at_freeze' => $hasVip ? GENESIS_NFT_VIP_BONUS_PERCENT : 0,
    ':freeze_challenge_id' => $challengeId,
    ':freeze_memo_signature' => $memoSignature,
    ':metadata' => $metadata
]);

    $stakeId = (int)$pdo->lastInsertId();

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
            'status' => 'active',
            'wallet' => $wallet,
            'token_id' => $tokenId,
            'collection' => GENESIS_NFT_COLLECTION_KEY,
            'nft_name' => $ownership['nft_name'] ?? null,
            'image_url' => $ownership['image_url'] ?? null,
            'frozen_at' => date('Y-m-d H:i:s'),
            'last_claimed_at' => date('Y-m-d H:i:s'),
            'daily_reward' => $dailyReward,
            'tier' => $tier,
            'slots' => [
                'used_before' => $usedSlots,
                'used_after' => $usedSlots + 1,
                'total' => $totalSlots,
                'available_after' => max(0, $totalSlots - ($usedSlots + 1))
            ],
            'ownership_check_status' => 'verified',
            'system_status' => [
                'phase' => 'phase_3_local_freeze_created',
                'local_only_activation' => is_localhost_request(),
                'writes_rewards' => false,
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
        'error' => 'Genesis NFT stake creation failed',
        'details' => $e->getMessage()
    ], 500);
}
