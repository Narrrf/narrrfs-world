# 🧊 Render Database Migration - Correct Commands

**Issue:** SQL commands were pasted directly into bash shell instead of running through sqlite3.

---

## ✅ **CORRECT METHOD 1: Using sqlite3 Command**

Run these commands on Render:

```bash
# Navigate to your database location
cd /data

# Run Migration 1: Create main table
sqlite3 narrrf_world.sqlite <<EOF
CREATE TABLE IF NOT EXISTS tbl_dspoinc_stakes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    amount INTEGER NOT NULL,
    freeze_duration_months INTEGER NOT NULL,
    reward_rate REAL NOT NULL,
    expected_reward INTEGER NOT NULL,
    frozen_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    unfreeze_at DATETIME NOT NULL,
    status TEXT DEFAULT 'active',
    completed_at DATETIME,
    reward_paid INTEGER DEFAULT 0,
    transaction_id TEXT,
    metadata TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_stakes_user_status ON tbl_dspoinc_stakes(user_id, status);
CREATE INDEX IF NOT EXISTS idx_stakes_unfreeze_at ON tbl_dspoinc_stakes(unfreeze_at, status);
CREATE INDEX IF NOT EXISTS idx_stakes_user_active ON tbl_dspoinc_stakes(user_id, status, unfreeze_at);
EOF

# Run Migration 2: Add unstake fields
sqlite3 narrrf_world.sqlite <<EOF
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN cancelled_at DATETIME;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN penalty_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN returned_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN unstake_reason TEXT;
CREATE INDEX IF NOT EXISTS idx_dspoinc_stakes_cancelled ON tbl_dspoinc_stakes (status, cancelled_at);
EOF

# Verify migrations
sqlite3 narrrf_world.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_dspoinc_stakes';"
sqlite3 narrrf_world.sqlite "PRAGMA table_info(tbl_dspoinc_stakes);"
```

---

## ✅ **CORRECT METHOD 2: Interactive sqlite3 Mode**

```bash
# Navigate to database location
cd /data

# Open sqlite3 interactive mode
sqlite3 narrrf_world.sqlite

# Then paste these SQL commands one by one:
```

**Inside sqlite3 prompt:**

```sql
-- Migration 1: Create main table
CREATE TABLE IF NOT EXISTS tbl_dspoinc_stakes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    amount INTEGER NOT NULL,
    freeze_duration_months INTEGER NOT NULL,
    reward_rate REAL NOT NULL,
    expected_reward INTEGER NOT NULL,
    frozen_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    unfreeze_at DATETIME NOT NULL,
    status TEXT DEFAULT 'active',
    completed_at DATETIME,
    reward_paid INTEGER DEFAULT 0,
    transaction_id TEXT,
    metadata TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_stakes_user_status ON tbl_dspoinc_stakes(user_id, status);
CREATE INDEX IF NOT EXISTS idx_stakes_unfreeze_at ON tbl_dspoinc_stakes(unfreeze_at, status);
CREATE INDEX IF NOT EXISTS idx_stakes_user_active ON tbl_dspoinc_stakes(user_id, status, unfreeze_at);

-- Migration 2: Add unstake fields
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN cancelled_at DATETIME;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN penalty_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN returned_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN unstake_reason TEXT;
CREATE INDEX IF NOT EXISTS idx_dspoinc_stakes_cancelled ON tbl_dspoinc_stakes (status, cancelled_at);

-- Verify
SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_dspoinc_stakes';
PRAGMA table_info(tbl_dspoinc_stakes);

-- Exit sqlite3
.quit
```

---

## ✅ **CORRECT METHOD 3: Using SQL File (If Available)**

If you have the migration files on Render:

```bash
cd /var/www/html
sqlite3 /data/narrrf_world.sqlite < db/migrations/create_dspoinc_staking_tables.sql
sqlite3 /data/narrrf_world.sqlite < db/migrations/add_unstake_fields.sql
```

---

## 🎯 **RECOMMENDED: Use Method 1 (Heredoc)**

**Copy and paste this entire block:**

```bash
cd /data && sqlite3 narrrf_world.sqlite <<'SQL'
CREATE TABLE IF NOT EXISTS tbl_dspoinc_stakes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    amount INTEGER NOT NULL,
    freeze_duration_months INTEGER NOT NULL,
    reward_rate REAL NOT NULL,
    expected_reward INTEGER NOT NULL,
    frozen_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    unfreeze_at DATETIME NOT NULL,
    status TEXT DEFAULT 'active',
    completed_at DATETIME,
    reward_paid INTEGER DEFAULT 0,
    transaction_id TEXT,
    metadata TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_stakes_user_status ON tbl_dspoinc_stakes(user_id, status);
CREATE INDEX IF NOT EXISTS idx_stakes_unfreeze_at ON tbl_dspoinc_stakes(unfreeze_at, status);
CREATE INDEX IF NOT EXISTS idx_stakes_user_active ON tbl_dspoinc_stakes(user_id, status, unfreeze_at);
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN cancelled_at DATETIME;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN penalty_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN returned_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN unstake_reason TEXT;
CREATE INDEX IF NOT EXISTS idx_dspoinc_stakes_cancelled ON tbl_dspoinc_stakes (status, cancelled_at);
SQL
```

**Then verify:**

```bash
sqlite3 /data/narrrf_world.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_dspoinc_stakes';"
sqlite3 /data/narrrf_world.sqlite "PRAGMA table_info(tbl_dspoinc_stakes);"
```

---

## ⚠️ **WHAT WENT WRONG**

You pasted SQL directly into bash. Bash tried to interpret SQL as shell commands, which caused errors like:
- `bash: --: command not found` (SQL comments)
- `bash: syntax error` (SQL syntax)
- `id: 'INTEGER': no such user` (SQL column definitions)

**Solution:** Always use `sqlite3` command to run SQL!

---

## ✅ **QUICK FIX - Single Command**

Run this one command (all migrations in one go):

```bash
cd /data && sqlite3 narrrf_world.sqlite <<'SQL'
CREATE TABLE IF NOT EXISTS tbl_dspoinc_stakes (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, amount INTEGER NOT NULL, freeze_duration_months INTEGER NOT NULL, reward_rate REAL NOT NULL, expected_reward INTEGER NOT NULL, frozen_at DATETIME DEFAULT CURRENT_TIMESTAMP, unfreeze_at DATETIME NOT NULL, status TEXT DEFAULT 'active', completed_at DATETIME, reward_paid INTEGER DEFAULT 0, transaction_id TEXT, metadata TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP);
CREATE INDEX IF NOT EXISTS idx_stakes_user_status ON tbl_dspoinc_stakes(user_id, status);
CREATE INDEX IF NOT EXISTS idx_stakes_unfreeze_at ON tbl_dspoinc_stakes(unfreeze_at, status);
CREATE INDEX IF NOT EXISTS idx_stakes_user_active ON tbl_dspoinc_stakes(user_id, status, unfreeze_at);
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN cancelled_at DATETIME;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN penalty_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN returned_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN unstake_reason TEXT;
CREATE INDEX IF NOT EXISTS idx_dspoinc_stakes_cancelled ON tbl_dspoinc_stakes (status, cancelled_at);
SQL
```

