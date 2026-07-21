<?php
// 🧬 Get Favorite Traits API
// Returns all favorite trait types for the logged-in user.
//
// Stability-first rules:
// - Read-only endpoint
// - User-bound data only (NOT NFT-bound)
// - Session-first auth (production)
// - Localhost fallback allowed
// - No side effects

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
 * Send JSON response and exit
 */
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Detect localhost environment
 */
function is_localhost_env(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Safely read request data
 */
function get_request_data(): array {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);

    if (is_array($decoded)) return $decoded;

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return is_array($_GET) ? $_GET : [];
    }

    return is_array($_POST) ? $_POST : [];
}

/**
 * Resolve user_id (same pattern as Lab APIs)
 */
function resolve_user_id(array $request): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $requestUserId = trim((string)($request['user_id'] ?? ''));
    $isLocalhost = is_localhost_env();

    // Localhost override
    if ($isLocalhost && $requestUserId !== '') {
        return $requestUserId;
    }

    // Security check
    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($sessionUserId !== '') {
        return $sessionUserId;
    }

    // Local fallback
    if ($isLocalhost) {
        return $LOCAL_TEST_DISCORD_ID;
    }

    return '';
}

/**
 * Get DB connection
 */
function get_db(): PDO {
    if (function_exists('getDatabaseConnection')) {
        return getDatabaseConnection();
    }

    if (function_exists('getDB')) {
        return getDB();
    }

    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

    if (!file_exists($dbPath)) {
        throw new Exception('Database not found');
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
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

    $pdo = get_db();

    // 🔍 Fetch favorite
    // BUG #726:
    // The Lab automation supports one active favorite trait type at a time.
    // Keep this endpoint read-only, but only return the winning favorite row if
    // older duplicate favorite rows still exist in the database.
    $stmt = $pdo->prepare("
        SELECT
            trait_type,
            is_favorite,
            priority_order,
            created_at,
            updated_at
        FROM tbl_user_favorite_traits
        WHERE user_id = ?
          AND is_favorite = 1
        ORDER BY priority_order DESC, created_at ASC
        LIMIT 1
    ");

    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    // 🧠 Normalize output (frontend-friendlyy)
    $favorites = [];
    foreach ($rows as $row) {
        $favorites[] = [
            'trait_type' => $row['trait_type'],
            'priority_order' => (int)$row['priority_order']
        ];
    }

    json_response([
        'success' => true,
        'user_id' => $userId,
        'favorites' => $favorites,
        'count' => count($favorites)
    ]);

} catch (Throwable $e) {
    json_response([
        'success' => false,
        'error' => 'Server error',
        'details' => $e->getMessage()
    ], 500);
}