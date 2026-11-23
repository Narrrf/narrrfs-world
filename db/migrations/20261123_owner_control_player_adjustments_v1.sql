-- Owner-only player adjustments: the manifest runner owns the transaction.
CREATE TABLE tbl_owner_player_adjustment_audit (
    audit_id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_subject TEXT NOT NULL,
    target_user_id TEXT NOT NULL,
    adjustment_kind TEXT NOT NULL CHECK (adjustment_kind IN ('points', 'inventory')),
    item_id INTEGER,
    signed_amount INTEGER NOT NULL CHECK (signed_amount <> 0),
    before_value INTEGER NOT NULL,
    after_value INTEGER NOT NULL,
    reason TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER owner_player_adjustment_audit_no_update
BEFORE UPDATE ON tbl_owner_player_adjustment_audit BEGIN SELECT RAISE(ABORT, 'immutable audit'); END;
CREATE TRIGGER owner_player_adjustment_audit_no_delete
BEFORE DELETE ON tbl_owner_player_adjustment_audit BEGIN SELECT RAISE(ABORT, 'immutable audit'); END;
CREATE TABLE tbl_owner_player_adjustment_receipts (
    actor_subject TEXT NOT NULL,
    idempotency_key_hash TEXT NOT NULL,
    request_hash TEXT NOT NULL,
    audit_id INTEGER NOT NULL REFERENCES tbl_owner_player_adjustment_audit(audit_id),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (actor_subject, idempotency_key_hash)
);
CREATE TRIGGER owner_player_adjustment_receipts_no_update
BEFORE UPDATE ON tbl_owner_player_adjustment_receipts BEGIN SELECT RAISE(ABORT, 'immutable receipt'); END;
CREATE TRIGGER owner_player_adjustment_receipts_no_delete
BEFORE DELETE ON tbl_owner_player_adjustment_receipts BEGIN SELECT RAISE(ABORT, 'immutable receipt'); END;
