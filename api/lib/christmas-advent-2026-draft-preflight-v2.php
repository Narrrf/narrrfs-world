<?php
/**
 * Christmas 2026 range-draft V2: pure, bounded, read-only financial planning preflight.
 *
 * DEVS FOR DECADES:
 * This examines unapproved local JSON, never SQLite or real Store stock.
 * Its maxima are for exposure review, NOT a live schedule or grant authority.
 * The V1 fixed-amount preflight remains unchanged for historical draft checks.
 */
declare(strict_types=1);
require_once __DIR__ . '/christmas-advent-2026-draft-preflight-v1.php';

const CHRISTMAS26_DRAFT_SCHEMA_V2 = 'christmas_advent_2026_draft_v2';

/** Validate day-specific bounds and reuse existing V1 bonus/cap checks safely. */
function christmas26_draft_preflight_v2(array $draft): array
{
    $errors = [];
    if (($draft['schema_version'] ?? null) !== CHRISTMAS26_DRAFT_SCHEMA_V2) {
        $errors[] = 'invalid_range_draft_schema';
    }
    $legacy = $draft;
    $legacy['schema_version'] = CHRISTMAS26_DRAFT_SCHEMA_V1;
    $minimumPerPlayer = 0;
    $maximumPerPlayer = 0;
    $minimumEventAtCaps = 0;
    $maximumEventAtCaps = 0;

    if (is_array($legacy['days'] ?? null) && array_is_list($legacy['days'])) {
        foreach ($legacy['days'] as $index => &$day) {
            if (!is_array($day)) {
                continue;
            }
            $dayNumber = $index + 1;
            $dayMinimum = $day['dspoinc_min'] ?? null;
            $dayMaximum = $day['dspoinc_max'] ?? null;
            $allowedMinimum = $dayNumber === 24 ? 50000 : 1000;
            $allowedMaximum = $dayNumber === 24 ? 250000 : 10000;
            $validRange = christmas26_draft_int_in_range_v1($dayMinimum, $allowedMinimum, $allowedMaximum)
                && christmas26_draft_int_in_range_v1($dayMaximum, $allowedMinimum, $allowedMaximum)
                && $dayMinimum <= $dayMaximum;
            if (!$validRange) {
                $errors[] = 'invalid_dspoinc_range_day_' . $dayNumber;
                // Force a V1 failure too instead of silently accepting malformed maximums.
                $day['guaranteed_dspoinc'] = null;
            } else {
                $day['guaranteed_dspoinc'] = $dayMaximum;
                $minimumPerPlayer += $dayMinimum;
                $maximumPerPlayer += $dayMaximum;
                $cap = $day['max_daily_claims'] ?? null;
                if (christmas26_draft_int_in_range_v1($cap, 1, CHRISTMAS26_DRAFT_MAX_DAILY_CLAIMS)) {
                    $minimumEventAtCaps += $dayMinimum * $cap;
                    $maximumEventAtCaps += $dayMaximum * $cap;
                }
            }
            unset($day['dspoinc_min'], $day['dspoinc_max']);
        }
        unset($day);
    }
    $base = christmas26_draft_preflight_v1($legacy);
    $base['errors'] = array_values(array_unique(array_merge($base['errors'], $errors)));
    $base['valid'] = $base['errors'] === [];
    $base['review']['min_per_player_dspoinc'] = $minimumPerPlayer;
    $base['review']['max_per_player_dspoinc'] = $maximumPerPlayer;
    $base['review']['min_event_dspoinc_at_claim_caps'] = $minimumEventAtCaps;
    $base['review']['max_event_dspoinc_at_claim_caps'] = $maximumEventAtCaps;
    $base['review']['reward_selection'] = 'PROPOSED_RANGE_NOT_IMPLEMENTED_SERVER_SIDE';
    $base['publishable'] = false;
    $base['unverified_release_gates'][] = 'server_side_random_draw_persisted_once_and_replayed';
    return $base;
}
