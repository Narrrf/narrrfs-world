<?php
// 🧀 Cheese Runner Score Save API
// Backend-authoritative score save for Cheeseman / Cheese Runner.
// Writes:
// - tbl_tetris_scores = seasonal game leaderboard source
// - tbl_user_scores = DSPOINC ledger source of truth
//
// Rules:
// - Append-only
// - Session-first auth in production
// - Localhost fallback allowed only for testing
// - Frontend score is validated before insert
// - Uses active season from tbl_seasons

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

date_default_timezone_set('UTC');
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

$LOCAL_TEST_DISCORD_ID = '328601656659017732';
$GAME_KEY = 'cheeseman';
$GAME_SOURCE = 'game_reward';

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
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);

    if (is_array($json)) {
        return $json;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return is_array($_GET) ? $_GET : [];
    }

    return is_array($_POST) ? $_POST : [];
}

/**
 * Open the Narrrfs World SQLite database locally or in production.
 */
function get_database_connection(): PDO {
    $candidates = [];

    if (is_localhost_env()) {
        $candidates[] = __DIR__ . '/../../db/narrrf_world.sqlite';
        $candidates[] = 'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite';
    }

    $candidates[] = __DIR__ . '/../../db/narrrf_world.sqlite';
    $candidates[] = '/var/www/html/db/narrrf_world.sqlite';

    foreach ($candidates as $path) {
        if ($path && file_exists($path)) {
            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        }
    }

    throw new Exception('Database file not found');
}

/**
 * Resolve active Discord user.
 * Production uses session auth. Localhost may use request fallback.
 */
function resolve_user_id(array $request): string {
    global $LOCAL_TEST_DISCORD_ID;

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $requestUserId = trim((string)($request['user_id'] ?? $request['discord_id'] ?? ''));
    $isLocalhost = is_localhost_env();

    if ($sessionUserId !== '') {
        if ($requestUserId !== '' && $requestUserId !== $sessionUserId && !$isLocalhost) {
            json_response([
                'success' => false,
                'error' => 'Unauthorized: user_id mismatch'
            ], 403);
        }

        return $sessionUserId;
    }

    if ($isLocalhost && $requestUserId !== '') {
        return $requestUserId;
    }

    if ($isLocalhost) {
        return $LOCAL_TEST_DISCORD_ID;
    }

    return '';
}

/**
 * Return active season name.
 */
function get_current_season(PDO $pdo): string {
    try {
        $stmt = $pdo->prepare("
            SELECT season_name
            FROM tbl_seasons
            WHERE is_active = 1
            ORDER BY start_date DESC
            LIMIT 1
        ");
        $stmt->execute();
        $season = trim((string)$stmt->fetchColumn());

        return $season !== '' ? $season : 'Season 11';
    } catch (Exception $e) {
        error_log('Cheeseman season lookup failed: ' . $e->getMessage());
        return 'Season 11';
    }
}

/**
 * Return username from users table or fallback.
 */
function get_discord_name(PDO $pdo, string $userId, array $request): string {
    $requestName = trim((string)($request['discord_name'] ?? $request['username'] ?? ''));

    try {
        $stmt = $pdo->prepare("
            SELECT username
            FROM tbl_users
            WHERE discord_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $name = trim((string)$stmt->fetchColumn());

        if ($name !== '') {
            return $name;
        }
    } catch (Exception $e) {
        error_log('Cheeseman username lookup failed: ' . $e->getMessage());
    }

    return $requestName !== '' ? $requestName : $userId;
}

/**
 * Read table columns so inserts stay compatible with current DB shape.
 */
function get_table_columns(PDO $pdo, string $table): array {
    $stmt = $pdo->query("PRAGMA table_info($table)");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map(static fn($row) => $row['name'], $rows);
}

/**
 * Insert only columns that exist in the current table.
 */
function adaptive_insert(PDO $pdo, string $table, array $values): void {
    $columns = get_table_columns($pdo, $table);
    $insertValues = [];

    foreach ($values as $column => $value) {
        if (in_array($column, $columns, true)) {
            $insertValues[$column] = $value;
        }
    }

    if (!$insertValues) {
        throw new Exception("No matching columns found for $table");
    }

    $columnSql = implode(', ', array_keys($insertValues));
    $placeholderSql = implode(', ', array_fill(0, count($insertValues), '?'));

    $stmt = $pdo->prepare("INSERT INTO $table ($columnSql) VALUES ($placeholderSql)");
    $stmt->execute(array_values($insertValues));
}

/**
 * Validate Cheese Runner score against simple anti-cheat limits.
 */
function validate_score_payload(array $request): array {
    $rawScore = (int)($request['raw_score'] ?? $request['score'] ?? 0);
    $dspoincScore = (int)($request['dspoinc_score'] ?? 0);
    $level = (int)($request['level'] ?? 1);
    $livesRemaining = (int)($request['lives_remaining'] ?? 0);
    $roleMultiplier = (float)($request['role_multiplier'] ?? 1.0);

    if ($rawScore <= 0) {
        json_response([
            'success' => false,
            'error' => 'Score must be positive'
        ], 400);
    }

    if ($rawScore > 250000) {
        json_response([
            'success' => false,
            'error' => 'Score exceeds Cheese Runner validation limit'
        ], 400);
    }

    if ($dspoincScore <= 0) {
        json_response([
            'success' => false,
            'error' => 'DSPOINC reward must be positive'
        ], 400);
    }

    if ($dspoincScore > 50000) {
        json_response([
            'success' => false,
            'error' => 'DSPOINC reward exceeds validation limit'
        ], 400);
    }

    if ($level < 1 || $level > 100) {
        json_response([
            'success' => false,
            'error' => 'Invalid level value'
        ], 400);
    }

    if ($livesRemaining < 0 || $livesRemaining > 3) {
        json_response([
            'success' => false,
            'error' => 'Invalid lives value'
        ], 400);
    }

    if ($roleMultiplier < 1.0 || $roleMultiplier > 2.0) {
        json_response([
            'success' => false,
            'error' => 'Invalid role multiplier'
        ], 400);
    }

    return [
        'raw_score' => $rawScore,
        'dspoinc_score' => $dspoincScore,
        'level' => $level,
        'lives_remaining' => $livesRemaining,
        'role_multiplier' => $roleMultiplier
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response([
            'success' => false,
            'error' => 'POST required'
        ], 405);
    }

    $request = get_request_data();
    $pdo = get_database_connection();

    $userId = resolve_user_id($request);
    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Discord login required'
        ], 401);
    }

    $validated = validate_score_payload($request);
    $discordName = get_discord_name($pdo, $userId, $request);
    $season = get_current_season($pdo);
    $timestamp = gmdate('Y-m-d H:i:s');

    $pdo->beginTransaction();

    adaptive_insert($pdo, 'tbl_tetris_scores', [
        'discord_id' => $userId,
        'discord_name' => $discordName,
        'wallet' => $userId,
        'score' => $validated['dspoinc_score'],
        'raw_score' => $validated['raw_score'],
        'game' => 'cheeseman',
        'season' => $season,
        'timestamp' => $timestamp,
        'created_at' => $timestamp,
        'level' => $validated['level'],
        'role_multiplier' => $validated['role_multiplier']
    ]);

    adaptive_insert($pdo, 'tbl_user_scores', [
        'user_id' => $userId,
        'score' => $validated['dspoinc_score'],
        'game' => 'cheeseman',
        'source' => 'game_reward',
        'timestamp' => $timestamp,
        'created_at' => $timestamp,
        'reason' => 'Cheese Runner game reward',
        'metadata' => json_encode([
            'raw_score' => $validated['raw_score'],
            'level' => $validated['level'],
            'lives_remaining' => $validated['lives_remaining'],
            'role_multiplier' => $validated['role_multiplier'],
            'season' => $season
        ])
    ]);

        adaptive_insert($pdo, 'tbl_score_adjustments', [
        'user_id' => $userId,
        'amount' => $validated['dspoinc_score'],
        'action' => 'add',
        'reason' => 'cheeseman game score: ' . $validated['dspoinc_score'] . ' DSPOINC (frontend calculated)',
        'timestamp' => $timestamp,
        'created_at' => $timestamp,
        'admin_id' => 'system'
    ]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Cheese Runner score saved',
        'game' => 'cheeseman',
        'season' => $season,
        'user_id' => $userId,
        'discord_name' => $discordName,
        'raw_score' => $validated['raw_score'],
        'dspoinc_score' => $validated['dspoinc_score']
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Cheeseman save error: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => 'Cheese Runner score save failed',
        'details' => is_localhost_env() ? $e->getMessage() : null
    ], 500);
}