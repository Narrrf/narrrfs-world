<?php
/**
 * Giveaway Store Item Delivery V1 — pure transaction service.
 *
 * DEVS FOR DECADES:
 * The Discord bot identifies a persisted winner; the server alone chooses the
 * item and quantity from tbl_giveaways. Inventory and its winner receipt must
 * commit together, so a replay can never grant a second item.
 * No Genesis NFTs, genetic traits, DSPOINC, token payouts, or airdrops are changed.
 */
declare(strict_types=1);

final class GiveawayStoreDeliveryV1Error extends RuntimeException
{
    public function __construct(string $errorCode, int $httpStatus)
    {
        parent::__construct($errorCode, $httpStatus);
    }
}

/** Deliver one pending, persisted Store Item reward, or replay its saved receipt. */
function giveaway_store_delivery_v1(PDO $database, string $giveawayId, string $winnerUserId): array
{
    if (!preg_match('/\Agiveaway_[0-9]{13}_[a-z0-9]{9}\z/D', $giveawayId)
        || !preg_match('/\A[0-9]{16,20}\z/D', $winnerUserId)) {
        throw new GiveawayStoreDeliveryV1Error('invalid_giveaway_or_winner_id', 400);
    }

    // BEGIN IMMEDIATE serializes all writers before inventory or receipt reads.
    // This prevents two bot processes from both treating one pending prize as new.
    $database->exec('PRAGMA busy_timeout = 10000');
    $database->exec('BEGIN IMMEDIATE');
    $transactionOpen = true;

    try {
        $statement = $database->prepare(
            'SELECT giveaway_id, status, reward_type, reward_reference_id, reward_quantity, reward_snapshot_title, prize '
            . 'FROM tbl_giveaways WHERE giveaway_id = ? LIMIT 1'
        );
        $statement->execute([$giveawayId]);
        $giveaway = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($giveaway)) {
            throw new GiveawayStoreDeliveryV1Error('giveaway_not_found', 404);
        }
        if ((string) $giveaway['status'] !== 'ended') {
            throw new GiveawayStoreDeliveryV1Error('giveaway_not_ended', 409);
        }
        if ((string) $giveaway['reward_type'] !== 'store_item') {
            throw new GiveawayStoreDeliveryV1Error('unsupported_reward_type', 409);
        }

        $itemId = filter_var($giveaway['reward_reference_id'], FILTER_VALIDATE_INT);
        $quantity = filter_var($giveaway['reward_quantity'], FILTER_VALIDATE_INT);
        if ($itemId === false || $itemId < 1 || $quantity === false || $quantity < 1 || $quantity > 999) {
            throw new GiveawayStoreDeliveryV1Error('invalid_stored_reward_configuration', 409);
        }

        $statement = $database->prepare(
            'SELECT id, delivery_status, delivery_payload_json FROM tbl_giveaway_winners '
            . 'WHERE giveaway_id = ? AND user_id = ? ORDER BY id LIMIT 2'
        );
        $statement->execute([$giveawayId, $winnerUserId]);
        $winnerRows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (count($winnerRows) === 0) {
            throw new GiveawayStoreDeliveryV1Error('winner_not_found', 404);
        }
        if (count($winnerRows) !== 1) {
            throw new GiveawayStoreDeliveryV1Error('ambiguous_winner_records', 409);
        }
        $winner = $winnerRows[0];
        $deliveryStatus = trim((string) ($winner['delivery_status'] ?? ''));
        $savedPayload = trim((string) ($winner['delivery_payload_json'] ?? ''));

        if ($deliveryStatus === 'delivered') {
            $previousReceipt = json_decode($savedPayload, true);
            if (!is_array($previousReceipt)
                || ($previousReceipt['giveaway_id'] ?? null) !== $giveawayId
                || ($previousReceipt['winner_user_id'] ?? null) !== $winnerUserId
                || ($previousReceipt['reward_type'] ?? null) !== 'store_item'
                || (int) ($previousReceipt['reward_reference_id'] ?? 0) !== $itemId) {
                throw new GiveawayStoreDeliveryV1Error('unverifiable_delivery_receipt', 409);
            }
            $database->exec('COMMIT');
            $transactionOpen = false;
            return ['success' => true, 'already_delivered' => true] + $previousReceipt;
        }
        if ($deliveryStatus !== 'pending' || $savedPayload !== '') {
            throw new GiveawayStoreDeliveryV1Error('delivery_requires_manual_review', 409);
        }

        $statement = $database->prepare('SELECT item_id, item_name FROM tbl_store_items WHERE item_id = ? LIMIT 1');
        $statement->execute([$itemId]);
        $storeItem = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($storeItem) || trim((string) ($storeItem['item_name'] ?? '')) === '') {
            throw new GiveawayStoreDeliveryV1Error('store_item_not_found', 404);
        }

        $statement = $database->prepare(
            'SELECT inventory_id, quantity FROM tbl_user_inventory '
            . 'WHERE user_id = ? AND item_id = ? ORDER BY inventory_id LIMIT 2'
        );
        $statement->execute([$winnerUserId, $itemId]);
        $inventoryRows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (count($inventoryRows) > 1) {
            throw new GiveawayStoreDeliveryV1Error('ambiguous_inventory_rows', 409);
        }

        if (count($inventoryRows) === 1) {
            $inventoryId = (int) $inventoryRows[0]['inventory_id'];
            $existingQuantity = filter_var($inventoryRows[0]['quantity'], FILTER_VALIDATE_INT);
            if ($inventoryId < 1 || $existingQuantity === false || $existingQuantity < 0) {
                throw new GiveawayStoreDeliveryV1Error('invalid_existing_inventory', 409);
            }
            $totalQuantity = $existingQuantity + $quantity;
            $statement = $database->prepare(
                'UPDATE tbl_user_inventory SET quantity = ? '
                . 'WHERE inventory_id = ? AND user_id = ? AND item_id = ? AND quantity = ?'
            );
            $statement->execute([$totalQuantity, $inventoryId, $winnerUserId, $itemId, $existingQuantity]);
            if ($statement->rowCount() !== 1) {
                throw new GiveawayStoreDeliveryV1Error('inventory_update_conflict', 409);
            }
        } else {
            $statement = $database->prepare(
                'INSERT INTO tbl_user_inventory (user_id, item_id, quantity, acquired_at) '
                . 'VALUES (?, ?, ?, CURRENT_TIMESTAMP)'
            );
            $statement->execute([$winnerUserId, $itemId, $quantity]);
            if ($statement->rowCount() !== 1) {
                throw new GiveawayStoreDeliveryV1Error('inventory_insert_failed', 500);
            }
            $inventoryId = (int) $database->lastInsertId();
            $totalQuantity = $quantity;
        }

        // Preserve the bot's established response keys for its winner DM.
        $receipt = [
            'giveaway_id' => $giveawayId,
            'winner_user_id' => $winnerUserId,
            'reward_type' => 'store_item',
            'reward_reference_id' => $itemId,
            'reward_snapshot_title' => (string) ($giveaway['reward_snapshot_title'] ?: $giveaway['prize']),
            'delivery_payload' => [
                'inventory_id' => $inventoryId,
                'item_id' => $itemId,
                'item_name' => (string) $storeItem['item_name'],
                'quantity' => $quantity,
                'total_quantity' => $totalQuantity,
                'source' => 'giveaway_reward'
            ],
            'used_fallback_dspoinc' => false
        ];
        $receiptJson = json_encode($receipt, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $statement = $database->prepare(
            "UPDATE tbl_giveaway_winners SET delivery_status = 'delivered', "
            . 'delivered_at = CURRENT_TIMESTAMP, delivery_payload_json = ? '
            . "WHERE id = ? AND giveaway_id = ? AND user_id = ? AND delivery_status = 'pending' "
            . "AND (delivery_payload_json IS NULL OR TRIM(delivery_payload_json) = '')"
        );
        $statement->execute([$receiptJson, $winner['id'], $giveawayId, $winnerUserId]);
        if ($statement->rowCount() !== 1) {
            throw new GiveawayStoreDeliveryV1Error('delivery_receipt_conflict', 409);
        }

        $database->exec('COMMIT');
        $transactionOpen = false;
        return ['success' => true, 'already_delivered' => false] + $receipt;
    } catch (Throwable $error) {
        if ($transactionOpen) {
            $database->exec('ROLLBACK');
        }
        throw $error;
    }
}
