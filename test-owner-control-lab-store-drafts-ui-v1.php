<?php
declare(strict_types=1);
function draft_ui_assert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException('FAIL: '.$label);echo 'PASS: '.$label.PHP_EOL;}
$ui=file_get_contents(__DIR__.'/owner-control/v1/lab-store-drafts.php');$index=file_get_contents(__DIR__.'/owner-control/v1/index.php');
draft_ui_assert(is_string($ui)&&str_contains($ui,'/api/owner-control/v1/lab-store/drafts.php'),'draft UI calls only its read route');
foreach(['/api/owner-control/v1/lab-store/draft-create.php','/api/owner-control/v1/lab-store/draft-update.php','/api/owner-control/v1/lab-store/draft-csrf.php'] as $route)draft_ui_assert(str_contains($ui,$route),'draft UI names exact guarded route '.$route);
draft_ui_assert(str_contains($ui,"credentials:'same-origin'")&&str_contains($ui,"'Content-Type':'application/json'")&&str_contains($ui,'async function csrf(action)')&&str_contains($ui,'body:JSON.stringify({action})')&&str_contains($ui,"const action=editing?'update':'create',token=await csrf(action)")&&str_contains($ui,"'X-CSRF-Token':token")&&str_contains($ui,"payload.capability!=='lab_store_drafts_csrf'"),'draft UI uses same-origin JSON and requests an action-specific dedicated CSRF token');
draft_ui_assert(str_contains($ui,'let pendingSave=null,inFlight=false')&&str_contains($ui,'pendingKey(payload)')&&str_contains($ui,'if(inFlight)return'),'draft UI coalesces duplicate submits and retains one pending request key');
draft_ui_assert(str_contains($ui,'pendingSave=null')&&str_contains($ui,'pendingSave.fingerprint!==fingerprint(basePayload())'),'draft UI clears the pending key only on cancellation, success, or payload change');
foreach(['price','stock','dspoinc','purchase','inventory','fulfil','genetic_item','api/admin'] as $forbidden)draft_ui_assert(!str_contains(strtolower($ui),$forbidden),'draft UI excludes '.$forbidden);
preg_match('/\\$labStoreDraftsAvailable\\s*=\\s*(.+?);/',is_string($index)?$index:'',$draftGateMatch);$draftGateExpression=$draftGateMatch[1]??'';
draft_ui_assert(str_contains($draftGateExpression,'NARRRFS_OWNER_LAB_STORE_DRAFTS_UI_ENABLED')&&str_contains($draftGateExpression,'NARRRFS_OWNER_LAB_STORE_DRAFTS_WRITE_ENABLED')&&!str_contains($draftGateExpression,'NARRRFS_OWNER_CSRF_ENABLED')&&str_contains(is_string($index)?$index:'','/owner-control/v1/lab-store-drafts.php'),'Owner Control card requires only dedicated draft gates');
echo "PASS: Lab Store M1 draft UI contract\n";
