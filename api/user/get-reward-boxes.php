<?php
// 🎁 Get Reward Boxes API
// Read-only endpoint for profile Reward Chamber.
// Returns visible reward boxes, user cooldown state, box availability, and reward pool summary.
//
// Stability-first rules:
// - Session auth is authoritative in production
// - Localhost may use request user_id or Narrrf fallback
// - Backend computes disabled state / cooldown state
// - No frontend authority for availability, stock, cooldown, or pricing
// - This endpoint is read-only and does not modify balances or rewards

error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('UTC');

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$databaseIncludeCandidates = [
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../config/database.php'
];

foreach ($databaseIncludeCandidates as $databaseIncludePath) {
    if (file_exists($databaseIncludePath)) {
        require_once $databaseIncludePath;
        break;
    }
}

$discordSecretCandidates = [
    __DIR__ . '/../../config/discord-secret.php',
    __DIR__ . '/../config/discord-secret.php'
];

foreach ($discordSecretCandidates as $discordSecretPath) {
    if (file_exists($discordSecretPath)) {
        include_once $discordSecretPath;
        break;
    }
}

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf local fallback

function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function is_localhost_env(): bool {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

function get_request_data(): array {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $cached = [];
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            $cached = $json;
        } elseif (!empty($_POST) && is_array($_POST)) {
            $cached = $_POST;
        }
    } else {
        $cached = $_GET ?? [];
    }

    return is_array($cached) ? $cached : [];
}

function resolve_user_id(): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $request = get_request_data();
    $requestUserId = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $requestUserId !== '') {
        error_log("🎁 Get Reward Boxes: Using request user_id for localhost testing: {$requestUserId}");
        return $requestUserId;
    }

    $userId = $sessionUserId;

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        error_log("🚨 SECURITY: Get Reward Boxes - user_id mismatch. Session: {$sessionUserId}, Request: {$requestUserId}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($userId === '' && $isLocalhost) {
        error_log("🎁 Get Reward Boxes: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $userId;
}

function get_reward_boxes_database_connection(): PDO {
    if (function_exists('getDatabaseConnection')) {
        $pdo = getDatabaseConnection();
        if ($pdo instanceof PDO) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        }
    }

    if (function_exists('getDB')) {
        $pdo = getDB();
        if ($pdo instanceof PDO) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        }
    }

    $dbPathCandidates = [];

    if (is_localhost_env()) {
        $dbPathCandidates[] = __DIR__ . '/../../db/narrrf_world.sqlite';
        $dbPathCandidates[] = 'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite';
    }

    $dbPathCandidates[] = __DIR__ . '/../../db/narrrf_world.sqlite';
    $dbPathCandidates[] = '/var/www/html/db/narrrf_world.sqlite';

    foreach ($dbPathCandidates as $dbPath) {
        if (!$dbPath || !file_exists($dbPath)) {
            continue;
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }

    throw new Exception('Database connection helper not available and SQLite file not found');
}

function get_user_available_dspoinc(PDO $pdo, string $userId): int {
    if ($userId === '') {
        return 0;
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0)
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0)
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $frozen = (int)$stmt->fetchColumn();

    return max(0, $total - $frozen);
}

function fetch_user_box_state_map(PDO $pdo, string $userId): array {
    if ($userId === '') {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT
            box_id,
            user_box_state_id,
            open_count,
            last_opened_at,
            next_open_at,
            last_reward_type,
            last_reward_title,
            last_reward_reference_id,
            last_reward_dspoinc_amount,
            created_at,
            updated_at
        FROM tbl_reward_box_user_state
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int)$row['box_id']] = $row;
    }

    return $map;
}

function fetch_reward_pool_summary_map(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT
            box_id,
            COUNT(*) AS reward_count,
            SUM(CASE WHEN reward_type = 'store_item' THEN 1 ELSE 0 END) AS store_rewards,
            SUM(CASE WHEN reward_type = 'genetic_trait' THEN 1 ELSE 0 END) AS genetic_rewards,
            SUM(CASE WHEN reward_type = 'premium_claim' THEN 1 ELSE 0 END) AS premium_claim_rewards,
            SUM(CASE WHEN reward_type = 'dspoinc' THEN 1 ELSE 0 END) AS dspoinc_rewards
        FROM tbl_reward_box_reward_pool
        WHERE is_active = 1
        GROUP BY box_id
    ");

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int)$row['box_id']] = [
            'reward_count' => (int)($row['reward_count'] ?? 0),
            'store_rewards' => (int)($row['store_rewards'] ?? 0),
            'genetic_rewards' => (int)($row['genetic_rewards'] ?? 0),
            'premium_claim_rewards' => (int)($row['premium_claim_rewards'] ?? 0),
            'dspoinc_rewards' => (int)($row['dspoinc_rewards'] ?? 0)
        ];
    }

    return $map;
}

function fetch_visible_reward_boxes(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT
            box_id,
            box_key,
            box_name,
            box_description,
            box_type,
            is_active,
            is_visible,
            sort_order,
            price_dspoinc,
            cooldown_enabled,
            cooldown_type,
            cooldown_hours,
            reward_mode,
            fallback_dspoinc_enabled,
            fallback_dspoinc_min,
            fallback_dspoinc_max,
            item_pool_roll_chance,
            fallback_dspoinc_roll_chance,
            global_stock_limit,
            global_open_count,
            max_opens_per_user,
            start_at,
            end_at,
            disabled_reason_text,
            visual_theme,
            image_path,
            legacy_fallback_enabled,
            created_at,
            updated_at
        FROM tbl_reward_boxes
        WHERE is_visible = 1
        ORDER BY sort_order ASC, box_id ASC
    ");

    return $stmt->fetchAll();
}

function calculate_disabled_state(array $box, ?array $userState, string $userId, int $availableDspoinc, string $nowUtc): array {
    $isLoggedIn = trim($userId) !== '';
    $isDisabled = false;
    $reasons = [];

    if (!$isLoggedIn) {
        $isDisabled = true;
        $reasons[] = 'Login with Discord to access reward boxes';
    }

    if ((int)($box['is_active'] ?? 0) !== 1) {
        $isDisabled = true;
        $reasons[] = trim((string)($box['disabled_reason_text'] ?? 'This reward box is currently inactive')) ?: 'This reward box is currently inactive';
    }

    $startAt = trim((string)($box['start_at'] ?? ''));
    if ($startAt !== '' && strtotime($nowUtc) < strtotime($startAt)) {
        $isDisabled = true;
        $reasons[] = 'This reward box is not live yet';
    }

    $endAt = trim((string)($box['end_at'] ?? ''));
    if ($endAt !== '' && strtotime($nowUtc) > strtotime($endAt)) {
        $isDisabled = true;
        $reasons[] = 'This reward box event has ended';
    }

    $globalStockLimit = $box['global_stock_limit'];
    $globalOpenCount = (int)($box['global_open_count'] ?? 0);
    if ($globalStockLimit !== null && $globalStockLimit !== '' && (int)$globalStockLimit >= 0) {
        if ($globalOpenCount >= (int)$globalStockLimit) {
            $isDisabled = true;
            $reasons[] = 'This reward box is sold out';
        }
    }

    $maxOpensPerUser = $box['max_opens_per_user'];
    $userOpenCount = (int)($userState['open_count'] ?? 0);
    if ($maxOpensPerUser !== null && $maxOpensPerUser !== '' && $isLoggedIn) {
        if ($userOpenCount >= (int)$maxOpensPerUser) {
            $isDisabled = true;
            $reasons[] = 'You reached the maximum number of opens for this box';
        }
    }

    $cooldownEnabled = (int)($box['cooldown_enabled'] ?? 0) === 1;
    $nextOpenAt = trim((string)($userState['next_open_at'] ?? ''));
    if ($cooldownEnabled && $isLoggedIn && $nextOpenAt !== '' && strtotime($nowUtc) < strtotime($nextOpenAt)) {
        $isDisabled = true;
        $reasons[] = 'Cooldown active';
    }

    $price = max(0, (int)($box['price_dspoinc'] ?? 0));
    if ($isLoggedIn && $price > $availableDspoinc) {
        $isDisabled = true;
        $reasons[] = 'Not enough DSPOINC';
    }

    return [
        'is_disabled' => $isDisabled,
        'disabled_reason' => implode(' · ', array_values(array_unique(array_filter($reasons)))),
        'user_open_count' => $userOpenCount,
        'next_open_at' => $nextOpenAt !== '' ? $nextOpenAt : null
    ];
}

try {
    $pdo = get_reward_boxes_database_connection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method !== 'GET') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $userId = resolve_user_id();
    $isLoggedIn = $userId !== '';
    $nowUtc = gmdate('Y-m-d H:i:s');
    $availableDspoinc = $isLoggedIn ? get_user_available_dspoinc($pdo, $userId) : 0;

    $boxes = fetch_visible_reward_boxes($pdo);
    $userStateMap = fetch_user_box_state_map($pdo, $userId);
    $poolSummaryMap = fetch_reward_pool_summary_map($pdo);

    $payloadBoxes = [];

    foreach ($boxes as $box) {
        $boxId = (int)($box['box_id'] ?? 0);
        $userState = $userStateMap[$boxId] ?? null;
        $poolSummary = $poolSummaryMap[$boxId] ?? [
            'reward_count' => 0,
            'store_rewards' => 0,
            'genetic_rewards' => 0,
            'premium_claim_rewards' => 0,
            'dspoinc_rewards' => 0
        ];

        $disabledState = calculate_disabled_state($box, $userState, $userId, $availableDspoinc, $nowUtc);

        $payloadBoxes[] = [
            'box_id' => $boxId,
            'box_key' => (string)($box['box_key'] ?? ''),
            'box_name' => (string)($box['box_name'] ?? ''),
            'box_description' => (string)($box['box_description'] ?? ''),
            'box_type' => (string)($box['box_type'] ?? ''),
            'price_dspoinc' => max(0, (int)($box['price_dspoinc'] ?? 0)),
            'reward_mode' => (string)($box['reward_mode'] ?? ''),
            'visual_theme' => (string)($box['visual_theme'] ?? ''),
            'image_path' => (string)($box['image_path'] ?? ''),
            'cooldown_enabled' => (int)($box['cooldown_enabled'] ?? 0) === 1,
            'cooldown_type' => (string)($box['cooldown_type'] ?? 'none'),
            'cooldown_hours' => (float)($box['cooldown_hours'] ?? 0),
            'fallback_dspoinc_enabled' => (int)($box['fallback_dspoinc_enabled'] ?? 0) === 1,
            'fallback_dspoinc_min' => (int)($box['fallback_dspoinc_min'] ?? 0),
            'fallback_dspoinc_max' => (int)($box['fallback_dspoinc_max'] ?? 0),
            'item_pool_roll_chance' => (float)($box['item_pool_roll_chance'] ?? 0),
            'fallback_dspoinc_roll_chance' => (float)($box['fallback_dspoinc_roll_chance'] ?? 0),
            'global_stock_limit' => $box['global_stock_limit'] !== null ? (int)$box['global_stock_limit'] : null,
            'global_open_count' => (int)($box['global_open_count'] ?? 0),
            'max_opens_per_user' => $box['max_opens_per_user'] !== null ? (int)$box['max_opens_per_user'] : null,
            'start_at' => !empty($box['start_at']) ? (string)$box['start_at'] : null,
            'end_at' => !empty($box['end_at']) ? (string)$box['end_at'] : null,
            'is_active' => (int)($box['is_active'] ?? 0) === 1,
            'is_visible' => (int)($box['is_visible'] ?? 0) === 1,
            'is_disabled' => (bool)$disabledState['is_disabled'],
            'disabled_reason' => $disabledState['is_disabled']
                ? (string)$disabledState['disabled_reason']
                : null,
            'user_state' => [
                'open_count' => (int)$disabledState['user_open_count'],
                'last_opened_at' => !empty($userState['last_opened_at']) ? (string)$userState['last_opened_at'] : null,
                'next_open_at' => $disabledState['next_open_at'],
                'last_reward_type' => !empty($userState['last_reward_type']) ? (string)$userState['last_reward_type'] : null,
                'last_reward_title' => !empty($userState['last_reward_title']) ? (string)$userState['last_reward_title'] : null,
                'last_reward_reference_id' => isset($userState['last_reward_reference_id']) && $userState['last_reward_reference_id'] !== null
                    ? (int)$userState['last_reward_reference_id']
                    : null,
                'last_reward_dspoinc_amount' => isset($userState['last_reward_dspoinc_amount']) && $userState['last_reward_dspoinc_amount'] !== null
                    ? (int)$userState['last_reward_dspoinc_amount']
                    : null
            ],
            'reward_summary' => $poolSummary,
            'legacy_fallback_enabled' => (int)($box['legacy_fallback_enabled'] ?? 0) === 1
        ];
    }

    json_response([
        'success' => true,
        'data' => [
            'user_id' => $userId,
            'is_logged_in' => $isLoggedIn,
            'available_dspoinc' => $availableDspoinc,
            'server_time_utc' => $nowUtc,
            'boxes' => $payloadBoxes
        ]
    ]);
} catch (Throwable $e) {
    error_log('🎁 Get Reward Boxes failed: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Failed to load reward boxes'
    ], 500);
}