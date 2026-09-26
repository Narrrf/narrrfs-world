<?php
/** Transaction-local delivery helpers. They never open or commit a transaction. */

function mouse_lp_store_load_source(PDO $pdo, string $type, int $sourceId): ?array
{
    if ($type === 'normal_store') {
        $stmt = $pdo->prepare("SELECT item_id AS source_id, item_name AS title, image_url, is_active AS active
            FROM tbl_store_items WHERE item_id = ? AND COALESCE(is_active, 0) = 1 LIMIT 1");
        $stmt->execute([$sourceId]);
    } elseif ($type === 'genetic_item') {
        $stmt = $pdo->prepare("SELECT catalog_id AS source_id, display_title AS title, preview_path AS image_url,
            is_active AS active, is_visible, trait_type, trait_value
            FROM tbl_genetic_trait_catalog WHERE catalog_id = ? AND is_active = 1 AND is_visible = 1 LIMIT 1");
        $stmt->execute([$sourceId]);
    } else { return null; }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function mouse_lp_store_deliver_normal(PDO $pdo, string $userId, int $itemId): int
{
    if (!mouse_lp_store_load_source($pdo, 'normal_store', $itemId)) throw new RuntimeException('Store item is unavailable.');
    $stmt = $pdo->prepare("INSERT INTO tbl_user_inventory (user_id, item_id, quantity, acquired_at)
        VALUES (?, ?, 1, CURRENT_TIMESTAMP)
        ON CONFLICT(user_id, item_id) DO UPDATE SET quantity = quantity + 1");
    $stmt->execute([$userId, $itemId]);
    $id = $pdo->prepare('SELECT inventory_id FROM tbl_user_inventory WHERE user_id = ? AND item_id = ?'); $id->execute([$userId, $itemId]);
    return (int)$id->fetchColumn();
}

function mouse_lp_store_deliver_genetic(PDO $pdo, string $userId, int $catalogId): int
{
    $catalog = mouse_lp_store_load_source($pdo, 'genetic_item', $catalogId);
    if (!$catalog) throw new RuntimeException('Genetic item is unavailable.');
    $count = $pdo->prepare('SELECT COUNT(*) FROM tbl_user_genetic_items WHERE user_id = ? AND trait_type = ? AND trait_value = ?');
    $count->execute([$userId, $catalog['trait_type'], $catalog['trait_value']]);
    if ((int)$count->fetchColumn() >= 2) throw new RuntimeException('You already own the maximum 2 copies of this genetic trait.');
    $insert = $pdo->prepare("INSERT INTO tbl_user_genetic_items (user_id, catalog_id, trait_type, trait_value, current_level, upgrade_status, acquired_method, is_listed_for_sale, created_at, updated_at)
        VALUES (?, ?, ?, ?, 1, 'idle', 'mouse_lp_store', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    $insert->execute([$userId, $catalogId, $catalog['trait_type'], $catalog['trait_value']]); $id = (int)$pdo->lastInsertId();
    $history = $pdo->prepare("INSERT INTO tbl_genetic_item_history (genetic_item_id, listing_id, user_id, action_type, old_value_json, new_value_json, admin_user_id, created_at)
        VALUES (?, NULL, ?, 'shop_purchase', '{}', ?, NULL, CURRENT_TIMESTAMP)");
    $history->execute([$id, $userId, json_encode(['catalog_id' => $catalogId, 'trait_type' => $catalog['trait_type'], 'trait_value' => $catalog['trait_value'], 'display_title' => $catalog['title'], 'acquired_method' => 'mouse_lp_store'], JSON_UNESCAPED_SLASHES)]);
    return $id;
}

/** Confirm the existing maximum-two exact-trait rule before payment or stock changes. */
function mouse_lp_store_can_deliver_genetic(PDO $pdo, string $userId, int $catalogId): bool
{
    $catalog = mouse_lp_store_load_source($pdo, 'genetic_item', $catalogId);
    if (!$catalog) {
        throw new RuntimeException('Genetic item is unavailable.');
    }
    $count = $pdo->prepare('SELECT COUNT(*) FROM tbl_user_genetic_items WHERE user_id = ? AND trait_type = ? AND trait_value = ?');
    $count->execute([$userId, $catalog['trait_type'], $catalog['trait_value']]);
    return (int)$count->fetchColumn() < 2;
}
