<?php
/**
 * Get Genesis NFT Stakes API.
 *
 * Plain language for DEVS:
 * This is the first read-only API for the Genesis Mouse Freezer.
 * It does not freeze NFTs.
 * It does not claim DSPOINC.
 * It does not unfreeze NFTs.
 * It does not write to the database.
 *
 * It only returns:
 * - current verified Genesis count
 * - current staking tier
 * - VIP bonus state
 * - active frozen NFTs
 * - available verified Genesis NFTs
 * - read-only claim preview math
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
function json_response($payload, $code = 200): void
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
function get_genesis_nft_stakes_request_data(): array
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

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732';
$requestData = get_genesis_nft_stakes_request_data();

$sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
$requestUserId = trim((string)($requestData['user_id'] ?? ''));
$isLocalhost = is_localhost_request();

if ($isLocalhost && $requestUserId !== '') {
    $userId = $requestUserId;
} else {
    $userId = $sessionUserId;

    if ($requestUserId !== '' && $requestUserId !== $sessionUserId) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }
}

if ($userId === '' && $isLocalhost) {
    $userId = $LOCAL_TEST_DISCORD_ID;
}

if ($userId === '') {
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

try {
    $genesisCount = genesis_nft_staking_count_verified_collection($pdo, $userId, GENESIS_NFT_COLLECTION_KEY);
    $vipCount = genesis_nft_staking_count_verified_collection($pdo, $userId, VIP_NFT_COLLECTION_KEY);
    $hasVip = $vipCount > 0;

    $tier = genesis_nft_staking_get_tier($genesisCount);
    $totalSlots = genesis_nft_staking_calculate_slots($tier, $hasVip);

    $activeStakes = genesis_nft_staking_load_active_stakes($pdo, $userId);
    $availableNfts = genesis_nft_staking_load_available_genesis($pdo, $userId);

    $frozenNfts = [];
    $claimableTotal = 0;
    $earningPerDay = 0;
    $activeIndex = 0;

    foreach ($activeStakes as $stake) {
        $activeIndex++;

        $claimPreview = genesis_nft_staking_calculate_claim_preview(
            $stake,
            $tier,
            $hasVip,
            $activeIndex
        );

        $claimableTotal += (int)$claimPreview['final_reward_amount'];
        $earningPerDay += (int)$claimPreview['daily_reward'];

        $frozenNfts[] = [
            'stake_id' => (int)$stake['id'],
            'token_id' => $stake['token_id'],
            'collection' => $stake['collection'],
            'wallet' => $stake['wallet'],
            'nft_name' => $stake['nft_name'],
            'image_url' => $stake['image_url'],
            'status' => $stake['status'],
            'frozen_at' => $stake['frozen_at'],
            'last_claimed_at' => $stake['last_claimed_at'],
            'total_claimed' => (int)$stake['total_claimed'],
            'genesis_count_at_freeze' => (int)$stake['genesis_count_at_freeze'],
            'genesis_tier_at_freeze' => $stake['genesis_tier_at_freeze'],
            'daily_reward_at_freeze' => (int)$stake['daily_reward_at_freeze'],
            'freezer_slots_at_freeze' => (int)$stake['freezer_slots_at_freeze'],
            'vip_verified_at_freeze' => (int)$stake['vip_verified_at_freeze'],
            'vip_bonus_percent_at_freeze' => (float)$stake['vip_bonus_percent_at_freeze'],
            'ownership_check_status' => $stake['ownership_check_status'],
            'ownership_checked_at' => $stake['ownership_checked_at'],
            'claim_preview' => $claimPreview
        ];
    }

    $slotUsage = [
        'used' => count($frozenNfts),
        'total' => $totalSlots,
        'available' => max(0, $totalSlots - count($frozenNfts)),
        'has_capacity' => count($frozenNfts) < $totalSlots
    ];

    json_response([
        'success' => true,
        'data' => [
            'system_status' => [
                'phase' => 'phase_1_read_only',
                'is_active' => false,
                'message' => 'Genesis Mouse Freezer read-only API is available. Freeze, claim, and unfreeze are not active yet.'
            ],
            'user' => [
                'user_id' => $userId
            ],
            'ownership' => [
                'verified_genesis_count' => $genesisCount,
                'verified_vip_count' => $vipCount,
                'has_vip_bonus' => $hasVip
            ],
            'tier' => $tier,
            'vip_bonus' => [
                'enabled' => $hasVip,
                'bonus_percent' => $hasVip ? GENESIS_NFT_VIP_BONUS_PERCENT : 0,
                'bonus_cap_frozen_nfts' => GENESIS_NFT_VIP_BONUS_CAP_FROZEN_NFTS,
                'extra_slots' => $hasVip ? GENESIS_NFT_VIP_EXTRA_SLOTS : 0
            ],
            'slots' => $slotUsage,
            'summary' => [
                'frozen_count' => count($frozenNfts),
                'available_to_freeze_count' => count($availableNfts),
                'earning_per_day_preview' => $earningPerDay,
                'claimable_preview' => $claimableTotal
            ],
            'frozen_nfts' => $frozenNfts,
            'available_nfts' => $availableNfts,
            'rules' => [
                'full_days_only' => true,
                'ownership_check_required_for_future_claims' => true,
                'frontend_is_not_reward_authority' => true,
                'writes_enabled' => false
            ]
        ]
    ]);
} catch (Exception $e) {
    json_response([
        'success' => false,
        'error' => 'Genesis NFT staking read-only load failed',
        'details' => $e->getMessage()
    ], 500);
}