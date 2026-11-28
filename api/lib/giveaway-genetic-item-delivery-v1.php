<?php
/**
 * Giveaway Genetic Item Delivery V1 — pure protected transaction service.
 *
 * The Discord bot supplies only persisted identifiers. This service reads the
 * reward, cap, fallback policy, and winner receipt from SQLite under one
 * transaction, so a retry can never grant a second Genetic Item or fallback.
 */
declare(strict_types=1);

final class GiveawayGeneticDeliveryV1Error extends RuntimeException
{
    public function __construct(string $errorCode, int $httpStatus)
    {
        parent::__construct($errorCode, $httpStatus);
    }
}

const GIVEAWAY_GENETIC_MAX_OWNED_PER_EXACT_TRAIT = 2;

function giveaway_genetic_delivery_v1_is_authorized(string $expectedSecret, string $authorization): bool
{
    if (strlen($expectedSecret) < 32
        || !preg_match('/\ABearer ([A-Za-z0-9_-]{32,256})\z/D', trim($authorization), $matches)) {
        return false;
    }

    return hash_equals($expectedSecret, $matches[1]);
}

function giveaway_genetic_delivery_v1_validate_ids(string $giveawayId, string $winnerUserId): void
{
    if (!preg_match('/\Agiveaway_[0-9]{13}_[a-z0-9]{9}\z/D', $giveawayId)
        || !preg_match('/\A[0-9]{16,20}\z/D', $winnerUserId)) {
        throw new GiveawayGeneticDeliveryV1Error('invalid_giveaway_or_winner_id', 400);
    }
}

function giveaway_genetic_delivery_v1_finish(PDO $database, bool &$transactionOpen, array $response): array
{
    $database->exec('COMMIT');
    $transactionOpen = false;
    return $response;
}

function giveaway_genetic_delivery_v1_save_winner_receipt(
    PDO $database,
    array $winner,
    string $giveawayId,
    string $winnerUserId,
    string $status,
    array $receipt
): void {
    $receiptJson = json_encode($receipt, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $statement = $database->prepare(
        "UPDATE tbl_giveaway_winners SET delivery_status = ?, "
        . "delivered_at = CASE WHEN ? = 'delivered' THEN CURRENT_TIMESTAMP ELSE delivered_at END, "
        . 'delivery_payload_json = ? '
        . "WHERE id = ? AND giveaway_id = ? AND user_id = ? AND delivery_status = 'pending' "
        . "AND (delivery_payload_json IS NULL OR TRIM(delivery_payload_json) = '')"
    );
    $statement->execute([$status, $status, $receiptJson, $winner['id'], $giveawayId, $winnerUserId]);
    if ($statement->rowCount() !== 1) {
        throw new GiveawayGeneticDeliveryV1Error('delivery_receipt_conflict', 409);
    }
}

/** Deliver one persisted Genetic Item reward, fallback, or durable manual-review receipt. */
function giveaway_genetic_delivery_v1(PDO $database, string $giveawayId, string $winnerUserId): array
{
    giveaway_genetic_delivery_v1_validate_ids($giveawayId, $winnerUserId);

    $database->exec('PRAGMA busy_timeout = 10000');
    $database->exec('BEGIN IMMEDIATE');
    $transactionOpen = true;

    try {
        $statement = $database->prepare(
            'SELECT giveaway_id, status, reward_type, reward_reference_id, reward_quantity, reward_snapshot_title, prize, '
            . 'fallback_dspoinc_enabled, fallback_dspoinc_min, fallback_dspoinc_max '
            . 'FROM tbl_giveaways WHERE giveaway_id = ? LIMIT 1'
        );
        $statement->execute([$giveawayId]);
        $giveaway = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($giveaway)) {
            throw new GiveawayGeneticDeliveryV1Error('giveaway_not_found', 404);
        }
        if ((string) $giveaway['status'] !== 'ended') {
            throw new GiveawayGeneticDeliveryV1Error('giveaway_not_ended', 409);
        }
        if ((string) $giveaway['reward_type'] !== 'genetic_trait') {
            throw new GiveawayGeneticDeliveryV1Error('unsupported_reward_type', 409);
        }

        $catalogId = filter_var($giveaway['reward_reference_id'], FILTER_VALIDATE_INT);
        $quantity = filter_var($giveaway['reward_quantity'], FILTER_VALIDATE_INT);
        if ($catalogId === false || $catalogId < 1 || $quantity !== 1) {
            throw new GiveawayGeneticDeliveryV1Error('invalid_stored_reward_configuration', 409);
        }

        $statement = $database->prepare(
            'SELECT id, delivery_status, delivery_payload_json FROM tbl_giveaway_winners '
            . 'WHERE giveaway_id = ? AND user_id = ? ORDER BY id LIMIT 2'
        );
        $statement->execute([$giveawayId, $winnerUserId]);
        $winnerRows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (count($winnerRows) === 0) {
            throw new GiveawayGeneticDeliveryV1Error('winner_not_found', 404);
        }
        if (count($winnerRows) !== 1) {
            throw new GiveawayGeneticDeliveryV1Error('ambiguous_winner_records', 409);
        }
        $winner = $winnerRows[0];
        $deliveryStatus = trim((string) ($winner['delivery_status'] ?? ''));
        $savedPayload = trim((string) ($winner['delivery_payload_json'] ?? ''));

        if (in_array($deliveryStatus, ['delivered', 'manual_review'], true)) {
            $previousReceipt = json_decode($savedPayload, true);
            if (!is_array($previousReceipt)
                || ($previousReceipt['giveaway_id'] ?? null) !== $giveawayId
                || ($previousReceipt['winner_user_id'] ?? null) !== $winnerUserId
                || ($previousReceipt['reward_type'] ?? null) !== 'genetic_trait'
                || (int) ($previousReceipt['reward_reference_id'] ?? 0) !== $catalogId) {
                throw new GiveawayGeneticDeliveryV1Error('unverifiable_delivery_receipt', 409);
            }
            return giveaway_genetic_delivery_v1_finish($database, $transactionOpen, [
                'success' => true,
                'already_delivered' => $deliveryStatus === 'delivered',
                'manual_review' => $deliveryStatus === 'manual_review'
            ] + $previousReceipt);
        }
        if ($deliveryStatus !== 'pending' || $savedPayload !== '') {
            throw new GiveawayGeneticDeliveryV1Error('delivery_requires_manual_review', 409);
        }

        $statement = $database->prepare(
            'SELECT catalog_id, display_title, trait_type, trait_value, is_active '
            . 'FROM tbl_genetic_trait_catalog WHERE catalog_id = ? LIMIT 1'
        );
        $statement->execute([$catalogId]);
        $catalog = $statement->fetch(PDO::FETCH_ASSOC);
        $traitType = trim((string) ($catalog['trait_type'] ?? ''));
        $traitValue = trim((string) ($catalog['trait_value'] ?? ''));
        if (!is_array($catalog) || (int) ($catalog['is_active'] ?? 0) !== 1 || $traitType === '' || $traitValue === '') {
            throw new GiveawayGeneticDeliveryV1Error('genetic_catalog_unavailable', 409);
        }
        $displayTitle = trim((string) ($catalog['display_title'] ?? $traitValue));

        $statement = $database->prepare(
            'SELECT COUNT(*) FROM tbl_user_genetic_items WHERE user_id = ? AND trait_type = ? AND trait_value = ?'
        );
        $statement->execute([$winnerUserId, $traitType, $traitValue]);
        $ownedCopies = (int) $statement->fetchColumn();

        $receiptBase = [
            'giveaway_id' => $giveawayId,
            'winner_user_id' => $winnerUserId,
            'reward_type' => 'genetic_trait',
            'reward_reference_id' => $catalogId,
            'reward_snapshot_title' => (string) ($giveaway['reward_snapshot_title'] ?: $giveaway['prize'])
        ];

        if ($ownedCopies >= GIVEAWAY_GENETIC_MAX_OWNED_PER_EXACT_TRAIT) {
            if ((int) ($giveaway['fallback_dspoinc_enabled'] ?? 0) !== 1) {
                $receipt = $receiptBase + [
                    'delivery_outcome' => 'manual_review',
                    'delivery_payload' => [
                        'reason' => 'already_max_owned',
                        'owned_copies' => $ownedCopies,
                        'max_owned_copies' => GIVEAWAY_GENETIC_MAX_OWNED_PER_EXACT_TRAIT,
                        'catalog_id' => $catalogId,
                        'trait_type' => $traitType,
                        'trait_value' => $traitValue,
                        'display_title' => $displayTitle
                    ],
                    'used_fallback_dspoinc' => false
                ];
                giveaway_genetic_delivery_v1_save_winner_receipt(
                    $database, $winner, $giveawayId, $winnerUserId, 'manual_review', $receipt
                );
                return giveaway_genetic_delivery_v1_finish($database, $transactionOpen, [
                    'success' => true,
                    'already_delivered' => false,
                    'manual_review' => true
                ] + $receipt);
            }

            $minimum = filter_var($giveaway['fallback_dspoinc_min'], FILTER_VALIDATE_INT);
            $maximum = filter_var($giveaway['fallback_dspoinc_max'], FILTER_VALIDATE_INT);
            if ($minimum === false || $maximum === false || $minimum < 1 || $maximum < $minimum) {
                throw new GiveawayGeneticDeliveryV1Error('invalid_fallback_dspoinc_configuration', 409);
            }
            $fallbackAmount = random_int($minimum, $maximum);
            $statement = $database->prepare(
                "INSERT INTO tbl_user_scores (user_id, score, game, source, timestamp) VALUES (?, ?, 'giveaway', 'giveaway_genetic_fallback_v1', CURRENT_TIMESTAMP)"
            );
            $statement->execute([$winnerUserId, $fallbackAmount]);
            $statement = $database->prepare(
                "INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason, timestamp) VALUES (?, 'giveaway_system', ?, 'add', 'giveaway_genetic_fallback_v1', CURRENT_TIMESTAMP)"
            );
            $statement->execute([$winnerUserId, $fallbackAmount]);

            $receipt = $receiptBase + [
                'delivery_outcome' => 'fallback_dspoinc',
                'delivery_payload' => [
                    'catalog_id' => $catalogId,
                    'trait_type' => $traitType,
                    'trait_value' => $traitValue,
                    'display_title' => $displayTitle,
                    'owned_copies' => $ownedCopies,
                    'fallback_dspoinc_amount' => $fallbackAmount,
                    'source' => 'giveaway_genetic_fallback_v1'
                ],
                'fallback_dspoinc_amount' => $fallbackAmount,
                'used_fallback_dspoinc' => true
            ];
            giveaway_genetic_delivery_v1_save_winner_receipt(
                $database, $winner, $giveawayId, $winnerUserId, 'delivered', $receipt
            );
            return giveaway_genetic_delivery_v1_finish($database, $transactionOpen, [
                'success' => true,
                'already_delivered' => false,
                'manual_review' => false
            ] + $receipt);
        }

        $statement = $database->prepare(
            "INSERT INTO tbl_user_genetic_items (user_id, catalog_id, trait_type, trait_value, current_level, upgrade_status, acquired_method, is_listed_for_sale, created_at, updated_at) "
            . "VALUES (?, ?, ?, ?, 1, 'idle', 'giveaway_reward', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
        );
        $statement->execute([$winnerUserId, $catalogId, $traitType, $traitValue]);
        $geneticItemId = (int) $database->lastInsertId();
        if ($geneticItemId < 1) {
            throw new GiveawayGeneticDeliveryV1Error('genetic_item_insert_failed', 500);
        }
        $historyPayload = json_encode([
            'catalog_id' => $catalogId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'acquired_method' => 'giveaway_reward'
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $statement = $database->prepare(
            "INSERT INTO tbl_genetic_item_history (genetic_item_id, listing_id, user_id, action_type, old_value_json, new_value_json, admin_user_id, created_at) "
            . "VALUES (?, NULL, ?, 'giveaway_grant', '{}', ?, 'giveaway_system', CURRENT_TIMESTAMP)"
        );
        $statement->execute([$geneticItemId, $winnerUserId, $historyPayload]);

        $receipt = $receiptBase + [
            'delivery_outcome' => 'genetic_item_grant',
            'delivery_payload' => [
                'genetic_item_id' => $geneticItemId,
                'catalog_id' => $catalogId,
                'trait_type' => $traitType,
                'trait_value' => $traitValue,
                'display_title' => $displayTitle,
                'current_level' => 1,
                'upgrade_status' => 'idle',
                'source' => 'giveaway_reward'
            ],
            'used_fallback_dspoinc' => false
        ];
        giveaway_genetic_delivery_v1_save_winner_receipt(
            $database, $winner, $giveawayId, $winnerUserId, 'delivered', $receipt
        );
        return giveaway_genetic_delivery_v1_finish($database, $transactionOpen, [
            'success' => true,
            'already_delivered' => false,
            'manual_review' => false
        ] + $receipt);
    } catch (Throwable $error) {
        if ($transactionOpen) {
            $database->exec('ROLLBACK');
        }
        throw $error;
    }
}
