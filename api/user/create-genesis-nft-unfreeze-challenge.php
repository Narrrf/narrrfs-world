<?php
/**
 * Create Genesis NFT Unfreeze Challenge API.
 *
 * Plain language for DEVS:
 * This endpoint creates a short-lived pending unfreeze challenge for one active
 * Genesis Mouse Freezer stake.
 *
 * It does NOT unfreeze the NFT.
 * It does NOT close the stake row.
 * It does NOT pay DSPOINC.
 * It does NOT touch tbl_dspoinc_stakes.
 *
 * The next endpoint will verify the user's Solana Memo signature/transaction
 * and then close the active freezer row.
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

const GENESIS_NFT_UNFREEZE_CHALLENGE_TTL_MINUTES = 15;

/**
 * Controlled live tester allowlist for Genesis Mouse Freezer.
 *
 * Plain language for DEVS:
 * The wallet Memo production path is ready for controlled testing, but this is
 * not the public Season 13 NFT staking launch yet. Localhost remains open for
 * development. Production challenge creation is limited to Narrrf and justme
 * until the live test is proven safe.
 */
const GENESIS_FREEZER_CONTROLLED_LIVE_TEST_USER_IDS = [
    '328601656659017732', // Narrrf
    '1224428436928594015', // justme
];

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
function get_unfreeze_challenge_request_data(): array
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
function resolve_unfreeze_challenge_user_id(array $requestData): string
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
 * Block non-allowlisted production users during the controlled live test.
 *
 * Plain language for DEVS:
 * This gate only affects production. It does not block localhost curl/browser
 * testing. It prevents accidental public NFT staking access while we verify
 * real wallet Memo transactions with approved testers.
 */
function enforce_genesis_freezer_controlled_live_test_gate(string $userId): void
{
    if (is_localhost_request()) {
        return;
    }

    if (in_array($userId, GENESIS_FREEZER_CONTROLLED_LIVE_TEST_USER_IDS, true)) {
        return;
    }

    json_response([
        'success' => false,
        'error' => 'Genesis Mouse Freezer is in controlled live testing. Public access opens with the Season 13 staking rollout.',
        'system_status' => [
            'phase' => 'controlled_live_test_only',
            'allowed_testers' => ['Narrrf', 'justme'],
            'public_activation' => false
        ]
    ], 403);
}

/**
 * Load one active freezer stake for the user.
 */
function load_active_genesis_nft_stake_for_unfreeze(PDO $pdo, int $stakeId, string $userId): ?array
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
 * Ownership check is still mandatory before unfreeze.
 * The user should only unfreeze a stake tied to an NFT they still control.
 */
function verify_unfreeze_genesis_nft_ownership(PDO $pdo, array $stake): ?array
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
 * Mark old pending unfreeze challenges for this stake/token as expired.
 *
 * Plain language for DEVS:
 * This prevents old unfreeze memo messages from staying pending forever.
 */
function expire_old_unfreeze_challenges(PDO $pdo, string $userId, string $tokenId): void
{
    $stmt = $pdo->prepare("
        UPDATE tbl_genesis_nft_stake_challenges
        SET status = 'expired'
        WHERE user_id = ?
          AND token_id = ?
          AND collection = ?
          AND action = 'unfreeze'
          AND status = 'pending'
    ");
    $stmt->execute([$userId, $tokenId, GENESIS_NFT_COLLECTION_KEY]);
}

/**
 * Build the Solana Memo message the user will sign/send later.
 */
function build_unfreeze_challenge_message(int $stakeId, string $userId, string $wallet, string $tokenId, string $nonce, string $expiresAt): string
{
    return implode("\n", [
        'Narrrfs World Genesis Mouse Freezer',
        'Action: unfreeze',
        'Stake ID: ' . $stakeId,
        'User ID: ' . $userId,
        'Wallet: ' . $wallet,
        'Collection: genesis',
        'Token ID: ' . $tokenId,
        'Nonce: ' . $nonce,
        'Expires At: ' . $expiresAt,
        'Your NFT stays in your wallet. This memo only confirms freezer exit intent.'
    ]);
}

session_start();

$requestData = get_unfreeze_challenge_request_data();
$userId = resolve_unfreeze_challenge_user_id($requestData);

if ($userId === '') {
    json_response([
        'success' => false,
        'error' => 'Not logged in'
    ], 401);
}

enforce_genesis_freezer_controlled_live_test_gate($userId);

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
    $stake = load_active_genesis_nft_stake_for_unfreeze($pdo, $stakeId, $userId);

    if (!$stake) {
        json_response([
            'success' => false,
            'error' => 'Active Genesis NFT stake not found for this user.'
        ], 404);
    }

    $ownership = verify_unfreeze_genesis_nft_ownership($pdo, $stake);

    if (!$ownership) {
        json_response([
            'success' => false,
            'error' => 'Ownership changed. Unfreeze challenge cannot be created until ownership is reverified.',
            'ownership_check_status' => 'ownership_lost'
        ], 409);
    }

    $nonce = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', time() + (GENESIS_NFT_UNFREEZE_CHALLENGE_TTL_MINUTES * 60));
    $message = build_unfreeze_challenge_message(
        $stakeId,
        $userId,
        $stake['wallet'],
        $stake['token_id'],
        $nonce,
        $expiresAt
    );

    $pdo->beginTransaction();

    expire_old_unfreeze_challenges($pdo, $userId, $stake['token_id']);

    $insertStmt = $pdo->prepare("
        INSERT INTO tbl_genesis_nft_stake_challenges (
            user_id,
            wallet,
            token_id,
            collection,
            action,
            nonce,
            message,
            status,
            expires_at,
            created_at
        )
        VALUES (
            :user_id,
            :wallet,
            :token_id,
            :collection,
            'unfreeze',
            :nonce,
            :message,
            'pending',
            :expires_at,
            CURRENT_TIMESTAMP
        )
    ");

    $insertStmt->execute([
        ':user_id' => $userId,
        ':wallet' => $stake['wallet'],
        ':token_id' => $stake['token_id'],
        ':collection' => GENESIS_NFT_COLLECTION_KEY,
        ':nonce' => $nonce,
        ':message' => $message,
        ':expires_at' => $expiresAt
    ]);

    $challengeId = (int)$pdo->lastInsertId();

    $pdo->commit();

    json_response([
        'success' => true,
        'data' => [
            'challenge_id' => $challengeId,
            'stake_id' => $stakeId,
            'action' => 'unfreeze',
            'nonce' => $nonce,
            'message' => $message,
            'expires_at' => $expiresAt,
            'ttl_minutes' => GENESIS_NFT_UNFREEZE_CHALLENGE_TTL_MINUTES,
            'ownership' => [
                'wallet' => $stake['wallet'],
                'token_id' => $stake['token_id'],
                'collection' => $stake['collection'],
                'nft_name' => $stake['nft_name'],
                'image_url' => $stake['image_url'],
                'verified_at' => $ownership['verified_at']
            ],
            'stake' => [
                'stake_id' => (int)$stake['id'],
                'status' => $stake['status'],
                'frozen_at' => $stake['frozen_at'],
                'last_claimed_at' => $stake['last_claimed_at'],
                'total_claimed' => (int)$stake['total_claimed']
            ],
            'system_status' => [
                'phase' => 'phase_5_unfreeze_challenge_only',
                'closes_active_stake' => false,
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
        'error' => 'Unfreeze challenge creation failed',
        'details' => $e->getMessage()
    ], 500);
}
