<?php
/**
 * Local/production migration entry for Genetic Item Multi-Upgrade Slots V1.
 *
 * It creates only user-level entitlement and audit tables. It does not insert
 * entitlement rows, alter Genetic Items, or change any economy state.
 */

require_once __DIR__ . '/../api/config/database.php';

$pdo = getDatabaseConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->beginTransaction();

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbl_user_genetic_upgrade_slots (
            user_id TEXT PRIMARY KEY,
            upgrade_slots INTEGER NOT NULL CHECK (upgrade_slots BETWEEN 1 AND 3),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_by TEXT NOT NULL
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbl_user_genetic_upgrade_slot_audit (
            audit_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT NOT NULL,
            old_upgrade_slots INTEGER NOT NULL,
            new_upgrade_slots INTEGER NOT NULL CHECK (new_upgrade_slots BETWEEN 1 AND 3),
            admin_user_id TEXT NOT NULL,
            action_type TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        'CREATE INDEX IF NOT EXISTS idx_user_genetic_upgrade_slot_audit_user_created_at
         ON tbl_user_genetic_upgrade_slot_audit(user_id, created_at DESC)'
    );

    $pdo->commit();
    echo "Genetic upgrade slot schema is ready. No entitlement rows were created.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Migration failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
