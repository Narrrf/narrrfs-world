<?php
/** Disposable transaction tests. Never opens XAMPP, Render, or /data databases. */
declare(strict_types=1);
require_once __DIR__ . '/api/lib/giveaway-genetic-item-delivery-v1.php';

if (!extension_loaded('pdo_sqlite')) { fwrite(STDERR, "FAIL: pdo_sqlite extension required\n"); exit(1); }
function genetic_test_assert(bool $value, string $message): void { if (!$value) throw new RuntimeException('FAIL: ' . $message); echo 'PASS: ' . $message . PHP_EOL; }
function genetic_test_count(PDO $db, string $sql): int { return (int) $db->query($sql)->fetchColumn(); }
function genetic_test_expect(callable $action, string $code): void { try { $action(); } catch (GiveawayGeneticDeliveryV1Error $error) { genetic_test_assert($error->getMessage() === $code, 'Reject ' . $code); return; } throw new RuntimeException('FAIL: expected ' . $code); }

$path = tempnam(sys_get_temp_dir(), 'giveaway-genetic-v1-');
if (!is_string($path)) throw new RuntimeException('Could not create disposable database');
try {
    $db = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec(<<<'SQL'
CREATE TABLE tbl_giveaways (giveaway_id TEXT PRIMARY KEY,status TEXT NOT NULL,reward_type TEXT NOT NULL,reward_reference_id INTEGER,reward_quantity INTEGER,reward_snapshot_title TEXT,prize TEXT NOT NULL,fallback_dspoinc_enabled INTEGER DEFAULT 0,fallback_dspoinc_min INTEGER,fallback_dspoinc_max INTEGER);
CREATE TABLE tbl_giveaway_winners (id INTEGER PRIMARY KEY,giveaway_id TEXT NOT NULL,user_id TEXT NOT NULL,username TEXT NOT NULL,position INTEGER NOT NULL,delivery_status TEXT,delivered_at TEXT,delivery_payload_json TEXT);
CREATE TABLE tbl_genetic_trait_catalog (catalog_id INTEGER PRIMARY KEY,display_title TEXT,trait_type TEXT,trait_value TEXT,is_active INTEGER NOT NULL,is_visible INTEGER DEFAULT 1);
CREATE TABLE tbl_user_genetic_items (genetic_item_id INTEGER PRIMARY KEY,user_id TEXT NOT NULL,catalog_id INTEGER NOT NULL,trait_type TEXT NOT NULL,trait_value TEXT NOT NULL,current_level INTEGER,upgrade_status TEXT,acquired_method TEXT,is_listed_for_sale INTEGER,created_at TEXT,updated_at TEXT);
CREATE TABLE tbl_genetic_item_history (history_id INTEGER PRIMARY KEY,genetic_item_id INTEGER,listing_id INTEGER,user_id TEXT NOT NULL,action_type TEXT NOT NULL,old_value_json TEXT NOT NULL,new_value_json TEXT NOT NULL,admin_user_id TEXT,created_at TEXT NOT NULL);
CREATE TABLE tbl_user_scores (id INTEGER PRIMARY KEY,user_id TEXT,game TEXT,score INTEGER,timestamp TEXT,source TEXT);
CREATE TABLE tbl_score_adjustments (id INTEGER PRIMARY KEY,user_id TEXT NOT NULL,admin_id TEXT NOT NULL,amount INTEGER NOT NULL,action TEXT NOT NULL,reason TEXT NOT NULL,timestamp TEXT);
INSERT INTO tbl_genetic_trait_catalog VALUES (49,'Angel Wings','Accessories','Angel Wings',1,1);
INSERT INTO tbl_giveaways VALUES ('giveaway_1234567890123_abcdefghi','ended','genetic_trait',49,1,'Angel Wings','Angel Wings',0,NULL,NULL);
INSERT INTO tbl_giveaway_winners (giveaway_id,user_id,username,position,delivery_status) VALUES ('giveaway_1234567890123_abcdefghi','111111111111111111','winner-a',1,'pending');
INSERT INTO tbl_giveaways VALUES ('giveaway_1234567890124_abcdefghi','ended','genetic_trait',49,1,'Angel Wings','Angel Wings',1,500,500);
INSERT INTO tbl_giveaway_winners (giveaway_id,user_id,username,position,delivery_status) VALUES ('giveaway_1234567890124_abcdefghi','222222222222222222','winner-b',1,'pending');
INSERT INTO tbl_user_genetic_items VALUES (10,'222222222222222222',49,'Accessories','Angel Wings',1,'idle','seed',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),(11,'222222222222222222',49,'Accessories','Angel Wings',1,'idle','seed',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);
INSERT INTO tbl_giveaways VALUES ('giveaway_1234567890125_abcdefghi','ended','genetic_trait',49,1,'Angel Wings','Angel Wings',0,NULL,NULL);
INSERT INTO tbl_giveaway_winners (giveaway_id,user_id,username,position,delivery_status) VALUES ('giveaway_1234567890125_abcdefghi','333333333333333333','winner-c',1,'pending');
INSERT INTO tbl_user_genetic_items VALUES (12,'333333333333333333',49,'Accessories','Angel Wings',1,'idle','seed',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),(13,'333333333333333333',49,'Accessories','Angel Wings',1,'idle','seed',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);
SQL);
    $first = giveaway_genetic_delivery_v1($db, 'giveaway_1234567890123_abcdefghi', '111111111111111111');
    genetic_test_assert($first['success'] && !$first['already_delivered'], 'Fresh Genetic Item grant succeeded');
    genetic_test_assert(genetic_test_count($db, "SELECT COUNT(*) FROM tbl_user_genetic_items WHERE user_id='111111111111111111'") === 1, 'One Genetic Item inserted');
    genetic_test_assert(genetic_test_count($db, "SELECT COUNT(*) FROM tbl_genetic_item_history WHERE user_id='111111111111111111' AND action_type='giveaway_grant'") === 1, 'Paired Genetic Item history inserted');
    $replay = giveaway_genetic_delivery_v1($db, 'giveaway_1234567890123_abcdefghi', '111111111111111111');
    genetic_test_assert($replay['already_delivered'], 'Receipt replay is idempotent');
    genetic_test_assert(genetic_test_count($db, "SELECT COUNT(*) FROM tbl_user_genetic_items WHERE user_id='111111111111111111'") === 1, 'Replay did not add a Genetic Item');
    $fallback = giveaway_genetic_delivery_v1($db, 'giveaway_1234567890124_abcdefghi', '222222222222222222');
    genetic_test_assert($fallback['used_fallback_dspoinc'] && $fallback['fallback_dspoinc_amount'] === 500, 'Two-copy cap used configured fallback');
    genetic_test_assert(genetic_test_count($db, "SELECT COUNT(*) FROM tbl_user_scores WHERE user_id='222222222222222222' AND score=500") === 1, 'Fallback score ledger inserted');
    genetic_test_assert(genetic_test_count($db, "SELECT COUNT(*) FROM tbl_score_adjustments WHERE user_id='222222222222222222' AND amount=500") === 1, 'Fallback audit ledger inserted');
    genetic_test_assert(giveaway_genetic_delivery_v1($db, 'giveaway_1234567890124_abcdefghi', '222222222222222222')['already_delivered'], 'Fallback replay is idempotent');
    $review = giveaway_genetic_delivery_v1($db, 'giveaway_1234567890125_abcdefghi', '333333333333333333');
    genetic_test_assert($review['manual_review'], 'Two-copy cap without fallback enters manual review');
    genetic_test_assert(genetic_test_count($db, "SELECT COUNT(*) FROM tbl_user_genetic_items WHERE user_id='333333333333333333'") === 2, 'Manual review did not grant another item');
    genetic_test_expect(fn() => giveaway_genetic_delivery_v1($db, 'invalid', '111111111111111111'), 'invalid_giveaway_or_winner_id');
    genetic_test_assert(!giveaway_genetic_delivery_v1_is_authorized(str_repeat('a', 32), 'Bearer short'), 'Reject malformed authorization');
    genetic_test_assert(!giveaway_genetic_delivery_v1_is_authorized(str_repeat('a', 32), 'Bearer ' . str_repeat('b', 32)), 'Reject unauthorized service secret');
    genetic_test_assert(giveaway_genetic_delivery_v1_is_authorized(str_repeat('a', 32), 'Bearer ' . str_repeat('a', 32)), 'Accept valid authorization shape');
    genetic_test_assert((string) $db->query('PRAGMA quick_check')->fetchColumn() === 'ok', 'Disposable SQLite quick_check ok');
    echo "PASS: Giveaway Genetic Item Delivery V1 disposable tests\n";
} finally { unset($db); @unlink($path); }
