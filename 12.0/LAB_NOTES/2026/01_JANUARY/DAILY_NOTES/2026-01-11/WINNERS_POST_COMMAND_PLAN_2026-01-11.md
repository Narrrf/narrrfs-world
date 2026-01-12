# 🏆 Winners Post Command - Implementation Plan

**Date:** January 11, 2026  
**Status:** 📋 **PLANNING**  
**Type:** Discord Bot Command Enhancement

---

## 🎯 **REQUIREMENT**

Create a new Discord bot command `/winners post` with **TWO MODES**:

### **Mode 1: 24h Snapshot (Fixed Window)**
1. **Posts to #winners channel** (ID: `1459935292659208336`)
2. **Shows summary of DSPOINC distributed** in the last 24 hours (fixed window)
3. **Displays in embed format** (similar to "Items Transferred Successfully!" pop-up style)
4. **Shows total DSPOINC amount** distributed

### **Mode 2: Since Last Snapshot (Incremental)**
1. **Posts to #winners channel** (ID: `1459935292659208336`)
2. **Shows summary of DSPOINC distributed** since the last `/winners post` command execution
3. **Prevents double-counting** - Each post only shows new data since last post
4. **Tracks last post timestamp** in database
5. **Displays in embed format** (similar to "Items Transferred Successfully!" pop-up style)

**Key Feature:** Both modes prevent double-posting by tracking execution history

---

## 📊 **DATABASE ANALYSIS**

### **Source Table:**
- **Table:** `tbl_score_adjustments`
- **Key Fields:**
  - `user_id` - Discord ID of user who received DSPOINC
  - `amount` - DSPOINC amount (positive for additions)
  - `action` - Action type (`'add'` for rewards)
  - `admin_id` - Source of reward (e.g., `'system-riddle-reward'`, Discord user IDs for manual)
  - `reason` - Description of the adjustment
  - `timestamp` - When the adjustment was made

### **Test Query Results (Last 24 Hours - Updated):**
- **Total Adjustments:** 34
- **Total DSPOINC:** 433,409 DSPOINC
- **Unique Users:** 14 users
- **Latest Timestamp:** 2026-01-11 22:18:43

### **Database Schema for Tracking:**
We need a simple table to track the last post timestamp. **Run this SQL on Render:**

```sql
CREATE TABLE IF NOT EXISTS tbl_winners_post_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  post_type TEXT NOT NULL, -- '24h' or 'since_last'
  last_post_timestamp DATETIME NOT NULL,
  total_dspoinc INTEGER,
  unique_users INTEGER,
  total_adjustments INTEGER,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(post_type) -- Only one record per type
);
```

**Note:** This is the ONLY table that needs to be created. All other data comes from existing `tbl_score_adjustments` table.

---

## 🎨 **EMBED DESIGN**

### **Style Reference:**
Based on screenshot, the embed should be similar to "Items Transferred Successfully!" style:
- Clean, structured format
- Clear title
- Summary information
- Professional appearance

### **Proposed Embed Structure:**
```
Title: "🏆 Daily DSPOINC Distribution Summary"
Color: Gold/Yellow (#FFD700 or similar)
Description: Summary of DSPOINC distributed in last 24 hours

Fields:
- **Total DSPOINC Distributed:** [amount] DSPOINC
- **Recipients:** [count] members
- **Time Period:** Last 24 hours
- **Breakdown:** (Optional) By source/reason
```

---

## 🔧 **IMPLEMENTATION PLAN**

### **1. Command Structure:**
```javascript
module.exports = {
  data: new SlashCommandBuilder()
    .setName('winners')
    .setDescription('🏆 Winners channel management')
    .addSubcommand(subcommand =>
      subcommand
        .setName('post')
        .setDescription('📊 Post DSPOINC distribution summary to #winners channel')
        .addStringOption(option =>
          option
            .setName('mode')
            .setDescription('Snapshot mode (default: since_last)')
            .addChoices(
              { name: '📅 Last 24 Hours (Fixed Window)', value: '24h' },
              { name: '🔄 Since Last Post (Incremental)', value: 'since_last' }
            )
            .setRequired(false)
        )
    ),
  
  async execute(interaction, queryDb) {
    const mode = interaction.options.getString('mode') || 'since_last';
    // Implementation
  }
};
```

### **2. Database Query Logic:**

#### **Mode 1: 24h Snapshot (Fixed Window)**
```sql
-- Query: Last 24 hours from now
SELECT 
  SUM(amount) as total_dspoinc,
  COUNT(*) as total_adjustments,
  COUNT(DISTINCT user_id) as unique_users
FROM tbl_score_adjustments
WHERE action = 'add'
AND timestamp >= datetime('now', '-24 hours')
```

#### **Mode 2: Since Last Snapshot (Incremental)**
```sql
-- Step 1: Get last post timestamp
SELECT last_post_timestamp 
FROM tbl_winners_post_log 
WHERE post_type = 'since_last'
LIMIT 1

-- Step 2: Query from last timestamp to now
SELECT 
  SUM(amount) as total_dspoinc,
  COUNT(*) as total_adjustments,
  COUNT(DISTINCT user_id) as unique_users
FROM tbl_score_adjustments
WHERE action = 'add'
AND timestamp > ? -- last_post_timestamp
AND timestamp <= datetime('now')

-- Step 3: Update last post timestamp after posting
INSERT OR REPLACE INTO tbl_winners_post_log 
  (post_type, last_post_timestamp, total_dspoinc, unique_users, total_adjustments)
VALUES 
  ('since_last', datetime('now'), ?, ?, ?)
```

#### **Top 3 Users Query (Both Modes)**
```sql
SELECT 
  user_id,
  SUM(amount) as total_dspoinc,
  COUNT(*) as adjustment_count
FROM tbl_score_adjustments
WHERE action = 'add'
AND timestamp >= ? -- Start timestamp (24h ago or last_post_timestamp)
AND timestamp <= datetime('now')
GROUP BY user_id
ORDER BY total_dspoinc DESC
LIMIT 3
```

#### **Optional: Breakdown by Source (Both Modes)**
```sql
SELECT 
  admin_id,
  COUNT(*) as count,
  SUM(amount) as total
FROM tbl_score_adjustments
WHERE action = 'add'
AND timestamp >= ? -- Start timestamp (24h ago or last_post_timestamp)
AND timestamp <= datetime('now')
GROUP BY admin_id
ORDER BY total DESC
```

### **3. Channel Posting:**
- Fetch #winners channel by ID: `1459935292659208336`
- Create embed with summary data (includes top 3 users)
- **Tag Community Member role:** `<@&1332017969181622342>` in message content
- Send message with role mention + embed to channel
- Reply to interaction (ephemeral confirmation)

### **4. Permission Requirements:**
- Command should require admin/moderator permissions
- Only authorized users can post summaries
- Consider adding permission check in command

---

## 📋 **FEATURES TO IMPLEMENT**

### **Core Features:**
1. ✅ **Dual Mode Support** - 24h snapshot OR since last snapshot
2. ✅ **Query Database** - Get DSPOINC distribution data from `tbl_score_adjustments`
3. ✅ **Calculate Summary** - Sum total DSPOINC, count users, count adjustments
4. ✅ **Top 3 Users** - Display top 3 recipients with amounts
5. ✅ **Role Tagging** - Tag Community Member role (`<@&1332017969181622342>`) in every post
6. ✅ **Track Last Post** - Store/update last post timestamp for incremental mode
7. ✅ **Create Embed** - Build Discord embed with summary information
8. ✅ **Post to Channel** - Send embed to #winners channel
9. ✅ **Prevent Double-Counting** - Since last mode only shows new data

### **Optional Features:**
- **Breakdown by Source** - Show distribution by `admin_id` (manual vs system rewards)
- **Top Recipients** - Show top 5 users who received most DSPOINC
- **Reason Breakdown** - Show distribution by `reason` field
- **Confirmation Reply** - Reply to interaction with success message (ephemeral)

---

## 🔍 **DATABASE QUERY DETAILS**

### **Implementation Logic:**

#### **Mode 1: 24h Snapshot**
```javascript
const mode = interaction.options.getString('mode') || 'since_last';

if (mode === '24h') {
  // Fixed 24-hour window
  const result = await queryDb(`
    SELECT 
      SUM(amount) as total_dspoinc,
      COUNT(*) as total_adjustments,
      COUNT(DISTINCT user_id) as unique_users
    FROM tbl_score_adjustments
    WHERE action = 'add'
    AND timestamp >= datetime('now', '-24 hours')
  `);
  
  // Post embed, but don't update tracking table (24h is always fixed window)
}
```

#### **Mode 2: Since Last Snapshot**
```javascript
if (mode === 'since_last') {
  // Get last post timestamp
  const lastPost = await queryDb(`
    SELECT last_post_timestamp 
    FROM tbl_winners_post_log 
    WHERE post_type = 'since_last'
    LIMIT 1
  `);
  
  const startTimestamp = lastPost[0]?.last_post_timestamp || datetime('now', '-24 hours');
  
  // Query from last timestamp to now
  const result = await queryDb(`
    SELECT 
      SUM(amount) as total_dspoinc,
      COUNT(*) as total_adjustments,
      COUNT(DISTINCT user_id) as unique_users
    FROM tbl_score_adjustments
    WHERE action = 'add'
    AND timestamp > ?
    AND timestamp <= datetime('now')
  `, [startTimestamp]);
  
  // After posting, update tracking table
  await queryDb(`
    INSERT OR REPLACE INTO tbl_winners_post_log 
      (post_type, last_post_timestamp, total_dspoinc, unique_users, total_adjustments)
    VALUES 
      ('since_last', datetime('now'), ?, ?, ?)
  `, [result[0].total_dspoinc, result[0].unique_users, result[0].total_adjustments]);
}
```

---

## 🎨 **EMBED EXAMPLE**

```javascript
// Fetch top 3 users with usernames
const topUsers = await queryDb(`
  SELECT 
    user_id,
    SUM(amount) as total_dspoinc,
    COUNT(*) as adjustment_count
  FROM tbl_score_adjustments
  WHERE action = 'add'
  AND timestamp >= ?
  AND timestamp <= datetime('now')
  GROUP BY user_id
  ORDER BY total_dspoinc DESC
  LIMIT 3
`, [startTimestamp]);

// Build top 3 users display (with Discord username fetching)
let topUsersText = 'No recipients yet';
if (topUsers && topUsers.length > 0) {
  const topUsersList = [];
  for (let i = 0; i < topUsers.length; i++) {
    const userData = topUsers[i];
    try {
      const user = await interaction.client.users.fetch(userData.user_id);
      const medal = i === 0 ? '🥇' : i === 1 ? '🥈' : '🥉';
      topUsersList.push(`${medal} ${user.username}: ${parseInt(userData.total_dspoinc).toLocaleString()} DSPOINC`);
    } catch (error) {
      // If user not found, use ID
      const medal = i === 0 ? '🥇' : i === 1 ? '🥈' : '🥉';
      topUsersList.push(`${medal} <@${userData.user_id}>: ${parseInt(userData.total_dspoinc).toLocaleString()} DSPOINC`);
    }
  }
  topUsersText = topUsersList.join('\n');
}

const embed = new EmbedBuilder()
  .setColor('#FFD700') // Gold color for winners
  .setTitle('🏆 Daily DSPOINC Distribution Summary')
  .setDescription(`**Distribution Summary ${mode === '24h' ? 'for Last 24 Hours' : 'Since Last Post'}**`)
  .addFields(
    {
      name: '💰 Total DSPOINC Distributed',
      value: `${totalDspoinc.toLocaleString()} DSPOINC`,
      inline: true
    },
    {
      name: '👥 Recipients',
      value: `${uniqueUsers} member${uniqueUsers !== 1 ? 's' : ''}`,
      inline: true
    },
    {
      name: '📊 Total Adjustments',
      value: `${totalAdjustments} transactions`,
      inline: true
    },
    {
      name: '🏅 Top 3 Recipients',
      value: topUsersText,
      inline: false
    }
  )
  .setFooter({ text: 'Narrrf\'s World - Daily Summary', iconURL: interaction.guild.iconURL() })
  .setTimestamp();

// Post to channel with role mention
const channel = await interaction.client.channels.fetch('1459935292659208336');
await channel.send({
  content: '<@&1332017969181622342>', // Community Member role mention
  embeds: [embed]
});
```

---

## 📁 **FILE STRUCTURE**

### **New File:**
- `discord/commands/winners.js` - New command file

### **Registration:**
- Command will be automatically loaded by `deploy-commands.js`
- No changes needed to `discord/index.js` (commands auto-load from `commands/` directory)

---

## 🔒 **PERMISSIONS & SECURITY**

### **Permission Requirements:**
- **Command Permission:** Admin/Moderator only (Manage Messages or similar)
- **Channel Access:** Bot needs "Send Messages" permission in #winners channel
- **Database Access:** Uses existing `queryDb` function (already has access)

### **Permission Check:**
```javascript
// Check if user has admin/moderator permissions
if (!interaction.member.permissions.has(PermissionFlagsBits.ManageMessages)) {
  return interaction.reply({
    content: '❌ You do not have permission to use this command.',
    ephemeral: true
  });
}
```

---

## ✅ **IMPLEMENTATION CHECKLIST**

- [ ] **Database Setup:**
  - [ ] Create `tbl_winners_post_log` table (if doesn't exist)
  - [ ] Test table creation and queries
  
- [ ] **Command Implementation:**
  - [ ] Create `discord/commands/winners.js` file
  - [ ] Implement command structure with `/winners post` subcommand
  - [ ] Add mode selection (24h vs since_last)
  - [ ] Add database queries for both modes
  - [ ] Add top 3 users query and username fetching
  - [ ] Implement timestamp tracking for since_last mode
  - [ ] Create embed builder function (with top 3 users field)
  - [ ] Implement channel fetching and message sending
  - [ ] Add role mention (`<@&1332017969181622342>`) to message content
  - [ ] Add permission checks
  - [ ] Add error handling
  
- [ ] **Testing:**
  - [ ] Test 24h mode (fixed window)
  - [ ] Test since_last mode (first run - should use 24h fallback)
  - [ ] Test since_last mode (second run - should show only new data)
  - [ ] Test with no data scenarios
  - [ ] Test permission checks
  - [ ] Test error handling
  
- [ ] **Deployment:**
  - [ ] Deploy command (`node deploy-commands.js`)
  - [ ] Test in Discord server
  - [ ] Verify embed format matches requirements
  - [ ] Verify no double-counting in since_last mode

---

## 🧪 **TESTING PLAN**

1. **Database Setup Testing:**
   - Test `tbl_winners_post_log` table creation
   - Test timestamp storage and retrieval
   - Test INSERT OR REPLACE logic

2. **Mode 1: 24h Snapshot Testing:**
   - Test command with 24h mode
   - Verify it shows last 24 hours of data
   - Test multiple runs (should show same data if run within 24h)
   - Test with no data in last 24h

3. **Mode 2: Since Last Snapshot Testing:**
   - **First Run:** Test with no previous timestamp (should use 24h fallback)
   - **Second Run:** Test after first run (should show only new data)
   - **Third Run:** Test after second run (verify no double-counting)
   - Test with no new data since last post
   - Verify timestamp updates correctly after each post

4. **General Testing:**
   - Test permission checks (non-admin user)
   - Test error handling (database errors, channel not found)
   - Test embed format and styling
   - Test with real data

5. **Production Testing:**
   - Deploy command to Discord
   - Test in #winners channel
   - Verify embed format matches requirements
   - Verify data accuracy
   - Test both modes with real data
   - Verify no double-counting in since_last mode

---

## 📝 **NOTES**

- Command uses existing `queryDb` function from `discord/index.js`
- Command follows existing command patterns (similar to `balance.js`, `dashboard.js`)
- Embed style should match existing bot embeds for consistency
- Channel ID is hardcoded: `1459935292659208336`
- **Community Member Role ID:** `1332017969181622342` (tagged in every post)
- **Mode 1 (24h):** Always shows last 24 hours (fixed window, no tracking needed)
- **Mode 2 (since_last):** Shows data since last execution (tracks timestamp in `tbl_winners_post_log`)
- **Default Mode:** `since_last` (prevents double-counting, recommended for daily posts)
- Command requires admin/moderator permissions for security
- **Database Table:** `tbl_winners_post_log` stores last post timestamp (created automatically if doesn't exist)
- **Top 3 Users:** Fetched with Discord usernames, falls back to mention if user not found

### **Database Tables Required:**
- ✅ **Existing:** `tbl_score_adjustments` (already exists - source data)
- ✅ **New:** `tbl_winners_post_log` (CREATE TABLE SQL provided above - run on Render)

### **Use Cases:**
- **24h Mode:** Use for one-time reports or fixed time window analysis
- **Since Last Mode:** Use for daily automated posts (recommended - prevents double-counting)

---

**Status:** 📋 **READY FOR IMPLEMENTATION**
