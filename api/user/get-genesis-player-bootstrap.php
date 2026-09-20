<?php
/**
 * Genesis Player Bootstrap V1
 *
 * This route is the read-only boundary between one authenticated, verified
 * Genesis mouse and a temporary Three.js session. It deliberately does not
 * repair, seed, synchronize, or finalize permanent Lab or ownership data.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const GENESIS_PLAYER_BOOTSTRAP_V1_CONTRACT_VERSION = 'genesis_player_bootstrap_v1';
const GENESIS_PLAYER_BOOTSTRAP_V1_COLLECTION = 'genesis';

/**
 * Send one privacy-minimized Bootstrap V1 response and stop execution.
 */
function genesis_player_bootstrap_v1_response(int $status, string $state, array $data = []): void
{
    http_response_code($status);
    echo json_encode(array_merge([
        'state' => $state,
        'contract_version' => GENESIS_PLAYER_BOOTSTRAP_V1_CONTRACT_VERSION
    ], $data));
    exit;
}

/**
 * Finish the deferred read transaction before returning any response.
 */
function genesis_player_bootstrap_v1_finish_read(PDO $pdo): void
{
    $pdo->exec('COMMIT');
}

/**
 * Cancel a read snapshot after a failed SELECT. No permanent state is changed.
 */
function genesis_player_bootstrap_v1_cancel_read(PDO $pdo): void
{
    $pdo->exec('ROLLBACK');
}

/**
 * Return the selected user's current verified Genesis ownership row.
 *
 * The ownership row is intentionally kept internal. Trait metadata is scoped
 * through its ownership_id so a token alone cannot select another holder's data.
 */
function genesis_player_bootstrap_v1_find_ownership(PDO $pdo, string $userId, string $tokenId): ?array
{
    $stmt = $pdo->prepare("
        SELECT ownership_id, token_id, collection
        FROM tbl_nft_ownership
        WHERE user_id = ?
          AND token_id = ?
          AND LOWER(COALESCE(collection, '')) = 'genesis'
          AND COALESCE(is_verified, 0) = 1
        ORDER BY
            COALESCE(verified_at, '') DESC,
            COALESCE(acquired_at, '') DESC,
            ownership_id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $tokenId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

/**
 * Read verified metadata traits, then overlay only persisted permanent levels.
 * A missing progression row is the documented level-one fallback; this function
 * never creates that row.
 */
function genesis_player_bootstrap_v1_read_traits(PDO $pdo, array $ownership): array
{
    $metadataStmt = $pdo->prepare("
        SELECT trait_type, trait_value
        FROM tbl_nft_traits
        WHERE ownership_id = ?
          AND token_id = ?
          AND LOWER(COALESCE(collection, '')) = 'genesis'
        ORDER BY trait_type ASC, trait_value ASC
    ");
    $metadataStmt->execute([
        (int)$ownership['ownership_id'],
        (string)$ownership['token_id']
    ]);
    $metadataRows = $metadataStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $levelStmt = $pdo->prepare("
        SELECT trait_type, trait_value, current_level
        FROM tbl_nft_trait_upgrades
        WHERE token_id = ?
          AND LOWER(COALESCE(collection, '')) = 'genesis'
    ");
    $levelStmt->execute([(string)$ownership['token_id']]);
    $levelRows = $levelStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $levels = [];
    foreach ($levelRows as $levelRow) {
        $traitType = trim((string)($levelRow['trait_type'] ?? ''));
        $traitValue = trim((string)($levelRow['trait_value'] ?? ''));
        if ($traitType === '' || $traitValue === '') {
            continue;
        }

        $key = $traitType . "\0" . $traitValue;
        $levels[$key] = max(1, (int)($levelRow['current_level'] ?? 1));
    }

    $traits = [];
    foreach ($metadataRows as $metadataRow) {
        $traitType = trim((string)($metadataRow['trait_type'] ?? ''));
        $traitValue = trim((string)($metadataRow['trait_value'] ?? ''));
        if ($traitType === '' || $traitValue === '') {
            continue;
        }

        $key = $traitType . "\0" . $traitValue;
        $traits[] = [
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'current_level' => $levels[$key] ?? 1
        ];
    }

    return $traits;
}

/**
 * Read only persisted Ability progression. Missing canonical rows remain absent
 * rather than being seeded by this Bootstrap route.
 */
function genesis_player_bootstrap_v1_read_abilities(PDO $pdo, array $ownership): array
{
    $stmt = $pdo->prepare("
        SELECT category, ability_key, current_level
        FROM tbl_nft_ability_upgrades
        WHERE token_id = ?
          AND LOWER(COALESCE(collection, '')) = 'genesis'
        ORDER BY category ASC, ability_key ASC
    ");
    $stmt->execute([(string)$ownership['token_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $abilities = [];
    foreach ($rows as $row) {
        $category = trim((string)($row['category'] ?? ''));
        $abilityKey = trim((string)($row['ability_key'] ?? ''));
        if ($category === '' || $abilityKey === '') {
            continue;
        }

        $abilities[] = [
            'category' => $category,
            'ability_key' => $abilityKey,
            'current_level' => max(1, (int)($row['current_level'] ?? 1))
        ];
    }

    return $abilities;
}

/**
 * Read user-bound Genetic Items without marketplace, price, audit, or raw
 * effect-metadata fields. The returned records are permanent-state snapshots.
 */
function genesis_player_bootstrap_v1_read_inventory(PDO $pdo, string $userId): array
{
    $stmt = $pdo->prepare("
        SELECT trait_type, trait_value, current_level
        FROM tbl_user_genetic_items
        WHERE user_id = ?
        ORDER BY trait_type ASC, trait_value ASC, current_level ASC
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'trait_type' => (string)($row['trait_type'] ?? ''),
            'trait_value' => (string)($row['trait_value'] ?? ''),
            'current_level' => max(1, (int)($row['current_level'] ?? 1))
        ];
    }

    return $items;
}

/**
 * Build an opaque, session-scoped revision for this returned snapshot without
 * exposing an ownership identifier, wallet, timestamp, audit record, or the
 * server-only HMAC key to the client.
 */
function genesis_player_bootstrap_v1_snapshot_revision(
    array $ownership,
    array $traits,
    array $abilities,
    array $inventoryItems,
    string $sessionSecret
): string
{
    $revisionPayload = [
        'contract_version' => GENESIS_PLAYER_BOOTSTRAP_V1_CONTRACT_VERSION,
        'selected_ownership' => (int)($ownership['ownership_id'] ?? 0),
        'token_id' => (string)($ownership['token_id'] ?? ''),
        'collection' => GENESIS_PLAYER_BOOTSTRAP_V1_COLLECTION,
        'traits' => $traits,
        'abilities' => $abilities,
        'inventory' => $inventoryItems
    ];

    return hash_hmac(
        'sha256',
        (string)json_encode($revisionPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $sessionSecret
    );
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    genesis_player_bootstrap_v1_response(405, 'method_not_allowed');
}

$userId = trim((string)($_SESSION['discord_id'] ?? ''));
if ($userId === '') {
    genesis_player_bootstrap_v1_response(401, 'unauthenticated');
}

$tokenId = trim((string)($_GET['token_id'] ?? ''));
$requestedCollection = strtolower(trim((string)($_GET['collection'] ?? GENESIS_PLAYER_BOOTSTRAP_V1_COLLECTION)));
if ($requestedCollection !== GENESIS_PLAYER_BOOTSTRAP_V1_COLLECTION) {
    genesis_player_bootstrap_v1_response(422, 'selected_genesis_not_owned');
}

try {
    $pdo = getDatabaseConnection();
    // PDO is configured for SQLite by database.php; BEGIN DEFERRED gives all
    // following SELECTs one consistent read snapshot without a write lock.
    $pdo->exec('BEGIN DEFERRED TRANSACTION');
} catch (Throwable $error) {
    genesis_player_bootstrap_v1_response(503, 'ownership_verification_unavailable');
}

try {
    $hasGenesisStmt = $pdo->prepare("
        SELECT 1
        FROM tbl_nft_ownership
        WHERE user_id = ?
          AND LOWER(COALESCE(collection, '')) = 'genesis'
          AND COALESCE(is_verified, 0) = 1
        LIMIT 1
    ");
    $hasGenesisStmt->execute([$userId]);
    $hasVerifiedGenesis = (bool)$hasGenesisStmt->fetchColumn();
} catch (Throwable $error) {
    genesis_player_bootstrap_v1_cancel_read($pdo);
    genesis_player_bootstrap_v1_response(503, 'ownership_verification_unavailable');
}

if (!$hasVerifiedGenesis) {
    genesis_player_bootstrap_v1_finish_read($pdo);
    genesis_player_bootstrap_v1_response(200, 'no_verified_genesis');
}

if ($tokenId === '') {
    genesis_player_bootstrap_v1_finish_read($pdo);
    genesis_player_bootstrap_v1_response(422, 'genesis_not_selected');
}

try {
    $ownership = genesis_player_bootstrap_v1_find_ownership($pdo, $userId, $tokenId);
} catch (Throwable $error) {
    genesis_player_bootstrap_v1_cancel_read($pdo);
    genesis_player_bootstrap_v1_response(503, 'ownership_verification_unavailable');
}

if ($ownership === null) {
    genesis_player_bootstrap_v1_finish_read($pdo);
    genesis_player_bootstrap_v1_response(422, 'selected_genesis_not_owned');
}

try {
    $traits = genesis_player_bootstrap_v1_read_traits($pdo, $ownership);
} catch (Throwable $error) {
    genesis_player_bootstrap_v1_cancel_read($pdo);
    genesis_player_bootstrap_v1_response(503, 'trait_data_unavailable');
}

try {
    $abilities = genesis_player_bootstrap_v1_read_abilities($pdo, $ownership);
} catch (Throwable $error) {
    genesis_player_bootstrap_v1_cancel_read($pdo);
    genesis_player_bootstrap_v1_response(503, 'ability_data_unavailable');
}

try {
    $inventoryItems = genesis_player_bootstrap_v1_read_inventory($pdo, $userId);
} catch (Throwable $error) {
    genesis_player_bootstrap_v1_cancel_read($pdo);
    genesis_player_bootstrap_v1_response(503, 'inventory_data_unavailable');
}

genesis_player_bootstrap_v1_finish_read($pdo);

$snapshotRevision = genesis_player_bootstrap_v1_snapshot_revision(
    $ownership,
    $traits,
    $abilities,
    $inventoryItems,
    session_id()
);

genesis_player_bootstrap_v1_response(200, 'bootstrap_ready', [
    'snapshot_revision' => $snapshotRevision,
    'selected_genesis' => [
        'token_id' => (string)$ownership['token_id'],
        'collection' => GENESIS_PLAYER_BOOTSTRAP_V1_COLLECTION
    ],
    'traits' => $traits,
    'abilities' => $abilities,
    'genetic_inventory' => [
        'state' => $inventoryItems === [] ? 'inventory_verified_empty' : 'inventory_ready',
        'items' => $inventoryItems
    ],
    'readiness' => [
        'gameplay' => 'ready'
    ]
]);
