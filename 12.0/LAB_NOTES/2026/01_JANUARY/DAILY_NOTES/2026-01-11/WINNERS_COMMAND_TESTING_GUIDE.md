# 🏆 Winners Command - Quick Testing Guide

**Date:** January 11, 2026  
**Command:** `/winners post`  
**Channel:** `#winners` (ID: 1459935292659208336)

---

## 🚀 **QUICK START**

### **Basic Usage:**
```
/winners post
```
**Default:** Uses `since_last` mode (incremental updates)

### **24-Hour Snapshot:**
```
/winners post mode:24h
```
**Fixed window:** Shows last 24 hours regardless of previous posts

### **Since Last Post (Incremental):**
```
/winners post mode:since_last
```
**Incremental:** Shows only new data since last post (prevents double-counting)

---

## ✅ **TESTING CHECKLIST**

### **Test 1: First Run (Since Last Mode)**
1. **Run Command:**
   ```
   /winners post
   ```
   OR
   ```
   /winners post mode:since_last
   ```

2. **Expected Result:**
   - ✅ Summary posted to `#winners` channel
   - ✅ Community Member role tagged (`<@&1332017969181622342>`)
   - ✅ Embed shows "Last 24 Hours (First Run)" in description
   - ✅ Shows total DSPOINC, recipients, adjustments
   - ✅ Shows top 3 users with medals (🥇🥈🥉)
   - ✅ Bot sends confirmation message (ephemeral)

3. **Verify:**
   - Check `#winners` channel for the embed
   - Verify all fields display correctly
   - Verify top 3 users show usernames or mentions

---

### **Test 2: Second Run (Incremental Mode)**
1. **Wait a few minutes** (or make some DSPOINC adjustments)
2. **Run Command:**
   ```
   /winners post mode:since_last
   ```

3. **Expected Result:**
   - ✅ Embed shows "Since Last Post" in description
   - ✅ Shows ONLY new data since first post
   - ✅ Should show fewer DSPOINC/recipients (only new ones)
   - ✅ Timestamp tracking updated in database

4. **Verify:**
   - Compare totals with first run
   - Verify no double-counting (should be less than 24h total)
   - Check that timestamp was updated correctly

---

### **Test 3: 24-Hour Snapshot Mode**
1. **Run Command:**
   ```
   /winners post mode:24h
   ```

2. **Expected Result:**
   - ✅ Embed shows "Last 24 Hours" in description
   - ✅ Shows ALL data from last 24 hours
   - ✅ Should show more data than incremental mode (if time passed)
   - ✅ Does NOT update tracking table

3. **Verify:**
   - Compare with incremental mode results
   - Verify it shows full 24-hour window
   - Verify tracking table NOT updated (next since_last should still work)

---

## 📊 **WHAT TO CHECK**

### **Embed Fields:**
- ✅ **Title:** "🏆 Daily DSPOINC Distribution Summary"
- ✅ **Description:** Dynamic based on mode
- ✅ **Total DSPOINC Distributed:** Number with commas
- ✅ **Recipients:** Count with proper pluralization
- ✅ **Total Adjustments:** Count with proper pluralization
- ✅ **Top 3 Recipients:** Usernames or mentions with medals
- ✅ **Footer:** Guild icon and timestamp

### **Role Tagging:**
- ✅ Community Member role should be mentioned in message content
- ✅ Role mention format: `<@&1332017969181622342>`

### **Channel:**
- ✅ Message posted to `#winners` channel (ID: 1459935292659208336)
- ✅ Only one message per command execution

---

## 🐛 **TROUBLESHOOTING**

### **Error: "You need Manage Messages permission"**
- ✅ **Required Permission:** `Manage Messages`
- ✅ **Solution:** Ensure you have admin/mod permissions

### **Error: "Error posting to #winners channel"**
- ✅ Check channel ID is correct
- ✅ Check bot has permission to send messages in channel
- ✅ Check bot is in the server

### **Error: "An error occurred"**
- ✅ Check database table exists (`tbl_winners_post_log`)
- ✅ Check database connection is working
- ✅ Check bot console for detailed error logs

### **No Data Showing:**
- ✅ Check `tbl_score_adjustments` table has data with `action = 'add'`
- ✅ Check timestamps are recent (within 24 hours for 24h mode)
- ✅ Check database queries are working

### **Top 3 Users Not Showing:**
- ✅ Check if any users received DSPOINC in time range
- ✅ Check Discord API access (for username fetching)
- ✅ Check database has user_id values

---

## 📝 **EXAMPLE USAGE SCENARIOS**

### **Daily Summary (Recommended):**
```
/winners post mode:since_last
```
**When:** Once per day  
**Result:** Shows only new DSPOINC distributed since yesterday  
**Benefit:** Prevents double-counting, shows daily activity

### **Weekly Overview:**
```
/winners post mode:24h
```
**When:** End of week  
**Result:** Shows all DSPOINC from last 24 hours  
**Benefit:** Complete snapshot of recent activity

### **First-Time Setup:**
```
/winners post
```
**When:** First time using command  
**Result:** Shows last 24 hours (fallback mode)  
**Benefit:** Establishes baseline for future incremental posts

---

## ✅ **SUCCESS CRITERIA**

The command is working correctly if:
- ✅ Embed posts to `#winners` channel
- ✅ Role is tagged correctly
- ✅ All fields display with correct data
- ✅ Top 3 users show with medals
- ✅ Since_last mode shows incremental data
- ✅ 24h mode shows full 24-hour window
- ✅ No errors in bot console
- ✅ Tracking table updates correctly (since_last mode)

---

## 🎯 **QUICK REFERENCE**

| Mode | Command | Description | Updates Tracking? |
|------|---------|-------------|-------------------|
| **Default** | `/winners post` | Since last post (incremental) | ✅ Yes |
| **24h** | `/winners post mode:24h` | Last 24 hours (fixed window) | ❌ No |
| **Since Last** | `/winners post mode:since_last` | Since last post (incremental) | ✅ Yes |

---

**Ready to Test!** 🚀
