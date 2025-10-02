# 🎯 Twitter Mission System - Quick Reference Guide

## 📋 **COMMAND STRUCTURE**

### **Basic Command:**
```
/tweet tweet_url:<URL> type:<INTERACTION> duration:<HOURS> reward:<DSPOINC>
```

### **Full Command with Options:**
```
/tweet tweet_url:<URL> type:<INTERACTION> duration:<HOURS> reward:<DSPOINC> description:<TEXT> channel:<#CHANNEL> role:<@ROLE>
```

---

## 🔧 **PARAMETER REFERENCE**

### **Required Parameters:**
| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `tweet_url` | String | Twitter/X post URL | `https://x.com/narrrf12345/status/196909378621185658` |
| `type` | Choice | Required interaction | `like_retweet_comment` |
| `duration` | Integer | Hours until expiry | `48` |
| `reward` | Integer | DSPOINC amount | `500` |

### **Optional Parameters:**
| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `description` | String | Mission description | `"Engage to collect your points!"` |
| `channel` | Channel | Target channel | `#quests-new` |
| `role` | Role | Role to mention | `@Rumble` |
| `image_url` | String | Accompanying image | `https://example.com/image.png` |

---

## 📊 **INTERACTION TYPES**

| Type | Description | Required Actions |
|------|-------------|------------------|
| `like` | Like the tweet | 👍 Like |
| `retweet` | Retweet the tweet | 🔄 Retweet |
| `comment` | Comment on the tweet | 💬 Comment |
| `like_retweet` | Like + Retweet | 👍 Like + 🔄 Retweet |
| `like_comment` | Like + Comment | 👍 Like + 💬 Comment |
| `retweet_comment` | Retweet + Comment | 🔄 Retweet + 💬 Comment |
| `like_retweet_comment` | All actions | 👍 Like + 🔄 Retweet + 💬 Comment |

---

## 🎯 **EXAMPLE COMMANDS**

### **Simple Mission:**
```
/tweet tweet_url:https://x.com/narrrf12345/status/196909378621185658 type:like duration:24 reward:100
```

### **Complex Mission:**
```
/tweet tweet_url:https://x.com/narrrf12345/status/196909378621185658 type:like_retweet_comment duration:48 reward:500 description:"Friday Beacon Mission - Complete all actions!" channel:#quests-new role:@everyone
```

### **Community Event Mission:**
```
/tweet tweet_url:https://x.com/narrrf12345/status/196909378621185658 type:like_retweet duration:72 reward:250 description:"Community engagement mission - Like and retweet to support!" channel:#general-talk role:@Rumble
```

---

## 🔄 **MISSION FLOW**

1. **Admin creates mission** using `/tweet` command
2. **Bot posts mission** in designated channel
3. **Members click "Join Mission"** button
4. **Members complete** Twitter interactions
5. **Admin verifies** completion (manual system)
6. **Bot distributes** DSPOINC rewards
7. **Mission expires** after duration

---

## 📈 **REWARD STRUCTURE**

### **Suggested Rewards by Complexity:**
- **Like only:** 50-100 DSPOINC
- **Retweet only:** 75-150 DSPOINC
- **Comment only:** 100-200 DSPOINC
- **Like + Retweet:** 150-300 DSPOINC
- **Like + Comment:** 200-400 DSPOINC
- **Retweet + Comment:** 200-400 DSPOINC
- **All three actions:** 300-500 DSPOINC

---

## ⚙️ **ADMIN COMMANDS**

### **Mission Management:**
- `/tweet` - Create new mission
- `/mission-list` - List active missions
- `/mission-verify <user> <mission_id>` - Verify user completion
- `/mission-expire <mission_id>` - Expire mission early
- `/mission-stats <mission_id>` - Show mission statistics

---

## 🚨 **BEST PRACTICES**

### **Mission Creation:**
- **Clear descriptions** - Explain what users need to do
- **Reasonable duration** - Give users enough time (24-72 hours)
- **Appropriate rewards** - Match reward to effort required
- **Valid URLs** - Always test Twitter URLs before creating missions

### **Community Management:**
- **Regular missions** - Keep community engaged with regular missions
- **Variety** - Mix different interaction types
- **Timing** - Consider time zones for global community
- **Feedback** - Monitor community response and adjust

---

## 🔧 **TECHNICAL NOTES**

### **Database Tables:**
- `tbl_twitter_missions` - Mission data
- `tbl_twitter_mission_participants` - User participation
- `tbl_user_scores` - DSPOINC rewards (existing)

### **Integration Points:**
- **Discord Bot** - Command handling and embeds
- **Database** - Mission storage and user tracking
- **Reward System** - DSPOINC distribution
- **Admin Interface** - Mission management

---

**QUICK REFERENCE CREATED:** September 22, 2025  
**STATUS:** Ready for Implementation  
**PRIORITY:** High - Community Engagement  
**COMPLEXITY:** Medium - Discord.js + Database integration
