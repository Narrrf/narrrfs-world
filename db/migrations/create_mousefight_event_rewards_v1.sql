-- MouseFight Event Lucky Loser V1 durable reward ledger.
-- One-time local migration; no foreign keys by project SQLite policy.

CREATE TABLE tbl_mousefight_event_rewards (
    reward_id INTEGER PRIMARY KEY AUTOINCREMENT,
    fight_id TEXT NOT NULL,
    reward_type TEXT NOT NULL,
    recipient_user_id TEXT NOT NULL,
    item_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    inventory_id INTEGER NOT NULL,
    granted_by_actor TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (fight_id, reward_type)
);

CREATE INDEX idx_mousefight_event_rewards_recipient_completed
    ON tbl_mousefight_event_rewards (recipient_user_id, completed_at DESC);
