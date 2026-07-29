<?php
/**
 * Public MouseFight player statistics API.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint summarizes official finished MouseFight history for one season.
 * It can scope statistics to one Discord user, one Genesis mouse token, or the
 * exact combination of both.
 *
 * Historical safety:
 * - Only finished fights inside the selected season are competitive history.
 * - Fight wins use tbl_mousefights.winner_user_id / winner_token_id.
 * - Round wins use tbl_mousefight_rounds winner user + token identity.
 * - Fighter names, Genesis images, and power use fight-time participant rows.
 * - Current Discord username/avatar may be displayed when available.
 * - Legacy fights without battle_mode are displayed as Champion Mode.
 *
 * This endpoint must not:
 * - write database rows;
 * - recalculate fights or rankings;
 * - settle DSPOINC, burns, escrow, prizes, or token payouts;
 * - change Fight Recovery;
 * - mutate Genesis ownership, traits, abilities, names, or Genetic Items.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

const MOUSEFIGHT_PLAYER_STATS_DEFAULT_RECENT_LIMIT = 10;
const MOUSEFIGHT_PLAYER_STATS_MAX_RECENT_LIMIT = 25;

const MOUSEFIGHT_PLAYER_STATS_MODE_PVP = 'pvp_challenge';
const MOUSEFIGHT_PLAYER_STATS_MODE_EVENT = 'admin_bracket_event';

const MOUSEFIGHT_PLAYER_STATS_BATTLE_MODES = [
    'champion',
    'underdog',
    'equalized',
    'chaos',
];

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

/**
 * Return one JSON response and stop execution.
 */
function mousefight_player_stats_json(
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
 * Return a bounded integer request value.
 */
function mousefight_player_stats_bounded_int(
    $value,
    int $default,
    int $minimum,
    int $maximum
): int {
    if (!is_numeric($value)) {
        return $default;
    }

    return max($minimum, min($maximum, (int)$value));
}

/**
 * Return a safe boolean query value.
 *
 * Plain language for DEVS FOR DECADES:
 * This is used only to select an all-time read scope.
 * It does not write fights, change winners, recalculate combat,
 * settle economy, or modify season records.
 */
function mousefight_player_stats_boolean(
    $value,
    bool $default = false
): bool {
    if ($value === null || $value === '') {
        return $default;
    }

    $normalizedValue = strtolower(trim((string)$value));

    return in_array(
        $normalizedValue,
        ['1', 'true', 'yes', 'on'],
        true
    );
}

/**
 * Return a clean optional request string.
 */
function mousefight_player_stats_optional_string(
    $value,
    int $maximumLength
): string {
    $cleanValue = trim((string)$value);

    if (strlen($cleanValue) > $maximumLength) {
        return substr($cleanValue, 0, $maximumLength);
    }

    return $cleanValue;
}

/**
 * Return a nullable trimmed string.
 */
function mousefight_player_stats_nullable_string($value): ?string
{
    $cleanValue = trim((string)$value);

    return $cleanValue !== '' ? $cleanValue : null;
}

/**
 * Convert known SQLite and ISO timestamps to public UTC ISO-8601.
 */
function mousefight_player_stats_iso_timestamp($value): ?string
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
 * Calculate a small modulo from a Discord snowflake stored as text.
 */
function mousefight_player_stats_string_modulo(
    string $numericString,
    int $divisor
): int {
    if ($divisor < 1) {
        return 0;
    }

    $remainder = 0;

    foreach (str_split($numericString) as $character) {
        if ($character < '0' || $character > '9') {
            continue;
        }

        $remainder = (($remainder * 10) + (int)$character) % $divisor;
    }

    return $remainder;
}

/**
 * Return the deterministic Discord default avatar for one user id.
 */
function mousefight_player_stats_default_avatar(string $userId): string
{
    $avatarIndex = mousefight_player_stats_string_modulo($userId, 5);

    return "https://cdn.discordapp.com/embed/avatars/{$avatarIndex}.png";
}

/**
 * Resolve the public Discord avatar and explain which source was selected.
 */
function mousefight_player_stats_resolve_avatar(
    string $userId,
    $currentAvatar,
    $historicalAvatar
): array {
    $currentAvatarUrl =
        mousefight_player_stats_nullable_string($currentAvatar);

    $historicalAvatarUrl =
        mousefight_player_stats_nullable_string($historicalAvatar);

    if ($currentAvatarUrl !== null) {
        return [
            'avatar_url' => $currentAvatarUrl,
            'avatar_source' => 'current_user',
            'historical_avatar_url' => $historicalAvatarUrl,
        ];
    }

    if ($historicalAvatarUrl !== null) {
        return [
            'avatar_url' => $historicalAvatarUrl,
            'avatar_source' => 'fight_snapshot',
            'historical_avatar_url' => $historicalAvatarUrl,
        ];
    }

    return [
        'avatar_url' => mousefight_player_stats_default_avatar($userId),
        'avatar_source' => 'discord_default',
        'historical_avatar_url' => null,
    ];
}

/**
 * Bind named PDO values using safe basic value types.
 */
function mousefight_player_stats_bind_values(
    PDOStatement $statement,
    array $values
): void {
    foreach ($values as $placeholder => $value) {
        $type = PDO::PARAM_STR;

        if (is_int($value)) {
            $type = PDO::PARAM_INT;
        } elseif ($value === null) {
            $type = PDO::PARAM_NULL;
        }

        $statement->bindValue($placeholder, $value, $type);
    }
}

/**
 * Return true when one participant row is the authoritative fight winner.
 */
function mousefight_player_stats_is_fight_winner(array $row): bool
{
    $participantUserId = trim((string)($row['user_id'] ?? ''));
    $participantTokenId = trim((string)($row['token_id'] ?? ''));
    $winnerUserId = trim((string)($row['winner_user_id'] ?? ''));
    $winnerTokenId = trim((string)($row['winner_token_id'] ?? ''));

    if ($participantUserId === '' || $participantUserId !== $winnerUserId) {
        return false;
    }

    if ($participantTokenId !== '' && $winnerTokenId !== '') {
        return $participantTokenId === $winnerTokenId;
    }

    return true;
}

/**
 * Return a rounded percentage without division-by-zero warnings.
 */
function mousefight_player_stats_rate(int $wins, int $total): float
{
    if ($total < 1) {
        return 0.0;
    }

    return round(($wins * 100) / $total, 2);
}

/**
 * Build one public historical mouse object from an aggregate row.
 */
function mousefight_player_stats_mouse(array $mouse): array
{
    return [
        'user_id' => mousefight_player_stats_nullable_string(
            $mouse['user_id'] ?? null
        ),
        'token_id' => mousefight_player_stats_nullable_string(
            $mouse['token_id'] ?? null
        ),
        'collection' => mousefight_player_stats_nullable_string(
            $mouse['collection'] ?? null
        ),
        'custom_name' => mousefight_player_stats_nullable_string(
            $mouse['custom_name'] ?? null
        ),
        'metadata_name' => mousefight_player_stats_nullable_string(
            $mouse['metadata_name'] ?? null
        ),
        'image_url' => mousefight_player_stats_nullable_string(
            $mouse['image_url'] ?? null
        ),
        'mouse_warrior_power' => isset($mouse['mouse_warrior_power'])
            ? (int)$mouse['mouse_warrior_power']
            : null,
        'fights_used' => (int)($mouse['fights_used'] ?? 0),
        'wins' => (int)($mouse['wins'] ?? 0),
        'losses' => (int)($mouse['losses'] ?? 0),
        'win_rate' => mousefight_player_stats_rate(
            (int)($mouse['wins'] ?? 0),
            (int)($mouse['fights_used'] ?? 0)
        ),
    ];
}

try {
    if (!file_exists($dbPath)) {
        throw new RuntimeException('Database file not found.');
    }

    $userIdFilter = mousefight_player_stats_optional_string(
        $_GET['user_id'] ?? null,
        100
    );

    $tokenIdFilter = mousefight_player_stats_optional_string(
        $_GET['token_id'] ?? null,
        200
    );

    if ($userIdFilter === '' && $tokenIdFilter === '') {
        mousefight_player_stats_json([
            'success' => false,
            'error' => 'At least one parameter is required: user_id or token_id',
        ], 400);
    }

    $requestedSeasonId = mousefight_player_stats_bounded_int(
        $_GET['season_id'] ?? null,
        0,
        0,
        PHP_INT_MAX
    );

        /**
     * All-time is an explicit opt-in scope.
     *
     * Plain language:
     * Normal requests must continue to use the selected or active season.
     * Only all_time=1 removes the season date boundary.
     */
    $allTime = mousefight_player_stats_boolean(
        $_GET['all_time'] ?? null,
        false
    );

    $recentLimit = mousefight_player_stats_bounded_int(
        $_GET['recent_limit'] ?? null,
        MOUSEFIGHT_PLAYER_STATS_DEFAULT_RECENT_LIMIT,
        1,
        MOUSEFIGHT_PLAYER_STATS_MAX_RECENT_LIMIT
    );

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /**
     * Resolve the requested statistics period.
     *
     * Plain language for DEVS FOR DECADES:
     * - all_time=1 includes all official finished MouseFights.
     * - season_id selects one exact season.
     * - normal requests default to the active season.
     *
     * This changes only the read boundary. It does not change stored fights,
     * season ownership, combat, Fight Recovery, economy, or rewards.
     */
    if ($allTime) {
        $season = [
            'season_id' => 0,
            'season_name' => 'All-Time',
            'start_date' => null,
            'end_date' => null,
            'is_active' => 0,
        ];
    } else {
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
            mousefight_player_stats_json([
                'success' => false,
                'error' => 'Season not found.',
            ], 404);
        }
    }

    $seasonStart = trim((string)($season['start_date'] ?? ''));
    $seasonEnd = trim((string)($season['end_date'] ?? ''));

    if (!$allTime && $seasonStart === '') {
        throw new RuntimeException(
            'Selected season has no valid start date.'
        );
    }

    $scopeParts = [];
    $scopeValues = [];

    if (!$allTime) {
        $scopeValues[':season_start'] = $seasonStart;

        if ($seasonEnd !== '') {
            $scopeValues[':season_end'] = $seasonEnd;
        }
    }

    if ($userIdFilter !== '') {
        $scopeParts[] = "
            TRIM(CAST(p.user_id AS TEXT))
                = TRIM(CAST(:user_id AS TEXT))
        ";
        $scopeValues[':user_id'] = $userIdFilter;
    }

    if ($tokenIdFilter !== '') {
        $scopeParts[] = "
            TRIM(CAST(p.token_id AS TEXT))
                = TRIM(CAST(:token_id AS TEXT))
        ";
        $scopeValues[':token_id'] = $tokenIdFilter;
    }

    $seasonStartSql = $allTime
        ? ''
        : "AND datetime(f.created_at) >= datetime(:season_start)";

    $seasonEndSql = (!$allTime && $seasonEnd !== '')
        ? "AND datetime(f.created_at) < datetime(:season_end)"
        : '';

    $scopeSql = implode("\n AND ", $scopeParts);

    /**
     * Load one row per exact historical participant/fight identity.
     */
    $participantStatement = $pdo->prepare("
        SELECT DISTINCT
            p.fight_id,
            TRIM(CAST(p.user_id AS TEXT)) AS user_id,
            p.username,
            p.display_name,
            p.avatar_url AS historical_avatar_url,
            p.token_id,
            p.collection,
            p.custom_name,
            p.metadata_name,
            p.image_url,
            p.mouse_warrior_power,
            f.title,
            f.mode,
            f.match_format,
            f.winner_user_id,
            f.winner_token_id,
            f.created_at,
            f.ended_at,
            CASE
                WHEN NULLIF(
                    TRIM(json_extract(f.metadata_json, '$.battle_mode')),
                    ''
                ) IS NULL
                THEN 'champion'
                ELSE json_extract(f.metadata_json, '$.battle_mode')
            END AS battle_mode
        FROM tbl_mousefight_participants p
        INNER JOIN tbl_mousefights f
            ON f.fight_id = p.fight_id
        WHERE f.status = 'finished'
          {$seasonStartSql}
          {$seasonEndSql}
          AND {$scopeSql}
        ORDER BY datetime(f.created_at) DESC, p.id DESC
    ");

    mousefight_player_stats_bind_values(
        $participantStatement,
        $scopeValues
    );

    $participantStatement->execute();
    $participantRows = $participantStatement->fetchAll() ?: [];

    if (count($participantRows) < 1) {
        mousefight_player_stats_json([
            'success' => false,
            'error' => 'No finished MouseFight history found for this scope.',
            'season' => [
                'season_id' => (int)$season['season_id'],
                'season_name' => (string)$season['season_name'],
            ],
            'filters' => [
                'user_id' => $userIdFilter !== '' ? $userIdFilter : null,
                'token_id' => $tokenIdFilter !== '' ? $tokenIdFilter : null,
            ],
        ], 404);
    }

    $resolvedUserId = $userIdFilter !== ''
        ? $userIdFilter
        : trim((string)$participantRows[0]['user_id']);

    $currentUser = null;

    if ($resolvedUserId !== '') {
        $currentUserStatement = $pdo->prepare("
            SELECT
                discord_id,
                username,
                avatar_url
            FROM tbl_users
            WHERE TRIM(CAST(discord_id AS TEXT))
                = TRIM(CAST(:user_id AS TEXT))
            LIMIT 1
        ");

        $currentUserStatement->bindValue(
            ':user_id',
            $resolvedUserId,
            PDO::PARAM_STR
        );

        $currentUserStatement->execute();
        $currentUser = $currentUserStatement->fetch() ?: null;
    }

    $latestParticipant = $participantRows[0];
    $avatar = mousefight_player_stats_resolve_avatar(
        $resolvedUserId,
        $currentUser['avatar_url'] ?? null,
        $latestParticipant['historical_avatar_url'] ?? null
    );

    $uniqueFightRows = [];
    $mouseAggregates = [];
    $battleModeStats = [];

    foreach (MOUSEFIGHT_PLAYER_STATS_BATTLE_MODES as $battleMode) {
        $battleModeStats[$battleMode] = [
            'fights' => 0,
            'wins' => 0,
            'losses' => 0,
            'win_rate' => 0.0,
        ];
    }

    foreach ($participantRows as $row) {
        $fightId = trim((string)$row['fight_id']);

        if ($fightId === '' || isset($uniqueFightRows[$fightId])) {
            continue;
        }

        $isWinner = mousefight_player_stats_is_fight_winner($row);
        $uniqueFightRows[$fightId] = [
            'row' => $row,
            'is_winner' => $isWinner,
        ];

        $battleMode = trim((string)$row['battle_mode']);
        if (!isset($battleModeStats[$battleMode])) {
            $battleMode = 'champion';
        }

        $battleModeStats[$battleMode]['fights']++;
        if ($isWinner) {
            $battleModeStats[$battleMode]['wins']++;
        } else {
            $battleModeStats[$battleMode]['losses']++;
        }
    }

    foreach ($participantRows as $row) {
        $tokenId = trim((string)($row['token_id'] ?? ''));
        if ($tokenId === '') {
            continue;
        }

        $mouseUserId = trim((string)($row['user_id'] ?? ''));
        $mouseKey = $mouseUserId . '|' . $tokenId;

        if (!isset($mouseAggregates[$mouseKey])) {
            $mouseAggregates[$mouseKey] = [
                'user_id' => $mouseUserId,
                'token_id' => $tokenId,
                'collection' => $row['collection'] ?? null,
                'custom_name' => $row['custom_name'] ?? null,
                'metadata_name' => $row['metadata_name'] ?? null,
                'image_url' => $row['image_url'] ?? null,
                'mouse_warrior_power' => $row['mouse_warrior_power'] ?? null,
                'fight_ids' => [],
                'wins' => 0,
            ];
        }

        $fightId = trim((string)$row['fight_id']);
        if ($fightId === '' || isset($mouseAggregates[$mouseKey]['fight_ids'][$fightId])) {
            continue;
        }

        $mouseAggregates[$mouseKey]['fight_ids'][$fightId] = true;

        if (mousefight_player_stats_is_fight_winner($row)) {
            $mouseAggregates[$mouseKey]['wins']++;
        }
    }

    $mice = [];
    foreach ($mouseAggregates as $mouse) {
        $fightsUsed = count($mouse['fight_ids']);
        $wins = (int)$mouse['wins'];

        $mouse['fights_used'] = $fightsUsed;
        $mouse['losses'] = max(0, $fightsUsed - $wins);
        unset($mouse['fight_ids']);
        $mice[] = $mouse;
    }

    usort($mice, static function (array $left, array $right): int {
        $fightComparison =
            ((int)$right['fights_used']) <=> ((int)$left['fights_used']);

        if ($fightComparison !== 0) {
            return $fightComparison;
        }

        $winComparison = ((int)$right['wins']) <=> ((int)$left['wins']);

        if ($winComparison !== 0) {
            return $winComparison;
        }

        return strcmp(
            (string)$left['token_id'],
            (string)$right['token_id']
        );
    });

    $totalFights = count($uniqueFightRows);
    $wins = 0;
    $pvpFights = 0;
    $pvpWins = 0;
    $eventFights = 0;
    $eventChampionships = 0;
    $recentFights = [];

    foreach ($uniqueFightRows as $fightData) {
        $row = $fightData['row'];
        $isWinner = (bool)$fightData['is_winner'];
        $mode = trim((string)$row['mode']);

        if ($isWinner) {
            $wins++;
        }

        if ($mode === MOUSEFIGHT_PLAYER_STATS_MODE_PVP) {
            $pvpFights++;
            if ($isWinner) {
                $pvpWins++;
            }
        }

        if ($mode === MOUSEFIGHT_PLAYER_STATS_MODE_EVENT) {
            $eventFights++;
            if ($isWinner) {
                $eventChampionships++;
            }
        }

        if (count($recentFights) < $recentLimit) {
            $recentFights[] = [
                'fight_id' => (string)$row['fight_id'],
                'title' => mousefight_player_stats_nullable_string(
                    $row['title'] ?? null
                ),
                'mode' => $mode,
                'battle_mode' => (string)$row['battle_mode'],
                'match_format' => mousefight_player_stats_nullable_string(
                    $row['match_format'] ?? null
                ),
                'result' => $isWinner ? 'win' : 'loss',
                'created_at' => mousefight_player_stats_iso_timestamp(
                    $row['created_at'] ?? null
                ),
                'ended_at' => mousefight_player_stats_iso_timestamp(
                    $row['ended_at'] ?? null
                ),
                'mouse' => [
                    'token_id' => mousefight_player_stats_nullable_string(
                        $row['token_id'] ?? null
                    ),
                    'custom_name' => mousefight_player_stats_nullable_string(
                        $row['custom_name'] ?? null
                    ),
                    'metadata_name' => mousefight_player_stats_nullable_string(
                        $row['metadata_name'] ?? null
                    ),
                    'image_url' => mousefight_player_stats_nullable_string(
                        $row['image_url'] ?? null
                    ),
                    'mouse_warrior_power' => isset($row['mouse_warrior_power'])
                        ? (int)$row['mouse_warrior_power']
                        : null,
                ],
            ];
        }
    }

    foreach ($battleModeStats as &$modeStats) {
        $modeStats['win_rate'] = mousefight_player_stats_rate(
            (int)$modeStats['wins'],
            (int)$modeStats['fights']
        );
    }
    unset($modeStats);

    /**
     * Count rounds for the same scope. The exact user + token combination is
     * used whenever both filters are supplied.
     *
     * Plain language for DEVS FOR DECADES:
     * Season parameters are bound only when the round SQL contains the matching
     * placeholders. All-time mode removes the season boundary entirely.
     *
     * This changes read-only query binding only. It does not change combat,
     * stored winners, Fight Recovery, DSPOINC, escrow, burns, rewards,
     * Genesis ownership, traits, abilities, or Lab progression.
     */
    $roundSideParts = [];
    $roundValues = [];

    if (!$allTime) {
        $roundValues[':season_start'] = $seasonStart;

        if ($seasonEnd !== '') {
            $roundValues[':season_end'] = $seasonEnd;
        }
    }

    if ($userIdFilter !== '') {
        $roundSideParts[] = "
            TRIM(CAST(side_user_id AS TEXT))
                = TRIM(CAST(:round_user_id AS TEXT))
        ";
        $roundValues[':round_user_id'] = $userIdFilter;
    }

    if ($tokenIdFilter !== '') {
        $roundSideParts[] = "
            TRIM(CAST(side_token_id AS TEXT))
                = TRIM(CAST(:round_token_id AS TEXT))
        ";
        $roundValues[':round_token_id'] = $tokenIdFilter;
    }

    $roundSideSql = implode("\n AND ", $roundSideParts);

    $roundStatement = $pdo->prepare("
        WITH official_rounds AS (
            SELECT r.*
            FROM tbl_mousefight_rounds r
            INNER JOIN tbl_mousefights f
                ON f.fight_id = r.fight_id
            WHERE f.status = 'finished'
              {$seasonStartSql}
              {$seasonEndSql}
        ),
        round_sides AS (
            SELECT
                id AS round_id,
                attacker_user_id AS side_user_id,
                attacker_token_id AS side_token_id,
                winner_user_id,
                winner_token_id
            FROM official_rounds

            UNION ALL

            SELECT
                id AS round_id,
                defender_user_id AS side_user_id,
                defender_token_id AS side_token_id,
                winner_user_id,
                winner_token_id
            FROM official_rounds
        )
        SELECT
            COUNT(*) AS rounds_played,
            COALESCE(SUM(
                CASE
                    WHEN TRIM(CAST(side_user_id AS TEXT))
                         = TRIM(CAST(winner_user_id AS TEXT))
                     AND TRIM(CAST(side_token_id AS TEXT))
                         = TRIM(CAST(winner_token_id AS TEXT))
                    THEN 1 ELSE 0
                END
            ), 0) AS rounds_won
        FROM round_sides
        WHERE {$roundSideSql}
    ");

    mousefight_player_stats_bind_values(
        $roundStatement,
        $roundValues
    );

    $roundStatement->execute();
    $roundStats = $roundStatement->fetch() ?: [];

    $roundsPlayed = (int)($roundStats['rounds_played'] ?? 0);
    $roundsWon = (int)($roundStats['rounds_won'] ?? 0);
    $losses = max(0, $totalFights - $wins);

    $queryScope = 'user';
    if ($userIdFilter !== '' && $tokenIdFilter !== '') {
        $queryScope = 'user_token';
    } elseif ($tokenIdFilter !== '') {
        $queryScope = 'token';
    }

    mousefight_player_stats_json([
        'success' => true,
        'contract' => [
            'name' => 'mousefight_player_stats',
            'version' => 1,
            'read_only' => true,
            'official_status' => 'finished',
            'season_timestamp' => 'created_at',
            'season_start_inclusive' => true,
            'season_end_exclusive' => true,
            'fight_winner_source' => 'tbl_mousefights header winner identity',
            'round_winner_source' => 'tbl_mousefight_rounds winner user + token identity',
            'economy_included' => false,
        ],
        'season' => [
            'season_id' => (int)$season['season_id'],
            'season_name' => (string)$season['season_name'],
            'start_date' => mousefight_player_stats_iso_timestamp(
                $season['start_date']
            ),
            'end_date' => mousefight_player_stats_iso_timestamp(
                $season['end_date'] ?? null
            ),
            'is_active' => (bool)((int)$season['is_active']),
        ],
        'filters' => [
            'scope' => $queryScope,
            'all_time' => $allTime,
            'user_id' => $userIdFilter !== '' ? $userIdFilter : null,
            'token_id' => $tokenIdFilter !== '' ? $tokenIdFilter : null,
            'recent_limit' => $recentLimit,
        ],
        'player' => [
            'user_id' => $resolvedUserId !== '' ? $resolvedUserId : null,
            'username' => mousefight_player_stats_nullable_string(
                $currentUser['username']
                    ?? $latestParticipant['username']
                    ?? null
            ),
            'display_name' => mousefight_player_stats_nullable_string(
                $latestParticipant['display_name']
                    ?? $latestParticipant['username']
                    ?? null
            ),
            'avatar_url' => $avatar['avatar_url'],
            'avatar_source' => $avatar['avatar_source'],
            'historical_avatar_url' => $avatar['historical_avatar_url'],
        ],
        'stats' => [
            'total_fights' => $totalFights,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => mousefight_player_stats_rate(
                $wins,
                $totalFights
            ),
            'pvp_fights' => $pvpFights,
            'pvp_wins' => $pvpWins,
            'pvp_losses' => max(0, $pvpFights - $pvpWins),
            'event_fights' => $eventFights,
            'event_championships' => $eventChampionships,
            'event_losses' => max(
                0,
                $eventFights - $eventChampionships
            ),
            'rounds_played' => $roundsPlayed,
            'rounds_won' => $roundsWon,
            'rounds_lost' => max(0, $roundsPlayed - $roundsWon),
            'round_win_rate' => mousefight_player_stats_rate(
                $roundsWon,
                $roundsPlayed
            ),
        ],
        'battle_modes' => $battleModeStats,
        'most_used_mouse' => isset($mice[0])
            ? mousefight_player_stats_mouse($mice[0])
            : null,
        'mice_used' => array_map(
            'mousefight_player_stats_mouse',
            array_slice($mice, 0, 10)
        ),
        'recent_fights' => $recentFights,
    ]);
} catch (InvalidArgumentException $error) {
    mousefight_player_stats_json([
        'success' => false,
        'error' => $error->getMessage(),
    ], 400);
} catch (Throwable $error) {
    error_log(
        '[MOUSEFIGHT PLAYER STATS API] ' . $error->getMessage()
    );

    mousefight_player_stats_json([
        'success' => false,
        'error' => 'Unable to load MouseFight player statistics.',
    ], 500);
}
