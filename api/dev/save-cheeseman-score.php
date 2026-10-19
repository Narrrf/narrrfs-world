<?php
declare(strict_types=1);

// CHEESE RUNNER SECURITY RECOVERY V1.
//
// DEVS FOR DECADES:
// The browser may report raw gameplay facts, but it never decides identity,
// Discord role multiplier, final DSPOINC reward, season, or display name.
//
// Production reward transaction:
//   tbl_tetris_scores      = seasonal leaderboard
//   tbl_user_scores        = DSPOINC ledger
//   tbl_score_adjustments  = Profile/audit history
//
// Those three writes are atomic.
//
// Important limitation:
// Raw gameplay score still originates in the browser. Authentication and
// server-side reward calculation prevent anonymous/client-selected payouts,
// but they do not constitute server-proven gameplay.

error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

const CHEESEMAN_GAME_KEY = 'cheeseman';
const CHEESEMAN_GAME_SOURCE = 'game_reward';
const CHEESEMAN_CONVERSION_RATE = 25;
const CHEESEMAN_MAX_RAW_SCORE = 250000;
const CHEESEMAN_MAX_LEVEL = 100;
const CHEESEMAN_MAX_LIVES = 3;
const CHEESEMAN_MAX_REQUEST_BYTES = 8192;
const CHEESEMAN_REPLAY_WINDOW_SECONDS = 30;

/**
 * Finish one API request with a bounded JSON response.
 */
function cheeseman_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

/**
 * Remove emoji characters used by Discord role presentation so both
 * "VIP Holder" and "🎴 VIP Holder" resolve to the same server rule.
 */
function cheeseman_normalize_role_name(string $roleName): string
{
    $normalized = preg_replace(
        '/[\x{1F300}-\x{1FAFF}]/u',
        '',
        $roleName
    );

    return trim(
        is_string($normalized)
            ? $normalized
            : $roleName
    );
}

/**
 * Resolve the strongest approved Cheese Runner multiplier from server-owned
 * Discord roles. Browser role_multiplier values are never consulted.
 */
function cheeseman_role_bonus(array $roles): array
{
    $multipliers = [
        'VIP Holder' => 2.0,
        'Holder' => 1.5,
        'Champion' => 1.4,
        'WL' => 1.3,
        'Season Tester' => 1.3,
        'Early Bird' => 1.2,
        'Cheese Hunter' => 1.1,
    ];

    $bestRole = null;
    $bestMultiplier = 1.0;

    foreach ($roles as $storedRole) {
        $roleName = trim((string)$storedRole);

        foreach ([
            $roleName,
            cheeseman_normalize_role_name($roleName)
        ] as $candidate) {
            if (!isset($multipliers[$candidate])) {
                continue;
            }

            $candidateMultiplier = (float)$multipliers[$candidate];

            if ($candidateMultiplier > $bestMultiplier) {
                $bestMultiplier = $candidateMultiplier;
                $bestRole = $roleName;
            }
        }
    }

    return [
        'role' => $bestRole,
        'multiplier' => $bestMultiplier,
    ];
}

/**
 * Calculate DSPOINC using the current Cheese Runner production rule:
 *
 * floor(raw score × server role multiplier / 25)
 *
 * The final reward can never be supplied by the browser.
 */
function cheeseman_calculate_reward(
    int $rawScore,
    float $roleMultiplier
): int {
    if ($rawScore <= 0) {
        return 0;
    }

    $safeMultiplier = max(
        1.0,
        min(2.0, $roleMultiplier)
    );

    return (int)floor(
        ($rawScore * $safeMultiplier)
        / CHEESEMAN_CONVERSION_RATE
    );
}

$requestMethod = strtoupper(
    (string)($_SERVER['REQUEST_METHOD'] ?? '')
);

if ($requestMethod !== 'POST') {
    header('Allow: POST');

    cheeseman_response(405, [
        'success' => false,
        'code' => 'method_not_allowed',
        'error' => 'POST is required.',
    ]);
}

$contentType = strtolower(trim(
    explode(
        ';',
        (string)($_SERVER['CONTENT_TYPE'] ?? ''),
        2
    )[0]
));

if ($contentType !== 'application/json') {
    cheeseman_response(415, [
        'success' => false,
        'code' => 'unsupported_media_type',
        'error' => 'application/json is required.',
    ]);
}

$origin = strtolower(rtrim(
    trim((string)($_SERVER['HTTP_ORIGIN'] ?? '')),
    '/'
));

if (
    $origin !== '' &&
    !in_array($origin, [
        'https://narrrfs.world',
        'http://localhost',
        'http://127.0.0.1',
    ], true)
) {
    cheeseman_response(403, [
        'success' => false,
        'code' => 'origin_rejected',
        'error' => 'Request origin is not allowed.',
    ]);
}

$fetchSite = strtolower(trim(
    (string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')
));

if (
    $fetchSite !== '' &&
    !in_array(
        $fetchSite,
        ['same-origin', 'same-site', 'none'],
        true
    )
) {
    cheeseman_response(403, [
        'success' => false,
        'code' => 'cross_site_rejected',
        'error' => 'Cross-site score submission is not allowed.',
    ]);
}

require_once __DIR__ . '/../config/session.php';

$sessionDiscordId = trim(
    (string)($_SESSION['discord_id'] ?? '')
);

if (
    !preg_match(
        '/\A[0-9]{17,20}\z/',
        $sessionDiscordId
    )
) {
    cheeseman_response(401, [
        'success' => false,
        'code' => 'authentication_required',
        'error' => 'Authentication is required.',
    ]);
}

$rawBody = file_get_contents('php://input');

if (
    !is_string($rawBody) ||
    $rawBody === '' ||
    strlen($rawBody) > CHEESEMAN_MAX_REQUEST_BYTES
) {
    cheeseman_response(400, [
        'success' => false,
        'code' => 'invalid_request_body',
        'error' => 'A bounded JSON object is required.',
    ]);
}

$request = json_decode(
    $rawBody,
    true
);

if (
    !is_array($request) ||
    json_last_error() !== JSON_ERROR_NONE
) {
    cheeseman_response(400, [
        'success' => false,
        'code' => 'invalid_json',
        'error' => 'A valid JSON object is required.',
    ]);
}

$requestDiscordId = trim(
    (string)($request['discord_id'] ?? '')
);

if (
    !preg_match(
        '/\A[0-9]{17,20}\z/',
        $requestDiscordId
    )
) {
    cheeseman_response(400, [
        'success' => false,
        'code' => 'invalid_request_identity',
        'error' => 'A valid Discord identity is required.',
    ]);
}

if (
    !hash_equals(
        $sessionDiscordId,
        $requestDiscordId
    )
) {
    cheeseman_response(403, [
        'success' => false,
        'code' => 'identity_mismatch',
        'error' => 'The request identity does not match the authenticated session.',
    ]);
}

$requestUserId = trim(
    (string)($request['user_id'] ?? '')
);

if (
    $requestUserId !== '' &&
    !hash_equals(
        $sessionDiscordId,
        $requestUserId
    )
) {
    cheeseman_response(403, [
        'success' => false,
        'code' => 'user_identity_mismatch',
        'error' => 'The request user does not match the authenticated session.',
    ]);
}

$requestGame = strtolower(trim(
    (string)($request['game'] ?? CHEESEMAN_GAME_KEY)
));

if ($requestGame !== CHEESEMAN_GAME_KEY) {
    cheeseman_response(400, [
        'success' => false,
        'code' => 'invalid_game',
        'error' => 'Invalid Cheese Runner game key.',
    ]);
}

$submittedRawScore = $request['raw_score']
    ?? $request['score']
    ?? null;

if (
    (!is_int($submittedRawScore) &&
        !is_float($submittedRawScore)) ||
    !is_finite((float)$submittedRawScore)
) {
    cheeseman_response(400, [
        'success' => false,
        'code' => 'invalid_raw_score',
        'error' => 'Raw score must be a JSON number.',
    ]);
}

$rawScore = (int)floor(
    (float)$submittedRawScore
);

if (
    $rawScore <= 0 ||
    $rawScore > CHEESEMAN_MAX_RAW_SCORE
) {
    cheeseman_response(400, [
        'success' => false,
        'code' => 'raw_score_out_of_range',
        'error' => 'Raw score is outside the accepted range.',
    ]);
}

$levelValue = $request['level'] ?? 1;

if (
    !is_int($levelValue) &&
    !is_float($levelValue)
) {
    cheeseman_response(400, [
        'success' => false,
        'code' => 'invalid_level',
        'error' => 'Level is invalid.',
    ]);
}

$level = (int)floor(
    (float)$levelValue
);

if (
    $level < 1 ||
    $level > CHEESEMAN_MAX_LEVEL
) {
    cheeseman_response(400, [
        'success' => false,
        'code' => 'invalid_level',
        'error' => 'Level is outside the accepted range.',
    ]);
}

$livesValue = $request['lives_remaining'] ?? 0;

if (
    !is_int($livesValue) &&
    !is_float($livesValue)
) {
    cheeseman_response(400, [
        'success' => false,
        'code' => 'invalid_lives',
        'error' => 'Lives value is invalid.',
    ]);
}

$livesRemaining = (int)floor(
    (float)$livesValue
);

if (
    $livesRemaining < 0 ||
    $livesRemaining > CHEESEMAN_MAX_LIVES
) {
    cheeseman_response(400, [
        'success' => false,
        'code' => 'invalid_lives',
        'error' => 'Lives value is outside the accepted range.',
    ]);
}

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

if (
    !is_file($dbPath) ||
    !is_writable($dbPath)
) {
    cheeseman_response(503, [
        'success' => false,
        'code' => 'score_store_unavailable',
        'error' => 'Score storage is unavailable.',
    ]);
}

try {
    $db = new PDO(
        'sqlite:' . $dbPath,
        null,
        null,
        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );

    $db->exec(
        'PRAGMA busy_timeout = 5000'
    );

    /*
     * Exactly one active season is required.
     * Never silently fall back to a stale historical season.
     */
    $seasonStmt = $db->query(
        'SELECT season_name
         FROM tbl_seasons
         WHERE is_active = 1
         ORDER BY start_date DESC
         LIMIT 2'
    );

    $activeSeasons = $seasonStmt->fetchAll(
        PDO::FETCH_COLUMN
    );

    if (
        count($activeSeasons) !== 1 ||
        trim((string)$activeSeasons[0]) === ''
    ) {
        cheeseman_response(503, [
            'success' => false,
            'code' => 'active_season_unavailable',
            'error' => 'Active season state is unavailable.',
        ]);
    }

    $season = trim(
        (string)$activeSeasons[0]
    );

    /*
     * Display identity is resolved from server-owned data.
     * Browser discord_name is ignored.
     */
    $nameStmt = $db->prepare(
        'SELECT username
         FROM tbl_users
         WHERE discord_id = ?
         LIMIT 1'
    );

    $nameStmt->execute([
        $sessionDiscordId
    ]);

    $discordName = trim(
        (string)$nameStmt->fetchColumn()
    );

    if ($discordName === '') {
        $discordName = $sessionDiscordId;
    }

    /*
     * Economy multiplier is also server-owned.
     */
    $roleStmt = $db->prepare(
        'SELECT role_name
         FROM tbl_user_roles
         WHERE user_id = ?'
    );

    $roleStmt->execute([
        $sessionDiscordId
    ]);

    $storedRoles = $roleStmt->fetchAll(
        PDO::FETCH_COLUMN
    );

    $roleBonus = cheeseman_role_bonus(
        is_array($storedRoles)
            ? $storedRoles
            : []
    );

    $roleMultiplier = (float)(
        $roleBonus['multiplier'] ?? 1.0
    );

    $reward = cheeseman_calculate_reward(
        $rawScore,
        $roleMultiplier
    );

    if ($reward <= 0) {
        cheeseman_response(400, [
            'success' => false,
            'code' => 'reward_below_minimum',
            'error' => 'Score does not produce a positive DSPOINC reward.',
        ]);
    }

    $db->beginTransaction();

    /*
     * Short exact-result replay protection.
     *
     * The historical leaderboard stores the DSPOINC reward in score.
     * A retry of the same reward within 30 seconds must never create
     * a second economic credit.
     */
    $duplicateStmt = $db->prepare(
        "SELECT id
         FROM tbl_tetris_scores
         WHERE discord_id = ?
           AND game = ?
           AND season = ?
           AND score = ?
           AND timestamp >= datetime(
               'now',
               '-30 seconds'
           )
         ORDER BY id DESC
         LIMIT 1"
    );

    $duplicateStmt->execute([
        $sessionDiscordId,
        CHEESEMAN_GAME_KEY,
        $season,
        $reward,
    ]);

    $existingScoreId =
        $duplicateStmt->fetchColumn();

    if ($existingScoreId !== false) {
        /*
         * Confirm that the prior transaction also created its audit.
         * If not, fail closed rather than issuing another reward.
         */
        $reasonMarker =
            '(score_id: ' .
            (int)$existingScoreId .
            ')';

        $auditStmt = $db->prepare(
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

        $auditStmt->execute([
            $sessionDiscordId,
            $reward,
            '%' . $reasonMarker,
        ]);

        $existingAuditId =
            $auditStmt->fetchColumn();

        $db->commit();

        if ($existingAuditId === false) {
            cheeseman_response(409, [
                'success' => false,
                'code' =>
                    'duplicate_reward_state_unverified',
                'error' =>
                    'A matching leaderboard result exists without a verified reward audit. No additional reward was credited.',
                'score_id' =>
                    (int)$existingScoreId,
            ]);
        }

        cheeseman_response(200, [
            'success' => true,
            'duplicate' => true,
            'score_id' =>
                (int)$existingScoreId,
            'game' =>
                CHEESEMAN_GAME_KEY,
            'season' => $season,
            'user_id' =>
                $sessionDiscordId,
            'discord_name' =>
                $discordName,
            'raw_score' => $rawScore,
            'dspoinc_score' =>
                $reward,
            'role_multiplier' =>
                $roleMultiplier,
            'role_bonus_role' =>
                $roleBonus['role'],
            'level' => $level,
            'lives_remaining' =>
                $livesRemaining,
        ]);
    }

    /*
     * All three writes below are one transaction.
     */
    $leaderboardStmt = $db->prepare(
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

    $leaderboardStmt->execute([
        $sessionDiscordId,
        $reward,
        $sessionDiscordId,
        $discordName,
        CHEESEMAN_GAME_KEY,
        $season,
    ]);

    $scoreId = (int)$db->lastInsertId();

    /*
     * Explicit season prevents the historical season_2 table default
     * from corrupting current-season ledger attribution.
     */
    $ledgerStmt = $db->prepare(
        'INSERT INTO tbl_user_scores
         (
             user_id,
             game,
             score,
             source,
             season
         )
         VALUES (?, ?, ?, ?, ?)'
    );

    $ledgerStmt->execute([
        $sessionDiscordId,
        CHEESEMAN_GAME_KEY,
        $reward,
        CHEESEMAN_GAME_SOURCE,
        $season,
    ]);

    $rewardReason =
        'Cheese Runner game reward: ' .
        $reward .
        ' DSPOINC from raw score ' .
        $rawScore .
        ' at server role multiplier ' .
        number_format(
            $roleMultiplier,
            1,
            '.',
            ''
        ) .
        'x (score_id: ' .
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
        $reward,
        'add',
        $rewardReason,
    ]);

    $db->commit();

    cheeseman_response(200, [
        'success' => true,
        'duplicate' => false,
        'message' =>
            'Cheese Runner score and DSPOINC reward saved.',
        'score_id' => $scoreId,
        'game' => CHEESEMAN_GAME_KEY,
        'season' => $season,
        'user_id' =>
            $sessionDiscordId,
        'discord_name' =>
            $discordName,
        'raw_score' => $rawScore,
        'dspoinc_score' =>
            $reward,
        'role_multiplier' =>
            $roleMultiplier,
        'role_bonus_role' =>
            $roleBonus['role'],
        'level' => $level,
        'lives_remaining' =>
            $livesRemaining,
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
        'Cheese Runner secure score save failed: ' .
        $error->getMessage()
    );

    cheeseman_response(500, [
        'success' => false,
        'code' => 'score_save_failed',
        'error' =>
            'Cheese Runner score saving failed.',
    ]);
}
