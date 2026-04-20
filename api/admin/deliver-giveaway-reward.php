<?php
// 🎁 Deliver Giveaway Reward API
// Backend-authoritative structured giveaway delivery endpoint.
// Supports:
// - store_item -> tbl_user_inventory
// - genetic_trait -> tbl_user_genetic_items

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

function get_authorization_header(): string {
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim((string)$_SERVER['HTTP_AUTHORIZATION']);
    }

    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return trim((string)$_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    if (!empty($_SERVER['Authorization'])) {
        return trim((string)$_SERVER['Authorization']);
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strtolower((string)$key) === 'authorization') {
                    return trim((string)$value);
                }
            }
        }
    }

    return '';
}

function get_internal_api_secret(): string {
    $candidates = [
        getenv('API_SECRET') ?: '',
        $_ENV['API_SECRET'] ?? '',
        $_SERVER['API_SECRET'] ?? '',
        getenv('INTERNAL_API_SECRET') ?: '',
        $_ENV['INTERNAL_API_SECRET'] ?? '',
        $_SERVER['INTERNAL_API_SECRET'] ?? '',
        getenv('DISCORD_SECRET') ?: '',
        $_ENV['DISCORD_SECRET'] ?? '',
        $_SERVER['DISCORD_SECRET'] ?? '',
        getenv('DISCORD_BOT_SECRET') ?: '',
        $_ENV['DISCORD_BOT_SECRET'] ?? '',
        $_SERVER['DISCORD_BOT_SECRET'] ?? ''
    ];

    foreach ($candidates as $candidate) {
        $candidate = trim((string)$candidate);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
}

function assert_internal_authorization(): void {
    $expectedToken = trim((string)get_internal_api_secret());
    if ($expectedToken === '') {
        json_response(['success' => false, 'error' => 'Internal auth secret missing on server'], 500);
    }

    $authHeader = get_authorization_header();
    $providedToken = '';

    if (stripos($authHeader, 'Bearer ') === 0) {
        $providedToken = trim(substr($authHeader, 7));
    }

    if ($providedToken === '') {
        $request = get_request_data();
        $providedToken = trim((string)($request['internal_secret'] ?? $request['api_secret'] ?? ''));
    }

    if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
        json_response(['success' => false, 'error' => 'Invalid internal authorization'], 401);
    }
}

function get_giveaway_database_connection(): PDO {
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

    $dbPathCandidates = [
        __DIR__ . '/../../db/narrrf_world.sqlite',
        __DIR__ . '/../db/narrrf_world.sqlite',
        __DIR__ . '/db/narrrf_world.sqlite',
        '/var/www/html/db/narrrf_world.sqlite'
    ];

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
    return (bool)$stmt->fetchColumn();
}

function get_table_columns(PDO $pdo, string $tableName): array {
    $stmt = $pdo->query("PRAGMA table_info({$tableName})");
    $columns = [];
    foreach ($stmt->fetchAll() as $row) {
        $columns[] = (string)($row['name'] ?? '');
    }
    return array_values(array_filter($columns));
}

function fetch_first_row_by_candidate_id(PDO $pdo, string $tableName, array $candidateIdColumns, int $referenceId): ?array {
    if ($referenceId < 1 || !sqlite_table_exists($pdo, $tableName)) {
        return null;
    }

    $columns = get_table_columns($pdo, $tableName);
    foreach ($candidateIdColumns as $column) {
        if (!in_array($column, $columns, true)) {
            continue;
        }

        $stmt = $pdo->prepare("SELECT * FROM {$tableName} WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$referenceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    return null;
}

function fetch_store_item_row(PDO $pdo, int $storeItemId): ?array {
    return fetch_first_row_by_candidate_id($pdo, 'tbl_store_items', ['item_id', 'store_item_id', 'id'], $storeItemId);
}

function fetch_genetic_catalog_row(PDO $pdo, int $catalogId): ?array {
    return fetch_first_row_by_candidate_id($pdo, 'tbl_genetic_trait_catalog', ['catalog_id', 'trait_catalog_id', 'id'], $catalogId);
}

function user_owns_genetic_trait(PDO $pdo, string $userId, string $traitType, string $traitValue): bool {
    if ($userId === '' || $traitType === '' || $traitValue === '' || !sqlite_table_exists($pdo, 'tbl_user_genetic_items')) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT genetic_item_id FROM tbl_user_genetic_items WHERE user_id = ? AND trait_type = ? AND trait_value = ? LIMIT 1");
    $stmt->execute([$userId, $traitType, $traitValue]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

function insert_genetic_item_history(PDO $pdo, ?int $geneticItemId, string $userId, array $newValue): void {
    if (!sqlite_table_exists($pdo, 'tbl_genetic_item_history')) {
        return;
    }

    $stmt = $pdo->prepare("\n        INSERT INTO tbl_genetic_item_history (\n            genetic_item_id,\n            listing_id,\n            user_id,\n            action_type,\n            old_value_json,\n            new_value_json,\n            admin_user_id,\n            created_at\n        ) VALUES (?, NULL, ?, 'giveaway_grant', '{}', ?, 'giveaway_system', CURRENT_TIMESTAMP)\n    ");

    $stmt->execute([
        $geneticItemId,
        $userId,
        json_encode($newValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ]);
}

function grant_store_item_to_user(PDO $pdo, string $userId, array $storeItem, int $quantity, string $sourceTag): array {
    if (!sqlite_table_exists($pdo, 'tbl_user_inventory')) {
        throw new Exception('tbl_user_inventory table not found');
    }

    $itemName = trim((string)($storeItem['item_name'] ?? $storeItem['name'] ?? $storeItem['title'] ?? ''));
    if ($itemName === '') {
        throw new Exception('Store reward is missing item_name');
    }

    $description = trim((string)($storeItem['description'] ?? ''));
    $inventoryColumns = get_table_columns($pdo, 'tbl_user_inventory');

    $select = $pdo->prepare("SELECT * FROM tbl_user_inventory WHERE user_id = ? AND item_name = ? LIMIT 1");
    $select->execute([$userId, $itemName]);
    $existing = $select->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($existing) {
        $newQuantity = (int)($existing['quantity'] ?? 0) + max(1, $quantity);
        $sets = ['quantity = ?'];
        $params = [$newQuantity];

        if (in_array('updated_at', $inventoryColumns, true)) {
            $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        }

        $pkColumn = in_array('inventory_id', $inventoryColumns, true) ? 'inventory_id' : (in_array('user_inventory_id', $inventoryColumns, true) ? 'user_inventory_id' : 'id');
        $params[] = (int)($existing['inventory_id'] ?? $existing['user_inventory_id'] ?? $existing['id'] ?? 0);

        $stmt = $pdo->prepare("UPDATE tbl_user_inventory SET " . implode(', ', $sets) . " WHERE {$pkColumn} = ?");
        $stmt->execute($params);

        return [
            'inventory_id' => (int)($existing['inventory_id'] ?? $existing['user_inventory_id'] ?? $existing['id'] ?? 0),
            'item_name' => $itemName,
            'quantity' => $newQuantity,
            'description' => $description,
            'source' => $sourceTag
        ];
    }

    $columnValueMap = [
        'user_id' => $userId,
        'item_name' => $itemName,
        'quantity' => max(1, $quantity),
        'description' => $description,
        'item_id' => (int)($storeItem['item_id'] ?? $storeItem['store_item_id'] ?? $storeItem['id'] ?? 0),
        'store_item_id' => (int)($storeItem['item_id'] ?? $storeItem['store_item_id'] ?? $storeItem['id'] ?? 0),
        'source' => $sourceTag,
        'acquired_method' => $sourceTag,
        'is_used' => 0,
        'used' => 0
    ];

    $insertColumns = [];
    $placeholders = [];
    $params = [];

    foreach ($inventoryColumns as $column) {
        if (in_array($column, ['created_at', 'updated_at', 'acquired_at', 'timestamp'], true)) {
            $insertColumns[] = $column;
            $placeholders[] = 'CURRENT_TIMESTAMP';
            continue;
        }

        if (!array_key_exists($column, $columnValueMap)) {
            continue;
        }

        $insertColumns[] = $column;
        $placeholders[] = '?';
        $params[] = $columnValueMap[$column];
    }

    if (empty($insertColumns)) {
        throw new Exception('tbl_user_inventory does not expose compatible insert columns');
    }

    $stmt = $pdo->prepare('INSERT INTO tbl_user_inventory (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')');
    $stmt->execute($params);

    return [
        'inventory_id' => (int)$pdo->lastInsertId(),
        'item_name' => $itemName,
        'quantity' => max(1, $quantity),
        'description' => $description,
        'source' => $sourceTag
    ];
}

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

    if (user_owns_genetic_trait($pdo, $userId, $traitType, $traitValue)) {
        throw new Exception('User already owns this genetic trait');
    }

    $stmt = $pdo->prepare("\n        INSERT INTO tbl_user_genetic_items (\n            user_id,\n            catalog_id,\n            trait_type,\n            trait_value,\n            current_level,\n            upgrade_status,\n            acquired_method,\n            is_listed_for_sale,\n            created_at,\n            updated_at\n        ) VALUES (?, ?, ?, ?, 1, 'idle', ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)\n    ");
    $stmt->execute([$userId, $catalogId, $traitType, $traitValue, $sourceTag]);

    $geneticItemId = (int)$pdo->lastInsertId();
    insert_genetic_item_history($pdo, $geneticItemId, $userId, [
        'catalog_id' => $catalogId,
        'trait_type' => $traitType,
        'trait_value' => $traitValue,
        'display_title' => $displayTitle,
        'acquired_method' => $sourceTag
    ]);

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

function update_winner_delivery_row(PDO $pdo, string $giveawayId, string $winnerUserId, array $payload): void {
    if (!sqlite_table_exists($pdo, 'tbl_giveaway_winners')) {
        return;
    }

    $columns = get_table_columns($pdo, 'tbl_giveaway_winners');
    $sets = [];
    $params = [];

    if (in_array('delivery_status', $columns, true)) {
        $sets[] = 'delivery_status = ?';
        $params[] = 'delivered';
    }

    if (in_array('delivered_at', $columns, true)) {
        $sets[] = 'delivered_at = CURRENT_TIMESTAMP';
    }

    if (in_array('delivery_payload_json', $columns, true)) {
        $sets[] = 'delivery_payload_json = ?';
        $params[] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    if (!$sets) {
        return;
    }

    $params[] = $giveawayId;
    $params[] = $winnerUserId;
    $stmt = $pdo->prepare('UPDATE tbl_giveaway_winners SET ' . implode(', ', $sets) . ' WHERE giveaway_id = ? AND user_id = ?');
    $stmt->execute($params);
}

assert_internal_authorization();
$request = get_request_data();

$giveawayId = trim((string)($request['giveaway_id'] ?? ''));
$winnerUserId = trim((string)($request['winner_user_id'] ?? ''));
$rewardType = strtolower(trim((string)($request['reward_type'] ?? '')));
$rewardReferenceId = (int)($request['reward_reference_id'] ?? 0);
$rewardQuantity = max(1, (int)($request['reward_quantity'] ?? 1));
$rewardSnapshotTitle = trim((string)($request['reward_snapshot_title'] ?? ''));

if ($giveawayId === '' || $winnerUserId === '' || $rewardType === '' || $rewardReferenceId < 1) {
    json_response(['success' => false, 'error' => 'Missing required delivery fields'], 400);
}

if (!in_array($rewardType, ['store_item', 'genetic_trait'], true)) {
    json_response(['success' => false, 'error' => 'Unsupported structured reward type'], 400);
}

try {
    $pdo = get_giveaway_database_connection();
    $pdo->beginTransaction();

    if (sqlite_table_exists($pdo, 'tbl_giveaways')) {
        $stmt = $pdo->prepare('SELECT * FROM tbl_giveaways WHERE giveaway_id = ? LIMIT 1');
        $stmt->execute([$giveawayId]);
        $giveawayRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$giveawayRow) {
            throw new Exception('Giveaway not found');
        }
    }

    if (sqlite_table_exists($pdo, 'tbl_giveaway_winners')) {
        $stmt = $pdo->prepare('SELECT * FROM tbl_giveaway_winners WHERE giveaway_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$giveawayId, $winnerUserId]);
        $winnerRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$winnerRow) {
            throw new Exception('Giveaway winner record not found');
        }

        $deliveryStatus = strtolower(trim((string)($winnerRow['delivery_status'] ?? '')));
        if ($deliveryStatus === 'delivered') {
            throw new Exception('Reward already delivered to this winner');
        }
    }

    $sourceTag = 'giveaway_reward';
    $deliveryPayload = [];

    if ($rewardType === 'store_item') {
        $storeItem = fetch_store_item_row($pdo, $rewardReferenceId);
        if (!$storeItem) {
            throw new Exception('Store item not found');
        }

        $deliveryPayload = grant_store_item_to_user($pdo, $winnerUserId, $storeItem, $rewardQuantity, $sourceTag);
    } elseif ($rewardType === 'genetic_trait') {
        $catalogRow = fetch_genetic_catalog_row($pdo, $rewardReferenceId);
        if (!$catalogRow) {
            throw new Exception('Genetic catalog row not found');
        }

        $deliveryPayload = grant_genetic_trait_to_user($pdo, $winnerUserId, $catalogRow, $sourceTag);
    }

    $responsePayload = [
        'reward_type' => $rewardType,
        'reward_reference_id' => $rewardReferenceId,
        'reward_snapshot_title' => $rewardSnapshotTitle,
        'winner_user_id' => $winnerUserId,
        'delivery_payload' => $deliveryPayload
    ];

    update_winner_delivery_row($pdo, $giveawayId, $winnerUserId, $responsePayload);
    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Giveaway reward delivered successfully',
        'giveaway_id' => $giveawayId,
        'winner_user_id' => $winnerUserId,
        'reward_type' => $rewardType,
        'reward_reference_id' => $rewardReferenceId,
        'reward_snapshot_title' => $rewardSnapshotTitle,
        'delivery_payload' => $deliveryPayload
    ]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $code = 500;
    $message = $error->getMessage();
    if (stripos($message, 'already delivered') !== false || stripos($message, 'already owns') !== false) {
        $code = 409;
    } elseif (stripos($message, 'not found') !== false || stripos($message, 'missing') !== false) {
        $code = 404;
    }

    json_response(['success' => false, 'error' => $message], $code);
}
