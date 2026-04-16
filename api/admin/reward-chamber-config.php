<?php
// 🎁 Reward Chamber Admin Config API
// Admin configuration layer for Narrrfs World Reward Chamber boxes and reward pools.
// This endpoint intentionally edits config only and does not execute reward delivery.

declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');

date_default_timezone_set('UTC');

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$databaseIncludeCandidates = [
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../config/database.php',
    __DIR__ . '/config/database.php'
];

foreach ($databaseIncludeCandidates as $databaseIncludePath) {
    if (file_exists($databaseIncludePath)) {
        require_once $databaseIncludePath;
        break;
    }
}

$discordSecretCandidates = [
    __DIR__ . '/../../config/discord-secret.php',
    __DIR__ . '/../config/discord-secret.php',
    __DIR__ . '/config/discord-secret.php'
];

foreach ($discordSecretCandidates as $discordSecretPath) {
    if (file_exists($discordSecretPath)) {
        include_once $discordSecretPath;
        break;
    }
}

session_start();

function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
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

function require_admin_actor(array $request): string {
    $givenBy = trim((string)($request['given_by'] ?? ''));

    $sessionDiscordId = trim((string)($_SESSION['discord_id'] ?? ''));
    $sessionAdminLoggedIn = !empty($_SESSION['admin_logged_in']);
    $sessionIsModerator = !empty($_SESSION['is_moderator']);
    $sessionIsAdmin = !empty($_SESSION['is_admin']);
    $sessionUsername = trim((string)($_SESSION['admin_username'] ?? $_SESSION['username'] ?? ''));

    $hasAdminSession =
        $sessionAdminLoggedIn ||
        $sessionIsAdmin ||
        $sessionIsModerator ||
        $sessionDiscordId !== '';

    if (!$hasAdminSession) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized admin access'
        ], 403);
    }

    if ($givenBy !== '') {
        return $givenBy;
    }

    if ($sessionUsername !== '') {
        return $sessionUsername;
    }

    if ($sessionDiscordId !== '') {
        return $sessionDiscordId;
    }

    return 'admin_session';
}

function is_localhost_request(): bool {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

function get_reward_chamber_database_connection(): PDO {
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

    if (is_localhost_request()) {
        $dbPathCandidates[] = __DIR__ . '/../../db/narrrf_world.sqlite';
        $dbPathCandidates[] = 'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite';
        $dbPathCandidates[] = __DIR__ . '/db/narrrf_world.sqlite';
    }

    $dbPathCandidates[] = __DIR__ . '/../../db/narrrf_world.sqlite';
    $dbPathCandidates[] = '/var/www/html/db/narrrf_world.sqlite';
    $dbPathCandidates[] = __DIR__ . '/db/narrrf_world.sqlite';

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

function sqlite_table_exists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1");
    $stmt->execute([$tableName]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

function to_nullable_int($value): ?int {
    if ($value === null || $value === '') {
        return null;
    }
    return (int)$value;
}

function to_nullable_float($value): ?float {
    if ($value === null || $value === '') {
        return null;
    }
    return (float)$value;
}

function normalize_datetime_or_null($value): ?string {
    $text = trim((string)$value);
    return $text === '' ? null : $text;
}

function fetch_reward_boxes(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM tbl_reward_boxes ORDER BY sort_order ASC, box_id ASC");
    return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
}

function fetch_reward_pool_rows(PDO $pdo): array {
    if (!sqlite_table_exists($pdo, 'tbl_reward_box_reward_pool')) {
        return [];
    }

    $stmt = $pdo->query("SELECT * FROM tbl_reward_box_reward_pool ORDER BY box_id ASC, sort_order ASC, pool_reward_id ASC");
    return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
}

function fetch_store_item_references(PDO $pdo): array {
    if (!sqlite_table_exists($pdo, 'tbl_store_items')) {
        return [];
    }

    $stmt = $pdo->query("SELECT item_id, item_name, description, price, is_active FROM tbl_store_items ORDER BY is_active DESC, item_name ASC");
    return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
}

function fetch_genetic_catalog_references(PDO $pdo): array {
    if (!sqlite_table_exists($pdo, 'tbl_genetic_trait_catalog')) {
        return [];
    }

    $stmt = $pdo->query("SELECT catalog_id, trait_type, trait_value, display_title, base_price_dspoinc, is_active, is_visible FROM tbl_genetic_trait_catalog ORDER BY LOWER(COALESCE(display_title, trait_value, '')) ASC");
    return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
}

function get_config_payload(PDO $pdo): array {
    return [
        'boxes' => fetch_reward_boxes($pdo),
        'pool_rows' => fetch_reward_pool_rows($pdo),
        'references' => [
            'store_items' => fetch_store_item_references($pdo),
            'genetic_catalog' => fetch_genetic_catalog_references($pdo)
        ]
    ];
}

function save_box_config(PDO $pdo, array $request): array {
    $boxId = (int)($request['box_id'] ?? 0);
    if ($boxId < 1) {
        throw new Exception('box_id is required');
    }

    $boxName = trim((string)($request['box_name'] ?? ''));
    if ($boxName === '') {
        throw new Exception('box_name is required');
    }

    $fallbackMin = to_nullable_int($request['fallback_dspoinc_min'] ?? null);
    $fallbackMax = to_nullable_int($request['fallback_dspoinc_max'] ?? null);
    if ($fallbackMin !== null && $fallbackMax !== null && $fallbackMax < $fallbackMin) {
        throw new Exception('fallback_dspoinc_max cannot be lower than fallback_dspoinc_min');
    }

    $stmt = $pdo->prepare("
        UPDATE tbl_reward_boxes
        SET
            box_name = ?,
            box_description = ?,
            price_dspoinc = ?,
            is_active = ?,
            is_visible = ?,
            sort_order = ?,
            cooldown_enabled = ?,
            cooldown_type = ?,
            cooldown_hours = ?,
            reward_mode = ?,
            fallback_dspoinc_enabled = ?,
            fallback_dspoinc_min = ?,
            fallback_dspoinc_max = ?,
            item_pool_roll_chance = ?,
            fallback_dspoinc_roll_chance = ?,
            global_stock_limit = ?,
            max_opens_per_user = ?,
            start_at = ?,
            end_at = ?,
            disabled_reason_text = ?,
            visual_theme = ?,
            image_path = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE box_id = ?
    ");

    $stmt->execute([
        $boxName,
        trim((string)($request['box_description'] ?? '')),
        max(0, (int)($request['price_dspoinc'] ?? 0)),
        !empty($request['is_active']) ? 1 : 0,
        !empty($request['is_visible']) ? 1 : 0,
        (int)($request['sort_order'] ?? 0),
        !empty($request['cooldown_enabled']) ? 1 : 0,
        trim((string)($request['cooldown_type'] ?? 'none')) ?: 'none',
        max(0, (float)($request['cooldown_hours'] ?? 0)),
        trim((string)($request['reward_mode'] ?? 'dspoinc_only')) ?: 'dspoinc_only',
        !empty($request['fallback_dspoinc_enabled']) ? 1 : 0,
        $fallbackMin,
        $fallbackMax,
        to_nullable_float($request['item_pool_roll_chance'] ?? null),
        to_nullable_float($request['fallback_dspoinc_roll_chance'] ?? null),
        to_nullable_int($request['global_stock_limit'] ?? null),
        to_nullable_int($request['max_opens_per_user'] ?? null),
        normalize_datetime_or_null($request['start_at'] ?? null),
        normalize_datetime_or_null($request['end_at'] ?? null),
        normalize_datetime_or_null($request['disabled_reason_text'] ?? null),
        normalize_datetime_or_null($request['visual_theme'] ?? null),
        normalize_datetime_or_null($request['image_path'] ?? null),
        $boxId
    ]);

    return ['box_id' => $boxId];
}

function save_pool_row(PDO $pdo, array $request): array {
    $boxId = (int)($request['box_id'] ?? 0);
    if ($boxId < 1) {
        throw new Exception('box_id is required');
    }

    $rewardType = trim((string)($request['reward_type'] ?? ''));
    $validTypes = ['store_item', 'genetic_trait', 'dspoinc', 'premium_claim'];
    if (!in_array($rewardType, $validTypes, true)) {
        throw new Exception('reward_type is invalid');
    }

    $storeItemId = to_nullable_int($request['store_item_id'] ?? null);
    $geneticCatalogId = to_nullable_int($request['genetic_catalog_id'] ?? null);
    $fixedDspoincAmount = to_nullable_int($request['fixed_dspoinc_amount'] ?? null);
    $dspoincMin = to_nullable_int($request['dspoinc_min'] ?? null);
    $dspoincMax = to_nullable_int($request['dspoinc_max'] ?? null);

    if (($rewardType === 'store_item' || $rewardType === 'premium_claim') && $storeItemId === null) {
        throw new Exception('store_item_id is required for this reward type');
    }

    if ($rewardType === 'genetic_trait' && $geneticCatalogId === null) {
        throw new Exception('genetic_catalog_id is required for genetic_trait');
    }

    if ($rewardType === 'dspoinc') {
        $hasFixed = $fixedDspoincAmount !== null && $fixedDspoincAmount > 0;
        $hasRange = $dspoincMin !== null && $dspoincMax !== null;
        if (!$hasFixed && !$hasRange) {
            throw new Exception('dspoinc rewards require fixed_dspoinc_amount or dspoinc_min + dspoinc_max');
        }
        if ($hasRange && $dspoincMax < $dspoincMin) {
            throw new Exception('dspoinc_max cannot be lower than dspoinc_min');
        }
    }

    $metadataJson = trim((string)($request['metadata_json'] ?? ''));
    if ($metadataJson !== '') {
        json_decode($metadataJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('metadata_json must be valid JSON');
        }
    }

    $poolRewardId = (int)($request['pool_reward_id'] ?? 0);
    $commonValues = [
        $boxId,
        $rewardType,
        trim((string)($request['reward_title'] ?? '')),
        trim((string)($request['reward_description'] ?? '')),
        $geneticCatalogId,
        $storeItemId,
        $fixedDspoincAmount,
        $dspoincMin,
        $dspoincMax,
        max(1, (int)($request['weight'] ?? 1)),
        !empty($request['is_active']) ? 1 : 0,
        (int)($request['sort_order'] ?? 0),
        $metadataJson === '' ? null : $metadataJson
    ];

    if ($poolRewardId > 0) {
        $stmt = $pdo->prepare("
            UPDATE tbl_reward_box_reward_pool
            SET
                box_id = ?,
                reward_type = ?,
                reward_title = ?,
                reward_description = ?,
                genetic_catalog_id = ?,
                store_item_id = ?,
                fixed_dspoinc_amount = ?,
                dspoinc_min = ?,
                dspoinc_max = ?,
                weight = ?,
                is_active = ?,
                sort_order = ?,
                metadata_json = ?
            WHERE pool_reward_id = ?
        ");
        $executeValues = $commonValues;
        $executeValues[] = $poolRewardId;
        $stmt->execute($executeValues);
        return ['pool_reward_id' => $poolRewardId, 'mode' => 'updated'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO tbl_reward_box_reward_pool (
            box_id,
            reward_type,
            reward_title,
            reward_description,
            genetic_catalog_id,
            store_item_id,
            fixed_dspoinc_amount,
            dspoinc_min,
            dspoinc_max,
            weight,
            is_active,
            sort_order,
            metadata_json
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute($commonValues);

    return ['pool_reward_id' => (int)$pdo->lastInsertId(), 'mode' => 'inserted'];
}

function toggle_pool_row(PDO $pdo, array $request): array {
    $poolRewardId = (int)($request['pool_reward_id'] ?? 0);
    if ($poolRewardId < 1) {
        throw new Exception('pool_reward_id is required');
    }

    $isActive = !empty($request['is_active']) ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE tbl_reward_box_reward_pool SET is_active = ? WHERE pool_reward_id = ?');
    $stmt->execute([$isActive, $poolRewardId]);

    return ['pool_reward_id' => $poolRewardId, 'is_active' => $isActive];
}

function soft_delete_pool_row(PDO $pdo, array $request): array {
    $poolRewardId = (int)($request['pool_reward_id'] ?? 0);
    if ($poolRewardId < 1) {
        throw new Exception('pool_reward_id is required');
    }

    $stmt = $pdo->prepare('UPDATE tbl_reward_box_reward_pool SET is_active = 0 WHERE pool_reward_id = ?');
    $stmt->execute([$poolRewardId]);

    return ['pool_reward_id' => $poolRewardId, 'is_active' => 0, 'soft_disabled' => true];
}

try {
    $request = get_request_data();
    require_admin_actor($request);
    $pdo = get_reward_chamber_database_connection();

    if (!sqlite_table_exists($pdo, 'tbl_reward_boxes')) {
        throw new Exception('tbl_reward_boxes table not found');
    }

    $action = trim((string)($request['action'] ?? ($_GET['action'] ?? 'get_config')));

    if ($action === 'get_config') {
        json_response([
            'success' => true,
            'data' => get_config_payload($pdo)
        ]);
    }

    $pdo->beginTransaction();

    switch ($action) {
        case 'save_box':
            $result = save_box_config($pdo, $request);
            break;
        case 'save_pool_row':
            $result = save_pool_row($pdo, $request);
            break;
        case 'toggle_pool_row':
            $result = toggle_pool_row($pdo, $request);
            break;
        case 'delete_pool_row':
            $result = soft_delete_pool_row($pdo, $request);
            break;
        default:
            throw new Exception('Unsupported action');
    }

    $pdo->commit();

    json_response([
        'success' => true,
        'action' => $action,
        'result' => $result,
        'data' => get_config_payload($pdo)
    ]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'success' => false,
        'error' => $error->getMessage()
    ], 500);
}
