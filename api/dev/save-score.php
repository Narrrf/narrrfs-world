<?php
declare(strict_types=1);

// SECURITY RECOVERY V2 — authenticated score + audited DSPOINC persistence.
// DEVS FOR DECADES:
// Tetris, Snake, and Space Invaders atomically persist the seasonal leaderboard
// row, DSPOINC ledger credit, and Profile/audit adjustment.
// Glyph Memory remains leaderboard-only and never credits DSPOINC.
// WL grants and season-setting creation remain disabled.
// Client results are session-bound and bounded but are not server-proven gameplay results.

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

function score_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
if ($requestMethod !== 'POST') {
    header('Allow: POST');
    score_response(405, [
        'success' => false,
        'code' => 'method_not_allowed',
        'error' => 'POST is required.'
    ]);
}

$contentType = strtolower(trim(
    explode(';', (string)($_SERVER['CONTENT_TYPE'] ?? ''), 2)[0]
));

if ($contentType !== 'application/json') {
    score_response(415, [
        'success' => false,
        'code' => 'unsupported_media_type',
        'error' => 'application/json is required.'
    ]);
}

$origin = strtolower(rtrim(
    trim((string)($_SERVER['HTTP_ORIGIN'] ?? '')),
    '/'
));

if ($origin !== '' && !in_array($origin, [
    'https://narrrfs.world',
    'https://narrrfs-world.onrender.com',
    'http://localhost',
    'http://127.0.0.1',
], true)) {
    score_response(403, [
        'success' => false,
        'code' => 'origin_rejected',
        'error' => 'Request origin is not allowed.'
    ]);
}

$fetchSite = strtolower(trim(
    (string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')
));

if (
    $fetchSite !== '' &&
    !in_array($fetchSite, ['same-origin', 'same-site', 'none'], true)
) {
    score_response(403, [
        'success' => false,
        'code' => 'cross_site_rejected',
        'error' => 'Cross-site score submission is not allowed.'
    ]);
}

require_once __DIR__ . '/../config/session.php';

$sessionDiscordId = trim((string)($_SESSION['discord_id'] ?? ''));

if (!preg_match('/\A[0-9]{17,20}\z/', $sessionDiscordId)) {
    score_response(401, [
        'success' => false,
        'code' => 'authentication_required',
        'error' => 'Authentication is required.'
    ]);
}

$rawBody = file_get_contents('php://input');

if (
    !is_string($rawBody) ||
    $rawBody === '' ||
    strlen($rawBody) > 8192
) {
    score_response(400, [
        'success' => false,
        'code' => 'invalid_request_body',
        'error' => 'A bounded JSON object is required.'
    ]);
}

$requestData = json_decode($rawBody, true);

if (
    !is_array($requestData) ||
    json_last_error() !== JSON_ERROR_NONE
) {
    score_response(400, [
        'success' => false,
        'code' => 'invalid_json',
        'error' => 'A valid JSON object is required.'
    ]);
}

$requestDiscordId = trim(
    (string)($requestData['discord_id'] ?? '')
);

if (!preg_match('/\A[0-9]{17,20}\z/', $requestDiscordId)) {
    score_response(400, [
        'success' => false,
        'code' => 'invalid_request_identity',
        'error' => 'A valid request identity is required.'
    ]);
}

if (!hash_equals($sessionDiscordId, $requestDiscordId)) {
    score_response(403, [
        'success' => false,
        'code' => 'identity_mismatch',
        'error' => 'The request identity does not match the authenticated session.'
    ]);
}

$game = strtolower(trim(
    (string)($requestData['game'] ?? '')
));

$allowedGames = [
    'tetris',
    'snake',
    'space_invaders',
    'glyph_memory'
];

if (!in_array($game, $allowedGames, true)) {
    score_response(400, [
        'success' => false,
        'code' => 'unsupported_game',
        'error' => 'This game is not allowed on the shared score route.'
    ]);
}

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

if (!is_file($dbPath) || !is_writable($dbPath)) {
    score_response(503, [
        'success' => false,
        'code' => 'score_store_unavailable',
        'error' => 'Score storage is unavailable.'
    ]);
}

try {
    $db = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);

    $db->exec('PRAGMA busy_timeout = 5000');

    /*
     * One active season only.
     * Missing or ambiguous season state fails closed.
     */
    $seasonStmt = $db->query(
        'SELECT season_name
         FROM tbl_seasons
         WHERE is_active = 1
         ORDER BY start_date DESC
         LIMIT 2'
    );

    $activeSeasons = $seasonStmt->fetchAll(PDO::FETCH_COLUMN);

    if (
        count($activeSeasons) !== 1 ||
        trim((string)$activeSeasons[0]) === ''
    ) {
        score_response(503, [
            'success' => false,
            'code' => 'active_season_unavailable',
            'error' => 'Active season state is unavailable.'
        ]);
    }

    $season = trim((string)$activeSeasons[0]);

    /*
     * Resolve the display name from server-owned data.
     * Never persist the browser-supplied discord_name.
     */
    $nameStmt = $db->prepare(
        'SELECT username
         FROM tbl_users
         WHERE discord_id = ?
         LIMIT 1'
    );

    $nameStmt->execute([$sessionDiscordId]);

    $discordName = trim(
        (string)$nameStmt->fetchColumn()
    );

    if ($discordName === '') {
        $discordName = $sessionDiscordId;
    }

    /*
     * Glyph Memory is leaderboard-only and has no reward write.
     */
    if ($game === 'glyph_memory') {
        $difficulty = strtolower(trim(
            (string)($requestData['difficulty'] ?? '')
        ));

        $expectedPairs = [
            'easy' => 6,
            'medium' => 8,
            'hard' => 12
        ];

        $timeMs = $requestData['time_ms'] ?? null;
        $pairsMatched = $requestData['pairs_matched'] ?? null;

        if (!isset($expectedPairs[$difficulty])) {
            score_response(400, [
                'success' => false,
                'code' => 'invalid_glyph_difficulty',
                'error' => 'Glyph difficulty is invalid.'
            ]);
        }

        if (
            !is_int($timeMs) ||
            $timeMs < 1000 ||
            $timeMs > 3600000
        ) {
            score_response(400, [
                'success' => false,
                'code' => 'invalid_glyph_time',
                'error' => 'Glyph completion time is outside the accepted range.'
            ]);
        }

        if (
            !is_int($pairsMatched) ||
            $pairsMatched !== $expectedPairs[$difficulty]
        ) {
            score_response(400, [
                'success' => false,
                'code' => 'invalid_glyph_pairs',
                'error' => 'Glyph pair count does not match difficulty.'
            ]);
        }

        $db->beginTransaction();

        /*
         * Short replay guard.
         * A browser retry of the exact result returns success without
         * inserting another leaderboard row.
         */
        $duplicateStmt = $db->prepare(
            "SELECT id
             FROM tbl_glyph_memory_scores
             WHERE discord_id = ?
               AND difficulty = ?
               AND time_ms = ?
               AND pairs_matched = ?
               AND season = ?
               AND timestamp >= datetime('now', '-30 seconds')
             ORDER BY id DESC
             LIMIT 1"
        );

        $duplicateStmt->execute([
            $sessionDiscordId,
            $difficulty,
            $timeMs,
            $pairsMatched,
            $season
        ]);

        $existingId = $duplicateStmt->fetchColumn();

        if ($existingId !== false) {
            $db->commit();

            score_response(200, [
                'success' => true,
                'score_id' => (int)$existingId,
                'duplicate' => true,
                'game' => $game,
                'season' => $season,
                'difficulty' => $difficulty,
                'time_ms' => $timeMs,
                'pairs_matched' => $pairsMatched,
                'leaderboard_only' => true,
                'rewards_credited' => false
            ]);
        }

        $insertStmt = $db->prepare(
            'INSERT INTO tbl_glyph_memory_scores
             (
                 discord_id,
                 discord_name,
                 difficulty,
                 time_ms,
                 pairs_matched,
                 season
             )
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $insertStmt->execute([
            $sessionDiscordId,
            $discordName,
            $difficulty,
            $timeMs,
            $pairsMatched,
            $season
        ]);

        $scoreId = (int)$db->lastInsertId();

        $db->commit();

        score_response(200, [
            'success' => true,
            'message' => 'Glyph leaderboard result saved. Rewards remain disabled on this recovery route.',
            'score_id' => $scoreId,
            'duplicate' => false,
            'game' => $game,
            'season' => $season,
            'difficulty' => $difficulty,
            'time_ms' => $timeMs,
            'pairs_matched' => $pairsMatched,
            'leaderboard_only' => true,
            'rewards_credited' => false
        ]);
    }

    /*
     * Classic arcade score validation.
     */
    $submittedScore = $requestData['score'] ?? null;

    if (
        (!is_int($submittedScore) && !is_float($submittedScore)) ||
        !is_finite((float)$submittedScore) ||
        $submittedScore <= 0
    ) {
        score_response(400, [
            'success' => false,
            'code' => 'invalid_score',
            'error' => 'Score must be a positive JSON number.'
        ]);
    }

    /*
     * Read existing Season 16-style limits.
     * Never create or mutate settings from a score submission.
     */
    $settingsStmt = $db->prepare(
        'SELECT
             tetris_max_score,
             snake_max_score,
             space_invaders_max_score
         FROM tbl_season_settings
         WHERE season_name = ?
         LIMIT 2'
    );

    $settingsStmt->execute([$season]);

    $settingsRows = $settingsStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    if (count($settingsRows) !== 1) {
        score_response(503, [
            'success' => false,
            'code' => 'season_settings_unavailable',
            'error' => 'Season settings are unavailable.'
        ]);
    }

    $settings = $settingsRows[0];

    $maxScoreKey = match ($game) {
        'tetris' => 'tetris_max_score',
        'snake' => 'snake_max_score',
        'space_invaders' => 'space_invaders_max_score',
    };

    $maxScore = (int)(
        $settings[$maxScoreKey] ?? 0
    );

    /*
     * Preserve the verified frontend contract:
     *
     * Tetris / Snake:
     * frontend already submits the stored leaderboard value.
     *
     * Space Invaders:
     * frontend submits its displayed DSPOINC value and the historical
     * backend contract stores floor(score / 10).
     */
    $storedScore = $game === 'space_invaders'
        ? (int)floor((float)$submittedScore / 10)
        : (int)round((float)$submittedScore);

    if (
        $maxScore <= 0 ||
        $storedScore <= 0 ||
        $storedScore > $maxScore
    ) {
        score_response(400, [
            'success' => false,
            'code' => 'score_out_of_range',
            'error' => 'Score is outside the active season limit.',
            'max_score' => $maxScore
        ]);
    }

    $db->beginTransaction();

    /*
     * Prevent immediate exact replay without introducing a schema migration.
     */
    $duplicateStmt = $db->prepare(
        "SELECT id
         FROM tbl_tetris_scores
         WHERE discord_id = ?
           AND game = ?
           AND season = ?
           AND score = ?
           AND timestamp >= datetime('now', '-30 seconds')
         ORDER BY id DESC
         LIMIT 1"
    );

    $duplicateStmt->execute([
        $sessionDiscordId,
        $game,
        $season,
        $storedScore
    ]);

    $existingId = $duplicateStmt->fetchColumn();

    if ($existingId !== false) {
        /*
         * A duplicate leaderboard row is safe only when this V2 route can
         * verify that the original atomic transaction also wrote its reward
         * audit. Never issue a second reward merely because the client retried.
         */
        $rewardReasonMarker = '(score_id: ' . (int)$existingId . ')';

        $rewardAuditStmt = $db->prepare(
            "SELECT id
             FROM tbl_score_adjustments
             WHERE user_id = ?
               AND admin_id = 'system'
               AND amount = ?
               AND action = 'add'
               AND reason LIKE ?
             ORDER BY id DESC
             LIMIT 1"
        );

        $rewardAuditStmt->execute([
            $sessionDiscordId,
            $storedScore,
            '%' . $rewardReasonMarker
        ]);

        $rewardAuditId = $rewardAuditStmt->fetchColumn();

        $db->commit();

        if ($rewardAuditId === false) {
            score_response(409, [
                'success' => false,
                'code' => 'duplicate_reward_state_unverified',
                'error' => 'A matching leaderboard result exists without a verified reward audit. No additional reward was credited.',
                'score_id' => (int)$existingId,
                'game' => $game,
                'season' => $season
            ]);
        }

        score_response(200, [
            'success' => true,
            'score_id' => (int)$existingId,
            'duplicate' => true,
            'game' => $game,
            'season' => $season,
            'raw_score' => $submittedScore,
            'dspoinc_score' => $storedScore,
            'max_score' => $maxScore,
            'leaderboard_only' => false,
            'rewards_credited' => true
        ]);
    }

    /*
     * wallet is required by the historical table schema.
     * Use the authenticated Discord subject instead of client wallet data.
     */
    $insertStmt = $db->prepare(
        'INSERT INTO tbl_tetris_scores
         (
             wallet,
             score,
             discord_id,
             discord_name,
             game,
             season
         )
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $insertStmt->execute([
        $sessionDiscordId,
        $storedScore,
        $sessionDiscordId,
        $discordName,
        $game,
        $season
    ]);

    $scoreId = (int)$db->lastInsertId();

    /*
     * Append the real DSPOINC credit inside the SAME transaction as the
     * leaderboard row. A failure in either ledger/audit write rolls back all
     * three records, preventing leaderboard/economy divergence.
     */
    $ledgerStmt = $db->prepare(
        'INSERT INTO tbl_user_scores
         (
             user_id,
             score,
             game,
             source,
             season
         )
         VALUES (?, ?, ?, ?, ?)'
    );

    $ledgerStmt->execute([
        $sessionDiscordId,
        $storedScore,
        $game,
        'game_score',
        $season
    ]);

    $gameDisplayName = match ($game) {
        'tetris' => 'Tetris',
        'snake' => 'Snake',
        'space_invaders' => 'Space Invaders',
    };

    $rewardReason =
        $gameDisplayName .
        ' game reward: ' .
        $storedScore .
        ' DSPOINC (score_id: ' .
        $scoreId .
        ')';

    $adjustmentStmt = $db->prepare(
        'INSERT INTO tbl_score_adjustments
         (
             user_id,
             admin_id,
             amount,
             action,
             reason
         )
         VALUES (?, ?, ?, ?, ?)'
    );

    $adjustmentStmt->execute([
        $sessionDiscordId,
        'system',
        $storedScore,
        'add',
        $rewardReason
    ]);

    $db->commit();

    score_response(200, [
        'success' => true,
        'message' => 'Leaderboard score and DSPOINC reward saved.',
        'score_id' => $scoreId,
        'duplicate' => false,
        'game' => $game,
        'season' => $season,
        'raw_score' => $submittedScore,
        'dspoinc_score' => $storedScore,
        'max_score' => $maxScore,
        'leaderboard_only' => false,
        'rewards_credited' => true,
        'wl_check' => [
            'eligible' => false,
            'message' => 'WL grants remain disabled on this recovery route.'
        ]
    ]);
} catch (Throwable $error) {
    if (
        isset($db) &&
        $db instanceof PDO &&
        $db->inTransaction()
    ) {
        $db->rollBack();
    }

    error_log(
        'Authenticated score save failed: ' .
        $error->getMessage()
    );

    score_response(500, [
        'success' => false,
        'code' => 'score_save_failed',
        'error' => 'Score saving failed.'
    ]);
}