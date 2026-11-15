-- M2B: audit and idempotency for owner commercial edits. Runner owns the transaction.
CREATE TABLE tbl_owner_control_lab_store_offer_commercial_audit (
    audit_id INTEGER PRIMARY KEY AUTOINCREMENT,
    offer_id INTEGER NOT NULL,
    actor_subject TEXT NOT NULL,
    prior_version INTEGER NOT NULL,
    resulting_version INTEGER NOT NULL,
    prior_lp_price INTEGER,
    resulting_lp_price INTEGER,
    prior_dspoinc_price INTEGER,
    resulting_dspoinc_price INTEGER,
    prior_stock INTEGER NOT NULL,
    resulting_stock INTEGER NOT NULL,
    request_hash TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE tbl_owner_control_lab_store_offer_commercial_receipts (
    receipt_id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_subject TEXT NOT NULL,
    idempotency_key_hash TEXT NOT NULL,
    request_hash TEXT NOT NULL,
    offer_id INTEGER NOT NULL,
    resulting_version INTEGER NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    UNIQUE (actor_subject, idempotency_key_hash)
);
CREATE TRIGGER owner_lab_store_commercial_audit_no_update
    BEFORE UPDATE ON tbl_owner_control_lab_store_offer_commercial_audit
    BEGIN SELECT RAISE(ABORT, 'immutable audit'); END;
CREATE TRIGGER owner_lab_store_commercial_audit_no_delete
    BEFORE DELETE ON tbl_owner_control_lab_store_offer_commercial_audit
    BEGIN SELECT RAISE(ABORT, 'immutable audit'); END;
CREATE INDEX idx_owner_lab_store_commercial_receipts_expiry
    ON tbl_owner_control_lab_store_offer_commercial_receipts (expires_at);
