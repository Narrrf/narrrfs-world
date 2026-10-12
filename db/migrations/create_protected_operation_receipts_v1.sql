CREATE TABLE tbl_protected_operation_receipts (
    receipt_id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_user_id TEXT NOT NULL CHECK (length(trim(actor_user_id)) > 0),
    contract_version TEXT NOT NULL CHECK (contract_version = 'v1'),
    operation_kind TEXT NOT NULL CHECK (
        operation_kind IN ('trait_unlock', 'riddle_reward', 'chest_reward')
    ),
    action_key TEXT NOT NULL CHECK (length(trim(action_key)) > 0),
    claim_key TEXT NOT NULL CHECK (length(trim(claim_key)) > 0),
    policy_version TEXT NOT NULL CHECK (length(trim(policy_version)) > 0),
    idempotency_key TEXT NOT NULL CHECK (length(trim(idempotency_key)) BETWEEN 16 AND 200),
    request_fingerprint TEXT NOT NULL CHECK (
        request_fingerprint GLOB '[0-9a-f]*' AND length(request_fingerprint) = 64
    ),
    status TEXT NOT NULL CHECK (status = 'completed'),
    outcome_code TEXT NOT NULL CHECK (length(trim(outcome_code)) > 0),
    outcome_json TEXT NOT NULL,
    audit_json TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (actor_user_id, operation_kind, idempotency_key),
    UNIQUE (actor_user_id, operation_kind, claim_key)
);

CREATE INDEX idx_protected_operation_receipts_actor_completed
    ON tbl_protected_operation_receipts (actor_user_id, completed_at DESC);