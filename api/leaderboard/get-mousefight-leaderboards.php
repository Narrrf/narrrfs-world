<?php
/**
 * Public MouseFight leaderboards API.
 *
 * Plain language for DEVS FOR DECADES:
 * This read-only endpoint prepares the shared Season MouseFight rankings and
 * explanatory frontend contract for mousefights.html, leaderboard.html,
 * profile previews, and read-only admin displays.
 *
 * Historical authority:
 * - Only finished fights inside the selected season are ranked.
 * - Fight wins use tbl_mousefights winner identity.
 * - Round wins use tbl_mousefight_rounds winner user + token identity.
 * - Fighter names, Genesis images, and power use fight-time participant rows.
 * - Current Discord username/avatar may be displayed when available.
 * - Legacy fights without battle_mode are Champion Mode.
 *
 * This endpoint does not write rows, recalculate combat, settle economy,
 * change Fight Recovery, or mutate Genesis/Lab/ownership/item data.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

const MOUSEFIGHT_LEADERBOARDS_DEFAULT_LIMIT = 20;
const MOUSEFIGHT_LEADERBOARDS_MAX_LIMIT = 100;
const MOUSEFIGHT_LEADERBOARDS_DEFAULT_WIN_RATE_MIN_FIGHTS = 5;
const MOUSEFIGHT_LEADERBOARDS_DEFAULT_MOUSE_MIN_FIGHTS = 5;
const MOUSEFIGHT_LEADERBOARDS_DEFAULT_MODE_MIN_FIGHTS = 3;

const MOUSEFIGHT_LEADERBOARDS_MODE_PVP = 'pvp_challenge';
const MOUSEFIGHT_LEADERBOARDS_MODE_EVENT = 'admin_bracket_event';
const MOUSEFIGHT_LEADERBOARDS_BATTLE_MODES = [
    'champion',
    'underdog',
    'equalized',
    'chaos',
];

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

/** Return one JSON response and stop execution. */
function mousefight_leaderboards_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    exit;
}

/** Return a bounded integer request value. */
function mousefight_leaderboards_bounded_int(
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

/** Return a nullable clean string. */
function mousefight_leaderboards_nullable_string($value): ?string
{
    $cleanValue = trim((string)$value);
    return $cleanValue !== '' ? $cleanValue : null;
}

/** Convert known timestamps to UTC ISO-8601. */
function mousefight_leaderboards_iso_timestamp($value): ?string
{
    $cleanValue = trim((string)$value);
    if ($cleanValue === '') {
        return null;
    }

    $hasTimezone = str_ends_with($cleanValue, 'Z')
        || preg_match('/[+-]\d{2}:\d{2}$/', $cleanValue) === 1;
    $timestamp = strtotime($hasTimezone ? $cleanValue : $cleanValue . ' UTC');

    return $timestamp === false
        ? null
        : gmdate('Y-m-d\TH:i:s\Z', $timestamp);
}

/** Calculate a safe percentage. */
function mousefight_leaderboards_rate(int $wins, int $total): float
{
    return $total > 0 ? round(($wins * 100) / $total, 2) : 0.0;
}

/** Calculate a small modulo without overflowing Discord snowflakes. */
function mousefight_leaderboards_string_modulo(string $value, int $divisor): int
{
    if ($divisor < 1) {
        return 0;
    }

    $remainder = 0;
    foreach (str_split($value) as $character) {
        if ($character < '0' || $character > '9') {
            continue;
        }
        $remainder = (($remainder * 10) + (int)$character) % $divisor;
    }

    return $remainder;
}

/** Return the deterministic Discord default avatar. */
function mousefight_leaderboards_default_avatar(string $userId): string
{
    $index = mousefight_leaderboards_string_modulo($userId, 5);
    return "https://cdn.discordapp.com/embed/avatars/{$index}.png";
}

/** Resolve current PFP, historical PFP, then Discord default. */
function mousefight_leaderboards_resolve_avatar(
    string $userId,
    $currentAvatar,
    $historicalAvatar
): array {
    $current = mousefight_leaderboards_nullable_string($currentAvatar);
    $historical = mousefight_leaderboards_nullable_string($historicalAvatar);

    if ($current !== null) {
        return [
            'avatar_url' => $current,
            'avatar_source' => 'current_user',
            'historical_avatar_url' => $historical,
        ];
    }

    if ($historical !== null) {
        return [
            'avatar_url' => $historical,
            'avatar_source' => 'fight_snapshot',
            'historical_avatar_url' => $historical,
        ];
    }

    return [
        'avatar_url' => mousefight_leaderboards_default_avatar($userId),
        'avatar_source' => 'discord_default',
        'historical_avatar_url' => null,
    ];
}

/** Return true when one participant is the authoritative fight winner. */
function mousefight_leaderboards_is_fight_winner(array $row): bool
{
    $userId = trim((string)($row['user_id'] ?? ''));
    $tokenId = trim((string)($row['token_id'] ?? ''));
    $winnerUserId = trim((string)($row['winner_user_id'] ?? ''));
    $winnerTokenId = trim((string)($row['winner_token_id'] ?? ''));

    if ($userId === '' || $userId !== $winnerUserId) {
        return false;
    }

    if ($tokenId !== '' && $winnerTokenId !== '') {
        return $tokenId === $winnerTokenId;
    }

    return true;
}

/** Return a public battle-mode information contract. */
function mousefight_leaderboards_battle_mode_contract(): array
{
    return [
        'champion' => [
            'value' => 'champion',
            'label' => 'Champion Mode',
            'emoji' => '🏆',
            'theme' => 'gold',
            'description' => 'Full Genesis progression and selected Owner Genetic Items matter normally.',
            'rules' => [
                'Uses authentic fight-time fighter snapshots.',
                'Traits, nine abilities, selected support items, and normal fight rolls retain their full meaning.',
                'Legacy fights without an explicit mode are included here.',
            ],
            'fight_only' => true,
        ],
        'underdog' => [
            'value' => 'underdog',
            'label' => 'Underdog Mode',
            'emoji' => '🐭',
            'theme' => 'green',
            'description' => 'The authentic weaker fighter receives bounded temporary round support while progression still matters.',
            'public_constants' => [
                'base_support_ceiling' => 8,
                'power_gap_multiplier' => 0.50,
                'maximum_round_support' => 40,
            ],
            'fight_only' => true,
        ],
        'equalized' => [
            'value' => 'equalized',
            'label' => 'Equalized Mode',
            'emoji' => '⚖️',
            'theme' => 'cyan',
            'description' => 'Both temporary fighter snapshots move toward the matchup midpoint.',
            'public_constants' => [
                'midpoint_compression_rate' => 0.60,
                'approximate_original_gap_remaining' => 0.40,
            ],
            'fight_only' => true,
        ],
        'chaos' => [
            'value' => 'chaos',
            'label' => 'Chaos Mode',
            'emoji' => '🎲',
            'theme' => 'purple',
            'description' => 'Temporary fight-only fighter values are randomized inside approved shared ranges.',
            'rules' => [
                'Neither fighter receives an authentic progression preference.',
                'One generated matchup pair remains attached to its complete match.',
            ],
            'fight_only' => true,
        ],
    ];
}

/** Return the public nine-ability explanation matrix. */
function mousefight_leaderboards_ability_matrix(): array
{
    return [
        ['key' => 'HP', 'label' => 'HP', 'icon' => '♥', 'description' => 'Supports endurance and survivability.'],
        ['key' => 'SPEED', 'label' => 'Speed', 'icon' => '»', 'description' => 'Improves initiative and turn control.'],
        ['key' => 'AIR', 'label' => 'Air', 'icon' => '◎', 'description' => 'Supports initiative and dodge chance.'],
        ['key' => 'ATK', 'label' => 'Attack', 'icon' => '⚔', 'description' => 'Raises offensive combat power.'],
        ['key' => 'DEF', 'label' => 'Defense', 'icon' => '◆', 'description' => 'Resists incoming offensive power.'],
        ['key' => 'SPECIAL', 'label' => 'Special', 'icon' => '★', 'description' => 'Can trigger a temporary special combat bonus.'],
        ['key' => 'SPELLS', 'label' => 'Spells', 'icon' => '✦', 'description' => 'Can trigger spell power and interacts with spell resistance.'],
        ['key' => 'CRAFTING', 'label' => 'Crafting', 'icon' => '⚒', 'description' => 'Improves temporary selected Genetic Item effectiveness.'],
        ['key' => 'EXPANSION', 'label' => 'Expansion', 'icon' => '⬡', 'description' => 'Supports battlefield control and may increase temporary item capacity.'],
    ];
}

/** Build a compact historical mouse object. */
function mousefight_leaderboards_mouse(array $mouse): array
{
    $fights = (int)($mouse['fights_used'] ?? 0);
    $wins = (int)($mouse['wins'] ?? 0);
    $rounds = (int)($mouse['rounds_played'] ?? 0);
    $roundWins = (int)($mouse['rounds_won'] ?? 0);

    return [
        'user_id' => mousefight_leaderboards_nullable_string($mouse['user_id'] ?? null),
        'token_id' => mousefight_leaderboards_nullable_string($mouse['token_id'] ?? null),
        'collection' => mousefight_leaderboards_nullable_string($mouse['collection'] ?? null),
        'custom_name' => mousefight_leaderboards_nullable_string($mouse['custom_name'] ?? null),
        'metadata_name' => mousefight_leaderboards_nullable_string($mouse['metadata_name'] ?? null),
        'image_url' => mousefight_leaderboards_nullable_string($mouse['image_url'] ?? null),
        'mouse_warrior_power' => isset($mouse['mouse_warrior_power'])
            ? (int)$mouse['mouse_warrior_power']
            : null,
        'fights_used' => $fights,
        'wins' => $wins,
        'losses' => max(0, $fights - $wins),
        'win_rate' => mousefight_leaderboards_rate($wins, $fights),
        'rounds_played' => $rounds,
        'rounds_won' => $roundWins,
        'rounds_lost' => max(0, $rounds - $roundWins),
        'round_win_rate' => mousefight_leaderboards_rate($roundWins, $rounds),
    ];
}

/** Sort and rank a board while keeping the input untouched. */
function mousefight_leaderboards_rank(
    array $rows,
    callable $comparison,
    int $limit
): array {
    usort($rows, $comparison);
    $rows = array_slice($rows, 0, $limit);

    foreach ($rows as $index => &$row) {
        $row['rank'] = $index + 1;
    }
    unset($row);

    return $rows;
}

try {
    if (!file_exists($dbPath)) {
        throw new RuntimeException('Database file not found.');
    }

    $requestedSeasonId = mousefight_leaderboards_bounded_int(
        $_GET['season_id'] ?? null,
        0,
        0,
        PHP_INT_MAX
    );
    $limit = mousefight_leaderboards_bounded_int(
        $_GET['limit'] ?? null,
        MOUSEFIGHT_LEADERBOARDS_DEFAULT_LIMIT,
        5,
        MOUSEFIGHT_LEADERBOARDS_MAX_LIMIT
    );
    $winRateMinimum = mousefight_leaderboards_bounded_int(
        $_GET['win_rate_min_fights'] ?? null,
        MOUSEFIGHT_LEADERBOARDS_DEFAULT_WIN_RATE_MIN_FIGHTS,
        1,
        100
    );
    $mouseMinimum = mousefight_leaderboards_bounded_int(
        $_GET['mouse_min_fights'] ?? null,
        MOUSEFIGHT_LEADERBOARDS_DEFAULT_MOUSE_MIN_FIGHTS,
        1,
        100
    );
    $modeMinimum = mousefight_leaderboards_bounded_int(
        $_GET['mode_min_fights'] ?? null,
        MOUSEFIGHT_LEADERBOARDS_DEFAULT_MODE_MIN_FIGHTS,
        1,
        100
    );

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($requestedSeasonId > 0) {
        $seasonStatement = $pdo->prepare("\n            SELECT season_id, season_name, start_date, end_date, is_active\n            FROM tbl_seasons\n            WHERE season_id = :season_id\n            LIMIT 1\n        ");
        $seasonStatement->bindValue(':season_id', $requestedSeasonId, PDO::PARAM_INT);
    } else {
        $seasonStatement = $pdo->prepare("\n            SELECT season_id, season_name, start_date, end_date, is_active\n            FROM tbl_seasons\n            WHERE is_active = 1\n            ORDER BY season_id DESC\n            LIMIT 1\n        ");
    }

    $seasonStatement->execute();
    $season = $seasonStatement->fetch();
    if (!$season) {
        mousefight_leaderboards_json([
            'success' => false,
            'error' => 'Season not found.',
        ], 404);
    }

    $seasonStart = trim((string)$season['start_date']);
    $seasonEnd = trim((string)($season['end_date'] ?? ''));
    if ($seasonStart === '') {
        throw new RuntimeException('Selected season has no valid start date.');
    }

    $seasonEndSql = $seasonEnd !== ''
        ? 'AND datetime(f.created_at) < datetime(:season_end)'
        : '';

    $participantStatement = $pdo->prepare("\n        SELECT DISTINCT\n            p.fight_id,\n            TRIM(CAST(p.user_id AS TEXT)) AS user_id,\n            p.username AS historical_username,\n            p.display_name,\n            p.avatar_url AS historical_avatar_url,\n            TRIM(CAST(p.token_id AS TEXT)) AS token_id,\n            p.collection,\n            p.custom_name,\n            p.metadata_name,\n            p.image_url,\n            p.mouse_warrior_power,\n            f.mode,\n            f.winner_user_id,\n            f.winner_token_id,\n            f.created_at,\n            f.ended_at,\n            CASE\n                WHEN NULLIF(TRIM(json_extract(f.metadata_json, '$.battle_mode')), '') IS NULL\n                THEN 'champion'\n                ELSE json_extract(f.metadata_json, '$.battle_mode')\n            END AS battle_mode\n        FROM tbl_mousefight_participants p\n        INNER JOIN tbl_mousefights f ON f.fight_id = p.fight_id\n        WHERE f.status = 'finished'\n          AND datetime(f.created_at) >= datetime(:season_start)\n          {$seasonEndSql}\n        ORDER BY datetime(f.created_at), p.id\n    ");
    $participantStatement->bindValue(':season_start', $seasonStart, PDO::PARAM_STR);
    if ($seasonEnd !== '') {
        $participantStatement->bindValue(':season_end', $seasonEnd, PDO::PARAM_STR);
    }
    $participantStatement->execute();
    $participantRows = $participantStatement->fetchAll() ?: [];

    if (count($participantRows) < 1) {
        mousefight_leaderboards_json([
            'success' => true,
            'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'season' => [
                'season_id' => (int)$season['season_id'],
                'season_name' => (string)$season['season_name'],
            ],
            'summary' => [
                'finished_fights' => 0,
                'pvp_fights' => 0,
                'event_fights' => 0,
                'unique_players' => 0,
                'player_participations' => 0,
                'rounds_fought' => 0,
            ],
            'leaderboards' => [],
        ]);
    }

    $currentUsersStatement = $pdo->query("\n        SELECT TRIM(CAST(discord_id AS TEXT)) AS user_id, username, avatar_url\n        FROM tbl_users\n        WHERE discord_id IS NOT NULL\n    ");
    $currentUsers = [];
    foreach (($currentUsersStatement->fetchAll() ?: []) as $userRow) {
        $currentUsers[(string)$userRow['user_id']] = $userRow;
    }

    $players = [];
    $mice = [];
    $fightSummary = [];
    $modeSummary = [];
    foreach (MOUSEFIGHT_LEADERBOARDS_BATTLE_MODES as $mode) {
        $modeSummary[$mode] = [
            'fights' => [],
            'rounds' => 0,
            'player_participations' => 0,
        ];
    }

    foreach ($participantRows as $row) {
        $fightId = trim((string)$row['fight_id']);
        $userId = trim((string)$row['user_id']);
        $tokenId = trim((string)$row['token_id']);
        $battleMode = trim((string)$row['battle_mode']);
        if (!isset($modeSummary[$battleMode])) {
            $battleMode = 'champion';
        }

        if (!isset($fightSummary[$fightId])) {
            $fightSummary[$fightId] = [
                'mode' => trim((string)$row['mode']),
                'battle_mode' => $battleMode,
                'participants' => [],
                'created_at' => $row['created_at'] ?? null,
            ];
        }
        $fightSummary[$fightId]['participants'][$userId . '|' . $tokenId] = true;
        $modeSummary[$battleMode]['fights'][$fightId] = true;
        $modeSummary[$battleMode]['player_participations']++;

        if ($userId === '') {
            continue;
        }

        if (!isset($players[$userId])) {
            $current = $currentUsers[$userId] ?? [];
            $avatar = mousefight_leaderboards_resolve_avatar(
                $userId,
                $current['avatar_url'] ?? null,
                $row['historical_avatar_url'] ?? null
            );
            $players[$userId] = [
                'user_id' => $userId,
                'username' => mousefight_leaderboards_nullable_string($current['username'] ?? null)
                    ?? mousefight_leaderboards_nullable_string($row['display_name'] ?? null)
                    ?? mousefight_leaderboards_nullable_string($row['historical_username'] ?? null)
                    ?? 'Unknown Fighter',
                'historical_username' => mousefight_leaderboards_nullable_string($row['historical_username'] ?? null),
                'avatar_url' => $avatar['avatar_url'],
                'avatar_source' => $avatar['avatar_source'],
                'historical_avatar_url' => $avatar['historical_avatar_url'],
                'fight_ids' => [],
                'wins' => 0,
                'pvp_fights' => 0,
                'pvp_wins' => 0,
                'event_fights' => 0,
                'event_championships' => 0,
                'rounds_played' => 0,
                'rounds_won' => 0,
                'distinct_mice' => [],
                'mode_stats' => [],
                'first_fight_at' => null,
                'last_fight_at' => null,
            ];
            foreach (MOUSEFIGHT_LEADERBOARDS_BATTLE_MODES as $mode) {
                $players[$userId]['mode_stats'][$mode] = [
                    'fights' => 0,
                    'wins' => 0,
                    'losses' => 0,
                    'win_rate' => 0.0,
                    'rounds_played' => 0,
                    'rounds_won' => 0,
                ];
            }
        }

        if (!isset($players[$userId]['fight_ids'][$fightId])) {
            $players[$userId]['fight_ids'][$fightId] = true;
            $isWinner = mousefight_leaderboards_is_fight_winner($row);
            if ($isWinner) {
                $players[$userId]['wins']++;
            }

            $fightMode = trim((string)$row['mode']);
            if ($fightMode === MOUSEFIGHT_LEADERBOARDS_MODE_PVP) {
                $players[$userId]['pvp_fights']++;
                if ($isWinner) {
                    $players[$userId]['pvp_wins']++;
                }
            }
            if ($fightMode === MOUSEFIGHT_LEADERBOARDS_MODE_EVENT) {
                $players[$userId]['event_fights']++;
                if ($isWinner) {
                    $players[$userId]['event_championships']++;
                }
            }

            $players[$userId]['mode_stats'][$battleMode]['fights']++;
            if ($isWinner) {
                $players[$userId]['mode_stats'][$battleMode]['wins']++;
            } else {
                $players[$userId]['mode_stats'][$battleMode]['losses']++;
            }

            $createdAt = mousefight_leaderboards_iso_timestamp($row['created_at'] ?? null);
            if ($players[$userId]['first_fight_at'] === null) {
                $players[$userId]['first_fight_at'] = $createdAt;
            }
            $players[$userId]['last_fight_at'] = $createdAt;
        }

        if ($tokenId !== '') {
            $players[$userId]['distinct_mice'][$tokenId] = true;
            $mouseKey = $userId . '|' . $tokenId;
            if (!isset($mice[$mouseKey])) {
                $mice[$mouseKey] = [
                    'user_id' => $userId,
                    'token_id' => $tokenId,
                    'collection' => $row['collection'] ?? null,
                    'custom_name' => $row['custom_name'] ?? null,
                    'metadata_name' => $row['metadata_name'] ?? null,
                    'image_url' => $row['image_url'] ?? null,
                    'mouse_warrior_power' => $row['mouse_warrior_power'] ?? null,
                    'fight_ids' => [],
                    'wins' => 0,
                    'rounds_played' => 0,
                    'rounds_won' => 0,
                    'owner' => [
                        'user_id' => $userId,
                        'username' => $players[$userId]['username'],
                        'avatar_url' => $players[$userId]['avatar_url'],
                        'avatar_source' => $players[$userId]['avatar_source'],
                    ],
                ];
            }
            if (!isset($mice[$mouseKey]['fight_ids'][$fightId])) {
                $mice[$mouseKey]['fight_ids'][$fightId] = true;
                if (mousefight_leaderboards_is_fight_winner($row)) {
                    $mice[$mouseKey]['wins']++;
                }
            }
        }
    }

    $roundStatement = $pdo->prepare("\n        SELECT\n            r.fight_id,\n            TRIM(CAST(r.attacker_user_id AS TEXT)) AS attacker_user_id,\n            TRIM(CAST(r.attacker_token_id AS TEXT)) AS attacker_token_id,\n            TRIM(CAST(r.defender_user_id AS TEXT)) AS defender_user_id,\n            TRIM(CAST(r.defender_token_id AS TEXT)) AS defender_token_id,\n            TRIM(CAST(r.winner_user_id AS TEXT)) AS winner_user_id,\n            TRIM(CAST(r.winner_token_id AS TEXT)) AS winner_token_id,\n            json_extract(r.metadata_json, '$.attacker_score') AS attacker_score,\n            json_extract(r.metadata_json, '$.defender_score') AS defender_score,\n            json_extract(r.metadata_json, '$.attacker_combat.specialTriggered') AS attacker_special,\n            json_extract(r.metadata_json, '$.defender_combat.specialTriggered') AS defender_special,\n            json_extract(r.metadata_json, '$.attacker_combat.spellTriggered') AS attacker_spell,\n            json_extract(r.metadata_json, '$.defender_combat.spellTriggered') AS defender_spell,\n            json_extract(r.metadata_json, '$.attacker_combat.dodgeTriggered') AS attacker_dodge,\n            json_extract(r.metadata_json, '$.defender_combat.dodgeTriggered') AS defender_dodge,\n            CASE\n                WHEN NULLIF(TRIM(json_extract(f.metadata_json, '$.battle_mode')), '') IS NULL\n                THEN 'champion'\n                ELSE json_extract(f.metadata_json, '$.battle_mode')\n            END AS battle_mode\n        FROM tbl_mousefight_rounds r\n        INNER JOIN tbl_mousefights f ON f.fight_id = r.fight_id\n        WHERE f.status = 'finished'\n          AND datetime(f.created_at) >= datetime(:season_start)\n          {$seasonEndSql}\n        ORDER BY r.id\n    ");
    $roundStatement->bindValue(':season_start', $seasonStart, PDO::PARAM_STR);
    if ($seasonEnd !== '') {
        $roundStatement->bindValue(':season_end', $seasonEnd, PDO::PARAM_STR);
    }
    $roundStatement->execute();
    $roundRows = $roundStatement->fetchAll() ?: [];

    $triggerCounts = [
        'special' => 0,
        'spell' => 0,
        'dodge' => 0,
        'rounds_with_combat_details' => 0,
        'rounds_without_combat_details' => 0,
    ];
    $highestWinningScore = null;
    $largestScoreMargin = null;

    foreach ($roundRows as $round) {
        $battleMode = trim((string)$round['battle_mode']);
        if (!isset($modeSummary[$battleMode])) {
            $battleMode = 'champion';
        }
        $modeSummary[$battleMode]['rounds']++;

        $sides = [
            [
                'user_id' => trim((string)$round['attacker_user_id']),
                'token_id' => trim((string)$round['attacker_token_id']),
                'score' => is_numeric($round['attacker_score']) ? (int)$round['attacker_score'] : null,
            ],
            [
                'user_id' => trim((string)$round['defender_user_id']),
                'token_id' => trim((string)$round['defender_token_id']),
                'score' => is_numeric($round['defender_score']) ? (int)$round['defender_score'] : null,
            ],
        ];
        $winnerUserId = trim((string)$round['winner_user_id']);
        $winnerTokenId = trim((string)$round['winner_token_id']);

        foreach ($sides as $side) {
            $userId = $side['user_id'];
            $tokenId = $side['token_id'];
            if ($userId !== '' && isset($players[$userId])) {
                $players[$userId]['rounds_played']++;
                $players[$userId]['mode_stats'][$battleMode]['rounds_played']++;
                if ($userId === $winnerUserId && ($tokenId === '' || $winnerTokenId === '' || $tokenId === $winnerTokenId)) {
                    $players[$userId]['rounds_won']++;
                    $players[$userId]['mode_stats'][$battleMode]['rounds_won']++;
                }
            }
            if ($userId !== '' && $tokenId !== '') {
                $mouseKey = $userId . '|' . $tokenId;
                if (isset($mice[$mouseKey])) {
                    $mice[$mouseKey]['rounds_played']++;
                    if ($userId === $winnerUserId && ($winnerTokenId === '' || $tokenId === $winnerTokenId)) {
                        $mice[$mouseKey]['rounds_won']++;
                    }
                }
            }
        }

        $hasCombatDetails = $round['attacker_special'] !== null
            || $round['defender_special'] !== null
            || $round['attacker_spell'] !== null
            || $round['defender_spell'] !== null
            || $round['attacker_dodge'] !== null
            || $round['defender_dodge'] !== null;
        if ($hasCombatDetails) {
            $triggerCounts['rounds_with_combat_details']++;
        } else {
            $triggerCounts['rounds_without_combat_details']++;
        }
        $triggerCounts['special'] += ((int)$round['attacker_special'] === 1 ? 1 : 0)
            + ((int)$round['defender_special'] === 1 ? 1 : 0);
        $triggerCounts['spell'] += ((int)$round['attacker_spell'] === 1 ? 1 : 0)
            + ((int)$round['defender_spell'] === 1 ? 1 : 0);
        $triggerCounts['dodge'] += ((int)$round['attacker_dodge'] === 1 ? 1 : 0)
            + ((int)$round['defender_dodge'] === 1 ? 1 : 0);

        if (is_numeric($round['attacker_score']) && is_numeric($round['defender_score'])) {
            $attackerScore = (int)$round['attacker_score'];
            $defenderScore = (int)$round['defender_score'];
            $winningScore = max($attackerScore, $defenderScore);
            $margin = abs($attackerScore - $defenderScore);
            $highestWinningScore = $highestWinningScore === null
                ? $winningScore
                : max($highestWinningScore, $winningScore);
            $largestScoreMargin = $largestScoreMargin === null
                ? $margin
                : max($largestScoreMargin, $margin);
        }
    }

    $playerRows = [];
    foreach ($players as $userId => $player) {
        $fights = count($player['fight_ids']);
        $wins = (int)$player['wins'];
        $rounds = (int)$player['rounds_played'];
        $roundWins = (int)$player['rounds_won'];

        foreach ($player['mode_stats'] as &$modeStats) {
            $modeStats['win_rate'] = mousefight_leaderboards_rate(
                (int)$modeStats['wins'],
                (int)$modeStats['fights']
            );
            $modeStats['round_win_rate'] = mousefight_leaderboards_rate(
                (int)$modeStats['rounds_won'],
                (int)$modeStats['rounds_played']
            );
        }
        unset($modeStats);

        $mostUsedMouse = null;
        $ownedMice = [];
        foreach ($mice as $mouse) {
            if ($mouse['user_id'] !== $userId) {
                continue;
            }
            $mouse['fights_used'] = count($mouse['fight_ids']);
            unset($mouse['fight_ids']);
            $ownedMice[] = $mouse;
        }
        usort($ownedMice, static function (array $left, array $right): int {
            $fights = ((int)$right['fights_used']) <=> ((int)$left['fights_used']);
            if ($fights !== 0) {
                return $fights;
            }
            $wins = ((int)$right['wins']) <=> ((int)$left['wins']);
            if ($wins !== 0) {
                return $wins;
            }
            return strcmp((string)$left['token_id'], (string)$right['token_id']);
        });
        if (isset($ownedMice[0])) {
            $mostUsedMouse = mousefight_leaderboards_mouse($ownedMice[0]);
        }

        /**
         * NARRRFS_MOUSEFIGHT_LEADERBOARD_DISCORD_ID_STRING_V1
         *
         * DEVS FOR DECADES:
         * $players is keyed by Discord snowflake. PHP converts integer-shaped
         * array keys to integers, so the foreach key must never become the
         * public JSON identity. JavaScript cannot safely represent Discord
         * snowflakes as Number values.
         *
         * Preserve the exact participant string stored before array-key
         * conversion. This is presentation/read-contract only.
         */
        $playerRows[] = [
            'user_id' => (string)($player['user_id'] ?? $userId),
            'username' => $player['username'],
            'historical_username' => $player['historical_username'],
            'avatar_url' => $player['avatar_url'],
            'avatar_source' => $player['avatar_source'],
            'historical_avatar_url' => $player['historical_avatar_url'],
            'total_fights' => $fights,
            'wins' => $wins,
            'losses' => max(0, $fights - $wins),
            'win_rate' => mousefight_leaderboards_rate($wins, $fights),
            'pvp_fights' => (int)$player['pvp_fights'],
            'pvp_wins' => (int)$player['pvp_wins'],
            'pvp_losses' => max(0, (int)$player['pvp_fights'] - (int)$player['pvp_wins']),
            'pvp_win_rate' => mousefight_leaderboards_rate((int)$player['pvp_wins'], (int)$player['pvp_fights']),
            'event_fights' => (int)$player['event_fights'],
            'event_championships' => (int)$player['event_championships'],
            'rounds_played' => $rounds,
            'rounds_won' => $roundWins,
            'rounds_lost' => max(0, $rounds - $roundWins),
            'round_win_rate' => mousefight_leaderboards_rate($roundWins, $rounds),
            'distinct_mice_used' => count($player['distinct_mice']),
            'most_used_mouse' => $mostUsedMouse,
            'battle_modes' => $player['mode_stats'],
            'first_fight_at' => $player['first_fight_at'],
            'last_fight_at' => $player['last_fight_at'],
        ];
    }

    $mouseRows = [];
    foreach ($mice as $mouse) {
        $mouse['fights_used'] = count($mouse['fight_ids']);
        unset($mouse['fight_ids']);
        $publicMouse = mousefight_leaderboards_mouse($mouse);
        $publicMouse['owner'] = $mouse['owner'];
        $mouseRows[] = $publicMouse;
    }

    $descNumber = static fn(string $field): callable => static function (array $a, array $b) use ($field): int {
        $comparison = ((float)($b[$field] ?? 0)) <=> ((float)($a[$field] ?? 0));
        return $comparison !== 0
            ? $comparison
            : strcmp((string)($a['user_id'] ?? $a['token_id'] ?? ''), (string)($b['user_id'] ?? $b['token_id'] ?? ''));
    };

    $mostWins = mousefight_leaderboards_rank($playerRows, static function (array $a, array $b): int {
        return (($b['wins'] <=> $a['wins']) ?: ($b['win_rate'] <=> $a['win_rate']) ?: ($b['total_fights'] <=> $a['total_fights']) ?: strcmp($a['user_id'], $b['user_id']));
    }, $limit);
    $bestWinRate = mousefight_leaderboards_rank(
        array_values(array_filter($playerRows, static fn(array $row): bool => $row['total_fights'] >= $winRateMinimum)),
        static function (array $a, array $b): int {
            return (($b['win_rate'] <=> $a['win_rate']) ?: ($b['wins'] <=> $a['wins']) ?: ($b['total_fights'] <=> $a['total_fights']) ?: strcmp($a['user_id'], $b['user_id']));
        },
        $limit
    );
    $mostActive = mousefight_leaderboards_rank($playerRows, static function (array $a, array $b): int {
        return (($b['total_fights'] <=> $a['total_fights']) ?: ($b['wins'] <=> $a['wins']) ?: ($b['rounds_played'] <=> $a['rounds_played']) ?: strcmp($a['user_id'], $b['user_id']));
    }, $limit);
    $mostPvpWins = mousefight_leaderboards_rank($playerRows, static function (array $a, array $b): int {
        return (($b['pvp_wins'] <=> $a['pvp_wins']) ?: ($b['pvp_fights'] <=> $a['pvp_fights']) ?: ($b['pvp_win_rate'] <=> $a['pvp_win_rate']) ?: strcmp($a['user_id'], $b['user_id']));
    }, $limit);
    $eventChampions = mousefight_leaderboards_rank($playerRows, static function (array $a, array $b): int {
        return (($b['event_championships'] <=> $a['event_championships']) ?: ($b['event_fights'] <=> $a['event_fights']) ?: ($b['wins'] <=> $a['wins']) ?: strcmp($a['user_id'], $b['user_id']));
    }, $limit);
    $mostRoundWins = mousefight_leaderboards_rank($playerRows, static function (array $a, array $b): int {
        return (($b['rounds_won'] <=> $a['rounds_won']) ?: ($b['round_win_rate'] <=> $a['round_win_rate']) ?: ($b['rounds_played'] <=> $a['rounds_played']) ?: strcmp($a['user_id'], $b['user_id']));
    }, $limit);
    $mostVersatile = mousefight_leaderboards_rank($playerRows, static function (array $a, array $b): int {
        return (($b['distinct_mice_used'] <=> $a['distinct_mice_used']) ?: ($b['total_fights'] <=> $a['total_fights']) ?: ($b['wins'] <=> $a['wins']) ?: strcmp($a['user_id'], $b['user_id']));
    }, $limit);
    $veterans = mousefight_leaderboards_rank($playerRows, static function (array $a, array $b): int {
        $left = (string)($a['first_fight_at'] ?? '9999');
        $right = (string)($b['first_fight_at'] ?? '9999');
        return ($left <=> $right) ?: ($b['total_fights'] <=> $a['total_fights']) ?: strcmp($a['user_id'], $b['user_id']);
    }, $limit);

    $modeSpecialists = [];
    foreach (MOUSEFIGHT_LEADERBOARDS_BATTLE_MODES as $mode) {
        $eligible = [];
        foreach ($playerRows as $player) {
            $modeStats = $player['battle_modes'][$mode];
            if ((int)$modeStats['fights'] < $modeMinimum) {
                continue;
            }
            $eligible[] = array_merge($player, [
                'mode' => $mode,
                'mode_fights' => (int)$modeStats['fights'],
                'mode_wins' => (int)$modeStats['wins'],
                'mode_losses' => (int)$modeStats['losses'],
                'mode_win_rate' => (float)$modeStats['win_rate'],
                'mode_rounds_played' => (int)$modeStats['rounds_played'],
                'mode_rounds_won' => (int)$modeStats['rounds_won'],
                'mode_round_win_rate' => (float)$modeStats['round_win_rate'],
            ]);
        }
        $modeSpecialists[$mode] = mousefight_leaderboards_rank($eligible, static function (array $a, array $b): int {
            return (($b['mode_wins'] <=> $a['mode_wins']) ?: ($b['mode_win_rate'] <=> $a['mode_win_rate']) ?: ($b['mode_fights'] <=> $a['mode_fights']) ?: strcmp($a['user_id'], $b['user_id']));
        }, $limit);
    }

    $mostUsedMice = mousefight_leaderboards_rank($mouseRows, static function (array $a, array $b): int {
        return (($b['fights_used'] <=> $a['fights_used']) ?: ($b['wins'] <=> $a['wins']) ?: strcmp((string)$a['token_id'], (string)$b['token_id']));
    }, $limit);
    $mostSuccessfulMice = mousefight_leaderboards_rank(
        array_values(array_filter($mouseRows, static fn(array $row): bool => $row['fights_used'] >= $mouseMinimum)),
        static function (array $a, array $b): int {
            return (($b['wins'] <=> $a['wins']) ?: ($b['win_rate'] <=> $a['win_rate']) ?: ($b['fights_used'] <=> $a['fights_used']) ?: strcmp((string)$a['token_id'], (string)$b['token_id']));
        },
        $limit
    );
    $mostMouseRoundWins = mousefight_leaderboards_rank($mouseRows, static function (array $a, array $b): int {
        return (($b['rounds_won'] <=> $a['rounds_won']) ?: ($b['round_win_rate'] <=> $a['round_win_rate']) ?: ($b['rounds_played'] <=> $a['rounds_played']) ?: strcmp((string)$a['token_id'], (string)$b['token_id']));
    }, $limit);

    $pvpFights = 0;
    $eventFights = 0;
    $totalParticipations = 0;
    $largestEventParticipants = 0;
    foreach ($fightSummary as $fight) {
        $participants = count($fight['participants']);
        $totalParticipations += $participants;
        if ($fight['mode'] === MOUSEFIGHT_LEADERBOARDS_MODE_PVP) {
            $pvpFights++;
        }
        if ($fight['mode'] === MOUSEFIGHT_LEADERBOARDS_MODE_EVENT) {
            $eventFights++;
            $largestEventParticipants = max($largestEventParticipants, $participants);
        }
    }

    $battleModes = mousefight_leaderboards_battle_mode_contract();
    foreach ($battleModes as $mode => &$contract) {
        $contract['finished_fights'] = count($modeSummary[$mode]['fights']);
        $contract['rounds'] = (int)$modeSummary[$mode]['rounds'];
        $contract['player_participations'] = (int)$modeSummary[$mode]['player_participations'];
        $contract['top_specialist'] = $modeSpecialists[$mode][0] ?? null;
    }
    unset($contract);

    $finishedFightCount = count($fightSummary);
    $roundCount = count($roundRows);
    $summary = [
        'finished_fights' => $finishedFightCount,
        'pvp_fights' => $pvpFights,
        'event_fights' => $eventFights,
        'unique_players' => count($playerRows),
        'player_participations' => $totalParticipations,
        'rounds_fought' => $roundCount,
        'battle_modes_used' => count(array_filter($modeSummary, static fn(array $mode): bool => count($mode['fights']) > 0)),
        'average_participants_per_fight' => $finishedFightCount > 0 ? round($totalParticipations / $finishedFightCount, 2) : 0.0,
        'average_participants_per_event' => $eventFights > 0 ? round(($totalParticipations - ($pvpFights * 2)) / $eventFights, 2) : 0.0,
        'average_rounds_per_fight' => $finishedFightCount > 0 ? round($roundCount / $finishedFightCount, 2) : 0.0,
        'largest_completed_event_participants' => $largestEventParticipants,
        'highest_recorded_winning_score' => $highestWinningScore,
        'largest_recorded_score_margin' => $largestScoreMargin,
        'combat_event_triggers' => $triggerCounts,
    ];

    mousefight_leaderboards_json([
        'success' => true,
        'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'contract' => [
            'read_only' => true,
            'finished_fights_only' => true,
            'economy_notice' => 'Configured prizes and wagers are not proof of final DSPOINC or SPL delivery.',
            'combat_notice' => 'Statistics use stored fight results and never recalculate combat.',
            'legacy_notice' => 'Legacy fights without an explicit battle mode are displayed as Champion Mode.',
        ],
        'season' => [
            'season_id' => (int)$season['season_id'],
            'season_name' => (string)$season['season_name'],
            'start_date' => mousefight_leaderboards_iso_timestamp($season['start_date']),
            'end_date' => mousefight_leaderboards_iso_timestamp($season['end_date'] ?? null),
            'is_active' => (bool)$season['is_active'],
        ],
        'filters' => [
            'limit' => $limit,
            'win_rate_min_fights' => $winRateMinimum,
            'mouse_min_fights' => $mouseMinimum,
            'mode_min_fights' => $modeMinimum,
        ],
        'criteria' => [
            'most_wins' => 'Wins, then win rate, then total fights.',
            'best_win_rate' => "Minimum {$winRateMinimum} finished fights; win rate, then wins, then fights.",
            'most_active' => 'Total fights, then wins, then rounds played.',
            'pvp' => 'PVP wins, then PVP fights, then PVP win rate.',
            'events' => 'Event championships, then event participations, then total wins.',
            'rounds' => 'Round wins, then round win rate, then rounds played.',
            'mode_specialists' => "Minimum {$modeMinimum} fights in the selected mode; mode wins, then mode win rate.",
            'most_used_mice' => 'Historical fight uses, then fight wins.',
            'successful_mice' => "Minimum {$mouseMinimum} fight uses; mouse wins, then mouse win rate.",
        ],
        'summary' => $summary,
        'battle_modes' => $battleModes,
        'combat_matrix' => [
            'engine_name' => 'MouseFight Nine-Ability Matrix',
            'fight_only' => true,
            'abilities' => mousefight_leaderboards_ability_matrix(),
            'recorded_trigger_counts' => $triggerCounts,
        ],
        'featured' => [
            'most_wins' => $mostWins[0] ?? null,
            'best_qualified_win_rate' => $bestWinRate[0] ?? null,
            'most_pvp_wins' => $mostPvpWins[0] ?? null,
            'most_event_championships' => $eventChampions[0] ?? null,
            'most_round_wins' => $mostRoundWins[0] ?? null,
            'most_used_mouse' => $mostUsedMice[0] ?? null,
            'most_successful_mouse' => $mostSuccessfulMice[0] ?? null,
        ],
        'leaderboards' => [
            'most_wins' => $mostWins,
            'best_win_rate' => $bestWinRate,
            'most_active' => $mostActive,
            'most_pvp_wins' => $mostPvpWins,
            'event_champions' => $eventChampions,
            'most_round_wins' => $mostRoundWins,
            'most_versatile_players' => $mostVersatile,
            'veteran_fighters' => $veterans,
            'battle_mode_specialists' => $modeSpecialists,
            'most_used_mice' => $mostUsedMice,
            'most_successful_mice' => $mostSuccessfulMice,
            'most_mouse_round_wins' => $mostMouseRoundWins,
        ],
    ]);
} catch (Throwable $error) {
    error_log('MouseFight leaderboards API error: ' . $error->getMessage());
    mousefight_leaderboards_json([
        'success' => false,
        'error' => 'Unable to load MouseFight leaderboards.',
    ], 500);
}
