<?php
/**
 * Public MouseFight detail API.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint reconstructs one official finished MouseFight for the website.
 * It returns the stored fight header, fight-time participants, reconstructed
 * match scores, and curated round results.
 *
 * Historical safety:
 * - The fight header remains authoritative for the overall winner.
 * - Fighter identity and power come from tbl_mousefight_participants snapshots.
 * - Match scores are reconstructed from round winner user_id + token_id values.
 * - Legacy fights without explicit battle_mode are displayed as Champion Mode.
 * - Older legacy rounds may not contain nested combat-event objects. Missing
 *   event details are returned as null instead of being invented as false.
 *
 * This endpoint must not:
 * - write database rows;
 * - rerun or recalculate combat;
 * - change fight winners, rankings, or rounds;
 * - settle DSPOINC, burns, escrow, prizes, or token payouts;
 * - change Fight Recovery;
 * - mutate Genesis ownership, traits, abilities, names, or Genetic Items;
 * - expose raw metadata_json or internal Genetic Item calculations.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

const MOUSEFIGHT_DETAILS_MAX_FIGHT_ID_LENGTH = 160;

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

/**
 * Return one JSON response and stop execution.
 */
function mousefight_details_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);

    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}

/**
 * Return a required clean request string.
 */
function mousefight_details_required_string(
    $value,
    string $parameterName,
    int $maximumLength
): string {
    $cleanValue = trim((string)$value);

    if ($cleanValue === '') {
        throw new InvalidArgumentException(
            "Missing required parameter: {$parameterName}"
        );
    }

    if (strlen($cleanValue) > $maximumLength) {
        throw new InvalidArgumentException(
            "Parameter is too long: {$parameterName}"
        );
    }

    return $cleanValue;
}

/**
 * Return a nullable trimmed string.
 */
function mousefight_details_nullable_string($value): ?string
{
    $cleanValue = trim((string)$value);

    return $cleanValue !== '' ? $cleanValue : null;
}

/**
 * Return a safe nullable integer.
 */
function mousefight_details_nullable_int($value): ?int
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return null;
    }

    return (int)$value;
}

/**
 * Return a stored JSON boolean as true, false, or null.
 *
 * Null means the historical round did not record that event field.
 */
function mousefight_details_nullable_bool($value): ?bool
{
    if ($value === null || $value === '') {
        return null;
    }

    return (bool)((int)$value);
}

/**
 * Convert known SQLite and ISO timestamps to public UTC ISO-8601.
 */
function mousefight_details_iso_timestamp($value): ?string
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
function mousefight_details_string_modulo(
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
function mousefight_details_default_avatar(string $userId): string
{
    $avatarIndex = mousefight_details_string_modulo($userId, 5);

    return "https://cdn.discordapp.com/embed/avatars/{$avatarIndex}.png";
}

/**
 * Resolve the public Discord avatar and explain which source was selected.
 */
function mousefight_details_resolve_avatar(
    string $userId,
    $currentAvatar,
    $historicalAvatar
): array {
    $currentAvatarUrl =
        mousefight_details_nullable_string($currentAvatar);

    $historicalAvatarUrl =
        mousefight_details_nullable_string($historicalAvatar);

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
        'avatar_url' => mousefight_details_default_avatar($userId),
        'avatar_source' => 'discord_default',
        'historical_avatar_url' => null,
    ];
}

/**
 * Build the exact historical fighter identity key used for round matching.
 */
function mousefight_details_fighter_key($userId, $tokenId): string
{
    return trim((string)$userId) . '|' . trim((string)$tokenId);
}

/**
 * Return a compact public participant object for round and winner references.
 */
function mousefight_details_compact_participant(?array $participant): ?array
{
    if ($participant === null) {
        return null;
    }

    return [
        'user_id' => $participant['user_id'],
        'username' => $participant['username'],
        'display_name' => $participant['display_name'],
        'avatar_url' => $participant['avatar_url'],
        'token_id' => $participant['token_id'],
        'collection' => $participant['collection'],
        'custom_name' => $participant['custom_name'],
        'metadata_name' => $participant['metadata_name'],
        'image_url' => $participant['image_url'],
        'mouse_warrior_power' => $participant['mouse_warrior_power'],
    ];
}

/**
 * Return a stable public bracket-round value.
 *
 * Older PVP rows may not have a bracket round. Zero keeps those matches
 * groupable without pretending they belonged to a tournament bracket.
 */
function mousefight_details_bracket_round($value): int
{
    return is_numeric($value) ? (int)$value : 0;
}

try {
    if (!file_exists($dbPath)) {
        throw new RuntimeException('Database file not found.');
    }

    try {
        $fightId = mousefight_details_required_string(
            $_GET['fight_id'] ?? null,
            'fight_id',
            MOUSEFIGHT_DETAILS_MAX_FIGHT_ID_LENGTH
        );
    } catch (InvalidArgumentException $error) {
        mousefight_details_json([
            'success' => false,
            'error' => $error->getMessage(),
        ], 400);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /**
     * Load only official finished public history.
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
            f.inventory_enabled,
            f.all_levels_accepted,
            f.fitness_required,
            f.min_highest_trait_level,
            f.max_inventory_items,
            f.max_players,
            f.duration_seconds,
            f.recovery_minutes,
            f.winner_user_id,
            f.winner_token_id,
            f.created_at,
            f.started_at,
            f.ended_at,
            CASE
                WHEN NULLIF(
                    TRIM(json_extract(f.metadata_json, '$.battle_mode')),
                    ''
                ) IS NULL
                THEN 'champion'
                ELSE json_extract(f.metadata_json, '$.battle_mode')
            END AS battle_mode,
            CASE
                WHEN NULLIF(
                    TRIM(json_extract(f.metadata_json, '$.battle_mode')),
                    ''
                ) IS NULL
                THEN 'legacy_champion_default'
                ELSE 'header_metadata'
            END AS battle_mode_source,
            (
                SELECT COUNT(*)
                FROM tbl_mousefight_participants pc
                WHERE pc.fight_id = f.fight_id
            ) AS participant_count,
            (
                SELECT COUNT(*)
                FROM tbl_mousefight_rounds rc
                WHERE rc.fight_id = f.fight_id
            ) AS round_count
        FROM tbl_mousefights f
        WHERE f.fight_id = :fight_id
          AND f.status = 'finished'
        LIMIT 1
    ");

    $fightStatement->bindValue(':fight_id', $fightId, PDO::PARAM_STR);
    $fightStatement->execute();
    $fightRow = $fightStatement->fetch();

    if (!$fightRow) {
        mousefight_details_json([
            'success' => false,
            'error' => 'Finished MouseFight not found.',
        ], 404);
    }

    /**
     * Resolve the season using the verified inclusive-start/exclusive-end rule.
     */
    $seasonStatement = $pdo->prepare("
        SELECT
            season_id,
            season_name,
            start_date,
            end_date,
            is_active
        FROM tbl_seasons
        WHERE datetime(:created_at) >= datetime(start_date)
          AND (
              end_date IS NULL
              OR TRIM(end_date) = ''
              OR datetime(:created_at) < datetime(end_date)
          )
        ORDER BY season_id DESC
        LIMIT 1
    ");

    $seasonStatement->bindValue(
        ':created_at',
        (string)$fightRow['created_at'],
        PDO::PARAM_STR
    );
    $seasonStatement->execute();
    $seasonRow = $seasonStatement->fetch();

    /**
     * Load the fight-time fighter snapshots and current Discord PFPs.
     */
    $participantStatement = $pdo->prepare("
        SELECT
            p.id,
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
            p.trait_power,
            p.ability_power,
            p.genetic_support_power,
            p.highest_trait_level,
            p.fitness_unlocked,
            p.inventory_enabled,
            p.max_inventory_items,
            p.status,
            p.wins,
            p.losses,
            p.kills,
            p.final_rank,
            p.bracket_seed,
            p.current_round,
            p.joined_at,
            p.eliminated_at,
            u.username AS current_username,
            u.avatar_url AS current_avatar_url
        FROM tbl_mousefight_participants p
        LEFT JOIN tbl_users u
            ON TRIM(CAST(u.discord_id AS TEXT))
             = TRIM(CAST(p.user_id AS TEXT))
        WHERE p.fight_id = :fight_id
        ORDER BY
            CASE WHEN p.final_rank IS NULL THEN 1 ELSE 0 END,
            p.final_rank,
            p.id
    ");

    $participantStatement->bindValue(':fight_id', $fightId, PDO::PARAM_STR);
    $participantStatement->execute();
    $participantRows = $participantStatement->fetchAll();

    $participants = [];
    $participantsByKey = [];

    foreach ($participantRows as $participantRow) {
        $userId = (string)$participantRow['user_id'];
        $tokenId = (string)$participantRow['token_id'];

        $avatar = mousefight_details_resolve_avatar(
            $userId,
            $participantRow['current_avatar_url'] ?? null,
            $participantRow['historical_avatar_url'] ?? null
        );

        $participant = [
            'user_id' => $userId,
            'username' =>
                mousefight_details_nullable_string(
                    $participantRow['current_username'] ?? null
                )
                ?? mousefight_details_nullable_string(
                    $participantRow['historical_username'] ?? null
                )
                ?? $userId,
            'historical_username' =>
                mousefight_details_nullable_string(
                    $participantRow['historical_username'] ?? null
                ),
            'display_name' =>
                mousefight_details_nullable_string(
                    $participantRow['display_name'] ?? null
                ),
            'avatar_url' => $avatar['avatar_url'],
            'avatar_source' => $avatar['avatar_source'],
            'historical_avatar_url' =>
                $avatar['historical_avatar_url'],
            'token_id' => $tokenId,
            'collection' =>
                mousefight_details_nullable_string(
                    $participantRow['collection'] ?? null
                ) ?? 'genesis',
            'custom_name' =>
                mousefight_details_nullable_string(
                    $participantRow['custom_name'] ?? null
                ),
            'metadata_name' =>
                mousefight_details_nullable_string(
                    $participantRow['metadata_name'] ?? null
                ),
            'image_url' =>
                mousefight_details_nullable_string(
                    $participantRow['image_url'] ?? null
                ),
            'mouse_warrior_power' =>
                (int)($participantRow['mouse_warrior_power'] ?? 0),
            'trait_power' =>
                (int)($participantRow['trait_power'] ?? 0),
            'ability_power' =>
                (int)($participantRow['ability_power'] ?? 0),
            'genetic_support_power' =>
                (float)($participantRow['genetic_support_power'] ?? 0),
            'highest_trait_level' =>
                (int)($participantRow['highest_trait_level'] ?? 0),
            'fitness_unlocked' =>
                (bool)((int)($participantRow['fitness_unlocked'] ?? 0)),
            'inventory_enabled' =>
                (bool)((int)($participantRow['inventory_enabled'] ?? 0)),
            'max_inventory_items' =>
                (int)($participantRow['max_inventory_items'] ?? 0),
            'status' =>
                mousefight_details_nullable_string(
                    $participantRow['status'] ?? null
                ),
            'wins' => (int)($participantRow['wins'] ?? 0),
            'losses' => (int)($participantRow['losses'] ?? 0),
            'kills' => (int)($participantRow['kills'] ?? 0),
            'final_rank' =>
                mousefight_details_nullable_int(
                    $participantRow['final_rank'] ?? null
                ),
            'bracket_seed' =>
                mousefight_details_nullable_int(
                    $participantRow['bracket_seed'] ?? null
                ),
            'current_round' =>
                mousefight_details_nullable_int(
                    $participantRow['current_round'] ?? null
                ),
            'joined_at' =>
                mousefight_details_iso_timestamp(
                    $participantRow['joined_at'] ?? null
                ),
            'eliminated_at' =>
                mousefight_details_iso_timestamp(
                    $participantRow['eliminated_at'] ?? null
                ),
        ];

        $participant['is_winner'] =
            $userId === (string)$fightRow['winner_user_id']
            && $tokenId === (string)$fightRow['winner_token_id'];

        $participants[] = $participant;
        $participantsByKey[
            mousefight_details_fighter_key($userId, $tokenId)
        ] = $participant;
    }

    $winnerKey = mousefight_details_fighter_key(
        $fightRow['winner_user_id'] ?? '',
        $fightRow['winner_token_id'] ?? ''
    );

    $overallWinner = $participantsByKey[$winnerKey] ?? null;

    /**
     * Load curated round fields only. Raw metadata_json is intentionally excluded.
     */
    $roundStatement = $pdo->prepare("
        SELECT
            r.id,
            r.bracket_round,
            r.match_number,
            r.round_number,
            r.attacker_user_id,
            r.attacker_token_id,
            r.defender_user_id,
            r.defender_token_id,
            r.attacker_roll,
            r.defender_roll,
            r.attacker_power,
            r.defender_power,
            r.result,
            r.winner_user_id,
            r.winner_token_id,
            r.event_text,
            r.created_at,
            json_extract(r.metadata_json, '$.attacker_score')
                AS attacker_score,
            json_extract(r.metadata_json, '$.defender_score')
                AS defender_score,
            json_extract(r.metadata_json, '$.attacker_chaos')
                AS attacker_chaos,
            json_extract(r.metadata_json, '$.defender_chaos')
                AS defender_chaos,
            json_extract(r.metadata_json, '$.attacker_combat')
                AS attacker_combat_object,
            json_extract(r.metadata_json, '$.defender_combat')
                AS defender_combat_object,
            json_extract(
                r.metadata_json,
                '$.attacker_combat.specialTriggered'
            ) AS attacker_special,
            json_extract(
                r.metadata_json,
                '$.defender_combat.specialTriggered'
            ) AS defender_special,
            json_extract(
                r.metadata_json,
                '$.attacker_combat.spellTriggered'
            ) AS attacker_spell,
            json_extract(
                r.metadata_json,
                '$.defender_combat.spellTriggered'
            ) AS defender_spell,
            json_extract(
                r.metadata_json,
                '$.attacker_combat.dodgeTriggered'
            ) AS attacker_dodge,
            json_extract(
                r.metadata_json,
                '$.defender_combat.dodgeTriggered'
            ) AS defender_dodge
        FROM tbl_mousefight_rounds r
        WHERE r.fight_id = :fight_id
        ORDER BY
            COALESCE(r.bracket_round, 0),
            COALESCE(r.match_number, 0),
            r.round_number,
            r.id
    ");

    $roundStatement->bindValue(':fight_id', $fightId, PDO::PARAM_STR);
    $roundStatement->execute();
    $roundRows = $roundStatement->fetchAll();

    $matchesByKey = [];

    foreach ($roundRows as $roundRow) {
        $bracketRound = mousefight_details_bracket_round(
            $roundRow['bracket_round'] ?? null
        );
        $matchNumber = is_numeric($roundRow['match_number'] ?? null)
            ? (int)$roundRow['match_number']
            : 0;

        $matchKey = $bracketRound . '|' . $matchNumber;

        $attackerKey = mousefight_details_fighter_key(
            $roundRow['attacker_user_id'] ?? '',
            $roundRow['attacker_token_id'] ?? ''
        );
        $defenderKey = mousefight_details_fighter_key(
            $roundRow['defender_user_id'] ?? '',
            $roundRow['defender_token_id'] ?? ''
        );
        $roundWinnerKey = mousefight_details_fighter_key(
            $roundRow['winner_user_id'] ?? '',
            $roundRow['winner_token_id'] ?? ''
        );

        if (!isset($matchesByKey[$matchKey])) {
            $matchesByKey[$matchKey] = [
                'bracket_round' => $bracketRound,
                'match_number' => $matchNumber,
                'fighter_keys' => [],
                'round_wins' => [],
                'rounds' => [],
            ];
        }

        foreach ([$attackerKey, $defenderKey] as $fighterKey) {
            if ($fighterKey === '|') {
                continue;
            }

            if (!in_array(
                $fighterKey,
                $matchesByKey[$matchKey]['fighter_keys'],
                true
            )) {
                $matchesByKey[$matchKey]['fighter_keys'][] = $fighterKey;
            }

            if (!isset($matchesByKey[$matchKey]['round_wins'][$fighterKey])) {
                $matchesByKey[$matchKey]['round_wins'][$fighterKey] = 0;
            }
        }

        if ($roundWinnerKey !== '|') {
            if (!isset(
                $matchesByKey[$matchKey]['round_wins'][$roundWinnerKey]
            )) {
                $matchesByKey[$matchKey]['round_wins'][$roundWinnerKey] = 0;
            }

            $matchesByKey[$matchKey]['round_wins'][$roundWinnerKey]++;
        }

        $combatDetailsAvailable =
            $roundRow['attacker_combat_object'] !== null
            && $roundRow['defender_combat_object'] !== null;

        $round = [
            'round_id' => (int)$roundRow['id'],
            'round_number' => (int)$roundRow['round_number'],
            'attacker' => mousefight_details_compact_participant(
                $participantsByKey[$attackerKey] ?? null
            ),
            'defender' => mousefight_details_compact_participant(
                $participantsByKey[$defenderKey] ?? null
            ),
            'attacker_roll' =>
                mousefight_details_nullable_int(
                    $roundRow['attacker_roll'] ?? null
                ),
            'defender_roll' =>
                mousefight_details_nullable_int(
                    $roundRow['defender_roll'] ?? null
                ),
            'attacker_power' =>
                (int)($roundRow['attacker_power'] ?? 0),
            'defender_power' =>
                (int)($roundRow['defender_power'] ?? 0),
            'attacker_score' =>
                (int)($roundRow['attacker_score'] ?? 0),
            'defender_score' =>
                (int)($roundRow['defender_score'] ?? 0),
            'attacker_chaos' =>
                (int)($roundRow['attacker_chaos'] ?? 0),
            'defender_chaos' =>
                (int)($roundRow['defender_chaos'] ?? 0),
            'winner' => mousefight_details_compact_participant(
                $participantsByKey[$roundWinnerKey] ?? null
            ),
            'result' =>
                mousefight_details_nullable_string(
                    $roundRow['result'] ?? null
                ),
            'event_text' =>
                mousefight_details_nullable_string(
                    $roundRow['event_text'] ?? null
                ),
            'events' => [
                'combat_details_available' => $combatDetailsAvailable,
                'attacker_special' => $combatDetailsAvailable
                    ? mousefight_details_nullable_bool(
                        $roundRow['attacker_special'] ?? null
                    )
                    : null,
                'defender_special' => $combatDetailsAvailable
                    ? mousefight_details_nullable_bool(
                        $roundRow['defender_special'] ?? null
                    )
                    : null,
                'attacker_spell' => $combatDetailsAvailable
                    ? mousefight_details_nullable_bool(
                        $roundRow['attacker_spell'] ?? null
                    )
                    : null,
                'defender_spell' => $combatDetailsAvailable
                    ? mousefight_details_nullable_bool(
                        $roundRow['defender_spell'] ?? null
                    )
                    : null,
                'attacker_dodge' => $combatDetailsAvailable
                    ? mousefight_details_nullable_bool(
                        $roundRow['attacker_dodge'] ?? null
                    )
                    : null,
                'defender_dodge' => $combatDetailsAvailable
                    ? mousefight_details_nullable_bool(
                        $roundRow['defender_dodge'] ?? null
                    )
                    : null,
            ],
            'created_at' =>
                mousefight_details_iso_timestamp(
                    $roundRow['created_at'] ?? null
                ),
        ];

        $matchesByKey[$matchKey]['rounds'][] = $round;
    }

    $matches = [];

    foreach ($matchesByKey as $matchData) {
        $fighterKeys = array_values($matchData['fighter_keys']);
        $fighterOneKey = $fighterKeys[0] ?? '';
        $fighterTwoKey = $fighterKeys[1] ?? '';

        $fighterOneWins =
            (int)($matchData['round_wins'][$fighterOneKey] ?? 0);
        $fighterTwoWins =
            (int)($matchData['round_wins'][$fighterTwoKey] ?? 0);

        $matchWinnerKey = '';

        if ($fighterOneWins > $fighterTwoWins) {
            $matchWinnerKey = $fighterOneKey;
        } elseif ($fighterTwoWins > $fighterOneWins) {
            $matchWinnerKey = $fighterTwoKey;
        } else {
            $lastRound = end($matchData['rounds']);

            if (is_array($lastRound) && isset($lastRound['winner'])) {
                $lastWinner = $lastRound['winner'];

                if (is_array($lastWinner)) {
                    $matchWinnerKey = mousefight_details_fighter_key(
                        $lastWinner['user_id'] ?? '',
                        $lastWinner['token_id'] ?? ''
                    );
                }
            }
        }

        $matches[] = [
            'bracket_round' => $matchData['bracket_round'],
            'match_number' => $matchData['match_number'],
            'match_format' => (string)$fightRow['match_format'],
            'fighter_one' => mousefight_details_compact_participant(
                $participantsByKey[$fighterOneKey] ?? null
            ),
            'fighter_two' => mousefight_details_compact_participant(
                $participantsByKey[$fighterTwoKey] ?? null
            ),
            'fighter_one_round_wins' => $fighterOneWins,
            'fighter_two_round_wins' => $fighterTwoWins,
            'winner' => mousefight_details_compact_participant(
                $participantsByKey[$matchWinnerKey] ?? null
            ),
            'round_count' => count($matchData['rounds']),
            'rounds' => $matchData['rounds'],
        ];
    }

    $season = null;

    if ($seasonRow) {
        $season = [
            'season_id' => (int)$seasonRow['season_id'],
            'season_name' => (string)$seasonRow['season_name'],
            'start_date' => mousefight_details_iso_timestamp(
                $seasonRow['start_date'] ?? null
            ),
            'end_date' => mousefight_details_iso_timestamp(
                $seasonRow['end_date'] ?? null
            ),
            'is_active' => (bool)((int)$seasonRow['is_active']),
        ];
    }

    $fight = [
        'fight_id' => (string)$fightRow['fight_id'],
        'title' => mousefight_details_nullable_string(
            $fightRow['title'] ?? null
        ),
        'status' => (string)$fightRow['status'],
        'mode' => (string)$fightRow['mode'],
        'battle_mode' => (string)$fightRow['battle_mode'],
        'battle_mode_source' =>
            (string)$fightRow['battle_mode_source'],
        'bracket_mode' => mousefight_details_nullable_string(
            $fightRow['bracket_mode'] ?? null
        ),
        'match_format' => (string)$fightRow['match_format'],
        'participant_count' => (int)$fightRow['participant_count'],
        'round_count' => (int)$fightRow['round_count'],
        'match_count' => count($matches),
        'winner' => mousefight_details_compact_participant(
            $overallWinner
        ),
        'rules' => [
            'inventory_enabled' =>
                (bool)((int)($fightRow['inventory_enabled'] ?? 0)),
            'all_levels_accepted' =>
                (bool)((int)($fightRow['all_levels_accepted'] ?? 0)),
            'fitness_required' =>
                (bool)((int)($fightRow['fitness_required'] ?? 0)),
            'min_highest_trait_level' =>
                (int)($fightRow['min_highest_trait_level'] ?? 0),
            'max_inventory_items' =>
                (int)($fightRow['max_inventory_items'] ?? 0),
            'max_players' => (int)($fightRow['max_players'] ?? 0),
            'duration_seconds' =>
                (int)($fightRow['duration_seconds'] ?? 0),
            'recovery_minutes' =>
                (int)($fightRow['recovery_minutes'] ?? 0),
        ],
        'configured_economy' => [
            'wager_dspoinc_per_player' =>
                (int)($fightRow['wager_dspoinc'] ?? 0),
            'entry_cost_dspoinc' =>
                (int)($fightRow['buy_in_dspoinc'] ?? 0),
            'prize_dspoinc' =>
                (int)($fightRow['prize_dspoinc'] ?? 0),
            'token_symbol' =>
                mousefight_details_nullable_string(
                    $fightRow['token_symbol'] ?? null
                ),
            'token_amount' =>
                (float)($fightRow['token_amount'] ?? 0),
            'status_note' =>
                'Configured fight terms only. This response does not confirm burn, settlement, approval, or token delivery.',
        ],
        'created_at' => mousefight_details_iso_timestamp(
            $fightRow['created_at'] ?? null
        ),
        'started_at' => mousefight_details_iso_timestamp(
            $fightRow['started_at'] ?? null
        ),
        'ended_at' => mousefight_details_iso_timestamp(
            $fightRow['ended_at'] ?? null
        ),
    ];

    mousefight_details_json([
        'success' => true,
        'generated_at' => gmdate('c'),
        'history_contract' => [
            'official_status' => 'finished',
            'season_timestamp' => 'created_at',
            'season_start_inclusive' => true,
            'season_end_exclusive' => true,
            'overall_winner_source' => 'tbl_mousefights',
            'historical_fighter_source' =>
                'tbl_mousefight_participants',
            'match_score_source' =>
                'tbl_mousefight_rounds winner_user_id + winner_token_id',
            'legacy_missing_battle_mode' => 'champion',
            'legacy_combat_events_may_be_null' => true,
            'economy_values_are_configured_terms' => true,
        ],
        'season' => $season,
        'fight' => $fight,
        'participants' => $participants,
        'matches' => $matches,
    ]);
} catch (Throwable $error) {
    mousefight_details_json([
        'success' => false,
        'error' => $error->getMessage(),
    ], 500);
}
