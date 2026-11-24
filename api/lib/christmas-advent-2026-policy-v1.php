<?php
/**
 * Christmas Advent 2026 — Store eligibility & immutable policy publication V1.
 *
 * Enforces:
 * OWNER DRAFT -> VALIDATED RELEASE CANDIDATE -> OWNER-APPROVED IMMUTABLE POLICY
 * without allowing Save Draft or client inputs to mutate or activate payouts.
 */
declare(strict_types=1);

require_once __DIR__ . '/christmas-advent-2026-draft-storage-v1.php';

const CHRISTMAS26_POLICY_SCHEMA_V1 = 'christmas_advent_2026_policy_v1';
const CHRISTMAS26_POLICIES_TABLE = 'tbl_christmas_advent_2026_policies';
const CHRISTMAS26_CLAIM_RECEIPTS_TABLE = 'tbl_christmas_advent_2026_claim_receipts';
const CHRISTMAS26_NOTIFY_OUTBOX_TABLE = 'tbl_christmas_advent_2026_notify_outbox';

/** Auto-grantable stackable consumables with verified tbl_user_inventory delivery. */
const CHRISTMAS26_AUTO_GRANT_ELIXIRS = [
    33 => 'Green Elixir',
    34 => 'Blue Elixir',
    35 => 'Red Elixir',
];

/** Auto-grantable lifetime one-per-user Arcade unlocks. */
const CHRISTMAS26_AUTO_GRANT_ARCADE_ONCE = [
    21 => 'Space Triple Shot Core',
    22 => 'Space Ship Paint Kit',
    23 => 'Snake Serpent Velocity Core',
    24 => 'Snake Golden Apple Booster',
    25 => 'Tetris Matrix Glow Pack',
    26 => 'Tetris Cheese Drop Reactor',
];

final class Christmas26PolicyException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $publicCode,
        public readonly array $details = []
    ) {
        parent::__construct($publicCode);
    }
}

function christmas26_policy_version_is_valid(string $policyVersion): bool
{
    return preg_match('/^christmas_advent_2026_v[1-9][0-9]{0,3}$/D', $policyVersion) === 1;
}

/**
 * Inspect tbl_store_items and determine whether an item has a verified,
 * automatic inventory delivery contract and finite available stock.
 */
function christmas26_classify_store_item(SQLite3 $db, int $itemId): array
{
    if ($itemId < 1 || $itemId > 1000000) {
        return [
            'item_id' => $itemId,
            'exists' => false,
            'active' => false,
            'auto_grantable' => false,
            'delivery_mode' => 'blocked_invalid_id',
            'reason' => 'invalid_item_id',
            'item_name' => null,
            'stock' => 0,
        ];
    }

    $cols = [];
    $colRes = $db->query('PRAGMA table_info(tbl_store_items)');
    if ($colRes instanceof SQLite3Result) {
        while (($col = $colRes->fetchArray(SQLITE3_ASSOC)) !== false) {
            $cols[(string)($col['name'] ?? '')] = true;
        }
    }
    if (!isset($cols['item_id'], $cols['item_name'], $cols['is_active'])) {
        return [
            'item_id' => $itemId,
            'exists' => false,
            'active' => false,
            'auto_grantable' => false,
            'delivery_mode' => 'blocked_catalog_unavailable',
            'reason' => 'store_catalog_schema_unavailable',
            'item_name' => null,
            'stock' => 0,
        ];
    }

    $selectDesc = isset($cols['description']) ? 'COALESCE(description, \'\') AS description' : '\'\' AS description';
    $selectQty = isset($cols['quantity']) ? 'quantity' : 'NULL AS quantity';
    $stmt = $db->prepare("SELECT item_id, item_name, {$selectDesc}, COALESCE(is_active, 0) AS is_active, {$selectQty} FROM tbl_store_items WHERE item_id = :id LIMIT 1");
    $stmt->bindValue(':id', $itemId, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $row = $res instanceof SQLite3Result ? $res->fetchArray(SQLITE3_ASSOC) : false;

    if (!is_array($row)) {
        return [
            'item_id' => $itemId,
            'exists' => false,
            'active' => false,
            'auto_grantable' => false,
            'delivery_mode' => 'blocked_not_found',
            'reason' => 'item_not_found',
            'item_name' => null,
            'stock' => 0,
        ];
    }

    $itemName = trim((string)($row['item_name'] ?? ''));
    $desc = trim((string)($row['description'] ?? ''));
    $active = (int)($row['is_active'] ?? 0) === 1;
    $rawStock = $row['quantity'] ?? null;
    $validStock = is_int($rawStock) && $rawStock >= 0;
    $stock = $validStock ? $rawStock : 0;

    if (!$active) {
        return [
            'item_id' => $itemId,
            'exists' => true,
            'active' => false,
            'auto_grantable' => false,
            'delivery_mode' => 'blocked_inactive',
            'reason' => 'item_inactive',
            'item_name' => $itemName,
            'stock' => $stock,
        ];
    }

    // Fail closed on any token, NFT, Discord manual-redeem, or unverified item.
    $blockedPattern = '/\b(nft|sol|solana|token|tokens|empire|spoinc|smz|ghc|trq|mst|bingo|vip|ticket|redeem|airdrop|wallet|coin|cheeseboard|cheesboard)\b|\/use\s*item/i';
    if (preg_match($blockedPattern, $itemName . ' ' . $desc) === 1) {
        return [
            'item_id' => $itemId,
            'exists' => true,
            'active' => true,
            'auto_grantable' => false,
            'delivery_mode' => 'blocked_manual_or_chain',
            'reason' => 'token_nft_or_manual_item_blocked',
            'item_name' => $itemName,
            'stock' => $stock,
        ];
    }

    if (isset(CHRISTMAS26_AUTO_GRANT_ELIXIRS[$itemId]) && $itemName === CHRISTMAS26_AUTO_GRANT_ELIXIRS[$itemId]) {
        if (!$validStock) {
            return [
                'item_id' => $itemId,
                'exists' => true,
                'active' => true,
                'auto_grantable' => false,
                'delivery_mode' => 'blocked_invalid_stock',
                'reason' => 'invalid_stock_quantity',
                'item_name' => $itemName,
                'stock' => 0,
            ];
        }
        return [
            'item_id' => $itemId,
            'exists' => true,
            'active' => true,
            'auto_grantable' => true,
            'delivery_mode' => 'stackable_consumable',
            'reason' => 'eligible',
            'item_name' => $itemName,
            'stock' => $stock,
        ];
    }

    if (isset(CHRISTMAS26_AUTO_GRANT_ARCADE_ONCE[$itemId]) && $itemName === CHRISTMAS26_AUTO_GRANT_ARCADE_ONCE[$itemId]) {
        if (!$validStock) {
            return [
                'item_id' => $itemId,
                'exists' => true,
                'active' => true,
                'auto_grantable' => false,
                'delivery_mode' => 'blocked_invalid_stock',
                'reason' => 'invalid_stock_quantity',
                'item_name' => $itemName,
                'stock' => 0,
            ];
        }
        return [
            'item_id' => $itemId,
            'exists' => true,
            'active' => true,
            'auto_grantable' => true,
            'delivery_mode' => 'arcade_unlock_once',
            'reason' => 'eligible',
            'item_name' => $itemName,
            'stock' => $stock,
        ];
    }

    return [
        'item_id' => $itemId,
        'exists' => true,
        'active' => true,
        'auto_grantable' => false,
        'delivery_mode' => 'blocked_unverified_item',
        'reason' => 'not_allowlisted_for_automatic_christmas_delivery',
        'item_name' => $itemName,
        'stock' => $stock,
    ];
}

function christmas26_policy_v1_canonical_payload(array $policy): array
{
    return [
        'schema_version' => $policy['schema_version'] ?? null,
        'event_key' => $policy['event_key'] ?? null,
        'policy_version' => $policy['policy_version'] ?? null,
        'timezone' => $policy['timezone'] ?? null,
        'start_date' => $policy['start_date'] ?? null,
        'end_date' => $policy['end_date'] ?? null,
        'source_draft_revision' => $policy['source_draft_revision'] ?? null,
        'source_draft_fingerprint' => $policy['source_draft_fingerprint'] ?? null,
        'max_bonus_wins_per_player' => $policy['max_bonus_wins_per_player'] ?? null,
        'first_bonus_guarantee_enabled' => $policy['first_bonus_guarantee_enabled'] ?? null,
        'min_per_player_dspoinc' => $policy['min_per_player_dspoinc'] ?? null,
        'max_per_player_dspoinc' => $policy['max_per_player_dspoinc'] ?? null,
        'max_event_dspoinc' => $policy['max_event_dspoinc'] ?? null,
        'max_bonus_grants' => $policy['max_bonus_grants'] ?? null,
        'days' => $policy['days'] ?? null,
    ];
}

function christmas26_policy_v1_fingerprint(array $policy): string
{
    $canonical = christmas26_policy_v1_canonical_payload($policy);
    return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

function christmas26_policy_v1_is_valid(array $policy): bool
{
    if (($policy['schema_version'] ?? null) !== CHRISTMAS26_POLICY_SCHEMA_V1
        || ($policy['event_key'] ?? null) !== CHRISTMAS26_EVENT_KEY
        || !is_string($policy['policy_version'] ?? null)
        || !christmas26_policy_version_is_valid($policy['policy_version'])
        || ($policy['timezone'] ?? null) !== CHRISTMAS26_TIMEZONE
        || ($policy['start_date'] ?? null) !== '2026-12-01'
        || ($policy['end_date'] ?? null) !== '2026-12-24'
        || !is_int($policy['source_draft_revision'] ?? null)
        || $policy['source_draft_revision'] < 1
        || !is_string($policy['source_draft_fingerprint'] ?? null)
        || preg_match('/^[a-f0-9]{64}$/D', $policy['source_draft_fingerprint']) !== 1
        || !christmas26_draft_int_in_range_v1($policy['max_bonus_wins_per_player'] ?? null, 1, 3)
        || ($policy['first_bonus_guarantee_enabled'] ?? null) !== false
        || !is_string($policy['policy_fingerprint'] ?? null)
        || preg_match('/^[a-f0-9]{64}$/D', $policy['policy_fingerprint']) !== 1) {
        return false;
    }

    $days = $policy['days'] ?? null;
    if (!is_array($days) || !array_is_list($days) || count($days) !== 24) {
        return false;
    }

    $minPerPlayer = 0;
    $maxPerPlayer = 0;
    $maxEvent = 0;
    $maxBonusGrants = 0;

    foreach ($days as $index => $day) {
        $expectedDay = $index + 1;
        if (!is_array($day)
            || ($day['day_number'] ?? null) !== $expectedDay
            || ($day['calendar_date'] ?? null) !== sprintf('2026-12-%02d', $expectedDay)) {
            return false;
        }
        $low = $expectedDay === 24 ? 50000 : 1000;
        $high = $expectedDay === 24 ? 250000 : 10000;
        $min = $day['dspoinc_min'] ?? null;
        $max = $day['dspoinc_max'] ?? null;
        $cap = $day['max_daily_claims'] ?? null;
        $budget = $day['daily_dspoinc_budget'] ?? null;
        if (!christmas26_draft_int_in_range_v1($min, $low, $high)
            || !christmas26_draft_int_in_range_v1($max, $low, $high)
            || $min > $max
            || !christmas26_draft_int_in_range_v1($cap, 1, CHRISTMAS26_DRAFT_MAX_DAILY_CLAIMS)
            || !is_int($budget)
            || $budget !== $max * $cap) {
            return false;
        }

        $minPerPlayer += $min;
        $maxPerPlayer += $max;
        $maxEvent += $budget;

        $bonus = $day['bonus'] ?? null;
        if (!is_array($bonus) || !is_bool($bonus['enabled'] ?? null)) {
            return false;
        }
        $chance = $bonus['chance_basis_points'] ?? null;
        $winners = $bonus['max_winners'] ?? null;
        $qty = $bonus['quantity_per_win'] ?? null;
        $items = $bonus['items'] ?? null;
        if (!christmas26_draft_int_in_range_v1($chance, 0, 10000)
            || !christmas26_draft_int_in_range_v1($winners, 0, $cap)
            || $qty !== 1
            || !is_array($items)
            || !array_is_list($items)) {
            return false;
        }
        if (!$bonus['enabled']) {
            if ($chance !== 0 || $winners !== 0 || $items !== []) {
                return false;
            }
            continue;
        }
        if ($chance === 0 || $winners === 0 || count($items) < 1 || count($items) > 10) {
            return false;
        }
        $seenItemIds = [];
        foreach ($items as $item) {
            if (!is_array($item)
                || !christmas26_draft_int_in_range_v1($item['item_id'] ?? null, 1, 1000000)
                || isset($seenItemIds[$item['item_id']])
                || !is_string($item['item_name'] ?? null)
                || trim($item['item_name']) === ''
                || !in_array($item['delivery_mode'] ?? null, ['stackable_consumable', 'arcade_unlock_once'], true)) {
                return false;
            }
            $seenItemIds[$item['item_id']] = true;
        }
        $maxBonusGrants += $winners;
    }

    if (($policy['min_per_player_dspoinc'] ?? null) !== $minPerPlayer
        || ($policy['max_per_player_dspoinc'] ?? null) !== $maxPerPlayer
        || !is_int($policy['max_event_dspoinc'] ?? null)
        || $policy['max_event_dspoinc'] < $maxPerPlayer
        || $policy['max_event_dspoinc'] > $maxEvent
        || ($policy['max_bonus_grants'] ?? null) !== $maxBonusGrants) {
        return false;
    }

    return hash_equals(christmas26_policy_v1_fingerprint($policy), $policy['policy_fingerprint']);
}

/**
 * Convert a validated draft revision into a canonical release-candidate policy,
 * enforcing automatic Store item eligibility and available stock/allocation.
 */
function christmas26_build_policy_from_draft_v1(
    SQLite3 $db,
    array $draft,
    int $draftRevision,
    string $draftFingerprint,
    string $policyVersion
): array {
    if (!christmas26_policy_version_is_valid($policyVersion)) {
        throw new Christmas26PolicyException(400, 'invalid_policy_version');
    }
    if ($draftRevision < 1 || preg_match('/^[a-f0-9]{64}$/D', $draftFingerprint) !== 1) {
        throw new Christmas26PolicyException(400, 'invalid_draft_reference');
    }

    $preflight = christmas26_draft_preflight_v2($draft);
    if (!$preflight['valid']) {
        throw new Christmas26PolicyException(400, 'invalid_draft', array_slice($preflight['errors'], 0, 25));
    }
    if (($draft['first_bonus_guarantee']['enabled'] ?? null) !== false) {
        throw new Christmas26PolicyException(400, 'first_bonus_guarantee_disabled_required');
    }

    $requiredStockByItem = [];
    $classifiedCache = [];
    $policyDays = [];
    $minPerPlayer = 0;
    $maxPerPlayer = 0;
    $maxEvent = 0;
    $maxBonusGrants = 0;
    $ineligibleErrors = [];

    foreach ($draft['days'] as $day) {
        $dayNumber = (int)$day['day_number'];
        $min = (int)$day['dspoinc_min'];
        $max = (int)$day['dspoinc_max'];
        $cap = (int)$day['max_daily_claims'];
        $budget = $max * $cap;

        $minPerPlayer += $min;
        $maxPerPlayer += $max;
        $maxEvent += $budget;

        $bonus = $day['bonus'];
        $bonusItems = [];
        if ($bonus['enabled']) {
            $maxBonusGrants += (int)$bonus['max_winners'];
            foreach ($bonus['item_ids'] as $itemId) {
                if (!isset($classifiedCache[$itemId])) {
                    $classifiedCache[$itemId] = christmas26_classify_store_item($db, (int)$itemId);
                }
                $cls = $classifiedCache[$itemId];
                if (!$cls['auto_grantable']) {
                    $ineligibleErrors[] = 'day_' . $dayNumber . '_item_' . $itemId . '_' . $cls['reason'];
                    continue;
                }
                $requiredStockByItem[$itemId] = ($requiredStockByItem[$itemId] ?? 0) + (int)$bonus['max_winners'];
                $bonusItems[] = [
                    'item_id' => (int)$cls['item_id'],
                    'item_name' => (string)$cls['item_name'],
                    'delivery_mode' => (string)$cls['delivery_mode'],
                ];
            }
        }

        $policyDays[] = [
            'day_number' => $dayNumber,
            'calendar_date' => sprintf('2026-12-%02d', $dayNumber),
            'dspoinc_min' => $min,
            'dspoinc_max' => $max,
            'max_daily_claims' => $cap,
            'daily_dspoinc_budget' => $budget,
            'bonus' => [
                'enabled' => (bool)$bonus['enabled'],
                'chance_basis_points' => (int)$bonus['chance_basis_points'],
                'max_winners' => (int)$bonus['max_winners'],
                'quantity_per_win' => 1,
                'items' => $bonusItems,
            ],
        ];
    }

    if ($ineligibleErrors !== []) {
        throw new Christmas26PolicyException(409, 'ineligible_store_bonus_item', array_slice($ineligibleErrors, 0, 25));
    }

    $stockErrors = [];
    foreach ($requiredStockByItem as $itemId => $neededStock) {
        $cls = $classifiedCache[$itemId];
        if ((int)$cls['stock'] < $neededStock) {
            $stockErrors[] = 'insufficient_store_stock_item_' . $itemId . '_need_' . $neededStock . '_have_' . $cls['stock'];
        }
    }
    if ($stockErrors !== []) {
        throw new Christmas26PolicyException(409, 'insufficient_store_stock', array_slice($stockErrors, 0, 25));
    }

    $policy = [
        'schema_version' => CHRISTMAS26_POLICY_SCHEMA_V1,
        'event_key' => CHRISTMAS26_EVENT_KEY,
        'policy_version' => $policyVersion,
        'timezone' => CHRISTMAS26_TIMEZONE,
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-24',
        'source_draft_revision' => $draftRevision,
        'source_draft_fingerprint' => $draftFingerprint,
        'max_bonus_wins_per_player' => (int)$draft['max_bonus_wins_per_player'],
        'first_bonus_guarantee_enabled' => false,
        'min_per_player_dspoinc' => $minPerPlayer,
        'max_per_player_dspoinc' => $maxPerPlayer,
        'max_event_dspoinc' => $maxEvent,
        'max_bonus_grants' => $maxBonusGrants,
        'days' => $policyDays,
    ];
    $policy['policy_fingerprint'] = christmas26_policy_v1_fingerprint($policy);

    if (!christmas26_policy_v1_is_valid($policy)) {
        throw new Christmas26PolicyException(500, 'policy_validation_failed');
    }

    return $policy;
}

/**
 * Step 1 of publication: convert an existing saved server draft revision into a
 * validated candidate row in tbl_christmas_advent_2026_policies.
 */
function christmas26_stage_policy_candidate(
    SQLite3 $db,
    int $expectedDraftRevision,
    string $policyVersion,
    string $actor
): array {
    if ($expectedDraftRevision < 1 || !preg_match('/^\d{17,20}$/D', $actor)) {
        throw new Christmas26PolicyException(400, 'invalid_request');
    }
    if (!christmas26_policy_version_is_valid($policyVersion)) {
        throw new Christmas26PolicyException(400, 'invalid_policy_version');
    }

    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $open = false;
    try {
        try {
            $db->exec('BEGIN IMMEDIATE');
            $open = true;
        } catch (Throwable $e) {
            if (in_array($db->lastErrorCode() & 255, [5, 6], true)) {
                throw new Christmas26PolicyException(503, 'database_busy');
            }
            throw $e;
        }

        $hasTable = (int)$db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='" . CHRISTMAS26_POLICIES_TABLE . "'") === 1;
        if (!$hasTable) {
            throw new Christmas26PolicyException(503, 'policy_schema_unavailable');
        }

        $saved = christmas26_draft_load_v1($db);
        if ($saved === null) {
            throw new Christmas26PolicyException(404, 'draft_not_found');
        }
        if ((int)$saved['revision'] !== $expectedDraftRevision) {
            throw new Christmas26PolicyException(409, 'draft_revision_conflict');
        }

        $policy = christmas26_build_policy_from_draft_v1(
            $db,
            $saved['draft'],
            (int)$saved['revision'],
            (string)$saved['fingerprint'],
            $policyVersion
        );
        $policyJson = json_encode($policy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (strlen($policyJson) > 131072) {
            throw new Christmas26PolicyException(400, 'policy_too_large');
        }

        $stmt = $db->prepare('SELECT status FROM tbl_christmas_advent_2026_policies WHERE policy_version = :v LIMIT 1');
        $stmt->bindValue(':v', $policyVersion, SQLITE3_TEXT);
        $res = $stmt->execute();
        $existing = $res instanceof SQLite3Result ? $res->fetchArray(SQLITE3_ASSOC) : false;

        if (is_array($existing)) {
            if (($existing['status'] ?? '') !== 'candidate') {
                throw new Christmas26PolicyException(409, 'policy_version_locked');
            }
            $up = $db->prepare(
                'UPDATE tbl_christmas_advent_2026_policies
                 SET source_draft_revision = :rev,
                     source_draft_fingerprint = :dfp,
                     policy_json = :json,
                     policy_fingerprint = :pfp,
                     min_per_player_dspoinc = :minp,
                     max_per_player_dspoinc = :maxp,
                     max_event_dspoinc = :maxe,
                     max_bonus_grants = :maxb,
                     created_by = :actor,
                     created_at = CURRENT_TIMESTAMP
                 WHERE policy_version = :v AND status = \'candidate\''
            );
            $up->bindValue(':rev', $policy['source_draft_revision'], SQLITE3_INTEGER);
            $up->bindValue(':dfp', $policy['source_draft_fingerprint'], SQLITE3_TEXT);
            $up->bindValue(':json', $policyJson, SQLITE3_TEXT);
            $up->bindValue(':pfp', $policy['policy_fingerprint'], SQLITE3_TEXT);
            $up->bindValue(':minp', $policy['min_per_player_dspoinc'], SQLITE3_INTEGER);
            $up->bindValue(':maxp', $policy['max_per_player_dspoinc'], SQLITE3_INTEGER);
            $up->bindValue(':maxe', $policy['max_event_dspoinc'], SQLITE3_INTEGER);
            $up->bindValue(':maxb', $policy['max_bonus_grants'], SQLITE3_INTEGER);
            $up->bindValue(':actor', $actor, SQLITE3_TEXT);
            $up->bindValue(':v', $policyVersion, SQLITE3_TEXT);
            $up->execute();
        } else {
            $ins = $db->prepare(
                'INSERT INTO tbl_christmas_advent_2026_policies (
                    policy_version, event_key, source_draft_revision, source_draft_fingerprint,
                    policy_json, policy_fingerprint, status,
                    min_per_player_dspoinc, max_per_player_dspoinc, max_event_dspoinc, max_bonus_grants,
                    created_by
                ) VALUES (
                    :v, :e, :rev, :dfp, :json, :pfp, \'candidate\', :minp, :maxp, :maxe, :maxb, :actor
                )'
            );
            $ins->bindValue(':v', $policyVersion, SQLITE3_TEXT);
            $ins->bindValue(':e', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
            $ins->bindValue(':rev', $policy['source_draft_revision'], SQLITE3_INTEGER);
            $ins->bindValue(':dfp', $policy['source_draft_fingerprint'], SQLITE3_TEXT);
            $ins->bindValue(':json', $policyJson, SQLITE3_TEXT);
            $ins->bindValue(':pfp', $policy['policy_fingerprint'], SQLITE3_TEXT);
            $ins->bindValue(':minp', $policy['min_per_player_dspoinc'], SQLITE3_INTEGER);
            $ins->bindValue(':maxp', $policy['max_per_player_dspoinc'], SQLITE3_INTEGER);
            $ins->bindValue(':maxe', $policy['max_event_dspoinc'], SQLITE3_INTEGER);
            $ins->bindValue(':maxb', $policy['max_bonus_grants'], SQLITE3_INTEGER);
            $ins->bindValue(':actor', $actor, SQLITE3_TEXT);
            $ins->execute();
        }

        $db->exec('COMMIT');
        $open = false;

        return [
            'status' => 'candidate',
            'policy_version' => $policyVersion,
            'policy_fingerprint' => $policy['policy_fingerprint'],
            'source_draft_revision' => $policy['source_draft_revision'],
            'min_per_player_dspoinc' => $policy['min_per_player_dspoinc'],
            'max_per_player_dspoinc' => $policy['max_per_player_dspoinc'],
            'max_event_dspoinc' => $policy['max_event_dspoinc'],
            'max_bonus_grants' => $policy['max_bonus_grants'],
            'payouts_enabled' => false,
        ];
    } catch (Throwable $e) {
        if ($open) {
            try { $db->exec('ROLLBACK'); } catch (Throwable) {}
        }
        if ($e instanceof Christmas26PolicyException) {
            throw $e;
        }
        if ($e->getMessage() === 'draft_integrity_failed') {
            throw new Christmas26PolicyException(409, 'draft_integrity_failed');
        }
        throw new Christmas26PolicyException(503, 'policy_stage_unavailable');
    }
}

/**
 * Step 2 of publication: freeze a validated candidate into an immutable
 * owner-approved policy. Payout execution remains separately gated.
 */
function christmas26_approve_policy(
    SQLite3 $db,
    string $policyVersion,
    string $expectedFingerprint,
    string $actor,
    bool $replaceActive = false
): array {
    if (!christmas26_policy_version_is_valid($policyVersion)
        || preg_match('/^[a-f0-9]{64}$/D', $expectedFingerprint) !== 1
        || !preg_match('/^\d{17,20}$/D', $actor)) {
        throw new Christmas26PolicyException(400, 'invalid_request');
    }

    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $open = false;
    try {
        try {
            $db->exec('BEGIN IMMEDIATE');
            $open = true;
        } catch (Throwable $e) {
            if (in_array($db->lastErrorCode() & 255, [5, 6], true)) {
                throw new Christmas26PolicyException(503, 'database_busy');
            }
            throw $e;
        }

        $hasTable = (int)$db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='" . CHRISTMAS26_POLICIES_TABLE . "'") === 1;
        if (!$hasTable) {
            throw new Christmas26PolicyException(503, 'policy_schema_unavailable');
        }

        $stmt = $db->prepare('SELECT policy_version, policy_json, policy_fingerprint, status, source_draft_revision FROM tbl_christmas_advent_2026_policies WHERE policy_version = :v LIMIT 1');
        $stmt->bindValue(':v', $policyVersion, SQLITE3_TEXT);
        $res = $stmt->execute();
        $row = $res instanceof SQLite3Result ? $res->fetchArray(SQLITE3_ASSOC) : false;
        if (!is_array($row)) {
            throw new Christmas26PolicyException(404, 'policy_not_found');
        }
        if (($row['status'] ?? '') !== 'candidate') {
            throw new Christmas26PolicyException(409, 'policy_not_candidate');
        }
        if (!hash_equals((string)$row['policy_fingerprint'], $expectedFingerprint)) {
            throw new Christmas26PolicyException(409, 'policy_fingerprint_mismatch');
        }

        $policy = json_decode((string)$row['policy_json'], true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($policy) || !christmas26_policy_v1_is_valid($policy) || !hash_equals((string)$policy['policy_fingerprint'], $expectedFingerprint)) {
            throw new Christmas26PolicyException(409, 'policy_integrity_failed');
        }

        // Re-verify live catalog eligibility and stock right before immutable approval.
        $requiredStockByItem = [];
        $classified = [];
        foreach ($policy['days'] as $day) {
            if (!($day['bonus']['enabled'] ?? false)) {
                continue;
            }
            foreach ($day['bonus']['items'] as $item) {
                $itemId = (int)$item['item_id'];
                $cls = $classified[$itemId] ??= christmas26_classify_store_item($db, $itemId);
                if (!$cls['auto_grantable'] || $cls['item_name'] !== $item['item_name'] || $cls['delivery_mode'] !== $item['delivery_mode']) {
                    throw new Christmas26PolicyException(409, 'ineligible_store_bonus_item', ['item_' . $itemId . '_' . $cls['reason']]);
                }
                $requiredStockByItem[$itemId] = ($requiredStockByItem[$itemId] ?? 0) + (int)$day['bonus']['max_winners'];
            }
        }
        foreach ($requiredStockByItem as $itemId => $neededStock) {
            if ((int)$classified[$itemId]['stock'] < $neededStock) {
                throw new Christmas26PolicyException(409, 'insufficient_store_stock', ['item_' . $itemId]);
            }
        }

        $activeStmt = $db->prepare("SELECT policy_version FROM tbl_christmas_advent_2026_policies WHERE event_key = :e AND status = 'approved' LIMIT 1");
        $activeStmt->bindValue(':e', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
        $activeRes = $activeStmt->execute();
        $activeRow = $activeRes instanceof SQLite3Result ? $activeRes->fetchArray(SQLITE3_ASSOC) : false;
        if (is_array($activeRow)) {
            if (!$replaceActive) {
                throw new Christmas26PolicyException(409, 'active_policy_already_approved');
            }
            $retire = $db->prepare(
                "UPDATE tbl_christmas_advent_2026_policies
                 SET status = 'retired', retired_by = :actor, retired_at = CURRENT_TIMESTAMP
                 WHERE policy_version = :v AND status = 'approved'"
            );
            $retire->bindValue(':actor', $actor, SQLITE3_TEXT);
            $retire->bindValue(':v', (string)$activeRow['policy_version'], SQLITE3_TEXT);
            $retire->execute();
        }

        $approve = $db->prepare(
            "UPDATE tbl_christmas_advent_2026_policies
             SET status = 'approved', approved_by = :actor, approved_at = CURRENT_TIMESTAMP
             WHERE policy_version = :v AND status = 'candidate' AND policy_fingerprint = :fp"
        );
        $approve->bindValue(':actor', $actor, SQLITE3_TEXT);
        $approve->bindValue(':v', $policyVersion, SQLITE3_TEXT);
        $approve->bindValue(':fp', $expectedFingerprint, SQLITE3_TEXT);
        $approve->execute();
        if ($db->changes() !== 1) {
            throw new Christmas26PolicyException(409, 'policy_approval_conflict');
        }

        $db->exec('COMMIT');
        $open = false;

        return [
            'status' => 'approved',
            'policy_version' => $policyVersion,
            'policy_fingerprint' => $expectedFingerprint,
            'source_draft_revision' => (int)$row['source_draft_revision'],
            'min_per_player_dspoinc' => (int)$policy['min_per_player_dspoinc'],
            'max_per_player_dspoinc' => (int)$policy['max_per_player_dspoinc'],
            'max_event_dspoinc' => (int)$policy['max_event_dspoinc'],
            'max_bonus_grants' => (int)$policy['max_bonus_grants'],
            'replaced_version' => is_array($activeRow) ? (string)$activeRow['policy_version'] : null,
            'payouts_enabled' => false,
        ];
    } catch (Throwable $e) {
        if ($open) {
            try { $db->exec('ROLLBACK'); } catch (Throwable) {}
        }
        if ($e instanceof Christmas26PolicyException) {
            throw $e;
        }
        throw new Christmas26PolicyException(503, 'policy_approve_unavailable');
    }
}

/** Load and cryptographically verify an approved (or retired for historical replay) policy. */
function christmas26_load_verified_policy(SQLite3 $db, string $policyVersion, bool $allowRetired = false): ?array
{
    $hasTable = (int)$db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='" . CHRISTMAS26_POLICIES_TABLE . "'") === 1;
    if (!$hasTable) {
        return null;
    }

    $statuses = $allowRetired ? "('approved', 'retired')" : "('approved')";
    $stmt = $db->prepare(
        "SELECT policy_version, policy_json, policy_fingerprint, status, source_draft_revision, approved_by, approved_at
         FROM tbl_christmas_advent_2026_policies
         WHERE event_key = :e AND policy_version = :v AND status IN {$statuses}
         LIMIT 1"
    );
    $stmt->bindValue(':e', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
    $stmt->bindValue(':v', $policyVersion, SQLITE3_TEXT);
    $res = $stmt->execute();
    $row = $res instanceof SQLite3Result ? $res->fetchArray(SQLITE3_ASSOC) : false;
    if (!is_array($row)) {
        return null;
    }
    $decoded = json_decode((string)$row['policy_json'], true);
    if (!is_array($decoded)
        || !hash_equals((string)$row['policy_fingerprint'], (string)($decoded['policy_fingerprint'] ?? ''))
        || !christmas26_policy_v1_is_valid($decoded)) {
        throw new RuntimeException('policy_integrity_failed');
    }
    $decoded['_row_status'] = (string)$row['status'];
    $decoded['_approved_by'] = (string)($row['approved_by'] ?? '');
    $decoded['_approved_at'] = (string)($row['approved_at'] ?? '');
    return $decoded;
}

/** Read-only Owner Control summary of policies, catalog eligibility, and runtime gates. */
function christmas26_policy_overview(SQLite3 $db): array
{
    $policiesAvailable = (int)$db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='" . CHRISTMAS26_POLICIES_TABLE . "'") === 1;
    $draft = null;
    try {
        $loaded = christmas26_draft_load_v1($db);
        if (is_array($loaded)) {
            $draft = [
                'revision' => $loaded['revision'],
                'fingerprint' => $loaded['fingerprint'],
            ];
        }
    } catch (Throwable) {
        $draft = null;
    }

    $policies = [];
    if ($policiesAvailable) {
        $res = $db->query(
            'SELECT policy_version, source_draft_revision, source_draft_fingerprint, policy_fingerprint, status,
                    min_per_player_dspoinc, max_per_player_dspoinc, max_event_dspoinc, max_bonus_grants,
                    created_by, created_at, approved_by, approved_at, retired_by, retired_at
             FROM tbl_christmas_advent_2026_policies
             ORDER BY created_at DESC, rowid DESC
             LIMIT 20'
        );
        while ($res instanceof SQLite3Result && ($row = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
            $policies[] = $row;
        }
    }

    $catalog = [];
    $res = $db->query('SELECT item_id FROM tbl_store_items WHERE COALESCE(is_active, 0) = 1 ORDER BY item_id ASC');
    while ($res instanceof SQLite3Result && ($row = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
        $catalog[] = christmas26_classify_store_item($db, (int)$row['item_id']);
    }

    return [
        'schema_ready' => $policiesAvailable,
        'latest_draft' => $draft,
        'policies' => $policies,
        'catalog_eligibility' => $catalog,
        'gates' => [
            'policy_stage_enabled' => getenv('NARRRFS_OWNER_CHRISTMAS_POLICY_WRITE_ENABLED') === '1',
            'policy_approve_enabled' => getenv('NARRRFS_OWNER_CHRISTMAS_POLICY_APPROVE_ENABLED') === '1',
            'player_status_enabled' => getenv('NARRRFS_CHRISTMAS_ADVENT_2026_STATUS_ENABLED') === '1',
            'player_payout_enabled' => getenv('NARRRFS_CHRISTMAS_ADVENT_2026_PAYOUT_ENABLED') === '1',
            'active_policy_version_env' => getenv('NARRRFS_CHRISTMAS_ADVENT_2026_POLICY_VERSION') ?: null,
            'discord_outbox_enabled' => getenv('NARRRFS_CHRISTMAS_ADVENT_2026_OUTBOX_ENABLED') === '1',
        ],
    ];
}
