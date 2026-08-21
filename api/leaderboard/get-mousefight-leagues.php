<?php
/**
 * Public MouseFight Genesis Leagues API.
 *
 * Plain language for DEVS FOR DECADES:
 * This read-only endpoint classifies every currently named Genesis mouse into
 * one permanent-power league for the public MouseFight league directory.
 *
 * League identity:
 * - Mouse identity is token_id + collection.
 * - The current Discord owner controls the mouse but is not the league identity.
 *
 * Genesis League Power:
 * - Genesis Trait levels
 * - Genesis Ability levels multiplied by 2
 * - Named Mouse bonus +25
 * - Fitness unlock bonus +50 when the highest Genesis Trait is level 5+
 *
 * Explicitly excluded:
 * - Genetic Items / owner support inventory
 * - MouseFight temporary battle-mode power
 * - MouseFight results
 * - DSPOINC / SPOINC
 * - rewards
 *
 * A named Genesis mouse belongs to a league even when Fitness is not unlocked.
 * Current MouseFight readiness is returned separately and never changes the
 * mouse's league membership.
 *
 * This endpoint must never write rows, mutate Genesis/Lab state, change
 * ownership, settle MouseFight economy, or touch Fight Recovery.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

const MOUSEFIGHT_LEAGUE_FITNESS_UNLOCK_LEVEL = 5;
const MOUSEFIGHT_LEAGUE_NAMED_MOUSE_BONUS = 25;
const MOUSEFIGHT_LEAGUE_FITNESS_UNLOCK_BONUS = 50;
const MOUSEFIGHT_LEAGUE_ABILITY_POWER_MULTIPLIER = 2;

const MOUSEFIGHT_LEAGUE_TIERS = [
    1 => [
        'key' => 'crumb',
        'label' => 'Crumb',
        'symbol' => '▫️',
        'minimum_power' => 0,
        'maximum_power' => 59,
    ],
    2 => [
        'key' => 'cheese',
        'label' => 'Cheese',
        'symbol' => '🧀',
        'minimum_power' => 60,
        'maximum_power' => 130,
    ],
    3 => [
        'key' => 'bronze',
        'label' => 'Bronze',
        'symbol' => '🥉',
        'minimum_power' => 131,
        'maximum_power' => 171,
    ],
    4 => [
        'key' => 'silver',
        'label' => 'Silver',
        'symbol' => '🥈',
        'minimum_power' => 172,
        'maximum_power' => 222,
    ],
    5 => [
        'key' => 'golden',
        'label' => 'Golden',
        'symbol' => '🥇',
        'minimum_power' => 223,
        'maximum_power' => 247,
    ],
    6 => [
        'key' => 'diamond',
        'label' => 'Diamond',
        'symbol' => '💎',
        'minimum_power' => 248,
        'maximum_power' => 294,
    ],
    7 => [
        'key' => 'genesis_master',
        'label' => 'Genesis Master',
        'symbol' => '🧬',
        'minimum_power' => 295,
        'maximum_power' => 350,
    ],
    8 => [
        'key' => 'mouseverse_champion',
        'label' => 'Mouseverse Champion',
        'symbol' => '👑',
        'minimum_power' => 351,
        'maximum_power' => null,
    ],
];

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

/**
 * Return one JSON response and stop execution.
 */
function mousefight_leagues_json(
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
 * Return a safe integer from database values.
 */
function mousefight_leagues_int($value): int
{
    return is_numeric($value) ? (int)$value : 0;
}

/**
 * Build the stable token + collection identity key.
 */
function mousefight_leagues_token_key(
    string $tokenId,
    string $collection
): string {
    return $tokenId . '|' . $collection;
}

/**
 * Return the fixed V1 tier for one permanent Genesis League Power value.
 */
function mousefight_leagues_resolve_tier(int $leaguePower): array
{
    foreach (MOUSEFIGHT_LEAGUE_TIERS as $tierRank => $tier) {
        $minimumPower = (int)$tier['minimum_power'];
        $maximumPower = $tier['maximum_power'];

        if ($leaguePower < $minimumPower) {
            continue;
        }

        if ($maximumPower !== null && $leaguePower > (int)$maximumPower) {
            continue;
        }

        return [
            'rank' => $tierRank,
            'key' => $tier['key'],
            'label' => $tier['label'],
            'symbol' => $tier['symbol'],
            'minimum_power' => $minimumPower,
            'maximum_power' => $maximumPower,
        ];
    }

    return [
        'rank' => 8,
        'key' => MOUSEFIGHT_LEAGUE_TIERS[8]['key'],
        'label' => MOUSEFIGHT_LEAGUE_TIERS[8]['label'],
        'symbol' => MOUSEFIGHT_LEAGUE_TIERS[8]['symbol'],
        'minimum_power' => MOUSEFIGHT_LEAGUE_TIERS[8]['minimum_power'],
        'maximum_power' => null,
    ];
}

/**
 * Return promotion information for one current tier and power.
 */
function mousefight_leagues_next_tier(
    int $tierRank,
    int $leaguePower
): ?array {
    $nextRank = $tierRank + 1;

    if (!isset(MOUSEFIGHT_LEAGUE_TIERS[$nextRank])) {
        return null;
    }

    $nextTier = MOUSEFIGHT_LEAGUE_TIERS[$nextRank];
    $requiredPower = (int)$nextTier['minimum_power'];

    return [
        'rank' => $nextRank,
        'key' => $nextTier['key'],
        'label' => $nextTier['label'],
        'symbol' => $nextTier['symbol'],
        'minimum_power' => $requiredPower,
        'power_needed' => max(0, $requiredPower - $leaguePower),
    ];
}

/**
 * Return the public V1 tier contract.
 */
function mousefight_leagues_tier_contract(): array
{
    $tiers = [];

    foreach (MOUSEFIGHT_LEAGUE_TIERS as $rank => $tier) {
        $tiers[] = [
            'rank' => $rank,
            'key' => $tier['key'],
            'label' => $tier['label'],
            'symbol' => $tier['symbol'],
            'minimum_power' => $tier['minimum_power'],
            'maximum_power' => $tier['maximum_power'],
        ];
    }

    return $tiers;
}

try {
    if (!file_exists($dbPath)) {
        throw new RuntimeException('Database file not found.');
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /**
     * Load every named Genesis mouse and resolve one current/best ownership row.
     *
     * This intentionally follows the already-verified MouseFight / Strongest
     * Genesis ownership ordering:
     * verified -> verified_at -> acquired_at -> ownership_id.
     *
     * Ownership is LEFT JOINed because league identity belongs to the named
     * Genesis NFT. Missing/unverified current ownership is reported separately
     * and must not silently erase the named mouse from the league directory.
     */
    $namedMiceStmt = $db->prepare("
        WITH current_ownership AS (
            SELECT *
            FROM (
                SELECT
                    o.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY o.token_id, o.collection
                        ORDER BY
                            COALESCE(o.is_verified, 0) DESC,
                            COALESCE(o.verified_at, '') DESC,
                            COALESCE(o.acquired_at, '') DESC,
                            o.ownership_id DESC
                    ) AS ownership_rank
                FROM tbl_nft_ownership o
                WHERE COALESCE(o.collection, 'genesis') = 'genesis'
            )
            WHERE ownership_rank = 1
        )
        SELECT
            cn.custom_name,
            cn.user_id AS name_user_id,
            cn.token_id,
            cn.collection,
            co.user_id AS owner_user_id,
            COALESCE(co.username, u.username, cn.user_id) AS owner_username,
            co.nft_name,
            co.image_url,
            COALESCE(co.is_verified, 0) AS is_verified
        FROM tbl_nft_custom_names cn
        LEFT JOIN current_ownership co
            ON co.token_id = cn.token_id
           AND co.collection = cn.collection
        LEFT JOIN tbl_users u
            ON u.discord_id = COALESCE(co.user_id, cn.user_id)
        WHERE cn.collection = 'genesis'
          AND TRIM(COALESCE(cn.custom_name, '')) <> ''
        ORDER BY cn.updated_at DESC, cn.custom_name_id DESC
    ");

    $namedMiceStmt->execute();
    $namedMice = $namedMiceStmt->fetchAll(PDO::FETCH_ASSOC);

    $traitStmt = $db->prepare("
        SELECT
            token_id,
            collection,
            current_level
        FROM tbl_nft_trait_upgrades
        WHERE COALESCE(collection, 'genesis') = 'genesis'
    ");

    $traitStmt->execute();
    $traitRows = $traitStmt->fetchAll(PDO::FETCH_ASSOC);

    $abilityStmt = $db->prepare("
        SELECT
            token_id,
            collection,
            ability_key,
            current_level
        FROM tbl_nft_ability_upgrades
        WHERE COALESCE(collection, 'genesis') = 'genesis'
    ");

    $abilityStmt->execute();
    $abilityRows = $abilityStmt->fetchAll(PDO::FETCH_ASSOC);

    $traitsByToken = [];

    foreach ($traitRows as $traitRow) {
        $tokenId = trim((string)($traitRow['token_id'] ?? ''));
        $collection = trim((string)($traitRow['collection'] ?? 'genesis'));

        if ($tokenId === '') {
            continue;
        }

        $key = mousefight_leagues_token_key($tokenId, $collection);

        if (!isset($traitsByToken[$key])) {
            $traitsByToken[$key] = [];
        }

        $traitsByToken[$key][] = $traitRow;
    }

    $abilitiesByToken = [];

    foreach ($abilityRows as $abilityRow) {
        $tokenId = trim((string)($abilityRow['token_id'] ?? ''));
        $collection = trim((string)($abilityRow['collection'] ?? 'genesis'));

        if ($tokenId === '') {
            continue;
        }

        $key = mousefight_leagues_token_key($tokenId, $collection);

        if (!isset($abilitiesByToken[$key])) {
            $abilitiesByToken[$key] = [];
        }

        $abilitiesByToken[$key][] = $abilityRow;
    }

    $mice = [];
    $tierSummaries = [];

    foreach (MOUSEFIGHT_LEAGUE_TIERS as $rank => $tierDefinition) {
        $tierSummaries[$rank] = [
            'rank' => $rank,
            'key' => $tierDefinition['key'],
            'label' => $tierDefinition['label'],
            'symbol' => $tierDefinition['symbol'],
            'minimum_power' => $tierDefinition['minimum_power'],
            'maximum_power' => $tierDefinition['maximum_power'],
            'named_mice' => 0,
            'verified_current_owner_mice' => 0,
            'fitness_unlocked_mice' => 0,
            'default_mousefight_ready_mice' => 0,
            'distinct_owner_ids' => [],
        ];
    }

    foreach ($namedMice as $mouse) {
        $tokenId = trim((string)($mouse['token_id'] ?? ''));
        $collection = trim((string)($mouse['collection'] ?? 'genesis'));

        if ($tokenId === '') {
            continue;
        }

        $tokenKey = mousefight_leagues_token_key(
            $tokenId,
            $collection
        );

        $traitPower = 0;
        $highestTraitLevel = 0;

        foreach ($traitsByToken[$tokenKey] ?? [] as $trait) {
            $level = mousefight_leagues_int(
                $trait['current_level'] ?? 1
            );

            $traitPower += $level;
            $highestTraitLevel = max(
                $highestTraitLevel,
                $level
            );
        }

        $abilityRawPower = 0;
        $abilityKeys = [];

        foreach ($abilitiesByToken[$tokenKey] ?? [] as $ability) {
            $level = mousefight_leagues_int(
                $ability['current_level'] ?? 0
            );

            $abilityRawPower += $level;

            $abilityKey = trim(
                (string)($ability['ability_key'] ?? '')
            );

            if ($abilityKey !== '') {
                $abilityKeys[$abilityKey] = true;
            }
        }

        $abilityPower =
            $abilityRawPower
            * MOUSEFIGHT_LEAGUE_ABILITY_POWER_MULTIPLIER;

        $fitnessUnlocked =
            $highestTraitLevel
            >= MOUSEFIGHT_LEAGUE_FITNESS_UNLOCK_LEVEL;

        $fitnessBonus = $fitnessUnlocked
            ? MOUSEFIGHT_LEAGUE_FITNESS_UNLOCK_BONUS
            : 0;

        $leaguePower =
            $traitPower
            + $abilityPower
            + MOUSEFIGHT_LEAGUE_NAMED_MOUSE_BONUS
            + $fitnessBonus;

        $tier = mousefight_leagues_resolve_tier(
            $leaguePower
        );

        $nextTier = mousefight_leagues_next_tier(
            (int)$tier['rank'],
            $leaguePower
        );

        $ownerUserId = trim(
            (string)($mouse['owner_user_id'] ?? '')
        );

        $verifiedCurrentOwner =
            mousefight_leagues_int(
                $mouse['is_verified'] ?? 0
            ) === 1;

        /**
         * This mirrors the current default MouseFight entry concept only:
         * verified current owner + named mouse + highest Trait level 5+.
         *
         * Ability-row completeness is diagnostic only because current
         * MouseFight production code does not use it as an explicit gate.
         */
        $defaultMouseFightReady =
            $verifiedCurrentOwner
            && $highestTraitLevel
                >= MOUSEFIGHT_LEAGUE_FITNESS_UNLOCK_LEVEL;

        $mouseRow = [
            'token_id' => $tokenId,
            'collection' => $collection,
            'custom_name' => (string)($mouse['custom_name'] ?? ''),
            'metadata_name' => (string)($mouse['nft_name'] ?? ''),
            'image_url' => (string)($mouse['image_url'] ?? ''),
            'owner' => [
                'user_id' => $ownerUserId !== ''
                    ? $ownerUserId
                    : null,
                'username' => trim(
                    (string)($mouse['owner_username'] ?? '')
                ) ?: null,
                'verified' => $verifiedCurrentOwner,
            ],
            'power' => [
                'league_power' => $leaguePower,
                'trait_power' => $traitPower,
                'ability_power' => $abilityPower,
                'ability_raw_power' => $abilityRawPower,
                'named_mouse_bonus' =>
                    MOUSEFIGHT_LEAGUE_NAMED_MOUSE_BONUS,
                'fitness_bonus' => $fitnessBonus,
                'highest_trait_level' => $highestTraitLevel,
                'genetic_support_power' => 0,
            ],
            'league' => [
                'rank' => $tier['rank'],
                'key' => $tier['key'],
                'label' => $tier['label'],
                'symbol' => $tier['symbol'],
                'minimum_power' => $tier['minimum_power'],
                'maximum_power' => $tier['maximum_power'],
                'next_tier' => $nextTier,
            ],
            'eligibility' => [
                'league_member' => true,
                'fitness_unlocked' => $fitnessUnlocked,
                'verified_current_owner' =>
                    $verifiedCurrentOwner,
                'default_mousefight_ready' =>
                    $defaultMouseFightReady,
                'canonical_ability_keys_present' =>
                    count($abilityKeys),
            ],
        ];

        $mice[] = $mouseRow;

        $tierRank = (int)$tier['rank'];

        $tierSummaries[$tierRank]['named_mice']++;

        if ($verifiedCurrentOwner) {
            $tierSummaries[$tierRank]
                ['verified_current_owner_mice']++;
        }

        if ($fitnessUnlocked) {
            $tierSummaries[$tierRank]
                ['fitness_unlocked_mice']++;
        }

        if ($defaultMouseFightReady) {
            $tierSummaries[$tierRank]
                ['default_mousefight_ready_mice']++;
        }

        if ($ownerUserId !== '') {
            $tierSummaries[$tierRank]
                ['distinct_owner_ids'][$ownerUserId] = true;
        }
    }

    usort(
        $mice,
        static function (array $a, array $b): int {
            $aRank = (int)($a['league']['rank'] ?? 0);
            $bRank = (int)($b['league']['rank'] ?? 0);

            if ($aRank !== $bRank) {
                return $bRank <=> $aRank;
            }

            $aPower = (int)($a['power']['league_power'] ?? 0);
            $bPower = (int)($b['power']['league_power'] ?? 0);

            if ($aPower !== $bPower) {
                return $bPower <=> $aPower;
            }

            return strcmp(
                (string)($a['custom_name'] ?? ''),
                (string)($b['custom_name'] ?? '')
            );
        }
    );

    $publicTierSummaries = [];

    foreach ($tierSummaries as $summary) {
        $ownerIds = $summary['distinct_owner_ids'];
        unset($summary['distinct_owner_ids']);

        $summary['distinct_owners'] = count($ownerIds);
        $publicTierSummaries[] = $summary;
    }

    mousefight_leagues_json([
        'success' => true,
        'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'contract' => [
            'version' => 'mousefight_genesis_leagues_v1',
            'read_only' => true,
            'identity' => 'token_id_plus_collection',
            'membership' =>
                'every named Genesis mouse receives a current league tier',
            'tier_movement' =>
                'current permanent power only; fight results do not promote or relegate',
            'genetic_items_included' => false,
            'temporary_battle_mode_power_included' => false,
            'fight_results_included_in_tier' => false,
            'league_power_formula' =>
                'trait_power + ability_power + named_mouse_bonus + fitness_bonus',
            'ability_power_formula' =>
                'sum of Genesis ability current levels multiplied by 2',
            'fitness_unlock_level' =>
                MOUSEFIGHT_LEAGUE_FITNESS_UNLOCK_LEVEL,
            'named_mouse_bonus' =>
                MOUSEFIGHT_LEAGUE_NAMED_MOUSE_BONUS,
            'fitness_unlock_bonus' =>
                MOUSEFIGHT_LEAGUE_FITNESS_UNLOCK_BONUS,
        ],
        'tiers' => mousefight_leagues_tier_contract(),
        'summary' => [
            'named_mice' => count($mice),
            'tiers' => $publicTierSummaries,
        ],
        'mice' => $mice,
    ]);
} catch (Throwable $error) {
    error_log(
        'MouseFight Genesis Leagues API error: '
        . $error->getMessage()
    );

    mousefight_leagues_json([
        'success' => false,
        'error' => 'Unable to load MouseFight Genesis leagues.',
    ], 500);
}
