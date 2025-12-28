# 🧊 DSPOINC STAKING SYSTEM - UNSTAKE & REWARD CLAIM INTEGRATION PLAN

**Created:** December 26, 2025  
**Status:** 📋 **PLANNING PHASE**  
**Purpose:** Complete integration plan for unstake feature and reward claim tab system  
**Version:** 1.0.0

---

## 📋 **TABLE OF CONTENTS**

1. [Overview](#overview)
2. [Feature 1: Unstake Option](#feature-1-unstake-option)
3. [Feature 2: Reward Claim Tab](#feature-2-reward-claim-tab)
4. [Database Changes](#database-changes)
5. [API Endpoints](#api-endpoints)
6. [Frontend Implementation](#frontend-implementation)
7. [Integration Points](#integration-points)
8. [Testing Checklist](#testing-checklist)

---

## 🎯 **OVERVIEW**

### **Goal:**
Add two major features to complete the DSPOINC Staking System:
1. **Unstake Option** - Allow users to unstake/freeze their DSPOINC before the freeze period ends (with penalty)
2. **Reward Claim Tab** - Dedicated tab/section to claim rewards from completed stakes

### **Current System:**
- ✅ Users can freeze DSPOINC for 1, 3, 6, 12, 24, 36 months
- ✅ Rewards are calculated and stored
- ✅ Completed stakes are automatically processed by `complete-stake.php` (cron job)
- ✅ Active and completed stakes are displayed separately

### **New Features:**
- 🆕 **Unstake Feature** - Early withdrawal with penalty
- 🆕 **Reward Claim Tab** - Manual reward claiming interface
- 🆕 **Tab System** - Convert completed stakes section into tabbed interface

---

## 🧊 **FEATURE 1: UNSTAKE OPTION**

### **1.1 Business Logic**

#### **Unstake Penalty System:**
- **Early Unstake Penalty:** 15% of original staked amount (minimum penalty)
- **No Reward:** Users forfeit all expected rewards when unstaking early
- **Return Amount:** Original staked amount - 15% penalty = 85% returned
- **Example:** 
  - Staked: 100,000 DSPOINC for 24 months (35% reward = 35,000 expected)
  - Early unstake: Return 85,000 DSPOINC (lose 15,000 + forfeit 35,000 reward)

#### **Unstake Rules:**
- ✅ Can only unstake **active** stakes (status = 'active')
- ✅ Cannot unstake if stake is already completed or cancelled
- ✅ Penalty is fixed at 15% (not time-based)
- ✅ Original amount is returned minus penalty
- ✅ No rewards are paid for early unstakes

### **1.2 Database Changes**

#### **Update `tbl_dspoinc_stakes` Table:**
```sql
-- Add new fields for unstake tracking
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN cancelled_at DATETIME;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN penalty_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN returned_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN unstake_reason TEXT;
```

**New Fields:**
- `cancelled_at` - Timestamp when stake was cancelled/unstaked
- `penalty_amount` - Amount deducted as penalty (10% of original)
- `returned_amount` - Amount returned to user (90% of original)
- `unstake_reason` - Optional reason for unstaking (for audit trail)

**Status Values:**
- `'active'` - Stake is active and frozen
- `'completed'` - Stake completed successfully, reward paid
- `'cancelled'` - Stake was unstaked early (new status)

### **1.3 API Endpoint: `unstake-stake.php`**

#### **Endpoint:** `/api/user/unstake-stake.php`
#### **Method:** POST
#### **Purpose:** Process early unstake request with penalty

#### **Request Payload:**
```json
{
  "user_id": "discord_id",
  "stake_id": 123,
  "reason": "Optional reason for unstaking"
}
```

#### **Response Structure:**
```json
{
  "success": true,
  "data": {
    "stake_id": 123,
    "original_amount": 100000,
    "penalty_amount": 15000,
    "returned_amount": 85000,
    "status": "cancelled",
    "cancelled_at": "2025-12-26 10:30:00",
    "new_balance": 900000
  }
}
```

#### **Implementation Logic:**
1. **Validate Request:**
   - Check user is logged in
   - Verify stake exists and belongs to user
   - Verify stake status is 'active'
   - Verify stake hasn't already been completed

2. **Calculate Penalty:**
   - Penalty = 15% of original amount (rounded down)
   - Returned amount = Original amount - Penalty

3. **Process Unstake:**
   - Begin database transaction
   - Update stake status to 'cancelled'
   - Set `cancelled_at` timestamp
   - Set `penalty_amount` and `returned_amount`
   - Add returned amount to `tbl_user_scores` (game: 'staking', source: 'unstake_return')
   - Create audit trail in `tbl_score_adjustments`:
     - Amount: `returned_amount` (positive)
     - Action: `'add'`
     - Reason: `"Early unstake (15% penalty): {original_amount} DSPOINC - {penalty_amount} penalty = {returned_amount} returned (stake_id: {stake_id})"`
   - Commit transaction

4. **Error Handling:**
   - Return 400 if stake not found or not active
   - Return 403 if stake doesn't belong to user
   - Return 500 for database errors
   - Rollback transaction on any error

### **1.4 Frontend Implementation**

#### **Unstake Button in Active Stakes:**
- Add "Unstake" button to each active stake card
- Show warning modal before unstaking:
  - Display penalty amount (10%)
  - Display returned amount (90%)
  - Display forfeited reward amount
  - Require confirmation
- Call `unstake-stake.php` API
- Refresh stakes list after successful unstake
- Show success/error notifications

#### **UI Elements:**
```html
<!-- Unstake Button in Active Stake Card -->
<button class="unstake-btn bg-red-600/20 hover:bg-red-600/30 border border-red-500/50 text-red-300 px-4 py-2 rounded-lg transition-all">
  ⚠️ Unstake (15% Penalty)
</button>
```

#### **Unstake Warning Modal:**
- Title: "⚠️ Early Unstake Warning"
- Content:
  - Original staked amount
  - Penalty amount (15%)
  - Returned amount (85%)
  - Forfeited reward amount
  - Warning message about losing rewards
- Buttons:
  - "Cancel" (close modal)
  - "Confirm Unstake" (proceed with unstake)

---

## 🎁 **FEATURE 2: REWARD CLAIM TAB**

### **2.1 Business Logic**

#### **Reward Claim System:**
- **Completed Stakes:** Stakes that have reached `unfreeze_at` date
- **Auto-Processing:** `complete-stake.php` automatically processes completed stakes (cron job)
- **Manual Claim:** Users can manually claim rewards via UI (if auto-processing fails or delayed)
- **Claim Status:** Check if reward has been paid (`reward_paid = 1`)

#### **Claim Rules:**
- ✅ Can only claim rewards from **completed** stakes (status = 'completed')
- ✅ Cannot claim if reward already paid (`reward_paid = 1`)
- ✅ Cannot claim if stake is still active
- ✅ Claim adds original amount + reward to user balance
- ✅ Updates `reward_paid` flag to prevent double-claiming

### **2.2 Database Changes**

#### **Current Status:**
- ✅ `tbl_dspoinc_stakes` already has `reward_paid` field (INTEGER, 0 = false, 1 = true)
- ✅ `tbl_dspoinc_stakes` already has `completed_at` field
- ✅ `tbl_dspoinc_stakes` already has `status` field

**No new fields needed** - existing schema supports reward claiming!

### **2.3 API Endpoint: `claim-stake-reward.php`**

#### **Endpoint:** `/api/user/claim-stake-reward.php`
#### **Method:** POST
#### **Purpose:** Manually claim reward from a completed stake

#### **Request Payload:**
```json
{
  "user_id": "discord_id",
  "stake_id": 123
}
```

#### **Response Structure:**
```json
{
  "success": true,
  "data": {
    "stake_id": 123,
    "original_amount": 100000,
    "reward_amount": 35000,
    "total_returned": 135000,
    "reward_paid": true,
    "new_balance": 1350000
  }
}
```

#### **Implementation Logic:**
1. **Validate Request:**
   - Check user is logged in
   - Verify stake exists and belongs to user
   - Verify stake status is 'completed'
   - Verify reward hasn't been paid already (`reward_paid = 0`)

2. **Process Claim:**
   - Begin database transaction
   - Calculate total return: `original_amount + expected_reward`
   - Add total return to `tbl_user_scores` (game: 'staking', source: 'claim_reward')
   - Update stake `reward_paid = 1`
   - Create audit trail in `tbl_score_adjustments`:
     - Amount: `total_returned` (positive)
     - Action: `'add'`
     - Reason: `"Stake reward claimed: {original_amount} DSPOINC + {reward_amount} reward = {total_returned} total (stake_id: {stake_id})"`
   - Commit transaction

3. **Error Handling:**
   - Return 400 if stake not found or not completed
   - Return 403 if stake doesn't belong to user
   - Return 409 if reward already paid
   - Return 500 for database errors
   - Rollback transaction on any error

### **2.4 Frontend Implementation**

#### **Tab System Structure:**
Convert the current "Completed Stakes" section into a tabbed interface:

**Tabs:**
1. **"Active Stakes"** - Current active stakes section (unchanged)
2. **"Completed Stakes"** - Completed stakes history (unchanged display)
3. **"Claim Rewards"** - New tab for claiming rewards from completed stakes

#### **Tab Navigation:**
```html
<!-- Tab Navigation -->
<div class="flex space-x-4 mb-6 border-b border-blue-400/30">
  <button class="tab-nav-btn px-6 py-3 font-semibold border-b-2 border-blue-400" data-tab="active">
    ❄️ Active Stakes
  </button>
  <button class="tab-nav-btn px-6 py-3 font-semibold text-gray-400 hover:text-blue-300" data-tab="completed">
    ✅ Completed Stakes
  </button>
  <button class="tab-nav-btn px-6 py-3 font-semibold text-gray-400 hover:text-blue-300" data-tab="claim">
    🎁 Claim Rewards
  </button>
</div>
```

#### **Claim Rewards Tab Content:**
- Display list of completed stakes with unpaid rewards
- Show for each stake:
  - Stake ID
  - Original amount
  - Reward amount
  - Total return (original + reward)
  - Completion date
  - "Claim Reward" button
- Show empty state if no rewards to claim
- Show success message after claiming
- Refresh list after successful claim

#### **Claim Button:**
```html
<button class="claim-btn bg-green-600/20 hover:bg-green-600/30 border border-green-500/50 text-green-300 px-4 py-2 rounded-lg transition-all">
  🎁 Claim Reward
</button>
```

#### **JavaScript Functions:**
- `switchTab(tabName)` - Switch between tabs
- `loadClaimableRewards()` - Fetch completed stakes with unpaid rewards
- `displayClaimableRewards()` - Display rewards ready to claim
- `claimReward(stakeId)` - Call API to claim reward
- `refreshAllTabs()` - Refresh all tab data after claim

---

## 🗄️ **DATABASE CHANGES SUMMARY**

### **Migration Script: `db/migrations/add_unstake_fields.sql`**

```sql
-- 🧊 DSPOINC Staking System - Unstake Feature Migration
-- Created: December 26, 2025
-- Purpose: Add fields for unstake/cancel tracking

-- Add unstake tracking fields to tbl_dspoinc_stakes
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN cancelled_at DATETIME;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN penalty_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN returned_amount INTEGER DEFAULT 0;
ALTER TABLE tbl_dspoinc_stakes ADD COLUMN unstake_reason TEXT;

-- Add index for cancelled stakes queries
CREATE INDEX IF NOT EXISTS idx_dspoinc_stakes_cancelled ON tbl_dspoinc_stakes (status, cancelled_at);

-- Note: reward_paid field already exists, no changes needed for claim feature
```

### **Table Schema After Migration:**

```sql
tbl_dspoinc_stakes:
  - id (PRIMARY KEY)
  - user_id (TEXT)
  - amount (INTEGER)
  - freeze_duration_months (INTEGER)
  - reward_rate (REAL)
  - expected_reward (INTEGER)
  - frozen_at (DATETIME)
  - unfreeze_at (DATETIME)
  - status (TEXT) -- 'active', 'completed', 'cancelled'
  - completed_at (DATETIME)
  - reward_paid (INTEGER) -- 0 = false, 1 = true
  - transaction_id (TEXT)
  - metadata (TEXT)
  - created_at (DATETIME)
  - updated_at (DATETIME)
  - cancelled_at (DATETIME) -- NEW
  - penalty_amount (INTEGER) -- NEW
  - returned_amount (INTEGER) -- NEW
  - unstake_reason (TEXT) -- NEW
```

---

## 🔌 **API ENDPOINTS SUMMARY**

### **New Endpoints:**

#### **1. Unstake Stake API**
- **File:** `api/user/unstake-stake.php`
- **Method:** POST
- **Purpose:** Process early unstake with 10% penalty
- **Returns:** Unstake details, returned amount, new balance

#### **2. Claim Stake Reward API**
- **File:** `api/user/claim-stake-reward.php`
- **Method:** POST
- **Purpose:** Manually claim reward from completed stake
- **Returns:** Claim details, total returned, new balance

### **Updated Endpoints:**

#### **3. Get Stakes API** (Update)
- **File:** `api/user/get-stakes.php`
- **Changes:**
  - Add filter for `status = 'cancelled'` (unstaked stakes)
  - Include `cancelled_at`, `penalty_amount`, `returned_amount` in response
  - Add filter for claimable rewards (`status = 'completed' AND reward_paid = 0`)

---

## 🎨 **FRONTEND IMPLEMENTATION SUMMARY**

### **File: `public/stake-lab.html`**

#### **Changes Required:**

1. **Add Unstake Button to Active Stakes:**
   - Add "Unstake" button to each active stake card
   - Add unstake warning modal
   - Add `unstakeStake(stakeId)` JavaScript function
   - Add confirmation dialog

2. **Convert to Tab System:**
   - Replace "Active Stakes" and "Completed Stakes" sections with tab navigation
   - Create 3 tabs: "Active Stakes", "Completed Stakes", "Claim Rewards"
   - Add tab switching JavaScript
   - Maintain existing display functions

3. **Add Claim Rewards Tab:**
   - Create new tab content section
   - Add `loadClaimableRewards()` function
   - Add `displayClaimableRewards()` function
   - Add `claimReward(stakeId)` function
   - Add claim success/error notifications

4. **Update JavaScript Functions:**
   - `loadStakes()` - Update to handle cancelled stakes
   - `displayActiveStakes()` - Add unstake button
   - `displayCompletedStakes()` - Keep as-is
   - `switchTab(tabName)` - New function for tab switching
   - `loadClaimableRewards()` - New function for claimable rewards
   - `displayClaimableRewards()` - New function to display claimable rewards
   - `claimReward(stakeId)` - New function to claim reward
   - `unstakeStake(stakeId)` - New function to unstake stake

---

## 🔗 **INTEGRATION POINTS**

### **1. Profile Page Integration:**
- ✅ Staking overview already shows frozen balance
- ✅ Recent Score Changes will show unstake and claim transactions
- ✅ No changes needed to profile page

### **2. Recent Score Changes:**
- ✅ Unstake transactions will appear with action `'add'` and reason containing "Early unstake"
- ✅ Claim transactions will appear with action `'add'` and reason containing "Stake reward claimed"
- ✅ Both will use `admin_id = 'system-staking'`

### **3. Balance Calculation:**
- ✅ `get-staking-stats.php` already calculates frozen balance correctly
- ✅ Unstaked amounts will automatically become available (returned to balance)
- ✅ Claimed rewards will automatically add to total balance
- ✅ No changes needed to balance calculation

### **4. Store Purchase Validation:**
- ✅ `purchase.php` already checks `available_balance` (total - frozen)
- ✅ Unstaked amounts will automatically become available for spending
- ✅ No changes needed to store validation

---

## ✅ **TESTING CHECKLIST**

### **Unstake Feature Testing:**
- [ ] Test unstaking an active stake
- [ ] Verify 15% penalty is deducted correctly
- [ ] Verify 85% is returned to balance
- [ ] Verify stake status changes to 'cancelled'
- [ ] Verify `cancelled_at` timestamp is set
- [ ] Verify penalty and returned amounts are stored
- [ ] Verify audit trail entry is created
- [ ] Verify balance updates correctly
- [ ] Verify unstake appears in Recent Score Changes
- [ ] Test error handling (stake not found, not active, etc.)
- [ ] Test unstake confirmation modal
- [ ] Test unstake button is hidden for cancelled/completed stakes

### **Reward Claim Feature Testing:**
- [ ] Test claiming reward from completed stake
- [ ] Verify original amount + reward is added to balance
- [ ] Verify `reward_paid` flag is set to 1
- [ ] Verify audit trail entry is created
- [ ] Verify balance updates correctly
- [ ] Verify claim appears in Recent Score Changes
- [ ] Test error handling (stake not found, not completed, already paid, etc.)
- [ ] Test claim button is hidden for already-claimed rewards
- [ ] Test tab switching works correctly
- [ ] Test claimable rewards list displays correctly
- [ ] Test empty state when no rewards to claim

### **Integration Testing:**
- [ ] Test unstake + claim flow together
- [ ] Test multiple unstakes in sequence
- [ ] Test multiple claims in sequence
- [ ] Verify profile page balance updates after unstake
- [ ] Verify profile page balance updates after claim
- [ ] Verify Recent Score Changes shows both transaction types
- [ ] Verify store purchase works after unstake (available balance increases)
- [ ] Test local development bypass for both features

---

## 📝 **IMPLEMENTATION PHASES**

### **Phase 1: Database Migration**
1. Create migration script
2. Run migration on local database
3. Verify schema changes
4. Test database queries

### **Phase 2: API Development**
1. Create `unstake-stake.php` endpoint
2. Create `claim-stake-reward.php` endpoint
3. Update `get-stakes.php` to include cancelled stakes and claimable rewards
4. Test all API endpoints locally

### **Phase 3: Frontend Development**
1. Add unstake button to active stakes
2. Add unstake warning modal
3. Convert to tab system
4. Add Claim Rewards tab
5. Implement JavaScript functions
6. Test UI interactions

### **Phase 4: Integration & Testing**
1. Test complete unstake flow
2. Test complete claim flow
3. Test integration with profile page
4. Test integration with Recent Score Changes
5. Test local development bypass
6. Verify all edge cases

### **Phase 5: Documentation & Deployment**
1. Update technical documentation
2. Update master ruleset
3. Update Nerd Lab overview
4. Deploy to production
5. Monitor for issues

---

## 🎯 **SUCCESS CRITERIA**

### **Unstake Feature:**
- ✅ Users can unstake active stakes with 15% penalty
- ✅ 85% of original amount is returned to balance
- ✅ Stake status changes to 'cancelled'
- ✅ Audit trail is created
- ✅ Balance updates correctly
- ✅ Transaction appears in Recent Score Changes

### **Reward Claim Feature:**
- ✅ Users can claim rewards from completed stakes
- ✅ Original amount + reward is added to balance
- ✅ `reward_paid` flag is set correctly
- ✅ Audit trail is created
- ✅ Balance updates correctly
- ✅ Transaction appears in Recent Score Changes
- ✅ Tab system works smoothly

### **Overall:**
- ✅ All features work in local development
- ✅ All features work in production
- ✅ All edge cases are handled
- ✅ All error messages are user-friendly
- ✅ All transactions are properly audited
- ✅ Documentation is complete

---

## 📚 **RELATED DOCUMENTATION**

- **Technical Documentation:** `12.0/YEAR_END_2025/SYSTEM_13_DSPOINC_STAKING_COMPLETE_TECHNICAL.md`
- **Database Documentation:** `12.0/YEAR_END_2025/DATABASE_COMPLETE_TECHNICAL.md`
- **Frontend Documentation:** `12.0/YEAR_END_2025/FRONTEND_WEBSITE_COMPLETE_TECHNICAL.md`
- **Master Ruleset:** `12.0/RULES/01_MASTER_RULESET.md`

---

**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Next Step:** Begin Phase 1 (Database Migration)

