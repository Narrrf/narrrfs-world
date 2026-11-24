-- Christmas Advent 2026 Reward Engine V1 additive schema.
-- Extends tbl_christmas_advent_2026_claims and tbl_christmas_advent_2026_draft_revisions.
-- No IF NOT EXISTS, no nested transactions: the manifest runner owns BEGIN IMMEDIATE / COMMIT.

CREATE TABLE tbl_christmas_advent_2026_policies (
    policy_version TEXT PRIMARY KEY CHECK (length(trim(policy_version)) BETWEEN 3 AND 128),
    event_key TEXT NOT NULL CHECK (event_key = 'christmas_advent_2026'),
    source_draft_revision INTEGER NOT NULL CHECK (source_draft_revision > 0),
    source_draft_fingerprint TEXT NOT NULL CHECK (length(source_draft_fingerprint) = 64),
    policy_json TEXT NOT NULL CHECK (length(policy_json) BETWEEN 2 AND 131072),
    policy_fingerprint TEXT NOT NULL CHECK (length(policy_fingerprint) = 64),
    status TEXT NOT NULL CHECK (status IN ('candidate', 'approved', 'retired')),
    min_per_player_dspoinc INTEGER NOT NULL CHECK (min_per_player_dspoinc > 0),
    max_per_player_dspoinc INTEGER NOT NULL CHECK (max_per_player_dspoinc >= min_per_player_dspoinc),
    max_event_dspoinc INTEGER NOT NULL CHECK (max_event_dspoinc >= max_per_player_dspoinc),
    max_bonus_grants INTEGER NOT NULL CHECK (max_bonus_grants >= 0),
    created_by TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_by TEXT,
    approved_at TEXT,
    retired_by TEXT,
    retired_at TEXT,

    CHECK (
        (status = 'candidate' AND approved_by IS NULL AND approved_at IS NULL AND retired_by IS NULL AND retired_at IS NULL)
        OR (status = 'approved' AND approved_by IS NOT NULL AND approved_at IS NOT NULL AND retired_by IS NULL AND retired_at IS NULL)
        OR (status = 'retired' AND approved_by IS NOT NULL AND approved_at IS NOT NULL AND retired_by IS NOT NULL AND retired_at IS NOT NULL)
    ),

    FOREIGN KEY (source_draft_revision) REFERENCES tbl_christmas_advent_2026_draft_revisions(revision)
);

CREATE UNIQUE INDEX idx_christmas26_policies_single_approved
    ON tbl_christmas_advent_2026_policies (event_key)
    WHERE status = 'approved';

CREATE TRIGGER trg_christmas26_policies_no_delete
BEFORE DELETE ON tbl_christmas_advent_2026_policies
BEGIN
    SELECT RAISE(ABORT, 'christmas26_policy_delete_forbidden');
END;

CREATE TRIGGER trg_christmas26_policies_immutable
BEFORE UPDATE ON tbl_christmas_advent_2026_policies
WHEN OLD.status IN ('approved', 'retired')
AND (
    OLD.status = 'retired'
    OR NEW.status != 'retired'
    OR NEW.policy_version != OLD.policy_version
    OR NEW.event_key != OLD.event_key
    OR NEW.source_draft_revision != OLD.source_draft_revision
    OR NEW.source_draft_fingerprint != OLD.source_draft_fingerprint
    OR NEW.policy_json != OLD.policy_json
    OR NEW.policy_fingerprint != OLD.policy_fingerprint
    OR NEW.min_per_player_dspoinc != OLD.min_per_player_dspoinc
    OR NEW.max_per_player_dspoinc != OLD.max_per_player_dspoinc
    OR NEW.max_event_dspoinc != OLD.max_event_dspoinc
    OR NEW.max_bonus_grants != OLD.max_bonus_grants
    OR NEW.created_by != OLD.created_by
    OR NEW.created_at != OLD.created_at
    OR NEW.approved_by IS NOT OLD.approved_by
    OR NEW.approved_at IS NOT OLD.approved_at
)
BEGIN
    SELECT RAISE(ABORT, 'christmas26_policy_immutable');
END;

CREATE TABLE tbl_christmas_advent_2026_claim_receipts (
    claim_id INTEGER PRIMARY KEY,
    user_id TEXT NOT NULL,
    day_number INTEGER NOT NULL CHECK (day_number BETWEEN 1 AND 24),
    calendar_date TEXT NOT NULL CHECK (calendar_date = printf('2026-12-%02d', day_number)),
    policy_version TEXT NOT NULL,
    policy_fingerprint TEXT NOT NULL CHECK (length(policy_fingerprint) = 64),
    dspoinc_min INTEGER NOT NULL CHECK (dspoinc_min > 0),
    dspoinc_max INTEGER NOT NULL CHECK (dspoinc_max >= dspoinc_min),
    awarded_dspoinc INTEGER NOT NULL CHECK (awarded_dspoinc BETWEEN dspoinc_min AND dspoinc_max),
    bonus_status TEXT NOT NULL CHECK (
        bonus_status IN ('disabled', 'miss', 'daily_winner_cap', 'player_event_cap', 'out_of_stock', 'ineligible_item', 'delivered')
    ),
    bonus_item_id INTEGER,
    bonus_item_name TEXT,
    bonus_quantity INTEGER NOT NULL DEFAULT 0 CHECK (bonus_quantity IN (0, 1)),
    inventory_id INTEGER,
    inventory_quantity_after INTEGER,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (user_id, day_number),

    CHECK (
        (
            bonus_status = 'delivered'
            AND bonus_item_id IS NOT NULL
            AND bonus_item_name IS NOT NULL
            AND length(trim(bonus_item_name)) > 0
            AND bonus_quantity = 1
            AND inventory_id IS NOT NULL
            AND inventory_id > 0
            AND inventory_quantity_after IS NOT NULL
            AND inventory_quantity_after >= 1
        )
        OR (
            bonus_status != 'delivered'
            AND bonus_item_id IS NULL
            AND bonus_item_name IS NULL
            AND bonus_quantity = 0
            AND inventory_id IS NULL
            AND inventory_quantity_after IS NULL
        )
    ),

    FOREIGN KEY (claim_id) REFERENCES tbl_christmas_advent_2026_claims(claim_id),
    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id),
    FOREIGN KEY (policy_version) REFERENCES tbl_christmas_advent_2026_policies(policy_version),
    FOREIGN KEY (bonus_item_id) REFERENCES tbl_store_items(item_id)
);

CREATE INDEX idx_christmas26_claim_receipts_day_bonus
    ON tbl_christmas_advent_2026_claim_receipts (day_number, bonus_status);

CREATE INDEX idx_christmas26_claim_receipts_user_bonus
    ON tbl_christmas_advent_2026_claim_receipts (user_id, bonus_status);

CREATE TRIGGER trg_christmas26_claim_receipts_no_update
BEFORE UPDATE ON tbl_christmas_advent_2026_claim_receipts
BEGIN
    SELECT RAISE(ABORT, 'christmas26_claim_receipt_immutable');
END;

CREATE TRIGGER trg_christmas26_claim_receipts_no_delete
BEFORE DELETE ON tbl_christmas_advent_2026_claim_receipts
BEGIN
    SELECT RAISE(ABORT, 'christmas26_claim_receipt_immutable');
END;

CREATE TABLE tbl_christmas_advent_2026_notify_outbox (
    claim_id INTEGER PRIMARY KEY,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'sent', 'failed')),
    attempts INTEGER NOT NULL DEFAULT 0 CHECK (attempts >= 0),
    reclaimed_after_lease INTEGER NOT NULL DEFAULT 0 CHECK (reclaimed_after_lease IN (0, 1)),
    claim_token TEXT,
    lease_expires_at TEXT,
    discord_channel_id TEXT,
    discord_message_id TEXT,
    last_error TEXT,
    next_attempt_at TEXT,
    sent_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CHECK (
        status != 'sent'
        OR (discord_channel_id IS NOT NULL AND discord_message_id IS NOT NULL AND sent_at IS NOT NULL)
    ),

    FOREIGN KEY (claim_id) REFERENCES tbl_christmas_advent_2026_claims(claim_id)
);

CREATE INDEX idx_christmas26_notify_outbox_poll
    ON tbl_christmas_advent_2026_notify_outbox (status, next_attempt_at, claim_id);

CREATE TRIGGER trg_christmas26_notify_outbox_no_delete
BEFORE DELETE ON tbl_christmas_advent_2026_notify_outbox
BEGIN
    SELECT RAISE(ABORT, 'christmas26_notify_outbox_delete_forbidden');
END;
