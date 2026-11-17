<?php
/** Disposable, pure-contract tests. No SQLite, network, accounts or rewards. */
declare(strict_types=1);
require_once __DIR__ . '/api/lib/christmas-advent-2026-draft-preflight-v1.php';

function christmas26_draft_assert_v1(bool $condition, string $test): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $test);
    }
    echo 'PASS: ' . $test . PHP_EOL;
}

/** Build a fully specified example for deterministic preflight tests only. */
function christmas26_draft_fixture_v1(): array
{
    $days = [];
    for ($day = 1; $day <= 24; $day++) {
        $days[] = [
            'day_number' => $day,
            'guaranteed_dspoinc' => $day === 24 ? 25000 : 5000,
            'max_daily_claims' => 1000,
            'bonus' => [
                'enabled' => false,
                'item_ids' => [],
                'chance_basis_points' => 0,
                'max_winners' => 0,
                'quantity_per_win' => 1,
            ],
        ];
    }
    return [
        'schema_version' => CHRISTMAS26_DRAFT_SCHEMA_V1,
        'event_key' => CHRISTMAS26_EVENT_KEY,
        'max_bonus_wins_per_player' => 3,
        'first_bonus_guarantee' => ['enabled' => false],
        'days' => $days,
    ];
}

function christmas26_draft_has_error_v1(array $draft, string $error): bool
{
    return in_array($error, christmas26_draft_preflight_v1($draft)['errors'], true);
}

$base = christmas26_draft_fixture_v1();
$review = christmas26_draft_preflight_v1($base);
christmas26_draft_assert_v1($review['valid'] && !$review['publishable'], 'valid 24-day draft never becomes publishable');
christmas26_draft_assert_v1($review['review']['max_per_player_dspoinc'] === 140000, 'per-player illustrative 24-day DSPOINC sum');
christmas26_draft_assert_v1($review['review']['max_event_dspoinc_at_claim_caps'] === 140000000, 'claim-capped illustrative event DSPOINC maximum');
christmas26_draft_assert_v1($review['review']['max_bonus_item_grants_at_winner_caps'] === 0, 'bonus disabled across all days');

$bonus = $base;
$bonus['days'][0]['bonus'] = [
    'enabled' => true, 'item_ids' => [60, 61], 'chance_basis_points' => 300,
    'max_winners' => 100, 'quantity_per_win' => 1,
];
$result = christmas26_draft_preflight_v1($bonus);
christmas26_draft_assert_v1($result['valid'] && $result['review']['candidate_store_item_ids'] === [60, 61]
    && $result['review']['max_bonus_item_grants_at_winner_caps'] === 100,
    'Legacy/Mullet coin candidates with a 3% bonus and bounded winners');

$bad = $base;
array_pop($bad['days']);
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'requires_exactly_24_ordered_day_entries'), 'missing calendar day rejected');
$bad = $base;
$bad['days'][23]['day_number'] = 23;
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'duplicate_day_23'), 'duplicate calendar day rejected');
$bad = $base;
[$bad['days'][0], $bad['days'][1]] = [$bad['days'][1], $bad['days'][0]];
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'out_of_order_day_2'), 'out-of-order day entries rejected');
$bad = $base;
$bad['days'][2]['guaranteed_dspoinc'] = 0;
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'invalid_dspoinc_day_3'), 'zero guaranteed DSPOINC rejected');
$bad = $base;
$bad['days'][2]['max_daily_claims'] = true;
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'invalid_claim_cap_day_3'), 'boolean claim cap rejected');
$bad = $bonus;
$bad['days'][0]['bonus']['chance_basis_points'] = 10001;
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'invalid_bonus_values_day_1'), 'out-of-range chance rejected');
$bad = $bonus;
$bad['days'][0]['bonus']['item_ids'] = [60, 60];
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'invalid_bonus_item_list_day_1'), 'duplicate bonus item ID rejected');
$bad = $bonus;
$bad['days'][0]['bonus']['item_ids'] = [0];
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'invalid_bonus_item_id_day_1'), 'invalid Store item ID rejected');
$bad = $bonus;
$bad['days'][0]['bonus']['max_winners'] = 1001;
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'bonus_winners_exceed_claims_day_1'), 'bonus award cap cannot exceed claim cap');
$bad = $bonus;
$bad['days'][0]['bonus']['quantity_per_win'] = 2;
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'invalid_bonus_values_day_1'), 'unsupported multi-quantity award rejected');
$bad = $base;
$bad['days'][0]['bonus']['chance_basis_points'] = 300;
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'disabled_bonus_has_rewards_day_1'), 'disabled bonus cannot promise draws');
$bad = $bonus;
$bad['max_bonus_wins_per_player'] = 4;
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'invalid_max_bonus_wins_per_player'), 'player special win cap limited to 1–3');
$bad = $bonus;
$bad['first_bonus_guarantee'] = [
    'enabled' => true, 'qualifying_claim_days' => 24,
    'maximum_eligible_accounts' => 200, 'proposed_reserved_units' => 100,
];
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'insufficient_proposed_guarantee_capacity'), 'insufficient proposed first-win reservation rejected');
$bad = $bonus;
$bad['first_bonus_guarantee'] = [
    'enabled' => true, 'qualifying_claim_days' => 24,
    'maximum_eligible_accounts' => 50, 'proposed_reserved_units' => 50,
];
$result = christmas26_draft_preflight_v1($bad);
christmas26_draft_assert_v1($result['valid'] && !$result['publishable']
    && $result['review']['first_bonus_guarantee_requested'], 'arithmetic-only guarantee proposal still requires inventory proof');
$bad = $base;
$bad['event_key'] = 'other_event';
christmas26_draft_assert_v1(christmas26_draft_has_error_v1($bad, 'invalid_event_key'), 'wrong event rejected');

echo 'PASS: Christmas draft preflight V1 pure test suite (no DB or payouts)' . PHP_EOL;
