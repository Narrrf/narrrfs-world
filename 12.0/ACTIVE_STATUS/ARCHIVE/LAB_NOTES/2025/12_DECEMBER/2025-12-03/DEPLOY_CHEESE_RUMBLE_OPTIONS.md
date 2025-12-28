# 🚀 DEPLOY CHEESE RUMBLE NEW OPTIONS

**Date:** December 3, 2025  
**Status:** ✅ **READY TO DEPLOY**

---

## ✅ **CHANGES COMPLETED**

Both missing options have been added:
- ✅ `/start_in` option (1-10080 minutes = 1 week)
- ✅ `/category` option (dropdown with 6 choices)

---

## 📋 **DEPLOYMENT STEPS**

### **Step 1: Deploy Commands to Discord**

Navigate to the discord directory and run:
```bash
cd discord
node deploy-commands.js
```

**Expected output:**
```
[CHEESE RACE] Initializing activeRaces Map...
[CHEESE RUMBLE] Initializing activeRumbles Map...
Started refreshing 47 application (/) commands.
Successfully reloaded 47 application (/) commands.
```

### **Step 2: Restart Bot (if needed)**

If the bot is running, restart it to ensure all changes are loaded:
```bash
npm start
```

---

## ✅ **VERIFY IN DISCORD**

After deploying, the new options should appear in Discord:

### **Test Command:**
```
/cheese-rumble create players:10 duration:300 reward:5000 start_in:5 category:kills
```

### **Expected Options:**
- ✅ `players` - Integer (2-50)
- ✅ `duration` - Integer (30-3600)
- ✅ `reward` - Integer (100-1000000)
- ✅ `autostart` - Boolean
- ✅ **`start_in`** - Integer (1-10080) ← NEW
- ✅ **`category`** - Choice dropdown ← NEW
  - 🎲 Random (All Categories)
  - ⚔️ Kills Only
  - 💀 Self-Eliminations Only
  - ✨ Special Events Only
  - 🌍 Environmental Events Only
  - 🎯 All Categories Mixed
- ✅ `role_reward` - Role
- ✅ `tag_role` - Role
- ✅ `comment` - String

---

## 🎯 **TESTING**

1. **Test `/start_in` option:**
   - Create rumble with `start_in:5`
   - Should auto-start after 5 minutes
   - Should send notification when starting

2. **Test `/category` option:**
   - Create rumble with `category:kills`
   - All events should be kill events only
   - Test with other categories

---

## 📝 **NOTES**

- **Category Filter:** Only affects event generation during rounds
- **Auto-Start Timer:** Only triggers if rumble is still in 'waiting' status
- **Persistence:** Currently stored in memory (consider database columns for full persistence)

---

**Status:** ✅ **READY** - Deploy commands and test! 🚀

