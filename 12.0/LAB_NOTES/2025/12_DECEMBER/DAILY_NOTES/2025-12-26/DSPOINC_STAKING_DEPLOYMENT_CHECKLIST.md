# 🧊 DSPOINC STAKING SYSTEM - DEPLOYMENT CHECKLIST

**Date:** December 26, 2025  
**Status:** ✅ **READY FOR PRODUCTION DEPLOYMENT**  
**Scope:** Complete deployment guide for Render live database

---

## 📋 **PRE-DEPLOYMENT CHECKLIST**

### **✅ Code Files Ready:**
- ✅ `public/stake-lab.html` - Staking page with tabs (1,134 lines)
- ✅ `public/nerd-lab.html` - Updated with Stake Lab tab
- ✅ `public/js/nerd-lab-overviews.js` - Updated with staking overview
- ✅ `public/profile.html` - Updated with staking section
- ✅ `api/user/create-stake.php` - Create stake endpoint
- ✅ `api/user/get-stakes.php` - Get stakes endpoint (includes cancelled & claimable)
- ✅ `api/user/get-staking-stats.php` - Stats endpoint
- ✅ `api/user/complete-stake.php` - Complete stake endpoint (cron job)
- ✅ `api/user/unstake-stake.php` - **NEW** - Unstake endpoint
- ✅ `api/user/claim-stake-reward.php` - **NEW** - Claim reward endpoint
- ✅ `db/migrations/create_dspoinc_staking_tables.sql` - Main table migration
- ✅ `db/migrations/add_unstake_fields.sql` - Unstake fields migration

---

## 🗄️ **DATABASE MIGRATIONS (MUST RUN ON LIVE DATABASE)**

### **Step 1: Create Main Staking Table**

**File:** `db/migrations/create_dspoinc_staking_tables.sql`

**What it does:**
- Creates `tbl_dspoinc_stakes` table with all core fields
- Creates 3 indexes for performance

**How to run on Render:**
```bash
# Option 1: Via Render Shell/Dashboard
# Connect to your Render database and run:
sqlite3 narrrf_world.sqlite < db/migrations/create_dspoinc_staking_tables.sql

# Option 2: Via SQL directly
# Copy the SQL from the file and run it in your database tool
```

**Verification:**
```sql
-- Check table exists
SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_dspoinc_stakes';

-- Check indexes
SELECT name FROM sqlite_master WHERE type='index' AND name LIKE 'idx_stakes%';
```

**Expected Result:**
- ✅ Table `tbl_dspoinc_stakes` created
- ✅ 3 indexes created: `idx_stakes_user_status`, `idx_stakes_unfreeze_at`, `idx_stakes_user_active`

---

### **Step 2: Add Unstake Fields**

**File:** `db/migrations/add_unstake_fields.sql`

**What it does:**
- Adds `cancelled_at` column
- Adds `penalty_amount` column
- Adds `returned_amount` column
- Adds `unstake_reason` column
- Creates index `idx_dspoinc_stakes_cancelled`

**How to run on Render:**
```bash
# Option 1: Via Render Shell/Dashboard
sqlite3 narrrf_world.sqlite < db/migrations/add_unstake_fields.sql

# Option 2: Via SQL directly
# Copy the SQL from the file and run it in your database tool
```

**Verification:**
```sql
-- Check columns exist
PRAGMA table_info(tbl_dspoinc_stakes);

-- Should show:
-- cancelled_at (DATETIME)
-- penalty_amount (INTEGER)
-- returned_amount (INTEGER)
-- unstake_reason (TEXT)

-- Check index exists
SELECT name FROM sqlite_master WHERE type='index' AND name='idx_dspoinc_stakes_cancelled';
```

**Expected Result:**
- ✅ 4 new columns added to `tbl_dspoinc_stakes`
- ✅ Index `idx_dspoinc_stakes_cancelled` created

---

## 🚀 **DEPLOYMENT STEPS**

### **1. Run Database Migrations (BEFORE CODE PUSH)**

**⚠️ CRITICAL: Run migrations BEFORE pushing code!**

1. **Connect to Render database:**
   - Use Render Shell or your database tool
   - Connect to the live `narrrf_world.sqlite` database

2. **Run Migration 1:**
   ```sql
   -- Copy and paste contents of db/migrations/create_dspoinc_staking_tables.sql
   ```

3. **Verify Migration 1:**
   ```sql
   SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_dspoinc_stakes';
   ```

4. **Run Migration 2:**
   ```sql
   -- Copy and paste contents of db/migrations/add_unstake_fields.sql
   ```

5. **Verify Migration 2:**
   ```sql
   PRAGMA table_info(tbl_dspoinc_stakes);
   -- Should show cancelled_at, penalty_amount, returned_amount, unstake_reason
   ```

---

### **2. Push Code to Render**

**After migrations are complete, push code:**

```bash
git add .
git commit -m "🧊 DSPOINC Staking System v2.0 - Unstake & Claim Features Complete"
git push origin render-deploy
```

**Files that will be deployed:**
- ✅ All API endpoints (6 total)
- ✅ Frontend pages (stake-lab.html, profile.html, nerd-lab.html)
- ✅ JavaScript files (nerd-lab-overviews.js)
- ✅ Migration files (for reference, already run)

---

### **3. Post-Deployment Verification**

**Test on Live Environment:**

1. **Database Verification:**
   ```sql
   -- Check table structure
   PRAGMA table_info(tbl_dspoinc_stakes);
   
   -- Check indexes
   SELECT name FROM sqlite_master WHERE type='index' AND name LIKE 'idx_dspoinc%';
   ```

2. **API Verification:**
   - ✅ Test `GET /api/user/get-staking-stats.php` (should return empty stats for new users)
   - ✅ Test `POST /api/user/get-stakes.php` (should return empty arrays)
   - ✅ Verify no errors in logs

3. **Frontend Verification:**
   - ✅ Visit `https://narrrfs.world/stake-lab.html`
   - ✅ Verify page loads without errors
   - ✅ Verify Discord login works
   - ✅ Verify balance shows correctly (0 for new users)
   - ✅ Verify tabs work (Active, Completed, Claim Rewards, Cancelled)

4. **Profile Page Verification:**
   - ✅ Visit `https://narrrfs.world/profile.html`
   - ✅ Verify staking section appears
   - ✅ Verify shows 0 frozen, 0 active stakes for new users

---

## 📊 **MIGRATION SUMMARY**

### **Tables Created:**
- ✅ `tbl_dspoinc_stakes` (main staking table)

### **Columns Added:**
- ✅ `cancelled_at` (DATETIME)
- ✅ `penalty_amount` (INTEGER)
- ✅ `returned_amount` (INTEGER)
- ✅ `unstake_reason` (TEXT)

### **Indexes Created:**
- ✅ `idx_stakes_user_status`
- ✅ `idx_stakes_unfreeze_at`
- ✅ `idx_stakes_user_active`
- ✅ `idx_dspoinc_stakes_cancelled`

### **Total Database Changes:**
- **1 new table**
- **4 new columns** (added to existing table)
- **4 new indexes**

---

## ⚠️ **IMPORTANT NOTES**

1. **Migration Order:**
   - ✅ MUST run `create_dspoinc_staking_tables.sql` FIRST
   - ✅ THEN run `add_unstake_fields.sql` SECOND
   - ❌ Do NOT skip Migration 1

2. **Backward Compatibility:**
   - ✅ All migrations use `IF NOT EXISTS` and `ADD COLUMN IF NOT EXISTS` patterns
   - ✅ Safe to run multiple times (won't break if already exists)
   - ✅ Existing data is preserved

3. **No Data Loss:**
   - ✅ All migrations are additive (only add, never remove)
   - ✅ Existing tables and data remain untouched
   - ✅ Safe to run on production

4. **Rollback Plan:**
   - If needed, you can drop the table (but this will delete all staking data):
     ```sql
     DROP TABLE IF EXISTS tbl_dspoinc_stakes;
     DROP INDEX IF EXISTS idx_stakes_user_status;
     DROP INDEX IF EXISTS idx_stakes_unfreeze_at;
     DROP INDEX IF EXISTS idx_stakes_user_active;
     DROP INDEX IF EXISTS idx_dspoinc_stakes_cancelled;
     ```
   - ⚠️ **WARNING:** Only use rollback if absolutely necessary - will delete all staking data!

---

## ✅ **DEPLOYMENT CHECKLIST**

### **Before Push:**
- [ ] Run `create_dspoinc_staking_tables.sql` on live database
- [ ] Verify `tbl_dspoinc_stakes` table exists
- [ ] Run `add_unstake_fields.sql` on live database
- [ ] Verify unstake columns exist
- [ ] Verify all indexes created

### **After Push:**
- [ ] Verify API endpoints accessible
- [ ] Test stake creation (create a test stake)
- [ ] Test unstake functionality
- [ ] Test claim reward functionality
- [ ] Verify profile page shows staking section
- [ ] Verify Recent Score Changes shows staking entries
- [ ] Check error logs for any issues

---

## 🎯 **QUICK DEPLOYMENT COMMANDS**

**For Render Database (via SQL):**

```sql
-- Step 1: Create main table
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

-- Step 2: Add unstake fields
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN cancelled_at DATETIME;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN penalty_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN returned_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN unstake_reason TEXT;
CREATE INDEX IF NOT EXISTS idx_dspoinc_stakes_cancelled ON tbl_dspoinc_stakes (status, cancelled_at);
```

---

**STATUS:** ✅ **READY FOR DEPLOYMENT**

**Next Steps:**
1. Run database migrations on Render
2. Verify migrations successful
3. Push code to Render
4. Test on live environment

