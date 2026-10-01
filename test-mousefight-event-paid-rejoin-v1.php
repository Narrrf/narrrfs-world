<?php
/** Disposable SQLite coverage for paid Event leave → rejoin attempts. */
define('MOUSEFIGHT_ECONOMY_LIBRARY_ONLY', true);
require __DIR__ . '/api/discord/mousefight-economy.php';

$path = tempnam(sys_get_temp_dir(), 'mousefight-paid-rejoin-');
$db = new SQLite3($path);
$db->enableExceptions(true);

$db->exec('CREATE TABLE tbl_mousefights (fight_id TEXT PRIMARY KEY, created_by_user_id TEXT, title TEXT, mode TEXT, status TEXT, buy_in_dspoinc INTEGER, max_players INTEGER)');
$db->exec('CREATE TABLE tbl_mousefight_participants (id INTEGER PRIMARY KEY AUTOINCREMENT, fight_id TEXT, user_id TEXT, username TEXT, display_name TEXT, avatar_url TEXT, wallet TEXT, token_id TEXT, collection TEXT, custom_name TEXT, metadata_name TEXT, image_url TEXT, mouse_warrior_power INTEGER, trait_power INTEGER, ability_power INTEGER, genetic_support_power INTEGER, highest_trait_level INTEGER, fitness_unlocked INTEGER, inventory_enabled INTEGER, max_inventory_items INTEGER, status TEXT, wins INTEGER, losses INTEGER, kills INTEGER, current_round INTEGER, snapshot_json TEXT, joined_at TEXT)');
$db->exec('CREATE TABLE tbl_mousefight_dspoinc_burns (burn_id INTEGER PRIMARY KEY AUTOINCREMENT, fight_id TEXT, user_id TEXT, username TEXT, amount INTEGER, status TEXT, score_id INTEGER, adjustment_id INTEGER, burned_at TEXT, created_at TEXT, updated_at TEXT, refunded_at TEXT, refund_score_id INTEGER, refund_adjustment_id INTEGER)');
$db->exec('CREATE TABLE tbl_user_scores (score_id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT, game TEXT, score INTEGER, timestamp TEXT, source TEXT)');
$db->exec('CREATE TABLE tbl_score_adjustments (adjustment_id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT, admin_id TEXT, amount INTEGER, action TEXT, reason TEXT, timestamp TEXT)');
$db->exec('CREATE TABLE tbl_dspoinc_stakes (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT, amount INTEGER, status TEXT)');
$db->exec('CREATE TABLE tbl_mousefight_mouse_cooldowns (cooldown_id INTEGER PRIMARY KEY AUTOINCREMENT, token_id TEXT, collection TEXT, user_id TEXT, fight_id TEXT, cooldown_minutes INTEGER, cooldown_reason TEXT, started_at TEXT, expires_at TEXT, status TEXT)');

$passed = 0;
function rejoin_ok($condition, $label) { global $passed; if (!$condition) throw new RuntimeException("FAIL: $label"); $passed++; echo "PASS $label\n"; }
function rejoin_tx($db, $callback) { $db->exec('BEGIN IMMEDIATE'); try { $result = $callback(); $db->exec('COMMIT'); return $result; } catch (Throwable $error) { $db->exec('ROLLBACK'); throw $error; } }
function rejoin_fail($callback, $label) { try { $callback(); } catch (Throwable $error) { rejoin_ok(true, $label); return; } throw new RuntimeException("FAIL: $label did not reject"); }
function rejoin_payload() { return ['user_id' => 'user-1', 'username' => 'User', 'token_id' => 'token-1', 'collection' => 'genesis']; }
function rejoin_join($db) { return mousefight_economy_event_join($db, ['fight_id' => 'paid-rejoin', 'participant' => rejoin_payload()]); }
function rejoin_leave($db) { return mousefight_economy_event_leave($db, ['fight_id' => 'paid-rejoin', 'user_id' => 'user-1', 'token_id' => 'token-1', 'collection' => 'genesis']); }

$db->exec("INSERT INTO tbl_mousefights (fight_id, created_by_user_id, title, mode, status, buy_in_dspoinc, max_players) VALUES ('paid-rejoin', 'creator', 'Paid Event', 'admin_bracket_event', 'waiting', 20, 4)");
$db->exec("INSERT INTO tbl_user_scores (user_id, game, score, timestamp, source) VALUES ('user-1', 'mousefight', 100, datetime('now'), 'seed')");
/* Historical refunded attempt: preserved, but must not block a new paid entry. */
$db->exec("INSERT INTO tbl_mousefight_dspoinc_burns (fight_id, user_id, username, amount, status, refund_score_id, refund_adjustment_id, refunded_at) VALUES ('paid-rejoin', 'user-1', 'User', 20, 'refunded', 501, 601, datetime('now'))");

$firstJoin = rejoin_tx($db, fn() => rejoin_join($db));
rejoin_ok((int)$firstJoin['burn_id'] > 1, 'A refunded historical attempt permits a new paid entry');
rejoin_ok($db->querySingle("SELECT COUNT(*) FROM tbl_mousefight_participants WHERE fight_id = 'paid-rejoin'") === 1, 'B rejoin creates one participant');
rejoin_ok($db->querySingle("SELECT status FROM tbl_mousefight_dspoinc_burns WHERE burn_id = " . (int)$firstJoin['burn_id']) === 'burned', 'C rejoin creates a new active burn attempt');
rejoin_fail(fn() => rejoin_tx($db, fn() => rejoin_join($db)), 'D duplicate active entry remains blocked');

$firstLeave = rejoin_tx($db, fn() => rejoin_leave($db));
rejoin_ok($firstLeave['state'] === 'refunded' && (int)$firstLeave['refund_amount'] === 20, 'E leave refunds the current rejoin attempt');
rejoin_ok($db->querySingle("SELECT COUNT(*) FROM tbl_mousefight_dspoinc_burns WHERE fight_id = 'paid-rejoin' AND status = 'refunded'") === 2, 'F both attempts remain durable refunded history');

$secondJoin = rejoin_tx($db, fn() => rejoin_join($db));
rejoin_ok((int)$secondJoin['burn_id'] > (int)$firstJoin['burn_id'], 'G each later re-entry receives a distinct burn id');
$secondLeave = rejoin_tx($db, fn() => rejoin_leave($db));
rejoin_ok($secondLeave['state'] === 'refunded' && (int)$secondLeave['refund_amount'] === 20, 'H later leave refunds only its current attempt');
$replay = rejoin_tx($db, fn() => rejoin_leave($db));
rejoin_ok($replay['state'] === 'already_refunded' && (int)$replay['refund_score_id'] === (int)$secondLeave['refund_score_id'], 'I leave replay reports the newest durable refund receipt');
rejoin_ok($db->querySingle("SELECT COUNT(*) FROM tbl_user_scores WHERE user_id = 'user-1'") === 5, 'J each approved entry and leave produces one ledger row');

$db->close();
unlink($path);
echo "PASS total=$passed; disposable DB removed\n";
