<?php
// 🧀 Admin DSPOINC Overview API
// Canonical economy overview for admin dashboard
// Uses the same ledger model as profile staking stats:
// total supply = SUM(tbl_user_scores.score)
// frozen balance = SUM(tbl_dspoinc_stakes.amount WHERE status='active')
// available balance = total - frozen

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

/**
 * Returns a single scalar value from a prepared PDO query.
 */
function fetchSingleValue(PDO $pdo, string $sql, array $params = [], $default = 0) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();

    return ($value !== false && $value !== null) ? $value : $default;
}

/**
 * Returns all rows from a prepared PDO query.
 */
function fetchAllRows(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

    try {
    // Database path strategy:
    // - production often uses /data/narrrf_world.sqlite
    // - local usually uses ../../db/narrrf_world.sqlite
    $candidatePaths = [
    __DIR__ . '/../../db/narrrf_world.sqlite', // live / primary database
    '/data/narrrf_world.sqlite'                // backup copy fallback
    ];

    $dbPath = null;
    foreach ($candidatePaths as $candidatePath) {
        if (file_exists($candidatePath)) {
            $dbPath = $candidatePath;
            break;
        }
    }

    if (!$dbPath) {
        throw new Exception('Database file not found in expected locations');
    }

    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1) Canonical total supply from ledger
    $totalSupply = (int) fetchSingleValue(
        $pdo,
        "SELECT COALESCE(SUM(score), 0) FROM tbl_user_scores"
    );

    // 2) Frozen balance from active stakes
    $frozenBalance = (int) fetchSingleValue(
        $pdo,
        "SELECT COALESCE(SUM(amount), 0)
         FROM tbl_dspoinc_stakes
         WHERE status = 'active'"
    );

    $availableBalance = $totalSupply - $frozenBalance;

    // 3) Active stakes count
    $activeStakes = (int) fetchSingleValue(
        $pdo,
        "SELECT COUNT(*)
         FROM tbl_dspoinc_stakes
         WHERE status = 'active'"
    );

    // 4) Pending rewards
    $pendingRewards = (int) fetchSingleValue(
        $pdo,
        "SELECT COALESCE(SUM(
            CASE
                WHEN status = 'active'
                THEN COALESCE(expected_reward, 0) - COALESCE(reward_paid, 0)
                ELSE 0
            END
         ), 0)
         FROM tbl_dspoinc_stakes"
    );

    // 5) Holder balances
$holderRows = fetchAllRows(
    $pdo,
    "SELECT
        s.user_id,
        u.discord_id AS matched_discord_id,
        u.username AS matched_username,
        COALESCE(SUM(s.score), 0) AS balance
     FROM tbl_user_scores s
     LEFT JOIN tbl_users u
       ON TRIM(CAST(u.discord_id AS TEXT)) = TRIM(CAST(s.user_id AS TEXT))
     GROUP BY s.user_id, u.discord_id, u.username
     HAVING balance > 0
     ORDER BY balance DESC"
);

$fundedUsers = count($holderRows);

$averageBalance = 0;
if ($fundedUsers > 0) {
    $sumBalances = 0;
    foreach ($holderRows as $row) {
        $sumBalances += (int) $row['balance'];
    }
    $averageBalance = (int) round($sumBalances / $fundedUsers);
}

$topHolders = [];
foreach (array_slice($holderRows, 0, 10) as $row) {
    $resolvedUsername = $row['matched_username'];

    if ($resolvedUsername === null || trim($resolvedUsername) === '') {
        $resolvedUsername = 'Unknown User';
    }

    $topHolders[] = [
        'user_id' => $row['user_id'],
        'matched_discord_id' => $row['matched_discord_id'],
        'username' => $resolvedUsername,
        'balance' => (int) $row['balance']
    ];
}

    $topHolderBalance = $topHolders[0]['balance'] ?? 0;

    // 6) Ledger flow windows
    $entries24h = (int) fetchSingleValue(
        $pdo,
        "SELECT COUNT(*)
         FROM tbl_user_scores
         WHERE timestamp >= datetime('now', '-24 hours')"
    );

    $entries7d = (int) fetchSingleValue(
        $pdo,
        "SELECT COUNT(*)
         FROM tbl_user_scores
         WHERE timestamp >= datetime('now', '-7 days')"
    );

    $positiveFlow24h = (int) fetchSingleValue(
        $pdo,
        "SELECT COALESCE(SUM(score), 0)
         FROM tbl_user_scores
         WHERE score > 0
           AND timestamp >= datetime('now', '-24 hours')"
    );

    $negativeFlow24hRaw = (int) fetchSingleValue(
        $pdo,
        "SELECT COALESCE(SUM(score), 0)
         FROM tbl_user_scores
         WHERE score < 0
           AND timestamp >= datetime('now', '-24 hours')"
    );
    $negativeFlow24h = abs($negativeFlow24hRaw);

    // 7) Current active season
    $currentSeason = fetchSingleValue(
        $pdo,
        "SELECT season_name
         FROM tbl_seasons
         WHERE is_active = 1
         ORDER BY season_id DESC
         LIMIT 1",
        [],
        'unknown'
    );

    // 8) Staked ratio
    $stakedRatio = $totalSupply > 0
        ? round(($frozenBalance / $totalSupply) * 100, 2)
        : 0;

    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'database_path' => $dbPath,
            'total_supply' => $totalSupply,
            'available_balance' => $availableBalance,
            'frozen_balance' => $frozenBalance,
            'active_stakes' => $activeStakes,
            'pending_rewards' => $pendingRewards,
            'average_balance' => $averageBalance,
            'funded_users' => $fundedUsers,
            'top_holder_balance' => $topHolderBalance,
            'entries_24h' => $entries24h,
            'entries_7d' => $entries7d,
            'positive_flow_24h' => $positiveFlow24h,
            'negative_flow_24h' => $negativeFlow24h,
            'staked_ratio' => $stakedRatio,
            'current_season' => $currentSeason,
            'top_holders' => $topHolders
        ]
    ]);
} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load DSPOINC overview',
        'details' => $e->getMessage(),
        'trace_hint' => 'Check database path, table names, and PDO SQLite availability'
    ]);
}
?>