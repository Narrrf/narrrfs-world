-- Add new columns to tbl_users if they don't exist
ALTER TABLE tbl_users ADD COLUMN IF NOT EXISTS username TEXT;
ALTER TABLE tbl_users ADD COLUMN IF NOT EXISTS discriminator TEXT;
ALTER TABLE tbl_users ADD COLUMN IF NOT EXISTS avatar_url TEXT;

-- Update tbl_user_roles to use discord_id and role_id if needed
CREATE TABLE IF NOT EXISTS temp_user_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,
    role_id TEXT NOT NULL,
    UNIQUE(discord_id, role_id)
);

-- Copy data if old table exists
INSERT OR IGNORE INTO temp_user_roles (discord_id, role_id)
SELECT user_id, role_name FROM tbl_user_roles;

-- Drop old table and rename new one
DROP TABLE IF EXISTS tbl_user_roles;
ALTER TABLE temp_user_roles RENAME TO tbl_user_roles;

-- Create indices for performance
CREATE INDEX IF NOT EXISTS idx_users_discord_id ON tbl_users(discord_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_discord_id ON tbl_user_roles(discord_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_role_id ON tbl_user_roles(role_id); 