# 🚀 Twitter Mission System - Implementation Roadmap

## 🎯 **IMPLEMENTATION ORDER & PRIORITIES**

### **Phase 1: Foundation Setup (Start Here)**
**Estimated Time:** 1-2 sessions  
**Goal:** Get the basic system working with manual verification

---

## 📋 **STEP-BY-STEP IMPLEMENTATION PLAN**

### **STEP 1: Database Schema Setup** ⭐ **START HERE**
**Priority:** CRITICAL - Foundation for everything else

#### **A. Create Database Tables**
```sql
-- Add Twitter columns to existing users table
ALTER TABLE tbl_users ADD COLUMN twitter_username TEXT;
ALTER TABLE tbl_users ADD COLUMN twitter_linked_at DATETIME;
ALTER TABLE tbl_users ADD COLUMN twitter_verification_status TEXT DEFAULT 'unverified';

-- Create Twitter missions table
CREATE TABLE IF NOT EXISTS tbl_twitter_missions (
    mission_id TEXT PRIMARY KEY,
    creator_id TEXT NOT NULL,
    creator_name TEXT NOT NULL,
    tweet_url TEXT NOT NULL,
    tweet_id TEXT NOT NULL,
    mission_type TEXT NOT NULL,
    reward_dspoinc INTEGER NOT NULL,
    duration_hours INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    channel_id TEXT NOT NULL,
    message_id TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    expires_at TEXT,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Create Twitter mission participants table
CREATE TABLE IF NOT EXISTS tbl_twitter_mission_participants (
    participant_id TEXT PRIMARY KEY,
    mission_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    username TEXT NOT NULL,
    joined_at TEXT DEFAULT CURRENT_TIMESTAMP,
    status TEXT NOT NULL DEFAULT 'joined',
    verification_status TEXT DEFAULT 'pending',
    verification_attempts INTEGER DEFAULT 0,
    completed_at TEXT,
    reward_claimed INTEGER DEFAULT 0,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mission_id) REFERENCES tbl_twitter_missions(mission_id)
);

-- Create Twitter verification logs table
CREATE TABLE IF NOT EXISTS tbl_twitter_verification_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    mission_id TEXT NOT NULL,
    verification_attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    verification_status TEXT NOT NULL,
    twitter_api_response TEXT,
    reward_distributed INTEGER DEFAULT 0,
    error_message TEXT,
    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id),
    FOREIGN KEY (mission_id) REFERENCES tbl_twitter_missions(mission_id)
);
```

#### **B. Test Database Operations**
- Verify tables created successfully
- Test basic INSERT/SELECT operations
- Confirm foreign key relationships work

---

### **STEP 2: Basic Discord Commands** ⭐ **HIGH PRIORITY**
**Priority:** HIGH - Core user functionality

#### **A. Create `/set twitter` Command**
**File:** `discord/commands/set-twitter.js`

```javascript
const { SlashCommandBuilder, EmbedBuilder } = require('discord.js');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('set')
        .setDescription('Set your social media accounts')
        .addSubcommand(subcommand =>
            subcommand
                .setName('twitter')
                .setDescription('Link your Twitter account for missions')
                .addStringOption(option =>
                    option.setName('username')
                        .setDescription('Your Twitter username (without @)')
                        .setRequired(true))),

    async execute(interaction, queryDb) {
        try {
            const twitterUsername = interaction.options.getString('username');
            
            // Validate Twitter username format
            if (!isValidTwitterUsername(twitterUsername)) {
                return interaction.reply({ 
                    content: '❌ **Invalid Twitter username!** Please provide a valid username (without @).', 
                    ephemeral: true 
                });
            }

            // Check if username is already linked
            const existingUser = await queryDb(`
                SELECT discord_id FROM tbl_users 
                WHERE twitter_username = ? AND discord_id != ?
            `, [twitterUsername, interaction.user.id]);

            if (existingUser.length > 0) {
                return interaction.reply({ 
                    content: '❌ **Twitter account already linked!** This Twitter account is already linked to another Discord user.', 
                    ephemeral: true 
                });
            }

            // Update user's Twitter account
            await queryDb(`
                UPDATE tbl_users 
                SET twitter_username = ?, twitter_linked_at = CURRENT_TIMESTAMP, twitter_verification_status = 'linked'
                WHERE discord_id = ?
            `, [twitterUsername, interaction.user.id]);

            // Create success embed
            const embed = new EmbedBuilder()
                .setTitle('🐦 Twitter Account Linked')
                .setDescription(`Your Twitter account **@${twitterUsername}** has been successfully linked!`)
                .addFields(
                    { name: '✅ Status', value: 'Account linked and ready for missions', inline: true },
                    { name: '🔗 Username', value: `@${twitterUsername}`, inline: true },
                    { name: '📅 Linked', value: `<t:${Math.floor(Date.now() / 1000)}:R>`, inline: true }
                )
                .setColor(0x1DA1F2)
                .setFooter({ text: 'You can now participate in Twitter missions!' })
                .setTimestamp();

            await interaction.reply({ embeds: [embed], ephemeral: true });

            console.log(`[TWITTER LINK] User ${interaction.user.username} linked Twitter @${twitterUsername}`);

        } catch (error) {
            console.error('[TWITTER LINK] Error:', error);
            await interaction.reply({ 
                content: '❌ **Error linking Twitter account!** Please try again.', 
                ephemeral: true 
            });
        }
    }
};

function isValidTwitterUsername(username) {
    const twitterRegex = /^[a-zA-Z0-9_]{1,15}$/;
    return twitterRegex.test(username);
}
```

#### **B. Create Basic `/tweet` Command**
**File:** `discord/commands/tweet-mission.js`

```javascript
const { SlashCommandBuilder, EmbedBuilder, ActionRowBuilder, ButtonBuilder, ButtonStyle } = require('discord.js');
const { v4: uuidv4 } = require('uuid');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('tweet')
        .setDescription('Create a Twitter mission for members (mods/admins only)')
        .addStringOption(option =>
            option.setName('tweet_url')
                .setDescription('The URL of the Twitter post')
                .setRequired(true))
        .addStringOption(option =>
            option.setName('type')
                .setDescription('Type of interaction required')
                .setRequired(true)
                .addChoices(
                    { name: 'Like', value: 'like' },
                    { name: 'Retweet', value: 'retweet' },
                    { name: 'Comment', value: 'comment' },
                    { name: 'Like & Retweet', value: 'like_retweet' }
                ))
        .addIntegerOption(option =>
            option.setName('duration')
                .setDescription('Duration of the mission in hours')
                .setRequired(true))
        .addIntegerOption(option =>
            option.setName('reward')
                .setDescription('DSPOINC reward for completing the mission')
                .setRequired(true)),

    async execute(interaction, queryDb) {
        try {
            // Permission check
            const userRoles = interaction.member.roles.cache;
            const hasModRole = userRoles.has(process.env.MOD_ROLE_ID);
            const hasAdminRole = userRoles.some(role =>
                ['Founder', 'Moderator', 'Admin'].includes(role.name)
            );
            const hasManageGuild = interaction.member.permissions.has('ManageGuild');

            if (!hasModRole && !hasAdminRole && !hasManageGuild) {
                return interaction.reply({
                    content: '❌ **Admin Access Required**\nYou need a Moderator/Admin role or ManageGuild permission to create Twitter missions.',
                    ephemeral: true
                });
            }

            await interaction.deferReply();

            const tweetUrl = interaction.options.getString('tweet_url');
            const missionType = interaction.options.getString('type');
            const durationHours = interaction.options.getInteger('duration');
            const rewardDspoinc = interaction.options.getInteger('reward');

            // Extract tweet ID
            const tweetIdMatch = tweetUrl.match(/\/status\/(\d+)/);
            if (!tweetIdMatch || !tweetIdMatch[1]) {
                return interaction.editReply({
                    content: '❌ **Invalid Tweet URL.** Please provide a valid Twitter post URL.',
                    ephemeral: true
                });
            }
            const tweetId = tweetIdMatch[1];

            // Generate mission ID
            const missionId = `twitter_mission_${Date.now()}`;
            
            // Calculate expiration time
            const expiresAt = new Date(Date.now() + (durationHours * 60 * 60 * 1000));

            // Create mission in database
            await queryDb(`
                INSERT INTO tbl_twitter_missions 
                (mission_id, creator_id, creator_name, tweet_url, tweet_id, mission_type, reward_dspoinc, duration_hours, status, channel_id, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
            `, [missionId, interaction.user.id, interaction.user.username, tweetUrl, tweetId, missionType, rewardDspoinc, durationHours, interaction.channel.id, expiresAt.toISOString()]);

            // Create mission embed
            const embed = new EmbedBuilder()
                .setTitle('🎯 Twitter Mission')
                .setDescription(`Complete the required Twitter actions to earn **${rewardDspoinc} DSPOINC**!`)
                .addFields(
                    { name: '🔗 Tweet URL', value: tweetUrl, inline: false },
                    { name: '📋 Required Actions', value: getMissionTypeDescription(missionType), inline: true },
                    { name: '💰 Reward', value: `${rewardDspoinc} DSPOINC`, inline: true },
                    { name: '⏰ Duration', value: `${durationHours} hours`, inline: true },
                    { name: '⏳ Expires', value: `<t:${Math.floor(expiresAt.getTime() / 1000)}:R>`, inline: false },
                    { name: '📝 Requirements', value: 'Link your Twitter with `/set twitter <username>`', inline: false }
                )
                .setColor(0x1DA1F2)
                .setFooter({ text: `Mission ID: ${missionId}` })
                .setTimestamp();

            // Add participation button
            const row = new ActionRowBuilder()
                .addComponents(
                    new ButtonBuilder()
                        .setCustomId(`join_twitter_mission_${missionId}`)
                        .setLabel('🎯 Join Mission')
                        .setStyle(ButtonStyle.Primary)
                );

            // Send mission message
            await interaction.editReply({ 
                embeds: [embed], 
                components: [row],
                content: `@everyone **New Twitter Mission!** Complete the task to earn **${rewardDspoinc} DSPOINC**!`
            });

            console.log(`[TWITTER MISSION] Created mission ${missionId} by ${interaction.user.username}`);

        } catch (error) {
            console.error('[TWITTER MISSION] Error:', error);
            await interaction.editReply({
                content: '❌ **Error creating mission!** Please try again.',
                ephemeral: true
            });
        }
    }
};

function getMissionTypeDescription(type) {
    const descriptions = {
        'like': 'Like the tweet',
        'retweet': 'Retweet the tweet',
        'comment': 'Comment on the tweet',
        'like_retweet': 'Like and retweet the tweet'
    };
    return descriptions[type] || type;
}
```

#### **C. Test Commands**
- Test `/set twitter` command with valid/invalid usernames
- Test `/tweet` command with different mission types
- Verify database entries are created correctly

---

### **STEP 3: Mission Participation System** ⭐ **HIGH PRIORITY**
**Priority:** HIGH - Core user interaction

#### **A. Add Button Handler to `index.js`**
```javascript
// Add to the button interaction handler in index.js
else if (customId.startsWith('join_twitter_mission_')) {
    const missionId = customId.replace('join_twitter_mission_', '');
    await handleTwitterMissionJoin(interaction, missionId, queryDb);
}
```

#### **B. Create Mission Join Handler**
```javascript
// Add this function to index.js or create separate file
async function handleTwitterMissionJoin(interaction, missionId, queryDb) {
    try {
        const userId = interaction.user.id;
        const username = interaction.user.username;

        // Check if user has linked Twitter account
        const user = await queryDb(`
            SELECT twitter_username FROM tbl_users WHERE discord_id = ?
        `, [userId]);

        if (!user || !user[0].twitter_username) {
            return interaction.reply({ 
                content: '❌ **Twitter account required!** Please link your Twitter account first using `/set twitter <username>`', 
                ephemeral: true 
            });
        }

        // Check if user already participated
        const existingParticipation = await queryDb(`
            SELECT * FROM tbl_twitter_mission_participants 
            WHERE user_id = ? AND mission_id = ?
        `, [userId, missionId]);

        if (existingParticipation.length > 0) {
            return interaction.reply({ 
                content: '❌ **Already participating!** You have already joined this mission.', 
                ephemeral: true 
            });
        }

        // Add user to mission
        await queryDb(`
            INSERT INTO tbl_twitter_mission_participants 
            (mission_id, user_id, username, joined_at, status, verification_status)
            VALUES (?, ?, ?, datetime('now'), 'joined', 'pending')
        `, [missionId, userId, username]);

        await interaction.reply({ 
            content: `🎯 **Mission joined!** Complete the required Twitter actions and contact an admin for verification. Your Twitter: @${user[0].twitter_username}`, 
            ephemeral: true 
        });

        console.log(`[TWITTER MISSION] User ${username} joined mission ${missionId}`);

    } catch (error) {
        console.error(`[TWITTER MISSION] Error handling join for mission ${missionId}:`, error);
        await interaction.reply({ 
            content: '❌ **Error joining mission!** Please try again.', 
            ephemeral: true 
        });
    }
}
```

#### **C. Test Mission Participation**
- Test joining missions with/without linked Twitter accounts
- Verify database entries for participants
- Test duplicate participation prevention

---

### **STEP 4: Basic Admin Interface Integration** ⭐ **MEDIUM PRIORITY**
**Priority:** MEDIUM - Admin management capabilities

#### **A. Add Twitter Missions Tab to Admin Interface**
**File:** `public/admin-interface.html`

Add the Twitter Missions tab button and content structure (as detailed in the admin interface integration document).

#### **B. Create Basic API Endpoints**
**File:** `api/admin/get-twitter-missions-overview.php`

```php
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once '../../config/database.php';
    
    $db = getSQLite3Connection();
    
    // Get overview statistics
    $overview = [];
    
    // Active missions count
    $activeMissions = $db->query("SELECT COUNT(*) as count FROM tbl_twitter_missions WHERE status = 'active'")->fetchArray();
    $overview['active_missions'] = $activeMissions['count'];
    
    // Completed missions count
    $completedMissions = $db->query("SELECT COUNT(*) as count FROM tbl_twitter_missions WHERE status = 'completed'")->fetchArray();
    $overview['completed_missions'] = $completedMissions['count'];
    
    // Linked users count
    $linkedUsers = $db->query("SELECT COUNT(*) as count FROM tbl_users WHERE twitter_username IS NOT NULL")->fetchArray();
    $overview['linked_users'] = $linkedUsers['count'];
    
    // Total rewards distributed
    $totalRewards = $db->query("SELECT SUM(reward_dspoinc) as total FROM tbl_twitter_missions WHERE status = 'completed'")->fetchArray();
    $overview['total_rewards'] = $totalRewards['total'] ?: 0;
    
    echo json_encode([
        'success' => true,
        'overview' => $overview
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

#### **C. Test Admin Interface**
- Verify Twitter Missions tab loads
- Test overview statistics display
- Confirm API endpoints work correctly

---

### **STEP 5: Manual Verification System** ⭐ **MEDIUM PRIORITY**
**Priority:** MEDIUM - Get system working without Twitter API

#### **A. Create Admin Verification Command**
**File:** `discord/commands/verify-twitter.js`

```javascript
const { SlashCommandBuilder, EmbedBuilder } = require('discord.js');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('verify-twitter')
        .setDescription('Verify a user completed a Twitter mission (admin only)')
        .addStringOption(option =>
            option.setName('mission_id')
                .setDescription('The mission ID to verify')
                .setRequired(true))
        .addUserOption(option =>
            option.setName('user')
                .setDescription('The user to verify')
                .setRequired(true)),

    async execute(interaction, queryDb) {
        try {
            // Permission check
            const userRoles = interaction.member.roles.cache;
            const hasModRole = userRoles.has(process.env.MOD_ROLE_ID);
            const hasAdminRole = userRoles.some(role =>
                ['Founder', 'Moderator', 'Admin'].includes(role.name)
            );
            const hasManageGuild = interaction.member.permissions.has('ManageGuild');

            if (!hasModRole && !hasAdminRole && !hasManageGuild) {
                return interaction.reply({
                    content: '❌ **Admin Access Required**',
                    ephemeral: true
                });
            }

            const missionId = interaction.options.getString('mission_id');
            const user = interaction.options.getUser('user');

            // Get mission details
            const mission = await queryDb(`
                SELECT * FROM tbl_twitter_missions WHERE mission_id = ?
            `, [missionId]);

            if (!mission || mission.length === 0) {
                return interaction.reply({
                    content: '❌ **Mission not found!**',
                    ephemeral: true
                });
            }

            // Get user participation
            const participation = await queryDb(`
                SELECT * FROM tbl_twitter_mission_participants 
                WHERE mission_id = ? AND user_id = ?
            `, [missionId, user.id]);

            if (!participation || participation.length === 0) {
                return interaction.reply({
                    content: '❌ **User has not joined this mission!**',
                    ephemeral: true
                });
            }

            // Update participation status
            await queryDb(`
                UPDATE tbl_twitter_mission_participants 
                SET verification_status = 'verified', completed_at = datetime('now'), reward_claimed = ?
                WHERE mission_id = ? AND user_id = ?
            `, [mission[0].reward_dspoinc, missionId, user.id]);

            // Add DSPOINC to user's balance
            await queryDb(`
                INSERT INTO tbl_user_scores (user_id, score, game, source, timestamp)
                VALUES (?, ?, 'twitter_mission', 'mission_reward', datetime('now'))
            `, [user.id, mission[0].reward_dspoinc]);

            // Log verification
            await queryDb(`
                INSERT INTO tbl_twitter_verification_logs 
                (user_id, mission_id, verification_status, reward_distributed)
                VALUES (?, ?, 'success', ?)
            `, [user.id, missionId, mission[0].reward_dspoinc]);

            const embed = new EmbedBuilder()
                .setTitle('✅ Twitter Mission Verified')
                .setDescription(`**${user.username}** has been verified for mission **${missionId}**`)
                .addFields(
                    { name: '💰 Reward', value: `${mission[0].reward_dspoinc} DSPOINC`, inline: true },
                    { name: '🎯 Mission Type', value: mission[0].mission_type, inline: true },
                    { name: '📅 Verified', value: `<t:${Math.floor(Date.now() / 1000)}:R>`, inline: true }
                )
                .setColor(0x10b981)
                .setTimestamp();

            await interaction.reply({ embeds: [embed] });

            console.log(`[TWITTER VERIFY] User ${user.username} verified for mission ${missionId}`);

        } catch (error) {
            console.error('[TWITTER VERIFY] Error:', error);
            await interaction.reply({
                content: '❌ **Error verifying mission!** Please try again.',
                ephemeral: true
            });
        }
    }
};
```

#### **B. Test Manual Verification**
- Test verification command with different users
- Verify DSPOINC rewards are distributed correctly
- Check database logs for verification records

---

## 🎯 **IMPLEMENTATION CHECKLIST**

### **Phase 1: Foundation (Steps 1-5)**
- [ ] **Database Schema** - Create all required tables
- [ ] **Basic Commands** - `/set twitter` and `/tweet` commands
- [ ] **Mission Participation** - Join mission functionality
- [ ] **Admin Interface** - Basic Twitter Missions tab
- [ ] **Manual Verification** - Admin verification system

### **Phase 2: Enhancement (Future)**
- [ ] **Twitter API Integration** - Automatic verification
- [ ] **Advanced Admin Features** - Complete management interface
- [ ] **Analytics Dashboard** - Performance metrics
- [ ] **Automated Rewards** - Automatic DSPOINC distribution

---

## 🚀 **RECOMMENDED STARTING POINT**

### **Start with Step 1: Database Schema Setup**
1. **Create the database tables** using the SQL provided
2. **Test basic operations** to ensure everything works
3. **Verify foreign key relationships** are working correctly

### **Then Move to Step 2: Basic Discord Commands**
1. **Create `/set twitter` command** for user account linking
2. **Create `/tweet` command** for mission creation
3. **Test both commands** thoroughly

### **Continue with Steps 3-5**
1. **Mission participation system**
2. **Basic admin interface integration**
3. **Manual verification system**

---

## ⚠️ **IMPORTANT NOTES**

### **Start Simple:**
- **Begin with manual verification** (no Twitter API needed initially)
- **Get the complete flow working** end-to-end
- **Add Twitter API integration** later for automation

### **Testing Strategy:**
- **Test each step thoroughly** before moving to the next
- **Use sample data** to verify database operations
- **Test with multiple users** to ensure scalability

### **Deployment Approach:**
- **Deploy incrementally** - each step can be deployed independently
- **Test in production** after each major step
- **Maintain backward compatibility** throughout

---

**IMPLEMENTATION ROADMAP CREATED:** September 22, 2025  
**STATUS:** Ready for Step-by-Step Implementation  
**PRIORITY:** HIGH - Complete Twitter Mission System  
**ESTIMATED TIME:** 4-6 development sessions for Phase 1  
**APPROACH:** Incremental - Start simple, add complexity gradually
