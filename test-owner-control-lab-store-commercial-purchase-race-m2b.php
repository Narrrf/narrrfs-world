<?php
declare(strict_types=1);
require_once __DIR__.'/api/lib/mouse-lp-store-service.php';
require_once __DIR__.'/api/lib/owner-lab-store-offer-commercial-v1.php';

function raceCheck(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function racePause(string $prefix): int {
    file_put_contents($prefix.'.entered', '1');
    $deadline=microtime(true)+15;
    while (!is_file($prefix.'.release')) { if (microtime(true)>$deadline) throw new RuntimeException('barrier timeout'); usleep(10000); }
    return 1;
}
if (($argv[1]??'')==='worker') {
    [$unused,$worker,$mode,$path,$prefix,$barrier]=$argv;
    try {
        if ($barrier==='no') {
            // A separate zero-timeout writer probe confirms that the first
            // worker still holds SQLite's write reservation at this barrier.
            $probe=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $probe->exec('PRAGMA busy_timeout=0'); $locked=false;
            try { $probe->exec('BEGIN IMMEDIATE'); $probe->exec('ROLLBACK'); } catch (PDOException $e) { $locked=str_contains(strtolower($e->getMessage()),'locked'); }
            if (!$locked) throw new RuntimeException('expected writer lock was not observed');
            $probe=null; file_put_contents($prefix.'.'.$mode.'.started','locked');
        }
        if ($mode==='purchase') {
            $db=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $db->exec('PRAGMA busy_timeout=5000');
            $db->sqliteCreateFunction('test_barrier',fn()=> $barrier==='yes' ? racePause($prefix) : 1,0);
            $r=mouse_lp_store_purchase($db,'test-user',['offer_id'=>1,'payment_method'=>'dspoinc','idempotency_key'=>'purchase-test']);
            echo json_encode(['result'=>'ok','amount'=>$r['receipt']['amount_paid']]);
        } else {
            $db=new SQLite3($path); $db->createFunction('test_barrier',fn()=> $barrier==='yes' ? racePause($prefix) : 1,0);
            $r=narrrfs_owner_lab_store_offer_commercial($db,['offer_id'=>1,'expected_version'=>1,'lp_price'=>null,'dspoinc_price'=>80,'stock'=>8,'idempotency_key'=>'commercial-test-key-123456789'],'111111111111111111');
            echo json_encode(['result'=>'ok','version'=>$r['version']]);
        }
    } catch (Throwable $e) { echo json_encode(['result'=>'error','error'=>method_exists($e,'publicCode')?$e->publicCode():$e->getMessage()]); }
    exit;
}

foreach (['purchase','owner'] as $first) {
    $dir=sys_get_temp_dir().DIRECTORY_SEPARATOR.'m2b-race-'.bin2hex(random_bytes(8)); mkdir($dir); $path=$dir.DIRECTORY_SEPARATOR.'fixture.sqlite'; $prefix=$dir.DIRECTORY_SEPARATOR.'barrier';
    $db=new SQLite3($path); $db->enableExceptions(true);
    $db->exec("CREATE TABLE tbl_mouse_lp_store_offers(offer_id INTEGER PRIMARY KEY,source_type TEXT,source_item_id INTEGER,lp_price INTEGER,dspoinc_price INTEGER,stock INTEGER,is_active INTEGER,owner_control_version INTEGER,updated_by TEXT,updated_at TEXT); CREATE TABLE tbl_store_items(item_id INTEGER PRIMARY KEY,item_name TEXT,image_url TEXT,is_active INTEGER); CREATE TABLE tbl_mouse_lp_store_purchases(purchase_id INTEGER PRIMARY KEY,purchaser_user_id TEXT,idempotency_key TEXT,request_fingerprint TEXT,offer_id INTEGER,source_type TEXT,source_item_id INTEGER,item_title_snapshot TEXT,payment_method TEXT,amount_paid INTEGER,selected_token_id TEXT,selected_collection TEXT,mouse_name_snapshot TEXT,delivery_reference_id INTEGER,completed_at TEXT); CREATE TABLE tbl_user_scores(user_id TEXT,score INTEGER,game TEXT,source TEXT,timestamp TEXT); CREATE TABLE tbl_dspoinc_stakes(user_id TEXT,amount INTEGER,status TEXT); CREATE TABLE tbl_score_adjustments(user_id TEXT,admin_id TEXT,amount INTEGER,action TEXT,reason TEXT,timestamp TEXT); CREATE TABLE tbl_user_inventory(inventory_id INTEGER PRIMARY KEY,user_id TEXT,item_id INTEGER,quantity INTEGER,acquired_at TEXT,UNIQUE(user_id,item_id)); INSERT INTO tbl_store_items VALUES(1,'Test',NULL,1); INSERT INTO tbl_mouse_lp_store_offers VALUES(1,'normal_store',1,NULL,50,4,1,1,'test',CURRENT_TIMESTAMP); INSERT INTO tbl_user_scores VALUES('test-user',1000,'fixture','fixture',CURRENT_TIMESTAMP);");
    // The exact audit/receipt schema comes from the reviewed migration.
    $migration=file_get_contents(__DIR__.'/db/migrations/20261115_owner_control_lab_store_offer_commercial_m2b.sql');
    raceCheck(is_string($migration),'migration unavailable'); $db->exec($migration);
    $db->exec('CREATE TRIGGER test_pause BEFORE UPDATE ON tbl_mouse_lp_store_offers BEGIN SELECT test_barrier(); END'); $db->close();
    $second=$first==='purchase'?'owner':'purchase'; $pipes1=[]; $pipes2=[];
    $start=fn($mode,$barrier,&$pipes)=>proc_open([PHP_BINARY,__FILE__,'worker',$mode,$path,$prefix,$barrier],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
    $p1=$start($first,'yes',$pipes1); raceCheck(is_resource($p1),'first worker failed'); $deadline=microtime(true)+10; while(!is_file($prefix.'.entered')) { if(microtime(true)>$deadline) throw new RuntimeException('first worker did not reach barrier'); usleep(10000); }
    $p2=$start($second,'no',$pipes2); raceCheck(is_resource($p2),'second worker failed'); $deadline=microtime(true)+10; while(!is_file($prefix.'.'.$second.'.started')) { if(microtime(true)>$deadline) throw new RuntimeException('second worker did not start'); usleep(10000); } file_put_contents($prefix.'.release','1');
    $out1=stream_get_contents($pipes1[1]); $err1=stream_get_contents($pipes1[2]); foreach($pipes1 as $p)fclose($p); $exit1=proc_close($p1);
    $out2=stream_get_contents($pipes2[1]); $err2=stream_get_contents($pipes2[2]); foreach($pipes2 as $p)fclose($p); $exit2=proc_close($p2);
    raceCheck($exit1===0 && $exit2===0,"worker failure $err1 $err2"); $a=json_decode($out1,true); $b=json_decode($out2,true);
    raceCheck(($a['result']??'')==='ok',"$first first failed: $out1");
    if($first==='purchase') raceCheck(($b['error']??'')==='version_conflict',"owner did not conflict: $out2");
    else raceCheck(($b['result']??'')==='ok' && ($b['amount']??0)===80,"purchase did not use updated price: $out2");
    $db=new SQLite3($path,SQLITE3_OPEN_READONLY); $row=$db->querySingle('SELECT stock,owner_control_version FROM tbl_mouse_lp_store_offers WHERE offer_id=1',true);
    raceCheck((int)$row['stock']===($first==='purchase'?3:7) && (int)$row['owner_control_version']===($first==='purchase'?2:3),'stock or version mismatch');
    raceCheck((int)$db->querySingle('SELECT COUNT(*) FROM tbl_mouse_lp_store_purchases')===1,'purchase count mismatch');
    raceCheck((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_audit')===($first==='purchase'?0:1),'audit count mismatch');
    raceCheck((int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_lab_store_offer_commercial_receipts')===($first==='purchase'?0:1),'receipt count mismatch'); $db->close();
    foreach (glob($dir.DIRECTORY_SEPARATOR.'*') as $f) unlink($f); rmdir($dir);
    echo "PASS: $first first; competing transaction observed correct version and price\n";
}
