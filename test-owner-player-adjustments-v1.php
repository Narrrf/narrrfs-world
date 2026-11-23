<?php
declare(strict_types=1);
require_once __DIR__ . '/api/lib/owner-player-adjustments-v1.php';
function check(bool $ok, string $name): void { if (!$ok) throw new RuntimeException('FAIL: '.$name); }
function fixture(): SQLite3 {
    $db=new SQLite3(':memory:'); $db->enableExceptions(true);
    $db->exec("CREATE TABLE tbl_users(discord_id TEXT PRIMARY KEY); CREATE TABLE tbl_user_scores(user_id TEXT,score INTEGER,game TEXT,source TEXT,timestamp TEXT); CREATE TABLE tbl_dspoinc_stakes(user_id TEXT,amount INTEGER,status TEXT); CREATE TABLE tbl_score_adjustments(user_id TEXT,admin_id TEXT,amount INTEGER,action TEXT,reason TEXT,timestamp TEXT); CREATE TABLE tbl_store_items(item_id INTEGER PRIMARY KEY,is_active INTEGER,item_name TEXT); CREATE TABLE tbl_user_inventory(user_id TEXT,item_id INTEGER,quantity INTEGER,acquired_at TEXT,UNIQUE(user_id,item_id)); INSERT INTO tbl_users VALUES('111111111111111111'),('222222222222222222'); INSERT INTO tbl_store_items VALUES(33,1,'Green Elixir'),(34,1,'Blue Elixir'),(35,1,'Red Elixir'),(36,1,'other'); INSERT INTO tbl_user_scores VALUES('111111111111111111',1000,'test','test',CURRENT_TIMESTAMP); INSERT INTO tbl_dspoinc_stakes VALUES('111111111111111111',700,'active');");
    $db->exec((string)file_get_contents(__DIR__.'/db/migrations/20261123_owner_control_player_adjustments_v1.sql'));
    return $db;
}
$owner='222222222222222222'; $user='111111111111111111';
$points=['user_id'=>$user,'action'=>'remove','amount'=>300,'reason'=>'fixture adjustment','idempotency_key'=>'fixture_key_00000001'];
$db=fixture(); $r=owner_player_adjust($db,$points,$owner,'points'); check($r['before']===1000 && $r['after']===700 && !$r['replayed'],'available debit');
check(owner_player_adjust($db,$points,$owner,'points')['replayed'],'replay');
try { $changed=$points; $changed['amount']=299; owner_player_adjust($db,$changed,$owner,'points'); throw new RuntimeException('changed key accepted'); } catch(OwnerPlayerAdjustmentException $e) { check($e->publicCode==='idempotency_key_reused','changed replay'); }
try { $next=$points; $next['amount']=1; $next['idempotency_key']='fixture_key_00000002'; owner_player_adjust($db,$next,$owner,'points'); throw new RuntimeException('overdraw accepted'); } catch(OwnerPlayerAdjustmentException $e) { check($e->publicCode==='insufficient_available_balance','frozen stake protected'); }
check((int)$db->querySingle('SELECT SUM(amount) FROM tbl_dspoinc_stakes')===700,'stake unchanged');
$inv=['user_id'=>$user,'action'=>'add','amount'=>3,'item_id'=>33,'reason'=>'fixture adjustment','idempotency_key'=>'fixture_key_00000003'];
check(owner_player_adjust($db,$inv,$owner,'inventory')['after']===3,'inventory add');
check(owner_player_adjust($db,$inv,$owner,'inventory')['replayed'],'inventory replay');
try { $changed=$inv; $changed['amount']=4; owner_player_adjust($db,$changed,$owner,'inventory'); throw new RuntimeException('changed inventory key accepted'); } catch(OwnerPlayerAdjustmentException $e) { check($e->publicCode==='idempotency_key_reused','changed inventory replay'); }
check((int)$db->querySingle('SELECT quantity FROM tbl_user_inventory')===3,'inventory replay changed no quantity');
$inv['action']='remove'; $inv['amount']=2; $inv['idempotency_key']='fixture_key_00000004'; check(owner_player_adjust($db,$inv,$owner,'inventory')['after']===1,'inventory remove');
try { $inv['amount']=2; $inv['idempotency_key']='fixture_key_00000005'; owner_player_adjust($db,$inv,$owner,'inventory'); throw new RuntimeException('overdraw accepted'); } catch(OwnerPlayerAdjustmentException $e) { check($e->publicCode==='invalid_inventory_quantity','inventory overdraw'); }
try { $inv['item_id']=36; $inv['idempotency_key']='fixture_key_00000006'; owner_player_adjust($db,$inv,$owner,'inventory'); throw new RuntimeException('unsupported item accepted'); } catch(OwnerPlayerAdjustmentException $e) { check($e->publicCode==='item_not_supported','item boundary'); }
check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_player_adjustment_audit')===3,'audit count');
check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_player_adjustment_receipts')===3,'receipt count');
$add=$points; $add['action']='add'; $add['amount']=25; $add['idempotency_key']='fixture_key_00000007'; check(owner_player_adjust($db,$add,$owner,'points')['after']===725,'points add');
try { $missing=$add; $missing['user_id']='333333333333333333'; $missing['idempotency_key']='fixture_key_00000008'; owner_player_adjust($db,$missing,$owner,'points'); throw new RuntimeException('missing user accepted'); } catch(OwnerPlayerAdjustmentException $e) { check($e->publicCode==='user_not_found','missing user'); }
try { $invalid=$add; $invalid['amount']=0; owner_player_adjust($db,$invalid,$owner,'points'); throw new RuntimeException('zero accepted'); } catch(OwnerPlayerAdjustmentException $e) { check($e->publicCode==='invalid_request','invalid amount'); }
try { $db->exec('UPDATE tbl_owner_player_adjustment_audit SET reason=\'changed\''); throw new RuntimeException('audit mutable'); } catch(Exception $e) { if ($e->getMessage()==='audit mutable') throw $e; }
$db->close();
foreach(['tbl_owner_player_adjustment_audit','tbl_owner_player_adjustment_receipts'] as $table) {
    $db=fixture(); $db->exec("CREATE TRIGGER fixture_failure BEFORE INSERT ON {$table} BEGIN SELECT RAISE(ABORT,'fixture failure'); END");
    try { owner_player_adjust($db,$points,$owner,'points'); throw new RuntimeException('failure accepted'); } catch(SQLite3Exception $e) { /* intentional */ } catch(Exception $e) { if ($e->getMessage()==='failure accepted') throw $e; }
    check((int)$db->querySingle('SELECT SUM(score) FROM tbl_user_scores')===1000,'ledger rollback '.$table);
    check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_score_adjustments')===0,'adjustment rollback '.$table);
    check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_player_adjustment_audit')===0,'audit rollback '.$table);
    $db->close();
}
foreach(['tbl_owner_player_adjustment_audit','tbl_owner_player_adjustment_receipts'] as $table) {
    $db=fixture(); $db->exec("CREATE TRIGGER fixture_failure BEFORE INSERT ON {$table} BEGIN SELECT RAISE(ABORT,'fixture failure'); END");
    $inv=['user_id'=>$user,'action'=>'add','amount'=>3,'item_id'=>33,'reason'=>'fixture adjustment','idempotency_key'=>'fixture_key_00000009'];
    try { owner_player_adjust($db,$inv,$owner,'inventory'); throw new RuntimeException('failure accepted'); } catch(Exception $e) { if ($e->getMessage()==='failure accepted') throw $e; }
    check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_user_inventory')===0,'inventory rollback '.$table);
    check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_player_adjustment_audit')===0,'inventory audit rollback '.$table);
    $db->close();
}
echo "PASS: local points, elixir inventory, replay and rollback fixtures\n";
