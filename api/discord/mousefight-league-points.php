<?php
/**
 * MouseFight Genesis League Points API.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint owns the permanent competitive League Point ledger for Genesis
 * mice. League Points belong to token_id + collection, not to the current
 * Discord owner.
 *
 * League Points are deliberately separate from:
 * - Genesis League Power;
 * - MouseFight combat power;
 * - permanent Traits and Abilities;
 * - Lab progression;
 * - Genetic Items;
 * - DSPOINC / SPOINC;
 * - PVP escrow and settlement;
 * - Fight Recovery;
 * - token payouts and rewards.
 *
 * A point can be awarded only after the authoritative database independently
 * proves a finished competitive League matchmaking PVP and its persisted winner.
 *
 * This endpoint never accepts arbitrary SQL.
 */

if (function_exists('ob_end_clean')) {
    @ob_end_clean();
}

if (function_exists('ob_clean')) {
    @ob_clean();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

const MOUSEFIGHT_LEAGUE_POINTS_CONTRACT =
    'mousefight_genesis_league_points_v1';

const MOUSEFIGHT_LEAGUE_POINTS_PVP_MODE =
    'pvp_challenge';

const MOUSEFIGHT_LEAGUE_POINTS_FINISHED_STATUS =
    'finished';

const MOUSEFIGHT_LEAGUE_POINTS_EXACT_MODE =
    'exact';

const MOUSEFIGHT_LEAGUE_POINTS_MODE_VERSION =
    'mousefight_genesis_league_mode_v1';

const MOUSEFIGHT_LEAGUE_POINTS_SOURCE =
    'matchmaking';

const MOUSEFIGHT_LEAGUE_POINTS_COMPETITIVE_VERSION =
    'mousefight_genesis_league_competitive_v1';

const MOUSEFIGHT_LEAGUE_POINTS_PER_MATCH = 1;

const MOUSEFIGHT_LEAGUE_POINTS_TIERS = [
    'crumb',
    'cheese',
    'bronze',
    'silver',
    'golden',
    'diamond',
    'genesis_master',
    'mouseverse_champion',
    'ultra_champion'
];

/**
 * Return one JSON response and stop execution.
 */
function mousefight_league_points_output(
    array $payload,
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    if (function_exists('ob_end_clean')) {
        @ob_end_clean();
    }

    if (function_exists('ob_clean')) {
        @ob_clean();
    }

    echo json_encode($payload);
    exit;
}

/**
 * Return the Discord bot authorization header.
 */
function mousefight_league_points_get_auth_token(): string
{
    $headers = [];

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    }

    foreach (['Authorization', 'authorization'] as $headerName) {
        if (isset($headers[$headerName])) {
            return trim((string)$headers[$headerName]);
        }
    }

    return '';
}

/**
 * Return one required clean request string.
 */
function mousefight_league_points_required_string(
    array $data,
    string $key,
    int $maximumLength = 1000
): string {
    $value = trim((string)($data[$key] ?? ''));

    if ($value === '') {
        throw new RuntimeException(
            "Missing required value: {$key}"
        );
    }

    if (strlen($value) > $maximumLength) {
        throw new RuntimeException(
            "Value is too long: {$key}"
        );
    }

    return $value;
}

/**
 * Prepare one SQLite statement or throw.
 */
function mousefight_league_points_prepare(
    SQLite3 $db,
    string $sql
): SQLite3Stmt {
    $statement = $db->prepare($sql);

    if (!$statement) {
        throw new RuntimeException(
            'Failed to prepare statement: ' .
            $db->lastErrorMsg()
        );
    }

    return $statement;
}

/**
 * Bind positional values using explicit SQLite value types.
 */
function mousefight_league_points_bind_values(
    SQLite3Stmt $statement,
    array $values
): void {
    foreach (array_values($values) as $index => $value) {
        $parameterIndex = $index + 1;

        if ($value === null) {
            $statement->bindValue(
                $parameterIndex,
                null,
                SQLITE3_NULL
            );
            continue;
        }

        if (is_int($value)) {
            $statement->bindValue(
                $parameterIndex,
                $value,
                SQLITE3_INTEGER
            );
            continue;
        }

        if (is_float($value)) {
            $statement->bindValue(
                $parameterIndex,
                $value,
                SQLITE3_FLOAT
            );
            continue;
        }

        $statement->bindValue(
            $parameterIndex,
            (string)$value,
            SQLITE3_TEXT
        );
    }
}

/**
 * Execute one prepared statement or throw.
 */
function mousefight_league_points_execute(
    SQLite3 $db,
    SQLite3Stmt $statement
) {
    $result = $statement->execute();

    if ($result === false) {
        throw new RuntimeException(
            'Failed to execute statement: ' .
            $db->lastErrorMsg()
        );
    }

    return $result;
}

/**
 * Return one associative database row or null.
 */
function mousefight_league_points_fetch_one(
    SQLite3 $db,
    string $sql,
    array $values = []
): ?array {
    $statement =
        mousefight_league_points_prepare(
            $db,
            $sql
        );

    mousefight_league_points_bind_values(
        $statement,
        $values
    );

    $result =
        mousefight_league_points_execute(
            $db,
            $statement
        );

    $row = $result->fetchArray(SQLITE3_ASSOC);

    return is_array($row)
        ? $row
        : null;
}

/**
 * Return every associative row produced by one query.
 */
function mousefight_league_points_fetch_all(
    SQLite3 $db,
    string $sql,
    array $values = []
): array {
    $statement =
        mousefight_league_points_prepare(
            $db,
            $sql
        );

    mousefight_league_points_bind_values(
        $statement,
        $values
    );

    $result =
        mousefight_league_points_execute(
            $db,
            $statement
        );

    $rows = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }

    return $rows;
}

/**
 * Execute one guarded write and require exactly one changed row.
 */
function mousefight_league_points_write(
    SQLite3 $db,
    string $sql,
    array $values
): int {
    $statement =
        mousefight_league_points_prepare(
            $db,
            $sql
        );

    mousefight_league_points_bind_values(
        $statement,
        $values
    );

    mousefight_league_points_execute(
        $db,
        $statement
    );

    if ($db->changes() !== 1) {
        throw new RuntimeException(
            'League Point write did not change exactly one row.'
        );
    }

    return (int)$db->lastInsertRowID();
}

/**
 * Refuse operation when the migrated League Point ledger is unavailable.
 */
function mousefight_league_points_require_ledger(
    SQLite3 $db
): void {
    $table =
        mousefight_league_points_fetch_one(
            $db,
            "
            SELECT name
            FROM sqlite_master
            WHERE type = 'table'
              AND name = 'tbl_mousefight_league_point_awards'
            LIMIT 1
            "
        );

    if (!$table) {
        throw new RuntimeException(
            'MouseFight League Point ledger is not available.'
        );
    }
}

/**
 * Return the single active Narrrfs World season.
 *
 * The backend resolves season identity itself. Discord is never trusted to
 * choose a season for a permanent League Point award.
 */
function mousefight_league_points_get_active_season(
    SQLite3 $db
): array {
    $seasons =
        mousefight_league_points_fetch_all(
            $db,
            "
            SELECT
                season_id,
                season_name,
                start_date,
                end_date
            FROM tbl_seasons
            WHERE is_active = 1
            ORDER BY season_id ASC
            "
        );

    if (count($seasons) !== 1) {
        throw new RuntimeException(
            'Expected exactly one active Narrrfs World season.'
        );
    }

    $season = $seasons[0];

    if (
        (int)($season['season_id'] ?? 0) <= 0 ||
        trim((string)($season['season_name'] ?? '')) === ''
    ) {
        throw new RuntimeException(
            'Active season identity is incomplete.'
        );
    }

    return $season;
}

/**
 * Validate the persisted competitive matchmaking provenance.
 *
 * Exact League Mode alone never awards a point.
 * Every competitive marker must be present together.
 */
function mousefight_league_points_validate_metadata(
    array $fight
): string {
    $rawMetadata =
        trim(
            (string)($fight['metadata_json'] ?? '')
        );

    if ($rawMetadata === '') {
        throw new RuntimeException(
            'MouseFight has no competitive League metadata.'
        );
    }

    $metadata =
        json_decode(
            $rawMetadata,
            true
        );

    if (
        json_last_error() !== JSON_ERROR_NONE ||
        !is_array($metadata)
    ) {
        throw new RuntimeException(
            'MouseFight League metadata is invalid JSON.'
        );
    }

    $leagueMode =
        strtolower(
            trim(
                (string)(
                    $metadata['league_mode'] ?? ''
                )
            )
        );

    if (
        $leagueMode !==
        MOUSEFIGHT_LEAGUE_POINTS_EXACT_MODE
    ) {
        throw new RuntimeException(
            'This fight is not an Exact Tier League fight.'
        );
    }

    $leagueModeVersion =
        trim(
            (string)(
                $metadata['league_mode_version'] ?? ''
            )
        );

    if (
        $leagueModeVersion !==
        MOUSEFIGHT_LEAGUE_POINTS_MODE_VERSION
    ) {
        throw new RuntimeException(
            'Unsupported Genesis League Mode contract.'
        );
    }

    $tierKey =
        strtolower(
            trim(
                (string)(
                    $metadata['league_tier_key'] ?? ''
                )
            )
        );

    if (
        !in_array(
            $tierKey,
            MOUSEFIGHT_LEAGUE_POINTS_TIERS,
            true
        )
    ) {
        throw new RuntimeException(
            'Invalid Genesis League tier for League Points.'
        );
    }

    if (
        ($metadata['league_competitive'] ?? null)
        !== true
    ) {
        throw new RuntimeException(
            'Competitive League provenance is not enabled.'
        );
    }

    $leagueSource =
        strtolower(
            trim(
                (string)(
                    $metadata['league_source'] ?? ''
                )
            )
        );

    if (
        $leagueSource !==
        MOUSEFIGHT_LEAGUE_POINTS_SOURCE
    ) {
        throw new RuntimeException(
            'This fight source does not award League Points.'
        );
    }

    if (
        ($metadata['league_points_enabled'] ?? null)
        !== true
    ) {
        throw new RuntimeException(
            'League Points are not enabled for this fight.'
        );
    }

    $competitiveVersion =
        trim(
            (string)(
                $metadata['league_competitive_version']
                    ?? ''
            )
        );

    if (
        $competitiveVersion !==
        MOUSEFIGHT_LEAGUE_POINTS_COMPETITIVE_VERSION
    ) {
        throw new RuntimeException(
            'Unsupported competitive League contract.'
        );
    }

    return $tierKey;
}

/**
 * Return lifetime and current-season totals for one Genesis NFT.
 */
function mousefight_league_points_get_totals(
    SQLite3 $db,
    string $tokenId,
    string $collection,
    array $season
): array {
    $lifetime =
        mousefight_league_points_fetch_one(
            $db,
            "
            SELECT
                COUNT(*) AS award_count,
                COALESCE(
                    SUM(points_awarded),
                    0
                ) AS points
            FROM tbl_mousefight_league_point_awards
            WHERE token_id = ?
              AND collection = ?
            ",
            [
                $tokenId,
                $collection
            ]
        );

    $seasonTotals =
        mousefight_league_points_fetch_one(
            $db,
            "
            SELECT
                COUNT(*) AS award_count,
                COALESCE(
                    SUM(points_awarded),
                    0
                ) AS points
            FROM tbl_mousefight_league_point_awards
            WHERE token_id = ?
              AND collection = ?
              AND season_id = ?
            ",
            [
                $tokenId,
                $collection,
                (int)$season['season_id']
            ]
        );

    return [
        'token_id' => $tokenId,
        'collection' => $collection,

        'lifetime_points' =>
            (int)($lifetime['points'] ?? 0),

        'lifetime_awards' =>
            (int)($lifetime['award_count'] ?? 0),

        'season_id' =>
            (int)$season['season_id'],

        'season_name' =>
            (string)$season['season_name'],

        'season_points' =>
            (int)($seasonTotals['points'] ?? 0),

        'season_awards' =>
            (int)($seasonTotals['award_count'] ?? 0)
    ];
}

/**
 * Return current League Point totals for one Genesis NFT.
 *
 * This action is read-only.
 */
function mousefight_league_points_get_mouse_points(
    SQLite3 $db,
    array $data
): array {
    $tokenId =
        mousefight_league_points_required_string(
            $data,
            'token_id',
            200
        );

    $collection =
        strtolower(
            trim(
                (string)(
                    $data['collection']
                    ?? 'genesis'
                )
            )
        );

    if ($collection !== 'genesis') {
        throw new RuntimeException(
            'League Points currently support Genesis mice only.'
        );
    }

    $season =
        mousefight_league_points_get_active_season(
            $db
        );

    $totals =
        mousefight_league_points_get_totals(
            $db,
            $tokenId,
            $collection,
            $season
        );

    $recentAwards =
        mousefight_league_points_fetch_all(
            $db,
            "
            SELECT
                award_id,
                fight_id,
                league_tier_key,
                source,
                points_awarded,
                season_id,
                season_name,
                awarded_at
            FROM tbl_mousefight_league_point_awards
            WHERE token_id = ?
              AND collection = ?
            ORDER BY award_id DESC
            LIMIT 10
            ",
            [
                $tokenId,
                $collection
            ]
        );

    $totals['recent_awards'] =
        $recentAwards;

    return $totals;
}

/**
 * Award exactly one League Point for one completed competitive matchmaking PVP.
 *
 * The caller supplies only fight_id.
 * Winner identity, NFT identity, League tier, provenance and season are derived
 * again from persisted authoritative database state.
 */
function mousefight_league_points_award_completed_match(
    SQLite3 $db,
    array $data
): array {
    $fightId =
        mousefight_league_points_required_string(
            $data,
            'fight_id',
            100
        );

    $fight =
        mousefight_league_points_fetch_one(
            $db,
            "
            SELECT
                fight_id,
                status,
                mode,
                winner_user_id,
                winner_token_id,
                metadata_json
            FROM tbl_mousefights
            WHERE fight_id = ?
            LIMIT 1
            ",
            [$fightId]
        );

    if (!$fight) {
        throw new RuntimeException(
            'MouseFight does not exist.'
        );
    }

    if (
        (string)$fight['status'] !==
        MOUSEFIGHT_LEAGUE_POINTS_FINISHED_STATUS
    ) {
        throw new RuntimeException(
            'League Points require a finished MouseFight.'
        );
    }

    if (
        (string)$fight['mode'] !==
        MOUSEFIGHT_LEAGUE_POINTS_PVP_MODE
    ) {
        throw new RuntimeException(
            'League Points require a PVP challenge.'
        );
    }

    $winnerUserId =
        trim(
            (string)(
                $fight['winner_user_id'] ?? ''
            )
        );

    $winnerTokenId =
        trim(
            (string)(
                $fight['winner_token_id'] ?? ''
            )
        );

    if (
        $winnerUserId === '' ||
        $winnerTokenId === ''
    ) {
        throw new RuntimeException(
            'Persisted MouseFight winner identity is incomplete.'
        );
    }

    $tierKey =
        mousefight_league_points_validate_metadata(
            $fight
        );

    $winnerParticipants =
        mousefight_league_points_fetch_all(
            $db,
            "
            SELECT
                id,
                user_id,
                token_id,
                collection,
                status,
                final_rank
            FROM tbl_mousefight_participants
            WHERE fight_id = ?
              AND token_id = ?
              AND collection = 'genesis'
            ORDER BY id ASC
            ",
            [
                $fightId,
                $winnerTokenId
            ]
        );

    if (count($winnerParticipants) !== 1) {
        throw new RuntimeException(
            'Expected exactly one persisted Genesis winner participant.'
        );
    }

    $winnerParticipant =
        $winnerParticipants[0];

    if (
        trim(
            (string)(
                $winnerParticipant['user_id'] ?? ''
            )
        ) !== $winnerUserId
    ) {
        throw new RuntimeException(
            'Persisted winner user does not match winner participant.'
        );
    }

    if (
        strtolower(
            trim(
                (string)(
                    $winnerParticipant['collection']
                    ?? ''
                )
            )
        ) !== 'genesis'
    ) {
        throw new RuntimeException(
            'League Points require a Genesis winner.'
        );
    }

    if (
        strtolower(
            trim(
                (string)(
                    $winnerParticipant['status']
                    ?? ''
                )
            )
        ) !== 'winner'
    ) {
        throw new RuntimeException(
            'Persisted participant is not marked as winner.'
        );
    }

    if (
        (int)(
            $winnerParticipant['final_rank']
            ?? 0
        ) !== 1
    ) {
        throw new RuntimeException(
            'Persisted winner does not have final rank 1.'
        );
    }

    $winnerState =
        mousefight_league_points_fetch_one(
            $db,
            "
            SELECT COUNT(*) AS winner_count
            FROM tbl_mousefight_participants
            WHERE fight_id = ?
              AND status = 'winner'
              AND final_rank = 1
            ",
            [$fightId]
        );

    if (
        (int)(
            $winnerState['winner_count']
            ?? 0
        ) !== 1
    ) {
        throw new RuntimeException(
            'MouseFight does not have exactly one persisted final winner.'
        );
    }

    $season =
        mousefight_league_points_get_active_season(
            $db
        );

    $existingAwards =
        mousefight_league_points_fetch_all(
            $db,
            "
            SELECT
                award_id,
                fight_id,
                token_id,
                collection,
                league_tier_key,
                source,
                points_awarded,
                season_id,
                season_name,
                awarded_at
            FROM tbl_mousefight_league_point_awards
            WHERE fight_id = ?
            ",
            [$fightId]
        );

    if (count($existingAwards) > 1) {
        throw new RuntimeException(
            'League Point ledger contains duplicate Fight IDs.'
        );
    }

    if (count($existingAwards) === 1) {
        $existingAward =
            $existingAwards[0];

        if (
            (string)$existingAward['token_id']
                !== $winnerTokenId ||
            (string)$existingAward['collection']
                !== 'genesis' ||
            (string)$existingAward['league_tier_key']
                !== $tierKey ||
            (string)$existingAward['source']
                !== MOUSEFIGHT_LEAGUE_POINTS_SOURCE ||
            (int)$existingAward['points_awarded']
                !== MOUSEFIGHT_LEAGUE_POINTS_PER_MATCH
        ) {
            throw new RuntimeException(
                'Existing League Point award conflicts with authoritative fight state.'
            );
        }

        return [
            'awarded' => false,
            'idempotent' => true,
            'award' => $existingAward,
            'totals' =>
                mousefight_league_points_get_totals(
                    $db,
                    $winnerTokenId,
                    'genesis',
                    $season
                )
        ];
    }

    $awardId =
        mousefight_league_points_write(
            $db,
            "
            INSERT INTO tbl_mousefight_league_point_awards (
                fight_id,
                token_id,
                collection,
                league_tier_key,
                source,
                points_awarded,
                season_id,
                season_name,
                awarded_at
            ) VALUES (
                ?,
                ?,
                'genesis',
                ?,
                ?,
                ?,
                ?,
                ?,
                CURRENT_TIMESTAMP
            )
            ",
            [
                $fightId,
                $winnerTokenId,
                $tierKey,
                MOUSEFIGHT_LEAGUE_POINTS_SOURCE,
                MOUSEFIGHT_LEAGUE_POINTS_PER_MATCH,
                (int)$season['season_id'],
                (string)$season['season_name']
            ]
        );

    $award =
        mousefight_league_points_fetch_one(
            $db,
            "
            SELECT
                award_id,
                fight_id,
                token_id,
                collection,
                league_tier_key,
                source,
                points_awarded,
                season_id,
                season_name,
                awarded_at
            FROM tbl_mousefight_league_point_awards
            WHERE award_id = ?
            LIMIT 1
            ",
            [$awardId]
        );

    if (!$award) {
        throw new RuntimeException(
            'League Point award could not be read back after insert.'
        );
    }

    return [
        'awarded' => true,
        'idempotent' => false,
        'award' => $award,
        'totals' =>
            mousefight_league_points_get_totals(
                $db,
                $winnerTokenId,
                'genesis',
                $season
            )
    ];
}

$authToken =
    mousefight_league_points_get_auth_token();

$validTokens =
    array_values(
        array_filter([
            $_ENV['DISCORD_BOT_SECRET']
                ?? getenv('DISCORD_BOT_SECRET'),

            $_ENV['DISCORD_SECRET']
                ?? getenv('DISCORD_SECRET')
        ])
    );

if (
    $authToken === '' ||
    !in_array(
        $authToken,
        $validTokens,
        true
    )
) {
    mousefight_league_points_output(
        [
            'success' => false,
            'error' => 'Unauthorized'
        ],
        401
    );
}

if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !== 'POST'
) {
    mousefight_league_points_output(
        [
            'success' => false,
            'error' => 'Method not allowed'
        ],
        405
    );
}

$rawInput =
    file_get_contents('php://input');

if (!$rawInput) {
    mousefight_league_points_output(
        [
            'success' => false,
            'error' => 'No input received'
        ],
        400
    );
}

$data =
    json_decode(
        $rawInput,
        true
    );

if (
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($data)
) {
    mousefight_league_points_output(
        [
            'success' => false,
            'error' =>
                'Invalid JSON input: ' .
                json_last_error_msg()
        ],
        400
    );
}

$action =
    trim(
        (string)($data['action'] ?? '')
    );

if (
    !in_array(
        $action,
        [
            'award_completed_match',
            'get_mouse_points'
        ],
        true
    )
) {
    mousefight_league_points_output(
        [
            'success' => false,
            'error' =>
                'Unsupported MouseFight League Points action'
        ],
        400
    );
}

$dbPath =
    __DIR__ .
    '/../../db/narrrf_world.sqlite';

if (!file_exists($dbPath)) {
    mousefight_league_points_output(
        [
            'success' => false,
            'error' =>
                'MouseFight database not found'
        ],
        500
    );
}

$db = null;
$transactionStarted = false;
$responsePayload = null;
$responseStatus = 200;

try {
    $db =
        new SQLite3(
            $dbPath
        );

    $db->enableExceptions(true);
    $db->busyTimeout(60000);

    $db->exec(
        'PRAGMA foreign_keys = ON'
    );

    mousefight_league_points_require_ledger(
        $db
    );

    if ($action === 'award_completed_match') {
        if (
            !$db->exec(
                'BEGIN IMMEDIATE'
            )
        ) {
            throw new RuntimeException(
                'Could not start League Point transaction.'
            );
        }

        $transactionStarted = true;

        $result =
            mousefight_league_points_award_completed_match(
                $db,
                $data
            );

        if (
            !$db->exec(
                'COMMIT'
            )
        ) {
            throw new RuntimeException(
                'Could not commit League Point transaction.'
            );
        }

        $transactionStarted = false;
    } else {
        $result =
            mousefight_league_points_get_mouse_points(
                $db,
                $data
            );
    }

    $responsePayload = [
        'success' => true,
        'contract' => [
            'version' =>
                MOUSEFIGHT_LEAGUE_POINTS_CONTRACT,

            'points_per_match' =>
                MOUSEFIGHT_LEAGUE_POINTS_PER_MATCH,

            'competitive_source' =>
                MOUSEFIGHT_LEAGUE_POINTS_SOURCE
        ],
        'action' => $action,
        'data' => $result
    ];
} catch (Throwable $error) {
    if (
        $db instanceof SQLite3 &&
        $transactionStarted
    ) {
        try {
            $db->exec(
                'ROLLBACK'
            );
        } catch (Throwable $rollbackError) {
            error_log(
                '[MOUSEFIGHT LEAGUE POINTS] Rollback failed: ' .
                $rollbackError->getMessage()
            );
        }
    }

    error_log(
        '[MOUSEFIGHT LEAGUE POINTS] ' .
        $action .
        ' failed: ' .
        $error->getMessage()
    );

    $responsePayload = [
        'success' => false,
        'action' => $action,
        'error' => $error->getMessage()
    ];

    $responseStatus = 400;
} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}

mousefight_league_points_output(
    $responsePayload,
    $responseStatus
);
