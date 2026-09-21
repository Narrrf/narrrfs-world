<?php
/**
 * Shared read-only rules for the user-level Genetic Item upgrade entitlement.
 *
 * Genetic upgrade capacity belongs to a Discord user, never to one Genetic
 * Item or a Genesis NFT. A missing entitlement row preserves the historic
 * one-slot behavior. Ready-to-claim items continue to occupy capacity until
 * the player claims them; reducing an entitlement never changes item state.
 */

const GENETIC_UPGRADE_SLOT_DEFAULT = 1;
const GENETIC_UPGRADE_SLOT_MINIMUM = 1;
const GENETIC_UPGRADE_SLOT_MAXIMUM = 3;

function genetic_upgrade_slot_table_exists(PDO $pdo): bool {
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'tbl_user_genetic_upgrade_slots'");
    $stmt->execute();
    return (bool)$stmt->fetchColumn();
}

/**
 * Return the user's configured slot limit. Invalid or absent data never grants
 * extra capacity and safely preserves the original one-slot entitlement.
 */
function get_user_genetic_upgrade_slot_limit(PDO $pdo, string $userId): int {
    if ($userId === '' || !genetic_upgrade_slot_table_exists($pdo)) {
        return GENETIC_UPGRADE_SLOT_DEFAULT;
    }

    $stmt = $pdo->prepare('SELECT upgrade_slots FROM tbl_user_genetic_upgrade_slots WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $value = $stmt->fetchColumn();

    if ($value === false || !preg_match('/^[1-3]$/', trim((string)$value))) {
        return GENETIC_UPGRADE_SLOT_DEFAULT;
    }

    return (int)$value;
}

/**
 * Count capacity already occupied by this user. Both active research and
 * ready-to-claim research occupy a slot until the item is claimed.
 */
function count_user_active_genetic_upgrades(PDO $pdo, string $userId, int $excludeGeneticItemId = 0): int {
    $sql = "SELECT COUNT(*) FROM tbl_user_genetic_items WHERE user_id = ? AND upgrade_status IN ('upgrading', 'ready_to_claim')";
    $params = [$userId];

    if ($excludeGeneticItemId > 0) {
        $sql .= ' AND genetic_item_id != ?';
        $params[] = $excludeGeneticItemId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function get_user_genetic_upgrade_slot_state(PDO $pdo, string $userId, int $excludeGeneticItemId = 0): array {
    $limit = get_user_genetic_upgrade_slot_limit($pdo, $userId);
    $active = count_user_active_genetic_upgrades($pdo, $userId, $excludeGeneticItemId);

    return [
        'limit' => $limit,
        'active' => $active,
        'available' => max(0, $limit - $active)
    ];
}
