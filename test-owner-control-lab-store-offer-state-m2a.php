<?php
declare(strict_types=1);

require_once __DIR__ . '/api/lib/owner-lab-store-offer-state-v1.php';
require_once __DIR__ . '/api/lib/mouse-lp-store-service.php';

function m2a_assert(bool $value, string $label): void { if (!$value) throw new RuntimeException('FAIL: ' . $label); }
function m2a_expect(callable $call, string $message, string $label): void {
    try { $call(); } catch (Throwable $error) { m2a_assert($error->getMessage() === $message, $label . ': ' . $error->getMessage()); return; }
    throw new RuntimeException('FAIL: ' . $label . ' did not fail');
}
function m2a_setup(string $path): void {
    $db = new SQLite3($path);
    $db->exec("CREATE TABLE tbl_mouse_lp_store_offers (offer_id INTEGER PRIMARY KEY, source_type TEXT NOT NULL, source_item_id INTEGER NOT NULL, lp_price INTEGER, dspoinc_price INTEGER, stock INTEGER NOT NULL, is_active INTEGER NOT NULL, owner_control_version INTEGER NOT NULL, updated_by TEXT, updated_at TEXT);");
    $db->exec("CREATE TABLE tbl_store_items (item_id INTEGER PRIMARY KEY, item_name TEXT NOT NULL, image_url TEXT, is_active INTEGER NOT NULL);");
    $db->exec("CREATE TABLE tbl_mouse_lp_store_purchases (purchase_id INTEGER PRIMARY KEY, purchaser_user_id TEXT, idempotency_key TEXT, request_fingerprint TEXT, offer_id INTEGER, source_type TEXT, source_item_id INTEGER, item_title_snapshot TEXT, payment_method TEXT, amount_paid INTEGER, selected_token_id TEXT, selected_collection TEXT, mouse_name_snapshot TEXT, delivery_reference_id INTEGER, completed_at TEXT);");
    $db->exec("CREATE TABLE tbl_user_scores (user_id TEXT, score INTEGER, game TEXT, source TEXT, timestamp TEXT);");
    $db->exec("CREATE TABLE tbl_dspoinc_stakes (user_id TEXT, amount INTEGER, status TEXT);");
    $db->exec("CREATE TABLE tbl_owner_control_lab_store_offer_state_audit (audit_id INTEGER PRIMARY KEY AUTOINCREMENT, offer_id INTEGER, actor_subject TEXT, prior_version INTEGER, resulting_version INTEGER, prior_is_active INTEGER, resulting_is_active INTEGER, request_hash TEXT, created_at TEXT);");
    $db->exec("CREATE TABLE tbl_owner_control_lab_store_offer_state_receipts (receipt_id INTEGER PRIMARY KEY AUTOINCREMENT, actor_subject TEXT, idempotency_key_hash TEXT, request_hash TEXT, offer_id INTEGER, resulting_version INTEGER, resulting_is_active INTEGER, created_at TEXT, expires_at TEXT, UNIQUE(actor_subject,idempotency_key_hash));");
    $db->exec("INSERT INTO tbl_store_items VALUES (11, 'Disposable source', '/public/img/disposable.png', 1);");
    $db->exec("INSERT INTO tbl_mouse_lp_store_offers VALUES (1, 'normal_store', 11, NULL, 50, 4, 1, 1, NULL, NULL);");
    $db->exec("INSERT INTO tbl_mouse_lp_store_purchases VALUES (7, 'historical-user', 'historic-key', 'historic-fingerprint', 1, 'normal_store', 11, 'Disposable source', 'dspoinc', 50, NULL, NULL, NULL, NULL, CURRENT_TIMESTAMP);");
    $db->close();
}
function m2a_state(SQLite3 $db): array { return $db->querySingle('SELECT is_active, owner_control_version FROM tbl_mouse_lp_store_offers WHERE offer_id=1', true); }

$path = tempnam(sys_get_temp_dir(), 'm2a-'); if ($path === false) throw new RuntimeException('FAIL: temporary database unavailable');
m2a_setup($path);
$db = new SQLite3($path);
$actor = '111111111111111111';
$input = ['offer_id'=>1, 'expected_version'=>1, 'is_active'=>false, 'idempotency_key'=>'abcdefghijklmnopqrstuvwx'];

// This proves the authoritative purchase service reaches its normal active-offer eligibility path.
$pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
m2a_expect(fn() => mouse_lp_store_purchase($pdo, 'purchase-test-user', ['offer_id'=>1, 'payment_method'=>'dspoinc', 'idempotency_key'=>'purchase-before-disable']), 'Insufficient available DSPOINC.', 'active offer reaches authoritative purchase eligibility');
m2a_assert((int)$db->querySingle('SELECT COUNT(*) FROM tbl_mouse_lp_store_purchases') === 1, 'eligibility probe created no purchase');

$one = narrrfs_owner_lab_store_offer_state($db, $input, $actor);
m2a_assert($one['is_active'] === false && $one['version'] === 2, 'disable increments version');
m2a_expect(fn() => mouse_lp_store_purchase($pdo, 'purchase-test-user', ['offer_id'=>1, 'payment_method'=>'dspoinc', 'idempotency_key'=>'purchase-after-disable']), 'Offer is unavailable.', 'disabled offer rejected by authoritative purchase service');
m2a_assert((int)$db->querySingle('SELECT COUNT(*) FROM tbl_mouse_lp_store_purchases') === 1, 'toggle did not change historical purchases');
m2a_assert((int)$db->querySingle('SELECT COUNT(*) FROM tbl_user_scores') === 0, 'toggle did not create score rows');

$again = narrrfs_owner_lab_store_offer_state($db, $input, $actor);
m2a_assert($again['replayed'] === true && $again['version'] === 2, 'same key replay');
try { narrrfs_owner_lab_store_offer_state($db, ['offer_id'=>1,'expected_version'=>1,'is_active'=>true,'idempotency_key'=>'abcdefghijklmnopqrstuvwx'], $actor); throw new RuntimeException('FAIL: changed replay'); }
catch (NarrrfsOwnerLabStoreOfferStateException $error) { m2a_assert($error->status() === 409 && $error->publicCode() === 'idempotency_key_reused', 'changed replay rejected'); }
try { narrrfs_owner_lab_store_offer_state($db, ['offer_id'=>1,'expected_version'=>1,'is_active'=>true,'idempotency_key'=>'zyxwvutsrqponmlkjihgfedc'], $actor); throw new RuntimeException('FAIL: stale'); }
catch (NarrrfsOwnerLabStoreOfferStateException $error) { m2a_assert($error->publicCode() === 'version_conflict', 'stale conflict'); }
m2a_assert((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_state_audit') === 1, 'one audit');
m2a_assert((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_state_receipts') === 1, 'one receipt');
m2a_assert((int)$db->querySingle('SELECT stock FROM tbl_mouse_lp_store_offers WHERE offer_id=1') === 4, 'stock untouched');
$db->close(); $pdo = null; @unlink($path);

foreach (['audit' => 'tbl_owner_control_lab_store_offer_state_audit', 'receipt' => 'tbl_owner_control_lab_store_offer_state_receipts'] as $kind => $table) {
    $path = tempnam(sys_get_temp_dir(), 'm2a-rollback-'); if ($path === false) throw new RuntimeException('FAIL: temporary database unavailable'); m2a_setup($path); $db = new SQLite3($path);
    $db->exec("CREATE TRIGGER fail_{$kind} BEFORE INSERT ON {$table} BEGIN SELECT RAISE(ABORT, '{$kind} failure'); END;");
    try { narrrfs_owner_lab_store_offer_state($db, ['offer_id'=>1,'expected_version'=>1,'is_active'=>false,'idempotency_key'=>'rollback-' . $kind . '-key-123456789'], $actor); throw new RuntimeException('FAIL: ' . $kind . ' failure accepted'); }
    catch (Throwable) { /* The exact SQLite exception text is driver-specific; rollback evidence is authoritative. */ }
    $state = m2a_state($db); m2a_assert((int)$state['is_active'] === 1 && (int)$state['owner_control_version'] === 1, $kind . ' failure rolls offer back');
    m2a_assert((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_state_audit') === 0, $kind . ' failure rolls audit back');
    m2a_assert((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_state_receipts') === 0, $kind . ' failure rolls receipt back');
    $db->close(); @unlink($path);
}
echo "PASS: M2A authoritative purchase boundary, transaction/idempotency, protected writes, and audit/receipt rollback\n";
