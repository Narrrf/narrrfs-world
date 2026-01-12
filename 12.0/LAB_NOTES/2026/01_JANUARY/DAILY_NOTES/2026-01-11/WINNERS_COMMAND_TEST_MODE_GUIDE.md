# 🧪 Winners Command - Test Mode Guide

**Date:** January 11, 2026  
**Feature:** `/winners test` - Test mode for winners command  
**Channel:** `#bot-commander` (ID: 1337377013366915086)

---

## ✅ **WHAT IS TEST MODE?**

The `/winners test` command works exactly like `/winners post`, but:

1. ✅ **Posts to test channel** - `#bot-commander` instead of `#winners`
2. ✅ **Shows "TEST MODE" indicator** - Embed title and description clearly marked
3. ✅ **Does NOT update tracking** - Tests don't affect production tracking
4. ✅ **No role mention** - Community Member role not mentioned in test mode
5. ✅ **Same data queries** - Uses same database queries as production

---

## 🎯 **WHEN TO USE TEST MODE**

### **Use Test Mode When:**
- ✅ **Testing before production post** - Preview what will be posted
- ✅ **Debugging issues** - Test without affecting production tracking
- ✅ **Verifying data** - Check if queries return correct data
- ✅ **Testing different modes** - Try 24h vs since_last without consequences

### **Use Production Mode When:**
- ✅ **Actual daily summary** - Real winners channel post
- ✅ **Official announcements** - Community-facing posts
- ✅ **Production tracking** - When you want tracking updated

---

## 🚀 **HOW TO USE**

### **Test Command:**
```
/winners test
```

**Default:** Uses `since_last` mode (reads from tracking table)

### **Test with 24h Mode:**
```
/winners test mode:24h
```

**Fixed window:** Shows last 24 hours regardless of tracking

### **Test with Since Last Mode:**
```
/winners test mode:since_last
```

**Incremental:** Shows data since last production post (uses tracking table)

---

## 📊 **WHAT TO EXPECT WHEN TESTING**

### **Current Situation (After Manual INSERT):**

Timestamp exists: `2026-01-11 23:33:35`

### **Expected Results:**

#### **Test with `since_last` mode (default):**
- ✅ **Should show 0 DSPOINC** - No new data since timestamp was saved
- ✅ **Should show 0 recipients** - No new users received DSPOINC
- ✅ **Should show 0 adjustments** - No new transactions
- ✅ **Should show "No recipients yet"** - Top 3 section
- ✅ **Description:** "Since Last Post"
- ✅ **Title:** "🧪 TEST MODE - Daily DSPOINC Distribution Summary"

#### **Test with `24h` mode:**
- ✅ **Should show ALL data from last 24 hours** - Full 24-hour window
- ✅ **Should show all recipients** - All users who received DSPOINC in last 24h
- ✅ **Should show all adjustments** - All transactions from last 24h
- ✅ **Description:** "Last 24 Hours"
- ✅ **Title:** "🧪 TEST MODE - Daily DSPOINC Distribution Summary"

---

## ✅ **TEST MODE FEATURES**

### **Visual Differences:**

1. **Embed Color:**
   - Production: Gold (`#FFD700`)
   - Test: Orange (`#FFA500`)

2. **Embed Title:**
   - Production: `🏆 Daily DSPOINC Distribution Summary`
   - Test: `🧪 TEST MODE - Daily DSPOINC Distribution Summary`

3. **Embed Description:**
   - Production: `Distribution Summary for [mode]`
   - Test: `Distribution Summary for [mode]` + `⚠️ **TEST MODE** - This is a test post and does NOT update tracking.`

4. **Channel:**
   - Production: `#winners` (ID: 1459935292659208336)
   - Test: `#bot-commander` (ID: 1337377013366915086)

5. **Role Mention:**
   - Production: Community Member role mentioned
   - Test: No role mention (just "🧪 **TEST MODE**" message)

---

## 🔍 **TRACKING BEHAVIOR**

### **Test Mode:**
- ✅ **Reads from tracking table** - Uses same timestamp as production
- ✅ **Does NOT update tracking** - Tests don't affect production
- ✅ **Uses same queries** - Same data, just different output

### **Production Mode:**
- ✅ **Reads from tracking table** - Uses saved timestamp
- ✅ **Updates tracking** - Saves new timestamp after post
- ✅ **Affects future runs** - Next run uses new timestamp

---

## 📋 **TESTING CHECKLIST**

### **Before Testing:**
- [ ] Bot has permission to post in `#bot-commander` channel
- [ ] You have `Manage Messages` permission
- [ ] Know what data to expect (0 for since_last, or 24h data)

### **During Test:**
- [ ] Run `/winners test` command
- [ ] Check `#bot-commander` channel for embed
- [ ] Verify embed shows "TEST MODE" indicator
- [ ] Verify data matches expectations
- [ ] Check bot console for logs

### **After Test:**
- [ ] Verify tracking table NOT updated (check database)
- [ ] Verify production command still works correctly
- [ ] Use test results to verify production post

---

## 🎯 **EXAMPLE TEST SCENARIOS**

### **Scenario 1: Verify Since Last Mode Shows 0**

**Command:**
```
/winners test
```

**Expected:**
- Total DSPOINC: 0
- Recipients: 0
- Adjustments: 0
- Top 3: "No recipients yet"
- Description: "Since Last Post"

**Why:** No new data since timestamp was saved at 23:33:35

---

### **Scenario 2: Verify 24h Mode Shows All Data**

**Command:**
```
/winners test mode:24h
```

**Expected:**
- Total DSPOINC: [All DSPOINC from last 24 hours]
- Recipients: [All users from last 24 hours]
- Adjustments: [All transactions from last 24 hours]
- Top 3: [Top 3 users from last 24 hours]
- Description: "Last 24 Hours"

**Why:** 24h mode shows full window, not incremental

---

## 🚨 **IMPORTANT NOTES**

### **✅ DO:**
- ✅ Use test mode before production posts
- ✅ Use test mode to verify data
- ✅ Use test mode for debugging
- ✅ Use test mode to preview output

### **❌ DON'T:**
- ❌ Use test mode for actual daily summaries
- ❌ Expect test mode to update tracking
- ❌ Use test mode for official announcements
- ❌ Rely on test mode for production tracking

---

## 🔄 **TEST MODE vs PRODUCTION MODE**

| Feature | Test Mode | Production Mode |
|---------|-----------|-----------------|
| **Channel** | `#bot-commander` | `#winners` |
| **Embed Color** | Orange (`#FFA500`) | Gold (`#FFD700`) |
| **Title** | 🧪 TEST MODE - ... | 🏆 Daily ... |
| **Role Mention** | No | Yes (Community Member) |
| **Updates Tracking** | ❌ No | ✅ Yes |
| **Data Queries** | ✅ Same | ✅ Same |
| **Tracking Reads** | ✅ Same | ✅ Same |

---

## 📝 **QUICK REFERENCE**

### **Test Commands:**
```
/winners test                    # Test with since_last mode (default)
/winners test mode:since_last    # Test with since_last mode (explicit)
/winners test mode:24h           # Test with 24h mode
```

### **Production Commands:**
```
/winners post                    # Production post with since_last mode
/winners post mode:since_last    # Production post with since_last mode
/winners post mode:24h           # Production post with 24h mode
```

---

## ✅ **SUCCESS CRITERIA**

Test mode is working correctly if:
- ✅ Embed posts to `#alpha-lab` channel
- ✅ Embed shows "TEST MODE" indicator
- ✅ Embed shows correct data (0 for since_last if no new data)
- ✅ Tracking table NOT updated after test
- ✅ Production command still works correctly
- ✅ No errors in bot console

---

**Status:** ✅ **READY FOR TESTING** - Use `/winners test` to preview output! 🧪
