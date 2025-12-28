# 💥 CHEESE RUMBLE - REQUIREMENTS DOCUMENT

**Date:** December 3, 2025  
**Status:** 🔄 **REQUIREMENTS GATHERED - AWAITING GAMEPLAY EXAMPLE**

---

## ✅ **CONFIRMED REQUIREMENTS**

### **1. Setup & Configuration (Same as Cheese Race)**
All options from Cheese Race should be implemented:

- ✅ **Rewards System** - DSPOINC reward amount
- ✅ **Duration** - Time limit for the rumble
- ✅ **Comment** - Optional description/comment
- ✅ **Tag Role** - Optional role to mention
- ✅ **Auto Start** - Auto-start when full (boolean)
- ✅ **Start Time** - Optional scheduled start time
- ✅ **Max Players** - Maximum participants
- ✅ **Role Reward** - Optional role reward for winner

### **2. Join System (Same as Cheese Race)**
- ✅ Members/mice can join via Discord button
- ✅ Join Race button in Discord embed
- ✅ Leave Race button (before start)
- ✅ View participants button
- ✅ Real-time participant list updates

### **3. Database Structure (Same as Cheese Race)**
- ✅ Similar table structure to Cheese Race
- ✅ `tbl_cheese_rumbles` - Main rumble events
- ✅ `tbl_rumble_participants` - Participant data
- ✅ Same field patterns and relationships

### **4. Score Adjustments (Auto-Made Like Cheese Race)**
- ✅ Automatic DSPOINC rewards to winner
- ✅ Score adjustment logged in `tbl_score_adjustments`
- ✅ Balance updated in `tbl_user_scores`
- ✅ Same reward flow as Cheese Race

### **5. Discord Bot Management**
- ✅ Managed from local Discord bot
- ✅ Same architecture as Cheese Race
- ✅ Persistent across bot restarts
- ✅ Recovery mechanisms

### **6. Future Integration**
- ⏳ **NOT YET** - Will become 6th game in website profile
- ⏳ **PLANNED** - Future integration like other 5 games
- ✅ For now, Discord-only gameplay

---

## 🔄 **DIFFERENCE: GAMEPLAY MECHANICS**

### **What's Different:**
- ❓ **Gameplay will run differently** than Cheese Race
- ❓ **Specific mechanics TBD** - Awaiting gameplay example
- ✅ Same setup, join, and reward systems
- ✅ Different in-game competition format

---

## 📋 **IMPLEMENTATION PLAN (To Be Finalized)**

### **Phase 1: Database Schema**
- Create `tbl_cheese_rumbles` table
- Create `tbl_rumble_participants` table
- Mirror Cheese Race structure with Rumble-specific fields

### **Phase 2: Discord Command**
- Create `/cheese-rumble create` command
- Same parameters as `/cheese-race create`
- Add any Rumble-specific options if needed

### **Phase 3: Button System**
- Join Rumble button
- Leave Rumble button
- Start Rumble button (if not auto-start)
- Cancel Rumble button
- View Participants button

### **Phase 4: Gameplay Integration**
- **AWAITING GAMEPLAY EXAMPLE**
- Will determine how gameplay differs from Cheese Race
- Integration with game mechanics
- Progress tracking system

### **Phase 5: Reward System**
- Winner determination logic
- Automatic DSPOINC rewards
- Score adjustment logging
- Optional role rewards

### **Phase 6: Persistence & Recovery**
- Database loading on bot startup
- Active rumble restoration
- Timer recovery
- State management

---

## 🎮 **GAMEPLAY EXAMPLE**

**Status:** ⏳ **AWAITING USER'S EXAMPLE**

Once the gameplay example is provided, we will:
1. Analyze the gameplay mechanics
2. Design the integration approach
3. Create detailed implementation plan
4. Begin development

---

## 📊 **COMPARISON: Cheese Race vs Cheese Rumble**

| Feature | Cheese Race | Cheese Rumble |
|---------|-------------|---------------|
| **Setup Options** | ✅ All options | ✅ Same options |
| **Join System** | ✅ Discord buttons | ✅ Same system |
| **Database** | ✅ `tbl_cheese_races` | ✅ `tbl_cheese_rumbles` |
| **Participants** | ✅ `tbl_race_participants` | ✅ `tbl_rumble_participants` |
| **Rewards** | ✅ Auto DSPOINC | ✅ Same system |
| **Score Adjustments** | ✅ Auto logging | ✅ Same system |
| **Game Integration** | ✅ Snake game | ❓ TBD (awaiting example) |
| **Gameplay** | Race format | ❓ Different format |
| **Website Profile** | ✅ 5th game | ⏳ Future 6th game |

---

## 🚀 **NEXT STEPS**

1. ⏳ **WAIT** - User will show gameplay example
2. 🔍 **ANALYZE** - Review gameplay mechanics
3. 📋 **DESIGN** - Create detailed implementation plan
4. 🏗️ **BUILD** - Start implementation

---

## 📝 **NOTES**

- All setup/configuration options match Cheese Race exactly
- Database structure will mirror Cheese Race with Rumble naming
- Reward system will work identically to Cheese Race
- Gameplay mechanics are the only difference
- Website profile integration is planned but not immediate priority
- Will be managed entirely through Discord bot initially

---

**Status:** ✅ **REQUIREMENTS DOCUMENTED - READY FOR GAMEPLAY EXAMPLE**  
**Next:** Review gameplay example and create implementation plan

