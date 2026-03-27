<?php
session_start();
header('Content-Type: application/json');

/**
 * Return a JSON response and stop execution.
 */
function json_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running in localhost-style development.
 */
function is_local_development(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Resolve the active user with session-first auth in production.
 */
function resolve_user_id(): string {
    $LOCAL_TEST_DISCORD_ID = '328601656659017732';

    $isLocalDevelopment = is_local_development();
    $sessionUserId = $_SESSION['discord_id'] ?? '';
    $requestUserId = trim((string)($_GET['user_id'] ?? ''));

    if ($isLocalDevelopment && $requestUserId !== '') {
        return $requestUserId;
    }

    $userId = $sessionUserId;

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch'
        ], 403);
    }

    if ($userId === '' && $isLocalDevelopment) {
        return $LOCAL_TEST_DISCORD_ID;
    }

    return $userId;
}

/**
 * Build the database path for local or production environments.
 */
function get_database_path(): string {
    if (is_local_development()) {
        return __DIR__ . '/../../db/narrrf_world.sqlite';
    }

    return '/var/www/html/db/narrrf_world.sqlite';
}

/**
 * Convert a Discord avatar hash into a full CDN URL when needed.
 */
function normalize_avatar_url(string $userId, ?string $avatarUrl): string {
    $avatarUrl = trim((string)$avatarUrl);

    if ($avatarUrl === '') {
        return 'https://cdn.discordapp.com/embed/avatars/0.png';
    }

    if (strpos($avatarUrl, 'http') === 0) {
        return $avatarUrl;
    }

    return "https://cdn.discordapp.com/avatars/{$userId}/{$avatarUrl}.png";
}

try {
    $userId = resolve_user_id();

    if ($userId === '') {
        json_response([
            'success' => false,
            'error' => 'Not authenticated'
        ], 401);
    }

    $dbPath = get_database_path();
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /**
     * Get basic user identity data.
     */
    $stmt = $db->prepare("
        SELECT username, avatar_url
        FROM tbl_users
        WHERE discord_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    /**
     * Get canonical total DSPOINC from the score ledger.
     */
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(score), 0) AS total_dspoinc
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $totalRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $totalDspoinc = (int)($totalRow['total_dspoinc'] ?? 0);

    /**
     * Get frozen DSPOINC from active stakes.
     */
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) AS frozen_dspoinc
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $frozenRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $frozenDspoinc = (int)($frozenRow['frozen_dspoinc'] ?? 0);

    /**
     * Canonical available DSPOINC.
     */
    $availableDspoinc = max(0, $totalDspoinc - $frozenDspoinc);

    /**
     * Get guilds from session.
     */
    $guilds = $_SESSION['guilds'] ?? [];

    /**
     * Get total adjustment count.
     */
    $stmt = $db->prepare("
        SELECT COUNT(*) AS adj_count
        FROM tbl_score_adjustments
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $adjustments = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    /**
     * Get unique score source count.
     */
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT source) AS source_count
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $sources = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    /**
     * Get the first score adjustment date.
     */
    $stmt = $db->prepare("
        SELECT MIN(timestamp) AS first_score
        FROM tbl_score_adjustments
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $firstScore = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    /**
     * Get the user's role count.
     */
    $stmt = $db->prepare("
        SELECT COUNT(*) AS role_count
        FROM tbl_user_roles
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $roles = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    json_response([
        'success' => true,
        'discord_id' => $userId,
        'discord_name' => (string)($user['username'] ?? 'Guest'),
        'avatar_url' => normalize_avatar_url($userId, $user['avatar_url'] ?? ''),
        'guilds' => is_array($guilds) ? $guilds : [],
        'total_dspoinc' => $totalDspoinc,
        'frozen_dspoinc' => $frozenDspoinc,
        'available_dspoinc' => $availableDspoinc,
        'total_spoinc' => (int)floor($totalDspoinc / 10000),
        'available_spoinc' => (int)floor($availableDspoinc / 10000),
        'stats' => [
            'adjustments_count' => (int)($adjustments['adj_count'] ?? 0),
            'source_count' => (int)($sources['source_count'] ?? 0),
            'first_score_date' => $firstScore['first_score'] ?? null,
            'role_count' => (int)($roles['role_count'] ?? 0)
        ]
    ]);
} catch (Exception $e) {
    json_response([
        'success' => false,
        'error' => 'Failed to load score total',
        'details' => $e->getMessage()
    ], 500);
}
?>