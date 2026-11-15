<?php
declare(strict_types=1);
require_once __DIR__.'/api/lib/owner-lab-store-offer-commercial-v1.php';
function checkM2b(bool $v,string $m):void { if(!$v) throw new RuntimeException($m); }
function expectM2b(callable $f,string $code):void { try {$f();} catch(NarrrfsOwnerLabStoreOfferCommercialException $e) {checkM2b($e->publicCode()===$code,'expected '.$code.' got '.$e->publicCode());return;} throw new RuntimeException('expected '.$code); }
function fixtureM2b():array {
    $path=tempnam(sys_get_temp_dir(),'m2b-txn-'); if($path===false)throw new RuntimeException('temp unavailable'); $db=new SQLite3($path);$db->enableExceptions(true);
    $db->exec("CREATE TABLE tbl_mouse_lp_store_offers(offer_id INTEGER PRIMARY KEY,lp_price INTEGER,dspoinc_price INTEGER,stock INTEGER,is_active INTEGER,owner_control_version INTEGER); INSERT INTO tbl_mouse_lp_store_offers VALUES(1,5,10,3,1,1);");
    $db->exec(file_get_contents(__DIR__.'/db/migrations/20261115_owner_control_lab_store_offer_commercial_m2b.sql'));
    foreach(['tbl_mouse_lp_store_purchases','tbl_mousefight_league_point_spends','tbl_user_scores','tbl_score_adjustments','tbl_user_inventory','tbl_user_genetic_items','tbl_genetic_item_history','tbl_store_items','tbl_genetic_trait_catalog'] as $name) {
        $db->exec("CREATE TABLE $name(id INTEGER); INSERT INTO $name VALUES(1); CREATE TRIGGER trap_{$name}_insert BEFORE INSERT ON $name BEGIN SELECT RAISE(ABORT,'protected insert'); END; CREATE TRIGGER trap_{$name}_update BEFORE UPDATE ON $name BEGIN SELECT RAISE(ABORT,'protected update'); END; CREATE TRIGGER trap_{$name}_delete BEFORE DELETE ON $name BEGIN SELECT RAISE(ABORT,'protected delete'); END;");
    }
    return [$path,$db];
}
$actor='111111111111111111'; $in=['offer_id'=>1,'expected_version'=>1,'lp_price'=>7,'dspoinc_price'=>12,'stock'=>4,'idempotency_key'=>'m2b-test-key-abcdefghijklmnop'];
[$path,$db]=fixtureM2b();
foreach ([['stock'=>-1],['lp_price'=>0],['lp_price'=>null,'dspoinc_price'=>null]] as $bad) expectM2b(fn()=>narrrfs_owner_lab_store_offer_commercial($db,array_replace($in,$bad),$actor),'invalid_request');
$r=narrrfs_owner_lab_store_offer_commercial($db,$in,$actor);checkM2b(!$r['replayed'] && $r['version']===2,'initial save');
$r=narrrfs_owner_lab_store_offer_commercial($db,$in,$actor);checkM2b($r['replayed'] && $r['version']===2,'same-key replay');
expectM2b(fn()=>narrrfs_owner_lab_store_offer_commercial($db,array_replace($in,['stock'=>8]),$actor),'idempotency_key_reused');
expectM2b(fn()=>narrrfs_owner_lab_store_offer_commercial($db,array_replace($in,['idempotency_key'=>'other-test-key-abcdefghijklmn']),$actor),'version_conflict');
$db->exec("UPDATE tbl_owner_control_lab_store_offer_commercial_receipts SET expires_at='2000-01-01 00:00:00'");expectM2b(fn()=>narrrfs_owner_lab_store_offer_commercial($db,$in,$actor),'idempotency_key_expired');
checkM2b((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_audit')===1,'audit count');checkM2b((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_receipts')===1,'receipt count');
$blocked=false;try {$db->exec('DELETE FROM tbl_owner_control_lab_store_offer_commercial_audit');} catch (Throwable $e) {$blocked=true;} checkM2b($blocked,'audit mutable');
foreach(['tbl_mouse_lp_store_purchases','tbl_mousefight_league_point_spends','tbl_user_scores','tbl_score_adjustments','tbl_user_inventory','tbl_user_genetic_items','tbl_genetic_item_history','tbl_store_items','tbl_genetic_trait_catalog'] as $name) checkM2b((int)$db->querySingle("SELECT COUNT(*) FROM $name")===1,'protected table changed: '.$name);
$db->close();unlink($path);
foreach(['audit','receipts'] as $target) {
    [$path,$db]=fixtureM2b();$db->exec("CREATE TRIGGER fail_insert BEFORE INSERT ON tbl_owner_control_lab_store_offer_commercial_$target BEGIN SELECT RAISE(ABORT,'forced failure'); END");$failed=false;
    try {narrrfs_owner_lab_store_offer_commercial($db,$in,$actor);} catch(Throwable $e) {$failed=true;} checkM2b($failed,'failure not raised');
    $row=$db->querySingle('SELECT lp_price,dspoinc_price,stock,owner_control_version FROM tbl_mouse_lp_store_offers WHERE offer_id=1',true);checkM2b(array_values($row)===[5,10,3,1],$target.' did not rollback offer');
    checkM2b((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_audit')===0,$target.' audit rollback');checkM2b((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_receipts')===0,$target.' receipt rollback');$db->close();unlink($path);
}
[$path,$db]=fixtureM2b();$lock=new SQLite3($path);$lock->enableExceptions(true);$lock->exec('BEGIN IMMEDIATE');
expectM2b(fn()=>narrrfs_owner_lab_store_offer_commercial($db,$in,$actor),'database_busy');
checkM2b((int)$db->querySingle('SELECT owner_control_version FROM tbl_mouse_lp_store_offers WHERE offer_id=1')===1,'busy changed version');
checkM2b((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_audit')===0,'busy wrote audit');
checkM2b((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_receipts')===0,'busy wrote receipt');
$lock->exec('ROLLBACK');$lock->close();$db->close();unlink($path);
echo "PASS: commercial validation, replay, expiry, stale conflict, immutable audit, protected traps, independent rollback and busy exhaustion\n";
