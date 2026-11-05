-- Christmas Advent 2026 claim-marker foundation.
--
-- This migration creates only the durable marker and its reconciliation
-- references. It does not activate a reward schedule or a payout path.
-- Apply through the approved SQLite migration procedure with foreign-key
-- enforcement enabled on the claim transaction connection.
--
-- Deliberately no IF NOT EXISTS: a repeated application must fail rather
-- than silently accepting an unknown pre-existing schema. The verified
-- manifest runner owns BEGIN IMMEDIATE, COMMIT, rollback, and the matching
-- migration-ledger record; this immutable file must not open a nested
-- transaction.

CREATE TABLE tbl_christmas_advent_2026_claims (
    claim_id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_key TEXT NOT NULL
        CHECK (event_key = 'christmas_advent_2026'),
    user_id TEXT NOT NULL,
    calendar_date TEXT NOT NULL
        CHECK (calendar_date >= '2026-12-01' AND calendar_date <= '2026-12-24'),
    day_number INTEGER NOT NULL
        CHECK (day_number BETWEEN 1 AND 24),
    reward_dspoinc INTEGER NOT NULL
        CHECK (reward_dspoinc > 0),
    actor_user_id TEXT NOT NULL
        CHECK (actor_user_id = user_id),
    score_id INTEGER NOT NULL UNIQUE,
    adjustment_id INTEGER NOT NULL UNIQUE,
    claimed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CHECK (calendar_date = printf('2026-12-%02d', day_number)),

    UNIQUE (event_key, user_id, calendar_date),
    UNIQUE (event_key, user_id, day_number),

    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id),
    FOREIGN KEY (actor_user_id) REFERENCES tbl_users(discord_id),
    FOREIGN KEY (score_id) REFERENCES tbl_user_scores(id),
    FOREIGN KEY (adjustment_id) REFERENCES tbl_score_adjustments(id)
);

-- Supports authoritative event/date aggregation for a future, separately
-- approved claim transaction. It does not itself implement a budget policy.
CREATE INDEX idx_christmas_advent_2026_claims_event_date
    ON tbl_christmas_advent_2026_claims (event_key, calendar_date, claimed_at);
