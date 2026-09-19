<?php
/**
 * Read-only Profile Score History API.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint combines real score-adjustment records with completed Season 13
 * V2 stake lifecycle events for member presentation. A principal-unlock event
 * explains that an active-frozen subtraction ended; it is not a DSPOINC credit,
 * does not create a ledger row, and must never change staking state or balances.
 */

session_start();

header('Content-Type: application/json');

$isLocalDevelopment = ($_SERVER['HTTP_HOST'] ?? '') === 'localhost'
    || ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($isLocalDevelopment && (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} elseif ($isLocalDevelopment) {
    header('Access-Control-Allow-Origin: http://localhost:5173');
} else {
    header('Access-Control-Allow-Origin: https://narrrfs.world');
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Return request data from JSON, POST, or GET so Profile can keep its existing
 * POST-first and GET-fallback behavior.
 */
function profile_score_history_request_data(): array
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST) && is_array($_POST)) {
            return $_POST;
        }

        $decoded = json_decode(file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_SERVER['REQUEST_METHOD'] === 'GET' && is_array($_GET) ? $_GET : [];
}

/**
 * Keep pagination bounded before using it to slice presentation-only events.
 */
function profile_score_history_bounded_int(array $requestData, string $key, int $default, int $min, int $max): int
{
    $value = (int)($requestData[$key] ?? $default);
    return $value < $min ? $default : min($value, $max);
}

$requestData = profile_score_history_request_data();
$requestUserId = trim((string)($requestData['user_id'] ?? ''));
$userId = $_SESSION['discord_id'] ?? '';

// Preserve the existing Profile history authorization boundary: only localhost
// may select a request user; production always relies on the session identity.
if ($isLocalDevelopment && $requestUserId !== '') {
    $userId = $requestUserId;
} elseif ($requestUserId !== '' && $userId !== '' && $requestUserId !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: user_id mismatch']);
    exit;
}

if (!$userId && $isLocalDevelopment) {
    $userId = '328601656659017732';
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$limit = profile_score_history_bounded_int($requestData, 'limit', 20, 1, 100);
$offset = max(0, (int)($requestData['offset'] ?? 0));
$dbPath = $isLocalDevelopment
    ? __DIR__ . '/../../db/narrrf_world.sqlite'
    : '/var/www/html/db/narrrf_world.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // SELECT-only: real adjustment records retain their existing ledger meaning.
    $adjustmentStmt = $db->prepare(
        'SELECT id, admin_id, amount, action, reason, timestamp
         FROM tbl_score_adjustments
         WHERE user_id = ?
         ORDER BY timestamp DESC'
    );
    $adjustmentStmt->execute([$userId]);
    $adjustments = $adjustmentStmt->fetchAll();
    $claimAuditsByStake = [];
    foreach ($adjustments as $adjustment) {
        $reason = (string)($adjustment['reason'] ?? '');
        if ((string)($adjustment['admin_id'] ?? '') !== 'system-staking'
            || (string)($adjustment['action'] ?? '') !== 'add'
            || strpos($reason, 'Stake reward claimed') !== 0
            || !preg_match('/\\(stake_id:\\s*(\\d+)\\s*\\)$/', $reason, $match)) {
            continue;
        }

        $claimAuditsByStake[(int)$match[1]][] = $adjustment;
    }

    $events = array_map(static function (array $adjustment): array {
        $adjustment['event_type'] = 'score_adjustment';
        return $adjustment;
    }, $adjustments);

    // SELECT-only: completed V2 stakes explain principal availability without
    // writing a credit, audit row, status transition, or any staking mutation.
    $unlockStmt = $db->prepare(
        "SELECT id, amount, completed_at
         FROM tbl_dspoinc_stakes
         WHERE user_id = ?
           AND staking_contract_version = 'season13_v2'
           AND status = 'completed'
           AND completed_at IS NOT NULL
         ORDER BY completed_at DESC"
    );
    $unlockStmt->execute([$userId]);

    foreach ($unlockStmt->fetchAll() as $stake) {
        $stakeId = (int)$stake['id'];
        $matchingClaims = $claimAuditsByStake[$stakeId] ?? [];
        $hasExactClaim = count($matchingClaims) === 1;
        $completedAt = (string)$stake['completed_at'];
        $claimTimestamp = $hasExactClaim ? (string)$matchingClaims[0]['timestamp'] : null;

        $events[] = [
            'event_type' => 'stake_principal_unlocked',
            'stake_id' => $stakeId,
            'principal_amount' => (int)$stake['amount'],
            'completed_at' => $completedAt,
            'claim_timestamp' => $claimTimestamp,
            'timestamp' => $hasExactClaim ? $claimTimestamp : $completedAt,
            'event_priority' => 0,
        ];
    }

    usort($events, static function (array $left, array $right): int {
        $timestampComparison = strcmp((string)($right['timestamp'] ?? ''), (string)($left['timestamp'] ?? ''));
        if ($timestampComparison !== 0) {
            return $timestampComparison;
        }

        $priorityComparison = ((int)($left['event_priority'] ?? 1)) <=> ((int)($right['event_priority'] ?? 1));
        if ($priorityComparison !== 0) {
            return $priorityComparison;
        }

        return ((int)($left['id'] ?? $left['stake_id'] ?? 0)) <=> ((int)($right['id'] ?? $right['stake_id'] ?? 0));
    });

    $totalEvents = count($events);
    $currentPage = (int)floor($offset / $limit) + 1;

    echo json_encode([
        'success' => true,
        'adjustments' => array_slice($events, $offset, $limit),
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => $totalEvents,
            'has_more' => ($offset + $limit) < $totalEvents,
            'current_page' => $currentPage,
            'total_pages' => max(1, (int)ceil($totalEvents / $limit)),
        ],
    ]);
} catch (Exception $exception) {
    error_log('Profile score history error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch profile score history']);
}
