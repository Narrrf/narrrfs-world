<?php
// 🧬 Get NFT Custom Names API
// Returns custom player names for verified Genesis NFTs.
//
// Stability-first rules:
// - Read-only endpoint
// - User-bound names only
// - Does not mutate NFT metadata
// - Does not affect trait/ability upgrade identity
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

try {
    $request = get_request_data();
    $userId = resolve_user_id($request);

    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Not authenticated'
        ], 401);
    }

    $collection = normalize_collection((string)($request['collection'] ?? 'genesis'));

    $pdo = get_db();

    $stmt = $pdo->prepare("
        SELECT
            token_id,
            collection,
            custom_name,
            created_at,
            updated_at
        FROM tbl_nft_custom_names
        WHERE user_id = ?
          AND collection = ?
        ORDER BY updated_at DESC, custom_name ASC
    ");
    $stmt->execute([$userId, $collection]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $names = [];
    $map = [];

    foreach ($rows as $row) {
        $tokenId = trim((string)($row['token_id'] ?? ''));
        $customName = trim((string)($row['custom_name'] ?? ''));

        if ($tokenId === '' || $customName === '') {
            continue;
        }

        $entry = [
            'token_id' => $tokenId,
            'collection' => normalize_collection((string)($row['collection'] ?? $collection)),
            'custom_name' => $customName,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null
        ];

        $names[] = $entry;
        $map[$tokenId] = $customName;
    }

    json_response([
        'success' => true,
        'user_id' => $userId,
        'collection' => $collection,
        'names' => $names,
        'map' => $map,
        'count' => count($names)
    ]);

} catch (Throwable $e) {
    json_response([
        'success' => false,
        'error' => 'Server error',
        'details' => $e->getMessage()
    ], 500);
}