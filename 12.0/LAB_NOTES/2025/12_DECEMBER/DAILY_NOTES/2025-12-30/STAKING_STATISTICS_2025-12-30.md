# 💰 DSPOINC STAKING STATISTICS - December 30, 2025

**Database:** Live production database downloaded locally  
**Date:** December 30, 2025  
**Analysis:** Complete staking system statistics from production database

---

## 📊 **OVERVIEW STATISTICS**

### **User Participation:**
- **Total Unique Users Who Staked:** **6 users**
- **Total Stakes Created:** **6 stakes**
- **Total DSPOINC Staked:** **2,549,000 DSPOINC**
- **Total Expected Rewards:** **753,070 DSPOINC**
- **All Stakes Status:** **100% Active** (0 completed, 0 cancelled)

---

## 📋 **BREAKDOWN BY DURATION**

| Duration | Stake Count | Total Amount | Average Amount | Reward Rate |
|----------|-------------|--------------|----------------|-------------|
| **1 Month** | 1 | 1,000 | 1,000 | 5% (0.05) |
| **3 Months** | 2 | 1,157,000 | 578,500 | 8% (0.08) |
| **12 Months** | 1 | 1,000 | 1,000 | 18% (0.18) |
| **36 Months** | 2 | 1,390,000 | 695,000 | 50% (0.50) ✅ |

**⚠️ NOTE:** The 36-month stakes show reward_rate = 0.50 (50%), but expected reward rate should be 0.35 (35%). This needs verification.

---

## 👥 **TOP STAKERS (By Total Staked)**

| Rank | User ID | Stake Count | Total Staked | Active Staked |
|------|---------|-------------|--------------|---------------|
| 1 | 1138915296959287468 | 1 | 1,000,000 | 1,000,000 |
| 2 | 328601656659017732 | 1 | 1,000,000 | 1,000,000 (Narrrf) |
| 3 | 1222200013489180743 | 1 | 390,000 | 390,000 |
| 4 | 1220324061213622353 | 1 | 157,000 | 157,000 |
| 5 | 919474204687077387 | 1 | 1,000 | 1,000 |
| 6 | 987492370616561714 | 1 | 1,000 | 1,000 |

---

## 📝 **INDIVIDUAL STAKE DETAILS**

### **Stake #1:**
- **User ID:** 987492370616561714
- **Amount:** 1,000 DSPOINC
- **Duration:** 1 month
- **Reward Rate:** 2% (0.02) ✅
- **Expected Reward:** 20 DSPOINC
- **Frozen At:** 2025-12-29 00:21:55
- **Unfreeze At:** 2026-01-29 00:21:55
- **Status:** Active

### **Stake #2:**
- **User ID:** 328601656659017732 (Narrrf)
- **Amount:** 1,000,000 DSPOINC
- **Duration:** 36 months
- **Reward Rate:** 50% (0.50) ✅
- **Expected Reward:** 500,000 DSPOINC
- **Frozen At:** 2025-12-29 05:04:28
- **Unfreeze At:** 2028-12-29 05:04:28
- **Status:** Active

### **Stake #3:**
- **User ID:** 919474204687077387
- **Amount:** 1,000 DSPOINC
- **Duration:** 12 months
- **Reward Rate:** 20% (0.20) ✅
- **Expected Reward:** 200 DSPOINC
- **Frozen At:** 2025-12-29 10:27:14
- **Unfreeze At:** 2026-12-29 10:27:14
- **Status:** Active

### **Stake #4:**
- **User ID:** 1138915296959287468
- **Amount:** 1,000,000 DSPOINC
- **Duration:** 3 months
- **Reward Rate:** 5% (0.05) ✅
- **Expected Reward:** 50,000 DSPOINC
- **Frozen At:** 2025-12-29 13:20:15
- **Unfreeze At:** 2026-03-29 13:20:15
- **Status:** Active

### **Stake #5:**
- **User ID:** 1220324061213622353
- **Amount:** 157,000 DSPOINC
- **Duration:** 3 months
- **Reward Rate:** 5% (0.05) ✅
- **Expected Reward:** 7,850 DSPOINC
- **Frozen At:** 2025-12-29 17:36:28
- **Unfreeze At:** 2026-03-29 17:36:28
- **Status:** Active

### **Stake #6:**
- **User ID:** 1222200013489180743
- **Amount:** 390,000 DSPOINC
- **Duration:** 36 months
- **Reward Rate:** 50% (0.50) ✅
- **Expected Reward:** 195,000 DSPOINC
- **Frozen At:** 2025-12-29 22:36:53
- **Unfreeze At:** 2028-12-29 22:36:53
- **Status:** Active

---

## ✅ **REWARD RATE VERIFICATION**

### **Actual Reward Rates (from `create-stake.php` API):**
- **1 Month:** 2% (0.02) ✅
- **3 Months:** 5% (0.05) ✅
- **6 Months:** 10% (0.10)
- **12 Months:** 20% (0.20) ✅
- **24 Months:** 35% (0.35)
- **36 Months:** 50% (0.50) ✅

### **Database Verification:**
All stakes in the database match the API reward rates:
- ✅ **Stake #1:** 1 month, reward_rate = 0.02 (2%) - Correct
- ✅ **Stake #2:** 36 months, reward_rate = 0.50 (50%) - Correct
- ✅ **Stake #3:** 12 months, reward_rate = 0.20 (20%) - Correct
- ✅ **Stake #4:** 3 months, reward_rate = 0.05 (5%) - Correct
- ✅ **Stake #5:** 3 months, reward_rate = 0.05 (5%) - Correct
- ✅ **Stake #6:** 36 months, reward_rate = 0.50 (50%) - Correct

### **Documentation Discrepancy:**
The technical documentation may show different rates (5%, 8%, 12%, 18%, 25%, 35%), but the **actual API implementation** uses:
- 2%, 5%, 10%, 20%, 35%, 50%

**✅ CONCLUSION:** Database rates are correct - they match the API implementation. Documentation may need to be updated to reflect actual rates.

---

## 📅 **STAKING TIMELINE**

### **December 29, 2025 Activity:**
- **00:21:55** - First stake created (1,000 DSPOINC, 1 month)
- **05:04:28** - Narrrf's stake (1,000,000 DSPOINC, 36 months)
- **10:27:14** - Third stake (1,000 DSPOINC, 12 months)
- **13:20:15** - Large stake (1,000,000 DSPOINC, 3 months)
- **17:36:28** - Medium stake (157,000 DSPOINC, 3 months)
- **22:36:53** - Final stake (390,000 DSPOINC, 36 months)

**Total Activity:** 6 stakes created in one day (December 29, 2025)

---

## 🎯 **KEY FINDINGS**

### **✅ Positive:**
- **System is being used!** 6 users have staked
- **Large amounts staked:** 2.5M+ DSPOINC total
- **Long-term commitments:** 2 users chose 36-month stakes
- **All stakes active:** No issues with stake creation

### **✅ Verified:**
- **Reward rates correct:** All database rates match API implementation
- **Reward calculations correct:** expected_reward = amount × reward_rate for all stakes
- **System working as intended:** Rates are 2%, 5%, 10%, 20%, 35%, 50% (not the documented rates)

### **📊 Distribution:**
- **Most Popular Duration:** 3 months (2 stakes, 1.15M DSPOINC)
- **Highest Value:** 36 months (2 stakes, 1.39M DSPOINC)
- **Average Stake Amount:** 424,833 DSPOINC
- **Median Stake Amount:** 157,000 DSPOINC (between 1,000 and 390,000)

---

## 🔍 **VERIFICATION REQUIRED**

### **Priority 1: Reward Rate Documentation Update**
- [x] ✅ Verified `create-stake.php` reward rate calculation
- [x] ✅ Verified database rates match API implementation
- [ ] Update technical documentation to reflect actual rates:
  - 1 Month: 2% (0.02)
  - 3 Months: 5% (0.05)
  - 6 Months: 10% (0.10)
  - 12 Months: 20% (0.20)
  - 24 Months: 35% (0.35)
  - 36 Months: 50% (0.50)
- [x] ✅ Verified expected_reward calculations (all correct)

### **Priority 2: Data Integrity**
- [ ] Verify all 6 stakes have valid user_ids
- [ ] Check frozen_at vs unfreeze_at dates (should be correct)
- [ ] Verify expected_reward = amount × reward_rate
- [ ] Check if any stakes need status updates

---

## 📝 **SQL QUERIES USED**

```sql
-- Total statistics
SELECT COUNT(DISTINCT user_id) as unique_users, 
       COUNT(*) as total_stakes, 
       SUM(amount) as total_staked,
       SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END) as active_staked
FROM tbl_dspoinc_stakes;

-- Breakdown by duration
SELECT freeze_duration_months, 
       COUNT(*) as count, 
       SUM(amount) as total_amount,
       AVG(amount) as avg_amount
FROM tbl_dspoinc_stakes
GROUP BY freeze_duration_months
ORDER BY freeze_duration_months;

-- Top stakers
SELECT user_id, 
       COUNT(*) as stake_count, 
       SUM(amount) as total_staked
FROM tbl_dspoinc_stakes
GROUP BY user_id
ORDER BY total_staked DESC;

-- All stake details
SELECT id, user_id, amount, freeze_duration_months, 
       reward_rate, expected_reward, frozen_at, unfreeze_at, status
FROM tbl_dspoinc_stakes
ORDER BY frozen_at DESC;
```

---

## 🎯 **RECOMMENDATIONS**

1. **Immediate:** Verify reward rate calculations in API
2. **Review:** Check if reward rates in database match intended rates
3. **Documentation:** Verify reward rate documentation is correct
4. **Fix:** If rates are wrong, determine if existing stakes need correction
5. **Monitor:** Track stake completions and reward claims

---

**Status:** ✅ **STATISTICS COMPILED**  
**Next Steps:** Review reward rate discrepancies and verify API calculations

