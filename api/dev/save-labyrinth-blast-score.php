<?php
// 💥 Labyrinth Blast Score Save API
// Backend-authoritative score save for Labyrinth Blast.
//
// Plain language for DEVS:
// Labyrinth Blast sends only raw gameplay data from the frontend.
// This API validates that data, resolves the real Discord user from session,
// calculates DSPOINC on the backend, and writes to the existing Narrrfs score tables.
//
// Writes:
// - tbl_tetris_scores = seasonal leaderboard source
// - tbl_user_scores = DSPOINC ledger source
// - tbl_score_adjustments = audit/source trail
//
// Rules:
// - Append-only
// - POST only
// - JSON only
// - Session-first auth in production
// - Localhost fallback allowed only for testing
// - Frontend does not decide DSPOINC
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

const LOCAL_TEST_DISCORD_ID = '328601656659017732';
const GAME_KEY = 'labyrinth_blast';
const GAME_SOURCE = 'game_reward';
const GAME_DISPLAY_NAME = 'Labyrinth Blast';
const DSP_CONVERSION_RATE = 10;
const MAX_DSP_REWARD = 5000;
const MAX_REASONABLE_RAW_SCORE = 250000;
const DEFAULT_ROLE_MULTIPLIER = 1.0;
const LOCAL_TEST_ROLE_MULTIPLIER = 2.0;

function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function is_localhost_env(): bool {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Reads JSON, GET, or POST data in the same safe style as other Narrrfs game APIs.
 */
function get_request_data(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);

    if (is_array($json)) {
        return $json;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        return is_array($_GET) ? $_GET : [];
    }

    return is_array($_POST) ? $_POST : [];
}

/**
 * Opens the Narrrfs SQLite database in local XAMPP or production Render.
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
 * Resolves the active Discord user.
 *
 * Plain language for DEVS:
 * Production trusts the PHP session. Localhost may use a request/user fallback
 * so we can test the game without a full Discord login loop.
 */
function resolve_user_id(array $request): string {
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
        return LOCAL_TEST_DISCORD_ID;
    }

    return '';
}

/**
 * Returns the active season name.
 *
 * Plain language for DEVS:
 * This is important for the Season 11 -> Season 12 transition.
 * When tbl_seasons changes the active season, this API follows automatically.
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

        return $season !== '' ? $season : 'Season 12';
    } catch (Exception $e) {
        error_log('Labyrinth Blast season lookup failed: ' . $e->getMessage());
        return 'Season 12';
    }
}

/**
 * Resolves display name from tbl_users or request fallback.
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
        error_log('Labyrinth Blast username lookup failed: ' . $e->getMessage());
    }

    return $requestName !== '' ? $requestName : $userId;
}

/**
 * Returns role names stored for this Discord user.
 *
 * Plain language for DEVS:
 * Narrrfs role sync writes Discord role names into tbl_user_roles.
 * Labyrinth Blast reads those roles on the backend so the browser cannot decide
 * the economy multiplier.
 */
function get_user_roles(PDO $pdo, string $userId): array {
    try {
        $stmt = $pdo->prepare("
            SELECT role_name
            FROM tbl_user_roles
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return is_array($roles) ? $roles : [];
    } catch (Exception $e) {
        error_log('Labyrinth Blast role lookup failed: ' . $e->getMessage());
        return [];
    }
}

/**
 * Normalizes role names so emoji and non-emoji role variants both work.
 */
function normalize_role_name(string $role): string {
    $cleanRole = preg_replace('/[\x{1F300}-\x{1FAFF}]/u', '', $role);
    $cleanRole = trim((string)$cleanRole);
    return $cleanRole !== '' ? $cleanRole : trim($role);
}

/**
 * Calculates the Narrrfs role multiplier for Labyrinth Blast.
 *
 * Plain language for DEVS:
 * This mirrors the existing Snake multiplier ladder, but the calculation happens
 * server-side for Labyrinth Blast so the final DSPOINC reward stays authoritative.
 */
function calculate_role_multiplier(array $roles): array {
    $roleMultipliers = [
        'VIP Holder' => 2.0,
        'Holder' => 1.5,
        'Champion' => 1.4,
        'Season Tester' => 1.3,
        'WL' => 1.3,
        'Early Bird' => 1.2,
        'Cheese Hunter' => 1.1
    ];

    $bestRole = null;
    $bestMultiplier = DEFAULT_ROLE_MULTIPLIER;

    foreach ($roles as $role) {
        $roleName = trim((string)$role);
        $cleanRoleName = normalize_role_name($roleName);

        foreach ([$roleName, $cleanRoleName] as $candidate) {
            if (!isset($roleMultipliers[$candidate])) {
                continue;
            }

            $candidateMultiplier = (float)$roleMultipliers[$candidate];

            if ($candidateMultiplier > $bestMultiplier) {
                $bestMultiplier = $candidateMultiplier;
                $bestRole = $roleName;
            }
        }
    }

    return [
        'role' => $bestRole,
        'multiplier' => $bestMultiplier
    ];
}

/**
 * Reads table columns so inserts survive older/newer DB shapes.
 */
function get_table_columns(PDO $pdo, string $table): array {
    $stmt = $pdo->query("PRAGMA table_info($table)");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map(static fn($row) => $row['name'], $rows);
}

/**
 * Inserts only columns that exist in the current SQLite table.
 *
 * Plain language for DEVS:
 * Narrrfs DB tables have evolved over seasons. This keeps the API compatible
 * without requiring schema changes during game integration.
 */
function adaptive_insert(PDO $pdo, string $table, array $values): void {
    $columns = get_table_columns($pdo, $table);
    $insertValues = [];

    foreach ($values as $column => $value) {
        if (in_array($column, $columns, true)) {
            $insertValues[$column] = $value;
        }
    }

    if (empty($insertValues)) {
        throw new Exception("No compatible columns found for $table");
    }

    $columnNames = array_keys($insertValues);
    $placeholders = array_map(static fn($column) => ':' . $column, $columnNames);

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $columnNames),
        implode(', ', $placeholders)
    );

    $stmt = $pdo->prepare($sql);

    foreach ($insertValues as $column => $value) {
        if (is_int($value)) {
            $stmt->bindValue(':' . $column, $value, PDO::PARAM_INT);
            continue;
        }

        $stmt->bindValue(':' . $column, $value);
    }

    $stmt->execute();
}

/**
 * Calculates DSPOINC from Labyrinth Blast raw gameplay score.
 *
 * Plain language for DEVS:
 * The frontend is allowed to send raw score only.
 * The economy reward is calculated here to avoid client-side reward exploits.
 */
function calculate_dspoinc_reward(int $rawScore, float $roleMultiplier): int {
    if ($rawScore <= 0) {
        return 0;
    }

    $safeMultiplier = max(DEFAULT_ROLE_MULTIPLIER, min(2.0, $roleMultiplier));
    $reward = (int)floor(($rawScore * $safeMultiplier) / DSP_CONVERSION_RATE);

    if ($reward < 1) {
        return 1;
    }

    return min($reward, MAX_DSP_REWARD);
}

/**
 * Validates the Labyrinth Blast score payload.
 */
function validate_score_payload(array $request, float $roleMultiplier): array {
    $rawScore = (int)floor((float)($request['score'] ?? 0));
    $level = (int)floor((float)($request['level'] ?? 1));
    $durationSeconds = (int)floor((float)($request['duration_seconds'] ?? $request['time'] ?? 0));
    $status = trim((string)($request['status'] ?? ''));
    $character = trim((string)($request['character'] ?? ''));

    $allowedStatuses = ['won', 'lost'];
    $allowedCharacters = ['nightfox', 'bear', 'bull'];

    if ($rawScore <= 0) {
        json_response([
            'success' => false,
            'error' => 'Score must be positive'
        ], 400);
    }

    if ($rawScore > MAX_REASONABLE_RAW_SCORE) {
        json_response([
            'success' => false,
            'error' => 'Score exceeds validation limit'
        ], 400);
    }

    if ($level < 1 || $level > 100) {
        json_response([
            'success' => false,
            'error' => 'Invalid level value'
        ], 400);
    }

    if ($durationSeconds < 0 || $durationSeconds > 86400) {
        json_response([
            'success' => false,
            'error' => 'Invalid duration value'
        ], 400);
    }

    if (!in_array($status, $allowedStatuses, true)) {
        json_response([
            'success' => false,
            'error' => 'Invalid run status'
        ], 400);
    }

    if (!in_array($character, $allowedCharacters, true)) {
        json_response([
            'success' => false,
            'error' => 'Invalid character value'
        ], 400);
    }

    $dspoincScore = calculate_dspoinc_reward($rawScore, $roleMultiplier);

    if ($dspoincScore <= 0) {
        json_response([
            'success' => false,
            'error' => 'DSPOINC reward must be positive'
        ], 400);
    }

    return [
        'raw_score' => $rawScore,
        'dspoinc_score' => $dspoincScore,
        'level' => $level,
        'duration_seconds' => $durationSeconds,
        'status' => $status,
        'character' => $character,
        'role_multiplier' => $roleMultiplier
    ];
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
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

    $discordName = get_discord_name($pdo, $userId, $request);
$userRoles = get_user_roles($pdo, $userId);

if (is_localhost_env() && empty($userRoles)) {
    $userRoles = ['VIP Holder'];
}

$roleBonus = calculate_role_multiplier($userRoles);
$validated = validate_score_payload($request, (float)$roleBonus['multiplier']);
$season = get_current_season($pdo);
$timestamp = gmdate('Y-m-d H:i:s');

    $metadata = [
        'raw_score' => $validated['raw_score'],
        'dspoinc_score' => $validated['dspoinc_score'],
        'level' => $validated['level'],
        'duration_seconds' => $validated['duration_seconds'],
        'status' => $validated['status'],
        'character' => $validated['character'],
        'role_multiplier' => $validated['role_multiplier'],
'role_bonus_role' => $roleBonus['role'],
'roles' => $userRoles,
        'conversion_rate' => DSP_CONVERSION_RATE,
        'max_dsp_reward' => MAX_DSP_REWARD,

        'season' => $season
    ];

    $pdo->beginTransaction();

    adaptive_insert($pdo, 'tbl_tetris_scores', [
        'discord_id' => $userId,
        'discord_name' => $discordName,
        'wallet' => $userId,
        'score' => $validated['dspoinc_score'],
        'raw_score' => $validated['raw_score'],
        'game' => GAME_KEY,
        'season' => $season,
        'timestamp' => $timestamp,
        'created_at' => $timestamp,
        'level' => $validated['level'],
        'role_multiplier' => $validated['role_multiplier'],
        'role_bonus_role' => $roleBonus['role'],
        'metadata' => json_encode($metadata)
    ]);

    adaptive_insert($pdo, 'tbl_user_scores', [
        'user_id' => $userId,
        'discord_id' => $userId,
        'score' => $validated['dspoinc_score'],
        'game' => GAME_KEY,
        'source' => GAME_SOURCE,
        'season' => $season,
        'timestamp' => $timestamp,
        'created_at' => $timestamp,
        'reason' => GAME_DISPLAY_NAME . ' game reward',
        'metadata' => json_encode($metadata)
    ]);

    adaptive_insert($pdo, 'tbl_score_adjustments', [
        'user_id' => $userId,
        'discord_id' => $userId,
        'amount' => $validated['dspoinc_score'],
        'action' => 'add',
        'reason' => GAME_DISPLAY_NAME . ' game score: ' . $validated['dspoinc_score'] . ' DSPOINC from raw score ' . $validated['raw_score'],
        'source' => GAME_SOURCE,
        'game' => GAME_KEY,
        'season' => $season,
        'timestamp' => $timestamp,
        'created_at' => $timestamp,
        'admin_id' => 'system',
        'metadata' => json_encode($metadata)
    ]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => GAME_DISPLAY_NAME . ' score saved',
        'game' => GAME_KEY,
        'season' => $season,
        'user_id' => $userId,
        'discord_name' => $discordName,
        'raw_score' => $validated['raw_score'],
        'dspoinc_score' => $validated['dspoinc_score'],
        'role_multiplier' => $validated['role_multiplier'],
        'role_bonus_role' => $roleBonus['role'],
        'level' => $validated['level'],
        'status' => $validated['status'],
        'character' => $validated['character'],
        'duration_seconds' => $validated['duration_seconds']
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Labyrinth Blast save error: ' . $e->getMessage());

    json_response([
        'success' => false,
        'error' => GAME_DISPLAY_NAME . ' score save failed',
        'details' => is_localhost_env() ? $e->getMessage() : null
    ], 500);
}