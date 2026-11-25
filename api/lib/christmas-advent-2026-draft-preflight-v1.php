<?php
/**
 * Christmas 2026 — pure, non-paying owner draft preflight.
 *
 * DEVS FOR DECADES:
 * This helper examines only a caller-supplied planning document. It neither
 * trusts Store stock nor approves a payout policy, reads SQLite, publishes a
 * reward schedule, or changes inventory/DSPOINC. A successful preflight is
 * NOT authorization to deliver rewards or activate any production feature.
 */
declare(strict_types=1);

require_once __DIR__ . '/christmas-advent-2026.php';

const CHRISTMAS26_DRAFT_SCHEMA_V1 = 'christmas_advent_2026_draft_v1';
const CHRISTMAS26_DRAFT_MAX_DAY_AMOUNT = 1000000000;
const CHRISTMAS26_DRAFT_MAX_DAILY_CLAIMS = 100000;

/** Check a bounded integer without silently accepting floats or booleans. */
function christmas26_draft_int_in_range_v1(mixed $value, int $minimum, int $maximum): bool
{
    return is_int($value) && $value >= $minimum && $value <= $maximum;
}

/**
 * Validate a 24-day owner planning proposal and calculate worst-case exposure.
 *
 * The result always reports `publishable=false`; live Store item eligibility,
 * real stock reservations, immutable approval and transaction integration are
 * separate protected release gates, even when `valid=true`.
 */
function christmas26_draft_preflight_v1(array $draft): array
{
    $errors = [];
    $maxPerPlayerDspoinc = 0;
    $maxEventDspoinc = 0;
    $maxBonusGrants = 0;
    $seenDays = [];
    $candidateItemIds = [];

    if (($draft['schema_version'] ?? null) !== CHRISTMAS26_DRAFT_SCHEMA_V1) {
        $errors[] = 'invalid_draft_schema_version';
    }
    if (($draft['event_key'] ?? null) !== CHRISTMAS26_EVENT_KEY) {
        $errors[] = 'invalid_event_key';
    }
    if (!christmas26_draft_int_in_range_v1($draft['max_bonus_wins_per_player'] ?? null, 1, 24)) {
        $errors[] = 'invalid_max_bonus_wins_per_player';
    }

    $days = $draft['days'] ?? null;
    if (!is_array($days) || !array_is_list($days) || count($days) !== 24) {
        $errors[] = 'requires_exactly_24_ordered_day_entries';
        $days = [];
    }

    foreach ($days as $index => $day) {
        if (!is_array($day)) {
            $errors[] = 'invalid_day_entry_' . ($index + 1);
            continue;
        }

        $dayNumber = $day['day_number'] ?? null;
        if (!christmas26_draft_int_in_range_v1($dayNumber, 1, 24)) {
            $errors[] = 'invalid_day_number_' . ($index + 1);
            continue;
        }
        if (isset($seenDays[$dayNumber])) {
            $errors[] = 'duplicate_day_' . $dayNumber;
            continue;
        }
        if ($dayNumber !== $index + 1) {
            $errors[] = 'out_of_order_day_' . $dayNumber;
        }
        $seenDays[$dayNumber] = true;

        $amount = $day['guaranteed_dspoinc'] ?? null;
        $dailyClaimCap = $day['max_daily_claims'] ?? null;
        if (!christmas26_draft_int_in_range_v1($amount, 1, CHRISTMAS26_DRAFT_MAX_DAY_AMOUNT)) {
            $errors[] = 'invalid_dspoinc_day_' . $dayNumber;
        } else {
            $maxPerPlayerDspoinc += $amount;
        }
        if (!christmas26_draft_int_in_range_v1($dailyClaimCap, 1, CHRISTMAS26_DRAFT_MAX_DAILY_CLAIMS)) {
            $errors[] = 'invalid_claim_cap_day_' . $dayNumber;
        }
        if (is_int($amount) && $amount >= 1 && $amount <= CHRISTMAS26_DRAFT_MAX_DAY_AMOUNT
            && is_int($dailyClaimCap) && $dailyClaimCap >= 1 && $dailyClaimCap <= CHRISTMAS26_DRAFT_MAX_DAILY_CLAIMS) {
            $maxEventDspoinc += $amount * $dailyClaimCap;
        }

        $bonus = $day['bonus'] ?? null;
        if (!is_array($bonus) || !is_bool($bonus['enabled'] ?? null)) {
            $errors[] = 'invalid_bonus_contract_day_' . $dayNumber;
            continue;
        }
        $chance = $bonus['chance_basis_points'] ?? null;
        $maxWinners = $bonus['max_winners'] ?? null;
        $quantity = $bonus['quantity_per_win'] ?? null;
        $itemIds = $bonus['item_ids'] ?? null;
        if (!christmas26_draft_int_in_range_v1($chance, 0, 10000)
            || !christmas26_draft_int_in_range_v1($maxWinners, 0, CHRISTMAS26_DRAFT_MAX_DAILY_CLAIMS)
            || $quantity !== 1 || !is_array($itemIds) || !array_is_list($itemIds)) {
            $errors[] = 'invalid_bonus_values_day_' . $dayNumber;
            continue;
        }
        if (count($itemIds) > 10 || count($itemIds) !== count(array_unique($itemIds, SORT_REGULAR))) {
            $errors[] = 'invalid_bonus_item_list_day_' . $dayNumber;
            continue;
        }
        foreach ($itemIds as $itemId) {
            if (!christmas26_draft_int_in_range_v1($itemId, 1, 1000000)) {
                $errors[] = 'invalid_bonus_item_id_day_' . $dayNumber;
                continue;
            }
            // IDs are candidates only; this function cannot confirm live catalog eligibility.
            $candidateItemIds[$itemId] = true;
        }
        if (!$bonus['enabled']) {
            if ($chance !== 0 || $maxWinners !== 0 || count($itemIds) !== 0) {
                $errors[] = 'disabled_bonus_has_rewards_day_' . $dayNumber;
            }
            continue;
        }
        if ($chance === 0 || $maxWinners === 0 || count($itemIds) === 0) {
            $errors[] = 'enabled_bonus_requires_chance_items_cap_day_' . $dayNumber;
        }
        if (is_int($dailyClaimCap) && $maxWinners > $dailyClaimCap) {
            $errors[] = 'bonus_winners_exceed_claims_day_' . $dayNumber;
        }
        $maxBonusGrants += $maxWinners;
    }

    if (count($seenDays) !== 24) {
        $errors[] = 'missing_or_duplicate_calendar_days';
    }

    $guarantee = $draft['first_bonus_guarantee'] ?? null;
    $guaranteeRequested = false;
    if (!is_array($guarantee) || !is_bool($guarantee['enabled'] ?? null)) {
        $errors[] = 'invalid_first_bonus_guarantee_contract';
    } else {
        $guaranteeRequested = $guarantee['enabled'];
        if ($guaranteeRequested) {
            $qualifyingDays = $guarantee['qualifying_claim_days'] ?? null;
            $eligibleAccounts = $guarantee['maximum_eligible_accounts'] ?? null;
            $reservedUnits = $guarantee['proposed_reserved_units'] ?? null;
            if (!christmas26_draft_int_in_range_v1($qualifyingDays, 1, 24)
                || !christmas26_draft_int_in_range_v1($eligibleAccounts, 1, 100000)
                || !christmas26_draft_int_in_range_v1($reservedUnits, 1, 100000)) {
                $errors[] = 'invalid_first_bonus_guarantee_bounds';
            } elseif ($reservedUnits < $eligibleAccounts || $maxBonusGrants < $eligibleAccounts) {
                $errors[] = 'insufficient_proposed_guarantee_capacity';
            }
        }
    }

    $itemIds = array_keys($candidateItemIds);
    sort($itemIds, SORT_NUMERIC);
    return [
        'valid' => $errors === [],
        'errors' => array_values(array_unique($errors)),
        'review' => [
            'configured_days' => count($seenDays),
            'max_per_player_dspoinc' => $maxPerPlayerDspoinc,
            'max_event_dspoinc_at_claim_caps' => $maxEventDspoinc,
            'max_bonus_item_grants_at_winner_caps' => $maxBonusGrants,
            'candidate_store_item_ids' => $itemIds,
            'first_bonus_guarantee_requested' => $guaranteeRequested,
        ],
        'publishable' => false,
        'unverified_release_gates' => [
            'owner_financial_approval',
            'authoritative_live_store_item_eligibility',
            'real_inventory_reservation_and_first_win_guarantee_feasibility',
            'immutable_published_policy_and_claim_contract',
            'transaction_concurrency_and_replay_review',
            'production_database_preservation_and_release_approval',
        ],
    ];
}
