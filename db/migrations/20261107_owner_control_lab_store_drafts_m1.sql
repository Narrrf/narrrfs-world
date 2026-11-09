-- Immutable Owner Control Lab Store M1 draft-only schema.
-- This migration is applied only through the verified manifest runner.
-- Drafts deliberately do not reference live offers, source catalog rows, prices,
-- stock, eligibility, purchases, inventory, or any protected delivery state.

CREATE TABLE tbl_owner_control_lab_store_drafts (
    draft_id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL CHECK (length(trim(title)) BETWEEN 1 AND 200),
    category TEXT NOT NULL CHECK (length(trim(category)) BETWEEN 1 AND 80),
    description TEXT NOT NULL CHECK (length(trim(description)) BETWEEN 1 AND 4000),
    media_reference TEXT NULL CHECK (media_reference IS NULL OR length(media_reference) <= 500),
    state TEXT NOT NULL DEFAULT 'draft' CHECK (state = 'draft'),
    version INTEGER NOT NULL DEFAULT 1 CHECK (version >= 1),
    created_by TEXT NOT NULL,
    updated_by TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_owner_control_lab_store_drafts_updated
    ON tbl_owner_control_lab_store_drafts (updated_at DESC, draft_id DESC);

CREATE TABLE tbl_owner_control_lab_store_draft_audit (
    audit_id INTEGER PRIMARY KEY AUTOINCREMENT,
    draft_id INTEGER NOT NULL,
    actor_subject TEXT NOT NULL,
    action TEXT NOT NULL CHECK (action IN ('created', 'updated')),
    prior_version INTEGER NULL,
    resulting_version INTEGER NOT NULL CHECK (resulting_version >= 1),
    change_hash TEXT NOT NULL CHECK (length(change_hash) = 64),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (draft_id) REFERENCES tbl_owner_control_lab_store_drafts(draft_id)
);

CREATE INDEX idx_owner_control_lab_store_draft_audit_draft
    ON tbl_owner_control_lab_store_draft_audit (draft_id, audit_id DESC);

CREATE TABLE tbl_owner_control_lab_store_draft_receipts (
    receipt_id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_subject TEXT NOT NULL,
    idempotency_key_hash TEXT NOT NULL,
    request_hash TEXT NOT NULL,
    draft_id INTEGER NOT NULL,
    resulting_version INTEGER NOT NULL CHECK (resulting_version >= 1),
    action TEXT NOT NULL CHECK (action IN ('created', 'updated')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    UNIQUE (actor_subject, idempotency_key_hash),
    FOREIGN KEY (draft_id) REFERENCES tbl_owner_control_lab_store_drafts(draft_id)
);

CREATE INDEX idx_owner_control_lab_store_draft_receipts_expiry
    ON tbl_owner_control_lab_store_draft_receipts (expires_at);
