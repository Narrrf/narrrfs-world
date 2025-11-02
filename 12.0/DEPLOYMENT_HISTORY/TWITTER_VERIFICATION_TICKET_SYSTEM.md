# 🐦 TWITTER MISSION VERIFICATION TICKET SYSTEM

**Date:** November 1, 2025  
**Purpose:** Create Discord ticket system for Twitter mission verification (like `/useitem`)  
**Status:** 🔧 **IMPLEMENTATION IN PROGRESS**  

---

## 🎯 SYSTEM OVERVIEW

### **Current Flow (Admin Interface Only):**
1. User clicks "🎯 Join Mission" button
2. Status set to `verification_status = 'pending'`
3. **Admin uses web interface OR `/verify-twitter` command**
4. User manually approved/denied
5. Reward distributed if approved

### **New Flow (Discord Ticket System):**
1. User clicks "🎯 Join Mission" button
2. **Auto-creates ticket in Discord** (like `/useitem`)
3. Ticket appears in "🎫-mission-verification" category
4. **Admin clicks Approve/Deny buttons in Discord**
5. Reward distributed automatically
6. User notified via DM
7. Ticket auto-closes after 30 seconds

---

## 🗄️ DATABASE SETUP

### **New Table: `tbl_twitter_verification_requests`**

```sql
CREATE TABLE IF NOT EXISTS tbl_twitter_verification_requests (
    request_id INTEGER PRIMARY KEY AUTOINCREMENT,
    mission_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    username TEXT NOT NULL,
    twitter_username TEXT NOT NULL,
    mission_type TEXT NOT NULL,
    reward_amount INTEGER NOT NULL,
    ticket_channel_id TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    admin_id TEXT,
    admin_username TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME
);

CREATE INDEX idx_twitter_verification_user ON tbl_twitter_verification_requests(user_id);
CREATE INDEX idx_twitter_verification_mission ON tbl_twitter_verification_requests(mission_id);
CREATE INDEX idx_twitter_verification_status ON tbl_twitter_verification_requests(status);
```

---

## 📋 RENDER SHELL COMMANDS

### **Run on Render:**

```bash
# Step 1: Create table
cd /var/www/html/db
sqlite3 narrrf_world.sqlite "CREATE TABLE IF NOT EXISTS tbl_twitter_verification_requests (request_id INTEGER PRIMARY KEY AUTOINCREMENT, mission_id TEXT NOT NULL, user_id TEXT NOT NULL, username TEXT NOT NULL, twitter_username TEXT NOT NULL, mission_type TEXT NOT NULL, reward_amount INTEGER NOT NULL, ticket_channel_id TEXT NOT NULL, status TEXT DEFAULT 'pending', admin_id TEXT, admin_username TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, processed_at DATETIME);"

# Step 2: Create indexes
sqlite3 narrrf_world.sqlite "CREATE INDEX IF NOT EXISTS idx_twitter_verification_user ON tbl_twitter_verification_requests(user_id); CREATE INDEX IF NOT EXISTS idx_twitter_verification_mission ON tbl_twitter_verification_requests(mission_id); CREATE INDEX IF NOT EXISTS idx_twitter_verification_status ON tbl_twitter_verification_requests(status);"

# Step 3: Verify
echo ".schema tbl_twitter_verification_requests" | sqlite3 narrrf_world.sqlite

# Step 4: Backup to /data
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite && echo "✅ Done!"
```

---

## 🎯 IMPLEMENTATION PLAN

### **Files to Create:**
1. ✅ `discord/commands/twitter-mission-handlers.js` - Button handlers (approve/deny)

### **Files to Modify:**
1. `discord/index.js` - Add ticket creation to `handleTwitterMissionJoin()`
2. `discord/index.js` - Register button handlers for approve/deny

### **Environment Variables Needed:**
- `TWITTER_VERIFICATION_CATEGORY_ID` - Discord category for verification tickets

---

## 🚀 BENEFITS

### **For Admins:**
- ✅ Approve/deny directly in Discord (no need to switch to admin interface)
- ✅ Complete verification history tracked
- ✅ Auto-closing tickets keep Discord clean
- ✅ Works alongside admin interface (dual system)

### **For Users:**
- ✅ Instant feedback when verified
- ✅ Clear notification if denied
- ✅ DM notifications for status updates
- ✅ Professional ticket workflow

---

**Status:** Ready to implement after database table is created! 🚀

