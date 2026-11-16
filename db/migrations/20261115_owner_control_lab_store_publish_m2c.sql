-- Catalog-backed publishing keeps planning metadata private and records one
-- immutable publication per draft. The manifest runner owns the transaction.
CREATE TABLE tbl_owner_control_lab_store_publish_audit (
    publish_id INTEGER PRIMARY KEY AUTOINCREMENT,
    draft_id INTEGER NOT NULL UNIQUE,
    draft_version INTEGER NOT NULL,
    offer_id INTEGER NOT NULL UNIQUE,
    actor_subject TEXT NOT NULL,
    source_type TEXT NOT NULL,
    source_item_id INTEGER NOT NULL,
    request_hash TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER owner_lab_publish_audit_no_update BEFORE UPDATE ON tbl_owner_control_lab_store_publish_audit BEGIN SELECT RAISE(ABORT, 'immutable audit'); END;
CREATE TRIGGER owner_lab_publish_audit_no_delete BEFORE DELETE ON tbl_owner_control_lab_store_publish_audit BEGIN SELECT RAISE(ABORT, 'immutable audit'); END;
CREATE TABLE tbl_owner_control_lab_store_publish_receipts (
    actor_subject TEXT NOT NULL,
    idempotency_key_hash TEXT NOT NULL,
    request_hash TEXT NOT NULL,
    draft_id INTEGER NOT NULL,
    offer_id INTEGER NOT NULL,
    expires_at TEXT NOT NULL,
    PRIMARY KEY (actor_subject, idempotency_key_hash)
);
CREATE TRIGGER owner_lab_publish_receipts_no_update BEFORE UPDATE ON tbl_owner_control_lab_store_publish_receipts BEGIN SELECT RAISE(ABORT, 'immutable receipt'); END;
CREATE TRIGGER owner_lab_publish_receipts_no_delete BEFORE DELETE ON tbl_owner_control_lab_store_publish_receipts BEGIN SELECT RAISE(ABORT, 'immutable receipt'); END;
