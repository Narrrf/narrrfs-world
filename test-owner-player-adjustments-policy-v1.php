<?php
declare(strict_types=1);
require_once __DIR__.'/api/config/admin-authorization.php';
function ok(bool $v,string $m): void { if (!$v) throw new RuntimeException('FAIL: '.$m); }
$owner='111111111111111111';$now=2000000000;$token=str_repeat('a',64);
$flags=['NARRRFS_OWNER_PLAYER_ADJUSTMENT_CSRF_ENABLED','NARRRFS_OWNER_PLAYER_POINTS_ADJUST_ENABLED','NARRRFS_OWNER_PLAYER_INVENTORY_ADJUST_ENABLED','NARRRFS_OWNER_PLAYER_ADJUSTMENT_UI_ENABLED'];
foreach($flags as $flag)putenv($flag);
$policies=require __DIR__.'/api/config/admin-route-policies.php';
foreach(['csrf','points','inventory','ui'] as $kind)ok(!$policies['routes']['owner_control.player_adjustment.'.$kind.'.v1']['enabled'],'default off '.$kind);
foreach($flags as $flag)putenv($flag.'=1');
$policies=require __DIR__.'/api/config/admin-route-policies.php';
foreach(['points','inventory'] as $kind){
 $key='owner_control.player_adjustment.'.$kind.'.v1';$policy=$policies['routes'][$key];
 $session=['discord_id'=>$owner,NARRRFS_ADMIN_PROVENANCE_SESSION_KEY=>NARRRFS_ADMIN_AUTH_PROVENANCE,NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY=>$now,NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY=>$now,NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY=>$now+43200,NARRRFS_ADMIN_CSRF_SESSION_KEY=>$token,NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY=>$now,NARRRFS_ADMIN_CSRF_SCOPE_SESSION_KEY=>$key];
 $req=['method'=>'POST','contentType'=>'application/json','origin'=>'https://narrrfs.world','csrfToken'=>$token];
 $d=narrrfs_admin_authorization_decision($policy,$session,$req,[$owner],$now);ok($d['allowed']&&$d['consumeCsrf'],'owner '.$kind);
 $d=narrrfs_admin_authorization_decision($policy,[],$req,[$owner],$now);ok(!$d['allowed']&&$d['status']===401,'anonymous '.$kind);
 $other=$session;$other['discord_id']='222222222222222222';$d=narrrfs_admin_authorization_decision($policy,$other,$req,[$owner],$now);ok(!$d['allowed']&&$d['status']===403,'nonowner '.$kind);
 foreach([['method'=>'GET'],['origin'=>'https://evil.invalid'],['origin'=>''],['csrfToken'=>'invalid']] as $override){$d=narrrfs_admin_authorization_decision($policy,$session,array_replace($req,$override),[$owner],$now);ok(!$d['allowed'],'request denied '.$kind);}
 $otherScope=$session;$otherScope[NARRRFS_ADMIN_CSRF_SCOPE_SESSION_KEY]='owner_control.player_adjustment.other.v1';$d=narrrfs_admin_authorization_decision($policy,$otherScope,$req,[$owner],$now);ok(!$d['allowed'],'wrong scope '.$kind);
 narrrfs_consume_admin_csrf($session,true);$d=narrrfs_admin_authorization_decision($policy,$session,$req,[$owner],$now);ok(!$d['allowed'],'replay denied '.$kind);
}
echo "PASS: adjustment policies default-off and owner/method/origin/CSRF boundaries\n";
