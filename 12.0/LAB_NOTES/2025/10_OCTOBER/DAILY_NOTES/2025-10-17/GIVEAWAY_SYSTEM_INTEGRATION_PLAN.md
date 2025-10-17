# 🎁 GIVEAWAY SYSTEM DATABASE SCHEMA

## **Core Tables Needed:**

### **1. tbl_giveaways**
```sql
CREATE TABLE tbl_giveaways (
    giveaway_id TEXT PRIMARY KEY,
    creator_id TEXT NOT NULL,
    creator_name TEXT NOT NULL,
    channel_id TEXT NOT NULL,
    prize TEXT NOT NULL,
    winner_count INTEGER DEFAULT 1,
    duration_minutes INTEGER NOT NULL,
    status TEXT DEFAULT 'active', -- 'active', 'ended', 'cancelled'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ends_at DATETIME NOT NULL,
    message_id TEXT,
    comment TEXT,
    role_requirement TEXT, -- Optional role requirement
    FOREIGN KEY (creator_id) REFERENCES tbl_users(discord_id)
);
```

### **2. tbl_giveaway_participants**
```sql
CREATE TABLE tbl_giveaway_participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    giveaway_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    username TEXT NOT NULL,
    entry_count INTEGER DEFAULT 1,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (giveaway_id) REFERENCES tbl_giveaways(giveaway_id),
    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id),
    UNIQUE(giveaway_id, user_id)
);
```

### **3. tbl_giveaway_winners**
```sql
CREATE TABLE tbl_giveaway_winners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    giveaway_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    username TEXT NOT NULL,
    position INTEGER NOT NULL, -- 1st, 2nd, 3rd place
    won_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (giveaway_id) REFERENCES tbl_giveaways(giveaway_id),
    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id)
);
```

---

## 🎯 **COMMAND STRUCTURE DESIGN**

### **Admin Giveaway Command:**
```
/giveaway create
├── prize: "1 wl role" (required)
├── winners: 1-10 (default: 1)
├── duration: 5-10080 minutes (5min to 7 days)
├── role_requirement: @role (optional)
├── comment: "Special event giveaway!" (optional)
└── channel: #giveaways (optional, defaults to current)
```

### **User Commands:**
```
/giveaway join <giveaway_id>     # Join giveaway
/giveaway list                   # List active giveaways
/giveaway participants <id>      # View participants (admin only)
/giveaway reroll <giveaway_id>  # Reroll winner (admin only)
```

---

## 🎨 **DISCORD EMBED DESIGN**

### **Giveaway Announcement Embed:**
```
🎁 GIVEAWAY: 1 wl role
👥 Winners: 1
⏰ Ends: in 1 day (Timer)
🥳 4 participants

[🥳 Join Giveaway] [👥 Participants]
```

### **Entry Confirmation (Ephemeral):**
```
✅ Entry Confirmed!
Your entry for the giveaway of **1 wl role** is confirmed!
Please help me by voting 🗳️
```

### **Participants List (Ephemeral):**
```
👥 Giveaway Participants
These are the members that have participated in the giveaway of **1 wl role**:

1. @JabbaTheCrypto (1 entry)
2. @Legend 56 (1 entry)
3. @Mohammad (1 entry)
4. @Narrrf AT (1 entry)

Total Participants: 4
[Show User Tags]
```

---

## 🔧 **TECHNICAL IMPLEMENTATION PLAN**

### **Phase 1: Database Setup**
1. Create giveaway tables
2. Add indexes for performance
3. Test database operations

### **Phase 2: Core Command Structure**
1. `/giveaway create` command
2. Basic embed creation
3. Database integration

### **Phase 3: Participant System**
1. Button-based entry
2. Participant tracking
3. Entry confirmation messages

### **Phase 4: Winner Selection**
1. Random draw algorithm
2. Winner announcement
3. Reroll functionality

### **Phase 5: Advanced Features**
1. Participant list viewing
2. Role requirements
3. Multiple winners
4. Timer management

---

## 🎲 **RANDOM DRAW ALGORITHM**

```javascript
function selectWinners(participants, winnerCount) {
    const weightedParticipants = [];
    
    // Create weighted array based on entry count
    participants.forEach(participant => {
        for (let i = 0; i < participant.entry_count; i++) {
            weightedParticipants.push(participant);
        }
    });
    
    // Shuffle and select unique winners
    const shuffled = weightedParticipants.sort(() => Math.random() - 0.5);
    const winners = [];
    const usedIds = new Set();
    
    for (let i = 0; i < shuffled.length && winners.length < winnerCount; i++) {
        if (!usedIds.has(shuffled[i].user_id)) {
            winners.push(shuffled[i]);
            usedIds.add(shuffled[i].user_id);
        }
    }
    
    return winners;
}
```

---

## 📁 **FILE STRUCTURE**

```
discord/commands/
├── giveaway.js              # Main giveaway command
├── giveaway-admin.js        # Admin giveaway management
└── giveaway-handlers.js     # Button/interaction handlers

discord/utils/
├── giveawayEmbed.js         # Embed creation utilities
├── giveawayDraw.js          # Winner selection logic
└── giveawayDatabase.js      # Database operations
```

---

## 🚀 **INTEGRATION WITH EXISTING BOT**

### **Advantages:**
- ✅ Uses existing database connection (`queryDb`)
- ✅ Follows existing command patterns
- ✅ Integrates with user management system
- ✅ Uses existing embed styling

### **Integration Points:**
- **Database:** Extends existing `tbl_users` table
- **Commands:** Follows existing slash command structure
- **Embeds:** Uses existing `EmbedBuilder` patterns
- **Buttons:** Uses existing button interaction system

---

## ⏱️ **DEVELOPMENT TIMELINE**

### **Day 1: Foundation**
- Database schema creation
- Basic `/giveaway create` command
- Simple embed display

### **Day 2: Participant System**
- Button-based entry
- Participant tracking
- Entry confirmation

### **Day 3: Winner Selection**
- Random draw algorithm
- Winner announcement
- Basic reroll functionality

### **Day 4: Advanced Features**
- Participant list viewing
- Role requirements
- Timer management

---

## 🎯 **SUCCESS METRICS**

### **Core Functionality:**
- ✅ Create giveaways with custom prizes
- ✅ Users can join via button click
- ✅ Random winner selection
- ✅ Participant list viewing
- ✅ Reroll functionality

### **Advanced Features:**
- ✅ Role-based requirements
- ✅ Multiple winners
- ✅ Custom durations
- ✅ Admin management tools

---

**Ready to start implementing? This will be a fantastic addition to your bot!** 🎁✨
