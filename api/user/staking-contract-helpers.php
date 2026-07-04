<?php
/**
 * DSPOINC Staking Contract Helpers.
 *
 * Plain language for DEVS:
 * This file centralizes staking contract versions, Season 13 V2 pool math,
 * Genesis holder multiplier tiers, and verified Genesis ownership checks.
 *
 * Backend remains authoritative. Frontend may preview these values, but final
 * reward calculation and Genesis tier validation must happen in PHP.
 */

const STAKING_CONTRACT_LEGACY_V1 = 'legacy_v1';
const STAKING_CONTRACT_SEASON13_V2 = 'season13_v2';

/**
 * Phase A safety:
 * Keep legacy active now. In Season 13, this constant can be changed to
 * STAKING_CONTRACT_SEASON13_V2 after live DB migration and final testing.
 */
const ACTIVE_STAKING_CONTRACT_VERSION = STAKING_CONTRACT_SEASON13_V2;

/**
 * Season 13 V2 base lock pools.
 *
 * reward_rate is stored as percent, matching the legacy table style.
 */
const STAKING_SEASON13_V2_POOLS = [
    14 => [
        'label' => '14 Days',
        'reward_rate' => 0.5,
    ],
    30 => [
        'label' => '30 Days',
        'reward_rate' => 1.25,
    ],
    90 => [
        'label' => '90 Days',
        'reward_rate' => 5.0,
    ],
    180 => [
        'label' => '180 Days',
        'reward_rate' => 12.0,
    ],
    365 => [
        'label' => '365 Days',
        'reward_rate' => 30.0,
    ],
    730 => [
        'label' => '730 Days',
        'reward_rate' => 75.0,
    ],
];

/**
 * Genesis holder multiplier tiers.
 *
 * Plain language for DEVS:
 * One Genesis opens the holder lane, but the first real staking boost starts
 * at two Genesis mice. This makes the second Genesis feel meaningful without
 * making early tiers too generous. Higher tiers still reward stronger collector
 * conviction and keep a reason to accumulate more Genesis over time.
 *
 * Non-Genesis members can still stake with x1.00.
 */
const STAKING_GENESIS_MULTIPLIER_TIERS = [
    [
        'key' => 'no_genesis',
        'label' => 'No Genesis Boost',
        'min' => 0,
        'max' => 0,
        'multiplier' => 1.00,
    ],
    [
        'key' => 'genesis_1',
        'label' => '1 Genesis',
        'min' => 1,
        'max' => 1,
        'multiplier' => 1.00,
    ],
    [
        'key' => 'genesis_2',
        'label' => '2 Genesis',
        'min' => 2,
        'max' => 2,
        'multiplier' => 1.025,
    ],
    [
        'key' => 'genesis_3_5',
        'label' => '3–5 Genesis',
        'min' => 3,
        'max' => 5,
        'multiplier' => 1.05,
    ],
    [
        'key' => 'genesis_6_15',
        'label' => '6–15 Genesis',
        'min' => 6,
        'max' => 15,
        'multiplier' => 1.10,
    ],
    [
        'key' => 'genesis_16_29',
        'label' => '16–29 Genesis',
        'min' => 16,
        'max' => 29,
        'multiplier' => 1.20,
    ],
    [
        'key' => 'genesis_30_plus',
        'label' => '30+ Genesis',
        'min' => 30,
        'max' => null,
        'multiplier' => 1.35,
    ],
];

/**
 * Return the V2 pool config for a lock duration in days.
 */
function staking_get_v2_pool_by_days(int $lockDurationDays): ?array {
    return STAKING_SEASON13_V2_POOLS[$lockDurationDays] ?? null;
}

/**
 * Return all public V2 pools for frontend display.
 */
function staking_get_v2_pools(): array {
    return STAKING_SEASON13_V2_POOLS;
}

/**
 * Return the Genesis tier for a verified Genesis NFT count.
 */
function staking_get_genesis_tier(int $genesisCount): array {
    foreach (STAKING_GENESIS_MULTIPLIER_TIERS as $tier) {
        $min = (int)$tier['min'];
        $max = $tier['max'];

        if ($genesisCount < $min) {
            continue;
        }

        if ($max !== null && $genesisCount > (int)$max) {
            continue;
        }

        return $tier;
    }

    return STAKING_GENESIS_MULTIPLIER_TIERS[0];
}

/**
 * Count currently verified Genesis NFTs for a Discord user.
 *
 * Plain language for DEVS:
 * This mirrors the current-owner approach used elsewhere in the Genesis system.
 * It counts the best/current verified ownership row for each Genesis token.
 */
function staking_count_verified_genesis_for_user(PDO $pdo, string $userId): int {
    $stmt = $pdo->prepare("
        WITH current_ownership AS (
            SELECT *
            FROM (
                SELECT
                    o.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY o.token_id, o.collection
                        ORDER BY
                            COALESCE(o.is_verified, 0) DESC,
                            COALESCE(o.verified_at, '') DESC,
                            COALESCE(o.acquired_at, '') DESC,
                            o.ownership_id DESC
                    ) AS ownership_rank
                FROM tbl_nft_ownership o
                WHERE LOWER(o.collection) = 'genesis'
            )
            WHERE ownership_rank = 1
        )
        SELECT COUNT(*) AS genesis_count
        FROM current_ownership
        WHERE user_id = :user_id
          AND COALESCE(is_verified, 0) = 1
    ");

    $stmt->execute([':user_id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int)($row['genesis_count'] ?? 0);
}

/**
 * Calculate a reward amount from amount and percent.
 */
function staking_calculate_percent_reward(int $amount, float $rewardRate): int {
    if ($amount <= 0 || $rewardRate <= 0) {
        return 0;
    }

    return (int)floor($amount * ($rewardRate / 100));
}

/**
 * Calculate V2 base and boosted reward values.
 */
function staking_calculate_v2_rewards(int $amount, float $baseRewardRate, float $genesisMultiplier): array {
    $baseReward = staking_calculate_percent_reward($amount, $baseRewardRate);
    $boostedReward = (int)floor($baseReward * $genesisMultiplier);

    return [
        'base_expected_reward' => $baseReward,
        'expected_reward' => $boostedReward,
        'genesis_bonus_amount' => max(0, $boostedReward - $baseReward),
    ];
}

/**
 * Compare Genesis tier strength.
 *
 * Same-tier rule:
 * The current tier must be the same or higher than the tier stored at stake start.
 */
function staking_get_genesis_tier_rank(string $tierKey): int {
    foreach (STAKING_GENESIS_MULTIPLIER_TIERS as $index => $tier) {
        if ($tier['key'] === $tierKey) {
            return $index;
        }
    }

    return 0;
}

/**
 * Return true when current tier keeps the original Genesis boost valid.
 */
function staking_is_same_or_higher_genesis_tier(string $startTierKey, string $currentTierKey): bool {
    return staking_get_genesis_tier_rank($currentTierKey) >= staking_get_genesis_tier_rank($startTierKey);
}

/**
 * Calculate final claim reward for a V2 stake after checking current Genesis tier.
 */
function staking_calculate_v2_final_reward(array $stake, array $currentTier): array {
    $startTierKey = (string)($stake['genesis_tier_at_stake'] ?? 'no_genesis');
    $currentTierKey = (string)($currentTier['key'] ?? 'no_genesis');

    $baseReward = (int)($stake['base_expected_reward'] ?? 0);
    $expectedReward = (int)($stake['expected_reward'] ?? 0);

    $termsValid = staking_is_same_or_higher_genesis_tier($startTierKey, $currentTierKey);
    $finalReward = $termsValid ? $expectedReward : $baseReward;

    return [
        'terms_status' => $termsValid ? 'valid' : 'tier_dropped',
        'final_reward_amount' => $finalReward,
        'genesis_terms_penalty_amount' => max(0, $expectedReward - $finalReward),
    ];
}

/**
 * Return true if the stake belongs to the Season 13 V2 contract.
 */
function staking_is_v2_stake(array $stake): bool {
    return (string)($stake['staking_contract_version'] ?? STAKING_CONTRACT_LEGACY_V1) === STAKING_CONTRACT_SEASON13_V2;
}

/**
 * Return true when the active contract is Season 13 V2.
 */
function staking_is_v2_active(): bool {
    return ACTIVE_STAKING_CONTRACT_VERSION === STAKING_CONTRACT_SEASON13_V2;
}