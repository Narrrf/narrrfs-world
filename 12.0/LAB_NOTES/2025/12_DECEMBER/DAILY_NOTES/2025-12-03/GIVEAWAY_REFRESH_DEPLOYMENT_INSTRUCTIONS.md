# 🔄 GIVEAWAY REFRESH COMMAND - DEPLOYMENT INSTRUCTIONS

**Date:** December 3, 2025  
**Command:** `/giveaway refresh`  
**Status:** ✅ **READY TO DEPLOY**

---

## 🚀 DEPLOYMENT STEPS

### **Step 1: Deploy Commands to Discord**
```bash
cd discord
node deploy-commands.js
```

**Or using npm:**
```bash
cd discord
npm run deploy
```

**Expected Output:**
```
Started refreshing X application (/) commands.
Successfully reloaded X application (/) commands.
```

**What This Does:**
- Registers the new `/giveaway refresh` subcommand with Discord
- Updates all commands in your Discord server
- Takes ~5-10 seconds

---

### **Step 2: Restart Discord Bot**
**If bot is running, stop it first (Ctrl+C), then:**

```bash
cd discord
npm start
```

**Or:**
```bash
cd discord
node index.js
```

**Expected Output:**
```
🚀 Narrrf's World Bot is ready as BotName#1234
✅ Connected, users: X
✅ Races loaded from database
✅ Giveaways loaded (manual recovery available via /recover-giveaways)
[GIVEAWAY] Loading active giveaways from database...
[GIVEAWAY] Found 1 active giveaways
[GIVEAWAY] Restored giveaway giveaway_1764782608091_6h22dwdb5, ends in X seconds
[GIVEAWAY] ✅ Refreshed message embed for giveaway_1764782608091_6h22dwdb5
✅ Giveaways loaded (manual recovery available via /recover-giveaways)
```

---

## ✅ VERIFICATION

### **1. Check Command is Available:**
1. Open Discord
2. Type `/giveaway` in any channel
3. You should see **"refresh"** as an option
4. Command should show: `/giveaway refresh giveaway_id:`

### **2. Check Automatic Refresh:**
1. Look at bot console logs
2. Should see: `[GIVEAWAY] ✅ Refreshed message embed for giveaway_1764782608091_6h22dwdb5`
3. Check Discord message - should now show ~94 hours remaining

### **3. Test Manual Refresh:**
1. Run: `/giveaway refresh giveaway_id:giveaway_1764782608091_6h22dwdb5`
2. Should see confirmation with new end time
3. Message embed should update immediately

---

## 🎯 QUICK REFERENCE

### **Deploy Commands:**
```bash
cd discord && node deploy-commands.js
```

### **Start Bot:**
```bash
cd discord && npm start
```

### **Restart Bot:**
1. Stop: `Ctrl+C` (if running)
2. Start: `cd discord && npm start`

---

## 📝 NOTES

### **Why Deploy is Needed:**
- Discord slash commands must be registered with Discord's API
- New subcommands won't appear until deployed
- Takes effect immediately after deployment (no bot restart needed for command registration)
- Bot restart is needed for automatic message refresh on startup

### **Command Location:**
- **Deploy Script:** `discord/deploy-commands.js`
- **Command File:** `discord/commands/giveaway.js`
- **New Subcommand:** Lines 192-199
- **Handler Function:** Lines 892-949

---

**Lab Note Created:** December 3, 2025  
**Status:** ✅ **READY TO DEPLOY**  
**Next:** Deploy commands, then restart bot

