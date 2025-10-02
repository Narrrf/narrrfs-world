# 📝 Discord Bot Twitter Mission System - Implementation Worksheet

## 🎯 **OVERVIEW**
This worksheet guides you through implementing a Twitter mission system in your Discord bot, similar to the paid bot shown in the screenshot. Members will perform specific actions (like, retweet, comment) on designated Twitter posts, and the bot will verify their completion to award DSPOINC rewards.

---

## 📊 **SYSTEM ANALYSIS FROM SCREENSHOT**

### **Current Paid Bot Features:**
- **Command:** `/tweet` with multiple parameters
- **Mission Types:** Like, RT, Reply, Like & RT & Comment, etc.
- **Duration:** Configurable expiry (e.g., "Expires vor 2 Tagen")
- **Rewards:** DSPOINC points (e.g., 500 points)
- **Verification:** Automatic checking of Twitter interactions
- **Notifications:** @everyone announcements for missions

### **Mission Flow:**
1. **Admin creates mission** using `/tweet` command
2. **Bot posts mission** in designated channel (#quests-new)
3. **Members interact** with Twitter post (like, retweet, comment)
4. **Bot verifies** completion automatically
5. **Rewards distributed** (DSPOINC points)

---

## 🛠️ **IMPLEMENTATION PLAN**

### **Phase 1: Command Structure**
```javascript
// Basic command structure
/tweet tweet_url:<twitter_url> type:<interaction_type> duration:<time> reward:<amount> description:<text>
```

### **Phase 2: Database Schema**
```sql
-- Twitter missions table
CREATE TABLE tbl_twitter_missions (
    mission_id TEXT PRIMARY KEY,
    tweet_url TEXT NOT NULL,
    mission_type TEXT NOT NULL, -- 'like', 'retweet', 'comment', 'like_retweet_comment'
    reward_amount INTEGER NOT NULL,
    duration_hours INTEGER NOT NULL,
    created_by TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    status TEXT DEFAULT 'active', -- 'active', 'expired', 'completed'
    description TEXT,
    channel_id TEXT,
    message_id TEXT
);

-- Mission participants table
CREATE TABLE tbl_twitter_mission_participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mission_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    username TEXT NOT NULL,
    completed_at DATETIME,
    reward_claimed INTEGER DEFAULT 0,
    verification_status TEXT DEFAULT 'pending', -- 'pending', 'verified', 'failed'
    FOREIGN KEY (mission_id) REFERENCES tbl_twitter_missions(mission_id)
);
```

### **Phase 3: Core Components**

#### **A. Command Handler (`/tweet`)**
```javascript
const { SlashCommandBuilder } = require('discord.js');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('tweet')
        .setDescription('Create a Twitter mission for community members')
        .addStringOption(option =>
            option.setName('tweet_url')
                .setDescription('Twitter/X post URL')
                .setRequired(true))
        .addStringOption(option =>
            option.setName('type')
                .setDescription('Required interaction type')
                .setRequired(true)
                .addChoices(
                    { name: 'Like', value: 'like' },
                    { name: 'Retweet', value: 'retweet' },
                    { name: 'Comment', value: 'comment' },
                    { name: 'Like & Retweet', value: 'like_retweet' },
                    { name: 'Like & Comment', value: 'like_comment' },
                    { name: 'Retweet & Comment', value: 'retweet_comment' },
                    { name: 'Like & Retweet & Comment', value: 'like_retweet_comment' }
                ))
        .addIntegerOption(option =>
            option.setName('duration')
                .setDescription('Mission duration in hours')
                .setRequired(true))
        .addIntegerOption(option =>
            option.setName('reward')
                .setDescription('DSPOINC reward amount')
                .setRequired(true))
        .addStringOption(option =>
            option.setName('description')
                .setDescription('Mission description')
                .setRequired(false)),

    async execute(interaction, queryDb) {
        // Implementation details below
    }
};
```

#### **B. Mission Creation Logic**
```javascript
async function createTwitterMission(interaction, queryDb) {
    try {
        const tweetUrl = interaction.options.getString('tweet_url');
        const missionType = interaction.options.getString('type');
        const duration = interaction.options.getInteger('duration');
        const reward = interaction.options.getInteger('reward');
        const description = interaction.options.getString('description') || 'Complete the Twitter mission to earn DSPOINC!';

        // Validate tweet URL
        if (!isValidTwitterUrl(tweetUrl)) {
            return interaction.reply({ 
                content: '❌ **Invalid Twitter URL!** Please provide a valid Twitter/X post URL.', 
                ephemeral: true 
            });
        }

        // Generate unique mission ID
        const missionId = `twitter_mission_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        
        // Calculate expiry time
        const expiresAt = new Date(Date.now() + (duration * 60 * 60 * 1000));
        
        // Create mission in database
        await queryDb(`
            INSERT INTO tbl_twitter_missions 
            (mission_id, tweet_url, mission_type, reward_amount, duration_hours, 
             created_by, expires_at, description, channel_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        `, [missionId, tweetUrl, missionType, reward, duration, 
            interaction.user.id, expiresAt, description, interaction.channel.id]);

        // Create mission embed
        const missionEmbed = createMissionEmbed(missionId, tweetUrl, missionType, reward, duration, description, expiresAt);
        
        // Add participation button
        const row = new ActionRowBuilder()
            .addComponents(
                new ButtonBuilder()
                    .setCustomId(`join_twitter_mission_${missionId}`)
                    .setLabel('🎯 Join Mission')
                    .setStyle(ButtonStyle.Primary)
            );

        // Send mission message
        const missionMessage = await interaction.reply({ 
            embeds: [missionEmbed], 
            components: [row],
            content: `@everyone **New Twitter Mission!** Complete the task to earn **${reward} DSPOINC**!`
        });

        // Update database with message ID
        await queryDb(`
            UPDATE tbl_twitter_missions 
            SET message_id = ? 
            WHERE mission_id = ?
        `, [missionMessage.id, missionId]);

        console.log(`[TWITTER MISSION] Created mission ${missionId} by ${interaction.user.username}`);

    } catch (error) {
        console.error('[TWITTER MISSION] Error creating mission:', error);
        await interaction.reply({ 
            content: '❌ **Error creating mission!** Please try again.', 
            ephemeral: true 
        });
    }
}
```

#### **C. Mission Embed Creation**
```javascript
function createMissionEmbed(missionId, tweetUrl, missionType, reward, duration, description, expiresAt) {
    const embed = new EmbedBuilder()
        .setTitle('🎯 Twitter Mission')
        .setDescription(description)
        .addFields(
            { name: '🔗 Tweet URL', value: tweetUrl, inline: false },
            { name: '📋 Required Actions', value: getMissionTypeDescription(missionType), inline: true },
            { name: '💰 Reward', value: `${reward} DSPOINC`, inline: true },
            { name: '⏰ Duration', value: `${duration} hours`, inline: true },
            { name: '⏳ Expires', value: `<t:${Math.floor(expiresAt.getTime() / 1000)}:R>`, inline: false }
        )
        .setColor(0x1DA1F2) // Twitter blue
        .setFooter({ text: `Mission ID: ${missionId}` })
        .setTimestamp();

    return embed;
}

function getMissionTypeDescription(type) {
    const descriptions = {
        'like': '👍 Like the tweet',
        'retweet': '🔄 Retweet the tweet',
        'comment': '💬 Comment on the tweet',
        'like_retweet': '👍 Like + 🔄 Retweet',
        'like_comment': '👍 Like + 💬 Comment',
        'retweet_comment': '🔄 Retweet + 💬 Comment',
        'like_retweet_comment': '👍 Like + 🔄 Retweet + 💬 Comment'
    };
    return descriptions[type] || 'Unknown mission type';
}
```

#### **D. Verification System**
```javascript
// Twitter API verification (requires Twitter API access)
async function verifyTwitterInteraction(userId, tweetUrl, missionType) {
    try {
        // This would require Twitter API integration
        // For now, we'll implement a manual verification system
        
        // Check if user has joined the mission
        const participant = await queryDb(`
            SELECT * FROM tbl_twitter_mission_participants 
            WHERE user_id = ? AND mission_id = ?
        `, [userId, missionId]);

        if (!participant) {
            return { success: false, reason: 'User not participating in mission' };
        }

        // Manual verification system (admin can verify)
        return { success: true, requiresManualVerification: true };
        
    } catch (error) {
        console.error('[TWITTER MISSION] Verification error:', error);
        return { success: false, reason: 'Verification failed' };
    }
}
```

---

## 🚀 **IMPLEMENTATION STEPS**

### **Step 1: Database Setup**
1. **Create tables** using the SQL schema above
2. **Test database** with sample data
3. **Verify foreign keys** and constraints

### **Step 2: Command Implementation**
1. **Create `/tweet` command** with all parameters
2. **Implement validation** for Twitter URLs
3. **Add permission checks** (mod/admin only)

### **Step 3: Mission Management**
1. **Create mission embeds** with proper formatting
2. **Implement participation buttons** for users
3. **Add expiry handling** with automatic cleanup

### **Step 4: Verification System**
1. **Implement manual verification** (admin can verify users)
2. **Add reward distribution** system
3. **Create verification logs** for audit trail

### **Step 5: Integration Testing**
1. **Test command creation** with different parameters
2. **Verify database operations** work correctly
3. **Test user participation** and reward distribution

---

## 📋 **COMMAND PARAMETERS BREAKDOWN**

### **Required Parameters:**
- **`tweet_url`** - Twitter/X post URL
- **`type`** - Interaction type (like, retweet, comment, combinations)
- **`duration`** - Mission duration in hours
- **`reward`** - DSPOINC reward amount

### **Optional Parameters:**
- **`description`** - Custom mission description
- **`channel`** - Target channel (default: current channel)
- **`role_to_tag`** - Role to mention (default: @everyone)
- **`image_url`** - Accompanying image
- **`start_delay`** - Delay before mission starts
- **`max_participants`** - Maximum number of participants
- **`min_level`** - Minimum user level required
- **`cooldown`** - Cooldown between missions per user

---

## 🔧 **TECHNICAL REQUIREMENTS**

### **Discord.js Features:**
- **Slash Commands** - For `/tweet` command
- **Embeds** - For mission display
- **Buttons** - For user participation
- **Permissions** - For admin-only commands

### **Database Features:**
- **SQLite** - For mission storage
- **Foreign Keys** - For data integrity
- **Timestamps** - For expiry handling
- **Indexes** - For performance

### **External APIs:**
- **Twitter API** - For verification (optional)
- **Manual Verification** - Admin-based verification system

---

## 🎯 **EXAMPLE USAGE**

### **Basic Mission:**
```
/tweet tweet_url:https://x.com/narrrf12345/status/196909378621185658 type:like_retweet_comment duration:48 reward:500 description:"Engage to collect your points!"
```

### **Advanced Mission:**
```
/tweet tweet_url:https://x.com/narrrf12345/status/196909378621185658 type:like_retweet duration:24 reward:250 description:"Friday Beacon Mission" channel:#quests-new role_to_tag:@Rumble
```

---

## 🚨 **SECURITY CONSIDERATIONS**

### **Permission Checks:**
- **Admin/Mod only** - Only authorized users can create missions
- **Rate limiting** - Prevent spam mission creation
- **URL validation** - Ensure valid Twitter URLs only

### **Data Protection:**
- **User privacy** - Don't store sensitive Twitter data
- **Mission cleanup** - Remove expired missions
- **Audit logging** - Track all mission activities

---

## 📊 **SUCCESS METRICS**

### **Technical Metrics:**
- **Mission Creation** - Successful mission creation rate
- **User Participation** - Participation rate per mission
- **Verification Accuracy** - Verification success rate
- **System Uptime** - Mission system availability

### **Community Metrics:**
- **Engagement Rate** - Twitter interaction completion rate
- **Reward Distribution** - DSPOINC distribution accuracy
- **User Satisfaction** - Community feedback on missions
- **Mission Completion** - Overall mission success rate

---

## 🧀 **FINAL IMPLEMENTATION NOTES**

**This Twitter mission system will integrate seamlessly with your existing Discord bot infrastructure, using the same database and reward systems. The implementation focuses on manual verification initially, with the option to add Twitter API integration later for automated verification.**

**The system maintains consistency with your existing mission and reward infrastructure while providing a powerful tool for community engagement through social media integration.**

---

**WORKSHEET CREATED:** September 22, 2025  
**STATUS:** Ready for Implementation  
**PRIORITY:** High - Community Engagement Feature  
**COMPLEXITY:** Medium - Requires Discord.js and database integration  
**ESTIMATED TIME:** 2-3 development sessions
