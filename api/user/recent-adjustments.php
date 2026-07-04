<?php
session_start();

require_once __DIR__ . '/staking-contract-helpers.php';

// 🚨 CRITICAL FIX: Handle OPTIONS preflight requests FIRST (before any other headers)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // CORS preflight request - return allowed methods and headers
    $isLocalDevelopment = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($isLocalDevelopment) {
        // Allow localhost origins for local development
        if (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            header('Access-Control-Allow-Origin: http://localhost:5173');
        }
    } else {
        // Production: only allow narrrfs.world
        header('Access-Control-Allow-Origin: https://narrrfs.world');
    }

    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
    header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// 🐛 BUG #335 FIX: Environment-aware CORS headers (works both locally and in production)
$isLocalDevelopment = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($isLocalDevelopment) {
    // Allow localhost origins for local development
    if (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        header('Access-Control-Allow-Origin: http://localhost:5173');
    }
} else {
    // Production: only allow narrrfs.world
    header('Access-Control-Allow-Origin: https://narrrfs.world');
}

header('Access-Control-Allow-Credentials: true');

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID for local testing

/**
 * Return request payload from JSON, POST, or GET.
 *
 * DEVS FOR DECADES:
 * This endpoint supports POST JSON because profile.html uses POST first.
 * It also supports GET as fallback for local/manual testing.
 */
function get_request_data(): array {
    static $cachedRequestData = null;

    if ($cachedRequestData !== null) {
        return $cachedRequestData;
    }

    $cachedRequestData = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST) && is_array($_POST)) {
            $cachedRequestData = $_POST;
            return $cachedRequestData;
        }

        $rawInput = file_get_contents('php://input');
        $decodedInput = json_decode($rawInput, true);

        if (is_array($decodedInput)) {
            $cachedRequestData = $decodedInput;
            return $cachedRequestData;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $cachedRequestData = is_array($_GET) ? $_GET : [];
        return $cachedRequestData;
    }

    return $cachedRequestData;
}

/**
 * Return a bounded integer request value.
 */
function get_bounded_int(array $requestData, string $key, int $default, int $min, int $max): int {
    $value = (int)($requestData[$key] ?? $default);

    if ($value < $min) {
        return $default;
    }

    if ($value > $max) {
        return $max;
    }

    return $value;
}

$requestData = get_request_data();

// 🐛 BUG #335 FIX: Accept user_id from GET/POST in both local and production
// This ensures Recent Score Changes work in production where session might not be set
$user_id = $_SESSION['discord_id'] ?? '';

// Check if user_id is provided in POST/GET/JSON request data
$request_user_id = trim((string)($requestData['user_id'] ?? ''));

// SECURITY: Only allow request user_id override on localhost
if ($isLocalDevelopment && $request_user_id !== '') {
    $user_id = $request_user_id;
    error_log("📊 Recent adjustments: Using user_id from localhost request: " . substr($user_id, 0, 10) . "...");
} else {
    $user_id = $_SESSION['discord_id'] ?? '';

    if ($request_user_id !== '' && $user_id !== '' && $request_user_id !== $user_id) {
        error_log("🚨 SECURITY: Recent adjustments - user_id mismatch. Session: {$user_id}, Request: {$request_user_id}");
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ]);
        exit;
    }

    if ($user_id !== '') {
        error_log("📊 Recent adjustments: Using user_id from session: " . substr($user_id, 0, 10) . "...");
    }
}

// For local development, use Narrrf's account if no session exists
if (!$user_id && $isLocalDevelopment) {
    // If empty or legacy LOCAL_TEST_DISCORD, use Narrrf's account
    if ($user_id === 'LOCAL_TEST_DISCORD' || $user_id === '') {
        $user_id = $LOCAL_TEST_DISCORD_ID;
        error_log("📊 Recent adjustments: Using local test user ID");
    }
}

if (!$user_id) {
    error_log("❌ Recent adjustments: No user_id found (session: " . ($_SESSION['discord_id'] ?? 'none') . ", GET: " . ($_GET['user_id'] ?? 'none') . ", POST/JSON: " . ($requestData['user_id'] ?? 'none') . ")");
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Not logged in',
        'debug' => [
            'has_session' => isset($_SESSION['discord_id']),
            'session_id' => $_SESSION['discord_id'] ?? null,
            'get_user_id' => $_GET['user_id'] ?? null,
            'request_user_id' => $requestData['user_id'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD']
        ]
    ]);
    exit;
}

// Prevent test user IDs in production (only block the literal test string, not real Discord IDs)
// Note: $LOCAL_TEST_DISCORD_ID is Narrrf's real Discord ID, so it should work in production
if (!$isLocalDevelopment && $user_id === 'LOCAL_TEST_DISCORD') {
    error_log("❌ Recent adjustments: Test user string blocked in production: " . $user_id);
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Test user not allowed in production'
    ]);
    exit;
}

// ✅ DSPOINC Journey pagination.
// DEVS FOR DECADES:
// - limit controls how many ledger rows profile.html requests per page.
// - offset controls the page start.
// - Backend remains read-only here.
// - No DSPOINC ledger mutations happen in the pagination query.
$limit = get_bounded_int($requestData, 'limit', 20, 1, 100);
$offset = (int)($requestData['offset'] ?? 0);

if ($offset < 0) {
    $offset = 0;
}

// Use a safe relative path so it works both locally and on Render!
if ($isLocalDevelopment) {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
} else {
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Backfill: Check if staking entries exist for active stakes and create missing ones.
    // This ensures staking transactions appear in Recent Score Changes.
    try {
        $stakesCheck = $db->prepare("
    SELECT
        id,
        amount,
        freeze_duration_months,
        expected_reward,
        staking_contract_version,
        lock_duration_days
    FROM tbl_dspoinc_stakes
    WHERE user_id = ? AND status = 'active'
");
        $stakesCheck->execute([$user_id]);
        $activeStakes = $stakesCheck->fetchAll(PDO::FETCH_ASSOC);

        foreach ($activeStakes as $stake) {

        /**
 * Skip legacy audit repair for Season 13 V2 stakes.
 *
 * Plain language for DEVS:
 * V2 stakes use lock_duration_days and create their own V2 audit row inside
 * create-stake.php. This old repair block is legacy-only and would create
 * confusing "0 months" audit rows for V2 stakes.
 */
if (staking_is_v2_stake($stake)) {
    continue;
}
            // Check if entry exists - match by amount AND reason pattern
            // to handle multiple stakes with same amount.
            $reasonPattern = '%DSPOINC frozen for staking: ' . (int)$stake['amount'] . ' DSPOINC for ' . (int)$stake['freeze_duration_months'] . ' months%';

            $entryCheck = $db->prepare("
                SELECT COUNT(*) as count
                FROM tbl_score_adjustments
                WHERE user_id = ?
                  AND action = 'remove'
                  AND amount = ?
                  AND reason LIKE ?
            ");
            $entryCheck->execute([$user_id, -(int)$stake['amount'], $reasonPattern]);
            $entryResult = $entryCheck->fetch(PDO::FETCH_ASSOC);

            if ((int)($entryResult['count'] ?? 0) === 0) {
                // Create missing entry.
                $reason = sprintf(
                    'DSPOINC frozen for staking: %d DSPOINC for %d months (expected reward: %d DSPOINC)',
                    (int)$stake['amount'],
                    (int)$stake['freeze_duration_months'],
                    (int)$stake['expected_reward']
                );

                $backfillStmt = $db->prepare("
                    INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $backfillStmt->execute([
                    $user_id,
                    'system-staking',
                    -(int)$stake['amount'],
                    'remove', // Use 'remove' since CHECK constraint only allows 'add', 'remove', 'set'
                    $reason,
                    $stake['frozen_at']
                ]);

                if ($isLocalDevelopment) {
                    error_log("✅ Backfilled score adjustment for stake #{$stake['id']} in recent-adjustments API");
                }
            }
        }
    } catch (Exception $backfillError) {
        // Don't fail the whole request if backfill fails.
        if ($isLocalDevelopment) {
            error_log("⚠️ Backfill error in recent-adjustments: " . $backfillError->getMessage());
        }
    }

    // ✅ Count total rows first so profile.html can render Page X / Y.
    $countStmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM tbl_score_adjustments
        WHERE user_id = ?
    ");
    $countStmt->execute([$user_id]);
    $totalAdjustments = (int)$countStmt->fetchColumn();

    // ✅ Fetch only the current page.
    $stmt = $db->prepare("
        SELECT
            a.*,
            u1.username as username,
            u2.username as admin_name
        FROM tbl_score_adjustments a
        LEFT JOIN tbl_users u1 ON a.user_id = u1.discord_id
        LEFT JOIN tbl_users u2 ON a.admin_id = u2.discord_id
        WHERE a.user_id = ?
        ORDER BY a.timestamp DESC
        LIMIT ?
        OFFSET ?
    ");
    $stmt->bindValue(1, $user_id, PDO::PARAM_STR);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();

    $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasMore = ($offset + $limit) < $totalAdjustments;
    $currentPage = (int)floor($offset / $limit) + 1;
    $totalPages = $limit > 0 ? max(1, (int)ceil($totalAdjustments / $limit)) : 1;

    // Debug logging for staking entries.
    // Check for 'remove' action with staking reason
    // since CHECK constraint requires 'add', 'remove', or 'set'.
    $stakingEntries = array_filter($adjustments, function($a) {
        return (
            (
                $a['action'] === 'remove' ||
                $a['action'] === 'stake_freeze' ||
                $a['action'] === 'freeze'
            ) &&
            isset($a['reason']) &&
            strpos($a['reason'], 'DSPOINC frozen for staking') !== false
        );
    });

    // Also check directly in database for staking entries
    // in case they exist but weren't returned on this page.
    $directStakingCheck = $db->prepare("
        SELECT COUNT(*) as count
        FROM tbl_score_adjustments
        WHERE user_id = ?
          AND action = 'remove'
          AND reason LIKE '%DSPOINC frozen for staking%'
    ");
    $directStakingCheck->execute([$user_id]);
    $directResult = $directStakingCheck->fetch(PDO::FETCH_ASSOC);

    if ($isLocalDevelopment) {
        error_log("📊 Recent adjustments: Found " . count($adjustments) . " paged entries for user " . substr($user_id, 0, 10) . "...");
        error_log("📊 Recent adjustments: Total entries for user: " . $totalAdjustments);
        error_log("📊 Recent adjustments: Page {$currentPage} / {$totalPages}, limit {$limit}, offset {$offset}");
        error_log("📊 Recent adjustments: Found " . count($stakingEntries) . " staking entries in current page");
        error_log("📊 Recent adjustments: Direct DB query found " . ($directResult['count'] ?? 0) . " staking entries");

        if (count($stakingEntries) > 0) {
            error_log("📊 Staking entries details: " . json_encode($stakingEntries, JSON_PRETTY_PRINT));
        } else if (($directResult['count'] ?? 0) > 0) {
            error_log("ℹ️ Staking entries exist in DB but are not on this current paged result.");
        }
    }

    echo json_encode([
        'success' => true,
        'adjustments' => $adjustments,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => $totalAdjustments,
            'has_more' => $hasMore,
            'current_page' => $currentPage,
            'total_pages' => $totalPages
        ]
    ]);
} catch (Exception $e) {
    error_log("❌ Recent adjustments error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch adjustments: ' . $e->getMessage()
    ]);
}
?>