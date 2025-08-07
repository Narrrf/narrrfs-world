-- Create NFT verification tables for Narrrfs World
-- This migration creates the necessary tables for NFT holder verification system

-- Table to track all verification attempts
CREATE TABLE IF NOT EXISTS tbl_holder_verifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    username TEXT,
    wallet TEXT NOT NULL,
    collection TEXT NOT NULL,
    nft_count INTEGER DEFAULT 0,
    role_granted INTEGER DEFAULT 0,
    verified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    signature TEXT,
    message TEXT,
    status TEXT DEFAULT 'pending'
);

-- Table to store NFT ownership records
CREATE TABLE IF NOT EXISTS tbl_nft_ownership (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wallet TEXT NOT NULL,
    collection TEXT NOT NULL,
    nft_count INTEGER DEFAULT 0,
    verified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_checked DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table to track role grants (if not exists)
CREATE TABLE IF NOT EXISTS tbl_role_grants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    username TEXT,
    role_id TEXT NOT NULL,
    role_name TEXT NOT NULL,
    granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reason TEXT DEFAULT 'system',
    granted_by TEXT DEFAULT 'system'
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_holder_verifications_user ON tbl_holder_verifications(user_id);
CREATE INDEX IF NOT EXISTS idx_holder_verifications_wallet ON tbl_holder_verifications(wallet);
CREATE INDEX IF NOT EXISTS idx_holder_verifications_collection ON tbl_holder_verifications(collection);
CREATE INDEX IF NOT EXISTS idx_holder_verifications_verified_at ON tbl_holder_verifications(verified_at DESC);

CREATE INDEX IF NOT EXISTS idx_nft_ownership_wallet ON tbl_nft_ownership(wallet);
CREATE INDEX IF NOT EXISTS idx_nft_ownership_collection ON tbl_nft_ownership(collection);

CREATE INDEX IF NOT EXISTS idx_role_grants_user ON tbl_role_grants(user_id);
CREATE INDEX IF NOT EXISTS idx_role_grants_role ON tbl_role_grants(role_id);
CREATE INDEX IF NOT EXISTS idx_role_grants_granted_at ON tbl_role_grants(granted_at DESC);
