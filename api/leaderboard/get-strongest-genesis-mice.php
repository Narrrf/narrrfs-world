<?php
/**
 * Strongest Genesis Mice leaderboard.
 *
 * Plain language for DEVS:
 * This endpoint is read-only. It ranks named Genesis mice by their NFT-bound
 * Genesis trait levels, NFT-bound Genesis ability levels, and the current
 * owner's Discord-bound Genetic Item support inventory.
 *
 * Important architecture:
 * - Genesis Traits are NFT-bound by token_id + collection.
 * - Genesis Abilities are NFT-bound by token_id + collection.
 * - Genetic Items are Discord-user-bound in V1, not permanently equipped to one mouse.
 * - This endpoint must not write DSPOINC, inventory, traits, abilities, names, or ownership.
 */

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const FITNESS_UNLOCK_LEVEL = 5;
const NAMED_MOUSE_BONUS = 25;
const FITNESS_UNLOCK_BONUS = 50;
const ABILITY_POWER_MULTIPLIER = 2;
const GENETIC_SUPPORT_CAP = 250;
const DEFAULT_LIMIT = 50;

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

/**
 * Return a safe integer from DB values.
 */
function strongest_mouse_int($value): int {
    return is_numeric($value) ? (int)$value : 0;
}

/**
 * Return a safe float rarity multiplier for Genetic Support.
 *
 * Plain language for DEVS:
 * Genetic Items are user-bound in V1. This multiplier only gives the owner's
 * inventory a limited support value around the mouse. It does not equip items
 * to the NFT and does not mutate inventory.
 */
function strongest_mouse_rarity_multiplier($rarity): float {
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
 * Create a short token label for public display.
 */
function strongest_mouse_short_token($tokenId): string {
    $token = (string)$tokenId;

    if (strlen($token) <= 12) {
        return $token;
    }

    return substr($token, 0, 4) . '...' . substr($token, -4);
}

/**
 * Fetch rows grouped by token_id + collection.
 */
function strongest_mouse_group_by_token(array $rows): array {
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
 * Fetch rows grouped by owner/user id.
 */
function strongest_mouse_group_by_user(array $rows): array {
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

try {
    if (!file_exists($dbPath)) {
        throw new RuntimeException('Database file not found.');
    }

    $limit = isset($_GET['limit']) ? strongest_mouse_int($_GET['limit']) : DEFAULT_LIMIT;
    if ($limit < 1) {
        $limit = DEFAULT_LIMIT;
    }
    if ($limit > 100) {
        $limit = 100;
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /**
     * Load named Genesis mice with one current/best ownership row per token.
     *
     * Plain language for DEVS:
     * tbl_nft_ownership can contain more than one row per token after transfers
     * or recovery work. This CTE picks the most trustworthy current row and
     * prevents duplicate leaderboard entries for the same named mouse.
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
        ORDER BY cn.updated_at DESC
    ");
    $namedMiceStmt->execute();
    $namedMice = $namedMiceStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$namedMice) {
        echo json_encode([
            'success' => true,
            'leaderboard_name' => 'Strongest Genesis Mice',
            'top_mouse' => null,
            'mice' => [],
            'criteria' => [
                'requires_custom_name' => true,
                'requires_fitness_unlock' => true,
                'fitness_unlock_trait_level' => FITNESS_UNLOCK_LEVEL,
            ],
        ]);
        exit;
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
    $traitsByToken = strongest_mouse_group_by_token($traitStmt->fetchAll(PDO::FETCH_ASSOC));

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
    $abilitiesByToken = strongest_mouse_group_by_token($abilityStmt->fetchAll(PDO::FETCH_ASSOC));

    /**
     * Load owner Genetic Items as support inventory.
     *
     * Plain language for DEVS:
     * These items are NOT equipped to one mouse in V1. They are current owner
     * support inventory, capped in the final score so a large inventory does
     * not overpower actual Genesis mouse progression.
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
        WHERE COALESCE(g.user_id, '') <> ''
    ");
    $geneticStmt->execute();
    $geneticItemsByUser = strongest_mouse_group_by_user($geneticStmt->fetchAll(PDO::FETCH_ASSOC));

    $rankedMice = [];

    foreach ($namedMice as $mouse) {
        $tokenId = (string)($mouse['token_id'] ?? '');
        $collection = (string)($mouse['collection'] ?? 'genesis');
        $tokenKey = $tokenId . '|' . $collection;

        $traits = $traitsByToken[$tokenKey] ?? [];
        $traitPower = 0;
        $highestTraitLevel = 0;

        foreach ($traits as &$trait) {
            $level = strongest_mouse_int($trait['current_level'] ?? 1);
            $trait['current_level'] = $level;
            $traitPower += $level;
            $highestTraitLevel = max($highestTraitLevel, $level);
        }
        unset($trait);

        if ($highestTraitLevel < FITNESS_UNLOCK_LEVEL) {
            continue;
        }

        $abilities = $abilitiesByToken[$tokenKey] ?? [];
        $abilityRawPower = 0;

        foreach ($abilities as &$ability) {
            $level = strongest_mouse_int($ability['current_level'] ?? 0);
            $ability['current_level'] = $level;
            $abilityRawPower += $level;
        }
        unset($ability);

        $abilityPower = $abilityRawPower * ABILITY_POWER_MULTIPLIER;

        $ownerUserId = (string)($mouse['owner_user_id'] ?: $mouse['name_user_id']);
        $geneticItems = $geneticItemsByUser[$ownerUserId] ?? [];

        $geneticRawSupport = 0.0;

        foreach ($geneticItems as &$item) {
            $itemLevel = strongest_mouse_int($item['current_level'] ?? 1);
            $rarityMultiplier = strongest_mouse_rarity_multiplier($item['rarity_tier'] ?? 'unknown');

            $item['current_level'] = $itemLevel;
            $item['support_power'] = round($itemLevel * $rarityMultiplier, 2);

            $geneticRawSupport += $item['support_power'];
        }
        unset($item);

        $geneticSupportPower = min((int)round($geneticRawSupport), GENETIC_SUPPORT_CAP);

        $mousePower =
            $traitPower +
            $abilityPower +
            $geneticSupportPower +
            NAMED_MOUSE_BONUS +
            FITNESS_UNLOCK_BONUS;

        usort($traits, function ($a, $b) {
            return strongest_mouse_int($b['current_level'] ?? 0) <=> strongest_mouse_int($a['current_level'] ?? 0);
        });

        usort($abilities, function ($a, $b) {
            return strongest_mouse_int($b['current_level'] ?? 0) <=> strongest_mouse_int($a['current_level'] ?? 0);
        });

        usort($geneticItems, function ($a, $b) {
            return (float)($b['support_power'] ?? 0) <=> (float)($a['support_power'] ?? 0);
        });

        $rankedMice[] = [
            'rank' => 0,
            'token_id' => $tokenId,
            'token_short' => strongest_mouse_short_token($tokenId),
            'collection' => $collection,
            'custom_name' => $mouse['custom_name'],
            'nft_name' => $mouse['nft_name'] ?: strongest_mouse_short_token($tokenId),
            'image_url' => $mouse['image_url'] ?: '',
            'owner_user_id' => $ownerUserId,
            'owner_username' => $mouse['owner_username'] ?: $ownerUserId,
            'is_verified' => strongest_mouse_int($mouse['is_verified'] ?? 0) === 1,
            'mouse_power' => $mousePower,
            'trait_power' => $traitPower,
            'ability_power' => $abilityPower,
            'ability_raw_power' => $abilityRawPower,
            'genetic_support_power' => $geneticSupportPower,
            'genetic_support_raw' => round($geneticRawSupport, 2),
            'named_mouse_bonus' => NAMED_MOUSE_BONUS,
            'fitness_unlock_bonus' => FITNESS_UNLOCK_BONUS,
            'highest_trait_level' => $highestTraitLevel,
            'fitness_unlocked' => true,
            'traits' => array_slice($traits, 0, 12),
            'abilities' => array_slice($abilities, 0, 12),
            'genetic_items' => array_slice($geneticItems, 0, 12),
            'genetic_items_total' => count($geneticItems),
        ];
    }

    usort($rankedMice, function ($a, $b) {
        if ($a['mouse_power'] !== $b['mouse_power']) {
            return $b['mouse_power'] <=> $a['mouse_power'];
        }

        if ($a['highest_trait_level'] !== $b['highest_trait_level']) {
            return $b['highest_trait_level'] <=> $a['highest_trait_level'];
        }

        return strcmp((string)$a['custom_name'], (string)$b['custom_name']);
    });

    $rankedMice = array_slice($rankedMice, 0, $limit);

    foreach ($rankedMice as $index => &$mouse) {
        $mouse['rank'] = $index + 1;
    }
    unset($mouse);

    echo json_encode([
        'success' => true,
        'leaderboard_name' => 'Strongest Genesis Mice',
        'generated_at' => gmdate('c'),
        'criteria' => [
            'requires_custom_name' => true,
            'requires_fitness_unlock' => true,
            'fitness_unlock_trait_level' => FITNESS_UNLOCK_LEVEL,
            'genetic_items_are_owner_support_inventory' => true,
            'genetic_support_cap' => GENETIC_SUPPORT_CAP,
        ],
        'scoring' => [
            'trait_power' => 'sum of Genesis trait levels on this NFT',
            'ability_power' => 'sum of Genesis ability levels on this NFT multiplied by 2',
            'genetic_support_power' => 'owner Genetic Item levels with rarity multipliers, capped at 250',
            'named_mouse_bonus' => NAMED_MOUSE_BONUS,
            'fitness_unlock_bonus' => FITNESS_UNLOCK_BONUS,
        ],
        'top_mouse' => $rankedMice[0] ?? null,
        'mice' => $rankedMice,
    ]);
} catch (Throwable $error) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage(),
    ]);
}