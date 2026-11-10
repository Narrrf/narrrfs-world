<?php
declare(strict_types=1);
function draft_ui_assert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException('FAIL: '.$label);echo 'PASS: '.$label.PHP_EOL;}
$ui=file_get_contents(__DIR__.'/owner-control/v1/lab-store-drafts.php');$index=file_get_contents(__DIR__.'/owner-control/v1/index.php');
draft_ui_assert(is_string($ui)&&str_contains($ui,'draft-details.php'),'UI integrates details route');
foreach(['proposed_source_type','normal_store','genetic_item','proposed_source_item_id','proposed_lp_price','proposed_dspoinc_price','proposed_stock'] as $field)draft_ui_assert(str_contains($ui,$field),'UI supports approved private metadata '.$field);
draft_ui_assert(str_contains($ui,'pending')&&str_contains($ui,'keyFor')&&str_contains($ui,'pending.fingerprint'),'unchanged retry reuses idempotency key');
draft_ui_assert(str_contains($ui,'pending=null')&&str_contains($ui,'version_conflict'),'success clears key and conflict is presented');
draft_ui_assert(str_contains($ui,'No publish')&&str_contains($ui,'private draft'),'private draft-only labels are present');
draft_ui_assert(!str_contains(strtolower($ui),'api/admin'),'UI has no legacy admin caller');
draft_ui_assert(str_contains((string)$index,'/owner-control/v1/lab-store-drafts.php'),'Owner Control entry exists'); echo "PASS: Lab Store M1.1 UI contract\n";
