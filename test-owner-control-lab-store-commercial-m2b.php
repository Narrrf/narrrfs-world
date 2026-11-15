<?php
declare(strict_types=1);
require __DIR__.'/api/lib/owner-lab-store-offer-commercial-v1.php';
function check(bool $ok, string $label): void { if (!$ok) throw new RuntimeException($label); }
function fails(callable $fn, string $code): void { try { $fn(); throw new RuntimeException('Expected '.$code); } catch (NarrrfsOwnerLabStoreOfferCommercialException $e) { check($e->publicCode()===$code, 'Wrong error '.$e->publicCode()); } }
$db=new SQLite3(':memory:'); $db->enableExceptions(true);
$db->exec('CREATE TABLE tbl_mouse_lp_store_offers (offer_id INTEGER PRIMARY KEY, lp_price INTEGER, dspoinc_price INTEGER, stock INTEGER NOT NULL, is_active INTEGER NOT NULL, owner_control_version INTEGER NOT NULL); INSERT INTO tbl_mouse_lp_store_offers VALUES (1,10,20,8,1,1); CREATE TABLE protected_rows (value TEXT); INSERT INTO protected_rows VALUES (\'unchanged\');');
$db->exec(file_get_contents(__DIR__.'/db/migrations/20261115_owner_control_lab_store_offer_commercial_m2b.sql'));
$actor='123456789012345678'; $in=['offer_id'=>1,'expected_version'=>1,'lp_price'=>11,'dspoinc_price'=>null,'stock'=>7,'idempotency_key'=>str_repeat('a',32)];
$result=narrrfs_owner_lab_store_offer_commercial($db,$in,$actor); check(!$result['replayed'] && $result['version']===2,'initial');
$result=narrrfs_owner_lab_store_offer_commercial($db,$in,$actor); check($result['replayed'] && $result['version']===2,'replay');
$different=$in; $different['stock']=6; fails(fn()=>narrrfs_owner_lab_store_offer_commercial($db,$different,$actor),'idempotency_key_reused');
$different['idempotency_key']=str_repeat('b',32); fails(fn()=>narrrfs_owner_lab_store_offer_commercial($db,$different,$actor),'version_conflict');
check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_audit')===1,'one audit');
check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_receipts')===1,'one receipt');
foreach (['UPDATE tbl_owner_control_lab_store_offer_commercial_audit SET prior_stock=0','DELETE FROM tbl_owner_control_lab_store_offer_commercial_audit'] as $sql) { $blocked=false; try { $db->exec($sql); } catch (Throwable $e) { $blocked=str_contains($e->getMessage(),'immutable audit'); } check($blocked,'audit protection'); }
foreach (['tbl_owner_control_lab_store_offer_commercial_audit','tbl_owner_control_lab_store_offer_commercial_receipts'] as $table) {
    $db->exec("CREATE TRIGGER fail_insert BEFORE INSERT ON $table BEGIN SELECT RAISE(ABORT,'forced'); END");
    $next=['offer_id'=>1,'expected_version'=>2,'lp_price'=>12,'dspoinc_price'=>20,'stock'=>6,'idempotency_key'=>str_repeat($table==='tbl_owner_control_lab_store_offer_commercial_audit'?'c':'d',32)];
    $blocked=false; try { narrrfs_owner_lab_store_offer_commercial($db,$next,$actor); } catch (Throwable $e) { $blocked=str_contains($e->getMessage(),'forced'); } check($blocked,'forced failure');
    check($db->querySingle('SELECT stock FROM tbl_mouse_lp_store_offers WHERE offer_id=1')===7 && $db->querySingle('SELECT owner_control_version FROM tbl_mouse_lp_store_offers WHERE offer_id=1')===2,'rollback');
    check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_audit')===1 && (int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_receipts')===1,'rollback audit receipt');
    $db->exec('DROP TRIGGER fail_insert');
}
check($db->querySingle('SELECT value FROM protected_rows')==='unchanged','protected');
echo "PASS commercial validation, replay, conflict, immutable audit and independent rollback\n";
