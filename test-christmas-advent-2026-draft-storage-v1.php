<?php
declare(strict_types=1);
require_once __DIR__ . '/api/lib/christmas-advent-2026-draft-storage-v1.php';
function checkStorage(bool $ok, string $name): void { if (!$ok) throw new RuntimeException($name); echo "PASS: $name\n"; }
$db = new SQLite3(':memory:');
$db->enableExceptions(true);
$db->exec('CREATE TABLE tbl_store_items(item_id INTEGER PRIMARY KEY,is_active INTEGER NOT NULL)');
$db->exec("INSERT INTO tbl_store_items VALUES(33,1),(34,0)");
$db->exec((string)file_get_contents(__DIR__.'/db/migrations/create_christmas_advent_2026_draft_revisions_v1.sql'));
$days=[];
for($i=1;$i<=24;$i++) $days[]=['day_number'=>$i,'dspoinc_min'=>$i===24?50000:1000,'dspoinc_max'=>$i===24?250000:10000,'max_daily_claims'=>10,'bonus'=>['enabled'=>false,'item_ids'=>[],'chance_basis_points'=>0,'max_winners'=>0,'quantity_per_win'=>1]];
$draft=['schema_version'=>CHRISTMAS26_DRAFT_SCHEMA_V2,'event_key'=>CHRISTMAS26_EVENT_KEY,'max_bonus_wins_per_player'=>3,'first_bonus_guarantee'=>['enabled'=>false],'days'=>$days];
checkStorage(christmas26_draft_load_v1($db)===null,'empty draft');
$r=christmas26_draft_save_v1($db,$draft,0,'111111111111111111');
checkStorage($r['revision']===1 && !$r['publishable'] && $r['review']['max_per_player_dspoinc']===480000,'save draft and exposure');
checkStorage(christmas26_draft_load_v1($db)['draft']===$draft,'load verified draft');
try { christmas26_draft_save_v1($db,$draft,0,'111111111111111111'); throw new RuntimeException('conflict missed'); } catch (RuntimeException $e) { checkStorage($e->getMessage()==='draft_revision_conflict','stale revision rejected'); }
$draft['days'][0]['bonus']=['enabled'=>true,'item_ids'=>[34],'chance_basis_points'=>50,'max_winners'=>1,'quantity_per_win'=>1];
try { christmas26_draft_save_v1($db,$draft,1,'111111111111111111'); throw new RuntimeException('inactive accepted'); } catch (InvalidArgumentException $e) { checkStorage($e->getMessage()==='invalid_store_item','inactive store item rejected'); }
checkStorage((int)$db->querySingle('SELECT COUNT(*) FROM tbl_christmas_advent_2026_draft_revisions')===1,'failed writes rolled back');
$db->exec("UPDATE tbl_christmas_advent_2026_draft_revisions SET draft_json='{}' WHERE revision=1");
try { christmas26_draft_load_v1($db); throw new RuntimeException('tamper accepted'); } catch (RuntimeException $e) { checkStorage($e->getMessage()==='draft_integrity_failed','tampered draft rejected'); }
echo "PASS: disposable draft storage suite\n";
