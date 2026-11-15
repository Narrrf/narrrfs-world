-- M2A: immutable Owner Control state transition support for existing live offers.
-- This migration changes only the concurrency version and adds Owner Control audit/receipt tables.
ALTER TABLE tbl_mouse_lp_store_offers
    ADD COLUMN owner_control_version INTEGER NOT NULL DEFAULT 1
    CHECK (owner_control_version >= 1);

CREATE TABLE tbl_owner_control_lab_store_offer_state_audit (
    audit_id INTEGER PRIMARY KEY AUTOINCREMENT,
    offer_id INTEGER NOT NULL,
    actor_subject TEXT NOT NULL,
    prior_version INTEGER NOT NULL,
    resulting_version INTEGER NOT NULL,
    prior_is_active INTEGER NOT NULL CHECK (prior_is_active IN (0,1)),
    resulting_is_active INTEGER NOT NULL CHECK (resulting_is_active IN (0,1)),
    request_hash TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tbl_owner_control_lab_store_offer_state_receipts (
    receipt_id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_subject TEXT NOT NULL,
    idempotency_key_hash TEXT NOT NULL,
    request_hash TEXT NOT NULL,
    offer_id INTEGER NOT NULL,
    resulting_version INTEGER NOT NULL,
    resulting_is_active INTEGER NOT NULL CHECK (resulting_is_active IN (0,1)),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    UNIQUE (actor_subject, idempotency_key_hash)
);

CREATE INDEX idx_owner_control_lab_store_offer_state_receipts_expiry
    ON tbl_owner_control_lab_store_offer_state_receipts (expires_at);
