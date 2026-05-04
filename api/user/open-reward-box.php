<?php
// 🎁 Open Reward Box API
// Opens one Reward Chamber box for the active Discord-authenticated user.
//
// Current scope:
// - Box Type 1: free_dspoinc_box
// - Box Type 2: paid_random_box
// - Box Type 3: premium_claim_box
//
// Current behavior:
// - Backend computes cooldown, pricing, availability, and reward delivery
// - Box 2 supports weighted pool / fallback delivery
// - Box 3 creates a pending claim request
//
// Stability-first rules:
// - Session auth is authoritative in production
// - Localhost may use request user_id or Narrrf fallback
// - Backend computes cooldown, price, and availability
// - SQLite-safe: no FOR UPDATE usage
// - DSPOINC reward credits write to tbl_user_scores and tbl_score_adjustments
// - Box open history and per-user box state are updated atomically

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
const REWARD_BOX_SYSTEM_ACTOR = 'reward_box_system';
const REWARD_BOX_GAME_KEY = 'reward_box';
const REWARD_BOX_OPEN_METHOD_AUTO = 'auto';
const REWARD_BOX_STATUS_GRANTED = 'granted';
const REWARD_BOX_STATUS_PENDING = 'pending';

/**
 * Return a JSON response and stop execution.
 */
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running on localhost-style development hosts.
 */
function is_localhost_env(): bool {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data once from JSON, POST, or GET.
 */
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

/**
 * Resolve active user ID.
 * Production = session-first.
 * Localhost = request override allowed.
 */
function resolve_user_id(): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $request = get_request_data();
    $requestUserId = trim((string)($request['user_id'] ?? ''));

    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $requestUserId !== '') {
        error_log("🎁 Open Reward Box: Using request user_id for localhost testing: {$requestUserId}");
        return $requestUserId;
    }

    $userId = $sessionUserId;

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        error_log("🚨 SECURITY: Open Reward Box - user_id mismatch. Session: {$sessionUserId}, Request: {$requestUserId}");
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($userId === '' && $isLocalhost) {
        error_log("🎁 Open Reward Box: Using local test user (Narrrf) for localhost");
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $userId;
}

/**
 * Open the Narrrfs World database in a way that works both locally and in production.
 */
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

/**
 * Return canonical available DSPOINC = total ledger score - active frozen stakes.
 */
function get_user_available_dspoinc(PDO $pdo, string $userId): int {
    if ($userId === '') {
        return 0;
    }

    $stmt = $pdo->prepare("\n        SELECT COALESCE(SUM(score), 0)\n        FROM tbl_user_scores\n        WHERE user_id = ?\n    ");
    $stmt->execute([$userId]);
    $total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("\n        SELECT COALESCE(SUM(amount), 0)\n        FROM tbl_dspoinc_stakes\n        WHERE user_id = ?\n          AND status = 'active'\n    ");
    $stmt->execute([$userId]);
    $frozen = (int)$stmt->fetchColumn();

    return max(0, $total - $frozen);
}

/**
 * Load one reward box by ID.
 */
function fetch_reward_box(PDO $pdo, int $boxId): ?array {
    $stmt = $pdo->prepare("\n        SELECT\n            box_id,\n            box_key,\n            box_name,\n            box_description,\n            box_type,\n            is_active,\n            is_visible,\n            sort_order,\n            price_dspoinc,\n            cooldown_enabled,\n            cooldown_type,\n            cooldown_hours,\n            reward_mode,\n            fallback_dspoinc_enabled,\n            fallback_dspoinc_min,\n            fallback_dspoinc_max,\n            item_pool_roll_chance,\n            fallback_dspoinc_roll_chance,\n            global_stock_limit,\n            global_open_count,\n            max_opens_per_user,\n            start_at,\n            end_at,\n            disabled_reason_text,\n            visual_theme,\n            image_path,\n            legacy_fallback_enabled,\n            created_at,\n            updated_at\n        FROM tbl_reward_boxes\n        WHERE box_id = ?\n        LIMIT 1\n    ");
    $stmt->execute([$boxId]);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Load one per-user reward box state row when it exists.
 */
function fetch_user_box_state(PDO $pdo, int $boxId, string $userId): ?array {
    if ($userId === '' || $boxId < 1) {
        return null;
    }

    $stmt = $pdo->prepare("\n        SELECT\n            user_box_state_id,\n            user_id,\n            box_id,\n            open_count,\n            last_opened_at,\n            next_open_at,\n            last_reward_type,\n            last_reward_title,\n            last_reward_reference_id,\n            last_reward_dspoinc_amount,\n            created_at,\n            updated_at\n        FROM tbl_reward_box_user_state\n        WHERE user_id = ?\n          AND box_id = ?\n        LIMIT 1\n    ");
    $stmt->execute([$userId, $boxId]);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Return a compact reward summary for one box.
 */
function fetch_reward_pool_summary(PDO $pdo, int $boxId): array {
    $stmt = $pdo->prepare("\n        SELECT\n            COUNT(*) AS reward_count,\n            SUM(CASE WHEN reward_type = 'store_item' THEN 1 ELSE 0 END) AS store_rewards,\n            SUM(CASE WHEN reward_type = 'genetic_trait' THEN 1 ELSE 0 END) AS genetic_rewards,\n            SUM(CASE WHEN reward_type = 'premium_claim' THEN 1 ELSE 0 END) AS premium_claim_rewards,\n            SUM(CASE WHEN reward_type = 'dspoinc' THEN 1 ELSE 0 END) AS dspoinc_rewards\n        FROM tbl_reward_box_reward_pool\n        WHERE box_id = ?\n          AND is_active = 1\n    ");
    $stmt->execute([$boxId]);

    $row = $stmt->fetch() ?: [];

    return [
        'reward_count' => (int)($row['reward_count'] ?? 0),
        'store_rewards' => (int)($row['store_rewards'] ?? 0),
        'genetic_rewards' => (int)($row['genetic_rewards'] ?? 0),
        'premium_claim_rewards' => (int)($row['premium_claim_rewards'] ?? 0),
        'dspoinc_rewards' => (int)($row['dspoinc_rewards'] ?? 0)
    ];
}

/**
 * Compute frontend-safe disabled state using backend truth only.
 */
function calculate_disabled_state(array $box, ?array $userState, string $userId, int $availableDspoinc, string $nowUtc): array {
    $isLoggedIn = trim($userId) !== '';
    $isDisabled = false;
    $reasons = [];

    if (!$isLoggedIn) {
        $isDisabled = true;
        $reasons[] = 'Login with Discord to access reward boxes';
    }

    if ((int)($box['is_visible'] ?? 0) !== 1) {
        $isDisabled = true;
        $reasons[] = 'This reward box is not visible';
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

    $globalStockLimit = $box['global_stock_limit'] ?? null;
    $globalOpenCount = (int)($box['global_open_count'] ?? 0);
    if ($globalStockLimit !== null && $globalStockLimit !== '' && (int)$globalStockLimit >= 0) {
        if ($globalOpenCount >= (int)$globalStockLimit) {
            $isDisabled = true;
            $reasons[] = 'This reward box is sold out';
        }
    }

    $maxOpensPerUser = $box['max_opens_per_user'] ?? null;
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
        'disabled_reason' => $isDisabled
            ? implode(' · ', array_values(array_unique(array_filter($reasons))))
            : null,
        'user_open_count' => $userOpenCount,
        'next_open_at' => $nextOpenAt !== '' ? $nextOpenAt : null
    ];
}

/**
 * Return the next-open timestamp based on the configured cooldown model.
 */
function calculate_next_open_at(array $box, string $openedAtUtc): ?string {
    $cooldownEnabled = (int)($box['cooldown_enabled'] ?? 0) === 1;
    if (!$cooldownEnabled) {
        return null;
    }

    $cooldownType = trim((string)($box['cooldown_type'] ?? ''));
    $cooldownHours = (float)($box['cooldown_hours'] ?? 0);

    if ($cooldownType === 'daily' && $cooldownHours <= 0) {
        $cooldownHours = 24;
    }

    if ($cooldownHours <= 0) {
        return null;
    }

    $timestamp = strtotime($openedAtUtc . ' UTC');
    if ($timestamp === false) {
        return null;
    }

    $seconds = (int)round($cooldownHours * 3600);
    if ($seconds <= 0) {
        return null;
    }

    return gmdate('Y-m-d H:i:s', $timestamp + $seconds);
}

/**
 * Return a random DSPOINC amount between the configured fallback min/max values.
 */
function roll_free_box_dspoinc_amount(array $box): int {
    $min = max(0, (int)($box['fallback_dspoinc_min'] ?? 0));
    $max = max(0, (int)($box['fallback_dspoinc_max'] ?? 0));

    if ($max < $min) {
        $max = $min;
    }

    if ($max === $min) {
        return $min;
    }

    return random_int($min, $max);
}

/**
 * Insert one positive or negative DSPOINC ledger row.
 */
function insert_dspoinc_change(PDO $pdo, string $userId, int $amount, string $game, string $source): void {
    $stmt = $pdo->prepare("\n        INSERT INTO tbl_user_scores (\n            user_id,\n            game,\n            score,\n            source,\n            timestamp\n        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)\n    ");

    $stmt->execute([
        $userId,
        $game,
        $amount,
        $source
    ]);
}

/**
 * Insert one score adjustment audit row.
 */
function insert_score_adjustment(PDO $pdo, string $userId, string $adminId, int $amount, string $action, string $reason): void {
    $stmt = $pdo->prepare("\n        INSERT INTO tbl_score_adjustments (\n            user_id,\n            admin_id,\n            amount,\n            action,\n            reason,\n            timestamp\n        ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)\n    ");

    $stmt->execute([
        $userId,
        $adminId,
        $amount,
        $action,
        $reason
    ]);
}

/**
 * Insert one reward box open history row.
 */
function insert_reward_box_open_history(
    PDO $pdo,
    int $boxId,
    string $userId,
    int $boxPriceDspoinc,
    string $rewardType,
    string $rewardTitle,
    ?int $rewardReferenceId,
    ?int $rewardDspoincAmount,
    bool $usedFallback,
    bool $usedReroll,
    ?int $claimRequestId,
    string $openStatus
): int {
    $stmt = $pdo->prepare("
        INSERT INTO tbl_reward_box_open_history (
            box_id,
            user_id,
            open_status,
            box_price_dspoinc,
            reward_type,
            reward_title,
            reward_reference_id,
            reward_dspoinc_amount,
            rolled_pool_reward_id,
            fallback_used,
            reroll_used,
            claim_request_id,
            opened_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $boxId,
        $userId,
        $openStatus,
        $boxPriceDspoinc,
        $rewardType,
        $rewardTitle,
        $rewardReferenceId,
        $rewardDspoincAmount,
        null,
        $usedFallback ? 1 : 0,
        $usedReroll ? 1 : 0,
        $claimRequestId
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Insert or update one user's persistent state row for a reward box.
 */
function upsert_reward_box_user_state(
    PDO $pdo,
    int $boxId,
    string $userId,
    int $openCount,
    string $lastOpenedAt,
    ?string $nextOpenAt,
    string $lastRewardType,
    string $lastRewardTitle,
    ?int $lastRewardReferenceId,
    ?int $lastRewardDspoincAmount
): void {
    $existing = fetch_user_box_state($pdo, $boxId, $userId);

    if ($existing) {
        $stmt = $pdo->prepare("\n            UPDATE tbl_reward_box_user_state\n            SET\n                open_count = ?,\n                last_opened_at = ?,\n                next_open_at = ?,\n                last_reward_type = ?,\n                last_reward_title = ?,\n                last_reward_reference_id = ?,\n                last_reward_dspoinc_amount = ?,\n                updated_at = CURRENT_TIMESTAMP\n            WHERE user_box_state_id = ?\n        ");

        $stmt->execute([
            $openCount,
            $lastOpenedAt,
            $nextOpenAt,
            $lastRewardType,
            $lastRewardTitle,
            $lastRewardReferenceId,
            $lastRewardDspoincAmount,
            (int)$existing['user_box_state_id']
        ]);
        return;
    }

    $stmt = $pdo->prepare("\n        INSERT INTO tbl_reward_box_user_state (\n            user_id,\n            box_id,\n            open_count,\n            last_opened_at,\n            next_open_at,\n            last_reward_type,\n            last_reward_title,\n            last_reward_reference_id,\n            last_reward_dspoinc_amount,\n            created_at,\n            updated_at\n        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)\n    ");

    $stmt->execute([
        $userId,
        $boxId,
        $openCount,
        $lastOpenedAt,
        $nextOpenAt,
        $lastRewardType,
        $lastRewardTitle,
        $lastRewardReferenceId,
        $lastRewardDspoincAmount
    ]);
}

/**
 * Increment the global open counter for one box.
 */
function increment_reward_box_global_open_count(PDO $pdo, int $boxId): void {
    $stmt = $pdo->prepare("\n        UPDATE tbl_reward_boxes\n        SET\n            global_open_count = COALESCE(global_open_count, 0) + 1,\n            updated_at = CURRENT_TIMESTAMP\n        WHERE box_id = ?\n    ");
    $stmt->execute([$boxId]);
}



/**
 * Return true if a SQLite table exists.
 */
function sqlite_table_exists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1");
    $stmt->execute([$tableName]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Return available columns for one table.
 */
function get_table_columns(PDO $pdo, string $tableName): array {
    if (!sqlite_table_exists($pdo, $tableName)) {
        return [];
    }

    $stmt = $pdo->query("PRAGMA table_info($tableName)");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $columns = [];

    foreach ($rows as $row) {
        $name = trim((string)($row['name'] ?? ''));
        if ($name !== '') {
            $columns[] = $name;
        }
    }

    return $columns;
}

/**
 * Return one row from a table by trying multiple possible identifier columns.
 */
function fetch_first_row_by_candidate_id(PDO $pdo, string $tableName, array $idColumns, int $idValue): ?array {
    if ($idValue < 1 || !sqlite_table_exists($pdo, $tableName)) {
        return null;
    }

    $columns = get_table_columns($pdo, $tableName);
    foreach ($idColumns as $column) {
        if (!in_array($column, $columns, true)) {
            continue;
        }

        $stmt = $pdo->prepare("SELECT * FROM {$tableName} WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$idValue]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    return null;
}

/**
 * Load all active reward-pool entries for one box.
 */
function fetch_reward_pool_entries(PDO $pdo, int $boxId): array {
    if ($boxId < 1 || !sqlite_table_exists($pdo, 'tbl_reward_box_reward_pool')) {
        return [];
    }

    $columns = get_table_columns($pdo, 'tbl_reward_box_reward_pool');
    $orderCandidates = ['sort_order', 'reward_pool_id', 'pool_reward_id', 'id'];
    $orderParts = [];

    foreach ($orderCandidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            $orderParts[] = $candidate . ' ASC';
        }
    }

    if (empty($orderParts)) {
        $orderParts[] = 'rowid ASC';
    }

    $hasIsActive = in_array('is_active', $columns, true);
    $sql = "SELECT * FROM tbl_reward_box_reward_pool WHERE box_id = ?" . ($hasIsActive ? " AND is_active = 1" : "") . " ORDER BY " . implode(', ', $orderParts);

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$boxId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $entries = [];
    foreach ($rows as $row) {
        $poolId = (int)($row['reward_pool_id'] ?? $row['pool_reward_id'] ?? $row['id'] ?? 0);
        $rewardType = trim((string)($row['reward_type'] ?? ''));
        $title = trim((string)($row['reward_title'] ?? $row['display_title'] ?? ''));
        $description = trim((string)($row['reward_description'] ?? $row['description'] ?? ''));
        $storeItemId = (int)($row['store_item_id'] ?? 0);
        $catalogId = (int)($row['catalog_id'] ?? $row['genetic_catalog_id'] ?? $row['trait_catalog_id'] ?? 0);
        $rewardReferenceId = (int)($row['reward_reference_id'] ?? 0);
        $weight = max(1, (int)($row['weight'] ?? 1));
        $rewardDspoincAmount = isset($row['fixed_dspoinc_amount'])
    ? (int)$row['fixed_dspoinc_amount']
    : (isset($row['reward_dspoinc_amount']) ? (int)$row['reward_dspoinc_amount'] : null);

$rewardDspoincMin = isset($row['dspoinc_min'])
    ? (int)$row['dspoinc_min']
    : (isset($row['reward_dspoinc_min']) ? (int)$row['reward_dspoinc_min'] : null);

$rewardDspoincMax = isset($row['dspoinc_max'])
    ? (int)$row['dspoinc_max']
    : (isset($row['reward_dspoinc_max']) ? (int)$row['reward_dspoinc_max'] : null);

        if ($rewardType === '') {
            continue;
        }

        $entries[] = [
            'pool_reward_id' => $poolId,
            'reward_type' => $rewardType,
            'reward_title' => $title,
            'reward_description' => $description,
            'store_item_id' => $storeItemId,
            'catalog_id' => $catalogId,
            'reward_reference_id' => $rewardReferenceId,
            'weight' => $weight,
            'reward_dspoinc_amount' => $rewardDspoincAmount,
            'reward_dspoinc_min' => $rewardDspoincMin,
            'reward_dspoinc_max' => $rewardDspoincMax,
            'raw' => $row
        ];
    }

    return $entries;
}

/**
 * Choose one weighted reward entry.
 */
function pick_weighted_reward_entry(array $entries, array $excludedPoolIds = []): ?array {
    $excludedMap = [];
    foreach ($excludedPoolIds as $id) {
        $excludedMap[(int)$id] = true;
    }

    $eligible = [];
    $totalWeight = 0;
    foreach ($entries as $entry) {
        $poolId = (int)($entry['pool_reward_id'] ?? 0);
        if ($poolId > 0 && isset($excludedMap[$poolId])) {
            continue;
        }

        $weight = max(1, (int)($entry['weight'] ?? 1));
        $entry['weight'] = $weight;
        $eligible[] = $entry;
        $totalWeight += $weight;
    }

    if (empty($eligible) || $totalWeight <= 0) {
        return null;
    }

    $roll = random_int(1, $totalWeight);
    $cursor = 0;

    foreach ($eligible as $entry) {
        $cursor += (int)$entry['weight'];
        if ($roll <= $cursor) {
            return $entry;
        }
    }

    return $eligible[0] ?? null;
}

/**
 * Return true when the box should use a fallback DSPOINC path instead of the item pool.
 */
function should_roll_reward_box_fallback(array $box, array $poolEntries): bool {
    if (empty($poolEntries)) {
        return true;
    }

    $itemChance = max(0.0, (float)($box['item_pool_roll_chance'] ?? 0));
    $fallbackChance = max(0.0, (float)($box['fallback_dspoinc_roll_chance'] ?? 0));

    if ($fallbackChance <= 0) {
        return false;
    }

    if ($itemChance <= 0 && $fallbackChance > 0) {
        return true;
    }

    $totalChance = $itemChance + $fallbackChance;
    if ($totalChance <= 0) {
        return false;
    }

    $roll = random_int(1, (int)round($totalChance * 1000));
    $poolThreshold = (int)round($itemChance * 1000);

    return $roll > $poolThreshold;
}

/**
 * Return a fallback DSPOINC amount for non-free reward boxes.
 */
function roll_reward_box_fallback_dspoinc_amount(array $box, ?array $poolEntry = null): int {
    if ($poolEntry) {
        $fixedAmount = (int)($poolEntry['reward_dspoinc_amount'] ?? 0);
        $min = isset($poolEntry['reward_dspoinc_min']) ? (int)$poolEntry['reward_dspoinc_min'] : null;
        $max = isset($poolEntry['reward_dspoinc_max']) ? (int)$poolEntry['reward_dspoinc_max'] : null;

        if ($fixedAmount > 0) {
            return $fixedAmount;
        }

        if ($min !== null || $max !== null) {
            $min = max(0, (int)($min ?? 0));
            $max = max($min, (int)($max ?? $min));
            return $max === $min ? $min : random_int($min, $max);
        }
    }

    return roll_free_box_dspoinc_amount($box);
}

/**
 * Load one store item by id.
 */
function fetch_store_item_row(PDO $pdo, int $storeItemId): ?array {
    return fetch_first_row_by_candidate_id($pdo, 'tbl_store_items', ['item_id', 'store_item_id', 'id'], $storeItemId);
}

/**
 * Load one genetic catalog row by id.
 */
function fetch_genetic_catalog_row(PDO $pdo, int $catalogId): ?array {
    return fetch_first_row_by_candidate_id($pdo, 'tbl_genetic_trait_catalog', ['catalog_id', 'trait_catalog_id', 'id'], $catalogId);
}

/**
 * Return true when the user already owns the exact genetic trait.
 */
function user_owns_genetic_trait(PDO $pdo, string $userId, string $traitType, string $traitValue): bool {
    if ($userId === '' || $traitType === '' || $traitValue === '' || !sqlite_table_exists($pdo, 'tbl_user_genetic_items')) {
        return false;
    }

    $stmt = $pdo->prepare("\n        SELECT genetic_item_id\n        FROM tbl_user_genetic_items\n        WHERE user_id = ?\n          AND trait_type = ?\n          AND trait_value = ?\n        LIMIT 1\n    ");
    $stmt->execute([$userId, $traitType, $traitValue]);

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Insert one genetic item history row when that table exists.
 */
function insert_genetic_item_history(PDO $pdo, ?int $geneticItemId, ?int $listingId, string $userId, string $actionType, array $oldValue, array $newValue, ?string $adminUserId = null): void {
    if (!sqlite_table_exists($pdo, 'tbl_genetic_item_history')) {
        return;
    }

    $stmt = $pdo->prepare("\n        INSERT INTO tbl_genetic_item_history (\n            genetic_item_id,\n            listing_id,\n            user_id,\n            action_type,\n            old_value_json,\n            new_value_json,\n            admin_user_id,\n            created_at\n        ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)\n    ");

    $stmt->execute([
        $geneticItemId,
        $listingId,
        $userId,
        $actionType,
        json_encode($oldValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        json_encode($newValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $adminUserId
    ]);
}

/**
 * Grant or stack one store item into user inventory using runtime column detection.
 */
function grant_store_item_to_user(PDO $pdo, string $userId, array $storeItem, int $quantity, string $sourceTag): array {
    if (!sqlite_table_exists($pdo, 'tbl_user_inventory')) {
        throw new Exception('tbl_user_inventory table not found');
    }

    $itemName = trim((string)($storeItem['item_name'] ?? $storeItem['name'] ?? $storeItem['title'] ?? ''));
    if ($itemName === '') {
        throw new Exception('Store reward is missing item_name');
    }

    $storeItemId = (int)($storeItem['item_id'] ?? $storeItem['store_item_id'] ?? $storeItem['id'] ?? 0);
    if ($storeItemId <= 0) {
        throw new Exception('Store reward is missing item_id');
    }

    $safeQuantity = max(1, $quantity);
    $description = trim((string)($storeItem['description'] ?? ''));
    $inventoryColumns = get_table_columns($pdo, 'tbl_user_inventory');

    /**
     * Look up existing inventory with the column that actually exists.
     * Production tbl_user_inventory uses item_id, not item_name.
     */
    if (in_array('item_id', $inventoryColumns, true)) {
        $select = $pdo->prepare("SELECT * FROM tbl_user_inventory WHERE user_id = ? AND item_id = ? LIMIT 1");
        $select->execute([$userId, $storeItemId]);
    } elseif (in_array('store_item_id', $inventoryColumns, true)) {
        $select = $pdo->prepare("SELECT * FROM tbl_user_inventory WHERE user_id = ? AND store_item_id = ? LIMIT 1");
        $select->execute([$userId, $storeItemId]);
    } elseif (in_array('item_name', $inventoryColumns, true)) {
        $select = $pdo->prepare("SELECT * FROM tbl_user_inventory WHERE user_id = ? AND item_name = ? LIMIT 1");
        $select->execute([$userId, $itemName]);
    } else {
        throw new Exception('tbl_user_inventory has no compatible item lookup column');
    }

    $existing = $select->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($existing) {
        $newQuantity = (int)($existing['quantity'] ?? 0) + $safeQuantity;
        $sets = ['quantity = ?'];
        $params = [$newQuantity];

        if (in_array('updated_at', $inventoryColumns, true)) {
            $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        }

        if (in_array('last_used_at', $inventoryColumns, true)) {
            // Keep last_used_at unchanged when granting new inventory.
        }

        $pkColumn = in_array('inventory_id', $inventoryColumns, true)
            ? 'inventory_id'
            : (in_array('user_inventory_id', $inventoryColumns, true) ? 'user_inventory_id' : 'id');

        $inventoryId = (int)($existing[$pkColumn] ?? 0);
        if ($inventoryId <= 0) {
            throw new Exception('Existing inventory row is missing primary key');
        }

        $params[] = $inventoryId;

        $stmt = $pdo->prepare("UPDATE tbl_user_inventory SET " . implode(', ', $sets) . " WHERE {$pkColumn} = ?");
        $stmt->execute($params);

        return [
            'inventory_id' => $inventoryId,
            'item_id' => $storeItemId,
            'item_name' => $itemName,
            'quantity' => $newQuantity,
            'description' => $description,
            'source' => $sourceTag
        ];
    }

    $insertValues = [];
    $placeholders = [];
    $params = [];

    $columnValueMap = [
        'user_id' => $userId,
        'item_name' => $itemName,
        'quantity' => $safeQuantity,
        'description' => $description,
        'item_id' => $storeItemId,
        'store_item_id' => $storeItemId,
        'source' => $sourceTag,
        'acquired_method' => $sourceTag,
        'is_used' => 0,
        'used' => 0
    ];

    foreach ($inventoryColumns as $column) {
        if (in_array($column, ['created_at', 'updated_at', 'acquired_at', 'timestamp'], true)) {
            $insertValues[] = $column;
            $placeholders[] = 'CURRENT_TIMESTAMP';
            continue;
        }

        if (!array_key_exists($column, $columnValueMap)) {
            continue;
        }

        $insertValues[] = $column;
        $placeholders[] = '?';
        $params[] = $columnValueMap[$column];
    }

    if (!in_array('user_id', $insertValues, true) || !in_array('quantity', $insertValues, true)) {
        throw new Exception('tbl_user_inventory is missing required user_id or quantity columns');
    }

    if (
        !in_array('item_id', $insertValues, true) &&
        !in_array('store_item_id', $insertValues, true) &&
        !in_array('item_name', $insertValues, true)
    ) {
        throw new Exception('tbl_user_inventory does not expose compatible item insert columns');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO tbl_user_inventory (' . implode(', ', $insertValues) . ') VALUES (' . implode(', ', $placeholders) . ')'
    );
    $stmt->execute($params);

    return [
        'inventory_id' => (int)$pdo->lastInsertId(),
        'item_id' => $storeItemId,
        'item_name' => $itemName,
        'quantity' => $safeQuantity,
        'description' => $description,
        'source' => $sourceTag
    ];
}

/**
 * Grant one user-bound genetic item from the catalog.
 */
function grant_genetic_trait_to_user(PDO $pdo, string $userId, array $catalogRow, string $sourceTag): array {
    if (!sqlite_table_exists($pdo, 'tbl_user_genetic_items')) {
        throw new Exception('tbl_user_genetic_items table not found');
    }

    $traitType = trim((string)($catalogRow['trait_type'] ?? ''));
    $traitValue = trim((string)($catalogRow['trait_value'] ?? ''));
    $displayTitle = trim((string)($catalogRow['display_title'] ?? $traitValue));
    $catalogId = (int)($catalogRow['catalog_id'] ?? $catalogRow['trait_catalog_id'] ?? $catalogRow['id'] ?? 0);

    if ($traitType === '' || $traitValue === '') {
        throw new Exception('Genetic reward catalog row is malformed');
    }

    $stmt = $pdo->prepare("\n        INSERT INTO tbl_user_genetic_items (\n            user_id,\n            catalog_id,\n            trait_type,\n            trait_value,\n            current_level,\n            upgrade_status,\n            acquired_method,\n            is_listed_for_sale,\n            created_at,\n            updated_at\n        ) VALUES (?, ?, ?, ?, 1, 'idle', ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)\n    ");
    $stmt->execute([
        $userId,
        $catalogId,
        $traitType,
        $traitValue,
        $sourceTag
    ]);

    $geneticItemId = (int)$pdo->lastInsertId();

    insert_genetic_item_history(
        $pdo,
        $geneticItemId,
        null,
        $userId,
        'reward_box_grant',
        [],
        [
            'catalog_id' => $catalogId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'acquired_method' => $sourceTag
        ],
        REWARD_BOX_SYSTEM_ACTOR
    );

    return [
        'genetic_item_id' => $geneticItemId,
        'catalog_id' => $catalogId,
        'trait_type' => $traitType,
        'trait_value' => $traitValue,
        'display_title' => $displayTitle,
        'current_level' => 1,
        'upgrade_status' => 'idle'
    ];
}

/**
 * Create one premium reward claim request using flexible runtime columns.
 */
function create_reward_claim_request(PDO $pdo, string $userId, int $boxId, array $poolEntry, array $box): int {
    if (!sqlite_table_exists($pdo, 'tbl_reward_claim_requests')) {
        throw new Exception('tbl_reward_claim_requests table not found');
    }

    $columns = get_table_columns($pdo, 'tbl_reward_claim_requests');
    $insertColumns = [];
    $placeholders = [];
    $params = [];

    $status = 'pending_review';
    $rewardTitle = trim((string)($poolEntry['reward_title'] ?? $box['box_name'] ?? 'Premium Reward Claim'));
    $rewardDescription = trim((string)($poolEntry['reward_description'] ?? $box['box_description'] ?? 'Premium reward claim created from Reward Chamber'));
    $rewardReferenceId = (int)(
    $poolEntry['reward_reference_id']
    ?? $poolEntry['genetic_catalog_id']
    ?? $poolEntry['catalog_id']
    ?? $poolEntry['store_item_id']
    ?? 0
);
    $poolRewardId = (int)($poolEntry['pool_reward_id'] ?? 0);

    $valueMap = [
        'user_id' => $userId,
        'box_id' => $boxId,
        'reward_pool_id' => $poolRewardId,
        'pool_reward_id' => $poolRewardId,
        'reward_type' => 'premium_claim',
        'reward_title' => $rewardTitle,
        'title' => $rewardTitle,
        'reward_description' => $rewardDescription,
        'description' => $rewardDescription,
        'reward_reference_id' => $rewardReferenceId > 0 ? $rewardReferenceId : null,
        'status' => $status,
        'claim_status' => $status,
        'box_name' => (string)($box['box_name'] ?? ''),
        'box_key' => (string)($box['box_key'] ?? ''),
        'price_dspoinc' => max(0, (int)($box['price_dspoinc'] ?? 0)),
        'box_price_dspoinc' => max(0, (int)($box['price_dspoinc'] ?? 0)),
        'requested_by' => $userId,
        'created_by' => $userId,
        'source' => 'reward_box',
        'request_source' => 'reward_box',
        'admin_notes' => 'Created automatically by Reward Chamber premium claim flow',
        'notes' => 'Created automatically by Reward Chamber premium claim flow'
    ];

    foreach ($columns as $column) {
        if (in_array($column, ['created_at', 'updated_at', 'requested_at'], true)) {
            $insertColumns[] = $column;
            $placeholders[] = 'CURRENT_TIMESTAMP';
            continue;
        }

        if (!array_key_exists($column, $valueMap)) {
            continue;
        }

        $insertColumns[] = $column;
        $placeholders[] = '?';
        $params[] = $valueMap[$column];
    }

    if (empty($insertColumns)) {
        throw new Exception('tbl_reward_claim_requests does not expose compatible insert columns');
    }

    $stmt = $pdo->prepare('INSERT INTO tbl_reward_claim_requests (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')');
    $stmt->execute($params);

    return (int)$pdo->lastInsertId();
}

/**
 * Update one open history row with the selected pool reward id after insert.
 */
function attach_reward_box_open_history_pool_reward(PDO $pdo, int $openId, ?int $poolRewardId): void {
    if ($openId < 1 || !$poolRewardId) {
        return;
    }

    $stmt = $pdo->prepare("UPDATE tbl_reward_box_open_history SET rolled_pool_reward_id = ?, updated_at = CURRENT_TIMESTAMP WHERE open_id = ?");
    $stmt->execute([$poolRewardId, $openId]);
}

/**
 * Apply one negative DSPOINC spend to the canonical ledger + audit trail.
 */
function insert_reward_box_spend(PDO $pdo, string $userId, int $amount, array $box): void {
    if ($amount <= 0) {
        return;
    }

    insert_dspoinc_change(
        $pdo,
        $userId,
        -1 * $amount,
        REWARD_BOX_GAME_KEY,
        'Reward box spend: ' . ((string)($box['box_key'] ?? 'box'))
    );

    insert_score_adjustment(
    $pdo,
    $userId,
    REWARD_BOX_SYSTEM_ACTOR,
    -abs($amount),
    'remove',
    'Reward box spend: ' . ((string)($box['box_name'] ?? 'Reward Box'))
);
}
/**
 * Build the response box payload in the same shape as get-reward-boxes.php.
 */
function build_box_payload(PDO $pdo, array $box, string $userId, int $availableDspoinc, string $nowUtc): array {
    $boxId = (int)($box['box_id'] ?? 0);
    $userState = fetch_user_box_state($pdo, $boxId, $userId);
    $poolSummary = fetch_reward_pool_summary($pdo, $boxId);
    $disabledState = calculate_disabled_state($box, $userState, $userId, $availableDspoinc, $nowUtc);

    return [
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

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $userId = resolve_user_id();
    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    $request = get_request_data();
    $boxId = (int)($request['box_id'] ?? 0);

    if ($boxId < 1) {
        json_response([
            'success' => false,
            'error' => 'Missing or invalid box_id'
        ], 400);
    }

    $pdo = get_reward_boxes_database_connection();
    $nowUtc = gmdate('Y-m-d H:i:s');

    $box = fetch_reward_box($pdo, $boxId);
    if (!$box) {
        json_response([
            'success' => false,
            'error' => 'Reward box not found'
        ], 404);
    }

    if ((int)($box['is_visible'] ?? 0) !== 1) {
        json_response([
            'success' => false,
            'error' => 'This reward box is not currently available'
        ], 404);
    }

    $boxType = trim((string)($box['box_type'] ?? ''));
    if (!in_array($boxType, ['free_dspoinc_box', 'paid_random_box', 'premium_claim_box'], true)) {
        json_response([
            'success' => false,
            'error' => 'Unsupported reward box type',
            'data' => [
                'box_id' => $boxId,
                'box_type' => $boxType
            ]
        ], 409);
    }

    $availableDspoinc = get_user_available_dspoinc($pdo, $userId);
    $userState = fetch_user_box_state($pdo, $boxId, $userId);
    $disabledState = calculate_disabled_state($box, $userState, $userId, $availableDspoinc, $nowUtc);

    if ((bool)$disabledState['is_disabled']) {
        json_response([
            'success' => false,
            'error' => (string)($disabledState['disabled_reason'] ?? 'This reward box cannot be opened right now'),
            'data' => [
                'box' => build_box_payload($pdo, $box, $userId, $availableDspoinc, $nowUtc)
            ]
        ], 409);
    }

    $pdo->beginTransaction();

    // Re-load all mutable state inside the transaction to avoid stale decisions.
    $box = fetch_reward_box($pdo, $boxId);
    if (!$box) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Reward box not found'
        ], 404);
    }

    $openedAtUtc = gmdate('Y-m-d H:i:s');
    $availableDspoinc = get_user_available_dspoinc($pdo, $userId);
    $userState = fetch_user_box_state($pdo, $boxId, $userId);
    $disabledState = calculate_disabled_state($box, $userState, $userId, $availableDspoinc, $openedAtUtc);

    if ((bool)($disabledState['is_disabled'] ?? false)) {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => (string)($disabledState['disabled_reason'] ?? 'This reward box cannot be opened right now'),
            'data' => [
                'box' => build_box_payload($pdo, $box, $userId, $availableDspoinc, $openedAtUtc)
            ]
        ], 409);
    }

    $boxPrice = max(0, (int)($box['price_dspoinc'] ?? 0));

    $rewardType = 'dspoinc';
    $rewardTitle = 'DSPOINC Reward';
    $rewardReferenceId = null;
    $rewardAmount = null;
    $claimRequestId = null;
    $usedFallback = false;
    $usedReroll = false;
    $poolRewardId = null;
    $openStatus = REWARD_BOX_STATUS_GRANTED;
    $deliveryPayload = [];
    $rewardDescription = trim((string)($box['box_description'] ?? ''));

    /**
     * TODO: The admin Reward Chamber config UI should edit only DB-backed values
     * such as pool rows, weights, prices, fallback ranges, visibility, and active flags.
     * Frontend/admin must never become the authority for reward decisions.
     */

    /**
     * Spend box price safely.
     */
    $ensureAndSpendBoxPrice = function () use ($pdo, $userId, $boxPrice, $availableDspoinc, $box) {
        if ($boxPrice <= 0) {
            return;
        }

        if ($availableDspoinc < $boxPrice) {
            $pdo->rollBack();
            json_response([
                'success' => false,
                'error' => 'Not enough DSPOINC',
                'data' => [
                    'price_dspoinc' => $boxPrice,
                    'balance_dspoinc' => $availableDspoinc,
                    'missing_dspoinc' => ($boxPrice - $availableDspoinc)
                ]
            ], 409);
        }

        insert_reward_box_spend($pdo, $userId, $boxPrice, $box);
    };

    /**
     * Deliver a DSPOINC reward safely.
     */
    $grantDspoincReward = function (int $amount, string $sourceTitle) use ($pdo, $userId, $box) {
        if ($amount <= 0) {
            throw new Exception('Reward DSPOINC amount must be greater than zero');
        }

        insert_dspoinc_change(
            $pdo,
            $userId,
            $amount,
            REWARD_BOX_GAME_KEY,
            $sourceTitle
        );

        insert_score_adjustment(
            $pdo,
            $userId,
            REWARD_BOX_SYSTEM_ACTOR,
            $amount,
            'add',
            'Reward box credit: ' . ((string)($box['box_name'] ?? 'Reward Box'))
        );
    };

    /**
     * TODO: Confirm reward pool source columns for genetic_trait/store_item remain stable across local and production DB snapshots.
     * Try to resolve one paid_random_box reward entry into a deliverable reward.
     * Returns a normalized payload array.
     */
    $resolvePoolEntry = function (array $entry) use ($pdo, $userId, $box) {
        $resolvedRewardType = trim((string)($entry['reward_type'] ?? ''));
        $resolvedRewardTitle = trim((string)($entry['reward_title'] ?? '')) ?: 'Reward Box Grant';
        $resolvedRewardReferenceId = null;
        $resolvedRewardAmount = null;
        $resolvedDeliveryPayload = [];

        if ($resolvedRewardType === 'store_item') {
            $storeItemId = (int)($entry['store_item_id'] ?: $entry['reward_reference_id']);
            if ($storeItemId <= 0) {
                throw new Exception('Reward pool store item reference is invalid');
            }

            $storeItem = fetch_store_item_row($pdo, $storeItemId);
            if (!$storeItem) {
                throw new Exception('Reward pool store item could not be loaded');
            }

            $resolvedRewardReferenceId = (int)($storeItem['item_id'] ?? $storeItem['store_item_id'] ?? $storeItem['id'] ?? $storeItemId);
            $resolvedRewardTitle = trim((string)($entry['reward_title'] ?? $storeItem['item_name'] ?? $storeItem['name'] ?? 'Store Reward')) ?: 'Store Reward';
            $resolvedDeliveryPayload = grant_store_item_to_user($pdo, $userId, $storeItem, 1, 'reward_box');

            return [
                'reward_type' => 'store_item',
                'reward_title' => $resolvedRewardTitle,
                'reward_reference_id' => $resolvedRewardReferenceId,
                'reward_amount' => null,
                'reward_description' => trim((string)($entry['reward_description'] ?? $storeItem['description'] ?? $box['box_description'] ?? '')),
                'delivery_payload' => $resolvedDeliveryPayload
            ];
        }

        if ($resolvedRewardType === 'genetic_trait') {
    $catalogId = (int)(
        $entry['genetic_catalog_id']
        ?? $entry['catalog_id']
        ?? $entry['reward_reference_id']
        ?? 0
    );

    if ($catalogId <= 0) {
        throw new Exception('Reward pool genetic trait reference is invalid');
    }

            $catalogRow = fetch_genetic_catalog_row($pdo, $catalogId);
            if (!$catalogRow) {
                throw new Exception('Reward pool genetic trait could not be loaded');
            }

            $traitType = trim((string)($catalogRow['trait_type'] ?? ''));
            $traitValue = trim((string)($catalogRow['trait_value'] ?? ''));

            if ($traitType === '' || $traitValue === '') {
                throw new Exception('Reward pool genetic trait is missing trait_type or trait_value');
            }

            if (user_owns_genetic_trait($pdo, $userId, $traitType, $traitValue)) {
                return [
                    'reward_type' => 'duplicate_genetic_trait',
                    'reward_title' => $resolvedRewardTitle,
                    'reward_reference_id' => null,
                    'reward_amount' => null,
                    'reward_description' => trim((string)($entry['reward_description'] ?? $box['box_description'] ?? '')),
                    'delivery_payload' => []
                ];
            }

            $resolvedRewardReferenceId = (int)($catalogRow['catalog_id'] ?? $catalogRow['trait_catalog_id'] ?? $catalogId);
            $resolvedRewardTitle = trim((string)($entry['reward_title'] ?? $catalogRow['display_title'] ?? $traitValue)) ?: 'Genetic Reward';
            $resolvedDeliveryPayload = grant_genetic_trait_to_user($pdo, $userId, $catalogRow, 'reward_box');

            return [
                'reward_type' => 'genetic_trait',
                'reward_title' => $resolvedRewardTitle,
                'reward_reference_id' => $resolvedRewardReferenceId,
                'reward_amount' => null,
                'reward_description' => trim((string)($entry['reward_description'] ?? $catalogRow['description'] ?? $box['box_description'] ?? '')),
                'delivery_payload' => $resolvedDeliveryPayload
            ];
        }

        if ($resolvedRewardType === 'dspoinc') {
            $resolvedRewardAmount = roll_reward_box_fallback_dspoinc_amount($box, $entry);
            $resolvedRewardTitle = trim((string)($entry['reward_title'] ?? 'DSPOINC Reward')) ?: 'DSPOINC Reward';

            return [
                'reward_type' => 'dspoinc',
                'reward_title' => $resolvedRewardTitle,
                'reward_reference_id' => null,
                'reward_amount' => $resolvedRewardAmount,
                'reward_description' => trim((string)($entry['reward_description'] ?? $box['box_description'] ?? '')),
                'delivery_payload' => []
            ];
        }

        throw new Exception('Unsupported reward type in reward pool: ' . $resolvedRewardType);
    };

    if ($boxType === 'free_dspoinc_box') {
        $rewardAmount = roll_free_box_dspoinc_amount($box);
        if ((int)$rewardAmount <= 0) {
            $pdo->rollBack();
            json_response([
                'success' => false,
                'error' => 'Free reward box reward range is invalid'
            ], 500);
        }

        $rewardDescription = trim((string)($box['box_description'] ?? 'Free DSPOINC reward from Reward Chamber'));
        $grantDspoincReward(
            (int)$rewardAmount,
            'Free reward box: ' . ((string)($box['box_key'] ?? 'box_' . $boxId))
        );
    } elseif ($boxType === 'paid_random_box' || $boxType === 'free_random_box') {
    if ($boxType === 'paid_random_box' && $boxPrice <= 0) {
            $pdo->rollBack();
            json_response([
                'success' => false,
                'error' => 'Invalid paid box price configuration'
            ], 400);
        }

        $poolEntries = fetch_reward_pool_entries($pdo, $boxId);
        $canFallback = (int)($box['fallback_dspoinc_enabled'] ?? 0) === 1;

        if (empty($poolEntries) && !$canFallback) {
            $pdo->rollBack();
            json_response([
                'success' => false,
                'error' => 'Reward pool is empty for this box'
            ], 400);
        }

        $ensureAndSpendBoxPrice();

        $selectedEntry = null;
        if (!should_roll_reward_box_fallback($box, $poolEntries)) {
            $selectedEntry = pick_weighted_reward_entry($poolEntries);
        } else {
            $usedFallback = true;
        }

        if (!$selectedEntry && !$usedFallback) {
            if (!$canFallback) {
                $pdo->rollBack();
                json_response([
                    'success' => false,
                    'error' => 'Unable to select a reward from the pool'
                ], 500);
            }
            $usedFallback = true;
        }

        if ($selectedEntry && !$usedFallback) {
            $poolRewardId = (int)($selectedEntry['pool_reward_id'] ?? 0);

            try {
                $resolved = $resolvePoolEntry($selectedEntry);

                if ($resolved['reward_type'] === 'duplicate_genetic_trait') {
                    $usedReroll = true;

                    $rerollEntry = pick_weighted_reward_entry($poolEntries, [$poolRewardId]);
                    if ($rerollEntry) {
                        $selectedEntry = $rerollEntry;
                        $poolRewardId = (int)($selectedEntry['pool_reward_id'] ?? 0);
                        $resolved = $resolvePoolEntry($selectedEntry);

                        if ($resolved['reward_type'] === 'duplicate_genetic_trait') {
                            $usedFallback = true;
                        }
                    } else {
                        $usedFallback = true;
                    }
                }

                if (!$usedFallback && $resolved['reward_type'] !== 'duplicate_genetic_trait') {
                    $rewardType = $resolved['reward_type'];
                    $rewardTitle = $resolved['reward_title'];
                    $rewardReferenceId = $resolved['reward_reference_id'];
                    $rewardAmount = $resolved['reward_amount'];
                    $rewardDescription = trim((string)($resolved['reward_description'] ?? $rewardDescription));
                    $deliveryPayload = $resolved['delivery_payload'];
                }
            } catch (Exception $poolResolutionError) {
                if (!$canFallback) {
                    throw $poolResolutionError;
                }

                $usedFallback = true;
            }
            error_log(
    '🎁 Reward Box Pool Resolution Failed: box_id=' . $boxId .
    ' pool_reward_id=' . $poolRewardId .
    ' reward_type=' . (string)($selectedEntry['reward_type'] ?? '') .
    ' error=' . $poolResolutionError->getMessage()
);
        }

        if ($usedFallback) {
            if (!$canFallback) {
                throw new Exception('No deliverable paid_random_box reward and fallback is disabled');
            }

            $rewardType = 'dspoinc';
            $rewardTitle = 'Fallback DSPOINC Reward';
            $rewardReferenceId = null;
            $rewardAmount = roll_reward_box_fallback_dspoinc_amount($box, null);
            $rewardDescription = trim((string)($box['box_description'] ?? 'Fallback DSPOINC reward from Reward Chamber'));
            $deliveryPayload = [];
        }

        if ($rewardType === 'dspoinc') {
            if ($rewardDescription === '') {
                $rewardDescription = trim((string)($box['box_description'] ?? 'DSPOINC reward from Reward Chamber'));
            }
            $rewardAmount = (int)($rewardAmount ?? 0);
            if ($rewardAmount <= 0) {
                throw new Exception('Fallback DSPOINC reward range is invalid');
            }

            $grantDspoincReward(
                $rewardAmount,
                'Reward box grant: ' . ((string)($box['box_key'] ?? 'box_' . $boxId))
            );
        }
    } elseif ($boxType === 'premium_claim_box') {
        if ($boxPrice <= 0) {
            $pdo->rollBack();
            json_response([
                'success' => false,
                'error' => 'Invalid premium claim box price configuration'
            ], 400);
        }

        $ensureAndSpendBoxPrice();

        $poolEntries = fetch_reward_pool_entries($pdo, $boxId);
        $selectedEntry = pick_weighted_reward_entry($poolEntries);

        if (!$selectedEntry) {
            $pdo->rollBack();
            json_response([
                'success' => false,
                'error' => 'No premium claim reward is configured for this box'
            ], 500);
        }

        $poolRewardId = (int)($selectedEntry['pool_reward_id'] ?? 0);
        $rewardType = 'premium_claim';
        $rewardTitle = trim((string)($selectedEntry['reward_title'] ?? $box['box_name'] ?? 'Premium Claim Reward')) ?: 'Premium Claim Reward';
        $rewardReferenceId = (int)($selectedEntry['reward_reference_id'] ?? $selectedEntry['catalog_id'] ?? $selectedEntry['store_item_id'] ?? 0) ?: null;
        $rewardDescription = trim((string)($selectedEntry['reward_description'] ?? $box['box_description'] ?? 'Premium reward claim created from Reward Chamber'));

        // TODO: Confirm tbl_reward_claim_requests final production schema/columns if this branch is expanded further.
        $claimRequestId = create_reward_claim_request($pdo, $userId, $boxId, $selectedEntry, $box);
        if (!$claimRequestId) {
            throw new Exception('Premium claim request could not be created');
        }

        $openStatus = REWARD_BOX_STATUS_PENDING;
    } else {
        $pdo->rollBack();
        json_response([
            'success' => false,
            'error' => 'Unsupported reward box type: ' . $boxType
        ], 400);
    }

    $nextOpenAt = calculate_next_open_at($box, $openedAtUtc);
    $openId = insert_reward_box_open_history(
        $pdo,
        $boxId,
        $userId,
        $boxPrice,
        $rewardType,
        $rewardTitle,
        $rewardReferenceId,
        $rewardType === 'dspoinc' ? (int)$rewardAmount : null,
        $usedFallback,
        $usedReroll,
        $claimRequestId,
        $openStatus === REWARD_BOX_STATUS_PENDING ? 'pending' : 'completed'
    );
    attach_reward_box_open_history_pool_reward($pdo, $openId, $poolRewardId);

    upsert_reward_box_user_state(
        $pdo,
        $boxId,
        $userId,
        ((int)($userState['open_count'] ?? 0)) + 1,
        $openedAtUtc,
        $nextOpenAt,
        $rewardType,
        $rewardTitle,
        $rewardReferenceId,
        $rewardType === 'dspoinc' ? (int)$rewardAmount : null
    );

    increment_reward_box_global_open_count($pdo, $boxId);
    $pdo->commit();

    $refreshedBox = fetch_reward_box($pdo, $boxId);
    $refreshedBalance = get_user_available_dspoinc($pdo, $userId);
    $responseNowUtc = gmdate('Y-m-d H:i:s');

    json_response([
        'success' => true,
        'message' => 'Reward box opened successfully',
        'data' => [
            'user_id' => $userId,
            'server_time_utc' => $responseNowUtc,
            'available_dspoinc' => $refreshedBalance,
            'new_available_dspoinc' => $refreshedBalance,
            'open_result' => [
                'open_id' => $openId,
                'box_id' => $boxId,
                'box_key' => (string)($refreshedBox['box_key'] ?? ''),
                'box_name' => (string)($refreshedBox['box_name'] ?? ''),
                'box_type' => (string)($refreshedBox['box_type'] ?? ''),
                'reward_type' => $rewardType,
                'reward_title' => $rewardTitle,
                'reward_reference_id' => $rewardReferenceId,
                'reward_description' => $rewardDescription,
                'reward_dspoinc_amount' => $rewardType === 'dspoinc' ? (int)$rewardAmount : null,
                'new_available_dspoinc' => $refreshedBalance,
                'used_fallback_dspoinc' => $usedFallback,
                'used_reroll' => $usedReroll,
                'claim_request_id' => $claimRequestId,
                'opened_at' => $openedAtUtc,
                'next_open_at' => $nextOpenAt,
                'open_status' => $openStatus,
                'delivery_payload' => $deliveryPayload
            ],
            'box' => build_box_payload($pdo, $refreshedBox ?: $box, $userId, $refreshedBalance, $responseNowUtc)
        ]
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🎁 Open Reward Box PDO ERROR: ' . $e->getMessage());

    json_response([
    'success' => false,
    'error' => $e->getMessage(),
    'type' => 'PDOException',
    'line' => $e->getLine()
], 500);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('🎁 Open Reward Box ERROR: ' . $e->getMessage());

    json_response([
    'success' => false,
    'error' => $e->getMessage(),
    'type' => 'Throwable',
    'line' => $e->getLine()
], 500);
}
