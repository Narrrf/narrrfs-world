<?php
// 🧬 Genesis Ability Helpers
// Shared helpers for NFT-bound Genesis mouse ability progression.
//
// This system is intentionally separate from:
// - tbl_nft_trait_upgrades (Genesis trait progression)
// - tbl_user_genetic_items (Discord-bound Genetic items)
//
// It introduces a second NFT-bound progression matrix per Genesis mouse:
// Fitness / Weapons / Education.

declare(strict_types=1);

const NFT_ABILITY_MAX_LEVEL = 100;

const NFT_ABILITY_STATUS_IDLE = 'idle';
const NFT_ABILITY_STATUS_UPGRADING = 'upgrading';

const NFT_ABILITY_CATEGORIES = [
    'Fitness' => ['HP', 'SPEED', 'AIR'],
    'Weapons' => ['ATK', 'DEF', 'SPECIAL'],
    'Education' => ['SPELLS', 'CRAFTING', 'EXPANSION']
];

const NFT_ABILITY_UNLOCK_LEVELS = [
    'Fitness' => 5,
    'Weapons' => 20,
    'Education' => 30
];

/**
 * on top vakues for abilitys -max level etc - Return true when the category exists in the Genesis ability system.
 */
function is_valid_nft_ability_category(string $category): bool {
    return array_key_exists($category, NFT_ABILITY_CATEGORIES);
}

/**
 * Return true when the ability key belongs to the given category.
 */
function is_valid_nft_ability_key(string $category, string $abilityKey): bool {
    if (!is_valid_nft_ability_category($category)) {
        return false;
    }

    return in_array($abilityKey, NFT_ABILITY_CATEGORIES[$category], true);
}

/**
 * Return all 9 canonical Genesis ability rows in fixed order.
 */
function get_all_nft_ability_definitions(): array {
    $rows = [];

    foreach (NFT_ABILITY_CATEGORIES as $category => $abilityKeys) {
        foreach ($abilityKeys as $abilityKey) {
            $rows[] = [
                'category' => $category,
                'ability_key' => $abilityKey
            ];
        }
    }

    return $rows;
}

/**
 * Return the upgrade duration in seconds for the NEXT level,
 * based on the current level before upgrade completes.
 */
function get_nft_ability_upgrade_duration_seconds(int $currentLevel): int {
    if ($currentLevel <= 5) {
        return 3600; // 1h
    }

    if ($currentLevel <= 15) {
        return 7200; // 2h
    }

    if ($currentLevel <= 30) {
        return 14400; // 4h
    }

    if ($currentLevel <= 50) {
        return 28800; // 8h
    }

    if ($currentLevel <= 75) {
        return 43200; // 12h
    }

    return 86400; // 24h
}

/**
 * Return the DSPOINC cost for the NEXT ability upgrade.
 */
function get_nft_ability_upgrade_cost_dspoinc(int $currentLevel): int {
    if ($currentLevel <= 5) {
        return 250;
    }

    if ($currentLevel <= 15) {
        return 500;
    }

    if ($currentLevel <= 30) {
        return 1000;
    }

    if ($currentLevel <= 50) {
        return 2000;
    }

    if ($currentLevel <= 75) {
        return 4000;
    }

    return 8000;
}

/**
 * Return true when a category is unlocked by the NFT's highest Genesis trait level.
 */
function is_nft_ability_category_unlocked(string $category, int $highestTraitLevel): bool {
    if (!isset(NFT_ABILITY_UNLOCK_LEVELS[$category])) {
        return false;
    }

    return $highestTraitLevel >= NFT_ABILITY_UNLOCK_LEVELS[$category];
}

/**
 * Return unlock metadata for all 3 categories.
 */
function get_nft_ability_unlock_map(int $highestTraitLevel): array {
    $result = [];

    foreach (NFT_ABILITY_UNLOCK_LEVELS as $category => $requiredLevel) {
        $result[$category] = [
            'required_trait_level' => $requiredLevel,
            'is_unlocked' => $highestTraitLevel >= $requiredLevel
        ];
    }

    return $result;
}

/**
 * Ensure all 9 ability rows exist for one NFT.
 */
function seed_missing_nft_ability_rows(PDO $pdo, string $userId, string $tokenId, string $collection): void {
    $definitions = get_all_nft_ability_definitions();

    $selectStmt = $pdo->prepare("
        SELECT category, ability_key
        FROM tbl_nft_ability_upgrades
        WHERE token_id = ?
          AND collection = ?
    ");
    $selectStmt->execute([$tokenId, $collection]);

    $existing = [];
    foreach ($selectStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $existingKey = ($row['category'] ?? '') . '::' . ($row['ability_key'] ?? '');
        $existing[$existingKey] = true;
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO tbl_nft_ability_upgrades (
            user_id,
            token_id,
            collection,
            category,
            ability_key,
            current_level,
            upgrade_status,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, 1, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");

    foreach ($definitions as $definition) {
        $category = $definition['category'];
        $abilityKey = $definition['ability_key'];
        $compoundKey = $category . '::' . $abilityKey;

        if (isset($existing[$compoundKey])) {
            continue;
        }

        $insertStmt->execute([
            $userId,
            $tokenId,
            $collection,
            $category,
            $abilityKey,
            NFT_ABILITY_STATUS_IDLE
        ]);
    }
}

/**
 * Load all ability rows for one NFT in stable UI order.
 */
function fetch_nft_ability_rows(PDO $pdo, string $tokenId, string $collection): array {
    $stmt = $pdo->prepare("
        SELECT
            ability_upgrade_id,
            user_id,
            token_id,
            collection,
            category,
            ability_key,
            current_level,
            upgrade_status,
            upgrade_started_at,
            upgrade_ends_at,
            last_completed_at,
            last_notified_ready_at,
            ready_notification_count,
            base_duration_seconds,
            last_duration_seconds,
            last_upgrade_cost_dspoinc,
            unlock_source_trait_level,
            created_at,
            updated_at,
            last_owner_user_id
        FROM tbl_nft_ability_upgrades
        WHERE token_id = ?
          AND collection = ?
        ORDER BY
            CASE category
                WHEN 'Fitness' THEN 1
                WHEN 'Weapons' THEN 2
                WHEN 'Education' THEN 3
                ELSE 99
            END,
            CASE ability_key
                WHEN 'HP' THEN 1
                WHEN 'SPEED' THEN 2
                WHEN 'AIR' THEN 3
                WHEN 'ATK' THEN 4
                WHEN 'DEF' THEN 5
                WHEN 'SPECIAL' THEN 6
                WHEN 'SPELLS' THEN 7
                WHEN 'CRAFTING' THEN 8
                WHEN 'EXPANSION' THEN 9
                ELSE 99
            END
    ");
    $stmt->execute([$tokenId, $collection]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return is_array($rows) ? $rows : [];
}

/**
 * Return true when this NFT already has one active ability upgrade.
 */
function nft_has_active_ability_upgrade(PDO $pdo, string $tokenId, string $collection, int $excludeAbilityUpgradeId = 0): bool {
    $sql = "
        SELECT ability_upgrade_id
        FROM tbl_nft_ability_upgrades
        WHERE token_id = ?
          AND collection = ?
          AND upgrade_status = ?
    ";

    $params = [$tokenId, $collection, NFT_ABILITY_STATUS_UPGRADING];

    if ($excludeAbilityUpgradeId > 0) {
        $sql .= " AND ability_upgrade_id != ? ";
        $params[] = $excludeAbilityUpgradeId;
    }

    $sql .= " LIMIT 1 ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Write one ability history row.
 */
function insert_nft_ability_history(
    PDO $pdo,
    ?int $abilityUpgradeId,
    string $userId,
    string $tokenId,
    string $collection,
    string $category,
    string $abilityKey,
    string $actionType,
    array $oldValue,
    array $newValue,
    ?string $adminUserId = null
): void {
    $stmt = $pdo->prepare("
        INSERT INTO tbl_nft_ability_upgrade_history (
            ability_upgrade_id,
            user_id,
            token_id,
            collection,
            category,
            ability_key,
            action_type,
            old_value_json,
            new_value_json,
            admin_user_id,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $abilityUpgradeId,
        $userId,
        $tokenId,
        $collection,
        $category,
        $abilityKey,
        $actionType,
        json_encode($oldValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        json_encode($newValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $adminUserId
    ]);
}

/**
 * Auto-complete all expired ability upgrades for one NFT.
 *
 * This keeps the system auto-claimed:
 * - level increases automatically
 * - row returns to idle
 * - completion timestamp is recorded
 */
function auto_finalize_expired_nft_ability_upgrades(PDO $pdo, string $tokenId, string $collection): array {
    $stmt = $pdo->prepare("
        SELECT
            ability_upgrade_id,
            user_id,
            token_id,
            collection,
            category,
            ability_key,
            current_level,
            upgrade_status,
            upgrade_started_at,
            upgrade_ends_at,
            last_completed_at,
            ready_notification_count,
            last_notified_ready_at,
            base_duration_seconds,
            last_duration_seconds,
            last_upgrade_cost_dspoinc,
            unlock_source_trait_level
        FROM tbl_nft_ability_upgrades
        WHERE token_id = ?
          AND collection = ?
          AND upgrade_status = ?
          AND upgrade_ends_at IS NOT NULL
          AND datetime(upgrade_ends_at) <= datetime('now')
    ");
    $stmt->execute([$tokenId, $collection, NFT_ABILITY_STATUS_UPGRADING]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $completed = [];

    if (!$rows) {
        return $completed;
    }

    $updateStmt = $pdo->prepare("
        UPDATE tbl_nft_ability_upgrades
        SET
            current_level = ?,
            upgrade_status = ?,
            upgrade_started_at = NULL,
            upgrade_ends_at = NULL,
            last_completed_at = CURRENT_TIMESTAMP,
            ready_notification_count = COALESCE(ready_notification_count, 0) + 1,
            last_notified_ready_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE ability_upgrade_id = ?
    ");

    foreach ($rows as $row) {
        $oldValue = $row;
        $currentLevel = (int)($row['current_level'] ?? 1);

        if ($currentLevel >= NFT_ABILITY_MAX_LEVEL) {
            continue;
        }

        $newLevel = min(NFT_ABILITY_MAX_LEVEL, $currentLevel + 1);

        $updateStmt->execute([
            $newLevel,
            NFT_ABILITY_STATUS_IDLE,
            (int)$row['ability_upgrade_id']
        ]);

        $newValue = array_merge($row, [
            'current_level' => $newLevel,
            'upgrade_status' => NFT_ABILITY_STATUS_IDLE,
            'upgrade_started_at' => null,
            'upgrade_ends_at' => null,
            'last_completed_at' => gmdate('Y-m-d H:i:s')
        ]);

        insert_nft_ability_history(
            $pdo,
            (int)$row['ability_upgrade_id'],
            (string)$row['user_id'],
            (string)$row['token_id'],
            (string)$row['collection'],
            (string)$row['category'],
            (string)$row['ability_key'],
            'auto_complete',
            $oldValue,
            $newValue,
            null
        );

        $completed[] = [
            'ability_upgrade_id' => (int)$row['ability_upgrade_id'],
            'category' => (string)$row['category'],
            'ability_key' => (string)$row['ability_key'],
            'new_level' => $newLevel
        ];
    }

    return $completed;
}