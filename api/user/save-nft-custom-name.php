<?php
// 🧬 Save NFT Custom Name API
// Saves or clears a personal name for one verified Genesis NFT.
//
// Stability-first rules:
// - User-bound display name only
// - Does not mutate NFT metadata
// - Does not change token_id, collection, trait rows, ability rows, or marketplace data
// - Empty custom_name clears the saved name
// - Only verified/owned Genesis NFT rows may be named
// - Session-first auth in production
// - Localhost request user_id allowed for testing

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf fallback

const CUSTOM_NFT_NAME_MAX_LENGTH = 32;

/**
 * Return JSON response and stop execution.
 */
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Detect localhost environment.
 */
function is_localhost_env(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Read request data safely.
 */
function get_request_data(): array {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);

    if (is_array($decoded)) {
        return $decoded;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return is_array($_GET) ? $_GET : [];
    }

    return is_array($_POST) ? $_POST : [];
}

/**
 * Resolve active user using the same pattern as the live Lab APIs.
 */
function resolve_user_id(array $request): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $requestUserId = trim((string)($request['user_id'] ?? ''));
    $isLocalhost = is_localhost_env();

    if ($isLocalhost && $requestUserId !== '') {
        return $requestUserId;
    }

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($sessionUserId !== '') {
        return $sessionUserId;
    }

    if ($isLocalhost) {
        return $LOCAL_TEST_DISCORD_ID;
    }

    return '';
}

/**
 * Get DB connection.
 */
function get_db(): PDO {
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

    throw new Exception('Database not found');
}

/**
 * Normalize collection names for storage and lookup.
 */
function normalize_collection(string $collection): string {
    $normalized = strtolower(trim($collection));
    return $normalized !== '' ? $normalized : 'genesis';
}

/**
 * Normalize a player-provided custom name.
 * Plain-language: keep names readable, short, and safe for HTML rendering.
 */
function normalize_custom_name(string $rawName): string {
    $name = trim($rawName);
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
    $name = preg_replace('/\s+/u', ' ', $name);
    $name = trim((string)$name);

    if (function_exists('mb_substr')) {
        return mb_substr($name, 0, CUSTOM_NFT_NAME_MAX_LENGTH);
    }

    return substr($name, 0, CUSTOM_NFT_NAME_MAX_LENGTH);
}

/**
 * Return true when the user has a backend Genesis progression row for this NFT.
 * DEVS FOR DECADES:
 * This is a conservative ownership guard. Lab progression rows are NFT-bound and
 * created from verified Genesis data. Do not save names for arbitrary token IDs.
 */
function user_has_verified_genesis_token(PDO $pdo, string $userId, string $tokenId, string $collection): bool {
    $traitStmt = $pdo->prepare("
        SELECT 1
        FROM tbl_nft_trait_upgrades
        WHERE last_owner_user_id = ?
          AND token_id = ?
          AND LOWER(collection) = ?
        LIMIT 1
    ");
    $traitStmt->execute([$userId, $tokenId, strtolower($collection)]);

    if ($traitStmt->fetchColumn()) {
        return true;
    }

    $abilityStmt = $pdo->prepare("
        SELECT 1
        FROM tbl_nft_ability_upgrades
        WHERE user_id = ?
          AND token_id = ?
          AND LOWER(collection) = ?
        LIMIT 1
    ");
    $abilityStmt->execute([$userId, $tokenId, strtolower($collection)]);

    return (bool)$abilityStmt->fetchColumn();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'Method not allowed'
        ], 405);
    }

    $request = get_request_data();
    $userId = resolve_user_id($request);

    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Not authenticated'
        ], 401);
    }

    $tokenId = trim((string)($request['token_id'] ?? ''));
    $collection = normalize_collection((string)($request['collection'] ?? 'genesis'));
    $customName = normalize_custom_name((string)($request['custom_name'] ?? ''));

    if ($tokenId === '') {
        json_response([
            'success' => false,
            'error' => 'token_id is required'
        ], 400);
    }

    if ($collection !== 'genesis') {
        json_response([
            'success' => false,
            'error' => 'Only Genesis NFT custom names are supported'
        ], 400);
    }

    $pdo = get_db();

    if (!user_has_verified_genesis_token($pdo, $userId, $tokenId, $collection)) {
        json_response([
            'success' => false,
            'error' => 'NFT is not verified for this user'
        ], 403);
    }

    if ($customName === '') {
        $deleteStmt = $pdo->prepare("
            DELETE FROM tbl_nft_custom_names
            WHERE user_id = ?
              AND token_id = ?
              AND collection = ?
        ");
        $deleteStmt->execute([$userId, $tokenId, $collection]);

        json_response([
            'success' => true,
            'action' => 'deleted',
            'user_id' => $userId,
            'token_id' => $tokenId,
            'collection' => $collection,
            'custom_name' => ''
        ]);
    }

    $upsertStmt = $pdo->prepare("
        INSERT INTO tbl_nft_custom_names (
            user_id,
            token_id,
            collection,
            custom_name,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ON CONFLICT(user_id, token_id, collection)
        DO UPDATE SET
            custom_name = excluded.custom_name,
            updated_at = CURRENT_TIMESTAMP
    ");
    $upsertStmt->execute([
        $userId,
        $tokenId,
        $collection,
        $customName
    ]);

    json_response([
        'success' => true,
        'action' => 'saved',
        'user_id' => $userId,
        'token_id' => $tokenId,
        'collection' => $collection,
        'custom_name' => $customName
    ]);

} catch (Throwable $e) {
    json_response([
        'success' => false,
        'error' => 'Server error',
        'details' => $e->getMessage()
    ], 500);
}