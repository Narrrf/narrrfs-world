<?php
/**
 * Recent MouseFights public history API.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint returns official finished MouseFight history for one season.
 * It is shared by the future MouseFight page, profile integration,
 * leaderboards, and read-only admin views.
 *
 * Historical safety:
 * - Fight headers come from tbl_mousefights.
 * - Fighter names, NFT images, and fighter power come from the stored
 *   tbl_mousefight_participants fight-time snapshot.
 * - Current Discord PFPs may be displayed from tbl_users.avatar_url.
 * - The fight-time Discord PFP remains available as a historical fallback.
 * - Legacy fights without an explicit battle mode are displayed as Champion
 *   Mode because they used the original pre-mode MouseFight engine.
 *
 * This endpoint must not:
 * - write database rows;
 * - change MouseFight results;
 * - recalculate combat;
 * - settle DSPOINC;
 * - change burns, escrow, prizes, or token payouts;
 * - alter Fight Recovery;
 * - mutate Genesis ownership, traits, abilities, names, or Genetic Items.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

const RECENT_MOUSEFIGHTS_DEFAULT_LIMIT = 20;
const RECENT_MOUSEFIGHTS_MAX_LIMIT = 100;

const RECENT_MOUSEFIGHTS_MODE_PVP = 'pvp_challenge';
const RECENT_MOUSEFIGHTS_MODE_EVENT = 'admin_bracket_event';

const RECENT_MOUSEFIGHTS_BATTLE_MODE_CHAMPION = 'champion';
const RECENT_MOUSEFIGHTS_BATTLE_MODE_UNDERDOG = 'underdog';
const RECENT_MOUSEFIGHTS_BATTLE_MODE_EQUALIZED = 'equalized';
const RECENT_MOUSEFIGHTS_BATTLE_MODE_CHAOS = 'chaos';

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

/**
 * Return one JSON response and stop execution.
 */
function recent_mousefights_json(array $payload, int $statusCode = 200): void
{
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
function recent_mousefights_bounded_int(
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
 * Return a clean optional request string.
 */
function recent_mousefights_optional_string(
    $value,
    int $maximumLength = 200
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
function recent_mousefights_nullable_string($value): ?string
{
    $cleanValue = trim((string)$value);

    return $cleanValue !== '' ? $cleanValue : null;
}

/**
 * Convert known SQLite and ISO timestamps to public UTC ISO-8601.
 *
 * Plain language for DEVS:
 * Event rows currently use ISO timestamps while older PVP rows may use
 * "YYYY-MM-DD HH:MM:SS". This normalizes both for frontend consumers.
 * It does not modify stored timestamps.
 */
function recent_mousefights_iso_timestamp($value): ?string
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
 *
 * Plain language:
 * Discord IDs can be larger than normal integer ranges in some environments.
 * This string-based modulo avoids converting the complete ID to an integer.
 */
function recent_mousefights_string_modulo(
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
function recent_mousefights_default_avatar(string $userId): string
{
    $avatarIndex = recent_mousefights_string_modulo($userId, 5);

    return "https://cdn.discordapp.com/embed/avatars/{$avatarIndex}.png";
}

/**
 * Resolve the public Discord avatar and explain which source was selected.
 *
 * Current tbl_users avatar is preferred for present-day member recognition.
 * The participant avatar remains the historical fallback.
 */
function recent_mousefights_resolve_avatar(
    string $userId,
    $currentAvatar,
    $historicalAvatar
): array {
    $currentAvatarUrl =
        recent_mousefights_nullable_string($currentAvatar);

    $historicalAvatarUrl =
        recent_mousefights_nullable_string($historicalAvatar);

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
        'avatar_url' => recent_mousefights_default_avatar($userId),
        'avatar_source' => 'discord_default',
        'historical_avatar_url' => null,
    ];
}

/**
 * Bind named PDO values using the correct basic value type.
 */
function recent_mousefights_bind_values(
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
 * Return the validated technical fight mode filter.
 */
function recent_mousefights_mode_filter($value): string
{
    $mode = recent_mousefights_optional_string($value, 50);

    $allowedModes = [
        RECENT_MOUSEFIGHTS_MODE_PVP,
        RECENT_MOUSEFIGHTS_MODE_EVENT,
    ];

    return in_array($mode, $allowedModes, true) ? $mode : '';
}

/**
 * Return the validated battle mode filter.
 */
function recent_mousefights_battle_mode_filter($value): string
{
    $battleMode = recent_mousefights_optional_string($value, 50);

    $allowedBattleModes = [
        RECENT_MOUSEFIGHTS_BATTLE_MODE_CHAMPION,
        RECENT_MOUSEFIGHTS_BATTLE_MODE_UNDERDOG,
        RECENT_MOUSEFIGHTS_BATTLE_MODE_EQUALIZED,
        RECENT_MOUSEFIGHTS_BATTLE_MODE_CHAOS,
    ];

    return in_array($battleMode, $allowedBattleModes, true)
        ? $battleMode
        : '';
}

try {
    if (!file_exists($dbPath)) {
        throw new RuntimeException('Database file not found.');
    }

    $limit = recent_mousefights_bounded_int(
        $_GET['limit'] ?? null,
        RECENT_MOUSEFIGHTS_DEFAULT_LIMIT,
        1,
        RECENT_MOUSEFIGHTS_MAX_LIMIT
    );

    $offset = recent_mousefights_bounded_int(
        $_GET['offset'] ?? null,
        0,
        0,
        1000000
    );

    $requestedSeasonId = recent_mousefights_bounded_int(
        $_GET['season_id'] ?? null,
        0,
        0,
        PHP_INT_MAX
    );

    $modeFilter =
        recent_mousefights_mode_filter($_GET['mode'] ?? null);

    $battleModeFilter =
        recent_mousefights_battle_mode_filter(
            $_GET['battle_mode'] ?? null
        );

    $userIdFilter =
        recent_mousefights_optional_string(
            $_GET['user_id'] ?? null,
            100
        );

    $tokenIdFilter =
        recent_mousefights_optional_string(
            $_GET['token_id'] ?? null,
            200
        );

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /**
     * Resolve the requested season or default to the active season.
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
        recent_mousefights_json([
            'success' => false,
            'error' => 'Season not found.',
        ], 404);
    }

    $seasonStart = trim((string)$season['start_date']);
    $seasonEnd = trim((string)($season['end_date'] ?? ''));

    if ($seasonStart === '') {
        throw new RuntimeException(
            'Selected season has no valid start date.'
        );
    }

    /**
     * Build the shared official-history filter.
     *
     * Only finished fights are public competitive history.
     * Season start is inclusive and season end is exclusive.
     */
    $whereParts = [
        "f.status = 'finished'",
        "datetime(f.created_at) >= datetime(:season_start)",
    ];

    $queryValues = [
        ':season_start' => $seasonStart,
    ];

    if ($seasonEnd !== '') {
        $whereParts[] =
            "datetime(f.created_at) < datetime(:season_end)";

        $queryValues[':season_end'] = $seasonEnd;
    }

    if ($modeFilter !== '') {
        $whereParts[] = 'f.mode = :mode';
        $queryValues[':mode'] = $modeFilter;
    }

    if ($battleModeFilter !== '') {
        $whereParts[] = "
            COALESCE(
                NULLIF(
                    TRIM(
                        json_extract(
                            f.metadata_json,
                            '$.battle_mode'
                        )
                    ),
                    ''
                ),
                'champion'
            ) = :battle_mode
        ";

        $queryValues[':battle_mode'] = $battleModeFilter;
    }

    if ($userIdFilter !== '') {
        $whereParts[] = "
            EXISTS (
                SELECT 1
                FROM tbl_mousefight_participants filter_user
                WHERE filter_user.fight_id = f.fight_id
                  AND TRIM(CAST(filter_user.user_id AS TEXT))
                      = TRIM(CAST(:user_id AS TEXT))
            )
        ";

        $queryValues[':user_id'] = $userIdFilter;
    }

    if ($tokenIdFilter !== '') {
        $whereParts[] = "
            EXISTS (
                SELECT 1
                FROM tbl_mousefight_participants filter_token
                WHERE filter_token.fight_id = f.fight_id
                  AND TRIM(CAST(filter_token.token_id AS TEXT))
                      = TRIM(CAST(:token_id AS TEXT))
            )
        ";

        $queryValues[':token_id'] = $tokenIdFilter;
    }

    $whereSql = implode("\n AND ", $whereParts);

    $countStatement = $pdo->prepare("
        SELECT COUNT(*)
        FROM tbl_mousefights f
        WHERE {$whereSql}
    ");

    recent_mousefights_bind_values(
        $countStatement,
        $queryValues
    );

    $countStatement->execute();
    $totalMatching = (int)$countStatement->fetchColumn();

    /**
     * Load compact fight headers and authoritative row counts.
     *
     * Header metadata round_count is intentionally not used because verified
     * completed events may still contain zero there.
     */
    $fightStatement = $pdo->prepare("
        SELECT
            f.fight_id,
            f.title,
            f.status,
            f.mode,
            f.bracket_mode,
            f.match_format,
            f.wager_dspoinc,
            f.buy_in_dspoinc,
            f.prize_dspoinc,
            f.token_symbol,
            f.token_amount,
            f.winner_user_id,
            f.winner_token_id,
            f.created_at,
            f.started_at,
            f.ended_at,
            CASE
                WHEN NULLIF(
                    TRIM(
                        json_extract(
                            f.metadata_json,
                            '$.battle_mode'
                        )
                    ),
                    ''
                ) IS NULL
                THEN 'champion'
                ELSE json_extract(
                    f.metadata_json,
                    '$.battle_mode'
                )
            END AS battle_mode,
            CASE
                WHEN NULLIF(
                    TRIM(
                        json_extract(
                            f.metadata_json,
                            '$.battle_mode'
                        )
                    ),
                    ''
                ) IS NULL
                THEN 'legacy_champion_default'
                ELSE 'header_metadata'
            END AS battle_mode_source,
            (
                SELECT COUNT(*)
                FROM tbl_mousefight_participants participant_count
                WHERE participant_count.fight_id = f.fight_id
            ) AS participant_count,
            (
                SELECT COUNT(*)
                FROM tbl_mousefight_rounds round_count
                WHERE round_count.fight_id = f.fight_id
            ) AS round_count
        FROM tbl_mousefights f
        WHERE {$whereSql}
        ORDER BY
            datetime(f.created_at) DESC,
            f.fight_id DESC
        LIMIT :limit
        OFFSET :offset
    ");

    $fightQueryValues = $queryValues;
    $fightQueryValues[':limit'] = $limit;
    $fightQueryValues[':offset'] = $offset;

    recent_mousefights_bind_values(
        $fightStatement,
        $fightQueryValues
    );

    $fightStatement->execute();
    $fightRows = $fightStatement->fetchAll();

    if (!$fightRows) {
        recent_mousefights_json([
            'success' => true,
            'generated_at' => gmdate('c'),
            'season' => [
                'season_id' => (int)$season['season_id'],
                'season_name' => (string)$season['season_name'],
                'start_date' =>
                    recent_mousefights_iso_timestamp(
                        $season['start_date']
                    ),
                'end_date' =>
                    recent_mousefights_iso_timestamp(
                        $season['end_date'] ?? null
                    ),
                'is_active' =>
                    (bool)((int)$season['is_active']),
            ],
            'filters' => [
                'mode' => $modeFilter !== '' ? $modeFilter : null,
                'battle_mode' =>
                    $battleModeFilter !== ''
                        ? $battleModeFilter
                        : null,
                'user_id' =>
                    $userIdFilter !== '' ? $userIdFilter : null,
                'token_id' =>
                    $tokenIdFilter !== '' ? $tokenIdFilter : null,
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'returned' => 0,
                'total_matching' => $totalMatching,
                'has_more' => false,
            ],
            'fights' => [],
        ]);
    }

    $fightIds = array_column($fightRows, 'fight_id');
    $fightPlaceholders = [];

    foreach ($fightIds as $index => $fightId) {
        $fightPlaceholders[] = ':fight_id_' . $index;
    }

    /**
     * Load fight-time participant identity with current Discord avatar support.
     *
     * The participant row remains authoritative for the historical fighter.
     * tbl_users is joined only for the current Discord username/PFP display.
     */
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
            " . implode(', ', $fightPlaceholders) . "
        )
        ORDER BY
            p.fight_id,
            CASE
                WHEN p.final_rank IS NULL THEN 1
                ELSE 0
            END,
            p.final_rank,
            p.id
    ");

    foreach ($fightIds as $index => $fightId) {
        $participantStatement->bindValue(
            ':fight_id_' . $index,
            $fightId,
            PDO::PARAM_STR
        );
    }

    $participantStatement->execute();
    $participantRows = $participantStatement->fetchAll();

    $participantsByFight = [];

    foreach ($participantRows as $participantRow) {
        $fightId = (string)$participantRow['fight_id'];
        $userId = (string)$participantRow['user_id'];

        $avatar = recent_mousefights_resolve_avatar(
            $userId,
            $participantRow['current_avatar_url'] ?? null,
            $participantRow['historical_avatar_url'] ?? null
        );

        $participantsByFight[$fightId][] = [
            'user_id' => $userId,
            'username' =>
                recent_mousefights_nullable_string(
                    $participantRow['current_username'] ?? null
                )
                ?? recent_mousefights_nullable_string(
                    $participantRow['historical_username'] ?? null
                )
                ?? $userId,
            'historical_username' =>
                recent_mousefights_nullable_string(
                    $participantRow['historical_username'] ?? null
                ),
            'display_name' =>
                recent_mousefights_nullable_string(
                    $participantRow['display_name'] ?? null
                ),
            'avatar_url' => $avatar['avatar_url'],
            'avatar_source' => $avatar['avatar_source'],
            'historical_avatar_url' =>
                $avatar['historical_avatar_url'],
            'token_id' => (string)$participantRow['token_id'],
            'collection' =>
                recent_mousefights_nullable_string(
                    $participantRow['collection'] ?? null
                ) ?? 'genesis',
            'custom_name' =>
                recent_mousefights_nullable_string(
                    $participantRow['custom_name'] ?? null
                ),
            'metadata_name' =>
                recent_mousefights_nullable_string(
                    $participantRow['metadata_name'] ?? null
                ),
            'image_url' =>
                recent_mousefights_nullable_string(
                    $participantRow['image_url'] ?? null
                ),
            'mouse_warrior_power' =>
                (int)($participantRow['mouse_warrior_power'] ?? 0),
            'final_rank' =>
                $participantRow['final_rank'] !== null
                    ? (int)$participantRow['final_rank']
                    : null,
            'joined_at' =>
                recent_mousefights_iso_timestamp(
                    $participantRow['joined_at'] ?? null
                ),
        ];
    }

    $fights = [];

    foreach ($fightRows as $fightRow) {
        $fightId = (string)$fightRow['fight_id'];
        $participants = $participantsByFight[$fightId] ?? [];

        $winner = null;

        foreach ($participants as &$participant) {
            $isWinner =
                (string)$participant['user_id']
                    === (string)$fightRow['winner_user_id']
                && (string)$participant['token_id']
                    === (string)$fightRow['winner_token_id'];

            $participant['is_winner'] = $isWinner;

            if ($isWinner) {
                $winner = $participant;
            }
        }
        unset($participant);

        $fights[] = [
            'fight_id' => $fightId,
            'title' =>
                recent_mousefights_nullable_string(
                    $fightRow['title'] ?? null
                ),
            'status' => (string)$fightRow['status'],
            'mode' => (string)$fightRow['mode'],
            'battle_mode' => (string)$fightRow['battle_mode'],
            'battle_mode_source' =>
                (string)$fightRow['battle_mode_source'],
            'bracket_mode' =>
                recent_mousefights_nullable_string(
                    $fightRow['bracket_mode'] ?? null
                ),
            'match_format' =>
                (string)$fightRow['match_format'],
            'participant_count' =>
                (int)$fightRow['participant_count'],
            'round_count' =>
                (int)$fightRow['round_count'],
            'winner' => $winner,
            'participants' => $participants,
            'configured_economy' => [
                'wager_dspoinc_per_player' =>
                    (int)($fightRow['wager_dspoinc'] ?? 0),
                'entry_cost_dspoinc' =>
                    (int)($fightRow['buy_in_dspoinc'] ?? 0),
                'prize_dspoinc' =>
                    (int)($fightRow['prize_dspoinc'] ?? 0),
                'token_symbol' =>
                    recent_mousefights_nullable_string(
                        $fightRow['token_symbol'] ?? null
                    ),
                'token_amount' =>
                    (float)($fightRow['token_amount'] ?? 0),
                'status_note' =>
                    'Configured fight terms only. This response does not confirm burn, settlement, approval, or token delivery.',
            ],
            'created_at' =>
                recent_mousefights_iso_timestamp(
                    $fightRow['created_at'] ?? null
                ),
            'started_at' =>
                recent_mousefights_iso_timestamp(
                    $fightRow['started_at'] ?? null
                ),
            'ended_at' =>
                recent_mousefights_iso_timestamp(
                    $fightRow['ended_at'] ?? null
                ),
        ];
    }

    $returned = count($fights);

    recent_mousefights_json([
        'success' => true,
        'generated_at' => gmdate('c'),
        'history_contract' => [
            'official_status' => 'finished',
            'season_timestamp' => 'created_at',
            'season_start_inclusive' => true,
            'season_end_exclusive' => true,
            'legacy_missing_battle_mode' => 'champion',
            'historical_fighter_source' =>
                'tbl_mousefight_participants',
            'current_discord_avatar_source' =>
                'tbl_users.avatar_url',
            'economy_values_are_configured_terms' => true,
        ],
        'season' => [
            'season_id' => (int)$season['season_id'],
            'season_name' => (string)$season['season_name'],
            'start_date' =>
                recent_mousefights_iso_timestamp(
                    $season['start_date']
                ),
            'end_date' =>
                recent_mousefights_iso_timestamp(
                    $season['end_date'] ?? null
                ),
            'is_active' =>
                (bool)((int)$season['is_active']),
        ],
        'filters' => [
            'mode' => $modeFilter !== '' ? $modeFilter : null,
            'battle_mode' =>
                $battleModeFilter !== ''
                    ? $battleModeFilter
                    : null,
            'user_id' =>
                $userIdFilter !== '' ? $userIdFilter : null,
            'token_id' =>
                $tokenIdFilter !== '' ? $tokenIdFilter : null,
        ],
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'returned' => $returned,
            'total_matching' => $totalMatching,
            'has_more' =>
                ($offset + $returned) < $totalMatching,
        ],
        'fights' => $fights,
    ]);
} catch (Throwable $error) {
    recent_mousefights_json([
        'success' => false,
        'error' => $error->getMessage(),
    ], 500);
}