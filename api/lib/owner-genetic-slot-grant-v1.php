<?php
declare(strict_types=1);

/**
 * Owner Control R5.4: grant exactly one additional user-level Genetic Item research slot.
 * DEVS FOR DECADES: no item, timer, Lab, NFT, staking, or economy table may be changed here.
 * This helper is independent of HTTP and operates only on the passed SQLite connection.
 */
final class NarrrfsOwnerGeneticSlotGrantException extends RuntimeException
{
    public function __construct(private string $publicCode, private int $httpStatus)
    {
        parent::__construct($publicCode);
    }
    public function publicCode(): string { return $this->publicCode; }
    public function httpStatus(): int { return $this->httpStatus; }
}

/** Grant one slot and record its provenance atomically; rejects stale or invalid transitions. */
function narrrfs_owner_grant_genetic_slot(SQLite3 $db, array $input, string $ownerSubject): array
{
    $id = $input['user_id'] ?? null;
    $expected = $input['expected_upgrade_slots'] ?? null;
    $requested = $input['new_upgrade_slots'] ?? null;
    $reason = $input['reason'] ?? null;
    if (!is_string($id) || preg_match('/^\d{17,20}$/D', $id) !== 1 ||
        !is_string($ownerSubject) || preg_match('/^\d{17,20}$/D', $ownerSubject) !== 1 ||
        !is_int($expected) || !in_array($expected, [1, 2], true) ||
        !is_int($requested) || $requested !== $expected + 1 ||
        $reason !== '1k_mint_contest') {
        throw new NarrrfsOwnerGeneticSlotGrantException('invalid_grant', 400);
    }

    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    if (!$db->exec('PRAGMA foreign_keys = ON') || !$db->exec('BEGIN IMMEDIATE')) {
        throw new RuntimeException('Unable to begin entitlement transaction.');
    }
    $transactionOpen = true;
    try {
        $user = $db->prepare('SELECT discord_id FROM tbl_users WHERE discord_id = :id LIMIT 1');
        $user->bindValue(':id', $id, SQLITE3_TEXT);
        if ($user->execute()->fetchArray(SQLITE3_ASSOC) === false) {
            throw new NarrrfsOwnerGeneticSlotGrantException('user_not_found', 404);
        }
        // Refuse malformed entitlement rows rather than silently repairing protected state.
        $existing = $db->prepare('SELECT upgrade_slots FROM tbl_user_genetic_upgrade_slots WHERE user_id = :id LIMIT 1');
        $existing->bindValue(':id', $id, SQLITE3_TEXT);
        $row = $existing->execute()->fetchArray(SQLITE3_ASSOC);
        $current = 1;
        if ($row !== false) {
            $raw = trim((string)$row['upgrade_slots']);
            if (preg_match('/^[1-3]$/D', $raw) !== 1) {
                throw new NarrrfsOwnerGeneticSlotGrantException('invalid_entitlement_state', 409);
            }
            $current = (int)$raw;
        }
        if ($current !== $expected) {
            throw new NarrrfsOwnerGeneticSlotGrantException('stale_entitlement', 409);
        }
        if ($row === false) {
            $write = $db->prepare('INSERT INTO tbl_user_genetic_upgrade_slots (user_id, upgrade_slots, created_at, updated_at, updated_by) VALUES (:id, :new, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :actor)');
        } else {
            $write = $db->prepare('UPDATE tbl_user_genetic_upgrade_slots SET upgrade_slots = :new, updated_at = CURRENT_TIMESTAMP, updated_by = :actor WHERE user_id = :id AND upgrade_slots = :old');
            $write->bindValue(':old', $expected, SQLITE3_INTEGER);
        }
        $write->bindValue(':id', $id, SQLITE3_TEXT);
        $write->bindValue(':new', $requested, SQLITE3_INTEGER);
        $write->bindValue(':actor', $ownerSubject, SQLITE3_TEXT);
        $write->execute();
        if ($db->changes() !== 1) { throw new RuntimeException('Entitlement write count mismatch.'); }

        $audit = $db->prepare("INSERT INTO tbl_user_genetic_upgrade_slot_audit (user_id, old_upgrade_slots, new_upgrade_slots, admin_user_id, action_type) VALUES (:id, :old, :new, :actor, 'owner_control_1k_mint_contest_slot_grant')");
        $audit->bindValue(':id', $id, SQLITE3_TEXT);
        $audit->bindValue(':old', $current, SQLITE3_INTEGER);
        $audit->bindValue(':new', $requested, SQLITE3_INTEGER);
        $audit->bindValue(':actor', $ownerSubject, SQLITE3_TEXT);
        $audit->execute();
        if ($db->changes() !== 1) { throw new RuntimeException('Audit write count mismatch.'); }
        if (!$db->exec('COMMIT')) { throw new RuntimeException('Entitlement commit failed.'); }
        $transactionOpen = false;
        return ['user_id' => $id, 'old_upgrade_slots' => $current, 'new_upgrade_slots' => $requested,
            'action_type' => 'owner_control_1k_mint_contest_slot_grant'];
    } finally {
        if ($transactionOpen) { $db->exec('ROLLBACK'); }
    }
}
