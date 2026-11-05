-- Immutable Owner Control Bug Tracker R2.1 schema publication.
-- This file is applied only by the manifest runner; it must never be fed to a
-- live shell manually. Existing reports begin at version 1.
ALTER TABLE tbl_bug_reports
    ADD COLUMN owner_control_version INTEGER NOT NULL DEFAULT 1
    CHECK (owner_control_version >= 1);

CREATE TABLE tbl_owner_control_bug_update_receipts (
    receipt_id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_subject TEXT NOT NULL,
    idempotency_key_hash TEXT NOT NULL,
    request_hash TEXT NOT NULL,
    bug_id INTEGER NOT NULL,
    expected_version INTEGER NOT NULL,
    result_version INTEGER NOT NULL,
    result_json TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    UNIQUE (actor_subject, idempotency_key_hash),
    FOREIGN KEY (bug_id) REFERENCES tbl_bug_reports(bug_id)
);

CREATE INDEX idx_owner_control_bug_update_receipts_expiry
    ON tbl_owner_control_bug_update_receipts (expires_at);
