<?php
/**
 * MouseFight API — List eligible Genesis mice for one Discord user.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint is read-only. It returns named Genesis mice owned by a Discord
 * user so the Discord bot can show `/mousefight list` and slash autocomplete.
 *
 * Important architecture:
 * - Genesis Traits are NFT-bound by token_id + collection.
 * - Genesis Abilities are NFT-bound by token_id + collection.
 * - Genetic Items are Discord-user-bound in V1 and act only as owner support.
 * - Genetic Items are not permanently equipped to one Genesis mouse in V1.
 * - Inventory flavor must use only real DB-fetched owner inventory rows.
 * - This endpoint must not write DSPOINC, inventory, traits, abilities, names, or ownership.
 */

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const MOUSEFIGHT_FITNESS_UNLOCK_LEVEL = 5;
const MOUSEFIGHT_NAMED_MOUSE_BONUS = 25;
const MOUSEFIGHT_FITNESS_UNLOCK_BONUS = 50;
const MOUSEFIGHT_ABILITY_POWER_MULTIPLIER = 2;
const MOUSEFIGHT_GENETIC_SUPPORT_CAP = 250;
const MOUSEFIGHT_DEFAULT_LIMIT = 25;
const MOUSEFIGHT_MAX_LIMIT = 100;
const MOUSEFIGHT_DEFAULT_MAX_INVENTORY_ITEMS = 5;
const MOUSEFIGHT_MAX_INVENTORY_ITEMS = 10;

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

/**
 * Return a JSON response and stop execution.
 */
function mousefight_json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return a safe integer from DB or request values.
 */
function mousefight_int($value): int {
    return is_numeric($value) ? (int)$value : 0;
}

/**
 * Return a bounded integer request value.
 */
function mousefight_bounded_int($value, int $default, int $min, int $max): int {
    if (!is_numeric($value)) {
        return $default;
    }

    return max($min, min($max, (int)$value));
}

/**
 * Return a safe boolean from query parameters.
 */
function mousefight_bool($value, bool $default = false): bool {
    if ($value === null || $value === '') {
        return $default;
    }

    $normalized = strtolower(trim((string)$value));
    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

/**
 * Return a safe float rarity multiplier for Genetic Support.
 *
 * Plain language for DEVS:
 * This only scores the owner inventory support around the mouse. It does not
 * equip the item to the NFT and does not mutate inventory.
 */
function mousefight_rarity_multiplier($rarity): float {
    $key = strtolower(trim((string)$rarity));

    $multipliers = [
        'common' => 1.0,
        'uncommon' => 1.25,
        'rare' => 1.5,
        'epic' => 2.0,
        'legendary' => 3.0,
        'mythic' => 5.0,
    ];

    return $multipliers[$key] ?? 1.0;
}

/**
 * Create a short token label for Discord display.
 */
function mousefight_short_token($tokenId): string {
    $token = (string)$tokenId;

    if (strlen($token) <= 12) {
        return $token;
    }

    return substr($token, 0, 4) . '...' . substr($token, -4);
}

/**
 * Group rows by token_id + collection.
 */
function mousefight_group_by_token(array $rows): array {
    $grouped = [];

    foreach ($rows as $row) {
        $key = ($row['token_id'] ?? '') . '|' . ($row['collection'] ?? '');
        if ($key === '|') {
            continue;
        }

        if (!isset($grouped[$key])) {
            $grouped[$key] = [];
        }

        $grouped[$key][] = $row;
    }

    return $grouped;
}

/**
 * Group Genetic Item rows by owner/user id.
 */
function mousefight_group_by_user(array $rows): array {
    $grouped = [];

    foreach ($rows as $row) {
        $userId = (string)($row['user_id'] ?? '');
        if ($userId === '') {
            continue;
        }

        if (!isset($grouped[$userId])) {
            $grouped[$userId] = [];
        }

        $grouped[$userId][] = $row;
    }

    return $grouped;
}

/**
 * Return true when a mouse row matches the optional autocomplete/search input.
 */
function mousefight_matches_search(array $mouse, string $search): bool {
    if ($search === '') {
        return true;
    }

    $haystack = strtolower(implode(' ', [
        $mouse['custom_name'] ?? '',
        $mouse['nft_name'] ?? '',
        $mouse['token_id'] ?? '',
        $mouse['owner_username'] ?? '',
    ]));

    return strpos($haystack, strtolower($search)) !== false;
}

/**
 * Build one MouseFight-ready mouse profile from DB rows.
 *
 * Plain language:
 * This composes existing read-only Lab data into a Discord bot fighter card.
 * It does not decide match winners and does not write any state.
 */
function mousefight_build_mouse_profile(
    array $mouse,
    array $traitsByToken,
    array $abilitiesByToken,
    array $geneticItemsByUser,
    bool $inventoryEnabled,
    int $maxInventoryItems
): array {
    $tokenId = (string)($mouse['token_id'] ?? '');
    $collection = (string)($mouse['collection'] ?? 'genesis');
    $tokenKey = $tokenId . '|' . $collection;

    $traits = $traitsByToken[$tokenKey] ?? [];
    $traitPower = 0;
    $highestTraitLevel = 0;

    foreach ($traits as &$trait) {
        $level = mousefight_int($trait['current_level'] ?? 1);
        $trait['current_level'] = $level;
        $traitPower += $level;
        $highestTraitLevel = max($highestTraitLevel, $level);
    }
    unset($trait);

    $abilities = $abilitiesByToken[$tokenKey] ?? [];
    $abilityRawPower = 0;

    foreach ($abilities as &$ability) {
        $level = mousefight_int($ability['current_level'] ?? 0);
        $ability['current_level'] = $level;
        $abilityRawPower += $level;
    }
    unset($ability);

    $abilityPower = $abilityRawPower * MOUSEFIGHT_ABILITY_POWER_MULTIPLIER;

    $ownerUserId = (string)($mouse['owner_user_id'] ?: $mouse['name_user_id']);
    $allGeneticItems = $geneticItemsByUser[$ownerUserId] ?? [];

    foreach ($allGeneticItems as &$item) {
        $itemLevel = mousefight_int($item['current_level'] ?? 1);
        $rarityMultiplier = mousefight_rarity_multiplier($item['rarity_tier'] ?? 'unknown');

        $item['current_level'] = $itemLevel;
        $item['support_power'] = round($itemLevel * $rarityMultiplier, 2);
        $item['source'] = 'tbl_user_genetic_items';
    }
    unset($item);

    usort($traits, function ($a, $b) {
        return mousefight_int($b['current_level'] ?? 0) <=> mousefight_int($a['current_level'] ?? 0);
    });

    usort($abilities, function ($a, $b) {
        return mousefight_int($b['current_level'] ?? 0) <=> mousefight_int($a['current_level'] ?? 0);
    });

    usort($allGeneticItems, function ($a, $b) {
        return (float)($b['support_power'] ?? 0) <=> (float)($a['support_power'] ?? 0);
    });

    $selectedGeneticItems = $inventoryEnabled
        ? array_slice($allGeneticItems, 0, $maxInventoryItems)
        : [];

    $geneticRawSupport = 0.0;
    foreach ($selectedGeneticItems as $item) {
        $geneticRawSupport += (float)($item['support_power'] ?? 0);
    }

    $geneticSupportPower = min((int)round($geneticRawSupport), MOUSEFIGHT_GENETIC_SUPPORT_CAP);

    $fitnessUnlocked = $highestTraitLevel >= MOUSEFIGHT_FITNESS_UNLOCK_LEVEL;
    $mouseWarriorPower =
        $traitPower +
        $abilityPower +
        $geneticSupportPower +
        MOUSEFIGHT_NAMED_MOUSE_BONUS +
        ($fitnessUnlocked ? MOUSEFIGHT_FITNESS_UNLOCK_BONUS : 0);

    return [
        'token_id' => $tokenId,
        'token_short' => mousefight_short_token($tokenId),
        'collection' => $collection,
        'custom_name' => $mouse['custom_name'],
        'metadata_name' => $mouse['nft_name'] ?: mousefight_short_token($tokenId),
        'image_url' => $mouse['image_url'] ?: '',
        'wallet' => $mouse['wallet'] ?? '',
        'owner_user_id' => $ownerUserId,
        'owner_username' => $mouse['owner_username'] ?: $ownerUserId,
        'is_verified' => mousefight_int($mouse['is_verified'] ?? 0) === 1,
        'power' => [
            'trait_power' => $traitPower,
            'ability_power' => $abilityPower,
            'ability_raw_power' => $abilityRawPower,
            'genetic_support_power' => $geneticSupportPower,
            'genetic_support_raw' => round($geneticRawSupport, 2),
            'named_mouse_bonus' => MOUSEFIGHT_NAMED_MOUSE_BONUS,
            'fitness_bonus' => $fitnessUnlocked ? MOUSEFIGHT_FITNESS_UNLOCK_BONUS : 0,
            'mouse_warrior_power' => $mouseWarriorPower,
            'highest_trait_level' => $highestTraitLevel,
        ],
        'eligibility' => [
            'has_custom_name' => trim((string)($mouse['custom_name'] ?? '')) !== '',
            'fitness_unlocked' => $fitnessUnlocked,
            'verified_owner' => mousefight_int($mouse['is_verified'] ?? 0) === 1,
            'can_fight' => false,
        ],
        'traits' => array_slice($traits, 0, 12),
        'abilities' => array_slice($abilities, 0, 12),
        'genetic_support' => [
            'enabled' => $inventoryEnabled,
            'cap' => MOUSEFIGHT_GENETIC_SUPPORT_CAP,
            'max_items_used' => $maxInventoryItems,
            'power' => $geneticSupportPower,
            'raw_power' => round($geneticRawSupport, 2),
            'items' => $selectedGeneticItems,
            'available_items_total' => count($allGeneticItems),
            'rule' => 'owner_support_inventory_not_permanent_equipment',
        ],
    ];
}

try {
    if (!file_exists($dbPath)) {
        throw new RuntimeException('Database file not found.');
    }

    $discordId = trim((string)($_GET['discord_id'] ?? $_GET['user_id'] ?? ''));
    if ($discordId === '') {
        mousefight_json_response([
            'success' => false,
            'error' => 'discord_id is required',
        ], 400);
    }

    $search = trim((string)($_GET['search'] ?? ''));
    $limit = mousefight_bounded_int($_GET['limit'] ?? null, MOUSEFIGHT_DEFAULT_LIMIT, 1, MOUSEFIGHT_MAX_LIMIT);
    $inventoryEnabled = mousefight_bool($_GET['inventory_enabled'] ?? null, true);
    $maxInventoryItems = mousefight_bounded_int(
        $_GET['max_inventory_items'] ?? null,
        MOUSEFIGHT_DEFAULT_MAX_INVENTORY_ITEMS,
        0,
        MOUSEFIGHT_MAX_INVENTORY_ITEMS
    );
    $fitnessRequired = mousefight_bool($_GET['fitness_required'] ?? null, true);
    $minHighestTraitLevel = mousefight_bounded_int(
        $_GET['min_highest_trait_level'] ?? null,
        MOUSEFIGHT_FITNESS_UNLOCK_LEVEL,
        0,
        999
    );

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /**
     * Load only this Discord user's named Genesis mice with one current ownership row per token.
     *
     * Plain language for DEVS:
     * tbl_nft_ownership can contain historical rows. The current_ownership CTE
     * keeps MouseFight from showing duplicate or outdated mice.
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
            co.wallet,
            COALESCE(co.username, u.username, cn.user_id) AS owner_username,
            co.nft_name,
            co.image_url,
            COALESCE(co.is_verified, 0) AS is_verified
        FROM tbl_nft_custom_names cn
        INNER JOIN current_ownership co
            ON co.token_id = cn.token_id
           AND co.collection = cn.collection
        LEFT JOIN tbl_users u
            ON u.discord_id = COALESCE(co.user_id, cn.user_id)
        WHERE cn.collection = 'genesis'
          AND TRIM(COALESCE(cn.custom_name, '')) <> ''
          AND COALESCE(co.is_verified, 0) = 1
          AND co.user_id = ?
        ORDER BY cn.updated_at DESC
    ");
    $namedMiceStmt->execute([$discordId]);
    $namedMice = $namedMiceStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$namedMice) {
        mousefight_json_response([
            'success' => true,
            'data' => [
                'discord_id' => $discordId,
                'mice' => [],
            ],
            'criteria' => [
                'requires_custom_name' => true,
                'requires_verified_current_owner' => true,
                'fitness_required' => $fitnessRequired,
                'min_highest_trait_level' => $minHighestTraitLevel,
                'genetic_items_are_owner_support_inventory' => true,
            ],
        ]);
    }

    $traitStmt = $db->prepare("
        SELECT
            token_id,
            collection,
            trait_type,
            trait_value,
            current_level,
            upgrade_status
        FROM tbl_nft_trait_upgrades
        WHERE COALESCE(collection, 'genesis') = 'genesis'
    ");
    $traitStmt->execute();
    $traitsByToken = mousefight_group_by_token($traitStmt->fetchAll(PDO::FETCH_ASSOC));

    $abilityStmt = $db->prepare("
        SELECT
            token_id,
            collection,
            category,
            ability_key,
            ability_key AS ability_label,
            current_level,
            upgrade_status
        FROM tbl_nft_ability_upgrades
        WHERE COALESCE(collection, 'genesis') = 'genesis'
    ");
    $abilityStmt->execute();
    $abilitiesByToken = mousefight_group_by_token($abilityStmt->fetchAll(PDO::FETCH_ASSOC));

    /**
     * Load real owner Genetic Items for MouseFight support and flavor.
     *
     * Plain language for DEVS:
     * MouseFight may describe only items returned here. Do not invent weapons,
     * gear, or inventory names in Discord embeds.
     */
    $geneticStmt = $db->prepare("
        SELECT
            g.user_id,
            g.genetic_item_id,
            g.catalog_id,
            g.trait_type,
            g.trait_value,
            g.current_level,
            g.upgrade_status,
            COALESCE(c.rarity_tier, 'unknown') AS rarity_tier,
            c.image_path,
            c.preview_path
        FROM tbl_user_genetic_items g
        LEFT JOIN tbl_genetic_trait_catalog c
            ON c.catalog_id = g.catalog_id
        WHERE g.user_id = ?
          AND COALESCE(g.user_id, '') <> ''
    ");
    $geneticStmt->execute([$discordId]);
    $geneticItemsByUser = mousefight_group_by_user($geneticStmt->fetchAll(PDO::FETCH_ASSOC));

    $mice = [];

    foreach ($namedMice as $mouse) {
        if (!mousefight_matches_search($mouse, $search)) {
            continue;
        }

        $profile = mousefight_build_mouse_profile(
            $mouse,
            $traitsByToken,
            $abilitiesByToken,
            $geneticItemsByUser,
            $inventoryEnabled,
            $maxInventoryItems
        );

        $highestTraitLevel = mousefight_int($profile['power']['highest_trait_level'] ?? 0);
        $canFight = $profile['eligibility']['verified_owner']
            && $profile['eligibility']['has_custom_name']
            && (!$fitnessRequired || $highestTraitLevel >= $minHighestTraitLevel);

        $profile['eligibility']['can_fight'] = $canFight;
        $profile['eligibility']['min_highest_trait_level_required'] = $fitnessRequired ? $minHighestTraitLevel : 0;

        if ($fitnessRequired && $highestTraitLevel < $minHighestTraitLevel) {
            continue;
        }

        $mice[] = $profile;
    }

    usort($mice, function ($a, $b) {
        $aPower = mousefight_int($a['power']['mouse_warrior_power'] ?? 0);
        $bPower = mousefight_int($b['power']['mouse_warrior_power'] ?? 0);

        if ($aPower !== $bPower) {
            return $bPower <=> $aPower;
        }

        return strcmp((string)$a['custom_name'], (string)$b['custom_name']);
    });

    $mice = array_slice($mice, 0, $limit);

    mousefight_json_response([
        'success' => true,
        'generated_at' => gmdate('c'),
        'data' => [
            'discord_id' => $discordId,
            'mice' => $mice,
        ],
        'criteria' => [
            'requires_custom_name' => true,
            'requires_verified_current_owner' => true,
            'fitness_required' => $fitnessRequired,
            'min_highest_trait_level' => $fitnessRequired ? $minHighestTraitLevel : 0,
            'inventory_enabled' => $inventoryEnabled,
            'max_inventory_items' => $maxInventoryItems,
            'genetic_items_are_owner_support_inventory' => true,
            'genetic_support_cap' => MOUSEFIGHT_GENETIC_SUPPORT_CAP,
        ],
        'scoring' => [
            'trait_power' => 'sum of Genesis trait levels on this NFT',
            'ability_power' => 'sum of Genesis ability levels on this NFT multiplied by 2',
            'genetic_support_power' => 'real owner Genetic Item levels with rarity multipliers, capped at 250',
            'named_mouse_bonus' => MOUSEFIGHT_NAMED_MOUSE_BONUS,
            'fitness_unlock_bonus' => MOUSEFIGHT_FITNESS_UNLOCK_BONUS,
        ],
    ]);
} catch (Throwable $error) {
    mousefight_json_response([
        'success' => false,
        'error' => $error->getMessage(),
    ], 500);
}
