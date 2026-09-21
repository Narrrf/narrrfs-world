<?php
/**
 * Focused local-only regression test for Genetic Item Multi-Upgrade Slots V1.
 * It uses a disposable copy of the local database and never touches player data.
 */

require_once __DIR__ . '/api/user/genetic-upgrade-slot-helpers.php';

function assert_same($expected, $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

$sourceDb = __DIR__ . '/db/narrrf_world.sqlite';
$testDb = tempnam(sys_get_temp_dir(), 'narrrfs-genetic-slots-');

if ($testDb === false || !copy($sourceDb, $testDb)) {
    throw new RuntimeException('Unable to create disposable Genetic slot test database.');
}

try {
    $pdo = new PDO('sqlite:' . $testDb);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $catalog = $pdo->query('SELECT catalog_id, trait_type, trait_value FROM tbl_genetic_trait_catalog LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if (!$catalog) {
        throw new RuntimeException('No catalog row is available for the focused test.');
    }

    $userId = 'local-genetic-upgrade-slots-v1-test';
    $pdo->prepare('INSERT OR REPLACE INTO tbl_users (discord_id, username) VALUES (?, ?)')->execute([$userId, 'Genetic Slots Test']);
    $insertItem = $pdo->prepare(
        "INSERT INTO tbl_user_genetic_items (user_id, catalog_id, trait_type, trait_value, current_level, upgrade_status, is_listed_for_sale)
         VALUES (?, ?, ?, ?, 1, ?, 0)"
    );

    $insertItem->execute([$userId, $catalog['catalog_id'], $catalog['trait_type'], $catalog['trait_value'], 'idle']);
    $idleId = (int)$pdo->lastInsertId();

    assert_same(['limit' => 1, 'active' => 0, 'available' => 1], get_user_genetic_upgrade_slot_state($pdo, $userId), 'Missing entitlement must default to one slot.');

    $insertItem->execute([$userId, $catalog['catalog_id'], $catalog['trait_type'], $catalog['trait_value'], 'upgrading']);
    $upgradingId = (int)$pdo->lastInsertId();
    assert_same(['limit' => 1, 'active' => 1, 'available' => 0], get_user_genetic_upgrade_slot_state($pdo, $userId), 'An upgrading item must occupy the default slot.');
    assert_same(0, count_user_active_genetic_upgrades($pdo, $userId, $upgradingId), 'Exclude item behavior must remain available.');

    $pdo->prepare("INSERT INTO tbl_user_genetic_upgrade_slots (user_id, upgrade_slots, updated_by) VALUES (?, 2, 'test')")->execute([$userId]);
    assert_same(['limit' => 2, 'active' => 1, 'available' => 1], get_user_genetic_upgrade_slot_state($pdo, $userId), 'Two-slot entitlement must allow a second occupied slot.');

    $insertItem->execute([$userId, $catalog['catalog_id'], $catalog['trait_type'], $catalog['trait_value'], 'ready_to_claim']);
    $readyId = (int)$pdo->lastInsertId();
    assert_same(['limit' => 2, 'active' => 2, 'available' => 0], get_user_genetic_upgrade_slot_state($pdo, $userId), 'Ready-to-claim must occupy a slot.');

    $pdo->prepare("UPDATE tbl_user_genetic_upgrade_slots SET upgrade_slots = 3 WHERE user_id = ?")->execute([$userId]);
    $insertItem->execute([$userId, $catalog['catalog_id'], $catalog['trait_type'], $catalog['trait_value'], 'upgrading']);
    assert_same(['limit' => 3, 'active' => 3, 'available' => 0], get_user_genetic_upgrade_slot_state($pdo, $userId), 'Three-slot entitlement must cap at three occupied items.');

    $beforeReduction = $pdo->query("SELECT genetic_item_id, upgrade_status FROM tbl_user_genetic_items WHERE user_id = '$userId' ORDER BY genetic_item_id")->fetchAll(PDO::FETCH_ASSOC);
    $pdo->prepare("UPDATE tbl_user_genetic_upgrade_slots SET upgrade_slots = 1 WHERE user_id = ?")->execute([$userId]);
    assert_same(['limit' => 1, 'active' => 3, 'available' => 0], get_user_genetic_upgrade_slot_state($pdo, $userId), 'Reducing an entitlement must block new starts without changing occupied items.');
    $afterReduction = $pdo->query("SELECT genetic_item_id, upgrade_status FROM tbl_user_genetic_items WHERE user_id = '$userId' ORDER BY genetic_item_id")->fetchAll(PDO::FETCH_ASSOC);
    assert_same($beforeReduction, $afterReduction, 'Reducing entitlement must not mutate existing Genetic Item states.');

    foreach ([0, 4] as $invalidLimit) {
        try {
            $pdo->prepare("UPDATE tbl_user_genetic_upgrade_slots SET upgrade_slots = ? WHERE user_id = ?")->execute([$invalidLimit, $userId]);
            throw new RuntimeException('Schema accepted invalid slot limit ' . $invalidLimit . '.');
        } catch (PDOException $expected) {
        }
    }

    $startSource = file_get_contents(__DIR__ . '/api/user/start-genetic-item-upgrade.php');
    assert_same(2, substr_count($startSource, "\$upgradeSlots['active'] >= \$upgradeSlots['limit']"), 'Both start-upgrade guard locations must use capacity checks.');
    assert_same(0, substr_count($startSource, 'user_has_active_genetic_upgrade'), 'Legacy one-slot helper must not remain in the start API.');
    foreach ([
        "if (\$currentLevel >= GENETIC_ITEM_MAX_LEVEL)",
        'if ($isListedForSale)',
        "if (\$upgradeStatus === 'upgrading')",
        "if (\$upgradeStatus === 'ready_to_claim')",
        "if (\$upgradeStatus !== 'idle')",
        "'upgrade_started'"
    ] as $requiredGuard) {
        if (strpos($startSource, $requiredGuard) === false) {
            throw new RuntimeException('Existing start-upgrade safeguard is missing: ' . $requiredGuard);
        }
    }

    $adminSource = file_get_contents(__DIR__ . '/api/admin/genetic-upgrade-slots.php');
    foreach (['require_genetic_slot_admin()', '!is_int($rawSlots)', "'genetic_upgrade_slots_changed'"] as $requiredAdminGuard) {
        if (strpos($adminSource, $requiredAdminGuard) === false) {
            throw new RuntimeException('Admin slot-management safeguard is missing: ' . $requiredAdminGuard);
        }
    }

    echo "PASS: Genetic Item Multi-Upgrade Slots V1 focused local test\n";
} finally {
    unset($insertItem, $catalog, $beforeReduction, $afterReduction);
    if (isset($pdo)) {
        $pdo = null;
    }
    if (isset($testDb) && is_file($testDb)) {
        unlink($testDb);
    }
}
