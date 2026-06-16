<?php
/**
 * Claim Genesis NFT Stake Reward API.
 *
 * Plain language for DEVS:
 * This endpoint claims DSPOINC from one active Genesis Mouse Freezer row.
 * It is separate from DSPOINC Staking V2 and must never touch tbl_dspoinc_stakes.
 *
 * Claim rules:
 * - Stake must belong to the logged-in Discord user.
 * - Stake must be active.
 * - NFT ownership must still match the same user + wallet + token.
 * - Rewards use full days only.
 * - Backend recalculates current Genesis tier and VIP bonus at claim time.
 * - One claim row and one DSPOINC ledger/audit pair are written in one DB transaction.
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
function get_claim_genesis_nft_stake_request_data(): array
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
function resolve_claim_genesis_nft_stake_user_id(array $requestData): string
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
function load_claimable_genesis_nft_stake(PDO $pdo, int $stakeId, string $userId): ?array
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
 * Confirm the NFT is still verified for the exact same user + wallet + token.
 *
 * Plain language for DEVS:
 * This is the ownership proof for claims.
 * Memo TX is not required for claims, but ownership check is mandatory.
 */
function verify_claim_genesis_nft_ownership(PDO $pdo, array $stake): ?array
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
 * Return this stake's active index for VIP bonus cap logic.
 *
 * Plain language for DEVS:
 * VIP bonus is capped to the first 10 active frozen NFTs.
 * The order is stable by frozen_at then id.
 */
function get_claim_genesis_nft_active_index(PDO $pdo, string $userId, int $stakeId): int
{
    $activeStakes = genesis_nft_staking_load_active_stakes($pdo, $userId);
    $activeIndex = 0;

    foreach ($activeStakes as $activeStake) {
        $activeIndex++;

        if ((int)$activeStake['id'] === $stakeId) {
            return $activeIndex;
        }
    }

    return 999999;
}

/**
 * Load the active season name for ledger rows.
 */
function get_claim_genesis_nft_current_season(PDO $pdo): string
{
    try {
        $stmt = $pdo->prepare("SELECT season_name FROM tbl_seasons WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($row['season_name'])) {
            return (string)$row['season_name'];
        }
    } catch (Exception $e) {
        // Fall through to safe default.
    }

    return 'Season 12';
}

/**
 * Pause an active stake when ownership is no longer valid.
 *
 * Plain language for DEVS:
 * This does not pay DSPOINC. It prevents ongoing claims until the user
 * re-verifies ownership or support/admin resolves the state.
 */
function pause_genesis_nft_stake_for_lost_ownership(PDO $pdo, int $stakeId): void
{
    $stmt = $pdo->prepare("
        UPDATE tbl_genesis_nft_stakes
        SET status = 'needs_reverify',
            ownership_check_status = 'ownership_lost',
            ownership_checked_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
          AND status = 'active'
    ");
    $stmt->execute([$stakeId]);
}

/**
 * Build metadata for the claim audit row.
 */
function build_genesis_nft_claim_metadata(array $stake, array $ownership, int $activeIndex): string
{
    return json_encode([
        'source' => 'genesis_nft_staking',
        'stake_id' => (int)$stake['id'],
        'active_stake_index' => $activeIndex,
        'ownership_id' => $ownership['ownership_id'] ?? null,
        'ownership_verified_at' => $ownership['verified_at'] ?? null,
        'full_days_only' => true,
        'memo_required_for_claim' => false
    ]);
}

session_start();

$requestData = get_claim_genesis_nft_stake_request_data();
$userId = resolve_claim_genesis_nft_stake_user_id($requestData);

if ($userId === '') {
    json_response([
        'success' => false,
        'error' => 'Not logged in'
    ], 401);
}

$stakeId = isset($requestData['stake_id']) ? (int)$requestData['stake_id'] : 0;

if ($stakeId <= 0) {
    json_response([
        'success' => false,
        'error' => 'stake_id is required'
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
    $stake = load_claimable_genesis_nft_stake($pdo, $stakeId, $userId);

    if (!$stake) {
        json_response([
            'success' => false,
            'error' => 'Active Genesis NFT stake not found for this user.'
        ], 404);
    }

    $ownership = verify_claim_genesis_nft_ownership($pdo, $stake);

    if (!$ownership) {
        $pdo->beginTransaction();
        pause_genesis_nft_stake_for_lost_ownership($pdo, $stakeId);
        $pdo->commit();

        json_response([
            'success' => false,
            'error' => 'Ownership changed. Rewards are paused. Reverify wallet ownership or unfreeze this slot.',
            'ownership_check_status' => 'ownership_lost'
        ], 409);
    }

    $claimFrom = $stake['last_claimed_at'] ?: $stake['frozen_at'];
    $claimTo = date('Y-m-d H:i:s');
    $fullDays = genesis_nft_staking_calculate_full_days($claimFrom, $claimTo);

    if ($fullDays <= 0) {
        json_response([
            'success' => false,
            'error' => 'No full claimable day is available yet.',
            'claim_preview' => [
                'claim_from' => $claimFrom,
                'claim_to' => $claimTo,
                'full_days' => 0,
                'minimum_full_days_required' => 1
            ]
        ], 409);
    }

    $genesisCount = genesis_nft_staking_count_verified_collection($pdo, $userId, GENESIS_NFT_COLLECTION_KEY);
    $vipCount = genesis_nft_staking_count_verified_collection($pdo, $userId, VIP_NFT_COLLECTION_KEY);
    $hasVip = $vipCount > 0;

    $tier = genesis_nft_staking_get_tier($genesisCount);
    $dailyReward = genesis_nft_staking_int($tier['daily_reward'] ?? 0);

    if ($dailyReward <= 0) {
        json_response([
            'success' => false,
            'error' => 'Current Genesis tier does not allow NFT staking rewards.'
        ], 403);
    }

    $activeIndex = get_claim_genesis_nft_active_index($pdo, $userId, $stakeId);
    $baseRewardAmount = $fullDays * $dailyReward;

    $vipBonusPercent = 0.0;
    $vipBonusAmount = 0;

    if ($hasVip && $activeIndex <= GENESIS_NFT_VIP_BONUS_CAP_FROZEN_NFTS) {
        $vipBonusPercent = GENESIS_NFT_VIP_BONUS_PERCENT;
        $vipBonusAmount = (int)floor($baseRewardAmount * ($vipBonusPercent / 100));
    }

    $finalRewardAmount = $baseRewardAmount + $vipBonusAmount;

    if ($finalRewardAmount <= 0) {
        json_response([
            'success' => false,
            'error' => 'Calculated reward is zero.'
        ], 409);
    }

    $currentSeason = get_claim_genesis_nft_current_season($pdo);
    $claimMetadata = build_genesis_nft_claim_metadata($stake, $ownership, $activeIndex);

    $pdo->beginTransaction();

    $scoreStmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (
            user_id,
            game,
            score,
            timestamp,
            source,
            game_type,
            season,
            created_at
        )
        VALUES (
            :user_id,
            'genesis_nft_staking',
            :score,
            CURRENT_TIMESTAMP,
            'Genesis Mouse Freezer',
            NULL,
            :season,
            CURRENT_TIMESTAMP
        )
    ");
    $scoreStmt->execute([
        ':user_id' => $userId,
        ':score' => $finalRewardAmount,
        ':season' => $currentSeason
    ]);

    $adjustmentStmt = $pdo->prepare("
        INSERT INTO tbl_score_adjustments (
            user_id,
            admin_id,
            amount,
            action,
            reason,
            timestamp,
            quest_id
        )
        VALUES (
            :user_id,
            'system',
            :amount,
            'add',
            :reason,
            CURRENT_TIMESTAMP,
            NULL
        )
    ");
    $adjustmentStmt->execute([
        ':user_id' => $userId,
        ':amount' => $finalRewardAmount,
        ':reason' => "Genesis Mouse Freezer claim: {$finalRewardAmount} DSPOINC for {$fullDays} full day(s)"
    ]);

    $ledgerAdjustmentId = (int)$pdo->lastInsertId();

    $claimStmt = $pdo->prepare("
        INSERT INTO tbl_genesis_nft_stake_claims (
            stake_id,
            user_id,
            wallet,
            token_id,
            collection,
            claim_from,
            claim_to,
            full_days,
            daily_reward,
            base_reward_amount,
            genesis_count_at_claim,
            genesis_tier_at_claim,
            vip_verified_at_claim,
            vip_bonus_percent_at_claim,
            vip_bonus_amount,
            final_reward_amount,
            ownership_check_status,
            ownership_checked_at,
            ledger_adjustment_id,
            metadata,
            claimed_at
        )
        VALUES (
            :stake_id,
            :user_id,
            :wallet,
            :token_id,
            :collection,
            :claim_from,
            :claim_to,
            :full_days,
            :daily_reward,
            :base_reward_amount,
            :genesis_count_at_claim,
            :genesis_tier_at_claim,
            :vip_verified_at_claim,
            :vip_bonus_percent_at_claim,
            :vip_bonus_amount,
            :final_reward_amount,
            'verified',
            CURRENT_TIMESTAMP,
            :ledger_adjustment_id,
            :metadata,
            CURRENT_TIMESTAMP
        )
    ");
    $claimStmt->execute([
        ':stake_id' => $stakeId,
        ':user_id' => $userId,
        ':wallet' => $stake['wallet'],
        ':token_id' => $stake['token_id'],
        ':collection' => GENESIS_NFT_COLLECTION_KEY,
        ':claim_from' => $claimFrom,
        ':claim_to' => $claimTo,
        ':full_days' => $fullDays,
        ':daily_reward' => $dailyReward,
        ':base_reward_amount' => $baseRewardAmount,
        ':genesis_count_at_claim' => $genesisCount,
        ':genesis_tier_at_claim' => $tier['label'] ?? '',
        ':vip_verified_at_claim' => $hasVip ? 1 : 0,
        ':vip_bonus_percent_at_claim' => $vipBonusPercent,
        ':vip_bonus_amount' => $vipBonusAmount,
        ':final_reward_amount' => $finalRewardAmount,
        ':ledger_adjustment_id' => $ledgerAdjustmentId,
        ':metadata' => $claimMetadata
    ]);

    $claimId = (int)$pdo->lastInsertId();

    $updateStakeStmt = $pdo->prepare("
        UPDATE tbl_genesis_nft_stakes
        SET last_claimed_at = :claim_to,
            total_claimed = total_claimed + :final_reward_amount,
            ownership_check_status = 'verified',
            ownership_checked_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :stake_id
          AND user_id = :user_id
          AND status = 'active'
    ");
    $updateStakeStmt->execute([
        ':claim_to' => $claimTo,
        ':final_reward_amount' => $finalRewardAmount,
        ':stake_id' => $stakeId,
        ':user_id' => $userId
    ]);

    $pdo->commit();

    json_response([
        'success' => true,
        'data' => [
            'claim_id' => $claimId,
            'stake_id' => $stakeId,
            'ledger_adjustment_id' => $ledgerAdjustmentId,
            'wallet' => $stake['wallet'],
            'token_id' => $stake['token_id'],
            'collection' => GENESIS_NFT_COLLECTION_KEY,
            'claim_from' => $claimFrom,
            'claim_to' => $claimTo,
            'full_days' => $fullDays,
            'daily_reward' => $dailyReward,
            'base_reward_amount' => $baseRewardAmount,
            'vip_bonus_percent' => $vipBonusPercent,
            'vip_bonus_amount' => $vipBonusAmount,
            'final_reward_amount' => $finalRewardAmount,
            'genesis_count_at_claim' => $genesisCount,
            'genesis_tier_at_claim' => $tier['label'] ?? '',
            'ownership_check_status' => 'verified',
            'system_status' => [
                'phase' => 'phase_4_local_claim_created',
                'writes_rewards' => true,
                'touches_dspoinc_stakes' => false,
                'frontend_reward_authority' => false
            ]
        ]
    ]);
} catch (Exception $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'success' => false,
        'error' => 'Genesis NFT stake claim failed',
        'details' => $e->getMessage()
    ], 500);
}
