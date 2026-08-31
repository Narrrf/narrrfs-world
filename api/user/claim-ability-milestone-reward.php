<?php
/**
 * Genesis Ability Milestone Rewards — Claim Endpoint.
 *
 * Plain language for DEVS:
 * This endpoint claims one Weapon Ability milestone reward chest for one
 * verified Genesis mouse. Backend is authoritative: it verifies Discord session,
 * verified ownership, ability category, current ability level, duplicate claim
 * protection, and DSPOINC ledger delivery.
 *
 * It does not upgrade abilities, traits, Genetic Items, marketplace state,
 * ownership, custom names, or Reward Chamber boxes.
 */

declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/genesis-ability-helpers.php';

const LAB_MILESTONE_SYSTEM_ACTOR = 'lab_milestone_system';
const LAB_MILESTONE_GAME_KEY = 'lab_milestone_reward';

const WEAPON_MILESTONE_REWARDS = [
    [
        'required_level' => 1,
        'reward_amount' => 25000,
        'title' => 'Weapon Unlock Chest',
        'description' => 'Your Genesis mouse unlocked the Weapons lane.',
    ],
    [
        'required_level' => 5,
        'reward_amount' => 10000,
        'title' => 'Weapon Training Chest',
        'description' => 'Your weapon ability reached level 5.',
    ],
    [
        'required_level' => 10,
        'reward_amount' => 25000,
        'title' => 'Weapon Specialist Chest',
        'description' => 'Your weapon ability reached level 10.',
    ],
    [
        'required_level' => 25,
        'reward_amount' => 75000,
        'title' => 'Weapon Master Chest',
        'description' => 'Your weapon ability reached level 25.',
    ],
    [
        'required_level' => 50,
        'reward_amount' => 150000,
        'title' => 'Legendary Weapon Chest',
        'description' => 'Your weapon ability reached level 50.',
    ],
];


const FITNESS_TRAIT_MILESTONE_REWARDS = [
    [
        'required_trait_level' => 10,
        'milestone_key' => 'fitness_trait_level_10',
        'reward_type' => 'store_item',
        'reward_reference_id' => 51,
        'reward_amount' => 0,
        'reward_currency' => 'ITEM',
        'title' => 'Halfway Fitness Journey Chest',
        'description' => 'YEAHHH! Your Genesis mouse reached Trait Level 10 — halfway to the Weapon Journey.',
        'preview_label' => '500 EMPIRE TOKEN',
        'source' => 'genesis_fitness_trait',
    ],
[
    'required_trait_level' => 15,
    'milestone_key' => 'fitness_trait_level_15',
    'reward_type' => 'genetic_trait',
    'reward_reference_id' => 69,
    'reward_amount' => 1000000,
    'reward_currency' => 'DSPOINC_FALLBACK',
    'title' => 'Deep Training Fitness Chest',
    'description' => 'Deep Training Milestone unlocked — only 5 more trait levels until the Weapon Lane opens.',
    'preview_label' => 'Gun Special Genetic Item or 1,000,000 DSPOINC fallback',
    'source' => 'genesis_fitness_trait',
],
[
    'required_trait_level' => 20,
    'milestone_key' => 'fitness_trait_level_20',
    'reward_type' => 'store_item',
    'reward_reference_id' => 51,
    'reward_amount' => 0,
    'reward_currency' => 'ITEM',
    'title' => 'Weapon Journey Fitness Chest',
    'description' => 'Weapon Lane unlocked — your Genesis mouse reached Trait Level 20.',
    'preview_label' => '500 EMPIRE TOKEN',
    'source' => 'genesis_fitness_trait',
],
];

/**
 * Return one JSON response and stop execution.
 */
function milestone_json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/**
 * Return true when running on localhost-style development hosts.
 */
function milestone_is_localhost_env(): bool {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Return the active Discord user id from session.
 *
 * Plain language for DEVS:
 * Production must use the Discord session. Localhost may pass user_id only for
 * controlled local testing, matching the Reward Chamber local testing pattern.
 */
function get_active_claim_milestone_user_id(): string {
    if (function_exists('narrrfs_touch_session')) {
        narrrfs_touch_session();
    } else {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $request = get_claim_milestone_request_data();
    $requestUserId = trim((string)($request['user_id'] ?? ''));

    if (milestone_is_localhost_env() && $requestUserId !== '') {
        error_log("⚔️ Ability Milestone: Using local request user_id for testing: {$requestUserId}");
        return $requestUserId;
    }

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        milestone_json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch',
        ], 403);
    }

    return $sessionUserId;
}

/**
 * Return request data from JSON body, POST, and query string.
 */
function get_claim_milestone_request_data(): array {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '{}', true);

    if (!is_array($json)) {
        $json = [];
    }

    $cached = array_merge($_GET ?? [], $_POST ?? [], $json);

    return $cached;
}

/**
 * Return one stable milestone key.
 */
function build_claim_weapon_milestone_key(string $abilityKey, int $requiredLevel): string {
    return 'weapon_' . strtoupper($abilityKey) . '_level_' . $requiredLevel;
}

/**
 * Return the configured reward by milestone key.
 */
function resolve_weapon_milestone_reward(string $milestoneKey): ?array {
    foreach (['ATK', 'DEF', 'SPECIAL'] as $abilityKey) {
        foreach (WEAPON_MILESTONE_REWARDS as $milestone) {
            $requiredLevel = (int)$milestone['required_level'];
            $expectedKey = build_claim_weapon_milestone_key($abilityKey, $requiredLevel);

            if ($expectedKey !== $milestoneKey) {
                continue;
            }

            return [
                'milestone_key' => $expectedKey,
                'ability_key' => $abilityKey,
                'ability_category' => 'Weapons',
                'required_level' => $requiredLevel,
                'reward_amount' => (int)$milestone['reward_amount'],
                'reward_currency' => 'DSPOINC',
                'title' => $milestone['title'],
                'description' => $milestone['description'],
                'source' => 'genesis_weapon_ability',
            ];
        }
    }

    return null;
}


/**
 * Return one stable Fitness Journey trait milestone key.
 *
 * Plain language for DEVS:
 * Fitness Journey chests are based on highest Genesis trait level.
 * They are not based on seeded Fitness ability rows.
 */
function build_claim_fitness_trait_milestone_key(int $requiredTraitLevel): string {
    return 'fitness_trait_level_' . $requiredTraitLevel;
}

/**
 * Return the configured Fitness Journey reward by milestone key.
 */
function resolve_fitness_trait_milestone_reward(string $milestoneKey): ?array {
    foreach (FITNESS_TRAIT_MILESTONE_REWARDS as $milestone) {
        $requiredTraitLevel = (int)$milestone['required_trait_level'];
        $expectedKey = build_claim_fitness_trait_milestone_key($requiredTraitLevel);

        if ($expectedKey !== $milestoneKey) {
            continue;
        }

        return [
            'milestone_key' => $expectedKey,
            'ability_key' => 'TRAIT',
            'ability_category' => 'Fitness',
            'required_level' => $requiredTraitLevel,
            'required_trait_level' => $requiredTraitLevel,
            'reward_amount' => (int)$milestone['reward_amount'],
            'reward_currency' => (string)$milestone['reward_currency'],
            'reward_type' => (string)$milestone['reward_type'],
            'reward_reference_id' => $milestone['reward_reference_id'],
            'title' => (string)$milestone['title'],
            'description' => (string)$milestone['description'],
            'preview_label' => (string)$milestone['preview_label'],
            'source' => 'genesis_fitness_trait',
        ];
    }

    return null;
}

/**
 * Return true when the selected Genesis mouse is verified for this user.
 */
function user_controls_claim_genesis_mouse(PDO $pdo, string $userId, string $tokenId, string $collection): bool {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM tbl_nft_ownership
        WHERE user_id = ?
          AND token_id = ?
          AND collection = ?
          AND COALESCE(is_verified, 0) = 1
        ORDER BY
          COALESCE(verified_at, '') DESC,
          COALESCE(acquired_at, '') DESC,
          ownership_id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $tokenId, $collection]);

    return (bool)$stmt->fetchColumn();
}

/**
 * Return one Weapon ability row for this Genesis mouse.
 */
function fetch_claim_weapon_ability_row(PDO $pdo, string $tokenId, string $collection, string $abilityKey): ?array {
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
            unlock_source_trait_level
        FROM tbl_nft_ability_upgrades
        WHERE token_id = ?
          AND collection = ?
          AND category = 'Weapons'
          AND ability_key = ?
        LIMIT 1
    ");
    $stmt->execute([$tokenId, $collection, $abilityKey]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

/**
 * Return the selected mouse's highest Genesis trait level.
 *
 * Plain language for DEVS:
 * Weapon rewards are locked behind the real Weapons lane unlock. Seeded ATK,
 * DEF, or SPECIAL rows at level 1 must not be enough to claim DSPOINC. The
 * mouse needs at least one Genesis trait at level 20+ first.
 */
function fetch_claim_highest_genesis_trait_level(PDO $pdo, string $tokenId, string $collection): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(current_level), 0)
        FROM tbl_nft_trait_upgrades
        WHERE token_id = ?
          AND collection = ?
    ");
    $stmt->execute([$tokenId, $collection]);

    return (int)$stmt->fetchColumn();
}

/**
 * Return true when this exact milestone was already claimed.
 */
function milestone_reward_already_claimed(PDO $pdo, string $userId, string $tokenId, string $collection, string $milestoneKey): bool {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM tbl_lab_milestone_rewards
        WHERE user_id = ?
          AND token_id = ?
          AND collection = ?
          AND milestone_key = ?
        LIMIT 1
    ");
    $stmt->execute([$userId, $tokenId, $collection, $milestoneKey]);

    return (bool)$stmt->fetchColumn();
}

/**
 * Insert one positive DSPOINC ledger row.
 *
 * Plain language for DEVS:
 * This mirrors the Reward Chamber ledger style. Automated rewards should add
 * one positive row instead of deleting/rebuilding a user's score history.
 */
function insert_claim_dspoinc_change(PDO $pdo, string $userId, int $amount, string $game, string $source): void {
    $stmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (
            user_id,
            game,
            score,
            source,
            timestamp
        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$userId, $game, $amount, $source]);
}

/**
 * Insert one score adjustment audit row.
 */
function insert_claim_score_adjustment(PDO $pdo, string $userId, string $adminId, int $amount, string $action, string $reason): void {
    $stmt = $pdo->prepare("
        INSERT INTO tbl_score_adjustments (
            user_id,
            admin_id,
            amount,
            action,
            reason,
            timestamp
        ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$userId, $adminId, $amount, $action, $reason]);
}

/**
 * Insert the milestone claim row.
 *
 * Plain language for DEVS:
 * This row is the one-time lock/audit row. Real ownership still goes into
 * tbl_user_inventory, tbl_user_genetic_items, or the DSPOINC ledger.
 */
function insert_lab_milestone_reward_claim(PDO $pdo, string $userId, string $tokenId, string $collection, array $reward, array $deliveryPayload = []): int {
    $stmt = $pdo->prepare("
        INSERT INTO tbl_lab_milestone_rewards (
            user_id,
            token_id,
            collection,
            milestone_key,
            ability_key,
            ability_category,
            required_level,
            reward_amount,
            reward_currency,
            reward_type,
            reward_reference_id,
            reward_title,
            reward_description,
            reward_payload_json,
            source,
            claimed_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $userId,
        $tokenId,
        $collection,
        $reward['milestone_key'],
        $reward['ability_key'],
        $reward['ability_category'],
        (int)$reward['required_level'],
        (int)($deliveryPayload['reward_amount'] ?? $reward['reward_amount']),
        (string)($deliveryPayload['reward_currency'] ?? $reward['reward_currency']),
        (string)($deliveryPayload['reward_type'] ?? $reward['reward_type'] ?? 'dspoinc'),
        $deliveryPayload['reward_reference_id'] ?? $reward['reward_reference_id'] ?? null,
        (string)($deliveryPayload['reward_title'] ?? $reward['title']),
        (string)($deliveryPayload['reward_description'] ?? $reward['description']),
        json_encode($deliveryPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $reward['source'],
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Load one store item by id.
 */
function fetch_claim_store_item_row(PDO $pdo, int $storeItemId): ?array {
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_store_items
        WHERE item_id = ?
          AND COALESCE(is_active, 1) = 1
        LIMIT 1
    ");
    $stmt->execute([$storeItemId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

/**
 * Grant or stack one store item into user inventory.
 *
 * Plain language for DEVS:
 * tbl_user_inventory has UNIQUE(user_id, item_id). We update quantity when the
 * user already owns the item and insert a new row when they do not.
 */
function grant_claim_store_item_to_user(PDO $pdo, string $userId, array $storeItem, int $quantity, string $sourceTag): array {
    if (!claim_table_exists($pdo, 'tbl_user_inventory')) {
        throw new RuntimeException('tbl_user_inventory table not found');
    }

    $storeItemId = (int)($storeItem['item_id'] ?? 0);
    $itemName = trim((string)($storeItem['item_name'] ?? ''));
    $safeQuantity = max(1, $quantity);

    if ($storeItemId <= 0 || $itemName === '') {
        throw new RuntimeException('Store reward item row is malformed');
    }

    $select = $pdo->prepare("
        SELECT inventory_id, quantity
        FROM tbl_user_inventory
        WHERE user_id = ?
          AND item_id = ?
        LIMIT 1
    ");
    $select->execute([$userId, $storeItemId]);
    $existing = $select->fetch(PDO::FETCH_ASSOC);

    if (is_array($existing)) {
        $inventoryId = (int)$existing['inventory_id'];
        $newQuantity = (int)($existing['quantity'] ?? 0) + $safeQuantity;

        $update = $pdo->prepare("
            UPDATE tbl_user_inventory
            SET quantity = ?
            WHERE inventory_id = ?
        ");
        $update->execute([$newQuantity, $inventoryId]);

        return [
            'inventory_id' => $inventoryId,
            'item_id' => $storeItemId,
            'item_name' => $itemName,
            'quantity_awarded' => $safeQuantity,
            'inventory_quantity_after' => $newQuantity,
            'source' => $sourceTag,
        ];
    }

    $insert = $pdo->prepare("
        INSERT INTO tbl_user_inventory (
            user_id,
            item_id,
            quantity,
            acquired_at
        ) VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $insert->execute([$userId, $storeItemId, $safeQuantity]);

    return [
        'inventory_id' => (int)$pdo->lastInsertId(),
        'item_id' => $storeItemId,
        'item_name' => $itemName,
        'quantity_awarded' => $safeQuantity,
        'inventory_quantity_after' => $safeQuantity,
        'source' => $sourceTag,
    ];
}

/**
 * Load one Genetic Item catalog row by id.
 */
function fetch_claim_genetic_catalog_row(PDO $pdo, int $catalogId): ?array {
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_genetic_trait_catalog
        WHERE catalog_id = ?
          AND COALESCE(is_active, 1) = 1
          AND COALESCE(is_visible, 1) = 1
        LIMIT 1
    ");
    $stmt->execute([$catalogId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

/**
 * Return true when the user already owns this exact Genetic Item.
 *
 * Plain language for DEVS:
 * Markus requested the Lv15 Gun Special reward to fallback to DSPOINC if the
 * player already has Gun Special. So V1 uses a simple owns-any-copy check.
 */
function user_owns_claim_genetic_trait(PDO $pdo, string $userId, string $traitType, string $traitValue): bool {
    if ($userId === '' || $traitType === '' || $traitValue === '' || !claim_table_exists($pdo, 'tbl_user_genetic_items')) {
        return false;
    }

    $stmt = $pdo->prepare("
        SELECT genetic_item_id
        FROM tbl_user_genetic_items
        WHERE user_id = ?
          AND trait_type = ?
          AND trait_value = ?
        LIMIT 1
    ");
    $stmt->execute([$userId, $traitType, $traitValue]);

    return (bool)$stmt->fetchColumn();
}

/**
 * Insert one Genetic Item history row when the history table exists.
 */
function insert_claim_genetic_item_history(PDO $pdo, ?int $geneticItemId, string $userId, string $actionType, array $newValue): void {
    if (!claim_table_exists($pdo, 'tbl_genetic_item_history')) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO tbl_genetic_item_history (
            genetic_item_id,
            listing_id,
            user_id,
            action_type,
            old_value_json,
            new_value_json,
            admin_user_id,
            created_at
        ) VALUES (?, NULL, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $geneticItemId,
        $userId,
        $actionType,
        json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        json_encode($newValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LAB_MILESTONE_SYSTEM_ACTOR,
    ]);
}

/**
 * Grant one user-bound Genetic Item from the catalog.
 */
function grant_claim_genetic_trait_to_user(PDO $pdo, string $userId, array $catalogRow, string $sourceTag): array {
    if (!claim_table_exists($pdo, 'tbl_user_genetic_items')) {
        throw new RuntimeException('tbl_user_genetic_items table not found');
    }

    $catalogId = (int)($catalogRow['catalog_id'] ?? 0);
    $traitType = trim((string)($catalogRow['trait_type'] ?? ''));
    $traitValue = trim((string)($catalogRow['trait_value'] ?? ''));
    $displayTitle = trim((string)($catalogRow['display_title'] ?? $traitValue));

    if ($catalogId <= 0 || $traitType === '' || $traitValue === '') {
        throw new RuntimeException('Genetic reward catalog row is malformed');
    }

    $stmt = $pdo->prepare("
        INSERT INTO tbl_user_genetic_items (
            user_id,
            catalog_id,
            trait_type,
            trait_value,
            current_level,
            upgrade_status,
            acquired_method,
            is_listed_for_sale,
            created_at,
            updated_at,
            last_owner_user_id
        ) VALUES (?, ?, ?, ?, 1, 'idle', ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, ?)
    ");
    $stmt->execute([
        $userId,
        $catalogId,
        $traitType,
        $traitValue,
        $sourceTag,
        $userId,
    ]);

    $geneticItemId = (int)$pdo->lastInsertId();

    insert_claim_genetic_item_history(
        $pdo,
        $geneticItemId,
        $userId,
        'fitness_milestone_grant',
        [
            'catalog_id' => $catalogId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'acquired_method' => $sourceTag,
        ]
    );

    return [
        'genetic_item_id' => $geneticItemId,
        'catalog_id' => $catalogId,
        'trait_type' => $traitType,
        'trait_value' => $traitValue,
        'display_title' => $displayTitle,
        'current_level' => 1,
        'upgrade_status' => 'idle',
        'source' => $sourceTag,
    ];
}

/**
 * Deliver one Fitness Journey reward.
 */
function deliver_fitness_trait_milestone_reward(PDO $pdo, string $userId, array $reward): array {
    $rewardType = (string)($reward['reward_type'] ?? '');
    $referenceId = (int)($reward['reward_reference_id'] ?? 0);

    if ($rewardType === 'store_item') {
        $storeItem = fetch_claim_store_item_row($pdo, $referenceId);
        if (!$storeItem) {
            throw new RuntimeException('Fitness milestone store item could not be loaded');
        }

        $deliveryPayload = grant_claim_store_item_to_user($pdo, $userId, $storeItem, 1, 'fitness_milestone');

        return [
            'reward_type' => 'store_item',
            'reward_reference_id' => (int)$deliveryPayload['item_id'],
            'reward_amount' => 0,
            'reward_currency' => 'ITEM',
            'reward_title' => (string)$deliveryPayload['item_name'],
            'reward_description' => (string)($reward['description'] ?? ''),
            'delivery_payload' => $deliveryPayload,
            'fallback_used' => false,
        ];
    }

    if ($rewardType === 'genetic_trait') {
        $catalogRow = fetch_claim_genetic_catalog_row($pdo, $referenceId);
        if (!$catalogRow) {
            throw new RuntimeException('Fitness milestone Genetic Item could not be loaded');
        }

        $traitType = trim((string)($catalogRow['trait_type'] ?? ''));
        $traitValue = trim((string)($catalogRow['trait_value'] ?? ''));
        $displayTitle = trim((string)($catalogRow['display_title'] ?? $traitValue));

        if (user_owns_claim_genetic_trait($pdo, $userId, $traitType, $traitValue)) {
            $fallbackAmount = max(0, (int)($reward['reward_amount'] ?? 0));

            insert_claim_dspoinc_change(
                $pdo,
                $userId,
                $fallbackAmount,
                LAB_MILESTONE_GAME_KEY,
                'fitness_milestone_fallback:' . (string)$reward['milestone_key']
            );

            insert_claim_score_adjustment(
                $pdo,
                $userId,
                LAB_MILESTONE_SYSTEM_ACTOR,
                $fallbackAmount,
                'add',
                'Fitness Journey fallback DSPOINC: ' . $displayTitle
            );

            return [
                'reward_type' => 'dspoinc_fallback',
                'reward_reference_id' => $referenceId,
                'reward_amount' => $fallbackAmount,
                'reward_currency' => 'DSPOINC',
                'reward_title' => number_format($fallbackAmount) . ' DSPOINC Fallback',
                'reward_description' => 'Gun Special was already in your Lab inventory, so you received DSPOINC instead.',
                'delivery_payload' => [
                    'catalog_id' => $referenceId,
                    'display_title' => $displayTitle,
                    'fallback_amount' => $fallbackAmount,
                ],
                'fallback_used' => true,
            ];
        }

        $deliveryPayload = grant_claim_genetic_trait_to_user($pdo, $userId, $catalogRow, 'fitness_milestone');

        return [
            'reward_type' => 'genetic_trait',
            'reward_reference_id' => (int)$deliveryPayload['catalog_id'],
            'reward_amount' => 0,
            'reward_currency' => 'GENETIC_ITEM',
            'reward_title' => (string)$deliveryPayload['display_title'],
            'reward_description' => (string)($reward['description'] ?? ''),
            'delivery_payload' => $deliveryPayload,
            'fallback_used' => false,
        ];
    }

    throw new RuntimeException('Unsupported Fitness milestone reward type');
}

/**
 * Return true when a table exists.
 */
function claim_table_exists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM sqlite_master
        WHERE type = 'table'
          AND name = ?
        LIMIT 1
    ");
    $stmt->execute([$tableName]);

    return (bool)$stmt->fetchColumn();
}

/**
 * Return total DSPOINC ledger balance.
 */
function get_claim_total_dspoinc(PDO $pdo, string $userId): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score), 0)
        FROM tbl_user_scores
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    return (int)$stmt->fetchColumn();
}

/**
 * Return frozen staking balance when the staking table exists.
 */
function get_claim_frozen_dspoinc(PDO $pdo, string $userId): int {
    if (!claim_table_exists($pdo, 'tbl_dspoinc_stakes')) {
        return 0;
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0)
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = 'active'
    ");
    $stmt->execute([$userId]);

    return (int)$stmt->fetchColumn();
}

/**
 * Return spendable DSPOINC balance.
 */
function get_claim_available_dspoinc(PDO $pdo, string $userId): int {
    $total = get_claim_total_dspoinc($pdo, $userId);
    $frozen = get_claim_frozen_dspoinc($pdo, $userId);

    return max(0, $total - $frozen);
}

try {
    $request = get_claim_milestone_request_data();

    $userId = get_active_claim_milestone_user_id();
    if ($userId === '') {
        milestone_json_response([
            'success' => false,
            'error' => 'Discord login required',
        ], 401);
    }

    $tokenId = trim((string)($request['token_id'] ?? ''));
    $collection = trim((string)($request['collection'] ?? 'genesis')) ?: 'genesis';
    $milestoneKey = trim((string)($request['milestone_key'] ?? ''));

    if ($tokenId === '') {
        milestone_json_response([
            'success' => false,
            'error' => 'token_id is required',
        ], 400);
    }

    if ($collection !== 'genesis') {
        milestone_json_response([
            'success' => false,
            'error' => 'Only Genesis collection is supported for ability milestones',
        ], 400);
    }

    if ($milestoneKey === '') {
        milestone_json_response([
            'success' => false,
            'error' => 'milestone_key is required',
        ], 400);
    }

        $reward = resolve_weapon_milestone_reward($milestoneKey);
    $isFitnessTraitMilestone = false;

    if (!$reward) {
        $reward = resolve_fitness_trait_milestone_reward($milestoneKey);
        $isFitnessTraitMilestone = $reward !== null;
    }

    if (!$reward) {
        milestone_json_response([
            'success' => false,
            'error' => 'Unknown or unsupported milestone reward',
        ], 400);
    }

    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if (!user_controls_claim_genesis_mouse($pdo, $userId, $tokenId, $collection)) {
        milestone_json_response([
            'success' => false,
            'error' => 'You do not control this verified Genesis mouse',
        ], 403);
    }

    $highestGenesisTraitLevel = fetch_claim_highest_genesis_trait_level($pdo, $tokenId, $collection);
    $currentLevel = 0;
    $requiredLevel = (int)$reward['required_level'];

    if ($isFitnessTraitMilestone) {
        $currentLevel = $highestGenesisTraitLevel;

        if ($highestGenesisTraitLevel < $requiredLevel) {
            milestone_json_response([
                'success' => false,
                'error' => 'This Fitness Journey milestone is not unlocked yet',
                'current_level' => $highestGenesisTraitLevel,
                'required_trait_level' => $requiredLevel,
                'highest_genesis_trait_level' => $highestGenesisTraitLevel,
            ], 409);
        }
    } else {
        if (!is_valid_nft_ability_key('Weapons', (string)$reward['ability_key'])) {
            milestone_json_response([
                'success' => false,
                'error' => 'Invalid Weapon ability key',
            ], 400);
        }

        $abilityRow = fetch_claim_weapon_ability_row($pdo, $tokenId, $collection, (string)$reward['ability_key']);
        if (!$abilityRow) {
            milestone_json_response([
                'success' => false,
                'error' => 'Weapon ability row not found for this Genesis mouse',
            ], 404);
        }

        $weaponUnlockRequiredTraitLevel = 20;

        if ($highestGenesisTraitLevel < $weaponUnlockRequiredTraitLevel) {
            milestone_json_response([
                'success' => false,
                'error' => 'Weapons are not unlocked for this Genesis mouse yet',
                'highest_genesis_trait_level' => $highestGenesisTraitLevel,
                'required_trait_level' => $weaponUnlockRequiredTraitLevel,
            ], 409);
        }

        $currentLevel = (int)($abilityRow['current_level'] ?? 0);

        if ($currentLevel < $requiredLevel) {
            milestone_json_response([
                'success' => false,
                'error' => 'This Weapon Ability milestone is not unlocked yet',
                'current_level' => $currentLevel,
                'required_level' => $requiredLevel,
                'highest_genesis_trait_level' => $highestGenesisTraitLevel,
                'weapon_unlock_required_trait_level' => $weaponUnlockRequiredTraitLevel,
            ], 409);
        }
    }

    if (milestone_reward_already_claimed($pdo, $userId, $tokenId, $collection, $milestoneKey)) {
        milestone_json_response([
            'success' => false,
            'already_claimed' => true,
            'error' => $isFitnessTraitMilestone
                ? 'This Fitness Journey reward chest was already claimed'
                : 'This Weapon Ability reward chest was already claimed',
            'milestone_key' => $milestoneKey,
        ], 409);
    }

    $pdo->beginTransaction();

    try {
        /**
         * Insert the unique claim first inside the transaction.
         * If a double-click/race happens, the UNIQUE constraint prevents a
         * second reward from being created.
         */
        $deliveryPayload = [];

        if ($isFitnessTraitMilestone) {
            $deliveryPayload = deliver_fitness_trait_milestone_reward($pdo, $userId, $reward);
            $rewardId = insert_lab_milestone_reward_claim($pdo, $userId, $tokenId, $collection, $reward, $deliveryPayload);
        } else {
            $rewardAmount = (int)$reward['reward_amount'];
            $ledgerSource = 'weapon_ability_milestone:' . $milestoneKey;
            $reason = sprintf(
                '%s claimed for Genesis #%s (%s level %d)',
                (string)$reward['title'],
                $tokenId,
                (string)$reward['ability_key'],
                $requiredLevel
            );

            $deliveryPayload = [
                'reward_type' => 'dspoinc',
                'reward_reference_id' => null,
                'reward_amount' => $rewardAmount,
                'reward_currency' => 'DSPOINC',
                'reward_title' => (string)$reward['title'],
                'reward_description' => (string)$reward['description'],
                'delivery_payload' => [],
                'fallback_used' => false,
            ];

            $rewardId = insert_lab_milestone_reward_claim($pdo, $userId, $tokenId, $collection, $reward, $deliveryPayload);

            insert_claim_dspoinc_change(
                $pdo,
                $userId,
                $rewardAmount,
                LAB_MILESTONE_GAME_KEY,
                $ledgerSource
            );

            insert_claim_score_adjustment(
                $pdo,
                $userId,
                LAB_MILESTONE_SYSTEM_ACTOR,
                $rewardAmount,
                'add',
                $reason
            );
        }

        $pdo->commit();
    } catch (Throwable $inner) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = $inner->getMessage();
        if (stripos($message, 'UNIQUE') !== false || stripos($message, 'constraint') !== false) {
            milestone_json_response([
                'success' => false,
                'already_claimed' => true,
                'error' => $isFitnessTraitMilestone
                    ? 'This Fitness Journey reward chest was already claimed'
                    : 'This Weapon Ability reward chest was already claimed',
                'milestone_key' => $milestoneKey,
            ], 409);
        }

        throw $inner;
    }

    $totalDspoinc = get_claim_total_dspoinc($pdo, $userId);
    $availableDspoinc = get_claim_available_dspoinc($pdo, $userId);

    $responseRewardType = (string)($deliveryPayload['reward_type'] ?? ($reward['reward_type'] ?? 'dspoinc'));
    $responseRewardTitle = (string)($deliveryPayload['reward_title'] ?? $reward['title']);
    $responseRewardAmount = (int)($deliveryPayload['reward_amount'] ?? $reward['reward_amount']);
    $responseRewardCurrency = (string)($deliveryPayload['reward_currency'] ?? $reward['reward_currency']);

    milestone_json_response([
        'success' => true,
        'claimed' => true,
        'reward_id' => $rewardId,
        'user_id' => $userId,
        'token_id' => $tokenId,
        'collection' => $collection,
        'milestone_key' => $milestoneKey,
        'source' => $reward['source'],
        'ability_category' => $reward['ability_category'],
        'ability_key' => $reward['ability_key'],
        'current_level' => $currentLevel,
        'required_level' => $requiredLevel,
        'required_trait_level' => $isFitnessTraitMilestone ? $requiredLevel : null,
        'highest_genesis_trait_level' => $highestGenesisTraitLevel,
        'reward_title' => $responseRewardTitle,
        'reward_description' => (string)($deliveryPayload['reward_description'] ?? $reward['description']),
        'reward_type' => $responseRewardType,
        'reward_reference_id' => $deliveryPayload['reward_reference_id'] ?? $reward['reward_reference_id'] ?? null,
        'reward_amount' => $responseRewardAmount,
        'reward_currency' => $responseRewardCurrency,
        'fallback_used' => (bool)($deliveryPayload['fallback_used'] ?? false),
        'delivery_payload' => $deliveryPayload['delivery_payload'] ?? [],
        'total_dspoinc' => $totalDspoinc,
        'available_dspoinc' => $availableDspoinc,
        'new_available_dspoinc' => $availableDspoinc,
        'chest' => [
            'type' => $isFitnessTraitMilestone ? 'fitness_journey' : 'weapon_ability',
            'title' => $isFitnessTraitMilestone ? $reward['title'] : $responseRewardTitle,
            'subtitle' => $isFitnessTraitMilestone ? 'Fitness Journey Reward Chest' : 'Weapon Ability Milestone Reward',
            'message' => $isFitnessTraitMilestone
                ? 'Your Genesis mouse opened a Fitness Journey Reward Chest!'
                : 'Your Genesis mouse opened a Weapon Ability Reward Chest!',
            'reward_type' => $responseRewardType,
            'reward_title' => $responseRewardTitle,
            'amount' => $responseRewardAmount,
            'currency' => $responseRewardCurrency,
            'icon' => $isFitnessTraitMilestone ? '💪' : '⚔️',
            'fallback_used' => (bool)($deliveryPayload['fallback_used'] ?? false),
        ],
    ]);
} catch (Throwable $e) {
    error_log('❌ Ability milestone reward claim failed: ' . $e->getMessage());

    milestone_json_response([
        'success' => false,
        'error' => 'Failed to claim ability milestone reward',
    ], 500);
}