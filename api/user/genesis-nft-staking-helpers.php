<?php
/**
 * Genesis NFT Staking / Genesis Mouse Freezer helpers.
 *
 * Plain language for DEVS:
 * This helper supports the Season 13 Genesis Mouse Freezer system.
 * It is separate from DSPOINC Staking V2.
 *
 * DSPOINC Staking V2:
 * - Freezes DSPOINC balance in tbl_dspoinc_stakes.
 *
 * Genesis Mouse Freezer:
 * - Freezes verified Genesis NFTs in tbl_genesis_nft_stakes.
 * - Uses claim audit rows in tbl_genesis_nft_stake_claims.
 * - Uses challenge rows in tbl_genesis_nft_stake_challenges.
 *
 * This file must not write rewards by itself.
 * This file must not activate NFT staking buttons by itself.
 */

const GENESIS_NFT_COLLECTION_KEY = 'genesis';
const VIP_NFT_COLLECTION_KEY = 'vip';

const GENESIS_NFT_VIP_BONUS_PERCENT = 10.0;
const GENESIS_NFT_VIP_BONUS_CAP_FROZEN_NFTS = 10;
const GENESIS_NFT_VIP_EXTRA_SLOTS = 1;

/**
 * Genesis Mouse Freezer economy ladder.
 *
 * Plain language for DEVS:
 * The Discord public role system currently has six Genesis roles.
 * The NFT staking system can still use deeper internal staking subtiers
 * for 50+ / 75+ / 100+ holders without creating Discord roles yet.
 */
const GENESIS_NFT_STAKING_TIERS = [
    [
        'key' => 'genesis_tier_1',
        'label' => 'Genesis Tier 1',
        'range_label' => '1 Genesis',
        'min_genesis' => 1,
        'max_genesis' => 1,
        'daily_reward' => 250,
        'freezer_slots' => 3,
        'discord_role_label' => 'Genesis Tier 1'
    ],
    [
        'key' => 'genesis_tier_2',
        'label' => 'Genesis Tier 2',
        'range_label' => '2 Genesis',
        'min_genesis' => 2,
        'max_genesis' => 2,
        'daily_reward' => 300,
        'freezer_slots' => 3,
        'discord_role_label' => 'Genesis Tier 2'
    ],
    [
        'key' => 'genesis_collector',
        'label' => 'Genesis Collector',
        'range_label' => '3–5 Genesis',
        'min_genesis' => 3,
        'max_genesis' => 5,
        'daily_reward' => 400,
        'freezer_slots' => 4,
        'discord_role_label' => 'Genesis Collector'
    ],
    [
        'key' => 'genesis_expert',
        'label' => 'Genesis Expert',
        'range_label' => '6–15 Genesis',
        'min_genesis' => 6,
        'max_genesis' => 15,
        'daily_reward' => 550,
        'freezer_slots' => 5,
        'discord_role_label' => 'Genesis Expert'
    ],
    [
        'key' => 'genesis_elite_holder',
        'label' => 'Genesis Elite Holder',
        'range_label' => '16–29 Genesis',
        'min_genesis' => 16,
        'max_genesis' => 29,
        'daily_reward' => 750,
        'freezer_slots' => 6,
        'discord_role_label' => 'Genesis Elite Holder'
    ],
    [
        'key' => 'genesis_legend',
        'label' => 'Genesis Legend',
        'range_label' => '30–49 Genesis',
        'min_genesis' => 30,
        'max_genesis' => 49,
        'daily_reward' => 1000,
        'freezer_slots' => 8,
        'discord_role_label' => 'Genesis Legend'
    ],
    [
        'key' => 'genesis_mythic',
        'label' => 'Genesis Mythic',
        'range_label' => '50–74 Genesis',
        'min_genesis' => 50,
        'max_genesis' => 74,
        'daily_reward' => 1150,
        'freezer_slots' => 10,
        'discord_role_label' => 'Genesis Legend'
    ],
    [
        'key' => 'genesis_ancient',
        'label' => 'Genesis Ancient',
        'range_label' => '75–99 Genesis',
        'min_genesis' => 75,
        'max_genesis' => 99,
        'daily_reward' => 1275,
        'freezer_slots' => 12,
        'discord_role_label' => 'Genesis Legend'
    ],
    [
        'key' => 'genesis_overlord',
        'label' => 'Genesis Overlord',
        'range_label' => '100+ Genesis',
        'min_genesis' => 100,
        'max_genesis' => null,
        'daily_reward' => 1400,
        'freezer_slots' => 15,
        'discord_role_label' => 'Genesis Legend'
    ],
];

/**
 * Return a safe integer from any DB/request value.
 */
function genesis_nft_staking_int($value): int
{
    return is_numeric($value) ? (int)$value : 0;
}

/**
 * Return the current Genesis Mouse Freezer tier for a verified Genesis count.
 */
function genesis_nft_staking_get_tier(int $genesisCount): array
{
    $matchedTier = [
        'key' => 'no_genesis',
        'label' => 'No Genesis Tier',
        'range_label' => '0 Genesis',
        'min_genesis' => 0,
        'max_genesis' => 0,
        'daily_reward' => 0,
        'freezer_slots' => 0,
        'discord_role_label' => 'No Genesis Tier'
    ];

    foreach (GENESIS_NFT_STAKING_TIERS as $tier) {
        $minGenesis = (int)$tier['min_genesis'];
        $maxGenesis = $tier['max_genesis'];

        if ($genesisCount < $minGenesis) {
            continue;
        }

        if ($maxGenesis !== null && $genesisCount > (int)$maxGenesis) {
            continue;
        }

        $matchedTier = $tier;
    }

    return $matchedTier;
}

/**
 * Count verified NFTs for a user and collection.
 */
function genesis_nft_staking_count_verified_collection(PDO $pdo, string $userId, string $collection): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS nft_count
        FROM tbl_nft_ownership
        WHERE user_id = ?
          AND collection = ?
          AND COALESCE(is_verified, 0) = 1
    ");
    $stmt->execute([$userId, $collection]);

    return genesis_nft_staking_int($stmt->fetchColumn());
}

/**
 * Return true if the user has at least one verified VIP NFT.
 */
function genesis_nft_staking_user_has_vip(PDO $pdo, string $userId): bool
{
    return genesis_nft_staking_count_verified_collection($pdo, $userId, VIP_NFT_COLLECTION_KEY) > 0;
}

/**
 * Calculate total freezer slots for a user.
 *
 * Plain language for DEVS:
 * Genesis tier gives the base slot count.
 * A verified VIP holder receives one extra slot.
 */
function genesis_nft_staking_calculate_slots(array $tier, bool $hasVip): int
{
    $baseSlots = genesis_nft_staking_int($tier['freezer_slots'] ?? 0);

    if ($hasVip) {
        return $baseSlots + GENESIS_NFT_VIP_EXTRA_SLOTS;
    }

    return $baseSlots;
}

/**
 * Calculate full claimable days only.
 *
 * Plain language for DEVS:
 * NFT staking claims must use full days only.
 * This prevents minute/second farming.
 */
function genesis_nft_staking_calculate_full_days(?string $fromDate, ?string $toDate = null): int
{
    if (!$fromDate) {
        return 0;
    }

    $fromTimestamp = strtotime($fromDate);
    $toTimestamp = $toDate ? strtotime($toDate) : time();

    if (!$fromTimestamp || !$toTimestamp || $toTimestamp <= $fromTimestamp) {
        return 0;
    }

    return (int)floor(($toTimestamp - $fromTimestamp) / 86400);
}

/**
 * Calculate preview claim values for one active frozen Genesis NFT.
 *
 * Plain language for DEVS:
 * This is math only. It does not write DSPOINC and does not create ledger rows.
 */
function genesis_nft_staking_calculate_claim_preview(array $stake, array $tier, bool $hasVip, int $activeStakeIndex): array
{
    $dailyReward = genesis_nft_staking_int($stake['daily_reward_at_freeze'] ?? 0);
    if ($dailyReward <= 0) {
        $dailyReward = genesis_nft_staking_int($tier['daily_reward'] ?? 0);
    }

    $lastClaimedAt = $stake['last_claimed_at'] ?: ($stake['frozen_at'] ?? null);
    $fullDays = genesis_nft_staking_calculate_full_days($lastClaimedAt);
    $baseReward = $fullDays * $dailyReward;

    $vipBonusPercent = 0.0;
    $vipBonusAmount = 0;

    if ($hasVip && $activeStakeIndex <= GENESIS_NFT_VIP_BONUS_CAP_FROZEN_NFTS) {
        $vipBonusPercent = GENESIS_NFT_VIP_BONUS_PERCENT;
        $vipBonusAmount = (int)floor($baseReward * ($vipBonusPercent / 100));
    }

    return [
        'full_days' => $fullDays,
        'daily_reward' => $dailyReward,
        'base_reward_amount' => $baseReward,
        'vip_bonus_percent' => $vipBonusPercent,
        'vip_bonus_amount' => $vipBonusAmount,
        'final_reward_amount' => $baseReward + $vipBonusAmount,
        'claim_from' => $lastClaimedAt,
        'claim_to' => date('Y-m-d H:i:s')
    ];
}

/**
 * Load active Genesis NFT stakes for one user.
 */
function genesis_nft_staking_load_active_stakes(PDO $pdo, string $userId): array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_genesis_nft_stakes
        WHERE user_id = ?
          AND status = 'active'
        ORDER BY frozen_at ASC, id ASC
    ");
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Load available verified Genesis NFTs for the user.
 *
 * Plain language for DEVS:
 * This query picks the best/current ownership row per token and excludes
 * tokens that already have an active freezer row.
 */
function genesis_nft_staking_load_available_genesis(PDO $pdo, string $userId): array
{
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
                WHERE o.collection = ?
                  AND o.user_id = ?
            )
            WHERE ownership_rank = 1
        )
        SELECT
            co.ownership_id,
            co.wallet,
            co.token_id,
            co.collection,
            co.nft_name,
            co.image_url,
            co.is_verified,
            co.verified_at,
            co.last_seen_at
        FROM current_ownership co
        LEFT JOIN tbl_genesis_nft_stakes s
          ON s.token_id = co.token_id
         AND s.collection = co.collection
         AND s.status = 'active'
        WHERE COALESCE(co.is_verified, 0) = 1
          AND s.id IS NULL
        ORDER BY COALESCE(co.verified_at, '') DESC, co.ownership_id DESC
    ");
    $stmt->execute([GENESIS_NFT_COLLECTION_KEY, $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}