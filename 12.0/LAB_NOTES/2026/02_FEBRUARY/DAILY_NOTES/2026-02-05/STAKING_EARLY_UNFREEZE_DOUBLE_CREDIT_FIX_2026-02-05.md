# 🧊 Staking Early Unfreeze Double-Credit Bug Fix

**Date:** 2026-02-05  
**Status:** ✅ **FIXED**  
**User Affected:** 775049759193956382 (early unfroze 1M staked DSPOINC)  
**Root Cause:** `unstake-stake.php` added +850K to balance when it should have applied -150K penalty

---

## 🐛 Bug Description

When a user early-unfroze their 1M staked DSPOINC:
- **Expected:** User should receive 850K only (1M - 15% penalty = 850K)
- **Actual:** User received 850K + 1M = 1.85M (double credit)

---

## 🔍 Root Cause Analysis

### Staking Design (create-stake.php)
- **create-stake does NOT deduct from tbl_user_scores** when freezing
- The freeze is tracked only in `tbl_dspoinc_stakes`
- Balance calculation: `available_balance = total_balance - frozen_balance`
- So when user stakes 1M: their `total_balance` (from tbl_user_scores) stays unchanged
- The 1M is "conceptually" locked but never removed from the ledger

### Early Unfreeze Bug (unstake-stake.php - BEFORE FIX)
- Code added **+850,000** to tbl_user_scores (the "returned" amount)
- But the user's total_balance **already included the 1M** (never deducted)
- Result: total = old_total + 850K = user got 1M + 850K = **1.85M** (wrong!)

### Correct Behavior
- User had X balance, staked 1M → available = X - 1M (frozen)
- On early unfreeze: user should get 850K back, lose 150K penalty
- Net balance change: **-150,000** (the penalty)
- New total should be: X - 150K (not X + 850K)

---

## ✅ Fix Applied

**File:** `api/user/unstake-stake.php`

**Change:** Instead of adding `+returned_amount` (850K), now add `-penalty_amount` (150K)

```php
// BEFORE (wrong):
':score' => $returned_amount,  // +850,000 - double credits the user!
':source' => 'unstake_return',

// AFTER (correct):
':score' => -$penalty_amount,  // -150,000 - applies the penalty
':source' => 'unstake_penalty',
```

**tbl_score_adjustments:** Also updated to use `-penalty_amount` and `action='remove'` for consistency with actual balance change in "Recent Score Changes" display.

---

## 📊 Database Verification (User 775049759193956382)

**Stake record (id 16):**
- amount: 1,000,000
- status: cancelled
- penalty_amount: 150,000
- returned_amount: 850,000
- cancelled_at: 2026-02-05 18:45:21

**Score adjustments (chronological):**
- 5778: -1,000,000 remove "DSPOINC frozen for staking" (Jan 9 - when staked)
- 6993: +850,000 add "Early unstake..." (Feb 5 - BUG: should have been -150K)
- 6997: -1,000,000 remove "Correction Staking" (Admin manual fix)
- 6998: +150,000 add "Bug found Staking Contract" (Admin manual fix)

**Note:** Admin (Narrrf) manually corrected the affected user's balance. This code fix prevents the bug for all future early unfreezes.

---

## 🎯 Impact

- **Future early unfreezes:** Will correctly apply -150K penalty
- **Balance calculation:** `SUM(tbl_user_scores)` will reflect correct change
- **Recent Score Changes:** Will show -150,000 with reason explaining the penalty

---

## 📁 Files Modified

- `api/user/unstake-stake.php` - Lines 178-212 (score insert + adjustment insert)

---

**Lab Note Created:** 2026-02-05  
**Fix Verified:** Logic analysis + database investigation

---

## 🔍 Other Users Verification (2026-02-05)

**Question:** Did any other users mistakenly receive the double-credit reward?

**Database queries run:**
```sql
-- Cancelled stakes (early unfreezes)
SELECT * FROM tbl_dspoinc_stakes WHERE status='cancelled';
-- Result: 1 row only - user 775049759193956382 (stake_id 16)

-- Early unstake score adjustments
SELECT * FROM tbl_score_adjustments WHERE reason LIKE '%Early unstake%';
-- Result: 1 row only - user 775049759193956382 (amount 850000, action add)
```

**Conclusion:** ✅ **Only user 775049759193956382 was affected.** No other users have ever performed an early unfreeze. Total stakes: 28. Cancelled (early unfrozen): 1. The fix prevents the bug for all future early unfreezes.
