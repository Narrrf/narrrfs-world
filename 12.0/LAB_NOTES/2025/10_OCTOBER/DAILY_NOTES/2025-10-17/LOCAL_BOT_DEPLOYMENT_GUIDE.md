# 🤖 LOCAL BOT DEPLOYMENT GUIDE - OCTOBER 17, 2025

**Date:** October 17, 2025  
**Time:** 17:30  
**Purpose:** Deploy cheese race enhancements to local bot  

---

## ✅ **WHAT WAS CHANGED**

**File Modified:** `discord/commands/cheese-race.js`

**Enhancements:**
1. ⏰ **Extended race scheduling** - Up to 48 hours in advance (2880 minutes)
2. 👥 **Increased max players** - From 25 to 50 players
3. 🏆 **All-time leaderboard** - Shows top 10 champions after each race

---

## 🚀 **HOW TO DEPLOY (LOCAL BOT)**

### **Step 1: Restart Your Bot**

**If your bot is currently running:**
1. Go to your bot terminal/command prompt
2. Press **Ctrl+C** to stop the bot
3. Restart with your usual command (e.g., `node index.js` or `npm start`)
4. Wait for bot to connect to Discord
5. ✅ **Done!** The new features are now active

**If using nodemon or PM2:**
- The bot should auto-restart when it detects the file change
- Check bot console for restart confirmation
- Verify bot status in Discord

---

## 🧪 **TESTING THE NEW FEATURES**

### **Test 1: Create a 50-Player Race** 👥
```
/cheese-race start max_players:50 race_duration:120 dspoinc_reward:5000
```
**Expected:**
- Race accepts up to 50 players (was 25 before)
- Race runs for 2 minutes
- Winner gets 5000 $DSPOINC

### **Test 2: Schedule Race for Later** ⏰
```
/cheese-race start when_start:60 max_players:10 race_duration:180
```
**Expected:**
- Race scheduled to start in 60 minutes
- Allows 10 players
- Runs for 3 minutes when started

### **Test 3: Schedule 24-Hour Race** 🗓️
```
/cheese-race start when_start:1440 max_players:30 race_duration:300
```
**Expected:**
- Race scheduled to start in 24 hours (1440 minutes)
- Allows 30 players
- Runs for 5 minutes when started

### **Test 4: Leaderboard Display** 🏆
1. Complete any race
2. Wait for race to finish
3. Winner announcement appears
4. **NEW:** All-time leaderboard appears showing:
   - 🥇🥈🥉 Top 10 race champions
   - Total wins per player
   - Total races participated
   - Total DSPOINC earned
   - Win percentage

---

## 📋 **VERIFICATION CHECKLIST**

### **After Bot Restart:**
- [ ] Bot shows online in Discord
- [ ] No errors in bot console
- [ ] `/cheese-race` command appears in Discord
- [ ] Command options show new limits

### **Feature Testing:**
- [ ] Can create race with 50 max players
- [ ] Can schedule race 24+ hours ahead
- [ ] Leaderboard displays after race completion
- [ ] Leaderboard shows accurate statistics

---

## 🎮 **EXAMPLE COMMANDS**

### **Quick Short Race (5 players, 2 minutes):**
```
/cheese-race start max_players:5 race_duration:120 dspoinc_reward:1000
```

### **Large Event Race (50 players, 5 minutes):**
```
/cheese-race start max_players:50 race_duration:300 dspoinc_reward:10000
```

### **Scheduled Weekend Race (starts in 48h, 30 players):**
```
/cheese-race start when_start:2880 max_players:30 race_duration:300 dspoinc_reward:5000
```

---

## 🔍 **WHAT TO WATCH FOR**

### **Success Indicators:**
- ✅ Race accepts up to 50 players
- ✅ when_start parameter accepts up to 2880 minutes
- ✅ After race ends, leaderboard message appears
- ✅ Leaderboard shows accurate statistics
- ✅ No console errors

### **Potential Issues:**
- ❌ Bot fails to restart (check console for errors)
- ❌ Database query errors (check if `tbl_race_participants` exists)
- ❌ Leaderboard not displaying (check bot logs)

---

## 🎯 **CURRENT FEATURES**

### **Race Scheduling:**
- **Min:** 1 minute ahead
- **Max:** 2880 minutes (48 hours) ahead
- **Example:** Schedule Friday race on Wednesday

### **Player Capacity:**
- **Min:** 2 players
- **Max:** 50 players (increased from 25)
- **Default:** 30 players

### **Race Duration:**
- **Min:** 30 seconds
- **Max:** 300 seconds (5 minutes)
- **Note:** This is the actual race time, not scheduling time

### **Leaderboard:**
- Displays after every race
- Shows top 10 all-time champions
- Statistics: wins, total races, DSPOINC earned, win %
- Updates automatically from database

---

## 🏆 **EXPECTED LEADERBOARD OUTPUT**

After a race finishes, you'll see:

```
🏆 ALL-TIME RACE CHAMPIONS 🏆
Overall Race Leaderboard Across All Races

🎯 Top Race Champions

🥇 Santa
   └ 🏆 15 wins • 🎮 20 races • 💰 45,000 $DSPOINC • 📊 75.0% win rate

🥈 CryptoDaniel
   └ 🏆 12 wins • 🎮 18 races • 💰 38,000 $DSPOINC • 📊 66.7% win rate

🥉 RacerPro
   └ 🏆 8 wins • 🎮 15 races • 💰 25,000 $DSPOINC • 📊 53.3% win rate

... (up to top 10)
```

---

## 🚨 **TROUBLESHOOTING**

### **Bot Won't Start:**
- Check for syntax errors in console
- Verify Node.js is running
- Check Discord token is valid

### **Leaderboard Not Showing:**
- Check bot console for errors
- Verify `tbl_race_participants` table exists
- Ensure races have `status = 'completed'` in database

### **Commands Not Updating:**
- Discord caches commands for up to 1 hour
- Either wait 1 hour for cache to clear
- Or use `/` in Discord to see if limits show correctly

---

## ✅ **DEPLOYMENT COMPLETE WHEN:**

- ✅ Bot restarts successfully
- ✅ No console errors
- ✅ Can create races with 50 players
- ✅ Can schedule races 24-48 hours ahead
- ✅ Leaderboard appears after races

---

**DEPLOYMENT GUIDE CREATED:** 2025-10-17 17:30  
**STATUS:** ✅ **READY FOR LOCAL BOT RESTART**  
**NEXT:** 🔄 **RESTART BOT AND TEST**  

**🧀 JUST RESTART YOUR LOCAL BOT AND YOU'RE GOOD TO GO! 🧀**

