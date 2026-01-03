# 💰 DSPOINC STAKING SYSTEM REVIEW CHECKLIST - December 30, 2025

**Status:** 🔍 **REVIEW CHECKLIST CREATED**  
**Date:** December 30, 2025  
**Purpose:** Comprehensive review checklist for DSPOINC Staking System before New Year 2026 deployment  
**System Version:** v2.0 (with unstake and claim features)

---

## 🎯 **REVIEW OVERVIEW**

This checklist provides comprehensive verification procedures for the DSPOINC Staking System to ensure all features work correctly before production deployment.

**System Components:**
- **6 API Endpoints** - Create, get, complete, unstake, claim, stats
- **Database Table** - `tbl_dspoinc_stakes` with unstake fields
- **Frontend Page** - `stake-lab.html` with 4 tabs (Active, Completed, Claim Rewards, Cancelled)
- **Integrations** - Profile page, Discord bot, store validation
- **Security** - Session-based authorization (fixed December 29, 2025)

---

## 📋 **PHASE 1: DATABASE VERIFICATION**

### **1.1 Table Structure Verification:**
- [ ] **Verify `tbl_dspoinc_stakes` Table Exists:**
  ```sql
  SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_dspoinc_stakes';
  ```
  - Expected: Table exists
  
- [ ] **Verify Table Schema:**
  ```sql
  PRAGMA table_info(tbl_dspoinc_stakes);
  ```
  - Verify all fields exist:
    - `id` (INTEGER PRIMARY KEY)
    - `user_id` (TEXT NOT NULL)
    - `amount` (INTEGER NOT NULL)
    - `freeze_duration_months` (INTEGER NOT NULL)
    - `reward_rate` (REAL NOT NULL)
    - `expected_reward` (INTEGER NOT NULL)
    - `frozen_at` (DATETIME DEFAULT CURRENT_TIMESTAMP)
    - `unfreeze_at` (DATETIME NOT NULL)
    - `status` (TEXT DEFAULT 'active')
    - `completed_at` (DATETIME)
    - `reward_paid` (INTEGER DEFAULT 0)
    - `transaction_id` (TEXT)
    - `metadata` (TEXT)
    - **Unstake Fields:**
      - `cancelled_at` (DATETIME)
      - `penalty_amount` (INTEGER)
      - `returned_amount` (INTEGER)
      - `unstake_reason` (TEXT)
    - `created_at` (DATETIME DEFAULT CURRENT_TIMESTAMP)
    - `updated_at` (DATETIME DEFAULT CURRENT_TIMESTAMP)

- [ ] **Verify Indexes Exist:**
  ```sql
  SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='tbl_dspoinc_stakes';
  ```
  - Verify indexes:
    - `idx_stakes_user_status`
    - `idx_stakes_unfreeze_at`
    - `idx_stakes_user_active`
    - `idx_dspoinc_stakes_cancelled` (for cancelled stakes)

### **1.2 Data Integrity Verification:**
- [ ] **Check for Orphaned Records:**
  ```sql
  SELECT COUNT(*) FROM tbl_dspoinc_stakes 
  WHERE user_id NOT IN (SELECT discord_id FROM tbl_users);
  ```
  - Expected: 0 orphaned records

- [ ] **Verify Status Values:**
  ```sql
  SELECT DISTINCT status FROM tbl_dspoinc_stakes;
  ```
  - Expected values: 'active', 'completed', 'cancelled'

- [ ] **Check Date Consistency:**
  ```sql
  SELECT COUNT(*) FROM tbl_dspoinc_stakes 
  WHERE unfreeze_at < frozen_at;
  ```
  - Expected: 0 records (unfreeze_at should always be after frozen_at)

### **1.3 Transaction Tracking Verification:**
- [ ] **Verify Score Adjustments for Stakes:**
  ```sql
  SELECT COUNT(*) FROM tbl_score_adjustments 
  WHERE reason LIKE '%stake%' OR reason LIKE '%freeze%';
  ```
  - Should match number of stakes created + unstaked + claimed

- [ ] **Verify Balance Calculations:**
  ```sql
  -- Check user balance matches sum of user_scores
  SELECT user_id, 
         (SELECT SUM(score) FROM tbl_user_scores WHERE user_id = s.user_id) as total_balance,
         (SELECT SUM(amount) FROM tbl_dspoinc_stakes WHERE user_id = s.user_id AND status = 'active') as frozen_amount
  FROM tbl_user_scores s
  GROUP BY user_id;
  ```
  - Available balance = total_balance - frozen_amount

---

## 📋 **PHASE 2: API ENDPOINT VERIFICATION**

### **2.1 Create Stake API (`create-stake.php`):**

#### **Test 1: Valid Stake Creation**
- [ ] **Request:**
  ```json
  POST /api/user/create-stake.php
  {
    "amount": 1000,
    "duration": 1
  }
  ```
- [ ] **Verify Response:**
  - `success: true`
  - `stake_id` returned
  - `frozen_at` and `unfreeze_at` dates correct
  - `expected_reward` calculated correctly (amount × reward_rate)
- [ ] **Verify Database:**
  - New record in `tbl_dspoinc_stakes`
  - Status = 'active'
  - Amount frozen correctly
- [ ] **Verify Score Adjustment:**
  - Entry in `tbl_score_adjustments`
  - Reason includes "freeze" or "stake"
  - Amount is negative (frozen, removed from available)

#### **Test 2: Insufficient Balance**
- [ ] **Request:**
  ```json
  {
    "amount": 999999999,
    "duration": 1
  }
  ```
- [ ] **Expected Response:**
  - `success: false`
  - Error message: "Insufficient balance"
- [ ] **Verify:**
  - No stake record created
  - No score adjustment created

#### **Test 3: Invalid Duration**
- [ ] **Request:**
  ```json
  {
    "amount": 1000,
    "duration": 99
  }
  ```
- [ ] **Expected Response:**
  - `success: false`
  - Error message: "Invalid duration"
- [ ] **Verify:**
  - No stake record created

#### **Test 4: Security - Other User's Stakes**
- [ ] **Attempt to create stake for different user:**
  - Try with different `user_id` in request (if allowed on localhost)
  - In production, verify request is rejected (403)
- [ ] **Expected:**
  - Production: 403 Forbidden
  - Localhost: May work for development (document behavior)

### **2.2 Get Stakes API (`get-stakes.php`):**

#### **Test 1: Get All Stakes**
- [ ] **Request:**
  ```json
  POST /api/user/get-stakes.php
  {}
  ```
- [ ] **Verify Response:**
  - `success: true`
  - Arrays: `active`, `completed`, `cancelled`, `claimable`
  - All stakes for logged-in user
- [ ] **Verify Data:**
  - Active stakes have `status: 'active'`
  - Completed stakes have `status: 'completed'` and `completed_at`
  - Cancelled stakes have `status: 'cancelled'` and `cancelled_at`
  - Claimable stakes have `reward_paid: 0` and `status: 'completed'`

#### **Test 2: Empty Stakes**
- [ ] **Test with user who has no stakes:**
  - Verify empty arrays returned
  - No errors thrown

#### **Test 3: Security - Other User's Stakes**
- [ ] **Attempt to get other user's stakes:**
  - Verify only logged-in user's stakes returned
  - Production: 403 if attempting different user_id

### **2.3 Get Staking Stats API (`get-staking-stats.php`):**

#### **Test 1: Get Stats**
- [ ] **Request:**
  ```json
  GET /api/user/get-staking-stats.php?user_id=[DISCORD_ID]
  ```
- [ ] **Verify Response:**
  - `success: true`
  - `total_dspoinc` - Total balance
  - `available_dspoinc` - Available balance (total - frozen)
  - `frozen_dspoinc` - Frozen amount
  - `active_stakes_count` - Number of active stakes
  - `ready_to_claim_count` - Number of claimable rewards
- [ ] **Verify Calculations:**
  - `available_dspoinc = total_dspoinc - frozen_dspoinc`
  - `frozen_dspoinc` matches sum of active stakes
  - `active_stakes_count` matches count of active stakes

#### **Test 2: Discord Bot Request**
- [ ] **Test GET request (Discord bot pattern):**
  ```json
  GET /api/user/get-staking-stats.php?user_id=[DISCORD_ID]
  ```
- [ ] **Verify:**
  - Works without session (for Discord bot)
  - Returns correct staking stats
  - Response includes `staking_stats` object

### **2.4 Unstake API (`unstake-stake.php`):**

#### **Test 1: Valid Unstake**
- [ ] **Request:**
  ```json
  POST /api/user/unstake-stake.php
  {
    "stake_id": [VALID_STAKE_ID]
  }
  ```
- [ ] **Verify Response:**
  - `success: true`
  - `penalty_amount` = 15% of staked amount
  - `returned_amount` = 85% of staked amount
  - `forfeited_reward` = expected_reward
- [ ] **Verify Database:**
  - Stake `status` = 'cancelled'
  - `cancelled_at` set
  - `penalty_amount` and `returned_amount` set
  - `unstake_reason` set
- [ ] **Verify Score Adjustments:**
  - Two entries created:
    1. Returned amount (positive, adds back 85%)
    2. Penalty amount (negative, removes 15% penalty)
  - Both have descriptive reasons

#### **Test 2: Unstake Completed Stake**
- [ ] **Attempt to unstake completed stake:**
  - Expected: `success: false`
  - Error: "Cannot unstake completed stake"

#### **Test 3: Unstake Already Cancelled Stake**
- [ ] **Attempt to unstake cancelled stake:**
  - Expected: `success: false`
  - Error: "Stake already cancelled"

#### **Test 4: Unstake Other User's Stake**
- [ ] **Security test:**
  - Attempt to unstake stake belonging to different user
  - Expected: 403 Forbidden (production)
  - Verify stake ownership checked

### **2.5 Claim Reward API (`claim-stake-reward.php`):**

#### **Test 1: Valid Claim**
- [ ] **Request:**
  ```json
  POST /api/user/claim-stake-reward.php
  {
    "stake_id": [COMPLETED_STAKE_ID]
  }
  ```
- [ ] **Verify Response:**
  - `success: true`
  - `reward_amount` = expected_reward
  - `total_dspoinc` updated
- [ ] **Verify Database:**
  - Stake `reward_paid` = expected_reward
  - `updated_at` timestamp updated
- [ ] **Verify Score Adjustment:**
  - Entry in `tbl_score_adjustments`
  - Amount = reward_amount (positive)
  - Reason includes "claim" or "reward"

#### **Test 2: Claim Non-Completed Stake**
- [ ] **Attempt to claim active stake:**
  - Expected: `success: false`
  - Error: "Stake not completed"

#### **Test 3: Claim Already Claimed Reward**
- [ ] **Attempt to claim reward that's already paid:**
  - Expected: `success: false`
  - Error: "Reward already claimed"

#### **Test 4: Claim Other User's Stake**
- [ ] **Security test:**
  - Attempt to claim stake belonging to different user
  - Expected: 403 Forbidden

### **2.6 Complete Stake API (`complete-stake.php`):**

#### **Test 1: Complete Stake (Cron Simulation)**
- [ ] **Request:**
  ```json
  POST /api/user/complete-stake.php
  {
    "stake_id": [STAKE_ID_WITH_PASSED_UNFREEZE_AT]
  }
  ```
- [ ] **Verify Response:**
  - `success: true`
  - Stake marked as completed
- [ ] **Verify Database:**
  - Stake `status` = 'completed'
  - `completed_at` set
  - `unfreeze_at` <= current time

#### **Note:** This is typically called by a cron job, not manually

---

## 📋 **PHASE 3: FRONTEND VERIFICATION**

### **3.1 Page Load & Authentication:**
- [ ] **Load `stake-lab.html`:**
  - Page loads without errors
  - Discord authentication check works
  - Redirects to Discord login if not authenticated
  - Shows staking interface if authenticated

### **3.2 Balance Dashboard:**
- [ ] **Verify Display:**
  - Total DSPOINC displays correctly
  - Available DSPOINC displays correctly (total - frozen)
  - Frozen DSPOINC displays correctly
  - All values update after stake creation/unstake/claim

### **3.3 Create Stake Form:**
- [ ] **Amount Input:**
  - Accepts numeric input
  - Validates minimum amount
  - Validates maximum amount (cannot exceed available balance)
  - Shows validation errors
- [ ] **Duration Selection:**
  - All 6 durations available (1, 3, 6, 12, 24, 36 months)
  - Reward rate displays correctly for each duration
  - Reward preview updates when duration changes
- [ ] **Reward Preview:**
  - Shows expected reward amount (amount × reward_rate)
  - Updates when amount or duration changes
  - Format: "You will earn: X DSPOINC"
- [ ] **Submit Button:**
  - Creates stake when clicked
  - Shows loading state during request
  - Shows success/error messages
  - Updates balance after creation
  - Adds stake to Active Stakes tab

### **3.4 Active Stakes Tab:**
- [ ] **Stake List:**
  - All active stakes displayed
  - Shows: amount, duration, expected reward, frozen date, unfreeze date
- [ ] **Progress Bars:**
  - Progress bar shows time remaining
  - Updates correctly (days/hours remaining)
  - Visual indicator of freeze progress
- [ ] **Unstake Button:**
  - Button visible on each active stake
  - Opens unstake modal when clicked
  - Shows warning about 15% penalty
  - Shows: original amount, penalty (15%), returned amount (85%), forfeited reward
  - Confirms unstake when accepted
  - Removes stake from Active tab after unstake
  - Adds stake to Cancelled tab after unstake

### **3.5 Completed Stakes Tab:**
- [ ] **Stake List:**
  - All completed stakes displayed
  - Shows: amount, duration, reward earned, completion date
  - Shows claim status (claimed/unclaimed)
- [ ] **Claim Button:**
  - Visible for unclaimed rewards
  - Claims reward when clicked
  - Updates balance after claim
  - Removes from claimable list after claim

### **3.6 Claim Rewards Tab:**
- [ ] **Claimable Rewards List:**
  - Shows all stakes ready to claim (completed, reward_paid = 0)
  - Shows: stake amount, reward amount, completion date
  - Claim button for each reward
- [ ] **Claim Functionality:**
  - Claim button works for each reward
  - Balance updates after claim
  - Stake moves to Completed tab (with claimed status)

### **3.7 Cancelled Stakes Tab:**
- [ ] **Cancelled Stakes List:**
  - Shows all cancelled stakes
  - Shows: original amount, penalty amount, returned amount, forfeited reward
  - Shows cancellation date

### **3.8 Balance Updates:**
- [ ] **After Stake Creation:**
  - Total DSPOINC unchanged
  - Available DSPOINC decreases by stake amount
  - Frozen DSPOINC increases by stake amount
- [ ] **After Unstake:**
  - Total DSPOINC decreases by penalty amount
  - Available DSPOINC increases by returned amount (85%)
  - Frozen DSPOINC decreases by original stake amount
- [ ] **After Claim:**
  - Total DSPOINC increases by reward amount
  - Available DSPOINC increases by reward amount
  - Frozen DSPOINC unchanged

---

## 📋 **PHASE 4: INTEGRATION VERIFICATION**

### **4.1 Profile Page Integration:**
- [ ] **Staking Section:**
  - Staking overview displays correctly
  - Shows: Total staked, Active stakes count, Available to stake
  - Link to `stake-lab.html` works
- [ ] **Recent Score Changes:**
  - Stake creation appears in Recent Score Changes
  - Unstake appears in Recent Score Changes (2 entries: returned + penalty)
  - Claim reward appears in Recent Score Changes
  - All transactions have descriptive reasons

### **4.2 Discord Bot Integration:**
- [ ] **`/balance` Command:**
  - Shows total DSPOINC
  - Shows available DSPOINC (excluding frozen)
  - Shows staked DSPOINC amount
  - Shows active stakes count
  - Shows staking status field
- [ ] **`/stake-status` Command:**
  - Shows detailed staking information
  - Shows active stakes count
  - Shows ready-to-claim rewards count
  - Links to stake-lab.html
- [ ] **`/check-holder` Command:**
  - Shows staking overview
  - Shows active stakes and rewards information

### **4.3 Store Integration:**
- [ ] **Purchase Validation:**
  - Store checks available balance (excludes frozen)
  - Cannot purchase if available balance < item price
  - Frozen DSPOINC not counted in available balance
  - Purchase validation works correctly

### **4.4 DSPOINC Journey:**
- [ ] **Links to Staking:**
  - Links to `stake-lab.html` work
  - Navigation is clear

---

## 📋 **PHASE 5: SECURITY VERIFICATION**

### **5.1 Authorization Checks:**
- [ ] **Session-Based Authorization:**
  - All APIs check session user_id
  - Cannot access other users' stakes
  - Production: Request user_id must match session user_id (403 if not)
  - Localhost: May allow override for development (document behavior)

### **5.2 Input Validation:**
- [ ] **Amount Validation:**
  - Must be positive integer
  - Must not exceed available balance
  - Rejects non-numeric input
  - Rejects negative amounts
  - Rejects zero amount
- [ ] **Duration Validation:**
  - Must be valid duration (1, 3, 6, 12, 24, 36)
  - Rejects invalid durations
  - Rejects non-numeric input
- [ ] **Stake ID Validation:**
  - Must be valid integer
  - Must belong to logged-in user
  - Rejects invalid stake IDs

### **5.3 SQL Injection Protection:**
- [ ] **Prepared Statements:**
  - All database queries use prepared statements
  - No string concatenation in SQL queries
  - Parameters properly bound

### **5.4 Transaction Safety:**
- [ ] **Atomic Operations:**
  - Stake creation: Database transaction with rollback on error
  - Unstake: Two score adjustments in single transaction
  - Claim: Reward payment and stake update in single transaction

---

## 📋 **PHASE 6: PERFORMANCE & EDGE CASES**

### **6.1 Large Amounts:**
- [ ] **Test with Large Stake:**
  - Stake large amount (e.g., 1M DSPOINC)
  - Verify calculations work correctly
  - Verify balance updates correctly
  - Verify database handles large integers

### **6.2 Multiple Stakes:**
- [ ] **Test Multiple Concurrent Stakes:**
  - Create 5-10 active stakes
  - Verify all display correctly
  - Verify balance calculations correct
  - Verify query performance acceptable

### **6.3 Edge Cases:**
- [ ] **Stake Completion:**
  - Test stake completion (cron simulation)
  - Verify stake moves to completed status
  - Verify reward becomes claimable
- [ ] **Unstake Timing:**
  - Unstake stake that's almost complete
  - Verify penalty still applies
  - Verify forfeited reward calculated correctly
- [ ] **Claim Timing:**
  - Claim reward immediately after completion
  - Verify reward amount correct
  - Verify balance updated correctly

### **6.4 Error Scenarios:**
- [ ] **Network Failure:**
  - Simulate network failure during stake creation
  - Verify no partial stake created
  - Verify error handling works
- [ ] **API Timeout:**
  - Simulate API timeout
  - Verify error message displayed
  - Verify no duplicate stakes created

### **6.5 Database Performance:**
- [ ] **Query Performance:**
  - Test queries with indexes
  - Verify indexes are used (EXPLAIN QUERY PLAN)
  - Verify query speed acceptable (< 100ms)

---

## 📊 **TEST RESULTS TEMPLATE**

### **Test Session Information:**
```
Date: ____________________
Tester: __________________
Environment: Local / Production
Test Account: ____________________
```

### **Phase 1: Database Verification**
- Table Structure: ✅ Pass / ❌ Fail
- Indexes: ✅ Pass / ❌ Fail
- Data Integrity: ✅ Pass / ❌ Fail
- Notes: _________________________________

### **Phase 2: API Endpoint Verification**
- Create Stake: ✅ Pass / ❌ Fail
- Get Stakes: ✅ Pass / ❌ Fail
- Get Stats: ✅ Pass / ❌ Fail
- Unstake: ✅ Pass / ❌ Fail
- Claim Reward: ✅ Pass / ❌ Fail
- Complete Stake: ✅ Pass / ❌ Fail
- Notes: _________________________________

### **Phase 3: Frontend Verification**
- Page Load: ✅ Pass / ❌ Fail
- Balance Dashboard: ✅ Pass / ❌ Fail
- Create Form: ✅ Pass / ❌ Fail
- Active Tab: ✅ Pass / ❌ Fail
- Completed Tab: ✅ Pass / ❌ Fail
- Claim Tab: ✅ Pass / ❌ Fail
- Cancelled Tab: ✅ Pass / ❌ Fail
- Notes: _________________________________

### **Phase 4: Integration Verification**
- Profile Page: ✅ Pass / ❌ Fail
- Discord Bot: ✅ Pass / ❌ Fail
- Store Validation: ✅ Pass / ❌ Fail
- Notes: _________________________________

### **Phase 5: Security Verification**
- Authorization: ✅ Pass / ❌ Fail
- Input Validation: ✅ Pass / ❌ Fail
- SQL Injection: ✅ Pass / ❌ Fail
- Transaction Safety: ✅ Pass / ❌ Fail
- Notes: _________________________________

### **Phase 6: Performance & Edge Cases**
- Large Amounts: ✅ Pass / ❌ Fail
- Multiple Stakes: ✅ Pass / ❌ Fail
- Edge Cases: ✅ Pass / ❌ Fail
- Error Handling: ✅ Pass / ❌ Fail
- Performance: ✅ Pass / ❌ Fail
- Notes: _________________________________

---

## 🐛 **KNOWN ISSUES TO VERIFY**

### **Issues from Previous Testing:**
- [ ] **Balance Display Issue (Fixed?):** Discord bot balance showing staked amount correctly
- [ ] **Security Vulnerability (Fixed):** User ID manipulation vulnerability fixed (December 29, 2025)
- [ ] **API Authentication:** Bot token authentication working for Discord bot

### **Potential Issues to Watch For:**
- [ ] **Balance Calculation:** Available balance = total - frozen (verify calculation)
- [ ] **Unstake Penalty:** 15% penalty calculated correctly
- [ ] **Reward Calculation:** Reward = amount × reward_rate (verify for all durations)
- [ ] **Transaction Timing:** Score adjustments created at correct times
- [ ] **Status Updates:** Stake status updates correctly (active → completed/cancelled)

---

## 🎯 **SUCCESS CRITERIA**

### **Minimum Viable:**
- ✅ All 6 APIs work correctly
- ✅ Database table structure correct
- ✅ Frontend page loads and displays data
- ✅ Balance calculations correct
- ✅ Security checks working

### **Good:**
- ✅ All above criteria met
- ✅ All integrations working
- ✅ Error handling robust
- ✅ Edge cases handled
- ✅ Performance acceptable

### **Excellent:**
- ✅ All above criteria met
- ✅ No bugs or issues found
- ✅ User experience smooth
- ✅ All features polished
- ✅ Production-ready

---

## 📚 **RESOURCES & REFERENCES**

### **Code Files:**
- Frontend: `public/stake-lab.html`
- APIs: `api/user/create-stake.php`, `get-stakes.php`, `get-staking-stats.php`, `complete-stake.php`, `unstake-stake.php`, `claim-stake-reward.php`
- Database: `db/migrations/create_dspoinc_staking_tables.sql`, `db/migrations/add_unstake_fields.sql`

### **Documentation:**
- Technical Doc: `12.0/YEAR_END_2025/SYSTEM_13_DSPOINC_STAKING_COMPLETE_TECHNICAL.md`
- Security Audit: See December 29, 2025 security fixes

---

**Status:** 📋 **READY FOR REVIEW**  
**Next Steps:** Execute review checklist, document findings, fix any issues found

---

## ✅ **REVIEW COMPLETION CHECKLIST**

After completing review:
- [ ] All phases executed
- [ ] Test results documented
- [ ] Issues found prioritized
- [ ] Bug reports created (if needed)
- [ ] Documentation updated
- [ ] Review summary created
- [ ] Next steps planned

---

**🧀 READY TO REVIEW DSPOINC STAKING SYSTEM! 💰**

