<?php
/** Pure tests for unapproved Christmas range planning. No DB/net writes. */
declare(strict_types=1);
require_once __DIR__ . '/api/lib/christmas-advent-2026-draft-preflight-v2.php';
function checkV2(bool $ok, string $name): void {
    if (!$ok) throw new RuntimeException('FAIL: ' . $name);
    echo 'PASS: ' . $name . PHP_EOL;
}
$days = [];
for ($day = 1; $day <= 24; $day++) {
    $days[] = [
        'day_number' => $day,
        'dspoinc_min' => $day === 24 ? 50000 : 1000,
        'dspoinc_max' => $day === 24 ? 250000 : 10000,
        'max_daily_claims' => 1000,
        'bonus' => ['enabled'=>false,'item_ids'=>[],'chance_basis_points'=>0,
            'max_winners'=>0,'quantity_per_win'=>1]
    ];
}
$base = ['schema_version'=>'christmas_advent_2026_draft_v2',
    'event_key'=>'christmas_advent_2026','max_bonus_wins_per_player'=>3,
    'first_bonus_guarantee'=>['enabled'=>false],'days'=>$days];
$result = christmas26_draft_preflight_v2($base);
checkV2($result['valid'] && !$result['publishable'], 'valid planning document never publishable');
checkV2($result['review']['min_per_player_dspoinc'] === 73000, 'minimum 24-day per-player exposure');
checkV2($result['review']['max_per_player_dspoinc'] === 480000, 'maximum 24-day per-player exposure');
checkV2($result['review']['max_event_dspoinc_at_claim_caps'] === 480000000, 'worst case at 1000 claims per day');
checkV2(str_contains($result['review']['reward_selection'],'NOT_IMPLEMENTED'), 'server draw explicitly unimplemented');
foreach ([
    ['normal_below_min',0,'dspoinc_min',999],
    ['normal_above_max',0,'dspoinc_max',10001],
    ['eve_below_min',23,'dspoinc_min',49999],
    ['eve_above_max',23,'dspoinc_max',250001],
    ['inverted',0,'dspoinc_min',10001],
    ['bool',0,'dspoinc_min',true],
    ['negative',0,'dspoinc_max',-1],
] as [$name,$index,$field,$value]) {
    $invalid = $base; $invalid['days'][$index][$field] = $value;
    checkV2(!christmas26_draft_preflight_v2($invalid)['valid'], 'reject '.$name);
}
$wrongSchema = $base; $wrongSchema['schema_version']='christmas_advent_2026_draft_v1';
checkV2(!christmas26_draft_preflight_v2($wrongSchema)['valid'], 'reject older schema in V2 preflight');
$bonus = $base;
$bonus['days'][0]['bonus']=['enabled'=>true,'item_ids'=>[17],
    'chance_basis_points'=>300,'max_winners'=>15,'quantity_per_win'=>1];
checkV2(christmas26_draft_preflight_v2($bonus)['review']['candidate_store_item_ids'] === [17],
    'any positive candidate Store ID preserved as unapproved');
$badBonus=$bonus; $badBonus['days'][0]['bonus']['max_winners']=1001;
checkV2(!christmas26_draft_preflight_v2($badBonus)['valid'], 'V1 cap safeguards inherited');
checkV2(!christmas26_draft_preflight_v1($base)['valid'], 'old V1 validator cannot approve V2 draft');
echo 'PASS: Stage 9 V2 pure tests; no rewards or databases'.PHP_EOL;
