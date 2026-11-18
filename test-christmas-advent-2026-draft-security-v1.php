<?php
declare(strict_types=1);
require_once __DIR__ . '/api/config/admin-authorization.php';
function securityCheck(bool $ok,string $label):void { if(!$ok) throw new RuntimeException($label); echo "PASS: $label\n"; }
putenv('NARRRFS_OWNER_CHRISTMAS_DRAFT_READ_ENABLED');
putenv('NARRRFS_OWNER_CHRISTMAS_DRAFT_WRITE_ENABLED');
$policies = narrrfs_load_admin_route_policies()['routes'];
$read = $policies['owner_control.christmas_draft.read.v1'];
$write = $policies['owner_control.christmas_draft.write.v1'];
securityCheck(!$read['enabled'] && !$write['enabled'], 'draft gates default off');
$id='111111111111111111'; $now=time();
$session=['discord_id'=>$id,NARRRFS_ADMIN_PROVENANCE_SESSION_KEY=>NARRRFS_ADMIN_AUTH_PROVENANCE,NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY=>$now,NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY=>$now,NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY=>$now+NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS];
$token=narrrfs_issue_admin_csrf_token($session,$now);
$req=['method'=>'POST','origin'=>'https://narrrfs.world','contentType'=>'application/json','csrfToken'=>$token];
securityCheck(!narrrfs_admin_authorization_decision($write,$session,$req,[$id],$now)['allowed'],'disabled write denied');
$write['enabled']=true; $read['enabled']=true;
securityCheck(narrrfs_admin_authorization_decision($write,$session,$req,[$id],$now)['allowed'],'trusted owner write accepted');
securityCheck(!narrrfs_admin_authorization_decision($write,[],$req,[$id],$now)['allowed'],'anonymous denied');
securityCheck(!narrrfs_admin_authorization_decision($write,['discord_id'=>$id],$req,[$id],$now)['allowed'],'forged identity denied');
$bad=$req; $bad['csrfToken']=str_repeat('0',64);
securityCheck(!narrrfs_admin_authorization_decision($write,$session,$bad,[$id],$now)['allowed'],'bad CSRF denied');
$bad=$req; $bad['origin']='https://evil.example';
securityCheck(!narrrfs_admin_authorization_decision($write,$session,$bad,[$id],$now)['allowed'],'foreign origin denied');
$bad=$req; $bad['method']='GET';
securityCheck(!narrrfs_admin_authorization_decision($write,$session,$bad,[$id],$now)['allowed'],'wrong method denied');
securityCheck(narrrfs_admin_authorization_decision($read,$session,['method'=>'GET'],[$id],$now)['allowed'],'trusted owner read accepted');
echo "PASS: draft security suite\n";
