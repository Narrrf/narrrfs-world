<?php
/**
 * Disposable transaction tests for Giveaway Store Item Delivery V1.
 * Never opens the XAMPP, Render runtime, or /data databases.
 */
declare(strict_types=1);
require_once __DIR__ . '/api/lib/giveaway-store-item-delivery-v1.php';

if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "FAIL: pdo_sqlite extension required\n");
    exit(1);
}

function giveaway_test_assert(bool $passed, string $description): void
{
    if (!$passed) {
        throw new RuntimeException('FAIL: ' . $description);
    }
    echo 'PASS: ' . $description . PHP_EOL;
}

function giveaway_test_count(PDO $database, string $sql): int
{
    return (int) $database->query($sql)->fetchColumn();
}

function giveaway_test_expect_error(callable $action, string $expectedCode): void
{
    try {
        $action();
    } catch (GiveawayStoreDeliveryV1Error $error) {
        giveaway_test_assert($error->getMessage() === $expectedCode, 'Reject ' . $expectedCode);
        return;
    }
    throw new RuntimeException('FAIL: Expected ' . $expectedCode);
}

$temporaryPath = tempnam(sys_get_temp_dir(), 'giveaway-v1-test-');
if (!is_string($temporaryPath)) {
    throw new RuntimeException('Could not create disposable test database');
}

try {
    $database = new PDO('sqlite:' . $temporaryPath);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $database->exec('PRAGMA foreign_keys = ON');
    $database->exec(<<<'SQL'
CREATE TABLE tbl_giveaways (
 giveaway_id TEXT PRIMARY KEY,
 status TEXT NOT NULL,
 reward_type TEXT NOT NULL,
 reward_reference_id INTEGER,
 reward_quantity INTEGER,
 reward_snapshot_title TEXT,
 prize TEXT NOT NULL
);
CREATE TABLE tbl_giveaway_winners (
 id INTEGER PRIMARY KEY,
 giveaway_id TEXT NOT NULL,
 user_id TEXT NOT NULL,
 username TEXT NOT NULL,
 position INTEGER NOT NULL,
 delivery_status TEXT,
 delivered_at TEXT,
 delivery_payload_json TEXT
);
CREATE TABLE tbl_store_items (
 item_id INTEGER PRIMARY KEY,
 item_name TEXT NOT NULL,
 description TEXT,
 price INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE tbl_user_inventory (
 inventory_id INTEGER PRIMARY KEY,
 user_id TEXT NOT NULL,
 item_id INTEGER NOT NULL,
 quantity INTEGER DEFAULT 1,
 acquired_at TEXT DEFAULT CURRENT_TIMESTAMP,
 last_used_at TEXT
);
INSERT INTO tbl_store_items (item_id, item_name) VALUES (19, 'Mad SkullZ NFT SOL'), (38, 'Narrrf Genesis NFT');
INSERT INTO tbl_giveaways VALUES ('giveaway_1234567890123_abcdefghi','ended','store_item',19,1,'Mad SkullZ NFT','Mad SkullZ NFT');
INSERT INTO tbl_giveaway_winners (giveaway_id,user_id,username,position,delivery_status)
 VALUES ('giveaway_1234567890123_abcdefghi','111111111111111111','test-a',1,'pending');
INSERT INTO tbl_giveaways VALUES ('giveaway_1234567890124_abcdefghi','ended','store_item',38,1,'Genesis NFT','Genesis NFT');
INSERT INTO tbl_giveaway_winners (giveaway_id,user_id,username,position,delivery_status)
 VALUES ('giveaway_1234567890124_abcdefghi','222222222222222222','test-b',1,'pending');
INSERT INTO tbl_user_inventory (user_id,item_id,quantity) VALUES ('222222222222222222',38,3);
INSERT INTO tbl_giveaways VALUES ('giveaway_1234567890125_abcdefghi','active','store_item',19,1,'Active','Active');
INSERT INTO tbl_giveaway_winners (giveaway_id,user_id,username,position,delivery_status)
 VALUES ('giveaway_1234567890125_abcdefghi','333333333333333333','test-c',1,'pending');
INSERT INTO tbl_giveaways VALUES ('giveaway_1234567890126_abcdefghi','ended','genetic_trait',19,1,'Genetic','Genetic');
INSERT INTO tbl_giveaway_winners (giveaway_id,user_id,username,position,delivery_status)
 VALUES ('giveaway_1234567890126_abcdefghi','444444444444444444','test-d',1,'pending');
SQL);

    $one = giveaway_store_delivery_v1($database, 'giveaway_1234567890123_abcdefghi', '111111111111111111');
    giveaway_test_assert($one['success'] && !$one['already_delivered'], 'First store-item grant succeeded');
    giveaway_test_assert($one['delivery_payload']['item_id'] === 19, 'Server selected stored catalog item 19');
    giveaway_test_assert(giveaway_test_count($database, "SELECT quantity FROM tbl_user_inventory WHERE user_id='111111111111111111' AND item_id=19") === 1, 'Initial inventory quantity exactly 1');
    giveaway_test_assert(giveaway_test_count($database, "SELECT COUNT(*) FROM tbl_giveaway_winners WHERE delivery_status='delivered'") === 1, 'Winner receipt persisted');

    $replay = giveaway_store_delivery_v1($database, 'giveaway_1234567890123_abcdefghi', '111111111111111111');
    giveaway_test_assert($replay['success'] && $replay['already_delivered'], 'Duplicate request returned saved receipt');
    giveaway_test_assert(giveaway_test_count($database, "SELECT quantity FROM tbl_user_inventory WHERE user_id='111111111111111111' AND item_id=19") === 1, 'Replay did not increase inventory');

    $existing = giveaway_store_delivery_v1($database, 'giveaway_1234567890124_abcdefghi', '222222222222222222');
    giveaway_test_assert($existing['delivery_payload']['total_quantity'] === 4, 'Existing inventory increased exactly 3 to 4');
    $existingReplay = giveaway_store_delivery_v1($database, 'giveaway_1234567890124_abcdefghi', '222222222222222222');
    giveaway_test_assert($existingReplay['already_delivered'], 'Existing-item replay idempotent');
    giveaway_test_assert(giveaway_test_count($database, "SELECT quantity FROM tbl_user_inventory WHERE user_id='222222222222222222' AND item_id=38") === 4, 'Existing inventory unchanged on replay');

    giveaway_test_expect_error(fn() => giveaway_store_delivery_v1($database, 'giveaway_1234567890123_abcdefghi', '999999999999999999'), 'winner_not_found');
    giveaway_test_expect_error(fn() => giveaway_store_delivery_v1($database, 'giveaway_1234567890125_abcdefghi', '333333333333333333'), 'giveaway_not_ended');
    giveaway_test_expect_error(fn() => giveaway_store_delivery_v1($database, 'giveaway_1234567890126_abcdefghi', '444444444444444444'), 'unsupported_reward_type');
    giveaway_test_expect_error(fn() => giveaway_store_delivery_v1($database, 'INVALID', '111111111111111111'), 'invalid_giveaway_or_winner_id');

    $database->exec(<<<'SQL'
INSERT INTO tbl_giveaways VALUES ('giveaway_1234567890127_abcdefghi','ended','store_item',19,1,'Rollback','Rollback');
INSERT INTO tbl_giveaway_winners (giveaway_id,user_id,username,position,delivery_status)
 VALUES ('giveaway_1234567890127_abcdefghi','555555555555555555','test-e',1,'pending');
CREATE TRIGGER giveaway_test_receipt_failure BEFORE UPDATE ON tbl_giveaway_winners
 WHEN NEW.giveaway_id='giveaway_1234567890127_abcdefghi'
 BEGIN SELECT RAISE(ABORT, 'simulated_receipt_failure'); END;
SQL);
    try {
        giveaway_store_delivery_v1($database, 'giveaway_1234567890127_abcdefghi', '555555555555555555');
        throw new RuntimeException('FAIL: Expected receipt-write rollback');
    } catch (PDOException $error) {
        giveaway_test_assert(str_contains($error->getMessage(), 'simulated_receipt_failure'), 'Simulated receipt failure occurred');
    }
    giveaway_test_assert(giveaway_test_count($database, "SELECT COUNT(*) FROM tbl_user_inventory WHERE user_id='555555555555555555'") === 0, 'Inventory insertion rolled back with receipt failure');
    giveaway_test_assert(giveaway_test_count($database, "SELECT COUNT(*) FROM tbl_giveaway_winners WHERE giveaway_id='giveaway_1234567890127_abcdefghi' AND delivery_status='pending'") === 1, 'Pending state preserved on rollback');
    giveaway_test_assert((string) $database->query('PRAGMA quick_check')->fetchColumn() === 'ok', 'Disposable SQLite quick_check ok');
    echo "PASS: Giveaway Store Item Delivery V1 disposable tests\n";
} finally {
    unset($database);
    @unlink($temporaryPath);
}
