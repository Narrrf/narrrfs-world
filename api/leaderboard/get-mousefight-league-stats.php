<?php
/**
 * Public MouseFight Genesis League competition statistics API.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint reads already-persisted MouseFight and League Point history
 * for the Genesis League Competition Center on mousefights.html.
 *
 * Three different historical layers must remain separate:
 *
 * 1. Automatic League Battle
 *    A finished fight whose stored fight metadata says league_matchmaking = 1.
 *
 * 2. Competitive League Battle
 *    An automatic League Battle whose authoritative stored metadata also says:
 *    league_competitive = 1,
 *    league_source = matchmaking,
 *    league_points_enabled = 1.
 *
 * 3. League Point Award
 *    An authoritative persisted row in tbl_mousefight_league_point_awards.
 *
 * The browser must never infer a League Point from fight metadata alone.
 *
 * Historical fighter identity comes from tbl_mousefight_participants.
 * Current Discord username/avatar may be used only as a display enhancement.
 *
 * This endpoint must never:
 * - write SQLite rows;
 * - award League Points;
 * - create or alter MouseFights;
 * - join or alter matchmaking queues;
 * - settle PVP economy;
 * - move DSPOINC/SPOINC;
 * - touch Fight Recovery;
 * - mutate Genesis ownership, names, Traits, Abilities, Lab progression,
 *   Genetic Items, staking, authentication, or deployment state.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

const MOUSEFIGHT_LEAGUE_STATS_DEFAULT_LIMIT = 12;
const MOUSEFIGHT_LEAGUE_STATS_MAX_LIMIT = 50;

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

/**
 * Return one JSON response and stop execution.
 */
function mousefight_league_stats_json(
    array $payload,
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}

/**
 * Return one request integer bounded to the approved range.
 */
function mousefight_league_stats_bounded_int(
    $value,
    int $default,
    int $minimum,
    int $maximum
): int {
    if (!is_numeric($value)) {
        return $default;
    }

    return max(
        $minimum,
        min($maximum, (int)$value)
    );
}

/**
 * Return a nullable trimmed display string.
 */
function mousefight_league_stats_nullable_string($value): ?string
{
    $cleanValue = trim((string)$value);

    return $cleanValue !== ''
        ? $cleanValue
        : null;
}

/**
 * Convert known SQLite or ISO timestamps to public UTC ISO-8601.
 *
 * Stored history is not modified.
 */
function mousefight_league_stats_iso_timestamp($value): ?string
{
    $cleanValue = trim((string)$value);

    if ($cleanValue === '') {
        return null;
    }

    $hasExplicitTimezone =
        str_ends_with($cleanValue, 'Z')
        || preg_match('/[+-]\d{2}:\d{2}$/', $cleanValue) === 1;

    $timestampInput = $hasExplicitTimezone
        ? $cleanValue
        : $cleanValue . ' UTC';

    $timestamp = strtotime($timestampInput);

    if ($timestamp === false) {
        return null;
    }

    return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
}

/**
 * Bind one associative value map using safe PDO parameter types.
 */
function mousefight_league_stats_bind_values(
    PDOStatement $statement,
    array $values
): void {
    foreach ($values as $key => $value) {
        $type = is_int($value)
            ? PDO::PARAM_INT
            : PDO::PARAM_STR;

        $statement->bindValue(
            $key,
            $value,
            $type
        );
    }
}

try {
    if (!file_exists($dbPath)) {
        throw new RuntimeException(
            'Database file not found.'
        );
    }

    $pdo = new PDO(
        'sqlite:' . $dbPath
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    /**
     * Hard read-only guard.
     *
     * This API is presentation/statistics only.
     */
    $pdo->exec('PRAGMA query_only = ON');
    $pdo->exec('PRAGMA busy_timeout = 60000');

    $requestedSeasonId =
        isset($_GET['season_id'])
            && is_numeric($_GET['season_id'])
            ? max(0, (int)$_GET['season_id'])
            : 0;

    $limit =
        mousefight_league_stats_bounded_int(
            $_GET['limit'] ?? null,
            MOUSEFIGHT_LEAGUE_STATS_DEFAULT_LIMIT,
            1,
            MOUSEFIGHT_LEAGUE_STATS_MAX_LIMIT
        );

    /**
     * Resolve the selected season.
     *
     * The displayed MouseFight page may request one explicit season_id.
     * Without one, the currently active season remains authoritative.
     */
    if ($requestedSeasonId > 0) {
        $seasonStatement = $pdo->prepare("
            SELECT
                season_id,
                season_name,
                start_date,
                end_date,
                is_active
            FROM tbl_seasons
            WHERE season_id = :season_id
            LIMIT 1
        ");

        $seasonStatement->bindValue(
            ':season_id',
            $requestedSeasonId,
            PDO::PARAM_INT
        );
    } else {
        $seasonStatement = $pdo->prepare("
            SELECT
                season_id,
                season_name,
                start_date,
                end_date,
                is_active
            FROM tbl_seasons
            WHERE is_active = 1
            ORDER BY season_id DESC
            LIMIT 1
        ");
    }

    $seasonStatement->execute();
    $season = $seasonStatement->fetch();

    if (!$season) {
        mousefight_league_stats_json([
            'success' => false,
            'error' => 'Season not found.',
        ], 404);
    }

    $seasonStart =
        trim(
            (string)$season['start_date']
        );

    $seasonEnd =
        trim(
            (string)($season['end_date'] ?? '')
        );

    if ($seasonStart === '') {
        throw new RuntimeException(
            'Selected season has no valid start date.'
        );
    }

    $seasonWhereParts = [
        "f.status = 'finished'",
        "datetime(f.created_at) >= datetime(:season_start)",
    ];

    $seasonQueryValues = [
        ':season_start' => $seasonStart,
    ];

    if ($seasonEnd !== '') {
        $seasonWhereParts[] =
            "datetime(f.created_at) < datetime(:season_end)";

        $seasonQueryValues[':season_end'] =
            $seasonEnd;
    }

    $seasonWhereSql =
        implode(
            "\n AND ",
            $seasonWhereParts
        );

    /**
     * Count persisted finished League battles.
     *
     * JSON extraction is guarded by json_valid so malformed legacy metadata
     * cannot break the public statistics endpoint.
     */
    $fightSummaryStatement = $pdo->prepare("
        WITH season_fights AS (
            SELECT
                f.fight_id,
                CASE
                    WHEN json_valid(f.metadata_json) = 1
                    THEN CAST(
                        json_extract(
                            f.metadata_json,
                            '$.league_matchmaking'
                        )
                        AS INTEGER
                    )
                    ELSE 0
                END AS league_matchmaking,
                CASE
                    WHEN json_valid(f.metadata_json) = 1
                    THEN CAST(
                        json_extract(
                            f.metadata_json,
                            '$.league_competitive'
                        )
                        AS INTEGER
                    )
                    ELSE 0
                END AS league_competitive,
                CASE
                    WHEN json_valid(f.metadata_json) = 1
                    THEN TRIM(
                        CAST(
                            json_extract(
                                f.metadata_json,
                                '$.league_source'
                            )
                            AS TEXT
                        )
                    )
                    ELSE ''
                END AS league_source,
                CASE
                    WHEN json_valid(f.metadata_json) = 1
                    THEN CAST(
                        json_extract(
                            f.metadata_json,
                            '$.league_points_enabled'
                        )
                        AS INTEGER
                    )
                    ELSE 0
                END AS league_points_enabled
            FROM tbl_mousefights f
            WHERE {$seasonWhereSql}
        )
        SELECT
            COALESCE(
                SUM(
                    CASE
                        WHEN league_matchmaking = 1
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS automatic_league_battles,
            COALESCE(
                SUM(
                    CASE
                        WHEN league_matchmaking = 1
                         AND league_competitive = 1
                         AND league_source = 'matchmaking'
                         AND league_points_enabled = 1
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS competitive_league_battles
        FROM season_fights
    ");

    mousefight_league_stats_bind_values(
        $fightSummaryStatement,
        $seasonQueryValues
    );

    $fightSummaryStatement->execute();

    $fightSummary =
        $fightSummaryStatement->fetch()
        ?: [];

    /**
     * League Point rows are the only authority for actual awarded points.
     */
    $awardSummaryStatement = $pdo->prepare("
        SELECT
            COUNT(DISTINCT fight_id)
                AS point_awarding_battles,
            COALESCE(
                SUM(points_awarded),
                0
            ) AS league_points_awarded
        FROM tbl_mousefight_league_point_awards
        WHERE season_id = :season_id
    ");

    $awardSummaryStatement->bindValue(
        ':season_id',
        (int)$season['season_id'],
        PDO::PARAM_INT
    );

    $awardSummaryStatement->execute();

    $awardSummary =
        $awardSummaryStatement->fetch()
        ?: [];

    $winningMiceStatement = $pdo->prepare("
        SELECT COUNT(*)
        FROM (
            SELECT
                token_id,
                collection
            FROM tbl_mousefight_league_point_awards
            WHERE season_id = :season_id
            GROUP BY
                token_id,
                collection
        )
    ");

    $winningMiceStatement->bindValue(
        ':season_id',
        (int)$season['season_id'],
        PDO::PARAM_INT
    );

    $winningMiceStatement->execute();

    $lpWinningMice =
        (int)$winningMiceStatement->fetchColumn();

    /**
     * Aggregate battle activity by persisted League tier.
     *
     * Zero-activity published tiers are intentionally not invented here.
     * mousefights.html already owns the authoritative published V2 tier list
     * and may merge these observed counts into that presentation.
     */
    $tierStatement = $pdo->prepare("
        WITH season_fights AS (
            SELECT
                f.fight_id,
                COALESCE(
                    NULLIF(
                        TRIM(
                            CASE
                                WHEN json_valid(f.metadata_json) = 1
                                THEN CAST(
                                    json_extract(
                                        f.metadata_json,
                                        '$.league_tier_key'
                                    )
                                    AS TEXT
                                )
                                ELSE ''
                            END
                        ),
                        ''
                    ),
                    'unknown'
                ) AS league_tier_key,
                CASE
                    WHEN json_valid(f.metadata_json) = 1
                    THEN CAST(
                        json_extract(
                            f.metadata_json,
                            '$.league_matchmaking'
                        )
                        AS INTEGER
                    )
                    ELSE 0
                END AS league_matchmaking,
                CASE
                    WHEN json_valid(f.metadata_json) = 1
                    THEN CAST(
                        json_extract(
                            f.metadata_json,
                            '$.league_competitive'
                        )
                        AS INTEGER
                    )
                    ELSE 0
                END AS league_competitive,
                CASE
                    WHEN json_valid(f.metadata_json) = 1
                    THEN TRIM(
                        CAST(
                            json_extract(
                                f.metadata_json,
                                '$.league_source'
                            )
                            AS TEXT
                        )
                    )
                    ELSE ''
                END AS league_source,
                CASE
                    WHEN json_valid(f.metadata_json) = 1
                    THEN CAST(
                        json_extract(
                            f.metadata_json,
                            '$.league_points_enabled'
                        )
                        AS INTEGER
                    )
                    ELSE 0
                END AS league_points_enabled
            FROM tbl_mousefights f
            WHERE {$seasonWhereSql}
        ),
        automatic AS (
            SELECT
                league_tier_key,
                COUNT(*) AS automatic_league_battles
            FROM season_fights
            WHERE league_matchmaking = 1
            GROUP BY league_tier_key
        ),
        competitive AS (
            SELECT
                league_tier_key,
                COUNT(*) AS competitive_league_battles
            FROM season_fights
            WHERE league_matchmaking = 1
              AND league_competitive = 1
              AND league_source = 'matchmaking'
              AND league_points_enabled = 1
            GROUP BY league_tier_key
        ),
        awards AS (
            SELECT
                league_tier_key,
                COUNT(DISTINCT fight_id)
                    AS point_awarding_battles,
                COALESCE(
                    SUM(points_awarded),
                    0
                ) AS league_points_awarded
            FROM tbl_mousefight_league_point_awards
            WHERE season_id = :award_season_id
            GROUP BY league_tier_key
        ),
        tier_keys AS (
            SELECT league_tier_key FROM automatic
            UNION
            SELECT league_tier_key FROM competitive
            UNION
            SELECT league_tier_key FROM awards
        )
        SELECT
            tier_keys.league_tier_key,
            COALESCE(
                automatic.automatic_league_battles,
                0
            ) AS automatic_league_battles,
            COALESCE(
                competitive.competitive_league_battles,
                0
            ) AS competitive_league_battles,
            COALESCE(
                awards.point_awarding_battles,
                0
            ) AS point_awarding_battles,
            COALESCE(
                awards.league_points_awarded,
                0
            ) AS league_points_awarded
        FROM tier_keys
        LEFT JOIN automatic
            ON automatic.league_tier_key
             = tier_keys.league_tier_key
        LEFT JOIN competitive
            ON competitive.league_tier_key
             = tier_keys.league_tier_key
        LEFT JOIN awards
            ON awards.league_tier_key
             = tier_keys.league_tier_key
        ORDER BY
            CASE tier_keys.league_tier_key
                WHEN 'crumb' THEN 1
                WHEN 'cheese' THEN 2
                WHEN 'bronze' THEN 3
                WHEN 'silver' THEN 4
                WHEN 'golden' THEN 5
                WHEN 'diamond' THEN 6
                WHEN 'genesis_master' THEN 7
                WHEN 'mouseverse_champion' THEN 8
                WHEN 'ultra_champion' THEN 9
                ELSE 99
            END,
            tier_keys.league_tier_key
    ");

    $tierQueryValues =
        $seasonQueryValues;

    $tierQueryValues[':award_season_id'] =
        (int)$season['season_id'];

    mousefight_league_stats_bind_values(
        $tierStatement,
        $tierQueryValues
    );

    $tierStatement->execute();
    $tierRows = $tierStatement->fetchAll();

    $tiers = [];

    foreach ($tierRows as $tierRow) {
        $tiers[] = [
            'league_tier_key' =>
                (string)$tierRow['league_tier_key'],
            'automatic_league_battles' =>
                (int)$tierRow['automatic_league_battles'],
            'competitive_league_battles' =>
                (int)$tierRow['competitive_league_battles'],
            'point_awarding_battles' =>
                (int)$tierRow['point_awarding_battles'],
            'league_points_awarded' =>
                (int)$tierRow['league_points_awarded'],
        ];
    }

    /**
     * Build the NFT-bound Season League Point board.
     *
     * The most recent stored participant snapshot is used only for public
     * fighter name/image presentation. League Points remain ledger-authoritative.
     */
    $topMiceStatement = $pdo->prepare("
        WITH latest_participant AS (
            SELECT *
            FROM (
                SELECT
                    p.token_id,
                    p.collection,
                    p.custom_name,
                    p.metadata_name,
                    p.image_url,
                    p.username,
                    p.display_name,
                    ROW_NUMBER() OVER (
                        PARTITION BY
                            p.token_id,
                            p.collection
                        ORDER BY
                            datetime(p.joined_at) DESC,
                            p.id DESC
                    ) AS participant_rank
                FROM tbl_mousefight_participants p
            )
            WHERE participant_rank = 1
        )
        SELECT
            a.token_id,
            a.collection,
            SUM(a.points_awarded)
                AS league_points,
            COUNT(DISTINCT a.fight_id)
                AS winning_fights,
            MAX(a.awarded_at)
                AS last_awarded_at,
            (
                SELECT a2.league_tier_key
                FROM tbl_mousefight_league_point_awards a2
                WHERE a2.season_id = a.season_id
                  AND a2.token_id = a.token_id
                  AND a2.collection = a.collection
                ORDER BY a2.award_id DESC
                LIMIT 1
            ) AS latest_league_tier_key,
            lp.custom_name,
            lp.metadata_name,
            lp.image_url,
            lp.username,
            lp.display_name
        FROM tbl_mousefight_league_point_awards a
        LEFT JOIN latest_participant lp
            ON lp.token_id = a.token_id
           AND lp.collection = a.collection
        WHERE a.season_id = :season_id
        GROUP BY
            a.season_id,
            a.token_id,
            a.collection
        ORDER BY
            league_points DESC,
            winning_fights DESC,
            datetime(last_awarded_at) ASC,
            a.token_id ASC
        LIMIT 20
    ");

    $topMiceStatement->bindValue(
        ':season_id',
        (int)$season['season_id'],
        PDO::PARAM_INT
    );

    $topMiceStatement->execute();
    $topMouseRows = $topMiceStatement->fetchAll();

    $topLeaguePointMice = [];
    $topMouseRank = 0;

    foreach ($topMouseRows as $topMouseRow) {
        $topMouseRank++;

        $topLeaguePointMice[] = [
            'rank' => $topMouseRank,
            'token_id' =>
                (string)$topMouseRow['token_id'],
            'collection' =>
                (string)$topMouseRow['collection'],
            'custom_name' =>
                mousefight_league_stats_nullable_string(
                    $topMouseRow['custom_name'] ?? null
                ),
            'metadata_name' =>
                mousefight_league_stats_nullable_string(
                    $topMouseRow['metadata_name'] ?? null
                ),
            'image_url' =>
                mousefight_league_stats_nullable_string(
                    $topMouseRow['image_url'] ?? null
                ),
            'username' =>
                mousefight_league_stats_nullable_string(
                    $topMouseRow['username'] ?? null
                ),
            'display_name' =>
                mousefight_league_stats_nullable_string(
                    $topMouseRow['display_name'] ?? null
                ),
            'league_tier_key' =>
                mousefight_league_stats_nullable_string(
                    $topMouseRow['latest_league_tier_key'] ?? null
                ),
            'league_points' =>
                (int)$topMouseRow['league_points'],
            'winning_fights' =>
                (int)$topMouseRow['winning_fights'],
            'last_awarded_at' =>
                mousefight_league_stats_iso_timestamp(
                    $topMouseRow['last_awarded_at'] ?? null
                ),
        ];
    }

    /**
     * Load recent finished automatic League battles.
     *
     * Every row remains an automatic League battle regardless of whether
     * competitive provenance or an LP ledger row exists.
     */
    $recentFightStatement = $pdo->prepare("
        SELECT
            f.fight_id,
            f.title,
            f.status,
            f.mode,
            f.match_format,
            f.winner_user_id,
            f.winner_token_id,
            f.created_at,
            f.started_at,
            f.ended_at,
            COALESCE(
                NULLIF(
                    TRIM(
                        CASE
                            WHEN json_valid(f.metadata_json) = 1
                            THEN CAST(
                                json_extract(
                                    f.metadata_json,
                                    '$.league_tier_key'
                                )
                                AS TEXT
                            )
                            ELSE ''
                        END
                    ),
                    ''
                ),
                'unknown'
            ) AS league_tier_key,
            CASE
                WHEN json_valid(f.metadata_json) = 1
                THEN CAST(
                    json_extract(
                        f.metadata_json,
                        '$.league_matchmaking'
                    )
                    AS INTEGER
                )
                ELSE 0
            END AS league_matchmaking,
            CASE
                WHEN json_valid(f.metadata_json) = 1
                THEN CAST(
                    json_extract(
                        f.metadata_json,
                        '$.league_competitive'
                    )
                    AS INTEGER
                )
                ELSE 0
            END AS league_competitive,
            CASE
                WHEN json_valid(f.metadata_json) = 1
                THEN TRIM(
                    CAST(
                        json_extract(
                            f.metadata_json,
                            '$.league_source'
                        )
                        AS TEXT
                    )
                )
                ELSE ''
            END AS league_source,
            CASE
                WHEN json_valid(f.metadata_json) = 1
                THEN CAST(
                    json_extract(
                        f.metadata_json,
                        '$.league_points_enabled'
                    )
                    AS INTEGER
                )
                ELSE 0
            END AS league_points_enabled,
            CASE
                WHEN json_valid(f.metadata_json) = 1
                THEN NULLIF(
                    TRIM(
                        CAST(
                            json_extract(
                                f.metadata_json,
                                '$.league_competitive_version'
                            )
                            AS TEXT
                        )
                    ),
                    ''
                )
                ELSE NULL
            END AS league_competitive_version,
            (
                SELECT
                    COALESCE(
                        SUM(a.points_awarded),
                        0
                    )
                FROM tbl_mousefight_league_point_awards a
                WHERE a.fight_id = f.fight_id
                  AND a.season_id = :award_season_id
            ) AS points_awarded
        FROM tbl_mousefights f
        WHERE {$seasonWhereSql}
          AND json_valid(f.metadata_json) = 1
          AND CAST(
                json_extract(
                    f.metadata_json,
                    '$.league_matchmaking'
                )
                AS INTEGER
              ) = 1
        ORDER BY
            datetime(f.created_at) DESC,
            f.fight_id DESC
        LIMIT :limit
    ");

    $recentFightValues =
        $seasonQueryValues;

    $recentFightValues[':award_season_id'] =
        (int)$season['season_id'];

    $recentFightValues[':limit'] =
        $limit;

    mousefight_league_stats_bind_values(
        $recentFightStatement,
        $recentFightValues
    );

    $recentFightStatement->execute();
    $recentFightRows = $recentFightStatement->fetchAll();

    $recentFightIds =
        array_column(
            $recentFightRows,
            'fight_id'
        );

    $participantsByFight = [];

    if ($recentFightIds) {
        $participantPlaceholders = [];

        foreach ($recentFightIds as $index => $fightId) {
            $participantPlaceholders[] =
                ':fight_id_' . $index;
        }

        $participantStatement = $pdo->prepare("
            SELECT
                p.id,
                p.fight_id,
                p.user_id,
                p.username AS historical_username,
                p.display_name,
                p.avatar_url AS historical_avatar_url,
                p.token_id,
                p.collection,
                p.custom_name,
                p.metadata_name,
                p.image_url,
                p.mouse_warrior_power,
                p.final_rank,
                p.joined_at,
                u.username AS current_username,
                u.avatar_url AS current_avatar_url
            FROM tbl_mousefight_participants p
            LEFT JOIN tbl_users u
                ON TRIM(CAST(u.discord_id AS TEXT))
                 = TRIM(CAST(p.user_id AS TEXT))
            WHERE p.fight_id IN (
                " . implode(
                    ', ',
                    $participantPlaceholders
                ) . "
            )
            ORDER BY
                p.fight_id,
                p.id
        ");

        foreach ($recentFightIds as $index => $fightId) {
            $participantStatement->bindValue(
                ':fight_id_' . $index,
                $fightId,
                PDO::PARAM_STR
            );
        }

        $participantStatement->execute();
        $participantRows =
            $participantStatement->fetchAll();

        foreach ($participantRows as $participantRow) {
            $fightId =
                (string)$participantRow['fight_id'];

            $userId =
                (string)$participantRow['user_id'];

            $participantsByFight[$fightId][] = [
                'user_id' => $userId,
                'username' =>
                    mousefight_league_stats_nullable_string(
                        $participantRow['current_username'] ?? null
                    )
                    ?? mousefight_league_stats_nullable_string(
                        $participantRow['historical_username'] ?? null
                    )
                    ?? $userId,
                'display_name' =>
                    mousefight_league_stats_nullable_string(
                        $participantRow['display_name'] ?? null
                    ),
                'avatar_url' =>
                    mousefight_league_stats_nullable_string(
                        $participantRow['current_avatar_url'] ?? null
                    )
                    ?? mousefight_league_stats_nullable_string(
                        $participantRow['historical_avatar_url'] ?? null
                    ),
                'token_id' =>
                    (string)$participantRow['token_id'],
                'collection' =>
                    mousefight_league_stats_nullable_string(
                        $participantRow['collection'] ?? null
                    ) ?? 'genesis',
                'custom_name' =>
                    mousefight_league_stats_nullable_string(
                        $participantRow['custom_name'] ?? null
                    ),
                'metadata_name' =>
                    mousefight_league_stats_nullable_string(
                        $participantRow['metadata_name'] ?? null
                    ),
                'image_url' =>
                    mousefight_league_stats_nullable_string(
                        $participantRow['image_url'] ?? null
                    ),
                'mouse_warrior_power' =>
                    (int)($participantRow['mouse_warrior_power'] ?? 0),
                'final_rank' =>
                    $participantRow['final_rank'] !== null
                        ? (int)$participantRow['final_rank']
                        : null,
            ];
        }
    }

    $recentLeagueBattles = [];

    foreach ($recentFightRows as $fightRow) {
        $fightId =
            (string)$fightRow['fight_id'];

        $participants =
            $participantsByFight[$fightId]
            ?? [];

        $winner = null;

        foreach ($participants as &$participant) {
            $isWinner =
                (string)$participant['user_id']
                    === (string)$fightRow['winner_user_id']
                && (string)$participant['token_id']
                    === (string)$fightRow['winner_token_id'];

            $participant['is_winner'] =
                $isWinner;

            if ($isWinner) {
                $winner =
                    $participant;
            }
        }
        unset($participant);

        $isCompetitive =
            (int)$fightRow['league_matchmaking'] === 1
            && (int)$fightRow['league_competitive'] === 1
            && (string)$fightRow['league_source'] === 'matchmaking'
            && (int)$fightRow['league_points_enabled'] === 1;

        $pointsAwarded =
            (int)$fightRow['points_awarded'];

        $recentLeagueBattles[] = [
            'fight_id' => $fightId,
            'title' =>
                mousefight_league_stats_nullable_string(
                    $fightRow['title'] ?? null
                ),
            'status' =>
                (string)$fightRow['status'],
            'mode' =>
                (string)$fightRow['mode'],
            'match_format' =>
                (string)$fightRow['match_format'],
            'league_tier_key' =>
                (string)$fightRow['league_tier_key'],
            'automatic_matchmaking' =>
                (bool)((int)$fightRow['league_matchmaking']),
            'competitive' =>
                $isCompetitive,
            'competitive_version' =>
                mousefight_league_stats_nullable_string(
                    $fightRow['league_competitive_version'] ?? null
                ),
            'points_awarded' =>
                $pointsAwarded,
            'point_awarded' =>
                $pointsAwarded > 0,
            'winner' =>
                $winner,
            'participants' =>
                $participants,
            'created_at' =>
                mousefight_league_stats_iso_timestamp(
                    $fightRow['created_at'] ?? null
                ),
            'started_at' =>
                mousefight_league_stats_iso_timestamp(
                    $fightRow['started_at'] ?? null
                ),
            'ended_at' =>
                mousefight_league_stats_iso_timestamp(
                    $fightRow['ended_at'] ?? null
                ),
        ];
    }

    mousefight_league_stats_json([
        'success' => true,
        'read_only' => true,
        'generated_at' => gmdate('c'),
        'competition_contract' => [
            'automatic_league_battle' =>
                'finished fight with league_matchmaking = 1',
            'competitive_league_battle' =>
                'automatic League battle with league_competitive = 1, league_source = matchmaking, and league_points_enabled = 1',
            'league_point_authority' =>
                'tbl_mousefight_league_point_awards',
            'season_timestamp' =>
                'tbl_mousefights.created_at',
            'season_start_inclusive' =>
                true,
            'season_end_exclusive' =>
                true,
            'historical_fighter_source' =>
                'tbl_mousefight_participants',
            'frontend_must_not_infer_points' =>
                true,
        ],
        'season' => [
            'season_id' =>
                (int)$season['season_id'],
            'season_name' =>
                (string)$season['season_name'],
            'start_date' =>
                mousefight_league_stats_iso_timestamp(
                    $season['start_date']
                ),
            'end_date' =>
                mousefight_league_stats_iso_timestamp(
                    $season['end_date'] ?? null
                ),
            'is_active' =>
                (bool)((int)$season['is_active']),
        ],
        'summary' => [
            'automatic_league_battles' =>
                (int)($fightSummary['automatic_league_battles'] ?? 0),
            'competitive_league_battles' =>
                (int)($fightSummary['competitive_league_battles'] ?? 0),
            'point_awarding_battles' =>
                (int)($awardSummary['point_awarding_battles'] ?? 0),
            'league_points_awarded' =>
                (int)($awardSummary['league_points_awarded'] ?? 0),
            'lp_winning_mice' =>
                $lpWinningMice,
        ],
        'tiers' =>
            $tiers,
        'top_league_point_mice' =>
            $topLeaguePointMice,
        'recent_league_battles' =>
            $recentLeagueBattles,
    ]);
} catch (Throwable $error) {
    mousefight_league_stats_json([
        'success' => false,
        'read_only' => true,
        'error' => $error->getMessage(),
    ], 500);
}
