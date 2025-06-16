-- Create score adjustments table
CREATE TABLE IF NOT EXISTS tbl_score_adjustments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    admin_id TEXT NOT NULL,
    amount INTEGER NOT NULL,
    action TEXT NOT NULL CHECK(action IN ('add', 'remove', 'set')),
    reason TEXT NOT NULL DEFAULT 'admin_adjustment',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id),
    FOREIGN KEY (admin_id) REFERENCES tbl_users(discord_id)
);

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_score_adjustments_user ON tbl_score_adjustments(user_id);
CREATE INDEX IF NOT EXISTS idx_score_adjustments_admin ON tbl_score_adjustments(admin_id);
CREATE INDEX IF NOT EXISTS idx_score_adjustments_timestamp ON tbl_score_adjustments(timestamp DESC);

-- Add source column to user scores
ALTER TABLE tbl_user_scores ADD COLUMN source TEXT DEFAULT 'legacy'; 