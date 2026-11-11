<?php
declare(strict_types=1);
function draft_ui_assert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException('FAIL: '.$label);echo 'PASS: '.$label.PHP_EOL;}
$ui=file_get_contents(__DIR__.'/owner-control/v1/lab-store-drafts.php');$index=file_get_contents(__DIR__.'/owner-control/v1/index.php');
draft_ui_assert(is_string($ui)&&str_contains($ui,'draft-details.php'),'UI integrates details route');
draft_ui_assert(str_contains($ui,'draft-sources.php')&&str_contains($ui,'Find items')&&str_contains($ui,'Use this item'),'UI integrates the dedicated source picker with an explicit accessible action');
draft_ui_assert(str_contains($ui,'row.onclick')&&str_contains($ui,"row.onkeydown")&&str_contains($ui,'selectSource(item)'),'search results themselves are selectable by mouse and keyboard');
draft_ui_assert(str_contains($ui,'Selected source')&&str_contains($ui,'change_source')&&str_contains($ui,'clear_source'),'selected-source card has Change and Clear actions');
draft_ui_assert(str_contains($ui,"if(!val('title').trim())set('title',item.title)")&&str_contains($ui,"if(item.presentation_ref&&!val('media_reference').trim())set('media_reference',item.presentation_ref)")&&str_contains($ui,"set('proposed_source_type',item.source_type)"),'selection fills only approved draft metadata and blank title/media values');
draft_ui_assert(str_contains($ui,'Basic item details')&&str_contains($ui,'Advanced draft metadata')&&str_contains($ui,'<details class="advanced">'),'normal flow is simplified while technical metadata is collapsed');
draft_ui_assert(str_contains($ui,'presentation_ref')&&str_contains($ui,'Preview unavailable.'),'UI provides non-sensitive source preview fallback');
foreach(['proposed_source_type','normal_store','genetic_item','proposed_source_item_id','proposed_lp_price','proposed_dspoinc_price','proposed_stock'] as $field)draft_ui_assert(str_contains($ui,$field),'UI supports approved private metadata '.$field);
draft_ui_assert(str_contains($ui,'pending')&&str_contains($ui,'keyFor')&&str_contains($ui,'pending.fingerprint'),'unchanged retry reuses idempotency key');
draft_ui_assert(str_contains($ui,'pending=null')&&str_contains($ui,'version_conflict'),'success clears key and conflict is presented');
draft_ui_assert(str_contains($ui,'Planning workspace only')&&str_contains($ui,'does not publish an offer'),'draft-only boundary is presented without technical proposal jargon');
draft_ui_assert(!str_contains(strtolower($ui),'api/admin'),'UI has no legacy admin caller');
draft_ui_assert(str_contains((string)$index,'/owner-control/v1/lab-store-drafts.php'),'Owner Control entry exists'); echo "PASS: Lab Store M1.1 UI contract\n";
